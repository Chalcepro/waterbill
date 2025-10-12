<?php
require_once 'config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Successfully connected to the database.<br><br>";
    
    // Check if users table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
    if (empty($tables)) {
        die("Error: 'users' table does not exist in the database.");
    }
    
    // Get column names
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in users table: " . implode(", ", $columns) . "<br><br>";
    
    // Try to select some data
    $users = $pdo->query("SELECT * FROM users LIMIT 5")->fetchAll();
    echo "Found " . count($users) . " users in the database.<br>";
    echo "<pre>" . print_r($users, true) . "</pre>";
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
