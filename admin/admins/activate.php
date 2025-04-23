<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify admin is logged in
if (empty($_SESSION['admin_id']) || empty($_SESSION['logged_in'])) {
    header("Location: /ecommerce%20website/admin/login.php");
    exit;
}

// Check required parameters
if (!isset($_GET['id'], $_GET['csrf_token'])) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid request parameters'
    ];
    header("Location: manage.php");
    exit;
}

// Verify CSRF token
if (empty($_SESSION['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid CSRF token'
    ];
    header("Location: manage.php");
    exit;
}

$adminId = (int)$_GET['id'];

try {
    // Check if admin exists and is inactive (using your 'status' column)
    $stmt = $pdo->prepare("SELECT id, email, status FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Admin not found'
        ];
        header("Location: manage.php");
        exit;
    }
    
    if ($admin['status'] === 'active') {
        $_SESSION['flash_message'] = [
            'type' => 'warning',
            'message' => 'Admin is already active'
        ];
        header("Location: manage.php");
        exit;
    }
    
    // Activate admin (using your 'status' column)
    $updateStmt = $pdo->prepare("UPDATE admins SET status = 'active', last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$adminId]);
    
    // Log this action (if logging is implemented)
    if (function_exists('logAdminAction')) {
        logAdminAction(
            $_SESSION['admin_id'],
            'activate_admin',
            "Activated admin: {$admin['email']} (ID: $adminId)"
        );
    }
    
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'Admin activated successfully'
    ];
    header("Location: manage.php");
    exit;
    
} catch (PDOException $e) {
    error_log("Admin activation failed: " . $e->getMessage());
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Database error while activating admin'
    ];
    header("Location: manage.php");
    exit;
}