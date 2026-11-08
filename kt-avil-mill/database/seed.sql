-- =====================================================================
-- KT Avil Mill - Sample / Demonstration Data
-- Demo passwords (DO NOT use in production):
--   admin  -> admin123   (hashed below with PHP password_hash)
--   staff  -> staff123
-- The hashes here were generated for "admin123" and "staff123".
-- If they do not authenticate on your PHP version, run the tiny script
-- given in README "Resetting demo passwords" to regenerate.
-- =====================================================================

USE kt_avil_mill;

-- Users. password_hash() output for the demo passwords (bcrypt, cost 12).
-- admin123 / staff123 — generated with PHP password_hash() and verified with
-- password_verify(). Replace via the Users page if you want your own password.
INSERT INTO users (id, username, password_hash, full_name, role, is_active) VALUES
(1, 'admin',
 '$2y$12$MbZTpa7nTr9hn/bxFizqt.OUkVYO8dbICEN.051ZArOA9AsT0DTYq',
 'Administrator', 'admin', 1),
(2, 'staff',
 '$2y$12$ofXC1MAe8b4aE5BvGQK/Bu92qfFxr093PzQBY/3okBqMIzZC/rz2q',
 'Mill Staff', 'staff', 1);

-- Products
INSERT INTO products (id, name, unit, pricing_method, default_price, sack_size_kg, is_active, is_ash_income, notes) VALUES
(1, 'White Aval',     'kg',    'per_unit',         80.00,  NULL,    1, 0, 'Unit may change; confirm per kg vs per sack.'),
(2, 'Red Aval',       'kg',    'per_unit',         90.00,  NULL,    1, 0, 'Price set by admin.'),
(3, 'Thavid',         'sack',  'per_kg_from_sack', 600.00, 40.000,  1, 0, 'Default 600/40kg=15/kg. Quoted 20/kg also possible; choose per sale.'),
(4, 'Avil-waste Thavid','kg',  'per_unit',         25.00,  NULL,    1, 0, 'Mixed waste thavid.'),
(5, 'Leftover Ash',   'bag',   'per_unit',         120.00, NULL,    1, 1, 'Resale income; unit configurable.');

-- Suppliers and customers
INSERT INTO suppliers (id, name, phone, place, balance) VALUES
(1, 'Ravi Paddy Supplier',  '9847000001', 'Aluva', 0.00),
(2, 'Lakshmi Farms',        '9847000002', 'Perumbavoor', 0.00);

INSERT INTO customers (id, name, phone, place, balance) VALUES
(1, 'Sreebhavan Store',     '9847000011', 'Angamaly', 0.00),
(2, 'Walk-in Customer',     NULL,         '-',        0.00);

-- Expense categories
INSERT INTO expense_categories (id, name, is_system) VALUES
(1,'Electricity',1),(2,'Labour',1),(3,'Transport',1),
(4,'Packaging',1),(5,'Repairs',1),(6,'Fuel',1),
(7,'Maintenance',1),(8,'Miscellaneous',1);

-- A sample paddy purchase with 3 sacks of different weights.
INSERT INTO paddy_purchases (id, purchase_date, supplier_id, supplier_name, num_sacks, total_weight_kg, rate_per_kg, purchase_amount, amount_paid, amount_outstanding, payment_status, notes, created_by)
VALUES (1, '2026-10-15', 1, 'Ravi Paddy Supplier', 3, 121.500, 28.00, 3402.00, 2000.00, 1402.00, 'partial', 'First purchase', 2);

INSERT INTO paddy_purchase_sacks (purchase_id, sack_no, weight_kg) VALUES
(1, 1, 40.500),
(1, 2, 41.000),
(1, 3, 40.000);

-- A sample production batch
INSERT INTO production_batches (id, batch_date, paddy_consumed_kg, processing_cost, notes, created_by)
VALUES (1, '2026-10-16', 100.000, 500.00, 'Demo batch', 2);
INSERT INTO production_outputs (batch_id, product_id, product_name, quantity, unit) VALUES
(1, 1, 'White Aval', 55.000, 'kg'),
(1, 3, 'Thavid', 1.500, 'sack'),
(1, 5, 'Leftover Ash', 2.000, 'bag');

-- Inventory movements from that production
INSERT INTO inventory_movements (movement_date, product_id, product_name, direction, quantity, ref_type, ref_id, created_by) VALUES
('2026-10-16',1,'White Aval','in',55.000,'production',1,2),
('2026-10-16',3,'Thavid','in',1.500,'production',1,2),
('2026-10-16',5,'Leftover Ash','in',2.000,'production',1,2);

-- A sample sale
INSERT INTO sales (id, sale_date, customer_id, customer_name, total_amount, amount_received, balance_due, payment_status, notes, created_by)
VALUES (1, '2026-10-17', 1, 'Sreebhavan Store', 2400.00, 2400.00, 0.00, 'paid', 'Demo sale', 2);
INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit, unit_price, line_total) VALUES
(1, 1, 'White Aval', 30.000, 'kg', 80.00, 2400.00);
INSERT INTO inventory_movements (movement_date, product_id, product_name, direction, quantity, ref_type, ref_id, created_by) VALUES
('2026-10-17',1,'White Aval','out',30.000,'sale',1,2);

-- A sample expense and a sample income (ash)
INSERT INTO expenses (expense_date, category_id, category_name, description, quantity, amount_incurred, amount_paid, payment_method, created_by)
VALUES ('2026-10-17', 2, 'Labour', 'Two workers', 2, 600.00, 600.00, 'cash', 2);

INSERT INTO income_entries (income_date, source, description, quantity, unit, unit_price, amount, amount_received, payment_method, created_by)
VALUES ('2026-10-18', 'Ash Sale', 'Leftover ash to nursery', 1.000, 'bag', 120.00, 120.00, 120.00, 'cash', 2);

-- Cash payments
INSERT INTO payment_entries (pay_date, direction, ref_type, ref_id, amount, method, created_by) VALUES
('2026-10-15','paid','purchase',1,2000.00,'cash',2),
('2026-10-17','received','sale',1,2400.00,'cash',2),
('2026-10-17','paid','expense',1,600.00,'cash',2),
('2026-10-18','received','income',1,120.00,'cash',2);
