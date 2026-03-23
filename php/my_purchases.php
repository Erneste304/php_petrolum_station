<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

if (!isset($_SESSION['customer_id']) && !isAdmin() && !isStaff()) {
    $_SESSION['error'] = "Customer profile required.";
    header("Location: index.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Fetch Fuel Requests
$requests_stmt = $pdo->prepare("
    SELECT r.*, f.fuel_name, f.price_per_liter 
    FROM fuel_request r 
    JOIN fuel_type f ON r.fuel_id = f.fuel_id 
    WHERE r.customer_id = ? 
    ORDER BY r.created_at DESC
");
$requests_stmt->execute([$customer_id]);
$requests = $requests_stmt->fetchAll();

// Fetch Completed Sales (Receipts)
$sales_stmt = $pdo->prepare("
    SELECT s.*, f.fuel_name, pay.payment_method, pay.payment_date
    FROM sale s
    JOIN pump p ON s.pump_id = p.pump_id
    JOIN fuel_type f ON p.fuel_id = f.fuel_id
    LEFT JOIN payment pay ON s.sale_id = pay.sale_id
    WHERE s.customer_id = ?
    ORDER BY s.sale_date DESC
");
$sales_stmt->execute([$customer_id]);
$sales = $sales_stmt->fetchAll();

include 'includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="mb-1"><i class="bi bi-clock-history text-success me-2"></i> My History & Receipts</h2>
        <p class="text-muted">Track your fuel requests and download receipts for completed purchases.</p>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success border-0 shadow-sm mb-4">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<!-- Pending & Current Requests -->
<div class="card border-0 shadow-sm mb-5" style="border-radius: 15px;">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="mb-0 fw-bold"><i class="bi bi-hourglass-split me-2 text-warning"></i> Fuel Requests</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Fuel Type</th>
                        <th>Quantity</th>
                        <th>Estimated Cost</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No requests found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?php echo htmlspecialchars($req['fuel_name']); ?></td>
                            <td><?php echo $req['requested_quantity']; ?> L</td>
                            <td class="fw-bold text-dark">RWF <?php echo number_format($req['requested_quantity'] * $req['price_per_liter'], 0); ?></td>
                            <td>
                                <span class="badge rounded-pill <?php 
                                    echo $req['status'] == 'Pending' ? 'bg-warning text-dark' : 
                                        ($req['status'] == 'Approved' ? 'bg-info text-dark' : 
                                        ($req['status'] == 'Completed' ? 'bg-success' : 'bg-secondary')); 
                                ?>">
                                    <?php echo $req['status']; ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?php echo date('M d, Y H:i', strtotime($req['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Completed Purchases & Receipts -->
<div class="card border-0 shadow-sm" style="border-radius: 15px;">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="mb-0 fw-bold"><i class="bi bi-receipt-cutoff me-2 text-primary"></i> Completed Purchases</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Sale ID</th>
                        <th>Fuel</th>
                        <th>Quantity</th>
                        <th>Total Amount</th>
                        <th>Date</th>
                        <th class="text-end pe-4">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No completed purchases.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td class="ps-4">#<?php echo str_pad($sale['sale_id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($sale['fuel_name']); ?></td>
                            <td><?php echo $sale['quantity']; ?> L</td>
                            <td class="fw-bold">RWF <?php echo number_format($sale['total_amount'], 0); ?></td>
                            <td class="small text-muted"><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                            <td class="text-end pe-4">
                                <a href="sales/view.php?id=<?php echo $sale['sale_id']; ?>" class="btn btn-sm btn-outline-primary px-3 shadow-sm rounded-pill">
                                    <i class="bi bi-download me-1"></i> View Receipt
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
