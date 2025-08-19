<?php

class Util {
    public static function e(?string $str): string {
        return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function csrfToken(): string {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }

    public static function verifyCsrf(?string $token): bool {
        return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$token);
    }

    public static function money($amount, string $currency): string {
        return $currency . ' ' . number_format((float)$amount, 2);
    }

    public static function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }
}

