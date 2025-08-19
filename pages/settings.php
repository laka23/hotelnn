<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Util.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!Util::verifyCsrf($_POST['csrf'] ?? '')) { die('Invalid CSRF'); }
  Database::upsertSetting('shop_name', trim($_POST['shop_name'] ?? ''));
  Database::upsertSetting('shop_address', trim($_POST['shop_address'] ?? ''));
  Database::upsertSetting('shop_phone', trim($_POST['shop_phone'] ?? ''));
  Database::upsertSetting('tax_rate', (string)floatval($_POST['tax_rate'] ?? '0'));
  Database::upsertSetting('currency', trim($_POST['currency'] ?? 'USD'));
}

$shop_name = Database::fetchSetting('shop_name', APP_NAME);
$shop_address = Database::fetchSetting('shop_address', '');
$shop_phone = Database::fetchSetting('shop_phone', '');
$tax_rate = Database::fetchSetting('tax_rate', '0');
$currency = Database::fetchSetting('currency', 'USD');
?>
<div class="panel">
  <h2>Settings</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo Util::csrfToken(); ?>">
    <div class="grid-2">
      <div>
        <label>Shop Name</label>
        <input name="shop_name" value="<?php echo Util::e($shop_name); ?>">
      </div>
      <div>
        <label>Phone</label>
        <input name="shop_phone" value="<?php echo Util::e($shop_phone); ?>">
      </div>
      <div>
        <label>Address</label>
        <input name="shop_address" value="<?php echo Util::e($shop_address); ?>">
      </div>
      <div>
        <label>Tax Rate (e.g., 0.07 for 7%)</label>
        <input type="number" step="0.0001" name="tax_rate" value="<?php echo Util::e($tax_rate); ?>">
      </div>
      <div>
        <label>Currency</label>
        <input name="currency" value="<?php echo Util::e($currency); ?>">
      </div>
    </div>
    <div style="margin-top:12px;">
      <button type="submit">Save</button>
    </div>
  </form>
</div>

