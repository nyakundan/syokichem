<?php
include 'components/connect.php';
session_start();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

if(isset($_POST['order'])){
   // Sanitize and validate inputs
   $name = filter_var($_POST['name'], FILTER_DEFAULT);
   $phone = filter_var($_POST['phone'], FILTER_DEFAULT);
   $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
   $payment_method = filter_var($_POST['payment_method'], FILTER_DEFAULT);
   $address_parts = [
      'flat' => filter_var($_POST['flat'], FILTER_DEFAULT),
      'street' => filter_var($_POST['street'], FILTER_DEFAULT),
      'city' => filter_var($_POST['city'], FILTER_DEFAULT),
      'state' => filter_var($_POST['state'], FILTER_DEFAULT),
      'country' => filter_var($_POST['country'], FILTER_DEFAULT),
      'pin_code' => filter_var($_POST['pin_code'], FILTER_DEFAULT)
   ];
   $address = implode(', ', $address_parts);
   $total_products = $_POST['total_products'];
   $total_price = filter_var($_POST['total_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

   if($user_id != ''){
      // Logged in user: use DB cart
      $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
      $check_cart->execute([$user_id]);
      if($check_cart->rowCount() > 0){
         try {
            $insert_order = $conn->prepare("INSERT INTO `orders` 
               (user_id, name, email, phone, address, payment_method, total_products, total_price, order_status) 
               VALUES (?,?,?,?,?,?,?,?,'pending')");
            $insert_order->execute([
               $user_id, $name, $email, $phone, $address, $payment_method, $total_products, $total_price
            ]);
            $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
            $delete_cart->execute([$user_id]);
            $message[] = 'Order placed successfully!';
            header('Location: order_confirmation.php?order_id='.$conn->lastInsertId());
            exit();
         } catch(PDOException $e) {
            $message[] = 'Error placing order: ' . $e->getMessage();
         }
      } else {
         $message[] = 'Your cart is empty';
      }
   } else {
      // Guest: use session cart
      if(isset($_SESSION['guest_cart']) && count($_SESSION['guest_cart']) > 0){
         try {
            $insert_order = $conn->prepare("INSERT INTO `orders` 
               (user_id, name, email, phone, address, payment_method, total_products, total_price, order_status) 
               VALUES (NULL,?,?,?,?,?,?,?,'pending')");
            $insert_order->execute([
               $name, $email, $phone, $address, $payment_method, $total_products, $total_price
            ]);
            unset($_SESSION['guest_cart']);
            $message[] = 'Order placed successfully!';
            header('Location: order_confirmation_guest.php?order_id='.$conn->lastInsertId());
            exit();
         } catch(PDOException $e) {
            $message[] = 'Error placing order: ' . $e->getMessage();
         }
      } else {
         $message[] = 'Your cart is empty';
      }
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Checkout - Syokichem Pharmaceuticals</title>
   <link rel="icon" href="images/favicon.png" type="image/png">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <style>
      /* Your existing CSS styles remain the same */
        
   <!-- Custom CSS -->
   <link rel="stylesheet" href="css/style.css">
   
   <!-- Checkout CSS -->
   <style>
      .checkout-container {
         max-width: 1200px;
         margin: 2rem auto;
         padding: 0 1.5rem;
      }
      
      .checkout-header {
         text-align: center;
         margin-bottom: 3rem;
      }
      
      .checkout-header h1 {
         font-size: 2.5rem;
         color: #2c3e50;
         margin-bottom: 1rem;
      }
      
      .checkout-grid {
         display: grid;
         grid-template-columns: 2fr 1fr;
         gap: 2rem;
      }
      
      @media (max-width: 992px) {
         .checkout-grid {
            grid-template-columns: 1fr;
         }
      }
      
      .order-summary {
         background: #f8f9fa;
         border-radius: 10px;
         padding: 2rem;
         box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      }
      
      .order-summary h2, .checkout-form h2 {
         font-size: 1.8rem;
         color: #2c3e50;
         margin-bottom: 1.5rem;
         padding-bottom: 1rem;
         border-bottom: 1px solid #e0e0e0;
      }
      
      .order-item {
         display: flex;
         justify-content: space-between;
         margin-bottom: 1rem;
         padding-bottom: 1rem;
         border-bottom: 1px solid #eee;
      }
      
      .order-item:last-child {
         border-bottom: none;
      }
      
      .order-total {
         font-size: 1.6rem;
         font-weight: 700;
         color: #e74c3c;
         margin-top: 2rem;
         text-align: right;
      }
      
      .checkout-form {
         background: #fff;
         border-radius: 10px;
         padding: 2rem;
         box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      }
      
      .form-group {
         margin-bottom: 1.5rem;
      }
      
      .form-group label {
         display: block;
         margin-bottom: 0.5rem;
         font-weight: 600;
         color: #2c3e50;
      }
      
      .form-control {
         width: 100%;
         padding: 1rem;
         border: 1px solid #ddd;
         border-radius: 5px;
         font-size: 1.4rem;
      }
      
      .form-control:focus {
         border-color: #3498db;
         outline: none;
         box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
      }
      
      .form-row {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 1.5rem;
      }
      
      .btn-checkout {
         width: 100%;
         padding: 1.2rem;
         background: #3498db;
         color: #fff;
         border: none;
         border-radius: 5px;
         font-size: 1.6rem;
         font-weight: 600;
         cursor: pointer;
         transition: background 0.3s;
      }
      
      .btn-checkout:hover {
         background: #2980b9;
      }
      
      .btn-checkout.disabled {
         background: #bdc3c7;
         cursor: not-allowed;
      }
      
      .payment-methods {
         margin: 2rem 0;
      }
      
      .payment-option {
         display: flex;
         align-items: center;
         margin-bottom: 1rem;
         padding: 1rem;
         border: 1px solid #ddd;
         border-radius: 5px;
         cursor: pointer;
         transition: all 0.3s;
      }
      
      .payment-option:hover {
         border-color: #3498db;
      }
      
      .payment-option input {
         margin-right: 1rem;
      }
      
      .payment-option i {
         margin-right: 1rem;
         font-size: 2rem;
         color: #3498db;
      }
      
      .empty-cart {
         text-align: center;
         padding: 5rem 0;
         grid-column: 1 / -1;
      }
      
      .empty-cart i {
         font-size: 5rem;
         color: #bdc3c7;
         margin-bottom: 2rem;
      }
      
      .empty-cart h3 {
         font-size: 1.8rem;
         color: #7f8c8d;
         margin-bottom: 1.5rem;
      }
      
      @media (max-width: 768px) {
         .checkout-container {
            padding: 0 0.5rem;
         }
         .checkout-header h1 {
            font-size: 2rem;
         }
         .checkout-header p {
            font-size: 1.1rem;
         }
         .checkout-grid {
            grid-template-columns: 1fr;
            gap: 1.2rem;
         }
         .order-summary,
         .checkout-form {
            padding: 1rem;
         }
         .form-row {
            grid-template-columns: 1fr;
            gap: 1rem;
         }
         .form-group label {
            font-size: 1.1rem;
         }
         .form-control {
            font-size: 1.1rem;
            padding: 0.8rem 1rem;
         }
         .btn-checkout {
            font-size: 1.1rem;
            padding: 1.1rem 1rem;
            width: 100%;
         }
         .order-summary h2 {
            font-size: 1.2rem;
         }
         .order-summary ul {
            font-size: 1rem;
         }
         .payment-methods {
            flex-direction: column;
            gap: 0.8rem;
         }
         .payment-option {
            font-size: 1rem;
            padding: 0.8rem;
         }
      }
      
      @media (max-width: 480px) {
         .checkout-header h1 {
            font-size: 1.3rem;
         }
         .order-summary,
         .checkout-form {
            padding: 0.5rem;
         }
         .btn-checkout {
            font-size: 1rem;
            padding: 1rem 0.5rem;
         }
         .form-control {
            font-size: 1rem;
            padding: 0.7rem 0.7rem;
         }
      }
      
      /* Prevent horizontal scroll */
      html, body {
         max-width: 100vw;
         overflow-x: hidden;
      }
   </style>
</head>
<body>
<?php
if (!empty($message)) {
    foreach ($message as $msg) {
        echo '<div class="message" style="background:#ffe0e0;color:#b71c1c;padding:10px 20px;margin:10px 0;border-radius:6px;">'.htmlspecialchars($msg).'</div>';
    }
}
?>

<?php include 'components/user_header.php'; ?>

<section class="checkout-container">
   <div class="checkout-header">
      <h1><i class="fas fa-shopping-bag"></i> Checkout</h1>
      <p>Complete your purchase securely</p>
   </div>

   <?php
   $grand_total = 0;
   $cart_items = [];
   if($user_id != ''){
      $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
      $select_cart->execute([$user_id]);
      $cart_data = $select_cart->fetchAll(PDO::FETCH_ASSOC);
   } else {
      $cart_data = isset($_SESSION['guest_cart']) ? $_SESSION['guest_cart'] : [];
   }
   ?>
   <form action="" method="POST" class="checkout-grid">
      <?php if(count($cart_data) > 0): ?>
         <div class="order-summary">
            <h2><i class="fas fa-receipt"></i> Order Summary</h2>
            <?php foreach($cart_data as $fetch_cart): 
               $grand_total += $fetch_cart['price'] * $fetch_cart['quantity'];
               $cart_items[] = $fetch_cart['name'].' ('.$fetch_cart['quantity'].')';
            ?>
            <div class="order-item">
               <span><?= htmlspecialchars($fetch_cart['name']); ?> (x<?= $fetch_cart['quantity']; ?>)</span>
               <span>KSh <?= number_format($fetch_cart['price'] * $fetch_cart['quantity'], 2); ?></span>
            </div>
            <?php endforeach; ?>
            <div class="cart-summary">
               <div class="summary-row">
                  <span>Subtotal</span>
                  <span id="summary-subtotal">KSh <?= number_format($grand_total, 2); ?></span>
               </div>
               <div class="summary-row">
                  <span>Shipping</span>
                  <span id="summary-shipping">KSh 200.00</span>
               </div>
               <div class="summary-row grand-total">
                  <span>Total</span>
                  <span id="summary-total">KSh <?= number_format($grand_total+200, 2); ?></span>
               </div>
            </div>
            <input type="hidden" name="total_products" value="<?= htmlspecialchars(implode($cart_items)); ?>">
            <input type="hidden" name="total_price" value="<?= htmlspecialchars($grand_total+200); ?>">
         </div>
         <div class="checkout-form">
            <h2><i class="fas fa-user-circle"></i> Customer Information</h2>
            
            <div class="form-group">
               <label for="name">Full Name</label>
               <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" required>
            </div>
            
            <div class="form-row">
               <div class="form-group">
                  <label for="phone">Phone Number</label>
                  <input type="tel" id="phone" name="phone" class="form-control" placeholder="e.g. 0712345678" required>
               </div>
               
               <div class="form-group">
                  <label for="email">Email Address</label>
                  <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
               </div>
            </div>
            
            <h2><i class="fas fa-map-marker-alt"></i> Delivery Address</h2>
            
            <div class="form-row">
               <div class="form-group">
                  <label for="delivery_area">Delivery Area</label>
                  <select id="delivery_area" name="delivery_area" class="form-control" required onchange="updateShipping()">
                     <option value="Nairobi">Nairobi</option>
                     <option value="Outside Nairobi">Outside Nairobi</option>
                  </select>
               </div>
               <div class="form-group" id="distance_group" style="display:none;">
                  <label for="distance">Distance from Nairobi (km)</label>
                  <input type="number" id="distance" name="distance" class="form-control" min="1" max="500" value="1" onchange="updateShipping()">
               </div>
            </div>
            
            <div class="form-row">
               <div class="form-group">
                  <label for="flat">House/Flat No.</label>
                  <input type="text" id="flat" name="flat" class="form-control" placeholder="e.g. 12" required>
               </div>
               
               <div class="form-group">
                  <label for="street">Street Name</label>
                  <input type="text" id="street" name="street" class="form-control" placeholder="e.g. Moi Avenue" required>
               </div>
            </div>
            
            <div class="form-row">
               <div class="form-group">
                  <label for="city">City</label>
                  <input type="text" id="city" name="city" class="form-control" placeholder="e.g. Nairobi" required>
               </div>
               
               <div class="form-group">
                  <label for="state">State/County</label>
                  <input type="text" id="state" name="state" class="form-control" placeholder="e.g. Nairobi County" required>
               </div>
            </div>
            
            <div class="form-row">
               <div class="form-group">
                  <label for="country">Country</label>
                  <input type="text" id="country" name="country" class="form-control" placeholder="e.g. Kenya" required>
               </div>
               
               <div class="form-group">
                  <label for="pin_code">Postal Code</label>
                  <input type="text" id="pin_code" name="pin_code" class="form-control" placeholder="e.g. 00100" required>
               </div>
            </div>
            
            <h2><i class="fas fa-credit-card"></i> Payment Method</h2>
            
            <div class="payment-methods">
               <div class="payment-option">
                  <input type="radio" id="mpesa" name="payment_method" value="M-Pesa" checked>
                  <i class="fas fa-mobile-alt"></i>
                  <label for="mpesa">M-Pesa <span style="font-size:0.95em;color:#666;">(Paybill: <strong>123456</strong>)</span></label>
               </div>
               
               <div class="payment-option">
                  <input type="radio" id="cod" name="payment_method" value="Cash on Delivery">
                  <i class="fas fa-money-bill-wave"></i>
                  <label for="cod">Cash on Delivery</label>
               </div>
               
               <div class="payment-option">
                  <input type="radio" id="card" name="payment_method" value="Credit Card">
                  <i class="fas fa-credit-card"></i>
                  <label for="card">Credit/Debit Card</label>
               </div>
            </div>
            
            <button type="submit" name="order" class="btn-checkout <?= ($grand_total > 1)?'':'disabled'; ?>">
               <i class="fas fa-lock"></i> Complete Order
            </button>
         </div>
      <?php else: ?>
         <div class="empty-cart">
            <i class="fas fa-shopping-basket"></i>
            <h3>Your cart is empty</h3>
            <p>There are no items in your cart to checkout</p>
            <a href="shop.php" class="btn"><i class="fas fa-shopping-bag"></i> Continue Shopping</a>
         </div>
      <?php endif; ?>
   </form>
</section>

<?php include 'components/footer.php'; ?>

<script>
function updateShipping() {
   const area = document.getElementById('delivery_area').value;
   const distanceGroup = document.getElementById('distance_group');
   let shipping = 200;
   if(area === 'Outside Nairobi') {
      distanceGroup.style.display = '';
      const distance = parseInt(document.getElementById('distance').value) || 1;
      shipping = 200 + (distance * 30);
   } else {
      distanceGroup.style.display = 'none';
      shipping = 200;
   }
   // Update summary
   document.getElementById('summary-shipping').textContent = 'KSh ' + shipping.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
   const subtotal = parseFloat(document.getElementById('summary-subtotal').textContent.replace(/[^\d.]/g, '')) || 0;
   document.getElementById('summary-total').textContent = 'KSh ' + (subtotal + shipping).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
}
document.addEventListener('DOMContentLoaded', updateShipping);
</script>

<script src="js/script.js"></script>
</body>
</html>