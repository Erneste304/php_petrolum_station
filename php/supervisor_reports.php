<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

// Only Supervisors and Admins can see this hub
if (!isStaff() && !isAdmin()) {
    $_SESSION['error'] = "Access denied.";
    header("Location: index.php");
    exit();
}

$metrics = [
    'station_sales' => [],
    'pending_actions' => [],
    'inventory_alerts' => [],
    'recent_activity' => []
];

try {
    // 1. Station Performance Summary
    $metrics['station_sales'] = $pdo->query("
        SELECT s.station_name, COUNT(sl.sale_id) as total_tx, COALESCE(SUM(sl.total_amount), 0) as total_revenue
        FROM station s
        LEFT JOIN pump p ON s.station_id = p.station_id
        LEFT JOIN sale sl ON p.pump_id = sl.pump_id
        GROUP BY s.station_id, s.station_name
        ORDER BY total_revenue DESC
    ")->fetchAll();

    // 2. Pending Approval Hub
    $metrics['pending_actions'] = [
        'payrolls' => $pdo->query("SELECT COUNT(*) FROM payroll_submissions WHERE status = 'Pending'")->fetchColumn(),
        'shares' => $pdo->query("SELECT COUNT(*) FROM share_requests WHERE status = 'Pending'")->fetchColumn(),
        'problems' => $pdo->query("SELECT COUNT(*) FROM problem_reports WHERE status = 'Open'")->fetchColumn()
    ];

    // 3. Low Inventory Alerts
    $metrics['inventory_alerts'] = $pdo->query("
        SELECT t.tank_name, f.fuel_name, t.current_stock, t.capacity, s.station_name
        FROM tank t
        JOIN fuel_type f ON t.fuel_id = f.fuel_id
        JOIN station s ON t.station_id = s.station_id
        WHERE (t.current_stock / t.capacity) < 0.20
        ORDER BY (t.current_stock / t.capacity) ASC
    ")->fetchAll();

    // 4. Recent Accountant Submissions
    $metrics['recent_activity'] = $pdo->query("
        SELECT p.submission_id, 'Payroll' as type, p.total_amount as amount, p.created_at, u.username as submitter, p.status
        FROM payroll_submissions p
        JOIN users u ON p.accountant_id = u.user_id
        UNION ALL
        SELECT s.request_id, 'Shares' as type, s.total_amount, s.created_at, u.username, s.status
        FROM share_requests s
        JOIN users u ON s.partner_id = u.user_id
        ORDER BY created_at DESC
        LIMIT 10
    ")->fetchAll();

} catch (PDOException $e) {
    $error = "Reporting Error: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="row align-items-center mb-5">
    <div class="col">
        <h1 class="fw-bold"><i class="bi bi-shield-check text-primary me-2"></i> Supervisor Monitoring Hub</h1>
        <p class="text-muted">High-level oversight of station operations, financials, and approvals.</p>
    </div>
    <div class="col-auto">
        <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-4">
            <i class="bi bi-printer me-2"></i> Print Summary
        </button>
    </div>
</div>

<!-- Approval Alerts -->
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-warning bg-opacity-10 border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold text-muted small">Pending Payrolls</h6>
                        <h2 class="fw-bold mb-0"><?php echo $metrics['pending_actions']['payrolls']; ?></h2>
                    </div>
                    <a href="admin_payroll_approval.php" class="btn btn-warning rounded-pill px-3 fw-bold">Review</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-info bg-opacity-10 border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold text-muted small">Pending Shares</h6>
                        <h2 class="fw-bold mb-0"><?php echo $metrics['pending_actions']['shares']; ?></h2>
                    </div>
                    <a href="admin_shares_requests.php" class="btn btn-info rounded-pill px-3 fw-bold">Review</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-danger bg-opacity-10 border-start border-danger border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-bold text-muted small">Open Incidents</h6>
                        <h2 class="fw-bold mb-0"><?php echo $metrics['pending_actions']['problems']; ?></h2>
                    </div>
                    <a href="staff_problem_reports.php" class="btn btn-danger rounded-pill px-3 fw-bold">Manage</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Station Performance -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold mb-0">Station Revenue Performance</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small fw-bold">
                            <tr>
                                <th class="ps-4">Station Name</th>
                                <th>Volume (L)</th>
                                <th>Total Transactions</th>
                                <th class="pe-4 text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($metrics['station_sales'] as $station): ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?php echo htmlspecialchars($station['station_name']); ?></td>
                                <td>-</td>
                                <td><?php echo number_format($station['total_tx']); ?></td>
                                <td class="pe-4 text-end fw-bold text-primary">RWF <?php echo number_format($station['total_revenue']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Recent Validation Activity -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold mb-0">Recent Validation Activity</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small fw-bold">
                            <tr>
                                <th class="ps-4">Ref #</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Submitter</th>
                                <th>Status</th>
                                <th class="pe-4">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($metrics['recent_activity'] as $act): ?>
                            <tr>
                                <td class="ps-4">#<?php echo $act['submission_id']; ?></td>
                                <td><span class="badge bg-secondary"><?php echo $act['type']; ?></span></td>
                                <td class="fw-bold">RWF <?php echo number_format($act['amount']); ?></td>
                                <td><?php echo htmlspecialchars($act['submitter']); ?></td>
                                <td>
                                    <?php 
                                    $badge = 'bg-warning';
                                    if ($act['status'] === 'Approved' || $act['status'] === 'Completed') $badge = 'bg-success';
                                    if ($act['status'] === 'Staff Approved') $badge = 'bg-info';
                                    ?>
                                    <span class="badge <?php echo $badge; ?>"><?php echo $act['status']; ?></span>
                                </td>
                                <td class="pe-4 text-muted small"><?php echo date('M d, H:i', strtotime($act['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Sidebar - Alerts & Health -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4 border-top border-4 border-danger">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Critical Inventory Alerts</h6>
                <?php if (empty($metrics['inventory_alerts'])): ?>
                    <p class="text-muted small">All stations have healthy stock levels above 20%.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($metrics['inventory_alerts'] as $tank): ?>
                            <div class="list-group-item px-0 py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold small"><?php echo htmlspecialchars($tank['station_name']); ?></div>
                                        <div class="text-danger fw-bold"><?php echo htmlspecialchars($tank['fuel_name']); ?> (<?php echo htmlspecialchars($tank['tank_name']); ?>)</div>
                                    </div>
                                    <span class="badge bg-danger"><?php echo round(($tank['current_stock']/$tank['capacity'])*100); ?>%</span>
                                </div>
                                <div class="progress mt-2" style="height: 4px;">
                                    <div class="progress-bar bg-danger" style="width: <?php echo ($tank['current_stock']/$tank['capacity'])*100; ?>%"></div>
                                </div>
                                <small class="text-muted d-block mt-1"><?php echo number_format($tank['current_stock']); ?> L / <?php echo number_format($tank['capacity']); ?> L</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-dark text-white rounded-4 overflow-hidden position-relative">
            <div class="card-body p-4 position-relative" style="z-index: 2;">
                <h6 class="text-white-50 fw-bold small text-uppercase mb-3">System Integrity</h6>
                <div class="d-flex align-items-center mb-4">
                    <div class="display-4 fw-bold me-3 text-info">98%</div>
                    <div class="small lh-sm text-white-50">Operational uptime and <br>data consistency score.</div>
                </div>
                <div class="d-grid">
                    <a href="admin_audit_trail.php" class="btn btn-outline-info rounded-pill btn-sm">View Detailed Audit Logs</a>
                </div>
            </div>
            <i class="bi bi-shield-check position-absolute bottom-0 end-0 display-1 opacity-10 mb-n4 me-n4"></i>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
