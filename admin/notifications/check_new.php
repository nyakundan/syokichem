<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

try {
    // Get count of unread notifications
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM notifications 
        WHERE recipient_id = ? AND is_read = 0
    ");
    $stmt->execute([$_SESSION['admin_id']]);
    $count = $stmt->fetchColumn();

    // Get latest notifications
    $stmt = $conn->prepare("
        SELECT n.*, a.email as sender_name 
        FROM notifications n
        LEFT JOIN admins a ON n.sender_id = a.id
        WHERE n.recipient_id = ? AND n.is_read = 0
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['admin_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format dates for notifications
    foreach ($notifications as &$notification) {
        $notification['created_at'] = date('M d, Y H:i', strtotime($notification['created_at']));
    }

    echo json_encode([
        'success' => true,
        'count' => $count,
        'notifications' => $notifications
    ]);
} catch (PDOException $e) {
    error_log("Check new notifications failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("Check new notifications failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}