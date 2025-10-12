<?php
require_once 'config.php';

// Try to connect using MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("MySQLi Connection failed: " . $conn->connect_error);
}
echo "MySQLi Connected successfully\n";

// Try to connect using PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "PDO Connected successfully\n";
} catch(PDOException $e) {
    echo "PDO Connection failed: " . $e->getMessage() . "\n";
}

// Check if fault_reports table exists
$result = $conn->query("SHOW TABLES LIKE 'fault_reports'");
if ($result->num_rows === 0) {
    echo "The 'fault_reports' table does not exist.\n";
} else {
    echo "'fault_reports' table exists.\n";
    
    // Get table structure
    $result = $conn->query("DESCRIBE fault_reports");
    echo "\nTable structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    
    // Count records
    $result = $conn->query("SELECT COUNT(*) as count FROM fault_reports");
    $row = $result->fetch_assoc();
    echo "\nNumber of records in fault_reports: " . $row['count'] . "\n";
}

$conn->close();
?>
