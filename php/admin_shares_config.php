<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

if (!isAdmin()) {
    $_SESSION['error'] = "Access denied. Only Administrators can configure shares.";
    header("Location: index.php");
    exit();
}

// Fetch current config
$stmt = $pdo->query("SELECT * FROM share_config ORDER BY id DESC LIMIT 1");
$config = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_price'])) {
    $new_price = $_POST['current_price'];
    
    try {
        if ($config) {
            $stmt = $pdo->prepare("UPDATE share_config SET current_price = ?, updated_by = ? WHERE id = ?");
            $stmt->execute([$new_price, $_SESSION['user_id'], $config['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO share_config (current_price, updated_by) VALUES (?, ?)");
            $stmt->execute([$new_price, $_SESSION['user_id']]);
        }
        
        // Log the change in audit trail
        $audit_stmt = $pdo->prepare("
            INSERT INTO transaction_audit 
            (transaction_type, record_id, changed_by, changed_by_role, previous_data, updated_data) 
            VALUES ('share_config', ?, ?, 'admin', ?, ?)
        ");
        $audit_stmt->execute([
            $config['id'] ?? 1,
            $_SESSION['user_id'],
            json_encode(['price' => $config['current_price'] ?? 0]),
            json_encode(['price' => $new_price])
        ]);

        $_SESSION['success'] = "Share price updated successfully to RWF " . number_format($new_price, 2);
        header("Location: admin_shares_config.php");
        exit();
    } catch (Exception $e) {
        $error = "Error updating price: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-gear-wide-connected text-primary me-2"></i> Share Market Configuration</h2>
        <p class="text-muted">Set the live market price for petroleum station shares.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
            <div class="card-header bg-info text-white py-3">
                <h5 class="card-title mb-0"><i class="bi bi-currency-exchange me-2"></i> Update Share Price</h5>
            </div>
            <div class="card-body p-4">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="text-center mb-4">
                    <div class="display-5 fw-bold text-primary">RWF <?php echo number_format($config['current_price'] ?? 0, 2); ?></div>
                    <div class="text-muted small">Current Live Price</div>
                    <div class="text-muted smallest mt-1">Last Updated: <?php echo date('d M Y, H:i', strtotime($config['last_updated'] ?? 'now')); ?></div>
                </div>

                <form method="POST">
                    <div class="mb-4">
                        <label for="current_price" class="form-label fw-bold">New Share Price (RWF)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light border-end-0">RWF</span>
                            <input type="number" step="0.01" min="0.01" class="form-control bg-light border-start-0" 
                                   id="current_price" name="current_price" 
                                   value="<?php echo $config['current_price'] ?? ''; ?>" required>
                        </div>
                        <div class="form-text mt-2">Entering a new price will immediately update the live signal for all partners.</div>
                    </div>
                    
                    <button type="submit" name="update_price" class="btn btn-primary btn-lg w-100 rounded-pill shadow-sm">
                        <i class="bi bi-check2-circle me-2"></i> Update Live Price
                    </button>
                </form>
            </div>
            <div class="card-footer bg-light border-0 py-3 text-center">
                <span class="text-muted small"><i class="bi bi-info-circle me-1"></i> Changes are tracked in the transaction audit trail.</span>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
