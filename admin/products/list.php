<?php
declare(strict_types=1);

// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/connect.php';

// Check if user is authorized
//if (!isset($_SESSION['admin_id'])) {
   // header('Location: login.php');
   // exit();
//}

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
    $searchCondition = "WHERE p.name LIKE :search OR p.barcode LIKE :search OR c.name LIKE :search OR s.name LIKE :search";
    $params[':search'] = "%$search%";
}

// Get products with category and supplier names
try {
    $sql = "SELECT p.*, c.name AS category_name, s.name AS supplier_name 
            FROM products p
            LEFT JOIN product_categories c ON p.category_id = c.id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            $searchCondition
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($search)) {
        $stmt->bindValue(':search', $search, PDO::PARAM_STR);
    }
    
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll();
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM products p
                 LEFT JOIN product_categories c ON p.category_id = c.id
                 LEFT JOIN suppliers s ON p.supplier_id = s.id
                 $searchCondition";
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

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--light-gray);
            color: var(--text-dark);
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--medium-gray);
        }

        .page-title {
            color: var(--primary-dark);
            font-weight: 600;
        }

        .search-box {
            max-width: 400px;
        }

        .table-responsive {
            overflow-x: auto;
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: var(--light-gray);
            border-top: none;
        }

        .badge-active {
            background-color: var(--primary);
        }

        .badge-inactive {
            background-color: var(--danger);
        }

        .action-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }

        .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .page-link {
            color: var(--primary);
        }

        .alert {
            border-radius: var(--border-radius);
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .search-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Manage Products</h1>
            <div class="d-flex gap-3">
                <form method="GET" class="search-box">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search products..." 
                               value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                <a href="add.php" class="btn btn-primary align-self-center">
                    <i class="fas fa-plus"></i> Add Product
                </a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

       
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Price</th>
                        <th>Barcode</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Stock Qty</th>
                        <th>Sales</th>
                        <th>Images</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="13" class="text-center py-4">No products found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): 
                            $stock = (int)($product['stock'] ?? 0);
                            $stockAlert = (int)($product['stock_alert'] ?? 10); // Default value if missing
                            $price = (float)($product['price'] ?? 0);
                            $status = (bool)($product['status'] ?? true); // Default to true
                        ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($product['id'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($product['name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></td>
                                <td><?= htmlspecialchars($product['supplier_name'] ?? 'N/A') ?></td>
                                <td>Ksh.<?= number_format($price, 2) ?></td>
                                <td><?= htmlspecialchars($product['barcode'] ?? '') ?></td>
                                <td><?= htmlspecialchars($product['product_type']) ?></td>
                                <td><span class="badge <?= $product['status']==='active'?'bg-success':'bg-danger' ?>"><?= ucfirst($product['status']) ?></span></td>
                                <td><?= htmlspecialchars((string)($product['stock'] ?? 0)) ?></td>
                                <td><?= htmlspecialchars((string)($product['sales'] ?? 0)) ?></td>
                                <td>
                                    <img src="../../images/products/<?= htmlspecialchars($product['image_01']) ?>" alt="Image 1" height="30">
                                    <?php if($product['image_02']): ?><img src="../../images/products/<?= htmlspecialchars($product['image_02']) ?>" alt="Image 2" height="30"><?php endif; ?>
                                    <?php if($product['image_03']): ?><img src="../../images/products/<?= htmlspecialchars($product['image_03']) ?>" alt="Image 3" height="30"><?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="edit.php?id=<?= (int)($product['id'] ?? 0) ?>" class="btn btn-sm btn-primary action-btn">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="delete.php" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                            <input type="hidden" name="id" value="<?= (int)($product['id'] ?? 0) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger action-btn">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/admin_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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