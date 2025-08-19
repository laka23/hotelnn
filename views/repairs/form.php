<?php
require_auth();
csrf_check();

$id = (int)($_GET['id'] ?? 0);
$repair = null;
if ($id) {
	$stmt = $db->prepare('SELECT * FROM repairs WHERE id = ? LIMIT 1');
	$stmt->execute([$id]);
	$repair = $stmt->fetch();
}

$customers = $db->query('SELECT id, name FROM customers ORDER BY name')->fetchAll();
$products = $db->query('SELECT id, name, sell_price, quantity FROM products ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$customer_id = (int)($_POST['customer_id'] ?? 0);
	$device_brand = trim((string)post('device_brand',''));
	$device_model = trim((string)post('device_model',''));
	$imei = trim((string)post('imei',''));
	$problem_description = trim((string)post('problem_description',''));
	$estimated_cost = (float)post('estimated_cost',0);
	$deposit = (float)post('deposit',0);
	$status = (string)post('status','received');
	$labor_cost = (float)post('labor_cost',0);

	try {
		$db->beginTransaction();

		if ($id) {
			$stmt = $db->prepare('UPDATE repairs SET customer_id=?, device_brand=?, device_model=?, imei=?, problem_description=?, estimated_cost=?, deposit=?, status=?, labor_cost=? WHERE id=?');
			$stmt->execute([$customer_id ?: null,$device_brand,$device_model,$imei,$problem_description,$estimated_cost,$deposit,$status,$labor_cost,$id]);
			$repairId = $id;
			$db->prepare('DELETE FROM repair_parts WHERE repair_id = ?')->execute([$repairId]);
		} else {
			$seq = db_next_sequence($db, 'ticket_no');
			$ticketNo = 'R-' . str_pad((string)$seq, 6, '0', STR_PAD_LEFT);
			$stmt = $db->prepare('INSERT INTO repairs (ticket_no, customer_id, device_brand, device_model, imei, problem_description, estimated_cost, deposit, status, labor_cost, user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
			$stmt->execute([$ticketNo,$customer_id ?: null,$device_brand,$device_model,$imei,$problem_description,$estimated_cost,$deposit,$status,$labor_cost,$_SESSION['user']['id'] ?? null]);
			$repairId = (int)$db->lastInsertId();
		}

		$totalParts = 0.0;
		$insPart = $db->prepare('INSERT INTO repair_parts (repair_id, product_id, quantity, unit_price, total_price) VALUES (?,?,?,?,?)');
		$updStock = $db->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?');
		$insMove = $db->prepare('INSERT INTO stock_movements (product_id, change_qty, reason, note, user_id) VALUES (?,?,?,?,?)');
		foreach ((array)($_POST['part'] ?? []) as $row) {
			$productId = (int)($row['product_id'] ?? 0);
			$qty = (int)($row['quantity'] ?? 0);
			$unitPrice = (float)($row['unit_price'] ?? 0);
			if ($productId && $qty > 0) {
				$line = $qty * $unitPrice;
				$totalParts += $line;
				$insPart->execute([$repairId, $productId, $qty, $unitPrice, $line]);
				$updStock->execute([$qty, $productId]);
				$insMove->execute([$productId, -$qty, 'repair', 'Repair parts for ticket #' . ($repair['ticket_no'] ?? ''), $_SESSION['user']['id'] ?? null]);
			}
		}
		$totalCost = $totalParts + $labor_cost;
		$db->prepare('UPDATE repairs SET parts_cost=?, total_cost=? WHERE id=?')->execute([$totalParts, $totalCost, $repairId]);

		$db->commit();
		redirect('repair_view', ['id' => $repairId]);
	} catch (Throwable $e) {
		$db->rollBack();
		$error = 'Failed to save repair: ' . $e->getMessage();
	}
}
?>
<div class="card">
	<h2><?php echo $id ? 'Edit' : 'New'; ?> Repair Ticket</h2>
	<?php if (!empty($error)): ?><div style="color:#dc3545;"><?php echo h($error); ?></div><?php endif; ?>
	<form method="post">
		<input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
		<div class="row">
			<div class="col">
				<label>Customer</label>
				<select class="input" name="customer_id">
					<option value="">Select customer</option>
					<?php foreach ($customers as $c): ?>
						<option value="<?php echo (int)$c['id']; ?>" <?php echo (!empty($repair['customer_id']) && (int)$repair['customer_id']===(int)$c['id']) ? 'selected' : ''; ?>><?php echo h($c['name']); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col">
				<label>IMEI</label>
				<input class="input" name="imei" value="<?php echo h($repair['imei'] ?? ''); ?>">
			</div>
		</div>
		<div class="row">
			<div class="col">
				<label>Brand</label>
				<input class="input" name="device_brand" value="<?php echo h($repair['device_brand'] ?? ''); ?>">
			</div>
			<div class="col">
				<label>Model</label>
				<input class="input" name="device_model" value="<?php echo h($repair['device_model'] ?? ''); ?>">
			</div>
		</div>
		<div>
			<label>Problem Description</label>
			<textarea class="input" name="problem_description"><?php echo h($repair['problem_description'] ?? ''); ?></textarea>
		</div>
		<div class="row">
			<div class="col">
				<label>Estimated Cost</label>
				<input class="input" type="number" step="0.01" name="estimated_cost" value="<?php echo h((string)($repair['estimated_cost'] ?? '0')); ?>">
			</div>
			<div class="col">
				<label>Deposit</label>
				<input class="input" type="number" step="0.01" name="deposit" value="<?php echo h((string)($repair['deposit'] ?? '0')); ?>">
			</div>
		</div>
		<div class="row">
			<div class="col">
				<label>Status</label>
				<select class="input" name="status">
					<?php foreach (['received','in_progress','ready','picked_up','cancelled'] as $st): ?>
						<option value="<?php echo h($st); ?>" <?php echo (!empty($repair['status']) && $repair['status']===$st) ? 'selected' : ''; ?>><?php echo h(ucwords(str_replace('_',' ',$st))); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col">
				<label>Labor Cost</label>
				<input class="input" type="number" step="0.01" name="labor_cost" value="<?php echo h((string)($repair['labor_cost'] ?? '0')); ?>">
			</div>
		</div>

		<h3>Parts Used</h3>
		<div id="parts">
			<div class="row">
				<div class="col"><strong>Product</strong></div>
				<div class="col"><strong>Qty</strong></div>
				<div class="col"><strong>Unit Price</strong></div>
			</div>
			<div class="row">
				<div class="col">
					<select class="input" name="part[0][product_id]">
						<option value="">Select product</option>
						<?php foreach ($products as $p): ?>
							<option value="<?php echo (int)$p['id']; ?>"><?php echo h($p['name']); ?> (Stock: <?php echo (int)$p['quantity']; ?>)</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col"><input class="input" type="number" name="part[0][quantity]" value="1"></div>
				<div class="col"><input class="input" type="number" step="0.01" name="part[0][unit_price]" value="0"></div>
			</div>
		</div>
		<div style="margin-top:10px;" class="no-print">
			<small>You can add additional parts after saving by editing the repair ticket again.</small>
		</div>

		<div style="margin-top:12px;">
			<button class="btn" type="submit">Save</button>
			<a class="btn secondary" href="?r=repairs">Cancel</a>
		</div>
	</form>
</div>