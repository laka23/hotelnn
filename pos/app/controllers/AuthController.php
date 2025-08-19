<?php

class AuthController extends BaseController {
    public function setup(): string {
        return $this->render('auth/setup', []);
    }

    public function createAdmin(): string {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            return $this->render('auth/setup', ['error' => 'Username and password are required.']);
        }
        $pdo = Database::getConnection();
        $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count > 0) {
            return $this->render('auth/login', ['error' => 'Admin already exists. Please log in.']);
        }
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), 'admin']);
        $_SESSION['user'] = ['username' => $username, 'role' => 'admin'];
        $this->redirect('?page=dashboard');
        return '';
    }

    public function login(): string {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = ['id' => $user['id'], 'username' => $user['username'], 'role' => $user['role']];
                $this->redirect('?page=dashboard');
                return '';
            }
            return $this->render('auth/login', ['error' => 'Invalid credentials']);
        }
        return $this->render('auth/login', []);
    }

    public function logout(): string {
        session_destroy();
        $this->redirect('?page=auth&action=login');
        return '';
    }
}

