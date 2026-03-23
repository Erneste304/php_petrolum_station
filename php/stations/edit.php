<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';
requirePermission('stations');

// Check if ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No station ID provided";
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Fetch station data
$stmt = $pdo->prepare("SELECT * FROM station WHERE station_id = ?");
$stmt->execute([$id]);
$station = $stmt->fetch();

if (!$station) {
    $_SESSION['error'] = "Station not found";
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isPartner()) {
        $_SESSION['error'] = "Access denied. Partners have read-only access to station records.";
        header("Location: index.php");
        exit();
    }
    try {
        $stmt = $pdo->prepare("UPDATE station SET station_name = ?, location = ?, phone = ? WHERE station_id = ?");
        $stmt->execute([$_POST['station_name'], $_POST['location'], $_POST['phone'], $id]);
        $_SESSION['success'] = "Station updated successfully!";
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
        <h2><i class="bi bi-pencil"></i> Edit Station</h2>
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
                <i class="bi bi-building"></i> Edit Station Information
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isPartner()): ?>
                    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Read-Only Access:</strong> As a business partner, you can view station details but cannot modify them.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="station_name" class="form-label">Station Name *</label>
                        <input type="text" class="form-control" id="station_name" name="station_name" 
                               value="<?php echo htmlspecialchars($station['station_name']); ?>" required <?php echo isPartner() ? 'readonly' : ''; ?>>
                    </div>
                    
                    <div class="mb-3">
                        <label for="location" class="form-label">Location *</label>
                        <input type="text" class="form-control" id="location" name="location" 
                               value="<?php echo htmlspecialchars($station['location']); ?>" required <?php echo isPartner() ? 'readonly' : ''; ?>>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number *</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($station['phone']); ?>" required <?php echo isPartner() ? 'readonly' : ''; ?>>
                    </div>
                    
                    <?php if (!isPartner()): ?>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Station
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
