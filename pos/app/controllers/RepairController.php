<?php

class RepairController extends BaseController {
    public function index(): string {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT r.*, c.name AS customer_name FROM repairs r LEFT JOIN customers c ON c.id = r.customer_id ORDER BY r.id DESC LIMIT 200");
        $repairs = $stmt->fetchAll();
        return $this->render('repairs/index', ['repairs' => $repairs]);
    }

    public function create(): string {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('INSERT INTO repairs (customer_id, device_brand, device_model, imei, issue_description, estimated_cost, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
                trim($_POST['device_brand'] ?? ''),
                trim($_POST['device_model'] ?? ''),
                $this->nullIfEmpty($_POST['imei'] ?? null),
                trim($_POST['issue_description'] ?? ''),
                (float)($_POST['estimated_cost'] ?? 0),
                $this->nullIfEmpty($_POST['notes'] ?? null),
            ]);
            $this->redirect('?page=repairs');
            return '';
        }
        return $this->render('repairs/create');
    }

    public function edit(): string {
        $pdo = Database::getConnection();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { $this->redirect('?page=repairs'); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $pdo->prepare('UPDATE repairs SET customer_id=?, device_brand=?, device_model=?, imei=?, issue_description=?, estimated_cost=?, actual_cost=?, status=?, notes=? WHERE id=?');
            $stmt->execute([
                !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
                trim($_POST['device_brand'] ?? ''),
                trim($_POST['device_model'] ?? ''),
                $this->nullIfEmpty($_POST['imei'] ?? null),
                trim($_POST['issue_description'] ?? ''),
                (float)($_POST['estimated_cost'] ?? 0),
                (float)($_POST['actual_cost'] ?? 0),
                $_POST['status'] ?? 'received',
                $this->nullIfEmpty($_POST['notes'] ?? null),
                $id
            ]);
            $this->redirect('?page=repairs');
            return '';
        }
        $stmt = $pdo->prepare('SELECT * FROM repairs WHERE id=?');
        $stmt->execute([$id]);
        $repair = $stmt->fetch();
        if (!$repair) { $this->redirect('?page=repairs'); }
        $partsStmt = $pdo->prepare('SELECT rp.*, p.name FROM repair_parts rp JOIN products p ON p.id = rp.product_id WHERE rp.repair_id = ?');
        $partsStmt->execute([$id]);
        $parts = $partsStmt->fetchAll();
        $products = $pdo->query('SELECT id, name, price, stock_qty FROM products ORDER BY name ASC LIMIT 500')->fetchAll();
        return $this->render('repairs/edit', ['repair' => $repair, 'parts' => $parts, 'products' => $products]);
    }

    private function nullIfEmpty($value) {
        $value = is_string($value) ? trim($value) : $value;
        return $value === '' ? null : $value;
    }

    public function addPart(): string {
        $pdo = Database::getConnection();
        $repairId = (int)($_POST['repair_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        $unitPrice = (float)($_POST['unit_price'] ?? 0);
        if ($repairId <= 0 || $productId <= 0) { $this->redirect('?page=repairs'); }
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare('INSERT INTO repair_parts (repair_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
            $ins->execute([$repairId, $productId, $qty, $unitPrice]);
            $upd = $pdo->prepare('UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?');
            $upd->execute([$qty, $productId]);
            $movement = $pdo->prepare('INSERT INTO stock_movements (product_id, change_qty, reason, reference_type, reference_id, note) VALUES (?, ?, "repair", "repair", ?, NULL)');
            $movement->execute([$productId, -$qty, $repairId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
        }
        $this->redirect('?page=repairs&action=edit&id=' . $repairId);
        return '';
    }

    public function removePart(): string {
        $pdo = Database::getConnection();
        $id = (int)($_GET['id'] ?? 0);
        $repairId = (int)($_GET['repair_id'] ?? 0);
        if ($id <= 0 || $repairId <= 0) { $this->redirect('?page=repairs'); }
        $pdo->beginTransaction();
        try {
            $rowStmt = $pdo->prepare('SELECT * FROM repair_parts WHERE id = ? AND repair_id = ?');
            $rowStmt->execute([$id, $repairId]);
            $row = $rowStmt->fetch();
            if ($row) {
                $del = $pdo->prepare('DELETE FROM repair_parts WHERE id = ?');
                $del->execute([$id]);
                $restock = $pdo->prepare('UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?');
                $restock->execute([(int)$row['quantity'], (int)$row['product_id']]);
                $movement = $pdo->prepare('INSERT INTO stock_movements (product_id, change_qty, reason, reference_type, reference_id, note) VALUES (?, ?, "adjustment", "repair", ?, "Removed part")');
                $movement->execute([(int)$row['product_id'], (int)$row['quantity'], $repairId]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
        }
        $this->redirect('?page=repairs&action=edit&id=' . $repairId);
        return '';
    }
}

