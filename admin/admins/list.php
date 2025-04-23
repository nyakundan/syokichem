<?php
declare(strict_types=1);

$page_title = "Manage Admins";
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Initialize variables
$admins = [];
$error = '';
$success = '';

try {
    // Fetch all admins with their activity counts
    $stmt = $conn->prepare("
        SELECT a.*, 
               (SELECT COUNT(*) FROM admin_logs WHERE admin_id = a.id) as activity_count,
               (SELECT MAX(created_at) FROM admin_logs WHERE admin_id = a.id) as last_activity
        FROM admins a
        ORDER BY a.created_at DESC
    ");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Failed to load admin data: ' . $e->getMessage();
    error_log($error);
}

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $admin_id = (int)($_POST['admin_id'] ?? 0);
    
    try {
        switch ($action) {
            case 'activate':
                $stmt = $conn->prepare("UPDATE admins SET is_active = 1 WHERE id = ?");
                $stmt->execute([$admin_id]);
                $success = "Admin account activated successfully.";
                break;
                
            case 'deactivate':
                $stmt = $conn->prepare("UPDATE admins SET is_active = 0 WHERE id = ?");
                $stmt->execute([$admin_id]);
                $success = "Admin account deactivated successfully.";
                break;
                
            case 'delete':
                // Prevent deleting the last admin
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE is_active = 1");
                $check_stmt->execute();
                $active_count = $check_stmt->fetchColumn();
                
                if ($active_count <= 1) {
                    $error = "Cannot delete the last active admin account.";
                } else {
                    $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
                    $stmt->execute([$admin_id]);
                    $success = "Admin account deleted successfully.";
                }
                break;
        }
        
        // Refresh the admin list
        $stmt = $conn->prepare("
            SELECT a.*, 
                   (SELECT COUNT(*) FROM admin_logs WHERE admin_id = a.id) as activity_count,
                   (SELECT MAX(created_at) FROM admin_logs WHERE admin_id = a.id) as last_activity
            FROM admins a
            ORDER BY a.created_at DESC
        ");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = 'Failed to perform action: ' . $e->getMessage();
        error_log($error);
    }
}

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Manage Admin Accounts</h6>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Admin
            </a>
        </div>
        
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Activity Count</th>
                            <th>Last Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$admin['id']) ?></td>
                                <td><?= htmlspecialchars($admin['name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($admin['email'] ?? '') ?></td>
                                <td>
                                    <span class="badge bg-<?= (isset($admin['is_active']) && $admin['is_active']) ? 'success' : 'danger' ?>">
                                        <?= (isset($admin['is_active']) && $admin['is_active']) ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars((string)($admin['activity_count'] ?? 0)) ?> actions</td>
                                <td>
                                    <?= isset($admin['last_activity']) && $admin['last_activity'] ? 
                                        htmlspecialchars(date('M d, Y H:i', strtotime($admin['last_activity']))) : 
                                        'Never' ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit.php?id=<?= htmlspecialchars((string)$admin['id']) ?>" 
                                           class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <?php if ($admin['is_active']): ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this admin?');">
                                                <input type="hidden" name="action" value="deactivate">
                                                <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-warning" title="Deactivate">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to activate this admin?');">
                                                <input type="hidden" name="action" value="activate">
                                                <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success" title="Activate">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this admin? This action cannot be undone.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        
                                        <a href="activity_logs.php?id=<?= htmlspecialchars((string)$admin['id']) ?>" 
                                           class="btn btn-sm btn-info" title="View Activity Logs">
                                            <i class="fas fa-history"></i>
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

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

<script>
$(document).ready(function() {
    $('#dataTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25
    });
});
</script>