<section class="grid">
    <div class="stat">
        <div class="label">Products</div>
        <div class="value"><?php echo (int)$productCount; ?></div>
    </div>
    <div class="stat">
        <div class="label">Total Stock</div>
        <div class="value"><?php echo (int)$stockSum; ?></div>
    </div>
    <div class="stat">
        <div class="label">Today's Sales</div>
        <div class="value">₹<?php echo number_format((float)$todaySales, 2); ?></div>
    </div>
    <div class="stat">
        <div class="label">Open Repairs</div>
        <div class="value"><?php echo (int)$openRepairs; ?></div>
    </div>
</section>

