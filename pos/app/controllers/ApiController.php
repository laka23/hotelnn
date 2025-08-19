<?php

class ApiController extends BaseController {
    private function ensureAuth(): void {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    public function products(): string {
        $this->ensureAuth();
        $pdo = Database::getConnection();
        $q = trim($_GET['q'] ?? '');
        $limit = (int)($_GET['limit'] ?? 20);
        $limit = max(1, min(100, $limit));
        if ($q === '') {
            $stmt = $pdo->prepare('SELECT id, name, sku, imei, price, stock_qty FROM products ORDER BY id DESC LIMIT ?');
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $like = "%$q%";
            $stmt = $pdo->prepare('SELECT id, name, sku, imei, price, stock_qty FROM products WHERE name LIKE ? OR sku LIKE ? OR imei LIKE ? ORDER BY name ASC LIMIT ?');
            $stmt->bindValue(1, $like, PDO::PARAM_STR);
            $stmt->bindValue(2, $like, PDO::PARAM_STR);
            $stmt->bindValue(3, $like, PDO::PARAM_STR);
            $stmt->bindValue(4, $limit, PDO::PARAM_INT);
            $stmt->execute();
        }
        return $this->json(['items' => $stmt->fetchAll()]);
    }

    public function customers(): string {
        $this->ensureAuth();
        $pdo = Database::getConnection();
        $q = trim($_GET['q'] ?? '');
        $like = "%$q%";
        $stmt = $pdo->prepare('SELECT id, name, phone, email FROM customers WHERE name LIKE ? OR phone LIKE ? ORDER BY name ASC LIMIT 20');
        $stmt->execute([$like, $like]);
        return $this->json(['items' => $stmt->fetchAll()]);
    }

    public function createCustomer(): string {
        $this->ensureAuth();
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($name === '') {
            return $this->json(['error' => 'Name is required'], 422);
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO customers (name, phone, email) VALUES (?, ?, ?)');
        $stmt->execute([$name, $phone !== '' ? $phone : null, $email !== '' ? $email : null]);
        $id = (int)$pdo->lastInsertId();
        return $this->json(['id' => $id, 'name' => $name, 'phone' => $phone, 'email' => $email], 201);
    }
}

