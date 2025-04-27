<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($product)) return;

// Add current product to recently viewed session
if (!isset($_SESSION['recently_viewed'])) {
    $_SESSION['recently_viewed'] = [];
}
$curr_id = $product['id'];
// Remove if already in the list
$_SESSION['recently_viewed'] = array_diff($_SESSION['recently_viewed'], [$curr_id]);
array_unshift($_SESSION['recently_viewed'], $curr_id);
// Limit to 6
$_SESSION['recently_viewed'] = array_slice($_SESSION['recently_viewed'], 0, 6);

if (count($_SESSION['recently_viewed']) > 1) {
    // Fetch recently viewed except current
    $placeholders = implode(',', array_fill(0, count($_SESSION['recently_viewed']) - 1, '?'));
    $ids = array_slice($_SESSION['recently_viewed'], 1);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $recent_products = [];
}
?>
<div class="recently-viewed-section" style="font-size:1.23rem;">
    <h2 style="font-size:1.35rem;">Recently Viewed Products</h2>
    <?php if (empty($recent_products)): ?>
        <div class="no-recent" style="font-size:1.07rem;">No other recently viewed products.</div>
    <?php else: ?>
        <div class="recently-viewed-list">
            <?php foreach ($recent_products as $rp): ?>
                <div class="recent-product-card">
                    <a href="product_details.php?pid=<?= $rp['id'] ?>">
                        <img src="images/products/<?= htmlspecialchars($rp['image_01']) ?>" alt="<?= htmlspecialchars($rp['name']) ?>" class="recent-img" onerror="if(!this._errored){this._errored=true;this.src='images/place.jpg';}">
                        <div class="name" style="font-size:1.13rem;"><?= htmlspecialchars($rp['name']) ?></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
