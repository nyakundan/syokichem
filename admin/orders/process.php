<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Minimal verification - just check if logged in
if (!isLoggedIn()) {
   header('Location: /ecommerce%20website/admin/orders/process.php');
   exit;
}
//checkAdminAccess();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = sanitizeInput($_POST['status']); // e.g., 'shipped'
    
    $valid_statuses = ['processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        die("Invalid status");
    }

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    
    // Log this action
    redirectWithMessage('list.php', 'success', "Order #$order_id updated to $new_status");
}
?>