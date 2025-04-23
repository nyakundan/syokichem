<?php
// File: components/wishlist_cart.php

// Check if user is logged in
if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
    
    // Count wishlist items
    $count_wishlist = $conn->prepare("SELECT COUNT(*) FROM `wishlist` WHERE user_id = ?");
    $count_wishlist->execute([$user_id]);
    $total_wishlist = $count_wishlist->fetchColumn();
    
    // Count cart items
    $count_cart = $conn->prepare("SELECT COUNT(*) FROM `cart` WHERE user_id = ?");
    $count_cart->execute([$user_id]);
    $total_cart = $count_cart->fetchColumn();
} else {
    $total_wishlist = 0;
    $total_cart = 0;
}
?>

<style>
    .wishlist-cart-container {
        display: flex;
        gap: 1.5rem;
        align-items: center;
    }
    
    .wishlist-cart-btn {
        position: relative;
        color: var(--dark);
        font-size: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .wishlist-cart-btn:hover {
        color: var(--primary);
        transform: translateY(-3px);
    }
    
    .wishlist-cart-count {
        position: absolute;
        top: -10px;
        right: -10px;
        background: var(--primary);
        color: white;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: bold;
    }
    
    @media (max-width: 768px) {
        .wishlist-cart-container {
            gap: 1rem;
        }
        
        .wishlist-cart-btn {
            font-size: 1.3rem;
        }
    }
</style>

<div class="wishlist-cart-container">
    <!-- Wishlist Button removed -->
    
    <!-- Cart Button -->
    <a href="cart.php" class="wishlist-cart-btn">
        <i class="fas fa-shopping-cart"></i>
        <?php if($total_cart > 0): ?>
            <span class="wishlist-cart-count"><?= $total_cart ?></span>
        <?php endif; ?>
    </a>
</div>