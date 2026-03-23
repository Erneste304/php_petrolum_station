<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

$user_id = $_SESSION['user_id'];

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2 class="fw-bold"><i class="bi bi-gear text-info me-2"></i> Account Settings</h2>
        <p class="text-muted">Manage your security and notification preferences.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-shield-lock me-2 text-warning"></i> Security</h5>
                <form>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control rounded-pill" placeholder="********">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control rounded-pill" placeholder="Enter new password">
                    </div>
                    <button type="button" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">Update Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-bell me-2 text-primary"></i> Notifications</h5>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="notifySales" checked>
                    <label class="form-check-label" for="notifySales">Email me on new sales</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="notifyPayroll">
                    <label class="form-check-label" for="notifyPayroll">Payroll approval alerts</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="notifyLogs">
                    <label class="form-check-label" for="notifyLogs">System audit logs</label>
                </div>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Save Preferences</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
