<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

if (!isAdmin() && !isAccountant()) {
    $_SESSION['error'] = "Access denied.";
    header("Location: index.php");
    exit();
}

$staff_filter = $_GET['staff'] ?? '';
$date_range = $_GET['range'] ?? 'week';

$interval = "1 WEEK";
if ($date_range == 'month') $interval = "1 MONTH";
if ($date_range == '3month') $interval = "3 MONTH";

$sql = "
    SELECT s.*, c.name as customer_name, f.fuel_name, 
           CONCAT(e.first_name, ' ', e.last_name) as staff_name,
           pay.payment_method
    FROM sale s
    LEFT JOIN customer c ON s.customer_id = c.customer_id
    JOIN employee e ON s.employee_id = e.employee_id
    JOIN pump pu ON s.pump_id = pu.pump_id
    JOIN fuel_type f ON pu.fuel_id = f.fuel_id
    LEFT JOIN payment pay ON s.sale_id = pay.sale_id
    WHERE s.sale_date >= DATE_SUB(NOW(), INTERVAL $interval)
";

if ($staff_filter) {
    $sql .= " AND (e.first_name LIKE :staff OR e.last_name LIKE :staff)";
}

$sql .= " ORDER BY s.sale_date DESC";

$stmt = $pdo->prepare($sql);
if ($staff_filter) {
    $stmt->bindValue(':staff', "%$staff_filter%");
}
$stmt->execute();
$logs = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col">
        <h2><i class="bi bi-journal-check text-info me-2"></i> Financial Audit & Staff Performance</h2>
    </div>
    <div class="col-md-auto">
        <form class="row g-2 align-items-center">
            <div class="col-auto">
                <input type="text" name="staff" class="form-control" placeholder="Search Staff Name..." value="<?php echo htmlspecialchars($staff_filter); ?>">
            </div>
            <div class="col-auto">
                <select name="range" class="form-select">
                    <option value="week" <?php echo $date_range == 'week' ? 'selected' : ''; ?>>Last Week</option>
                    <option value="month" <?php echo $date_range == 'month' ? 'selected' : ''; ?>>Last Month</option>
                    <option value="3month" <?php echo $date_range == '3month' ? 'selected' : ''; ?>>Last 3 Months</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Date</th>
                    <th>Staff Member</th>
                    <th>Customer</th>
                    <th>Fuel & Qty</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th class="text-end pe-4">Sale ID</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No transactions found for this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="ps-4 small"><?php echo date('d M, H:i', strtotime($log['sale_date'])); ?></td>
                        <td class="fw-bold"><?php echo htmlspecialchars($log['staff_name']); ?></td>
                        <td><?php echo htmlspecialchars($log['customer_name'] ?? 'Walk-in'); ?></td>
                        <td><?php echo $log['fuel_name']; ?> (<?php echo $log['quantity']; ?>L)</td>
                        <td class="fw-bold">RWF <?php echo number_format($log['total_amount'], 0); ?></td>
                        <td>
                            <span class="badge bg-light text-dark border"><?php echo $log['payment_method']; ?></span>
                        </td>
                        <td class="text-end pe-4">#<?php echo $log['sale_id']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
