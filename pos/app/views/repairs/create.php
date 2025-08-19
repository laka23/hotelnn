<section class="card">
    <h2>New Repair</h2>
    <form method="post" action="?page=repairs&action=create">
        <div class="grid">
            <label>Customer
                <input id="customerSearch" placeholder="Search customer (press Enter)">
                <input type="hidden" name="customer_id" id="customerId">
            </label>
            <label>Brand
                <input name="device_brand" required>
            </label>
            <label>Model
                <input name="device_model" required>
            </label>
            <label>IMEI
                <input name="imei">
            </label>
            <label>Estimated Cost
                <input name="estimated_cost" type="number" step="0.01" value="0">
            </label>
        </div>
        <label>Issue Description
            <textarea name="issue_description" required></textarea>
        </label>
        <label>Notes
            <textarea name="notes"></textarea>
        </label>
        <div style="margin-top:12px; display:flex; gap:8px;">
            <button class="btn primary" type="submit">Save</button>
            <a class="btn" href="?page=repairs">Cancel</a>
        </div>
    </form>
</section>

<script>
async function fetchJSON(url, opts={}) { const res = await fetch(url, opts); return await res.json(); }
document.getElementById('customerSearch').addEventListener('keydown', async (e) => {
  if (e.key === 'Enter') {
    const q = e.target.value.trim(); if (!q) return;
    const data = await fetchJSON(`?page=api&action=customers&q=${encodeURIComponent(q)}`);
    const item = data.items[0];
    if (item) { document.getElementById('customerId').value = item.id; e.target.value = `${item.name}${item.phone?(' ('+item.phone+')'):''}`; }
    else { const fd = new FormData(); fd.set('name', q); const c = await fetchJSON(`?page=api&action=createCustomer`, { method:'POST', body: fd }); document.getElementById('customerId').value = c.id; e.target.value = c.name; }
  }
});
</script>

