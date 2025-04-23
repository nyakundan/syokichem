<?php
declare(strict_types=1);
require_once __DIR__ . '/connect.php';
// DEBUG LOGGING: Output POST data for troubleshooting
file_put_contents(__DIR__ . '/debug_cart_handler.log', date('Y-m-d H:i:s') . "\nPOST: " . print_r($_POST, true) . "\n\n", FILE_APPEND);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle add to cart
if(isset($_POST['add_to_cart'])){
    session_start();
    $pid = filter_input(INPUT_POST, 'pid', FILTER_SANITIZE_NUMBER_INT);
    $name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $qty = filter_input(INPUT_POST, 'qty', FILTER_SANITIZE_NUMBER_INT) ?? 1;
    $image = htmlspecialchars($_POST['image'] ?? '', ENT_QUOTES, 'UTF-8');

    if (!$pid || !$name || !isset($price) || !$image) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product data']);
        exit();
    }

    // Check stock availability
    $check_stock = $conn->prepare("SELECT stock FROM `products` WHERE id = ?");
    $check_stock->execute([$pid]);
    $stock = $check_stock->fetchColumn();

    if ($qty > $stock) {
        echo json_encode(['status' => 'error', 'message' => 'Requested quantity exceeds available stock!']);
        exit();
    }

    if(isset($_SESSION['user_id']) && $_SESSION['user_id']){
        $user_id = $_SESSION['user_id'];
        // Check if product already exists in cart
        $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ? AND pid = ?");
        $check_cart->execute([$user_id, $pid]);
        if($check_cart->rowCount() > 0){
            echo json_encode(['status' => 'error', 'message' => 'Product already exists in cart!']);
            exit();
        } else {
            // Insert new product into cart
            $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
            $insert_cart->execute([$user_id, $pid, $name, $price, $qty, $image]);
            echo json_encode(['status' => 'success', 'message' => 'Product added to cart successfully!']);
            exit();
        }
    } else {
        // Guest user: store cart in session
        if(!isset($_SESSION['guest_cart'])){
            $_SESSION['guest_cart'] = [];
        }
        // Check if product already exists in guest cart
        foreach($_SESSION['guest_cart'] as $item){
            if($item['id'] == $pid){
                echo json_encode(['status' => 'error', 'message' => 'Product already exists in cart!']);
                exit();
            }
        }
        $_SESSION['guest_cart'][] = [
            'id' => $pid,
            'name' => $name,
            'price' => $price,
            'quantity' => $qty,
            'image' => $image
        ];
        echo json_encode(['status' => 'success', 'message' => 'Product added to cart successfully!']);
        exit();
    }
}

