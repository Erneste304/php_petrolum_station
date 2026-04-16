<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

if (!isAdmin() && !isStaff()) {
    $_SESSION['error'] = "Access denied.";
    header("Location: index.php");
    exit();
}

// Handle Approval/Rejection
if (isset($_POST['update_status'])) {
    $sub_id = $_POST['submission_id'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    
    // Stage logic: Staff marks as 'Staff Approved', Admin marks as 'Approved' (Final)
    if ($status === 'Approved' && !isAdmin()) {
        $status = 'Staff Approved';
    }

    $stmt = $pdo->prepare("UPDATE payroll_submissions SET status = ?, notes = ? WHERE submission_id = ?");
    $stmt->execute([$status, $notes, $sub_id]);
    
    if ($status === 'Approved') {
        $_SESSION['success'] = "Payroll submission #$sub_id has been finalized by Admin.";
    } elseif ($status === 'Staff Approved') {
        $_SESSION['success'] = "Payroll submission #$sub_id has been verified by Supervisor.";
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
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill">Awaiting Supervisor</span>
                        <?php elseif ($sub['status'] === 'Staff Approved'): ?>
                            <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 rounded-pill">Staff Verified</span>
                        <?php elseif ($sub['status'] === 'Approved'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">Finalized</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">Rejected</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        $can_review = ($sub['status'] === 'Pending' && (isStaff() || isAdmin())) || ($sub['status'] === 'Staff Approved' && isAdmin());
                        if ($can_review): 
                        ?>
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#reviewModal<?php echo $sub['submission_id']; ?>">
                            <i class="bi bi-search me-1"></i> Review
                        </button>
                        <?php else: ?>
                            <span class="text-muted small italic"><?php echo htmlspecialchars($sub['notes'] ?: 'No notes'); ?></span>
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

<?php 
// Render modals outside of the table for stability
foreach ($submissions as $sub): 
$can_review = ($sub['status'] === 'Pending' && (isStaff() || isAdmin())) || ($sub['status'] === 'Staff Approved' && isAdmin());
if ($can_review):
?>
<div class="modal fade" id="reviewModal<?php echo $sub['submission_id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-shield-check text-primary me-2"></i> 
                    Review Payroll #<?php echo $sub['submission_id']; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body py-4">
                    <input type="hidden" name="submission_id" value="<?php echo $sub['submission_id']; ?>">
                    <div class="mb-3">
                        <label class="form-label small text-uppercase fw-bold text-muted mb-1">Station Source</label>
                        <div class="form-control bg-light border-0 py-2"><?php echo htmlspecialchars($sub['station_name']); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-uppercase fw-bold text-muted mb-1">Authorization Amount</label>
                        <div class="form-control bg-light border-0 py-2 fw-bold text-dark">RWF <?php echo number_format($sub['total_amount'], 0); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Action Selection</label>
                        <select name="status" class="form-select border-primary-subtle" required>
                            <?php if (isStaff()): ?>
                                <option value="Approved">Verify & Forward to Admin</option>
                            <?php else: ?>
                                <option value="Approved">Final Authorization & Pay</option>
                            <?php endif; ?>
                            <option value="Rejected">Reject/Return to Accountant</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Internal Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Add reason for approval/rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-toggle="modal">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <?php echo isStaff() ? 'Confirm Verification' : 'Authorize Payment'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php 
endif;
endforeach; 
?>

<?php include 'includes/footer.php'; ?>
