<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define root path and include dependencies
define('ROOT_PATH', dirname(dirname(__FILE__)) . '/');
require ROOT_PATH . 'includes/auth.php';
require ROOT_PATH . 'components/connect.php';

// Check admin access (uncomment when ready)
// if (!isAdmin()) {
//     $_SESSION['flash_message'] = [
//         'type' => 'error',
//         'message' => 'Unauthorized access'
//     ];
//     header('Location: ../login.php');
//     exit();
// }

// Validate category ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid category ID'
    ];
    header('Location: list.php');
    exit();
}

$category_id = (int)$_GET['id'];

// Fetch current category data
try {
    $stmt = $conn->prepare("SELECT * FROM product_categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Category not found'
        ];
        header('Location: list.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Database error occurred'
    ];
    header('Location: list.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $error = null;

    // Validate inputs
    if (empty($name)) {
        $error = "Category name is required";
    } elseif (strlen($name) > 255) {
        $error = "Category name must be 255 characters or less";
    } elseif ($parent_id === $category_id) {
        $error = "A category cannot be its own parent";
    }

    if (!$error) {
        try {
            $conn->beginTransaction();
            error_log("Update params: name=$name, parent_id=" . var_export($parent_id, true) . ", category_id=$category_id");
            $update_stmt = $conn->prepare("
                UPDATE product_categories 
                SET name = ?, parent_id = ?
                WHERE id = ?
            ");
            $update_stmt->execute([$name, $parent_id, $category_id]);
            $conn->commit();
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Category updated successfully'
            ];
            header('Location: list.php');
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Update error: " . $e->getMessage());
            $error = "Failed to update category. DB says: " . $e->getMessage();
        }
    } else {
        // Prevent redirect, show error message and debug info
        echo '<div class="alert alert-danger">'.htmlspecialchars($error).'</div>';
    }
}

// Fetch all categories (excluding current one) for parent dropdown
try {
    $categories_stmt = $conn->query("
        SELECT id, name, parent_id 
        FROM product_categories 
        WHERE id != $category_id 
        ORDER BY name
    ");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Categories fetch error: " . $e->getMessage());
    $categories = [];
}

// Recursive function for dropdown
function displayCategories($categories, $parent_id = null, $level = 0, $current_parent = null) {
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parent_id) {
            $selected = ($current_parent == $category['id']) ? 'selected' : '';
            $indent = str_repeat('&nbsp;&nbsp;', $level);
            echo "<option value='{$category['id']}' $selected>";
            echo $indent . htmlspecialchars($category['name']);
            echo "</option>";
            displayCategories($categories, $category['id'], $level + 1, $current_parent);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - Admin Panel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="<?= ROOT_PATH ?>assets/css/admin.css">
    
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --light-bg: #f8f9fc;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .admin-card {
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: none;
        }
        
        .card-header {
            font-weight: 600;
            background-color: var(--primary-color);
            border-bottom: none;
            padding: 1rem 1.35rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-secondary {
            color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .alert {
            border-radius: 0.35rem;
            border-left: 0.25rem solid;
        }
        
        .alert-danger {
            border-left-color: var(--danger-color);
        }
        
        .alert-success {
            border-left-color: var(--success-color);
        }
    </style>
</head>
<body>
    <?php include ROOT_PATH . 'includes/admin_header.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="admin-card mb-4">
                    <div class="card-header text-white">
                        <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Category</h4>
                    </div>
                    
                    <div class="card-body">
                        <!-- Error Messages -->
                        <?php if (isset($error) && $error): ?>
                            <div class="alert alert-danger alert-dismissible fade show mb-4">
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Flash Messages -->
                        <?php if (isset($_SESSION['flash_message'])): ?>
                            <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show mb-4">
                                <?= htmlspecialchars($_SESSION['flash_message']['message']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['flash_message']); ?>
                        <?php endif; ?>
                        
                        <!-- Edit Form -->
                        <form method="post" id="editCategoryForm">
                            <div class="mb-4">
                                <label for="name" class="form-label">Category Name *</label>
                                <input type="text" class="form-control form-control-lg" id="name" name="name" 
                                       value="<?= htmlspecialchars($category['name'] ?? '') ?>" required
                                       maxlength="255">
                                <div class="invalid-feedback">Please provide a valid category name</div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="parent_id" class="form-label">Parent Category</label>
                                <select class="form-select form-select-lg" id="parent_id" name="parent_id">
                                    <option value="">-- No Parent Category --</option>
                                    <?php displayCategories($categories, null, 0, $category['parent_id'] ?? null); ?>
                                </select>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-5">
                                <button type="submit" class="btn btn-primary px-4 py-2">
                                    <i class="fas fa-save me-2"></i>Update Category
                                </button>
                                <a href="list.php" class="btn btn-outline-secondary px-4 py-2">
                                    <i class="fas fa-arrow-left me-2"></i>Back to List
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . 'includes/admin_footer.php'; ?>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Admin JS -->
    <script src="<?= ROOT_PATH ?>assets/js/admin.js"></script>
    
    <script>
        // Form validation
        (function() {
            'use strict';
            
            const form = document.getElementById('editCategoryForm');
            
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        })();
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>