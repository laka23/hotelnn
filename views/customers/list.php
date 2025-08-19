<?php
require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
	csrf_check();
	$id = (int)$_POST['delete_id'];
	$stmt = $db->prepare('DELETE FROM customers WHERE id = ?');
	$stmt->execute([$id]);
}

$customers = $db->query('SELECT * FROM customers ORDER BY id DESC')->fetchAll();
?>
<div class="card">
	<div style="display:flex;justify-content:space-between;align-items:center;">
		<h2>Customers</h2>
		<a class="btn" href="?r=customer_form">Add Customer</a>
	</div>
	<table class="table">
		<thead>
			<tr>
				<th>Name</th><th>Phone</th><th>Email</th><th>Address</th><th></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($customers as $c): ?>
			<tr>
				<td><?php echo h($c['name']); ?></td>
				<td><?php echo h($c['phone'] ?? ''); ?></td>
				<td><?php echo h($c['email'] ?? ''); ?></td>
				<td><?php echo h($c['address'] ?? ''); ?></td>
				<td>
					<a class="btn secondary" href="?r=customer_form&id=<?php echo (int)$c['id']; ?>">Edit</a>
					<form method="post" style="display:inline" onsubmit="return confirm('Delete this customer?');">
						<input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
						<input type="hidden" name="delete_id" value="<?php echo (int)$c['id']; ?>">
						<button class="btn danger" type="submit">Delete</button>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>