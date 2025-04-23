<?php
declare(strict_types=1);

require 'C:/xampp/htdocs/ecommerce website/admin/includes/auth.php';
require 'C:/xampp/htdocs/ecommerce website/admin/components/connect.php';
//require 'C:/xampp/htdocs/ecommerce website/admin/includes/functions.php';

// Initialize session and verify admin access
//initSession();
//verifyAdminSession();
//verifyAdminRole(['admin']); // Only allow admin users

// Handle user deletion with CSRF protection
if (isset($_GET['delete']) ){
    if (!verifyCsrfToken($_GET)) {
        redirectWithMessage('manage.php', 'error', 'Invalid CSRF token');
    }

    try {
        $user_id = (int)$_GET['delete'];
        
        // Prevent self-deletion
        if ($user_id === $_SESSION['admin']['id']) {
            redirectWithMessage('manage.php', 'error', 'You cannot delete your own account');
        }
        
        // Check if user has orders before deletion
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $order_count = $stmt->fetchColumn();
        
        if ($order_count > 0) {
            redirectWithMessage('manage.php', 'error', 'Cannot delete user with existing orders');
        }
        
        // Get user email for logging before deletion
        $email_stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $email_stmt->execute([$user_id]);
        $user_email = $email_stmt->fetchColumn();
        
        // Delete user
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        
        // Log the action
        logAdminAction(
            $_SESSION['admin']['id'],
            'delete_user',
            "Deleted user: $user_email (ID: $user_id)"
        );
        
        redirectWithMessage('manage.php', 'success', 'User deleted successfully');
        
    } catch (PDOException $e) {
        error_log("User deletion failed: " . $e->getMessage());
        redirectWithMessage('manage.php', 'error', 'Database error while deleting user');
    }
}

// Handle user status toggle
if (isset($_GET['toggle_status'])) {
    if (!verifyCsrfToken($_GET)) {
        redirectWithMessage('manage.php', 'error', 'Invalid CSRF token');
    }

    try {
        $user_id = (int)$_GET['toggle_status'];
        
        // Get current status
        $status_stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
        $status_stmt->execute([$user_id]);
        $current_status = (bool)$status_stmt->fetchColumn();
        
        // Toggle status
        $new_status = !$current_status;
        $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$new_status, $user_id]);
        
        // Log the action
        logAdminAction(
            $_SESSION['admin']['id'],
            'toggle_user_status',
            "User ID: $user_id | New status: " . ($new_status ? 'Active' : 'Inactive')
        );
        
        redirectWithMessage('manage.php', 'success', 'User status updated');
        
    } catch (PDOException $e) {
        error_log("Status toggle failed: " . $e->getMessage());
        redirectWithMessage('manage.php', 'error', 'Database error while updating status');
    }
}

// Get all users with additional info
try {
    $search_term = $_GET['search'] ?? '';
    $search_condition = '';
    $params = [];
    
    if (!empty($search_term)) {
        $search_condition = "WHERE u.email LIKE ? OR u.name LIKE ?";
        $search_param = "%$search_term%";
        $params = [$search_param, $search_param];
    }
    
    $users = $conn->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.is_active, u.created_at, 
               COUNT(o.id) as order_count,
               MAX(o.created_at) as last_order_date
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        $search_condition
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT 100
    ");
    
    $users->execute($params);
    $users = $users->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $error = 'Failed to load users: ' . $e->getMessage();
    error_log("User query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <?php include 'C:/xampp/htdocs/ecommerce website/admin/includes/admin_header.php';  ?>
    
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="mb-0"><i class="fas fa-users me-2"></i>Manage Users</h2>
                <div>
                    <form method="get" class="d-inline me-2">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control form-control-sm" 
                                   placeholder="Search users..." value="<?= htmlspecialchars($search_term ?? '') ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($search_term)): ?>
                                <a href="manage.php" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <a href="add.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Add User
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show">
                        <?= htmlspecialchars($_SESSION['flash_message']['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['flash_message']); ?>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if (empty($users)): ?>
                    <div class="alert alert-info">No users found</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registered</th>
                                    <th>Orders</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['id']) ?></td>
                                        <td><?= htmlspecialchars($user['name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                                        <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <?= $user['order_count'] ?>
                                            <?php if ($user['order_count'] > 0): ?>
                                                <small class="text-muted d-block">
                                                    Last: <?= date('M d, Y', strtotime($user['last_order_date'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="manage.php?toggle_status=<?= $user['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                               class="badge bg-<?= $user['is_active'] ? 'success' : 'danger' ?> text-decoration-none"
                                               title="Click to toggle status">
                                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="view.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="manage.php?delete=<?= $user['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                                   class="btn btn-sm btn-danger confirm-action" 
                                                   title="Delete"
                                                   data-confirm="Are you sure you want to delete this user?">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3 text-muted">
                        Showing <?= count($users) ?> users
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'C:/xampp/htdocs/ecommerce website/admin/includes/admin_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/admin.js"></script>
    <script>
    // Confirm before actions
    document.querySelectorAll('.confirm-action').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (element) {
        return new bootstrap.Tooltip(element);
    });
    </script>
</body>
</html>