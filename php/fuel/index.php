<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';
requirePermission('fuel');

// Handle delete request — Admin only
if (isset($_GET['delete'])) {
    if (!isAdmin()) {
        $_SESSION['error'] = "Only Administrators can delete fuel types.";
        header("Location: index.php");
        exit();
    }
    try {
        $pdo->beginTransaction();
        $fuel_id = $_GET['delete'];

        // 1. Delete sales associated with pumps of this fuel type
        $stmt = $pdo->prepare("
            DELETE s FROM sale s 
            JOIN pump p ON s.pump_id = p.pump_id 
            WHERE p.fuel_id = ?
        ");
        $stmt->execute([$fuel_id]);

        // 2. Delete pumps associated with this fuel type
        $stmt = $pdo->prepare("DELETE FROM pump WHERE fuel_id = ?");
        $stmt->execute([$fuel_id]);

        // 3. Delete tanks associated with this fuel type
        $stmt = $pdo->prepare("DELETE FROM tank WHERE fuel_id = ?");
        $stmt->execute([$fuel_id]);

        // 4. Finally delete the fuel type
        $stmt = $pdo->prepare("DELETE FROM fuel_type WHERE fuel_id = ?");
        $stmt->execute([$fuel_id]);

        $pdo->commit();
        $_SESSION['success'] = "Fuel type and all associated data deleted successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Cannot delete fuel type: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}

include '../includes/header.php';

// Fetch all fuel types with tank and pump info
$fuel_types = $pdo->query("
    SELECT f.*,
           t.capacity,
           t.current_stock,
           t.tank_id,
           COUNT(p.pump_id) AS pump_count
    FROM fuel_type f
    LEFT JOIN tank t ON f.fuel_id = t.fuel_id
    LEFT JOIN pump p ON f.fuel_id = p.fuel_id
    GROUP BY f.fuel_id, t.capacity, t.current_stock, t.tank_id
    ORDER BY f.fuel_name
")->fetchAll();
?>

<div class="row mb-4 align-items-center">
    <div class="col">
        <h2><i class="bi bi-droplet text-primary me-2"></i> Fuel, Tank & Pump Management</h2>
        <?php if (!isAdmin()): ?>
        <div class="alert alert-info border-0 rounded-3 py-2 mt-2 mb-0 small">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Your Access:</strong>
            <?php if (isStaff()): ?>
                As <strong>Staff</strong>, you can view and edit fuel/tank details. All your changes are logged for admin review. Deleting fuel types is restricted to Admins.
            <?php elseif (isAccountant()): ?>
                As <strong>Accountant</strong>, you can view and edit fuel pricing and tank capacity. All changes are tracked in the audit trail.
            <?php else: ?>
                You have read-only access to this section.
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php if (isAdmin()): ?>
    <div class="col-auto">
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Fuel Type
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <?php
    $total_fuel = count($fuel_types);
    $total_pumps = array_sum(array_column($fuel_types, 'pump_count'));
    $total_stock = array_sum(array_column($fuel_types, 'current_stock'));
    $total_cap   = array_sum(array_column($fuel_types, 'capacity'));
    ?>
    <div class="col-md-3">
        <div class="card border-0 bg-info bg-opacity-10 text-center py-3 shadow-sm">
            <div class="fs-2 fw-bold text-primary"><?php echo $total_fuel; ?></div>
            <div class="small text-muted">Fuel Types</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-info bg-opacity-10 text-center py-3 shadow-sm">
            <div class="fs-2 fw-bold text-info"><?php echo $total_pumps; ?></div>
            <div class="small text-muted">Total Pumps</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-success bg-opacity-10 text-center py-3 shadow-sm">
            <div class="fs-2 fw-bold text-success"><?php echo number_format($total_stock, 0); ?> L</div>
            <div class="small text-muted">Total Stock</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-warning bg-opacity-10 text-center py-3 shadow-sm">
            <div class="fs-2 fw-bold text-warning"><?php echo number_format($total_cap, 0); ?> L</div>
            <div class="small text-muted">Total Capacity</div>
        </div>
    </div>
</div>

<!-- Fuel / Tank / Pump Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold">
        <i class="bi bi-list me-2"></i> All Fuel Types with Tank & Pump Status
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Fuel Name</th>
                        <th>Price / Liter</th>
                        <th>Tank Capacity</th>
                        <th>Current Stock</th>
                        <th>Stock Level</th>
                        <th>Pumps</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fuel_types as $fuel):
                        $pct = ($fuel['capacity'] > 0)
                            ? round(($fuel['current_stock'] / $fuel['capacity']) * 100)
                            : 0;
                        $bar_class = $pct > 50 ? 'bg-success' : ($pct > 20 ? 'bg-warning' : 'bg-danger');
                    ?>
                    <tr>
                        <td class="ps-4 text-muted"><?php echo $fuel['fuel_id']; ?></td>
                        <td class="fw-bold">
                            <i class="bi bi-droplet-fill text-primary me-1"></i>
                            <?php echo htmlspecialchars($fuel['fuel_name']); ?>
                        </td>
                        <td>RWF <?php echo number_format($fuel['price_per_liter'], 0); ?></td>
                        <td><?php echo $fuel['capacity'] ? number_format($fuel['capacity'], 0) . ' L' : '<span class="text-muted">—</span>'; ?></td>
                        <td><?php echo $fuel['current_stock'] !== null ? number_format($fuel['current_stock'], 0) . ' L' : '<span class="text-muted">—</span>'; ?></td>
                        <td style="min-width:120px;">
                            <?php if ($fuel['capacity'] > 0): ?>
                            <div class="progress" style="height:8px;" title="<?php echo $pct; ?>% full">
                                <div class="progress-bar <?php echo $bar_class; ?>" style="width:<?php echo $pct; ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo $pct; ?>% full</small>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-secondary rounded-pill">
                                <i class="bi bi-fuel-pump me-1"></i><?php echo $fuel['pump_count']; ?>
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group" role="group">
                                <a href="edit.php?id=<?php echo $fuel['fuel_id']; ?>"
                                   class="btn btn-sm <?php echo isPartner() ? 'btn-outline-secondary' : 'btn-warning'; ?>"
                                   title="<?php echo isPartner() ? 'View Details' : 'Edit'; ?>">
                                    <i class="bi bi-<?php echo isPartner() ? 'eye' : 'pencil'; ?>"></i>
                                    <?php echo isPartner() ? 'View' : 'Edit'; ?>
                                </a>
                                <?php if (isAdmin()): ?>
                                <a href="index.php?delete=<?php echo $fuel['fuel_id']; ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure? This will also delete all pumps and sales for this fuel type!')"
                                   title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
