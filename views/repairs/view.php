<?php
require_auth();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT r.*, c.name AS customer_name FROM repairs r LEFT JOIN customers c ON c.id=r.customer_id WHERE r.id = ?');
$stmt->execute([$id]);
$repair = $stmt->fetch();
if (!$repair) {
	echo '<div class="card">Repair not found.</div>';
	return;
}
$parts = $db->prepare('SELECT rp.*, p.name FROM repair_parts rp JOIN products p ON p.id=rp.product_id WHERE rp.repair_id=?');
$parts->execute([$id]);
$parts = $parts->fetchAll();
?>
<div class="card">
	<h2>Repair Ticket <?php echo h($repair['ticket_no']); ?></h2>
	<div>Customer: <?php echo h($repair['customer_name'] ?? ''); ?></div>
	<div>Device: <?php echo h(trim(($repair['device_brand'] ?? '').' '.($repair['device_model'] ?? ''))); ?> | IMEI: <?php echo h($repair['imei'] ?? ''); ?></div>
	<div>Status: <?php echo h(ucwords(str_replace('_',' ',$repair['status']))); ?> | Created: <?php echo h($repair['created_at']); ?></div>
	<div>Description: <?php echo nl2br(h($repair['problem_description'] ?? '')); ?></div>

	<h3>Parts</h3>
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
	<p>
		<a class="btn" href="?r=repair_print&id=<?php echo (int)$repair['id']; ?>" target="_blank">Print Ticket</a>
		<a class="btn secondary" href="?r=repairs">Back</a>
		<a class="btn secondary" href="?r=repair_form&id=<?php echo (int)$repair['id']; ?>">Edit</a>
	</p>
</div>