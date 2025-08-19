<?php if (!empty($_SESSION['user_id'])): ?>
<header class="topbar">
  <div class="brand"><a href="index.php">📱 <?php echo Util::e(Database::fetchSetting('shop_name', APP_NAME)); ?></a></div>
  <nav class="menu">
    <a href="index.php">Dashboard</a>
    <a href="index.php?page=pos">POS</a>
    <a href="index.php?page=products">Products</a>
    <a href="index.php?page=customers">Customers</a>
    <a href="index.php?page=repairs">Repairs</a>
    <a href="index.php?page=sales">Sales</a>
    <a href="index.php?page=settings">Settings</a>
  </nav>
  <div class="user">
    <span>👤 <?php echo Util::e($_SESSION['username'] ?? ''); ?></span>
    <a class="btn" href="index.php?page=logout">Logout</a>
  </div>
</header>
<?php endif; ?>
<main class="container">

