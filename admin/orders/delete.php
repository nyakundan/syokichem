<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['error_message'] = 'Invalid order ID.';
    header('Location: list.php');
    exit();
}

$order_id = (int)$_GET['id'];

try {
    // Optionally, check if order exists
    $stmt = $conn->prepare('SELECT id FROM orders WHERE id = ?');
    $stmt->execute([$order_id]);
    if ($stmt->rowCount() === 0) {
        $_SESSION['error_message'] = 'Order not found.';
        header('Location: list.php');
        exit();
    }

    // Delete order
    $delete_stmt = $conn->prepare('DELETE FROM orders WHERE id = ?');
    $delete_stmt->execute([$order_id]);
    $_SESSION['success_message'] = 'Order deleted successfully.';
} catch (PDOException $e) {
    error_log('Order delete error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Failed to delete order.';
}

header('Location: list.php');
exit();
