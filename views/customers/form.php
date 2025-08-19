<?php
require_auth();
csrf_check();

$id = (int)($_GET['id'] ?? 0);
$customer = null;
if ($id) {
	$stmt = $db->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
	$stmt->execute([$id]);
	$customer = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$name = trim((string)post('name',''));
	$phone = trim((string)post('phone',''));
	$email = trim((string)post('email',''));
	$address = trim((string)post('address',''));
	if ($id) {
		$stmt = $db->prepare('UPDATE customers SET name=?, phone=?, email=?, address=? WHERE id=?');
		$stmt->execute([$name,$phone,$email,$address,$id]);
	} else {
		$stmt = $db->prepare('INSERT INTO customers (name, phone, email, address) VALUES (?,?,?,?)');
		$stmt->execute([$name,$phone,$email,$address]);
	}
	redirect('customers');
}
?>
<div class="card" style="max-width:700px;">
	<h2><?php echo $id ? 'Edit' : 'Add'; ?> Customer</h2>
	<form method="post">
		<input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
		<div class="row">
			<div class="col">
				<label>Name</label>
				<input class="input" name="name" required value="<?php echo h($customer['name'] ?? ''); ?>">
			</div>
			<div class="col">
				<label>Phone</label>
				<input class="input" name="phone" value="<?php echo h($customer['phone'] ?? ''); ?>">
			</div>
		</div>
		<div class="row">
			<div class="col">
				<label>Email</label>
				<input class="input" type="email" name="email" value="<?php echo h($customer['email'] ?? ''); ?>">
			</div>
			<div class="col">
				<label>Address</label>
				<input class="input" name="address" value="<?php echo h($customer['address'] ?? ''); ?>">
			</div>
		</div>
		<button class="btn" type="submit">Save</button>
		<a class="btn secondary" href="?r=customers">Cancel</a>
	</form>
</div>