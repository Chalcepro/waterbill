<?php
header('Content-Type: text/plain');

try {
    // Include config
    require_once __DIR__ . '/config.php';
    
    echo "Trying to connect to database...\n";
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
    echo "DB_USER: " . DB_USER . "\n";
    
    // Test connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ Successfully connected to database!\n\n";
    
    // List all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in database:\n";
    if (empty($tables)) {
        echo "❌ No tables found in the database\n";
    } else {
        foreach ($tables as $table) {
            echo "- " . $table . "\n";
        }
    }
    
    // Check if users table exists
    if (in_array('users', $tables)) {
        echo "\nUsers table structure:\n";
        $columns = $pdo->query("DESCRIBE users")->fetchAll();
        foreach ($columns as $col) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")";
            if ($col['Key'] === 'PRI') echo " [PRIMARY KEY]";
            if ($col['Null'] === 'NO') echo " [NOT NULL]";
            if ($col['Default'] !== null) echo " [DEFAULT: " . $col['Default'] . "]";
            echo "\n";
        }
    } else {
        echo "\n❌ Users table does not exist\n";
    }
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

// Check if config file exists and is readable
$configPath = __DIR__ . '/config.php';
echo "\nChecking config file...\n";
if (file_exists($configPath)) {
    echo "✅ Config file exists\n";
    if (is_readable($configPath)) {
        echo "✅ Config file is readable\n";
    } else {
        echo "❌ Config file is not readable\n";
    }
} else {
    echo "❌ Config file does not exist at: " . $configPath . "\n";
}

// Check if database connection file exists
$dbConnectPath = __DIR__ . '/api/includes/db_connect.php';
echo "\nChecking database connection file...\n";
if (file_exists($dbConnectPath)) {
    echo "✅ Database connection file exists\n";
    echo "Contents of db_connect.php:\n";
    echo "----------------------------------------\n";
    echo file_get_contents($dbConnectPath);
    echo "\n----------------------------------------\n";
} else {
    echo "❌ Database connection file does not exist at: " . $dbConnectPath . "\n";
}
