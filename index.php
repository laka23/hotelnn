<?php
require_once __DIR__ . '/config.php';

// Autoload simple libs
require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/Util.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

Auth::ensureInstall();

$page = $_GET['page'] ?? 'dashboard';
$publicPages = ['login'];

if (!in_array($page, $publicPages, true)) {
    Auth::requireLogin();
}

// Render layout
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/nav.php';

$pageFile = __DIR__ . '/pages/' . basename($page) . '.php';
if (is_file($pageFile)) {
    include $pageFile;
} else {
    http_response_code(404);
    echo '<div class="container"><h2>404 Not Found</h2><p>Page not found.</p></div>';
}

include __DIR__ . '/templates/footer.php';

