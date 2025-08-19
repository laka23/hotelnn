<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Util.php';
$shopName = Database::fetchSetting('shop_name', APP_NAME);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo Util::e($shopName); ?> - POS</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" media="print" href="assets/css/print.css">
</head>
<body>

