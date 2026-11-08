<?php
// includes/auth.php
// Session handling, login/logout, role checks, CSRF token helpers.

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function is_admin(): bool
{
    return is_logged_in() && $_SESSION['user']['role'] === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: index.php?page=login');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('Access denied: administrator only.');
    }
}

// Attempt login. Returns true on success.
function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id'       => (int) $user['id'],
            'username' => $user['username'],
            'full_name'=> $user['full_name'],
            'role'     => $user['role'],
        ];
        return true;
    }
    return false;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

// ---- CSRF ----
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? '';
    return !empty($_SESSION['csrf']) && hash_equals((string) $_SESSION['csrf'], (string) $token);
}
