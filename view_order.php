<?php
include 'components/connect.php';
session_start();

// Redirect if not logged in
if(!isset($_SESSION['user_id'])){
    header('location:user_login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Default order values
$order = [
    'id' => $order_id,
    'name' => 'N/A',
    'email' => 'N/A',
    'phone' => 'N/A',
    'address' => 'Address not specified',
    'payment_method' => 'Not specified',
    'order_status' => 'pending',
    'total_price' => 0.00,
    'delivery_fee' => 0.00,
    'discount' => 0.00,
    'placed_on' => date('Y-m-d H:i:s'),
    'tracking_number' => 'Not available',
    'delivery_notes' => 'None'
];

$order_items = [];

// Fetch order details
if($order_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $user_id]);
        
        if($stmt->rowCount() > 0) {
            $order = array_merge($order, $stmt->fetch(PDO::FETCH_ASSOC));
            
            // Fetch order items
            $stmt = $conn->prepare("SELECT oi.*, p.image_01 as image, p.name as product_name 
                                  FROM order_items oi 
                                  LEFT JOIN products p ON oi.product_id = p.id 
                                  WHERE oi.order_id = ?");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['error'] = 'Order not found';
            header("Location: orders.php");
            exit();
        }
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to load order details';
        header("Location: orders.php");
        exit();
    }
}

// Calculate total amount
$subtotal = (float)$order['total_price'];
$delivery_fee = (float)$order['delivery_fee'];
$discount = (float)$order['discount'];
$total_amount = $subtotal + $delivery_fee - $discount;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= htmlspecialchars($order_id) ?> - <?= htmlspecialchars($order['name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary: #0eb582;
            --danger: #dc3545;
            --light-gray: #f8f9fa;
            --dark-gray: #6c757d;
            --border: 1px solid #dee2e6;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #212529;
            background-color: #f5f5f5;
        }
        
        .order-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }
        
        .order-header {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: var(--border);
        }
        
        .order-header h1 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .meta-box {
            padding: 1.5rem;
            background: var(--light-gray);
            border-radius: 0.5rem;
            border-left: 4px solid var(--primary);
        }
        
        .meta-box h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .order-items {
            margin: 2rem 0;
        }
        
        .order-item {
            display: flex;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #fff;
            border-radius: 0.5rem;
            border: var(--border);
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .order-item:hover {
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }
        
        .order-item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            margin-right: 1.5rem;
            border-radius: 0.5rem;
            border: var(--border);
        }
        
        .order-item-details {
            flex-grow: 1;
        }
        
        .order-item-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .order-item-price {
            font-weight: 600;
            color: var(--primary);
        }
        
        .order-summary {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: var(--border);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding: 0.5rem 0;
        }
        
        .summary-total {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary);
            border-top: var(--border);
            padding-top: 1rem;
            margin-top: 0.5rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.35rem 1rem;
            border-radius: 50rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            margin-right: 0.75rem;
            margin-top: 1rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: 1px solid var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #0da271;
            border-color: #0da271;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
            border: 1px solid var(--danger);
        }
        
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        .delivery-info {
            margin-top: 2rem;
            padding: 1.5rem;
            background: var(--light-gray);
            border-radius: 0.5rem;
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
            
            .order-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<div class="order-container">
    <div class="order-header">
        <h1><i class="fas fa-receipt"></i> Order #<?= htmlspecialchars($order_id) ?></h1>
        <p>Placed on <?= date('F j, Y \a\t g:i A', strtotime($order['placed_on'])) ?></p>
        
        <div class="order-meta">
            <div class="meta-box">
                <h3>Customer Information</h3>
                <p><strong>Name:</strong> <?= htmlspecialchars($order['name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
            </div>
            
            <div class="meta-box">
                <h3>Delivery Information</h3>
                <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($order['address'])) ?></p>
                <p><strong>Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
            </div>
            
            <div class="meta-box">
                <h3>Order Status</h3>
                <span class="status-badge status-<?= htmlspecialchars($order['order_status']) ?>">
                    <?= ucfirst(htmlspecialchars($order['order_status'])) ?>
                </span>
                
                <?php if(!empty($order['tracking_number'])): ?>
                    <p style="margin-top: 0.5rem;"><strong>Tracking #:</strong> <?= htmlspecialchars($order['tracking_number']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <h2><i class="fas fa-box-open"></i> Order Items</h2>
    
    <?php if(empty($order_items)): ?>
        <div class="alert alert-info">No items found in this order.</div>
    <?php else: ?>
        <?php foreach($order_items as $item): ?>
        <div class="order-item">
            <img src="uploaded_img/<?= htmlspecialchars($item['image'] ?? 'default_product.jpg') ?>" 
                 alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>"
                 class="order-item-img"
                 onerror="this.src='uploaded_img/default_product.jpg'">
            
            <div class="order-item-details">
                <h3 class="order-item-name"><?= htmlspecialchars($item['product_name'] ?? 'Unknown Product') ?></h3>
                <p><strong>Quantity:</strong> <?= htmlspecialchars($item['quantity'] ?? 1) ?></p>
                <p><strong>Price:</strong> KSh <?= number_format($item['price'] ?? 0, 2) ?></p>
            </div>
            
            <div class="order-item-price">
                KSh <?= number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <div class="order-summary">
        <h2><i class="fas fa-file-invoice-dollar"></i> Order Summary</h2>
        
        <div class="summary-row">
            <span>Subtotal:</span>
            <span>KSh <?= number_format($subtotal, 2) ?></span>
        </div>
        
        <div class="summary-row">
            <span>Delivery Fee:</span>
            <span>KSh <?= number_format($delivery_fee, 2) ?></span>
        </div>
        
        <div class="summary-row">
            <span>Discount:</span>
            <span>- KSh <?= number_format($discount, 2) ?></span>
        </div>
        
        <div class="summary-row summary-total">
            <span>Total Amount:</span>
            <span>KSh <?= number_format($total_amount, 2) ?></span>
        </div>
    </div>
    
    <?php if(!empty($order['delivery_notes']) && $order['delivery_notes'] !== 'None'): ?>
    <div class="delivery-info">
        <h3><i class="fas fa-truck"></i> Delivery Notes</h3>
        <p><?= nl2br(htmlspecialchars($order['delivery_notes'])) ?></p>
    </div>
    <?php endif; ?>
    
    <div>
        <a href="orders.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Orders</a>
        
        <?php if($order['order_status'] === 'pending'): ?>
            <a href="cancel_order.php?id=<?= $order_id ?>" class="btn btn-danger">
                <i class="fas fa-times"></i> Cancel Order
            </a>
        <?php endif; ?>
    </div>
</div>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>