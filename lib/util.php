<?php
declare(strict_types=1);

function h(string $value): string
{
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function post(string $key, $default = null)
{
	return $_POST[$key] ?? $default;
}

function get(string $key, $default = null)
{
	return $_GET[$key] ?? $default;
}

function redirect(string $route, array $params = []): void
{
	$query = http_build_query(array_merge(['r' => $route], $params));
	header('Location: ?' . $query);
	exit;
}

function csrf_token(): string
{
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
	}
	return $_SESSION['csrf_token'];
}

function csrf_check(): void
{
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$token = $_POST['csrf_token'] ?? '';
		if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
			http_response_code(400);
			echo 'Invalid CSRF token';
			exit;
		}
	}
}