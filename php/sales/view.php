<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';

// Access control: Admin, Staff, or the specific Customer who owns the sale
$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['error'] = "No sale ID provided";
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT customer_id FROM sale WHERE sale_id = ?");
$stmt->execute([$id]);
$sale_meta = $stmt->fetch();

if (!isAdmin() && !isStaff() && (!isCustomer() || $_SESSION['customer_id'] != ($sale_meta['customer_id'] ?? -1))) {
    $_SESSION['error'] = "Access denied. You can only view your own receipts.";
    header("Location: ../index.php");
    exit();
}
include '../includes/header.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No sale ID provided";
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Fetch sale details
$stmt = $pdo->prepare("
    SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.vehicle_plate,
           CONCAT(e.first_name, ' ', e.last_name) as employee_name, e.position,
           f.fuel_name, f.price_per_liter,
           pay.payment_method, pay.payment_date,
           st.station_name, st.location as station_location
    FROM sale s
    LEFT JOIN customer c ON s.customer_id = c.customer_id
    JOIN employee e ON s.employee_id = e.employee_id
    JOIN pump pu ON s.pump_id = pu.pump_id
    JOIN fuel_type f ON pu.fuel_id = f.fuel_id
    LEFT JOIN payment pay ON s.sale_id = pay.sale_id
    JOIN station st ON e.station_id = st.station_id
    WHERE s.sale_id = ?
");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    $_SESSION['error'] = "Sale not found";
    header("Location: index.php");
    exit();
}
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-receipt"></i> Sale Receipt #<?php echo $id; ?></h2>
    </div>
    <div class="col-auto">
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Sales
        </a>
        <button onclick="window.print()" class="btn btn-info">
            <i class="bi bi-printer"></i> Print Receipt
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card" id="receipt">
            <div class="card-header text-center">
                <h3>Petroleum Station Management System</h3>
                <p><?php echo htmlspecialchars($sale['station_location']); ?></p>
                <p>Tel: <?php echo htmlspecialchars($sale['station_location']); ?></p>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-6">
                        <strong>Receipt No:</strong> #<?php echo str_pad($sale['sale_id'], 6, '0', STR_PAD_LEFT); ?><br>
                        <strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($sale['sale_date'])); ?>
                    </div>
                    <div class="col-6 text-end">
                        <strong>Customer:</strong> <?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer'); ?><br>
                        <?php if ($sale['customer_phone']): ?>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($sale['customer_phone']); ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo $sale['fuel_name']; ?> Fuel</td>
                            <td><?php echo $sale['quantity']; ?> L</td>
                            <td>RWF <?php echo number_format($sale['price_per_liter'], 0); ?></td>
                            <td class="text-end">RWF <?php echo number_format($sale['total_amount'], 0); ?></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total Amount:</th>
                            <th class="text-end">RWF <?php echo number_format($sale['total_amount'], 0); ?></th>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end">
                                <strong>Payment Method:</strong> <?php echo $sale['payment_method']; ?><br>
                                <strong>Payment Date:</strong> <?php echo date('d/m/Y H:i', strtotime($sale['payment_date'])); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                
                <div class="row mt-4">
                    <div class="col-6">
                        <strong>Served by:</strong><br>
                        <?php echo htmlspecialchars($sale['employee_name']); ?><br>
                        (<?php echo htmlspecialchars($sale['position']); ?>)
                    </div>
                    <div class="col-6 text-end">
                        <strong>Vehicle Plate:</strong><br>
                        <?php echo htmlspecialchars($sale['vehicle_plate'] ?? 'N/A'); ?>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p>Thank you for your business!</p>
                    <p>Goods sold are not returnable</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .btn, .footer {
        display: none !important;
    }
    #receipt {
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
