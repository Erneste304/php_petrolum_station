<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petroleum Station Management System</title>

    <?php
    // Dynamic base path detection
    $current_dir = dirname($_SERVER['PHP_SELF']);
    // If we are in a subfolder (not root petroleum_station_php), we need level up
    $dir_prefix = (basename($current_dir) === 'petroleum_station_php') ? '' : '../';
    ?>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- DataTables CSS for better tables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $dir_prefix; ?>css/custom.css">
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-info">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $dir_prefix; ?>index.php">
                <i class="bi bi-fuel-pump"></i> Petroleum Station MS
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $dir_prefix; ?>customers/index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <?php if (hasPermission('stations')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-building"></i> Stations
                            </a>
                            <ul class="dropdown-menu shadow">
                                <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>stations/index.php">View All</a></li>
                                <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>stations/create.php">Add New</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission('employees')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-people"></i> Employees
                            </a>
                            <ul class="dropdown-menu shadow">
                                <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>employees/index.php">View All</a></li>
                                <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>employees/create.php">Add New</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission('customers')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-badge"></i> Customers
                            </a>
                            <ul class="dropdown-menu shadow">
                                <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>customers/index.php">View All</a></li>
                                <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>customers/create.php">Add New</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission('fuel')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-droplet"></i> Fuel
                            </a>
                            <ul class="dropdown-menu shadow">
                                <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>fuel/index.php">View All</a></li>
                                <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>fuel/create.php">Add New</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission('sales')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-cart"></i> Sales
                            </a>
                            <ul class="dropdown-menu shadow">
                                <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>sales/index.php">View All</a></li>
                                <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>sales/create.php">New Sale</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (isCustomer()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $dir_prefix; ?>fuel_purchase.php">
                                <i class="bi bi-droplet-half"></i> Buy Fuel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $dir_prefix; ?>my_purchases.php">
                                <i class="bi bi-clock-history"></i> My Purchases
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): 
                        // Fetch user profile photo if not already defined
                        $header_stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE user_id = ?");
                        $header_stmt->execute([$_SESSION['user_id']]);
                        $header_user = $header_stmt->fetch();
                        $profile_photo = $header_user['profile_photo'] ?? null;
                    ?>
                        <!-- Services Dropdown -->
                        <?php if (hasPermission('fuel_delivery') || hasPermission('car_wash') || hasPermission('loyalty') || hasPermission('shares')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-grid"></i> Services
                            </a>
                            <ul class="dropdown-menu shadow">
                                <?php if (hasPermission('fuel_delivery')): ?>
                                <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>fuel_delivery.php"><i class="bi bi-truck me-2 text-primary"></i> Fuel Delivery</a></li>
                                <?php endif; ?>

                                <?php if (hasPermission('loyalty')): ?>
                                <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>loyalty.php"><i class="bi bi-gift me-2 text-warning"></i> Loyalty & Rewards</a></li>
                                <?php endif; ?>

                                <?php if (hasPermission('car_wash') || hasPermission('shares')): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Partner Services</h6></li>
                                
                                <?php if (hasPermission('car_wash')): ?>
                                <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>car_wash.php"><i class="bi bi-car-front me-2 text-info"></i> Car Detailing</a></li>
                                <?php endif; ?>

                                <?php if (hasPermission('shares')): ?>
                                <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>partner_shares.php"><i class="bi bi-graph-up-arrow me-2 text-warning"></i> Buy Shares</a></li>
                                <?php endif; ?>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <?php endif; ?>

                        <!-- Role-Specific Links -->
                        <?php if (isReceptionist()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $dir_prefix; ?>receptionist_dashboard.php"><i class="bi bi-cpu"></i> Control Room</a>
                            </li>
                        <?php endif; ?>

                        <?php if (isAccountant()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $dir_prefix; ?>accountant_dashboard.php"><i class="bi bi-bank"></i> Financials</a>
                            </li>
                        <?php endif; ?>

                        <?php if (isAdmin()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-shield-lock"></i> Administration
                                </a>
                                <ul class="dropdown-menu shadow border-0" style="border-radius: 12px;">
                                    <li><h6 class="dropdown-header">Operations Review</h6></li>
                                    <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>admin_shares_requests.php"><i class="bi bi-shield-shaded me-2"></i>Share Approvals</a></li>
                                    <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>admin_bulk_deliveries.php"><i class="bi bi-truck me-2"></i>Bulk Deliveries</a></li>
                                    <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>admin_payroll_approval.php"><i class="bi bi-cash-coin me-2"></i>Payroll Authority</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">System Settings</h6></li>
                                    <li><a class="dropdown-item" href="<?php echo $dir_prefix; ?>admin_shares_config.php"><i class="bi bi-gear me-2"></i>Market Strategy</a></li>
                                    <li><a class="dropdown-item text-warning fw-bold" href="<?php echo $dir_prefix; ?>admin_audit_trail.php"><i class="bi bi-shield-check me-2"></i>Audit Trail</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <!-- Unified User Dropdown -->
                        <li class="nav-item dropdown ms-lg-3">
                            <a class="nav-link dropdown-toggle d-flex align-items-center bg-white bg-opacity-10 rounded-pill px-3 py-1 mt-1 mt-lg-0" href="#" role="button" data-bs-toggle="dropdown">
                                <?php if ($profile_photo): ?>
                                    <img src="<?php echo $dir_prefix; ?>img/profiles/<?php echo $profile_photo; ?>" class="rounded-circle me-2" style="width: 28px; height: 28px; object-fit: cover;">
                                <?php else: ?>
                                    <i class="bi bi-person-circle me-2"></i>
                                <?php endif; ?>
                                <span class="small fw-bold text-white"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="border-radius: 15px; overflow: hidden;">
                                <li><h6 class="dropdown-header border-bottom pb-2">User Settings</h6></li>
                                <li><a class="dropdown-item py-2" href="<?php echo $dir_prefix; ?>profile.php"><i class="bi bi-person-badge me-2 text-primary"></i> My Profile</a></li>
                                <li><a class="dropdown-item py-2" href="<?php echo $dir_prefix; ?>settings.php"><i class="bi bi-gear me-2 text-info"></i> Account Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-danger fw-bold" href="<?php echo $dir_prefix; ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                            </ul>
                        </li>

                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-light rounded-pill px-4 btn-sm fw-bold my-1" href="<?php echo $dir_prefix; ?>login.php">
                                Login <i class="bi bi-arrow-right-short"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container mt-4">
        <!-- Back Button for Service Navigation -->
        <?php 
        $full_path = $_SERVER['PHP_SELF'];
        $current_page = basename($full_path);
        
        
        $is_root_index = (strpos($full_path, 'php/index.php') !== false || $full_path == '/index.php');
        $excluded_pages = ['index.php', 'receptionist_dashboard.php', 'accountant_dashboard.php', 'login.php', 'register.php'];
        
        if (!in_array($current_page, $excluded_pages) || (!$is_root_index && $current_page == 'index.php')): ?>
            <div class="mb-4 d-print-none animate__animated animate__fadeInLeft">
                <a href="javascript:history.back()" class="btn btn-white shadow-sm rounded-pill px-4 border-0 transition-hover py-2">
                    <i class="bi bi-arrow-left-circle-fill text-info fs-5 me-2"></i>
                    <span class="fw-bold text-dark">Go Back</span>
                </a>
            </div>
        <?php endif; ?>
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success'];
                                                    unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['error'];
                                                            unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>