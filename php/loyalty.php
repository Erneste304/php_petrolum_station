<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

$error = '';
$success = '';
$rewards = [];
$myRedemptions = [];

$totalPointsEarned = 0;
$pointsSpent = 0;
$currentPoints = 0;
$tier = 'Bronze';
$tierIcon = 'bi-star';
$tierColor = 'bronze-text';
$tierBg = 'bronze-bg';

$customerId = $_SESSION['customer_id'] ?? null;

// For Admin: allow selecting a customer to view their points
if (isAdmin() && isset($_GET['view_as'])) {
    $customerId = $_GET['view_as'];
}

if (!$customerId) {
    if (isAdmin()) {
        $error = "Select a customer from the Users list to view their loyalty profile.";
        // Fetch customers for admin selection
        $customers_list = $pdo->query("SELECT customer_id, name FROM customer ORDER BY name")->fetchAll();
    } else {
        $error = "Customer profile not found. Please contact support.";
    }
} else {
    // 1. Calculate Points
    try {
        // Points Earned (1 point per 1000 RWF spent)
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount), 0) as total_spent FROM sale WHERE customer_id = ?');
        $stmt->execute([$customerId]);
        $totalSpent = $stmt->fetch()['total_spent'];
        $totalPointsEarned = floor($totalSpent / 1000);

        // Points Spent
        $stmt = $pdo->prepare('
            SELECT COALESCE(SUM(r.points_cost), 0) as points_spent 
            FROM loyalty_redemption lr
            JOIN loyalty_reward r ON lr.reward_id = r.reward_id
            WHERE lr.customer_id = ?
        ');
        $stmt->execute([$customerId]);
        $pointsSpent = $stmt->fetch()['points_spent'];

        $currentPoints = $totalPointsEarned - $pointsSpent;

        // Determine Tier
        if ($totalPointsEarned >= 2000) {
            $tier = 'Gold';
            $tierIcon = 'bi-star-fill';
            $tierColor = 'gold-text';
            $tierBg = 'gold-bg';
        } elseif ($totalPointsEarned >= 800) {
            $tier = 'Silver';
            $tierIcon = 'bi-star-half';
            $tierColor = 'silver-text';
            $tierBg = 'silver-bg';
        }
    } catch (PDOException $e) {
        $error = "Failed to calculate points.";
    }

    // 2. Handle Redemption
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_reward'])) {
        $rewardId = $_POST['reward_id'] ?? '';
        
        if (!empty($rewardId)) {
            try {
                // Check reward cost
                $stmt = $pdo->prepare('SELECT name, points_cost FROM loyalty_reward WHERE reward_id = ?');
                $stmt->execute([$rewardId]);
                $reward = $stmt->fetch();

                if ($reward) {
                    if ($currentPoints >= $reward['points_cost']) {
                        $stmt = $pdo->prepare('INSERT INTO loyalty_redemption (customer_id, reward_id) VALUES (?, ?)');
                        $stmt->execute([$customerId, $rewardId]);
                        
                        $success = "Successfully redeemed: " . htmlspecialchars($reward['name']) . "! You can claim this during your next visit.";
                        $currentPoints -= $reward['points_cost'];
                        $pointsSpent += $reward['points_cost'];
                    } else {
                        $error = "You do not have enough points for this reward.";
                    }
                }
            } catch (PDOException $e) {
                $error = "Redemption failed.";
            }
        }
    }

    // 3. Fetch Data
    $rewards = $pdo->query('SELECT * FROM loyalty_reward ORDER BY points_cost ASC')->fetchAll();
    $stmt = $pdo->prepare('
        SELECT lr.*, r.name, r.points_cost 
        FROM loyalty_redemption lr
        JOIN loyalty_reward r ON lr.reward_id = r.reward_id
        WHERE lr.customer_id = ?
        ORDER BY lr.redeemed_date DESC LIMIT 5
    ');
    $stmt->execute([$customerId]);
    $myRedemptions = $stmt->fetchAll();
}