// Handle AJAX quantity update
if(isset($_POST['action']) && $_POST['action'] === 'update_qty'){
    session_start();
    $cart_id = filter_input(INPUT_POST, 'cart_id', FILTER_SANITIZE_NUMBER_INT);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $response = ['status' => 'error', 'message' => 'Unknown error'];

    if(!$cart_id){
        echo json_encode(['status' => 'error', 'message' => 'Invalid cart item.']);
        exit();
    }

    require_once __DIR__ . '/connect.php';
    $product_id = $cart_id;
    $product_price = null;
    // For guests, find the product ID from the session cart if needed
    if(!isset($_SESSION['user_id']) || !$_SESSION['user_id']){
        if(isset($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])){
            foreach($_SESSION['guest_cart'] as $item){
                if(isset($item['id']) && $item['id'] == $cart_id){
                    $product_id = $item['id'];
                    $product_price = $item['price'];
                    break;
                }
            }
        }
    }
    // Check stock and get price from DB
    $check_stock = $conn->prepare("SELECT stock, price FROM `products` WHERE id = ?");
    $check_stock->execute([$product_id]);
    $product = $check_stock->fetch(PDO::FETCH_ASSOC);
    if(!$product){
        echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
        exit();
    }
    if($qty > $product['stock']){
        echo json_encode(['status' => 'error', 'message' => 'Requested quantity exceeds available stock!']);
        exit();
    }
    $current_price = isset($product['price']) ? $product['price'] : $product_price;

    if(isset($_SESSION['user_id']) && $_SESSION['user_id']){
        // Logged-in user: update in DB
        $update_stmt = $conn->prepare("UPDATE `cart` SET quantity = ? WHERE pid = ? AND user_id = ?");
        $update_stmt->execute([$qty, $product_id, $_SESSION['user_id']]);
        // Get new cart total
        $cart_total_stmt = $conn->prepare("SELECT SUM(price * quantity) FROM `cart` WHERE user_id = ?");
        $cart_total_stmt->execute([$_SESSION['user_id']]);
        $grand_total = (float)($cart_total_stmt->fetchColumn() ?: 0);
        $item_subtotal = $current_price * $qty;
        $response = [
            'status' => 'success',
            'qty' => $qty,
            'item_subtotal' => $item_subtotal,
            'grand_total' => $grand_total,
            'message' => 'Cart updated successfully!'
        ];
    } else {
        // Guest user: update in session
        if(isset($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])){
            foreach($_SESSION['guest_cart'] as $k => $item){
                if(isset($item['id']) && $item['id'] == $product_id){
                    $_SESSION['guest_cart'][$k]['quantity'] = $qty;
                    $item_subtotal = $current_price * $qty;
                    // Recalculate grand total
                    $grand_total = 0;
                    foreach($_SESSION['guest_cart'] as $it){
                        $grand_total += $it['price'] * $it['quantity'];
                    }
                    $response = [
                        'status' => 'success',
                        'qty' => $qty,
                        'item_subtotal' => $item_subtotal,
                        'grand_total' => $grand_total,
                        'message' => 'Cart updated successfully!'
                    ];
                    break;
                }
            }
        }
    }
    echo json_encode($response);
    exit();
}

// Handle AJAX cart item delete
if(isset($_POST['action']) && $_POST['action'] === 'delete_item'){
    session_start();
    $cart_id = filter_input(INPUT_POST, 'cart_id', FILTER_SANITIZE_NUMBER_INT);
    $response = ['status' => 'error', 'message' => 'Unknown error'];
    if(!$cart_id){
        echo json_encode(['status' => 'error', 'message' => 'Invalid cart item.']);
        exit();
    }
    require_once __DIR__ . '/connect.php';
    if(isset($_SESSION['user_id']) && $_SESSION['user_id']){
        // Logged-in user: delete from DB
        $delete_stmt = $conn->prepare("DELETE FROM `cart` WHERE id = ? AND user_id = ?");
        $delete_stmt->execute([$cart_id, $_SESSION['user_id']]);
        // Get new cart total
        $cart_total_stmt = $conn->prepare("SELECT SUM(price * quantity) FROM `cart` WHERE user_id = ?");
        $cart_total_stmt->execute([$_SESSION['user_id']]);
        $grand_total = (float)($cart_total_stmt->fetchColumn() ?: 0);
        $response = [
            'status' => 'success',
            'grand_total' => $grand_total,
            'message' => 'Item removed from cart!'
        ];
    } else {
        // Guest user: delete from session
        if(isset($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])){
            foreach($_SESSION['guest_cart'] as $k => $item){
                if(isset($item['id']) && $item['id'] == $cart_id){
                    unset($_SESSION['guest_cart'][$k]);
                    break;
                }
            }
            // Recalculate grand total
            $grand_total = 0;
            foreach($_SESSION['guest_cart'] as $it){
                $grand_total += $it['price'] * $it['quantity'];
            }
            $response = [
                'status' => 'success',
                'grand_total' => $grand_total,
                'message' => 'Item removed from cart!'
            ];
        }
    }
    echo json_encode($response);
    exit();
}

