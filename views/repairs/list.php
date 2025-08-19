<?php
require_auth();
$repairs = $db->query('SELECT r.*, c.name AS customer_name FROM repairs r LEFT JOIN customers c ON c.id=r.customer_id ORDER BY r.id DESC LIMIT 100')->fetchAll();
?>
<div class="card">
	<div style="display:flex;justify-content:space-between;align-items:center;">
		<h2>Repairs</h2>
		<a class="btn" href="?r=repair_form">New Repair Ticket</a>
	</div>
	<table class="table">
		<thead><tr><th>Ticket</th><th>Customer</th><th>Device</th><th>Status</th><th>Total</th><th>Date</th><th></th></tr></thead>
		<tbody>
		<?php foreach ($repairs as $r): ?>
			<tr>
				<td><?php echo h($r['ticket_no']); ?></td>
				<td><?php echo h($r['customer_name'] ?? ''); ?></td>
				<td><?php echo h(trim(($r['device_brand'] ?? '').' '.($r['device_model'] ?? ''))); ?></td>
				<td><?php echo h($r['status']); ?></td>
				<td><?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$r['total_cost'],2); ?></td>
				<td><?php echo h($r['created_at']); ?></td>
				<td>
					<a class="btn secondary" href="?r=repair_view&id=<?php echo (int)$r['id']; ?>">View</a>
					<a class="btn" href="?r=repair_print&id=<?php echo (int)$r['id']; ?>" target="_blank">Print</a>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>