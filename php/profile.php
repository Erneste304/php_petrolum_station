<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

$user_id = $_SESSION['user_id'];

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $target_dir = "img/profiles/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $update_fields = [];
    $params = [];
    
    if (!empty($_FILES["profile_photo"]["name"])) {
        $file_name = time() . '_' . basename($_FILES["profile_photo"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
            $update_fields[] = "profile_photo = ?";
            $params[] = $file_name;
        }
    }
    
    if (!empty($update_fields)) {
        $params[] = $user_id;
        $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $_SESSION['success'] = "Profile updated successfully.";
    }
    header("Location: profile.php");
    exit();
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2 class="fw-bold"><i class="bi bi-person-circle text-primary me-2"></i> My Profile</h2>
        <p class="text-muted">Manage your personal information and profile settings.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-5">
                <form method="POST" enctype="multipart/form-data">
                    <div class="text-center mb-5">
                        <div class="position-relative d-inline-block">
                            <?php if ($user['profile_photo']): ?>
                                <img src="img/profiles/<?php echo $user['profile_photo']; ?>" class="rounded-circle shadow" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center shadow mx-auto" style="width: 150px; height: 150px; font-size: 4rem;">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <label for="profile_photo" class="btn btn-sm btn-light position-absolute bottom-0 end-0 rounded-circle shadow-sm">
                                <i class="bi bi-camera-fill"></i>
                            </label>
                            <input type="file" id="profile_photo" name="profile_photo" class="d-none" onchange="this.form.submit()">
                        </div>
                        <h4 class="mt-3 fw-bold"><?php echo htmlspecialchars($user['username']); ?></h4>
                        <span class="badge bg-info-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill text-uppercase"><?php echo $user['role']; ?></span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Username</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Role</label>
                            <input type="text" class="form-control bg-light" value="<?php echo ucfirst($user['role']); ?>" readonly>
                        </div>
                        <div class="col-12 mt-4 text-center">
                            <p class="small text-muted">To change your username or role, please contact the system administrator.</p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
