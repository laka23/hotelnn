<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Util.php';
$pdo = Database::connection();
$currency = Database::fetchSetting('currency', 'USD');

// Initialize cart in session
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

// Add item to cart
if (isset($_POST['add_product_id'])) {
    if (!Util::verifyCsrf($_POST['csrf'] ?? '')) { die('Invalid CSRF'); }
    $pid = (int)$_POST['add_product_id'];
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $stmt = $pdo->prepare('SELECT id, name, sell_price FROM products WHERE id=? AND active=1');
    $stmt->execute([$pid]);
    if ($p = $stmt->fetch()) {
        if (!isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid] = ['id'=>$pid,'name'=>$p['name'],'price'=>(float)$p['sell_price'],'qty'=>0];
        }
        $_SESSION['cart'][$pid]['qty'] += $qty;
    }
    Util::redirect('index.php?page=pos');
}

// Update quantities
if (isset($_POST['update_cart'])) {
    if (!Util::verifyCsrf($_POST['csrf'] ?? '')) { die('Invalid CSRF'); }
    foreach ($_POST['qty'] as $pid => $qty) {
        $pid = (int)$pid; $qty = (int)$qty;
        if ($qty <= 0) { unset($_SESSION['cart'][$pid]); }
        else if (isset($_SESSION['cart'][$pid])) { $_SESSION['cart'][$pid]['qty'] = $qty; }
    }
    Util::redirect('index.php?page=pos');
}

// Clear cart
if (isset($_POST['clear_cart'])) {
    if (!Util::verifyCsrf($_POST['csrf'] ?? '')) { die('Invalid CSRF'); }
    $_SESSION['cart'] = [];
    Util::redirect('index.php?page=pos');
}

