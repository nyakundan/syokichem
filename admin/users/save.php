<?php
require_once __DIR__ . '/../../includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: list.php");
    exit;
}

$id = $_POST['id'] ?? null;
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Validate
if (empty($name) || empty($email)) {
    $_SESSION['toast_message'] = "Name and email are required";
    header("Location: " . ($id ? "edit.php?id=$id" : "edit.php"));
    exit;
}

try {
    if (empty($id)) {
        // Create new user
        $password = password_hash('Temp1234', PASSWORD_DEFAULT); // Default temp password
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $password, $is_active]);
        $message = "User created successfully";
    } else {
        // Update existing
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $is_active, $id]);
        $message = "User updated successfully";
    }
    
    $_SESSION['toast_message'] = $message;
    header("Location: list.php");
    exit;
} catch (PDOException $e) {
    $_SESSION['toast_message'] = "Error: " . $e->getMessage();
    header("Location: " . ($id ? "edit.php?id=$id" : "edit.php"));
    exit;
}