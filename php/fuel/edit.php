<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';
requirePermission('fuel');

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Fetch fuel details with tank capacity
$stmt = $pdo->prepare("
    SELECT f.*, t.capacity, t.tank_id 
    FROM fuel_type f 
    LEFT JOIN tank t ON f.fuel_id = t.fuel_id 
    WHERE f.fuel_id = ?
");
$stmt->execute([$id]);
$fuel = $stmt->fetch();

if (!$fuel) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isPartner()) {
        $_SESSION['error'] = "Access denied. Partners have read-only access to fuel settings.";
        header("Location: index.php");
        exit();
    }
    try {
        $pdo->beginTransaction();

        // ---- AUDIT: capture snapshot BEFORE update ----
        $previous_snapshot = [
            'fuel_name'      => $fuel['fuel_name'],
            'price_per_liter'=> $fuel['price_per_liter'],
            'tank_capacity'  => $fuel['capacity'],
        ];
        
        // Update fuel type
        $stmt = $pdo->prepare("UPDATE fuel_type SET fuel_name = ?, price_per_liter = ? WHERE fuel_id = ?");
        $stmt->execute([$_POST['fuel_name'], $_POST['price_per_liter'], $id]);
        
        // Update tank capacity
        $stmt = $pdo->prepare("UPDATE tank SET capacity = ? WHERE fuel_id = ?");
        $stmt->execute([$_POST['capacity'], $id]);

        // ---- AUDIT: capture snapshot AFTER update ----
        $updated_snapshot = [
            'fuel_name'      => $_POST['fuel_name'],
            'price_per_liter'=> $_POST['price_per_liter'],
            'tank_capacity'  => $_POST['capacity'],
        ];

        // Only log if something changed
        if ($previous_snapshot !== $updated_snapshot) {
            $audit_stmt = $pdo->prepare("
                INSERT INTO transaction_audit
                    (transaction_type, record_id, changed_by, changed_by_role, previous_data, updated_data)
                VALUES ('fuel_type', ?, ?, ?, ?, ?)
            ");
            $audit_stmt->execute([
                $id,
                $_SESSION['user_id'],
                $_SESSION['role'],
                json_encode($previous_snapshot),
                json_encode($updated_snapshot),
            ]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Fuel type updated successfully!";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating fuel type: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-pencil"></i> Edit Fuel Type</h2>
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
                <i class="bi bi-droplet"></i> Edit Fuel Type Information
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isPartner()): ?>
                    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Read-Only Access:</strong> As a business partner, you can view these details but cannot modify them.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="fuel_name" class="form-label">Fuel Name *</label>
                        <select class="form-select" id="fuel_name" name="fuel_name" required <?php echo isPartner() ? 'disabled' : ''; ?>>
                            <option value="">Select Fuel Type</option>
                            <option value="Petrol" <?php echo $fuel['fuel_name'] == 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                            <option value="Diesel" <?php echo $fuel['fuel_name'] == 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                            <option value="Super" <?php echo $fuel['fuel_name'] == 'Super' ? 'selected' : ''; ?>>Super</option>
                            <option value="Kerosene" <?php echo $fuel['fuel_name'] == 'Kerosene' ? 'selected' : ''; ?>>Kerosene</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="price_per_liter" class="form-label">Price per Liter (RWF) *</label>
                        <div class="input-group">
                            <span class="input-group-text">RWF</span>
                            <input type="number" step="0.01" min="0" class="form-control" id="price_per_liter" name="price_per_liter" 
                                   value="<?php echo $fuel['price_per_liter']; ?>" required <?php echo isPartner() ? 'readonly' : ''; ?>>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="capacity" class="form-label">Tank Capacity (Liters) *</label>
                        <input type="number" step="1" min="0" class="form-control" id="capacity" name="capacity" 
                               value="<?php echo $fuel['capacity']; ?>" required <?php echo isPartner() ? 'readonly' : ''; ?>>
                    </div>
                    
                    <?php if (!isPartner()): ?>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Fuel Type
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
