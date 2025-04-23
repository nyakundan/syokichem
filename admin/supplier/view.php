<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

$supplierId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$supplierId]);
$supplier = $stmt->fetch();

if (!$supplier) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Supplier not found'
    ];
    header("Location: list.php");
    exit();
}

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="mb-4">Supplier Details</h1>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><?= htmlspecialchars($supplier['name']) ?></span>
                    <div>
                        <a href="edit.php?id=<?= $supplier['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="list.php" class="btn btn-secondary btn-sm">Back to List</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Contact Information</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Contact Person:</strong> 
                                    <?= htmlspecialchars($supplier['contact_person'] ?? 'N/A') ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Email:</strong> 
                                    <?= htmlspecialchars($supplier['email'] ?? 'N/A') ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Phone:</strong> 
                                    <?= htmlspecialchars($supplier['phone'] ?? 'N/A') ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Created At:</strong> 
                                    <?= date('M d, Y H:i', strtotime($supplier['created_at'])) ?>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Address</h5>
                            <div class="card">
                                <div class="card-body">
                                    <?= !empty($supplier['address']) ? nl2br(htmlspecialchars($supplier['address'])) : 'N/A' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>