// Handle add to wishlist
if(isset($_POST['add_to_wishlist'])) {
    if(!isset($_SESSION['user_id'])){
        echo json_encode(['status' => 'error', 'message' => 'Please login first to add items to wishlist']);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    
    $pid = filter_input(INPUT_POST, 'pid', FILTER_SANITIZE_NUMBER_INT);
    $name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $image = htmlspecialchars($_POST['image'] ?? '', ENT_QUOTES, 'UTF-8');

    if (!$pid || !$name || !isset($price) || !$image) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product data']);
        exit();
    }

    // Check if product already exists in wishlist
    $check_wishlist = $conn->prepare("SELECT * FROM `wishlist` WHERE user_id = ? AND pid = ?");
    $check_wishlist->execute([$user_id, $pid]);

    if($check_wishlist->rowCount() > 0){
        echo json_encode(['status' => 'error', 'message' => 'Product already exists in wishlist!']);
        exit();
    } else {
        // Insert new product into wishlist
        $insert_wishlist = $conn->prepare("INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES(?,?,?,?,?)");
        $insert_wishlist->execute([$user_id, $pid, $name, $price, $image]);
        echo json_encode(['status' => 'success', 'message' => 'Product added to wishlist successfully!']);
        exit();
    }
}

// Handle apply coupon
if (isset($_POST['apply_coupon'])) {
    session_start();
    $code = trim($_POST['coupon_code'] ?? '');
    if ($code === '') {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a coupon code.']);
        exit();
    }
    // Check coupon validity
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND expiry_date >= CURDATE()");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$coupon) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired coupon code.']);
        exit();
    }
    // Calculate current cart total
    $cart_total = 0;
    if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
        $stmt = $conn->prepare("SELECT SUM(price * quantity) FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_total = (float)($stmt->fetchColumn() ?: 0);
    } elseif (isset($_SESSION['guest_cart'])) {
        foreach ($_SESSION['guest_cart'] as $item) {
            $cart_total += $item['price'] * $item['quantity'];
        }
    }
    // Check minimum order
    $min_order = isset($coupon['min_order_amount']) ? (float)$coupon['min_order_amount'] : 0;
    if ($cart_total < $min_order) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Minimum order for this coupon is KSh ' . number_format($min_order, 2),
            'coupon_details' => [
                'code' => $coupon['code'],
                'discount' => isset($coupon['discount_amount']) ? $coupon['discount_amount'] : ($coupon['discount_percent'] ?? '') . '%',
                'min_order' => $min_order,
                'expiry' => $coupon['expiry_date']
            ]
        ]);
        exit();
    }
    // Ensure coupon has a valid discount value based on your schema
    if (
        empty($coupon['discount_value']) || $coupon['discount_value'] <= 0 ||
        !in_array($coupon['discount_type'], ['fixed', 'percentage'])
    ) {
        echo json_encode([
            'status' => 'error',
            'message' => 'This coupon does not have a valid discount value.',
            'coupon_details' => [
                'code' => $coupon['code'],
                'discount' => 'None',
                'min_order' => $min_order,
                'expiry' => $coupon['expiry_date']
            ]
        ]);
        exit();
    }
    // Save coupon to session
    $_SESSION['applied_coupon'] = $coupon;
    // Apply discount (fixed or percentage)
    $discount = 0;
    if ($coupon['discount_type'] === 'fixed' && $coupon['discount_value'] > 0) {
        $discount = (float)$coupon['discount_value'];
    } elseif ($coupon['discount_type'] === 'percentage' && $coupon['discount_value'] > 0) {
        $discount = $cart_total * ((float)$coupon['discount_value'] / 100);
        // Enforce max_discount_amount if set
        if (!empty($coupon['max_discount_amount']) && $discount > (float)$coupon['max_discount_amount']) {
            $discount = (float)$coupon['max_discount_amount'];
        }
    }
    $new_total = max(0, $cart_total - $discount);
    echo json_encode([
        'status' => 'success',
        'message' => 'Coupon "' . $coupon['code'] . '" applied! Discount: ' . ($discount > 0 ? 'KSh ' . number_format($discount,2) : 'None'),
        'new_total' => $new_total,
        'coupon_details' => [
            'code' => $coupon['code'],
            'discount' => $discount,
            'min_order' => $min_order,
            'expiry' => $coupon['expiry_date']
        ]
    ]);
    exit();
}

// Catch-all error handler for unexpected failures
if (!headers_sent()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'An unknown error occurred in cart handler']);
    exit();
}
