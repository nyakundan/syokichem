<?php
declare(strict_types=1);
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'syokichem_new');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    $conn = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_ALL_TABLES'"
        ]
    );

    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} catch (PDOException $e) {
    error_log("Database Connection Failed: " . $e->getMessage());
    if (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed. Please try again later.']);
        exit;
    } else {
        die("System temporarily unavailable. Please try again later.");
    }
}

// Helper function for database access
function db(): PDO {
    global $conn;
    return $conn;
} 