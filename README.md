# Mobile Phone Shop POS System

A comprehensive web-based Point of Sale (POS) system designed specifically for mobile phone shops that also offer repair services. Built with modern web technologies including HTML5, CSS3, JavaScript, PHP, and MySQL.

## Features

### 🛒 Point of Sale
- **Product Search**: Search by name, brand, model, or barcode
- **Shopping Cart**: Add/remove items with quantity controls
- **Customer Management**: Optional customer information for sales
- **Multiple Payment Methods**: Cash, Card, UPI, and other payment options
- **Tax & Discount Calculation**: Automatic tax calculation with discount support
- **Receipt Generation**: Professional receipts with print functionality

### 📦 Inventory Management
- **Product Catalog**: Comprehensive product database with categories
- **Stock Tracking**: Real-time stock level monitoring
- **Low Stock Alerts**: Automatic alerts for items below minimum levels
- **Barcode Support**: Barcode scanning and management
- **Categories**: Phones, Accessories, Parts, and Services
- **Cost & Pricing**: Track both cost price and selling price

### 🔧 Repair Management
- **Repair Job Tracking**: Complete repair workflow management
- **Status Updates**: Track repairs from received to delivered
- **Priority Levels**: Low, Medium, High, and Urgent priority settings
- **Customer Communication**: Notes and status updates
- **Parts Usage**: Track parts used in repairs
- **Cost Estimation**: Repair cost calculation and tracking

### 👥 Customer Management
- **Customer Database**: Store customer information and history
- **Purchase History**: Track all customer transactions
- **Repair History**: Complete repair service history
- **Contact Management**: Phone, email, and address information
- **Customer Analytics**: Total spent, number of visits, etc.

### 📊 Reports & Analytics
- **Sales Reports**: Daily, monthly, and custom date range reports
- **Inventory Reports**: Stock levels, low stock items, product performance
- **Repair Reports**: Active repairs, completion rates, revenue
- **Dashboard**: Real-time business metrics and KPIs

### 🧾 Billing & Printing
- **Professional Invoices**: Detailed sales receipts and invoices
- **Print Functionality**: Direct printer support for receipts
- **Tax Compliance**: Configurable tax rates and calculations
- **Payment Tracking**: Multiple payment method support

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Styling**: Custom CSS with responsive design
- **Icons**: Font Awesome 6.0
- **Architecture**: RESTful API design

## Installation & Setup

### Prerequisites
- Web server (Apache/Nginx) with PHP 7.4+
- MySQL 5.7+ or MariaDB 10.2+
- Modern web browser (Chrome, Firefox, Safari, Edge)

### Step 1: Database Setup
1. Create a new MySQL database:
```sql
CREATE DATABASE mobile_shop_pos;
```

2. Import the database schema:
```bash
mysql -u your_username -p mobile_shop_pos < database.sql
```

3. Update database connection settings in `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'mobile_shop_pos';
private $username = 'your_username';
private $password = 'your_password';
```

### Step 2: File Permissions
Set appropriate permissions for the web server:
```bash
chmod -R 755 /path/to/mobile-shop-pos
chown -R www-data:www-data /path/to/mobile-shop-pos
```

### Step 3: Web Server Configuration

#### Apache (.htaccess)
Create a `.htaccess` file in the root directory:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/$1.php [L,QSA]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

