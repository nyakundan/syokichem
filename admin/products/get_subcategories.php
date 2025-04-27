<?php
require_once __DIR__ . '/../components/connect.php';
header('Content-Type: application/json');

function fetchDescendants($conn, $parent_id, $depth = 0) {
    $stmt = $conn->prepare('SELECT id, name FROM product_categories WHERE parent_id = ? ORDER BY name');
    $stmt->execute([$parent_id]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];
    foreach ($categories as $cat) {
        $cat['depth'] = $depth;
        $result[] = $cat;
        $result = array_merge($result, fetchDescendants($conn, $cat['id'], $depth + 1));
    }
    return $result;
}

$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : null;
if ($parent_id === null) {
    echo json_encode([]);
    exit;
}

$descendants = fetchDescendants($conn, $parent_id);
echo json_encode($descendants);
