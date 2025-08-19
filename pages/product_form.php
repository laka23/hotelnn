<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Util.php';
$pdo = Database::connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$product = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Util::verifyCsrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $cost = (float)($_POST['cost_price'] ?? 0);
        $price = (float)($_POST['sell_price'] ?? 0);
        $stock = (int)($_POST['stock_qty'] ?? 0);
        $track_serial = isset($_POST['track_serial']) ? 1 : 0;
        $active = isset($_POST['active']) ? 1 : 0;

        if ($id) {
            $stmt = $pdo->prepare('UPDATE products SET sku=?, name=?, category=?, cost_price=?, sell_price=?, stock_qty=?, track_serial=?, active=? WHERE id=?');
            $stmt->execute([$sku, $name, $category, $cost, $price, $stock, $track_serial, $active, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO products (sku, name, category, cost_price, sell_price, stock_qty, track_serial, active) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$sku, $name, $category, $cost, $price, $stock, $track_serial, $active]);
        }
        Util::redirect('index.php?page=products');
    }
}
?>
<div class="panel">
  <h2><?php echo $id ? 'Edit' : 'Add'; ?> Product</h2>
  <?php if (!empty($error)): ?><div class="muted"><?php echo Util::e($error); ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo Util::csrfToken(); ?>">
    <div class="grid-3">
      <div>
        <label>SKU</label>
        <input name="sku" required value="<?php echo Util::e($product['sku'] ?? ''); ?>">
      </div>
      <div>
        <label>Name</label>
        <input name="name" required value="<?php echo Util::e($product['name'] ?? ''); ?>">
      </div>
      <div>
        <label>Category</label>
        <input name="category" value="<?php echo Util::e($product['category'] ?? ''); ?>">
      </div>
      <div>
        <label>Cost Price</label>
        <input type="number" step="0.01" name="cost_price" value="<?php echo Util::e($product['cost_price'] ?? '0'); ?>">
      </div>
      <div>
        <label>Selling Price</label>
        <input type="number" step="0.01" name="sell_price" value="<?php echo Util::e($product['sell_price'] ?? '0'); ?>">
      </div>
      <div>
        <label>Stock Qty</label>
        <input type="number" name="stock_qty" value="<?php echo Util::e($product['stock_qty'] ?? '0'); ?>">
      </div>
      <div>
        <label><input type="checkbox" name="track_serial" <?php echo !empty($product['track_serial']) ? 'checked' : ''; ?>> Track serial/IMEI</label>
      </div>
      <div>
        <label><input type="checkbox" name="active" <?php echo !isset($product['active']) || (int)$product['active'] === 1 ? 'checked' : ''; ?>> Active</label>
      </div>
    </div>
    <div style="margin-top:12px;">
      <button type="submit">Save</button>
      <a class="btn-primary" href="index.php?page=products">Back</a>
    </div>
  </form>
</div>

