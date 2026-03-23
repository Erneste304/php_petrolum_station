<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';

requirePermission('sales');

// Fetch data for dropdowns
$customers = $pdo->query("SELECT * FROM customer ORDER BY name")->fetchAll();
$employees = $pdo->query("SELECT * FROM employee ORDER BY first_name")->fetchAll();

// Fetch pumps with fuel details and current stock
$pumps = $pdo->query("
    SELECT p.*, f.fuel_name, f.price_per_liter, t.current_stock, t.tank_id 
    FROM pump p
    JOIN fuel_type f ON p.fuel_id = f.fuel_id
    JOIN tank t ON f.fuel_id = t.fuel_id
    WHERE t.current_stock > 0
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'cancel_request') {
        try {
            if (!isset($_POST['request_id']) || empty($_POST['request_id'])) {
                throw new Exception("No request ID provided to cancel.");
            }
            $stmt = $pdo->prepare("UPDATE fuel_request SET status = 'Cancelled' WHERE request_id = ?");
            $stmt->execute([$_POST['request_id']]);
            $_SESSION['success'] = "Fuel request #" . $_POST['request_id'] . " has been cancelled.";
            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            $error = "Error cancelling request: " . $e->getMessage();
        }
    } else {
        try {
            if (!isset($_POST['pump_id']) || empty($_POST['pump_id'])) {
                throw new Exception("Please select a fuel pump/type.");
            }
            if (!isset($_POST['quantity']) || $_POST['quantity'] <= 0) {
                throw new Exception("Please enter a valid quantity.");
            }

            $pdo->beginTransaction();
            
            // Get pump details for price
            $stmt = $pdo->prepare("
                SELECT f.price_per_liter, f.fuel_id 
                FROM pump p
                JOIN fuel_type f ON p.fuel_id = f.fuel_id
                WHERE p.pump_id = ?
            ");
            $stmt->execute([$_POST['pump_id']]);
            $pump = $stmt->fetch();
            
            if (!$pump) {
                throw new Exception("Selected pump information not found.");
            }
            
            $total_amount = $_POST['quantity'] * $pump['price_per_liter'];
            
            // Insert sale
            $stmt = $pdo->prepare("
                INSERT INTO sale (customer_id, employee_id, pump_id, quantity, total_amount) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['customer_id'] ?: null,
                $_POST['employee_id'],
                $_POST['pump_id'],
                $_POST['quantity'],
                $total_amount
            ]);
            
            $sale_id = $pdo->lastInsertId();
            
            // Insert payment
            $stmt = $pdo->prepare("INSERT INTO payment (sale_id, payment_method) VALUES (?, ?)");
            $stmt->execute([$sale_id, $_POST['payment_method']]);
            
            // Update tank stock
            $stmt = $pdo->prepare("
                UPDATE tank t 
                JOIN pump p ON t.fuel_id = p.fuel_id 
                SET t.current_stock = t.current_stock - ? 
                WHERE p.pump_id = ?
            ");
            $stmt->execute([$_POST['quantity'], $_POST['pump_id']]);

            // If this sale was from a request, mark the request as completed
            if (isset($_POST['request_id']) && !empty($_POST['request_id'])) {
                $stmt = $pdo->prepare("UPDATE fuel_request SET status = 'Completed' WHERE request_id = ?");
                $stmt->execute([$_POST['request_id']]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Sale recorded successfully! Total: RWF " . number_format($total_amount, 0);
            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "Error recording sale: " . $e->getMessage();
        }
    }
}

// Handle pre-filled request data
$pre_request_id = $_GET['request_id'] ?? '';
$pre_customer_id = $_GET['customer_id'] ?? '';
$pre_fuel_id = $_GET['fuel_id'] ?? '';
$pre_qty = $_GET['qty'] ?? '';

include '../includes/header.php';
?>

<style>
:root {
    --primary-hsl: 243, 75%, 59%;
    --glass-bg: rgba(255, 255, 255, 0.7);
    --glass-border: rgba(255, 255, 255, 0.3);
}

.sale-body {
    background: linear-gradient(135deg, hsl(var(--primary-hsl), 0.1) 0%, #f8f9fa 100%);
    min-height: calc(100vh - 56px);
    padding: 3rem 0;
}

.glass-card {
    background: var(--glass-bg);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border: 1px solid var(--glass-border);
    border-radius: 24px;
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
}

.premium-input {
    border-radius: 12px;
    border: 2px solid #eee;
    padding: 12px 16px;
    transition: all 0.3s ease;
}

.premium-input:focus {
    border-color: hsl(var(--primary-hsl));
    box-shadow: 0 0 0 0.25rem hsla(var(--primary-hsl), 0.2);
}

.price-badge {
    background: hsla(var(--primary-hsl), 0.1);
    color: hsl(var(--primary-hsl));
    border: 1px solid hsla(var(--primary-hsl), 0.2);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
}

.hero-title {
    font-weight: 800;
    background: linear-gradient(45deg, hsl(var(--primary-hsl)), #2575fc);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}
</style>

<div class="sale-body">
    <div class="container">
        <div class="row mb-5 align-items-center">
            <div class="col-md-8">
                <a href="index.php" class="btn btn-link text-decoration-none text-muted mb-2 d-inline-block">
                    <i class="bi bi-arrow-left me-2"></i> Back to Sales
                </a>
                <h1 class="hero-title display-5 mb-0">Record New Sale</h1>
                <p class="text-muted fs-5">Process fuel transactions with precision and speed.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <img src="../img/pos-system.svg" class="img-fluid d-none d-md-block ms-auto" style="max-height: 120px;" alt="POS">
            </div>
        </div>

        <form method="POST" id="saleForm">
            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($pre_request_id); ?>">
            
            <div class="row g-4">
                <!-- Main Form Column -->
                <div class="col-lg-8">
                    <div class="glass-card p-4 p-md-5">
                        <h4 class="fw-bold mb-4"><i class="bi bi-info-circle me-2 text-primary"></i> Transaction Details</h4>
                        
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Select Pump / Fuel Type <span class="text-danger">*</span></label>
                                <select name="pump_id" id="pump_select" class="form-select premium-input" required onchange="calculateTotal()">
                                    <option value="">-- Choose Fuel Source --</option>
                                    <?php foreach ($pumps as $p): ?>
                                        <option value="<?php echo $p['pump_id']; ?>" 
                                                data-price="<?php echo $p['price_per_liter']; ?>"
                                                data-stock="<?php echo $p['current_stock']; ?>"
                                                <?php echo ($pre_fuel_id == $p['fuel_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['pump_name'] . ' - ' . $p['fuel_name']); ?> 
                                            (Stock: <?php echo number_format($p['current_stock'], 1); ?>L)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Customer</label>
                                <select name="customer_id" class="form-select premium-input">
                                    <option value="">Walk-in Customer</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?php echo $c['customer_id']; ?>" 
                                                <?php echo ($pre_customer_id == $c['customer_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['phone']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Employee / Attendant <span class="text-danger">*</span></label>
                                <select name="employee_id" class="form-select premium-input" required>
                                    <option value="">-- Select Attendant --</option>
                                    <?php foreach ($employees as $e): ?>
                                        <option value="<?php echo $e['employee_id']; ?>">
                                            <?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-12 mt-4">
                                <label class="form-label fw-bold fs-5">Quantity (Liters) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="quantity" id="quantity" 
                                           class="form-control premium-input fs-4 fw-bold" 
                                           placeholder="0.00" 
                                           value="<?php echo htmlspecialchars($pre_qty); ?>" 
                                           required oninput="calculateTotal()">
                                    <span class="input-group-text bg-white border-0 fw-bold">LTR</span>
                                </div>
                                <div id="stock_warning" class="text-danger small mt-1 d-none">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Warning: Quantity exceeds available stock!
                                </div>
                            </div>

                            <div class="col-md-12 mt-4">
                                <label class="form-label fw-bold">Payment Method</label>
                                <div class="row g-3">
                                    <?php 
                                    $methods = [
                                        ['id' => 'Cash', 'icon' => 'bi-cash-coin', 'color' => '#198754'],
                                        ['id' => 'Mobile Money', 'icon' => 'bi-phone', 'color' => '#ffc107'],
                                        ['id' => 'Credit Card', 'icon' => 'bi-credit-card', 'color' => '#0d6efd']
                                    ];
                                    foreach ($methods as $m): ?>
                                    <div class="col-md-4">
                                        <input type="radio" class="btn-check" name="payment_method" id="pay_<?php echo $m['id']; ?>" value="<?php echo $m['id']; ?>" <?php echo $m['id'] == 'Cash' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-secondary w-100 p-3 rounded-4 d-flex flex-column align-items-center" for="pay_<?php echo $m['id']; ?>">
                                            <i class="bi <?php echo $m['icon']; ?> fs-2 mb-2"></i>
                                            <span class="fw-bold"><?php echo $m['id']; ?></span>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Column -->
                <div class="col-lg-4">
                    <div class="glass-card p-4 sticky-top" style="top: 2rem;">
                        <h4 class="fw-bold mb-4">Order Summary</h4>
                        
                        <div class="price-badge mb-4">
                            <span class="text-muted small text-uppercase fw-bold">Total Amount Due</span>
                            <div class="display-6 fw-bold mt-1">RWF <span id="display_total">0</span></div>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Price / Liter</span>
                            <span class="fw-bold">RWF <span id="display_price">0</span></span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-muted">Quantity</span>
                            <span class="fw-bold"><span id="display_qty text-primary">0.00</span> L</span>
                        </div>

                        <button type="submit" name="action" value="complete_sale" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-lg mt-3">
                            <i class="bi bi-check-circle-fill me-2"></i> COMPLETE SALE
                        </button>
                        
                        <?php if ($pre_request_id): ?>
                            <button type="submit" name="action" value="cancel_request" class="btn btn-outline-danger w-100 py-2 rounded-pill fw-bold mt-3" onclick="return confirm('Are you sure you want to cancel this request?')">
                                <i class="bi bi-x-circle me-1"></i> Cancel Request
                            </button>
                        <?php endif; ?>
                        
                        <div class="mt-4 p-3 bg-light rounded-4 small text-muted">
                            <i class="bi bi-shield-lock me-1 text-success"></i> Secure Transaction verified by Petroleum MS.
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function calculateTotal() {
    const pumpSelect = document.getElementById('pump_select');
    const quantityInput = document.getElementById('quantity');
    const displayTotal = document.getElementById('display_total');
    const displayPrice = document.getElementById('display_price');
    const displayQty = document.getElementById('display_qty');
    const stockWarning = document.getElementById('stock_warning');
    
    const selectedOption = pumpSelect.options[pumpSelect.selectedIndex];
    const price = selectedOption ? parseFloat(selectedOption.getAttribute('data-price')) || 0 : 0;
    const stock = selectedOption ? parseFloat(selectedOption.getAttribute('data-stock')) || 0 : 0;
    const quantity = parseFloat(quantityInput.value) || 0;
    
    const total = price * quantity;
    
    displayTotal.innerText = new Intl.NumberFormat().format(total);
    displayPrice.innerText = new Intl.NumberFormat().format(price);
    
    if (quantity > stock) {
        stockWarning.classList.remove('d-none');
    } else {
        stockWarning.classList.add('d-none');
    }
}

// Initial calculation if pre-filled
document.addEventListener('DOMContentLoaded', calculateTotal);
</script>

<?php include '../includes/footer.php'; ?>
