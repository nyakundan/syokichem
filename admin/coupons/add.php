<?php
declare(strict_types=1);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session at the very beginning
session_start();

// Required files
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Initialize variables
$errors = [];
$coupon = [
    'code' => '',
    'discount_type' => 'percentage',
    'discount_value' => '',
    'min_order_amount' => '',
    'max_discount_amount' => '',
    'start_date' => date('Y-m-d'),
    'expiry_date' => date('Y-m-d', strtotime('+1 month')),
    'usage_limit' => '',
    'is_active' => true
];

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Invalid CSRF token. Please refresh the page and try again.'
        ];
    } else {
        // Process form data
        $coupon['code'] = strtoupper(sanitize($_POST['code'] ?? ''));
        $coupon['discount_type'] = in_array($_POST['discount_type'] ?? '', ['percentage', 'fixed']) 
            ? $_POST['discount_type'] 
            : 'percentage';
        $coupon['discount_value'] = (float)($_POST['discount_value'] ?? 0);
        $coupon['min_order_amount'] = (float)($_POST['min_order_amount'] ?? 0);
        $coupon['max_discount_amount'] = !empty($_POST['max_discount_amount']) 
            ? (float)$_POST['max_discount_amount'] 
            : null;
        $coupon['start_date'] = $_POST['start_date'] ?? '';
        $coupon['expiry_date'] = $_POST['expiry_date'] ?? '';
        $coupon['usage_limit'] = !empty($_POST['usage_limit']) 
            ? (int)$_POST['usage_limit'] 
            : null;
        $coupon['is_active'] = isset($_POST['is_active']);

        // Validation
        if (empty($coupon['code'])) {
            $errors['code'] = 'Coupon code is required';
        } elseif (strlen($coupon['code']) < 4) {
            $errors['code'] = 'Coupon code must be at least 4 characters';
        }

        if ($coupon['discount_value'] <= 0) {
            $errors['discount_value'] = 'Discount value must be greater than 0';
        }

        if ($coupon['discount_type'] === 'percentage' && $coupon['discount_value'] > 100) {
            $errors['discount_value'] = 'Percentage discount cannot be more than 100%';
        }

        if (!empty($coupon['min_order_amount']) && $coupon['min_order_amount'] < 0) {
            $errors['min_order_amount'] = 'Minimum order amount cannot be negative';
        }

        if (empty($errors)) {
            try {
                // Start transaction only if not already in one
                $inTransaction = $conn->inTransaction();
                if (!$inTransaction) {
                    $conn->beginTransaction();
                }

                // Check if coupon code already exists
                $stmt = $conn->prepare("SELECT id FROM coupons WHERE code = ?");
                $stmt->execute([$coupon['code']]);
                if ($stmt->fetch()) {
                    $errors['code'] = 'This coupon code already exists';
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }
                } else {
                    // Insert coupon
                    $stmt = $conn->prepare("
                        INSERT INTO coupons 
                        (code, discount_type, discount_value, min_order_amount, max_discount_amount, 
                         start_date, expiry_date, usage_limit, is_active, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $coupon['code'],
                        $coupon['discount_type'],
                        $coupon['discount_value'],
                        $coupon['min_order_amount'],
                        $coupon['max_discount_amount'],
                        $coupon['start_date'],
                        $coupon['expiry_date'],
                        $coupon['usage_limit'],
                        $coupon['is_active'],
                        isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null
                    ]);

                    if (!$inTransaction) {
                        $conn->commit();
                    }
                    
                    // Basic admin log
                    $logStmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, ip_address) VALUES (?, ?, ?)");
                    $logStmt->execute([
                        isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null,
                        'Created coupon: ' . $coupon['code'],
                        $_SERVER['REMOTE_ADDR']
                    ]);
                    
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => 'Coupon created successfully'
                    ];
                    header("Location: list.php");
                    exit();
                }
            } catch (PDOException $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $errors[] = 'Database error: ' . $e->getMessage();
                error_log("Coupon creation failed: " . $e->getMessage());
            }
        }
    }
}

// Include header
require_once __DIR__ . '/../includes/admin_header.php';

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="mb-4">Create New Coupon</h1>
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_message']['type']) ?>">
                    <?= htmlspecialchars($_SESSION['flash_message']['message']) ?>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <div class="card mb-4">
                    <div class="card-header">Coupon Details</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="code" class="form-label">Coupon Code *</label>
                                <input type="text" class="form-control" id="code" name="code" 
                                       value="<?= htmlspecialchars($coupon['code']) ?>" required>
                                <small class="text-muted">Must be at least 4 characters</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="discount_type" class="form-label">Discount Type *</label>
                                <select class="form-select" id="discount_type" name="discount_type" required>
                                    <option value="percentage" <?= $coupon['discount_type'] === 'percentage' ? 'selected' : '' ?>>Percentage</option>
                                    <option value="fixed" <?= $coupon['discount_type'] === 'fixed' ? 'selected' : '' ?>>Fixed Amount</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="discount_value" class="form-label">Discount Value *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="discount_value" name="discount_value" 
                                           value="<?= htmlspecialchars($coupon['discount_value']) ?>" step="0.01" min="0.01" required>
                                    <span class="input-group-text">
                                        <?= $coupon['discount_type'] === 'percentage' ? '%' : '$' ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="min_order_amount" class="form-label">Minimum Order Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" 
                                           value="<?= htmlspecialchars($coupon['min_order_amount']) ?>" step="0.01" min="0">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3" id="max-discount-container" style="<?= $coupon['discount_type'] === 'percentage' ? '' : 'display: none;' ?>">
                                <label for="max_discount_amount" class="form-label">Maximum Discount (for % only)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="max_discount_amount" name="max_discount_amount" 
                                           value="<?= htmlspecialchars($coupon['max_discount_amount'] ?? '') ?>" step="0.01" min="0">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="usage_limit" class="form-label">Usage Limit (leave blank for unlimited)</label>
                                <input type="number" class="form-control" id="usage_limit" name="usage_limit" 
                                       value="<?= htmlspecialchars($coupon['usage_limit'] ?? '') ?>" min="1">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date *</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?= htmlspecialchars($coupon['start_date']) ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="expiry_date" class="form-label">Expiry Date *</label>
                                <input type="date" class="form-control" id="expiry_date" name="expiry_date" 
                                       value="<?= htmlspecialchars($coupon['expiry_date']) ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?= $coupon['is_active'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Update discount value suffix when type changes
document.getElementById('discount_type').addEventListener('change', function() {
    const suffix = this.value === 'percentage' ? '%' : '$';
    document.querySelector('#discount_value + .input-group-text').textContent = suffix;
    
    // Toggle max discount field visibility
    document.getElementById('max-discount-container').style.display = 
        this.value === 'percentage' ? 'block' : 'none';
});

// Initialize display
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('discount_type').dispatchEvent(new Event('change'));
});
</script>

<?php 
// Include footer
require __DIR__ . '/../includes/admin_footer.php';
?>