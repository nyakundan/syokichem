<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'components/connect.php';
include 'components/functions.php';

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    // Debugging: Log the incoming POST data
    error_log("Add to Cart POST data: " . print_r($_POST, true));

    $pid = $_POST['pid'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $qty = $_POST['qty'];
    $image = $_POST['image'];

    // Debugging: Log the product details
    error_log("Product Details - PID: $pid, Name: $name, Price: $price, Quantity: $qty, Image: $image");

    // Check if product already exists in cart
    $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE pid = ?");
    $check_cart->execute([$pid]);

    if ($check_cart->rowCount() > 0) {
        $message[] = 'Product already exists in cart!';
        error_log("Product already exists in cart for PID: $pid");
    } else {
        // Insert new product into cart
        $insert_cart = $conn->prepare("INSERT INTO `cart`(pid, name, price, quantity, image) VALUES(?,?,?,?,?)");
        try {
            if ($insert_cart->execute([$pid, $name, $price, $qty, $image])) {
                $message[] = 'Product added to cart successfully!';
                error_log("Product added to cart successfully for PID: $pid");
            } else {
                $message[] = 'Failed to add product to cart.';
                error_log("Failed to add product to cart for PID: $pid");
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $message[] = 'Database error occurred. Please try again later.';
        }
    }
}

// Handle add to wishlist
if(isset($_POST['add_to_wishlist'])) {
   if($user_id == ''){
      $message[] = 'Please login first to add items to wishlist';
      exit();
   }

   $pid = filter_input(INPUT_POST, 'pid', FILTER_SANITIZE_NUMBER_INT);
   $name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
   $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
   $image = htmlspecialchars($_POST['image'] ?? '', ENT_QUOTES, 'UTF-8');

   if (!$pid || !$name || !isset($price) || !$image) {
      $message[] = 'Invalid product data';
      exit();
   }

   // Check if product already exists in wishlist
   $check_wishlist = $conn->prepare("SELECT * FROM `wishlist` WHERE user_id = ? AND pid = ?");
   $check_wishlist->execute([$user_id, $pid]);

   if($check_wishlist->rowCount() > 0){
      $message[] = 'Product already exists in wishlist!';
   } else {
      // Insert new product into wishlist
      $insert_wishlist = $conn->prepare("INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES(?,?,?,?,?)");
      $insert_wishlist->execute([$user_id, $pid, $name, $price, $image]);
      $message[] = 'Product added to wishlist successfully!';
   }
}

// Fetch latest products
$select_products = $conn->prepare("SELECT p.*, 
   (SELECT COUNT(*) FROM wishlist w WHERE w.pid = p.id AND w.user_id = ?) as in_wishlist,
   (SELECT COUNT(*) FROM cart c WHERE c.pid = p.id AND c.user_id = ?) as in_cart
   FROM `products` p 
   ORDER BY p.id DESC 
   LIMIT 8");
$select_products->execute([$user_id, $user_id]);
$products = $select_products->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories from the product_categories table
$select_categories = $conn->prepare("SELECT * FROM product_categories WHERE parent_id IS NULL ORDER BY menu_order ASC");
$select_categories->execute();
$categories = $select_categories->fetchAll(PDO::FETCH_ASSOC);

// Fetch subcategories
$select_subcategories = $conn->prepare("SELECT * FROM product_categories WHERE parent_id IS NOT NULL ORDER BY menu_order ASC");
$select_subcategories->execute();
$subcategories = $select_subcategories->fetchAll(PDO::FETCH_ASSOC);

// Organize categories with subcategories
$category_tree = [];
foreach($categories as $category) {
    $category_tree[$category['id']] = $category;
    $category_tree[$category['id']]['subcategories'] = [];
}

foreach($subcategories as $subcategory) {
    if(isset($category_tree[$subcategory['parent_id']])) {
        $category_tree[$subcategory['parent_id']]['subcategories'][] = $subcategory;
    }
}

// Fetch latest blog posts
$select_posts = $conn->prepare("
   SELECT p.*, c.name as category_name 
   FROM blog_posts p
   LEFT JOIN blog_categories c ON p.category_id = c.id
   WHERE p.status = 'published'
   ORDER BY p.published_at DESC
   LIMIT 4
");
$select_posts->execute();
$blog_posts = $select_posts->fetchAll(PDO::FETCH_ASSOC);

// Fetch active coupons
$current_date = date('Y-m-d H:i:s');
$select_coupons = $conn->prepare("
SELECT * FROM `coupons` 
WHERE is_active = 1 
AND start_date <= ? 
AND expiry_date >= ?
AND (usage_limit IS NULL OR used_count < usage_limit)
ORDER BY expiry_date ASC
LIMIT 4
");
$select_coupons->execute([$current_date, $current_date]);
$coupons = $select_coupons->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Syokichem - Think medicines,Think Syokichem</title>
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/home.css">
   <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
   <meta http-equiv="Pragma" content="no-cache">
   <meta http-equiv="Expires" content="0">
   <meta name="description" content="Kenya's trusted online pharmacy. Prescription medicines, OTC drugs, and healthcare products delivered to your doorstep.">
   <style>
      a {
         text-decoration: none !important;
      }
      a:hover, a:focus {
         text-decoration: none !important;
      }
   </style>
</head>
<body>
<?php
if(isset($message)){
    foreach($message as $msg){
        echo '<div class="message">'.htmlspecialchars($msg).'</div>';
    }
}
?>

<?php include 'components/user_header.php'; ?>

<!-- Hero Section -->
<section class="pharmacy-hero">
   <div class="hero-background">
      <img src="images/home-bg-2.jpg" alt="Hero Background">
   </div>
   
   <div class="home-slider">
      <div class="swiper">
         <div class="swiper-wrapper">
            <div class="swiper-slide">
               <img src="images/hero-1.jpg" alt="Healthcare Professionals">
               <!--<div class="content">
                  <h1>Professional Healthcare Solutions</h1>
                  <p>Trusted by healthcare professionals across Kenya</p>
                  <a href="shop.php" class="btn">Shop Now</a>
               </div>-->
            </div>
            
            <div class="swiper-slide">
               <img src="images/hero-2.jpg" alt="Medicine Delivery">
               <!--<div class="content">
                  <h1>Fast & Reliable Delivery</h1>
                  <p>Medicines delivered to your doorstep</p>
                  <a href="shop.php" class="btn">Shop Now</a>
               </div>-->
            </div>
            
            <div class="swiper-slide">
               <img src="images/hero-3.jpeg" alt="Online Consultation">
               <!--<div class="content">
                  <h1>Online Consultation Services</h1>
                  <p>Connect with healthcare professionals</p>
                  <a href="telemedicine.php" class="btn">Book Now</a>
               </div>-->
            </div>
         </div>
         
         <div class="swiper-pagination"></div>
      </div>
   </div>
</section>


<!-- Special Offers Section -->
<section class="special-offers">
    <h2 class="title">Special Offers</h2>
    <div class="swiper offers-slider">
        <div class="swiper-wrapper">
            <?php
            $select_offers = $conn->prepare("
                SELECT so.*, p.* 
                FROM special_offers so
                JOIN products p ON so.product_id = p.id
                WHERE so.is_active = 1 
                AND so.start_date <= NOW() 
                AND so.end_date >= NOW()
                AND p.stock > 0
                ORDER BY (so.old_price - so.new_price) DESC 
                LIMIT 8
            ");
            $select_offers->execute();
            // DEBUG OUTPUT START
            // echo '<!-- DEBUG: Current server time: '.date('Y-m-d H:i:s').' -->';
            // echo '<!-- DEBUG: Offers found: '.$select_offers->rowCount().' -->';
            // if($select_offers->rowCount() === 0) {
            //     echo '<div style="color:red;background:#fff3cd;padding:1rem;margin-bottom:1rem;border:1px solid #ffeeba;">No special offers found. Check your special_offers table:<br> - is_active=1<br> - start_date &le; now<br> - end_date &ge; now<br> - product_id exists in products with stock &gt; 0</div>';
            // } else {
            //     $offers_preview = [];
            //     foreach($select_offers->fetchAll(PDO::FETCH_ASSOC) as $offer) {
            //         $offers_preview[] = htmlspecialchars($offer['name'] ?? '[no name]');
            //     }
            //     echo '<!-- DEBUG: Offer product names: '.implode(', ', $offers_preview).' -->';
            //     // Reset for normal loop
            //     $select_offers->execute();
            // }
            // DEBUG OUTPUT END
            if($select_offers->rowCount() > 0){
                while($offer = $select_offers->fetch(PDO::FETCH_ASSOC)){
                    $discount_percentage = round(($offer['old_price'] - $offer['new_price']) / $offer['old_price'] * 100);
                    ?>
                    <div class="swiper-slide">
                        <form action="" method="post" class="box add-to-cart-form">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="pid" value="<?= $offer['id']; ?>">
                            <input type="hidden" name="name" value="<?= htmlspecialchars($offer['name']); ?>">
                            <input type="hidden" name="price" value="<?= $offer['new_price']; ?>">
                            <input type="hidden" name="image" value="<?= htmlspecialchars($offer['image_01']); ?>">
                            <button class="fas fa-heart" type="submit" name="add_to_wishlist"></button>
                            <a href="quick_view.php?pid=<?= $offer['id']; ?>" class="fas fa-eye"></a>
                            <div class="offer-badge">Save <?= $discount_percentage; ?>%</div>
                            <img src="images/products/<?= $offer['image_01']; ?>" alt="<?= htmlspecialchars($offer['name']); ?>">
                            <div class="name"><a href="product_details.php?pid=<?= $offer['id']; ?>" style="color:inherit;text-decoration:underline;"><?= htmlspecialchars($offer['name']); ?></a></div>
                            <div class="flex">
                                <div class="price">
                                    <span class="original">KSh <?= number_format($offer['old_price'], 2); ?></span>
                                    <span class="discounted">KSh <?= number_format($offer['new_price'], 2); ?></span>
                                </div>
                            </div>
                            <input type="submit" value="Add to Cart" class="btn add-to-cart-btn" name="add_to_cart">
                        </form>
                    </div>
                    <?php
                }
            } else {
                echo '<p class="empty">No special offers available at the moment!</p>';
            }
            ?>
        </div>
        <div class="swiper-pagination"></div>
    </div>
</section>



<!-- Two Column Advert Section -->
<!--
<div class="advert-banner">
   <div class="container">
      <div class="banner-content">
         <div class="banner-text">
            <h2>Seasonal Health Check</h2>
            <p>Get 20% off on all health check packages this month</p>
            <a href="telemedicine.php" class="btn">Book Now</a>
         </div>
         <div class="banner-image">
            <img src="images/health-check.jpg" alt="Health Check Promotion">
         </div>
      </div>
   </div>
</div>
-->


<!-- New on Syokichem Section -->
<section class="home-products">
    <h1 class="heading">New on Syokichem</h1>
    <div class="swiper products-slider">
        <div class="swiper-wrapper">
            <?php
            $select_new = $conn->prepare("SELECT * FROM `products` ORDER BY id DESC LIMIT 8"); 
            $select_new->execute();
            if($select_new->rowCount() > 0){
                while($fetch_new = $select_new->fetch(PDO::FETCH_ASSOC)){
                    ?>
                    <form action="" method="post" class="swiper-slide box">
                        <input type="hidden" name="pid" value="<?= $fetch_new['id']; ?>">
                        <input type="hidden" name="name" value="<?= $fetch_new['name']; ?>">
                        <input type="hidden" name="price" value="<?= $fetch_new['price']; ?>">
                        <input type="hidden" name="image" value="<?= $fetch_new['image_01']; ?>">
                        <span class="new-badge">New</span>
                        <button class="fas fa-heart" type="submit" name="add_to_wishlist"></button>
                        <a href="quick_view.php?pid=<?= $fetch_new['id']; ?>" class="fas fa-eye"></a>
                        <img src="images/products/<?= $fetch_new['image_01']; ?>" alt="<?= $fetch_new['name']; ?>">
                        <div class="name"><a href="product_details.php?pid=<?= $fetch_new['id']; ?>" style="color:inherit;text-decoration:underline;"><?= $fetch_new['name']; ?></a></div>
                        <div class="flex">
                            <div class="price">
                                <span>KSh</span><?= number_format($fetch_new['price'], 2); ?>
                            </div>
                        </div>
                        <input type="submit" value="Add to Cart" class="btn" name="add_to_cart" onclick="addProductToCart(event)">
                    </form>
                    <?php
                }
            } else {
                echo '<p class="empty">No new products available at the moment!</p>';
            }
            ?>
        </div>
        <div class="swiper-pagination"></div>
    </div>
</section>

<script>
    function addProductToCart(event) {
        event.preventDefault();
        // Debug: function called
        console.log('addProductToCart called');
        const form = event.target.closest('form');
        const pid = form.querySelector('input[name="pid"]').value;
        const name = form.querySelector('input[name="name"]').value;
        const price = form.querySelector('input[name="price"]').value;
        const image = form.querySelector('input[name="image"]').value;
        // Debug: log all data
        console.log({pid, name, price, image});
        let formData = new FormData();
        formData.append('pid', pid);
        formData.append('name', name);
        formData.append('price', price);
        formData.append('image', image);
        formData.append('add_to_cart', '1');
        fetch('components/cart_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data);
            if(data.status === 'success'){
                alert('Product added to cart successfully!');
            } else {
                alert(data.message || 'Failed to add product to cart!');
            }
        })
        .catch(error => {
            console.log('Fetch error:', error);
            alert('An error occurred while adding to cart.');
        });
    }
</script>

<!-- Shop by Category Section -->
<section class="category">
   <h1 class="heading">Shop by Health Category</h1>
   <div class="swiper category-slider">
      <div class="swiper-wrapper">
         <?php
         if(!empty($category_tree)){
            foreach($category_tree as $category){
                $slug = $category['slug'];
                $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $imagePath = '';
                foreach ($imageExtensions as $ext) {
                    $tryPath = "images/categories/$slug.$ext";
                    if (file_exists($tryPath)) {
                        $imagePath = $tryPath;
                        break;
                    }
                }
                if (!$imagePath) {
                    $imagePath = 'images/categories/default-category.jpg';
                }
                ?>
                <a href="category.php?category=<?= urlencode($category['slug']); ?>" class="swiper-slide slide">
                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($category['name']); ?>">
                    <h3><?= htmlspecialchars($category['name']); ?></h3>
                </a>
                <?php
            }
         } else {
            echo '<p class="empty">No categories found!</p>';
         }
         ?>
      </div>
      <div class="swiper-pagination"></div>
   </div>
</section>


<!-- Latest Products Section -->
<section class="products">
    <h2 class="title">Latest Products</h2>
    <div class="box-container">
        <?php
        $select_products = $conn->prepare("SELECT * FROM `products` ORDER BY id DESC LIMIT 8");
        $select_products->execute();
        if($select_products->rowCount() > 0){
            while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
                ?>
                <form action="" method="post" class="box">
                    <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
                    <input type="hidden" name="name" value="<?= $fetch_product['name']; ?>">
                    <input type="hidden" name="price" value="<?= $fetch_product['price']; ?>">
                    <input type="hidden" name="image" value="<?= $fetch_product['image_01']; ?>">
                    <a href="quick_view.php?pid=<?= $fetch_product['id']; ?>" class="fas fa-eye"></a>
                    <img src="images/products/<?= $fetch_product['image_01']; ?>" alt="<?= $fetch_product['name']; ?>">
                    <div class="name"><a href="product_details.php?pid=<?= $fetch_product['id']; ?>" style="color:inherit;text-decoration:underline;"><?= $fetch_product['name']; ?></a></div>
                    <div class="flex">
                        <div class="price"><span>KSh</span><?= number_format($fetch_product['price'], 2); ?></div>
                    </div>
                    <input type="submit" value="Add to Cart" class="btn" name="add_to_cart" onclick="addProductToCart(event)">
                </form>
                <?php
            }
        } else {
            echo '<p class="empty">No products found!</p>';
        }
        ?>
    </div>
</section>



<!-- Active Coupons Section -->
<section class="coupons-section">
    <h2 class="title">Available Coupons</h2>
    <div class="coupon-grid">
        <?php if(!empty($coupons)): ?>
            <?php foreach($coupons as $coupon): ?>
                <?php
                $discount_text = $coupon['discount_type'] === 'percentage' 
                    ? $coupon['discount_value'] . '%' 
                    : 'KSh ' . number_format($coupon['discount_value'], 2);
                ?>
                <div class="coupon-card">
                    <div class="coupon-header">
                        <div class="coupon-discount"><?= $discount_text ?></div>
                        <div class="coupon-code"><?= $coupon['code'] ?></div>
                    </div>
                    <div class="coupon-details">
                        <?php if($coupon['min_order_amount'] > 0): ?>
                            <div class="coupon-detail">Min. Order: KSh <?= number_format($coupon['min_order_amount'], 2) ?></div>
                                               <?php endif; ?>
                        <?php if($coupon['max_discount_amount']): ?>
                            <div class="coupon-detail">Max Discount: KSh <?= number_format($coupon['max_discount_amount'], 2) ?></div>
                        <?php endif; ?>
                        <div class="coupon-expiry">Valid till: <?= date('M j, Y', strtotime($coupon['expiry_date'])) ?></div>
                    </div>
                    <button class="copy-btn" onclick="copyCode('<?= $coupon['code'] ?>')">
                        <i class="fas fa-copy"></i> Copy Code
                    </button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty">No active coupons available at the moment!</p>
        <?php endif; ?>
    </div>
</section>

<!-- Health Articles Section -->
<section class="health-articles">
   <div class="container">
      <h2 class="title">Health Articles & Tips</h2>
      <div class="articles-container">
         <?php foreach($blog_posts as $post): ?>
            <article class="article-card">
               <div class="article-image">
                  <?php if ($post['featured_image']): ?>
                     <img src="images/<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                  <?php else: ?>
                     <img src="images/default-blog.jpg" alt="Default blog image">
                  <?php endif; ?>
               </div>
               <div class="article-content">
                  <span class="article-category"><?= htmlspecialchars($post['category_name']) ?></span>
                  <h3 class="article-title"><?= htmlspecialchars($post['title']) ?></h3>
                  <p class="article-excerpt"><?= htmlspecialchars(substr($post['excerpt'] ?: strip_tags($post['content']), 0, 150)) ?>...</p>
                  <div class="article-meta">
                     <span class="article-date"><?= date('M j, Y', strtotime($post['published_at'])) ?></span>
                     <a href="blog-post.php?slug=<?= urlencode($post['slug']) ?>" class="read-more">Read More</a>
                  </div>
               </div>
            </article>
         <?php endforeach; ?>
      </div>
      <div class="view-all-container">
         <a href="blog.php" class="view-all-btn">View All Articles</a>
      </div>
   </div>
</section>

<!-- Final Advert Section (Brand Colored) -->
<section class="app-promo-banner brand-colored" aria-label="Mobile App Promotion">
   <div class="container">
      <div class="banner-content">
         <div class="banner-text">
            <h2>Download Our Mobile App</h2>
            <p>Get exclusive deals and manage your prescriptions on the go</p>
            <div class="app-buttons">
               <a href="#" class="app-store" aria-label="Download on App Store">
                  <img src="images/app-store.png" alt="Download on App Store">
               </a>
               <a href="#" class="play-store" aria-label="Get it on Google Play">
                  <img src="images/play-store.png" alt="Get it on Google Play">
               </a>
            </div>
         </div>
         <div class="banner-image">
            <img src="images/mobile-app.jpg" alt="Syokichem Mobile App">
         </div>
      </div>
   </div>
</section>

<section class="site-info-section">
  <div class="site-info-container">
    <div class="site-info-block">
      <h2>Eligibility</h2>
      <p>To access SYOKICHEM's website, mobile application, and services, you must meet the following conditions:</p>
      <ol>
        <li>Age Requirement: You must be at least 18 years old. SYOKICHEM does not knowingly provide services to individuals under this age.</li>
        <li>Legal Capacity: You must have the legal capacity to enter into binding agreements in accordance with the laws of Kenya.</li>
        <li>Valid Information: You must provide accurate and complete details when registering on our website.</li>
      </ol>
    </div>
    <div class="site-info-block">
      <h2>Health and Medical Disclaimer</h2>
      <p>The content, tools, and information provided by SYOKICHEM are for general informational purposes only and are not designed to replace professional consultation or advice. Always consult a licensed healthcare provider for personalized medical guidance. SYOKICHEM is not responsible for any action taken based on the information available on our website or services.</p>
    </div>
    <div class="site-info-block">
      <h2>Linking to Our Website</h2>
      <p>Organizations such as government entities, accredited businesses, and recognized search engines may link to SYOKICHEM's homepage without prior written consent, provided the link:</p>
      <ol>
        <li>Does not mislead or falsely imply sponsorship or approval.</li>
        <li>Aligns with the context of the linking party's platform.</li>
      </ol>
      <p>For other entities wishing to link to SYOKICHEM's website, written approval must be obtained by contacting <a href="mailto:info@syokichem.com">info@syokichem.com</a>. SYOKICHEM reserves the right to request the removal of links or deny link requests at its discretion.</p>
    </div>
  </div>
</section>

<?php include 'components/footer.php'; ?>

<!-- Age Eligibility Popup -->
<div id="ageEligibilityPopup" class="age-popup" style="display: none;">
    <div class="age-popup-content">
        <h2>Age Verification</h2>
        <p>You must be at least 18 years old to access this website.</p>
        <button id="ageConfirmBtn">I am 18 or older</button>
    </div>
</div>

<style>
.age-popup {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.age-popup-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.age-popup button {
    margin-top: 10px;
    padding: 10px 20px;
    background-color: #25D366; /* WhatsApp green */
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if the user has already confirmed their age
    if (!localStorage.getItem('ageConfirmed')) {
        // Show the age eligibility popup
        document.getElementById('ageEligibilityPopup').style.display = 'flex';
    }

    // Handle the age confirmation button click
    document.getElementById('ageConfirmBtn').addEventListener('click', function() {
        // Set a flag in local storage to remember the user's confirmation
        localStorage.setItem('ageConfirmed', 'true');
        // Hide the popup
        document.getElementById('ageEligibilityPopup').style.display = 'none';
    });
});
</script>

<!-- WhatsApp Popup Link -->
<div class="whatsapp-popup">
    <a href="https://wa.me/254792914662" target="_blank" class="whatsapp-button">
        <i class="fab fa-whatsapp"></i> Chat with us on WhatsApp
    </a>
</div>

<style>
.whatsapp-popup {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}

.whatsapp-button {
    background-color: #25D366; /* WhatsApp green */
    color: white;
    padding: 10px 15px;
    border-radius: 50px;
    text-decoration: none;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
}

.whatsapp-button i {
    margin-right: 5px;
}
</style>

<script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
<script src="js/script.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all sliders
    const sliders = {
        hero: new Swiper(".home-slider .swiper", {
            loop: true,
            autoplay: { delay: 5000, disableOnInteraction: false },
            pagination: { el: ".swiper-pagination", clickable: true }
        }),
        offers: new Swiper(".offers-slider", {
            loop: true,
            spaceBetween: 20,
            pagination: { el: ".swiper-pagination", clickable: true },
            breakpoints: { 0: { slidesPerView: 1 }, 768: { slidesPerView: 2 }, 1024: { slidesPerView: 4 } }
        }),
        products: new Swiper(".products-slider", {
            loop: true,
            spaceBetween: 20,
            pagination: { el: ".swiper-pagination", clickable: true },
            breakpoints: { 0: { slidesPerView: 1 }, 768: { slidesPerView: 2 }, 1024: { slidesPerView: 4 } }
        }),
        category: new Swiper(".category-slider", {
            loop: true,
            spaceBetween: 20,
            pagination: { el: ".swiper-pagination", clickable: true },
            breakpoints: { 0: { slidesPerView: 2 }, 640: { slidesPerView: 3 }, 768: { slidesPerView: 4 }, 1024: { slidesPerView: 6 } }
        })
    };

    // Handle all form submissions
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitter = e.submitter || this.querySelector('[type="submit"]');
            
            if (!submitter) return;
            
            const originalContent = submitter.innerHTML;
            const isCartAction = submitter.name === 'add_to_cart';
            
            try {
                submitter.disabled = true;
                submitter.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (isCartAction ? 'Adding...' : 'Processing...');
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.text();
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = result;
                
                // Show messages
                tempDiv.querySelectorAll('.message').forEach(msg => {
                    showToast(msg.textContent);
                });
                
                // Update cart count if added to cart
                if (isCartAction) {
                    updateCartCount();
                }
                
                // Update wishlist button if added to wishlist
                if (submitter.name === 'add_to_wishlist' && !result.includes('already exists')) {
                    submitter.classList.add('in-wishlist');
                    submitter.disabled = true;
                }
                
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            } finally {
                if (submitter.name !== 'add_to_wishlist') {
                    submitter.disabled = false;
                    submitter.innerHTML = originalContent;
                }
            }
        });
    });

    // Copy coupon code function
    window.copyCode = function(code) {
        navigator.clipboard.writeText(code).then(() => {
            showToast('Coupon code copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy code:', err);
            showToast('Failed to copy code. Please try again.', 'error');
        });
    };

    // Toast notification function
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Update cart count
    async function updateCartCount() {
        try {
            const response = await fetch('components/get_cart_count.php');
            const count = await response.text();
            
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = count;
                cartCount.classList.add('pulse');
                setTimeout(() => cartCount.classList.remove('pulse'), 500);
            }
        } catch (error) {
            console.error('Error updating cart count:', error);
        }
    }

    // AJAX Add to Cart for special offers
    document.querySelectorAll('.special-offers .add-to-cart-form').forEach(function(form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.add-to-cart-btn');
            const originalText = submitBtn.value;
            submitBtn.disabled = true;
            submitBtn.value = 'Adding...';
            try {
                const response = await fetch('components/cart_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    if (typeof showToast === 'function') {
                        showToast(result.message || 'Product added to cart!');
                    } else {
                        alert(result.message || 'Product added to cart!');
                    }
                    if (typeof updateCartCount === 'function') {
                        updateCartCount();
                    }
                } else {
                    if (typeof showToast === 'function') {
                        showToast(result.message || 'Failed to add product!', 'error');
                    } else {
                        alert(result.message || 'Failed to add product!');
                    }
                }
            } catch (error) {
                if (typeof showToast === 'function') {
                    showToast('An error occurred. Please try again.', 'error');
                } else {
                    alert('An error occurred. Please try again.');
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.value = originalText;
            }
        });
    });
});
</script>

</body>
</html>