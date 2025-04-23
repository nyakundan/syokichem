<?php
include 'components/connect.php';
include 'components/functions.php';

session_start();

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
}

// Initialize messages array
$messages = [];

if(isset($_POST['add_to_wishlist'])){
   try {
      if($user_id == ''){
         $messages[] = 'Please login first!';
      } else {
         $pid = filter_input(INPUT_POST, 'pid', FILTER_SANITIZE_NUMBER_INT);
         $name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
         $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
         $image = htmlspecialchars($_POST['image'] ?? '', ENT_QUOTES, 'UTF-8');

         // Check if product exists in wishlist
         $check_wishlist_numbers = $conn->prepare("SELECT * FROM `wishlist` WHERE name = ? AND user_id = ?");
         $check_wishlist_numbers->execute([$name, $user_id]);

         // Check if product exists in cart
         $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
         $check_cart_numbers->execute([$name, $user_id]);

         if($check_wishlist_numbers->rowCount() > 0){
            $messages[] = 'Product already exists in wishlist!';
         }else if($check_cart_numbers->rowCount() > 0){
            $messages[] = 'Product already exists in cart!';
         }else{
            // Add to wishlist
            $insert_wishlist = $conn->prepare("INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES(?,?,?,?,?)");
            if($insert_wishlist->execute([$user_id, $pid, $name, $price, $image])){
               $messages[] = 'Product added to wishlist successfully!';
            } else {
               throw new Exception('Failed to add product to wishlist');
            }
         }
      }
   } catch (Exception $e) {
      $messages[] = $e->getMessage();
      error_log("Wishlist Error: " . $e->getMessage());
   }
}

if(isset($_POST['add_to_cart'])){
   try {
      if($user_id == ''){
         $messages[] = 'Please login first!';
      } else {
         $pid = filter_input(INPUT_POST, 'pid', FILTER_SANITIZE_NUMBER_INT);
         $name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
         $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
         $image = htmlspecialchars($_POST['image'] ?? '', ENT_QUOTES, 'UTF-8');
         $qty = filter_input(INPUT_POST, 'qty', FILTER_SANITIZE_NUMBER_INT);

         // Check stock availability
         $check_stock = $conn->prepare("SELECT stock FROM `products` WHERE id = ?");
         $check_stock->execute([$pid]);
         $product = $check_stock->fetch(PDO::FETCH_ASSOC);

         if ($product && $qty > $product['stock']) {
            throw new Exception('Requested quantity exceeds available stock!');
         }

         // Check if product exists in cart
         $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
         $check_cart_numbers->execute([$name, $user_id]);

         if($check_cart_numbers->rowCount() > 0){
            $messages[] = 'Product already exists in cart!';
         } else {
            // Check wishlist and remove if exists
            $check_wishlist_numbers = $conn->prepare("SELECT * FROM `wishlist` WHERE name = ? AND user_id = ?");
            $check_wishlist_numbers->execute([$name, $user_id]);

            if($check_wishlist_numbers->rowCount() > 0){
               $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE name = ? AND user_id = ?");
               $delete_wishlist->execute([$name, $user_id]);
            }

            // Add to cart
            $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
            if($insert_cart->execute([$user_id, $pid, $name, $price, $qty, $image])){
               $messages[] = 'Product added to cart successfully!';
            } else {
               throw new Exception('Failed to add product to cart');
            }
         }
      }
   } catch (Exception $e) {
      $messages[] = $e->getMessage();
      error_log("Cart Error: " . $e->getMessage());
   }
}

include 'components/wishlist_cart.php';

$search_query = isset($_GET['query']) ? trim(htmlspecialchars($_GET['query'], ENT_QUOTES, 'UTF-8')) : '';
$search_results = [];

if (!empty($search_query)) {
    try {
        // Search products by name or description
        $query = "SELECT * FROM `products` WHERE (name LIKE ? OR description LIKE ?) AND stock > 0";
        $search_term = "%{$search_query}%";

        $select_products = $conn->prepare($query);
        $select_products->execute([$search_term, $search_term]);

        if ($select_products->rowCount() > 0) {
            $search_results = $select_products->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $messages[] = 'No products found matching your search.';
        }
    } catch (PDOException $e) {
        $messages[] = 'Database error: ' . $e->getMessage();
        error_log("Search Error: " . $e->getMessage());
    }
}
?>


