<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php'; // Include authentication if needed
require_once __DIR__ . '/../components/connect.php'; // Include database connection

// Check if the file parameter is set
if (!isset($_GET['file'])) {
    die("File not specified.");
}

// Sanitize the file name to prevent directory traversal attacks
$file = basename($_GET['file']); // Get the file name safely
$filePath = __DIR__ . '/../../uploads/prescriptions/' . $file; // Construct the full path

// Use realpath to resolve the absolute path
$realFilePath = realpath($filePath);

// Debugging output
if ($realFilePath === false || !file_exists($realFilePath)) {
    die("Invalid file path: " . htmlspecialchars($file) . ". Expected path: " . htmlspecialchars($filePath));
}

// Set headers to force download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($realFilePath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($realFilePath));
flush(); // Flush system output buffer
readfile($realFilePath); // Read the file and send it to the output buffer
exit;
