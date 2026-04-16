<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';
requirePermission('car_wash');

// Prepare variables for the view
$services = [];
$pendingServices = [];
$myBookings = [];
$allBookings = [];
$error = '';
$success = '';

$userId = $_SESSION['user_id'];
$customerId = $_SESSION['customer_id'] ?? null;
$role = $_SESSION['role'];

if (!$customerId) {
    if (hasPermission('manage_services')) {
        $error = "As a manager/partner, you can view this page but cannot book a car wash without a customer profile.";
    } else {
        $error = "Customer profile not found. Please contact support.";
    }
} else {
    // 1. Handle Booking Submission (Customers)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_wash'])) {
        $serviceId = $_POST['service_id'] ?? '';
        $bookingDate = $_POST['booking_date'] ?? '';
        $bookingTime = $_POST['booking_time'] ?? '';
        $vehiclePlate = trim($_POST['vehicle_plate'] ?? '');

        if (empty($serviceId) || empty($bookingDate) || empty($bookingTime) || empty($vehiclePlate)) {
            $error = 'All booking fields are required.';
        } else {
            // Check if booking date is in the future
            if (strtotime($bookingDate) < strtotime('today')) {
                $error = 'Booking date cannot be in the past.';
            } else {
                try {
                    $stmt = $pdo->prepare('
                        INSERT INTO car_wash_booking (customer_id, service_id, booking_date, booking_time, vehicle_plate) 
                        VALUES (?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([$customerId, $serviceId, $bookingDate, $bookingTime, $vehiclePlate]);
                    $success = 'Your car wash has been scheduled successfully!';
                } catch (PDOException $e) {
                    $error = 'Failed to schedule car wash. Please try again later.';
                }
            }
        }
    }
}

// 2. Handle Service Addition (Partner/Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? 0;
    $duration = $_POST['duration'] ?? 30;
    $isApproved = hasPermission('approve_services') ? 1 : 0;

    if (empty($name) || $price <= 0) {
        $error = 'Service name and price are required.';
    } else {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO car_wash_service (name, description, price, estimated_duration_minutes, is_approved, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$name, $description, $price, $duration, $isApproved, $userId]);
            $success = isAdmin() ? 'Service added successfully!' : 'Service submitted for approval.';
        } catch (PDOException $e) {
            $error = 'Failed to add service.';
        }
    }
}

// 3. Handle Service Approval (Admin Only)
if (isAdmin() && isset($_GET['approve_service'])) {
    try {
        $stmt = $pdo->prepare('UPDATE car_wash_service SET is_approved = 1, approved_by = ? WHERE service_id = ?');
        $stmt->execute([$userId, $_GET['approve_service']]);
        $success = 'Service approved successfully!';
    } catch (PDOException $e) {
        $error = 'Failed to approve service.';
    }
}

// 4. Handle Booking Status Update (Admin/Staff)
if (hasPermission('manage_bookings') && isset($_POST['update_booking_status'])) {
    $bookingId = $_POST['booking_id'];
    $newStatus = $_POST['status'];
    try {
        $stmt = $pdo->prepare('UPDATE car_wash_booking SET status = ? WHERE booking_id = ?');
        $stmt->execute([$newStatus, $bookingId]);
        $success = 'Booking status updated!';
    } catch (PDOException $e) {
        $error = 'Failed to update booking status.';
    }
}

