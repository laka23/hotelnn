<section class="card">
    <div style="display:flex; justify-content: space-between; align-items:center;">
        <h2>Invoice #<?php echo (int)$sale['id']; ?></h2>
        <button class="btn primary" data-print>Print</button>
    </div>
    <div style="display:flex; justify-content: space-between; margin:10px 0;">
        <div>
            <div><strong>Customer:</strong> <?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer'); ?></div>
            <?php if (!empty($sale['customer_phone'])): ?><div><strong>Phone:</strong> <?php echo htmlspecialchars($sale['customer_phone']); ?></div><?php endif; ?>
        </div>
        <div>
            <div><strong>Date:</strong> <?php echo htmlspecialchars($sale['created_at']); ?></div>
            <div><strong>Payment:</strong> <?php echo htmlspecialchars($sale['payment_method']); ?></div>
        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php $n=1; foreach ($items as $it): ?>
            <tr>
                <td><?php echo $n++; ?></td>
                <td><?php echo htmlspecialchars($it['name']); ?></td>
                <td><?php echo (int)$it['quantity']; ?></td>
                <td>₹<?php echo number_format((float)$it['unit_price'], 2); ?></td>
                <td>₹<?php echo number_format((float)$it['total_price'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;">Subtotal</td>
                <td>₹<?php echo number_format((float)$sale['subtotal'], 2); ?></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:right;">Discount</td>
                <td>₹<?php echo number_format((float)$sale['discount'], 2); ?></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:right;">Tax</td>
                <td>₹<?php echo number_format((float)$sale['tax'], 2); ?></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:right;"><strong>Total</strong></td>
                <td><strong>₹<?php echo number_format((float)$sale['total'], 2); ?></strong></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:right;">Paid</td>
                <td>₹<?php echo number_format((float)$sale['paid_amount'], 2); ?></td>
            </tr>
        </tfoot>
    </table>
    <p style="text-align:center; color: var(--muted);">Thank you for your business!</p>
</section>

