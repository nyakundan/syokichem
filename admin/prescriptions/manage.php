<?php
declare(strict_types=1);

$page_title = "Manage Prescriptions";
//require 'C:/xampp/htdocs/syokichem/admin/includes/auth.php';
//require 'C:/xampp/htdocs/syokichem/admin/components/connect.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';


// Simple CSRF token generation and verification
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }
}

function csrfTokenInput() {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

// Handle status filter
$status_filter = $_GET['status'] ?? '';
$valid_statuses = ['', 'pending', 'approved', 'rejected'];
if (!in_array($status_filter, $valid_statuses, true)) {
    $status_filter = '';
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrfToken();
    
    $prescription_ids = $_POST['prescription_ids'] ?? [];
    $action = $_POST['action'];
    
    if (!empty($prescription_ids)) {
        $placeholders = implode(',', array_fill(0, count($prescription_ids), '?'));
        
        try {
            switch ($action) {
                case 'approve':
                    $stmt = $conn->prepare("UPDATE prescriptions SET status = 'approved' WHERE id IN ($placeholders)");
                    $stmt->execute($prescription_ids);
                    break;
                    
                case 'reject':
                    $stmt = $conn->prepare("UPDATE prescriptions SET status = 'rejected' WHERE id IN ($placeholders)");
                    $stmt->execute($prescription_ids);
                    break;
                    
                case 'delete':
                    $stmt = $conn->prepare("DELETE FROM prescriptions WHERE id IN ($placeholders)");
                    $stmt->execute($prescription_ids);
                    break;
            }
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Bulk action completed successfully!'
            ];
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

//include 'C:/xampp/htdocs/syokichem/admin/includes/admin_header.php';

include __DIR__ . '/../includes/admin_header.php';


?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Prescription Management</h6>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" id="status-filter" style="width: 150px;">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
                <a href="add.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> New Prescription
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= htmlspecialchars((string)$_SESSION['flash_message']['type']) ?>">
                    <?= htmlspecialchars((string)$_SESSION['flash_message']['message']) ?>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>
            
            <form method="post" id="bulk-action-form">
                <?= csrfTokenInput() ?>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="prescriptions-table" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th width="20">
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "
                                SELECT p.*, u.name as patient_name, u.email as patient_email
                                FROM prescriptions p
                                JOIN users u ON p.user_id = u.id
                                " . ($status_filter ? "WHERE p.status = :status" : "") . "
                                ORDER BY p.created_at DESC
                            ";
                            
                            $stmt = $conn->prepare($query);
                            if ($status_filter) {
                                $stmt->bindParam(':status', $status_filter);
                            }
                            $stmt->execute();
                            
                            while ($prescription = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="prescription_ids[]" value="<?= htmlspecialchars((string)$prescription['id']) ?>">
                                </td>
                                <td><?= htmlspecialchars((string)$prescription['id']) ?></td>
                                <td>
                                    <div><?= htmlspecialchars($prescription['patient_name'] ?? '') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($prescription['patient_email'] ?? '') ?></small>
                                </td>
                                <td><?= htmlspecialchars(date('M d, Y H:i', strtotime($prescription['created_at']))) ?></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        ($prescription['status'] ?? '') === 'approved' ? 'success' : 
                                        (($prescription['status'] ?? '') === 'rejected' ? 'danger' : 'warning') 
                                    ?>">
                                        <?= ucfirst(htmlspecialchars($prescription['status'] ?? '')) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="view.php?id=<?= htmlspecialchars((string)$prescription['id']) ?>" 
                                           class="btn btn-sm btn-info" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if (($prescription['status'] ?? '') === 'pending'): ?>
                                            <a href="update.php?id=<?= htmlspecialchars((string)$prescription['id']) ?>&status=approved" 
                                               class="btn btn-sm btn-success confirm-action" 
                                               data-confirm="Approve this prescription?"
                                               title="Approve">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="update.php?id=<?= htmlspecialchars((string)$prescription['id']) ?>&status=rejected" 
                                               class="btn btn-sm btn-danger confirm-action" 
                                               data-confirm="Reject this prescription?"
                                               title="Reject">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="edit.php?id=<?= htmlspecialchars((string)$prescription['id']) ?>" 
                                           class="btn btn-sm btn-warning"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?= htmlspecialchars((string)$prescription['id']) ?>" 
                                           class="btn btn-sm btn-danger confirm-action" 
                                           data-confirm="Delete this prescription permanently?"
                                           title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" name="action" style="width: 150px;">
                                <option value="">Bulk Actions</option>
                                <option value="approve">Approve Selected</option>
                                <option value="reject">Reject Selected</option>
                                <option value="delete">Delete Selected</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary" id="apply-bulk-action">
                                Apply
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
//require_once 'C:/xampp/htdocs/syokichem/admin/includes/admin_footer.php'; 

require_once __DIR__ . '/../includes/admin_footer.php';


?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#prescriptions-table').DataTable({
        dom: '<"top"f>rt<"bottom"lip><"clear">',
        responsive: true,
        columnDefs: [
            { orderable: false, targets: [0, 5] },
            { searchable: false, targets: [0, 4, 5] }
        ]
    });
    
    // Status filter change
    $('#status-filter').change(function() {
        window.location.href = '<?= $_SERVER['PHP_SELF'] ?>?status=' + $(this).val();
    });
    
    // Select all checkbox
    $('#select-all').click(function() {
        $('input[name="prescription_ids[]"]').prop('checked', this.checked);
    });
    
    // Confirm actions
    $('.confirm-action').click(function(e) {
        if (!confirm($(this).data('confirm'))) {
            e.preventDefault();
        }
    });
    
    // Bulk action form submission
    $('#bulk-action-form').submit(function(e) {
        const action = $('select[name="action"]').val();
        const checked = $('input[name="prescription_ids[]"]:checked').length > 0;
        
        if (!action) {
            alert('Please select a bulk action');
            e.preventDefault();
            return false;
        }
        
        if (!checked) {
            alert('Please select at least one prescription');
            e.preventDefault();
            return false;
        }
        
        if (action === 'delete' && !confirm('Are you sure you want to delete the selected prescriptions?')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>