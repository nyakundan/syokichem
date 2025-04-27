<?php
declare(strict_types=1);

//require 'C:/xampp/htdocs/syokichem/admin/includes/auth.php';
//require 'C:/xampp/htdocs/syokichem/admin/components/connect.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

initSession();
verifyAdminSession();
verifyAdminRole(['admin', 'doctor']);

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['toast_message'] = ['type' => 'error', 'message' => 'Invalid request method'];
    header("Location: list.php");
    exit;
}

// Verify CSRF token
verifyCsrfToken();

// Validate and sanitize inputs
$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$user_id = (int)($_POST['user_id'] ?? 0);
$doctor_id = !empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null;
$notes = cleanInput($_POST['notes'] ?? '');
$status = in_array($_POST['status'] ?? '', ['pending', 'approved', 'rejected']) ? $_POST['status'] : 'pending';

// Validate required fields
if ($user_id <= 0) {
    $_SESSION['toast_message'] = ['type' => 'error', 'message' => 'Invalid patient selected'];
    header("Location: " . ($id ? "edit.php?id=$id" : "edit.php"));
    exit;
}

// Handle file upload
$image_path = null;
if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $_FILES['image']['tmp_name']);
    finfo_close($file_info);
    
    if (!in_array($mime_type, $allowed_types)) {
        $_SESSION['toast_message'] = ['type' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, GIF, or PDF allowed'];
        header("Location: " . ($id ? "edit.php?id=$id" : "edit.php"));
        exit;
    }
    
    if ($_FILES['image']['size'] > $max_size) {
        $_SESSION['toast_message'] = ['type' => 'error', 'message' => 'File too large. Maximum 5MB allowed'];
        header("Location: " . ($id ? "edit.php?id=$id" : "edit.php"));
        exit;
    }
    
    $upload_dir = __DIR__ . '/../../uploads/prescriptions/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = 'prescription_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
    $target_file = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        $image_path = '/uploads/prescriptions/' . $filename;
        
        // If updating, delete old image
        if ($id) {
            $stmt = $pdo->prepare("SELECT image_path FROM prescriptions WHERE id = ?");
            $stmt->execute([$id]);
            $old_image = $stmt->fetchColumn();
            
            if ($old_image && file_exists(__DIR__ . '/../..' . $old_image)) {
                unlink(__DIR__ . '/../..' . $old_image);
            }
        }
    }
}

try {
    $pdo->beginTransaction();
    
    if (empty($id)) {
        // Create new prescription
        $stmt = $pdo->prepare("
            INSERT INTO prescriptions 
            (user_id, doctor_id, notes, status, image_path, created_by, updated_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $doctor_id,
            $notes,
            $status,
            $image_path,
            $_SESSION['admin']['id'],
            $_SESSION['admin']['id']
        ]);
        
        $prescription_id = $pdo->lastInsertId();
        $action = 'create_prescription';
        $message = "Prescription #$prescription_id created successfully";
    } else {
        // Update existing prescription
        if ($image_path) {
            $stmt = $pdo->prepare("
                UPDATE prescriptions 
                SET user_id = ?, doctor_id = ?, notes = ?, status = ?, image_path = ?, updated_by = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([
                $user_id,
                $doctor_id,
                $notes,
                $status,
                $image_path,
                $_SESSION['admin']['id'],
                $id
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE prescriptions 
                SET user_id = ?, doctor_id = ?, notes = ?, status = ?, updated_by = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([
                $user_id,
                $doctor_id,
                $notes,
                $status,
                $_SESSION['admin']['id'],
                $id
            ]);
        }
        
        $prescription_id = $id;
        $action = 'update_prescription';
        $message = "Prescription #$prescription_id updated successfully";
    }
    
    // Log prescription items if they exist
    if (!empty($_POST['medications'])) {
        // First delete existing items if updating
        if ($id) {
            $pdo->prepare("DELETE FROM prescription_items WHERE prescription_id = ?")->execute([$id]);
        }
        
        // Insert new items
        $item_stmt = $pdo->prepare("
            INSERT INTO prescription_items 
            (prescription_id, medication, dosage, frequency, duration, instructions) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($_POST['medications'] as $med) {
            $item_stmt->execute([
                $prescription_id,
                cleanInput($med['name']),
                cleanInput($med['dosage']),
                cleanInput($med['frequency']),
                cleanInput($med['duration']),
                cleanInput($med['instructions'])
            ]);
        }
    }
    
    $pdo->commit();
    
    // Log admin action
    logAdminAction(
        $_SESSION['admin']['id'],
        $action,
        "Prescription ID: $prescription_id | Status: $status"
    );
    
    // Send notification if status changed
    if ($id && $status !== $_POST['original_status']) {
        sendPrescriptionStatusNotification($prescription_id, $status);
    }
    
    $_SESSION['toast_message'] = ['type' => 'success', 'message' => $message];
    header("Location: view.php?id=$prescription_id");
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['toast_message'] = ['type' => 'error', 'message' => "Database error: " . $e->getMessage()];
    $_SESSION['form_data'] = $_POST; // Preserve form data
    header("Location: " . ($id ? "edit.php?id=$id" : "edit.php"));
    exit;
}

// Helper functions
function cleanInput(string $data): string {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function sendPrescriptionStatusNotification(int $prescription_id, string $status): void {
    // Implementation would depend on your notification system
    // This could be email, SMS, or in-app notification
}
?>