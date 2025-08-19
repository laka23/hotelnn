// Reports and Analytics functionality
class ReportsManager {
    constructor(app) {
        this.app = app;
        this.init();
    }

    init() {
        this.loadReports();
        // Refresh reports every 5 minutes
        setInterval(() => {
            this.loadReports();
        }, 300000);
    }

    async loadReports() {
        try {
            await Promise.all([
                this.loadDailySales(),
                this.loadMonthlySales(),
                this.loadActiveRepairs(),
                this.loadLowStockCount()
            ]);
        } catch (error) {
            console.error('Failed to load reports:', error);
        }
    }

    async loadDailySales() {
        try {
            const response = await this.app.apiCall('api/sales.php');
            const sales = response.data || [];
            
            const today = new Date().toISOString().split('T')[0];
            const todaySales = sales.filter(sale => {
                const saleDate = new Date(sale.sale_date).toISOString().split('T')[0];
                return saleDate === today;
            });
            
            const dailyTotal = todaySales.reduce((sum, sale) => sum + parseFloat(sale.final_amount), 0);
            document.getElementById('dailySales').textContent = this.app.formatCurrency(dailyTotal);
            
        } catch (error) {
            document.getElementById('dailySales').textContent = 'Error';
        }
    }

    async loadMonthlySales() {
        try {
            const response = await this.app.apiCall('api/sales.php');
            const sales = response.data || [];
            
            const currentMonth = new Date().getMonth();
            const currentYear = new Date().getFullYear();
            
            const monthlySales = sales.filter(sale => {
                const saleDate = new Date(sale.sale_date);
                return saleDate.getMonth() === currentMonth && saleDate.getFullYear() === currentYear;
            });
            
            const monthlyTotal = monthlySales.reduce((sum, sale) => sum + parseFloat(sale.final_amount), 0);
            document.getElementById('monthlySales').textContent = this.app.formatCurrency(monthlyTotal);
            
        } catch (error) {
            document.getElementById('monthlySales').textContent = 'Error';
        }
    }

    async loadActiveRepairs() {
        try {
            const response = await this.app.apiCall('api/repairs.php');
            const repairs = response.data || [];
            
            const activeRepairs = repairs.filter(repair => 
                !['delivered', 'cancelled'].includes(repair.status)
            );
            
            document.getElementById('activeRepairs').textContent = activeRepairs.length;
            
        } catch (error) {
            document.getElementById('activeRepairs').textContent = 'Error';
        }
    }

    async loadLowStockCount() {
        try {
            const response = await this.app.apiCall('api/products.php');
            const products = response.data || [];
            
            const lowStockItems = products.filter(product => 
                product.stock_quantity <= product.min_stock_level
            );
            
            const lowStockElement = document.getElementById('lowStockCount');
            lowStockElement.textContent = lowStockItems.length;
            
            // Add warning color if there are low stock items
            const reportCard = lowStockElement.closest('.report-card');
            if (lowStockItems.length > 0) {
                reportCard.style.borderLeft = '4px solid #f39c12';
                lowStockElement.style.color = '#f39c12';
            } else {
                reportCard.style.borderLeft = '4px solid #27ae60';
                lowStockElement.style.color = '#27ae60';
            }
            
        } catch (error) {
            document.getElementById('lowStockCount').textContent = 'Error';
        }
    }

    // Additional report methods can be added here
    async generateSalesReport(fromDate, toDate) {
        try {
            const response = await this.app.apiCall('api/sales.php');
            const sales = response.data || [];
            
            const filteredSales = sales.filter(sale => {
                const saleDate = new Date(sale.sale_date).toISOString().split('T')[0];
                return saleDate >= fromDate && saleDate <= toDate;
            });
            
            return {
                totalSales: filteredSales.length,
                totalRevenue: filteredSales.reduce((sum, sale) => sum + parseFloat(sale.final_amount), 0),
                averageSale: filteredSales.length > 0 ? 
                    filteredSales.reduce((sum, sale) => sum + parseFloat(sale.final_amount), 0) / filteredSales.length : 0,
                paymentMethods: this.groupBy(filteredSales, 'payment_method'),
                dailyBreakdown: this.groupSalesByDate(filteredSales)
            };
        } catch (error) {
            console.error('Failed to generate sales report:', error);
            return null;
        }
    }

    async generateInventoryReport() {
        try {
            const response = await this.app.apiCall('api/products.php');
            const products = response.data || [];
            
            const totalProducts = products.length;
            const totalValue = products.reduce((sum, product) => 
                sum + (product.price * product.stock_quantity), 0);
            const lowStockItems = products.filter(product => 
                product.stock_quantity <= product.min_stock_level);
            const outOfStockItems = products.filter(product => 
                product.stock_quantity <= 0);
            
            return {
                totalProducts,
                totalValue,
                lowStockCount: lowStockItems.length,
                outOfStockCount: outOfStockItems.length,
                categoryBreakdown: this.groupBy(products, 'category'),
                lowStockItems,
                outOfStockItems
            };
        } catch (error) {
            console.error('Failed to generate inventory report:', error);
            return null;
        }
    }

    async generateRepairReport() {
        try {
            const response = await this.app.apiCall('api/repairs.php');
            const repairs = response.data || [];
            
            const totalRepairs = repairs.length;
            const completedRepairs = repairs.filter(r => r.status === 'completed').length;
            const activeRepairs = repairs.filter(r => 
                !['delivered', 'cancelled'].includes(r.status)).length;
            const totalRevenue = repairs
                .filter(r => r.total_cost)
                .reduce((sum, repair) => sum + parseFloat(repair.total_cost), 0);
            
            return {
                totalRepairs,
                completedRepairs,
                activeRepairs,
                totalRevenue,
                statusBreakdown: this.groupBy(repairs, 'status'),
                priorityBreakdown: this.groupBy(repairs, 'priority'),
                averageRepairCost: repairs.length > 0 ? totalRevenue / repairs.length : 0
            };
        } catch (error) {
            console.error('Failed to generate repair report:', error);
            return null;
        }
    }

    // Utility methods
    groupBy(array, key) {
        return array.reduce((result, item) => {
            const group = item[key];
            if (!result[group]) {
                result[group] = [];
            }
            result[group].push(item);
            return result;
        }, {});
    }

    groupSalesByDate(sales) {
        const grouped = {};
        sales.forEach(sale => {
            const date = new Date(sale.sale_date).toISOString().split('T')[0];
            if (!grouped[date]) {
                grouped[date] = {
                    count: 0,
                    total: 0
                };
            }
            grouped[date].count++;
            grouped[date].total += parseFloat(sale.final_amount);
        });
        return grouped;
    }

    // Export reports (future enhancement)
    exportToCSV(data, filename) {
        // Implementation for CSV export
        console.log('Export to CSV:', filename, data);
    }

    exportToPDF(data, filename) {
        // Implementation for PDF export (would require a PDF library)
        console.log('Export to PDF:', filename, data);
    }
}

// Initialize reports manager when app is ready
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        window.reportsManager = new ReportsManager(window.posApp);
    }, 100);
});