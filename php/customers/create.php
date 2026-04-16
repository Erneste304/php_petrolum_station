<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';
requirePermission('customers');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO customer (name, phone, vehicle_plate) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['phone'], $_POST['vehicle_plate']]);
        $_SESSION['success'] = "Customer added successfully!";
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error adding customer: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-plus-circle"></i> Add New Customer</h2>
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
                <i class="bi bi-person-badge"></i> Customer Information
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number *</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="vehicle_plate" class="form-label">Vehicle Plate</label>
                        <input type="text" class="form-control" id="vehicle_plate" name="vehicle_plate">
                        <div class="form-text">Optional: Enter vehicle registration number</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
