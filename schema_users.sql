CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @col_exists := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'orders'
    AND column_name = 'user_id'
);

SET @ddl := IF(@col_exists = 0,
  'ALTER TABLE orders ADD COLUMN user_id INT NULL, ADD INDEX idx_orders_user_id (user_id)',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_name := 'fk_orders_user';

SET @fk_exists := (
  SELECT COUNT(*)
  FROM information_schema.table_constraints
  WHERE constraint_schema = DATABASE()
    AND table_name = 'orders'
    AND constraint_type = 'FOREIGN KEY'
    AND constraint_name = @fk_name
);

SET @ddl2 := IF(@fk_exists = 0,
  CONCAT(
    'ALTER TABLE orders ADD CONSTRAINT ', @fk_name,
    ' FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL'
  ),
  'SELECT 1'
);

PREPARE stmt2 FROM @ddl2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

ALTER TABLE users
ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user';

INSERT INTO users (name, email, password_hash, role)
VALUES (
  'Administrator',
  'admin@store.com',
  '$2y$10$X0XH5q1QxE2V1X1UqF9g6uXy1m5QpYy9JqKcYzFZqK5s5gQk5Q0sS',
  'admin'
);

