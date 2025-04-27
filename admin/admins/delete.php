<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/connect.php';


if (!isset($_GET['id'], $_GET['csrf_token'])) {
    redirectWithMessage('manage.php', 'error', 'Invalid request parameters');
}

if (!verifyCsrfToken($_GET)) {
    redirectWithMessage('manage.php', 'error', 'Invalid CSRF token');
}

$adminId = (int)$_GET['id'];

// Prevent deleting own account
if ($adminId === $_SESSION['admin']['id']) {
    redirectWithMessage('manage.php', 'error', 'You cannot delete your own account');
}

try {
    // Get admin details before deletion for logging
    $stmt = $pdo->prepare("SELECT email FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        redirectWithMessage('manage.php', 'error', 'Admin not found');
    }
    
    $pdo->beginTransaction();

    // Delete related admin_logs records
    $pdo->prepare("DELETE FROM admin_logs WHERE admin_id = ?")->execute([$adminId]);
    
    // Delete admin
    $pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$adminId]);

    $pdo->commit();

    // Log this action
    logAdminAction(
        $_SESSION['admin']['id'],
        'delete_admin',
        "Deleted admin: {$admin['email']} (ID: $adminId)"
    );
    
    redirectWithMessage('manage.php', 'success', 'Admin deleted successfully');
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Admin deletion failed: " . $e->getMessage());
    redirectWithMessage('manage.php', 'error', 'Database error while deleting admin');
}