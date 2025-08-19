<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$pdo = getPDO();

// Handle status change
if (isset($_GET['change_status'])) {
    $id = (int)$_GET['change_status'];
    $nextStatus = $_GET['to'] ?? 'pending';
    $allowed = ['pending','in_progress','completed','delivered'];
    if (in_array($nextStatus, $allowed)) {
        $stmt = $pdo->prepare('UPDATE repairs SET status = ?, completed_at = IF(? = "completed", NOW(), completed_at) WHERE id = ?');
        $stmt->execute([$nextStatus, $nextStatus, $id]);
    }
    header('Location: repairs_list.php');
    exit;
}

$repairs = $pdo->query('SELECT * FROM repairs ORDER BY id DESC')->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Repairs</h2>
    <a href="repair_form.php" class="btn btn-primary">New Repair</a>
</div>
<table class="table table-bordered table-striped">
    <thead>
        <tr><th>ID</th><th>Customer</th><th>Device</th><th>Problem</th><th>Status</th><th>Estimate</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <?php foreach ($repairs as $r): ?>
            <tr>
                <td><?= $r['id']; ?></td>
                <td><?= htmlspecialchars($r['customer_name']); ?> <br> <?= htmlspecialchars($r['customer_phone']); ?></td>
                <td><?= htmlspecialchars($r['device_model']); ?></td>
                <td><?= nl2br(htmlspecialchars($r['problem_description'])); ?></td>
                <td><?= ucfirst(str_replace('_',' ', $r['status'])); ?></td>
                <td><?= number_format($r['estimate_cost'],2); ?></td>
                <td>
                    <a href="repair_form.php?id=<?= $r['id']; ?>" class="btn btn-sm btn-warning mb-1">Edit</a>
                    <?php if ($r['status']=='pending'): ?>
                        <a href="?change_status=<?= $r['id']; ?>&to=in_progress" class="btn btn-sm btn-info mb-1">Start</a>
                    <?php elseif ($r['status']=='in_progress'): ?>
                        <a href="?change_status=<?= $r['id']; ?>&to=completed" class="btn btn-sm btn-success mb-1">Complete</a>
                    <?php elseif ($r['status']=='completed'): ?>
                        <a href="?change_status=<?= $r['id']; ?>&to=delivered" class="btn btn-sm btn-secondary mb-1">Deliver</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>