<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

if (!isAdmin() && !isAccountant()) {
    $_SESSION['error'] = "Access denied.";
    header("Location: index.php");
    exit();
}

// Handle permission request
if (isset($_POST['request_permission'])) {
    $stmt = $pdo->prepare("INSERT INTO finance_permission_request (accountant_id, module_name, status) VALUES (?, ?, 'Pending')");
    $stmt->execute([$_SESSION['user_id'], $_POST['module']]);
    $_SESSION['success'] = "Permission request sent to Admin.";
}

// Fetch active permissions
$active_perms = $pdo->prepare("SELECT * FROM finance_permission_request WHERE accountant_id = ? AND status = 'Granted' AND (expiry IS NULL OR expiry > NOW())");
$active_perms->execute([$_SESSION['user_id']]);
$current_perms = $active_perms->fetchAll(PDO::FETCH_COLUMN, 3); // Get module_names

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-wallet2 text-primary"></i> Accountant Dashboard</h2>
        <p class="text-muted">Manage company finances, payroll, and audit logs.</p>
    </div>
</div>

<div class="row mb-5">
    <div class="col-lg-12">
        <div class="card border-0 shadow-lg p-4 text-white" style="border-radius: 20px; background: linear-gradient(135deg, #198754, #0b5131);">
            <div class="row align-items-center position-relative" style="z-index: 2;">
                <div class="col-auto">
                    <i class="bi bi-bank display-3 opacity-75"></i>
                </div>
                <div class="col">
                    <h1 class="fw-bold mb-1">Financial Control Center</h1>
                    <p class="mb-0 opacity-75">Accounting, Payroll & Audit Management</p>
                </div>
                <div class="col-auto">
                    <div class="badge bg-white text-success px-3 py-2 rounded-pill fw-bold">System Status: Secure</div>
                </div>
            </div>
            <i class="bi bi-currency-dollar position-absolute bottom-0 end-0 display-1 opacity-10 mb-n4 me-n4"></i>
        </div>
    </div>
</div>

<div class="row g-4 d-flex align-items-stretch">
    <!-- Payment Approval -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 premium-card">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-info bg-opacity-10 text-primary mb-4 mx-auto">
                    <i class="bi bi-cash-stack h2"></i>
                </div>
                <h5 class="fw-bold">Payment Approval</h5>
                <p class="small text-muted mb-4">Review and approve pending fuel and service payments.</p>
                <a href="payments.php" class="btn btn-primary w-100 rounded-pill fw-bold text-white">Manage Payments</a>
            </div>
        </div>
    </div>

    <!-- Share Verification -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 premium-card">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-success bg-opacity-10 text-success mb-4 mx-auto">
                    <i class="bi bi-check-all h2"></i>
                </div>
                <h5 class="fw-bold">Share Verification</h5>
                <p class="small text-muted mb-4">Final verification of share payments and ledger updates.</p>
                <a href="accountant_shares_verification.php" class="btn btn-success w-100 rounded-pill fw-bold">Verify Shares</a>
            </div>
        </div>
    </div>

    <!-- Payroll Management -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 premium-card">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-info bg-opacity-10 text-info mb-4 mx-auto">
                    <i class="bi bi-people-fill h2"></i>
                </div>
                <h5 class="fw-bold">Verify Payroll</h5>
                <p class="small text-muted mb-4 text-center">Review and certify staff payroll submissions before Admin payout.</p>
                
                <?php if (in_array('Salaries', $current_perms) || isAdmin()): ?>
                    <a href="staff_payroll_create.php" class="btn btn-info text-white w-100 rounded-pill fw-bold">Review Payroll</a>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="module" value="Salaries">
                        <button type="submit" name="request_permission" class="btn btn-outline-info w-100 rounded-pill fw-bold">Request Access</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Problem Logs -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 premium-card">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-warning bg-opacity-10 text-dark mb-4 mx-auto">
                    <i class="bi bi-exclamation-octagon h2"></i>
                </div>
                <h5 class="fw-bold">Problem Logs</h5>
                <p class="small text-muted mb-4">Review reported station issues from a financial perspective.</p>
                <a href="staff_problem_reports.php" class="btn btn-warning w-100 rounded-pill fw-bold">Monitor Logs</a>
            </div>
        </div>
    </div>
</div>

<style>
.icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.premium-card {
    transition: transform 0.3s ease, shadow 0.3s ease;
    border-radius: 18px;
}
.premium-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
}
</style>

<div class="mt-5">
    <h5 class="fw-bold mb-3">Recent Financial Requests</h5>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Action</th>
                        <th>Requested On</th>
                        <th>Status</th>
                        <th>Admin Info</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $reqs = $pdo->prepare("SELECT * FROM finance_permission_request WHERE accountant_id = ? ORDER BY created_at DESC LIMIT 5");
                    $reqs->execute([$_SESSION['user_id']]);
                    foreach ($reqs->fetchAll() as $r):
                    ?>
                    <tr>
                        <td class="ps-4"><?php echo $r['module_name']; ?></td>
                        <td><?php echo date('d M, H:i', strtotime($r['created_at'])); ?></td>
                        <td>
                            <span class="badge rounded-pill <?php echo $r['status'] == 'Granted' ? 'bg-success' : ($r['status'] == 'Pending' ? 'bg-warning text-dark' : 'bg-secondary'); ?>">
                                <?php echo $r['status']; ?>
                            </span>
                        </td>
                        <td class="small text-muted">
                            <?php echo $r['status'] == 'Granted' ? 'Expiry: ' . ($r['expiry'] ?? 'Never') : '-'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
