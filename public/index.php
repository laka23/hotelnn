<?php
declare(strict_types=1);

session_start();

$root = dirname(__DIR__);
$configFile = $root . '/config.php';
if (!file_exists($configFile)) {
	$configFile = $root . '/config.sample.php';
}
$config = require $configFile;

require_once $root . '/lib/util.php';
require_once $root . '/lib/db.php';
require_once $root . '/lib/auth.php';
require_once $root . '/lib/cart.php';

$db = get_pdo($config['db']);
$settings = load_settings($db, $config['app']);

// Bootstrap default admin if users table is empty
try {
	$cnt = $db->query('SELECT COUNT(*) AS c FROM users')->fetch();
	if (isset($cnt['c']) && (int)$cnt['c'] === 0) {
		$hash = password_hash('admin123', PASSWORD_BCRYPT);
		$stmt = $db->prepare('INSERT INTO users (username, password_hash, role) VALUES (?,?,?)');
		$stmt->execute(['admin', $hash, 'admin']);
	}
} catch (Throwable $e) {
	// ignore if table missing; will be created after schema import
}

$route = $_GET['r'] ?? 'dashboard';
$publicRoutes = ['login', 'logout'];

if (!in_array($route, $publicRoutes, true) && !is_authenticated()) {
	header('Location: ?r=login');
	exit;
}

ob_start();
$viewData = [];

switch ($route) {
	case 'login':
		require $root . '/views/login.php';
		break;
	case 'logout':
		logout();
		header('Location: ?r=login');
		exit;
	case 'dashboard':
		require $root . '/views/dashboard.php';
		break;
	case 'products':
		require $root . '/views/products/list.php';
		break;
	case 'product_form':
		require $root . '/views/products/form.php';
		break;
	case 'customers':
		require $root . '/views/customers/list.php';
		break;
	case 'customer_form':
		require $root . '/views/customers/form.php';
		break;
	case 'pos':
		require $root . '/views/pos/pos.php';
		break;
	case 'sales':
		require $root . '/views/sales/list.php';
		break;
	case 'sale_view':
		require $root . '/views/sales/view.php';
		break;
	case 'sale_print':
		require $root . '/views/sales/print.php';
		break;
	case 'repairs':
		require $root . '/views/repairs/list.php';
		break;
	case 'repair_form':
		require $root . '/views/repairs/form.php';
		break;
	case 'repair_view':
		require $root . '/views/repairs/view.php';
		break;
	case 'repair_print':
		require $root . '/views/repairs/print.php';
		break;
	case 'stock_in':
		require $root . '/views/stock/in.php';
		break;
	case 'settings':
		require $root . '/views/settings.php';
		break;
	default:
		http_response_code(404);
		echo '<h1>404 Not Found</h1>';
		break;
}

$content = ob_get_clean();

require $root . '/views/layout/header.php';
print $content;
require $root . '/views/layout/footer.php';