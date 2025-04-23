<?php
declare(strict_types=1);

// Start session and check authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/connect.php';

// Initialize variables
$errors = [];
$success = '';
$formData = [
    'name' => '',
    'category_id' => '',
    'product_type' => 'otc',
    'price' => '',
    'description' => '',
    'manufacturer' => '',
    'supplier_id' => '',
    'stock' => '',
    'max_quantity' => '5',
    'requires_prescription' => '0',
    'image_01' => '',
    'ingredients' => '',
    'dosage' => '',
    'image_02' => '',
    'image_03' => '',
    'sales' => 0,
    'status' => 'active',
];

// Get dropdown options
$categories = $conn->query("SELECT id, name FROM product_categories  ORDER BY name")->fetchAll();
$suppliers = $conn->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'category_id' => $_POST['category_id'] ?? null,
        'product_type' => $_POST['product_type'] ?? 'otc',
        'price' => (float)($_POST['price'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
        'manufacturer' => trim($_POST['manufacturer'] ?? ''),
        'supplier_id' => $_POST['supplier_id'] ?? null,
        'stock' => (int)($_POST['stock'] ?? 0),
        'max_quantity' => (int)($_POST['max_quantity'] ?? 5),
        'requires_prescription' => isset($_POST['requires_prescription']) ? 1 : 0,
        'image_01' => '',
        'ingredients' => trim($_POST['ingredients'] ?? ''),
        'dosage' => trim($_POST['dosage'] ?? ''),
        'image_02' => '',
        'image_03' => '',
        'sales' => 0,
        'status' => $_POST['status'] ?? 'active',
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

            // Handle image uploads
            $image_01 = 'default-product.jpg';
            $image_02 = '';
            $image_03 = '';
            if (!empty($_FILES['image_01']['name'])) {
                $uploadDir = __DIR__ . '/../../images/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $fileName = uniqid() . '_' . basename($_FILES['image_01']['name']);
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['image_01']['tmp_name'], $targetFile)) {
                    $image_01 = $fileName;
                } else {
                    throw new Exception('Failed to upload product image');
                }
            }
            if (!empty($_FILES['image_02']['name'])) {
                $uploadDir = __DIR__ . '/../../images/products/';
                $fileName2 = uniqid() . '_' . basename($_FILES['image_02']['name']);
                $targetFile2 = $uploadDir . $fileName2;
                if (move_uploaded_file($_FILES['image_02']['tmp_name'], $targetFile2)) {
                    $image_02 = $fileName2;
                }
            }
            if (!empty($_FILES['image_03']['name'])) {
                $uploadDir = __DIR__ . '/../../images/products/';
                $fileName3 = uniqid() . '_' . basename($_FILES['image_03']['name']);
                $targetFile3 = $uploadDir . $fileName3;
                if (move_uploaded_file($_FILES['image_03']['tmp_name'], $targetFile3)) {
                    $image_03 = $fileName3;
                }
            }

            // Insert product - matching your exact table structure
            $stmt = $conn->prepare("INSERT INTO products (
                name, product_type, category_id, price, status, description, ingredients, dosage, manufacturer, supplier_id, requires_prescription, max_quantity, stock, image_01, image_02, image_03, sales, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )");

            $stmt->execute([
                $formData['name'],
                $formData['product_type'],
                $formData['category_id'],
                $formData['price'],
                $formData['status'],
                $formData['description'],
                $formData['ingredients'],
                $formData['dosage'],
                $formData['manufacturer'],
                $formData['supplier_id'],
                $formData['requires_prescription'],
                $formData['max_quantity'],
                $formData['stock'],
                $image_01,
                $image_02,
                $image_03,
                $formData['sales'],
            ]);

            $conn->commit();
            $_SESSION['success'] = 'Product added successfully!';
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
    <title>Add New Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0eb582;
            --light: #f0fdfa;
            --dark: #1a1a1a;
            --border: 1px solid rgba(0,0,0,0.1);
            --shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 0.5rem;
            box-shadow: var(--shadow);
            border: none;
        }
        .form-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: var(--shadow);
        }
        .section-title {
            color: var(--primary);
            border-bottom: var(--border);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .required:after {
            content: " *";
            color: red;
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>
    
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Add New Product</h1>
            <a href="list.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Products
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p class="mb-1"><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-container">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="name" class="form-label required">Product Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($formData['name']) ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="product_type" class="form-label required">Product Type</label>
                    <select class="form-select" id="product_type" name="product_type" required>
                        <option value="prescription" <?= $formData['product_type'] === 'prescription' ? 'selected' : '' ?>>Prescription</option>
                        <option value="otc" <?= $formData['product_type'] === 'otc' ? 'selected' : '' ?>>Over-the-Counter</option>
                        <option value="wellness" <?= $formData['product_type'] === 'wellness' ? 'selected' : '' ?>>Wellness</option>
                        <option value="medical_device" <?= $formData['product_type'] === 'medical_device' ? 'selected' : '' ?>>Medical Device</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="category_id" class="form-label required">Category</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $formData['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No categories available</option>
                        <?php endif; ?>
                    </select>
                </div>
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
                <div class="form-group col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="requires_prescription" name="requires_prescription" value="1" <?= $formData['requires_prescription'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="requires_prescription">Requires Prescription</label>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="manufacturer" class="form-label">Manufacturer</label>
                    <input type="text" class="form-control" id="manufacturer" name="manufacturer" value="<?= htmlspecialchars($formData['manufacturer']) ?>">
                </div>
                <div class="form-group col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($formData['description']) ?></textarea>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="ingredients" class="form-label">Ingredients</label>
                    <textarea class="form-control" id="ingredients" name="ingredients" rows="2"><?= htmlspecialchars($formData['ingredients']) ?></textarea>
                </div>
                <div class="form-group col-md-6">
                    <label for="dosage" class="form-label">Dosage</label>
                    <input type="text" class="form-control" id="dosage" name="dosage" value="<?= htmlspecialchars($formData['dosage']) ?>">
                </div>
                <div class="form-group col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?= $formData['status']==='active'?'selected':'' ?>>Active</option>
                        <option value="inactive" <?= $formData['status']==='inactive'?'selected':'' ?>>Inactive</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="sales" class="form-label">Sales</label>
                    <input type="number" class="form-control" id="sales" name="sales" min="0" value="<?= htmlspecialchars($formData['sales']) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="image_01" class="form-label">Main Product Image</label>
                    <div id="imagePreview" class="image-preview mb-3"></div>
                    <input class="form-control" type="file" id="image_01" name="image_01" accept="image/*">
                </div>
                <div class="form-group col-md-4">
                    <label for="image_02" class="form-label">Image 2</label>
                    <input class="form-control" type="file" id="image_02" name="image_02" accept="image/*">
                </div>
                <div class="form-group col-md-4">
                    <label for="image_03" class="form-label">Image 3</label>
                    <input class="form-control" type="file" id="image_03" name="image_03" accept="image/*">
                </div>
            </div>
            <div class="form-row">
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-2"></i>Save Product
                    </button>
                </div>
            </div>
        </form>
        <?php include __DIR__ . '/../includes/admin_footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('image_01').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" alt="Preview">`;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>