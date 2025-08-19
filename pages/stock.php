<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$pdo = getPDO();

// Handle adjustment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $adjust_qty = (int)$_POST['adjust_qty'];
    if ($adjust_qty !== 0) {
        $pdo->beginTransaction();
        try {
            // Update product stock
            $stmt = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?');
            $stmt->execute([$adjust_qty, $product_id]);
            // Log movement
            $stmt = $pdo->prepare('INSERT INTO stock_movements (product_id, qty_change, movement_type, description) VALUES (?,?,?,?)');
            $type = $adjust_qty > 0 ? 'IN' : 'OUT';
            $desc = 'Manual adjustment';
            $stmt->execute([$product_id, $adjust_qty, $type, $desc]);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

$products = $pdo->query('SELECT * FROM products ORDER BY name')->fetchAll();
?>
<h2>Stock Management</h2>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Current Stock</th>
            <th>Adjust</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= $p['id']; ?></td>
                <td><?= htmlspecialchars($p['name']); ?></td>
                <td><?= $p['stock_quantity']; ?></td>
                <td>
                    <form class="d-flex" method="post" style="gap:0.5rem;">
                        <input type="hidden" name="product_id" value="<?= $p['id']; ?>">
                        <input type="number" name="adjust_qty" class="form-control" style="max-width:120px;" placeholder="Qty (+/-)" required>
                        <button class="btn btn-primary" type="submit">Update</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>