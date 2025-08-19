<?php
declare(strict_types=1);

function get_pdo(array $dbConfig): PDO
{
	static $pdo = null;
	if ($pdo instanceof PDO) {
		return $pdo;
	}
	$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
		$dbConfig['host'],
		(int)$dbConfig['port'],
		$dbConfig['name'],
		$dbConfig['charset'] ?? 'utf8mb4'
	);
	$options = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES => false,
	];
	$pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
	return $pdo;
}

function load_settings(PDO $pdo, array $appDefaults): array
{
	$settings = $appDefaults;
	try {
		$row = $pdo->query('SELECT * FROM settings ORDER BY id DESC LIMIT 1')->fetch();
		if ($row) {
			$settings['shop_name'] = $row['shop_name'];
			$settings['shop_address'] = $row['shop_address'];
			$settings['shop_phone'] = $row['shop_phone'];
			$settings['currency_symbol'] = $row['currency_symbol'];
			$settings['tax_rate'] = (float)$row['tax_rate'];
		}
	} catch (Throwable $e) {
		// settings table may not exist yet; ignore
	}
	return $settings;
}

function db_next_sequence(PDO $pdo, string $name): int
{
	$pdo->beginTransaction();
	try {
		$stmt = $pdo->prepare('SELECT current_value FROM sequences WHERE name = ? FOR UPDATE');
		$stmt->execute([$name]);
		$row = $stmt->fetch();
		if (!$row) {
			$pdo->prepare('INSERT INTO sequences (name, current_value) VALUES (?, 0)')->execute([$name]);
			$current = 0;
		} else {
			$current = (int)$row['current_value'];
		}
		$next = $current + 1;
		$pdo->prepare('UPDATE sequences SET current_value = ? WHERE name = ?')->execute([$next, $name]);
		$pdo->commit();
		return $next;
	} catch (Throwable $e) {
		$pdo->rollBack();
		throw $e;
	}
}