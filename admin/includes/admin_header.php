<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?>">
        <?= htmlspecialchars($_SESSION['flash_message']['message']) ?>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif;?>
 <?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify admin is logged in
//if (!isset($_SESSION['admin_id'])) {
   // header("Location: ../admin/login.php");
    //exit;
//}

// Database connection
require_once __DIR__ . '/../components/connect.php'; // Adjusted for live server

// Base URL for admin section
$base_url = '/ecommerce website/admin/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?= htmlspecialchars($page_title ?? 'Dashboard') ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/admin.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= $base_url ?>dashboard.php"><i class="fas fa-home"></i> Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url ?>dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="productsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-box"></i> Products
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $base_url ?>products/list.php">All Products</a></li>
                            <li><a class="dropdown-item" href="<?= $base_url ?>products/add.php">Add New Product</a></li>
                            <li><a class="dropdown-item" href="<?= $base_url ?>products/inventory.php">Inventory</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-tags"></i> Categories
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $base_url ?>categories/list.php">All Categories</a></li>
                            <li><a class="dropdown-item" href="<?= $base_url ?>categories/add.php">Add New Category</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="ordersDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-shopping-cart"></i> Orders
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $base_url ?>orders/list.php">All Orders</a></li>
                            <li><a class="dropdown-item" href="<?= $base_url ?>orders/pending.php">Pending Orders</a></li>
                            <li><a class="dropdown-item" href="<?= $base_url ?>orders/completed.php">Completed Orders</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="usersDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users"></i> Users
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $base_url ?>users/list.php">All Users</a></li>
                            <li><a class="dropdown-item" href="<?= $base_url ?>users/add.php">Add New User</a></li>
                            <li><a class="dropdown-item" href="<?= $base_url ?>users/customers.php">Customers</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield"></i> Admins
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $base_url ?>admins/list.php">All Admins</a></li>
                            <li><a class="dropdown-item" href="<?= $base_url ?>admins/add.php">Add New Admin</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="prescriptionsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-prescription-bottle-alt"></i> Prescriptions
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $base_url ?>prescriptions/list.php">All Prescriptions</a></li>
                            <li><a class="dropdown-item" href="<?= $base_url ?>prescriptions/pending.php">Pending Approval</a></li>
                            <li><a class="dropdown-item" href="<?= $base_url ?>prescriptions/approved.php">Approved</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url ?>reports.php">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url ?>notifications.php">
                            <i class="fas fa-bell"></i>
                            <span class="badge bg-danger">3</span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= $base_url ?>profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="<?= $base_url ?>settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= $base_url ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar (hidden on mobile) -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse" id="adminNav">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="<?= $base_url ?>dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_url ?>products/list.php">
                                <i class="fas fa-box"></i> Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_url ?>categories/list.php">
                                <i class="fas fa-tags"></i> Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_url ?>orders/list.php">
                                <i class="fas fa-shopping-cart"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_url ?>users/list.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_url ?>admins/list.php">
                                <i class="fas fa-user-shield"></i> Admins
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_url ?>prescriptions/list.php">
                                <i class="fas fa-prescription-bottle-alt"></i> Prescriptions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_url ?>settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_url ?>reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content Area -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mt-3 mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= $base_url ?>dashboard.php"><i class="fas fa-home"></i> Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($page_title ?? 'Current Page') ?></li>
                    </ol>
                </nav>

                <!-- Page Title -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= htmlspecialchars($page_title ?? 'Dashboard') ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
                        <?php if (isset($add_new_link)): ?>
                            <a href="<?= $add_new_link ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Add New
                            </a>
                        <?php endif; ?>
                    </div>
                </div>