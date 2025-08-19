// Inventory Management functionality
class InventoryManager {
    constructor(app) {
        this.app = app;
        this.products = [];
        this.filteredProducts = [];
        this.currentProduct = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadInventory();
    }

    setupEventListeners() {
        // Add product button
        document.getElementById('addProductBtn').addEventListener('click', () => {
            this.showProductModal();
        });

        // Product form submission
        document.getElementById('productForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveProduct();
        });

        // Search and filters
        const searchInput = document.getElementById('inventorySearch');
        searchInput.addEventListener('input', debounce(() => {
            this.filterProducts();
        }, 300));

        document.getElementById('categoryFilter').addEventListener('change', () => {
            this.filterProducts();
        });

        document.getElementById('lowStockBtn').addEventListener('click', () => {
            this.showLowStockItems();
        });

        // Modal close events
        document.getElementById('cancelProductBtn').addEventListener('click', () => {
            this.app.closeAllModals();
        });
    }

    async loadInventory() {
        try {
            const response = await this.app.apiCall('api/products.php');
            this.products = response.data || [];
            this.filteredProducts = [...this.products];
            this.displayInventory();
        } catch (error) {
            console.error('Failed to load inventory:', error);
        }
    }

    displayInventory() {
        const tbody = document.getElementById('inventoryTableBody');
        
        if (this.filteredProducts.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">No products found</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.filteredProducts.map(product => {
            const stockStatus = this.getStockStatus(product);
            return `
                <tr>
                    <td>
                        <strong>${product.name}</strong>
                        ${product.barcode ? `<br><small>Barcode: ${product.barcode}</small>` : ''}
                    </td>
                    <td>${product.brand}</td>
                    <td>
                        <span class="status-badge status-${product.category}">
                            ${product.category.charAt(0).toUpperCase() + product.category.slice(1)}
                        </span>
                    </td>
                    <td>
                        <strong>${this.app.formatCurrency(product.price)}</strong>
                        <br><small>Cost: ${this.app.formatCurrency(product.cost_price)}</small>
                    </td>
                    <td>
                        <strong class="${stockStatus.class}">${product.stock_quantity}</strong>
                        <br><small>Min: ${product.min_stock_level}</small>
                    </td>
                    <td>
                        <span class="status-badge ${stockStatus.class}">
                            ${stockStatus.text}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-secondary" onclick="inventoryManager.editProduct(${product.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="inventoryManager.deleteProduct(${product.id})" 
                                ${product.stock_quantity > 0 ? 'disabled title="Cannot delete product with stock"' : ''}>
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    getStockStatus(product) {
        if (product.stock_quantity <= 0) {
            return { class: 'stock-out', text: 'Out of Stock' };
        } else if (product.stock_quantity <= product.min_stock_level) {
            return { class: 'stock-low', text: 'Low Stock' };
        } else {
            return { class: 'stock-good', text: 'In Stock' };
        }
    }

    filterProducts() {
        const searchTerm = document.getElementById('inventorySearch').value.toLowerCase();
        const category = document.getElementById('categoryFilter').value;

        this.filteredProducts = this.products.filter(product => {
            const matchesSearch = !searchTerm || 
                product.name.toLowerCase().includes(searchTerm) ||
                product.brand.toLowerCase().includes(searchTerm) ||
                product.model.toLowerCase().includes(searchTerm) ||
                (product.barcode && product.barcode.toLowerCase().includes(searchTerm));

            const matchesCategory = !category || product.category === category;

            return matchesSearch && matchesCategory;
        });

        this.displayInventory();
    }

    showLowStockItems() {
        this.filteredProducts = this.products.filter(product => 
            product.stock_quantity <= product.min_stock_level
        );
        this.displayInventory();
        
        // Clear filters
        document.getElementById('inventorySearch').value = '';
        document.getElementById('categoryFilter').value = '';
    }

    showProductModal(product = null) {
        this.currentProduct = product;
        const modal = document.getElementById('productModal');
        const title = document.getElementById('productModalTitle');
        const form = document.getElementById('productForm');

        if (product) {
            title.textContent = 'Edit Product';
            this.populateProductForm(product);
        } else {
            title.textContent = 'Add Product';
            form.reset();
            document.getElementById('productMinStock').value = '5';
        }

        this.app.showModal('productModal');
    }

    populateProductForm(product) {
        document.getElementById('productName').value = product.name;
        document.getElementById('productBrand').value = product.brand;
        document.getElementById('productModel').value = product.model;
        document.getElementById('productCategory').value = product.category;
        document.getElementById('productPrice').value = product.price;
        document.getElementById('productCostPrice').value = product.cost_price;
        document.getElementById('productStock').value = product.stock_quantity;
        document.getElementById('productMinStock').value = product.min_stock_level;
        document.getElementById('productBarcode').value = product.barcode || '';
        document.getElementById('productDescription').value = product.description || '';
    }

    async saveProduct() {
        const form = document.getElementById('productForm');
        const formData = new FormData(form);
        const productData = {};

        // Convert FormData to object
        for (let [key, value] of formData.entries()) {
            productData[key] = value;
        }

        // Validate required fields
        const required = ['name', 'brand', 'model', 'category', 'price', 'cost_price'];
        for (let field of required) {
            if (!productData[field]) {
                this.app.showToast(`${field.replace('_', ' ')} is required`, 'error');
                return;
            }
        }

        // Convert numeric fields
        productData.price = parseFloat(productData.price);
        productData.cost_price = parseFloat(productData.cost_price);
        productData.stock_quantity = parseInt(productData.stock_quantity) || 0;
        productData.min_stock_level = parseInt(productData.min_stock_level) || 5;

        try {
            let response;
            if (this.currentProduct) {
                // Update existing product
                productData.id = this.currentProduct.id;
                response = await this.app.apiCall('api/products.php', {
                    method: 'PUT',
                    body: JSON.stringify(productData)
                });
                this.app.showToast('Product updated successfully', 'success');
            } else {
                // Create new product
                response = await this.app.apiCall('api/products.php', {
                    method: 'POST',
                    body: JSON.stringify(productData)
                });
                this.app.showToast('Product added successfully', 'success');
            }

            this.app.closeAllModals();
            this.loadInventory();

        } catch (error) {
            console.error('Failed to save product:', error);
        }
    }

    editProduct(productId) {
        const product = this.products.find(p => p.id === productId);
        if (product) {
            this.showProductModal(product);
        }
    }

    async deleteProduct(productId) {
        const product = this.products.find(p => p.id === productId);
        if (!product) return;

        if (product.stock_quantity > 0) {
            this.app.showToast('Cannot delete product with existing stock', 'error');
            return;
        }

        if (!confirm(`Are you sure you want to delete "${product.name}"?`)) {
            return;
        }

        try {
            await this.app.apiCall(`api/products.php?id=${productId}`, {
                method: 'DELETE'
            });

            this.app.showToast('Product deleted successfully', 'success');
            this.loadInventory();
        } catch (error) {
            console.error('Failed to delete product:', error);
        }
    }
}

// Initialize inventory manager when app is ready
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        window.inventoryManager = new InventoryManager(window.posApp);
    }, 100);
});