<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';
requirePermission('reports');

$error = '';
$metrics = [
    'total_revenue' => 0,
    'total_sales' => 0,
    'fuel_breakdown' => [],
    'recent_orders' => [],
    'recent_logs' => []
];

try {
    // 1. Total Revenue & Sales count
    $stmt = $pdo->query("SELECT COUNT(*) as total_sales, COALESCE(SUM(total_amount), 0) as total_revenue FROM sale");
    $totals = $stmt->fetch();
    $metrics['total_revenue'] = $totals['total_revenue'];
    $metrics['total_sales'] = $totals['total_sales'];

    // 2. Revenue Breakdown by Fuel Type
    $stmt = $pdo->query("
        SELECT f.fuel_name, COUNT(s.sale_id) as sales_count, COALESCE(SUM(s.total_amount), 0) as revenue
        FROM fuel_type f
        LEFT JOIN pump p ON f.fuel_id = p.fuel_id
        LEFT JOIN sale s ON p.pump_id = s.pump_id
        GROUP BY f.fuel_id, f.fuel_name
        ORDER BY revenue DESC
    ");
    $metrics['fuel_breakdown'] = $stmt->fetchAll();

    // 3. Recent Bulk Fuel Orders
    $stmt = $pdo->query("
        SELECT o.order_id, o.quantity_liters, o.total_estimated_cost, o.delivery_date, o.status, f.fuel_name, c.name as customer_name
        FROM bulk_fuel_order o
        JOIN fuel_type f ON o.fuel_id = f.fuel_id
        JOIN customer c ON o.customer_id = c.customer_id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $metrics['recent_orders'] = $stmt->fetchAll();

    // 4. Recent Stock Logs
    $stmt = $pdo->query("
        SELECT l.log_date, l.change_amount, l.log_type, t.capacity, t.current_stock, f.fuel_name
        FROM fuel_stock_log l
        JOIN tank t ON l.tank_id = t.tank_id
        JOIN fuel_type f ON t.fuel_id = f.fuel_id
        ORDER BY l.log_date DESC
        LIMIT 10
    ");
    $metrics['recent_logs'] = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Failed to load report data: " . $e->getMessage();
}

include 'includes/header.php';
?>

<!-- Print-specific styles to hide navigation and buttons when printing -->
<style>
    @media print {
        .navbar, .breadcrumb, .btn-print, footer, .no-print {
            display: none !important;
        }
        body {
            background-color: white !important;
        }
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
        .container {
            max-width: 100% !important;
            width: 100% !important;
            padding: 0 !important;
        }
    }
</style>

<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="mb-1"><i class="bi bi-graph-up-arrow text-primary me-2"></i> Comprehensive Reports</h2>
        <nav aria-label="breadcrumb" class="breadcrumb-container">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Reports</li>
            </ol>
        </nav>
    </div>
    <div class="col-auto">
        <button onclick="window.print()" class="btn btn-outline-primary btn-print">
            <i class="bi bi-printer me-2"></i> Print Report
        </button>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Executive Summary Cards -->
<div class="row mb-4">
    <div class="col-md-6 mb-3 mb-md-0">
        <div class="card h-100 border-0 shadow-sm bg-success text-white">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-uppercase fw-bold opacity-75 mb-2">Lifetime Station Revenue</h6>
                    <h2 class="mb-0 fw-bold">RWF <?php echo number_format($metrics['total_revenue']); ?></h2>
                </div>
                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                    <i class="bi bi-cash-stack fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm bg-info text-white">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-uppercase fw-bold opacity-75 mb-2">Total Sale Transactions</h6>
                    <h2 class="mb-0 fw-bold"><?php echo number_format($metrics['total_sales']); ?></h2>
                </div>
                <div class="bg-white bg-opacity-25 rounded-circle p-3">
                    <i class="bi bi-receipt fs-1"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Fuel Type Breakdown -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-white pt-3 pb-2 border-bottom-0">
                <h5 class="mb-0 text-dark fw-bold">Revenue by Fuel Type</h5>
            </div>
            <div class="card-body">
                <?php foreach ($metrics['fuel_breakdown'] as $fuel): ?>
                    <?php 
                        // Calculate percentage for progress bar
                        $percentage = $metrics['total_revenue'] > 0 ? ($fuel['revenue'] / $metrics['total_revenue']) * 100 : 0;
                        
                        // Assign colors based on fuel name loosely
                        $colorClass = 'bg-info';
                        $iconClass = 'bi-droplet-fill text-primary';
                        if (stripos($fuel['fuel_name'], 'diesel') !== false) {
                            $colorClass = 'bg-dark'; $iconClass = 'bi-funnel-fill text-dark';
                        } elseif (stripos($fuel['fuel_name'], 'super') !== false) {
                            $colorClass = 'bg-danger'; $iconClass = 'bi-fire text-danger';
                        }
                    ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-end mb-1">
                            <div>
                                <i class="bi <?php echo $iconClass; ?> me-1"></i> 
                                <span class="fw-bold"><?php echo htmlspecialchars($fuel['fuel_name']); ?></span>
                                <small class="text-muted ms-2">(<?php echo number_format($fuel['sales_count']); ?> transactions)</small>
                            </div>
                            <div class="fw-bold">RWF <?php echo number_format($fuel['revenue']); ?></div>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar <?php echo $colorClass; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7 mb-4">
        <!-- Data Tabs -->
        <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-white pt-3 border-bottom-0">
                <ul class="nav nav-tabs card-header-tabs" id="reportTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-selected="true">
                            <i class="bi bi-truck me-1"></i> Bulk Deliveries
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock" type="button" role="tab" aria-selected="false">
                            <i class="bi bi-box-seam me-1"></i> Stock Activity
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body p-0">
                <div class="tab-content" id="reportTabsContent">
                    
                    <!-- Bulk Orders Tab -->
                    <div class="tab-pane fade show active" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Customer</th>
                                        <th>Fuel</th>
                                        <th>Qty (L)</th>
                                        <th>Delivery Date</th>
                                        <th>Value</th>
                                        <th class="pe-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($metrics['recent_orders'])): ?>
                                        <tr><td colspan="6" class="text-center text-muted py-4">No bulk orders found.</td></tr>
                                    <?php endif; ?>
                                    
                                    <?php foreach ($metrics['recent_orders'] as $order): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($order['fuel_name']); ?></span></td>
                                            <td><?php echo number_format($order['quantity_liters']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($order['delivery_date'])); ?></td>
                                            <td class="fw-bold">RWF <?php echo number_format($order['total_estimated_cost']); ?></td>
                                            <td class="pe-4">
                                                <?php 
                                                    $statusClass = 'text-warning';
                                                    if ($order['status'] === 'Approved') $statusClass = 'text-info';
                                                    if ($order['status'] === 'Delivered') $statusClass = 'text-success';
                                                    if ($order['status'] === 'Cancelled') $statusClass = 'text-danger';
                                                ?>
                                                <i class="bi bi-circle-fill <?php echo $statusClass; ?> small me-1"></i>
                                                <?php echo htmlspecialchars($order['status']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Stock Logs Tab -->
                    <div class="tab-pane fade" id="stock" role="tabpanel" aria-labelledby="stock-tab">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Date & Time</th>
                                        <th>Fuel Type</th>
                                        <th>Activity Type</th>
                                        <th>Amount Changed</th>
                                        <th class="pe-4">Current Stock / Capacity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($metrics['recent_logs'])): ?>
                                        <tr><td colspan="5" class="text-center text-muted py-4">No stock logs found.</td></tr>
                                    <?php endif; ?>

                                    <?php foreach ($metrics['recent_logs'] as $log): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold"><?php echo date('M d, Y', strtotime($log['log_date'])); ?></div>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($log['log_date'])); ?></small>
                                            </td>
                                            <td><span class="badge bg-dark"><?php echo htmlspecialchars($log['fuel_name']); ?></span></td>
                                            <td>
                                                <?php if (stripos($log['log_type'], 'delivery') !== false): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                                        <i class="bi bi-arrow-down-left me-1"></i><?php echo htmlspecialchars($log['log_type']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">
                                                        <i class="bi bi-arrow-up-right me-1"></i><?php echo htmlspecialchars($log['log_type']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-bold <?php echo ($log['change_amount'] > 0) ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo ($log['change_amount'] > 0 ? '+' : '') . number_format($log['change_amount']); ?> L
                                            </td>
                                            <td class="pe-4">
                                                <?php 
                                                    $stockPct = ($log['current_stock'] / $log['capacity']) * 100;
                                                    $stockColor = $stockPct < 20 ? 'bg-danger' : 'bg-info';
                                                ?>
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span><?php echo number_format($log['current_stock']); ?> L</span>
                                                    <span class="text-muted"><?php echo number_format($log['capacity']); ?> L</span>
                                                </div>
                                                <div class="progress" style="height: 4px;">
                                                    <div class="progress-bar <?php echo $stockColor; ?>" style="width: <?php echo $stockPct; ?>%"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
