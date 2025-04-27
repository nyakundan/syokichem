<?php
declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and check authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/connect.php';

// Validate product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid product ID';
    header('Location: list.php');
    exit();
}
$productId = (int)$_GET['id'];

// Fetch all categories for dropdown
$categories = $conn->query("SELECT id, name, parent_id FROM product_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

function buildCategoryTree($elements, $parentId = null) {
    $branch = [];
    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $children = buildCategoryTree($elements, $element['id']);
            $element['children'] = $children;
            $branch[] = $element;
        }
    }
    return $branch;
}

function getLeafCategories($categories, &$leaf = []) {
    foreach ($categories as $cat) {
        if (empty($cat['children'])) {
            $leaf[] = $cat;
        } else {
            getLeafCategories($cat['children'], $leaf);
        }
    }
    return $leaf;
}

$category_tree = buildCategoryTree($categories);
$leaf_categories = getLeafCategories($category_tree);

// Get dropdown options
$suppliers = $conn->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll();

// Fetch product to edit
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    $_SESSION['error'] = 'Product not found';
    header('Location: list.php');
    exit();
}

// Initialize form data with existing product values
$formData = [
    'name' => $product['name'] ?? '',
    'slug' => $product['slug'] ?? '',
    'category_id' => $product['category_id'] ?? '',
    'category' => $product['category'] ?? '',
    'price' => $product['price'] ?? '',
    'status' => $product['status'] ?? 'active',
    'description' => $product['description'] ?? '',
    'ingredients' => $product['ingredients'] ?? '',
    'dosage' => $product['dosage'] ?? '',
    'manufacturer' => $product['manufacturer'] ?? '',
    'supplier_id' => $product['supplier_id'] ?? '',
    'requires_prescription' => $product['requires_prescription'] ?? 0,
    'max_quantity' => $product['max_quantity'] ?? 5,
    'sales' => $product['sales'] ?? 0,
    'stock' => $product['stock'] ?? '',
    'how_to_use' => $product['how_to_use'] ?? '',
    'precautions' => $product['precautions'] ?? '',
    'image_01' => $product['image_01'] ?? '',
    'image_02' => $product['image_02'] ?? '',
    'image_03' => $product['image_03'] ?? '',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'slug' => trim($_POST['slug'] ?? ''),
        'category_id' => $_POST['category_id'] ?? null,
        'category' => trim($_POST['category'] ?? ''),
        'price' => (float)($_POST['price'] ?? 0),
        'status' => $_POST['status'] ?? 'active',
        'description' => trim($_POST['description'] ?? ''),
        'ingredients' => trim($_POST['ingredients'] ?? ''),
        'dosage' => trim($_POST['dosage'] ?? ''),
        'manufacturer' => trim($_POST['manufacturer'] ?? ''),
        'supplier_id' => $_POST['supplier_id'] ?? null,
        'requires_prescription' => isset($_POST['requires_prescription']) ? 1 : 0,
        'max_quantity' => (int)($_POST['max_quantity'] ?? 5),
        'sales' => (int)($_POST['sales'] ?? 0),
        'stock' => (int)($_POST['stock'] ?? 0),
        'how_to_use' => trim($_POST['how_to_use'] ?? ''),
        'precautions' => trim($_POST['precautions'] ?? ''),
        'image_01' => $product['image_01'] ?? '',
        'image_02' => $product['image_02'] ?? '',
        'image_03' => $product['image_03'] ?? '',
    ];

    // Validate required fields
    if (empty($formData['name'])) {
        $errors[] = 'Product name is required';
    }
    if ($formData['price'] <= 0) {
        $errors[] = 'Price must be a positive number';
    }
    if ($formData['stock'] < 0) {
        $errors[] = 'Stock quantity cannot be negative';
    }

    // Process if no errors
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare("UPDATE products SET name=?, slug=?, category_id=?, category=?, price=?, status=?, description=?, ingredients=?, dosage=?, manufacturer=?, supplier_id=?, requires_prescription=?, max_quantity=?, sales=?, stock=?, how_to_use=?, precautions=? WHERE id=?");
            $stmt->execute([
                $formData['name'],
                $formData['slug'],
                $formData['category_id'],
                $formData['category'],
                $formData['price'],
                $formData['status'],
                $formData['description'],
                $formData['ingredients'],
                $formData['dosage'],
                $formData['manufacturer'],
                $formData['supplier_id'],
                $formData['requires_prescription'],
                $formData['max_quantity'],
                $formData['sales'],
                $formData['stock'],
                $formData['how_to_use'],
                $formData['precautions'],
                $productId
            ]);
            $conn->commit();
            $_SESSION['success'] = 'Product updated successfully!';
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Database Error: " . $e->getMessage());
            $errors[] = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("General Error: " . $e->getMessage());
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .required:after { content: " *"; color: red; }
        .image-preview { max-width: 200px; max-height: 200px; margin-top: 1rem; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/admin_header.php'; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Edit Product</h1>
        <a href="list.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Products</a>
    </div>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p class="mb-1"><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
    // Debug output for formData and suppliers
    echo '<pre style="background:#fff;color:#000;z-index:9999;position:relative;">';
    var_dump($formData);
    echo "\n";
    if (isset($suppliers)) var_dump($suppliers);
    echo '</pre>';
    ?>
    <form method="POST" class="form-container">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="name" class="form-label required">Product Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($formData['name']) ?>" required>
            </div>
            <div class="form-group col-md-6">
                <label for="slug" class="form-label">Slug</label>
                <input type="text" class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($formData['slug']) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="price" class="form-label required">Selling Price</label>
                <div class="input-group">
                    <span class="input-group-text">KSh</span>
                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?= htmlspecialchars($formData['price']) ?>" required>
                </div>
            </div>
            <div class="form-group col-md-4">
                <label for="stock" class="form-label required">Current Stock</label>
                <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?= htmlspecialchars($formData['stock']) ?>" required>
            </div>
            <div class="form-group col-md-4">
                <label for="max_quantity" class="form-label">Max Quantity per Order</label>
                <input type="number" class="form-control" id="max_quantity" name="max_quantity" min="1" value="<?= htmlspecialchars($formData['max_quantity']) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="sales" class="form-label">Sales</label>
                <input type="number" class="form-control" id="sales" name="sales" min="0" value="<?= htmlspecialchars((string)$formData['sales']) ?>">
            </div>
            <div class="form-group col-md-6">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="active" <?= $formData['status']==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $formData['status']==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="category_id" class="form-label required">Category</label>
                <input type="hidden" name="category" value="<?= htmlspecialchars($formData['category']) ?>">
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <option value="10" <?= $formData['category_id']==10?'selected':'' ?>>Test Category</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description"><?= htmlspecialchars($formData['description']) ?></textarea>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="supplier_id" class="form-label">Supplier</label>
                <select class="form-select" id="supplier_id" name="supplier_id">
                    <option value="">Select Supplier</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= $supplier['id'] ?>" <?= $formData['supplier_id'] == $supplier['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($supplier['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="requires_prescription" class="form-label">Requires Prescription</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="requires_prescription" name="requires_prescription" <?= $formData['requires_prescription'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="requires_prescription">Yes</label>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="manufacturer" class="form-label">Manufacturer</label>
                <input type="text" class="form-control" id="manufacturer" name="manufacturer" value="<?= htmlspecialchars($formData['manufacturer']) ?>">
            </div>
            <div class="form-group col-md-6">
                <label for="ingredients" class="form-label">Ingredients</label>
                <textarea class="form-control" id="ingredients" name="ingredients"><?= htmlspecialchars($formData['ingredients']) ?></textarea>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="dosage" class="form-label">Dosage</label>
                <textarea class="form-control" id="dosage" name="dosage"><?= htmlspecialchars($formData['dosage']) ?></textarea>
            </div>
            <div class="form-group col-md-6">
                <label for="how_to_use" class="form-label">How to Use</label>
                <textarea class="form-control" id="how_to_use" name="how_to_use"><?= htmlspecialchars($formData['how_to_use']) ?></textarea>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="precautions" class="form-label">Precautions</label>
                <textarea class="form-control" id="precautions" name="precautions"><?= htmlspecialchars($formData['precautions']) ?></textarea>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="image_01" class="form-label">Image 1</label>
                <input type="file" class="form-control" id="image_01" name="image_01" accept="image/*">
                <div id="imagePreview01" class="image-preview">
                    <?php if (!empty($formData['image_01'])): ?>
                        <img src="../../images/products/<?= htmlspecialchars($formData['image_01']) ?>" class="img-thumbnail" alt="Current Image 1">
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-group col-md-4">
                <label for="image_02" class="form-label">Image 2</label>
                <input type="file" class="form-control" id="image_02" name="image_02" accept="image/*">
                <div id="imagePreview02" class="image-preview">
                    <?php if (!empty($formData['image_02'])): ?>
                        <img src="../../images/products/<?= htmlspecialchars($formData['image_02']) ?>" class="img-thumbnail" alt="Current Image 2">
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-group col-md-4">
                <label for="image_03" class="form-label">Image 3</label>
                <input type="file" class="form-control" id="image_03" name="image_03" accept="image/*">
                <div id="imagePreview03" class="image-preview">
                    <?php if (!empty($formData['image_03'])): ?>
                        <img src="../../images/products/<?= htmlspecialchars($formData['image_03']) ?>" class="img-thumbnail" alt="Current Image 3">
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Update Product
                </button>
            </div>
        </div>
    </form>
    <?php include __DIR__ . '/../includes/admin_footer.php'; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>