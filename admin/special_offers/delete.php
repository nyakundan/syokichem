<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Get offer ID from POST
if (isset($_POST['id'])) {
    $offer_id = (int)$_POST['id'];
    
    try {
        // Delete the offer
        $delete = $conn->prepare("DELETE FROM special_offers WHERE id = ?");
        if ($delete->execute([$offer_id])) {
            header("Location: list.php?success=1");
            exit();
        } else {
            throw new Exception("Failed to delete offer");
        }
    } catch (PDOException $e) {
        error_log("Special offer delete error: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to delete the offer';
        header("Location: list.php");
        exit();
    }
} else {
    header("Location: list.php");
    exit();
}
