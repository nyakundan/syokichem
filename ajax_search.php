<?php
header('Content-Type: application/json');
include 'components/connect.php';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$results = [];

if($query !== '') {
    $sql = "SELECT id, name, price, image_01, stock FROM products WHERE (name LIKE ? OR description LIKE ?) AND stock > 0 LIMIT 20";
    $search_term = "%$query%";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$search_term, $search_term]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode([
    'success' => true,
    'products' => $results
]);
