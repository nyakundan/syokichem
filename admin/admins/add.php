<?php
declare(strict_types=1);

// Set page title
$page_title = "Add New Admin";

// Include required files
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Initialize variables
$errors = [];
$formData = [
    'name' => '',
    'email' => '',
    'password' => '',
    'password_confirm' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        redirectWithMessage('add.php', 'error', 'Invalid CSRF token');
    }
    
    // Sanitize and store form data
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? ''
    ];
    
    // Validate name
    if (empty($formData['name'])) {
        $errors['name'] = 'Name is required';
    } elseif (strlen($formData['name']) > 20) {
        $errors['name'] = 'Name cannot exceed 20 characters';
    }
    
    // Validate email
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } elseif (strlen($formData['email']) > 100) {
        $errors['email'] = 'Email cannot exceed 100 characters';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->execute([$formData['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email already exists';
        }
    }
    
    // Validate password
    if (empty($formData['password'])) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($formData['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    } elseif ($formData['password'] !== $formData['password_confirm']) {
        $errors['password_confirm'] = 'Passwords do not match';
    }
    
    // If no errors, create admin
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Hash password
            $hashedPassword = password_hash($formData['password'], PASSWORD_DEFAULT);
            
            // Insert admin
            $stmt = $conn->prepare("
                INSERT INTO admins 
                (name, email, password, status, created_at) 
                VALUES (?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([
                $formData['name'],
                $formData['email'],
                $hashedPassword
            ]);
            
            // Get new admin ID
            $adminId = $conn->lastInsertId();
            
            // Log the action
            if (function_exists('logAdminAction') && isset($_SESSION['admin_id'])) {
                logAdminAction(
                    $_SESSION['admin_id'],
                    'create_admin',
                    "Created admin: {$formData['email']} (ID: $adminId)"
                );
            }
            
            $conn->commit();
            
            // Redirect on success
            redirectWithMessage('manage.php', 'success', 'Admin account created successfully');
            
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $errors[] = 'Database error: ' . $e->getMessage();
            error_log("Admin creation failed: " . $e->getMessage());
        }
    }
}

// Include header
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= htmlspecialchars($page_title) ?></h6>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                               id="name" name="name" value="<?= htmlspecialchars($formData['name']) ?>" 
                               maxlength="20" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                               id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" 
                               maxlength="100" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                               id="password" name="password" required>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
                        <?php endif; ?>
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="password_confirm" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" 
                               id="password_confirm" name="password_confirm" required>
                        <?php if (isset($errors['password_confirm'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['password_confirm']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="manage.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Include footer
include __DIR__ . '/../includes/admin_footer.php';
?>