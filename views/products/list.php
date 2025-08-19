<?php
require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
	csrf_check();
	$id = (int)$_POST['delete_id'];
	$stmt = $db->prepare('DELETE FROM products WHERE id = ?');
	$stmt->execute([$id]);
}

$products = $db->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
?>
<div class="card">
	<div style="display:flex;justify-content:space-between;align-items:center;">
		<h2>Products</h2>
		<a class="btn" href="?r=product_form">Add Product</a>
	</div>
	<table class="table">
		<thead>
			<tr>
				<th>SKU</th>
				<th>Name</th>
				<th>Price</th>
				<th>Qty</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($products as $p): ?>
			<tr>
				<td><?php echo h($p['sku']); ?></td>
				<td><?php echo h($p['name']); ?></td>
				<td><?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$p['sell_price'], 2); ?></td>
				<td><?php echo (int)$p['quantity']; ?></td>
				<td>
					<a class="btn secondary" href="?r=product_form&id=<?php echo (int)$p['id']; ?>">Edit</a>
					<form method="post" style="display:inline" onsubmit="return confirm('Delete this product?');">
						<input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
						<input type="hidden" name="delete_id" value="<?php echo (int)$p['id']; ?>">
						<button class="btn danger" type="submit">Delete</button>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>