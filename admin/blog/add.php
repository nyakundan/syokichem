<?php
declare(strict_types=1);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and check auth
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Initialize variables
$errors = [];
$post = [
    'title' => '',
    'slug' => '',
    'content' => '',
    'excerpt' => '',
    'featured_image' => '',
    'status' => 'draft',
    'meta_title' => '',
    'meta_description' => '',
    'categories' => [],
    'author_id' => $_SESSION['admin_id'] ?? null // Safe access with null coalescing
];

// Verify author_id is set
//if (empty($post['author_id'])) {
   // $_SESSION['flash_message'] = [
     //   'type' => 'error',
      //  'message' => 'You must be logged in to create a post'
    //];
    //header("Location: ../login.php");
    //exit();
//}

// Check if categories exist
$categoryCount = $conn->query("SELECT COUNT(*) FROM blog_categories")->fetchColumn();
if ($categoryCount === 0) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'You must create at least one category before adding blog posts'
    ];
    header("Location: categories.php");
    exit();
}

// Get all categories
$categories = $conn->query("SELECT id, name FROM blog_categories ORDER BY name")->fetchAll();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form data
    $post['title'] = sanitize($_POST['title'] ?? '');
    $post['slug'] = getUniqueSlug($conn, createSlug($post['title']));
    $post['content'] = $_POST['content'] ?? '';
    $post['excerpt'] = sanitize($_POST['excerpt'] ?? '');
    $post['status'] = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft';
    $post['meta_title'] = sanitize($_POST['meta_title'] ?? '');
    $post['meta_description'] = sanitize($_POST['meta_description'] ?? '');
    $post['categories'] = array_map('intval', $_POST['categories'] ?? []);

    // Validation
    if (empty($post['title'])) $errors['title'] = 'Title is required';
    if (empty($post['content'])) $errors['content'] = 'Content is required';
    if (empty($post['categories'])) $errors['categories'] = 'At least one category is required';

    if (empty($errors)) {
        $transactionStarted = false;
        try {
            // Start transaction
            if ($conn->beginTransaction()) {
                $transactionStarted = true;
            }

            // Handle file upload
            $featuredImage = handleImageUpload('featured_image', $errors);

            if (isset($errors['featured_image'])) {
                throw new Exception($errors['featured_image']);
            }

            // Insert post
            $stmt = $conn->prepare("
                INSERT INTO blog_posts 
                (title, slug, content, excerpt, featured_image, status, 
                 meta_title, meta_description, author_id, category_id, published_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $publishedAt = $post['status'] === 'published' ? date('Y-m-d H:i:s') : null;
            $categoryId = !empty($post['categories']) ? $post['categories'][0] : null;
            
            $stmt->execute([
                $post['title'],
                $post['slug'],
                $post['content'],
                $post['excerpt'],
                $featuredImage,
                $post['status'],
                $post['meta_title'],
                $post['meta_description'],
                $post['author_id'],
                $categoryId,
                $publishedAt
            ]);

            $postId = $conn->lastInsertId();

            // Insert additional categories
            if (count($post['categories']) > 1) {
                $stmt = $conn->prepare("
                    INSERT INTO blog_post_categories (post_id, category_id) VALUES (?, ?)
                ");
                foreach (array_slice($post['categories'], 1) as $categoryId) {
                    $stmt->execute([$postId, $categoryId]);
                }
            }

            // Commit transaction if it was started
            if ($transactionStarted) {
                $conn->commit();
            }
            

             // Log admin action - matches your table columns
    $logStmt = $conn->prepare("
    INSERT INTO admin_logs 
    (admin_id, action, action_table, created_at)
    VALUES (?, ?, ?, NOW())
");
$logStmt->execute([
    $_SESSION['admin_id'],
    'Created blog post: ' . substr($post['title'], 0, 255), // action (varchar(255))
    'blog_posts' // action_table (varchar(100))
]);

$_SESSION['flash_message'] = [
    'type' => 'success',
    'message' => 'Blog post created successfully'
];
header("Location: list.php");
exit();

} catch (Exception $e) {
if ($transactionStarted) {
    try {
        $conn->rollBack();
    } catch (PDOException $rollbackException) {
        error_log("Rollback failed: " . $rollbackException->getMessage());
    }
}
$errors[] = 'Error: ' . $e->getMessage();
error_log("Blog post creation failed: " . $e->getMessage());
}
            
        
    }
}

include __DIR__ . '/../includes/admin_header.php';
?>

<!-- Your HTML form here -->

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="mb-4">Add New Blog Post</h1>
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_message']['type']) ?>">
                    <?= htmlspecialchars($_SESSION['flash_message']['message']) ?>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="card mb-4">
                    <div class="card-header">Post Details</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" 
                                   id="title" name="title" 
                                   value="<?= htmlspecialchars($post['title']) ?>" required>
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['title']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Content *</label>
                            <textarea class="form-control <?= isset($errors['content']) ? 'is-invalid' : '' ?>" 
                                      id="content" name="content" rows="10" required><?= 
                                htmlspecialchars($post['content']) 
                            ?></textarea>
                            <?php if (isset($errors['content'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['content']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Excerpt</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?= 
                                htmlspecialchars($post['excerpt']) 
                            ?></textarea>
                            <small class="text-muted">A short summary of your post (optional)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="featured_image" class="form-label">Featured Image</label>
                            <input type="file" class="form-control <?= isset($errors['featured_image']) ? 'is-invalid' : '' ?>" 
                                   id="featured_image" name="featured_image" accept="image/*">
                            <?php if (isset($errors['featured_image'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['featured_image']) ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Max size: 5MB (JPEG, PNG, GIF)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Categories *</label>
                            <?php if (empty($categories)): ?>
                                <div class="alert alert-warning">No categories found. Please create categories first.</div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($categories as $category): ?>
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="category_<?= $category['id'] ?>" 
                                                       name="categories[]" 
                                                       value="<?= $category['id'] ?>"
                                                       <?= in_array($category['id'], $post['categories']) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="category_<?= $category['id'] ?>">
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($errors['categories'])): ?>
                                <div class="text-danger"><?= htmlspecialchars($errors['categories']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">SEO Settings</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="meta_title" class="form-label">Meta Title</label>
                            <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                   value="<?= htmlspecialchars($post['meta_title']) ?>"
                                   maxlength="255">
                            <small class="text-muted">Recommended: 50-60 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <textarea class="form-control" id="meta_description" name="meta_description" 
                                      rows="3" maxlength="255"><?= 
                                htmlspecialchars($post['meta_description']) 
                            ?></textarea>
                            <small class="text-muted">Recommended: 150-160 characters</small>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Slug generation from title
document.getElementById('title').addEventListener('input', function() {
    const title = this.value.trim();
    const slug = title.toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
    // You might want to display this somewhere or store it in a hidden field
});

// Basic form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const title = document.getElementById('title');
    const content = document.getElementById('content');
    const categories = document.querySelectorAll('input[name="categories[]"]:checked');
    
    let isValid = true;
    
    if (!title.value.trim()) {
        title.classList.add('is-invalid');
        isValid = false;
    } else {
        title.classList.remove('is-invalid');
    }
    
    if (!content.value.trim()) {
        content.classList.add('is-invalid');
        isValid = false;
    } else {
        content.classList.remove('is-invalid');
    }
    
    if (categories.length === 0) {
        alert('Please select at least one category');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
    }
});
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>