<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';
requirePermission('customers');

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Fetch customer details
$stmt = $pdo->prepare("SELECT * FROM customer WHERE customer_id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isPartner()) {
        $_SESSION['error'] = "Access denied. Partners have read-only access to customer records.";
        header("Location: index.php");
        exit();
    }
    try {
        $stmt = $pdo->prepare("UPDATE customer SET name = ?, phone = ?, vehicle_plate = ? WHERE customer_id = ?");
        $stmt->execute([$_POST['name'], $_POST['phone'], $_POST['vehicle_plate'], $id]);
        $_SESSION['success'] = "Customer updated successfully!";
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating customer: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-pencil"></i> Edit Customer</h2>
    </div>
    <div class="col-auto">
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-badge"></i> Edit Customer Information
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isPartner()): ?>
                    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Read-Only Access:</strong> As a business partner, you can view customer details but cannot modify them.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($customer['name']); ?>" required <?php echo isPartner() ? 'readonly' : ''; ?>>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number *</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($customer['phone']); ?>" required <?php echo isPartner() ? 'readonly' : ''; ?>>
                    </div>
                    
                    <div class="mb-3">
                        <label for="vehicle_plate" class="form-label">Vehicle Plate</label>
                        <input type="text" class="form-control" id="vehicle_plate" name="vehicle_plate" 
                               value="<?php echo htmlspecialchars($customer['vehicle_plate'] ?? ''); ?>" <?php echo isPartner() ? 'readonly' : ''; ?>>
                    </div>
                    
                    <?php if (!isPartner()): ?>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Customer
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
