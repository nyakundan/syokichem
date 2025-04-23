<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require __DIR__ . '/../components/connect.php';

try {
    // First, let's verify the database connection
    echo "Testing database connection...<br>";
    $test_query = $conn->query("SELECT DATABASE()");
    $db_name = $test_query->fetchColumn();
    echo "Connected to database: " . htmlspecialchars($db_name) . "<br><br>";

    // Check if the admins table exists
    echo "Checking if admins table exists...<br>";
    $table_check = $conn->query("SHOW TABLES LIKE 'admins'");
    if ($table_check->rowCount() == 0) {
        throw new Exception("The 'admins' table does not exist in the database.");
    }
    echo "Admins table exists.<br><br>";

    // Check if the column exists
    echo "Checking if is_active column exists...<br>";
    $check_column = $conn->query("SHOW COLUMNS FROM admins LIKE 'is_active'");
    
    if ($check_column->rowCount() == 0) {
        echo "Adding is_active column...<br>";
        // Add the column if it doesn't exist
        $conn->exec("ALTER TABLE admins ADD COLUMN is_active TINYINT(1) DEFAULT 1");
        echo "Column 'is_active' added successfully.<br><br>";
    } else {
        echo "Column 'is_active' already exists.<br><br>";
    }
    
    // Update all existing admins to be active by default
    echo "Updating existing admin records...<br>";
    $update_result = $conn->exec("UPDATE admins SET is_active = 1 WHERE is_active IS NULL");
    echo "Updated " . $update_result . " admin records.<br><br>";
    
    // Verify the changes
    echo "Verifying changes...<br>";
    $verify = $conn->query("SELECT COUNT(*) as total, SUM(is_active) as active FROM admins");
    $stats = $verify->fetch(PDO::FETCH_ASSOC);
    echo "Total admins: " . $stats['total'] . "<br>";
    echo "Active admins: " . $stats['active'] . "<br>";
    
    echo "<br><strong>Operation completed successfully!</strong>";
    
} catch (PDOException $e) {
    echo "<strong>Database Error:</strong><br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "Error Message: " . $e->getMessage() . "<br>";
    echo "SQL State: " . $e->errorInfo[0] . "<br>";
} catch (Exception $e) {
    echo "<strong>Error:</strong><br>";
    echo $e->getMessage();
}
?> 