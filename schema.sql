-- MySQL schema for Mobile Phone Shop POS
-- Charset/engine
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username, password_hash)
VALUES ('admin', '$2y$10$T0lXxC2vM3DYxmD8p6D8Y.3U3t1E0qQ0o2Y5i3GErFq0hP2O3JXjK')
ON DUPLICATE KEY UPDATE username = username;
-- password: admin (bcrypt)

CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(64) NOT NULL UNIQUE,
  setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (setting_key, setting_value) VALUES
('shop_name', 'My Mobile Shop'),
('shop_address', '123 Main Street, City'),
('shop_phone', '+1 555-0100'),
('tax_rate', '0.00'),
('currency', 'USD')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  phone VARCHAR(32),
  email VARCHAR(128),
  address VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(64) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(128),
  cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  sell_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock_qty INT NOT NULL DEFAULT 0,
  track_serial TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_serials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  serial VARCHAR(128) NOT NULL,
  status ENUM('in_stock','sold','reserved','used_in_repair') NOT NULL DEFAULT 'in_stock',
  UNIQUE KEY unique_serial (product_id, serial),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(32) NOT NULL UNIQUE,
  customer_id INT NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tax DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_method VARCHAR(32) NOT NULL DEFAULT 'cash',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sale_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  serial VARCHAR(128) NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS repairs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_number VARCHAR(32) NOT NULL UNIQUE,
  customer_id INT NULL,
  device_make VARCHAR(128) NOT NULL,
  device_model VARCHAR(128) NOT NULL,
  imei VARCHAR(64),
  passcode VARCHAR(64),
  accessories_received VARCHAR(255),
  fault_description TEXT,
  diagnosis TEXT,
  status ENUM('received','in_progress','awaiting_parts','completed','delivered') NOT NULL DEFAULT 'received',
  parts_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  labor_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_estimate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  prepayment DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  completed_at TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS repair_parts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  repair_id INT NOT NULL,
  product_id INT NULL,
  description VARCHAR(255) NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (repair_id) REFERENCES repairs(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stock_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  change_qty INT NOT NULL,
  reason ENUM('sale','purchase','adjustment','repair_use','repair_return') NOT NULL,
  reference_type VARCHAR(32) NULL,
  reference_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

