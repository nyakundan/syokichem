<?php
// Start session and check admin login
//require 'C:/xampp/htdocs/ecommerce website/admin/components/connect.php';
//require 'C:/xampp/htdocs/ecommerce website/admin/includes/auth.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';


// Set page title
$page_title = "Manage Orders";

// Initialize variables with default values
$orders = [];
$total_records = 0;
$total_pages = 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $records_per_page;

try {
    // Base query matching your database structure
    $query = "SELECT 
                o.id, 
                o.user_id,
                o.name as customer_name,
                o.email,
                o.phone,
                o.address,
                o.payment_method,
                o.total_products,
                o.total_price as total_amount,
                o.order_status as status,
                o.placed_on as order_date
              FROM orders o 
              WHERE 1=1";
    
    // Add search condition
    if (!empty($search)) {
        $query .= " AND (o.id = :search_id OR o.name LIKE :search OR o.email LIKE :search_email)";
    }

    // Add status filter
    if (!empty($status_filter) && $status_filter != 'all') {
        $query .= " AND o.order_status = :status";
    }

    // Count total records
    $count_query = "SELECT COUNT(*) as total FROM ($query) as counted";
    $count_stmt = $conn->prepare($count_query);
    
    if (!empty($search)) {
        // Check if search is numeric (for ID search)
        if (is_numeric($search)) {
            $count_stmt->bindValue(':search_id', (int)$search, PDO::PARAM_INT);
        }
        $count_stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $count_stmt->bindValue(':search_email', "%$search%", PDO::PARAM_STR);
    }
    if (!empty($status_filter) && $status_filter != 'all') {
        $count_stmt->bindValue(':status', $status_filter, PDO::PARAM_STR);
    }
    
    $count_stmt->execute();
    $total_records = (int)$count_stmt->fetchColumn();
    $total_pages = max(1, ceil($total_records / $records_per_page));

    // Add sorting and pagination
    $query .= " ORDER BY o.placed_on DESC LIMIT :offset, :records_per_page";

    // Execute main query
    $stmt = $conn->prepare($query);
    
    if (!empty($search)) {
        if (is_numeric($search)) {
            $stmt->bindValue(':search_id', (int)$search, PDO::PARAM_INT);
        }
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':search_email', "%$search%", PDO::PARAM_STR);
    }
    if (!empty($status_filter) && $status_filter != 'all') {
        $stmt->bindValue(':status', $status_filter, PDO::PARAM_STR);
    }
    
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Unable to load orders. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge.bg-warning { background-color: #ffc107; color: #212529; }
        .badge.bg-info { background-color: #0dcaf0; color: #fff; }
        .badge.bg-primary { background-color: #0d6efd; color: #fff; }
        .badge.bg-success { background-color: #198754; color: #fff; }
        .badge.bg-danger { background-color: #dc3545; color: #fff; }
        .table-responsive { overflow-x: auto; }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,0.03); }
        .status-badge { min-width: 80px; display: inline-block; text-align: center; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-shopping-cart"></i> Manage Orders</h2>
            </div>
            <div class="col-md-6 text-end">
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <a href="list.php" class="float-end">Try Again</a>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Search and Filter Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" action="">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search by ID, name or email" 
                                       value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="status">
                                <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Statuses</option>
                                <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $status_filter == 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="shipped" <?= $status_filter == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $status_filter == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-info w-100">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['id']) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($order['customer_name']) ?></strong><br>
                                            <small><?= htmlspecialchars($order['email']) ?></small>
                                        </td>
                                        <td><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></td>
                                        <td>KSh <?= number_format((float)$order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge status-badge 
                                                <?= $order['status'] == 'pending' ? 'bg-warning' : '' ?>
                                                <?= $order['status'] == 'processing' ? 'bg-info' : '' ?>
                                                <?= $order['status'] == 'shipped' ? 'bg-primary' : '' ?>
                                                <?= $order['status'] == 'delivered' ? 'bg-success' : '' ?>
                                                <?= $order['status'] == 'cancelled' ? 'bg-danger' : '' ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view.php?id=<?= $order['id'] ?>" class="btn btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?= $order['id'] ?>" class="btn btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="print.php?id=<?= $order['id'] ?>" class="btn btn-secondary" title="Print" target="_blank">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-box-open fa-2x mb-2 text-muted"></i><br>
                                        No orders found matching your criteria
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $current_page == 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item <?= $current_page == 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= max(1, $current_page - 1) ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                            
                            <?php 
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $current_page == $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= min($total_pages, $current_page + 1) ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item <?= $current_page == $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/admin_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Confirm before deleting
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this order?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>