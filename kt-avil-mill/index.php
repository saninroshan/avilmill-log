<?php
// index.php
// Application entry point + simple page router.
// All pages live in pages/<name>.php; each page includes header/footer itself.
// API endpoints live in api/<name>.php and are called directly (api/sale_save.php).

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$page = $_GET['page'] ?? 'dashboard';
$page = preg_replace('/[^a-z0-9_]/', '', (string) $page);

// logout is a special action, not a page file.
if ($page === 'logout') {
    logout();
    header('Location: index.php?page=login');
    exit;
}

$public = ['login'];

// Process login submissions before rendering the login page.
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $loginError = 'Session expired. Refresh the page and try again.'; }
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (empty($loginError) && $username !== '' && $password !== '' && attempt_login($username, $password)) {
        session_regenerate_id(true);
        header('Location: index.php?page=dashboard');
        exit;
    }
    if (empty($loginError)) $loginError = 'Invalid username or password.';
}

if (!in_array($page, $public, true)) {
    require_login();
}

$safe = __DIR__ . '/pages/' . $page . '.php';
if (!file_exists($safe)) {
    http_response_code(404);
    die('Page not found.');
}
require $safe;
