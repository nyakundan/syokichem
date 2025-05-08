<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/connect.php';

if (!isset($_GET['id'])) {
    header('Location: manage.php');
    exit();
}

$productId = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT p.*, c.name AS category_name, s.name AS supplier_name 
    FROM products p
    LEFT JOIN product_categories c ON p.category = c.name
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE p.id = ?
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['error'] = 'Product not found';
    header('Location: manage.php');
    exit();
}

// Get stock history
$stockHistory = $conn->prepare("
    SELECT * FROM inventory_log 
    WHERE product_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stockHistory->execute([$productId]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Product</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Product Details</h1>
            <div class="actions">
                <a href="update.php?id=<?= $product['id'] ?>" class="btn btn-edit">Edit</a>
                <a href="manage.php" class="btn btn-back">Back to List</a>
            </div>
        </div>
        
        <div class="product-detail">
            <div class="detail-row">
                <span class="label">Product ID:</span>
                <span class="value"><?= $product['id'] ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Name:</span>
                <span class="value"><?= htmlspecialchars($product['name']) ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Category:</span>
                <span class="value"><?= htmlspecialchars($product['category_name'] ?? $product['category']) ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Barcode:</span>
                <span class="value"><?= $product['barcode'] ?: 'N/A' ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Description:</span>
                <span class="value"><?= nl2br(htmlspecialchars($product['description'] ?: 'No description')) ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label">Ingredients:</span>
                <span class="value"><?= nl2br(htmlspecialchars($product['ingredients'] ?: 'N/A')) ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Dosage:</span>
                <span class="value"><?= htmlspecialchars($product['dosage'] ?: 'N/A') ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Stock Quantity:</span>
                <span class="value"><?= htmlspecialchars($product['stock']) ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Status:</span>
                <span class="value">
                    <span class="badge <?= $product['status']==='active'?'badge-success':'badge-danger' ?>">
                        <?= ucfirst($product['status']) ?>
                    </span>
                </span>
            </div>
            <div class="detail-row">
                <span class="label">Sales:</span>
                <span class="value"><?= htmlspecialchars($product['sales']) ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Images:</span>
                <span class="value">
                    <img src="../../images/products/<?= htmlspecialchars($product['image_01']) ?>" alt="Image 1" height="40">
                    <?php if($product['image_02']): ?><img src="../../images/products/<?= htmlspecialchars($product['image_02']) ?>" alt="Image 2" height="40"><?php endif; ?>
                    <?php if($product['image_03']): ?><img src="../../images/products/<?= htmlspecialchars($product['image_03']) ?>" alt="Image 3" height="40"><?php endif; ?>
                </span>
            </div>
            
            <div class="detail-grid">
                <div class="detail-card">
                    <h3>Pricing</h3>
                    <div class="detail-row">
                        <span class="label">Cost Price:</span>
                        <span class="value">$<?= number_format($product['cost'], 2) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Selling Price:</span>
                        <span class="value">$<?= number_format($product['price'], 2) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Profit Margin:</span>
                        <span class="value">
                            <?= number_format(($product['price'] - $product['cost']) / $product['cost'] * 100, 2) ?>%
                        </span>
                    </div>
                </div>
                
                <div class="detail-card">
                    <h3>Inventory</h3>
                    <div class="detail-row">
                        <span class="label">Current Stock:</span>
                        <span class="value <?= $product['stock'] <= $product['stock_alert'] ? 'text-warning' : '' ?>">
                            <?= $product['stock'] ?>
                            <?php if ($product['stock'] <= $product['stock_alert']): ?>
                                <span class="badge badge-warning">Low Stock</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Low Stock Alert:</span>
                        <span class="value"><?= $product['stock_alert'] ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Status:</span>
                        <span class="value">
                            <span class="status-badge <?= $product['status'] ? 'active' : 'inactive' ?>">
                                <?= $product['status'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </span>
                    </div>
                </div>
                
                <div class="detail-card">
                    <h3>Supplier Info</h3>
                    <div class="detail-row">
                        <span class="label">Manufacturer:</span>
                        <span class="value"><?= $product['manufacturer'] ?: 'N/A' ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Supplier:</span>
                        <span class="value"><?= $product['supplier_name'] ?: 'N/A' ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Expiry Date:</span>
                        <span class="value <?= 
                            $product['expiry_date'] && strtotime($product['expiry_date']) < strtotime('+30 days') ? 
                            'text-danger' : '' ?>">
                            <?= $product['expiry_date'] ? 
                                date('M j, Y', strtotime($product['expiry_date'])) : 'N/A' ?>
                            <?php if ($product['expiry_date'] && strtotime($product['expiry_date']) < time()): ?>
                                <span class="badge badge-danger">Expired</span>
                            <?php elseif ($product['expiry_date'] && strtotime($product['expiry_date']) < strtotime('+30 days')): ?>
                                <span class="badge badge-warning">Expiring Soon</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Stock History -->
            <div class="detail-section">
                <h3>Recent Stock History</h3>
                <?php if ($stockHistory->rowCount() > 0): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Reference</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($entry = $stockHistory->fetch()): ?>
                            <tr>
                                <td><?= date('M j, Y H:i', strtotime($entry['created_at'])) ?></td>
                                <td><?= ucfirst($entry['movement_type']) ?></td>
                                <td><?= $entry['quantity'] ?></td>
                                <td><?= $entry['reference'] ?: 'N/A' ?></td>
                                <td><?= htmlspecialchars($entry['notes'] ?: '') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No stock history available</p>
                <?php endif; ?>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="adjust-stock.php?id=<?= $product['id'] ?>" class="btn btn-action">
                        <i class="fas fa-boxes"></i> Adjust Stock
                    </a>
                    <a href="print-barcode.php?id=<?= $product['id'] ?>" class="btn btn-action">
                        <i class="fas fa-barcode"></i> Print Barcode
                    </a>
                    <?php if ($product['supplier_id']): ?>
                        <a href="../suppliers/view.php?id=<?= $product['supplier_id'] ?>" class="btn btn-action">
                            <i class="fas fa-truck"></i> View Supplier
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/admin_footer.php'; ?>
</body>
</html>