<section>
    <div class="flex-row">
        <h2>Repairs</h2>
        <a class="btn primary" href="?page=repairs&action=create">New Repair</a>
    </div>
    <div class="card" style="margin-top:12px; overflow:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Device</th>
                    <th>IMEI</th>
                    <th>Status</th>
                    <th>Est. Cost</th>
                    <th>Actual Cost</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($repairs as $r): ?>
                <tr>
                    <td><?php echo (int)$r['id']; ?></td>
                    <td><?php echo htmlspecialchars($r['customer_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($r['device_brand'] . ' ' . $r['device_model']); ?></td>
                    <td><?php echo htmlspecialchars($r['imei'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($r['status']); ?></td>
                    <td>₹<?php echo number_format((float)$r['estimated_cost'], 2); ?></td>
                    <td>₹<?php echo number_format((float)$r['actual_cost'], 2); ?></td>
                    <td><a class="btn" href="?page=repairs&action=edit&id=<?php echo (int)$r['id']; ?>">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

