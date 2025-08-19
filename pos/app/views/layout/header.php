<?php $user = $_SESSION['user'] ?? null; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="app-header">
        <div class="brand"><?php echo APP_NAME; ?></div>
        <?php if ($user): ?>
        <nav class="top-nav">
            <a href="?page=dashboard">Dashboard</a>
            <a href="?page=products">Products</a>
            <a href="?page=sales&action=create">POS</a>
            <a href="?page=repairs">Repairs</a>
            <a href="?page=stock">Stock</a>
        </nav>
        <div class="user-info">
            <span><?php echo htmlspecialchars($user['username']); ?></span>
            <a class="btn" href="?page=auth&action=logout">Logout</a>
        </div>
        <?php endif; ?>
    </header>
    <main class="container">

