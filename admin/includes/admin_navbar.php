<?php
declare(strict_types=1);

// Security check - prevent direct access
// (!defined('ADMIN_PATH')) {
  //exit("Access denied");
//

// Get current admin data
//dmin = getCurrentAdmin();
//nreadNotifications = getUnreadNotificationCount();
//tats = $stats ?? [
   //pending_orders' => 0,
   //pending_blog_comments' => 0
//
?>

<nav class="top-navbar navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <!-- Brand/Logo -->
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-store me-2"></i>
            <span>Admin Panel</span>
        </a>
        
        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Main Navigation -->
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </li>
                
                <!-- Products Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="productsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-box-open me-1"></i> Products
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="products/list.php">All Products</a></li>
                        <li><a class="dropdown-item" href="products/add.php">Add New Product</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="products/categories.php">Categories</a></li>
                        <li><a class="dropdown-item" href="products/inventory.php">Inventory</a></li>
                    </ul>
                </li>
                
                <!-- Orders -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="ordersDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-shopping-cart me-1"></i> Orders
                        <?php if ($stats['pending_orders'] > 0): ?>
                            <span class="badge bg-danger ms-1"><?= $stats['pending_orders'] ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="orders/list.php">All Orders</a></li>
                        <li><a class="dropdown-item" href="orders/list.php?status=pending">Pending Orders</a></li>
                        <li><a class="dropdown-item" href="orders/list.php?status=processing">Processing</a></li>
                        <li><a class="dropdown-item" href="orders/list.php?status=completed">Completed</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="orders/refunds.php">Refund Requests</a></li>
                    </ul>
                </li>
                
                <!-- Customers -->
                <li class="nav-item">
                    <a class="nav-link" href="customers/list.php">
                        <i class="fas fa-users me-1"></i> Customers
                    </a>
                </li>
                
                <!-- Blog -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="blogDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-blog me-1"></i> Blog
                        <?php if ($stats['pending_blog_comments'] > 0): ?>
                            <span class="badge bg-danger ms-1"><?= $stats['pending_blog_comments'] ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="blog/posts.php">All Posts</a></li>
                        <li><a class="dropdown-item" href="blog/add.php">Add New Post</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="blog/categories.php">Categories</a></li>
                        <li><a class="dropdown-item" href="blog/comments.php">Comments</a></li>
                    </ul>
                </li>
                
                <!-- Marketing -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="marketingDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bullhorn me-1"></i> Marketing
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="marketing/coupons.php">Coupons</a></li>
                        <li><a class="dropdown-item" href="marketing/discounts.php">Discounts</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="marketing/email.php">Email Campaigns</a></li>
                    </ul>
                </li>
                
                <!-- Reports -->
                <li class="nav-item">
                    <a class="nav-link" href="reports/sales.php">
                        <i class="fas fa-chart-bar me-1"></i> Reports
                    </a>
                </li>
            </ul>
            
            <!-- Right Side Navigation -->
            <ul class="navbar-nav ms-auto">
                <!-- Notifications -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell me-1"></i>
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="badge bg-danger"><?= $unreadNotifications ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <?php if (function_exists('getRecentNotifications')): ?>
                            <?php foreach(getRecentNotifications(5) as $notification): ?>
                            <li>
                                <a class="dropdown-item <?= $notification['is_read'] ? '' : 'fw-bold' ?>" href="notifications/view.php?id=<?= $notification['id'] ?>">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 me-2">
                                            <i class="fas fa-<?= $notification['icon'] ?? 'bell' ?> text-<?= $notification['type'] ?? 'primary' ?>"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted"><?= timeAgo($notification['created_at']) ?></small>
                                            <div><?= htmlspecialchars($notification['message']) ?></div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><a class="dropdown-item text-muted">Notification system not available</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="notifications/list.php">View All Notifications</a></li>
                    </ul>
                </li>
                
                <!-- Admin Profile -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                        <?php if (!empty($admin)): ?>
                            <img src="<?= getAdminAvatar($admin['email'] ?? '') ?>" class="rounded-circle me-1" width="30" height="30">
                            <?= htmlspecialchars($admin['name'] ?? 'Admin') ?>
                        <?php else: ?>
                            <i class="fas fa-user me-1"></i> Account
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (!empty($admin)): ?>
                            <li><h6 class="dropdown-header">Signed in as <?= htmlspecialchars($admin['email'] ?? '') ?></h6></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Content Wrapper -->
<div class="container-fluid pt-5 mt-4">
    <!-- Flash Messages -->
    <?php if (function_exists('displayFlashMessages')) displayFlashMessages(); ?>