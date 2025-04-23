<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/connect.php';

// Check if user is logged in and is an admin
//if (!isset($_SESSION['admin_id'])) {
   // http_response_code(401);
   // echo json_encode(['success' => false, 'error' => 'Unauthorized']);
   // exit();
//}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit();
}

// Validate CSRF token
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

// Get and validate input
$recipientId = (int)($_POST['recipient_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
$type = $_POST['type'] ?? 'info';

if ($recipientId <= 0 || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

// Validate notification type
$allowedTypes = ['info', 'warning', 'success', 'error'];
if (!in_array($type, $allowedTypes)) {
    $type = 'info';
}

try {
    // Check if recipient exists
    $stmt = $conn->prepare("SELECT id FROM admins WHERE id = ?");
    $stmt->execute([$recipientId]);
    if ($stmt->rowCount() === 0) {
        throw new Exception('Recipient not found');
    }

    // Insert notification
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (recipient_id, sender_id, message, notification_type, is_read, created_at)
        VALUES (?, ?, ?, ?, 0, NOW())
    ");
    $stmt->execute([
        $recipientId,
        $_SESSION['admin_id'],
        $message,
        $type
    ]);

    // Return success
    echo json_encode([
        'success' => true, 
        'notification_id' => $conn->lastInsertId(),
        'message' => 'Notification sent successfully'
    ]);
} catch (PDOException $e) {
    error_log("Notification send failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("Notification send failed: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}