<?php
require_once __DIR__ . '/Database.php';

class Auth {
    public static function ensureInstall(): void {
        // Attempt a simple query to verify DB availability
        try {
            Database::connection()->query('SELECT 1');
        } catch (Throwable $e) {
            http_response_code(500);
            echo '<div style="padding:20px;font-family:sans-serif"><h2>Database connection failed</h2>';
            echo '<p>Please update DB settings in <code>config.php</code> and import <code>schema.sql</code>.</p>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre></div>';
            exit;
        }

        // Ensure at least one user exists; if not, create default admin/admin
        try {
            $pdo = Database::connection();
            // Check if users table exists
            $pdo->query('SELECT 1 FROM users LIMIT 1');
            $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            if ($count === 0) {
                $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
                $stmt->execute(['admin', password_hash('admin', PASSWORD_BCRYPT)]);
            }
        } catch (Throwable $e) {
            // If the table doesn't exist yet, show guidance
            http_response_code(500);
            echo '<div style="padding:20px;font-family:sans-serif"><h2>Database not initialized</h2>';
            echo '<p>Run the SQL in <code>schema.sql</code> to create tables, then reload this page.</p>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre></div>';
            exit;
        }
    }

    public static function attempt(string $username, string $password): bool {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
        return false;
    }

    public static function requireLogin(): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}

