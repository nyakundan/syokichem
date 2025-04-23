<?php
declare(strict_types=1);

$page_title = "Edit Admin";
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

if (!isset($_GET['id'])) {
    header("Location: list.php?error=Admin ID not provided");
    exit;
}

$adminId = (int)$_GET['id'];

// Prevent editing own account through this interface
if ($adminId === $_SESSION['admin']['id']) {
    header("Location: list.php?error=Please use your profile page to edit your own account");
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, name, email, is_active FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        header("Location: list.php?error=Admin not found");
        exit;
    }
} catch (PDOException $e) {
    header("Location: list.php?error=Database error");
    exit;
}

$errors = [];
$formData = [
    'name' => $admin['name'],
    'email' => $admin['email']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? '')
    ];
    
    // Validation
    if (empty($formData['name'])) {
        $errors['name'] = 'Name is required';
    }
    
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        // Check if email exists for another admin
        $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
        $stmt->execute([$formData['email'], $adminId]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email already exists';
        }
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            $stmt = $conn->prepare("
                UPDATE admins 
                SET name = ?, email = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([
                $formData['name'],
                $formData['email'],
                $adminId
            ]);
            
            $conn->commit();
            
            $_SESSION['success_message'] = 'Admin account updated successfully';
            header("Location: list.php");
            exit;
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
            error_log("Admin update failed: " . $e->getMessage());
        }
    }
}

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Edit Admin</h6>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <form method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                               id="name" name="name" value="<?= htmlspecialchars($formData['name']) ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                               id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/admin_footer.php';
?>