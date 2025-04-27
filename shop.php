<?php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

// Handle add to wishlist
if(isset($_POST['add_to_wishlist'])){
   if($user_id == ''){
      header('location:user_login.php');
      exit();
   }

   $pid = $_POST['pid'];
   $name = $_POST['name'];
   $price = $_POST['price'];
   $image = $_POST['image'];

   // Check if product already exists in wishlist
   $check_wishlist = $conn->prepare("SELECT * FROM `wishlist` WHERE user_id = ? AND pid = ?");
   $check_wishlist->execute([$user_id, $pid]);

   if($check_wishlist->rowCount() > 0){
      $message[] = 'Product already exists in wishlist!';
   }else{
      // Insert new product into wishlist
      $insert_wishlist = $conn->prepare("INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES(?,?,?,?,?)");
      $insert_wishlist->execute([$user_id, $pid, $name, $price, $image]);
      $message[] = 'Product added to wishlist successfully!';
      header('location:wishlist.php');
      exit();
   }
}

// Handle add to cart
if(isset($_POST['add_to_cart'])){
   if($user_id == ''){
      // Guest: use session cart
      if(!isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];
      $pid = $_POST['pid'];
      $name = $_POST['name'];
      $price = $_POST['price'];
      $qty = $_POST['qty'];
      $image = $_POST['image'];
      // Prevent duplicates
      $duplicate = false;
      foreach($_SESSION['guest_cart'] as &$item) {
         if($item['id'] == $pid) {
            $duplicate = true;
            break;
         }
      }
      if($duplicate) {
         $message[] = 'Product already exists in cart!';
      } else {
         $_SESSION['guest_cart'][] = [
            'id' => $pid,
            'name' => $name,
            'price' => $price,
            'quantity' => $qty,
            'image' => $image
         ];
         $message[] = 'Product added to cart successfully!';
      }
   } else {
      $pid = $_POST['pid'];
      $name = $_POST['name'];
      $price = $_POST['price'];
      $qty = $_POST['qty'];
      $image = $_POST['image'];
      // Check if product already exists in cart
      $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ? AND pid = ?");
      $check_cart->execute([$user_id, $pid]);
      if($check_cart->rowCount() > 0){
         $message[] = 'Product already exists in cart!';
      }else{
         // Insert new product into cart
         $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
         $insert_cart->execute([$user_id, $pid, $name, $price, $qty, $image]);
         $message[] = 'Product added to cart successfully!';
      }
   }
}

include 'components/wishlist_cart.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>shop</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<?php
if(isset($message)){
   foreach($message as $message){
      echo '
      <div class="message">
         <span>'.$message.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<section class="products">

   <h1 class="heading">Products</h1>

   <div class="box-container">

   <?php
   // Filter products by category if set
   $where = '';
   $params = [];
   if (isset($_GET['category']) && is_numeric($_GET['category'])) {
      $cat_id = (int)$_GET['category'];
      // Get all descendant category IDs (subcategories, sub-subcategories)
      function getDescendantCategoryIds($conn, $parent_id) {
         $ids = [$parent_id];
         $stmt = $conn->prepare("SELECT id FROM product_categories WHERE parent_id = ?");
         $stmt->execute([$parent_id]);
         $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
         foreach ($children as $child_id) {
            $ids = array_merge($ids, getDescendantCategoryIds($conn, $child_id));
         }
         return $ids;
      }
      $cat_ids = getDescendantCategoryIds($conn, $cat_id);
      $in_placeholders = implode(',', array_fill(0, count($cat_ids), '?'));
      $where = "WHERE category_id IN ($in_placeholders)";
      $params = $cat_ids;
   }

   $select_products = $conn->prepare("SELECT * FROM `products` $where");
   $select_products->execute($params);
   if($select_products->rowCount() > 0){
      while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
   ?>
   <form action="" method="post" class="box">
      <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
      <input type="hidden" name="name" value="<?= $fetch_product['name']; ?>">
      <input type="hidden" name="price" value="<?= $fetch_product['price']; ?>">
      <input type="hidden" name="image" value="<?= $fetch_product['image_01']; ?>">
      <input type="hidden" name="qty" value="1">
      <a href="quick_view.php?pid=<?= $fetch_product['id']; ?>" class="fas fa-eye"></a>
      <img src="images/products/<?= $fetch_product['image_01']; ?>" alt="<?= htmlspecialchars($fetch_product['name']); ?>">
      <div class="name"><a href="product_details.php?pid=<?= $fetch_product['id']; ?>" style="color:inherit;text-decoration:underline;"><?= $fetch_product['name']; ?></a></div>
      <div class="flex">
         <div class="price"><span>Ksh.</span><?= $fetch_product['price']; ?><span>/-</span></div>
      </div>
      <input type="submit" value="add to cart" class="btn" name="add_to_cart">
   </form>
   <?php
      }
   }else{
      echo '<p class="empty">no products found!</p>';
   }
   ?>

   </div>

</section>


<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>