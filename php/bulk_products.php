<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

if (!isPartner() && !isAdmin()) {
    $_SESSION['error'] = "Access denied.";
    header("Location: index.php");
    exit();
}

include 'includes/header.php';
?>

<div class="row mb-5 py-4 bg-light rounded-4 border">
    <div class="col-md-7 ps-5">
        <h1 class="display-5 fw-bold mb-3">Bulk Purchase Products</h1>
        <p class="lead text-muted mb-4">Secure your supply chain with our high-volume petroleum solutions. Competitive rates exclusively for our partners.</p>
        <div class="d-flex gap-3">
            <div class="badge bg-info px-3 py-2 rounded-pill"><i class="bi bi-shield-fill-check me-1"></i> Quality Guranteed</div>
            <div class="badge bg-success px-3 py-2 rounded-pill"><i class="bi bi-truck me-1"></i> Global Logistics</div>
        </div>
    </div>
    <div class="col-md-5 text-center d-none d-md-block">
        <i class="bi bi-layers-half text-primary opacity-25" style="font-size: 10rem;"></i>
    </div>
</div>

<div class="row g-4 mb-5">
    <?php
    $products = [
        ['id' => 1, 'name' => 'Industrial Hydraulic Oil', 'cat' => 'Industrial Oils', 'price' => 4500, 'unit' => 'L', 'icon' => 'bi-droplet-fill', 'color' => 'primary'],
        ['id' => 10, 'name' => 'Aviation Kerosene (Jet A-1)', 'cat' => 'Aviation Fuel', 'price' => 1800, 'unit' => 'L', 'icon' => 'bi-airplane-engines-fill', 'color' => 'warning'],
        ['id' => 5, 'name' => 'Ultra-Low Sulfur Diesel', 'cat' => 'Heavy Diesel', 'price' => 1600, 'unit' => 'L', 'icon' => 'bi-truck-front-fill', 'color' => 'dark'],
        ['id' => 8, 'name' => 'Extreme Pressure Grease', 'cat' => 'Specialized Lubricants', 'price' => 12000, 'unit' => 'KG', 'icon' => 'bi-gear-wide-connected', 'color' => 'info']
    ];
    foreach ($products as $p):
    ?>
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm overflow-hidden premium-hover" style="border-radius: 20px;">
            <div class="card-body p-4 text-center">
                <div class="mb-4 d-inline-block p-3 rounded-4 bg-<?php echo $p['color']; ?> bg-opacity-10 text-<?php echo $p['color']; ?>">
                    <i class="bi <?php echo $p['icon']; ?> h1 mb-0"></i>
                </div>
                <div class="text-uppercase small fw-bold text-muted mb-2"><?php echo $p['cat']; ?></div>
                <h5 class="fw-bold mb-3"><?php echo $p['name']; ?></h5>
                <div class="h4 fw-bold text-dark mb-4">RWF <?php echo number_format($p['price'], 0); ?> <span class="small text-muted fw-normal">/ <?php echo $p['unit']; ?></span></div>
                
                <button type="button" class="btn btn-outline-<?php echo $p['color']; ?> w-100 rounded-pill fw-bold" 
                        onclick="openOrderModal('<?php echo $p['name']; ?>', <?php echo $p['price']; ?>, <?php echo $p['id']; ?>)">
                    <i class="bi bi-cart-plus me-1"></i> Order Now
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-5">
                <div class="text-center mb-4">
                    <h3 class="fw-bold" id="modalProdName">Product Name</h3>
                    <p class="text-muted">Place a bulk acquisition request.</p>
                </div>
                
                <form id="bulkOrderForm" method="POST" action="process_bulk_order.php">
                    <input type="hidden" name="fuel_id" id="modalFuelId">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantity (Liters/KG)</label>
                        <input type="number" name="quantity" id="orderQty" class="form-control form-control-lg rounded-pill px-4" value="1000" min="500" required>
                        <div class="form-text">Minimum bulk order is 500 units.</div>
                    </div>
                    
                    <div class="p-3 bg-light rounded-4 mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Unit Price:</span>
                            <span class="fw-bold" id="modalUnitPrice">RWF 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between h5 mb-0 pt-2 border-top">
                            <span class="fw-bold">Estimated Cost:</span>
                            <span class="fw-bold text-primary" id="modalTotalCost">RWF 0.00</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold shadow">
                        Submit Order Request
                    </button>
                    <p class="text-center small text-muted mt-3 mb-0"><i class="bi bi-info-circle me-1"></i> All bulk orders require Admin certification.</p>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openOrderModal(name, price, id) {
    document.getElementById('modalProdName').innerText = name;
    document.getElementById('modalFuelId').value = id;
    document.getElementById('modalUnitPrice').innerText = 'RWF ' + price.toLocaleString();
    
    const qtyInput = document.getElementById('orderQty');
    const totalSpan = document.getElementById('modalTotalCost');
    
    const update = () => {
        const total = qtyInput.value * price;
        totalSpan.innerText = 'RWF ' + total.toLocaleString();
    };
    
    qtyInput.oninput = update;
    update();
    
    new bootstrap.Modal(document.getElementById('orderModal')).show();
}
</script>

<style>
.premium-hover { transition: all 0.3s ease; }
.premium-hover:hover { transform: translateY(-10px); box-shadow: 0 15px 45px rgba(0,0,0,0.1) !important; }
</style>

<?php include 'includes/footer.php'; ?>
