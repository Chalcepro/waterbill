<?php
// Test MySQLi database connection
require_once 'config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully with MySQLi";

// Check if fault_reports table exists
$result = $conn->query("SHOW TABLES LIKE 'fault_reports'");
if ($result->num_rows > 0) {
    echo "\n\nFault reports table exists. Structure:\n";
    
    // Get table structure
    $structure = $conn->query("DESCRIBE fault_reports");
    while($row = $structure->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    
    // Count records
    $count = $conn->query("SELECT COUNT(*) as count FROM fault_reports")->fetch_assoc();
    echo "\nTotal records: " . $count['count'];
} else {
    echo "\n\nFault reports table does not exist.";
}

$conn->close();
?>
