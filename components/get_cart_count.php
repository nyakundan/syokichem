<?php
session_start();
require_once 'connect.php';

$user_id = $_SESSION['user_id'] ?? '';
$count = 0;

if($user_id) {
    $count_cart = $conn->prepare("SELECT COUNT(*) FROM `cart` WHERE user_id = ?");
    $count_cart->execute([$user_id]);
    $count = $count_cart->fetchColumn();
}

echo $count;
?>
