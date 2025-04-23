<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Get search query and category filter
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';

// Get products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count
$total_count = $conn->prepare("
    SELECT COUNT(*) FROM products 
    WHERE status = 'active' 
    AND (name LIKE ? OR description LIKE ?)
    AND (category_id = ? OR ? = '')
");
$total_count->execute(["%$search%", "%$search%", $category_id, $category_id]);
$total_pages = ceil($total_count->fetchColumn() / $limit);

// Get products
$select_products = $conn->prepare("
    SELECT * FROM products 
    WHERE status = 'active' 
    AND (name LIKE ? OR description LIKE ?)
    AND (category_id = ? OR ? = '')
    ORDER BY name
    LIMIT ? OFFSET ?
");
$select_products->execute([
    "%$search%", "%$search%", 
    $category_id, $category_id,
    $limit, $offset
]);
$products = $select_products->fetchAll(PDO::FETCH_ASSOC);

// Get all categories
$select_categories = $conn->prepare("SELECT id, name FROM product_categories WHERE is_featured = 1 ORDER BY name");
$select_categories->execute();
$categories = $select_categories->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Select Product for Special Offer</h4>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Offers
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <form action="" method="get" class="d-flex">
                                <input type="text" name="search" class="form-control me-2" 
                                       placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <select name="category_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"
                                            <?= $category_id == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <?php if (empty($products)): ?>
                        <div class="alert alert-info">
                            No products found matching your criteria.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Price (KES)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <img src="../images/<?= htmlspecialchars($product['image_01']) ?>" 
                                                     alt="<?= htmlspecialchars($product['name']) ?>" 
                                                     class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                            </td>
                                            <td>
                                                <a href="../products.php?id=<?= $product['id'] ?>" target="_blank">
                                                    <?= htmlspecialchars($product['name']) ?>
                                                </a>
                                            </td>
                                            <td>KES <?= number_format((float)$product['price'], 2) ?></td>
                                            <td>
                                                <a href="add.php?product_id=<?= $product['id'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-tag me-2"></i>Create Offer
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Products navigation">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category_id=<?= $category_id ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category_id=<?= $category_id ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category_id=<?= $category_id ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
