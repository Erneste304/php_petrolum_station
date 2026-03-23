<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

if (!isAdmin()) {
    $_SESSION['error'] = "Access denied.";
    header("Location: index.php");
    exit();
}

// Handle Admin Approval
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_order'])) {
    $order_id = $_POST['order_id'];
    try {
        $stmt = $pdo->prepare("UPDATE bulk_fuel_order SET status = 'Approved' WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $_SESSION['success'] = "Bulk Delivery #$order_id Approved.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch pending bulk orders
$orders = $pdo->query("
    SELECT o.*, c.name as customer_name, f.fuel_name
    FROM bulk_fuel_order o
    JOIN customer c ON o.customer_id = c.customer_id
    JOIN fuel_type f ON o.fuel_id = f.fuel_id
    WHERE o.status = 'Pending'
    ORDER BY o.created_at DESC
")->fetchAll();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-truck text-dark me-2"></i> Bulk Delivery Approvals</h2>
        <p class="text-muted">Review and certify large-scale fuel delivery requests from premium customers.</p>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 15px;">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="bg-dark text-white">
                <tr>
                    <th class="ps-4 py-3">Customer</th>
                    <th class="py-3">Fuel Type</th>
                    <th class="py-3">Quantity (L)</th>
                    <th class="py-3">Total Value</th>
                    <th class="py-3">Delivery Date</th>
                    <th class="py-3 text-end pe-4">Certification</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">No pending bulk deliveries found.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?php echo htmlspecialchars($o['customer_name']); ?></td>
                        <td><span class="badge bg-secondary px-3 rounded-pill"><?php echo htmlspecialchars($o['fuel_name']); ?></span></td>
                        <td><?php echo number_format($o['quantity_liters'], 0); ?> L</td>
                        <td class="fw-bold">RWF <?php echo number_format($o['total_estimated_cost'], 2); ?></td>
                        <td><i class="bi bi-calendar-event me-2"></i><?php echo date('d M, Y', strtotime($o['delivery_date'])); ?></td>
                        <td class="text-end pe-4">
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                                <button type="submit" name="approve_order" class="btn btn-dark btn-sm rounded-pill px-4 fw-bold">
                                    <i class="bi bi-patch-check-fill text-warning me-2"></i> Approve Delivery
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
