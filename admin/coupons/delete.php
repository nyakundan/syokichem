<?php
declare(strict_types=1);

//require 'C:/xampp/htdocs/ecommerce website/admin/includes/auth.php';
//require 'C:/xampp/htdocs/ecommerce website/admin/components/connect.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid request method'
    ];
    header("Location: coupons/list.php");
    exit();
}

// Validate CSRF token
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid CSRF token'
    ];
    header("Location: coupons/list.php");
    exit();
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid coupon ID'
    ];
    header("Location: coupons/list.php");
    exit();
}

$couponId = (int)$_POST['id'];

try {
    // First get coupon info for logging
    $stmt = $conn->prepare("SELECT code FROM coupons WHERE id = ?");
    $stmt->execute([$couponId]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Coupon not found'
        ];
        header("Location: coupons/list.php");
        exit();
    }

    $conn->beginTransaction();

    // Delete coupon
    $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
    $stmt->execute([$couponId]);

    // Log admin action
    $logStmt = $conn->prepare("
        INSERT INTO admin_logs 
        (admin_id, action_type, description, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    $logStmt->execute([
        $_SESSION['admin_id'],
        'delete_coupon',
        "Deleted coupon: {$coupon['code']}",
        $_SERVER['REMOTE_ADDR']
    ]);

    $conn->commit();

    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'Coupon deleted successfully'
    ];
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Coupon deletion failed: " . $e->getMessage());
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Failed to delete coupon'
    ];
}

header("Location: coupons/list.php");
exit();