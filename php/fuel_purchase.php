<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

// Ensure only customers can access this part as customers
if (!isCustomer() && !isAdmin() && !isStaff()) {
    $_SESSION['error'] = "Access denied.";
    header("Location: index.php");
    exit();
}

// Redirect if it's a customer without a linked profile
if (isCustomer() && !isset($_SESSION['customer_id'])) {
    $_SESSION['error'] = "Your account is not linked to a customer profile. Please contact Admin.";
    header("Location: index.php");
    exit();
}

$customer_id = $_SESSION['customer_id'] ?? null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_fuel'])) {
    try {
        $status = ($_POST['payment_method'] === 'Mobile Money') ? 'Approved' : 'Pending';
        $stmt = $pdo->prepare("INSERT INTO fuel_request (customer_id, fuel_id, requested_quantity, payment_method, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $customer_id,
            $_POST['fuel_id'],
            $_POST['quantity'],
            $_POST['payment_method'],
            $status
        ]);
        
        $_SESSION['success'] = "Fuel request submitted successfully! Awaiting staff approval.";
        header("Location: my_purchases.php");
        exit();
    } catch (Exception $e) {
        $error = "Failed to submit request: " . $e->getMessage();
    }
}

// Fetch available fuels
$fuels = $pdo->query("SELECT * FROM fuel_type ORDER BY fuel_name")->fetchAll();

include 'includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="mb-1"><i class="bi bi-droplet-fill text-primary me-2"></i> Request Fuel</h2>
        <p class="text-muted">Select your fuel type and quantity to start a purchase.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm" style="border-radius: 15px;">
            <div class="card-body p-4">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger border-0 shadow-sm"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Fuel Type</label>
                        <select name="fuel_id" class="form-select shadow-sm" required style="border-radius: 10px;">
                            <?php foreach ($fuels as $fuel): ?>
                                <option value="<?php echo $fuel['fuel_id']; ?>">
                                    <?php echo htmlspecialchars($fuel['fuel_name']); ?> (RWF <?php echo number_format($fuel['price_per_liter'], 0); ?>/L)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantity (Liters)</label>
                        <input type="number" step="0.01" name="quantity" class="form-control shadow-sm" required min="1" placeholder="Enter amount in liters" style="border-radius: 10px;">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Payment Method</label>
                        <select name="payment_method" class="form-select shadow-sm" required style="border-radius: 10px;">
                            <option value="Cash">Cash</option>
                            <option value="Mobile Money">Mobile Money</option>
                            <option value="Card">Credit/Debit Card</option>
                        </select>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="request_fuel" class="btn btn-primary py-2 fw-bold" style="border-radius: 10px;">
                            <i class="bi bi-send-fill me-2"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
