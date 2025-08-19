<?php
csrf_check();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = trim((string)post('username',''));
	$password = (string)post('password','');
	if (login($db, $username, $password)) {
		redirect('dashboard');
	} else {
		$error = 'Invalid username or password';
	}
}
?>
<div class="card" style="max-width:420px;margin:40px auto;">
	<h2>Login</h2>
	<?php if (!empty($error)): ?>
		<div style="color:#dc3545;margin-bottom:10px;"><?php echo h($error); ?></div>
	<?php endif; ?>
	<form method="post">
		<input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
		<div style="margin-bottom:10px;">
			<label>Username</label>
			<input class="input" type="text" name="username" required>
		</div>
		<div style="margin-bottom:10px;">
			<label>Password</label>
			<input class="input" type="password" name="password" required>
		</div>
		<button class="btn" type="submit">Login</button>
	</form>
</div>