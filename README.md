## Mobile Phone Shop POS (PHP)

This is a lightweight web-based POS system for a mobile phone shop with repair intake/management, billing with printable invoices/receipts, and stock management. Built with PHP, MySQL, HTML, CSS, and vanilla JavaScript (no frameworks).

### Features
- Products with stock tracking (optional serial/IMEI tracking)
- POS (sales) with printable invoice/receipt
- Customers management
- Repairs intake and management with statuses and printable repair tickets
- Stock movements log (sale, adjustment, repair use/return)
- Basic settings (shop details, tax rate, currency)
- Simple authentication (single-user by default)

### Requirements
- PHP 8.0+
- MySQL 5.7+/MariaDB 10.3+
- Web server (Apache/Nginx). For development you can use `php -S 0.0.0.0:8000` from this folder.

### Install
1. Create a MySQL database and user.
2. Import `schema.sql` into your database.
3. Copy `config.php` and update the DB credentials and initial settings.
4. Ensure the web root points to this folder (where `index.php` resides).
5. Default login: `admin` / `admin`.

### Database Setup
- Import the schema:
```bash
mysql -u <USER> -p -h <HOST> <DB_NAME> < schema.sql
```

### Running Locally
```bash
php -S 0.0.0.0:8000 -t /workspace
```
Open `http://localhost:8000` in your browser.

### Printing
- Use the provided Print buttons on invoice/repair pages. They load a print-optimized layout and invoke `window.print()`.

### Project Structure
```
assets/
  css/
    styles.css
    print.css
  js/
    app.js
lib/
  Database.php
  Auth.php
  Util.php
pages/
  dashboard.php
  login.php
  logout.php
  products.php
  product_form.php
  customers.php
  customer_form.php
  pos.php
  sales.php
  repairs.php
  repair_form.php
print/
  invoice.php
  repair.php
templates/
  header.php
  footer.php
  nav.php
config.php
index.php
schema.sql
```

### Security Notes
- This is an MVP for small shops. For production harden sessions, add CSRF tokens, role-based auth, input validation, and set proper server config (HTTPS, headers).

### License
MIT

# hotelnn