<?php
// Enable all error reporting at the very top
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include authentication and database connection
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/connect.php';

// Validate product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid product ID';
    header('Location: list.php');
    exit();
}

$productId = (int)$_GET['id'];

// Get dropdown options
try {
    $categories = $conn->query("SELECT id, name FROM product_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $suppliers = $conn->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching dropdown options: " . $e->getMessage());
}

// Get current product data
try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        $_SESSION['error'] = 'Product not found';
        header('Location: list.php');
        exit();
    }
} catch (PDOException $e) {
    die("Error fetching product: " . $e->getMessage());
}

// Initialize form data
$formData = [
    'name' => $product['name'] ?? '',
    'category_id' => $product['category_id'] ?? '',
    'product_type' => $product['product_type'] ?? 'otc',
    'price' => $product['price'] ?? 0,
    'description' => $product['description'] ?? '',
    'ingredients' => $product['ingredients'] ?? '',
    'dosage' => $product['dosage'] ?? '',
    'manufacturer' => $product['manufacturer'] ?? '',
    'supplier_id' => $product['supplier_id'] ?? '',
    'requires_prescription' => $product['requires_prescription'] ?? 0,
    'max_quantity' => $product['max_quantity'] ?? 5,
    'stock' => $product['stock'] ?? 0,
    'status' => $product['status'] ?? 'active',
    'sales' => $product['sales'] ?? 0,
    'image_01' => $product['image_01'] ?? 'default-product.jpg',
    'image_02' => $product['image_02'] ?? '',
    'image_03' => $product['image_03'] ?? '',
];

