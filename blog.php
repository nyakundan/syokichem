<?php
// blog.php
include 'components/connect.php';
include 'components/user_header.php';

// Fetch all published blog posts (assume table: blog_posts)
$stmt = $conn->prepare("SELECT * FROM blog_posts ORDER BY published_at DESC");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog | Syokichem</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-size: 1.6rem; line-height: 1.7; font-family: 'Rubik', sans-serif; background: #f8f9fa; color: #222; }
        .blog-list-container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 2.5rem 2rem; }
        .blog-list-title { font-size: 2.6rem; color: var(--primary-green, #689F38); font-weight: 700; margin-bottom: 2.2rem; text-align: center; }
        .blog-list-grid { display: grid; grid-template-columns: 1fr; gap: 2.5rem; }
        .blog-list-item { border-bottom: 1px solid #e0e0e0; padding-bottom: 2rem; }
        .blog-list-item:last-child { border-bottom: none; }
        .blog-post-title { font-size: 2rem; color: var(--primary-green, #689F38); font-weight: 600; margin-bottom: 0.7rem; text-decoration: none; display: inline-block; }
        .blog-post-title:hover { color: var(--primary-yellow, #FFC107); }
        .blog-post-meta { color: #757575; font-size: 1.2rem; margin-bottom: 0.6rem; }
        .blog-post-excerpt { color: #222; font-size: 1.4rem; margin-bottom: 0.7rem; }
        .read-more-link { color: var(--primary-green, #689F38); text-decoration: underline dotted; font-size: 1.3rem; }
        .read-more-link:hover { color: var(--primary-yellow, #FFC107); }
        @media (max-width: 600px) { .blog-list-container { padding: 1.5rem 0.7rem; } .blog-list-title { font-size: 2rem; } }
    </style>
</head>
<body>
    <div class="blog-list-container">
        <div class="blog-list-title">Latest Blog Posts</div>
        <div class="blog-list-grid">
            <?php if ($posts && count($posts) > 0): ?>
                <?php foreach ($posts as $post): ?>
                    <div class="blog-list-item">
                        <a class="blog-post-title" href="blog-post.php?slug=<?= urlencode($post['slug']) ?>">
                            <?= htmlspecialchars($post['title']) ?>
                        </a>
                        <div class="blog-post-meta">
                            <i class="fas fa-calendar-alt"></i> <?= date('F j, Y', strtotime($post['published_at'])) ?>
                        </div>
                        <div class="blog-post-excerpt">
                            <?= htmlspecialchars(mb_strimwidth(strip_tags($post['content']), 0, 180, '...')) ?>
                        </div>
                        <a class="read-more-link" href="blog-post.php?slug=<?= urlencode($post['slug']) ?>">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div>No blog posts found.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
</body>
</html>
