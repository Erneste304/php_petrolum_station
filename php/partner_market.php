<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

if (!isPartner() && !isAdmin()) {
    $_SESSION['error'] = "Access denied.";
    header("Location: index.php");
    exit();
}

$partner_id = $_SESSION['user_id'];

// Fetch live share price
$stmt = $pdo->query("SELECT current_price FROM share_config ORDER BY id DESC LIMIT 1");
$config = $stmt->fetch();
$live_price = $config['current_price'] ?? 0;

// Fetch partner share balance
$stmt = $pdo->prepare("SELECT total_shares FROM partner_shares WHERE partner_id = ?");
$stmt->execute([$partner_id]);
$balance = $stmt->fetch();
$current_balance = $balance['total_shares'] ?? 0;

// Handle Buy/Sell requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_request'])) {
    $type = $_POST['request_type'];
    $shares = (float)$_POST['shares'];
    
    if ($shares <= 0) {
        $error = "Number of shares must be greater than zero.";
    } elseif ($type === 'sell' && $shares > $current_balance) {
        $error = "You do not have enough shares to sell.";
    } else {
        $total_amount = $shares * $live_price;
        $commission = 0;
        
        if ($type === 'sell') {
            $commission = $total_amount * 0.0001; // 0.01% commission
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO share_requests 
                (partner_id, request_type, number_of_shares, price_at_request, total_amount, commission_amount, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending')
            ");
            $stmt->execute([
                $partner_id,
                $type,
                $shares,
                $live_price,
                $total_amount,
                $commission
            ]);
            
            $_SESSION['success'] = "Your request to " . $type . " " . $shares . " shares has been submitted for approval.";
            header("Location: partner_market.php");
            exit();
        } catch (Exception $e) {
            $error = "Error submitting request: " . $e->getMessage();
        }
    }
}

// Fetch pending requests
$stmt = $pdo->prepare("SELECT * FROM share_requests WHERE partner_id = ? ORDER BY created_at DESC");
$stmt->execute([$partner_id]);
$requests = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col">
        <h2><i class="bi bi-graph-up-arrow text-warning me-2"></i> Share Market</h2>
        <p class="text-muted">Live trading desk for Petroleum Station shares.</p>
    </div>
    <div class="col-auto">
        <div class="card border-0 shadow-sm bg-light px-4 py-2 rounded-pill">
            <span class="text-muted small fw-bold me-2">Your Portfolio:</span>
            <span class="text-warning fw-bold h5 mb-0"><?php echo number_format($current_balance, 4); ?> Shares</span>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Live Price Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 overflow-hidden" style="border-radius: 15px;">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="card-title mb-0"><i class="bi bi-activity me-2"></i> Live Signal</h5>
            </div>
            <div class="card-body p-4 text-center">
                <div class="text-muted small mb-1">Current Market Price</div>
                <div class="display-6 fw-bold text-success">RWF <?php echo number_format($live_price, 2); ?></div>
                <div class="text-muted smallest mt-2 px-3 py-1 bg-light rounded-pill d-inline-block">
                    <i class="bi bi-clock-history me-1"></i> Real-time updates
                </div>
                <hr class="my-4">
                <div class="row g-2">
                    <div class="col-6">
                        <button class="btn btn-outline-success w-100 rounded-pill py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#tradeModal" onclick="setTradeType('buy')">
                            <i class="bi bi-plus-lg me-1"></i> Buy
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-outline-danger w-100 rounded-pill py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#tradeModal" onclick="setTradeType('sell')">
                            <i class="bi bi-dash-lg me-1"></i> Sell
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Market Activity / Portfolio Value -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold">My Trade History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Type</th>
                                <th>Shares</th>
                                <th>Price @ Req</th>
                                <th>Total Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">No recent trading activity.</td></tr>
                            <?php else: ?>
                                <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td class="ps-4 small"><?php echo date('d M, H:i', strtotime($req['created_at'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $req['request_type'] === 'buy' ? 'bg-success' : 'bg-danger'; ?> text-capitalize">
                                            <?php echo $req['request_type']; ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?php echo number_format($req['number_of_shares'], 4); ?></td>
                                    <td class="small">RWF <?php echo number_format($req['price_at_request'], 2); ?></td>
                                    <td class="fw-bold">RWF <?php echo number_format($req['total_amount'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $badge = match($req['status']) {
                                            'Pending' => 'bg-info',
                                            'Staff Approved' => 'bg-info',
                                            'Completed' => 'bg-success',
                                            'Rejected' => 'bg-dark',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $badge; ?>"><?php echo $req['status']; ?></span>
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
</div>

<!-- Trade Modal -->
<div class="modal fade" id="tradeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="tradeModalLabel">Order Shares</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="request_type" id="trade_type">
                    <div class="text-center mb-4 p-3 bg-light rounded-4">
                        <div class="text-muted small">Live Execution Price</div>
                        <div class="h4 fw-bold text-success mb-0">RWF <?php echo number_format($live_price, 2); ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantity (Shares)</label>
                        <input type="number" step="0.0001" min="0.0001" name="shares" id="order_shares" class="form-control form-control-lg" placeholder="0.0000" required oninput="calculateOrder()">
                    </div>
                    
                    <div class="p-3 border rounded-4 bg-white shadow-sm mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Subtotal</span>
                            <span class="fw-bold" id="subtotal_display">RWF 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2" id="commission_row" style="display:none !important;">
                            <span class="text-muted small">Commission (0.01%)</span>
                            <span class="text-danger fw-bold" id="commission_display">- RWF 0.00</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Estimated Total</span>
                            <span class="text-primary fw-bold h5 mb-0" id="total_display">RWF 0.00</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="submit" name="submit_request" class="btn btn-primary btn-lg w-100 rounded-pill shadow-sm" id="confirmTradeBtn">
                        Confirm Purchase
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const livePrice = <?php echo $live_price; ?>;
let tradeType = 'buy';

function setTradeType(type) {
    tradeType = type;
    document.getElementById('trade_type').value = type;
    document.getElementById('tradeModalLabel').innerText = type === 'buy' ? 'Purchase Shares' : 'Sell Shares';
    document.getElementById('confirmTradeBtn').innerText = type === 'buy' ? 'Confirm Purchase' : 'Confirm Sale';
    document.getElementById('confirmTradeBtn').className = type === 'buy' ? 'btn btn-success btn-lg w-100 rounded-pill shadow-sm' : 'btn btn-danger btn-lg w-100 rounded-pill shadow-sm';
    
    const commissionRow = document.getElementById('commission_row');
    if (type === 'sell') {
        commissionRow.style.setProperty('display', 'flex', 'important');
    } else {
        commissionRow.style.setProperty('display', 'none', 'important');
    }
    calculateOrder();
}

function calculateOrder() {
    const shares = parseFloat(document.getElementById('order_shares').value) || 0;
    const subtotal = shares * livePrice;
    let total = subtotal;
    let commission = 0;
    
    if (tradeType === 'sell') {
        commission = subtotal * 0.0001;
        total = subtotal - commission;
    }
    
    document.getElementById('subtotal_display').innerText = 'RWF ' + subtotal.toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('commission_display').innerText = '- RWF ' + commission.toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('total_display').innerText = 'RWF ' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
}
</script>

<?php include 'includes/footer.php'; ?>
