<?php

class SaleController extends BaseController {
    public function create(): string {
        return $this->render('sales/create');
    }

    public function store(): string {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $customerId = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
            $items = json_decode($_POST['items'] ?? '[]', true) ?: [];
            $discount = (float)($_POST['discount'] ?? 0);
            $tax = (float)($_POST['tax'] ?? 0);
            $paymentMethod = $_POST['payment_method'] ?? 'cash';
            $paidAmount = (float)($_POST['paid_amount'] ?? 0);

            $subtotal = 0.0;
            foreach ($items as $it) {
                $subtotal += (float)$it['unit_price'] * (int)$it['quantity'];
            }
            $total = max(0, $subtotal - $discount + $tax);

            $stmt = $pdo->prepare('INSERT INTO sales (customer_id, subtotal, discount, tax, total, paid_amount, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$customerId, $subtotal, $discount, $tax, $total, $paidAmount, $paymentMethod]);
            $saleId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)');
            $stockStmt = $pdo->prepare('UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?');
            $movementStmt = $pdo->prepare('INSERT INTO stock_movements (product_id, change_qty, reason, reference_type, reference_id, note) VALUES (?, ?, \"sale\", \"sale\", ?, NULL)');

            foreach ($items as $it) {
                $productId = (int)$it['product_id'];
                $qty = (int)$it['quantity'];
                $unitPrice = (float)$it['unit_price'];
                $totalPrice = $qty * $unitPrice;
                $itemStmt->execute([$saleId, $productId, $qty, $unitPrice, $totalPrice]);
                $stockStmt->execute([$qty, $productId]);
                $movementStmt->execute([$productId, -$qty, $saleId]);
            }

            $pdo->commit();
            $this->redirect('?page=sales&action=invoice&id=' . $saleId);
            return '';
        } catch (Throwable $e) {
            $pdo->rollBack();
            return $this->render('sales/create', ['error' => 'Failed to save sale: ' . $e->getMessage()]);
        }
    }

    public function invoice(): string {
        $pdo = Database::getConnection();
        $id = (int)($_GET['id'] ?? 0);
        $sale = $pdo->prepare('SELECT s.*, c.name as customer_name, c.phone as customer_phone FROM sales s LEFT JOIN customers c ON c.id = s.customer_id WHERE s.id = ?');
        $sale->execute([$id]);
        $saleData = $sale->fetch();
        if (!$saleData) { $this->redirect('?page=sales&action=create'); }
        $itemsStmt = $pdo->prepare('SELECT si.*, p.name FROM sale_items si JOIN products p ON p.id = si.product_id WHERE si.sale_id = ?');
        $itemsStmt->execute([$id]);
        $items = $itemsStmt->fetchAll();
        return $this->render('sales/invoice', ['sale' => $saleData, 'items' => $items]);
    }
}

