<?php
require_auth();
csrf_check();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$shop_name = trim((string)post('shop_name',''));
	$shop_address = trim((string)post('shop_address',''));
	$shop_phone = trim((string)post('shop_phone',''));
	$currency_symbol = trim((string)post('currency_symbol','$')) ?: '$';
	$tax_rate = (float)post('tax_rate',0);
	$db->prepare('INSERT INTO settings (shop_name, shop_address, shop_phone, currency_symbol, tax_rate) VALUES (?,?,?,?,?)')
		->execute([$shop_name,$shop_address,$shop_phone,$currency_symbol,$tax_rate]);
	$settings = load_settings($db, $settings); // reload
	$success = 'Settings saved';
}
?>
<div class="card" style="max-width:700px;">
	<h2>Settings</h2>
	<?php if (!empty($success)): ?><div style="color:#0a7c2f;"><?php echo h($success); ?></div><?php endif; ?>
	<form method="post">
		<input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
		<div class="row">
			<div class="col">
				<label>Shop Name</label>
				<input class="input" name="shop_name" value="<?php echo h($settings['shop_name'] ?? ''); ?>">
			</div>
			<div class="col">
				<label>Phone</label>
				<input class="input" name="shop_phone" value="<?php echo h($settings['shop_phone'] ?? ''); ?>">
			</div>
		</div>
		<div class="row">
			<div class="col">
				<label>Address</label>
				<input class="input" name="shop_address" value="<?php echo h($settings['shop_address'] ?? ''); ?>">
			</div>
			<div class="col">
				<label>Currency Symbol</label>
				<input class="input" name="currency_symbol" value="<?php echo h($settings['currency_symbol'] ?? '$'); ?>">
			</div>
		</div>
		<div>
			<label>Tax Rate (0.00 to 1.00)</label>
			<input class="input" type="number" step="0.001" name="tax_rate" value="<?php echo h((string)($settings['tax_rate'] ?? '0')); ?>">
		</div>
		<div style="margin-top:10px;">
			<button class="btn" type="submit">Save</button>
		</div>
	</form>
</div>