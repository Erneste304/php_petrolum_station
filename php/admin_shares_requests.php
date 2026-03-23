<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

// Staff and Admin can manage requests
if (!isAdmin() && !isStaff() && !isReceptionist()) {
    $_SESSION['error'] = "Access denied.";
    header("Location: index.php");
    exit();
}

// Fetch share config for stock
$config = $pdo->query("SELECT * FROM share_config ORDER BY id DESC LIMIT 1")->fetch();

// Handle Staff/Admin Approval/Rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['status'];
    $user_id = $_SESSION['user_id'];

    try {
        if ($new_status === 'Completed' && !isAdmin()) {
            throw new Exception("Only Administrators can finalize share issuance.");
        }

        $stmt = $pdo->prepare("UPDATE share_requests SET status = ?, " . (isAdmin() ? "admin_id" : "staff_id") . " = ? WHERE request_id = ?");
        $stmt->execute([$new_status, $user_id, $request_id]);
        
        // If Completed, deduct from global stock
        if ($new_status === 'Completed') {
            $req = $pdo->prepare("SELECT number_of_shares, request_type FROM share_requests WHERE request_id = ?");
            $req->execute([$request_id]);
            $data = $req->fetch();
            
            if ($data['request_type'] === 'buy') {
                $pdo->prepare("UPDATE share_config SET total_available_shares = total_available_shares - ? WHERE id = ?")
                    ->execute([$data['number_of_shares'], $config['id']]);
            } else {
                $pdo->prepare("UPDATE share_config SET total_available_shares = total_available_shares + ? WHERE id = ?")
                    ->execute([$data['number_of_shares'], $config['id']]);
            }
        }
        
        $_SESSION['success'] = "Request #" . $request_id . " updated to " . $new_status;
        header("Location: admin_shares_requests.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating status: " . $e->getMessage();
    }
}

// Fetch requests based on filters
$sql = "
    SELECT sr.*, u.username as partner_name, 
           (SELECT username FROM users WHERE user_id = sr.staff_id) as staff_name,
           (SELECT username FROM users WHERE user_id = sr.accountant_id) as accountant_name
    FROM share_requests sr
    JOIN users u ON sr.partner_id = u.user_id
";

$status_filter = $_GET['status'] ?? (isAdmin() ? 'Accountant Verified' : 'Pending');
if ($status_filter != 'All') {
    $sql .= " WHERE sr.status = " . $pdo->quote($status_filter);
}

$sql .= " ORDER BY sr.created_at DESC";
$requests = $pdo->query($sql)->fetchAll();

include 'includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col">
        <h2><i class="bi bi-shield-shaded text-primary me-2"></i> Share Issuance & Control</h2>
        <p class="text-muted">Certify share transfers and monitor market liquidity.</p>
    </div>
    <div class="col-auto">
        <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-0 py-2">
            <i class="bi bi-bank2 me-3 h4 mb-0"></i>
            <div>
                <div class="small fw-bold">Market Liquidity</div>
                <div class="h5 mb-0"><?php echo number_format($config['total_available_shares'] ?? 0, 4); ?> <span class="small">Available</span></div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col">
        <div class="btn-group shadow-sm" role="group">
            <a href="?status=Pending" class="btn <?php echo $status_filter == 'Pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="bi bi-hourglass me-1"></i> Pending
            </a>
            <a href="?status=Staff Approved" class="btn <?php echo $status_filter == 'Staff Approved' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="bi bi-person-check me-1"></i> Staff Approved
            </a>
            <a href="?status=Accountant Verified" class="btn <?php echo $status_filter == 'Accountant Verified' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="bi bi-cash-stack me-1"></i> Paid (Verify)
            </a>
            <a href="?status=Completed" class="btn <?php echo $status_filter == 'Completed' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="bi bi-check2-all me-1"></i> Completed
            </a>
            <a href="?status=All" class="btn <?php echo $status_filter == 'All' ? 'btn-primary' : 'btn-outline-primary'; ?>">All Logs</a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Req #</th>
                    <th>Partner</th>
                    <th>Type</th>
                    <th>Shares</th>
                    <th>Total Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">No requests found with status: <?php echo $status_filter; ?></td></tr>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                    <tr>
                        <td class="ps-4 text-muted">#<?php echo $req['request_id']; ?></td>
                        <td class="fw-bold"><?php echo htmlspecialchars($req['partner_name']); ?></td>
                        <td>
                            <span class="badge <?php echo $req['request_type'] == 'buy' ? 'bg-success' : 'bg-danger'; ?> text-capitalize">
                                <?php echo $req['request_type']; ?>
                            </span>
                        </td>
                        <td><?php echo number_format($req['number_of_shares'], 4); ?></td>
                        <td class="fw-bold">RWF <?php echo number_format($req['total_amount'], 2); ?></td>
                        <td class="small"><?php echo date('d M, H:i', strtotime($req['created_at'])); ?></td>
                        <td>
                            <?php 
                            $badge = match($req['status']) {
                                'Pending' => 'bg-warning text-dark',
                                'Staff Approved' => 'bg-info text-white',
                                'Completed' => 'bg-success',
                                'Rejected' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?php echo $badge; ?>"><?php echo $req['status']; ?></span>
                        </td>
                        <td class="text-end pe-4">
                            <?php if ($req['status'] == 'Pending' && (isAdmin() || isStaff() || isReceptionist())): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                    <input type="hidden" name="status" value="Staff Approved">
                                    <button type="submit" name="update_status" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold">
                                        <i class="bi bi-hand-thumbs-up me-1"></i> Staff OK
                                    </button>
                                </form>
                            <?php elseif ($req['status'] == 'Accountant Verified' && isAdmin()): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                    <input type="hidden" name="status" value="Completed">
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                                        <i class="bi bi-patch-check-fill me-1"></i> Finalize Issuance
                                    </button>
                                </form>
                            <?php elseif ($req['status'] == 'Completed'): ?>
                                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill small">
                                    <i class="bi bi-check2-circle me-1"></i> Legally Issued
                                </span>
                            <?php else: ?>
                                <div class="small text-muted">
                                    <?php if ($req['staff_name']) echo "Approved by: " . htmlspecialchars($req['staff_name']) . "<br>"; ?>
                                    <?php if ($req['accountant_name']) echo "Paid via: " . htmlspecialchars($req['accountant_name']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
