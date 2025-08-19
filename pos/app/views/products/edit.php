<section class="card">
    <h2>Edit Product</h2>
    <form method="post" action="?page=products&action=edit&id=<?php echo (int)$product['id']; ?>">
        <div class="grid">
            <label>Name
                <input name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </label>
            <label>SKU
                <input name="sku" value="<?php echo htmlspecialchars($product['sku']); ?>">
            </label>
            <label>IMEI
                <input name="imei" value="<?php echo htmlspecialchars($product['imei']); ?>">
            </label>
            <label>Category
                <input name="category" value="<?php echo htmlspecialchars($product['category']); ?>">
            </label>
            <label>Selling Price
                <input name="price" type="number" step="0.01" value="<?php echo number_format((float)$product['price'], 2, '.', ''); ?>">
            </label>
            <label>Cost Price
                <input name="cost" type="number" step="0.01" value="<?php echo number_format((float)$product['cost'], 2, '.', ''); ?>">
            </label>
            <label>Stock Qty
                <input name="stock_qty" type="number" step="1" value="<?php echo (int)$product['stock_qty']; ?>">
            </label>
            <label>Min Stock
                <input name="min_stock" type="number" step="1" value="<?php echo (int)$product['min_stock']; ?>">
            </label>
        </div>
        <div style="margin-top:12px; display:flex; gap:8px;">
            <button class="btn primary" type="submit">Save</button>
            <a class="btn" href="?page=products">Cancel</a>
        </div>
    </form>
</section>

