<?php
// Enable error reporting at the TOP
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session with no output before it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use __DIR__ for safer path inclusion
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Initialize form data
$formData = [
    'name' => '',
    'slug' => '',
    'parent_id' => '',
    'description' => '',
    'is_featured' => 0,
    'menu_order' => 0,
    'meta_title' => '',
    'meta_description' => '',
];

$errors = [];

// Fetch categories for dropdown
try {
    $stmt = $conn->query("SELECT id, name, parent_id FROM product_categories ORDER BY menu_order, name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Failed to load categories. Please try again.";
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'slug' => trim($_POST['slug'] ?? ''),
        'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
        'description' => trim($_POST['description'] ?? ''),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'menu_order' => (int)($_POST['menu_order'] ?? 0),
        'meta_title' => trim($_POST['meta_title'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
    ];

    // Validation
    if (empty($formData['name'])) {
        $errors[] = 'Category name is required.';
    }

    if (empty($formData['slug'])) {
        $formData['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $formData['name']));
    } elseif (!preg_match('/^[a-z0-9\-]+$/', $formData['slug'])) {
        $errors[] = 'Slug can only contain lowercase letters, numbers, and hyphens.';
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Check for duplicate slug
            $slugCheck = $conn->prepare("SELECT id FROM product_categories WHERE slug = ?");
            $slugCheck->execute([$formData['slug']]);

            if ($slugCheck->rowCount() > 0) {
                $errors[] = 'Slug already exists. Choose a different one.';
                $conn->rollBack();
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO product_categories (
                        name, slug, parent_id, description, 
                        is_featured, menu_order, meta_title, meta_description, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $formData['name'],
                    $formData['slug'],
                    $formData['parent_id'],
                    $formData['description'],
                    $formData['is_featured'],
                    $formData['menu_order'],
                    $formData['meta_title'],
                    $formData['meta_description'],
                ]);

                $conn->commit();
                $_SESSION['success'] = 'Category added successfully!';
                header('Location: list.php');
                exit();
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Database error: " . $e->getMessage());
            $errors[] = 'Failed to add category. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Category</title>
    <!-- Load Bootstrap CSS from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .container {
            max-width: 800px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .page-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .alert {
            margin-bottom: 20px;
        }
        .back-btn {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>Add New Category</h1>
            <a href="list.php" class="btn btn-secondary back-btn">
                <i class="fas fa-arrow-left"></i> Back to Categories
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name">Category Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required 
                               value="<?= htmlspecialchars($formData['name']) ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="slug">Slug *</label>
                        <input type="text" class="form-control" id="slug" name="slug" 
                               value="<?= htmlspecialchars($formData['slug']) ?>">
                        <small class="text-muted">Auto-generated if left blank</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="parent_id">Parent Category</label>
                        <select class="form-control" id="parent_id" name="parent_id">
                            <option value="">-- No Parent --</option>
                            <?php
                            function displayCategories($categories, $parent_id = null, $level = 0) {
                                foreach ($categories as $category) {
                                    if ($category['parent_id'] == $parent_id) {
                                        $selected = (isset($formData['parent_id']) && $formData['parent_id'] == $category['id']) ? 'selected' : '';
                                        $indent = str_repeat('&nbsp;&nbsp;', $level);
                                        echo "<option value='{$category['id']}' $selected>";
                                        echo $indent . htmlspecialchars($category['name']);
                                        echo "</option>";
                                        displayCategories($categories, $category['id'], $level + 1);
                                    }
                                }
                            }
                            displayCategories($categories);
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="menu_order">Menu Order</label>
                        <input type="number" class="form-control" id="menu_order" name="menu_order" 
                               value="<?= htmlspecialchars($formData['menu_order']) ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= 
                    htmlspecialchars($formData['description']) 
                ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="meta_title">Meta Title</label>
                        <input type="text" class="form-control" id="meta_title" name="meta_title"
                               value="<?= htmlspecialchars($formData['meta_title']) ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="meta_description">Meta Description</label>
                        <input type="text" class="form-control" id="meta_description" name="meta_description"
                               value="<?= htmlspecialchars($formData['meta_description']) ?>">
                    </div>
                </div>
            </div>

            <div class="form-group form-check">
                <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1"
                       <?= $formData['is_featured'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_featured">Featured Category</label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save Category</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Load Bootstrap JS (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-generate slug from name
        document.getElementById('name').addEventListener('input', function() {
            const slugInput = document.getElementById('slug');
            if (!slugInput.value) {
                slugInput.value = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-|-$/g, '');
            }
        });
    </script>
</body>
</html>