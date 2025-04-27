<?php
declare(strict_types=1);

// ==============================================
// SECURITY AND SESSION CONFIGURATION
// ==============================================

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Debug logging
error_log("Dashboard accessed by IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " at " . date('Y-m-d H:i:s'));

// Session configuration
$sessionParams = [
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
];

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params($sessionParams);
    session_start();
}

// ==============================================
// AUTHENTICATION CHECK
// ==============================================

if (!isset($_SESSION['admin'])) {
    error_log("Unauthorized access attempt - no admin session");
    $_SESSION['error'] = 'Please login to continue';
    header('Location: /syokichem/admin/login.php');
    exit();
}

require_once __DIR__ . '/components/connect.php';
$adminCheck = $conn->prepare("SELECT id FROM admins WHERE id = ? LIMIT 1");
$adminCheck->execute([$_SESSION['admin']['id'] ?? 0]);

if ($adminCheck->rowCount() === 0) {
    error_log("Invalid admin session - ID not found in database");
    session_unset();
    session_destroy();
    header('Location: /syokichem/admin/login.php');
    exit();
}

// ==============================================
// SESSION TIMEOUT HANDLING
// ==============================================

$inactiveTimeout = 1800; // 30 minutes

if (!isset($_SESSION['admin']['last_activity'])) {
    $_SESSION['admin']['last_activity'] = time();
}

if ((time() - $_SESSION['admin']['last_activity']) > $inactiveTimeout) {
    error_log("Session expired due to inactivity");
    session_unset();
    session_destroy();
    header('Location: /syokichem/admin/login.php?error=session_expired');
    exit();
}

$_SESSION['admin']['last_activity'] = time();

// ==============================================
// DASHBOARD DATA
// ==============================================

$stats = [
    'today_sales' => 0.0,
    'month_sales' => 0.0,
    'pending_orders' => 0,
    'total_users' => 0,
    'total_products' => 0,
    'low_stock_items' => 0,
    'total_categories' => 0,
    'active_coupons' => 0,
    'unread_notifications' => 0,
    'pending_blog_comments' => 0
];

