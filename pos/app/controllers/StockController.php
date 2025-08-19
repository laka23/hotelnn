<?php

class StockController extends BaseController {
    public function index(): string {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT sm.*, p.name as product_name FROM stock_movements sm JOIN products p ON p.id = sm.product_id ORDER BY sm.id DESC LIMIT 300');
        $movements = $stmt->fetchAll();
        return $this->render('stock/index', ['movements' => $movements]);
    }

    public function adjust(): string {
        $pdo = Database::getConnection();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productId = (int)$_POST['product_id'];
            $changeQty = (int)$_POST['change_qty'];
            $note = trim($_POST['note'] ?? '');
            $pdo->beginTransaction();
            try {
                $upd = $pdo->prepare('UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?');
                $upd->execute([$changeQty, $productId]);
                $ins = $pdo->prepare('INSERT INTO stock_movements (product_id, change_qty, reason, reference_type, reference_id, note) VALUES (?, ?, \"adjustment\", NULL, NULL, ?)');
                $ins->execute([$productId, $changeQty, $note !== '' ? $note : null]);
                $pdo->commit();
                $this->redirect('?page=stock');
                return '';
            } catch (Throwable $e) {
                $pdo->rollBack();
                return $this->render('stock/adjust', ['error' => $e->getMessage()]);
            }
        }
        // list products for selection
        $products = $pdo->query('SELECT id, name, stock_qty FROM products ORDER BY name ASC LIMIT 500')->fetchAll();
        return $this->render('stock/adjust', ['products' => $products]);
    }
}

