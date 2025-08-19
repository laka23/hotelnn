<?php
require_auth();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT r.*, c.name AS customer_name, c.phone AS customer_phone FROM repairs r LEFT JOIN customers c ON c.id=r.customer_id WHERE r.id = ?');
$stmt->execute([$id]);
$repair = $stmt->fetch();
if (!$repair) { echo 'Not found'; return; }
$parts = $db->prepare('SELECT rp.*, p.name FROM repair_parts rp JOIN products p ON p.id=rp.product_id WHERE rp.repair_id=?');
$parts->execute([$id]);
$parts = $parts->fetchAll();
?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Repair Ticket <?php echo h($repair['ticket_no']); ?></title>
	<link rel="stylesheet" href="assets/css/styles.css">
	<style>body{background:#fff}</style>
</head>
<body onload="window.print()">
	<div class="container receipt">
		<div class="center">
			<div><strong><?php echo h($settings['shop_name']); ?></strong></div>
			<div><?php echo h($settings['shop_address']); ?></div>
			<div><?php echo h($settings['shop_phone']); ?></div>
		</div>
		<hr>
		<div>Ticket: <?php echo h($repair['ticket_no']); ?> | Date: <?php echo h($repair['created_at']); ?></div>
		<div>Customer: <?php echo h($repair['customer_name'] ?? ''); ?> <?php echo h($repair['customer_phone'] ? '(' . $repair['customer_phone'] . ')' : ''); ?></div>
		<div>Device: <?php echo h(trim(($repair['device_brand'] ?? '').' '.($repair['device_model'] ?? ''))); ?> | IMEI: <?php echo h($repair['imei'] ?? ''); ?></div>
		<div>Status: <?php echo h(ucwords(str_replace('_',' ',$repair['status']))); ?></div>
		<div>Problem: <?php echo nl2br(h($repair['problem_description'] ?? '')); ?></div>
		<h4>Parts</h4>
		<table class="table">
			<thead><tr><th>Name</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
			<tbody>
			<?php foreach ($parts as $p): ?>
				<tr>
					<td><?php echo h($p['name']); ?></td>
					<td><?php echo (int)$p['quantity']; ?></td>
					<td><?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$p['unit_price'],2); ?></td>
					<td><?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$p['total_price'],2); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<div style="text-align:right">
			<div>Parts: <?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$repair['parts_cost'],2); ?></div>
			<div>Labor: <?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$repair['labor_cost'],2); ?></div>
			<div><strong>Total: <?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$repair['total_cost'],2); ?></strong></div>
		</div>
		<div class="center">Please keep this ticket for pickup.</div>
	</div>
</body>
</html>