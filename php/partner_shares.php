<?php
require_once 'includes/auth_middleware.php';
require_once 'config/database.php';

if (!isPartner() && !isAdmin()) {
    $_SESSION['error'] = "Access denied.";
    header("Location: index.php");
    exit();
}

// Handle buy shares
if (isset($_POST['buy_shares'])) {
    $_SESSION['success'] = "Interest in purchasing shares has been recorded. Our team will contact you.";
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-graph-up-arrow text-warning me-2"></i> Partner Investments & Products</h2>
        <p class="text-muted">Explore investment opportunities and purchase station shares or bulk products.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
            <div class="bg-warning p-4 text-center text-white">
                <i class="bi bi-layers display-1"></i>
            </div>
            <div class="card-body p-4 text-center">
                <h4 class="fw-bold">Petroleum Station Shares</h4>
                <p class="text-muted">Own a part of the station and earn dividends from monthly sales performance.</p>
                <a href="partner_market.php" class="btn btn-warning px-5 rounded-pill fw-bold">
                    Invest Now
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
            <div class="bg-info p-4 text-center text-white">
                <i class="bi bi-box-seam display-1"></i>
            </div>
            <div class="card-body p-4 text-center">
                <h4 class="fw-bold">Bulk Purchase Products</h4>
                <p class="text-muted">Order petroleum products in bulk (Oil, Lubricants, Specialized Fuels) at partner rates.</p>
                <a href="bulk_products.php" class="btn btn-primary px-5 rounded-pill fw-bold">Browse Catalog</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
