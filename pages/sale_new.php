<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$pdo = getPDO();
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $qty = (int)$_POST['quantity'];
    if ($qty > 0) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $qty;
        } else {
            $_SESSION['cart'][$product_id] = $qty;
        }
    }
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $pid = (int)$_GET['remove'];
    unset($_SESSION['cart'][$pid]);
}

// Handle finalize sale
if (isset($_POST['finalize_sale']) && !empty($_SESSION['cart'])) {
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';

    $pdo->beginTransaction();
    try {
        // Calculate total
        $total = 0;
        $items = [];
        foreach ($_SESSION['cart'] as $pid => $qty) {
            $stmt = $pdo->prepare('SELECT sell_price, name FROM products WHERE id = ?');
            $stmt->execute([$pid]);
            $prod = $stmt->fetch();
            if (!$prod) throw new Exception('Product not found');
            $subtotal = $prod['sell_price'] * $qty;
            $total += $subtotal;
            $items[] = ['id'=>$pid,'qty'=>$qty,'price'=>$prod['sell_price'],'subtotal'=>$subtotal,'name'=>$prod['name']];
        }
        // Insert sale
        $stmt = $pdo->prepare('INSERT INTO sales (customer_name, customer_phone, total_amount) VALUES (?,?,?)');
        $stmt->execute([$customer_name, $customer_phone, $total]);
        $sale_id = $pdo->lastInsertId();
        // Insert items and update stock
        foreach ($items as $it) {
            $stmt = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal) VALUES (?,?,?,?,?)');
            $stmt->execute([$sale_id, $it['id'], $it['qty'], $it['price'], $it['subtotal']]);
            // Decrease stock
            $stmt = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?');
            $stmt->execute([$it['qty'], $it['id'], $it['qty']]);
            if ($stmt->rowCount() == 0) throw new Exception('Insufficient stock for '.$it['name']);
            // Log movement
            $stmt = $pdo->prepare('INSERT INTO stock_movements (product_id, qty_change, movement_type, related_id, description) VALUES (?,?,?,?,?)');
            $stmt->execute([$it['id'], -$it['qty'], 'OUT', $sale_id, 'Sale']);
        }
        $pdo->commit();
        $invoice_id = $sale_id; // Use sale id
        $cart_backup = $_SESSION['cart'];
        $_SESSION['cart'] = [];
        // Redirect to invoice page
        header('Location: sale_invoice.php?id='.$invoice_id);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

$products = $pdo->query('SELECT id, name, sell_price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY name')->fetchAll();
?>
<h2>New Sale</h2>
<div class="row">
    <div class="col-md-6">
        <h4>Add Product</h4>
        <form method="post" class="row g-2">
            <div class="col-8">
                <select name="product_id" class="form-select" required>
                    <option value="">Select product</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id']; ?>"><?= htmlspecialchars($p['name']); ?> (Stock: <?= $p['stock_quantity']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-3">
                <input type="number" name="quantity" class="form-control" placeholder="Qty" min="1" value="1" required>
            </div>
            <div class="col-1">
                <button type="submit" name="add_to_cart" class="btn btn-success">+</button>
            </div>
        </form>
    </div>
</div>
<hr>
<h4>Cart</h4>
<?php if (empty($_SESSION['cart'])): ?>
    <p>No items in cart.</p>
<?php else: ?>
<table class="table table-bordered">
    <thead>
        <tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th><th></th></tr>
    </thead>
    <tbody>
        <?php
        $grandTotal = 0;
        foreach ($_SESSION['cart'] as $pid => $qty):
            $stmt = $pdo->prepare('SELECT name, sell_price FROM products WHERE id = ?');
            $stmt->execute([$pid]);
            $prod = $stmt->fetch();
            $subtotal = $prod['sell_price'] * $qty;
            $grandTotal += $subtotal;
        ?>
        <tr>
            <td><?= htmlspecialchars($prod['name']); ?></td>
            <td><?= $qty; ?></td>
            <td><?= number_format($prod['sell_price'],2); ?></td>
            <td><?= number_format($subtotal,2); ?></td>
            <td><a href="sale_new.php?remove=<?= $pid; ?>" class="btn btn-sm btn-danger">Remove</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td colspan="3" class="text-end fw-bold">Total</td><td colspan="2" class="fw-bold"><?= number_format($grandTotal,2); ?></td></tr></tfoot>
</table>
<form method="post" class="row g-3">
    <div class="col-md-4">
        <input type="text" name="customer_name" class="form-control" placeholder="Customer Name">
    </div>
    <div class="col-md-4">
        <input type="text" name="customer_phone" class="form-control" placeholder="Customer Phone">
    </div>
    <div class="col-md-4">
        <button type="submit" name="finalize_sale" class="btn btn-primary">Finalize & Print</button>
    </div>
</form>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>