try {
    // Get admin details
    $adminStmt = $conn->prepare("SELECT name, email, last_login FROM admins WHERE id = ?");
    $adminStmt->execute([$_SESSION['admin']['id'] ?? 0]);
    $adminData = $adminStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminData) {
        error_log("Admin not found in database");
        session_destroy();
        header('Location: /syokichem/admin/login.php?error=invalid_session');
        exit();
    }

    // Dashboard statistics
    $statQueries = [
        'today_sales' => "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'",
        'month_sales' => "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND status != 'cancelled'",
        'pending_orders' => "SELECT COUNT(*) FROM orders WHERE status = 'pending'",
        'total_users' => "SELECT COUNT(*) FROM users WHERE deleted_at IS NULL",
        'total_products' => "SELECT COUNT(*) FROM products WHERE status = 'active'",
        'low_stock_items' => "SELECT COUNT(*) FROM products WHERE stock <= stock_alert AND status = 'active'",
        'total_categories' => "SELECT COUNT(*) FROM product_categories WHERE is_active = 1",
        'active_coupons' => "SELECT COUNT(*) FROM coupons WHERE expiry_date >= CURDATE() AND is_active = 1",
        'unread_notifications' => "SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0",
        'pending_blog_comments' => "SELECT COUNT(*) FROM blog_comments WHERE status = 'pending'"
    ];

    foreach ($statQueries as $key => $query) {
        try {
            $stmt = $conn->query($query);
            $stats[$key] = $key === 'today_sales' || $key === 'month_sales' 
                ? (float)$stmt->fetchColumn() 
                : (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching $key: " . $e->getMessage());
            $stats[$key] = 0;
        }
    }

    // Recent data
    try {
        $recentOrders = $conn->prepare("
            SELECT o.id, o.total_amount, o.status, u.name AS customer, o.created_at
            FROM orders o 
            JOIN users u ON o.user_id = u.id
            WHERE o.deleted_at IS NULL
            ORDER BY o.created_at DESC 
            LIMIT 5
        ");
        $recentOrders->execute();
        $recentOrders = $recentOrders->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching recent orders: " . $e->getMessage());
        $recentOrders = [];
    }

    try {
        $lowStockItems = $conn->prepare("
            SELECT p.id, p.name, p.stock, p.stock_alert 
            FROM products p 
            WHERE p.stock <= p.stock_alert AND p.status = 'active'
            ORDER BY p.stock ASC LIMIT 5
        ");
        $lowStockItems->execute();
        $lowStockItems = $lowStockItems->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching low stock items: " . $e->getMessage());
        $lowStockItems = [];
    }

    try {
        $recentBlogPosts = $conn->prepare("
            SELECT id, title, status, created_at 
            FROM blog_posts 
            ORDER BY created_at DESC LIMIT 5
        ");
        $recentBlogPosts->execute();
        $recentBlogPosts = $recentBlogPosts->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching recent blog posts: " . $e->getMessage());
        $recentBlogPosts = [];
    }

    try {
        $activeCoupons = $conn->prepare("
            SELECT code, discount_value, expiry_date 
            FROM coupons WHERE expiry_date >= CURDATE() AND is_active = 1
            ORDER BY expiry_date ASC LIMIT 5
        ");
        $activeCoupons->execute();
        $activeCoupons = $activeCoupons->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching active coupons: " . $e->getMessage());
        $activeCoupons = [];
    }

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $recentOrders = $lowStockItems = $recentBlogPosts = $activeCoupons = [];
}

// Generate CSRF token
try {
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrfToken;
} catch (Exception $e) {
    error_log("CSRF token generation error: " . $e->getMessage());
    $csrfToken = bin2hex(random_bytes(32));
}

$page_title = "Admin Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Syokichem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #eef2ff;
            --secondary: #64748b;
            --success: #10b981;
            --info: #06b6d4;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --sidebar: #1e1b4b;
            --card-shadow: 0 1px 3px rgba(0,0,0,0.1);
            --card-shadow-hover: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            color: var(--dark);
        }

        /* Sidebar */
        .sidebar {
            background: var(--sidebar);
            min-height: 100vh;
            width: 260px;
            position: fixed;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-brand {
            height: 80px;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            color: white;
            font-weight: 600;
            font-size: 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-brand i {
            font-size: 1.8rem;
            margin-right: 0.75rem;
            color: var(--primary);
        }

        .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 0;
            border-radius: 0;
            transition: all 0.3s;
            font-weight: 500;
            display: flex;
            align-items: center;
            border-left: 3px solid transparent;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(255,255,255,0.05);
            color: white;
            border-left-color: var(--primary);
        }

        .nav-link i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
            transition: all 0.3s;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s;
            background: white;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card:hover {
            box-shadow: var(--card-shadow-hover);
            transform: translateY(-2px);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.25rem 1.5rem;
        }

        .card-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0;
        }

        /* Stats Cards */
        .stat-card {
            border-left: 4px solid;
            transition: all 0.3s;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(255,255,255,0.1), transparent);
            z-index: 0;
        }

        .stat-card .card-body {
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .stat-card i {
            font-size: 2rem;
            opacity: 0.8;
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
        }

        .stat-card h6 {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .stat-card h3 {
            font-weight: 700;
            margin-bottom: 0;
            font-size: 1.75rem;
        }

        .stat-card.border-left-primary {
            border-left-color: var(--primary);
            background-color: var(--primary-light);
        }
        .stat-card.border-left-primary i {
            color: var(--primary);
        }

        .stat-card.border-left-success {
            border-left-color: var(--success);
            background-color: rgba(16, 185, 129, 0.1);
        }
        .stat-card.border-left-success i {
            color: var(--success);
        }

        .stat-card.border-left-info {
            border-left-color: var(--info);
            background-color: rgba(6, 182, 212, 0.1);
        }
        .stat-card.border-left-info i {
            color: var(--info);
        }

        .stat-card.border-left-warning {
            border-left-color: var(--warning);
            background-color: rgba(245, 158, 11, 0.1);
        }
        .stat-card.border-left-warning i {
            color: var(--warning);
        }

        .stat-card.border-left-danger {
            border-left-color: var(--danger);
            background-color: rgba(239, 68, 68, 0.1);
        }
        .stat-card.border-left-danger i {
            color: var(--danger);
        }

        /* Tables */
        .table {
            margin-bottom: 0;
        }

        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: var(--secondary);
            border-top: none;
            background-color: #f8fafc;
        }

        .table td {
            vertical-align: middle;
            border-top: 1px solid rgba(0,0,0,0.03);
        }

        /* Quick Actions */
        .quick-actions .btn {
            padding: 0.75rem;
            font-weight: 500;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            margin-bottom: 0.5rem;
        }

        .quick-actions .btn i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        /* Badges */
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-260px);
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar.active {
                transform: translateX(0);
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-store"></i>
                <span>Syokichem</span>
            </div>
            
            <ul class="nav flex-column mt-3">
                <li class="nav-item">
                    <a href="/syokichem/admin/dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/syokichem/admin/orders/list.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/syokichem/admin/special_offers/list.php" class="nav-link">
                        <i class="fas fa-percentage"></i>
                        Special Offers
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/syokichem/admin/products/list.php" class="nav-link">
                        <i class="fas fa-box-open"></i>
                        Products
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/syokichem/admin/categories/list.php" class="nav-link">
                        <i class="fas fa-tags"></i>
                        Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/syokichem/admin/users/list.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/syokichem/admin/blog/list.php" class="nav-link">
                        <i class="fas fa-blog"></i>
                        Blog
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/syokichem/admin/coupons/list.php" class="nav-link">
                        <i class="fas fa-tag"></i>
                        Coupons
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="/syokichem/admin/settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Admin Header -->
            <header class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                    <p class="mb-0 text-muted">Welcome back, <?= htmlspecialchars($adminData['name'] ?? 'Admin') ?></p>
                </div>
                <div class="d-flex align-items-center">
                    <button class="btn btn-primary me-2">
                        <i class="fas fa-plus me-2"></i> Quick Add
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> Account
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                            <li><a class="dropdown-item" href="/syokichem/admin/profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="/syokichem/admin/settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/syokichem/admin/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </header>
            
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card border-left-primary">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Today's Sales</h6>
                            <h3 class="mb-0">Ksh.<?= number_format($stats['today_sales'], 2) ?></h3>
                            <i class="fas fa-calendar"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card border-left-success">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Monthly Sales</h6>
                            <h3 class="mb-0">Ksh.<?= number_format($stats['month_sales'], 2) ?></h3>
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card border-left-warning">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Pending Orders</h6>
                            <h3 class="mb-0"><?= $stats['pending_orders'] ?></h3>
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card border-left-info">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total Users</h6>
                            <h3 class="mb-0"><?= $stats['total_users'] ?></h3>
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Row -->
            <div class="row g-4">
                <!-- Quick Actions -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="/syokichem/admin/products/add.php" class="btn btn-primary w-100">
                                        <i class="fas fa-plus me-2"></i> Add Product
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/syokichem/admin/orders/view.php" class="btn btn-success w-100">
                                        <i class="fas fa-shopping-cart me-2"></i> Process Orders
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/syokichem/admin/blog/add.php" class="btn btn-info w-100">
                                        <i class="fas fa-blog me-2"></i> Add Blog Post
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/syokichem/admin/coupons/add.php" class="btn btn-warning w-100">
                                        <i class="fas fa-tag me-2"></i> Create Coupon
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/syokichem/admin/notifications/send.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-envelope me-2"></i> Send Notification
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/syokichem/admin/settings/payment.php" class="btn btn-danger w-100">
                                        <i class="fas fa-cog me-2"></i> Payment Settings
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/syokichem/admin/messages/list.php" class="btn btn-danger w-100">
                                        <i class="fas fa-envelope me-2"></i> Messages
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/syokichem/admin/special_offers/list.php" class="btn btn-primary w-100">
                                        <i class="fas fa-percentage me-2"></i> Special Offers
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/syokichem/admin/consultations.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-user-md me-2"></i> Booked Consultations
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Recent Orders</h5>
                            <a href="/syokichem/admin/orders/view.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($recentOrders as $order): ?>
                                        <tr>
                                            <td><a href="/syokichem/admin/orders/view.php?id=<?= $order['id'] ?>">#<?= $order['id'] ?></a></td>
                                            <td><?= htmlspecialchars($order['customer']) ?></td>
                                            <td>Ksh.<?= number_format($order['total_amount'], 2) ?></td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $order['status'] === 'completed' ? 'success' : 
                                                    ($order['status'] === 'cancelled' ? 'danger' : 'warning')
                                                ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="/syokichem/admin/orders/invoice.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-info" title="Invoice">
                                                        <i class="fas fa-file-invoice"></i>
                                                    </a>
                                                    <?php if ($order['status'] === 'pending'): ?>
                                                        <a href="/syokichem/admin/orders/assign.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-success" title="Assign">
                                                            <i class="fas fa-truck"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Second Content Row -->
            <div class="row g-4 mt-4">
                <!-- Low Stock Alerts -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Low Stock Alerts</h5>
                            <a href="/syokichem/admin/products/list.php?filter=low_stock" class="btn btn-sm btn-outline-danger">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Current Stock</th>
                                            <th>Alert Level</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($lowStockItems as $product): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($product['name']) ?></td>
                                            <td class="<?= $product['stock'] < 5 ? 'text-danger fw-bold' : 'text-warning' ?>">
                                                <?= $product['stock'] ?>
                                            </td>
                                            <td><?= $product['stock_alert'] ?></td>
                                            <td>
                                                <a href="/syokichem/admin/products/edit.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i> Restock
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Blog Posts -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Recent Blog Posts</h5>
                            <a href="/syokichem/admin/blog/list.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($recentBlogPosts as $post): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($post['title']) ?></td>
                                            <td><?= date('M d, Y', strtotime($post['created_at'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $post['status'] === 'published' ? 'success' : 'warning'
                                                ?>">
                                                    <?= ucfirst($post['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="/syokichem/admin/blog/edit.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="/syokichem/admin/blog/delete.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Admin Footer -->
    <footer class="bg-white py-3 mt-auto">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between small">
                <div class="text-muted">Copyright &copy; Syokichem <?= date('Y') ?></div>
                <div>
                    <a href="#">Privacy Policy</a>
                    &middot;
                    <a href="#">Terms &amp; Conditions</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Highlight current page in sidebar
        document.querySelectorAll('.nav-link').forEach(link => {
            if(link.href === window.location.href) {
                link.classList.add('active');
            }
        });
        
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>