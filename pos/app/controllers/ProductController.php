<?php

class ProductController extends BaseController {
    public function index(): string {
        $pdo = Database::getConnection();
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR sku LIKE ? OR imei LIKE ? ORDER BY id DESC LIMIT 200");
            $like = "%$q%";
            $stmt->execute([$like, $like, $like]);
        } else {
            $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 200");
        }
        $products = $stmt->fetchAll();
        return $this->render('products/index', ['products' => $products, 'q' => $q]);
    }

    public function create(): string {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('INSERT INTO products (name, sku, imei, category, price, cost, stock_qty, min_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                trim($_POST['name'] ?? ''),
                $this->nullIfEmpty($_POST['sku'] ?? null),
                $this->nullIfEmpty($_POST['imei'] ?? null),
                $this->nullIfEmpty($_POST['category'] ?? null),
                (float)($_POST['price'] ?? 0),
                (float)($_POST['cost'] ?? 0),
                (int)($_POST['stock_qty'] ?? 0),
                (int)($_POST['min_stock'] ?? 0),
            ]);
            $this->redirect('?page=products');
            return '';
        }
        return $this->render('products/create');
    }

    public function edit(): string {
        $pdo = Database::getConnection();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { $this->redirect('?page=products'); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $pdo->prepare('UPDATE products SET name=?, sku=?, imei=?, category=?, price=?, cost=?, stock_qty=?, min_stock=? WHERE id=?');
            $stmt->execute([
                trim($_POST['name'] ?? ''),
                $this->nullIfEmpty($_POST['sku'] ?? null),
                $this->nullIfEmpty($_POST['imei'] ?? null),
                $this->nullIfEmpty($_POST['category'] ?? null),
                (float)($_POST['price'] ?? 0),
                (float)($_POST['cost'] ?? 0),
                (int)($_POST['stock_qty'] ?? 0),
                (int)($_POST['min_stock'] ?? 0),
                $id,
            ]);
            $this->redirect('?page=products');
            return '';
        }
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id=?');
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) { $this->redirect('?page=products'); }
        return $this->render('products/edit', ['product' => $product]);
    }

    public function delete(): string {
        $pdo = Database::getConnection();
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM products WHERE id=?');
            $stmt->execute([$id]);
        }
        $this->redirect('?page=products');
        return '';
    }

    private function nullIfEmpty($value) {
        $value = is_string($value) ? trim($value) : $value;
        return $value === '' ? null : $value;
    }
}

