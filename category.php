<?php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

// Include user header
include 'components/user_header.php';
include 'components/wishlist_cart.php';

// Get current category slug
$category_slug = $_GET['category'] ?? '';
$parent_category = null;
$subcategories = [];
$category_name = 'All Products';
$query = ''; // Initialize the query variable

// Get category information if specified
if($category_slug) {
    // Get main category
    $get_category = $conn->prepare("SELECT * FROM product_categories WHERE slug = ?");
    $get_category->execute([$category_slug]);
    
    if($get_category->rowCount() > 0) {
        $current_category = $get_category->fetch(PDO::FETCH_ASSOC);
        $category_name = $current_category['name'];
        
        // Check if this is a parent category
        if(is_null($current_category['parent_id'])) {
            $parent_category = $current_category;
            
            // Get all subcategories
            $get_subcategories = $conn->prepare("SELECT * FROM product_categories WHERE parent_id = ? ORDER BY name");
            $get_subcategories->execute([$parent_category['id']]);
            $subcategories = $get_subcategories->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // This is a subcategory, get its parent
            $get_parent = $conn->prepare("SELECT * FROM product_categories WHERE id = ?");
            $get_parent->execute([$current_category['parent_id']]);
            $parent_category = $get_parent->fetch(PDO::FETCH_ASSOC);
            
            // Get sibling categories
            $get_subcategories = $conn->prepare("SELECT * FROM product_categories WHERE parent_id = ? AND id != ? ORDER BY name");
            $get_subcategories->execute([$parent_category['id'], $current_category['id']]);
            $subcategories = $get_subcategories->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

// Get products based on category
if($category_slug) {
    if(isset($current_category)) {
        // If we found a valid category
        if(is_null($current_category['parent_id'])) {
            // Parent category - show products from all subcategories
            $select_products = $conn->prepare("SELECT p.* FROM products p 
                                            JOIN product_category_mappings pcm ON p.id = pcm.product_id 
                                            JOIN product_categories c ON pcm.category_id = c.id
                                            WHERE c.parent_id = ?
                                            AND p.stock > 0
                                            ORDER BY p.sales DESC, p.created_at DESC");
            $select_products->execute([$current_category['id']]);
        } else {
            // Subcategory - show products only from this category
            $select_products = $conn->prepare("SELECT p.* FROM products p 
                                            JOIN product_category_mappings pcm ON p.id = pcm.product_id 
                                            WHERE pcm.category_id = ?
                                            AND p.stock > 0
                                            ORDER BY p.sales DESC, p.created_at DESC");
            $select_products->execute([$current_category['id']]);
        }
    } else {
        // Fallback to keyword search for old URLs
        $search_terms = [$category_slug];
        $query = "SELECT * FROM products WHERE stock > 0 AND (";
        $conditions = [];
        $params = [];
        
        foreach($search_terms as $term) {
            $conditions[] = "name LIKE ? OR description LIKE ?";
            $params[] = "%$term%";
            $params[] = "%$term%";
        }
        
        $query .= implode(" OR ", $conditions) . ") ORDER BY sales DESC, created_at DESC";
        $select_products = $conn->prepare($query);
        $select_products->execute($params);
    }
} else {
    // Show all products
    $select_products = $conn->prepare("SELECT * FROM products WHERE stock > 0 ORDER BY sales DESC, created_at DESC LIMIT 24");
    $select_products->execute();
}

// Log the query only if it has been defined
if (!empty($query)) {
    error_log("SQL Query: " . $query);
}

// Display products
?>

<section class="products">
    <h1 class="heading"><?= htmlspecialchars($category_name) ?></h1>

    <div class="box-container">
        <?php if($select_products->rowCount() > 0): ?>
            <?php while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)): ?>
                <form action="" method="post" class="box">
                    <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
                    <input type="hidden" name="name" value="<?= htmlspecialchars($fetch_product['name']); ?>">
                    <input type="hidden" name="price" value="<?= $fetch_product['price']; ?>">
                    <input type="hidden" name="image" value="<?= $fetch_product['image_01']; ?>">
                    <button class="fas fa-heart" type="submit" name="add_to_wishlist" <?= $disabled ?>></button>
                    <a href="quick_view.php?pid=<?= $fetch_product['id']; ?>" class="fas fa-eye"></a>
                    <?= $stock_status ?>
                    <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" alt="<?= htmlspecialchars($fetch_product['name']); ?>">
                    <div class="name"><?= htmlspecialchars($fetch_product['name']); ?></div>
                    
                    <!-- Show product categories -->
                    <?php
                    $get_product_cats = $conn->prepare("SELECT c.name 
                                                        FROM product_categories c 
                                                        JOIN product_category_mappings pcm ON c.id = pcm.category_id
                                                        WHERE pcm.product_id = ?");
                    $get_product_cats->execute([$fetch_product['id']]);
                    $product_categories = $get_product_cats->fetchAll(PDO::FETCH_COLUMN);
                    
                    if(!empty($product_categories)): ?>
                        <div class="category">
                            <?php foreach($product_categories as $cat): ?>
                                <span><?= htmlspecialchars($cat); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex">
                        <div class="price"><span>KSh</span><?= number_format($fetch_product['price'], 2); ?></div>
                        <input type="number" name="qty" class="qty" min="1" max="<?= min($fetch_product['max_quantity'], $fetch_product['stock']) ?>" 
                               value="1" <?= $disabled ?>>
                    </div>
                    <input type="submit" value="add to cart" class="btn" name="add_to_cart" <?= $disabled ?>>
                    <?php if($fetch_product['requires_prescription']): ?>
                        <div class="prescription-notice">Prescription required</div>
                    <?php endif; ?>
                </form>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty">No products found!</p>
        <?php endif; ?>
    </div>
</section>



<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?= htmlspecialchars($category_name) ?> - Syokichem</title>
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>

</body>
</html>