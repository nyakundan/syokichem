<?php
session_start();
// Simple Quick View for a Product
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'components/connect.php';
include 'components/user_header.php';

$pid = isset($_GET['pid']) ? filter_var($_GET['pid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$product = null;
$more_products = [];
if ($pid > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$pid]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    // Fetch more products (excluding current)
    $more_stmt = $conn->prepare("SELECT * FROM products WHERE id != ? ORDER BY RAND() LIMIT 4");
    $more_stmt->execute([$pid]);
    $more_products = $more_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle Add to Cart
$user_id = $_SESSION['user_id'] ?? '';
if(isset($_POST['add_to_cart'])) {
    $pid = $_POST['pid'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $qty = $_POST['qty'];
    $image = $_POST['image'];
    // Stock check
    $stock_stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stock_stmt->execute([$pid]);
    $stock = $stock_stmt->fetchColumn();
    if($stock < $qty) {
        $cart_message = 'Not enough stock available!';
    } else {
        if($user_id != '') {
            $check_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND pid = ?");
            $check_cart->execute([$user_id, $pid]);
            if($check_cart->rowCount() > 0){
                $cart_message = 'Product already exists in cart!';
            }else{
                $insert_cart = $conn->prepare("INSERT INTO cart(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
                $insert_cart->execute([$user_id, $pid, $name, $price, $qty, $image]);
                $cart_message = 'Product added to cart successfully!';
            }
        } else {
            // Guest cart (session)
            if(!isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];
            $duplicate = false;
            foreach($_SESSION['guest_cart'] as &$item) {
                if($item['id'] == $pid) {
                    $duplicate = true;
                    break;
                }
            }
            if($duplicate) {
                $cart_message = 'Product already exists in cart!';
            } else {
                $_SESSION['guest_cart'][] = [
                    'id' => $pid,
                    'name' => $name,
                    'price' => $price,
                    'quantity' => $qty,
                    'image' => $image
                ];
                $cart_message = 'Product added to cart successfully!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick View</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .quickview-container { max-width: 500px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px #ccc; padding: 30px; }
        .quickview-img { width: 100%; max-width: 250px; display: block; margin: 0 auto 20px; border-radius: 8px; }
        .quickview-title { font-size: 1.5em; font-weight: bold; margin-bottom: 10px; }
        .quickview-price { color: #388e3c; font-size: 1.2em; margin-bottom: 10px; }
        .quickview-desc { color: #444; margin-bottom: 20px; }
        .quickview-stock { margin-bottom: 10px; }
        .quickview-back { display: inline-block; margin-top: 20px; color: #fff; background: #388e3c; padding: 10px 20px; border-radius: 5px; text-decoration: none; }
        .quickview-form { margin-bottom: 20px; }
        .cart-message { color: #388e3c; font-weight: bold; margin-bottom: 12px; }
        .more-products { margin: 40px auto 0; max-width: 900px; }
        .more-products-title { font-size: 1.2em; font-weight: bold; margin-bottom: 16px; }
        .more-products-grid { display: flex; flex-wrap: wrap; gap: 25px; justify-content: center; }
        .more-product { background: #f8f8f8; border-radius: 8px; padding: 16px; width: 200px; text-align: center; box-shadow: 0 1px 6px #eee; }
        .more-product img { width: 100%; max-width: 120px; margin: 0 auto 10px; border-radius: 6px; }
        .more-product .name { font-weight: bold; margin-bottom: 4px; }
        .more-product .price { color: #388e3c; margin-bottom: 6px; }
        .more-product .desc { font-size: 0.95em; color: #555; margin-bottom: 6px; }
        .more-product .stock { font-size: 0.92em; margin-bottom: 4px; }
        .more-product .btn { margin-top: 8px; }
    </style>
</head>
<body>
    <div class="quickview-container">
        <?php if(isset($cart_message)): ?>
            <div class="cart-message"><?= htmlspecialchars($cart_message) ?></div>
        <?php endif; ?>
        <?php if ($product): ?>
            <form action="" method="post" class="quickview-form">
                <input type="hidden" name="pid" value="<?= htmlspecialchars($product['id']) ?>">
                <input type="hidden" name="name" value="<?= htmlspecialchars($product['name']) ?>">
                <input type="hidden" name="price" value="<?= htmlspecialchars($product['price']) ?>">
                <input type="hidden" name="image" value="<?= htmlspecialchars($product['image_01']) ?>">
                <input type="hidden" name="qty" value="1">
                <img src="uploaded_img/<?= htmlspecialchars($product['image_01']) ?>"
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     class="quickview-img"
                     onerror="if(!this._errored){this._errored=true;this.src='images/place.jpg';}">
                <div class="quickview-title"><?= htmlspecialchars($product['name']) ?></div>
                <div class="quickview-price">KSh <?= number_format($product['price'], 2) ?></div>
                <?php if (isset($product['details']) && $product['details'] !== null && $product['details'] !== ''): ?>
                    <div class="quickview-desc">Description: <?= nl2br(htmlspecialchars($product['details'])) ?></div>
                <?php endif; ?>
                <?php if (isset($product['manufacturer']) && $product['manufacturer'] !== ''): ?>
                    <div style="margin-bottom:8px;">Manufacturer: <b><?= htmlspecialchars($product['manufacturer']) ?></b></div>
                <?php endif; ?>
                <?php if (isset($product['dosage']) && $product['dosage'] !== ''): ?>
                    <div style="margin-bottom:8px;">Dosage: <b><?= htmlspecialchars($product['dosage']) ?></b></div>
                <?php endif; ?>
                <?php if (isset($product['prescription_required'])): ?>
                    <div style="margin-bottom:8px;">
                        Prescription Required: <b><?= ($product['prescription_required'] ? 'Yes' : 'No') ?></b>
                    </div>
                <?php endif; ?>
                <div class="quickview-stock">
                    <?php if ((int)($product['stock'] ?? 0) > 0): ?>
                        <span style="color: #388e3c;">In Stock: <?= $product['stock'] ?></span>
                    <?php else: ?>
                        <span style="color: #e53935;">Out of Stock</span>
                    <?php endif; ?>
                </div>
                <div style="margin-bottom:8px;">Category: <b><?= htmlspecialchars($product['category'] ?? 'N/A') ?></b></div>
                <div style="margin-bottom:8px;">Brand: <b><?= htmlspecialchars($product['brand'] ?? 'N/A') ?></b></div>
                <div style="margin-bottom:8px;">Added: <b><?= htmlspecialchars($product['created_at'] ?? 'N/A') ?></b></div>
                <!-- Removed quantity input. Increment/decrement will be handled in the cart. -->
                <button type="submit" class="btn" name="add_to_cart" <?= ((int)($product['stock'] ?? 0) <= 0) ? 'disabled' : '' ?>>Add to Cart</button>
            </form>
        <?php else: ?>
            <div style="color: #e53935; font-weight: bold;">Product not found!</div>
        <?php endif; ?>
        <a href="shop.php" class="quickview-back">Back to Shop</a>
    </div>

    <?php if (!empty($more_products)): ?>
    <div class="more-products">
        <div class="more-products-title">More Products</div>
        <div class="more-products-grid">
            <?php foreach ($more_products as $mp): ?>
                <div class="more-product">
                    <img src="uploaded_img/<?= htmlspecialchars($mp['image_01']) ?>" alt="<?= htmlspecialchars($mp['name']) ?>" onerror="if(!this._errored){this._errored=true;this.src='images/place.jpg';}">
                    <div class="name"><?= htmlspecialchars($mp['name']) ?></div>
                    <div class="price">KSh <?= number_format($mp['price'], 2) ?></div>
                    <div class="desc">Description: <?= htmlspecialchars(mb_strimwidth($mp['details'] ?? '', 0, 60, '...')) ?></div>
                    <div class="stock">Stock: <?= (int)($mp['stock'] ?? 0) ?></div>
                    <div>Category: <b><?= htmlspecialchars($mp['category'] ?? 'N/A') ?></b></div>
                    <div>Brand: <b><?= htmlspecialchars($mp['brand'] ?? 'N/A') ?></b></div>
                    <a href="quick_view.php?pid=<?= $mp['id'] ?>" class="btn">Quick View</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

<?php include 'components/footer.php'; ?>
</body>
</html>