<?php
// [Previous PHP code remains exactly the same until the HTML head section]
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Search Results for "<?= htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8') ?>"</title>
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
   <style>
      :root {
         --primary: #27ae60;
         --primary-dark: #219653;
         --black: #130f40;
         --white: #fff;
         --light-color: #666;
         --light-bg: #f7f7f7;
         --border: .1rem solid rgba(0,0,0,.1);
         --box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);
         --transition: all 0.3s ease;
      }
      
      html {
         font-size: 62.5%;
      }
      
      * {
         font-family: 'Rubik', sans-serif;
         margin: 0;
         padding: 0;
         box-sizing: border-box;
         outline: none;
         border: none;
         text-decoration: none;
         text-transform: capitalize;
      }
      
      body {
         font-size: 1.6rem;
      }
      
      /* Message Styling */
      .message-container {
         position: fixed;
         top: 1rem;
         right: 1rem;
         z-index: 1000;
         max-width: 350px;
      }
      
      .message {
         padding: 1.2rem 1.5rem;
         margin-bottom: 1rem;
         border-radius: 0.5rem;
         background-color: var(--white);
         box-shadow: var(--box-shadow);
         display: flex;
         align-items: center;
         justify-content: space-between;
         animation: fadeIn 0.5s ease;
         font-size: 1.4rem;
      }
      
      .message span {
         color: var(--black);
      }
      
      .message i {
         color: var(--light-color);
         cursor: pointer;
         font-size: 1.6rem;
         margin-left: 1rem;
         transition: var(--transition);
      }
      
      .message i:hover {
         color: var(--black);
      }
      
      /* Search Results Section */
      .search-results {
         padding: 2rem 9%;
         background-color: var(--light-bg);
         min-height: calc(100vh - 200px);
      }
      
      .search-results .heading {
         text-align: center;
         font-size: 2.5rem;
         color: var(--black);
         margin-bottom: 2.5rem;
         position: relative;
         padding-bottom: 1rem;
      }
      
      .search-results .heading::after {
         content: '';
         position: absolute;
         bottom: 0;
         left: 50%;
         transform: translateX(-50%);
         width: 100px;
         height: 3px;
         background-color: var(--primary);
      }
      
      /* Product Grid */
      .box-container {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
         gap: 2rem;
      }
      
      .box {
         background-color: var(--white);
         border-radius: 0.5rem;
         overflow: hidden;
         box-shadow: var(--box-shadow);
         transition: var(--transition);
         position: relative;
      }
      
      .box:hover {
         transform: translateY(-5px);
         box-shadow: 0 1rem 1.5rem rgba(0,0,0,.15);
      }
      
      .box .stock-status {
         position: absolute;
         top: 10px;
         left: 10px;
         background-color: rgba(255, 51, 51, 0.9);
         color: white;
         padding: 0.3rem 0.8rem;
         border-radius: 0.3rem;
         font-size: 1.2rem;
         z-index: 10;
      }
      
      .box .in-stock {
         background-color: rgba(0, 128, 0, 0.9);
      }
      
      .box img {
         width: 100%;
         height: 200px;
         object-fit: cover;
         border-bottom: var(--border);
      }
      
      .box .name {
         color: var(--black);
         font-size: 1.6rem;
         font-weight: 600;
         padding: 1rem;
         text-align: center;
         white-space: nowrap;
         overflow: hidden;
         text-overflow: ellipsis;
      }
      
      .box .flex {
         display: flex;
         align-items: center;
         justify-content: space-between;
         padding: 1rem;
         border-top: var(--border);
      }
      
      .box .price {
         color: var(--primary);
         font-size: 1.8rem;
         font-weight: 600;
      }
      
      .box .price span {
         font-size: 1rem;
         color: var(--light-color);
      }
      
      .box .qty {
         width: 60px;
         padding: 0.5rem;
         border: var(--border);
         border-radius: 0.3rem;
         font-size: 1.4rem;
         text-align: center;
      }
      
      .box .btn {
         display: block;
         width: 100%;
         padding: 1rem;
         background-color: var(--primary);
         color: var(--white);
         font-size: 1.5rem;
         font-weight: 500;
         text-align: center;
         cursor: pointer;
         transition: var(--transition);
      }
      
      .box .btn:hover {
         background-color: var(--primary-dark);
      }
      
      .box .btn:disabled {
         background-color: #ccc;
         cursor: not-allowed;
      }
      
      .box .fa-heart,
      .box .fa-eye {
         position: absolute;
         top: 1rem;
         height: 3.5rem;
         width: 3.5rem;
         line-height: 3.5rem;
         background-color: rgba(255,255,255,0.8);
         border-radius: 50%;
         text-align: center;
         font-size: 1.6rem;
         color: var(--black);
         cursor: pointer;
         transition: var(--transition);
         z-index: 10;
      }
      
      .box .fa-heart {
         right: 1rem;
      }
      
      .box .fa-eye {
         right: 4.5rem;
      }
      
      .box .fa-heart:hover,
      .box .fa-eye:hover {
         background-color: var(--primary);
         color: var(--white);
      }
      
      .box .fa-heart:disabled,
      .box .fa-eye:disabled {
         background-color: #ccc;
         color: #666;
         cursor: not-allowed;
      }
      
      /* Empty State */
      .empty {
         text-align: center;
         font-size: 1.8rem;
         color: var(--light-color);
         padding: 3rem 0;
      }
      
      /* Responsive */
      @media (max-width: 768px) {
         .search-results {
            padding: 2rem;
         }
         
         .box-container {
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
         }
      }
      
      @media (max-width: 480px) {
         .search-results .heading {
            font-size: 1.8rem;
         }
      }
      
      /* Animations */
      @keyframes fadeIn {
         from { opacity: 0; transform: translateY(-20px); }
         to { opacity: 1; transform: translateY(0); }
      }
   </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<!-- Display messages -->
