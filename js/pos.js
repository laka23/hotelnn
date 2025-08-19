// Point of Sale functionality
class POSManager {
    constructor(app) {
        this.app = app;
        this.products = [];
        this.cart = [];
        this.customer = null;
        this.taxRate = 18; // Default tax rate
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadProducts();
        this.updateCartDisplay();
    }

    setupEventListeners() {
        // Product search
        const productSearch = document.getElementById('productSearch');
        const searchBtn = document.getElementById('searchBtn');
        
        productSearch.addEventListener('input', debounce(() => {
            this.searchProducts(productSearch.value);
        }, 300));

        searchBtn.addEventListener('click', () => {
            this.searchProducts(productSearch.value);
        });

        // Customer search
        const customerPhone = document.getElementById('customerPhone');
        const customerSearchBtn = document.getElementById('customerSearchBtn');

        customerSearchBtn.addEventListener('click', () => {
            this.searchCustomer(customerPhone.value);
        });

        customerPhone.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.searchCustomer(customerPhone.value);
            }
        });

        // Cart controls
        document.getElementById('clearCartBtn').addEventListener('click', () => {
            this.clearCart();
        });

        document.getElementById('checkoutBtn').addEventListener('click', () => {
            this.checkout();
        });

        // Tax and discount inputs
        document.getElementById('discount').addEventListener('input', () => {
            this.updateCartSummary();
        });

        document.getElementById('taxRate').addEventListener('input', (e) => {
            this.taxRate = parseFloat(e.target.value) || 0;
            this.updateCartSummary();
        });
    }

    async loadProducts() {
        try {
            const response = await this.app.apiCall('api/products.php');
            this.products = response.data || [];
            this.displayProducts(this.products);
        } catch (error) {
            console.error('Failed to load products:', error);
        }
    }

    async searchProducts(query) {
        try {
            if (query.trim() === '') {
                this.displayProducts(this.products);
                return;
            }

            const response = await this.app.apiCall(`api/products.php?search=${encodeURIComponent(query)}`);
            this.displayProducts(response.data || []);
        } catch (error) {
            console.error('Failed to search products:', error);
        }
    }

    displayProducts(products) {
        const grid = document.getElementById('productGrid');
        
        if (products.length === 0) {
            grid.innerHTML = '<div class="empty-cart"><i class="fas fa-search"></i><p>No products found</p></div>';
            return;
        }

        grid.innerHTML = products.map(product => `
            <div class="product-card ${product.stock_quantity <= 0 ? 'out-of-stock' : ''}" 
                 onclick="posManager.addToCart(${product.id})">
                <div class="product-name">${product.name}</div>
                <div class="product-brand">${product.brand} ${product.model}</div>
                <div class="product-price">${this.app.formatCurrency(product.price)}</div>
                <div class="product-stock">
                    Stock: ${product.stock_quantity}
                    ${product.stock_quantity <= product.min_stock_level ? '<i class="fas fa-exclamation-triangle" style="color: #f39c12;"></i>' : ''}
                </div>
            </div>
        `).join('');
    }

    addToCart(productId) {
        const product = this.products.find(p => p.id === productId);
        if (!product) return;

        if (product.stock_quantity <= 0) {
            this.app.showToast('Product is out of stock', 'warning');
            return;
        }

        // Check if product already in cart
        const existingItem = this.cart.find(item => item.product_id === productId);
        
        if (existingItem) {
            if (existingItem.quantity >= product.stock_quantity) {
                this.app.showToast('Cannot add more items than available stock', 'warning');
                return;
            }
            existingItem.quantity++;
        } else {
            this.cart.push({
                product_id: productId,
                name: product.name,
                brand: product.brand,
                model: product.model,
                price: product.price,
                quantity: 1,
                max_quantity: product.stock_quantity
            });
        }

        this.updateCartDisplay();
        this.app.showToast(`${product.name} added to cart`, 'success');
    }

    removeFromCart(productId) {
        this.cart = this.cart.filter(item => item.product_id !== productId);
        this.updateCartDisplay();
    }

    updateQuantity(productId, newQuantity) {
        const item = this.cart.find(item => item.product_id === productId);
        if (!item) return;

        if (newQuantity <= 0) {
            this.removeFromCart(productId);
            return;
        }

        if (newQuantity > item.max_quantity) {
            this.app.showToast('Cannot exceed available stock', 'warning');
            return;
        }

        item.quantity = newQuantity;
        this.updateCartDisplay();
    }

    updateCartDisplay() {
        const cartItems = document.getElementById('cartItems');
        const checkoutBtn = document.getElementById('checkoutBtn');

        if (this.cart.length === 0) {
            cartItems.innerHTML = `
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Cart is empty</p>
                </div>
            `;
            checkoutBtn.disabled = true;
        } else {
            cartItems.innerHTML = this.cart.map(item => `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <p>${item.brand} ${item.model} - ${this.app.formatCurrency(item.price)}</p>
                    </div>
                    <div class="cart-item-actions">
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="posManager.updateQuantity(${item.product_id}, ${item.quantity - 1})">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="quantity-display">${item.quantity}</span>
                            <button class="quantity-btn" onclick="posManager.updateQuantity(${item.product_id}, ${item.quantity + 1})">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button class="remove-btn" onclick="posManager.removeFromCart(${item.product_id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
            checkoutBtn.disabled = false;
        }

        this.updateCartSummary();
    }

    updateCartSummary() {
        const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const discount = parseFloat(document.getElementById('discount').value) || 0;
        const taxableAmount = subtotal - discount;
        const taxAmount = taxableAmount * (this.taxRate / 100);
        const total = taxableAmount + taxAmount;

        document.getElementById('subtotal').textContent = this.app.formatCurrency(subtotal);
        document.getElementById('taxAmount').textContent = this.app.formatCurrency(taxAmount);
        document.getElementById('total').textContent = this.app.formatCurrency(total);
    }

    clearCart() {
        this.cart = [];
        this.customer = null;
        document.getElementById('customerPhone').value = '';
        document.getElementById('customerInfo').classList.add('hidden');
        document.getElementById('discount').value = '0';
        this.updateCartDisplay();
    }

    async searchCustomer(phone) {
        if (!phone.trim()) return;

        try {
            const response = await this.app.apiCall(`api/customers.php?phone=${encodeURIComponent(phone)}`);
            this.customer = response.data;
            
            document.getElementById('customerInfo').innerHTML = `
                <strong>${this.customer.name}</strong><br>
                Phone: ${this.customer.phone}<br>
                ${this.customer.email ? `Email: ${this.customer.email}` : ''}
            `;
            document.getElementById('customerInfo').classList.remove('hidden');
            
            this.app.showToast('Customer found', 'success');
        } catch (error) {
            document.getElementById('customerInfo').classList.add('hidden');
            this.customer = null;
            this.app.showToast('Customer not found', 'warning');
        }
    }

    async checkout() {
        if (this.cart.length === 0) {
            this.app.showToast('Cart is empty', 'warning');
            return;
        }

        const paymentMethod = document.getElementById('paymentMethod').value;
        const discount = parseFloat(document.getElementById('discount').value) || 0;
        const customerPhone = document.getElementById('customerPhone').value;

        const saleData = {
            items: this.cart.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
                unit_price: item.price
            })),
            payment_method: paymentMethod,
            discount: discount,
            tax_rate: this.taxRate,
            notes: ''
        };

        // Add customer data if provided
        if (customerPhone.trim()) {
            saleData.customer = {
                name: this.customer?.name || '',
                phone: customerPhone,
                email: this.customer?.email || '',
                address: this.customer?.address || ''
            };
        }

        try {
            const response = await this.app.apiCall('api/sales.php', {
                method: 'POST',
                body: JSON.stringify(saleData)
            });

            this.app.showToast('Sale completed successfully!', 'success');
            
            // Show receipt
            this.showReceipt(response.data.sale_id);
            
            // Clear cart
            this.clearCart();
            
            // Reload products to update stock
            this.loadProducts();

        } catch (error) {
            console.error('Checkout failed:', error);
        }
    }

    async showReceipt(saleId) {
        try {
            const response = await this.app.apiCall(`api/sales.php?receipt=${saleId}`);
            const receiptData = response.data;
            
            document.getElementById('receiptContent').innerHTML = this.generateReceiptHTML(receiptData);
            this.app.showModal('receiptModal');
            
            // Setup print functionality
            document.getElementById('printReceiptBtn').onclick = () => {
                window.print();
            };
            
        } catch (error) {
            console.error('Failed to load receipt:', error);
        }
    }

    generateReceiptHTML(data) {
        const { settings, sale } = data;
        const saleDate = new Date(sale.sale_date);
        
        return `
            <div class="receipt-header">
                <h2>${settings.shop_name || 'Mobile Shop'}</h2>
                <p>${settings.shop_address || ''}</p>
                <p>Phone: ${settings.shop_phone || ''}</p>
                <p>Email: ${settings.shop_email || ''}</p>
            </div>
            
            <div class="receipt-info">
                <div>
                    <strong>Receipt #${sale.id}</strong><br>
                    Date: ${saleDate.toLocaleDateString()}<br>
                    Time: ${saleDate.toLocaleTimeString()}
                </div>
                <div>
                    ${sale.customer_name ? `Customer: ${sale.customer_name}<br>` : ''}
                    ${sale.customer_phone ? `Phone: ${sale.customer_phone}` : ''}
                </div>
            </div>
            
            <div class="receipt-items">
                <div style="border-bottom: 1px solid #333; padding-bottom: 0.5rem; margin-bottom: 0.5rem;">
                    <strong>Items:</strong>
                </div>
                ${sale.items.map(item => `
                    <div class="receipt-item">
                        <div>${item.product_name}</div>
                        <div>${item.quantity} x ${this.app.formatCurrency(item.unit_price)} = ${this.app.formatCurrency(item.total_price)}</div>
                    </div>
                `).join('')}
            </div>
            
            <div class="receipt-summary">
                <div class="receipt-item">
                    <span>Subtotal:</span>
                    <span>${this.app.formatCurrency(sale.total_amount)}</span>
                </div>
                ${sale.discount > 0 ? `
                    <div class="receipt-item">
                        <span>Discount:</span>
                        <span>-${this.app.formatCurrency(sale.discount)}</span>
                    </div>
                ` : ''}
                ${sale.tax_amount > 0 ? `
                    <div class="receipt-item">
                        <span>Tax:</span>
                        <span>${this.app.formatCurrency(sale.tax_amount)}</span>
                    </div>
                ` : ''}
                <div class="receipt-item receipt-total">
                    <span><strong>Total:</strong></span>
                    <span><strong>${this.app.formatCurrency(sale.final_amount)}</strong></span>
                </div>
                <div class="receipt-item">
                    <span>Payment Method:</span>
                    <span>${sale.payment_method.toUpperCase()}</span>
                </div>
            </div>
            
            <div class="text-center" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #333;">
                <p>Thank you for your business!</p>
                <p>Visit us again soon</p>
            </div>
        `;
    }
}

// Initialize POS manager when app is ready
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        window.posManager = new POSManager(window.posApp);
    }, 100);
});