#### Nginx
Add this to your nginx configuration:
```nginx
location /api/ {
    try_files $uri $uri.php?$args;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

### Step 4: Initial Configuration
1. Access the system via your web browser
2. Default admin credentials:
   - Username: `admin`
   - Password: `admin123`
3. Change the default password immediately
4. Configure shop settings in the database `settings` table

## Usage Guide

### Getting Started
1. **Setup Products**: Add your inventory items in the Inventory section
2. **Configure Settings**: Update shop information, tax rates, and preferences
3. **Add Customers**: Import or manually add customer information
4. **Start Selling**: Use the POS interface to process sales

### Daily Operations
1. **Morning**: Check low stock alerts and active repairs
2. **Sales**: Process customer transactions through POS
3. **Repairs**: Update repair job statuses as work progresses
4. **Inventory**: Receive new stock and update quantities
5. **Reports**: Review daily sales and performance metrics

### Key Workflows

#### Processing a Sale
1. Search and add products to cart
2. Enter customer information (optional)
3. Apply discounts if needed
4. Select payment method
5. Complete checkout
6. Print receipt

#### Managing Repairs
1. Create new repair job with customer and device info
2. Update status as repair progresses
3. Add parts used during repair
4. Mark as completed when finished
5. Notify customer for pickup

#### Inventory Management
1. Add new products with complete information
2. Update stock levels when receiving inventory
3. Monitor low stock alerts
4. Track product performance

## API Documentation

The system provides RESTful APIs for all major operations:

### Products API (`api/products.php`)
- `GET /api/products` - Get all products
- `GET /api/products?id=1` - Get specific product
- `GET /api/products?search=query` - Search products
- `POST /api/products` - Add new product
- `PUT /api/products` - Update product
- `DELETE /api/products?id=1` - Delete product

### Sales API (`api/sales.php`)
- `GET /api/sales` - Get all sales
- `GET /api/sales?id=1` - Get specific sale
- `POST /api/sales` - Create new sale

### Repairs API (`api/repairs.php`)
- `GET /api/repairs` - Get all repairs
- `GET /api/repairs?status=active` - Filter by status
- `POST /api/repairs` - Create repair job
- `PUT /api/repairs` - Update repair

### Customers API (`api/customers.php`)
- `GET /api/customers` - Get all customers
- `GET /api/customers?search=query` - Search customers
- `POST /api/customers` - Add customer
- `PUT /api/customers` - Update customer

## Security Features

- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Input sanitization and output encoding
- **CSRF Protection**: Token-based request validation
- **Access Control**: Role-based permissions
- **Data Validation**: Server-side input validation
- **Secure Headers**: Security headers implementation

## Browser Compatibility

- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+
- Mobile browsers (responsive design)

## Performance Features

- **Lazy Loading**: Dynamic content loading
- **Caching**: Browser and server-side caching
- **Minification**: Optimized CSS and JavaScript
- **Database Indexing**: Optimized database queries
- **Responsive Design**: Mobile-first approach

## Customization

### Themes
Modify `css/styles.css` to customize the appearance:
- Color scheme variables at the top of the file
- Layout adjustments for different screen sizes
- Custom branding and logos

### Features
Add custom functionality by:
- Creating new API endpoints in the `api/` directory
- Adding JavaScript modules in the `js/` directory
- Extending database schema as needed

## Troubleshooting

### Common Issues

**Database Connection Error**
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists and user has proper permissions

**API Errors**
- Check PHP error logs
- Verify file permissions
- Ensure all required PHP extensions are installed

**Print Issues**
- Check browser print settings
- Verify printer connectivity
- Test with different browsers

**Performance Issues**
- Check database indexes
- Monitor server resources
- Optimize images and assets

## Support & Maintenance

### Regular Maintenance
- **Database Backup**: Regular automated backups
- **Software Updates**: Keep PHP and MySQL updated
- **Security Patches**: Apply security updates promptly
- **Performance Monitoring**: Monitor system performance
- **Log Rotation**: Manage log file sizes

### Backup Strategy
```bash
# Database backup
mysqldump -u username -p mobile_shop_pos > backup_$(date +%Y%m%d).sql

# File backup
tar -czf files_backup_$(date +%Y%m%d).tar.gz /path/to/mobile-shop-pos
```

## License

This project is developed for commercial use in mobile phone shops. Please ensure compliance with local business regulations and tax requirements.

## Contributing

This system is designed to be customizable for specific business needs. Key areas for customization include:
- Additional payment methods
- Extended reporting features
- Integration with external systems
- Multi-location support
- Advanced inventory features

## Version History

- **v1.0.0** - Initial release with core POS functionality
- Features: Sales, Inventory, Repairs, Customers, Reports
- Technologies: PHP, MySQL, HTML5, CSS3, JavaScript

---

For technical support or customization requests, please refer to the documentation or contact your system administrator.