<?php if(!empty($messages)): ?>
<div class="message-container">
   <?php foreach($messages as $message): ?>
      <div class="message"><span><?= $message ?></span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>
   <?php endforeach; ?>
</div>
<?php endif; ?>

<section class="search-results">
   <h1 class="heading">Search Results for "<?= htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8') ?>"</h1>

   <?php if (!empty($search_results)): ?>
      <div class="box-container">
         <?php foreach ($search_results as $fetch_product): ?>
            <form action="" method="post" class="box">
               <input type="hidden" name="pid" value="<?= htmlspecialchars($fetch_product['id'], ENT_QUOTES, 'UTF-8') ?>">
               <input type="hidden" name="name" value="<?= htmlspecialchars($fetch_product['name'], ENT_QUOTES, 'UTF-8') ?>">
               <input type="hidden" name="price" value="<?= htmlspecialchars($fetch_product['price'], ENT_QUOTES, 'UTF-8') ?>">
               <input type="hidden" name="image" value="<?= htmlspecialchars($fetch_product['image_01'], ENT_QUOTES, 'UTF-8') ?>">
               <input type="hidden" name="stock" value="<?= htmlspecialchars($fetch_product['stock'], ENT_QUOTES, 'UTF-8') ?>">

               <button type="submit" class="fas fa-heart" name="add_to_wishlist"></button>
               <a href="quick_view.php?pid=<?= htmlspecialchars($fetch_product['id'], ENT_QUOTES, 'UTF-8') ?>" class="fas fa-eye"></a>
               <span class="stock-status <?= $fetch_product['stock'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                  <?= $fetch_product['stock'] > 0 ? 'In Stock: ' . $fetch_product['stock'] : 'Out of Stock' ?>
               </span>

               <img src="uploaded_img/<?= htmlspecialchars($fetch_product['image_01'], ENT_QUOTES, 'UTF-8') ?>" 
                    alt="<?= htmlspecialchars($fetch_product['name'], ENT_QUOTES, 'UTF-8') ?>">
               <div class="name"><?= htmlspecialchars($fetch_product['name'], ENT_QUOTES, 'UTF-8') ?></div>

               <div class="flex">
                  <div class="price"><span>KSh</span><?= number_format($fetch_product['price'], 2) ?></div>
                  <input type="number" name="qty" class="qty" min="1" max="<?= $fetch_product['stock'] ?>" value="1">
               </div>

               <button type="submit" class="btn" name="add_to_cart">
                  <i class="fas fa-shopping-cart"></i> Add to Cart
               </button>
            </form>
         <?php endforeach; ?>
      </div>
   <?php else: ?>
      <p class="empty">No products found matching your search.</p>
   <?php endif; ?>
</section>

<?php include 'components/footer.php'; ?>

<script>
// Enhanced client-side validation with better UX
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form.box');
    
    forms.forEach(form => {
        const qtyInput = form.querySelector('input[name="qty"]');
        const submitBtn = form.querySelector('input[name="add_to_cart"]');
        
        if(qtyInput && submitBtn) {
            qtyInput.addEventListener('input', function() {
                const stockQty = parseInt(form.querySelector('input[name="stock"]').value);
                const qty = parseInt(this.value);
                
                if (isNaN(qty) || qty < 1) {
                    this.classList.add('error');
                    submitBtn.disabled = true;
                    return;
                }
                
                if (qty > stockQty) {
                    this.classList.add('error');
                    submitBtn.disabled = true;
                } else {
                    this.classList.remove('error');
                    submitBtn.disabled = false;
                }
            });
        }
        
        form.addEventListener('submit', function(e) {
            const qtyInput = form.querySelector('input[name="qty"]');
            const stockQty = parseInt(form.querySelector('input[name="stock"]').value);
            const qty = parseInt(qtyInput.value);
            
            if (isNaN(qty)) {
                alert('Please enter a valid number for quantity');
                e.preventDefault();
                qtyInput.focus();
                return false;
            }
            
            if (qty < 1) {
                alert('Quantity must be at least 1');
                e.preventDefault();
                qtyInput.focus();
                return false;
            }
            
            if (qty > stockQty) {
                alert(`Sorry, we only have ${stockQty} items in stock`);
                e.preventDefault();
                qtyInput.focus();
                return false;
            }
            
            // Show loading state
            submitBtn.value = 'Adding...';
            submitBtn.disabled = true;
            
            return true;
        });
    });
});
</script>
</body>
</html>
