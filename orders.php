<?php
// At the VERY TOP of your file (before any HTML output)
session_start();
include 'components/connect.php';

// Add missing columns to prescriptions table if they don't exist
try {
    $alter_table = $conn->prepare("
        ALTER TABLE `prescriptions` 
        ADD COLUMN IF NOT EXISTS `payment_status` VARCHAR(20) DEFAULT 'pending',
        ADD COLUMN IF NOT EXISTS `order_status` VARCHAR(20) DEFAULT 'pending',
        ADD COLUMN IF NOT EXISTS `payment_method` VARCHAR(50) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `total_price` DECIMAL(10,2) DEFAULT 0.00,
        ADD COLUMN IF NOT EXISTS `delivery_fee` DECIMAL(10,2) DEFAULT 0.00,
        ADD COLUMN IF NOT EXISTS `discount` DECIMAL(10,2) DEFAULT 0.00
    ");
    $alter_table->execute();
} catch(PDOException $e) {
    // If the columns already exist, we can ignore the error
    if($e->getCode() != '42S21') { // 42S21 is the error code for duplicate column
        throw $e;
    }
}

// Initialize variables
$user_id = '';
$page_title = 'My Orders';
$show_back_button = false;
$order_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Set page title based on order type
if($order_type == 'prescription') {
    $page_title = 'My Prescription Orders';
} elseif($order_type == 'regular') {
    $page_title = 'My Regular Orders';
}

// Check if user is logged in
if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | Your Store Name</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #3f37c9;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --white: #ffffff;
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
            font-size: 2rem;
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
            transform: translateY(-2px);
        }

        .btn-back i {
            margin-right: 0.5rem;
        }

        .orders-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem 3rem;
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .order-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 2rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
        }

        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .order-id {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .order-date {
            color: var(--gray);
            font-size: 0.9rem;
            background: rgba(0,0,0,0.03);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
        }

        .order-status {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 1.2rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .status-pending {
            background: rgba(247, 37, 133, 0.1);
            color: var(--danger);
        }

        .status-completed {
            background: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }

        .status-processing {
            background: rgba(248, 150, 30, 0.1);
            color: var(--warning);
        }

        .status-shipped {
            background: rgba(58, 12, 163, 0.1);
            color: var(--secondary);
        }

        .order-details {
            margin-bottom: 1.5rem;
        }

        .order-detail {
            display: flex;
            margin-bottom: 0.8rem;
        }

        .order-detail-label {
            font-weight: 600;
            color: var(--dark);
            min-width: 100px;
            font-size: 0.9rem;
        }

        .order-detail-value {
            color: var(--gray);
            font-size: 0.9rem;
            flex: 1;
        }

        .order-products {
            margin: 1.5rem 0;
            padding: 1rem;
            background: rgba(0,0,0,0.02);
            border-radius: 8px;
            border-left: 3px solid var(--primary);
        }

        .order-products strong {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .order-products p {
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 0;
        }

        .order-total {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark);
            text-align: right;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .order-total span {
            color: var(--primary);
        }

        .order-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-track, .btn-reorder {
            flex: 1;
            text-align: center;
            padding: 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-track {
            background: var(--primary);
            color: var(--white);
            border: 2px solid var(--primary);
        }

        .btn-track:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        .btn-reorder {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-reorder:hover {
            background: rgba(67, 97, 238, 0.1);
            transform: translateY(-2px);
        }

        .empty-orders, .login-prompt {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--light);
            border-radius: 12px;
            margin-top: 2rem;
            grid-column: 1 / -1;
        }

        .empty-orders i, .login-prompt i {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }

        .empty-orders h3, .login-prompt h3 {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .empty-orders p, .login-prompt p {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .empty-orders .btn, .login-prompt .btn {
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            background: var(--primary);
            color: var(--white);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .empty-orders .btn:hover, .login-prompt .btn:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }
            
            .order-actions {
                flex-direction: column;
            }
            
            .order-detail {
                flex-direction: column;
                gap: 0.3rem;
            }
            
            .order-detail-label, .order-detail-value,
            .order-products p {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.8rem;
            }
            
            .order-card {
                padding: 1.5rem;
            }
            
            .order-type-tab {
                font-size: 0.9rem;
                padding: 0.8rem 1.2rem;
            }
        }

        .order-type-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--light);
            padding-bottom: 1rem;
        }

        .order-type-tab {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            color: var(--gray);
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .order-type-tab.active {
            color: var(--primary);
            border-color: var(--primary);
            background: rgba(67, 97, 238, 0.1);
        }

        .order-type-tab:hover {
            color: var(--primary);
            background: rgba(67, 97, 238, 0.05);
        }

        .prescription-order {
            border-left: 4px solid var(--success);
        }

        .prescription-order .order-id {
            color: var(--success);
        }

        .prescription-details {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(76, 201, 240, 0.1);
            border-radius: 8px;
        }

        .prescription-details h4 {
            color: var(--success);
            margin-bottom: 0.5rem;
        }

        .prescription-details p {
            margin: 0.3rem 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<div class="orders-container">
    <div class="page-header">
        <h1 class="page-title"><?= htmlspecialchars($page_title) ?></h1>
        <?php if($show_back_button): ?>
            <a href="javascript:history.back()" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        <?php endif; ?>
    </div>

    <div class="order-type-tabs">
        <a href="orders.php?type=all" class="order-type-tab <?= $order_type == 'all' ? 'active' : '' ?>">
            <i class="fas fa-boxes"></i> All Orders
        </a>
        <a href="orders.php?type=regular" class="order-type-tab <?= $order_type == 'regular' ? 'active' : '' ?>">
            <i class="fas fa-shopping-bag"></i> Regular Orders
        </a>
        <a href="orders.php?type=prescription" class="order-type-tab <?= $order_type == 'prescription' ? 'active' : '' ?>">
            <i class="fas fa-prescription-bottle-alt"></i> Prescription Orders
        </a>
    </div>

    <?php
    if(isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // Build the query based on order type
        $query = "";
        $params = [$user_id];
        
        if($order_type == 'all') {
            // Get both regular and prescription orders
            $query = "SELECT 'regular' as order_type, id, placed_on, payment_status, order_status, name, phone, address, payment_method, total_products, total_price, delivery_fee, discount 
                     FROM `orders` WHERE user_id = ?
                     UNION ALL
                     SELECT 'prescription' as order_type, id, created_at as placed_on, 'completed' as payment_status, status as order_status, recipient_name as name, recipient_phone as phone, delivery_address as address, payment_method, 'Prescription' as total_products, 0 as total_price, 0 as delivery_fee, 0 as discount 
                     FROM `prescriptions` WHERE user_id = ?
                     ORDER BY placed_on DESC";
            $params = [$user_id, $user_id];
        } elseif($order_type == 'regular') {
            $query = "SELECT 'regular' as order_type, id, placed_on, payment_status, order_status, name, phone, address, payment_method, total_products, total_price, delivery_fee, discount 
                     FROM `orders` WHERE user_id = ? ORDER BY placed_on DESC";
            $params = [$user_id];
        } elseif($order_type == 'prescription') {
            $query = "SELECT 'prescription' as order_type, id, created_at as placed_on, status as order_status, recipient_name as name, recipient_phone as phone, delivery_address as address, payment_method, doctor_name, patient_name, medical_staff_id 
                     FROM `prescriptions` WHERE user_id = ? ORDER BY created_at DESC";
            $params = [$user_id];
        }
        
        $select_orders = $conn->prepare($query);
        $select_orders->execute($params);

        
        
        if($select_orders->rowCount() > 0) {
            echo '<div class="orders-grid">';
            
            while($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)) {
                $is_prescription = $fetch_orders['order_type'] == 'prescription';
                $order_class = $is_prescription ? 'prescription-order' : '';
                $status_class = 'status-' . strtolower($fetch_orders['order_status']);
    ?>
                <div class="order-card <?= $order_class ?>">
                    <div class="order-card-header">
                        <span class="order-id">
                            <?= $is_prescription ? 'PR#' : '#' ?><?= htmlspecialchars($fetch_orders['id']); ?>
                        </span>
                        <span class="order-date"><?= date('M j, Y', strtotime($fetch_orders['placed_on'])); ?></span>
                    </div>
                    
                    <div class="order-status <?= $status_class; ?>">
                        <?= ucfirst(htmlspecialchars($fetch_orders['order_status'])); ?>
                    </div>
                    
                    <div class="order-details">
                        <div class="order-detail">
                            <span class="order-detail-label">Customer:</span>
                            <span class="order-detail-value"><?= htmlspecialchars($fetch_orders['name'] ?? 'N/A'); ?></span>
                        </div>
                        
                        <div class="order-detail">
                            <span class="order-detail-label">Contact:</span>
                            <span class="order-detail-value"><?= htmlspecialchars($fetch_orders['phone'] ?? 'N/A'); ?></span>
                        </div>
                        
                        <div class="order-detail">
                            <span class="order-detail-label">Delivery:</span>
                            <span class="order-detail-value"><?= htmlspecialchars(truncateText($fetch_orders['address'] ?? 'N/A', 30)); ?></span>
                        </div>
                        
                        <div class="order-detail">
                            <span class="order-detail-label">Payment:</span>
                            <span class="order-detail-value"><?= ucwords(htmlspecialchars($fetch_orders['payment_method'] ?? 'N/A')); ?></span>
                        </div>
                    </div>

                    <?php if($is_prescription): ?>
                        <div class="prescription-details">
                            <h4>Prescription Details</h4>
                            <p><strong>Doctor:</strong> <?= htmlspecialchars($fetch_orders['doctor_name'] ?? 'N/A'); ?></p>
                            <p><strong>Patient:</strong> <?= htmlspecialchars($fetch_orders['patient_name'] ?? 'N/A'); ?></p>
                            <?php if(isset($fetch_orders['medical_staff_id'])): 
                                $select_staff = $conn->prepare("SELECT name, specialization FROM medical_staff WHERE id = ?");
                                $select_staff->execute([$fetch_orders['medical_staff_id']]);
                                $staff = $select_staff->fetch(PDO::FETCH_ASSOC);
                                if($staff): ?>
                                    <p><strong>Assigned Staff:</strong> <?= htmlspecialchars($staff['name']); ?>
                                    <?php if($staff['specialization']): ?>
                                        (<?= htmlspecialchars($staff['specialization']); ?>)
                                    <?php endif; ?>
                                    </p>
                                <?php endif;
                            endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="order-products">
                            <strong>Products Ordered:</strong>
                            <p><?= htmlspecialchars(truncateText($fetch_orders['total_products'] ?? 'N/A', 50)); ?></p>
                        </div>
                        
                        <div class="order-total">
                            Total: <span>KSh <?= number_format($fetch_orders['total_price'] + ($fetch_orders['delivery_fee'] ?? 0) - ($fetch_orders['discount'] ?? 0), 2); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="order-actions">
                        <a href="view_order.php?id=<?= $fetch_orders['id']; ?>&type=<?= $is_prescription ? 'prescription' : 'regular' ?>" class="btn-track">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <?php if(!$is_prescription): ?>
                            <a href="reorder.php?id=<?= $fetch_orders['id']; ?>" class="btn-reorder">
                                <i class="fas fa-redo"></i> Reorder
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
    <?php
            }
            echo '</div>'; // Close orders-grid
        } else {
            // No orders found
    ?>
            <div class="empty-orders">
                <i class="fas fa-box-open"></i>
                <h3>No orders found</h3>
                <p>You haven't placed any <?= $order_type == 'all' ? '' : $order_type ?> orders yet</p>
                <?php if($order_type == 'prescription'): ?>
                    <a href="prescription.php" class="btn"><i class="fas fa-prescription-bottle-alt"></i> Upload Prescription</a>
                <?php else: ?>
                    <a href="shop.php" class="btn"><i class="fas fa-shopping-bag"></i> Start Shopping</a>
                <?php endif; ?>
            </div>
    <?php
        }
    } else {
        // User is not logged in
    ?>
        <div class="login-prompt">
            <i class="fas fa-user-lock"></i>
            <h3>Please login to view your orders</h3>
            <p>Sign in to access your order history and track your purchases</p>
            <a href="user_login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Login Now</a>
        </div>
    <?php
    }
    ?>
</div>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>

<?php
// Helper function to truncate long text
function truncateText($text, $length) {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}
?>