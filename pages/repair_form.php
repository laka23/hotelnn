<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$pdo = getPDO();
$id = $_GET['id'] ?? null;
$isEdit = $id !== null;
$customer_name = $customer_phone = $device_model = $problem_description = $estimate_cost = $status = '';
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM repairs WHERE id = ?');
    $stmt->execute([$id]);
    $rep = $stmt->fetch();
    if (!$rep) {
        echo "<div class='alert alert-danger'>Repair not found</div>";
        include __DIR__ . '/../includes/footer.php';
        exit;
    }
    extract($rep);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = $_POST['customer_name'];
    $customer_phone = $_POST['customer_phone'];
    $device_model = $_POST['device_model'];
    $problem_description = $_POST['problem_description'];
    $estimate_cost = $_POST['estimate_cost'];
    $status = $_POST['status'] ?? 'pending';

    if ($isEdit) {
        $stmt = $pdo->prepare('UPDATE repairs SET customer_name=?, customer_phone=?, device_model=?, problem_description=?, estimate_cost=?, status=? WHERE id=?');
        $stmt->execute([$customer_name, $customer_phone, $device_model, $problem_description, $estimate_cost, $status, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO repairs (customer_name, customer_phone, device_model, problem_description, estimate_cost, status) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$customer_name, $customer_phone, $device_model, $problem_description, $estimate_cost, $status]);
    }
    header('Location: repairs_list.php');
    exit;
}
?>
<h2><?= $isEdit ? 'Edit' : 'New'; ?> Repair</h2>
<form method="post">
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Customer Name</label>
            <input type="text" name="customer_name" class="form-control" value="<?= htmlspecialchars($customer_name); ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Customer Phone</label>
            <input type="text" name="customer_phone" class="form-control" value="<?= htmlspecialchars($customer_phone); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Device Model</label>
            <input type="text" name="device_model" class="form-control" value="<?= htmlspecialchars($device_model); ?>" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Problem Description</label>
        <textarea name="problem_description" class="form-control" rows="3" required><?= htmlspecialchars($problem_description); ?></textarea>
    </div>
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Estimate Cost</label>
            <input type="number" step="0.01" name="estimate_cost" class="form-control" value="<?= htmlspecialchars($estimate_cost); ?>">
        </div>
        <?php if ($isEdit): ?>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <?php foreach (['pending','in_progress','completed','delivered'] as $st): ?>
                    <option value="<?= $st; ?>" <?= $status==$st?'selected':''; ?>><?= ucfirst(str_replace('_',' ',$st)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>
    <button type="submit" class="btn btn-success">Save</button>
    <a href="repairs_list.php" class="btn btn-secondary">Cancel</a>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>