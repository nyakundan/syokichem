<?php
session_start();
include 'components/connect.php';

// Redirect if not logged in
if(!isset($_SESSION['user_id'])){
    header('location:user_login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get order ID from URL with validation
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables
$success_message = '';
$error_message = '';

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // 1. Get the original order details
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $user_id]);
        
        if($stmt->rowCount() > 0) {
            $original_order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // 2. Create a new order with similar details
            $insert_order = $conn->prepare("INSERT INTO orders 
                (user_id, name, email, phone, address, payment_method, total_products, total_price, delivery_fee, discount, placed_on)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $insert_order->execute([
                $user_id,
                $original_order['name'],
                $original_order['email'],
                $original_order['phone'],
                $original_order['address'],
                $original_order['payment_method'],
                $original_order['total_products'],
                $original_order['total_price'],
                $original_order['delivery_fee'],
                $original_order['discount']
            ]);
            
            $new_order_id = $conn->lastInsertId();
            
            // 3. Get the original order items
            $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 4. Add items to the new order
            foreach($order_items as $item) {
                // Check if product still exists
                $product_check = $conn->prepare("SELECT id, price FROM products WHERE id = ?");
                $product_check->execute([$item['product_id']]);
                
                if($product_check->rowCount() > 0) {
                    $product = $product_check->fetch(PDO::FETCH_ASSOC);
                    
                    $insert_item = $conn->prepare("INSERT INTO order_items 
                        (order_id, product_id, name, price, quantity, size, color)
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                    
                    $insert_item->execute([
                        $new_order_id,
                        $item['product_id'],
                        $item['name'],
                        $product['price'], // Use current price
                        $item['quantity'],
                        $item['size'],
                        $item['color']
                    ]);
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "Order #$new_order_id has been successfully recreated!";
            
            // Redirect to the new order or cart
            header("Location: view_order.php?id=$new_order_id");
            exit();
        } else {
            $error_message = "Original order not found or doesn't belong to you.";
        }
    } catch(PDOException $e) {
        $conn->rollBack();
        $error_message = "Error processing reorder: " . $e->getMessage();
    }
}

// If not a POST request, show the reorder confirmation page
try {
    $stmt = $conn->prepare("SELECT o.*, COUNT(oi.id) as item_count 
                          FROM orders o
                          LEFT JOIN order_items oi ON o.id = oi.order_id
                          WHERE o.id = ? AND o.user_id = ?
                          GROUP BY o.id");
    $stmt->execute([$order_id, $user_id]);
    
    if($stmt->rowCount() > 0) {
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get order items
        $stmt = $conn->prepare("SELECT oi.*, p.image_01 as image, p.stock as stock 
                              FROM order_items oi
                              LEFT JOIN products p ON oi.product_id = p.id
                              WHERE oi.order_id = ?");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error_message = "Order not found or doesn't belong to you.";
    }
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reorder #<?= htmlspecialchars($order_id) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #3f37c9;
            --danger: #f72585;
            --warning: #f8961e;
            --success: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --white: #ffffff;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: var(--dark);
        }
        
        .reorder-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }
        
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .page-title {
            font-size: 1.8rem;
            color: var(--dark);
            margin: 0;
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            padding: 0.6rem 1.2rem;
            background: var(--light);
            color: var(--dark);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        .btn-back:hover {
            background: rgba(0,0,0,0.05);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .alert-danger {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        
        .order-summary {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--light);
            border-radius: 0.5rem;
        }
        
        .order-items {
            margin-bottom: 2rem;
        }
        
        .order-item {
            display: flex;
            padding: 1rem;
            margin-bottom: 1rem;
            background: var(--white);
            border-radius: 0.5rem;
            border: 1px solid rgba(0,0,0,0.05);
            align-items: center;
        }
        
        .order-item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            margin-right: 1.5rem;
            border-radius: 0.5rem;
        }
        
        .order-item-details {
            flex-grow: 1;
        }
        
        .order-item-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .order-item-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .order-item-price {
            font-weight: 600;
            color: var(--primary);
        }
        
        .stock-info {
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .in-stock {
            color: var(--success);
        }
        
        .out-of-stock {
            color: var(--danger);
        }
        
        .reorder-form {
            margin-top: 2rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: rgba(67, 97, 238, 0.1);
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .order-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-item-img {
                margin-bottom: 1rem;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<div class="reorder-container">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-redo"></i> Reorder #<?= htmlspecialchars($order_id) ?></h1>
        <a href="orders.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>
    
    <?php if(!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($order) && empty($error_message)): ?>
        <div class="order-summary">
            <h3>Original Order Summary</h3>
            <p>Placed on <?= date('F j, Y', strtotime($order['placed_on'])) ?></p>
            <p>Total: <strong>KSh <?= number_format($order['total_price'] + $order['delivery_fee'] - $order['discount'], 2) ?></strong></p>
            <p>Items: <?= htmlspecialchars($order['item_count']) ?></p>
        </div>
        
        <div class="order-items">
            <h3>Items in this Order</h3>
            
            <?php foreach($order_items as $item): ?>
                <div class="order-item">
                    <img src="uploaded_img/<?= htmlspecialchars($item['image'] ?? 'default_product.jpg') ?>" 
                         alt="<?= htmlspecialchars($item['name']) ?>" 
                         class="order-item-img"
                         onerror="this.src='uploaded_img/default_product.jpg'">
                    
                    <div class="order-item-details">
                        <h4 class="order-item-name"><?= htmlspecialchars($item['name']) ?></h4>
                        <div class="order-item-meta">
                            <span>Quantity: <?= htmlspecialchars($item['quantity']) ?></span>
                            <span>Price: KSh <?= number_format($item['price'], 2) ?></span>
                            <?php if(!empty($item['size'])): ?>
                                <span>Size: <?= htmlspecialchars($item['size']) ?></span>
                            <?php endif; ?>
                            <?php if(!empty($item['color'])): ?>
                                <span>Color: <?= htmlspecialchars($item['color']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="stock-info <?= ($item['stock'] > 0) ? 'in-stock' : 'out-of-stock' ?>">
                            <i class="fas fa-<?= ($item['stock'] > 0) ? 'check' : 'times' ?>"></i>
                            <?= ($item['stock'] > 0) ? 'In stock' : 'Out of stock' ?>
                        </div>
                    </div>
                    
                    <div class="order-item-price">
                        KSh <?= number_format($item['price'] * $item['quantity'], 2) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <form method="post" class="reorder-form">
            <h3>Reorder Options</h3>
            <p>This will create a new order with the same items and delivery information.</p>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Confirm Reorder
                </button>
                <a href="orders.php" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>