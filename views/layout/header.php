<?php
/** @var array $settings */
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo h($settings['shop_name']); ?></title>
	<link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
	<header class="header">
		<div class="container">
			<div style="display:flex;align-items:center;justify-content:space-between">
				<div><strong><?php echo h($settings['shop_name']); ?></strong></div>
				<nav class="nav">
					<a href="?r=dashboard">Dashboard</a>
					<a href="?r=pos">POS</a>
					<a href="?r=products">Products</a>
					<a href="?r=customers">Customers</a>
					<a href="?r=sales">Sales</a>
					<a href="?r=repairs">Repairs</a>
					<a href="?r=stock_in">Stock In</a>
					<a href="?r=settings">Settings</a>
					<?php if (!empty($_SESSION['user'])): ?>
						<a href="?r=logout">Logout (<?php echo h($_SESSION['user']['username']); ?>)</a>
					<?php endif; ?>
				</nav>
			</div>
		</div>
	</header>
	<main class="container">