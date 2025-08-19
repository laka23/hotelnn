<?php
require_auth();
csrf_check();

$id = (int)($_GET['id'] ?? 0);
$product = null;
if ($id) {
	$stmt = $db->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
	$stmt->execute([$id]);
	$product = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$sku = trim((string)post('sku',''));
	$name = trim((string)post('name',''));
	$brand = trim((string)post('brand',''));
	$model = trim((string)post('model',''));
	$imei_required = isset($_POST['imei_required']) ? 1 : 0;
	$cost_price = (float)post('cost_price',0);
	$sell_price = (float)post('sell_price',0);
	$quantity = (int)post('quantity',0);
	$low_stock_threshold = (int)post('low_stock_threshold',0);

	if ($id) {
		$stmt = $db->prepare('UPDATE products SET sku=?, name=?, brand=?, model=?, imei_required=?, cost_price=?, sell_price=?, quantity=?, low_stock_threshold=? WHERE id=?');
		$stmt->execute([$sku,$name,$brand,$model,$imei_required,$cost_price,$sell_price,$quantity,$low_stock_threshold,$id]);
	} else {
		$stmt = $db->prepare('INSERT INTO products (sku,name,brand,model,imei_required,cost_price,sell_price,quantity,low_stock_threshold) VALUES (?,?,?,?,?,?,?,?,?)');
		$stmt->execute([$sku,$name,$brand,$model,$imei_required,$cost_price,$sell_price,$quantity,$low_stock_threshold]);
	}
	redirect('products');
}
?>
<div class="card" style="max-width:700px;">
	<h2><?php echo $id ? 'Edit' : 'Add'; ?> Product</h2>
	<form method="post">
		<input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
		<div class="row">
			<div class="col">
				<label>SKU</label>
				<input class="input" name="sku" required value="<?php echo h($product['sku'] ?? ''); ?>">
			</div>
			<div class="col">
				<label>Name</label>
				<input class="input" name="name" required value="<?php echo h($product['name'] ?? ''); ?>">
			</div>
		</div>
		<div class="row">
			<div class="col">
				<label>Brand</label>
				<input class="input" name="brand" value="<?php echo h($product['brand'] ?? ''); ?>">
			</div>
			<div class="col">
				<label>Model</label>
				<input class="input" name="model" value="<?php echo h($product['model'] ?? ''); ?>">
			</div>
		</div>
		<div class="row">
			<div class="col">
				<label>Cost Price</label>
				<input class="input" type="number" step="0.01" name="cost_price" value="<?php echo h((string)($product['cost_price'] ?? '0')); ?>">
			</div>
			<div class="col">
				<label>Sell Price</label>
				<input class="input" type="number" step="0.01" name="sell_price" value="<?php echo h((string)($product['sell_price'] ?? '0')); ?>">
			</div>
		</div>
		<div class="row">
			<div class="col">
				<label>Quantity</label>
				<input class="input" type="number" name="quantity" value="<?php echo h((string)($product['quantity'] ?? '0')); ?>">
			</div>
			<div class="col">
				<label>Low Stock Threshold</label>
				<input class="input" type="number" name="low_stock_threshold" value="<?php echo h((string)($product['low_stock_threshold'] ?? '0')); ?>">
			</div>
		</div>
		<div style="margin:8px 0;">
			<label><input type="checkbox" name="imei_required" <?php echo !empty($product['imei_required']) ? 'checked' : ''; ?>> IMEI Required</label>
		</div>
		<button class="btn" type="submit">Save</button>
		<a class="btn secondary" href="?r=products">Cancel</a>
	</form>
</div>