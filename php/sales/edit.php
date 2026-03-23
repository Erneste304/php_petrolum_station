<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';
requirePermission('sales');

// Check if ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No sale ID provided";
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Fetch sale data with details
$stmt = $pdo->prepare("
    SELECT s.*, pu.pump_id, pu.fuel_id, f.price_per_liter,
           pay.payment_method, pay.payment_id
    FROM sale s
    JOIN pump pu ON s.pump_id = pu.pump_id
    JOIN fuel_type f ON pu.fuel_id = f.fuel_id
    LEFT JOIN payment pay ON s.sale_id = pay.sale_id
    WHERE s.sale_id = ?
");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    $_SESSION['error'] = "Sale not found";
    header("Location: index.php");
    exit();
}

// Fetch data for dropdowns
$customers = $pdo->query("SELECT * FROM customer ORDER BY name")->fetchAll();
$employees = $pdo->query("SELECT * FROM employee ORDER BY first_name")->fetchAll();

// Fetch pumps with fuel details
$pumps = $pdo->query("
    SELECT p.*, f.fuel_name, f.price_per_liter, t.current_stock
    FROM pump p
    JOIN fuel_type f ON p.fuel_id = f.fuel_id
    JOIN tank t ON f.fuel_id = t.fuel_id
")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isPartner()) {
        $_SESSION['error'] = "Access denied. Partners have read-only access to sales records.";
        header("Location: index.php");
        exit();
    }
    try {
        $pdo->beginTransaction();
        
        // ---- AUDIT: capture snapshot BEFORE update ----
        $previous_snapshot = [
            'customer_id'    => $sale['customer_id'],
            'employee_id'    => $sale['employee_id'],
            'pump_id'        => $sale['pump_id'],
            'quantity'       => $sale['quantity'],
            'total_amount'   => $sale['total_amount'],
            'payment_method' => $sale['payment_method'],
        ];

        // Get old sale quantity to adjust stock
        $old_quantity = $sale['quantity'];
        $new_quantity = $_POST['quantity'];
        
        // Get pump details for price
        $stmt = $pdo->prepare("
            SELECT f.price_per_liter 
            FROM pump p
            JOIN fuel_type f ON p.fuel_id = f.fuel_id
            WHERE p.pump_id = ?
        ");
        $stmt->execute([$_POST['pump_id']]);
        $pump = $stmt->fetch();
        
        $total_amount = $new_quantity * $pump['price_per_liter'];
        
        // Update sale
        $stmt = $pdo->prepare("
            UPDATE sale 
            SET customer_id = ?, employee_id = ?, pump_id = ?, 
                quantity = ?, total_amount = ? 
            WHERE sale_id = ?
        ");
        $stmt->execute([
            $_POST['customer_id'] ?: null,
            $_POST['employee_id'],
            $_POST['pump_id'],
            $new_quantity,
            $total_amount,
            $id
        ]);
        
        // Update payment
        if ($sale['payment_id']) {
            $stmt = $pdo->prepare("UPDATE payment SET payment_method = ? WHERE sale_id = ?");
            $stmt->execute([$_POST['payment_method'], $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO payment (sale_id, payment_method) VALUES (?, ?)");
            $stmt->execute([$id, $_POST['payment_method']]);
        }
        
        // Adjust tank stock
        if ($sale['pump_id'] != $_POST['pump_id']) {
            // Add quantity back to old tank
            $stmt = $pdo->prepare("
                UPDATE tank t 
                JOIN pump p ON t.fuel_id = p.fuel_id 
                SET t.current_stock = t.current_stock + ? 
                WHERE p.pump_id = ?
            ");
            $stmt->execute([$old_quantity, $sale['pump_id']]);
            
            // Deduct quantity from new tank
            $stmt = $pdo->prepare("
                UPDATE tank t 
                JOIN pump p ON t.fuel_id = p.fuel_id 
                SET t.current_stock = t.current_stock - ? 
                WHERE p.pump_id = ?
            ");
            $stmt->execute([$new_quantity, $_POST['pump_id']]);
        } else {
            // Adjust difference for the same tank
            $quantity_difference = $new_quantity - $old_quantity;
            $stmt = $pdo->prepare("
                UPDATE tank t 
                JOIN pump p ON t.fuel_id = p.fuel_id 
                SET t.current_stock = t.current_stock - ? 
                WHERE p.pump_id = ?
            ");
            $stmt->execute([$quantity_difference, $_POST['pump_id']]);
        }

        // ---- AUDIT: capture snapshot AFTER update ----
        $updated_snapshot = [
            'customer_id'    => $_POST['customer_id'] ?: null,
            'employee_id'    => $_POST['employee_id'],
            'pump_id'        => $_POST['pump_id'],
            'quantity'       => $new_quantity,
            'total_amount'   => $total_amount,
            'payment_method' => $_POST['payment_method'],
        ];

        // Only write an audit record if something actually changed
        if ($previous_snapshot !== $updated_snapshot) {
            $audit_stmt = $pdo->prepare("
                INSERT INTO transaction_audit
                    (transaction_type, record_id, changed_by, changed_by_role, previous_data, updated_data)
                VALUES ('sale', ?, ?, ?, ?, ?)
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
        $_SESSION['success'] = "Sale updated successfully!";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating sale: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-pencil"></i> Edit Sale #<?php echo $id; ?></h2>
    </div>
    <div class="col-auto">
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Sales
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-receipt"></i> Edit Sale Information
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isPartner()): ?>
                    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Read-Only Access:</strong> As a business partner, you can view this transaction but cannot modify it.
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="saleForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="customer_id" class="form-label">Customer</label>
                            <select class="form-select" name="customer_id" id="customer_id" <?php echo isPartner() ? 'disabled' : ''; ?>>
                                <option value="">Walk-in Customer</option>
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['customer_id']; ?>"
                                    <?php echo $sale['customer_id'] == $customer['customer_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($customer['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="employee_id" class="form-label">Employee *</label>
                            <select class="form-select" name="employee_id" id="employee_id" required <?php echo isPartner() ? 'disabled' : ''; ?>>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['employee_id']; ?>"
                                    <?php echo $sale['employee_id'] == $employee['employee_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="pump_id" class="form-label">Fuel Type *</label>
                            <select class="form-select" name="pump_id" id="pump_id" required onchange="updatePrice()" <?php echo isPartner() ? 'disabled' : ''; ?>>
                                <option value="">Select Fuel</option>
                                <?php foreach ($pumps as $pump): ?>
                                <option value="<?php echo $pump['pump_id']; ?>" 
                                        data-price="<?php echo $pump['price_per_liter']; ?>"
                                        data-stock="<?php echo $pump['current_stock'] + ($pump['pump_id'] == $sale['pump_id'] ? $sale['quantity'] : 0); ?>"
                                    <?php echo $sale['pump_id'] == $pump['pump_id'] ? 'selected' : ''; ?>>
                                    <?php echo $pump['fuel_name']; ?> - RWF <?php echo number_format($pump['price_per_liter'], 0); ?>/L 
                                    (Stock: <?php echo $pump['current_stock'] + ($pump['pump_id'] == $sale['pump_id'] ? $sale['quantity'] : 0); ?> L)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label">Quantity (Liters) *</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" 
                                   id="quantity" name="quantity" value="<?php echo $sale['quantity']; ?>" 
                                   required onchange="calculateTotal()" <?php echo isPartner() ? 'readonly' : ''; ?>>
                            <small class="text-muted" id="stockWarning"></small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price_per_liter" class="form-label">Price per Liter (RWF)</label>
                            <input type="text" class="form-control" id="price_per_liter" 
                                   value="RWF <?php echo number_format($sale['price_per_liter'], 0); ?>" readonly>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="total_amount" class="form-label">Total Amount (RWF)</label>
                            <input type="text" class="form-control" id="total_amount_display" 
                                   value="RWF <?php echo number_format($sale['total_amount'], 0); ?>" readonly disabled>
                            <input type="hidden" name="total_amount" id="total_amount_hidden" 
                                   value="<?php echo $sale['total_amount']; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method *</label>
                        <select class="form-select" name="payment_method" required <?php echo isPartner() ? 'disabled' : ''; ?>>
                            <option value="Cash" <?php echo $sale['payment_method'] == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="Mobile Money" <?php echo $sale['payment_method'] == 'Mobile Money' ? 'selected' : ''; ?>>Mobile Money</option>
                            <option value="Card" <?php echo $sale['payment_method'] == 'Card' ? 'selected' : ''; ?>>Credit/Debit Card</option>
                        </select>
                    </div>
                    
                    <?php if (!isPartner()): ?>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Sale
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function updatePrice() {
    const pumpSelect = document.getElementById('pump_id');
    const selectedOption = pumpSelect.options[pumpSelect.selectedIndex];
    const price = selectedOption.dataset.price;
    const stock = selectedOption.dataset.stock;
    
    document.getElementById('price_per_liter').value = price ? 'RWF ' + Number(price).toLocaleString() : '';
    document.getElementById('stockWarning').innerHTML = stock ? 'Available stock: ' + stock + ' L' : '';
    
    calculateTotal();
}

function calculateTotal() {
    const pumpSelect = document.getElementById('pump_id');
    const quantity = document.getElementById('quantity').value;
    const selectedOption = pumpSelect.options[pumpSelect.selectedIndex];
    const price = selectedOption.dataset.price;
    const stock = selectedOption.dataset.stock;
    
    // Validate quantity against stock
    if (stock && quantity > parseFloat(stock)) {
        alert('Warning: Quantity exceeds available stock!');
    }
    
    if (price && quantity) {
        const total = quantity * price;
        document.getElementById('total_amount_display').value = 'RWF ' + total.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0});
        document.getElementById('total_amount_hidden').value = total;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePrice();
});
</script>

<?php include '../includes/footer.php'; ?>
