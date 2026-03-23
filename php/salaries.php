<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

if (!isAdmin() && !isFinancePermitted('Salaries')) {
    $_SESSION['error'] = "Access denied. Action requires Admin-granted financial permission.";
    header("Location: accountant_dashboard.php");
    exit();
}

$selected_station_id = $_GET['station_id'] ?? null;

// Handle Employee Activation Toggle
if (isset($_POST['toggle_active'])) {
    $emp_id = $_POST['employee_id'];
    $new_status = $_POST['is_active'] ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE employee SET is_active = ? WHERE employee_id = ?");
    $stmt->execute([$new_status, $emp_id]);
    $_SESSION['success'] = "Employee status updated.";
    header("Location: salaries.php?station_id=" . $selected_station_id);
    exit();
}

// Handle Payroll Submission
if (isset($_POST['submit_payroll'])) {
    $station_id = $_POST['station_id'];
    $total_amount = $_POST['total_amount'];
    
    // Check if all employees in this station are active
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee WHERE station_id = ? AND is_active = 0");
    $stmt->execute([$station_id]);
    $inactive_count = $stmt->fetchColumn();
    
    if ($inactive_count > 0) {
        $_SESSION['error'] = "Cannot submit payroll. There are $inactive_count inactive/flagged workers. please resolve issues first.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO payroll_submissions (station_id, accountant_id, total_amount, status) VALUES (?, ?, ?, 'Pending')");
        $stmt->execute([$station_id, $_SESSION['user_id'], $total_amount]);
        $_SESSION['success'] = "Payroll for RWF " . number_format($total_amount, 0) . " submitted for Admin approval.";
    }
    header("Location: salaries.php?station_id=" . $station_id);
    exit();
}

// Handle Claim Salary Upload
if (isset($_POST['claim_salary'])) {
    $emp_id = $_POST['employee_id'];
    $target_dir = "uploads/claims/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_name = time() . '_' . basename($_FILES["claim_letter"]["name"]);
    $target_file = $target_dir . $file_name;
    
    if (move_uploaded_file($_FILES["claim_letter"]["tmp_name"], $target_file)) {
        $stmt = $pdo->prepare("UPDATE employee SET claim_letter = ?, is_approved = 0 WHERE employee_id = ?");
        $stmt->execute([$file_name, $emp_id]);
        $_SESSION['success'] = "Claim letter uploaded. Awaiting Admin verification.";
    } else {
        $_SESSION['error'] = "Failed to upload claim letter.";
    }
    header("Location: salaries.php?station_id=" . $selected_station_id);
    exit();
}

// Fetch Stations
$stations = $pdo->query("SELECT * FROM station ORDER BY station_name")->fetchAll();

// Fetch Employees if station is selected
$employees = [];
if ($selected_station_id) {
    $stmt = $pdo->prepare("SELECT * FROM employee WHERE station_id = ? ORDER BY first_name");
    $stmt->execute([$selected_station_id]);
    $employees = $stmt->fetchAll();
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-person-check text-success me-2"></i> Payroll Management</h2>
        <p class="text-muted">Process staff salaries by station and manage financial reports.</p>
    </div>
    <div class="col-auto">
        <a href="accountant_dashboard.php" class="btn btn-secondary rounded-pill">
            <i class="bi bi-arrow-left"></i> Dashboard
        </a>
    </div>
</div>

<!-- Station Selection -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <h5 class="card-title mb-3">Select Business/Station</h5>
        <div class="row g-2">
            <?php foreach ($stations as $station): ?>
            <div class="col-md-3">
                <a href="salaries.php?station_id=<?php echo $station['station_id']; ?>" 
                   class="btn <?php echo $selected_station_id == $station['station_id'] ? 'btn-primary' : 'btn-outline-primary'; ?> w-100">
                    <i class="bi bi-building"></i> <?php echo htmlspecialchars($station['station_name']); ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($selected_station_id): ?>
<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Staff List - <?php 
                    foreach($stations as $s) if($s['station_id'] == $selected_station_id) echo htmlspecialchars($s['station_name']);
                ?></h5>
                <?php 
                $total_payroll = 0;
                $all_active = true;
                foreach($employees as $e) {
                    if($e['is_active']) $total_payroll += 50000; // Sample default salary
                    else $all_active = false;
                }
                ?>
                <form method="POST">
                    <input type="hidden" name="station_id" value="<?php echo $selected_station_id; ?>">
                    <input type="hidden" name="total_amount" value="<?php echo $total_payroll; ?>">
                    <button type="submit" name="submit_payroll" class="btn btn-success rounded-pill fw-bold" 
                            <?php echo (!$all_active || empty($employees)) ? 'disabled' : ''; ?>
                            onclick="return confirm('Submit total payroll of RWF <?php echo number_format($total_payroll, 0); ?> for approval?')">
                        <i class="bi bi-send-check me-1"></i> Submit All Payroll
                    </button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th>Employee</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                            <th>Claims/Letters</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-light p-2 rounded-circle me-3">
                                        <i class="bi bi-person text-primary"></i>
                                    </div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($emp['position']); ?></td>
                            <td>
                                <?php if ($emp['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">Flagged/Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="employee_id" value="<?php echo $emp['employee_id']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $emp['is_active']; ?>">
                                    <button type="submit" name="toggle_active" class="btn btn-sm <?php echo $emp['is_active'] ? 'btn-outline-danger' : 'btn-outline-success'; ?> rounded-pill">
                                        <?php echo $emp['is_active'] ? '<i class="bi bi-pause-fill"></i> Flag/Stop' : '<i class="bi bi-play-fill"></i> Activate'; ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <?php if (!$emp['is_active']): ?>
                                <button type="button" class="btn btn-sm btn-link text-primary" data-bs-toggle="modal" data-bs-target="#claimModal<?php echo $emp['employee_id']; ?>">
                                    <i class="bi bi-file-earmark-arrow-up"></i> Upload Claim
                                </button>
                                
                                <!-- Claim Modal -->
                                <div class="modal fade" id="claimModal<?php echo $emp['employee_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Submit Salary Claim - <?php echo htmlspecialchars($emp['first_name']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" enctype="multipart/form-data">
                                                <div class="modal-body">
                                                    <input type="hidden" name="employee_id" value="<?php echo $emp['employee_id']; ?>">
                                                    <p class="small text-muted mb-3">Please upload the written letter explaining the salary claim for this worker.</p>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Claim Letter (PDF/JPG)</label>
                                                        <input type="file" name="claim_letter" class="form-control" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-modal="dismiss">Cancel</button>
                                                    <button type="submit" name="claim_salary" class="btn btn-primary">Submit Claim</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($emp['claim_letter']): ?>
                                <a href="uploads/claims/<?php echo $emp['claim_letter']; ?>" target="_blank" class="btn btn-sm btn-outline-info rounded-pill">
                                    <i class="bi bi-file-earmark-text"></i> View Letter
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No staff members found for this station.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="text-center py-5">
    <i class="bi bi-building fs-1 text-muted opacity-25 d-block mb-3"></i>
    <h5 class="text-muted">Please select a station above to manage payroll.</h5>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