// 5. Fetch Services
try {
    if (isAdmin()) {
        $stmt = $pdo->query('SELECT * FROM car_wash_service WHERE is_approved = 1 ORDER BY price ASC');
        $services = $stmt->fetchAll();
        
        $stmt = $pdo->query('SELECT s.*, u.username as creator FROM car_wash_service s LEFT JOIN users u ON s.created_by = u.user_id WHERE s.is_approved = 0');
        $pendingServices = $stmt->fetchAll();
    } elseif (isPartner()) {
        $stmt = $pdo->prepare('SELECT * FROM car_wash_service WHERE created_by = ? OR is_approved = 1 ORDER BY price ASC');
        $stmt->execute([$userId]);
        $services = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query('SELECT * FROM car_wash_service WHERE is_approved = 1 ORDER BY price ASC');
        $services = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    if (!$error) $error = 'Failed to load services.';
}

// 6. Fetch Bookings
try {
    if ($customerId) {
        $stmt = $pdo->prepare('
            SELECT b.*, s.name as service_name, s.price 
            FROM car_wash_booking b
            JOIN car_wash_service s ON b.service_id = s.service_id
            WHERE b.customer_id = ?
            ORDER BY b.booking_date DESC, b.booking_time DESC
        ');
        $stmt->execute([$customerId]);
        $myBookings = $stmt->fetchAll();
    }

    if (isAdmin() || isStaff()) {
        $stmt = $pdo->query('
            SELECT b.*, s.name as service_name, c.name as customer_name 
            FROM car_wash_booking b
            JOIN car_wash_service s ON b.service_id = s.service_id
            JOIN customer c ON b.customer_id = c.customer_id
            ORDER BY b.booking_date DESC, b.booking_time DESC
        ');
        $allBookings = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Handle error
}

// Include header after logic so alerts can be displayed
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-car-front text-info me-2"></i> Partner Car Wash & Detailing</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Car Wash</li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (hasPermission('approve_services') && !empty($pendingServices)): ?>
    <div class="card shadow-sm mb-4 border-warning">
        <div class="card-header bg-warning bg-opacity-10 text-dark">
            <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i> Pending Service Approvals</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Service Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Submitted By</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingServices as $ps): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($ps['name']); ?></td>
                                <td class="small"><?php echo htmlspecialchars($ps['description']); ?></td>
                                <td>RWF <?php echo number_format($ps['price'], 0); ?></td>
                                <td><?php echo htmlspecialchars($ps['creator'] ?? 'System'); ?></td>
                                <td class="text-end">
                                    <a href="car_wash.php?approve_service=<?php echo $ps['service_id']; ?>" class="btn btn-sm btn-success">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <?php if (hasPermission('manage_services')): ?>
        <!-- Add Service Form -->
        <div class="col-lg-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i> <?php echo isAdmin() ? 'Add New Service' : 'Submit New Service for Approval'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="car_wash.php" class="row g-3">
                        <input type="hidden" name="add_service" value="1">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Service Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Engine Steam Clean" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="Briefly describe the service">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Price (RWF)</label>
                            <input type="number" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Duration (Mins)</label>
                            <input type="number" name="duration" class="form-control" value="30">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($customerId): ?>
        <!-- New Booking Form Column -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-primary"><i class="bi bi-calendar-plus me-2"></i> Schedule a Wash</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="car_wash.php">
                        <input type="hidden" name="book_wash" value="1">
                        
                        <div class="mb-3">
                            <label for="service_id" class="form-label fw-bold">Select Service</label>
                            <select class="form-select" id="service_id" name="service_id" required>
                                <option value="" disabled selected>Choose a package...</option>
                                <?php foreach ($services as $srv): ?>
                                    <option value="<?php echo $srv['service_id']; ?>">
                                        <?php echo htmlspecialchars($srv['name']); ?> - RWF <?php echo number_format($srv['price'], 0); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="booking_date" class="form-label fw-bold">Date</label>
                                <input type="date" class="form-control" id="booking_date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="booking_time" class="form-label fw-bold">Time</label>
                                <input type="time" class="form-control" id="booking_time" name="booking_time" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="vehicle_plate" class="form-label fw-bold">Vehicle Plate Number</label>
                            <input type="text" class="form-control" id="vehicle_plate" name="vehicle_plate" placeholder="e.g. RAA 123 A" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Confirm Booking <i class="bi bi-check2-circle ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Available Services Info Column -->
        <div class="col-lg-7 mb-4">
            <div class="row g-3">
                <?php foreach ($services as $srv): ?>
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm <?php echo $srv['is_approved'] ? 'bg-light' : 'bg-warning bg-opacity-10 border border-warning'; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold mb-0">
                                        <?php echo htmlspecialchars($srv['name']); ?>
                                        <?php if (!$srv['is_approved']): ?>
                                            <span class="badge bg-warning text-dark small ms-1">Pending Approval</span>
                                        <?php endif; ?>
                                    </h6>
                                    <span class="badge bg-success rounded-pill">RWF <?php echo number_format($srv['price'], 0); ?></span>
                                </div>
                                <p class="text-muted small mb-3"><?php echo htmlspecialchars($srv['description']); ?></p>
                                <div class="text-muted small mt-auto">
                                    <i class="bi bi-clock me-1"></i> Est. <?php echo $srv['estimated_duration_minutes']; ?> mins
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (hasPermission('manage_bookings')): ?>
                <!-- Manage All Bookings -->
                <div class="card shadow-sm mt-4 border-info">
                    <div class="card-header bg-info bg-opacity-10 text-dark">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-layout-text-sidebar-reverse me-2"></i> Manage All Bookings</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Vehicle</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allBookings as $bk): ?>
                                        <tr>
                                            <td><?php echo date('M d, H:i', strtotime($bk['booking_date'] . ' ' . $bk['booking_time'])); ?></td>
                                            <td class="fw-bold"><?php echo htmlspecialchars($bk['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($bk['service_name']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($bk['vehicle_plate']); ?></span></td>
                                            <td>
                                                <form method="POST" action="car_wash.php" class="d-flex">
                                                    <input type="hidden" name="update_booking_status" value="1">
                                                    <input type="hidden" name="booking_id" value="<?php echo $bk['booking_id']; ?>">
                                                    <select name="status" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                                                        <option value="Pending" <?php echo $bk['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="Confirmed" <?php echo $bk['status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                        <option value="Completed" <?php echo $bk['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                        <option value="Cancelled" <?php echo $bk['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </form>
                                            </td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $bk['status'] === 'Completed' ? 'bg-success' : ($bk['status'] === 'Cancelled' ? 'bg-danger' : 'bg-info'); 
                                                ?>"><?php echo $bk['status']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Booking History Table -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2 text-primary"></i> My Bookings</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($myBookings)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-calendar-x fs-2 mb-2 d-block opacity-50"></i>
                            <p class="mb-0">You have no car wash bookings yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Service</th>
                                        <th>Vehicle</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($myBookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></div>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($booking['booking_time'])); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($booking['vehicle_plate']); ?></span></td>
                                            <td>
                                                <?php 
                                                    $statusClass = 'bg-warning text-dark';
                                                    if ($booking['status'] === 'Confirmed') $statusClass = 'bg-info bg-opacity-25 text-info border border-info';
                                                    if ($booking['status'] === 'Completed') $statusClass = 'bg-success';
                                                    if ($booking['status'] === 'Cancelled') $statusClass = 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars($booking['status']); ?>
                                                </span>
                                            </td>
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
<?php endif; ?>

<?php include 'includes/footer.php'; ?>