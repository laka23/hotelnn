<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Util.php';
$pdo = Database::connection();

$status = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');

$sql = 'SELECT r.*, c.name AS customer_name FROM repairs r LEFT JOIN customers c ON c.id=r.customer_id WHERE 1=1';
$params = [];
if ($status !== '') { $sql .= ' AND r.status = ?'; $params[] = $status; }
if ($q !== '') { $sql .= ' AND (r.ticket_number LIKE ? OR c.name LIKE ? OR r.device_model LIKE ?)'; $params[] = '%'.$q.'%'; $params[] = '%'.$q.'%'; $params[] = '%'.$q.'%'; }
$sql .= ' ORDER BY r.id DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$repairs = $stmt->fetchAll();
?>
<div class="panel">
  <div class="row" style="justify-content: space-between; align-items: center;">
    <h2>Repairs</h2>
    <a class="btn-primary" href="index.php?page=repair_form">New Repair</a>
  </div>
  <form class="row" method="get">
    <input type="hidden" name="page" value="repairs">
    <input name="q" placeholder="Search ticket, customer, model" value="<?php echo Util::e($q); ?>" style="flex:2;">
    <select name="status" style="flex:1;">
      <option value="">All statuses</option>
      <?php foreach (['received','in_progress','awaiting_parts','completed','delivered'] as $s): ?>
        <option value="<?php echo $s; ?>" <?php echo $status===$s?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option>
      <?php endforeach; ?>
    </select>
    <button class="secondary" type="submit">Filter</button>
  </form>
  <table>
    <thead><tr><th>Ticket</th><th>Customer</th><th>Device</th><th>Status</th><th>Date</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($repairs as $r): ?>
      <tr>
        <td><?php echo Util::e($r['ticket_number']); ?></td>
        <td><?php echo Util::e($r['customer_name'] ?? ''); ?></td>
        <td><?php echo Util::e($r['device_make'] . ' ' . $r['device_model']); ?></td>
        <td><span class="badge info"><?php echo Util::e(ucfirst(str_replace('_',' ',$r['status']))); ?></span></td>
        <td><?php echo Util::e($r['created_at']); ?></td>
        <td>
          <a href="index.php?page=repair_form&id=<?php echo $r['id']; ?>">Edit</a>
          <a href="print/repair.php?id=<?php echo $r['id']; ?>" target="_blank">Print</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

