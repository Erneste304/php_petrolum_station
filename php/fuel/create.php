<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';
requirePermission('fuel');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Insert fuel type
        $stmt = $pdo->prepare("INSERT INTO fuel_type (fuel_name, price_per_liter) VALUES (?, ?)");
        $stmt->execute([$_POST['fuel_name'], $_POST['price_per_liter']]);
        $fuel_id = $pdo->lastInsertId();
        
        // Insert associated tank with initial stock
        $stmt = $pdo->prepare("INSERT INTO tank (fuel_id, capacity, current_stock) VALUES (?, ?, ?)");
        $stmt->execute([$fuel_id, $_POST['capacity'], $_POST['initial_stock']]);
        
        $pdo->commit();
        $_SESSION['success'] = "Fuel type and tank added successfully!";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error adding fuel type: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-plus-circle"></i> Add New Fuel Type</h2>
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
                <i class="bi bi-droplet"></i> Fuel Type Information
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="fuel_name" class="form-label">Fuel Name *</label>
                        <select class="form-select" id="fuel_name" name="fuel_name" required>
                            <option value="">Select Fuel Type</option>
                            <option value="Petrol">Petrol</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Super">Super</option>
                            <option value="Kerosene">Kerosene</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="price_per_liter" class="form-label">Price per Liter (RWF) *</label>
                        <div class="input-group">
                            <span class="input-group-text">RWF</span>
                            <input type="number" step="0.01" min="0" class="form-control" id="price_per_liter" name="price_per_liter" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="capacity" class="form-label">Tank Capacity (Liters) *</label>
                            <input type="number" step="1" min="0" class="form-control" id="capacity" name="capacity" placeholder="e.g. 5000" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="initial_stock" class="form-label">Initial Stock (Liters) *</label>
                            <input type="number" step="1" min="0" class="form-control" id="initial_stock" name="initial_stock" placeholder="e.g. 1000" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Fuel Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
