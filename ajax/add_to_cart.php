<?php
session_start();
include '../components/connect.php';
header('Content-Type: application/json');

$response = ["success" => false, "message" => "Unknown error."];

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

$pid = $_POST['pid'] ?? null;
$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? 0;
$image = $_POST['image'] ?? '';
$qty = $_POST['qty'] ?? 1;

if (!$pid) {
    $response['message'] = 'Invalid product.';
    echo json_encode($response);
    exit;
}

if ($user_id === '') {
    // Guest: use session cart
    if(!isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];
    $duplicate = false;
    foreach($_SESSION['guest_cart'] as &$item) {
        if($item['id'] == $pid) {
            $duplicate = true;
            break;
        }
    }
    if($duplicate) {
        $response['message'] = 'Product already exists in cart!';
    } else {
        $_SESSION['guest_cart'][] = [
            'id' => $pid,
            'name' => $name,
            'price' => $price,
            'quantity' => $qty,
            'image' => $image
        ];
        $response['success'] = true;
        $response['message'] = 'Product added to cart successfully!';
    }
} else {
    // Logged in user: use DB
    $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ? AND pid = ?");
    $check_cart->execute([$user_id, $pid]);
    if($check_cart->rowCount() > 0){
        $response['message'] = 'Product already exists in cart!';
    }else{
        $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
        $insert_cart->execute([$user_id, $pid, $name, $price, $qty, $image]);
        $response['success'] = true;
        $response['message'] = 'Product added to cart successfully!';
    }
}
echo json_encode($response);
