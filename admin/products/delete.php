<?php
declare(strict_types=1);

// Start session at the very beginning
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

// Validate product ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['error'] = 'Invalid product ID';
    header('Location: list.php');
    exit();
}

$productId = (int)$_POST['id'];

try {
    // Check if product exists first
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    
    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'Product not found';
        header('Location: list.php');
        exit();
    }

    // Delete the product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    
    // Check if any rows were affected
    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Product deleted successfully';
    } else {
        $_SESSION['error'] = 'No product was deleted';
    }
    
} catch (PDOException $e) {
    error_log("Product delete error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to delete product. It may be referenced in orders.';
}

header('Location: list.php');
exit();