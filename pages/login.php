<?php
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/Util.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Util::verifyCsrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (Auth::attempt($username, $password)) {
            Util::redirect('index.php');
        } else {
            $error = 'Invalid credentials';
        }
    }
}
?>
<div class="panel" style="max-width:420px;margin:64px auto;">
  <h2>Login</h2>
  <?php if (!empty($error)): ?><div class="muted"><?php echo Util::e($error); ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo Util::csrfToken(); ?>">
    <label>Username</label>
    <input name="username" required>
    <label>Password</label>
    <input type="password" name="password" required>
    <div style="margin-top:10px;">
      <button type="submit">Sign in</button>
    </div>
  </form>
</div>

