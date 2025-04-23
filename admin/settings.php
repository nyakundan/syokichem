<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/components/connect.php';

// Check if user is authorized
//if (!isset($_SESSION['admin_id'])) {
   // $_SESSION['error'] = 'Unauthorized access';
   // header('Location: login.php');
    //exit();
//}

// Set page title
$page_title = "Pharmacy Settings";

// Initialize variables
$errors = [];
$success = false;
$settings = [
    'pharmacy_name' => '',
    'pharmacy_email' => '',
    'pharmacy_phone' => '',
    'pharmacy_address' => '',
    'delivery_fee' => '0.00',
    'currency' => 'KSh',
    'min_order_amount' => '0.00',
    'working_hours' => '',
    'facebook_url' => '',
    'twitter_url' => '',
    'instagram_url' => '',
    'whatsapp_number' => '',
    'about_us' => '',
    'privacy_policy' => '',
    'terms_conditions' => ''
];

// Load current settings
try {
    $stmt = $conn->query("SELECT * FROM settings LIMIT 1");
    $currentSettings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentSettings) {
        $settings = array_merge($settings, $currentSettings);
    }
} catch (PDOException $e) {
    $errors[] = "Failed to load settings: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $settings['pharmacy_name'] = trim($_POST['pharmacy_name'] ?? '');
    $settings['pharmacy_email'] = trim($_POST['pharmacy_email'] ?? '');
    $settings['pharmacy_phone'] = trim($_POST['pharmacy_phone'] ?? '');
    $settings['pharmacy_address'] = trim($_POST['pharmacy_address'] ?? '');
    $settings['delivery_fee'] = number_format((float)($_POST['delivery_fee'] ?? 0), 2, '.', '');
    $settings['min_order_amount'] = number_format((float)($_POST['min_order_amount'] ?? 0), 2, '.', '');
    $settings['working_hours'] = trim($_POST['working_hours'] ?? '');
    $settings['facebook_url'] = trim($_POST['facebook_url'] ?? '');
    $settings['twitter_url'] = trim($_POST['twitter_url'] ?? '');
    $settings['instagram_url'] = trim($_POST['instagram_url'] ?? '');
    $settings['whatsapp_number'] = trim($_POST['whatsapp_number'] ?? '');
    $settings['about_us'] = trim($_POST['about_us'] ?? '');
    $settings['privacy_policy'] = trim($_POST['privacy_policy'] ?? '');
    $settings['terms_conditions'] = trim($_POST['terms_conditions'] ?? '');

    // Validation
    if (empty($settings['pharmacy_name'])) {
        $errors['pharmacy_name'] = 'Pharmacy name is required';
    }
    
    if (empty($settings['pharmacy_email'])) {
        $errors['pharmacy_email'] = 'Pharmacy email is required';
    } elseif (!filter_var($settings['pharmacy_email'], FILTER_VALIDATE_EMAIL)) {
        $errors['pharmacy_email'] = 'Invalid email format';
    }
    
    if (empty($settings['pharmacy_phone'])) {
        $errors['pharmacy_phone'] = 'Phone number is required';
    }
    
    if (empty($settings['pharmacy_address'])) {
        $errors['pharmacy_address'] = 'Address is required';
    }
    
    if (!is_numeric($settings['delivery_fee']) || $settings['delivery_fee'] < 0) {
        $errors['delivery_fee'] = 'Delivery fee must be a positive number';
    }

    if (!is_numeric($settings['min_order_amount']) || $settings['min_order_amount'] < 0) {
        $errors['min_order_amount'] = 'Minimum order amount must be a positive number';
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Check if settings exist
            $checkStmt = $conn->query("SELECT COUNT(*) FROM settings");
            $exists = $checkStmt->fetchColumn() > 0;
            
            if ($exists) {
                // Update existing settings
                $stmt = $conn->prepare("
                    UPDATE settings 
                    SET pharmacy_name = ?, 
                        pharmacy_email = ?, 
                        pharmacy_phone = ?, 
                        pharmacy_address = ?, 
                        delivery_fee = ?,
                        min_order_amount = ?,
                        working_hours = ?,
                        facebook_url = ?,
                        twitter_url = ?,
                        instagram_url = ?,
                        whatsapp_number = ?,
                        about_us = ?,
                        privacy_policy = ?,
                        terms_conditions = ?,
                        updated_at = NOW()
                ");
                $stmt->execute([
                    $settings['pharmacy_name'],
                    $settings['pharmacy_email'],
                    $settings['pharmacy_phone'],
                    $settings['pharmacy_address'],
                    $settings['delivery_fee'],
                    $settings['min_order_amount'],
                    $settings['working_hours'],
                    $settings['facebook_url'],
                    $settings['twitter_url'],
                    $settings['instagram_url'],
                    $settings['whatsapp_number'],
                    $settings['about_us'],
                    $settings['privacy_policy'],
                    $settings['terms_conditions']
                ]);
            } else {
                // Insert new settings
                $stmt = $conn->prepare("
                    INSERT INTO settings 
                    (pharmacy_name, pharmacy_email, pharmacy_phone, pharmacy_address, 
                     delivery_fee, min_order_amount, working_hours, facebook_url, 
                     twitter_url, instagram_url, whatsapp_number, about_us, 
                     privacy_policy, terms_conditions)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $settings['pharmacy_name'],
                    $settings['pharmacy_email'],
                    $settings['pharmacy_phone'],
                    $settings['pharmacy_address'],
                    $settings['delivery_fee'],
                    $settings['min_order_amount'],
                    $settings['working_hours'],
                    $settings['facebook_url'],
                    $settings['twitter_url'],
                    $settings['instagram_url'],
                    $settings['whatsapp_number'],
                    $settings['about_us'],
                    $settings['privacy_policy'],
                    $settings['terms_conditions']
                ]);
            }
            
            $conn->commit();
            $success = true;
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Settings saved successfully!'
            ];
            
            // Refresh the page to show updated settings
            header("Location: settings.php");
            exit;
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Pharmacy Settings</h6>
        </div>
        
        <div class="card-body">
            <?php displayFlashMessages(); ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pharmacy_name">Pharmacy Name *</label>
                            <input type="text" class="form-control <?= isset($errors['pharmacy_name']) ? 'is-invalid' : '' ?>" 
                                   id="pharmacy_name" name="pharmacy_name" 
                                   value="<?= htmlspecialchars($settings['pharmacy_name']) ?>" required>
                            <?php if (isset($errors['pharmacy_name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['pharmacy_name']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="pharmacy_email">Pharmacy Email *</label>
                            <input type="email" class="form-control <?= isset($errors['pharmacy_email']) ? 'is-invalid' : '' ?>" 
                                   id="pharmacy_email" name="pharmacy_email" 
                                   value="<?= htmlspecialchars($settings['pharmacy_email']) ?>" required>
                            <?php if (isset($errors['pharmacy_email'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['pharmacy_email']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="pharmacy_phone">Phone Number *</label>
                            <input type="tel" class="form-control <?= isset($errors['pharmacy_phone']) ? 'is-invalid' : '' ?>" 
                                   id="pharmacy_phone" name="pharmacy_phone" 
                                   value="<?= htmlspecialchars($settings['pharmacy_phone']) ?>" required>
                            <?php if (isset($errors['pharmacy_phone'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['pharmacy_phone']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="whatsapp_number">WhatsApp Number</label>
                            <input type="tel" class="form-control" 
                                   id="whatsapp_number" name="whatsapp_number" 
                                   value="<?= htmlspecialchars($settings['whatsapp_number']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="working_hours">Working Hours</label>
                            <input type="text" class="form-control" 
                                   id="working_hours" name="working_hours" 
                                   value="<?= htmlspecialchars($settings['working_hours']) ?>"
                                   placeholder="e.g., Mon-Fri: 8:00 AM - 6:00 PM, Sat: 9:00 AM - 4:00 PM">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pharmacy_address">Address *</label>
                            <textarea class="form-control <?= isset($errors['pharmacy_address']) ? 'is-invalid' : '' ?>" 
                                      id="pharmacy_address" name="pharmacy_address" 
                                      rows="3" required><?= htmlspecialchars($settings['pharmacy_address']) ?></textarea>
                            <?php if (isset($errors['pharmacy_address'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['pharmacy_address']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="delivery_fee">Delivery Fee (KSh)</label>
                            <input type="number" class="form-control <?= isset($errors['delivery_fee']) ? 'is-invalid' : '' ?>" 
                                   id="delivery_fee" name="delivery_fee" 
                                   value="<?= htmlspecialchars($settings['delivery_fee']) ?>" min="0" step="0.01" required>
                            <?php if (isset($errors['delivery_fee'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['delivery_fee']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="min_order_amount">Minimum Order Amount (KSh)</label>
                            <input type="number" class="form-control <?= isset($errors['min_order_amount']) ? 'is-invalid' : '' ?>" 
                                   id="min_order_amount" name="min_order_amount" 
                                   value="<?= htmlspecialchars($settings['min_order_amount']) ?>" min="0" step="0.01" required>
                            <?php if (isset($errors['min_order_amount'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['min_order_amount']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <h5 class="mb-3">Social Media Links</h5>
                        <div class="form-group">
                            <label for="facebook_url">Facebook URL</label>
                            <input type="url" class="form-control" 
                                   id="facebook_url" name="facebook_url" 
                                   value="<?= htmlspecialchars($settings['facebook_url']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="twitter_url">Twitter URL</label>
                            <input type="url" class="form-control" 
                                   id="twitter_url" name="twitter_url" 
                                   value="<?= htmlspecialchars($settings['twitter_url']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="instagram_url">Instagram URL</label>
                            <input type="url" class="form-control" 
                                   id="instagram_url" name="instagram_url" 
                                   value="<?= htmlspecialchars($settings['instagram_url']) ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="mb-3">Content</h5>
                        <div class="form-group">
                            <label for="about_us">About Us</label>
                            <textarea class="form-control" id="about_us" name="about_us" rows="3"><?= htmlspecialchars($settings['about_us']) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="privacy_policy">Privacy Policy</label>
                            <textarea class="form-control" id="privacy_policy" name="privacy_policy" rows="3"><?= htmlspecialchars($settings['privacy_policy']) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="terms_conditions">Terms & Conditions</label>
                            <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="3"><?= htmlspecialchars($settings['terms_conditions']) ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="fas fa-save mr-2"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Check required fields
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // Validate email format
        const emailField = document.getElementById('pharmacy_email');
        if (emailField && !emailField.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            emailField.classList.add('is-invalid');
            isValid = false;
        }
        
        // Validate URLs
        const urlFields = form.querySelectorAll('input[type="url"]');
        urlFields.forEach(field => {
            if (field.value && !field.value.match(/^https?:\/\/.+/)) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields correctly.');
        }
    });
});
</script>