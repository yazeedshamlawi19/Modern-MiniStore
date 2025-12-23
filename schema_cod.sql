CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  image_url VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  gateway_order_id VARCHAR(200) NOT NULL,
  status VARCHAR(50) NOT NULL,
  currency VARCHAR(10) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  customer_name VARCHAR(255) NOT NULL,
  customer_phone VARCHAR(50) NOT NULL,
  delivery_method VARCHAR(20) NOT NULL,
  address VARCHAR(500) NULL,
  pickup_location VARCHAR(255) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_orders_user_id (user_id),
  CONSTRAINT fk_orders_user 
    FOREIGN KEY (user_id) 
    REFERENCES users(id) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NULL,
  name VARCHAR(255),
  qty INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) 
    REFERENCES orders(id) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO products (name, price, stock, image_url) VALUES
('قلم حبر', 2.50, 100, 'https://picsum.photos/seed/pen/400/300'),
('دفتر ملاحظات', 5.90, 80, 'https://picsum.photos/seed/notebook/400/300'),
('زجاجة ماء', 3.20, 60, 'https://picsum.photos/seed/bottle/400/300')
ON DUPLICATE KEY UPDATE name=VALUES(name);


CREATE TABLE IF NOT EXISTS product_variants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  color VARCHAR(50) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  UNIQUE KEY uniq_product_color (product_id, color),
  CONSTRAINT fk_variant_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE
);

ALTER TABLE products
ADD COLUMN description TEXT AFTER name;
