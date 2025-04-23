<?php
declare(strict_types=1);

//require 'C:/xampp/htdocs/ecommerce website/admin/includes/auth.php';
//require 'C:/xampp/htdocs/ecommerce website/admin/components/connect.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';


// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search and filter
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

// Build query
$query = "SELECT * FROM coupons WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND code LIKE ?";
    $params[] = "%$search%";
}

if ($status !== 'all') {
    $query .= " AND is_active = ?";
    $params[] = $status === 'active' ? 1 : 0;
}

// Get total count for pagination
$countStmt = $conn->prepare(str_replace('*', 'COUNT(*)', $query));
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// Add sorting and pagination
$query .= " ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";

// Get coupons
$stmt = $conn->prepare($query);
$stmt->execute($params);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

//include 'C:/xampp/htdocs/ecommerce website/admin/includes/admin_header.php';

include __DIR__ . '/../includes/admin_header.php';

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="mb-4">Coupons</h1>
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_message']['type']) ?>">
                    <?= htmlspecialchars($_SESSION['flash_message']['message']) ?>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>All Coupons</span>
                    
                    <a href="/ecommerce website/admin/coupons/add.php" class="btn btn-success btn-sm">Add New</a>
                </div>
                <div class="card-body">
                    <form method="get" class="mb-4">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <input type="text" class="form-control" name="search" placeholder="Search by code..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <select class="form-select" name="status">
                                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="coupons/list.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Discount</th>
                                    <th>Min. Order</th>
                                    <th>Valid From</th>
                                    <th>Valid To</th>
                                    <th>Usage</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($coupons)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No coupons found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($coupons as $coupon): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($coupon['code']) ?></strong></td>
                                            <td>
                                                <?= $coupon['discount_type'] === 'percentage' 
                                                    ? $coupon['discount_value'] . '%' 
                                                    : '$' . number_format((float)$coupon['discount_value'], 2) ?>
                                                <?php if ($coupon['discount_type'] === 'percentage' && $coupon['max_discount_amount']): ?>
                                                    <br><small>Max: $<?= number_format((float)$coupon['max_discount_amount'], 2) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= $coupon['min_order_amount'] > 0 
                                                    ? '$' . number_format((float)$coupon['min_order_amount'], 2) 
                                                    : 'None' ?>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($coupon['start_date'])) ?></td>
                                            <td><?= date('M d, Y', strtotime($coupon['expiry_date'])) ?></td>
                                            <td>
                                                <?php if ($coupon['usage_limit']): ?>
                                                    <?= $coupon['times_used'] ?? 0 ?> / <?= $coupon['usage_limit'] ?>
                                                <?php else: ?>
                                                    Unlimited
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $coupon['is_active'] ? 'success' : 'secondary' ?>">
                                                    <?= $coupon['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                                <?php if (strtotime($coupon['expiry_date']) < time()): ?>
                                                    <span class="badge bg-danger">Expired</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="coupons/edit.php?id=<?= $coupon['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                                    <form method="post" action="coupons/delete.php" onsubmit="return confirm('Are you sure you want to delete this coupon?');">
                                                        <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total > $perPage): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= ceil($total / $perPage); $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < ceil($total / $perPage)): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
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

<?php //include 'C:/xampp/htdocs/ecommerce website/admin/includes/admin_footer.php';
include __DIR__ . '/../includes/admin_footer.php';

 ?>