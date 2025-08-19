<?php
require_once __DIR__ . '/../lib/Auth.php';
Auth::logout();
header('Location: index.php?page=login');
exit;

