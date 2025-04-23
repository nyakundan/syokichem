<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Get messages with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count
$total_count = $conn->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$total_pages = ceil($total_count / $limit);

// Get messages
$select_messages = $conn->prepare("
    SELECT * FROM messages 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$select_messages->bindValue(1, $limit, PDO::PARAM_INT);
$select_messages->bindValue(2, $offset, PDO::PARAM_INT);
$select_messages->execute();
$messages = $select_messages->fetchAll(PDO::FETCH_ASSOC);

// Handle message status update
if (isset($_POST['message_id']) && isset($_POST['action'])) {
    $message_id = (int)$_POST['message_id'];
    $action = $_POST['action'];
    
    if ($action === 'mark_read') {
        $update = $conn->prepare("UPDATE messages SET status = 'read' WHERE id = ?");
        $update->execute([$message_id]);
    } elseif ($action === 'mark_unread') {
        $update = $conn->prepare("UPDATE messages SET status = 'unread' WHERE id = ?");
        $update->execute([$message_id]);
    } elseif ($action === 'delete') {
        $delete = $conn->prepare("DELETE FROM messages WHERE id = ?");
        $delete->execute([$message_id]);
    }
    
    header("Location: list.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include __DIR__ . '/../includes/admin_header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Messages</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($messages)): ?>
                        <div class="alert alert-info">No messages found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $message): ?>
                                        <tr class="<?= $message['status'] === 'unread' ? 'table-active' : '' ?>">
                                            <td>
                                                <span class="badge bg-<?= $message['status'] === 'unread' ? 'primary' : 'secondary' ?>">
                                                    <?= ucfirst($message['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('Y-m-d H:i', strtotime($message['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($message['name']) ?></td>
                                            <td>
                                                <a href="mailto:<?= htmlspecialchars($message['email']) ?>">
                                                    <?= htmlspecialchars($message['email']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($message['subject']) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-link" data-bs-toggle="modal" data-bs-target="#messageModal<?= $message['id'] ?>">
                                                    View Message
                                                </button>
                                                
                                                <!-- Message Modal -->
                                                <div class="modal fade" id="messageModal<?= $message['id'] ?>" tabindex="-1" aria-labelledby="messageModalLabel<?= $message['id'] ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="messageModalLabel<?= $message['id'] ?>">Message from <?= htmlspecialchars($message['name']) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="mb-2"><strong>Subject:</strong> <?= htmlspecialchars($message['subject']) ?></p>
                                                                <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($message['email']) ?></p>
                                                                <p class="mb-2"><strong>Date:</strong> <?= date('Y-m-d H:i', strtotime($message['created_at'])) ?></p>
                                                                <hr>
                                                                <p class="message-content"><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <a href="mailto:<?= htmlspecialchars($message['email']) ?>" class="btn btn-primary">
                                                                    <i class="fas fa-reply"></i> Reply
                                                                </a>
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                                        <?php if ($message['status'] === 'unread'): ?>
                                                            <button type="submit" name="action" value="mark_read" class="btn btn-sm btn-success">
                                                                <i class="fas fa-check"></i> Mark Read
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="submit" name="action" value="mark_unread" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-undo"></i> Mark Unread
                                                            </button>
                                                        <?php endif; ?>
                                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this message?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Message navigation">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                                        </li>
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

<style>
.message-content {
    white-space: pre-wrap;
    word-break: break-word;
}
</style>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
