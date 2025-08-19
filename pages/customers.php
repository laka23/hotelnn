<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Util.php';
$pdo = Database::connection();

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
    Util::redirect('index.php?page=customers');
}

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE name LIKE ? OR phone LIKE ? ORDER BY id DESC");
    $stmt->execute(['%' . $q . '%', '%' . $q . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM customers ORDER BY id DESC');
}
$customers = $stmt->fetchAll();
?>
<div class="panel">
  <div class="row" style="justify-content: space-between; align-items: center;">
    <h2>Customers</h2>
    <a class="btn-primary" href="index.php?page=customer_form">Add Customer</a>
  </div>
  <form class="searchbar" method="get">
    <input type="hidden" name="page" value="customers">
    <input placeholder="Search by name or phone" name="q" value="<?php echo Util::e($q); ?>">
    <button type="submit" class="secondary">Search</button>
  </form>
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Address</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($customers as $c): ?>
      <tr>
        <td><?php echo Util::e($c['name']); ?></td>
        <td><?php echo Util::e($c['phone']); ?></td>
        <td><?php echo Util::e($c['email']); ?></td>
        <td><?php echo Util::e($c['address']); ?></td>
        <td class="actions">
          <a href="index.php?page=customer_form&id=<?php echo $c['id']; ?>">Edit</a>
          <a href="index.php?page=customers&action=delete&id=<?php echo $c['id']; ?>" onclick="return confirmDelete('Delete this customer?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

