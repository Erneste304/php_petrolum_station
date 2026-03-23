<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

requireAdmin();

// Handle Approval/Rejection
if (isset($_POST['update_status'])) {
    $sub_id = $_POST['submission_id'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    
    $stmt = $pdo->prepare("UPDATE payroll_submissions SET status = ?, notes = ? WHERE submission_id = ?");
    $stmt->execute([$status, $notes, $sub_id]);
    
    if ($status === 'Approved') {
        // In a real system, trigger actual bank transfer or marking employees as paid
        $_SESSION['success'] = "Payroll submission #$sub_id has been approved.";
    } else {
        $_SESSION['error'] = "Payroll submission #$sub_id has been rejected/stopped.";
    }
    header("Location: admin_payroll_approval.php");
    exit();
}

// Fetch pending submissions
$submissions = $pdo->query("
    SELECT p.*, s.station_name, u.username as accountant_name
    FROM payroll_submissions p
    JOIN station s ON p.station_id = s.station_id
    JOIN users u ON p.accountant_id = u.user_id
    ORDER BY p.created_at DESC
")->fetchAll();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-shield-check text-primary me-2"></i> Payroll Approval</h2>
        <p class="text-muted">Review and authorize payroll requests from accountants.</p>
    </div>
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th>Ref #</th>
                    <th>Station</th>
                    <th>Accountant</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $sub): ?>
                <tr>
                    <td>#<?php echo $sub['submission_id']; ?></td>
                    <td><div class="fw-bold"><?php echo htmlspecialchars($sub['station_name']); ?></div></td>
                    <td><?php echo htmlspecialchars($sub['accountant_name']); ?></td>
                    <td class="fw-bold text-dark">RWF <?php echo number_format($sub['total_amount'], 0); ?></td>
                    <td><?php echo date('M d, Y H:i', strtotime($sub['created_at'])); ?></td>
                    <td>
                        <?php if ($sub['status'] === 'Pending'): ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill">Pending</span>
                        <?php elseif ($sub['status'] === 'Approved'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">Approved</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">Rejected</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($sub['status'] === 'Pending'): ?>
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#reviewModal<?php echo $sub['submission_id']; ?>">
                            Review
                        </button>
                        
                        <!-- Review Modal -->
                        <div class="modal fade" id="reviewModal<?php echo $sub['submission_id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Review Payroll #<?php echo $sub['submission_id']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="submission_id" value="<?php echo $sub['submission_id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Station</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($sub['station_name']); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Total Amount</label>
                                                <input type="text" class="form-control" value="RWF <?php echo number_format($sub['total_amount'], 0); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Decision</label>
                                                <select name="status" class="form-select" required>
                                                    <option value="Approved">Approve Payment</option>
                                                    <option value="Rejected">Reject/Stop Transaction</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Admin Notes</label>
                                                <textarea name="notes" class="form-control" rows="3" placeholder="Add reason for approval/rejection..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" name="update_status" class="btn btn-primary">Process Decision</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                            <span class="text-muted small"><?php echo htmlspecialchars($sub['notes'] ?: 'No notes'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($submissions)): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">No payroll submissions found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
