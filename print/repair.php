<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Util.php';
$pdo = Database::connection();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT r.*, c.name AS customer_name, c.phone AS customer_phone FROM repairs r LEFT JOIN customers c ON c.id = r.customer_id WHERE r.id=?');
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { die('Repair not found'); }

$shop_name = Database::fetchSetting('shop_name', APP_NAME);
$shop_address = Database::fetchSetting('shop_address', '');
$shop_phone = Database::fetchSetting('shop_phone', '');
$currency = Database::fetchSetting('currency', 'USD');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Repair Ticket <?php echo Util::e($r['ticket_number']); ?></title>
  <link rel="stylesheet" href="../assets/css/print.css">
</head>
<body onload="window.print()">
  <div style="max-width:700px;margin:auto;">
    <h2 style="margin:0;"><?php echo Util::e($shop_name); ?></h2>
    <div><?php echo Util::e($shop_address); ?> | <?php echo Util::e($shop_phone); ?></div>
    <hr>
    <div><strong>Ticket:</strong> <?php echo Util::e($r['ticket_number']); ?> &nbsp; <strong>Date:</strong> <?php echo Util::e($r['created_at']); ?></div>
    <div><strong>Customer:</strong> <?php echo Util::e($r['customer_name'] ?? ''); ?> <?php echo $r['customer_phone'] ? ' - '.Util::e($r['customer_phone']) : ''; ?></div>
    <div><strong>Device:</strong> <?php echo Util::e($r['device_make'] . ' ' . $r['device_model']); ?> <?php echo $r['imei'] ? '(IMEI: '.Util::e($r['imei']).')' : ''; ?></div>
    <div><strong>Accessories Received:</strong> <?php echo Util::e($r['accessories_received']); ?></div>
    <div><strong>Fault:</strong> <?php echo nl2br(Util::e($r['fault_description'])); ?></div>
    <div><strong>Diagnosis:</strong> <?php echo nl2br(Util::e($r['diagnosis'])); ?></div>
    <div><strong>Status:</strong> <?php echo Util::e(ucfirst(str_replace('_',' ',$r['status']))); ?></div>
    <div><strong>Estimate:</strong> Parts <?php echo Util::money($r['parts_cost'], $currency); ?> + Labor <?php echo Util::money($r['labor_cost'], $currency); ?> = <?php echo Util::money($r['total_estimate'], $currency); ?> | Prepayment: <?php echo Util::money($r['prepayment'], $currency); ?></div>
    <hr>
    <div style="font-size:12px;">I authorize the repair and acknowledge that data loss risk exists. Shop is not responsible for pre-existing damage or waterproof seal loss.</div>
    <div style="display:flex;justify-content:space-between;margin-top:40px;">
      <div>Customer Signature: ______________________</div>
      <div>Date: ____________</div>
    </div>
  </div>
</body>
</html>

