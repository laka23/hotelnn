<?php
require_auth();
csrf_check();

$products = $db->query('SELECT id, sku, name, quantity FROM products ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
	$productId = (int)$_POST['product_id'];
	$qty = (int)$_POST['quantity'];
	$note = trim((string)post('note',''));
	if ($productId && $qty > 0) {
		$stmt = $db->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
		$stmt->execute([$qty, $productId]);
		$db->prepare('INSERT INTO stock_movements (product_id, change_qty, reason, note, user_id) VALUES (?,?,?,?,?)')
			->execute([$productId, $qty, 'stock_in', $note, $_SESSION['user']['id'] ?? null]);
		$success = 'Stock updated';
	}
}
?>
<div class="card" style="max-width:700px;">
	<h2>Stock In</h2>
	<?php if (!empty($success)): ?><div style="color:#0a7c2f;"><?php echo h($success); ?></div><?php endif; ?>
	<form method="post">
		<input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
		<div class="row">
			<div class="col">
				<label>Product</label>
				<select class="input" name="product_id" required>
					<option value="">Select</option>
					<?php foreach ($products as $p): ?>
						<option value="<?php echo (int)$p['id']; ?>"><?php echo h($p['sku'].' - '.$p['name'].' (In stock: '.$p['quantity'].')'); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col">
				<label>Quantity to Add</label>
				<input class="input" type="number" name="quantity" value="1" required>
			</div>
		</div>
		<div>
			<label>Note</label>
			<input class="input" name="note" placeholder="Supplier, PO ref, etc">
		</div>
		<div style="margin-top:10px;">
			<button class="btn" type="submit">Add Stock</button>
		</div>
	</form>
</div>