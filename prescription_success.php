<?php
include 'components/connect.php';

// Check if order ID is provided
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch order details if available
$order = null;
if($order_id > 0){
    $select_order = $conn->prepare("SELECT * FROM `prescriptions` WHERE id = ?");
    $select_order->execute([$order_id]);
    $order = $select_order->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Order Confirmed | Syokichem Pharmaceuticals Ltd.</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <style>
      /* Same root variables and base styles as previous files */
      
      .success-container {
         max-width: 800px;
         margin: 4rem auto;
         padding: 3rem;
         background: var(--pure-white);
         border-radius: 12px;
         box-shadow: 0 10px 30px rgba(0,0,0,0.08);
         text-align: center;
      }
      
      .success-icon {
         width: 80px;
         height: 80px;
         background: var(--primary-green);
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         margin: 0 auto 2rem;
      }
      
      .success-icon i {
         font-size: 3.5rem;
         color: var(--pure-white);
      }
      
      .success-title {
         font-size: 2.5rem;
         color: var(--dark-green);
         margin-bottom: 1.5rem;
      }
      
      .success-message {
         font-size: 1.6rem;
         color: var(--text-dark);
         margin-bottom: 3rem;
         line-height: 1.6;
      }
      
      .order-number {
         font-weight: 600;
         color: var(--primary-green);
         font-size: 1.8rem;
         margin-bottom: 2rem;
      }
      
      .next-steps {
         background: var(--light-gray);
         border-radius: 8px;
         padding: 2rem;
         margin-top: 3rem;
         text-align: left;
      }
      
      .next-steps-title {
         font-size: 1.8rem;
         color: var(--dark-green);
         margin-bottom: 1.5rem;
      }
      
      .next-steps-list {
         list-style-type: none;
      }
      
      .next-steps-list li {
         margin-bottom: 1rem;
         padding-left: 2.5rem;
         position: relative;
         font-size: 1.5rem;
      }
      
      .next-steps-list li::before {
         content: '\f00c';
         font-family: 'Font Awesome 6 Free';
         font-weight: 900;
         position: absolute;
         left: 0;
         color: var(--primary-green);
      }
      
      .action-buttons {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 1.5rem;
         margin-top: 3rem;
      }
      
      @media (max-width: 768px) {
         .success-container {
            padding: 2rem;
            margin: 2rem;
         }
         
         .action-buttons {
            grid-template-columns: 1fr;
         }
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<div class="success-container">
   <div class="success-icon">
      <i class="fas fa-check"></i>
   </div>
   
   <h1 class="success-title">Order Confirmed!</h1>
   
   <p class="success-message">
      Thank you for your prescription order. Our pharmacists are reviewing your prescription 
      and will contact you shortly to confirm details and discuss delivery options.
   </p>
   
   <?php if($order): ?>
   <div class="order-number">
      Order Reference: #<?= $order['id'] ?>
   </div>
   <?php endif; ?>
   
   <div class="next-steps">
      <h3 class="next-steps-title">What Happens Next?</h3>
      <ul class="next-steps-list">
         <li>Our pharmacists will review your prescription within 1 business day</li>
         <li>We'll contact you to confirm medication availability</li>
         <li>Your order will be prepared for delivery or pickup</li>
         <li>You'll receive a notification when your order is ready</li>
      </ul>
   </div>
   
   <div class="action-buttons">
      <a href="index.php" class="btn btn-secondary">
         <i class="fas fa-home"></i> Back to Home
      </a>
      <a href="orders.php" class="btn">
         <i class="fas fa-clipboard-list"></i> View My Orders
      </a>
   </div>
</div>

<?php include 'components/footer.php'; ?>
</body>
</html>