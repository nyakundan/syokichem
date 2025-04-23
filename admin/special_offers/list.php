<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Get messages with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count
$total_count = $conn->query("SELECT COUNT(*) FROM special_offers WHERE is_active = 1")->fetchColumn();
$total_pages = ceil($total_count / $limit);

// Get special offers
$select_offers = $conn->prepare("
    SELECT so.*, p.name as product_name 
    FROM special_offers so
    JOIN products p ON so.product_id = p.id
    WHERE so.is_active = 1
    ORDER BY so.created_at DESC 
    LIMIT ? OFFSET ?
");
$select_offers->bindValue(1, $limit, PDO::PARAM_INT);
$select_offers->bindValue(2, $offset, PDO::PARAM_INT);
$select_offers->execute();
$offers = $select_offers->fetchAll(PDO::FETCH_ASSOC);

// Handle status update and delete
if (isset($_POST['offer_id'])) {
    $offer_id = (int)$_POST['offer_id'];
    
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'activate' || $action === 'deactivate') {
            $is_active = $action === 'activate' ? 1 : 0;
            $update = $conn->prepare("UPDATE special_offers SET is_active = ? WHERE id = ?");
            $update->execute([$is_active, $offer_id]);
        } elseif ($action === 'delete') {
            $delete = $conn->prepare("DELETE FROM special_offers WHERE id = ?");
            $delete->execute([$offer_id]);
        }
    }
    
    header("Location: list.php");
    exit();
}

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Special Offers</h4>
                    <a href="select_product.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New Offer
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($offers)): ?>
                        <div class="alert alert-info">No special offers found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Original Price</th>
                                        <th>Discounted Price</th>
                                        <th>Discount (%)</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($offers as $offer): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($offer['product_name']) ?></td>
                                            <td>KES <?= number_format((float)$offer['old_price'], 2) ?></td>
                                            <td>KES <?= number_format((float)$offer['new_price'], 2) ?></td>
                                            <td>
                                                <?= number_format((float)(($offer['old_price'] - $offer['new_price']) / $offer['old_price']) * 100, 2) ?>%
                                            </td>
                                            <td><?= date('Y-m-d H:i', strtotime($offer['start_date'])) ?></td>
                                            <td><?= date('Y-m-d H:i', strtotime($offer['end_date'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $offer['is_active'] ? 'success' : 'danger' ?>">
                                                    <?= $offer['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="edit.php?id=<?= $offer['id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="offer_id" value="<?= $offer['id'] ?>">
                                                        <?php if ($offer['is_active']): ?>
                                                            <button type="submit" name="action" value="deactivate" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to deactivate this offer?')">
                                                                <i class="fas fa-pause"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="submit" name="action" value="activate" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to activate this offer?')">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this offer?')">
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
                            <nav aria-label="Offers navigation">
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

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
