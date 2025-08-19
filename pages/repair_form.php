<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Util.php';
$pdo = Database::connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$repair = null;
if ($id) {
  $stmt = $pdo->prepare('SELECT * FROM repairs WHERE id=?');
  $stmt->execute([$id]);
  $repair = $stmt->fetch();
}

// Create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!Util::verifyCsrf($_POST['csrf'] ?? '')) { die('Invalid CSRF'); }
  $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
  $device_make = trim($_POST['device_make'] ?? '');
  $device_model = trim($_POST['device_model'] ?? '');
  $imei = trim($_POST['imei'] ?? '');
  $passcode = trim($_POST['passcode'] ?? '');
  $accessories_received = trim($_POST['accessories_received'] ?? '');
  $fault_description = trim($_POST['fault_description'] ?? '');
  $diagnosis = trim($_POST['diagnosis'] ?? '');
  $status = $_POST['status'] ?? 'received';
  $parts_cost = (float)($_POST['parts_cost'] ?? 0);
  $labor_cost = (float)($_POST['labor_cost'] ?? 0);
  $total_estimate = (float)($_POST['total_estimate'] ?? ($parts_cost + $labor_cost));
  $prepayment = (float)($_POST['prepayment'] ?? 0);

  if ($id) {
    $stmt = $pdo->prepare('UPDATE repairs SET customer_id=?, device_make=?, device_model=?, imei=?, passcode=?, accessories_received=?, fault_description=?, diagnosis=?, status=?, parts_cost=?, labor_cost=?, total_estimate=?, prepayment=?, updated_at=NOW() WHERE id=?');
    $stmt->execute([$customer_id,$device_make,$device_model,$imei,$passcode,$accessories_received,$fault_description,$diagnosis,$status,$parts_cost,$labor_cost,$total_estimate,$prepayment,$id]);
  } else {
    $ticket = 'R' . date('YmdHis');
    $stmt = $pdo->prepare('INSERT INTO repairs (ticket_number, customer_id, device_make, device_model, imei, passcode, accessories_received, fault_description, diagnosis, status, parts_cost, labor_cost, total_estimate, prepayment) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$ticket,$customer_id,$device_make,$device_model,$imei,$passcode,$accessories_received,$fault_description,$diagnosis,$status,$parts_cost,$labor_cost,$total_estimate,$prepayment]);
    $id = (int)$pdo->lastInsertId();
  }
  Util::redirect('index.php?page=repairs');
}

// Customers
$custStmt = $pdo->query('SELECT id, name FROM customers ORDER BY name ASC');
$customers = $custStmt->fetchAll();
?>
<div class="panel">
  <h2><?php echo $id ? 'Edit' : 'New'; ?> Repair</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo Util::csrfToken(); ?>">
    <div class="grid-3">
      <div>
        <label>Customer</label>
        <select name="customer_id">
          <option value="">Select customer</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo (!empty($repair['customer_id']) && (int)$repair['customer_id']===(int)$c['id'])?'selected':''; ?>><?php echo Util::e($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Device Make</label>
        <input name="device_make" required value="<?php echo Util::e($repair['device_make'] ?? ''); ?>">
      </div>
      <div>
        <label>Device Model</label>
        <input name="device_model" required value="<?php echo Util::e($repair['device_model'] ?? ''); ?>">
      </div>
      <div>
        <label>IMEI</label>
        <input name="imei" value="<?php echo Util::e($repair['imei'] ?? ''); ?>">
      </div>
      <div>
        <label>Passcode</label>
        <input name="passcode" value="<?php echo Util::e($repair['passcode'] ?? ''); ?>">
      </div>
      <div>
        <label>Accessories Received</label>
        <input name="accessories_received" value="<?php echo Util::e($repair['accessories_received'] ?? ''); ?>">
      </div>
    </div>
    <div class="grid-2">
      <div>
        <label>Fault Description</label>
        <textarea name="fault_description" rows="3"><?php echo Util::e($repair['fault_description'] ?? ''); ?></textarea>
      </div>
      <div>
        <label>Diagnosis</label>
        <textarea name="diagnosis" rows="3"><?php echo Util::e($repair['diagnosis'] ?? ''); ?></textarea>
      </div>
    </div>
    <div class="grid-3">
      <div>
        <label>Status</label>
        <select name="status">
          <?php foreach (['received','in_progress','awaiting_parts','completed','delivered'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php echo (($repair['status'] ?? '')===$s)?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Parts Cost</label>
        <input type="number" step="0.01" name="parts_cost" value="<?php echo Util::e($repair['parts_cost'] ?? '0'); ?>">
      </div>
      <div>
        <label>Labor Cost</label>
        <input type="number" step="0.01" name="labor_cost" value="<?php echo Util::e($repair['labor_cost'] ?? '0'); ?>">
      </div>
      <div>
        <label>Total Estimate</label>
        <input type="number" step="0.01" name="total_estimate" value="<?php echo Util::e($repair['total_estimate'] ?? '0'); ?>">
      </div>
      <div>
        <label>Prepayment</label>
        <input type="number" step="0.01" name="prepayment" value="<?php echo Util::e($repair['prepayment'] ?? '0'); ?>">
      </div>
    </div>
    <div style="margin-top:12px;">
      <button type="submit">Save</button>
      <a class="btn-primary" href="index.php?page=repairs">Back</a>
    </div>
  </form>
</div>

