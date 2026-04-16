require_once 'config/database.php';
// Payroll is typically handled by Accountants or Station Receptionists
requirePermission('reception');

// Fetch all active employees
$employees = $pdo->query("SELECT * FROM employee WHERE is_active = 1")->fetchAll();
$total_payroll = 0;
foreach($employees as $e) { $total_payroll += 500000; } // Assuming flat rate for now or fetch from some contract table if exists

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_payroll'])) {
    $notes = $_POST['notes'];
    $accountant_id = $_SESSION['user_id']; // The one submitting

    try {
        $stmt = $pdo->prepare("INSERT INTO payroll_submissions (station_id, accountant_id, total_amount, status, notes) VALUES (?, ?, ?, 'Pending', ?)");
        $stmt->execute([1, $accountant_id, $total_payroll, $notes]);
        $_SESSION['success'] = "Payroll submission for " . count($employees) . " employees sent to Admin for approval.";
        header("Location: receptionist_dashboard.php");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-calculator text-success me-2"></i> Payroll Generation</h2>
        <p class="text-muted">Compute monthly salaries and submit for Administrative certification.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm" style="border-radius: 15px;">
            <div class="card-header bg-success text-white py-3">
                <h5 class="card-title mb-0 fw-bold">Current Month Payroll Summary</h5>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info border-0 shadow-sm rounded-4">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    This payroll is generated based on all currently **Active** employee records.
                </div>
                
                <div class="table-responsive mb-4">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Employee</th>
                                <th>Position</th>
                                <th class="text-end">Base Salary (Est.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></div>
                                    <small class="text-muted">ID: #<?php echo $emp['employee_id']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($emp['position']); ?></td>
                                <td class="text-end fw-bold">RWF 500,000</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-light">
                            <tr class="h5">
                                <td colspan="2" class="fw-bold">Total Payroll Amount:</td>
                                <td class="text-end text-success fw-bold">RWF <?php echo number_format($total_payroll, 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Submission Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Add details about overtime, bonuses, or deductions..." required></textarea>
                    </div>
                    
                    <button type="submit" name="submit_payroll" class="btn btn-success btn-lg w-100 rounded-pill shadow-sm py-3 fw-bold">
                        <i class="bi bi-send-check-fill me-2"></i> Submit to Admin & Accountant
                    </button>
                </form>
            </div>
            <div class="card-footer bg-light border-0 py-3 text-center">
                <span class="text-muted small"><i class="bi bi-shield-check me-1"></i> Final disbursement requires Admin digital signature.</span>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
