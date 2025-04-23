<?php
declare(strict_types=1);

$page_title = "View User";
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header("Location: list.php?error=invalid_id");
    exit;
}

// Fetch user data
try {
    $stmt = $conn->prepare("
        SELECT u.*, 
               COUNT(o.id) as order_count,
               SUM(o.total_price) as total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header("Location: list.php?error=user_not_found");
        exit;
    }
} catch (PDOException $e) {
    $error = "Failed to load user data: " . $e->getMessage();
}

// Fetch recent orders
try {
    $orders_stmt = $conn->prepare("
        SELECT o.id, o.order_number, o.total_price, o.status, o.created_at
        FROM orders o
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $orders_stmt->execute([$user_id]);
    $recent_orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_orders = [];
}

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-user"></i> User Details</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users List
            </a>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- User Profile Card -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Profile</h6>
                    <div>
                        <a href="edit.php?id=<?= htmlspecialchars((string)$user_id) ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php if (!empty($user['image']) && $user['image'] !== 'default-user.jpg'): ?>
                            <img src="/uploads/users/<?= htmlspecialchars($user['image']) ?>" 
                                 alt="<?= htmlspecialchars($user['name']) ?>" 
                                 class="rounded-circle" width="150" height="150">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                 style="width: 150px; height: 150px; margin: 0 auto;">
                                <i class="fas fa-user text-white" style="font-size: 60px;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h4><?= htmlspecialchars($user['name']) ?></h4>
                    <p class="text-muted mb-1"><?= htmlspecialchars($user['email']) ?></p>
                    <p class="text-muted"><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></p>
                    
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>">
                            <?= htmlspecialchars(ucfirst($user['status'])) ?>
                        </span>
                        <span class="badge bg-<?= $user['is_admin'] ? 'primary' : 'info' ?>">
                            <?= htmlspecialchars($user['is_admin'] ? 'Admin' : 'Customer') ?>
                        </span>
                    </div>
                    
                    <hr>
                    
                    <div class="text-start">
                        <p><strong>Registered:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?></p>
                        <p><strong>Last Updated:</strong> <?= date('M d, Y', strtotime($user['updated_at'] ?? $user['created_at'])) ?></p>
                        <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($user['address'] ?? 'Not provided')) ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User Stats and Orders -->
        <div class="col-lg-8">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Orders</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= htmlspecialchars((string)$user['order_count']) ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Spent</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        $<?= number_format((float)($user['total_spent'] ?? 0), 2) ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Avg. Order Value</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        $<?= $user['order_count'] > 0 ? number_format((float)($user['total_spent'] / $user['order_count']), 2) : '0.00' ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                    <a href="../orders/list.php?user_id=<?= htmlspecialchars((string)$user_id) ?>" class="btn btn-sm btn-link">
                        View All Orders
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-muted">No recent orders found</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($order['order_number'] ?? $order['id']) ?></td>
                                            <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                            <td>$<?= number_format((float)$order['total_price'], 2) ?></td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $order['status'] === 'delivered' ? 'success' : 
                                                    ($order['status'] === 'cancelled' ? 'danger' : 'info')
                                                ?>">
                                                    <?= htmlspecialchars(ucfirst($order['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="../orders/view.php?id=<?= htmlspecialchars((string)$order['id']) ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>