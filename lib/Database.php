<?php
require_once __DIR__ . '/../config.php';

class Database {
    private static ?PDO $pdo = null;

    public static function connection(): PDO {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        return self::$pdo;
    }

    public static function fetchSetting(string $key, ?string $default = null): ?string {
        $stmt = self::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    }

    public static function upsertSetting(string $key, string $value): void {
        $stmt = self::connection()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->execute([$key, $value]);
    }
}

