<section class="card">
    <h2>Edit Repair #<?php echo (int)$repair['id']; ?></h2>
    <form method="post" action="?page=repairs&action=edit&id=<?php echo (int)$repair['id']; ?>">
        <div class="grid">
            <label>Customer ID
                <input name="customer_id" value="<?php echo (int)$repair['customer_id']; ?>">
            </label>
            <label>Brand
                <input name="device_brand" value="<?php echo htmlspecialchars($repair['device_brand']); ?>" required>
            </label>
            <label>Model
                <input name="device_model" value="<?php echo htmlspecialchars($repair['device_model']); ?>" required>
            </label>
            <label>IMEI
                <input name="imei" value="<?php echo htmlspecialchars($repair['imei']); ?>">
            </label>
            <label>Estimated Cost
                <input name="estimated_cost" type="number" step="0.01" value="<?php echo number_format((float)$repair['estimated_cost'], 2, '.', ''); ?>">
            </label>
            <label>Actual Cost
                <input name="actual_cost" type="number" step="0.01" value="<?php echo number_format((float)$repair['actual_cost'], 2, '.', ''); ?>">
            </label>
            <label>Status
                <select name="status">
                    <?php foreach (['received','in_progress','ready','delivered'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $repair['status']===$s?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ', $s)); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Issue Description
            <textarea name="issue_description" required><?php echo htmlspecialchars($repair['issue_description']); ?></textarea>
        </label>
        <label>Notes
            <textarea name="notes"><?php echo htmlspecialchars($repair['notes']); ?></textarea>
        </label>
        <div style="margin-top:12px; display:flex; gap:8px;">
            <button class="btn primary" type="submit">Save</button>
            <a class="btn" href="?page=repairs">Cancel</a>
        </div>
    </form>
</section>

<section class="card" style="margin-top:12px;">
    <h3>Parts Used</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Part</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php $sum = 0; foreach (($parts ?? []) as $p): $sum += (float)$p['quantity']*(float)$p['unit_price']; ?>
            <tr>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo (int)$p['quantity']; ?></td>
                <td>₹<?php echo number_format((float)$p['unit_price'], 2); ?></td>
                <td>₹<?php echo number_format((float)$p['quantity']*(float)$p['unit_price'], 2); ?></td>
                <td><a class="btn danger" href="?page=repairs&action=removePart&id=<?php echo (int)$p['id']; ?>&repair_id=<?php echo (int)$repair['id']; }?>" onclick="return confirm('Remove this part?')">Remove</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;">Parts Total</td>
                <td colspan="2">₹<?php echo number_format((float)$sum, 2); ?></td>
            </tr>
        </tfoot>
    </table>
    <form method="post" action="?page=repairs&action=addPart" style="margin-top:10px;">
        <input type="hidden" name="repair_id" value="<?php echo (int)$repair['id']; ?>">
        <div class="grid">
            <label>Part
                <select name="product_id">
                    <?php foreach (($products ?? []) as $prod): ?>
                    <option value="<?php echo (int)$prod['id']; ?>"><?php echo htmlspecialchars($prod['name']); ?> (₹<?php echo number_format((float)$prod['price'], 2); ?> | Stock: <?php echo (int)$prod['stock_qty']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Quantity
                <input type="number" name="quantity" value="1" min="1">
            </label>
            <label>Unit Price
                <input type="number" step="0.01" name="unit_price" value="0">
            </label>
        </div>
        <div style="margin-top:10px;">
            <button class="btn" type="submit">Add Part</button>
        </div>
    </form>
</section>

