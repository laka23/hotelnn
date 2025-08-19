<section>
    <div class="flex-row">
        <form method="get" class="flex-row" style="gap:8px;">
            <input type="hidden" name="page" value="products">
            <input type="text" name="q" placeholder="Search name / SKU / IMEI" value="<?php echo htmlspecialchars($q ?? ''); ?>">
            <button class="btn">Search</button>
        </form>
        <a class="btn primary" href="?page=products&action=create">Add Product</a>
    </div>
    <div class="card" style="margin-top:12px; overflow:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>SKU</th>
                    <th>IMEI</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td><?php echo (int)$p['id']; ?></td>
                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                    <td><?php echo htmlspecialchars($p['sku']); ?></td>
                    <td><?php echo htmlspecialchars($p['imei']); ?></td>
                    <td>₹<?php echo number_format((float)$p['price'], 2); ?></td>
                    <td><?php echo (int)$p['stock_qty']; ?></td>
                    <td style="white-space:nowrap;">
                        <a class="btn" href="?page=products&action=edit&id=<?php echo (int)$p['id']; ?>">Edit</a>
                        <a class="btn danger" href="?page=products&action=delete&id=<?php echo (int)$p['id']; ?>" onclick="return confirm('Delete this product?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

