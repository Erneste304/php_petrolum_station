<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';
requirePermission('employees');

// Handle delete request
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM employee WHERE employee_id = ?");
        $stmt->execute([$_GET['delete']]);
        $_SESSION['success'] = "Employee deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Cannot delete employee: " . $e->getMessage();
    }
    header("Location: index.php");
    exit();
}

include '../includes/header.php';

// Fetch all employees with station names
$employees = $pdo->query("
    SELECT e.*, s.station_name 
    FROM employee e 
    LEFT JOIN station s ON e.station_id = s.station_id
    ORDER BY e.first_name
")->fetchAll();
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-people"></i> Employees Management</h2>
    </div>
    <div class="col-auto">
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Employee
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list"></i> All Employees
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Position</th>
                        <th>Phone</th>
                        <th>Station</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?php echo $employee['employee_id']; ?></td>
                        <td><?php echo htmlspecialchars($employee['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($employee['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($employee['position']); ?></td>
                        <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                        <td><?php echo htmlspecialchars($employee['station_name'] ?? 'N/A'); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="edit.php?id=<?php echo $employee['employee_id']; ?>" 
                                   class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="index.php?delete=<?php echo $employee['employee_id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this employee?')"
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
