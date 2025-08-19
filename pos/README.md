## Mobile Phone Shop POS (PHP)

This is a lightweight web-based POS system for a mobile phone shop with sales, repairs, billing/printing, and stock management. Built with PHP, HTML, CSS, and JavaScript using MySQL/MariaDB.

### Features
- User authentication
- Products (CRUD) with stock tracking
- POS sales with tax/discount and printable invoices
- Customers management (basic)
- Repairs workflow (Received → In Progress → Ready → Delivered) with parts usage and cost tracking
- Stock movements (purchase, sale, adjustment, repair)
- Simple REST-like API endpoints for product search (used by POS)

### Requirements
- PHP 8.1+
- MySQL or MariaDB
- Web server (Apache/Nginx). For simplicity the app uses query-string routing (no special rewrite rules required).

### Quick Start
1. Create a database and user in MySQL, e.g. `pos_db`.
2. Import the schema:
   - `mysql -u <user> -p pos_db < sql/schema.sql`
3. Copy `app/config.example.php` to `app/config.php` and set DB credentials.
4. Point your web server to `public/` as the document root.
5. Open the app in the browser. On first launch you will be prompted to create the admin user.

### Default Structure
```
pos/
  app/
    controllers/
    lib/
    views/
    config.php (you create from config.example.php)
  public/
    assets/
  sql/
```

### Printing Invoices
Invoices have a print-optimized page with a "Print" button. Use your browser's print dialog to print to any connected printer.

### Security Notes
- Use HTTPS in production
- Change the default admin immediately after setup
- Review file permissions and restrict `public/` as the only web-accessible directory

### License
MIT

