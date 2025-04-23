<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Get product ID from URL
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Get product details
if ($product_id > 0) {
    $select_product = $conn->prepare("
        SELECT p.*, pc.name as category_name 
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE p.id = ? AND p.status = 'active'
    ");
    $select_product->execute([$product_id]);
    $product = $select_product->fetch(PDO::FETCH_ASSOC);
}

// If no product selected, show selection page
if (!$product) {
    header("Location: select_product.php");
    exit();
}

// Get existing special offer for this product (if any)
$existing_offer = $conn->prepare("
    SELECT * FROM special_offers 
    WHERE product_id = ? AND is_active = 1
    ORDER BY created_at DESC 
    LIMIT 1
");
$existing_offer->execute([$product_id]);
$existing = $existing_offer->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $old_price = (float)($_POST['old_price'] ?? $product['price']);
        $new_price = (float)($_POST['new_price'] ?? 0);
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $is_active = $_POST['is_active'] ?? '1';

        // Validate inputs
        $errors = [];
        if ($old_price <= 0) {
            $errors[] = 'Original price must be greater than 0';
        }
        if ($new_price <= 0 || $new_price >= $old_price) {
            $errors[] = 'Discounted price must be between 0 and the original price';
        }
        if (strtotime($end_date) <= strtotime($start_date)) {
            $errors[] = 'End date must be after start date';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header("Location: add.php?product_id=$product_id");
            exit();
        }

        // Insert offer
        $insert = $conn->prepare("
            INSERT INTO special_offers (product_id, old_price, new_price, start_date, end_date, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($insert->execute([$product_id, $old_price, $new_price, $start_date, $end_date, $is_active, $_SESSION['admin_id']])) {
            header("Location: list.php?success=1");
            exit();
        } else {
            throw new Exception("Failed to insert offer");
        }
    } catch (PDOException $e) {
        error_log("Special offer add error: " . $e->getMessage());
        $_SESSION['errors'] = ['An error occurred while adding the offer'];
        header("Location: add.php?product_id=$product_id");
        exit();
    }
}

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Create Special Offer for <?= htmlspecialchars($product['name']) ?></h4>
                    <div>
                        <a href="select_product.php" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Select Different Product
                        </a>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-list me-2"></i>Back to Offers List
                        </a>
                    </div>
                </div>
                
                <?php if ($existing): ?>
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        This product already has an active offer:
                        <ul class="mb-0">
                            <li>Original Price: KES <?= number_format((float)$existing['old_price'], 2) ?></li>
                            <li>Discounted Price: KES <?= number_format((float)$existing['new_price'], 2) ?></li>
                            <li>Discount: <?= number_format((float)(($existing['old_price'] - $existing['new_price']) / $existing['old_price']) * 100, 2) ?>%</li>
                            <li>Start Date: <?= date('Y-m-d H:i', strtotime($existing['start_date'])) ?></li>
                            <li>End Date: <?= date('Y-m-d H:i', strtotime($existing['end_date'])) ?></li>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card-body">
                    <?php if (isset($_SESSION['errors'])): ?>
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endforeach; ?>
                        <?php unset($_SESSION['errors']); ?>
                    <?php endif; ?>
                    
                    <form action="" method="post">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <img src="../images/<?= htmlspecialchars($product['image_01']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         class="img-thumbnail" 
                                         style="width: 100%; height: 300px; object-fit: cover;">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Product Name</label>
                                    <input type="text" class="form-control" 
                                           value="<?= htmlspecialchars($product['name']) ?>" 
                                           readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <input type="text" class="form-control" 
                                           value="<?= htmlspecialchars($product['category_name']) ?>" 
                                           readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Current Price (KES)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">KES</span>
                                        <input type="number" 
                                               class="form-control" 
                                               value="<?= number_format((float)$product['price'], 2) ?>" 
                                               readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="old_price" class="form-label">Offer Price (KES)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">KES</span>
                                        <input type="number" 
                                               class="form-control" 
                                               id="old_price" 
                                               name="old_price" 
                                               step="0.01" 
                                               min="0" 
                                               value="<?= number_format((float)($_POST['old_price'] ?? $product['price']), 2) ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_price" class="form-label">Discounted Price (KES)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">KES</span>
                                        <input type="number" 
                                               class="form-control" 
                                               id="new_price" 
                                               name="new_price" 
                                               step="0.01" 
                                               min="0" 
                                               value="<?= number_format((float)($_POST['new_price'] ?? 0), 2) ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="datetime-local" 
                                           class="form-control" 
                                           id="start_date" 
                                           name="start_date" 
                                           value="<?= htmlspecialchars($_POST['start_date'] ?? date('Y-m-d\TH:i')) ?>" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="datetime-local" 
                                           class="form-control" 
                                           id="end_date" 
                                           name="end_date" 
                                           value="<?= htmlspecialchars($_POST['end_date'] ?? date('Y-m-d\TH:i', strtotime('+1 week'))) ?>" 
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="is_active" class="form-label">Status</label>
                            <select class="form-control" id="is_active" name="is_active">
                                <option value="1" <?= ($_POST['is_active'] ?? '1') === '1' ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= ($_POST['is_active'] ?? '1') === '0' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Offer
                            </button>
                            <a href="select_product.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
