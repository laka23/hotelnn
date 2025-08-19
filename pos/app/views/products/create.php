<section class="card">
    <h2>Add Product</h2>
    <form method="post" action="?page=products&action=create">
        <div class="grid">
            <label>Name
                <input name="name" required>
            </label>
            <label>SKU
                <input name="sku">
            </label>
            <label>IMEI
                <input name="imei">
            </label>
            <label>Category
                <input name="category">
            </label>
            <label>Selling Price
                <input name="price" type="number" step="0.01" value="0">
            </label>
            <label>Cost Price
                <input name="cost" type="number" step="0.01" value="0">
            </label>
            <label>Stock Qty
                <input name="stock_qty" type="number" step="1" value="0">
            </label>
            <label>Min Stock
                <input name="min_stock" type="number" step="1" value="0">
            </label>
        </div>
        <div style="margin-top:12px; display:flex; gap:8px;">
            <button class="btn primary" type="submit">Save</button>
            <a class="btn" href="?page=products">Cancel</a>
        </div>
    </form>
</section>

