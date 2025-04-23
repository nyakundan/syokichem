<?php
include 'components/connect.php';
session_start();

$tracking_error = '';
$order = [];
$order_items = [];
$user_orders = [];

// Initialize default order values based on your schema
$default_order = [
    'id' => 0,
    'user_id' => 0,
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'payment_method' => '',
    'total_products' => '',
    'total_price' => 0,
    'prescription_id' => null,
    'order_status' => 'pending',
    'processing_start' => null,
    'shipped_date' => null,
    'delivered_date' => null,
    'placed_on' => date('Y-m-d H:i:s'),
    'email_sent' => 0,
    'tracking_number' => null,
    'cancellation_reason' => null,
    'delivery_notes' => null,
    'delivery_fee' => 0
];

// For logged-in users
if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    try {
        // Get all user orders with proper error handling
        $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE user_id = ? ORDER BY placed_on DESC");
        $select_orders->execute([$user_id]);
        $user_orders = $select_orders->fetchAll(PDO::FETCH_ASSOC);
        
        // If specific order requested
        if(isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
            $order_id = (int)$_GET['order_id'];
            
            // Verify order belongs to user
            $select_order = $conn->prepare("SELECT * FROM `orders` WHERE id = ? AND user_id = ?");
            $select_order->execute([$order_id, $user_id]);
            $order = $select_order->fetch(PDO::FETCH_ASSOC);
            
            if($order) {
                // Get order items
                $select_items = $conn->prepare("SELECT oi.*, p.name, p.image_01 as image 
                                              FROM `order_items` oi 
                                              LEFT JOIN `products` p ON oi.product_id = p.id 
                                              WHERE oi.order_id = ?");
                $select_items->execute([$order_id]);
                $order_items = $select_items->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $tracking_error = 'Order not found or does not belong to your account.';
            }
        }
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $tracking_error = 'Error retrieving your orders. Please try again later.';
    }
} 
// For guests
elseif(isset($_POST['track_order'])) {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';
    
    if(empty($order_id) || empty($email)) {
        $tracking_error = 'Please enter both order number and email address';
    } else {
        try {
            // Search for order
            $select_order = $conn->prepare("SELECT * FROM `orders` WHERE id = ? AND email = ?");
            $select_order->execute([$order_id, $email]);
            $order = $select_order->fetch(PDO::FETCH_ASSOC);
            
            if($order) {
                // Get order items
                $select_items = $conn->prepare("SELECT oi.*, p.name, p.image_01 as image 
                                              FROM `order_items` oi 
                                              LEFT JOIN `products` p ON oi.product_id = p.id 
                                              WHERE oi.order_id = ?");
                $select_items->execute([$order_id]);
                $order_items = $select_items->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $tracking_error = 'No order found with that number and email combination';
            }
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $tracking_error = 'Error retrieving order information. Please try again later.';
        }
    }
}

// Merge with default values to ensure all fields exist
if(!empty($order)) {
    $order = array_merge($default_order, $order);
} else {
    $order = $default_order;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($_SESSION['user_id']) ? 'Your Orders' : 'Track Your Order' ?> - SYOKICHEM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* [Previous CSS styles remain exactly the same] */

        :root {
            --primary: #006837;
            --primary-light: #4CAF50;
            --primary-dark: #004d29;
            --secondary: #FFC107;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --danger: #dc3545;
            --warning: #fd7e14;
            --success: #28a745;
            --info: #17a2b8;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header Section */
        .tracking-header {
            text-align: center;
            padding: 3rem 0 2rem;
            background: linear-gradient(135deg, rgba(0, 104, 55, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .tracking-header h1 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .tracking-header p {
            color: var(--gray);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .tracking-header .icon {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            display: inline-block;
            background: rgba(0, 104, 55, 0.1);
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
        }
        
        /* Tracking Form */
        .tracking-form-container {
            max-width: 600px;
            margin: 0 auto 3rem;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }
        
        .tracking-form {
            display: grid;
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1.2rem;
            font-size: 1rem;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            transition: all 0.3s ease;
            background-color: var(--light);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 104, 55, 0.1);
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 104, 55, 0.2);
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        /* Error Message */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        
        .alert-success {
            background-color: #d4edda;
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .alert i {
            font-size: 1.2rem;
        }
        
        /* Orders List */
        .orders-section {
            margin-bottom: 3rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light-gray);
        }
        
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .order-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid var(--light-gray);
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .order-card-header {
            background-color: var(--primary);
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-id {
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .order-date {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .order-card-body {
            padding: 1.5rem;
        }
        
        .order-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        
        .meta-label {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 0.25rem;
        }
        
        .meta-value {
            font-weight: 600;
            color: var(--dark);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
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
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .order-total {
            font-size: 1.1rem;
            text-align: right;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--light-gray);
        }
        
        .order-total span {
            font-weight: 700;
            color: var(--primary);
        }
        
        .order-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        
        /* Order Details */
        .order-details-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 3rem;
        }
        
        .order-details-header {
            background-color: var(--primary);
            color: white;
            padding: 1.5rem;
        }
        
        .order-details-header h2 {
            margin-bottom: 0.5rem;
        }
        
        .order-details-body {
            padding: 2rem;
        }
        
        .timeline {
            position: relative;
            padding-left: 2.5rem;
            margin: 2rem 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: var(--light-gray);
        }
        
        .timeline-step {
            position: relative;
            padding-bottom: 2rem;
        }
        
        .timeline-step:last-child {
            padding-bottom: 0;
        }
        
        .timeline-icon {
            position: absolute;
            left: -2.5rem;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            z-index: 1;
        }
        
        .timeline-step.active .timeline-icon {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .timeline-step.completed .timeline-icon {
            background: var(--primary-light);
            color: white;
            border-color: var(--primary-light);
        }
        
        .timeline-content {
            padding: 1rem;
            background: var(--light);
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .timeline-date {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 0.5rem;
        }
        
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }
        
        .order-items-table th {
            text-align: left;
            padding: 0.75rem;
            background: var(--light);
            color: var(--dark);
            font-weight: 600;
            border-bottom: 2px solid var(--light-gray);
        }
        
        .order-items-table td {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: top;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            object-fit: cover;
        }
        
        .item-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .item-meta {
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-bold {
            font-weight: 700;
        }
        
        .summary-card {
            background: var(--light);
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.1rem;
            padding-top: 1rem;
            color: var(--primary);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state-icon {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: var(--gray);
            margin-bottom: 1.5rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }
            
            .order-meta {
                grid-template-columns: 1fr;
            }
            
            .tracking-form-container {
                padding: 1.5rem;
            }
            
            .order-details-body {
                padding: 1.5rem;
            }
            
            .order-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/user_header.php'; ?>
    
    <div class="container">
        <section class="tracking-header">
            <div class="icon">
                <i class="fas fa-truck"></i>
            </div>
            <h1><?= isset($_SESSION['user_id']) ? 'Your Orders' : 'Track Your Order' ?></h1>
            <p><?= isset($_SESSION['user_id']) ? 'View and manage all your recent orders' : 'Enter your order details to check status' ?></p>
        </section>
        
        <?php if($tracking_error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($tracking_error) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if(!isset($_SESSION['user_id'])): ?>
            <section class="tracking-form-container">
                <form class="tracking-form" method="post">
                    <div class="form-group">
                        <label for="order_id" class="form-label">Order Number</label>
                        <input type="text" id="order_id" name="order_id" class="form-control" 
                               placeholder="Enter your order number" required
                               value="<?= isset($_POST['order_id']) ? htmlspecialchars($_POST['order_id']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="Enter the email used for ordering" required
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                    
                    <button type="submit" name="track_order" class="btn btn-primary">
                        <i class="fas fa-search"></i> Track Order
                    </button>
                </form>
            </section>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['user_id'])): ?>
            <section class="orders-section">
                <h2 class="section-title">Recent Orders</h2>
                
                <?php if(!empty($user_orders)): ?>
                    <div class="orders-grid">
                        <?php foreach($user_orders as $user_order): 
                            $user_order = array_merge($default_order, $user_order);
                        ?>
                            <div class="order-card">
                                <div class="order-card-header">
                                    <span class="order-id">Order #<?= htmlspecialchars($user_order['id']) ?></span>
                                    <span class="order-date"><?= date('M j, Y', strtotime($user_order['placed_on'])) ?></span>
                                </div>
                                
                                <div class="order-card-body">
                                    <div class="order-meta">
                                        <div class="meta-item">
                                            <span class="meta-label">Status</span>
                                            <span class="status-badge status-<?= htmlspecialchars($user_order['order_status']) ?>">
                                                <?= ucfirst(htmlspecialchars($user_order['order_status'])) ?>
                                            </span>
                                        </div>
                                        
                                        <div class="meta-item">
                                            <span class="meta-label">Items</span>
                                            <span class="meta-value">
                                                <?php
                                                try {
                                                    $count_items = $conn->prepare("SELECT COUNT(*) FROM `order_items` WHERE order_id = ?");
                                                    $count_items->execute([$user_order['id']]);
                                                    echo htmlspecialchars($count_items->fetchColumn());
                                                } catch(PDOException $e) {
                                                    echo "N/A";
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="order-total">
                                        Total: <span>KES <?= number_format($user_order['total_price'] + $user_order['delivery_fee'], 2) ?></span>
                                    </div>
                                    
                                    <div class="order-actions">
                                        <a href="order_tracking.php?order_id=<?= $user_order['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        
                                        <?php if($user_order['order_status'] == 'pending' || $user_order['order_status'] == 'processing'): ?>
                                            <a href="contact.php?order=<?= $user_order['id'] ?>" class="btn btn-outline btn-sm">
                                                <i class="fas fa-question-circle"></i> Help
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-shopping-basket"></i>
                        </div>
                        <h3>No Orders Yet</h3>
                        <p>You haven't placed any orders with us yet. Start shopping to discover our products.</p>
                        <a href="shop.php" class="btn btn-primary">
                            <i class="fas fa-store"></i> Browse Products
                        </a>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
        
        <?php if(!empty($order) && $order['id'] != 0): ?>
            <section class="order-details-container">
                <div class="order-details-header">
                    <h2>Order #<?= htmlspecialchars($order['id']) ?></h2>
                    <p>Placed on <?= date('F j, Y \a\t g:i a', strtotime($order['placed_on'])) ?></p>
                </div>
                
                <div class="order-details-body">
                    <h3>Order Status</h3>
                    
                    <div class="timeline">
                        <?php 
                        $statuses = [
                            'pending' => ['icon' => 'far fa-clock', 'label' => 'Order Placed'],
                            'processing' => ['icon' => 'fas fa-cog', 'label' => 'Processing'],
                            'shipped' => ['icon' => 'fas fa-truck', 'label' => 'Shipped'],
                            'delivered' => ['icon' => 'fas fa-check', 'label' => 'Delivered'],
                            'cancelled' => ['icon' => 'fas fa-times', 'label' => 'Cancelled']
                        ];
                        
                        $current_status = $order['order_status'];
                        $status_found = false;
                        
                        foreach($statuses as $status => $info): 
                            $step_class = '';
                            
                            if($status == $current_status) {
                                $step_class = 'active';
                                $status_found = true;
                            } elseif($status_found) {
                                $step_class = '';
                            } else {
                                $step_class = 'completed';
                            }
                        ?>
                        <div class="timeline-step <?= $step_class ?>">
                            <div class="timeline-icon">
                                <i class="<?= $info['icon'] ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <h4><?= $info['label'] ?></h4>
                                <?php if($status == 'pending'): ?>
                                    <p>We've received your order</p>
                                    <div class="timeline-date"><?= date('M j, g:i a', strtotime($order['placed_on'])) ?></div>
                                <?php elseif($status == 'processing' && $current_status == 'processing'): ?>
                                    <p>Your order is being prepared</p>
                                    <?php if(!empty($order['processing_start'])): ?>
                                        <div class="timeline-date">Started on <?= date('M j', strtotime($order['processing_start'])) ?></div>
                                    <?php endif; ?>
                                <?php elseif($status == 'shipped' && $current_status == 'shipped'): ?>
                                    <p>Your order is on the way</p>
                                    <?php if(!empty($order['tracking_number'])): ?>
                                        <p>Tracking Number: <strong><?= htmlspecialchars($order['tracking_number']) ?></strong></p>
                                    <?php endif; ?>
                                    <?php if(!empty($order['shipped_date'])): ?>
                                        <div class="timeline-date">Shipped on <?= date('M j', strtotime($order['shipped_date'])) ?></div>
                                    <?php endif; ?>
                                <?php elseif($status == 'delivered' && $current_status == 'delivered'): ?>
                                    <p>Your order has been delivered</p>
                                    <?php if(!empty($order['delivered_date'])): ?>
                                        <div class="timeline-date">Delivered on <?= date('M j', strtotime($order['delivered_date'])) ?></div>
                                    <?php endif; ?>
                                <?php elseif($status == 'cancelled' && $current_status == 'cancelled'): ?>
                                    <p>Your order has been cancelled</p>
                                    <?php if(!empty($order['cancellation_reason'])): ?>
                                        <p>Reason: <?= htmlspecialchars($order['cancellation_reason']) ?></p>
                                    <?php endif; ?>
                                    <div class="timeline-date">Cancelled on <?= date('M j', strtotime($order['placed_on'])) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <h3>Order Details</h3>
                    
                    <div class="customer-details">
                        <div class="detail-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div>
                                <h4>Customer Information</h4>
                                <p><strong>Name:</strong> <?= htmlspecialchars($order['name']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                                <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                            </div>
                            
                            <div>
                                <h4>Delivery Information</h4>
                                <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
                                <?php if(!empty($order['delivery_notes'])): ?>
                                    <p><strong>Delivery Notes:</strong> <?= htmlspecialchars($order['delivery_notes']) ?></p>
                                <?php endif; ?>
                                <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if(!empty($order['prescription_id'])): ?>
                        <div style="margin-top: 1.5rem;">
                            <h4>Prescription Information</h4>
                            <p>This order includes prescription items. Please ensure you have your prescription available when receiving delivery.</p>
                        </div>
                    <?php endif; ?>
                    
                    <h3 style="margin-top: 2rem;">Order Items</h3>
                    
                    <table class="order-items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <img src="uploaded_img/<?= htmlspecialchars($item['image'] ?? 'default_product.jpg') ?>" 
                                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                                 class="item-image"
                                                 onerror="this.src='uploaded_img/default_product.jpg'">
                                            <div>
                                                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                                <?php if(!empty($item['size'])): ?>
                                                    <div class="item-meta">Size: <?= htmlspecialchars($item['size']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>KES <?= number_format($item['price'], 2) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td class="text-right">KES <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="summary-card">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>KES <?= number_format($order['total_price'], 2) ?></span>
                        </div>
                        
                        <div class="summary-row">
    <span>Delivery Fee:</span>
    <span>KES <?= number_format($order['delivery_fee'] ?? 0.00, 2) ?></span>
</div>
                        
                        <div class="summary-row">
                            <span>Total:</span>
                            <span class="text-bold">KES <?= number_format($order['total_price'] + $order['delivery_fee'], 2) ?></span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 2rem; text-align: center;">
                        <p>Need help with your order?</p>
                        <a href="contact.php?order=<?= $order['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-headset"></i> Contact Customer Support
                        </a>
                        
                        <?php if($order['order_status'] == 'pending' || $order['order_status'] == 'processing'): ?>
                            <a href="cancel_order.php?id=<?= $order['id'] ?>" class="btn btn-danger" style="margin-left: 1rem;">
                                <i class="fas fa-times"></i> Cancel Order
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
    
    <?php include 'components/footer.php'; ?>
</body>
</html>