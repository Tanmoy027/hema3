/* ------------ 0.  DATABASE ------------ */
CREATE DATABASE IF NOT EXISTS warehouse_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE warehouse_db;

/* ------------ 1.  USERS & ROLES ------------ */
CREATE TABLE users (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    email        VARCHAR(100) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,        -- PHP bcrypt/hash
    role         ENUM('admin','staff') DEFAULT 'staff',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

/* ------------ 2.  SUPPLIERS ------------ */
CREATE TABLE suppliers (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(120) NOT NULL,
    contact_name VARCHAR(120) NULL,
    phone        VARCHAR(30)  NULL,
    email        VARCHAR(100) NULL,
    address      TEXT         NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

/* ------------ 3.  PRODUCTS ------------ */
CREATE TABLE products (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku           VARCHAR(50)  NOT NULL UNIQUE,
    name          VARCHAR(120) NOT NULL,
    description   TEXT         NULL,
    unit_price    DECIMAL(10,2) NOT NULL DEFAULT 0,
    reorder_level INT UNSIGNED  NOT NULL DEFAULT 0,
    supplier_id   INT UNSIGNED  NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

/* ------------ 4.  WAREHOUSE LOCATIONS (optional) ------------ */
CREATE TABLE locations (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(30)  NOT NULL UNIQUE,   -- e.g. "A1-03-B"
    description VARCHAR(120) NULL
) ENGINE=InnoDB;

/* ------------ 5.  STOCK MOVEMENTS ------------ */
CREATE TABLE stock_movements (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id    INT UNSIGNED  NOT NULL,
    location_id   INT UNSIGNED  NULL,
    qty           INT           NOT NULL,          -- positive = in, negative = out
    movement_type ENUM('PURCHASE','SALE','ADJUST','TRANSFER') NOT NULL,
    reference     VARCHAR(100)  NULL,              -- PO#, invoice#, etc.
    moved_by      INT UNSIGNED  NULL,              -- user who did it
    moved_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id)  REFERENCES products(id)   ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id)  ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (moved_by)    REFERENCES users(id)      ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX (product_id, moved_at)
) ENGINE=InnoDB;

/* ------------ 6.  PURCHASE ORDERS ------------ */
CREATE TABLE purchase_orders (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id   INT UNSIGNED  NOT NULL,
    ordered_by    INT UNSIGNED  NULL,     -- user who placed the PO
    order_date    DATE NOT NULL,
    status        ENUM('OPEN','RECEIVED','CANCELLED') DEFAULT 'OPEN',
    total_amount  DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (ordered_by)  REFERENCES users(id)     ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE purchase_order_items (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_id       BIGINT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED    NOT NULL,
    qty_ordered INT UNSIGNED    NOT NULL,
    unit_cost   DECIMAL(10,2)   NOT NULL,
    FOREIGN KEY (po_id)      REFERENCES purchase_orders(id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY (po_id, product_id)
) ENGINE=InnoDB;

/* ------------ 7.  VIEWS (quick stock on-hand) ------------ */
CREATE VIEW v_product_stock AS
SELECT
    p.id,
    p.sku,
    p.name,
    COALESCE(SUM(sm.qty),0) AS on_hand
FROM products p
LEFT JOIN stock_movements sm ON sm.product_id = p.id
GROUP BY p.id, p.sku, p.name;

/* ------------ 8.  SAMPLE ADMIN USER (change password ASAP) ------------ */
INSERT INTO users (username,email,password,role)
VALUES ('admin','admin@example.com',
        '$2y$10$JJJJJJJJJJJJJJJJJJJJjeIh7vJXzYxXc1s5og0kBZ08nH5wv/6aq', -- "admin123" hashed
        'admin');