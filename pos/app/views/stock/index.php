<section>
    <div class="flex-row">
        <h2>Stock Movements</h2>
        <a class="btn primary" href="?page=stock&action=adjust">Adjust Stock</a>
    </div>
    <div class="card" style="margin-top:12px; overflow:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Change</th>
                    <th>Reason</th>
                    <th>Note</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movements as $m): ?>
                <tr>
                    <td><?php echo (int)$m['id']; ?></td>
                    <td><?php echo htmlspecialchars($m['product_name']); ?></td>
                    <td><?php echo (int)$m['change_qty']; ?></td>
                    <td><?php echo htmlspecialchars($m['reason']); ?></td>
                    <td><?php echo htmlspecialchars($m['note'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($m['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

