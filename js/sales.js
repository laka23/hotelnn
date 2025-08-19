// Sales History functionality
class SalesManager {
    constructor(app) {
        this.app = app;
        this.sales = [];
        this.filteredSales = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadSales();
    }

    setupEventListeners() {
        // Date filters
        document.getElementById('filterSalesBtn').addEventListener('click', () => {
            this.filterSalesByDate();
        });

        // Set default date range (last 30 days)
        const today = new Date();
        const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
        
        document.getElementById('salesToDate').value = today.toISOString().split('T')[0];
        document.getElementById('salesFromDate').value = thirtyDaysAgo.toISOString().split('T')[0];
    }

    async loadSales() {
        try {
            const response = await this.app.apiCall('api/sales.php');
            this.sales = response.data || [];
            this.filteredSales = [...this.sales];
            this.displaySales();
        } catch (error) {
            console.error('Failed to load sales:', error);
        }
    }

    displaySales() {
        const tbody = document.getElementById('salesTableBody');
        
        if (this.filteredSales.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">No sales found</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.filteredSales.map(sale => `
            <tr>
                <td>
                    <strong>${this.app.formatDate(sale.sale_date)}</strong>
                    <br><small>${new Date(sale.sale_date).toLocaleTimeString()}</small>
                </td>
                <td>
                    ${sale.customer_name ? `
                        <strong>${sale.customer_name}</strong>
                        <br><small>${sale.customer_phone}</small>
                    ` : '<em>Walk-in Customer</em>'}
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-secondary" onclick="salesManager.viewSaleItems(${sale.id})" title="View Items">
                        <i class="fas fa-list"></i> Items
                    </button>
                </td>
                <td class="text-right">
                    <strong>${this.app.formatCurrency(sale.final_amount)}</strong>
                    ${sale.discount > 0 ? `<br><small>Discount: ${this.app.formatCurrency(sale.discount)}</small>` : ''}
                    ${sale.tax_amount > 0 ? `<br><small>Tax: ${this.app.formatCurrency(sale.tax_amount)}</small>` : ''}
                </td>
                <td>
                    <span class="status-badge status-${sale.payment_method}">
                        ${sale.payment_method.toUpperCase()}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="salesManager.viewSale(${sale.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="salesManager.printReceipt(${sale.id})" title="Print Receipt">
                        <i class="fas fa-print"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    filterSalesByDate() {
        const fromDate = document.getElementById('salesFromDate').value;
        const toDate = document.getElementById('salesToDate').value;

        if (!fromDate || !toDate) {
            this.app.showToast('Please select both from and to dates', 'warning');
            return;
        }

        this.filteredSales = this.sales.filter(sale => {
            const saleDate = new Date(sale.sale_date).toISOString().split('T')[0];
            return saleDate >= fromDate && saleDate <= toDate;
        });

        this.displaySales();
        
        // Show summary
        const totalSales = this.filteredSales.reduce((sum, sale) => sum + parseFloat(sale.final_amount), 0);
        this.app.showToast(`Found ${this.filteredSales.length} sales totaling ${this.app.formatCurrency(totalSales)}`, 'success');
    }

    async viewSale(saleId) {
        try {
            const response = await this.app.apiCall(`api/sales.php?id=${saleId}`);
            const sale = response.data;
            this.showSaleDetails(sale);
        } catch (error) {
            console.error('Failed to load sale details:', error);
        }
    }

    showSaleDetails(sale) {
        const detailsHTML = `
            <div class="modal active" id="saleDetailsModal">
                <div class="modal-content" style="max-width: 700px;">
                    <div class="modal-header">
                        <h3>Sale Details - #${sale.id}</h3>
                        <span class="close" onclick="this.closest('.modal').classList.remove('active')">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Sale Date:</label>
                                <p><strong>${this.app.formatDateTime(sale.sale_date)}</strong></p>
                            </div>
                            <div class="form-group">
                                <label>Payment Method:</label>
                                <span class="status-badge status-${sale.payment_method}">${sale.payment_method.toUpperCase()}</span>
                            </div>
                            ${sale.customer_name ? `
                                <div class="form-group">
                                    <label>Customer:</label>
                                    <p><strong>${sale.customer_name}</strong><br>${sale.customer_phone}</p>
                                </div>
                                <div class="form-group">
                                    <label>Customer Email:</label>
                                    <p>${sale.customer_email || '-'}</p>
                                </div>
                            ` : ''}
                            ${sale.notes ? `
                                <div class="form-group full-width">
                                    <label>Notes:</label>
                                    <p>${sale.notes}</p>
                                </div>
                            ` : ''}
                        </div>

                        <h4>Items Sold</h4>
                        <table class="data-table" style="margin-bottom: 2rem;">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${sale.items.map(item => `
                                    <tr>
                                        <td>
                                            <strong>${item.product_name}</strong>
                                            <br><small>${item.brand} ${item.model}</small>
                                        </td>
                                        <td class="text-center">${item.quantity}</td>
                                        <td class="text-right">${this.app.formatCurrency(item.unit_price)}</td>
                                        <td class="text-right"><strong>${this.app.formatCurrency(item.total_price)}</strong></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Subtotal:</label>
                                <p class="text-right"><strong>${this.app.formatCurrency(sale.total_amount)}</strong></p>
                            </div>
                            <div class="form-group">
                                <label>Discount:</label>
                                <p class="text-right">${this.app.formatCurrency(sale.discount)}</p>
                            </div>
                            <div class="form-group">
                                <label>Tax:</label>
                                <p class="text-right">${this.app.formatCurrency(sale.tax_amount)}</p>
                            </div>
                            <div class="form-group">
                                <label>Final Total:</label>
                                <p class="text-right"><strong style="font-size: 1.2rem; color: #27ae60;">${this.app.formatCurrency(sale.final_amount)}</strong></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="salesManager.printReceipt(${sale.id})">
                            <i class="fas fa-print"></i> Print Receipt
                        </button>
                        <button class="btn btn-secondary" onclick="this.closest('.modal').classList.remove('active')">Close</button>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing details modal if any
        const existingModal = document.getElementById('saleDetailsModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add new modal to body
        document.body.insertAdjacentHTML('beforeend', detailsHTML);
    }

    async viewSaleItems(saleId) {
        try {
            const response = await this.app.apiCall(`api/sales.php?id=${saleId}`);
            const sale = response.data;
            
            const itemsHTML = `
                <div class="modal active" id="saleItemsModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Sale #${sale.id} - Items</h3>
                            <span class="close" onclick="this.closest('.modal').classList.remove('active')">&times;</span>
                        </div>
                        <div class="modal-body">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${sale.items.map(item => `
                                        <tr>
                                            <td>
                                                <strong>${item.product_name}</strong>
                                                <br><small>${item.brand} ${item.model}</small>
                                            </td>
                                            <td class="text-center">${item.quantity}</td>
                                            <td class="text-right">${this.app.formatCurrency(item.unit_price)}</td>
                                            <td class="text-right"><strong>${this.app.formatCurrency(item.total_price)}</strong></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3">Total</th>
                                        <th class="text-right">${this.app.formatCurrency(sale.total_amount)}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" onclick="this.closest('.modal').classList.remove('active')">Close</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('saleItemsModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add new modal to body
            document.body.insertAdjacentHTML('beforeend', itemsHTML);
            
        } catch (error) {
            console.error('Failed to load sale items:', error);
        }
    }

    async printReceipt(saleId) {
        try {
            const response = await this.app.apiCall(`api/sales.php?receipt=${saleId}`);
            const receiptData = response.data;
            
            // Generate receipt content
            const receiptContent = this.generateReceiptHTML(receiptData);
            
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Receipt #${saleId}</title>
                    <style>
                        body { font-family: 'Courier New', monospace; margin: 20px; }
                        .receipt-header { text-align: center; margin-bottom: 1rem; border-bottom: 2px solid #333; padding-bottom: 1rem; }
                        .receipt-info { display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.9rem; }
                        .receipt-items { margin-bottom: 1rem; }
                        .receipt-item { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem; }
                        .receipt-summary { border-top: 1px solid #333; padding-top: 0.5rem; }
                        .receipt-total { font-weight: bold; font-size: 1.1rem; border-top: 2px solid #333; padding-top: 0.5rem; margin-top: 0.5rem; }
                        .text-center { text-align: center; }
                        @media print {
                            body { margin: 0; }
                        }
                    </style>
                </head>
                <body>
                    ${receiptContent}
                    <script>
                        window.onload = function() {
                            window.print();
                            window.onafterprint = function() {
                                window.close();
                            }
                        }
                    </script>
                </body>
                </html>
            `);
            printWindow.document.close();
            
        } catch (error) {
            console.error('Failed to print receipt:', error);
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

// Initialize sales manager when app is ready
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        window.salesManager = new SalesManager(window.posApp);
    }, 100);
});