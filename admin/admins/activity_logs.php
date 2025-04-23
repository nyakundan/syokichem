<?php
declare(strict_types=1);

$page_title = "Admin Activity Logs";
//require 'C:/xampp/htdocs/ecommerce website/admin/includes/auth.php';
//require 'C:/xampp/htdocs/ecommerce website/admin/components/connect.php';
// Start session and check admin login
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';


// Verify admin ID is provided
if (!isset($_GET['id'])) {
    header("Location: list.php?error=Admin ID not provided");
    exit;
}

$adminId = (int)$_GET['id'];

// Get admin basic info
try {
    $stmt = $conn->prepare("SELECT id, name, email FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        header("Location: list.php?error=Admin not found");
        exit;
    }
} catch (PDOException $e) {
    header("Location: list.php?error=Database error");
    exit;
}

// Pagination setup
$records_per_page = 15;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Get total logs count
try {
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM activity_logs WHERE admin_id = ?");
    $count_stmt->execute([$adminId]);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);
} catch (PDOException $e) {
    $error = "Failed to load logs count: " . $e->getMessage();
}

// Get logs data
try {
    $stmt = $conn->prepare("
        SELECT id, action, record_id, ip_address, created_at 
        FROM activity_logs 
        WHERE admin_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?, ?
    ");
    $stmt->bindValue(1, $adminId, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->bindValue(3, $records_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to load activity logs: " . $e->getMessage();
}

require_once 'C:/xampp/htdocs/ecommerce website/admin/includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Activity Logs for: <?= htmlspecialchars($admin['name']) ?> (<?= htmlspecialchars($admin['email']) ?>)
            </h6>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Admins List
            </a>
        </div>
        
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Action</th>
                            <th>Record ID</th>
                            <th>IP Address</th>
                            <th>Date/Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $index => $log): ?>
                                <tr>
                                    <td><?= $index + 1 + $offset ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            strpos($log['action'], 'delete') !== false ? 'danger' : 
                                            (strpos($log['action'], 'create') !== false ? 'success' : 'info')
                                        ?>">
                                            <?= htmlspecialchars(ucfirst($log['action'])) ?>
                                        </span>
                                    </td>
                                    <td><?= $log['record_id'] ? htmlspecialchars($log['record_id']) : 'N/A' ?></td>
                                    <td><?= $log['ip_address'] ? htmlspecialchars($log['ip_address']) : 'N/A' ?></td>
                                    <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($log['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No activity logs found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (isset($total_pages) && $total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $current_page == 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="activity_logs.php?id=<?= $adminId ?>&page=1">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item <?= $current_page == 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="activity_logs.php?id=<?= $adminId ?>&page=<?= $current_page - 1 ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="activity_logs.php?id=<?= $adminId ?>&page=<?= $i ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $current_page == $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="activity_logs.php?id=<?= $adminId ?>&page=<?= $current_page + 1 ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item <?= $current_page == $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="activity_logs.php?id=<?= $adminId ?>&page=<?= $total_pages ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
//require_once 'C:/xampp/htdocs/ecommerce website/admin/includes/admin_footer.php';
require_once __DIR__ . '/../includes/admin_footer.php';
?>