<?php
declare(strict_types=1);

function is_authenticated(): bool
{
	return !empty($_SESSION['user']);
}

function require_auth(): void
{
	if (!is_authenticated()) {
		header('Location: ?r=login');
		exit;
	}
}

function current_user(): ?array
{
	return $_SESSION['user'] ?? null;
}

function login(PDO $pdo, string $username, string $password): bool
{
	$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
	$stmt->execute([$username]);
	$user = $stmt->fetch();
	if ($user && password_verify($password, $user['password_hash'])) {
		$_SESSION['user'] = [
			'id' => (int)$user['id'],
			'username' => $user['username'],
			'role' => $user['role'],
		];
		return true;
	}
	return false;
}

function logout(): void
{
	$_SESSION = [];
	if (ini_get('session.use_cookies')) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
	}
	session_destroy();
}