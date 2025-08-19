<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Util.php';
$pdo = Database::connection();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT s.*, c.name AS customer_name, c.phone AS customer_phone FROM sales s LEFT JOIN customers c ON c.id = s.customer_id WHERE s.id = ?');
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale) { die('Invoice not found'); }
$items = $pdo->prepare('SELECT si.*, p.sku, p.name FROM sale_items si JOIN products p ON p.id = si.product_id WHERE si.sale_id = ?');
$items->execute([$id]);
$items = $items->fetchAll();

$shop_name = Database::fetchSetting('shop_name', APP_NAME);
$shop_address = Database::fetchSetting('shop_address', '');
$shop_phone = Database::fetchSetting('shop_phone', '');
$currency = Database::fetchSetting('currency', 'USD');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Invoice <?php echo Util::e($sale['invoice_number']); ?></title>
  <link rel="stylesheet" href="../assets/css/print.css">
</head>
<body onload="window.print()">
  <div style="max-width:700px;margin:auto;">
    <h2 style="margin:0;"><?php echo Util::e($shop_name); ?></h2>
    <div><?php echo Util::e($shop_address); ?> | <?php echo Util::e($shop_phone); ?></div>
    <hr>
    <div><strong>Invoice:</strong> <?php echo Util::e($sale['invoice_number']); ?> &nbsp; <strong>Date:</strong> <?php echo Util::e($sale['created_at']); ?></div>
    <div><strong>Customer:</strong> <?php echo Util::e($sale['customer_name'] ?? 'Walk-in'); ?> <?php echo $sale['customer_phone'] ? ' - '.Util::e($sale['customer_phone']) : ''; ?></div>
    <table style="width:100%;margin-top:8px;">
      <thead><tr><th>SKU</th><th>Item</th><th style="text-align:right">Qty</th><th style="text-align:right">Price</th><th style="text-align:right">Total</th></tr></thead>
      <tbody>
        <?php foreach ($items as $it): ?>
        <tr>
          <td><?php echo Util::e($it['sku']); ?></td>
          <td><?php echo Util::e($it['name']); ?></td>
          <td style="text-align:right"><?php echo (int)$it['quantity']; ?></td>
          <td style="text-align:right"><?php echo Util::money($it['unit_price'], $currency); ?></td>
          <td style="text-align:right"><?php echo Util::money($it['total_price'], $currency); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div style="text-align:right;margin-top:8px;">
      <div>Subtotal: <?php echo Util::money($sale['subtotal'], $currency); ?></div>
      <div>Tax: <?php echo Util::money($sale['tax'], $currency); ?></div>
      <div>Discount: <?php echo Util::money($sale['discount'], $currency); ?></div>
      <div><strong>Total: <?php echo Util::money($sale['total'], $currency); ?></strong></div>
      <div>Paid: <?php echo Util::money($sale['paid'], $currency); ?> (<?php echo Util::e($sale['payment_method']); ?>)</div>
    </div>
    <hr>
    <div style="text-align:center;">Thank you for your business!</div>
  </div>
</body>
</html>

