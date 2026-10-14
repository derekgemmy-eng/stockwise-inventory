CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    sku VARCHAR(40) NOT NULL UNIQUE,
    category VARCHAR(80) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 0,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    threshold INT UNSIGNED NOT NULL DEFAULT 5,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_products_name (name),
    INDEX idx_products_category (category),
    INDEX idx_products_stock (quantity, threshold)
);

INSERT INTO products (name, sku, category, quantity, price, threshold) VALUES
('Wireless Mouse', 'ELEC-001', 'Elektronik', 18, 125000, 5),
('Keyboard Mechanical', 'ELEC-002', 'Elektronik', 4, 450000, 5),
('Notebook A5', 'ATK-001', 'Alat Tulis', 42, 18000, 10),
('USB-C Cable', 'ELEC-003', 'Elektronik', 0, 75000, 3)
ON DUPLICATE KEY UPDATE sku = VALUES(sku);
