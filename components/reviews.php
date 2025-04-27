<?php
// Display reviews for a product
if (!isset($pid)) return;

// Handle review submission
if (isset($_POST['add_review'], $_POST['rating'], $_POST['review_text'])) {
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review_text']);
    $user_id = $_SESSION['user_id'] ?? null;
    if ($rating >= 1 && $rating <= 5 && $review_text !== '') {
        $insert = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, review_text, created_at) VALUES (?, ?, ?, ?, NOW())");
        $insert->execute([$pid, $user_id, $rating, $review_text]);
        // Refresh reviews after insert
        $stmt = $conn->prepare("SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
        $stmt->execute([$pid]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo '<script>document.addEventListener("DOMContentLoaded",function(){document.getElementById(\'add-review-form\').style.display=\'none\';});</script>';
    }
}

$stmt = $conn->prepare("SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$pid]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="reviews-section" style="font-size:1.25rem;">
    <h2 style="font-size:1.45rem;">Reviews</h2>
    <?php if (count($reviews) === 0): ?>
        <div class="no-reviews" style="font-size:1.15rem;">No reviews yet. Be the first to review this product!</div>
    <?php else: ?>
        <?php foreach ($reviews as $review): ?>
            <div class="review" style="font-size:1.18rem;">
                <div class="review-user"><strong><?= htmlspecialchars($review['user_name'] ?? 'Anonymous') ?></strong> <span class="review-date" style="font-size:1.07rem;"><?= date('M d, Y', strtotime($review['created_at'])) ?></span></div>
                <div class="review-rating">Rating: <?= (int)$review['rating'] ?>/5</div>
                <div class="review-text"><?= nl2br(htmlspecialchars($review['review_text'])) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <button class="btn add-review-btn" onclick="document.getElementById('add-review-form').style.display='block';">Add Review</button>
    <form id="add-review-form" class="add-review-form" action="" method="post" style="display:none; font-size:1.13rem;">
        <h3 style="font-size:1.18rem;">Add Your Review</h3>
        <label>Rating:
            <select name="rating" required>
                <option value="">Select</option>
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Good</option>
                <option value="3">3 - Average</option>
                <option value="2">2 - Poor</option>
                <option value="1">1 - Terrible</option>
            </select>
        </label>
        <label>Review:<br>
            <textarea name="review_text" rows="4" required></textarea>
        </label>
        <input type="hidden" name="add_review" value="1">
        <button type="submit" class="btn">Submit Review</button>
        <button type="button" class="btn" onclick="document.getElementById('add-review-form').style.display='none';">Cancel</button>
    </form>
</div>
