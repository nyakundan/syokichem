<?php
require __DIR__ . '/admin/components/connect.php';

// Debug health category
$health_category = $conn->query("SELECT id, name FROM blog_categories WHERE name LIKE '%health%' OR name LIKE '%Health%' LIMIT 1")->fetch();

echo "<h2>Health Category</h2>";
echo "<pre>".print_r($health_category, true)."</pre>";

if ($health_category) {
    // Debug posts in this category
    $articles = $conn->query("SELECT bp.id, bp.title, bp.status, COUNT(bpc.post_id) as category_count 
        FROM blog_posts bp
        JOIN blog_post_categories bpc ON bp.id = bpc.post_id
        WHERE bpc.category_id = {$health_category['id']}
        GROUP BY bp.id")->fetchAll();
    
    echo "<h2>Articles in Health Category</h2>";
    echo "<pre>".print_r($articles, true)."</pre>";
    
    // Check published status
    $published = $conn->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn();
    echo "<p>Total published posts: $published</p>";
}
?>
