<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/connect.php';

// Check if user is authorized and request method is POST
if (!isset($_SESSION['admin_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: list.php');
    exit();
}

// Validate user ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['error'] = 'Invalid user ID';
    header('Location: list.php');
    exit();
}

$userId = (int)$_POST['id'];

try {
    // Check if user exists first
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'User not found';
        header('Location: list.php');
        exit();
    }

    // Delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Check if any rows were affected
    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = 'User deleted successfully';
    } else {
        $_SESSION['error'] = 'No user was deleted';
    }
    
} catch (PDOException $e) {
    error_log("User delete error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to delete user.';
}

header('Location: list.php');
exit();