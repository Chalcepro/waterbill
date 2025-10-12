<?php
// Test database connection script
require_once 'config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Database connection successful!<br>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
    $result = $stmt->fetch();
    echo "Number of users in database: " . $result['user_count'];
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    
    // Additional debugging info
    echo "<br><br>Connection details:<br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "Database: " . DB_NAME . "<br>";
    echo "Username: " . DB_USER . "<br>";
    echo "Password: " . (DB_PASS ? "[set]" : "[not set]") . "<br>";
}
?>
