-- =====================================================================
-- KT Avil Mill - MySQL Schema
-- Target: XAMPP / MariaDB or MySQL 5.7+
-- All monetary values use DECIMAL(12,2). Weights use DECIMAL(10,3).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS kt_avil_mill
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kt_avil_mill;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Users: admin can manage everything; staff can record transactions.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(100) NOT NULL,
  role          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Products: aval, thavid, ash, etc. Default price is separate from the
-- price stored on each sale (see sales / sale_items).
-- pricing_method: 'per_unit' (unit = kg or sack) or 'per_kg_from_sack'
--   (used for thavid where the admin can choose 15/kg vs 20/kg).
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS products;
CREATE TABLE products (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(80)  NOT NULL,
  unit           VARCHAR(20)  NOT NULL DEFAULT 'kg',   -- kg, sack, bag...
  pricing_method ENUM('per_unit','per_kg_from_sack') NOT NULL DEFAULT 'per_unit',
  default_price  DECIMAL(12,2) NOT NULL DEFAULT 0.00,  -- price per unit
  sack_size_kg   DECIMAL(10,3) DEFAULT NULL,           -- for per_kg_from_sack
  is_active      TINYINT(1)   NOT NULL DEFAULT 1,
  is_ash_income  TINYINT(1)   NOT NULL DEFAULT 0,      -- leftover ash resale
  notes          VARCHAR(200) DEFAULT NULL,
  updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Suppliers and customers.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS suppliers;
CREATE TABLE suppliers (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  name    VARCHAR(120) NOT NULL,
  phone   VARCHAR(20)  DEFAULT NULL,
  place   VARCHAR(120) DEFAULT NULL,
  balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,  -- amount we still owe
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  name    VARCHAR(120) NOT NULL,
  phone   VARCHAR(20)  DEFAULT NULL,
  place   VARCHAR(120) DEFAULT NULL,
  balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,  -- amount they still owe us
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Expense categories (electricity, labour, transport...). Admin can add.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS expense_categories;
CREATE TABLE expense_categories (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(80) NOT NULL UNIQUE,
  is_system TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Paddy purchases. One row per purchase; many sacks in child table.
-- payment_status: unpaid / partial / paid
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS paddy_purchases;
CREATE TABLE paddy_purchases (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  purchase_date   DATE          NOT NULL,
  supplier_id     INT           DEFAULT NULL,
  supplier_name   VARCHAR(120)  NOT NULL,          -- denormalized for history
  num_sacks       INT           NOT NULL,
  total_weight_kg DECIMAL(10,3) NOT NULL,
  rate_per_kg     DECIMAL(12,2) NOT NULL,
  purchase_amount DECIMAL(12,2) NOT NULL,
  amount_paid     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  amount_outstanding DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  payment_status  ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  notes           VARCHAR(255)  DEFAULT NULL,
  created_by      INT           NOT NULL,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by)   REFERENCES users(id)
) ENGINE=InnoDB;

-- Individual sack weights (variable).
DROP TABLE IF EXISTS paddy_purchase_sacks;
CREATE TABLE paddy_purchase_sacks (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  purchase_id  INT NOT NULL,
  sack_no      INT NOT NULL,
  weight_kg    DECIMAL(10,3) NOT NULL,
  FOREIGN KEY (purchase_id) REFERENCES paddy_purchases(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Sales header + items. Sale can include multiple products.
-- Each item stores its own unit_price (so price changes don't rewrite history).
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS sales;
CREATE TABLE sales (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  sale_date       DATE          NOT NULL,
  customer_id     INT           DEFAULT NULL,
  customer_name   VARCHAR(120)  NOT NULL,
  total_amount    DECIMAL(12,2) NOT NULL,
  amount_received DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  balance_due     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  payment_status  ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  notes           VARCHAR(255)  DEFAULT NULL,
  created_by      INT           NOT NULL,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by)   REFERENCES users(id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS sale_items;
CREATE TABLE sale_items (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  sale_id     INT NOT NULL,
  product_id  INT NOT NULL,
  product_name VARCHAR(80) NOT NULL,           -- historical snapshot
  quantity    DECIMAL(10,3) NOT NULL,
  unit        VARCHAR(20)  NOT NULL,
  unit_price  DECIMAL(12,2) NOT NULL,          -- actual transaction price
  line_total  DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (sale_id)    REFERENCES sales(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Expenses. amount_incurred vs amount_paid distinguishes incurred vs paid.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS expenses;
CREATE TABLE expenses (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  expense_date   DATE          NOT NULL,
  category_id    INT           NOT NULL,
  category_name  VARCHAR(80)   NOT NULL,
  description    VARCHAR(255)  NOT NULL,
  quantity       DECIMAL(10,3) DEFAULT NULL,
  amount_incurred DECIMAL(12,2) NOT NULL,
  amount_paid    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  payment_method ENUM('cash','bank','upi','cheque','other') NOT NULL DEFAULT 'cash',
  notes          VARCHAR(255)  DEFAULT NULL,
  created_by     INT           NOT NULL,
  created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES expense_categories(id),
  FOREIGN KEY (created_by)   REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Other income (ash sales etc.), separate from product sales and expenses.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS income_entries;
CREATE TABLE income_entries (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  income_date  DATE          NOT NULL,
  source       VARCHAR(80)   NOT NULL,         -- 'Ash Sale', 'Scrap', ...
  description  VARCHAR(255)  DEFAULT NULL,
  quantity     DECIMAL(10,3) DEFAULT NULL,
  unit         VARCHAR(20)   DEFAULT NULL,
  unit_price   DECIMAL(12,2) DEFAULT NULL,
  amount       DECIMAL(12,2) NOT NULL,
  amount_received DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  payment_method ENUM('cash','bank','upi','cheque','other') NOT NULL DEFAULT 'cash',
  notes        VARCHAR(255)  DEFAULT NULL,
  created_by   INT           NOT NULL,
  created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Production batches. Paddy in -> products out.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS production_batches;
CREATE TABLE production_batches (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  batch_date        DATE          NOT NULL,
  paddy_consumed_kg DECIMAL(10,3) NOT NULL,
  processing_cost   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  notes             VARCHAR(255)  DEFAULT NULL,
  created_by        INT           NOT NULL,
  created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Output of each batch per product.
DROP TABLE IF EXISTS production_outputs;
CREATE TABLE production_outputs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  batch_id    INT NOT NULL,
  product_id  INT NOT NULL,
  product_name VARCHAR(80) NOT NULL,
  quantity    DECIMAL(10,3) NOT NULL,
  unit        VARCHAR(20)  NOT NULL,
  FOREIGN KEY (batch_id)   REFERENCES production_batches(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Inventory movements. Every production output is an 'in' movement; every
-- sale item is an 'out' movement; corrections are 'adjust'. Running stock
-- = SUM(quantity) per product. This prevents negative stock on sales.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS inventory_movements;
CREATE TABLE inventory_movements (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  movement_date DATE NOT NULL,
  product_id   INT NOT NULL,
  product_name VARCHAR(80) NOT NULL,
  direction    ENUM('in','out','adjust') NOT NULL,
  quantity     DECIMAL(10,3) NOT NULL,    -- positive; sign derived from direction
  ref_type     VARCHAR(30) DEFAULT NULL,  -- production / sale / correction
  ref_id       INT DEFAULT NULL,
  reason       VARCHAR(255) DEFAULT NULL,
  created_by   INT NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (created_by) REFERENCES users(id),
  INDEX idx_product (product_id),
  INDEX idx_date (movement_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Payment entries: separate cash ledger. Tracks actual cash in/out,
-- distinct from incurred revenue / expense.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS payment_entries;
CREATE TABLE payment_entries (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  pay_date     DATE          NOT NULL,
  direction    ENUM('received','paid') NOT NULL,
  ref_type     VARCHAR(30)   NOT NULL,   -- sale / purchase / expense / income
  ref_id       INT DEFAULT NULL,
  amount       DECIMAL(12,2) NOT NULL,
  method       ENUM('cash','bank','upi','cheque','other') NOT NULL DEFAULT 'cash',
  notes        VARCHAR(255)  DEFAULT NULL,
  created_by   INT           NOT NULL,
  created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id),
  INDEX idx_date (pay_date),
  INDEX idx_ref (ref_type, ref_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Audit trail for admin corrections/reversals.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS audit_log;
CREATE TABLE audit_log (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  action_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_id    INT NOT NULL,
  action     VARCHAR(50) NOT NULL,         -- 'reverse_sale', 'correct_purchase'
  ref_type   VARCHAR(30) NOT NULL,
  ref_id     INT NOT NULL,
  details    TEXT,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
