<?php
echo password_hash('admin123', PASSWORD_BCRYPT);
?>




/<?php
// Display latest blog posts on homepage
$latestPosts = $conn->query("
    SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, 
           p.created_at, c.name as category_name
    FROM blog_posts p
    LEFT JOIN blog_categories c ON p.category_id = c.id
    WHERE p.status = 'published'
    ORDER BY p.published_at DESC
    LIMIT 3
")->fetchAll();
?>

<section class="blog-section py-5">
    <div class="container">
        <h2 class="text-center mb-5">Latest Pharmacy News & Tips</h2>
        
        <div class="row">
            <?php foreach ($latestPosts as $post): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if ($post['featured_image']): ?>
                    <img src="/<?= htmlspecialchars($post['featured_image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($post['title']) ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <span class="badge bg-primary mb-2"><?= htmlspecialchars($post['category_name']) ?></span>
                        <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($post['excerpt']) ?></p>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">Posted on <?= date('M j, Y', strtotime($post['created_at'])) ?></small>
                        <a href="/blog/<?= htmlspecialchars($post['slug']) ?>" class="btn btn-sm btn-outline-primary float-end">Read More</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="/blog" class="btn btn-primary">View All Articles</a>
        </div>
    </div>
</section>











// Display messages
if(isset($message)) {
    if(is_array($message)) {
        foreach($message as $msg) {
            echo '
            <div class="message">
                <span>'.htmlspecialchars($msg).'</span>
                <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
            ';
        }
    } else {
        echo '
        <div class="message">
            <span>'.htmlspecialchars($message).'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
        </div>
        ';
    }
}




<!-- FAQ Search -->
<div class="search-container">
   <input type="text" placeholder="Search FAQs..." id="faqSearch">
   <button type="button"><i class="fas fa-search"></i></button>
</div>
