<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total count
$totalSuppliers = $conn->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
$totalPages = ceil($totalSuppliers / $perPage);

// Get suppliers
$stmt = $conn->prepare("SELECT * FROM suppliers ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$suppliers = $stmt->fetchAll();

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="mb-4">Suppliers</h1>
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_message']['type'] ?? 'info') ?>">
                    <?= htmlspecialchars($_SESSION['flash_message']['message'] ?? '') ?>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>All Suppliers</span>
                    <a href="add.php" class="btn btn-primary btn-sm">Add New Supplier</a>
                </div>
                <div class="card-body">
                    <?php if (empty($suppliers)): ?>
                        <div class="alert alert-info">No suppliers found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Contact Person</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($suppliers as $supplier): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($supplier['name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($supplier['contact_person'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($supplier['email'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($supplier['phone'] ?? 'N/A') ?></td>
                                        <td><?= date('M d, Y', strtotime($supplier['created_at'])) ?></td>
                                        <td>
                                            <a href="view.php?id=<?= (int)$supplier['id'] ?>" class="btn btn-sm btn-info">View</a>
                                            <a href="edit.php?id=<?= (int)$supplier['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <a href="delete.php?id=<?= (int)$supplier['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this supplier?')">Delete</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
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