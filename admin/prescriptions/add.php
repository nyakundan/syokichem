<?php
declare(strict_types=1);

$page_title = "Add New Prescription";
//require 'C:/xampp/htdocs/ecommerce website/admin/includes/auth.php';
//require 'C:/xampp/htdocs/ecommerce website/admin/components/connect.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';


$errors = [];
$formData = [
    'user_id' => '',
    'prescription_items' => '',
    'status' => 'pending'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'user_id' => $_POST['user_id'] ?? '',
        'prescription_items' => $_POST['prescription_items'] ?? '',
        'status' => $_POST['status'] ?? 'pending'
    ];
    
    // Validation
    if (empty($formData['user_id'])) {
        $errors['user_id'] = 'Patient is required';
    }
    
    if (empty($formData['prescription_items'])) {
        $errors['prescription_items'] = 'Prescription items are required';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO prescriptions 
                (user_id, prescription_items, status, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $formData['user_id'],
                $formData['prescription_items'],
                $formData['status']
            ]);
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Prescription added successfully!'
            ];
            header("Location: list.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get patients list
try {
    $patients_stmt = $conn->prepare("SELECT id, name, email FROM users ORDER BY name");
    $patients_stmt->execute();
    $patients = $patients_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Failed to load patients: ' . $e->getMessage();
}

//include 'C:/xampp/htdocs/ecommerce website/admin/includes/admin_header.php';

include __DIR__ . '/../includes/admin_header.php';


?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Add New Prescription</h6>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
        
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="mb-3">
                    <label for="user_id" class="form-label">Patient</label>
                    <select class="form-select <?= isset($errors['user_id']) ? 'is-invalid' : '' ?>" 
                            id="user_id" name="user_id" required>
                        <option value="">Select Patient</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= $patient['id'] ?>" <?= 
                                $formData['user_id'] == $patient['id'] ? 'selected' : '' 
                            ?>>
                                <?= htmlspecialchars($patient['name']) ?> (<?= htmlspecialchars($patient['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['user_id'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['user_id']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="pending" <?= $formData['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $formData['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $formData['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="prescription_items" class="form-label">Prescription Items</label>
                    <textarea class="form-control <?= isset($errors['prescription_items']) ? 'is-invalid' : '' ?>" 
                              id="prescription_items" name="prescription_items" rows="10" required><?= 
                        htmlspecialchars($formData['prescription_items']) 
                    ?></textarea>
                    <?php if (isset($errors['prescription_items'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['prescription_items']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Prescription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
//require_once 'C:/xampp/htdocs/ecommerce website/admin/includes/admin_footer.php';
require_once __DIR__ . '/../includes/admin_footer.php';


?>