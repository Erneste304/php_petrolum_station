<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

if (!isPartner() && !isCustomer() && !isAdmin()) {
    $_SESSION['error'] = "Access denied.";
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_report'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $is_critical = isset($_POST['is_critical']) ? 1 : 0;
    $reporter_id = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("INSERT INTO problem_reports (reporter_id, title, description, is_critical, status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->execute([$reporter_id, $title, $description, $is_critical]);
        $_SESSION['success'] = "Problem report submitted. Our staff will review it shortly.";
        header("Location: problem_report.php");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$my_reports = $pdo->prepare("SELECT * FROM problem_reports WHERE reporter_id = ? ORDER BY created_at DESC");
$my_reports->execute([$_SESSION['user_id']]);
$reports = $my_reports->fetchAll();

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi- megaphone-fill text-warning me-2"></i> Report a Problem</h2>
        <p class="text-muted">Submit technical issues or service complaints for staff resolution.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm p-4" style="border-radius: 20px;">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Issue Title</label>
                    <input type="text" name="title" class="form-control rounded-pill px-3" placeholder="Brief summary of the issue" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" class="form-control rounded-3" rows="4" placeholder="Detail the problem..." required></textarea>
                </div>
                <div class="mb-4 form-check form-switch p-3 bg-light rounded-3 ms-0">
                    <input class="form-check-input ms-0 me-2" type="checkbox" name="is_critical" id="criticalSwitch">
                    <label class="form-check-label fw-bold text-danger" for="criticalSwitch">This is a CRITICAL operational issue</label>
                </div>
                <button type="submit" name="submit_report" class="btn btn-warning w-100 rounded-pill fw-bold py-2 shadow-sm">
                    Submit Report
                </button>
            </form>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 20px;">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">My Recent Reports</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Issue</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">No reports submitted yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reports as $r): ?>
                            <tr>
                                <td class="ps-4 small"><?php echo date('d M, H:i', strtotime($r['created_at'])); ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($r['title']); ?></div>
                                    <?php if ($r['is_critical']): ?>
                                        <span class="badge bg-danger ms-0" style="font-size: 0.65rem;">CRITICAL</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-opacity-10 <?php 
                                        echo match($r['status']) {
                                            'Pending' => 'bg-warning text-dark',
                                            'Staff Handled' => 'bg-success text-success',
                                            'Awaiting Admin Approval' => 'bg-info text-primary',
                                            default => 'bg-secondary text-secondary'
                                        };
                                    ?> px-2 py-1 rounded-pill" style="font-size: 0.8rem;"><?php echo $r['status']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
