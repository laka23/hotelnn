<?php
require_auth();
csrf_check();

// Add to cart
if (isset($_POST['add_product_id'])) {
	$productId = (int)$_POST['add_product_id'];
	$stmt = $db->prepare('SELECT id, name, sell_price FROM products WHERE id = ?');
	$stmt->execute([$productId]);
	if ($row = $stmt->fetch()) {
		cart_add((int)$row['id'], $row['name'], (float)$row['sell_price'], 1);
	}
}

// Update quantities
if (isset($_POST['update_qty'])) {
	foreach ((array)($_POST['qty'] ?? []) as $pid => $qty) {
		cart_update((int)$pid, (int)$qty);
	}
}

// Clear cart
if (isset($_POST['clear_cart'])) {
	cart_clear();
}

// Checkout
if (isset($_POST['checkout'])) {
	$customerId = (int)($_POST['customer_id'] ?? 0);
	$discount = (float)($_POST['discount'] ?? 0);
	$paymentMethod = trim((string)($_POST['payment_method'] ?? 'cash'));
	$paidAmount = (float)($_POST['paid_amount'] ?? 0);

	$cart = cart_get();
	$totals = cart_totals((float)$settings['tax_rate'], $discount);

	if (!empty($cart)) {
		try {
			$db->beginTransaction();
			$seq = db_next_sequence($db, 'invoice_no');
			$invoiceNo = 'INV-' . str_pad((string)$seq, 6, '0', STR_PAD_LEFT);
			$stmt = $db->prepare('INSERT INTO sales (invoice_no, customer_id, subtotal, tax, discount, total, paid_amount, payment_method, notes, user_id) VALUES (?,?,?,?,?,?,?,?,?,?)');
			$stmt->execute([
				$invoiceNo,
				$customerId ?: null,
				$totals['subtotal'],
				$totals['tax'],
				$totals['discount'],
				$totals['total'],
				$paidAmount,
				$paymentMethod,
				null,
				$_SESSION['user']['id'] ?? null,
			]);
			$saleId = (int)$db->lastInsertId();

			$insItem = $db->prepare('INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?,?,?,?,?)');
			$updStock = $db->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?');
			$insMove = $db->prepare('INSERT INTO stock_movements (product_id, change_qty, reason, note, user_id) VALUES (?,?,?,?,?)');
			foreach ($cart as $item) {
				$lineTotal = $item['price'] * $item['quantity'];
				$insItem->execute([$saleId, $item['product_id'], $item['quantity'], $item['price'], $lineTotal]);
				$updStock->execute([$item['quantity'], $item['product_id']]);
				$insMove->execute([$item['product_id'], -$item['quantity'], 'sale', 'Invoice ' . $invoiceNo, $_SESSION['user']['id'] ?? null]);
			}

			$db->commit();
			cart_clear();
			redirect('sale_view', ['id' => $saleId]);
		} catch (Throwable $e) {
			$db->rollBack();
			$error = 'Checkout failed: ' . $e->getMessage();
		}
	}
}

// Fetch products for quick add
$search = trim((string)($_GET['q'] ?? ''));
if ($search !== '') {
	$stmt = $db->prepare("SELECT id, sku, name, sell_price, quantity FROM products WHERE sku LIKE ? OR name LIKE ? ORDER BY name LIMIT 20");
	$stmt->execute(['%' . $search . '%', '%' . $search . '%']);
	$products = $stmt->fetchAll();
} else {
	$products = $db->query('SELECT id, sku, name, sell_price, quantity FROM products ORDER BY id DESC LIMIT 10')->fetchAll();
}

$customers = $db->query('SELECT id, name FROM customers ORDER BY name')->fetchAll();
$cart = cart_get();
$totals = cart_totals((float)$settings['tax_rate'], (float)($_POST['discount'] ?? 0));
?>
<div class="row">
	<div class="col">
		<div class="card">
			<h2>POS</h2>
			<form method="get" class="no-print">
				<input type="hidden" name="r" value="pos">
				<input class="input" name="q" placeholder="Search by SKU or Name" value="<?php echo h($search); ?>">
			</form>
			<table class="table no-print">
				<thead><tr><th>SKU</th><th>Name</th><th>Price</th><th>Stock</th><th></th></tr></thead>
				<tbody>
				<?php foreach ($products as $p): ?>
					<tr>
						<td><?php echo h($p['sku']); ?></td>
						<td><?php echo h($p['name']); ?></td>
						<td><?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$p['sell_price'], 2); ?></td>
						<td><?php echo (int)$p['quantity']; ?></td>
						<td>
							<form method="post">
								<input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
								<input type="hidden" name="add_product_id" value="<?php echo (int)$p['id']; ?>">
								<button class="btn" type="submit">Add</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<div class="col">
		<div class="card">
			<h3>Cart</h3>
			<form method="post">
				<input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
				<table class="table">
					<thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
					<tbody>
					<?php foreach ($cart as $item): ?>
						<tr>
							<td><?php echo h($item['name']); ?></td>
							<td><input class="input" style="max-width:70px" type="number" name="qty[<?php echo (int)$item['product_id']; ?>]" value="<?php echo (int)$item['quantity']; ?>"></td>
							<td><?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)$item['price'], 2); ?></td>
							<td><?php echo h($settings['currency_symbol']); ?><?php echo number_format((float)($item['price'] * $item['quantity']), 2); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<div class="row">
					<div class="col">
						<label>Customer</label>
						<select class="input" name="customer_id">
							<option value="">Walk-in</option>
							<?php foreach ($customers as $c): ?>
								<option value="<?php echo (int)$c['id']; ?>"><?php echo h($c['name']); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col">
						<label>Discount</label>
						<input class="input" type="number" step="0.01" name="discount" value="<?php echo h((string)($_POST['discount'] ?? '0')); ?>">
					</div>
				</div>
				<div class="row">
					<div class="col">
						<label>Payment</label>
						<select class="input" name="payment_method">
							<option value="cash">Cash</option>
							<option value="card">Card</option>
						</select>
					</div>
					<div class="col">
						<label>Paid Amount</label>
						<input class="input" type="number" step="0.01" name="paid_amount" value="<?php echo h((string)($totals['total'] ?? '0')); ?>">
					</div>
				</div>
				<div class="row">
					<div class="col">
						<div>Subtotal: <?php echo h($settings['currency_symbol']); ?><?php echo number_format($totals['subtotal'], 2); ?></div>
						<div>Tax: <?php echo h($settings['currency_symbol']); ?><?php echo number_format($totals['tax'], 2); ?></div>
						<div>Discount: <?php echo h($settings['currency_symbol']); ?><?php echo number_format($totals['discount'], 2); ?></div>
						<div><strong>Total: <?php echo h($settings['currency_symbol']); ?><?php echo number_format($totals['total'], 2); ?></strong></div>
					</div>
				</div>
				<div style="margin-top:10px;">
					<button class="btn secondary" name="update_qty" value="1">Update Quantities</button>
					<button class="btn danger" name="clear_cart" value="1">Clear Cart</button>
					<button class="btn" name="checkout" value="1">Checkout</button>
				</div>
			</form>
		</div>
	</div>
</div>