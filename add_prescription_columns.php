<?php
include 'components/connect.php';

try {
    // Add new columns to prescriptions table
    $alter_table = $conn->prepare("
        ALTER TABLE `prescriptions` 
        ADD COLUMN `recipient_name` VARCHAR(100) NOT NULL AFTER `prescription_file`,
        ADD COLUMN `recipient_phone` VARCHAR(20) NOT NULL AFTER `recipient_name`,
        ADD COLUMN `delivery_address` TEXT NOT NULL AFTER `recipient_phone`,
        ADD COLUMN `medical_staff_id` INT(11) NOT NULL AFTER `delivery_address`,
        ADD COLUMN `payment_method` VARCHAR(50) NOT NULL AFTER `medical_staff_id`,
        
    ");
    
    $alter_table->execute();
    echo "Columns added successfully!";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 