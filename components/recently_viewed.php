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
<style>
.recently-viewed-section {
    padding: 2rem 0;
    margin-top: 2rem;
    border-top: 1px solid #eee;
}

.recently-viewed-section h2 {
    margin-bottom: 1.5rem;
    color: var(--text-dark);
    font-weight: 600;
}

.recently-viewed-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.recent-product-card {
    background: var(--pure-white);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1rem;
    transition: transform 0.3s ease;
}

.recent-product-card:hover {
    transform: translateY(-5px);
}

.recent-product-card a {
    text-decoration: none;
    color: inherit;
}

.recent-product-card .recent-img {
    width: 100%;
    height: auto;
    max-height: 200px;
    object-fit: contain;
    border-radius: var(--border-radius);
    background: transparent;
    margin-bottom: 1rem;
}

.recent-product-card .name {
    color: var(--text-dark);
    margin-top: 0.5rem;
    text-align: center;
}

.no-recent {
    color: var(--text-medium);
    text-align: center;
    padding: 1rem;
}

@media (max-width: 768px) {
    .recently-viewed-list {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .recent-product-card .recent-img {
        max-height: 150px;
    }
}
</style>
<div class="recently-viewed-section">
    <h2>Recently Viewed Products</h2>
    <?php if (empty($recent_products)): ?>
        <div class="no-recent">No other recently viewed products.</div>
    <?php else: ?>
        <div class="recently-viewed-list">
            <?php foreach ($recent_products as $rp): ?>
                <div class="recent-product-card">
                    <a href="product_details.php?pid=<?= $rp['id'] ?>">
                        <img src="images/products/<?= htmlspecialchars($rp['image_01']) ?>" 
                             alt="<?= htmlspecialchars($rp['name']) ?>" 
                             class="recent-img" 
                             onerror="if(!this._errored){this._errored=true;this.src='images/default-product.jpg';}">
                        <div class="name"><?= htmlspecialchars($rp['name']) ?></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
