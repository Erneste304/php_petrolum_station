<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

// Only allow Admins to view the full audit trail
if (!isAdmin()) {
    $_SESSION['error'] = "Access denied. Audit Trail is only accessible to Administrators.";
    header("Location: index.php");
    exit();
}

// --- Filters ---
$type_filter  = $_GET['type']  ?? '';
$role_filter  = $_GET['role']  ?? '';
$date_range   = $_GET['range'] ?? 'week';

$interval = "1 WEEK";
if ($date_range === 'month')  $interval = "1 MONTH";
if ($date_range === '3month') $interval = "3 MONTH";
if ($date_range === 'all')    $interval = "100 YEAR";

$where_clauses = ["ta.change_date >= DATE_SUB(NOW(), INTERVAL $interval)"];
$params = [];

if ($type_filter) {
    $where_clauses[] = "ta.transaction_type = ?";
    $params[] = $type_filter;
}
if ($role_filter) {
    $where_clauses[] = "ta.changed_by_role = ?";
    $params[] = $role_filter;
}

$where_sql = implode(' AND ', $where_clauses);

$stmt = $pdo->prepare("
    SELECT ta.*,
           u.username
    FROM transaction_audit ta
    LEFT JOIN users u ON ta.changed_by = u.user_id
    WHERE {$where_sql}
    ORDER BY ta.change_date DESC
");
$stmt->execute($params);
$audit_logs = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col">
        <h2><i class="bi bi-shield-check text-warning me-2"></i> Transaction Audit Trail</h2>
        <p class="text-muted mb-0">Full history of all changes made by Staff and Accountants. Compare what was changed and by whom.</p>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Date Range</label>
                <select name="range" class="form-select form-select-sm">
                    <option value="week"   <?php echo $date_range === 'week'   ? 'selected' : ''; ?>>Last Week</option>
                    <option value="month"  <?php echo $date_range === 'month'  ? 'selected' : ''; ?>>Last Month</option>
                    <option value="3month" <?php echo $date_range === '3month' ? 'selected' : ''; ?>>Last 3 Months</option>
                    <option value="all"    <?php echo $date_range === 'all'    ? 'selected' : ''; ?>>All Time</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Transaction Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="sale"      <?php echo $type_filter === 'sale'      ? 'selected' : ''; ?>>Sale</option>
                    <option value="fuel_type" <?php echo $type_filter === 'fuel_type' ? 'selected' : ''; ?>>Fuel / Tank</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Changed By Role</label>
                <select name="role" class="form-select form-select-sm">
                    <option value="">All Roles</option>
                    <option value="staff"      <?php echo $role_filter === 'staff'      ? 'selected' : ''; ?>>Staff</option>
                    <option value="accountant" <?php echo $role_filter === 'accountant' ? 'selected' : ''; ?>>Accountant</option>
                    <option value="admin"      <?php echo $role_filter === 'admin'      ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
    <?php
    $total      = count($audit_logs);
    $by_staff   = count(array_filter($audit_logs, fn($l) => $l['changed_by_role'] === 'staff'));
    $by_acc     = count(array_filter($audit_logs, fn($l) => $l['changed_by_role'] === 'accountant'));
    $by_sale    = count(array_filter($audit_logs, fn($l) => $l['transaction_type'] === 'sale'));
    ?>
    <div class="col-md-3">
        <div class="card border-0 bg-info bg-opacity-10 text-primary text-center py-3 shadow-sm">
            <div class="fs-2 fw-bold"><?php echo $total; ?></div>
            <div class="small">Total Changes</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-warning bg-opacity-10 text-warning text-center py-3 shadow-sm">
            <div class="fs-2 fw-bold"><?php echo $by_sale; ?></div>
            <div class="small">Sale Edits</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-info bg-opacity-10 text-info text-center py-3 shadow-sm">
            <div class="fs-2 fw-bold"><?php echo $by_staff; ?></div>
            <div class="small">By Staff</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-success bg-opacity-10 text-success text-center py-3 shadow-sm">
            <div class="fs-2 fw-bold"><?php echo $by_acc; ?></div>
            <div class="small">By Accountant</div>
        </div>
    </div>
</div>

<!-- Audit Log Table -->
<?php if (empty($audit_logs)): ?>
<div class="text-center py-5 text-muted">
    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
    No changes recorded for the selected period.
</div>
<?php else: ?>
<div class="accordion" id="auditAccordion">
<?php foreach ($audit_logs as $i => $log):
    $prev    = json_decode($log['previous_data'], true);
    $updated = json_decode($log['updated_data'],  true);
    $all_keys = array_unique(array_merge(array_keys($prev ?? []), array_keys($updated ?? [])));

    $role_badge_class = match($log['changed_by_role']) {
        'admin'      => 'bg-danger',
        'accountant' => 'bg-success',
        'staff'      => 'bg-info text-dark',
        default      => 'bg-secondary',
    };
    $type_icon = $log['transaction_type'] === 'sale' ? 'bi-cart-check' : 'bi-droplet-half';
?>
<div class="card border-0 shadow-sm mb-2">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2"
         style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#audit_<?php echo $i; ?>">
        <div class="d-flex align-items-center gap-3">
            <i class="bi <?php echo $type_icon; ?> text-primary fs-5"></i>
            <div>
                <span class="fw-bold text-capitalize"><?php echo str_replace('_', ' ', $log['transaction_type']); ?> #<?php echo $log['record_id']; ?></span>
                <span class="text-muted small ms-2"><i class="bi bi-clock me-1"></i><?php echo date('d M Y, H:i', strtotime($log['change_date'])); ?></span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge <?php echo $role_badge_class; ?> text-capitalize"><?php echo $log['changed_by_role']; ?></span>
            <span class="text-muted small"><?php echo htmlspecialchars($log['full_name'] ?? $log['username']); ?></span>
            <i class="bi bi-chevron-down text-muted small"></i>
        </div>
    </div>
    <div id="audit_<?php echo $i; ?>" class="collapse">
        <div class="card-body p-0">
            <div class="row g-0">
                <!-- Previous data -->
                <div class="col-md-6 border-end">
                    <div class="bg-danger bg-opacity-10 px-3 py-2 fw-bold text-danger border-bottom">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Before Change
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($all_keys as $k):
                            $old_val = $prev[$k] ?? '—';
                            $new_val = $updated[$k] ?? '—';
                            $changed = ($old_val != $new_val);
                        ?>
                        <li class="list-group-item d-flex justify-content-between py-2 <?php echo $changed ? 'bg-danger bg-opacity-10' : ''; ?>">
                            <span class="text-muted small text-capitalize"><?php echo str_replace('_', ' ', $k); ?></span>
                            <span class="fw-bold small <?php echo $changed ? 'text-danger' : ''; ?>"><?php echo htmlspecialchars($old_val ?? '—'); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <!-- Updated data -->
                <div class="col-md-6">
                    <div class="bg-success bg-opacity-10 px-3 py-2 fw-bold text-success border-bottom">
                        <i class="bi bi-check-circle me-1"></i> After Change
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($all_keys as $k):
                            $old_val = $prev[$k] ?? '—';
                            $new_val = $updated[$k] ?? '—';
                            $changed = ($old_val != $new_val);
                        ?>
                        <li class="list-group-item d-flex justify-content-between py-2 <?php echo $changed ? 'bg-success bg-opacity-10' : ''; ?>">
                            <span class="text-muted small text-capitalize"><?php echo str_replace('_', ' ', $k); ?></span>
                            <span class="fw-bold small <?php echo $changed ? 'text-success' : ''; ?>"><?php echo htmlspecialchars($new_val ?? '—'); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="px-3 py-2 bg-light border-top small text-muted">
                <i class="bi bi-person me-1"></i> Changed by: <strong><?php echo htmlspecialchars($log['full_name'] ?? $log['username']); ?></strong>
                &nbsp;|&nbsp; <i class="bi bi-shield me-1"></i> Role: <strong><?php echo ucfirst($log['changed_by_role']); ?></strong>
                &nbsp;|&nbsp; <i class="bi bi-calendar me-1"></i> <?php echo date('d M Y, H:i:s', strtotime($log['change_date'])); ?>
                &nbsp;|&nbsp; Audit ID: #<?php echo $log['audit_id']; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
