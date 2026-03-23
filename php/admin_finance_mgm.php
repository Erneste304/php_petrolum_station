<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';
requireAdmin();

// Handle Grant/Deny
if (isset($_POST['action'])) {
    $id = $_POST['permission_id'];
    $status = $_POST['action'] == 'grant' ? 'Granted' : ($_POST['action'] == 'close' ? 'Closed' : 'Denied');
    $expiry = ($_POST['action'] == 'grant') ? date('Y-m-d H:i:s', strtotime('+1 hour')) : null;
    
    $stmt = $pdo->prepare("UPDATE finance_permission_request SET status = ?, admin_id = ?, expiry = ? WHERE permission_id = ?");
    $stmt->execute([$status, $_SESSION['user_id'], $expiry, $id]);
    $_SESSION['success'] = "Permission status updated.";
}

$requests = $pdo->query("
    SELECT r.*, u.username as accountant_name 
    FROM finance_permission_request r 
    JOIN users u ON r.accountant_id = u.user_id 
    ORDER BY r.created_at DESC
")->fetchAll();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-shield-lock-fill text-danger me-2"></i> Financial Access Controls</h2>
        <p class="text-muted">Review and authorize temporary financial access for Accountants.</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Accountant</th>
                    <th>Module</th>
                    <th>Requested</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($r['accountant_name']); ?></td>
                    <td><span class="badge bg-light text-dark border"><?php echo $r['module_name']; ?></span></td>
                    <td class="small text-muted"><?php echo date('d M, H:i', strtotime($r['created_at'])); ?></td>
                    <td>
                        <span class="badge rounded-pill <?php echo $r['status'] == 'Granted' ? 'bg-success' : ($r['status'] == 'Pending' ? 'bg-warning text-dark' : 'bg-secondary'); ?>">
                            <?php echo $r['status']; ?>
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <?php if ($r['status'] == 'Pending'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="permission_id" value="<?php echo $r['permission_id']; ?>">
                                <button type="submit" name="action" value="grant" class="btn btn-sm btn-success rounded-pill px-3">Grant (1hr)</button>
                                <button type="submit" name="action" value="deny" class="btn btn-sm btn-outline-danger rounded-pill px-3">Deny</button>
                            </form>
                        <?php elseif ($r['status'] == 'Granted'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="permission_id" value="<?php echo $r['permission_id']; ?>">
                                <button type="submit" name="action" value="close" class="btn btn-sm btn-secondary rounded-pill px-3">Revoke Now</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
