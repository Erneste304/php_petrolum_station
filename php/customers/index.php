<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';
requirePermission('customers');

// Handle delete request
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM customer WHERE customer_id = ?");
        $stmt->execute([$_GET['delete']]);
        $_SESSION['success'] = "Customer deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Cannot delete customer: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}

include '../includes/header.php';

// Fetch stats for the counter cards
$stations_count = $pdo->query("SELECT COUNT(*) FROM station")->fetchColumn();
$employees_count = $pdo->query("SELECT COUNT(*) FROM employee")->fetchColumn();
$customers_count = $pdo->query("SELECT COUNT(*) FROM customer")->fetchColumn();
$fuel_types_count = $pdo->query("SELECT COUNT(*) FROM fuel_type")->fetchColumn();

// Fetch all customers with their pending service counts
$query = "
    SELECT c.*, 
        (SELECT COUNT(*) FROM car_wash_booking WHERE customer_id = c.customer_id AND status = 'Pending') as pending_washes,
        (SELECT COUNT(*) FROM bulk_fuel_order WHERE customer_id = c.customer_id AND status = 'Pending') as pending_deliveries
    FROM customer c 
    ORDER BY name
";
$customers = $pdo->query($query)->fetchAll();
?>

<div class="page-header mb-4">
    <div class="row align-items-center text-center text-md-start">
        <div class="col">
            <h1 class="display-5 fw-bold mb-0">Petroleum Station MS</h1>
            <p class="text-white-50 mb-0">Powered by Erneste304tech — System Control Center</p>
        </div>
        <div class="col-auto mt-3 mt-md-0">
            <span class="badge bg-white text-info p-3 fs-6 rounded-pill shadow-sm">
                <i class="bi bi-clock-history me-1"></i> <?php echo date('l, d M Y'); ?>
            </span>
        </div>
    </div>
</div>

