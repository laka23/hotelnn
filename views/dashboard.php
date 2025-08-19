<?php
require_auth();

$counts = [
	'products' => (int)$db->query('SELECT COUNT(*) AS c FROM products')->fetch()['c'] ?? 0,
	'customers' => (int)$db->query('SELECT COUNT(*) AS c FROM customers')->fetch()['c'] ?? 0,
	'sales' => (int)$db->query('SELECT COUNT(*) AS c FROM sales')->fetch()['c'] ?? 0,
	'repairs' => (int)$db->query('SELECT COUNT(*) AS c FROM repairs')->fetch()['c'] ?? 0,
];
?>
<div class="row">
	<div class="col">
		<div class="card">
			<h3>Quick Actions</h3>
			<p>
				<a class="btn" href="?r=pos">Open POS</a>
				<a class="btn secondary" href="?r=products">Manage Products</a>
				<a class="btn secondary" href="?r=repairs">Repairs</a>
			</p>
		</div>
	</div>
</div>
<div class="row">
	<div class="col"><div class="card"><strong>Products</strong><div><?php echo (int)$counts['products']; ?></div></div></div>
	<div class="col"><div class="card"><strong>Customers</strong><div><?php echo (int)$counts['customers']; ?></div></div></div>
	<div class="col"><div class="card"><strong>Sales</strong><div><?php echo (int)$counts['sales']; ?></div></div></div>
	<div class="col"><div class="card"><strong>Repairs</strong><div><?php echo (int)$counts['repairs']; ?></div></div></div>
</div>