<?php
declare(strict_types=1);

// Start session and check authentication
session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get pagination parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get notifications for current user
$stmt = $conn->prepare("
    SELECT n.*, a.email as sender_name
    FROM notifications n
    LEFT JOIN admins a ON n.sender_id = a.id
    WHERE n.recipient_id = ?
    ORDER BY n.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$_SESSION['admin_id'], $perPage, $offset]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count
$totalStmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_id = ?");
$totalStmt->execute([$_SESSION['admin_id']]);
$total = $totalStmt->fetchColumn();

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="mb-4">Notifications</h1>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Your Notifications</span>
                    <button id="mark-all-read" class="btn btn-sm btn-success">Mark All as Read</button>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="alert alert-info">You have no notifications</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $notification): ?>
                                <a href="mark_read.php?id=<?= $notification['id'] ?>" 
                                   class="list-group-item list-group-item-action <?= $notification['is_read'] ? '' : 'list-group-item-primary' ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            <?= htmlspecialchars($notification['message']) ?>
                                        </h5>
                                        <small class="text-muted">
                                            <?= date('M d, Y H:i', strtotime($notification['created_at'])) ?>
                                        </small>
                                    </div>
                                    <small class="text-muted">
                                        From: <?= htmlspecialchars($notification['sender_name'] ?? 'System') ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= ceil($total / $perPage); $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < ceil($total / $perPage)): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mark all as read
document.getElementById('mark-all-read').addEventListener('click', function() {
    fetch('mark_read.php?all=1', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({csrf_token: '<?= generateCsrfToken() ?>'})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>