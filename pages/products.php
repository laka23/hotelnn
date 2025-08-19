<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Util.php';
$pdo = Database::connection();

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    Util::redirect('index.php?page=products');
}

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR sku LIKE ? ORDER BY id DESC");
    $stmt->execute(['%' . $q . '%', '%' . $q . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM products ORDER BY id DESC');
}
$products = $stmt->fetchAll();
?>
<div class="panel">
  <div class="row" style="justify-content: space-between; align-items: center;">
    <h2>Products</h2>
    <a class="btn-primary" href="index.php?page=product_form">Add Product</a>
  </div>
  <form class="searchbar" method="get">
    <input type="hidden" name="page" value="products">
    <input placeholder="Search by name or SKU" name="q" value="<?php echo Util::e($q); ?>">
    <button type="submit" class="secondary">Search</button>
  </form>
  <table>
    <thead>
      <tr>
        <th>SKU</th>
        <th>Name</th>
        <th class="right">Cost</th>
        <th class="right">Price</th>
        <th class="right">Stock</th>
        <th>Serial</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($products as $p): ?>
      <tr>
        <td><?php echo Util::e($p['sku']); ?></td>
        <td><?php echo Util::e($p['name']); ?></td>
        <td class="right"><?php echo number_format($p['cost_price'],2); ?></td>
        <td class="right"><?php echo number_format($p['sell_price'],2); ?></td>
        <td class="right"><?php echo (int)$p['stock_qty']; ?></td>
        <td><?php echo $p['track_serial'] ? '<span class="badge info">Yes</span>' : 'No'; ?></td>
        <td class="actions">
          <a href="index.php?page=product_form&id=<?php echo $p['id']; ?>">Edit</a>
          <a href="index.php?page=products&action=delete&id=<?php echo $p['id']; ?>" onclick="return confirmDelete('Delete this product?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

