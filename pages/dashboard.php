<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Util.php';
$pdo = Database::connection();
$stats = [
  'products' => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
  'customers' => (int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn(),
  'repairs_open' => (int)$pdo->query("SELECT COUNT(*) FROM repairs WHERE status IN ('received','in_progress','awaiting_parts')")->fetchColumn(),
  'sales_today' => (float)($pdo->query("SELECT IFNULL(SUM(total),0) FROM sales WHERE DATE(created_at)=CURDATE()") ->fetchColumn()),
];
$currency = Database::fetchSetting('currency', 'USD');
?>
<div class="row">
  <div class="col panel">
    <h3>Products</h3>
    <div class="big"><?php echo $stats['products']; ?></div>
  </div>
  <div class="col panel">
    <h3>Customers</h3>
    <div class="big"><?php echo $stats['customers']; ?></div>
  </div>
  <div class="col panel">
    <h3>Open Repairs</h3>
    <div class="big"><?php echo $stats['repairs_open']; ?></div>
  </div>
  <div class="col panel">
    <h3>Sales Today</h3>
    <div class="big"><?php echo Util::money($stats['sales_today'], $currency); ?></div>
  </div>
</div>

<div class="panel">
  <h2>Quick Actions</h2>
  <div class="row">
    <a class="btn-primary inline" href="index.php?page=pos">New Sale</a>
    <a class="btn-primary inline" href="index.php?page=repair_form">New Repair</a>
    <a class="btn-primary inline" href="index.php?page=product_form">Add Product</a>
  </div>
</div>

