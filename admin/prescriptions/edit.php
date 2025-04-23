<?php
declare(strict_types=1);

$page_title = "Edit Prescription";
//require 'C:/xampp/htdocs/ecommerce website/admin/includes/auth.php';
//require 'C:/xampp/htdocs/ecommerce website/admin/components/connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/connect.php';


if (!isset($_GET['id'])) {
    header("Location: list.php?error=Prescription ID not provided");
    exit;
}

$prescription_id = (int)$_GET['id'];

try {
    $stmt = $conn->prepare("
        SELECT p.*, u.name as patient_name, u.email as patient_email, p.prescription_file as prescription_file
        FROM prescriptions p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$prescription_id]);
    $prescription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$prescription) {
        header("Location: list.php?error=Prescription not found");
        exit;
    }
} catch (PDOException $e) {
    header("Location: list.php?error=Database error: " . $e->getMessage());
    exit;
}

// Initialize form data with proper defaults
$errors = [];
$formData = [
    'patient_name' => $prescription['patient_name'] ?? '',
    'patient_email' => $prescription['patient_email'] ?? '',
    'status' => $prescription['status'] ?? 'pending',
    'prescription_items' => $prescription['prescription_items'] ?? '',
    'prescription_file' => $prescription['prescription_file'] ?? '' // Use prescription_file instead of image
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'status' => $_POST['status'] ?? 'pending',
        'prescription_items' => $_POST['prescription_items'] ?? '' // Ensure this is never null
    ];
    
    // Validation
    if (empty(trim($formData['prescription_items']))) {
        $errors['prescription_items'] = 'Prescription items are required';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                UPDATE prescriptions 
                SET status = ?, prescription_items = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([
                $formData['status'],
                $formData['prescription_items'],
                $prescription_id
            ]);
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Prescription updated successfully!'
            ];
            header("Location: list.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

//include 'C:/xampp/htdocs/ecommerce website/admin/includes/admin_header.php';
include __DIR__ . '/../includes/admin_header.php';


?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Edit Prescription #<?= htmlspecialchars((string)($prescription['id'] ?? '')) ?>
            </h6>
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
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Patient Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($formData['patient_name']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Patient Email</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($formData['patient_email']) ?>" readonly>
                    </div>
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
                
                <div class="mb-3">
                    <label class="form-label">Prescription File</label>
                    <?php if ($formData['prescription_file']): ?>
                        <a href="download.php?file=<?= urlencode($formData['prescription_file']) ?>" target="_blank">Download Prescription File</a>
                    <?php else: ?>
                        <p>No file uploaded.</p>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Prescription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
//require_once 'C:/xampp/htdocs/ecommerce website/admin/includes/admin_footer.php'; 
require_once __DIR__ . '/../includes/admin_footer.php';


?>