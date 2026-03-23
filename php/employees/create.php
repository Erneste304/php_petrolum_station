<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';
requirePermission('employees');

// Fetch stations for dropdown
$stations = $pdo->query("SELECT * FROM station ORDER BY station_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO employee (first_name, last_name, position, phone, station_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['position'], $_POST['phone'], $_POST['station_id']]);
        $_SESSION['success'] = "Employee added successfully!";
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<style>
    .premium-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .banner-container {
        width: 100%;
        height: 200px;
        overflow: hidden;
        position: relative;
    }
    .banner-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .banner-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to right, rgba(26, 54, 93, 0.8), rgba(42, 67, 101, 0.2));
        display: flex;
        align-items: center;
        padding-left: 40px;
    }
    .form-label {
        font-weight: 600;
        color: #4a5568;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    .form-control, .form-select {
        padding: 12px 16px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
        transition: all 0.2s;
    }
    .form-control:focus, .form-select:focus {
        background-color: #fff;
        border-color: #3182ce;
        box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
    }
    .btn-save {
        padding: 14px;
        font-weight: 700;
        border-radius: 12px;
        letter-spacing: 0.5px;
        background: linear-gradient(135deg, #3182ce 0%, #2b6cb0 100%);
        border: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(49, 130, 206, 0.3);
    }
</style>

<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="mb-1 text-dark fw-bold"><i class="bi bi-people-fill text-primary me-2"></i> Employee Management</h2>
        <p class="text-muted small mb-0">Onboard your new station staff members effortlessly.</p>
    </div>
    <div class="col-auto">
        <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i> Back to List
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="premium-card bg-white">
            <div class="banner-container">
                <img src="../img/employee_banner.png" alt="Employee Banner" class="banner-img">
                <div class="banner-overlay">
                    <h3 class="text-white mb-0 fw-bold">Add New Staff Member</h3>
                </div>
            </div>
            
            <div class="card-body p-5">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger border-0 shadow-sm mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="first_name" class="form-label">First Name *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control border-start-0" id="first_name" name="first_name" placeholder="Enter first name" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control border-start-0" id="last_name" name="last_name" placeholder="Enter last name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="position" class="form-label">Role / Position *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-briefcase"></i></span>
                                <select class="form-select border-start-0" id="position" name="position" required>
                                    <option value="">Select Position</option>
                                    <option value="Manager">Manager</option>
                                    <option value="Cashier">Cashier</option>
                                    <option value="Pump Attendant">Pump Attendant</option>
                                    <option value="Supervisor">Supervisor</option>
                                    <option value="Technician">Technician</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label for="phone" class="form-label">Contact Phone Number *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone"></i></span>
                                <input type="tel" class="form-control border-start-0" id="phone" name="phone" placeholder="+250..." required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-5">
                        <label for="station_id" class="form-label">Assigned Station *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-building"></i></span>
                            <select class="form-select border-start-0" id="station_id" name="station_id" required>
                                <option value="">Select Station Location</option>
                                <?php foreach ($stations as $station): ?>
                                <option value="<?php echo $station['station_id']; ?>">
                                    <?php echo htmlspecialchars($station['station_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-save shadow">
                            <i class="bi bi-person-plus-fill me-2"></i> Register Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
