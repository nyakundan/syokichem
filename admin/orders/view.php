<?php
$page_title = "Order Details";
//require 'C:/xampp/htdocs/ecommerce website/admin/includes/auth.php';
//require 'C:/xampp/htdocs/ecommerce website/admin/components/connect.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['toast_message'] = "Invalid order ID";
    header("Location: list.php");
    exit;
}

$order_id = (int)$_GET['id'];

try {
    // Fetch order details - modified to match your actual database structure
    $stmt = $conn->prepare("
        SELECT 
            o.*, 
            u.name as customer_name, 
            u.email as customer_email, 
            u.phone as customer_phone,
            u.address as shipping_address
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $_SESSION['toast_message'] = "Order not found";
        header("Location: list.php");
        exit;
    }

    // Fetch order items
    $items_stmt = $conn->prepare("
        SELECT 
            oi.*, 
            p.name as product_name, 
            p.image as product_image,
            p.price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $items_stmt->execute([$order_id]);
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch staff for assignment
    $staff_stmt = $conn->prepare("
        SELECT id, name 
        FROM admins 
        WHERE role IN ('admin','manager','staff') 
        ORDER BY name
    ");
    $staff_stmt->execute();
    $staff = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['toast_message'] = "Database error occurred. Please try again.";
    header("Location: list.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge.bg-warning { background-color: #ffc107; color: #212529; }
        .badge.bg-info { background-color: #0dcaf0; color: #fff; }
        .badge.bg-primary { background-color: #0d6efd; color: #fff; }
        .badge.bg-success { background-color: #198754; color: #fff; }
        .badge.bg-danger { background-color: #dc3545; color: #fff; }
        .table-responsive { overflow-x: auto; }
        .order-header { background-color: #f8f9fa; }
        .product-img { width: 50px; height: 50px; object-fit: cover; }
    </style>
</head>
<body>
    <?php //include 'C:/xampp/htdocs/ecommerce website/admin/includes/admin_header.php';
    
    include __DIR__ . '/../includes/admin_header.php';
    
     ?>
    
    <div class="container-fluid py-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center order-header">
                <h6 class="m-0 font-weight-bold text-primary">
                    Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>
                </h6>
                <div>
                    <span class="badge bg-<?= 
                        $order['order_status'] === 'delivered' ? 'success' : 
                        ($order['order_status'] === 'cancelled' ? 'danger' : 
                        ($order['order_status'] === 'processing' ? 'info' : 'warning')) 
                    ?> me-2">
                        <?= ucfirst($order['order_status']) ?>
                    </span>
                    <a href="invoice.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">
                        <i class="fas fa-file-invoice"></i> Invoice
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Customer Details</h5>
                        <div class="mb-3 p-3 bg-light rounded">
                            <p class="mb-1">
                                <strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?>
                            </p>
                            <p class="mb-1">
                                <strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?>
                            </p>
                            <p class="mb-1">
                                <strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?>
                            </p>
                        </div>
                        
                        <h5 class="mt-4">Shipping Address</h5>
                        <div class="p-3 bg-light rounded">
                            <?= nl2br(htmlspecialchars($order['shipping_address'] ?? 'No address provided')) ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Order Information</h5>
                        <div class="mb-3 p-3 bg-light rounded">
                            <p class="mb-1">
                                <strong>Date:</strong> <?= date('M d, Y H:i', strtotime($order['placed_on'])) ?>
                            </p>
                            <p class="mb-1">
                                <strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?>
                            </p>
                            <p class="mb-1">
                                <strong>Payment Status:</strong> 
                                <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($order['payment_status'] ?? 'pending') ?>
                                </span>
                            </p>
                            <p class="mb-1">
                                <strong>Total:</strong> $<?= number_format($order['total_price'], 2) ?>
                            </p>
                        </div>
                        
                        <form action="process.php" method="post" class="mt-4">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Update Status</label>
                                <select name="status" class="form-select" required>
                                    <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status): ?>
                                    <option value="<?= $status ?>" <?= $order['order_status'] == $status ? 'selected' : '' ?>>
                                        <?= ucfirst($status) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Order
                            </button>
                        </form>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <h5>Order Items</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($item['product_image'])): ?>
                                            <img src="<?= htmlspecialchars($item['product_image']) ?>" 
                                                 alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                 class="product-img me-3">
                                            <?php endif; ?>
                                            <div>
                                                <?= htmlspecialchars($item['product_name']) ?>
                                                <?php if (!empty($item['variation'])): ?>
                                                <div class="text-muted small"><?= htmlspecialchars($item['variation']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?= number_format($item['price'], 2) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">No items found in this order</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Subtotal:</th>
                                <th>$<?= number_format($order['total_price'], 2) ?></th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Shipping:</th>
                                <th>$<?= number_format($order['shipping_fee'] ?? 0, 2) ?></th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th>$<?= number_format($order['total_price'], 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php //include 'C:/xampp/htdocs/ecommerce website/admin/includes/footer.php';
    
    include __DIR__ . '/../includes/admin_footer.php';
    
     ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Confirm before cancelling order
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.querySelector('select[name="status"]');
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                if (statusSelect.value === 'cancelled') {
                    if (!confirm('Are you sure you want to cancel this order?')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>