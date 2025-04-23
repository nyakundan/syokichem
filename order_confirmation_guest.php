<?php
include 'components/connect.php';
session_start();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order = null;
$order_items = [];

if ($order_id) {
    $stmt = $conn->prepare("SELECT * FROM `orders` WHERE id = ?");
    $stmt->execute([$order_id]);
    if ($stmt->rowCount() > 0) {
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt_items = $conn->prepare("SELECT * FROM `order_items` WHERE order_id = ?");
        $stmt_items->execute([$order_id]);
        $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - SYOKICHEM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .confirmation-container {
            max-width: 700px;
            margin: 2rem auto;
            background: #f8f9fa;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 2rem 2rem 1rem 2rem;
        }
        .confirmation-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .confirmation-header .icon {
            font-size: 3rem;
            color: #006837;
            margin-bottom: 0.5rem;
        }
        .order-details, .customer-details {
            margin-bottom: 1.5rem;
        }
        .order-items {
            margin-bottom: 2rem;
        }
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .order-items-table th, .order-items-table td {
            border: 1px solid #e0e0e0;
            padding: 0.5rem 0.75rem;
        }
        .order-items-table th {
            background: #006837;
            color: #fff;
        }
        .summary {
            font-size: 1.1rem;
            margin-top: 1rem;
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #006837;
            color: white;
        }
        .btn-primary:hover {
            background: #4CAF50;
            transform: translateY(-2px);
        }
        .btn-outline {
            background: #fff;
            border: 1px solid #006837;
            color: #006837;
        }
        .btn-outline:hover {
            background: #f1f1f1;
        }
        @media (max-width: 600px) {
            .confirmation-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
<?php include 'components/user_header.php'; ?>
<div class="confirmation-container">
    <?php if ($order): ?>
        <div class="confirmation-header">
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your order<?= $order['user_id'] ? ', ' . htmlspecialchars($order['name']) : '' ?>. Your order has been placed successfully.</p>
        </div>
        <div class="order-details">
            <h3>Order Information</h3>
            <p><strong>Order Number:</strong> #<?= $order['id'] ?></p>
            <p><strong>Date:</strong> <?= date('F j, Y, g:i a', strtotime($order['placed_on'])) ?></p>
            <p><strong>Status:</strong> <?= ucfirst($order['order_status']) ?></p>
        </div>
        <div class="customer-details">
            <h3>Customer Details</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($order['name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
        </div>
        <div class="order-items">
            <h3>Items Ordered</h3>
            <?php if ($order_items): ?>
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td>KSh <?= number_format($item['price'], 2) ?></td>
                                <td><?= htmlspecialchars($item['quantity']) ?></td>
                                <td>KSh <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No items found for this order.</p>
            <?php endif; ?>
        </div>
        <div class="summary">
            <p><strong>Total Products:</strong> <?= htmlspecialchars($order['total_products']) ?></p>
            <p><strong>Total Price:</strong> KSh <?= number_format($order['total_price'], 2) ?></p>
            <?php if (!empty($order['delivery_fee'])): ?>
                <p><strong>Delivery Fee:</strong> KSh <?= number_format($order['delivery_fee'], 2) ?></p>
            <?php endif; ?>
            <?php if (!empty($order['discount'])): ?>
                <p><strong>Discount:</strong> KSh <?= number_format($order['discount'], 2) ?></p>
            <?php endif; ?>
        </div>
        <div class="action-buttons">
            <a href="shop.php" class="btn btn-outline">Continue Shopping</a>
            <a href="orders.php" class="btn btn-primary">View My Orders</a>
        </div>
    <?php else: ?>
        <div class="confirmation-header">
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1>Order Not Found</h1>
            <p>We couldn't find the order you're looking for.</p>
        </div>
        <div style="text-align: center; margin-top: 2rem;">
            <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    <?php endif; ?>
</div>
<?php include 'components/footer.php'; ?>
</body>
</html>
