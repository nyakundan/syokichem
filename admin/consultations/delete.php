<?php
require_once __DIR__ . '/../components/connect.php';

if (!isset($_GET['id'])) {
    header('Location: consultations.php');
    exit();
}

$id = (int)$_GET['id'];
$stmt = $conn->prepare("DELETE FROM consultations WHERE id = ?");
$stmt->execute([$id]);

header('Location: ../consultations.php?msg=deleted');
exit;
