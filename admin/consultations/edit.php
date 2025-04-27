<?php
require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../components/connect.php';

if (!isset($_GET['id'])) {
    header('Location: consultations.php');
    exit();
}

$id = (int)$_GET['id'];
$message = '';

// Fetch consultation
$stmt = $conn->prepare("SELECT * FROM consultations WHERE id = ?");
$stmt->execute([$id]);
$consultation = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$consultation) {
    header('Location: consultations.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? $consultation['status'];
    $type = $_POST['consultation_type'] ?? $consultation['consultation_type'];
    $date = $_POST['consultation_date'] ?? $consultation['consultation_date'];
    $time = $_POST['consultation_time'] ?? $consultation['consultation_time'];
    $symptoms = $_POST['symptoms'] ?? $consultation['symptoms'];

    $update = $conn->prepare("UPDATE consultations SET status=?, consultation_type=?, consultation_date=?, consultation_time=?, symptoms=? WHERE id=?");
    if ($update->execute([$status, $type, $date, $time, $symptoms, $id])) {
        $message = 'Consultation updated successfully!';
        // Refresh data
        $stmt->execute([$id]);
        $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = 'Update failed.';
    }
}
?>
<div class="container mt-4">
    <h2>Edit Consultation</h2>
    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label>Status</label>
            <select class="form-select" name="status">
                <?php foreach (["pending","confirmed","completed","cancelled"] as $s): ?>
                    <option value="<?= $s ?>" <?= $consultation['status']==$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Consultation Type</label>
            <input type="text" class="form-control" name="consultation_type" value="<?= htmlspecialchars($consultation['consultation_type']) ?>">
        </div>
        <div class="mb-3">
            <label>Date</label>
            <input type="date" class="form-control" name="consultation_date" value="<?= htmlspecialchars($consultation['consultation_date']) ?>">
        </div>
        <div class="mb-3">
            <label>Time</label>
            <input type="text" class="form-control" name="consultation_time" value="<?= htmlspecialchars($consultation['consultation_time']) ?>">
        </div>
        <div class="mb-3">
            <label>Symptoms</label>
            <textarea class="form-control" name="symptoms"><?= htmlspecialchars($consultation['symptoms']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="../consultations.php" class="btn btn-secondary">Back</a>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
