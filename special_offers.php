<?php
require_once 'components/connect.php';
session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

// Fetch products on special offer
$stmt = $conn->prepare('
    SELECT p.*, s.old_price, s.new_price
    FROM special_offers s
    JOIN products p ON s.product_id = p.id
    WHERE s.is_active = 1
    ORDER BY s.created_at DESC
');
$stmt->execute();
$offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

function get_discount_percent($old, $new) {
    if ($old > 0 && $new < $old) {
        return round((($old - $new) / $old) * 100);
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Offers - Syokichem</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Rubik', sans-serif; background: #f8f9fa; margin: 0; }
        .offers-container { max-width: 1100px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h1 { color: var(--primary-green, #8BC34A); text-align: center; margin-bottom: 2rem; }
        .offers-list { display: flex; flex-wrap: wrap; gap: 2rem; justify-content: center; }
        .offer-card { background: #f5fff2; border: 1px solid #e0f3d6; border-radius: 0.8rem; box-shadow: 0 1px 4px rgba(139,195,74,0.07); width: 270px; padding: 1.5rem; display: flex; flex-direction: column; align-items: center; transition: box-shadow 0.2s; }
        .offer-card:hover { box-shadow: 0 4px 16px rgba(139,195,74,0.16); }
        .offer-card img { width: 100%; height: 180px; object-fit: cover; border-radius: 0.6rem 0.6rem 0 0; margin-bottom: 1rem; display: block; }
        .offer-title { font-size: 1.2rem; font-weight: 600; color: #333; margin-bottom: 0.5rem; text-align: center; }
        .offer-pricing { margin-bottom: 0.7rem; }
        .old-price { color: #b0b0b0; text-decoration: line-through; margin-right: 0.7rem; }
        .new-price { color: var(--primary-green, #8BC34A); font-weight: bold; font-size: 1.2rem; }
        .discount { background: #8BC34A; color: #fff; font-size: 0.9rem; border-radius: 0.4rem; padding: 0.2rem 0.7rem; margin-left: 0.5rem; }
        .add-to-cart-btn { margin-top: auto; background: var(--primary-green, #8BC34A); color: #fff; border: none; border-radius: 0.4rem; padding: 0.6rem 1.2rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .add-to-cart-btn:hover { background: var(--dark-green, #689F38); }
        @media (max-width: 700px) { .offers-list { flex-direction: column; align-items: center; } .offer-card { width: 95%; } }
    </style>
</head>
<body>
    <?php include 'components/user_header.php'; ?>
    <div class="offers-container">
        <h1><i class="fas fa-gift"></i> Special Offers</h1>
        <?php if (count($offers) === 0): ?>
            <p style="text-align:center;color:#888;">No special offers available at the moment. Please check back later!</p>
        <?php else: ?>
        <div class="offers-list">
            <?php foreach ($offers as $offer): ?>
                <div class="offer-card">
                    <img src="images/products/<?= htmlspecialchars($offer['image_01']) ?>" alt="<?= htmlspecialchars($offer['name']) ?>">
                    <div class="offer-title"><?= htmlspecialchars($offer['name']) ?></div>
                    <div class="offer-pricing">
                        <span class="old-price">KSh <?= number_format($offer['old_price'], 2) ?></span>
                        <span class="new-price">KSh <?= number_format($offer['new_price'], 2) ?></span>
                        <?php $discount = get_discount_percent($offer['old_price'], $offer['new_price']); if ($discount): ?>
                        <span class="discount">-<?= $discount ?>%</span>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="" class="add-to-cart-form">
                        <input type="hidden" name="pid" value="<?= $offer['id'] ?>">
                        <input type="hidden" name="name" value="<?= htmlspecialchars($offer['name']) ?>">
                        <input type="hidden" name="price" value="<?= $offer['new_price'] ?>">
                        <input type="hidden" name="image" value="<?= htmlspecialchars($offer['image_01']) ?>">
                        <input type="hidden" name="qty" value="1">
                        <input type="hidden" name="add_to_cart" value="1">
                        <button type="submit" class="add-to-cart-btn"><i class="fas fa-cart-plus"></i> Add to Cart</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php include 'components/footer.php'; ?>
    <script src="js/special_offers_cart.js"></script>
</body>
</html>
