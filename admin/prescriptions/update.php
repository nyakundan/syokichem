<?php
declare(strict_types=1);

//require 'C:/xampp/htdocs/syokichem/admin/includes/auth.php';
//require 'C:/xampp/htdocs/syokichem/admin/components/connect.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';


if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header("Location: list.php?error=Invalid request");
    exit;
}

$prescription_id = (int)$_GET['id'];
$new_status = $_GET['status'];

if (!in_array($new_status, ['approved', 'rejected'])) {
    header("Location: list.php?error=Invalid status");
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE prescriptions 
        SET status = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$new_status, $prescription_id]);
    
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => "Prescription has been " . $new_status . " successfully!"
    ];
} catch (PDOException $e) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => "Failed to update prescription: " . $e->getMessage()
    ];
}

header("Location: list.php");
exit;