<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../components/connect.php';

$errors = [];
$supplier = [
    'name' => '',
    'contact_person' => '',
    'email' => '',
    'phone' => '',
    'address' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier['name'] = trim($_POST['name'] ?? '');
    $supplier['contact_person'] = trim($_POST['contact_person'] ?? null);
    $supplier['email'] = trim($_POST['email'] ?? null);
    $supplier['phone'] = trim($_POST['phone'] ?? null);
    $supplier['address'] = trim($_POST['address'] ?? null);

    // Validation
    if (empty($supplier['name'])) {
        $errors['name'] = 'Supplier name is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO suppliers 
                (name, contact_person, email, phone, address)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $supplier['name'],
                $supplier['contact_person'],
                $supplier['email'],
                $supplier['phone'],
                $supplier['address']
            ]);

            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Supplier added successfully'
            ];
            header("Location: list.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="mb-4">Add New Supplier</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="card mb-4">
                    <div class="card-header">Supplier Details</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Supplier Name *</label>
                            <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   id="name" name="name" value="<?= htmlspecialchars($supplier['name']) ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contact_person" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person" 
                                   value="<?= htmlspecialchars($supplier['contact_person']) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($supplier['email']) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($supplier['phone']) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?= 
                                htmlspecialchars($supplier['address']) 
                            ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>