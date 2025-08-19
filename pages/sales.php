<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Util.php';
$pdo = Database::connection();
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
  $stmt = $pdo->prepare('SELECT s.*, c.name AS customer_name FROM sales s LEFT JOIN customers c ON c.id = s.customer_id WHERE s.invoice_number LIKE ? ORDER BY s.id DESC');
  $stmt->execute(['%'.$q.'%']);
} else {
  $stmt = $pdo->query('SELECT s.*, c.name AS customer_name FROM sales s LEFT JOIN customers c ON c.id = s.customer_id ORDER BY s.id DESC LIMIT 200');
}
$sales = $stmt->fetchAll();
?>
<div class="panel">
  <h2>Sales</h2>
  <form class="searchbar" method="get">
    <input type="hidden" name="page" value="sales">
    <input name="q" placeholder="Invoice number" value="<?php echo Util::e($q); ?>">
    <button class="secondary" type="submit">Search</button>
  </form>
  <table>
    <thead><tr><th>Invoice</th><th>Customer</th><th class="right">Total</th><th>Date</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($sales as $s): ?>
      <tr>
        <td><?php echo Util::e($s['invoice_number']); ?></td>
        <td><?php echo Util::e($s['customer_name'] ?? 'Walk-in'); ?></td>
        <td class="right"><?php echo number_format($s['total'], 2); ?></td>
        <td><?php echo Util::e($s['created_at']); ?></td>
        <td><a href="print/invoice.php?id=<?php echo $s['id']; ?>" target="_blank">Print</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

