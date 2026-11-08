<?php
// includes/header.php
// Shared layout: opens <html>, <head>, sidebar nav, main area.
// Expects $pageTitle to be set before include.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'KT Avil Mill';
$activePage = $activePage ?? '';
$user = current_user();

$nav = [
    'dashboard'  => ['Dashboard', ''],
    'purchases'  => ['Paddy Purchases', ''],
    'sales'      => ['Sales', ''],
    'production' => ['Production', ''],
    'inventory'  => ['Inventory', ''],
    'expenses'   => ['Expenses', ''],
    'income'     => ['Other Income', ''],
    'reports'    => ['Reports', ''],
    'products'   => ['Products & Prices', 'admin'],
    'users'      => ['Users', 'admin'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($pageTitle) ?> - KT Avil Mill</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php if ($user): ?>
<aside class="sidebar">
  <div class="brand">
    <span class="brand-mark">KT</span>
    <div>
      <div class="brand-name">KT Avil Mill</div>
      <div class="brand-sub">Management Logbook</div>
    </div>
  </div>
  <nav>
    <?php foreach ($nav as $key => $info):
        [$label, $role] = $info;
        if ($role === 'admin' && !is_admin()) continue;
        $cls = $activePage === $key ? 'active' : '';
    ?>
      <a class="<?= $cls ?>" href="index.php?page=<?= $key ?>"><?= esc($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-foot">
    <div><?= esc($user['full_name']) ?></div>
    <div class="role"><?= esc($user['role']) ?></div>
    <a href="index.php?page=logout" class="logout">Sign out</a>
  </div>
</aside>
<main class="content">
  <header class="topbar">
    <button class="menu-toggle" onclick="document.body.classList.toggle('nav-open')" aria-label="Menu">&#9776;</button>
    <h1><?= esc($pageTitle) ?></h1>
  </header>
  <div class="flash"><?= render_flash() ?></div>
<?php else: ?>
<main class="content auth-content">
<?php endif; ?>