<!-- Quick Link Grid -->
<div class="row g-4 mb-5">
    <div class="col-6 col-lg-3">
        <a href="index.php" class="card border-0 shadow-sm h-100 text-decoration-none transition-hover">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-info bg-opacity-10 text-primary mb-3 mx-auto">
                    <i class="bi bi-speedometer2"></i>
                </div>
                <h6 class="fw-bold mb-0">Dashboard</h6>
                <small class="text-muted">Main Overview</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="../stations/index.php" class="card border-0 shadow-sm h-100 text-decoration-none transition-hover">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-success bg-opacity-10 text-success mb-3 mx-auto">
                    <i class="bi bi-building"></i>
                </div>
                <h6 class="fw-bold mb-0">Stations</h6>
                <small class="text-muted"><?php echo $stations_count; ?> Active</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="../employees/index.php" class="card border-0 shadow-sm h-100 text-decoration-none transition-hover">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-warning bg-opacity-10 text-warning mb-3 mx-auto">
                    <i class="bi bi-people"></i>
                </div>
                <h6 class="fw-bold mb-0">Employees</h6>
                <small class="text-muted"><?php echo $employees_count; ?> Staff</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="index.php" class="card border-0 shadow-sm h-100 text-decoration-none transition-hover border-primary border-2 shadow">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-info bg-opacity-10 text-primary mb-3 mx-auto">
                    <i class="bi bi-person-badge"></i>
                </div>
                <h6 class="fw-bold mb-0">Customers</h6>
                <small class="text-muted"><?php echo $customers_count; ?> Registered</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="../fuel/index.php" class="card border-0 shadow-sm h-100 text-decoration-none transition-hover">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-danger bg-opacity-10 text-danger mb-3 mx-auto">
                    <i class="bi bi-droplet"></i>
                </div>
                <h6 class="fw-bold mb-0">Fuel</h6>
                <small class="text-muted"><?php echo $fuel_types_count; ?> Types</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="../sales/index.php" class="card border-0 shadow-sm h-100 text-decoration-none transition-hover">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-primary bg-opacity-10 text-primary mb-3 mx-auto">
                    <i class="bi bi-cart"></i>
                </div>
                <h6 class="fw-bold mb-0">Sales</h6>
                <small class="text-muted">Total Revenue</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <div class="dropdown h-100">
            <a href="#" class="card border-0 shadow-sm h-100 text-decoration-none transition-hover dropdown-toggle no-caret" data-bs-toggle="dropdown">
                <div class="card-body p-4 text-center">
                    <div class="icon-circle bg-secondary bg-opacity-10 text-secondary mb-3 mx-auto">
                        <i class="bi bi-grid"></i>
                    </div>
                    <h6 class="fw-bold mb-0">Services</h6>
                    <small class="text-muted">Wash & Delivery</small>
                </div>
            </a>
            <ul class="dropdown-menu shadow border-0 p-2" style="border-radius: 12px;">
                <li><a class="dropdown-item rounded-3" href="../car_wash.php"><i class="bi bi-car-front me-2 text-info"></i>Car Wash</a></li>
                <li><a class="dropdown-item rounded-3" href="../fuel_delivery.php"><i class="bi bi-truck me-2 text-primary"></i>Fuel Delivery</a></li>
            </ul>
        </div>
    </div>
    <?php if (isAdmin()): ?>
    <div class="col-6 col-lg-3">
        <a href="../admin_audit_trail.php" class="card border-0 shadow-sm h-100 text-decoration-none transition-hover">
            <div class="card-body p-4 text-center">
                <div class="icon-circle bg-dark bg-opacity-10 text-dark mb-3 mx-auto">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h6 class="fw-bold mb-0">Admin</h6>
                <small class="text-muted">Settings & Logs</small>
            </div>
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="row align-items-center mb-4">
    <div class="col">
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-lines-fill me-2 text-info"></i> Recent Customer Activity</h4>
    </div>
    <?php if (isAdmin()): ?>
    <div class="col-auto">
        <a href="create.php" class="btn btn-info rounded-pill px-4 text-white shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add New Customer
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold"><i class="bi bi-list-stars me-2 text-primary"></i> All Customers & Service Status</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle datatable">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Customer Info</th>
                        <th>Vehicle Plate</th>
                        <th>Pending Services</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td class="fw-bold">#<?php echo $customer['customer_id']; ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $customer['customer_id']; ?>" class="text-decoration-none">
                                <div class="fw-bold text-dark hover-teal"><?php echo htmlspecialchars($customer['name']); ?></div>
                            </a>
                            <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($customer['phone']); ?></div>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                <i class="bi bi-car-front me-1"></i><?php echo htmlspecialchars($customer['vehicle_plate'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($customer['pending_washes'] > 0): ?>
                                <span class="badge bg-info rounded-pill" title="Pending Car Wash">
                                    <i class="bi bi-droplet"></i> <?php echo $customer['pending_washes']; ?> Wash
                                </span>
                            <?php endif; ?>
                            <?php if ($customer['pending_deliveries'] > 0): ?>
                                <span class="badge bg-warning text-dark rounded-pill" title="Pending Fuel Delivery">
                                    <i class="bi bi-truck"></i> <?php echo $customer['pending_deliveries']; ?> Fuel
                                </span>
                            <?php endif; ?>
                            <?php if ($customer['pending_washes'] == 0 && $customer['pending_deliveries'] == 0): ?>
                                <span class="text-muted small">No pending requests</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($customer['pending_washes'] > 0 || $customer['pending_deliveries'] > 0): ?>
                                <span class="status-badge warning"><i class="bi bi-clock-history me-1"></i> Attention Req.</span>
                            <?php else: ?>
                                <span class="status-badge success"><i class="bi bi-check-circle me-1"></i> Clear</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="view.php?id=<?php echo $customer['customer_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary rounded-circle" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (isAdmin()): ?>
                                <a href="edit.php?id=<?php echo $customer['customer_id']; ?>" 
                                   class="btn btn-sm btn-outline-info rounded-circle" title="Edit Profile">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="index.php?delete=<?php echo $customer['customer_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-circle" 
                                   onclick="return confirm('Are you sure you want to delete this customer? All their records will be removed.')"
                                   title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border py-1 px-2 small ms-2">View Only</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
