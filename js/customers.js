// Customer Management functionality
class CustomerManager {
    constructor(app) {
        this.app = app;
        this.customers = [];
        this.filteredCustomers = [];
        this.currentCustomer = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadCustomers();
    }

    setupEventListeners() {
        // Add customer button
        document.getElementById('addCustomerBtn').addEventListener('click', () => {
            this.showCustomerModal();
        });

        // Customer form submission
        document.getElementById('customerForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveCustomer();
        });

        // Search
        const searchInput = document.getElementById('customerSearchInput');
        searchInput.addEventListener('input', debounce(() => {
            this.searchCustomers(searchInput.value);
        }, 300));

        // Modal close events
        document.getElementById('cancelCustomerBtn').addEventListener('click', () => {
            this.app.closeAllModals();
        });
    }

    async loadCustomers() {
        try {
            const response = await this.app.apiCall('api/customers.php');
            this.customers = response.data || [];
            this.filteredCustomers = [...this.customers];
            this.displayCustomers();
        } catch (error) {
            console.error('Failed to load customers:', error);
        }
    }

    displayCustomers() {
        const tbody = document.getElementById('customersTableBody');
        
        if (this.filteredCustomers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">No customers found</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.filteredCustomers.map(customer => `
            <tr>
                <td>
                    <strong>${customer.name}</strong>
                    ${customer.address ? `<br><small>${customer.address}</small>` : ''}
                </td>
                <td>${customer.phone}</td>
                <td>${customer.email || '-'}</td>
                <td class="text-center">${customer.total_sales || 0}</td>
                <td class="text-center">${customer.total_repairs || 0}</td>
                <td class="text-right">
                    <strong>${this.app.formatCurrency(customer.total_spent || 0)}</strong>
                </td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="customerManager.viewCustomer(${customer.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="customerManager.editCustomer(${customer.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="customerManager.deleteCustomer(${customer.id})" 
                            ${(customer.total_sales > 0 || customer.total_repairs > 0) ? 'disabled title="Cannot delete customer with history"' : 'title="Delete"'}>
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    async searchCustomers(query) {
        try {
            if (query.trim() === '') {
                this.filteredCustomers = [...this.customers];
                this.displayCustomers();
                return;
            }

            const response = await this.app.apiCall(`api/customers.php?search=${encodeURIComponent(query)}`);
            this.filteredCustomers = response.data || [];
            this.displayCustomers();
        } catch (error) {
            console.error('Failed to search customers:', error);
        }
    }

    showCustomerModal(customer = null) {
        this.currentCustomer = customer;
        const modal = document.getElementById('customerModal');
        const title = document.getElementById('customerModalTitle');
        const form = document.getElementById('customerForm');

        if (customer) {
            title.textContent = 'Edit Customer';
            this.populateCustomerForm(customer);
        } else {
            title.textContent = 'Add Customer';
            form.reset();
        }

        this.app.showModal('customerModal');
    }

    populateCustomerForm(customer) {
        document.getElementById('customerName').value = customer.name;
        document.getElementById('customerPhone').value = customer.phone;
        document.getElementById('customerEmail').value = customer.email || '';
        document.getElementById('customerAddress').value = customer.address || '';
    }

    async saveCustomer() {
        const form = document.getElementById('customerForm');
        const formData = new FormData(form);
        const customerData = {};

        // Convert FormData to object
        for (let [key, value] of formData.entries()) {
            customerData[key] = value.trim();
        }

        // Validate required fields
        if (!customerData.name) {
            this.app.showToast('Customer name is required', 'error');
            return;
        }

        if (!customerData.phone) {
            this.app.showToast('Customer phone is required', 'error');
            return;
        }

        if (!validatePhone(customerData.phone)) {
            this.app.showToast('Please enter a valid phone number', 'error');
            return;
        }

        if (customerData.email && !validateEmail(customerData.email)) {
            this.app.showToast('Please enter a valid email address', 'error');
            return;
        }

        try {
            let response;
            if (this.currentCustomer) {
                // Update existing customer
                customerData.id = this.currentCustomer.id;
                response = await this.app.apiCall('api/customers.php', {
                    method: 'PUT',
                    body: JSON.stringify(customerData)
                });
                this.app.showToast('Customer updated successfully', 'success');
            } else {
                // Create new customer
                response = await this.app.apiCall('api/customers.php', {
                    method: 'POST',
                    body: JSON.stringify(customerData)
                });
                this.app.showToast('Customer added successfully', 'success');
            }

            this.app.closeAllModals();
            this.loadCustomers();

        } catch (error) {
            console.error('Failed to save customer:', error);
        }
    }

    async viewCustomer(customerId) {
        try {
            const response = await this.app.apiCall(`api/customers.php?id=${customerId}`);
            const customer = response.data;
            this.showCustomerDetails(customer);
        } catch (error) {
            console.error('Failed to load customer details:', error);
        }
    }

    showCustomerDetails(customer) {
        const detailsHTML = `
            <div class="modal active" id="customerDetailsModal">
                <div class="modal-content" style="max-width: 800px;">
                    <div class="modal-header">
                        <h3>Customer Details - ${customer.name}</h3>
                        <span class="close" onclick="this.closest('.modal').classList.remove('active')">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Name:</label>
                                <p><strong>${customer.name}</strong></p>
                            </div>
                            <div class="form-group">
                                <label>Phone:</label>
                                <p>${customer.phone}</p>
                            </div>
                            <div class="form-group">
                                <label>Email:</label>
                                <p>${customer.email || '-'}</p>
                            </div>
                            <div class="form-group">
                                <label>Customer Since:</label>
                                <p>${this.app.formatDate(customer.created_at)}</p>
                            </div>
                            <div class="form-group full-width">
                                <label>Address:</label>
                                <p>${customer.address || '-'}</p>
                            </div>
                        </div>

                        <div class="reports-grid" style="margin: 2rem 0;">
                            <div class="report-card">
                                <h3>Total Sales</h3>
                                <div class="report-value">${customer.total_sales || 0}</div>
                            </div>
                            <div class="report-card">
                                <h3>Total Repairs</h3>
                                <div class="report-value">${customer.total_repairs || 0}</div>
                            </div>
                            <div class="report-card">
                                <h3>Total Spent</h3>
                                <div class="report-value">${this.app.formatCurrency(customer.total_spent || 0)}</div>
                            </div>
                        </div>

                        ${customer.recent_sales && customer.recent_sales.length > 0 ? `
                            <h4>Recent Sales</h4>
                            <table class="data-table" style="margin-bottom: 2rem;">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Final Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${customer.recent_sales.map(sale => `
                                        <tr>
                                            <td>${this.app.formatDate(sale.sale_date)}</td>
                                            <td>${this.app.formatCurrency(sale.total_amount)}</td>
                                            <td><strong>${this.app.formatCurrency(sale.final_amount)}</strong></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : ''}

                        ${customer.recent_repairs && customer.recent_repairs.length > 0 ? `
                            <h4>Recent Repairs</h4>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Device</th>
                                        <th>Status</th>
                                        <th>Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${customer.recent_repairs.map(repair => `
                                        <tr>
                                            <td>${this.app.formatDate(repair.created_at)}</td>
                                            <td>${repair.device_brand} ${repair.device_model}</td>
                                            <td><span class="status-badge status-${repair.status}">${repair.status.replace('_', ' ')}</span></td>
                                            <td>${repair.total_cost ? this.app.formatCurrency(repair.total_cost) : '-'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="customerManager.editCustomer(${customer.id}); this.closest('.modal').classList.remove('active')">
                            <i class="fas fa-edit"></i> Edit Customer
                        </button>
                        <button class="btn btn-secondary" onclick="this.closest('.modal').classList.remove('active')">Close</button>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing details modal if any
        const existingModal = document.getElementById('customerDetailsModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add new modal to body
        document.body.insertAdjacentHTML('beforeend', detailsHTML);
    }

    editCustomer(customerId) {
        const customer = this.customers.find(c => c.id === customerId);
        if (customer) {
            this.showCustomerModal(customer);
        }
    }

    async deleteCustomer(customerId) {
        const customer = this.customers.find(c => c.id === customerId);
        if (!customer) return;

        if (customer.total_sales > 0 || customer.total_repairs > 0) {
            this.app.showToast('Cannot delete customer with existing sales or repairs', 'error');
            return;
        }

        if (!confirm(`Are you sure you want to delete customer "${customer.name}"?`)) {
            return;
        }

        try {
            await this.app.apiCall(`api/customers.php?id=${customerId}`, {
                method: 'DELETE'
            });

            this.app.showToast('Customer deleted successfully', 'success');
            this.loadCustomers();
        } catch (error) {
            console.error('Failed to delete customer:', error);
        }
    }
}

// Initialize customer manager when app is ready
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        window.customerManager = new CustomerManager(window.posApp);
    }, 100);
});