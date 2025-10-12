<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/config.php';

try {
    // Try to connect using PDO
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Successfully connected to the database\n";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'fault_reports'");
    if ($stmt->rowCount() > 0) {
        echo "✅ fault_reports table exists\n";
        
        // Show table structure
        echo "\nTable structure:\n";
        $result = $pdo->query("DESCRIBE fault_reports");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN);
        print_r($columns);
        
        // Show sample data
        echo "\nSample data (first 3 rows):\n";
        $reports = $pdo->query("SELECT * FROM fault_reports LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        print_r($reports);
    } else {
        echo "❌ fault_reports table does not exist\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    
    // Show connection details for debugging
    echo "\nConnection details used:\n";
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
    echo "DB_USER: " . DB_USER . "\n";
    
    // Check if PDO MySQL driver is available
    if (!in_array('mysql', PDO::getAvailableDrivers())) {
        echo "\n❌ PDO MySQL driver is not enabled in your PHP installation.\n";
        echo "Please enable the 'pdo_mysql' extension in your php.ini file.\n";
    } else {
        echo "\n✅ PDO MySQL driver is enabled\n";
    }
}
