<?php
require_once __DIR__ . '/../includes/db.php';

$pdo = getPDO();
$id = $_GET['id'] ?? null;
if ($id) {
    // TODO: Optional - check for foreign key constraints such as product in sales
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
}
header('Location: product_list.php');
exit;