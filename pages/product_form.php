<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$pdo = getPDO();

$id = $_GET['id'] ?? null;
$isEdit = $id !== null;
$name = $brand = $model = $cost_price = $sell_price = $stock_quantity = '';

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        echo "<div class='alert alert-danger'>Product not found.</div>";
        include __DIR__ . '/../includes/footer.php';
        exit;
    }
    extract($product);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $cost_price = $_POST['cost_price'];
    $sell_price = $_POST['sell_price'];
    $stock_quantity = $_POST['stock_quantity'];

    if ($isEdit) {
        $stmt = $pdo->prepare('UPDATE products SET name=?, brand=?, model=?, cost_price=?, sell_price=?, stock_quantity=? WHERE id=?');
        $stmt->execute([$name, $brand, $model, $cost_price, $sell_price, $stock_quantity, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO products (name, brand, model, cost_price, sell_price, stock_quantity) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$name, $brand, $model, $cost_price, $sell_price, $stock_quantity]);
    }
    header('Location: product_list.php');
    exit;
}
?>
<h2><?= $isEdit ? 'Edit' : 'Add'; ?> Product</h2>
<form method="post">
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name); ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Brand</label>
            <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($brand); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Model</label>
            <input type="text" name="model" class="form-control" value="<?= htmlspecialchars($model); ?>">
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Cost Price</label>
            <input type="number" step="0.01" name="cost_price" class="form-control" value="<?= htmlspecialchars($cost_price); ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Selling Price</label>
            <input type="number" step="0.01" name="sell_price" class="form-control" value="<?= htmlspecialchars($sell_price); ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Stock Quantity</label>
            <input type="number" name="stock_quantity" class="form-control" value="<?= htmlspecialchars($stock_quantity); ?>" required>
        </div>
    </div>
    <button type="submit" class="btn btn-success">Save</button>
    <a href="product_list.php" class="btn btn-secondary">Cancel</a>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>