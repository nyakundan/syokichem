<?php
declare(strict_types=1);

// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/connect.php';

// Handle success/error messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Pagination
$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchCondition = '';
$params = [];

if (!empty($search)) {
    $searchCondition = "WHERE p.name LIKE :search OR c.name LIKE :search OR s.name LIKE :search";
    $params[':search'] = "%$search%";
}

// Get products with category and supplier names
try {
    $sql = "SELECT p.id, p.name, p.category_id, p.category, p.price, p.status, p.description, p.ingredients, p.dosage, p.manufacturer, p.supplier_id, p.requires_prescription, p.max_quantity, p.image_01, p.image_02, p.image_03, p.sales, p.stock, p.how_to_use, p.precautions, c.name AS category_name, s.name AS supplier_name " .
        "FROM products p " .
        "LEFT JOIN product_categories c ON p.category_id = c.id " .
        "LEFT JOIN suppliers s ON p.supplier_id = s.id " .
        "$searchCondition " .
        "ORDER BY p.id DESC " .
        "LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($sql);
    if (!empty($search)) {
        $stmt->bindValue(':search', $search, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM products p " .
        "LEFT JOIN product_categories c ON p.category_id = c.id " .
        "LEFT JOIN suppliers s ON p.supplier_id = s.id " .
        "$searchCondition";
    $countStmt = $conn->prepare($countSql);
    if (!empty($search)) {
        $countStmt->bindValue(':search', $search, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalProducts = $countStmt->fetchColumn();
    $totalPages = ceil($totalProducts / $perPage);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Failed to load products. Please try again later.";
    $products = [];
    $totalPages = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4CAF50;
            --primary-light: #81C784;
            --primary-dark: #388E3C;
            --secondary: #2196F3;
            --accent: #FF9800;
            --danger: #F44336;
            --warning: #FFC107;
            --info: #00BCD4;
            --white: #FFFFFF;
            --light-gray: #F5F7FA;
            --medium-gray: #E0E0E0;
            --dark-gray: #607D8B;
            --text-dark: #263238;
            --text-medium: #546E7A;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: var(--light-gray); color: var(--text-dark); }
        .container { max-width: 1400px; margin: 2rem auto; padding: 0 1rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--medium-gray); }
        .page-title { color: var(--primary-dark); font-weight: 600; }
        .search-box { max-width: 400px; }
        .table-responsive { overflow-x: auto; background-color: var(--white); border-radius: var(--border-radius); box-shadow: var(--box-shadow); }
        .table { margin-bottom: 0; }
        .table th { background-color: var(--light-gray); border-top: none; }
        .badge-active { background-color: var(--primary); }
        .badge-inactive { background-color: var(--danger); }
        .action-btn { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .pagination { justify-content: center; margin-top: 2rem; }
        .page-item.active .page-link { background-color: var(--primary); border-color: var(--primary); }
        .page-link { color: var(--primary); }
        .alert { border-radius: var(--border-radius); }
        @media (max-width: 768px) { .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Manage Products</h1>
            <a href="add.php" class="btn btn-success"><i class="fas fa-plus"></i> Add Product</a>
        </div>
        <?php if ($success): ?>
            <div class="alert alert-success"> <?= htmlspecialchars($success) ?> </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category (ID)</th>
                        <th>Category (Text)</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Stock</th>
                        <th>Supplier</th>
                        <th>Image 1</th>
                        <th>Image 2</th>
                        <th>Image 3</th>
                        <th>Sales</th>
                        <th>Requires Prescription</th>
                        <th>Max Qty</th>
                        <th>How to Use</th>
                        <th>Precautions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="17" class="text-center">No products found.</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars(strval($product['id'])) ?></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= htmlspecialchars(strval($product['category_id'])) ?></td>
                            <td><?= htmlspecialchars($product['category']) ?></td>
                            <td>KSh <?= number_format((float)$product['price'], 2) ?></td>
                            <td>
                                <span class="badge <?= $product['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= ucfirst(htmlspecialchars($product['status'])) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(strval($product['stock'])) ?></td>
                            <td><?= htmlspecialchars($product['supplier_name'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($product['image_01'])): ?>
                                    <img src="../../images/products/<?= htmlspecialchars($product['image_01']) ?>" alt="Image 1" style="max-width:60px; max-height:60px;">
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($product['image_02'])): ?>
                                    <img src="../../images/products/<?= htmlspecialchars($product['image_02']) ?>" alt="Image 2" style="max-width:60px; max-height:60px;">
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($product['image_03'])): ?>
                                    <img src="../../images/products/<?= htmlspecialchars($product['image_03']) ?>" alt="Image 3" style="max-width:60px; max-height:60px;">
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars(strval($product['sales'])) ?></td>
                            <td><?= $product['requires_prescription'] ? 'Yes' : 'No' ?></td>
                            <td><?= htmlspecialchars(strval($product['max_quantity'])) ?></td>
                            <td><?= htmlspecialchars($product['how_to_use'] ?? '') ?></td>
                            <td><?= htmlspecialchars($product['precautions'] ?? '') ?></td>
                            <td>
                                <a href="update.php?id=<?= $product['id'] ?>" class="btn btn-primary btn-sm action-btn"><i class="fas fa-edit"></i> Edit</a>
                                <a href="delete.php?id=<?= $product['id'] ?>" class="btn btn-danger btn-sm action-btn" onclick="return confirm('Are you sure you want to delete this product?');"><i class="fas fa-trash"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"> <?= $i ?> </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php include __DIR__ . '/../includes/admin_footer.php'; ?>
</body>
</html>