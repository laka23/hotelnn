<section class="card">
    <h2>Adjust Stock</h2>
    <?php if (!empty($error)): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post" action="?page=stock&action=adjust">
        <div class="grid">
            <label>Product
                <select name="product_id">
                    <?php foreach ($products as $p): ?>
                    <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (<?php echo (int)$p['stock_qty']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Change Quantity (use negative to reduce)
                <input name="change_qty" type="number" step="1" value="0">
            </label>
        </div>
        <label>Note
            <input name="note">
        </label>
        <div style="margin-top:12px; display:flex; gap:8px;">
            <button class="btn primary" type="submit">Apply</button>
            <a class="btn" href="?page=stock">Cancel</a>
        </div>
    </form>
</section>

