<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Get offer ID from URL
$offer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get the offer data
$select_offer = $conn->prepare("SELECT * FROM special_offers WHERE id = ?");
$select_offer->execute([$offer_id]);
$offer = $select_offer->fetch(PDO::FETCH_ASSOC);

if (!$offer) {
    $_SESSION['error'] = 'Offer not found';
    header('Location: list.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $product_id = (int)($_POST['product_id'] ?? 0);
        $old_price = (float)($_POST['old_price'] ?? 0);
        $new_price = (float)($_POST['new_price'] ?? 0);
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validate inputs
        $errors = [];
        if ($product_id <= 0) {
            $errors[] = 'Please select a valid product';
        }
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
            $_SESSION['old_data'] = $_POST;
            header("Location: edit.php?id=$offer_id");
            exit();
        }

        // Update offer
        $update = $conn->prepare("
            UPDATE special_offers 
            SET product_id = ?, old_price = ?, new_price = ?, 
                start_date = ?, end_date = ?, is_active = ?
            WHERE id = ?
        ");
        
        if ($update->execute([$product_id, $old_price, $new_price, $start_date, $end_date, $is_active, $offer_id])) {
            header("Location: list.php?success=1");
            exit();
        } else {
            throw new Exception("Failed to update offer");
        }
    } catch (PDOException $e) {
        error_log("Special offer update error: " . $e->getMessage());
        $_SESSION['errors'] = ['An error occurred while updating the offer'];
        header("Location: edit.php?id=$offer_id");
        exit();
    }
}

// Get products for dropdown
try {
    $stmt = $conn->prepare("SELECT id, name FROM products WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Products fetch error: " . $e->getMessage());
    $products = [];
}

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Edit Special Offer</h4>
                </div>
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
                        <div class="mb-3">
                            <label for="product_id" class="form-label">Product</label>
                            <select class="form-control" id="product_id" name="product_id" required>
                                <option value="">Select a product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= $product['id'] ?>"
                                            <?= ($offer['product_id'] ?? '') == $product['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($product['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="old_price" class="form-label">Original Price (KES)</label>
                            <input type="number" class="form-control" id="old_price" name="old_price" 
                                   step="0.01" min="0" 
                                   value="<?= htmlspecialchars($_POST['old_price'] ?? $offer['old_price']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_price" class="form-label">Discounted Price (KES)</label>
                            <input type="number" class="form-control" id="new_price" name="new_price" 
                                   step="0.01" min="0" 
                                   value="<?= htmlspecialchars($_POST['new_price'] ?? $offer['new_price']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" 
                                   value="<?= htmlspecialchars($_POST['start_date'] ?? date('Y-m-d\TH:i', strtotime($offer['start_date']))) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" 
                                   value="<?= htmlspecialchars($_POST['end_date'] ?? date('Y-m-d\TH:i', strtotime($offer['end_date']))) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                       value="1" <?= $offer['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Offer</button>
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
