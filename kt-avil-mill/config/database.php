<?php
// config/database.php
// Single MySQL connection via PDO. Adjust credentials here if needed.
// For XAMPP defaults the values below usually work unchanged.

declare(strict_types=1);

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'kt_avil_mill');
define('DB_USER', 'root');     // XAMPP default
define('DB_PASS', '645702');    // MySQL 8.0 root password

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Database connection failed. Start MySQL in XAMPP and import database/schema.sql. Detail: ' .
                htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}
