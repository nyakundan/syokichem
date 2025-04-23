<?php
// Start session and check admin login
//require 'C:/xampp/htdocs/ecommerce website/admin/components/connect.php';
//require 'C:/xampp/htdocs/ecommerce website/admin/includes/auth.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';


// Set page title
$page_title = "Edit Order";

// Initialize variables
$order = [];
$error_message = '';
$success_message = '';
$status_options = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    $_SESSION['error_message'] = "Invalid order ID.";
    header("Location: list.php");
    exit();
}

// Add additional_notes column if it doesn't exist
try {
    $check_column = $conn->query("SHOW COLUMNS FROM orders LIKE 'additional_notes'");
    if ($check_column->rowCount() == 0) {
        $conn->exec("ALTER TABLE orders ADD COLUMN additional_notes TEXT DEFAULT NULL AFTER order_status");
    }
} catch (PDOException $e) {
    error_log("Error adding additional_notes column: " . $e->getMessage());
}

// Fetch order details
try {
    // First check if the orders table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'orders'");
    if ($table_check->rowCount() == 0) {
        throw new PDOException("Orders table does not exist");
    }

    // Then check if the order exists
    $stmt = $conn->prepare("SELECT 
                            o.id, 
                            o.user_id,
                            o.name as customer_name,
                            o.email,
                            o.phone,
                            o.address,
                            o.payment_method,
                            o.total_products,
                            o.total_price as total_amount,
                            o.order_status as status,
                            o.placed_on as order_date,
                            o.additional_notes
                          FROM orders o 
                          WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $_SESSION['error_message'] = "Order #$order_id not found.";
        header("Location: list.php");
        exit();
    }

} catch (PDOException $e) {
    error_log("Database error in edit.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    header("Location: list.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = trim($_POST['customer_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $payment_method = trim($_POST['payment_method']);
    $status = trim($_POST['status']);
    $notes = trim($_POST['notes']);

    // Validate inputs
    if (empty($customer_name)) {
        $error_message = "Customer name is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (empty($phone)) {
        $error_message = "Phone number is required.";
    } elseif (empty($address)) {
        $error_message = "Address is required.";
    } elseif (!in_array($status, $status_options)) {
        $error_message = "Invalid order status.";
    } else {
        try {
            // Update order in database
            $update_stmt = $conn->prepare("UPDATE orders SET 
                                        name = ?,
                                        email = ?,
                                        phone = ?,
                                        address = ?,
                                        payment_method = ?,
                                        order_status = ?,
                                        additional_notes = ?
                                      WHERE id = ?");
            
            $update_stmt->execute([
                $customer_name,
                $email,
                $phone,
                $address,
                $payment_method,
                $status,
                $notes,
                $order_id
            ]);

            $success_message = "Order updated successfully!";
            // Refresh order data
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Database error in update: " . $e->getMessage());
            $error_message = "Unable to update order: " . $e->getMessage();
        }
    }
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
        :root {
            --main-color: #0eb582;
            --light-bg: #f0fdfa;
            --dark-color: #1f2b38;
            --light-color: #f5f5f5;
            --text-color: #444;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background-color: var(--main-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        
        .status-badge {
            min-width: 100px;
            display: inline-block;
            text-align: center;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .badge-pending { background-color: #ffc107; color: #212529; }
        .badge-processing { background-color: #0dcaf0; color: #fff; }
        .badge-shipped { background-color: #0d6efd; color: #fff; }
        .badge-delivered { background-color: #198754; color: #fff; }
        .badge-cancelled { background-color: #dc3545; color: #fff; }
        
        .btn-main {
            background-color: var(--main-color);
            color: white;
            border: none;
        }
        
        .btn-main:hover {
            background-color: var(--dark-color);
            color: white;
        }
        
        .order-details {
            background-color: var(--light-bg);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-color);
        }
    </style>
</head>
<body>
    <?php //include 'C:/xampp/htdocs/ecommerce website/admin/includes/admin_header.php'; 

    include __DIR__ . '/../includes/admin_header.php';
    
    ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-edit"></i> Edit Order #<?= $order_id ?></h2>
            </div>
            <div class="col-md-6 text-end">
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="customer_name" class="form-label">Customer Name</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                           value="<?= htmlspecialchars($order['customer_name']) ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($order['email']) ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($order['phone']) ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="payment_method" class="form-label">Payment Method</label>
                                    <select class="form-select" id="payment_method" name="payment_method" required>
                                        <option value="cash_on_delivery" <?= $order['payment_method'] == 'cash_on_delivery' ? 'selected' : '' ?>>Cash on Delivery</option>
                                        <option value="credit_card" <?= $order['payment_method'] == 'credit_card' ? 'selected' : '' ?>>Credit Card</option>
                                        <option value="mpesa" <?= $order['payment_method'] == 'mpesa' ? 'selected' : '' ?>>M-Pesa</option>
                                        <option value="paypal" <?= $order['payment_method'] == 'paypal' ? 'selected' : '' ?>>PayPal</option>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" required><?= htmlspecialchars($order['address']) ?></textarea>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Order Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <?php foreach ($status_options as $option): ?>
                                            <option value="<?= $option ?>" <?= $order['status'] == $option ? 'selected' : '' ?>>
                                                <?= ucfirst($option) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="notes" class="form-label">Additional Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="1"><?= htmlspecialchars($order['additional_notes'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-main px-4 py-2">
                                        <i class="fas fa-save"></i> Update Order
                                    </button>
                                    <a href="list.php" class="btn btn-outline-secondary ms-2 px-4 py-2">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Order ID:</span>
                            <strong>#<?= $order_id ?></strong>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Date:</span>
                            <strong><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></strong>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Current Status:</span>
                            <span class="status-badge badge-<?= $order['status'] ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Products:</span>
                            <strong><?= $order['total_products'] ?></strong>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Payment Method:</span>
                            <strong><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></strong>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total Amount:</span>
                            <strong>KSh <?= number_format((float)$order['total_amount'], 2) ?></strong>
                        </div>
                        
                        <hr>
                        
                        <div class="mt-3">
                            <a href="view.php?id=<?= $order_id ?>" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <a href="print.php?id=<?= $order_id ?>" class="btn btn-outline-secondary w-100" target="_blank">
                                <i class="fas fa-print"></i> Print Invoice
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php //include 'C:/xampp/htdocs/ecommerce website/admin/includes/admin_footer.php';
    
    include __DIR__ . '/../includes/admin_footer.php';
    
     ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update status badge when dropdown changes
        document.getElementById('status').addEventListener('change', function() {
            const status = this.value;
            const badge = document.querySelector('.status-badge');
            
            // Remove all classes
            badge.className = 'status-badge';
            
            // Add the appropriate class
            badge.classList.add(`badge-${status}`);
            badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        });
        
        // Form submission confirmation
        document.querySelector('form').addEventListener('submit', function(e) {
            const status = document.getElementById('status').value;
            const currentStatus = '<?= $order['status'] ?>';
            
            if (status !== currentStatus) {
                if (!confirm(`Are you sure you want to change the order status from ${currentStatus} to ${status}?`)) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>