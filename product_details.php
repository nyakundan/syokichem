<?php
session_start();
include 'components/wishlist_cart.php';
include 'components/connect.php';

if (!isset($_GET['pid']) || !is_numeric($_GET['pid'])) {
    die('Invalid product ID.');
}
$pid = (int)$_GET['pid'];

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$pid]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die('Product not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | Product Details</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'components/user_header.php'; ?>

<section class="product-details">
    <div class="container">
        <div class="product-details-card">
            <div class="product-images">
                <img src="images/products/<?= htmlspecialchars($product['image_01']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <?php if (!empty($product['image_02'])): ?>
                    <img src="images/products/<?= htmlspecialchars($product['image_02']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <?php endif; ?>
                <?php if (!empty($product['image_03'])): ?>
                    <img src="images/products/<?= htmlspecialchars($product['image_03']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <?php endif; ?>
            </div>
            <div class="product-info">
                <h1><?= htmlspecialchars($product['name']) ?></h1>
                <div class="price">KSh <?= number_format($product['price'], 2) ?></div>
                <?php if (!empty($product['old_price'])): ?>
                    <div class="old-price">Old Price: <s>KSh <?= number_format($product['old_price'], 2) ?></s></div>
                <?php endif; ?>
                <div class="description" style="font-size:1.4rem;"><strong>Description:</strong> <?= nl2br(htmlspecialchars($product['description'])) ?></div>
                <div class="how-to-use" style="font-size:1.4rem;"><strong>How to Use:</strong> <?= $product['dosage'] !== null && $product['dosage'] !== '' ? nl2br(htmlspecialchars($product['dosage'])) : 'N/A' ?></div>
                <div class="precautions" style="font-size:1.4rem;"><strong>Precautions:</strong> <?= nl2br(htmlspecialchars($product['precautions'] ?? 'N/A')) ?></div>
                <?php if (!empty($product['ingredients'])): ?>
                    <div class="ingredients" style="font-size:1.4rem;"><strong>Ingredients:</strong> <?= nl2br(htmlspecialchars($product['ingredients'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($product['manufacturer'])): ?>
                    <div class="manufacturer" style="font-size:1.4rem;"><strong>Manufacturer:</strong> <?= htmlspecialchars($product['manufacturer']) ?></div>
                <?php endif; ?>
                <div class="stock"><strong>Stock:</strong> <?= (int)$product['stock'] ?></div>
                <?php if ($product['requires_prescription']): ?>
                    <div class="prescription"><strong>Requires Prescription</strong></div>
                <?php endif; ?>
                <form action="" method="post" class="add-to-cart-form">
                    <input type="hidden" name="pid" value="<?= $product['id'] ?>">
                    <input type="hidden" name="name" value="<?= htmlspecialchars($product['name']) ?>">
                    <input type="hidden" name="price" value="<?= $product['price'] ?>">
                    <input type="hidden" name="image" value="<?= htmlspecialchars($product['image_01']) ?>">
                    <input type="number" name="qty" min="1" max="<?= (int)$product['max_quantity'] ?>" value="1" class="qty-input">
                    <button type="submit" name="add_to_cart" class="btn">Add to Cart</button>
                </form>
            </div>
        </div>
        <?php include 'components/reviews.php'; ?>
        <?php include 'components/recently_viewed.php'; ?>
    </div>
</section>

<?php include 'components/footer.php'; ?>
<script>
// AJAX Add to Cart Handler for Product Details Page
function showCartPopup(message, success = true) {
    let popup = document.createElement('div');
    popup.className = 'cart-popup-message ' + (success ? 'success' : 'error');
    popup.innerText = message;
    document.body.appendChild(popup);
    setTimeout(() => { popup.classList.add('show'); }, 10);
    setTimeout(() => { popup.classList.remove('show'); setTimeout(()=>popup.remove(), 400); }, 2400);
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.add-to-cart-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            fetch('ajax/add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                showCartPopup(data.message, data.success);
                if (data.success) {
                  // Optionally update cart icon/count here via AJAX
                }
            })
            .catch(() => showCartPopup('An error occurred!', false));
        });
    }
});
</script>
<style>
.cart-popup-message {
  position: fixed;
  top: 32px;
  right: 32px;
  z-index: 9999;
  background: #fff;
  color: #222;
  padding: 1.1rem 2.2rem;
  border-radius: 0.7rem;
  box-shadow: 0 4px 24px rgba(0,0,0,0.18);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.35s, transform 0.35s;
  transform: translateY(-30px);
  font-size: 1.1rem;
  font-weight: 600;
}
.cart-popup-message.success {
  border-left: 6px solid #60c060;
}
.cart-popup-message.error {
  border-left: 6px solid #e74c3c;
}
.cart-popup-message.show {
  opacity: 1;
  pointer-events: auto;
  transform: translateY(0);
}
</style>
</body>
</html>
