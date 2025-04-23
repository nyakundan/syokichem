<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total count
$totalPosts = $conn->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
$totalPages = ceil($totalPosts / $perPage);

// Get posts with author and category info
$stmt = $conn->prepare("
    SELECT p.*, a.name as author_name, c.name as category_name
    FROM blog_posts p
    LEFT JOIN admins a ON p.author_id = a.id
    LEFT JOIN blog_categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="mb-4">Blog Posts</h1>
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_message']['type'] ?? 'info') ?>">
                    <?= htmlspecialchars($_SESSION['flash_message']['message'] ?? '') ?>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>All Posts</span>
                    <a href="add.php" class="btn btn-primary btn-sm">Add New Post</a>
                </div>
                <div class="card-body">
                    <?php if (empty($posts)): ?>
                        <div class="alert alert-info">No blog posts found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($post['title'] ?? 'Untitled Post') ?></td>
                                        <td><?= htmlspecialchars($post['author_name'] ?? 'Unknown Author') ?></td>
                                        <td><?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                ($post['status'] ?? 'draft') === 'published' ? 'success' : 
                                                (($post['status'] ?? 'draft') === 'draft' ? 'warning' : 'secondary') 
                                            ?>">
                                                <?= ucfirst($post['status'] ?? 'draft') ?>
                                            </span>
                                        </td>
                                        <td><?= !empty($post['created_at']) ? date('M d, Y', strtotime($post['created_at'])) : 'Unknown date' ?></td>
                                        <td>
                                            <?php if (isset($post['id'])): ?>
                                                <a href="edit.php?id=<?= (int)$post['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                                <a href="delete.php?id=<?= (int)$post['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this post?')">Delete</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav>
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a></li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">Next</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>