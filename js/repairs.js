// Repair Management functionality
class RepairManager {
    constructor(app) {
        this.app = app;
        this.repairs = [];
        this.filteredRepairs = [];
        this.currentRepair = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadRepairs();
    }

    setupEventListeners() {
        // Add repair button
        document.getElementById('addRepairBtn').addEventListener('click', () => {
            this.showRepairModal();
        });

        // Repair form submission
        document.getElementById('repairForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveRepair();
        });

        // Filters
        document.getElementById('statusFilter').addEventListener('change', () => {
            this.filterRepairs();
        });

        document.getElementById('priorityFilter').addEventListener('change', () => {
            this.filterRepairs();
        });

        // Modal close events
        document.getElementById('cancelRepairBtn').addEventListener('click', () => {
            this.app.closeAllModals();
        });

        // Customer phone auto-search
        document.getElementById('repairCustomerPhone').addEventListener('blur', (e) => {
            this.searchCustomerForRepair(e.target.value);
        });
    }

    async loadRepairs() {
        try {
            const response = await this.app.apiCall('api/repairs.php');
            this.repairs = response.data || [];
            this.filteredRepairs = [...this.repairs];
            this.displayRepairs();
        } catch (error) {
            console.error('Failed to load repairs:', error);
        }
    }

    displayRepairs() {
        const grid = document.getElementById('repairsGrid');
        
        if (this.filteredRepairs.length === 0) {
            grid.innerHTML = `
                <div class="empty-cart">
                    <i class="fas fa-tools"></i>
                    <p>No repair jobs found</p>
                </div>
            `;
            return;
        }

        grid.innerHTML = this.filteredRepairs.map(repair => {
            const statusClass = `status-${repair.status}`;
            const priorityClass = `priority-${repair.priority}`;
            
            return `
                <div class="repair-card ${priorityClass}">
                    <div class="repair-card-header">
                        <div>
                            <h4>Repair #${repair.id}</h4>
                            <p class="repair-card-info">
                                <strong>${repair.customer_name}</strong><br>
                                ${repair.customer_phone}<br>
                                ${repair.device_brand} ${repair.device_model}
                            </p>
                        </div>
                        <div>
                            <span class="status-badge ${statusClass}">
                                ${repair.status.replace('_', ' ')}
                            </span>
                            <span class="status-badge ${priorityClass}">
                                ${repair.priority}
                            </span>
                        </div>
                    </div>
                    
                    <div class="repair-card-info">
                        <strong>Issue:</strong> ${repair.issue_description}<br>
                        ${repair.total_cost ? `<strong>Cost:</strong> ${this.app.formatCurrency(repair.total_cost)}<br>` : ''}
                        <strong>Created:</strong> ${this.app.formatDate(repair.created_at)}
                        ${repair.estimated_completion ? `<br><strong>Est. Completion:</strong> ${this.app.formatDate(repair.estimated_completion)}` : ''}
                    </div>
                    
                    <div class="repair-card-footer">
                        <div>
                            ${this.getStatusActions(repair)}
                        </div>
                        <div>
                            <button class="btn btn-sm btn-secondary" onclick="repairManager.viewRepair(${repair.id})">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="repairManager.editRepair(${repair.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    getStatusActions(repair) {
        const actions = [];
        
        switch(repair.status) {
            case 'received':
                actions.push(`<button class="btn btn-sm btn-primary" onclick="repairManager.updateStatus(${repair.id}, 'diagnosed')">Diagnose</button>`);
                break;
            case 'diagnosed':
                actions.push(`<button class="btn btn-sm btn-primary" onclick="repairManager.updateStatus(${repair.id}, 'in_progress')">Start Work</button>`);
                break;
            case 'in_progress':
                actions.push(`<button class="btn btn-sm btn-success" onclick="repairManager.updateStatus(${repair.id}, 'completed')">Complete</button>`);
                break;
            case 'completed':
                actions.push(`<button class="btn btn-sm btn-success" onclick="repairManager.updateStatus(${repair.id}, 'delivered')">Mark Delivered</button>`);
                break;
        }
        
        if (repair.status !== 'delivered' && repair.status !== 'cancelled') {
            actions.push(`<button class="btn btn-sm btn-danger" onclick="repairManager.updateStatus(${repair.id}, 'cancelled')">Cancel</button>`);
        }
        
        return actions.join(' ');
    }

    filterRepairs() {
        const status = document.getElementById('statusFilter').value;
        const priority = document.getElementById('priorityFilter').value;

        this.filteredRepairs = this.repairs.filter(repair => {
            const matchesStatus = !status || repair.status === status;
            const matchesPriority = !priority || repair.priority === priority;
            return matchesStatus && matchesPriority;
        });

        this.displayRepairs();
    }

    showRepairModal(repair = null) {
        this.currentRepair = repair;
        const modal = document.getElementById('repairModal');
        const title = document.getElementById('repairModalTitle');
        const form = document.getElementById('repairForm');

        if (repair) {
            title.textContent = 'Edit Repair Job';
            this.populateRepairForm(repair);
        } else {
            title.textContent = 'New Repair Job';
            form.reset();
            document.getElementById('repairPriority').value = 'medium';
        }

        this.app.showModal('repairModal');
    }

    populateRepairForm(repair) {
        document.getElementById('repairCustomerName').value = repair.customer_name;
        document.getElementById('repairCustomerPhone').value = repair.customer_phone;
        document.getElementById('repairDeviceBrand').value = repair.device_brand;
        document.getElementById('repairDeviceModel').value = repair.device_model;
        document.getElementById('repairPriority').value = repair.priority;
        document.getElementById('repairCost').value = repair.repair_cost || '';
        document.getElementById('repairIssue').value = repair.issue_description;
        document.getElementById('repairNotes').value = repair.customer_notes || '';
    }

    async searchCustomerForRepair(phone) {
        if (!phone.trim()) return;

        try {
            const response = await this.app.apiCall(`api/customers.php?phone=${encodeURIComponent(phone)}`);
            const customer = response.data;
            document.getElementById('repairCustomerName').value = customer.name;
        } catch (error) {
            // Customer not found, that's okay for new customers
        }
    }

    async saveRepair() {
        const form = document.getElementById('repairForm');
        const formData = new FormData(form);
        const repairData = {
            customer: {
                name: formData.get('customer_name'),
                phone: formData.get('customer_phone')
            },
            device_brand: formData.get('device_brand'),
            device_model: formData.get('device_model'),
            issue_description: formData.get('issue_description'),
            priority: formData.get('priority'),
            repair_cost: parseFloat(formData.get('repair_cost')) || 0,
            customer_notes: formData.get('customer_notes')
        };

        // Validate required fields
        if (!repairData.customer.name || !repairData.customer.phone || 
            !repairData.device_brand || !repairData.device_model || 
            !repairData.issue_description) {
            this.app.showToast('Please fill in all required fields', 'error');
            return;
        }

        try {
            let response;
            if (this.currentRepair) {
                // Update existing repair
                repairData.id = this.currentRepair.id;
                response = await this.app.apiCall('api/repairs.php', {
                    method: 'PUT',
                    body: JSON.stringify(repairData)
                });
                this.app.showToast('Repair job updated successfully', 'success');
            } else {
                // Create new repair
                response = await this.app.apiCall('api/repairs.php', {
                    method: 'POST',
                    body: JSON.stringify(repairData)
                });
                this.app.showToast('Repair job created successfully', 'success');
            }

            this.app.closeAllModals();
            this.loadRepairs();

        } catch (error) {
            console.error('Failed to save repair:', error);
        }
    }

    async updateStatus(repairId, newStatus) {
        const repair = this.repairs.find(r => r.id === repairId);
        if (!repair) return;

        const confirmMessages = {
            'diagnosed': 'Mark this repair as diagnosed?',
            'in_progress': 'Start working on this repair?',
            'completed': 'Mark this repair as completed?',
            'delivered': 'Mark this repair as delivered?',
            'cancelled': 'Cancel this repair job?'
        };

        if (!confirm(confirmMessages[newStatus] || 'Update repair status?')) {
            return;
        }

        try {
            const updateData = {
                id: repairId,
                status: newStatus,
                device_brand: repair.device_brand,
                device_model: repair.device_model,
                issue_description: repair.issue_description,
                priority: repair.priority,
                repair_cost: repair.repair_cost || 0,
                customer_notes: repair.customer_notes || ''
            };

            await this.app.apiCall('api/repairs.php', {
                method: 'PUT',
                body: JSON.stringify(updateData)
            });

            this.app.showToast(`Repair status updated to ${newStatus.replace('_', ' ')}`, 'success');
            this.loadRepairs();

        } catch (error) {
            console.error('Failed to update repair status:', error);
        }
    }

    async viewRepair(repairId) {
        try {
            const response = await this.app.apiCall(`api/repairs.php?id=${repairId}`);
            const repair = response.data;
            
            // Show repair details in a modal or expand the card
            this.showRepairDetails(repair);
        } catch (error) {
            console.error('Failed to load repair details:', error);
        }
    }

    showRepairDetails(repair) {
        // Create a detailed view modal or expand the existing card
        const detailsHTML = `
            <div class="modal active" id="repairDetailsModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Repair Job #${repair.id} Details</h3>
                        <span class="close" onclick="this.closest('.modal').classList.remove('active')">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Customer:</label>
                                <p>${repair.customer_name} (${repair.customer_phone})</p>
                            </div>
                            <div class="form-group">
                                <label>Device:</label>
                                <p>${repair.device_brand} ${repair.device_model}</p>
                            </div>
                            <div class="form-group">
                                <label>Status:</label>
                                <span class="status-badge status-${repair.status}">${repair.status.replace('_', ' ')}</span>
                            </div>
                            <div class="form-group">
                                <label>Priority:</label>
                                <span class="status-badge priority-${repair.priority}">${repair.priority}</span>
                            </div>
                            <div class="form-group full-width">
                                <label>Issue Description:</label>
                                <p>${repair.issue_description}</p>
                            </div>
                            ${repair.technician_notes ? `
                                <div class="form-group full-width">
                                    <label>Technician Notes:</label>
                                    <p>${repair.technician_notes}</p>
                                </div>
                            ` : ''}
                            ${repair.customer_notes ? `
                                <div class="form-group full-width">
                                    <label>Customer Notes:</label>
                                    <p>${repair.customer_notes}</p>
                                </div>
                            ` : ''}
                            <div class="form-group">
                                <label>Created:</label>
                                <p>${this.app.formatDateTime(repair.created_at)}</p>
                            </div>
                            ${repair.estimated_completion ? `
                                <div class="form-group">
                                    <label>Est. Completion:</label>
                                    <p>${this.app.formatDate(repair.estimated_completion)}</p>
                                </div>
                            ` : ''}
                            ${repair.total_cost ? `
                                <div class="form-group">
                                    <label>Total Cost:</label>
                                    <p>${this.app.formatCurrency(repair.total_cost)}</p>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="repairManager.editRepair(${repair.id}); this.closest('.modal').classList.remove('active')">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-secondary" onclick="this.closest('.modal').classList.remove('active')">Close</button>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing details modal if any
        const existingModal = document.getElementById('repairDetailsModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add new modal to body
        document.body.insertAdjacentHTML('beforeend', detailsHTML);
    }

    editRepair(repairId) {
        const repair = this.repairs.find(r => r.id === repairId);
        if (repair) {
            this.showRepairModal(repair);
        }
    }
}

// Initialize repair manager when app is ready
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        window.repairManager = new RepairManager(window.posApp);
    }, 100);
});