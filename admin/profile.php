<?php
declare(strict_types=1);

$page_title = "Admin Profile";
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../components/connect.php';
//require __DIR__ . '/includes/functions.php';

// Verify admin is logged in
if (!isLoggedIn()) {
    redirectWithMessage('login.php', 'error', 'Please login first');
}

// Get current admin data
$admin = getCurrentAdmin();
if (!$admin) {
    redirectWithMessage('login.php', 'error', 'Invalid admin session');
}

$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        redirectWithMessage('profile.php', 'error', 'Invalid CSRF token');
    }

    // Sanitize inputs
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate name
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    } elseif (strlen($name) > 50) {
        $errors['name'] = 'Name cannot exceed 50 characters';
    }

    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } elseif (strlen($email) > 100) {
        $errors['email'] = 'Email cannot exceed 100 characters';
    } elseif ($email !== $admin['email']) {
        // Check if email is changed and exists
        $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
        $stmt->execute([$email, $admin['id']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email already in use by another admin';
        }
    }

    // Password change validation
    $password_changed = false;
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors['current_password'] = 'Current password is required to change password';
        } elseif (!password_verify($current_password, $admin['password'])) {
            $errors['current_password'] = 'Current password is incorrect';
        }

        if (empty($new_password)) {
            $errors['new_password'] = 'New password is required';
        } elseif (strlen($new_password) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters';
        }

        if ($new_password !== $confirm_password) {
            $errors['confirm_password'] = 'New passwords do not match';
        }

        $password_changed = empty($errors['current_password']) && 
                          empty($errors['new_password']) && 
                          empty($errors['confirm_password']);
    }

    // Update profile if no errors
    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Prepare base update query
            $query = "UPDATE admins SET name = ?, email = ?";
            $params = [$name, $email];

            // Add password to update if changed
            if ($password_changed) {
                $query .= ", password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            $query .= " WHERE id = ?";
            $params[] = $admin['id'];

            $stmt = $conn->prepare($query);
            $stmt->execute($params);

            // Log the profile update
            logAdminAction(
                $admin['id'],
                'update_profile',
                $password_changed 
                    ? 'Updated profile and changed password' 
                    : 'Updated profile information'
            );

            $conn->commit();
            $success = true;

            // Refresh admin data
            $admin = getCurrentAdmin(true); // Force refresh

            // Show success message
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Profile updated successfully'
            ];

        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
            error_log("Profile update failed: " . $e->getMessage());
        }
    }
}

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Admin Profile</h6>
                    <a href="dashboard.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                <div class="card-body">
                    <?php displayFlashMessages(); ?>

                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                       id="name" name="name" value="<?= htmlspecialchars($admin['name'] ?? '') ?>" 
                                       required maxlength="50">
                                <?php if (isset($errors['name'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                       id="email" name="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" 
                                       required maxlength="100">
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">Change Password</h5>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>" 
                                       id="current_password" name="current_password">
                                <?php if (isset($errors['current_password'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['current_password']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>" 
                                       id="new_password" name="new_password">
                                <?php if (isset($errors['new_password'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['new_password']) ?></div>
                                <?php endif; ?>
                                <small class="text-muted">At least 8 characters</small>
                            </div>
                            <div class="col-md-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                                       id="confirm_password" name="confirm_password">
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>

<script>
// Client-side validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });
    
    // Toggle password visibility
    const togglePassword = (inputId) => {
        const input = document.getElementById(inputId);
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
    };
    
    // Add eye icons to password fields
    ['current_password', 'new_password', 'confirm_password'].forEach(id => {
        const inputGroup = document.createElement('div');
        inputGroup.className = 'input-group';
        
        const input = document.getElementById(id);
        const parent = input.parentElement;
        
        input.className = input.className.replace('form-control', 'form-control pe-5');
        parent.appendChild(input);
        
        const eyeIcon = document.createElement('span');
        eyeIcon.className = 'position-absolute end-0 top-50 translate-middle-y me-3';
        eyeIcon.style.cursor = 'pointer';
        eyeIcon.innerHTML = '<i class="fas fa-eye"></i>';
        eyeIcon.onclick = () => togglePassword(id);
        
        parent.appendChild(eyeIcon);
        parent.style.position = 'relative';
    });
});
</script>