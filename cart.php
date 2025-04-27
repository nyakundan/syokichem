<?php
session_start();
include 'components/connect.php';

$messages = [];
$cart_items = [];
$grand_total = 0;

// ----- Handle Cart Operations -----
try {
    // Remove an item from cart
    if (isset($_POST['delete_item'])) {
        if (!empty($_POST['cart_id'])) {
            if (isset($_SESSION['user_id'])) {
                // Logged-in user - remove from database
                $cart_id = $_POST['cart_id'];
                $delete_stmt = $conn->prepare("DELETE FROM `cart` WHERE id = ? AND user_id = ?");
                $delete_stmt->execute([$cart_id, $_SESSION['user_id']]);
            } else {
                // Guest user - remove from session
                $cart_id = $_POST['cart_id'];
                foreach ($_SESSION['guest_cart'] as $key => $item) {
                    if ($item['id'] == $cart_id) {
                        unset($_SESSION['guest_cart'][$key]);
                        break;
                    }
                }
            }
            $messages[] = "Item removed from cart!";
        }
    }

    // Update quantity of an item in cart
    if (isset($_POST['update_qty'])) {
        if (!empty($_POST['cart_id']) && isset($_POST['qty'])) {
            $cart_id = $_POST['cart_id'];
            $qty = max(1, (int)$_POST['qty']); // Ensure quantity is at least 1
            
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== '') {
                // Logged-in user - update in database
                $update_stmt = $conn->prepare("UPDATE `cart` SET quantity = ? WHERE id = ? AND user_id = ?");
                $update_stmt->execute([$qty, $cart_id, $_SESSION['user_id']]);
            } else {
                // Guest user - update in session
                if (isset($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])) {
                    foreach ($_SESSION['guest_cart'] as $k => $item) {
                        if (isset($item['id']) && $item['id'] == $cart_id) {
                            $_SESSION['guest_cart'][$k]['quantity'] = $qty;
                            break;
                        }
                    }
                }
            }
            $messages[] = "Cart updated successfully!";
        }
    }

    // Clear entire cart
    if (isset($_POST['clear_cart'])) {
        if (isset($_SESSION['user_id'])) {
            // Logged-in user - clear database cart
            $clear_stmt = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
            $clear_stmt->execute([$_SESSION['user_id']]);
        } else {
            // Guest user - clear session cart
            unset($_SESSION['guest_cart']);
        }
        $messages[] = "Cart cleared!";
    }
} catch (Exception $e) {
    error_log("Cart Operation Error: " . $e->getMessage());
    $messages[] = "An error occurred while processing your request.";
}

