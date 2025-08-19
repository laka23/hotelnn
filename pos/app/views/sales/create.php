<section class="card">
    <h2>Point of Sale</h2>
    <?php if (!empty($error)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <div class="grid">
        <label>Customer
            <input id="customerSearch" placeholder="Search or add customer">
            <input type="hidden" id="customerId">
        </label>
        <label>Payment Method
            <select id="paymentMethod">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="upi">UPI</option>
                <option value="bank">Bank</option>
            </select>
        </label>
    </div>

    <div class="grid" style="margin-top:10px;">
        <label>Find Product
            <input id="productSearch" placeholder="Scan IMEI / search name or SKU">
        </label>
    </div>

    <div class="card" style="margin-top:12px; overflow:auto;">
        <table class="table" id="cartTable">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="grid" style="margin-top:10px;">
        <label>Discount
            <input id="discount" type="number" step="0.01" value="0">
        </label>
        <label>Tax
            <input id="tax" type="number" step="0.01" value="0">
        </label>
        <label>Paid Amount
            <input id="paidAmount" type="number" step="0.01" value="0">
        </label>
    </div>

    <div style="display:flex; justify-content: space-between; align-items:center; margin-top:12px;">
        <div>
            <div>Subtotal: <strong id="subtotal">0.00</strong></div>
            <div>Total: <strong id="grandTotal">0.00</strong></div>
        </div>
        <button class="btn primary" id="saveSale">Save & Print</button>
    </div>
</section>

<script>
const cart = [];

function recalc() {
  let subtotal = 0;
  cart.forEach(it => subtotal += it.quantity * it.unit_price);
  const discount = parseFloat(document.getElementById('discount').value||0);
  const tax = parseFloat(document.getElementById('tax').value||0);
  const total = Math.max(0, subtotal - discount + tax);
  document.getElementById('subtotal').textContent = subtotal.toFixed(2);
  document.getElementById('grandTotal').textContent = total.toFixed(2);
}

function renderCart() {
  const tbody = document.querySelector('#cartTable tbody');
  tbody.innerHTML = '';
  cart.forEach((it, idx) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${it.name}</td>
      <td><input type="number" min="1" value="${it.quantity}" data-idx="${idx}" class="qty-input" style="width:80px;"></td>
      <td><input type="number" step="0.01" value="${it.unit_price}" data-idx="${idx}" class="price-input" style="width:100px;"></td>
      <td>₹ ${(it.quantity * it.unit_price).toFixed(2)}</td>
      <td><button class="btn danger" data-remove="${idx}">Remove</button></td>
    `;
    tbody.appendChild(tr);
  });
  recalc();
}

document.getElementById('discount').addEventListener('input', recalc);
document.getElementById('tax').addEventListener('input', recalc);

document.querySelector('#cartTable').addEventListener('input', (e) => {
  if (e.target.classList.contains('qty-input')) {
    const idx = parseInt(e.target.dataset.idx);
    cart[idx].quantity = Math.max(1, parseInt(e.target.value||1));
    renderCart();
  }
  if (e.target.classList.contains('price-input')) {
    const idx = parseInt(e.target.dataset.idx);
    cart[idx].unit_price = Math.max(0, parseFloat(e.target.value||0));
    renderCart();
  }
});

document.querySelector('#cartTable').addEventListener('click', (e) => {
  if (e.target.dataset.remove !== undefined) {
    const idx = parseInt(e.target.dataset.remove);
    cart.splice(idx, 1);
    renderCart();
  }
});

async function fetchJSON(url, opts={}) {
  const res = await fetch(url, opts);
  return await res.json();
}

// Product search (basic: on Enter performs search and adds first result)
document.getElementById('productSearch').addEventListener('keydown', async (e) => {
  if (e.key === 'Enter') {
    const q = e.target.value.trim();
    if (!q) return;
    const data = await fetchJSON(`?page=api&action=products&q=${encodeURIComponent(q)}&limit=1`);
    const item = data.items[0];
    if (item) {
      const existing = cart.find(x => x.product_id == item.id);
      if (existing) existing.quantity += 1; else cart.push({ product_id: item.id, name: item.name, quantity: 1, unit_price: parseFloat(item.price) });
      renderCart();
      e.target.value='';
    }
  }
});

// Customer search (basic)
document.getElementById('customerSearch').addEventListener('keydown', async (e) => {
  if (e.key === 'Enter') {
    const q = e.target.value.trim();
    if (!q) return;
    const data = await fetchJSON(`?page=api&action=customers&q=${encodeURIComponent(q)}`);
    const item = data.items[0];
    if (item) {
      document.getElementById('customerId').value = item.id;
      e.target.value = `${item.name} ${item.phone?('('+item.phone+')'):''}`;
    } else {
      // create
      const fd = new FormData();
      fd.set('name', q);
      const created = await fetchJSON(`?page=api&action=createCustomer`, { method:'POST', body: fd });
      document.getElementById('customerId').value = created.id;
      e.target.value = created.name;
    }
  }
});

document.getElementById('saveSale').addEventListener('click', async () => {
  if (cart.length === 0) { alert('Add at least one item'); return; }
  const fd = new FormData();
  fd.set('customer_id', document.getElementById('customerId').value);
  fd.set('payment_method', document.getElementById('paymentMethod').value);
  fd.set('discount', document.getElementById('discount').value || '0');
  fd.set('tax', document.getElementById('tax').value || '0');
  fd.set('paid_amount', document.getElementById('paidAmount').value || '0');
  fd.set('items', JSON.stringify(cart));
  const res = await fetch(`?page=sales&action=store`, { method:'POST', body: fd });
  if (res.redirected) { window.location = res.url; } else { alert('Failed to save'); }
});
</script>

