<?php
// blog-post.php
include 'components/connect.php';
include 'components/user_header.php';

// Get slug from query string
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// Fetch blog post from database (assume table: blog_posts)
$post = null;
if ($slug) {
    $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $post ? htmlspecialchars($post['title']) : 'Blog Post' ?> | Syokichem</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-size: 1.6rem; line-height: 1.7; font-family: 'Rubik', sans-serif; background: #f8f9fa; color: #222; }
        .blog-container { max-width: 800px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 2.5rem 2rem; }
        .blog-title { font-size: 2.6rem; color: var(--primary-green, #689F38); font-weight: 700; margin-bottom: 1.2rem; }
        .blog-meta { color: #757575; font-size: 1.2rem; margin-bottom: 2rem; }
        .blog-content { font-size: 1.6rem; color: #222; }
        .blog-content h2, .blog-content h3 { color: var(--primary-green, #689F38); margin-top: 2rem; }
        .blog-content ul { margin-left: 2rem; }
        .blog-content li { margin-bottom: 0.7rem; }
        .back-link { display: inline-block; margin-bottom: 2rem; color: var(--primary-green, #689F38); text-decoration: underline dotted; font-size: 1.3rem; }
        .back-link:hover { color: var(--primary-yellow, #FFC107); }
        @media (max-width: 600px) { .blog-container { padding: 1.5rem 0.7rem; } .blog-title { font-size: 2rem; } }
    </style>
</head>
<body>
    <div class="blog-container">
        <a class="back-link" href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
        <?php if ($post): ?>
            <div class="blog-title"><?= htmlspecialchars($post['title']) ?></div>
            <div class="blog-meta">
                <i class="fas fa-calendar-alt"></i> <?= date('F j, Y', strtotime($post['published_at'])) ?>
            </div>
            <div class="blog-content">
                <?= $post['content'] ?>
            </div>
        <?php else: ?>
            <div class="blog-title">Blog Post Not Found</div>
            <div class="blog-content">Sorry, the requested blog post could not be found.</div>
        <?php endif; ?>
    </div>
    <?php include 'components/footer.php'; ?>
</body>
</html>
