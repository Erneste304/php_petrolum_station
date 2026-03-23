<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';
requirePermission('fuel_delivery');

$error = '';
$success = '';
$fuelTypes = [];
$myOrders = [];

$customerId = $_SESSION['customer_id'] ?? null;

if (!$customerId) {
    if (isAdmin()) {
        $error = "As an admin, you can view this page but cannot place a fuel order without a customer profile.";
    } else {
        $error = "Customer profile not found. Please contact support.";
    }
} else {
    // 1. Fetch Fuel Types
    try {
        $stmt = $pdo->query('SELECT fuel_id, fuel_name, price_per_liter FROM fuel_type ORDER BY fuel_name');
        $fuelTypes = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = "Failed to load fuel types.";
    }

    // 2. Handle Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
        $fuelId = $_POST['fuel_id'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        $deliveryDate = $_POST['delivery_date'] ?? '';
        $deliveryAddress = trim($_POST['delivery_address'] ?? '');

        if (empty($fuelId) || empty($quantity) || empty($deliveryDate) || empty($deliveryAddress)) {
            $error = 'All fields are required.';
        } elseif ($quantity < 500) {
            $error = 'Minimum delivery order is 500 Liters.';
        } elseif (strtotime($deliveryDate) < strtotime('today')) {
            $error = 'Delivery date cannot be in the past.';
        } else {
            // Find price to calculate total cost
            $pricePerLiter = 0;
            foreach ($fuelTypes as $ft) {
                if ($ft['fuel_id'] == $fuelId) {
                    $pricePerLiter = $ft['price_per_liter'];
                    break;
                }
            }
            
            if ($pricePerLiter > 0) {
                $totalCost = $quantity * $pricePerLiter;
                
                try {
                    $stmt = $pdo->prepare('
                        INSERT INTO bulk_fuel_order (customer_id, fuel_id, quantity_liters, delivery_date, delivery_address, total_estimated_cost) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([$customerId, $fuelId, $quantity, $deliveryDate, $deliveryAddress, $totalCost]);
                    $success = 'Your bulk fuel delivery order has been placed successfully!';
                } catch (PDOException $e) {
                    $error = 'Failed to place order. Please try again later.';
                    // error_log($e->getMessage()); 
                }
            } else {
                $error = 'Invalid fuel type selected.';
            }
        }
    }

    // 3. Fetch User's Orders
    try {
        $stmt = $pdo->prepare('
            SELECT o.*, f.fuel_name 
            FROM bulk_fuel_order o
            JOIN fuel_type f ON o.fuel_id = f.fuel_id
            WHERE o.customer_id = ?
            ORDER BY o.created_at DESC
        ');
        $stmt->execute([$customerId]);
        $myOrders = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Handle error silently
    }
}

// Pass fuel types to Javascript for live calculation
$fuelJson = json_encode($fuelTypes);

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-truck text-primary me-2"></i> Bulk Fuel Delivery</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Fuel Delivery</li>
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

<?php if ($customerId): ?>
    <div class="row">
        <!-- Order Form -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100 border-primary border-top border-3">
                <div class="card-header bg-white pt-3 pb-2">
                    <h5 class="mb-0 text-primary"><i class="bi bi-file-earmark-text me-2"></i> New Delivery Order</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="fuel_delivery.php" id="orderForm">
                        <input type="hidden" name="place_order" value="1">
                        
                        <div class="mb-3">
                            <label for="fuel_id" class="form-label fw-bold">Fuel Type</label>
                            <select class="form-select" id="fuel_id" name="fuel_id" required>
                                <option value="" disabled selected>Select Fuel...</option>
                                <?php foreach ($fuelTypes as $ft): ?>
                                    <option value="<?php echo $ft['fuel_id']; ?>">
                                        <?php echo htmlspecialchars($ft['fuel_name']); ?> (RWF <?php echo number_format($ft['price_per_liter'], 0); ?> / L)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="quantity" class="form-label fw-bold">Quantity (Liters)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="quantity" name="quantity" min="500" step="100" placeholder="Min. 500" required>
                                <span class="input-group-text">Liters</span>
                            </div>
                            <small class="text-muted">Minimum order: 500 Liters</small>
                        </div>

                        <div class="mb-3">
                            <label for="delivery_date" class="form-label fw-bold">Requested Delivery Date</label>
                            <input type="date" class="form-control" id="delivery_date" name="delivery_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>

                        <div class="mb-4">
                            <label for="delivery_address" class="form-label fw-bold">Delivery Address</label>
                            <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" placeholder="Enter complete facility or business address..." required></textarea>
                        </div>

                        <!-- Live Cost Estimator -->
                        <div class="bg-light p-3 rounded mb-4 text-center border">
                            <span class="text-muted d-block mb-1">Estimated Total Cost</span>
                            <h3 class="text-success mb-0 fw-bold" id="totalCostDisplay">RWF 0</h3>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Submit Order Request <i class="bi bi-send ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Order History List -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center pt-3 pb-2">
                    <h5 class="mb-0"><i class="bi bi-list-check text-primary me-2"></i> My Delivery Orders</h5>
                    <span class="badge bg-info rounded-pill"><?php echo count($myOrders); ?> Orders</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($myOrders)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-truck-flatbed fs-1 mb-3 d-block opacity-50"></i>
                            <p class="mb-0">You haven't requested any bulk fuel deliveries yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Fuel & Date</th>
                                        <th>Quantity</th>
                                        <th>Total Cost</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($myOrders as $order): ?>
                                        <tr>
                                            <td><span class="text-muted">#<?php echo str_pad($order['order_id'], 4, '0', STR_PAD_LEFT); ?></span></td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($order['fuel_name']); ?></div>
                                                <small class="text-muted"><i class="bi bi-calendar-event me-1"></i><?php echo date('M d, Y', strtotime($order['delivery_date'])); ?></small>
                                            </td>
                                            <td><?php echo number_format($order['quantity_liters'], 0); ?> L</td>
                                            <td class="fw-bold text-success">RWF <?php echo number_format($order['total_estimated_cost'], 0); ?></td>
                                            <td>
                                                <?php 
                                                    $statusClass = 'bg-warning text-dark';
                                                    if ($order['status'] === 'Approved') $statusClass = 'bg-info';
                                                    if ($order['status'] === 'Delivered') $statusClass = 'bg-success';
                                                    if ($order['status'] === 'Cancelled') $statusClass = 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars($order['status']); ?>
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

<!-- Javascript for Live Calculation -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fuelSelect = document.getElementById('fuel_id');
        const quantityInput = document.getElementById('quantity');
        const totalDisplay = document.getElementById('totalCostDisplay');
        const fuelTypes = <?php echo $fuelJson; ?>;

        function calculateTotal() {
            const fuelId = fuelSelect.value;
            const quantity = parseFloat(quantityInput.value) || 0;
            
            if (!fuelId || quantity <= 0) {
                totalDisplay.textContent = 'RWF 0';
                return;
            }

            // Find the price for selected fuel
            let price = 0;
            for(let i = 0; i < fuelTypes.length; i++) {
                if (fuelTypes[i].fuel_id == fuelId) {
                    price = parseFloat(fuelTypes[i].price_per_liter);
                    break;
                }
            }

            const total = price * quantity;
            // Format number with commas
            totalDisplay.textContent = 'RWF ' + total.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
        }

        fuelSelect.addEventListener('change', calculateTotal);
        quantityInput.addEventListener('input', calculateTotal);
    });
</script>

<?php include 'includes/footer.php'; ?>