// Checkout
if (isset($_POST['checkout'])) {
    if (!Util::verifyCsrf($_POST['csrf'] ?? '')) { die('Invalid CSRF'); }
    $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $discount = (float)($_POST['discount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $tax_rate = (float)Database::fetchSetting('tax_rate', '0');

    $subtotal = 0.0;
    foreach ($_SESSION['cart'] as $item) { $subtotal += $item['price'] * $item['qty']; }
    $tax = round($subtotal * $tax_rate, 2);
    $total = max(0.0, $subtotal + $tax - $discount);

    if ($total > 0 && !empty($_SESSION['cart'])) {
        $pdo->beginTransaction();
        try {
            // Generate invoice number
            $inv = 'INV' . date('YmdHis');
            $stmt = $pdo->prepare('INSERT INTO sales (invoice_number, customer_id, subtotal, tax, discount, total, paid, payment_method) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$inv, $customer_id, $subtotal, $tax, $discount, $total, $total, $payment_method]);
            $sale_id = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?,?,?,?,?)');
            $stockStmt = $pdo->prepare('UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?');
            $moveStmt = $pdo->prepare('INSERT INTO stock_movements (product_id, change_qty, reason, reference_type, reference_id) VALUES (?,?,?,?,?)');

            foreach ($_SESSION['cart'] as $pid => $item) {
                $lineTotal = $item['price'] * $item['qty'];
                $itemStmt->execute([$sale_id, $pid, $item['qty'], $item['price'], $lineTotal]);
                $stockStmt->execute([$item['qty'], $pid]);
                $moveStmt->execute([$pid, -$item['qty'], 'sale', 'sale', $sale_id]);
            }

            $pdo->commit();
            $_SESSION['cart'] = [];
            Util::redirect('print/invoice.php?id=' . $sale_id);
        } catch (Throwable $e) {
            $pdo->rollBack();
            $error = 'Checkout failed: ' . $e->getMessage();
        }
    }
}

// Load products for quick add
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
  $prodStmt = $pdo->prepare('SELECT id, name, sku, sell_price, stock_qty FROM products WHERE active=1 AND (name LIKE ? OR sku LIKE ?) ORDER BY id DESC LIMIT 100');
  $prodStmt->execute(['%'.$search.'%', '%'.$search.'%']);
  $products = $prodStmt->fetchAll();
} else {
  $prodStmt = $pdo->query('SELECT id, name, sku, sell_price, stock_qty FROM products WHERE active=1 ORDER BY id DESC LIMIT 50');
  $products = $prodStmt->fetchAll();
}

// Customers
$custStmt = $pdo->query('SELECT id, name FROM customers ORDER BY name ASC');
$customers = $custStmt->fetchAll();

// Totals
$subtotal = 0.0; foreach ($_SESSION['cart'] as $item) { $subtotal += $item['price'] * $item['qty']; }
$tax = round($subtotal * (float)Database::fetchSetting('tax_rate', '0'), 2);
$total = $subtotal + $tax;
?>
<div class="pos">
  <div class="panel">
    <h2>Products</h2>
    <form class="searchbar" method="get" action="index.php">
      <input type="hidden" name="page" value="pos">
      <input name="q" placeholder="Quick search..." value="<?php echo Util::e($_GET['q'] ?? ''); ?>">
      <button class="secondary" type="submit">Search</button>
    </form>
    <div style="max-height: 360px; overflow: auto;">
      <table>
        <thead><tr><th>SKU</th><th>Name</th><th class="right">Price</th><th class="right">Stock</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($products as $p): ?>
          <tr>
            <td><?php echo Util::e($p['sku']); ?></td>
            <td><?php echo Util::e($p['name']); ?></td>
            <td class="right"><?php echo Util::money($p['sell_price'], $currency); ?></td>
            <td class="right"><?php echo (int)$p['stock_qty']; ?></td>
            <td class="right">
              <form method="post" style="display:inline-block;">
                <input type="hidden" name="csrf" value="<?php echo Util::csrfToken(); ?>">
                <input type="hidden" name="add_product_id" value="<?php echo $p['id']; ?>">
                <input type="number" name="qty" value="1" min="1" style="width:70px;">
                <button type="submit">Add</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <h2>Cart</h2>
    <?php if (!empty($error)): ?><div class="muted"><?php echo Util::e($error); ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?php echo Util::csrfToken(); ?>">
      <table>
        <thead><tr><th>Item</th><th class="right">Price</th><th class="right">Qty</th><th class="right">Total</th></tr></thead>
        <tbody>
          <?php foreach ($_SESSION['cart'] as $pid => $item): ?>
          <tr>
            <td><?php echo Util::e($item['name']); ?></td>
            <td class="right"><?php echo Util::money($item['price'], $currency); ?></td>
            <td class="right"><input type="number" name="qty[<?php echo $pid; ?>]" value="<?php echo (int)$item['qty']; ?>" min="0" style="width:80px;"></td>
            <td class="right"><?php echo Util::money($item['price'] * $item['qty'], $currency); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="row" style="justify-content: space-between; margin-top: 8px;">
        <div>
          <button class="secondary" name="update_cart" value="1">Update</button>
          <button class="danger" name="clear_cart" value="1">Clear</button>
        </div>
        <div class="right">
          <div class="muted">Subtotal: <?php echo Util::money($subtotal, $currency); ?></div>
          <div class="muted">Tax: <?php echo Util::money($tax, $currency); ?></div>
          <div><strong>Total: <?php echo Util::money($total, $currency); ?></strong></div>
        </div>
      </div>

      <div class="grid-2" style="margin-top: 12px;">
        <div>
          <label>Customer</label>
          <select name="customer_id">
            <option value="">Walk-in</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?php echo $c['id']; ?>"><?php echo Util::e($c['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Discount</label>
          <input type="number" step="0.01" name="discount" value="0">
        </div>
        <div>
          <label>Payment Method</label>
          <select name="payment_method">
            <option value="cash">Cash</option>
            <option value="card">Card</option>
            <option value="bank">Bank</option>
          </select>
        </div>
      </div>
      <div style="margin-top: 12px;">
        <button name="checkout" value="1" <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>Checkout & Print</button>
      </div>
    </form>
  </div>
</div>

