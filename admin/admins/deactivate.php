<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/connect.php';
initSession();
verifyAdminSession();
verifyAdminRole(['superadmin']);

if (!isset($_GET['id'], $_GET['csrf_token'])) {
    redirectWithMessage('manage.php', 'error', 'Invalid request parameters');
}

if (!verifyCsrfToken($_GET)) {
    redirectWithMessage('manage.php', 'error', 'Invalid CSRF token');
}

$adminId = (int)$_GET['id'];

// Prevent deactivating own account
if ($adminId === $_SESSION['admin']['id']) {
    redirectWithMessage('manage.php', 'error', 'You cannot deactivate your own account');
}

try {
    // Check if admin exists and is active
    $stmt = $pdo->prepare("SELECT email, is_active FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        redirectWithMessage('manage.php', 'error', 'Admin not found');
    }
    
    if (!$admin['is_active']) {
        redirectWithMessage('manage.php', 'warning', 'Admin is already inactive');
    }
    
    // Deactivate admin
    $pdo->prepare("UPDATE admins SET is_active = 0, updated_at = NOW() WHERE id = ?")->execute([$adminId]);
    
    // Log this action
    logAdminAction(
        $_SESSION['admin']['id'],
        'deactivate_admin',
        "Deactivated admin: {$admin['email']} (ID: $adminId)"
    );
    
    redirectWithMessage('manage.php', 'success', 'Admin deactivated successfully');
    
} catch (PDOException $e) {
    error_log("Admin deactivation failed: " . $e->getMessage());
    redirectWithMessage('manage.php', 'error', 'Database error while deactivating admin');
}