include 'includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --glass: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.3);
        --gold: #FFD700;
        --silver: #C0C0C0;
        --bronze: #CD7F32;
    }

    body {
        font-family: 'Outfit', sans-serif;
        background: radial-gradient(circle at top left, #f8fafc, #e2e8f0);
    }

    .glass-card {
        background: var(--glass);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .glass-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.12);
    }

    .points-hero {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        border-radius: 24px;
        color: white;
        padding: 3rem;
        position: relative;
        overflow: hidden;
    }

    .points-hero::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }

    .tier-badge {
        padding: 0.5rem 1.5rem;
        border-radius: 100px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1px;
    }

    .gold-bg { background: rgba(255, 215, 0, 0.2); color: #B8860B; border: 1px solid rgba(255, 215, 0, 0.3); }
    .silver-bg { background: rgba(192, 192, 192, 0.2); color: #708090; border: 1px solid rgba(192, 192, 192, 0.3); }
    .bronze-bg { background: rgba(205, 127, 50, 0.2); color: #8B4513; border: 1px solid rgba(205, 127, 50, 0.3); }

    .reward-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
    }

    .btn-redeem {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.8rem;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-redeem:hover:not(:disabled) {
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        color: white;
    }

    .history-item {
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 1rem 0;
    }

    .history-item:last-child { border-bottom: none; }
</style>

<div class="container py-4">
    <?php if ($error): ?>
        <div class="alert glass-card border-danger text-danger mb-4">
            <i class="bi bi-exclamation-circle me-2"></i> <?php echo $error; ?>
        </div>
        <?php if (isAdmin() && isset($customers_list)): ?>
            <div class="glass-card p-4">
                <h5>Select Customer Profile</h5>
                <form action="" method="GET" class="d-flex gap-2">
                    <select name="view_as" class="form-select rounded-pill">
                        <?php foreach($customers_list as $c): ?>
                            <option value="<?php echo $c['customer_id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">View</button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert glass-card border-success text-success mb-4 animate__animated animate__fadeIn">
            <i class="bi bi-check-all me-2"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($customerId && !$error): ?>
        <div class="points-hero mb-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="tier-badge <?php echo $tierBg; ?> mb-3 d-inline-block">
                        <i class="bi <?php echo $tierIcon; ?> me-1"></i> <?php echo $tier; ?> member
                    </span>
                    <h1 class="display-4 fw-bold mb-2">Hello, Member!</h1>
                    <p class="fs-5 opacity-75">You have <span class="fw-bold text-white"><?php echo number_format($currentPoints); ?></span> loyalty points available.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="bg-white bg-opacity-20 p-4 rounded-4 backdrop-blur">
                        <small class="text-uppercase fw-bold opacity-75">Points earned</small>
                        <h2 class="mb-0 fw-bold"><?php echo number_format($totalPointsEarned); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Rewards Section -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0">Exclusive Rewards</h3>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary rounded-pill px-3">Filter</button>
                    </div>
                </div>

                <div class="row g-4">
                    <?php foreach ($rewards as $r): ?>
                        <div class="col-md-6">
                            <div class="glass-card p-4 h-100 position-relative border-0 shadow-lg">
                                <div class="reward-icon bg-info bg-opacity-10 text-primary">
                                    <i class="bi <?php 
                                        if (strpos($r['name'], 'Fuel') !== false) echo 'bi-droplet-fill';
                                        elseif (strpos($r['name'], 'Wash') !== false) echo 'bi-water';
                                        else echo 'bi-gear-fill';
                                    ?>"></i>
                                </div>
                                <h4 class="fw-bold"><?php echo htmlspecialchars($r['name']); ?></h4>
                                <p class="text-muted small mb-4"><?php echo htmlspecialchars($r['description']); ?></p>
                                
                                <div class="d-flex justify-content-between align-items-end mt-auto">
                                    <div>
                                        <small class="text-muted d-block">Cost</small>
                                        <span class="fw-bold fs-4 text-primary"><?php echo number_format($r['points_cost']); ?></span> 
                                        <small class="text-muted">pts</small>
                                    </div>
                                    <form method="POST" action="loyalty.php" class="ms-2 flex-grow-1">
                                        <input type="hidden" name="redeem_reward" value="1">
                                        <input type="hidden" name="reward_id" value="<?php echo $r['reward_id']; ?>">
                                        <?php if ($currentPoints >= $r['points_cost']): ?>
                                            <button type="submit" class="btn btn-redeem w-100" onclick="return confirm('Use <?php echo $r['points_cost']; ?> points?');">
                                                Redeem
                                            </button>
                                        <?php else: ?>
                                            <button disabled class="btn btn-light w-100 text-muted opacity-50 border-0">
                                                Lacking <?php echo number_format($r['points_cost'] - $currentPoints); ?>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Redemption History -->
                <div class="glass-card p-4 h-100">
                    <h4 class="fw-bold mb-4">Redemption History</h4>
                    <?php if (empty($myRedemptions)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-clock-history fs-1 text-muted opacity-25 d-block mb-3"></i>
                            <p class="text-muted">No redemptions yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($myRedemptions as $history): ?>
                            <div class="history-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold"><?php echo htmlspecialchars($history['name']); ?></span>
                                    <span class="text-danger small fw-bold">-<?php echo $history['points_cost']; ?> pts</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted"><?php echo date('d M, Y', strtotime($history['redeemed_date'])); ?></small>
                                    <span class="badge rounded-pill <?php echo $history['status'] == 'Fulfilled' ? 'bg-success' : 'bg-warning text-dark'; ?> bg-opacity-10 py-1 px-2 border-0" style="font-size: 0.65rem;">
                                        <?php echo $history['status']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>