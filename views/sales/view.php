<?php
require_auth();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT s.*, c.name AS customer_name FROM sales s LEFT JOIN customers c ON c.id=s.customer_id WHERE s.id = ?');
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale) {
	echo '<div class="card">Sale not found.</div>';
	return;
}
$items = $db->prepare('SELECT si.*, p.sku, p.name FROM sale_items si JOIN products p ON p.id=si.product_id WHERE si.sale_id = ?');
$items->execute([$id]);
$items = $items->fetchAll();
?>
<div class="card">
	<h2>Invoice <?php echo h($sale['invoice_no']); ?></h2>
	<div>Customer: <?php echo h($sale['customer_name'] ?? 'Walk-in'); ?></div>
	<div>Date: <?php echo h($sale['created_at']); ?></div>
	<table class="table">
		<thead><tr><th>SKU</th><th>Name</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
		<tbody>
		<?php foreach ($items as $it): ?>
			<tr>
				<td><?php echo h($it['sku']); ?></td>
				<td><?php echo h($it['name']); ?></td>
				<td><?php echo (int)$it['quantity']; ?></td>
				<td><?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$it['unit_price'],2); ?></td>
				<td><?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$it['total_price'],2); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<div style="text-align:right">
		<div>Subtotal: <?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$sale['subtotal'],2); ?></div>
		<div>Tax: <?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$sale['tax'],2); ?></div>
		<div>Discount: <?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$sale['discount'],2); ?></div>
		<div><strong>Total: <?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$sale['total'],2); ?></strong></div>
	</div>
	<p>
		<a class="btn" href="?r=sale_print&id=<?php echo (int)$sale['id']; ?>" target="_blank">Print</a>
		<a class="btn secondary" href="?r=sales">Back</a>
	</p>
</div>