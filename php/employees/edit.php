<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';
requirePermission('employees');

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Fetch stations for dropdown
$stations = $pdo->query("SELECT * FROM station ORDER BY station_name")->fetchAll();

// Fetch employee data
$stmt = $pdo->prepare("SELECT * FROM employee WHERE employee_id = ?");
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("UPDATE employee SET first_name = ?, last_name = ?, position = ?, phone = ?, station_id = ? WHERE employee_id = ?");
        $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['position'], $_POST['phone'], $_POST['station_id'], $id]);
        $_SESSION['success'] = "Employee updated successfully!";
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-pencil"></i> Edit Employee</h2>
    </div>
    <div class="col-auto">
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-badge"></i> Edit Employee Information
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="position" class="form-label">Position *</label>
                        <select class="form-select" id="position" name="position" required>
                            <option value="">Select Position</option>
                            <option value="Manager" <?php echo $employee['position'] == 'Manager' ? 'selected' : ''; ?>>Manager</option>
                            <option value="Cashier" <?php echo $employee['position'] == 'Cashier' ? 'selected' : ''; ?>>Cashier</option>
                            <option value="Pump Attendant" <?php echo $employee['position'] == 'Pump Attendant' ? 'selected' : ''; ?>>Pump Attendant</option>
                            <option value="Supervisor" <?php echo $employee['position'] == 'Supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number *</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($employee['phone']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="station_id" class="form-label">Station *</label>
                        <select class="form-select" id="station_id" name="station_id" required>
                            <option value="">Select Station</option>
                            <?php foreach ($stations as $station): ?>
                            <option value="<?php echo $station['station_id']; ?>"
                                <?php echo $employee['station_id'] == $station['station_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($station['station_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
