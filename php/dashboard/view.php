<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';
requirePermission('customers');

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// 1. Fetch Customer Details
$stmt = $pdo->prepare("SELECT * FROM customer WHERE customer_id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    header("Location: index.php");
    exit();
}

// 2. Fetch Car Wash Bookings
$stmt = $pdo->prepare("
    SELECT b.*, s.name as service_name, s.price 
    FROM car_wash_booking b
    JOIN car_wash_service s ON b.service_id = s.service_id
    WHERE b.customer_id = ?
    ORDER BY b.booking_date DESC, b.booking_time DESC
");
$stmt->execute([$id]);
$bookings = $stmt->fetchAll();

// 3. Fetch Fuel Delivery Orders
$stmt = $pdo->prepare("
    SELECT o.*, f.fuel_name 
    FROM bulk_fuel_order o
    JOIN fuel_type f ON o.fuel_id = f.fuel_id
    WHERE o.customer_id = ?
    ORDER BY o.delivery_date DESC
");
$stmt->execute([$id]);
$orders = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="mb-0"><i class="bi bi-person-badge"></i> <?php echo htmlspecialchars($customer['name']); ?></h2>
            <p class="text-white bg-opacity-75 mb-0">Customer Profile & Service History</p>
        </div>
        <div class="col-auto">
            <a href="index.php" class="btn btn-light rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
            <?php if (isAdmin() || isStaff()): ?>
            <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning rounded-pill px-4 ms-2">
                <i class="bi bi-pencil-square me-1"></i> Edit Profile
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <!-- Customer Info Card -->
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i> Contact Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small text-uppercase">Phone Number</label>
                    <div class="fw-bold fs-5"><?php echo htmlspecialchars($customer['phone']); ?></div>
                </div>
                <div class="mb-3">
                    <label class="text-muted small text-uppercase">Vehicle Plate</label>
                    <div class="fw-bold">
                        <span class="badge bg-info-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                            <i class="bi bi-car-front me-2"></i><?php echo htmlspecialchars($customer['vehicle_plate'] ?? 'N/A'); ?>
                        </span>
                    </div>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <a href="../car_wash.php" class="btn btn-outline-info">
                        <i class="bi bi-calendar-plus me-1"></i> Book Car Wash
                    </a>
                    <a href="../fuel_delivery.php" class="btn btn-outline-warning text-dark">
                        <i class="bi bi-truck me-1"></i> Order Fuel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Activity -->
    <div class="col-lg-8">
        <!-- Car Wash Bookings -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-droplet me-2 text-info"></i> Car Wash Bookings</h5>
                <span class="badge bg-info rounded-pill"><?php echo count($bookings); ?> Total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Service</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $bk): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo date('M d, Y', strtotime($bk['booking_date'])); ?></div>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($bk['booking_time'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($bk['service_name']); ?></td>
                                <td>RWF <?php echo number_format($bk['price'], 0); ?></td>
                                <td>
                                    <?php 
                                        $statusClass = 'warning';
                                        if ($bk['status'] === 'Confirmed') $statusClass = 'info';
                                        if ($bk['status'] === 'Completed') $statusClass = 'success';
                                        if ($bk['status'] === 'Cancelled') $statusClass = 'danger';
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $bk['status']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($bookings)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No car wash bookings found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Fuel Delivery Orders -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-truck me-2 text-warning"></i> Fuel Delivery Orders</h5>
                <span class="badge bg-warning text-dark rounded-pill"><?php echo count($orders); ?> Total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Delivery Date</th>
                                <th>Fuel Type</th>
                                <th>Quantity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $od): ?>
                            <tr>
                                <td class="fw-bold"><?php echo date('M d, Y', strtotime($od['delivery_date'])); ?></td>
                                <td><?php echo htmlspecialchars($od['fuel_name']); ?></td>
                                <td><?php echo number_format($od['quantity_liters'], 1); ?> L</td>
                                <td>
                                    <?php 
                                        $statusClass = 'warning';
                                        if ($od['status'] === 'Approved') $statusClass = 'info';
                                        if ($od['status'] === 'Delivered') $statusClass = 'success';
                                        if ($od['status'] === 'Cancelled') $statusClass = 'danger';
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $od['status']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($orders)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No fuel delivery orders found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
