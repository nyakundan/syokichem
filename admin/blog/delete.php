<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

if (isset($_GET['id'])) {
    $postId = (int)$_GET['id'];
    
    try {
        $conn->beginTransaction();
        
        // Delete from junction table first
        $conn->prepare("DELETE FROM blog_post_categories WHERE post_id = ?")->execute([$postId]);
        
        // Then delete the post
        $conn->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$postId]);
        
        $conn->commit();
        
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'Blog post deleted successfully'
        ];
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Error deleting post: ' . $e->getMessage()
        ];
    }
}

header("Location: list.php");
exit();