<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Util.php';
$pdo = Database::connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$customer = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    $customer = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Util::verifyCsrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($id) {
            $stmt = $pdo->prepare('UPDATE customers SET name=?, phone=?, email=?, address=? WHERE id=?');
            $stmt->execute([$name, $phone, $email, $address, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO customers (name, phone, email, address) VALUES (?,?,?,?)');
            $stmt->execute([$name, $phone, $email, $address]);
        }
        Util::redirect('index.php?page=customers');
    }
}
?>
<div class="panel">
  <h2><?php echo $id ? 'Edit' : 'Add'; ?> Customer</h2>
  <?php if (!empty($error)): ?><div class="muted"><?php echo Util::e($error); ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo Util::csrfToken(); ?>">
    <div class="grid-2">
      <div>
        <label>Name</label>
        <input name="name" required value="<?php echo Util::e($customer['name'] ?? ''); ?>">
      </div>
      <div>
        <label>Phone</label>
        <input name="phone" value="<?php echo Util::e($customer['phone'] ?? ''); ?>">
      </div>
      <div>
        <label>Email</label>
        <input type="email" name="email" value="<?php echo Util::e($customer['email'] ?? ''); ?>">
      </div>
      <div>
        <label>Address</label>
        <input name="address" value="<?php echo Util::e($customer['address'] ?? ''); ?>">
      </div>
    </div>
    <div style="margin-top:12px;">
      <button type="submit">Save</button>
      <a class="btn-primary" href="index.php?page=customers">Back</a>
    </div>
  </form>
</div>

