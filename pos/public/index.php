<?php
session_start();

// Load config or fallback to example for first run
if (file_exists(__DIR__ . '/../app/config.php')) {
    require_once __DIR__ . '/../app/config.php';
} else {
    require_once __DIR__ . '/../app/config.example.php';
}
require_once __DIR__ . '/../app/lib/Database.php';

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../app/controllers/' . $class . '.php',
        __DIR__ . '/../app/lib/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

// Public routes that do not require auth
$publicRoutes = [
    'auth' => true,
    'api' => true, // product search can be used after login; will restrict in controller
];

// Redirect to setup if no users exist
try {
    $pdo = Database::getConnection();
    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
} catch (Throwable $e) {
    $userCount = 0; // If table does not yet exist, let the user finish setup
}

if ($userCount === 0 && !($page === 'auth' && in_array($action, ['setup', 'createAdmin', 'login'], true))) {
    $page = 'auth';
    $action = 'setup';
}

// Simple auth check
$isLoggedIn = isset($_SESSION['user']);
if (!$isLoggedIn && !isset($publicRoutes[$page])) {
    $page = 'auth';
    $action = 'login';
}

// Route to controller
$controllerMap = [
    'auth' => 'AuthController',
    'dashboard' => 'DashboardController',
    'products' => 'ProductController',
    'sales' => 'SaleController',
    'repairs' => 'RepairController',
    'stock' => 'StockController',
    'api' => 'ApiController',
];

$controllerName = $controllerMap[$page] ?? 'DashboardController';
if (!class_exists($controllerName)) {
    http_response_code(404);
    echo 'Page not found';
    exit;
}

$controller = new $controllerName();
if (!method_exists($controller, $action)) {
    http_response_code(404);
    echo 'Action not found';
    exit;
}

echo $controller->$action();

