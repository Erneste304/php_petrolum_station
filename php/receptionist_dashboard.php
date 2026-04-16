<?php
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/config/database.php';

// Allow admin, staff, and receptionist roles to access this dashboard
if (!isAdmin() && !isStaff() && !isPumpAttendant()) {
    $_SESSION['error'] = 'Access denied. This dashboard is for staff only.';
    header('Location: index.php');
    exit;
}

include __DIR__ . '/includes/header.php';

// Fetch some stats for receptionist
$customers_count = $pdo->query("SELECT COUNT(*) FROM customer")->fetchColumn();
$recent_sales = $pdo->query("SELECT s.*, c.name as customer_name FROM sale s LEFT JOIN customer c ON s.customer_id = c.customer_id ORDER BY sale_date DESC LIMIT 5")->fetchAll();
?>

<div class="row mb-5">
    <div class="col-lg-12">
        <div class="card border-0 shadow-lg p-4 bg-gradient-staff text-white overflow-hidden position-relative" style="border-radius: 20px; background: linear-gradient(135deg, #0d6efd, #003399);">
            <div class="row align-items-center position-relative" style="z-index: 2;">
                <div class="col-auto">
                    <i class="bi bi-shield-lock display-3 opacity-75"></i>
                </div>
                <div class="col">
                    <h1 class="fw-bold mb-1">Supervisor Control Room</h1>
                    <p class="mb-0 opacity-75">High-Level Oversight & Multi-Stage Approval Center</p>
                </div>
                <div class="col-auto">
                    <div class="badge bg-white text-primary px-3 py-2 rounded-pill fw-bold">Supervisor ID: <?php echo $_SESSION['username']; ?></div>
                </div>
            </div>
            <i class="bi bi-shield-check position-absolute bottom-0 end-0 display-1 opacity-10 mb-n4 me-n4"></i>
        </div>
    </div>
</div>

<?php
// Supervisor Stats
$pending_payrolls = $pdo->query("SELECT COUNT(*) FROM payroll_submissions WHERE status = 'Pending'")->fetchColumn();
$pending_shares = $pdo->query("SELECT COUNT(*) FROM share_requests WHERE status = 'Pending'")->fetchColumn();
?>

<div class="row g-4 mb-5">
    <!-- User Management Module -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 premium-card border-bottom border-primary border-4">
            <div class="card-body p-4">
                <div class="icon-circle bg-primary bg-opacity-10 text-primary mb-3">
                    <i class="bi bi-people-fill h2 mb-0"></i>
                </div>
                <h5 class="fw-bold">User Oversight</h5>
                <p class="small text-muted mb-4">Manage Receptionists, Partners, and Investors records.</p>
                <div class="d-grid gap-2">
                    <a href="users/index.php" class="btn btn-outline-primary btn-sm rounded-pill fw-bold">Users List</a>
                    <a href="employees/index.php" class="btn btn-outline-primary btn-sm rounded-pill fw-bold">Receptionists</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Oversight Module -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 premium-card border-bottom border-info border-4">
            <div class="card-body p-4">
                <div class="icon-circle bg-info bg-opacity-10 text-info mb-3">
                    <i class="bi bi-person-badge h2 mb-0"></i>
                </div>
                <h5 class="fw-bold">Customer CRM</h5>
                <p class="small text-muted mb-4">Monitor customer base activity and vehicle registration.</p>
                <a href="customers/index.php" class="btn btn-outline-info w-100 rounded-pill fw-bold">View Customers</a>
            </div>
        </div>
    </div>

    <!-- Approval Hub Module -->
    <div class="col-md-6 col-lg-6">
        <div class="card border-0 shadow-sm h-100 premium-card bg-light">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-patch-check-fill text-warning me-2"></i> Approval Hub</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-white rounded-3 shadow-sm d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Pending Payrolls</small>
                                <span class="h4 fw-bold mb-0"><?php echo $pending_payrolls; ?></span>
                            </div>
                            <a href="admin_payroll_approval.php" class="btn btn-sm btn-warning rounded-circle"><i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-white rounded-3 shadow-sm d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Pending Shares</small>
                                <span class="h4 fw-bold mb-0"><?php echo $pending_shares; ?></span>
                            </div>
                            <a href="admin_shares_requests.php" class="btn btn-sm btn-warning rounded-circle"><i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <p class="small text-muted mt-3 mb-0">Verification is required before items reach the Super Admin for finalization.</p>
            </div>
        </div>
    </div>
</div>
    <?php if (hasPermission('reports')): ?>
    <!-- Problem Reports -->
    <div class="col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm h-100 premium-card">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-danger bg-opacity-10 text-danger mb-4 mx-auto">
                    <i class="bi bi-exclamation-triangle-fill h2"></i>
                </div>
                <h5 class="fw-bold">Problems</h5>
                <a href="staff_problem_reports.php" class="btn btn-danger w-100 rounded-pill fw-bold px-0">Open</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Payroll Submission -->
    <div class="col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm h-100 premium-card">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-success bg-opacity-10 text-success mb-4 mx-auto">
                    <i class="bi bi-cash-coin h2"></i>
                </div>
                <h5 class="fw-bold">Payroll</h5>
                <a href="staff_payroll_create.php" class="btn btn-success w-100 rounded-pill fw-bold px-0">Submit</a>
            </div>
        </div>
    </div>

    <?php if (hasPermission('reports')): ?>
    <!-- Supervisor Monitoring -->
    <div class="col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm h-100 premium-card">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-primary bg-opacity-10 text-primary mb-4 mx-auto">
                    <i class="bi bi-graph-up-arrow h2"></i>
                </div>
                <h5 class="fw-bold">Monitoring</h5>
                <a href="supervisor_reports.php" class="btn btn-primary w-100 rounded-pill fw-bold px-0">Stats</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (hasPermission('fuel')): ?>
    <!-- Service Guide -->
    <div class="col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm h-100 premium-card">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-info bg-opacity-10 text-info mb-4 mx-auto">
                    <i class="bi bi-book-half h2"></i>
                </div>
                <h5 class="fw-bold">Service</h5>
                <a href="bulk_products.php" class="btn btn-info text-white w-100 rounded-pill fw-bold px-0">Catalog</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (hasPermission('sales')): ?>
    <!-- Record Sale -->
    <div class="col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm h-100 premium-card">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-info bg-opacity-10 text-primary mb-4 mx-auto">
                    <i class="bi bi-cart-plus-fill h2"></i>
                </div>
                <h5 class="fw-bold">New Sale</h5>
                <p class="small text-muted mb-4">Assisted capture for walk-in customers.</p>
                <a href="sales/create.php" class="btn btn-primary w-100 rounded-pill fw-bold">Capture Sale</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (hasPermission('shares')): ?>
    <!-- Share Approvals -->
    <div class="col-md-4 col-lg-4">
        <div class="card border-0 shadow-sm h-100 premium-card border-start border-warning border-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-circle bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-shield-check h2 mb-0"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Share Approvals</h5>
                        <small class="text-muted">Investment Verification</small>
                    </div>
                </div>
                <p class="small text-muted mb-4">Initial staff-level validation for partner share purchase or sell requests.</p>
                <a href="admin_shares_requests.php" class="btn btn-warning w-100 rounded-pill fw-bold">Review Requests</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.icon-circle {
    width: 70px;
    height: 70px;
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

<?php include 'includes/footer.php'; ?>
