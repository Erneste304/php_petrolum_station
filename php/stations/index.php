<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';
requirePermission('stations');
// Handle delete request
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM station WHERE station_id = ?");
        $stmt->execute([$_GET['delete']]);
        $_SESSION['success'] = "Station deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Cannot delete station: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}

// Fetch all stations
$stations = $pdo->query("SELECT * FROM station ORDER BY station_name")->fetchAll();

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-building"></i> Stations Management</h2>
    </div>
    <div class="col-auto">
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Station
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list"></i> All Stations
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Station Name</th>
                        <th>Location</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stations as $station): ?>
                    <tr>
                        <td><?php echo $station['station_id']; ?></td>
                        <td><?php echo htmlspecialchars($station['station_name']); ?></td>
                        <td><?php echo htmlspecialchars($station['location']); ?></td>
                        <td><?php echo htmlspecialchars($station['phone']); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="edit.php?id=<?php echo $station['station_id']; ?>" 
                                   class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="index.php?delete=<?php echo $station['station_id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this station?')"
                                   title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
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
