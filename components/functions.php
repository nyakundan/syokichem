<?php
// components/functions.php

function is_admin($user_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($user && $user['is_admin'] == 2);
    } catch (PDOException $e) {
        error_log("Admin check error: " . $e->getMessage());
        return false;
    }
}