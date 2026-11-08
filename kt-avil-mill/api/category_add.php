<?php
// api/category_add.php
// Admin: add a new expense category.

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (!csrf_verify()) { flash('Bad CSRF.', 'error'); header('Location: index.php?page=expenses'); exit; }

try {
    $name = post('name');
    if ($name === '') throw new Exception('Name required.');
    $st = db()->prepare("INSERT IGNORE INTO expense_categories (name,is_system) VALUES (?,0)");
    $st->execute([$name]);
    flash('Category added.');
} catch (Throwable $e) {
    flash('Could not add category: ' . $e->getMessage(), 'error');
}
header('Location: index.php?page=expenses');
exit;
