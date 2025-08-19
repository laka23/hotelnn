-- Mobile Phone Shop POS System Database Schema
-- Created for comprehensive shop management including sales, repairs, and inventory

-- Database creation
CREATE DATABASE IF NOT EXISTS mobile_shop_pos;
USE mobile_shop_pos;

-- Products table for inventory management
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    category ENUM('phone', 'accessory', 'part', 'service') NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    cost_price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    min_stock_level INT DEFAULT 5,
    description TEXT,
    barcode VARCHAR(50) UNIQUE,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Customers table
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(255),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sales table for POS transactions
CREATE TABLE sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    total_amount DECIMAL(10, 2) NOT NULL,
    discount DECIMAL(10, 2) DEFAULT 0,
    tax_amount DECIMAL(10, 2) DEFAULT 0,
    final_amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'card', 'upi', 'other') NOT NULL,
    status ENUM('completed', 'pending', 'cancelled') DEFAULT 'completed',
    notes TEXT,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Sale items table for detailed transaction records
CREATE TABLE sale_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Repairs table for repair service management
CREATE TABLE repairs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    device_brand VARCHAR(100) NOT NULL,
    device_model VARCHAR(100) NOT NULL,
    issue_description TEXT NOT NULL,
    repair_cost DECIMAL(10, 2),
    parts_cost DECIMAL(10, 2) DEFAULT 0,
    total_cost DECIMAL(10, 2),
    status ENUM('received', 'diagnosed', 'in_progress', 'completed', 'delivered', 'cancelled') DEFAULT 'received',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    estimated_completion DATE,
    actual_completion DATE,
    technician_notes TEXT,
    customer_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Repair parts used table
CREATE TABLE repair_parts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    repair_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (repair_id) REFERENCES repairs(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Stock movements table for inventory tracking
CREATE TABLE stock_movements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type ENUM('sale', 'repair', 'purchase', 'adjustment', 'return') NOT NULL,
    reference_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Users table for system access
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'cashier', 'technician') NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Settings table for system configuration
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, role, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 'admin@mobileshop.com');

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES 
('shop_name', 'Mobile Phone Shop', 'Shop name for invoices and receipts'),
('shop_address', '123 Main Street, City, State 12345', 'Shop address for invoices'),
('shop_phone', '+1-234-567-8900', 'Shop contact phone number'),
('shop_email', 'info@mobileshop.com', 'Shop email address'),
('tax_rate', '18', 'Default tax rate percentage'),
('currency_symbol', '$', 'Currency symbol for display'),
('invoice_prefix', 'INV', 'Invoice number prefix'),
('repair_prefix', 'RPR', 'Repair job number prefix');

-- Insert sample products
INSERT INTO products (name, brand, model, category, price, cost_price, stock_quantity, description) VALUES 
('iPhone 14 Pro', 'Apple', 'iPhone 14 Pro', 'phone', 999.00, 800.00, 10, 'Latest iPhone with advanced camera system'),
('Samsung Galaxy S23', 'Samsung', 'Galaxy S23', 'phone', 799.00, 650.00, 15, 'Flagship Android smartphone'),
('Phone Case - Clear', 'Generic', 'Universal', 'accessory', 19.99, 8.00, 50, 'Transparent protective case'),
('Screen Protector', 'Generic', 'Universal', 'accessory', 9.99, 3.00, 100, 'Tempered glass screen protector'),
('iPhone Battery', 'Apple', 'iPhone 12/13/14', 'part', 49.99, 25.00, 20, 'Replacement battery for iPhone'),
('Screen Repair Service', 'Service', 'Generic', 'service', 79.99, 30.00, 999, 'Professional screen replacement service');

-- Insert sample customers
INSERT INTO customers (name, phone, email, address) VALUES 
('John Doe', '+1-234-567-8901', 'john.doe@email.com', '456 Oak Street, City, State'),
('Jane Smith', '+1-234-567-8902', 'jane.smith@email.com', '789 Pine Avenue, City, State'),
('Mike Johnson', '+1-234-567-8903', 'mike.j@email.com', '321 Elm Drive, City, State');