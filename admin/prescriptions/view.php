<?php
declare(strict_types=1);

$page_title = "View Prescription";
//require 'C:/xampp/htdocs/syokichem/admin/includes/auth.php';
//require 'C:/xampp/htdocs/syokichem/admin/components/connect.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';


if (!isset($_GET['id']) ){
    header("Location: list.php?error=Prescription ID not provided");
    exit;
}

$prescription_id = (int)$_GET['id'];

try {
    $stmt = $conn->prepare("
        SELECT p.*, u.name as patient_name, u.email as patient_email, u.phone as patient_phone, p.prescription_file as prescription_file
        FROM prescriptions p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$prescription_id]);
    $prescription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$prescription) {
        header("Location: list.php?
        error=Prescription not found");
        exit;
    }
} catch (PDOException $e) {
    header("Location: list.php?error=Database error");
    exit;
}

//include 'C:/xampp/htdocs/syokichem/admin/includes/admin_header.php';

include __DIR__ . '/../includes/admin_header.php';

?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Prescription #<?= htmlspecialchars((string)$prescription['id']) ?>
            </h6>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
        
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Patient Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Name</th>
                                    <td><?= htmlspecialchars($prescription['patient_name'] ?? $prescription['recipient_name'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= htmlspecialchars($prescription['patient_email'] ?? $prescription['recipient_email'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td><?= htmlspecialchars($prescription['patient_phone'] ?? $prescription['recipient_phone'] ?? 'N/A') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Prescription Details</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $prescription['status'] === 'approved' ? 'success' : 
                                            ($prescription['status'] === 'rejected' ? 'danger' : 'warning') 
                                        ?>">
                                            <?= ucfirst(htmlspecialchars($prescription['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td><?= htmlspecialchars(date('M d, Y H:i', strtotime($prescription['created_at']))) ?></td>
                                </tr>
                                <tr>
                                    <th>Updated At</th>
                                    <td>
                                        <?= $prescription['updated_at'] ? 
                                            htmlspecialchars(date('M d, Y H:i', strtotime($prescription['updated_at']))) : 
                                            'N/A' ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Prescription Items</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($prescription['prescription_items'])): ?>
                        <pre><?= htmlspecialchars($prescription['prescription_items'] ?? 'No items provided') ?></pre>
                    <?php else: ?>
                        <p>No prescription items found</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Prescription File</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($prescription['prescription_file'])): ?>
                        <a href="download.php?file=<?= urlencode($prescription['prescription_file']) ?>" target="_blank">Download Prescription File</a>
                    <?php else: ?>
                        <p>No file uploaded.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
//require_once 'C:/xampp/htdocs/syokichem/admin/includes/admin_footer.php'; 

require_once __DIR__ . '/../includes/admin_footer.php';


?>