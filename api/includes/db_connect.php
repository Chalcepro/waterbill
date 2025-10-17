<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'waterbill_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP default is empty password

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Log error but don't display to user
    error_log("Database connection error: " . $e->getMessage());
    throw new Exception("Database connection failed");
}
?>