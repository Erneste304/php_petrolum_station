<?php
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/config/database.php';

if (!isAdmin() && !isAccountant()) {
    $_SESSION['error'] = 'Access denied. This page is for accountants only.';
    header('Location: index.php');
    exit;
}


// Handle Accountant Verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_request'])) {
    $request_id = $_POST['request_id'];
    $accountant_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // 1. Fetch request details
        $stmt = $pdo->prepare("SELECT * FROM share_requests WHERE request_id = ? FOR UPDATE");
        $stmt->execute([$request_id]);
        $req = $stmt->fetch();

        if ($req && $req['status'] === 'Staff Approved') {
            // 2. Update status to 'Accountant Verified' (Wait for Admin final step)
            $stmt = $pdo->prepare("UPDATE share_requests SET status = 'Accountant Verified', accountant_id = ? WHERE request_id = ?");
            $stmt->execute([$accountant_id, $request_id]);

            // 3. Log in Audit Trail
            $audit_stmt = $pdo->prepare("
                INSERT INTO transaction_audit 
                (transaction_type, record_id, changed_by, changed_by_role, previous_data, updated_data) 
                VALUES ('share_payment_verified', ?, ?, 'accountant', ?, ?)
            ");
            $audit_stmt->execute([
                $request_id,
                $accountant_id,
                json_encode(['status' => 'Staff Approved']),
                json_encode(['status' => 'Accountant Verified'])
            ]);

            $pdo->commit();
            $_SESSION['success'] = "Payment for Transaction #" . $request_id . " verified. Awaiting Admin final issuance.";
        } else {
            $pdo->rollBack();
            $_SESSION['error'] = "Request not found or not ready for verification.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error verifying transaction: " . $e->getMessage();
    }
    
    header("Location: accountant_shares_verification.php");
    exit();
}

// Fetch Staff Approved requests for verification
$requests = $pdo->query("
    SELECT sr.*, u.username as partner_name,
           (SELECT username FROM users WHERE user_id = sr.staff_id) as staff_name
    FROM share_requests sr
    JOIN users u ON sr.partner_id = u.user_id
    WHERE sr.status = 'Staff Approved'
    ORDER BY sr.updated_at DESC
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col">
        <h2><i class="bi bi-bank text-success me-2"></i> Share Ledger Verification</h2>
        <p class="text-muted">Final verification of share transactions. Confirming these will update the financial accounts and partner portfolios.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold">Approved Orders Awaiting Verification</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Req #</th>
                                <th>Partner</th>
                                <th>Type</th>
                                <th>Shares</th>
                                <th>Financial impact</th>
                                <th>Staff Approval</th>
                                <th class="text-end pe-4">Confirmation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">No transactions currently awaiting verification.</td></tr>
                            <?php else: ?>
                                <?php foreach ($requests as $req): 
                                    $net_amount = $req['total_amount'];
                                    if ($req['request_type'] == 'sell') {
                                        $net_amount = $req['total_amount'] - $req['commission_amount'];
                                    }
                                ?>
                                <tr>
                                    <td class="ps-4 text-muted">#<?php echo $req['request_id']; ?></td>
                                    <td><span class="fw-bold"><?php echo htmlspecialchars($req['partner_name']); ?></span></td>
                                    <td>
                                        <span class="badge <?php echo $req['request_type'] == 'buy' ? 'bg-success' : 'bg-danger'; ?> text-capitalize">
                                            <?php echo $req['request_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($req['number_of_shares'], 4); ?></td>
                                    <td>
                                        <div class="fw-bold">RWF <?php echo number_format($net_amount, 2); ?></div>
                                        <?php if ($req['commission_amount'] > 0): ?>
                                            <small class="text-danger">Incl. RWF <?php echo number_format($req['commission_amount'], 2); ?> fee</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="small text-muted">Approved by:</span><br>
                                        <span class="fw-bold small"><?php echo htmlspecialchars($req['staff_name']); ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST">
                                            <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                            <button type="submit" name="verify_request" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" onclick="return confirm('Verify this transaction and update partner balance?')">
                                                <i class="bi bi-patch-check-fill me-1"></i> Verify & Update
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-success bg-opacity-10 h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold text-success mb-3">Verification Rules</h5>
                <ul class="list-unstyled small">
                    <li class="mb-3">
                        <i class="bi bi-1-circle-fill text-success me-2"></i>
                        Confirm that the physical payment (for buys) or bank details (for sells) are correct.
                    </li>
                    <li class="mb-3">
                        <i class="bi bi-2-circle-fill text-success me-2"></i>
                        Checking 'Verify' immediately updates the partner's share holdings.
                    </li>
                    <li class="mb-3">
                        <i class="bi bi-3-circle-fill text-success me-2"></i>
                        The system automatically deducts 0.01% commission on sell orders.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
