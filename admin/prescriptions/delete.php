<?php
declare(strict_types=1);

//require 'C:/xampp/htdocs/ecommerce website/admin/includes/auth.php';
//require 'C:/xampp/htdocs/ecommerce website/admin/components/connect.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';


if (!isset($_GET['id'])) {
    header("Location: list.php?error=Prescription ID not provided");
    exit;
}

$prescription_id = (int)$_GET['id'];

try {
    $stmt = $conn->prepare("DELETE FROM prescriptions WHERE id = ?");
    $stmt->execute([$prescription_id]);
    
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'Prescription deleted successfully!'
    ];
} catch (PDOException $e) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Failed to delete prescription: ' . $e->getMessage()
    ];
}

header("Location: list.php");
exit;