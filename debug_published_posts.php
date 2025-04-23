<?php
require __DIR__ . '/admin/components/connect.php';

// Get all published posts
$published_posts = $conn->query("SELECT id, title FROM blog_posts WHERE status = 'published'")->fetchAll();

echo "<h2>All Published Posts</h2>";
echo "<ul>";
foreach ($published_posts as $post) {
    // Check category assignments
    $categories = $conn->query("SELECT c.name FROM blog_post_categories pc JOIN blog_categories c ON pc.category_id = c.id WHERE pc.post_id = {$post['id']}")->fetchAll();
    
    echo "<li>";
    echo "<strong>{$post['title']}</strong> (ID: {$post['id']})";
    if ($categories) {
        echo " - Categories: ".implode(', ', array_column($categories, 'name'));
    } else {
        echo " - <span style='color:red'>No categories assigned</span>";
    }
    echo "</li>";
}
echo "</ul>";
?>