// ----- Fetch Cart Items -----
try {
    if (isset($_SESSION['user_id'])) {
        // Fetch cart items from database for logged-in users
        $select_stmt = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
        $select_stmt->execute([$_SESSION['user_id']]);
        while ($item = $select_stmt->fetch(PDO::FETCH_ASSOC)) {
            // Ensure id and quantity are set
            if (isset($item['id']) && isset($item['quantity'])) {
                $item['quantity'] = $item['quantity'] ?? 1; // Default to 1 if not set
                $cart_items[] = $item;
                $grand_total += $item['price'] * $item['quantity'];
            } else {
                error_log("Cart Item Error: Missing id or quantity for item - " . print_r($item, true));
            }
        }
    } elseif (isset($_SESSION['guest_cart'])) {
        // Fetch cart items from session for guests
        foreach ($_SESSION['guest_cart'] as $item) {
            // Ensure id and quantity are set
            if (isset($item['id']) && isset($item['quantity'])) {
                $item['quantity'] = $item['quantity'] ?? 1; // Default to 1 if not set
                $cart_items[] = $item;
                $grand_total += $item['price'] * $item['quantity'];
            } else {
                error_log("Guest Cart Item Error: Missing id or quantity for item - " . print_r($item, true));
            }
        }
    }
} catch (Exception $e) {
    error_log("Cart Fetch Error: " . $e->getMessage());
    $messages[] = "Failed to retrieve cart items.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Shopping Cart | Syokichem</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body {
            background: #f7f9fb;
            font-family: 'Poppins', Arial, sans-serif;
        }
        .cart {
            max-width: 950px;
            margin: 40px auto 60px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 32px rgba(0,0,0,0.07);
            padding: 35px 36px 36px 36px;
        }
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e0e3e7;
            margin-bottom: 22px;
        }
        .cart-title {
            font-size: 2.1rem;
            font-weight: 700;
            color: #2d3748;
        }
        .cart-count {
            font-size: 1.1rem;
            color: #5a5a5a;
            background: #f1f3f7;
            border-radius: 16px;
            padding: 4px 14px;
            margin-left: 10px;
        }
        .cart-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            background: #f8fafc;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
            padding: 18px 18px;
            gap: 24px;
        }
        .cart-item img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #e0e3e7;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-size: 1.15rem;
            font-weight: 600;
            color: #222;
            margin-bottom: 6px;
        }
        .item-price {
            color: #388e3c;
            font-size: 1.08rem;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .qty-controls {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 8px;
        }
        .qty-btn {
            background: #e2e8f0;
            color: #3b3b3b;
            border: none;
            border-radius: 4px;
            width: 34px;
            height: 34px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .qty-btn:hover {
            background: #c0c6d1;
        }
        .qty-input {
            width: 48px;
            text-align: center;
            border: 1px solid #e0e3e7;
            border-radius: 4px;
            padding: 6px 0;
            font-size: 1rem;
            background: #fff;
        }
        .item-total {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            margin-left: 16px;
        }
        .remove-form {
            margin-left: 16px;
        }
        .remove-item-btn {
            background: #f44336;
            color: #fff;
            border: none;
            padding: 7px 13px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.2s;
        }
        .remove-item-btn:hover {
            background: #d32f2f;
        }
        .cart-summary {
            margin-top: 30px;
            background: #f8fafc;
            border-radius: 10px;
            padding: 22px 28px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.14rem;
            margin-bottom: 10px;
        }
        .grand-total {
            font-size: 1.23rem;
            font-weight: 700;
            color: #388e3c;
        }
        .cart-actions {
            margin-top: 32px;
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .continue-shopping, .checkout-btn {
            background: #388e3c;
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1.08rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .continue-shopping:hover, .checkout-btn:hover {
            background: #2c6b27;
        }
        .checkout-btn[disabled] {
            background: #bdbdbd;
            cursor: not-allowed;
        }
        .empty-cart {
            text-align: center;
            padding: 70px 0;
            color: #888;
        }
        .empty-cart i {
            font-size: 3.5rem;
            color: #cfd8dc;
            margin-bottom: 12px;
        }
        @media (max-width: 700px) {
            .cart {
                padding: 18px 3vw;
            }
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .cart-summary {
                padding: 18px 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/user_header.php'; ?>

    <section class="cart">
        <div class="cart-header">
            <h1 class="cart-title">Shopping Cart</h1>
            <?php if(!empty($cart_items)): ?>
                <span class="cart-count"><?= count($cart_items) ?> items</span>
            <?php endif; ?>
        </div>

        <?php if(!empty($messages)): ?>
            <?php foreach($messages as $msg): ?>
                <div class="message">
                    <span><?= htmlspecialchars($msg) ?></span>
                    <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if(!empty($cart_items)): ?>
            <div class="cart-container">
                <?php foreach($cart_items as $item): ?>
                    <?php $is_logged_in = isset($_SESSION['user_id']) && $_SESSION['user_id']; ?>
                    <div class="cart-item">
                        <img src="images/products/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                        <div class="item-details">
                            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="item-price">KSh <?= number_format($item['price'], 2) ?></div>
                            <form action="" method="post" class="qty-controls" style="margin-bottom:0;" onsubmit="return false;">
                                <input type="hidden" name="cart_id" value="<?= $is_logged_in ? (isset($item['pid']) ? $item['pid'] : '') : (isset($item['id']) ? $item['id'] : ''); ?>">
                                <button type="button" class="qty-btn" onclick="updateCartQty(this, -1)"><i class="fas fa-minus"></i></button>
                                <input type="number" name="qty" value="<?= htmlspecialchars($item['quantity'] ?? 1); ?>" min="1" class="qty-input" onchange="updateCartQty(this, 0)">
                                <button type="button" class="qty-btn" onclick="updateCartQty(this, 1)"><i class="fas fa-plus"></i></button>
                            </form>
                        </div>
                        <div class="item-total">
                            KSh <?= number_format($item['price'] * ($item['quantity'] ?? 1), 2); ?>
                        </div>
                        <form action="" method="post" class="remove-form" onsubmit="return false;">
                            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                            <button type="button" class="remove-item-btn" data-cart-id="<?= $item['id'] ?>"><i class="fas fa-trash"></i> Remove</button>
                        </form>
                    </div>
                    <?php
                    // Debugging: Log item details
                    error_log("Cart Item - ID: {$item['id']}, Name: {$item['name']}, Quantity: " . ($item['quantity'] ?? 'N/A') . ", Price: {$item['price']}");
                    ?>
                <?php endforeach; ?>
            </div>

            <!-- Coupon Application Section -->
            <div class="cart-coupon-section" style="margin: 2rem 0;">
                <form id="apply-coupon-form" style="display: flex; gap: 1rem; align-items: center;">
                    <input type="text" name="coupon_code" placeholder="Enter coupon code" required style="padding: 0.5rem; font-size: 1rem;">
                    <button type="submit" style="padding: 0.5rem 1.5rem; font-size: 1rem; background: #2c6b27; color: #fff; border: none; border-radius: 5px;">Apply Coupon</button>
                </form>
                <div id="coupon-message" style="margin-top: 0.75rem; color: #2c6b27; font-weight: 500;"></div>
                <div id="applied-coupon-details" style="margin-top: 0.5rem; color: #333; font-size: 1rem;"></div>
            </div>

            <div class="cart-summary">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>KSh <?= number_format($grand_total, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>Calculated at checkout</span>
                </div>
                <div class="summary-row grand-total">
                    <span>Total</span>
                    <span>KSh <?= number_format($grand_total, 2); ?></span>
                </div>
            </div>

            <div class="cart-actions">
                <a href="shop.php" class="continue-shopping">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
                <form action="" method="post" style="display: inline;">
                    <button type="submit" name="clear_cart" class="continue-shopping" onclick="return confirm('Clear all items in cart?')">
                        <i class="fas fa-trash"></i> Clear Cart
                    </button>
                </form>
                <div class="checkout-total">
                    <span>Total Amount</span>
                    <strong>KSh <?= number_format($grand_total, 2); ?></strong>
                </div>
                <a href="checkout.php" class="checkout-btn" <?= empty($cart_items) ? 'disabled' : '' ?>>
                    <i class="fas fa-lock"></i>
                    <span>Proceed to Secure Checkout</span>
                </a>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty</p>
                <a href="shop.php" class="continue-shopping">Start Shopping</a>
            </div>
        <?php endif; ?>
    </section>

    <?php include 'components/footer.php'; ?>

    <script>
        function updateCartQty(el, change) {
            let form = el.closest('.qty-controls');
            let input = form.querySelector('.qty-input');
            let cartId = form.querySelector('input[name="cart_id"]').value;
            let qty = parseInt(input.value);
            if (change === -1 && qty > 1) qty--;
            if (change === 1) qty++;
            if (change !== 0) input.value = qty;

            // Disable controls during request
            form.querySelectorAll('button, input').forEach(e => e.disabled = true);

            fetch('components/cart_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=update_qty&cart_id=${encodeURIComponent(cartId)}&qty=${encodeURIComponent(input.value)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    input.value = data.qty;
                    // Update item subtotal
                    let itemTotal = form.closest('.cart-item').querySelector('.item-total');
                    if (itemTotal) {
                        itemTotal.textContent = 'KSh ' + Number(data.item_subtotal).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
                    }
                    // Update cart totals
                    document.querySelectorAll('.cart-summary .summary-row span:last-child, .checkout-total strong').forEach(e => {
                        if (e.textContent.includes('KSh')) e.textContent = 'KSh ' + Number(data.grand_total).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
                    });
                } else {
                    alert(data.message || 'Cart update failed');
                }
            })
            .catch(() => alert('Network error'))
            .finally(() => {
                form.querySelectorAll('button, input').forEach(e => e.disabled = false);
            });
        }
    </script>

    <script>
        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Remove this item from cart?')) return;
                const cartId = this.dataset.cartId;
                const formData = new URLSearchParams();
                formData.append('action', 'delete_item');
                formData.append('cart_id', cartId);
                this.disabled = true;
                fetch('components/cart_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: formData.toString()
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Remove the cart item from the DOM
                        const cartItem = this.closest('.cart-item');
                        if (cartItem) cartItem.remove();
                        // Update cart totals
                        if (data.grand_total !== undefined) {
                            document.querySelectorAll('.cart-summary .summary-row span:last-child, .checkout-total strong').forEach(e => {
                                if (e.textContent.includes('KSh')) e.textContent = 'KSh ' + Number(data.grand_total).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
                            });
                        }
                        // If cart is empty, show empty cart message
                        if (document.querySelectorAll('.cart-item').length === 0) {
                            document.querySelector('.cart').innerHTML = `<div class='empty-cart'><i class='fas fa-shopping-cart'></i><p>Your cart is empty</p><a href='shop.php' class='continue-shopping'>Start Shopping</a></div>`;
                        }
                    } else {
                        alert(data.message || 'Failed to remove item');
                        this.disabled = false;
                    }
                })
                .catch(() => {
                    alert('Network error');
                    this.disabled = false;
                });
            });
        });
    </script>

    <script>
        document.getElementById('apply-coupon-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const code = this.coupon_code.value.trim();
            if (!code) return;
            const response = await fetch('components/cart_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ apply_coupon: 1, coupon_code: code })
            });
            const result = await response.json();
            document.getElementById('coupon-message').textContent = result.message;
            // Show coupon details if provided
            const detailsDiv = document.getElementById('applied-coupon-details');
            if (result.coupon_details) {
                detailsDiv.innerHTML = `<strong>Coupon:</strong> ${result.coupon_details.code} <br>
                    <strong>Discount:</strong> ${result.coupon_details.discount > 0 ? 'KSh ' + Number(result.coupon_details.discount).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) : 'None'}<br>
                    <strong>Min. Order:</strong> KSh ${Number(result.coupon_details.min_order).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}<br>
                    <strong>Valid till:</strong> ${result.coupon_details.expiry}`;
            } else {
                detailsDiv.innerHTML = '';
            }
            if (result.status === 'success' && result.new_total !== undefined) {
                // Update cart total visually
                const totalEls = document.querySelectorAll('.cart-summary .summary-row span:last-child, .checkout-total strong');
                totalEls.forEach(e => {
                    if (e.textContent.includes('KSh')) {
                        e.textContent = 'KSh ' + Number(result.new_total).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
                    }
                });
            }
        });
    </script>
</body>
</html>