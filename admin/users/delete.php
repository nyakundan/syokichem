<?php
declare(strict_types=1);

// DEBUG: Log GET data
file_put_contents(__DIR__ . '/delete_debug.log', print_r($_GET, true), FILE_APPEND);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

if (isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    try {
        $conn->beginTransaction();

        // Delete related cart records
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Delete related wishlist records
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Delete related user_tokens records
        $stmt = $conn->prepare("DELETE FROM user_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Optionally check if user exists (optional, can be omitted for parity)
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            $conn->rollBack();
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'User not found.'
            ];
            header('Location: list.php');
            exit();
        }

        // Delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $conn->commit();

        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'User deleted successfully.'
        ];
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Error deleting user: ' . $e->getMessage()
        ];
    }
}

header('Location: list.php');
exit();