<?php
require_once '../includes/auth_middleware.php';
require_once '../config/database.php';
requirePermission('users');

// Fetch all users with role filtering for Staff
$query = "
    SELECT u.user_id, u.username, u.role, c.name as customer_name
    FROM users u
    LEFT JOIN customer c ON u.customer_id = c.customer_id
";

// Supervisors can only see specific roles and cannot see Admins/Accountants details
if (isStaff()) {
    $query .= " WHERE u.role IN ('receptionist', 'staff', 'partner', 'pump_attendant', 'customer')";
}

$query .= " ORDER BY u.role, u.username";
$users = $pdo->query($query)->fetchAll();

// Define available protected modules
$available_modules = [
    'sales' => ['icon' => 'bi-receipt', 'label' => 'Sales & POS'],
    'stations' => ['icon' => 'bi-building', 'label' => 'Stations'],
    'employees' => ['icon' => 'bi-people', 'label' => 'Employees'],
    'customers' => ['icon' => 'bi-person-badge', 'label' => 'Customers'],
    'fuel' => ['icon' => 'bi-droplet', 'label' => 'Fuel Inventory'],
    'fuel_delivery' => ['icon' => 'bi-truck', 'label' => 'Bulk Deliveries'],
    'car_wash' => ['icon' => 'bi-car-front', 'label' => 'Car Wash'],
    'loyalty' => ['icon' => 'bi-gift', 'label' => 'Loyalty & Rewards'],
    'users' => ['icon' => 'bi-shield-lock', 'label' => 'User Access Mgmt'],
    'reports' => ['icon' => 'bi-graph-up', 'label' => 'Analytics Reports']
];

// Initialize modals string
$modals = '';

// Handle User Addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? 'customer';
    
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username and password are required.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Check if username exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                throw new Exception("Username already exists.");
            }
            
            // Create customer profile
            $stmt = $pdo->prepare("INSERT INTO customer (name) VALUES (?)");
            $stmt->execute([$username]);
            $customerId = $pdo->lastInsertId();
            
            // Create user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, customer_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $role, $customerId]);
            
            $pdo->commit();
            $_SESSION['success'] = "New user account created successfully!";
            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Failed to create user: " . $e->getMessage();
        }
    }
}

// Handle role and permission updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $target_user_id = $_POST['user_id'];
    $new_role = $_POST['role'] ?? 'customer';
    $selected_modules = isset($_POST['modules']) ? $_POST['modules'] : [];
    
    try {
        $pdo->beginTransaction();
        
        // Update Role
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->execute([$new_role, $target_user_id]);

        // Clear old permissions
        $stmt = $pdo->prepare("DELETE FROM user_permission WHERE user_id = ?");
        $stmt->execute([$target_user_id]);
        
        // Insert new permissions
        if (!empty($selected_modules) && $new_role !== 'admin') {
            $stmt = $pdo->prepare("INSERT INTO user_permission (user_id, module_name) VALUES (?, ?)");
            foreach ($selected_modules as $module) {
                if (array_key_exists($module, $available_modules)) {
                    $stmt->execute([$target_user_id, $module]);
                }
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = "User updated successfully!";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to update user: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}

// Handle Password Reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $target_user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    
    if (empty($new_password)) {
        $_SESSION['error'] = "New password cannot be empty.";
    } else {
        try {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hash, $target_user_id]);
            $_SESSION['success'] = "Password reset successfully!";
            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to reset password: " . $e->getMessage();
        }
    }
}

