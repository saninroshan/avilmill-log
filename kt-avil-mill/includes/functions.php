<?php
// includes/functions.php
// Reusable helpers: formatting, validation, lookups, money calculations.

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// ---------- Formatting ----------
function money(float $n): string
{
    return '₹' . number_format($n, 2, '.', ',');
}

function kg(float $n): string
{
    return number_format($n, 3, '.', '') . ' kg';
}

function esc($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function flash(string $msg, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function render_flash(): string
{
    if (empty($_SESSION['flash'])) return '';
    $out = '';
    foreach ($_SESSION['flash'] as $f) {
        $cls = $f['type'] === 'error' ? 'error' : 'success';
        $out .= '<div class="alert ' . $cls . '">' . esc($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

// ---------- Validation ----------
function post(string $key, $default = '')
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function get(string $key, $default = '')
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function to_decimal($v): float
{
    $v = (string) $v;
    if (!preg_match('/^-?\d+(\.\d+)?$/', $v)) {
        throw new InvalidArgumentException('Invalid number: ' . $v);
    }
    return (float) $v;
}

function to_int($v): int
{
    $v = (string) $v;
    if (!preg_match('/^-?\d+$/', $v)) {
        throw new InvalidArgumentException('Invalid integer: ' . $v);
    }
    return (int) $v;
}

// ---------- Lookups ----------
function products_list(bool $active_only = true): array
{
    $sql = 'SELECT * FROM products';
    if ($active_only) $sql .= ' WHERE is_active = 1';
    return db()->query($sql . ' ORDER BY name')->fetchAll();
}

function product_by_id(int $id): ?array
{
    $s = db()->prepare('SELECT * FROM products WHERE id = ?');
    $s->execute([$id]);
    $r = $s->fetch();
    return $r ?: null;
}

function expense_categories_list(): array
{
    return db()->query('SELECT * FROM expense_categories ORDER BY name')->fetchAll();
}

function suppliers_list(): array
{
    return db()->query('SELECT * FROM suppliers ORDER BY name')->fetchAll();
}

function customers_list(): array
{
    return db()->query('SELECT * FROM customers ORDER BY name')->fetchAll();
}

// ---------- Stock ----------
function current_stock(int $productId): float
{
    $s = db()->prepare(
        "SELECT COALESCE(SUM(CASE direction
            WHEN 'in' THEN quantity WHEN 'out' THEN -quantity WHEN 'adjust' THEN quantity END),0) AS stock
         FROM inventory_movements WHERE product_id = ?"
    );
    $s->execute([$productId]);
    return (float) $s->fetchColumn();
}

// ---------- Payment status helper ----------
function payment_status_for(float $total, float $paid): string
{
    if ($paid <= 0) return 'unpaid';
    if ($paid >= $total) return 'paid';
    return 'partial';
}
