<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Get post ID
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch post data
$stmt = $conn->prepare("
    SELECT p.*, 
    GROUP_CONCAT(pc.category_id) as category_ids
    FROM blog_posts p
    LEFT JOIN blog_post_categories pc ON p.id = pc.post_id
    WHERE p.id = ?
    GROUP BY p.id
");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: list.php");
    exit();
}

// Convert category_ids to array
$post['categories'] = $post['category_ids'] ? explode(',', $post['category_ids']) : [];

// Get all categories
$categories = $conn->query("SELECT id, name FROM blog_categories ORDER BY name")->fetchAll();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $selectedCategories = $_POST['categories'] ?? [];
    $errors = [];

    // Validate input
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($content)) {
        $errors[] = "Content is required";
    }

    // Handle image upload
    $featured_image = $post['featured_image']; // Keep existing image by default
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Image must be jpg, jpeg, png, or webp';
        } elseif ($file['size'] > 2097152) {
            $errors[] = 'Image size must be less than 2MB';
        } else {
            // Delete old image if it exists
            if (!empty($post['featured_image'])) {
                $old_image = __DIR__ . '/../../images/' . $post['featured_image'];
                if (file_exists($old_image)) {
                    unlink($old_image);
                }
            }

            // Upload new image
            $new_image = uniqid() . '.' . $ext;
            $upload_path = __DIR__ . '/../../images/' . $new_image;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $featured_image = $new_image;
            } else {
                $errors[] = 'Failed to upload image';
            }
        }
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Update post
            $update_post = $conn->prepare("
                UPDATE blog_posts 
                SET title = ?, content = ?, status = ?, featured_image = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $update_post->execute([$title, $content, $status, $featured_image, $postId]);

            // Delete old categories
            $delete_categories = $conn->prepare("DELETE FROM blog_post_categories WHERE post_id = ?");
            $delete_categories->execute([$postId]);

            // Insert new categories
            if (!empty($selectedCategories)) {
                $insert_categories = $conn->prepare("
                    INSERT INTO blog_post_categories (post_id, category_id) VALUES (?, ?)
                ");
                foreach ($selectedCategories as $categoryId) {
                    $insert_categories->execute([$postId, $categoryId]);
                }
            }

            $conn->commit();
            $message[] = 'Post updated successfully!';
            
            // Refresh post data
            $stmt->execute([$postId]);
            $post = $stmt->fetch();
            $post['categories'] = $post['category_ids'] ? explode(',', $post['category_ids']) : [];
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = 'Failed to update post: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/admin_header.php';
?>

<section class="post-editor">
    <h1>Edit Blog Post</h1>

    <?php if (!empty($errors)): ?>
        <div class="message error">
            <?php foreach($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="message success">
            <?php foreach($message as $msg): ?>
                <p><?= htmlspecialchars($msg) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($post['title']) ?>" required>
        </div>

        <div class="form-group">
            <label for="content">Content:</label>
            <textarea id="content" name="content" rows="10" required><?= htmlspecialchars($post['content']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="image">Featured Image:</label>
            <?php if (!empty($post['featured_image'])): ?>
                <div class="current-image">
                    <img src="../../images/<?= htmlspecialchars($post['featured_image']) ?>" alt="Current post image" style="max-width: 200px;">
                    <p>Current image: <?= htmlspecialchars($post['featured_image']) ?></p>
                </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
            <p class="help-text">Leave empty to keep current image. Max size: 2MB. Allowed: JPG, JPEG, PNG, WEBP</p>
        </div>

        <div class="form-group">
            <label for="categories">Categories:</label>
            <div class="checkbox-group">
                <?php foreach ($categories as $category): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="categories[]" value="<?= $category['id'] ?>"
                            <?= in_array($category['id'], $post['categories']) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="status">Status:</label>
            <select id="status" name="status" required>
                <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Post</button>
            <a href="list.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</section>

<style>
.post-editor {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.post-editor h1 {
    margin-bottom: 2rem;
    color: var(--dark-green);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.form-group input[type="text"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.5rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: normal;
}

.help-text {
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #666;
}

.current-image {
    margin: 1rem 0;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary-green);
    color: white;
}

.btn-primary:hover {
    background: var(--dark-green);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.message {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
}

.message.error {
    background: #fee;
    color: #c00;
    border: 1px solid #fcc;
}

.message.success {
    background: #efe;
    color: #0c0;
    border: 1px solid #cfc;
}
</style>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>