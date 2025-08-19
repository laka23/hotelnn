<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$pdo = getPDO();
$products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Products</h2>
    <a href="product_form.php" class="btn btn-primary">Add Product</a>
</div>
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Brand</th>
            <th>Model</th>
            <th>Selling Price</th>
            <th>Stock</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
            <td><?= $p['id']; ?></td>
            <td><?= htmlspecialchars($p['name']); ?></td>
            <td><?= htmlspecialchars($p['brand']); ?></td>
            <td><?= htmlspecialchars($p['model']); ?></td>
            <td><?= number_format($p['sell_price'], 2); ?></td>
            <td><?= $p['stock_quantity']; ?></td>
            <td>
                <a href="product_form.php?id=<?= $p['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                <a href="product_delete.php?id=<?= $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>