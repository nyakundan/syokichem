<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/connect.php';

// Handle delete action
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    try {
        // Check if category has products
        $check_products = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $check_products->execute([$delete_id]);
        $product_count = $check_products->fetchColumn();
        
        if ($product_count > 0) {
            $_SESSION['flash_message'] = [
                'type' => 'error', 
                'message' => 'Cannot delete category with existing products.'
            ];
        } else {
            // Check if category has subcategories
            $check_subcategories = $conn->prepare("SELECT COUNT(*) FROM product_categories WHERE parent_id = ?");
            $check_subcategories->execute([$delete_id]);
            $subcategory_count = $check_subcategories->fetchColumn();
            
            if ($subcategory_count > 0) {
                $_SESSION['flash_message'] = [
                    'type' => 'error', 
                    'message' => 'Cannot delete category with existing subcategories.'
                ];
            } else {
                $delete_category = $conn->prepare("DELETE FROM product_categories WHERE id = ?");
                $delete_category->execute([$delete_id]);
                
                $_SESSION['flash_message'] = [
                    'type' => 'success', 
                    'message' => 'Category deleted successfully!'
                ];
            }
        }
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = [
            'type' => 'error', 
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
    
    header('Location: list.php');
    exit();
}

// Get all categories with parent names
$select_categories = $conn->query("
    SELECT c.id, c.parent_id, c.name, p.name as parent_name 
    FROM product_categories c
    LEFT JOIN product_categories p ON c.parent_id = p.id
    ORDER BY c.parent_id, c.name
");
$categories = $select_categories->fetchAll(PDO::FETCH_ASSOC);

// Build full recursive category tree for display
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

$category_tree = buildCategoryTree($categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-list-alt me-2"></i> Manage Categories
            </h2>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Category
            </a>
        </div>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_message']['type']) ?> alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['flash_message']['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>
        
        <div class="card shadow">
            <div class="card-body">
                <?php if (empty($category_tree)): ?>
                    <div class="alert alert-info">No categories found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Parent Category</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                function renderCategoryRows($categories, $parentName = '', $level = 0) {
                                    foreach ($categories as $category) {
                                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                                        echo '<tr'.($level === 0 ? ' class="table-primary"' : '').'>';
                                        echo '<td>' . htmlspecialchars((string)$category['id']) . '</td>';
                                        echo '<td>' . $indent . htmlspecialchars($category['name']) . '</td>';
                                        echo '<td>' . ($parentName ? htmlspecialchars($parentName) : '—') . '</td>';
                                        echo '<td><div class="d-flex gap-2">';
                                        echo '<a href="edit.php?id=' . htmlspecialchars((string)$category['id']) . '" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>';
                                        echo '<a href="list.php?delete=' . htmlspecialchars((string)$category['id']) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to delete this category?\')"><i class="fas fa-trash"></i></a>';
                                        echo '</div></td>';
                                        echo '</tr>';
                                        if (!empty($category['children'])) {
                                            renderCategoryRows($category['children'], $category['name'], $level + 1);
                                        }
                                    }
                                }
                                renderCategoryRows($category_tree);
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/admin_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Confirm before deleting
            const deleteButtons = document.querySelectorAll('a[href*="delete"]');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this category?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>