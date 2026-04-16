<?php
require_once 'includes/auth_middleware.php';
if (isStaff() || isPumpAttendant()) {
     header("Location: receptionist_dashboard.php");
     exit();
 }
require_once 'config/database.php';
include 'includes/header.php';

$isAdmin = isAdmin();

// Get statistics (Only for Admins)
$stats = [];
if ($isAdmin) {
    try {
        // Total stations
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM station");
        $stats['stations'] = $stmt->fetch()['count'];

        // Total employees
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM employee");
        $stats['employees'] = $stmt->fetch()['count'];

        // Total customers
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM customer");
        $stats['customers'] = $stmt->fetch()['count'];

        // Total fuel types
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM fuel_type");
        $stats['fuel_types'] = $stmt->fetch()['count'];

        // Today's sales
        $stmt = $pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sale WHERE DATE(sale_date) = CURDATE()");
        $todaySales = $stmt->fetch();

        // Monthly sales
        $stmt = $pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sale WHERE MONTH(sale_date) = MONTH(CURDATE())");
        $monthlySales = $stmt->fetch();

        // Low stock alerts
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM tank t
            JOIN fuel_type f ON t.fuel_id = f.fuel_id
            WHERE (t.current_stock / t.capacity * 100) < 20
        ");
        $lowStock = $stmt->fetch()['count'];

        // Recent sales
        $stmt = $pdo->query("
            SELECT s.*, c.name as customer_name, 
                   CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   f.fuel_name
            FROM sale s 
            LEFT JOIN customer c ON s.customer_id = c.customer_id 
            JOIN employee e ON s.employee_id = e.employee_id
            JOIN pump p ON s.pump_id = p.pump_id
            JOIN fuel_type f ON p.fuel_id = f.fuel_id
            ORDER BY s.sale_date DESC 
            LIMIT 10
        ");
        $recentSales = $stmt->fetchAll();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
        <p class="text-muted">Welcome to Petroleum Station Management System, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
    </div>
    <div class="col-auto">
        <span class="badge bg-info p-3">
            <i class="bi bi-calendar"></i> <?php echo date('F d, Y'); ?>
        </span>
    </div>
</div>

<?php if ($isAdmin): ?>
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <?php 
        $stat_items = [
            ['label' => 'Stations', 'value' => $stats['stations'], 'icon' => 'bi-building', 'color' => 'primary'],
            ['label' => 'Employees', 'value' => $stats['employees'], 'icon' => 'bi-people', 'color' => 'success'],
            ['label' => 'Customers', 'value' => $stats['customers'], 'icon' => 'bi-person-badge', 'color' => 'info'],
            ['label' => 'Fuel Types', 'value' => $stats['fuel_types'], 'icon' => 'bi-droplet', 'color' => 'warning']
        ];
        foreach ($stat_items as $item): ?>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-<?php echo $item['color']; ?> bg-opacity-10 p-3 rounded-4 me-3">
                            <i class="bi <?php echo $item['icon']; ?> text-<?php echo $item['color']; ?> fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted small text-uppercase fw-bold mb-0"><?php echo $item['label']; ?></p>
                            <h3 class="fw-bold mb-0"><?php echo $item['value']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Sales Overview -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-graph-up"></i> Today's Sales
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 text-center">
                            <h3 class="text-primary"><?php echo $todaySales['count']; ?></h3>
                            <p class="text-muted">Transactions</p>
                        </div>
                        <div class="col-6 text-center">
                            <h3 class="text-success">RWF <?php echo number_format($todaySales['total'], 0); ?></h3>
                            <p class="text-muted">Revenue</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-calendar-month"></i> Monthly Sales
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 text-center">
                            <h3 class="text-primary"><?php echo $monthlySales['count']; ?></h3>
                            <p class="text-muted">Transactions</p>
                        </div>
                        <div class="col-6 text-center">
                            <h3 class="text-success">RWF <?php echo number_format($monthlySales['total'], 0); ?></h3>
                            <p class="text-muted">Revenue</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <?php if ($lowStock > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Low Stock Alert!</strong> There are <?php echo $lowStock; ?> fuel tanks with stock below 20%.
            <a href="fuel/index.php" class="alert-link">View Inventory</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Recent Sales Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary"></i> Recent Sales</h5>
            <a href="sales/create.php" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">
                <i class="bi bi-plus-circle me-1"></i> New Sale
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Transaction</th>
                        <th>User Info</th>
                        <th>Details</th>
                        <th>Amount</th>
                        <th class="pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentSales as $sale): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark">#<?php echo $sale['sale_id']; ?></div>
                            <small class="text-muted"><?php echo date('M d, H:i', strtotime($sale['sale_date'])); ?></small>
                        </td>
                        <td>
                            <div class="small fw-bold"><?php echo $sale['customer_name'] ?? 'Walk-in'; ?></div>
                            <div class="small text-muted"><i class="bi bi-person me-1"></i><?php echo $sale['employee_name']; ?></div>
                        </td>
                        <td>
                            <span class="badge bg-info-subtle text-primary border border-primary-subtle rounded-pill px-2"><?php echo $sale['fuel_name']; ?></span>
                            <small class="ms-1 fw-bold"><?php echo $sale['quantity']; ?> L</small>
                        </td>
                        <td class="fw-bold text-success">RWF <?php echo number_format($sale['total_amount'], 0); ?></td>
                        <td class="pe-4">
                            <a href="sales/view.php?id=<?php echo $sale['sale_id']; ?>" class="btn btn-sm btn-light rounded-circle shadow-sm">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions Simplified -->
    <div class="row g-3">
        <?php 
        $actions = [
            ['perm' => 'sales', 'url' => 'sales/create.php', 'icon' => 'bi-cart-plus', 'label' => 'Sale', 'color' => 'primary'],
            ['perm' => 'customers', 'url' => 'customers/create.php', 'icon' => 'bi-person-plus', 'label' => 'Customer', 'color' => 'success'],
            ['perm' => 'employees', 'url' => 'employees/create.php', 'icon' => 'bi-person-workspace', 'label' => 'Employee', 'color' => 'info'],
            ['perm' => 'reports', 'url' => 'reports.php', 'icon' => 'bi-file-text', 'label' => 'Report', 'color' => 'warning'],
            ['perm' => 'users', 'url' => 'users/index.php', 'icon' => 'bi-shield-lock', 'label' => 'Access', 'color' => 'danger']
        ];
        foreach ($actions as $act): 
            if (hasPermission($act['perm'])): ?>
            <div class="col-4 col-md-2">
                <a href="<?php echo $act['url']; ?>" class="btn btn-white w-100 py-3 shadow-sm rounded-4 border-0 d-flex flex-column align-items-center transition-hover">
                    <i class="bi <?php echo $act['icon']; ?> fs-4 text-<?php echo $act['color']; ?> mb-1"></i>
                    <span class="small fw-bold text-dark"><?php echo $act['label']; ?></span>
                </a>
            </div>
        <?php endif; endforeach; ?>
    </div>
<?php else: ?>
    <?php
    // Fetch Customer Specific Data
    $customerId = $_SESSION['customer_id'] ?? 0;

    $customerStats = ['total_spent' => 0, 'total_liters' => 0, 'total_visits' => 0];
    $customerSales = [];

    if ($customerId) {
        try {
            // Get aggregates
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_visits,
                    COALESCE(SUM(total_amount), 0) as total_spent,
                    COALESCE(SUM(quantity), 0) as total_liters
                FROM sale 
                WHERE customer_id = ?
            ");
            $stmt->execute([$customerId]);
            $customerStats = $stmt->fetch();

            // Get recent history
            $stmt = $pdo->prepare("
                SELECT s.*, f.fuel_name, s.station_name
                FROM sale s
                JOIN pump p ON s.pump_id = p.pump_id
                JOIN fuel_type f ON p.fuel_id = f.fuel_id
                LEFT JOIN station st ON p.station_id = st.station_id
                WHERE s.customer_id = ?
                ORDER BY s.sale_date DESC
                LIMIT 5
            ");
            // Workaround for the query above, we just need basic info
            $stmt = $pdo->prepare("
                SELECT s.sale_id, s.quantity, s.total_amount, s.sale_date, f.fuel_name 
                FROM sale s
                JOIN pump p ON s.pump_id = p.pump_id
                JOIN fuel_type f ON p.fuel_id = f.fuel_id
                WHERE s.customer_id = ? 
                ORDER BY s.sale_date DESC 
                LIMIT 5
            ");
            $stmt->execute([$customerId]);
            $customerSales = $stmt->fetchAll();
        } catch (PDOException $e) {
            // Silently handle or log error
        }
    }
    ?>
    <!-- Customer Dashboard -->
    <div class="row mb-4">
        <!-- Loyalty & Summary Cards -->
        <div class="col-md-4">
            <div class="card text-white bg-success h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-uppercase fw-bold opacity-75">Total Spent</h6>
                            <h2 class="mb-0">RWF <?php echo number_format($customerStats['total_spent'], 0); ?></h2>
                        </div>
                        <i class="bi bi-wallet2 fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-uppercase fw-bold opacity-75">Fuel Purchased</h6>
                            <h2 class="mb-0"><?php echo number_format($customerStats['total_liters'], 1); ?> L</h2>
                        </div>
                        <i class="bi bi-fuel-pump fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-uppercase fw-bold opacity-75">Total Visits</h6>
                            <h2 class="mb-0"><?php echo $customerStats['total_visits']; ?></h2>
                        </div>
                        <i class="bi bi-car-front fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="row">
        <!-- Recent Transactions -->
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history text-primary me-2"></i> Recent Transactions</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($customerSales)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-receipt fs-1 mb-3 d-block opacity-50"></i>
                            <h5>No purchases yet</h5>
                            <p>Visit our stations to start tracking your fuel consumption.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Fuel Type</th>
                                        <th>Quantity</th>
                                        <th>Total Paid</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customerSales as $sale): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></div>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($sale['sale_date'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($sale['fuel_name']); ?></span>
                                            </td>
                                            <td><?php echo number_format($sale['quantity'], 1); ?> L</td>
                                            <td class="fw-bold text-success">RWF <?php echo number_format($sale['total_amount'], 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Our Services Section -->
    <h4 class="mb-3 mt-2"><i class="bi bi-grid text-primary me-2"></i> Our Services</h4>
    <div class="row g-4 mb-5">
        <!-- Service 1 -->
        <div class="col-md-4">
            <a href="fuel_delivery.php" class="text-decoration-none text-dark">
                <div class="card h-100 shadow-sm service-card border-0 text-center transition-hover">
                    <div class="card-body py-5">
                        <div class="rounded-circle bg-info bg-opacity-10 d-inline-flex p-4 mb-3">
                            <i class="bi bi-truck text-primary fs-1"></i>
                        </div>
                        <h5 class="fw-bold">Fuel Delivery</h5>
                        <p class="text-muted mb-0">Order bulk fuel securely directly to your premises or business location.</p>
                    </div>
                </div>
            </a>
        </div>
        <!-- Service 2 -->
        <div class="col-md-4">
            <a href="loyalty.php" class="text-decoration-none text-dark">
                <div class="card h-100 shadow-sm service-card border-0 text-center transition-hover">
                    <div class="card-body py-5">
                        <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex p-4 mb-3">
                            <i class="bi bi-gift text-warning fs-1"></i>
                        </div>
                        <h5 class="fw-bold">Loyalty & Rewards</h5>
                        <p class="text-muted mb-0">Track your bronze tier points and redeem exclusive discounts on fuel.</p>
                    </div>
                </div>
            </a>
        </div>
        <!-- Service 3 -->
        <div class="col-md-4">
            <a href="car_wash.php" class="text-decoration-none text-dark">
                <div class="card h-100 shadow-sm service-card border-0 text-center transition-hover">
                    <div class="card-body py-5">
                        <div class="rounded-circle bg-info bg-opacity-10 d-inline-flex p-4 mb-3">
                            <i class="bi bi-car-front text-info fs-1"></i>
                        </div>
                        <h5 class="fw-bold">Car Detailing</h5>
                        <p class="text-muted mb-0">Schedule premium car washing or interior detailing at our partner stations.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Add some quick CSS for hover effects -->
    <style>
        .transition-hover {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .transition-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
        }
    </style>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>