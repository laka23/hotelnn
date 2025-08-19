<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$pdo = getPDO();
$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<div class='alert alert-danger'>No invoice ID provided.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$saleStmt = $pdo->prepare('SELECT * FROM sales WHERE id = ?');
$saleStmt->execute([$id]);
$sale = $saleStmt->fetch();
if (!$sale) {
    echo "<div class='alert alert-danger'>Invoice not found.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}
$itemStmt = $pdo->prepare('SELECT si.*, p.name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?');
$itemStmt->execute([$id]);
$items = $itemStmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Invoice #<?= $sale['id']; ?></h2>
    <button class="btn btn-secondary" onclick="printElement('invoice')">Print</button>
</div>
<div id="invoice" class="p-3 border">
    <h3 class="text-center">Mobile Shop Invoice</h3>
    <p>Date: <?= $sale['sale_date']; ?></p>
    <?php if ($sale['customer_name']): ?>
        <p>Customer: <?= htmlspecialchars($sale['customer_name']); ?> | Phone: <?= htmlspecialchars($sale['customer_phone']); ?></p>
    <?php endif; ?>
    <table class="table table-bordered">
        <thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
        <tbody>
            <?php foreach ($items as $index => $it): ?>
            <tr>
                <td><?= $index+1; ?></td>
                <td><?= htmlspecialchars($it['name']); ?></td>
                <td><?= $it['quantity']; ?></td>
                <td><?= number_format($it['unit_price'],2); ?></td>
                <td><?= number_format($it['subtotal'],2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="4" class="text-end fw-bold">Total</td><td class="fw-bold"><?= number_format($sale['total_amount'],2); ?></td></tr>
        </tfoot>
    </table>
    <p class="text-center">Thank you for your purchase!</p>
</div>
<a href="sale_new.php" class="btn btn-primary mt-3">New Sale</a>
<?php include __DIR__ . '/../includes/footer.php'; ?>