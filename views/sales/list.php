<?php
require_auth();
$sales = $db->query('SELECT s.*, c.name AS customer_name FROM sales s LEFT JOIN customers c ON c.id=s.customer_id ORDER BY s.id DESC LIMIT 100')->fetchAll();
?>
<div class="card">
	<h2>Sales</h2>
	<table class="table">
		<thead><tr><th>Invoice</th><th>Customer</th><th>Total</th><th>Paid</th><th>Date</th><th></th></tr></thead>
		<tbody>
		<?php foreach ($sales as $s): ?>
			<tr>
				<td><?php echo h($s['invoice_no']); ?></td>
				<td><?php echo h($s['customer_name'] ?? 'Walk-in'); ?></td>
				<td><?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$s['total'], 2); ?></td>
				<td><?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$s['paid_amount'], 2); ?></td>
				<td><?php echo h($s['created_at']); ?></td>
				<td>
					<a class="btn secondary" href="?r=sale_view&id=<?php echo (int)$s['id']; ?>">View</a>
					<a class="btn" href="?r=sale_print&id=<?php echo (int)$s['id']; ?>" target="_blank">Print</a>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>