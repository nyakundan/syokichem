<?php
declare(strict_types=1);

$page_title = "Admin Account";
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';


try {
    // Prepare the statement first
    $stmt = $conn->prepare("
        SELECT a.*, 
               (SELECT COUNT(*) FROM admin_logs WHERE admin_id = a.id) as activity_count,
               (SELECT MAX(created_at) FROM admin_logs WHERE admin_id = a.id) as last_activity
        FROM admins a
        WHERE a.id = ?
    ");
    
    // Execute separately
    $stmt->execute([$_SESSION['admin']['id']]);
    
    // Now fetch the result
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        throw new Exception("Admin account not found");
    }
} catch (PDOException $e) {
    $error = 'Failed to load admin data: ' . $e->getMessage();
    error_log($error);
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log($error);
}

require_once __DIR__ . '/../includes/admin_header.php';

?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">My Admin Account</h6>
        </div>
        
        <div class="card-body">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= htmlspecialchars((string)$_SESSION['flash_message']['type']) ?> alert-dismissible fade show">
                    <?= htmlspecialchars((string)$_SESSION['flash_message']['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars((string)$error) ?></div>
            <?php endif; ?>
            
            <?php if (isset($admin) && $admin): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Account Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>ID</th>
                                    <td><?= htmlspecialchars((string)$admin['id']) ?></td>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <td><?= htmlspecialchars($admin['name'] ?? '') ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= htmlspecialchars($admin['email'] ?? '') ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge bg-<?= ($admin['is_active'] ?? false) ? 'success' : 'danger' ?>">
                                            <?= ($admin['is_active'] ?? false) ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Activity Count</th>
                                    <td><?= htmlspecialchars((string)($admin['activity_count'] ?? 0)) ?> actions</td>
                                </tr>
                                <tr>
                                    <th>Last Active</th>
                                    <td>
                                        <?= isset($admin['last_activity']) && $admin['last_activity'] ? 
                                            htmlspecialchars(date('M d, Y H:i', strtotime($admin['last_activity']))) : 
                                            'Never' ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Account Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="edit.php?id=<?= htmlspecialchars((string)$admin['id']) ?>" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i> Edit Profile
                                </a>
                                
                                <a href="changepassword.php?id=<?= htmlspecialchars((string)$admin['id']) ?>" class="btn btn-info">
                                    <i class="fas fa-key me-2"></i> Change Password
                                </a>
                                
                                <a href="activity_logs.php?id=<?= htmlspecialchars((string)$admin['id']) ?>" class="btn btn-secondary">
                                    <i class="fas fa-history me-2"></i> View Activity Logs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/admin_footer.php';

?>

<script>
$(document).ready(function() {
    // Simple confirmation for sensitive actions
    $('.btn-danger').click(function(e) {
        if (!confirm('Are you sure you want to perform this action?')) {
            e.preventDefault();
        }
    });
});
</script>