// Handle User Deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $target_user_id = $_POST['user_id'];
    
    // Prevent self-deletion
    if ($target_user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own administrative account.";
    } else {
        try {
            $pdo->beginTransaction();
            // Delete permissions first
            $stmt = $pdo->prepare("DELETE FROM user_permission WHERE user_id = ?");
            $stmt->execute([$target_user_id]);
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$target_user_id]);
            $pdo->commit();
            $_SESSION['success'] = "User account deleted successfully.";
            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Failed to delete user: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<style>
    .permission-card {
        transition: all 0.3s ease;
        border: 1px solid #edf2f7;
        background: #fff;
    }
    .permission-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border-color: #3182ce;
    }
    .form-check-input:checked {
        background-color: #38a169;
        border-color: #38a169;
        box-shadow: 0 0 8px rgba(56, 161, 105, 0.4);
    }
    .modal-content {
        border-radius: 15px;
        overflow: hidden;
        border: none;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .modal-header {
        background: linear-gradient(135deg, #1a365d 0%, #2a4365 100%);
        color: white;
    }
    .badge-admin {
        background: linear-gradient(135deg, #ECC94B 0%, #D69E2E 100%);
        color: #744210;
        box-shadow: 0 0 10px rgba(236, 201, 75, 0.5);
        border: none;
        padding: 8px 12px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .badge-user {
        padding: 6px 10px;
        font-weight: 600;
    }
</style>

<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="mb-1"><i class="bi bi-shield-lock text-danger me-2"></i> User Access Management</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">User Permissions</li>
            </ol>
        </nav>
    </div>
    <?php if (isAdmin()): ?>
    <div class="col-auto">
        <button type="button" class="btn btn-primary shadow-sm px-4 py-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill me-2"></i> Add New User
        </button>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
    <div class="card-header bg-white pt-3 pb-3 border-bottom">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-people me-2 text-primary"></i> System Users & Staff Access</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3">Username</th>
                        <th class="py-3">Role</th>
                        <th class="py-3">Linked Profile</th>
                        <th class="pe-4 py-3 text-end">Access Permissions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-secondary">
                            <?php if ($user['role'] === 'admin'): ?>
                                <i class="bi bi-star-fill text-warning me-2"></i>
                            <?php else: ?>
                                <i class="bi bi-person-fill text-primary ms-1 me-2"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="badge badge-admin rounded-pill">
                                    <i class="bi bi-patch-check-fill me-1"></i> SUPER ADMIN
                                </span>
                            <?php elseif ($user['role'] === 'staff'): ?>
                                <span class="badge bg-info text-dark border badge-user rounded-pill">STAFF MEMBER</span>
                            <?php elseif ($user['role'] === 'receptionist'): ?>
                                <span class="badge bg-primary text-white border badge-user rounded-pill"><i class="bi bi-headset me-1"></i>RECEPTIONIST</span>
                            <?php elseif ($user['role'] === 'pump_attendant'): ?>
                                <span class="badge bg-secondary text-white border badge-user rounded-pill"><i class="bi bi-fuel-pump me-1"></i>PUMP ATTENDANT</span>
                            <?php elseif ($user['role'] === 'accountant'): ?>
                                <span class="badge bg-info text-white border badge-user rounded-pill">ACCOUNTANT</span>
                            <?php elseif ($user['role'] === 'partner'): ?>
                                <span class="badge bg-warning text-dark border badge-user rounded-pill">BUSINESS PARTNER</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border badge-user rounded-pill">CUSTOMER</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $user['customer_name'] ? '<span class="fw-medium text-dark">'.htmlspecialchars($user['customer_name']).'</span>' : '<span class="text-muted fst-italic fst-small">System Account</span>'; ?>
                        </td>
                        <td class="pe-4 text-end">
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="text-success small fw-bold">
                                    <i class="bi bi-shield-check me-1"></i> UNRESTRICTED ACCESS
                                </span>
                            <?php else: ?>
                                <!-- Button triggers Permissions Modal -->
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#userModal<?php echo $user['user_id']; ?>">
                                    <i class="bi <?php echo isStaff() ? 'bi-eye' : 'bi-sliders'; ?> me-1"></i> 
                                    <?php echo isStaff() ? 'View Details' : 'Manage User'; ?>
                                </button>
                                
                                <?php
                                    // Fetch current permissions for this user
                                    $req = $pdo->prepare("SELECT module_name FROM user_permission WHERE user_id = ?");
                                    $req->execute([$user['user_id']]);
                                    $current_perms = $req->fetchAll(PDO::FETCH_COLUMN);
                                    
                                    // Build modal HTML
                                    $modals .= '<div class="modal fade text-start" id="userModal'.$user['user_id'].'" tabindex="-1" aria-labelledby="modalLabel'.$user['user_id'].'" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold" id="modalLabel'.$user['user_id'].'">
                                                        Manage User: '.htmlspecialchars($user['username']).'
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                
                                                <div class="modal-body p-0">
                                                    <!-- Nav Tabs -->
                                                    <ul class="nav nav-tabs px-4 pt-3 border-bottom-0" role="tablist">
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#perms-tab-'.$user['user_id'].'" type="button">Permissions</button>
                                                        </li>
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#security-tab-'.$user['user_id'].'" type="button">Security & Actions</button>
                                                        </li>
                                                    </ul>
                                                    
                                                    <div class="tab-content">
                                                        <!-- Permissions Tab -->
                                                        <div class="tab-pane fade show active p-4" id="perms-tab-'.$user['user_id'].'">
                                                            <form method="POST" action="index.php">
                                                                <input type="hidden" name="update_user" value="1">
                                                                <input type="hidden" name="user_id" value="'.$user['user_id'].'">
                                                                <div class="row mb-4">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label fw-bold">System Role</label>
                                                                        <select name="role" class="form-select shadow-sm" style="border-radius: 8px;">
                                                                            <option value="admin" '.($user['role'] === 'admin' ? 'selected' : '').'>Admin</option>
                                                                            <option value="staff" '.($user['role'] === 'staff' ? 'selected' : '').'>Staff</option>
                                                                            <option value="receptionist" '.($user['role'] === 'receptionist' ? 'selected' : '').'>Receptionist</option>
                                                                            <option value="pump_attendant" '.($user['role'] === 'pump_attendant' ? 'selected' : '').'>Pump Attendant</option>
                                                                            <option value="accountant" '.($user['role'] === 'accountant' ? 'selected' : '').'>Accountant</option>
                                                                            <option value="partner" '.($user['role'] === 'partner' ? 'selected' : '').'>Partner</option>
                                                                            <option value="customer" '.($user['role'] === 'customer' ? 'selected' : '').'>Customer</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="alert alert-info py-2 px-3 border-0 small mb-0 mt-2">
                                                                            <i class="bi bi-info-circle me-1"></i> Updating the role may reset custom permissions below.
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="alert alert-info border-0 shadow-sm mb-4" style="border-radius: 10px; background-color: rgba(49, 130, 206, 0.1); color: #2c5282;">
                                                                    <i class="bi bi-shield-check me-2"></i>
                                                                    <span class="small fw-medium">Grant dashboard module access below (Only for Staff/Partners/Customers). Admin role has full access.</span>
                                                                </div>
                                                                
                                                                <div class="row g-3">';
                                                                
                                                                foreach ($available_modules as $mod_key => $mod_data) {
                                                                    $modals .= '<div class="col-md-6">
                                                                        <div class="permission-card p-3 rounded d-flex align-items-center h-100">
                                                                            <div class="form-check form-switch p-0 m-0 d-flex align-items-center w-100">
                                                                                <input class="form-check-input ms-0 mt-0 me-3" type="checkbox" role="switch" 
                                                                                       id="perm_'.$user['user_id'].'_'.$mod_key.'" 
                                                                                       name="modules[]" value="'.$mod_key.'"
                                                                                       style="width: 2.8em; height: 1.4em; cursor: pointer; float: none;"
                                                                                       '.(in_array($mod_key, $current_perms) ? 'checked' : '').'>
                                                                                <label class="form-check-label fw-bold mb-0 text-dark" for="perm_'.$user['user_id'].'_'.$mod_key.'" style="cursor: pointer; flex-grow: 1;">
                                                                                    <i class="bi '.$mod_data['icon'].' text-primary me-2 shadow-sm p-1 rounded bg-light"></i>
                                                                                    <span class="small">'.$mod_data['label'].'</span>
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>';
                                                                }
                                                                
                                                                $modals .= '</div>';
                                                                
                                                                if (isAdmin()) {
                                                                    $modals .= '<div class="text-end mt-4">
                                                                        <button type="submit" class="btn btn-primary px-4 shadow-sm" style="border-radius: 8px;">
                                                                            <i class="bi bi-save2-fill me-2"></i> Save Permissions
                                                                        </button>
                                                                    </div>';
                                                                } else {
                                                                    $modals .= '<div class="alert alert-warning mt-4 py-2 px-3 border-0 small mb-0">
                                                                        <i class="bi bi-shield-lock me-1"></i> View-Only Mode: Supervisor cannot modify system permissions.
                                                                    </div>';
                                                                }
                                                                
                                                                $modals .= '</form>
                                                            </div>
                                                        
                                                        <!-- Security Tab -->
                                                        <div class="tab-pane fade p-4" id="security-tab-'.$user['user_id'].'">
                                                                     <?php if (isAdmin()): ?>
                                                                    <!-- Reset Password Section -->
                                                                    <div class="card border-0 bg-light rounded-3 mb-4">
                                                                        <div class="card-body">
                                                                            <h6 class="fw-bold mb-3"><i class="bi bi-key-fill text-warning me-2"></i> Reset User Password</h6>
                                                                            <form method="POST" action="index.php" class="row g-2">
                                                                                <input type="hidden" name="reset_password" value="1">
                                                                                <input type="hidden" name="user_id" value="'.$user['user_id'].'">
                                                                                <div class="col-md-8">
                                                                                    <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
                                                                                </div>
                                                                                <div class="col-md-4">
                                                                                    <button type="submit" class="btn btn-warning w-100 fw-bold">Update Password</button>
                                                                                </div>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <!-- Danger Zone -->
                                                                    <div class="card border-danger border-opacity-25 bg-danger bg-opacity-10 rounded-3">
                                                                        <div class="card-body">
                                                                            <h6 class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-octagon-fill me-2"></i> Danger Zone</h6>
                                                                            <p class="small text-danger mb-3">Deleting a user will permanently remove their access and permissions. This action cannot be undone.</p>
                                                                            <form method="POST" action="index.php" onsubmit="return confirm(\'ARE YOU SURE? This user and their access will be permanently deleted.\');">
                                                                                <input type="hidden" name="delete_user" value="1">
                                                                                <input type="hidden" name="user_id" value="'.$user['user_id'].'">
                                                                                <button type="submit" class="btn btn-danger '.($user['user_id'] == $_SESSION['user_id'] ? 'disabled' : '').'">
                                                                                    <i class="bi bi-trash3-fill me-2"></i> Delete User Account
                                                                                </button>
                                                                                '.($user['user_id'] == $_SESSION['user_id'] ? '<div class="alert alert-warning py-1 mt-2 mb-0 border-0 small">You cannot delete yourself.</div>' : '').'
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                    <?php else: ?>
                                                                    <div class="p-5 text-center text-muted">
                                                                        <i class="bi bi-shield-slash display-1 opacity-25 mb-4"></i>
                                                                        <h5>Access Restricted</h5>
                                                                        <p>Security settings can only be managed by the Super Administrator.</p>
                                                                    </div>
                                                                    <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
                                ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php echo $modals; ?>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-primary text-white p-4">
                <h5 class="modal-title fw-bold" id="addUserModalLabel"><i class="bi bi-person-plus-fill me-2"></i> Add New System User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="index.php">
                <div class="modal-body p-4 bg-light">
                    <input type="hidden" name="add_user" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-person-circle"></i></span>
                            <input type="text" name="username" class="form-control border-start-0" placeholder="e.g. john_doe" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Initial Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" name="password" class="form-control border-start-0" placeholder="Min 6 characters" required>
                        </div>
                    </div>
                    
                    <div class="mb-0">
                        <label class="form-label fw-bold">System Role</label>
                        <select name="role" class="form-select shadow-sm">
                            <option value="staff">Staff Member</option>
                            <option value="receptionist">Receptionist</option>
                            <option value="pump_attendant">Pump Attendant</option>
                            <option value="accountant">Accountant</option>
                            <option value="partner">Business Partner</option>
                            <option value="customer">Customer</option>
                            <option value="admin">System Admin</option>
                        </select>
                        <div class="form-text small mt-2">
                            <i class="bi bi-info-circle me-1"></i> A customer profile will be automatically linked to this user.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 bg-light">
                    <button type="button" class="btn btn-link text-muted text-decoration-none fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold shadow-sm">
                        Create User Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
