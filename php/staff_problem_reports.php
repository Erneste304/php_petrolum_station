<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

if (!isAdmin() && !isStaff() && !isReceptionist() && !isAccountant()) {
    $_SESSION['error'] = "Access denied.";
    header("Location: index.php");
    exit();
}

// Handle problem handling actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $report_id = $_POST['report_id'];
    $action = $_POST['action'];
    $staff_id = $_SESSION['user_id'];

    try {
        if ($action === 'handle') {
            $stmt = $pdo->prepare("UPDATE problem_reports SET status = 'Staff Handled', staff_id = ? WHERE report_id = ?");
            $stmt->execute([$staff_id, $report_id]);
            $_SESSION['success'] = "Report #$report_id marked as handled.";
        } elseif ($action === 'escalate') {
            $stmt = $pdo->prepare("UPDATE problem_reports SET status = 'Awaiting Admin Approval', staff_id = ? WHERE report_id = ?");
            $stmt->execute([$staff_id, $report_id]);
            $_SESSION['success'] = "Report #$report_id escalated to Admin.";
        }
        header("Location: staff_problem_reports.php");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch reports
$reports = $pdo->query("
    SELECT pr.*, u.username as reporter_name
    FROM problem_reports pr
    JOIN users u ON pr.reporter_id = u.user_id
    ORDER BY pr.is_critical DESC, pr.created_at DESC
")->fetchAll();

include 'includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col">
        <h2><i class="bi bi-shield-exclamation text-danger me-2"></i> Operational Problem Logs</h2>
        <p class="text-muted">Monitor and resolve service disruptions or customer complaints.</p>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($reports)): ?>
        <div class="col-12 text-center py-5">
            <i class="bi bi-check2-circle display-1 text-success opacity-25"></i>
            <p class="text-muted mt-3 h5">No active problems reported. All systems clear.</p>
        </div>
    <?php else: ?>
        <?php foreach ($reports as $report): ?>
        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
                <div class="card-header <?php echo $report['is_critical'] ? 'bg-danger text-white' : 'bg-light'; ?> py-3 d-flex justify-content-between">
                    <span class="fw-bold">Report #<?php echo $report['report_id']; ?>: <?php echo htmlspecialchars($report['title']); ?></span>
                    <?php if ($report['is_critical']): ?>
                        <span class="badge bg-white text-danger">CRITICAL</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <span class="text-muted small">Reported by:</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($report['reporter_name']); ?></span>
                        <span class="text-muted small ms-2">at <?php echo date('d M, H:i', strtotime($report['created_at'])); ?></span>
                    </div>
                    <p class="text-dark bg-light p-3 rounded" style="min-height: 80px;"><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                    
                    <div class="d-flex align-items-center justify-content-between mt-4">
                        <div>
                            <span class="text-muted small d-block">Current Status:</span>
                            <span class="badge bg-opacity-10 <?php 
                                echo match($report['status']) {
                                    'Pending' => 'bg-warning text-dark',
                                    'Staff Handled' => 'bg-success text-success',
                                    'Awaiting Admin Approval' => 'bg-info text-primary',
                                    default => 'bg-secondary text-secondary'
                                };
                            ?> px-3 py-2 rounded-pill"><?php echo $report['status']; ?></span>
                        </div>
                        
                        <div class="btn-group">
                            <?php if ($report['status'] == 'Pending'): ?>
                                <form method="POST" class="me-2">
                                    <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                                    <input type="hidden" name="action" value="handle">
                                    <button type="submit" class="btn btn-outline-success btn-sm rounded-pill px-3">
                                        <i class="bi bi-check-circle me-1"></i> Handle
                                    </button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                                    <input type="hidden" name="action" value="escalate">
                                    <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                        <i class="bi bi-arrow-up-circle me-1"></i> Escalate
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