$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
        'product_type' => $_POST['product_type'] ?? 'otc',
        'price' => (float)($_POST['price'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
        'ingredients' => trim($_POST['ingredients'] ?? ''),
        'dosage' => trim($_POST['dosage'] ?? ''),
        'manufacturer' => trim($_POST['manufacturer'] ?? ''),
        'supplier_id' => !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null,
        'requires_prescription' => isset($_POST['requires_prescription']) ? 1 : 0,
        'max_quantity' => (int)($_POST['max_quantity'] ?? 5),
        'stock' => (int)($_POST['stock'] ?? 0),
        'status' => $_POST['status'] ?? 'active',
        'sales' => (int)($_POST['sales'] ?? 0),
        'image_01' => $product['image_01'],
        'image_02' => $product['image_02'],
        'image_03' => $product['image_03'],
    ];

    // Validate required fields
    if (empty($formData['name'])) {
        $errors[] = 'Product name is required';
    }
    
    if (empty($formData['category_id'])) {
        $errors[] = 'Category is required';
    }
    
    if ($formData['price'] <= 0) {
        $errors[] = 'Price must be greater than 0';
    }
    
    if ($formData['stock'] < 0) {
        $errors[] = 'Stock cannot be negative';
    }

    // Process if no errors
    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Handle image upload if new file was provided
            if (!empty($_FILES['image_01']['name'])) {
                $uploadDir = __DIR__ . '/../../images/products/';
                
                // Create upload directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $fileExt = pathinfo($_FILES['image_01']['name'], PATHINFO_EXTENSION);
                $fileName = 'product_' . $productId . '_' . uniqid() . '.' . $fileExt;
                $targetFile = $uploadDir . $fileName;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['image_01']['tmp_name'], $targetFile)) {
                    // Delete old image if it's not the default
                    if ($formData['image_01'] !== 'default-product.jpg') {
                        $oldImagePath = __DIR__ . '/../../' . $formData['image_01'];
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    $formData['image_01'] = 'images/products/' . $fileName;
                } else {
                    throw new Exception('Failed to upload product image');
                }
            }
            if (!empty($_FILES['image_02']['name'])) {
                $uploadDir = __DIR__ . '/../../images/products/';
                $fileName2 = 'product_' . $productId . '_' . uniqid() . '_' . basename($_FILES['image_02']['name']);
                $targetFile2 = $uploadDir . $fileName2;
                if (move_uploaded_file($_FILES['image_02']['tmp_name'], $targetFile2)) {
                    $formData['image_02'] = 'images/products/' . $fileName2;
                }
            }
            if (!empty($_FILES['image_03']['name'])) {
                $uploadDir = __DIR__ . '/../../images/products/';
                $fileName3 = 'product_' . $productId . '_' . uniqid() . '_' . basename($_FILES['image_03']['name']);
                $targetFile3 = $uploadDir . $fileName3;
                if (move_uploaded_file($_FILES['image_03']['tmp_name'], $targetFile3)) {
                    $formData['image_03'] = 'images/products/' . $fileName3;
                }
            }

            // Update product
            $stmt = $conn->prepare("UPDATE products SET
                name = ?,
                product_type = ?,
                category_id = ?,
                price = ?,
                status = ?,
                description = ?,
                ingredients = ?,
                dosage = ?,
                manufacturer = ?,
                supplier_id = ?,
                requires_prescription = ?,
                max_quantity = ?,
                stock = ?,
                image_01 = ?,
                image_02 = ?,
                image_03 = ?,
                sales = ?
                WHERE id = ?");

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
                $formData['image_01'],
                $formData['image_02'],
                $formData['image_03'],
                $formData['sales'],
                $productId
            ]);

            $conn->commit();
            $_SESSION['success'] = 'Product updated successfully';
            header('Location: list.php');
            exit();
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            $conn->rollBack();
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
    <title>Edit Product - Admin Panel</title>
    <!-- Include admin header for consistent styling -->
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>
    <style>
        /* Minimal reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h1, h2, h3 {
            margin-bottom: 15px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        textarea {
            min-height: 100px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .current-image {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .col-md-6 {
            flex: 0 0 50%;
            padding: 0 10px;
        }
        
        @media (max-width: 768px) {
            .col-md-6 {
                flex: 0 0 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Edit Product</h1>
            <a href="list.php" class="btn btn-secondary">Back to Products</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h3>Please fix the following errors:</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <h2>Basic Information</h2>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" 
                               value="<?= htmlspecialchars($formData['name']) ?>" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="product_type">Product Type *</label>
                        <select id="product_type" name="product_type" required>
                            <option value="prescription" <?= $formData['product_type'] === 'prescription' ? 'selected' : '' ?>>Prescription</option>
                            <option value="otc" <?= $formData['product_type'] === 'otc' ? 'selected' : '' ?>>Over-the-Counter</option>
                            <option value="wellness" <?= $formData['product_type'] === 'wellness' ? 'selected' : '' ?>>Wellness</option>
                            <option value="medical_device" <?= $formData['product_type'] === 'medical_device' ? 'selected' : '' ?>>Medical Device</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $formData['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="supplier_id">Supplier</label>
                        <select id="supplier_id" name="supplier_id">
                            <option value="">Select Supplier</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['id'] ?>" <?= $formData['supplier_id'] == $supplier['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($supplier['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <h2>Pricing & Inventory</h2>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="price">Price *</label>
                        <input type="number" id="price" name="price" 
                               step="0.01" min="0.01" value="<?= htmlspecialchars($formData['price']) ?>" required>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="stock">Current Stock *</label>
                        <input type="number" id="stock" name="stock" 
                               min="0" value="<?= htmlspecialchars($formData['stock']) ?>" required>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="max_quantity">Max Order Quantity</label>
                        <input type="number" id="max_quantity" name="max_quantity" 
                               min="1" value="<?= htmlspecialchars($formData['max_quantity']) ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="requires_prescription" value="1" 
                           <?= $formData['requires_prescription'] ? 'checked' : '' ?>>
                    Requires Prescription
                </label>
            </div>

            <h2>Product Details</h2>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="manufacturer">Manufacturer</label>
                        <input type="text" id="manufacturer" name="manufacturer"
                               value="<?= htmlspecialchars($formData['manufacturer']) ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?= 
                    htmlspecialchars($formData['description']) 
                ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="ingredients">Ingredients</label>
                <textarea id="ingredients" name="ingredients"><?= htmlspecialchars($formData['ingredients']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="dosage">Dosage</label>
                <input type="text" id="dosage" name="dosage" value="<?= htmlspecialchars($formData['dosage']) ?>">
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" <?= $formData['status']==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $formData['status']==='inactive'?'selected':'' ?>>Inactive</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="sales">Sales</label>
                <input type="number" id="sales" name="sales" min="0" value="<?= htmlspecialchars($formData['sales']) ?>">
            </div>

            <h2>Product Images</h2>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="image_01">Main Product Image</label>
                        <input type="file" id="image_01" name="image_01" accept="image/*">
                        
                        <div id="imagePreview" class="image-preview"></div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <?php if (!empty($formData['image_01'])): ?>
                        <div class="form-group">
                            <label>Current Image</label>
                            <div>
                                <img src="<?= htmlspecialchars($formData['image_01']) ?>" 
                                     class="current-image" alt="Current Product Image">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="image_02">Image 2</label>
                        <input type="file" id="image_02" name="image_02" accept="image/*">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <?php if (!empty($formData['image_02'])): ?>
                        <div class="form-group">
                            <label>Current Image 2</label>
                            <div>
                                <img src="<?= htmlspecialchars($formData['image_02']) ?>" 
                                     class="current-image" alt="Current Product Image 2">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="image_03">Image 3</label>
                        <input type="file" id="image_03" name="image_03" accept="image/*">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <?php if (!empty($formData['image_03'])): ?>
                        <div class="form-group">
                            <label>Current Image 3</label>
                            <div>
                                <img src="<?= htmlspecialchars($formData['image_03']) ?>" 
                                     class="current-image" alt="Current Product Image 3">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <button type="submit" class="btn">Update Product</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <?php include __DIR__ . '/../includes/admin_footer.php'; ?>
    
    <script>
        // Image preview functionality
        document.getElementById('image_01').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '100%';
                    img.style.maxHeight = '200px';
                    preview.appendChild(img);
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Basic form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Check required fields
            const requiredFields = [
                document.getElementById('name'),
                document.getElementById('product_type'),
                document.getElementById('category_id'),
                document.getElementById('price'),
                document.getElementById('stock')
            ];
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = 'red';
                    isValid = false;
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    </script>
</body>
</html>