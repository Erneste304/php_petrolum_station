<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

if (!isAdmin() && !isAccountant()) {
    $_SESSION['error'] = "Access denied.";
    header("Location: index.php");
    exit();
}

include 'includes/header.php';

// Fetch recent payments from fuel sales and services
$payments = $pdo->query("
    SELECT p.*, s.total_amount, s.sale_date, c.name as customer_name, f.fuel_name
    FROM payment p
    JOIN sale s ON p.sale_id = s.sale_id
    LEFT JOIN customer c ON s.customer_id = c.customer_id
    JOIN pump pu ON s.pump_id = pu.pump_id
    JOIN fuel_type f ON pu.fuel_id = f.fuel_id
    ORDER BY p.payment_date DESC
    LIMIT 50
")->fetchAll();
?>

<div class="row mb-4 align-items-center">
    <div class="col">
        <h2><i class="bi bi-cash-stack text-primary me-2"></i> Financial Payments</h2>
        <p class="text-muted">Monitor all incoming payments and verify account balances.</p>
    </div>
    <div class="col-auto">
        <div class="badge bg-info px-3 py-2 rounded-pill">
            <i class="bi bi-shield-check me-1"></i> Auditor Ledger
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 20px;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4">Trans ID</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th class="text-end pe-4">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No payment records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="ps-4">#<?php echo $p['payment_id']; ?></td>
                        <td><span class="fw-bold"><?php echo htmlspecialchars($p['customer_name'] ?? 'Walk-in'); ?></span></td>
                        <td><span class="badge bg-info-subtle text-info px-2 py-1"><?php echo htmlspecialchars($p['fuel_name']); ?></span></td>
                        <td>
                            <i class="bi <?php echo $p['payment_method'] === 'Cash' ? 'bi-cash' : 'bi-phone'; ?> me-1"></i>
                            <?php echo $p['payment_method']; ?>
                        </td>
                        <td class="fw-bold text-success">RWF <?php echo number_format($p['amount'], 2); ?></td>
                        <td class="small text-muted"><?php echo date('M d, H:i', strtotime($p['payment_date'])); ?></td>
                        <td class="text-end pe-4">
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 border border-success border-opacity-25">
                                <i class="bi bi-check-circle-fill me-1"></i> Finalized
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
