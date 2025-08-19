// Mobile Shop POS System - Main Application JavaScript
class MobileShopPOS {
    constructor() {
        this.currentSection = 'pos';
        this.cart = [];
        this.customer = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupNavigation();
        this.showLoading(false);
        
        // Initialize sections
        this.loadProducts();
        this.loadCustomers();
        this.loadSales();
        this.loadRepairs();
        this.loadReports();
    }

    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.currentTarget.dataset.section;
                this.showSection(section);
            });
        });

        // Modal close buttons
        document.querySelectorAll('.close, [id$="CancelBtn"], [id$="closeBtn"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.closeAllModals();
            });
        });

        // Click outside modal to close
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeAllModals();
                }
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
    }

    setupNavigation() {
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.addEventListener('click', () => {
                navItems.forEach(nav => nav.classList.remove('active'));
                item.classList.add('active');
            });
        });
    }

    showSection(sectionName) {
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });

        // Show target section
        document.getElementById(sectionName).classList.add('active');
        this.currentSection = sectionName;

        // Update navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector(`[data-section="${sectionName}"]`).parentElement.classList.add('active');

        // Load section-specific data
        switch(sectionName) {
            case 'pos':
                this.loadProducts();
                break;
            case 'inventory':
                this.loadInventory();
                break;
            case 'repairs':
                this.loadRepairs();
                break;
            case 'customers':
                this.loadCustomers();
                break;
            case 'sales':
                this.loadSales();
                break;
            case 'reports':
                this.loadReports();
                break;
        }
    }

    // API Helper Methods
    async apiCall(url, options = {}) {
        try {
            this.showLoading(true);
            
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                },
            };

            const response = await fetch(url, { ...defaultOptions, ...options });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'API request failed');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            this.showToast(error.message, 'error');
            throw error;
        } finally {
            this.showLoading(false);
        }
    }

    // Loading and UI Helpers
    showLoading(show = true) {
        const overlay = document.getElementById('loadingOverlay');
        if (show) {
            overlay.classList.remove('hidden');
        } else {
            overlay.classList.add('hidden');
        }
    }

    showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div>${message}</div>
        `;
        
        container.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    showModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('active');
        });
    }

    // Format currency
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    // Format date
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString();
    }

    // Format datetime
    formatDateTime(dateString) {
        return new Date(dateString).toLocaleString();
    }

    // Placeholder methods for section-specific functionality
    async loadProducts() {
        // Implemented in pos.js
    }

    async loadInventory() {
        // Implemented in inventory.js
    }

    async loadRepairs() {
        // Implemented in repairs.js
    }

    async loadCustomers() {
        // Implemented in customers.js
    }

    async loadSales() {
        // Implemented in sales.js
    }

    async loadReports() {
        // Implemented in reports.js
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.posApp = new MobileShopPOS();
});

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[\+]?[1-9][\d]{0,15}$/;
    return re.test(phone.replace(/[\s\-\(\)]/g, ''));
}