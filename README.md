# Mobile Phone Shop POS (PHP + MySQL)

A lightweight web-based POS system for a mobile phone shop with repairs, billing/printing, and stock management. Built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features
- POS sales with cart, discounts, tax, and printable receipts
- Products and stock management (stock-in and automatic deductions on sale)
- Customers management
- Repairs workflow (intake ticket, status tracking, parts usage, final billing)
- Printable invoices and repair tickets (browser print-friendly)
- Simple authentication (admin/user)

## Requirements
- PHP 8.0+
- MySQL 5.7+/MariaDB 10.3+
- A web server (Apache/Nginx) or PHP built-in server for development

## Setup
1. Copy config and set DB credentials:
   ```bash
   cp config.sample.php config.php
   ```
   Edit `config.php` with your MySQL credentials and shop info.

2. Create database and import schema:
   ```sql
   -- in your MySQL shell or client
   CREATE DATABASE pos_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE pos_shop;
   SOURCE /path/to/this/project/database.sql;
   ```

3. Serve the app (development):
   ```bash
   php -S 0.0.0.0:8000 -t public
   ```
   Then open `http://localhost:8000` in a browser.

4. Default login:
   - Username: `admin`
   - Password: `admin123`

   Please change the password after first login (Settings page).

## Deployment
- Place files on your server, configure your vhost to point `DocumentRoot` to the `public/` directory.
- Ensure `config.php` is present and correct.
- Ensure PHP can write session data.

## Printing
- Receipts and repair tickets have print-friendly styles. Use your browser's print dialog (Ctrl/Cmd+P). For thermal printers, configure margins and disable headers/footers in the print dialog. Optionally set a dedicated printer in kiosk mode for 80mm paper.

## Structure
```
public/          # web root with router index.php
lib/             # DB connection, auth, utilities, cart
views/           # pages and layouts
assets/          # CSS and JS
config.php       # local configuration (git-ignored)
database.sql     # schema and seed data
```

## Notes
- This is an MVP designed for clarity and extensibility. You can add roles, barcodes, supplier POs, advanced reports, etc.
- If your server uses a different SQL engine, adjust `database.sql` accordingly.