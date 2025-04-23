<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../components/connect.php';

// Check if user is authorized
//if (!isset($_SESSION['admin_id'])) {
    //$_SESSION['error'] = 'Unauthorized access';
    //header('Location: ../login.php');
    ////exit();
//}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $user_ids = $_POST['user_ids'] ?? [];
    $action = $_POST['bulk_action'];
    
    if (!empty($user_ids)) {
        $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
        
        try {
            switch ($action) {
                case 'activate':
                    $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id IN ($placeholders)");
                    $stmt->execute($user_ids);
                    $_SESSION['success'] = 'Selected users activated successfully';
                    break;
                    
                case 'deactivate':
                    $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id IN ($placeholders)");
                    $stmt->execute($user_ids);
                    $_SESSION['success'] = 'Selected users deactivated successfully';
                    break;
                    
                case 'delete':
                    // Prevent self-deletion
                    if (in_array($_SESSION['admin_id'], $user_ids)) {
                        $_SESSION['error'] = 'You cannot delete your own account';
                    } else {
                        $stmt = $conn->prepare("DELETE FROM users WHERE id IN ($placeholders)");
                        $stmt->execute($user_ids);
                        $_SESSION['success'] = 'Selected users deleted successfully';
                    }
                    break;
            }
        } catch (PDOException $e) {
            error_log("Error in bulk action: " . $e->getMessage());
            $_SESSION['error'] = 'Database error occurred';
        }
    }
    
    header("Location: list.php");
    exit();
}

try {
    // Fetch all users with their order counts
    $stmt = $conn->query("
        SELECT u.id, u.name, u.email, u.is_admin, u.status, u.created_at,
               COUNT(o.id) as order_count
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $_SESSION['error'] = 'Error fetching users';
    $users = [];
}

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Users Management</h1>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New User
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo htmlspecialchars($_SESSION['success']);
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo htmlspecialchars($_SESSION['error']);
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" id="bulk-action-form">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="users-table">
                        <thead>
                            <tr>
                                <th width="20">
                                    <input type="checkbox" class="form-check-input" id="select-all">
                                </th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Orders</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">No users found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input" name="user_ids[]" 
                                                   value="<?php echo htmlspecialchars((string)$user['id']); ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars((string)$user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?= $user['is_admin'] ? 'Admin' : 'User' ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars((string)$user['order_count']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="edit.php?id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Delete User"
                                                        onclick="confirmDelete(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select" name="bulk_action" style="width: auto;">
                            <option value="">Bulk Actions</option>
                            <option value="activate">Activate</option>
                            <option value="deactivate">Deactivate</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="submit" class="btn btn-primary" id="apply-bulk-action">
                            Apply
                        </button>
                        <span class="text-muted ms-2" id="selected-count">0 selected</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this user? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" action="delete.php" method="POST" style="display: inline;">
                    <input type="hidden" name="id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
    const selectedCount = document.getElementById('selected-count');
    const bulkActionForm = document.getElementById('bulk-action-form');
    
    // Initialize DataTable
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#users-table').DataTable({
            responsive: true,
            columnDefs: [
                { orderable: false, targets: [0, 8] },
                { searchable: false, targets: [0, 5, 6, 7, 8] }
            ]
        });
    }
    
    // Select all functionality
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        updateSelectedCount();
    });
    
    // Update count when individual checkboxes change
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    
    // Update selected count
    function updateSelectedCount() {
        const count = document.querySelectorAll('input[name="user_ids[]"]:checked').length;
        selectedCount.textContent = count + ' selected';
    }
    
    // Bulk action form submission
    bulkActionForm.addEventListener('submit', function(e) {
        const action = this.querySelector('select[name="bulk_action"]').value;
        const checked = document.querySelectorAll('input[name="user_ids[]"]:checked').length > 0;
        
        if (!action) {
            e.preventDefault();
            alert('Please select a bulk action');
            return;
        }
        
        if (!checked) {
            e.preventDefault();
            alert('Please select at least one user');
            return;
        }
        
        if (action === 'delete' && !confirm('Are you sure you want to delete the selected users?')) {
            e.preventDefault();
        }
    });
});

function confirmDelete(userId) {
    document.getElementById('deleteUserId').value = userId;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>