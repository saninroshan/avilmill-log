# KT Avil Mill - Management & Financial Logbook System

A PHP + MySQL + AJAX + XML web application that replaces the paper logbook of a
traditional Kerala avil (beaten rice) mill. Built for the CUSAT 2023 B.Tech
Computer Science **Web Technologies** assignment.

## Stack (from the syllabus)
- Frontend: HTML5, CSS3, vanilla JavaScript (Fetch API)
- Backend: PHP (plain server-side processing, no framework)
- Database: MySQL / MariaDB (via XAMPP)
- AJAX: Fetch API for the Reports page and XML product-price export
- XML: product price configuration export/import via SimpleXML + XMLWriter
- Sessions, `password_hash()` / `password_verify()`, PDO prepared statements, CSRF tokens

No React, Node, Laravel, or cloud database. Runs entirely on XAMPP.

---

## 1. Installation (XAMPP)

1. Install XAMPP and start **Apache** and **MySQL** from the XAMPP control panel.
2. Copy the whole `kt-avil-mill` folder into `C:\xampp\htdocs\` (so the path is
   `C:\xampp\htdocs\kt-avil-mill`).
3. Open phpMyAdmin (http://localhost/phpmyadmin) and run:
   - `database/schema.sql` (creates the `kt_avil_mill` database and tables)
   - `database/seed.sql` (loads demo products, users, and a few transactions)
   You can also use the **Import** tab to upload each file.
4. Open http://localhost/kt-avil-mill/index.php
5. Log in with the demo credentials below.

If your MySQL root password is not empty, edit `config/database.php` and set
`DB_PASS`.

## 2. Demo login (demonstration only)

| Username | Password   | Role  |
|----------|------------|-------|
| admin    | admin123   | admin |
| staff    | staff123   | staff |

> The seed file ships with bcrypt hashes for these passwords. If they ever fail
> to verify on your PHP version, open phpMyAdmin and run:
> ```sql
> UPDATE users SET password_hash =
>  (SELECT 'REPLACE_WITH_PHP_HASH') WHERE username='admin';
> ```
> To generate a fresh hash, run this once in a PHP file:
> ```php
> <?php echo password_hash('admin123', PASSWORD_DEFAULT);
> ```
> and paste the output into the `UPDATE` above.

---

## 3. Project structure

```
kt-avil-mill/
├─ index.php                # entry point + page router
├─ config/
│  └─ database.php          # PDO MySQL connection
├─ includes/
│  ├─ auth.php              # sessions, login, role checks, CSRF
│  ├─ functions.php         # reusable helpers (money, stock, lookups)
│  ├─ header.php            # shared layout header + sidebar
│  └─ footer.php            # shared layout footer
├─ pages/
│  ├─ login.php             # login card
│  ├─ dashboard.php         # summary cards + date filter
│  ├─ purchases.php         # paddy purchase logbook + sack weights
│  ├─ sales.php             # sales logbook
│  ├─ production.php        # production batch log
│  ├─ inventory.php         # stock + corrections (admin)
│  ├─ expenses.php          # expense entry + categories
│  ├─ income.php            # other income (ash sales etc.)
│  ├─ products.php          # admin product price config + XML import
│  ├─ users.php             # admin user management
│  └─ reports.php           # AJAX weekly/monthly reports
├─ api/
│  ├─ purchase_save.php     # save purchase (transaction)
│  ├─ sale_save.php         # save sale with stock check
│  ├─ production_save.php   # save production batch
│  ├─ expense_save.php      # save expense
│  ├─ income_save.php       # save other income
│  ├─ stock_correct.php     # admin stock correction
│  ├─ category_add.php      # add expense category
│  ├─ report_data.php       # AJAX JSON report data (Fetch API)
│  └─ xml_export.php        # XML product-price export
├─ assets/
│  ├─ css/style.css         # all styles
│  └─ js/app.js             # calculations, AJAX, XML load, modal
├─ database/
│  ├─ schema.sql            # table creation
│  └─ seed.sql              # demo data
└─ README.md
```

## 4. Purpose of every file

| File | Purpose |
|------|---------|
| `index.php` | Reads `?page=` and includes the matching file in `pages/`. Guards login. |
| `config/database.php` | Single PDO connection. Change DB credentials here only. |
| `includes/auth.php` | Session start, login/logout, `require_login`, `require_admin`, CSRF token helpers. |
| `includes/functions.php` | Money/weight formatting, validation, product/category/supplier lookups, stock calculation. |
| `includes/header.php` / `footer.php` | Shared HTML layout + sidebar nav. |
| `pages/*.php` | One screen each. Each includes header/footer and renders its own form/table. |
| `api/*_save.php` | AJAX / POST endpoints that validate input, run SQL inside PDO transactions, return JSON. |
| `api/report_data.php` | Returns the report summary + breakdowns as JSON. Called from `app.js` with the Fetch API. |
| `api/xml_export.php` | Generates the current product-price configuration as XML using `XMLWriter`. |
| `assets/css/style.css` | All styling. Responsive sidebar layout, cards, tables, forms, modal. |
| `assets/js/app.js` | Sack-weight rows, auto totals, stock warnings, product modal, XML load, AJAX report. |
| `database/schema.sql` | Creates all tables with primary keys, foreign keys, indexes, DECIMAL money fields. |
| `database/seed.sql` | Demo users, products, suppliers, customers, categories, and sample transactions. |

## 5. Key PHP functions and SQL

- `attempt_login($u,$p)` (auth.php): prepared SELECT, `password_verify`, sets `$_SESSION['user']`.
- `csrf_token()` / `csrf_verify()` (auth.php): random token stored in session, checked on every POST.
- `current_stock($id)` (functions.php): `SUM(CASE direction WHEN 'in' ... WHEN 'out' ...)` per product.
- `payment_status_for($total,$paid)`: returns `unpaid` / `partial` / `paid`.
- Purchases, sales, production, expenses, income all use `beginTransaction()` + `commit()` /
  `rollBack()` so the main row, child rows, payment entry, and balance update stay consistent.
- Sale insert checks `current_stock($pid) >= qty` before inserting, so a sale cannot create negative stock.
- A price change edits `products.default_price` only; every `sale_items` row stores its own
  `unit_price`, so old sales are never rewritten.
- Report SQL uses `UNION ALL` of sales + expenses + income for the transaction list, and
  `GROUP BY product_id` for the sales-by-product breakdown.

## 6. AJAX operations

- `app.js → loadReport()` calls `GET api/report_data.php?from=...&to=...` with the Fetch API
  and renders the returned JSON into summary cards and two tables — without reloading the page.
- `app.js → loadXmlExport()` calls `GET api/xml_export.php` and fills the XML textbox on the
  Products page, so the admin can review, edit, and re-import product prices via XML.

## 7. XML operations

- **Export**: `api/xml_export.php` uses `XMLWriter` to produce:
  ```xml
  <?xml version="1.0" encoding="UTF-8"?>
  <products>
    <product>
      <name>White Aval</name><unit>kg</unit>
      <pricing_method>per_unit</pricing_method>
      <default_price>80</default_price><is_active>1</is_active>
    </product>
    ...
  </products>
  ```
- **Import**: `pages/products.php` parses the edited XML with `simplexml_load_string` and
  `UPDATE`s matching products by name. Invalid XML is caught and shown as an error.

## 8. Test checklist (run through this before the demo)

1. **Login** as admin and as staff — both should work; wrong password should fail.
2. **Purchases**: create a purchase with 3 sacks of weights 40.5, 41, 40.
   Verify total weight = 121.5 and amount = 121.5 × rate. Save and see it in the list.
3. **Variable sack weights**: open phpMyAdmin, `paddy_purchase_sacks` should have 3 rows
   with the exact weights you entered.
4. **Sales**: sell 10 kg White Aval at ₹80. Line total should be ₹800. Check the inventory
   movement was created and stock decreased.
5. **Negative stock**: try selling more than available stock — server should reject with
   "Insufficient stock".
6. **Price change doesn't affect history**: edit White Aval's default price to ₹90 in
   Products, then check that the earlier sale still shows ₹80.
7. **Thavid pricing**: choose `per_kg_from_sack` for Thavid; default is ₹600/40kg = ₹15/kg.
   On a sale you can type a different actual price (e.g. ₹20/kg) and it is stored on the sale item.
8. **Expenses**: add a Labour expense, amount incurred ₹600, paid ₹600.
9. **Add expense category** (admin): add "Rent" and confirm it appears in the dropdown.
10. **Other income**: record an Ash Sale for ₹120.
11. **Production**: record a batch consuming 100 kg paddy, producing 55 kg White Aval + 1.5 sack
    Thavid. Inventory should increase for each product.
12. **Stock correction** (admin): set White Aval stock to a new number with a reason; verify
    the `adjust` movement appears in the movement list.
13. **Dashboard**: set a date range that includes your test transactions; verify revenue,
    expenses, gross profit, net operating profit, cash flow, and balances.
14. **Reports**: click "This week" and "This month"; the AJAX load should fill the cards and
    tables without a page reload.
15. **XML export**: on Products, the XML box should show product config. Edit a price in the
    XML and click "Import XML"; the product's default price should update.
16. **Users** (admin): add a new staff user, disable it, then try logging in — it should fail.
17. **CSRF**: (advanced) use browser devtools to delete the `csrf` hidden field and submit a
    form — the server should reject it.

## 9. Viva preparation guide (mapping to syllabus)

| Syllabus topic | Where to show it |
|----------------|------------------|
| HTML5 structure | any `pages/*.php` — forms, tables, semantic tags in `header.php` |
| CSS3 styling | `assets/css/style.css` — responsive grid, variables, media queries |
| JavaScript & DOM | `assets/js/app.js` — sack rows, live totals, modal, stock warning |
| AJAX (Fetch API) | `app.js loadReport()` + `api/report_data.php` |
| XML | `api/xml_export.php` (XMLWriter) + `pages/products.php` (SimpleXML import) |
| PHP server processing | `api/*_save.php` — validation, calculations, transactions |
| PHP sessions & auth | `includes/auth.php` — login/logout, role checks |
| Password hashing | `password_hash` in `users.php` / `seed.sql`, `password_verify` in `auth.php` |
| MySQL + SQL | `database/schema.sql` (joins, FKs, indexes, DECIMAL), queries in api/*.php |
| Prepared statements | every `api/*` file uses PDO `prepare()` + `execute()` |
| Security | CSRF tokens, server-side validation, role checks, no plain-text passwords |
| Input validation | `to_decimal` / `to_int` in `functions.php`, plus per-endpoint checks |

Likely viva questions and one-line answers:
- *How does the sale price stay fixed when the default price changes?*
  `sale_items.unit_price` stores the actual price per sale; `products.default_price` only feeds new sales.
- *How is SQL injection prevented?* PDO prepared statements with bound parameters everywhere user input reaches SQL.
- *How do you separate incurred vs paid?* `expenses.amount_incurred` vs `amount_paid`; `sales.total_amount`
  vs `amount_received`. Cash flow uses only `payment_entries`.
- *Why is profit labelled "estimated"?* Per-product cost allocation is not performed; COGS uses average
  paddy rate × consumed kg + processing cost.
- *What does the XML feature do?* Export/import product price configuration, demonstrating XML generation
  (XMLWriter) and parsing (SimpleXML).
- *How are sack weights stored?* One row per sack in `paddy_purchase_sacks`, allowing variable weights.

## 10. Limitations and future improvements

Limitations:
- COGS is **estimated** (average paddy rate × consumed kg). True per-product cost allocation is out of scope.
- Sales form supports one product per sale for simplicity; a multi-item cart is a natural extension.
- No PDF/Excel export; reports are on-screen only.
- Audit trail is captured in `audit_log` but only admin corrections write to it in this build.
- Supplier/customer balances are updated on insert only; reversing a transaction is not wired to reverse the balance (future work).

Future improvements:
- Multi-item sales (cart).
- Per-product yield ratios for accurate COGS.
- PDF invoice and Excel report export.
- Full reversal flow with automatic balance adjustment and audit log entries.
- Mobile app or PWA for offline entry at the mill.
