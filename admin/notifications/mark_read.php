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

// Check if request is POST or GET with ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['all'])) {
    // Mark all as read
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit();
    }

    try {
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE recipient_id = ? AND is_read = 0
        ");
        $stmt->execute([$_SESSION['admin_id']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Mark all as read failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} elseif (isset($_GET['id'])) {
    // Mark single as read
    $notificationId = (int)$_GET['id'];
    
    try {
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE id = ? AND recipient_id = ?
        ");
        $stmt->execute([$notificationId, $_SESSION['admin_id']]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Notification not found or already read');
        }
        
        // Redirect back or to specific URL
        $redirect = $_GET['redirect'] ?? 'list.php';
        header("Location: $redirect");
        exit();
    } catch (PDOException $e) {
        error_log("Mark as read failed: " . $e->getMessage());
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Failed to mark notification as read'
        ];
        header("Location: list.php");
        exit();
    } catch (Exception $e) {
        error_log("Mark as read failed: " . $e->getMessage());
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => $e->getMessage()
        ];
        header("Location: list.php");
        exit();
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}