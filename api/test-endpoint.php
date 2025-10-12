<?php
// Test endpoint to check database connection and authentication
header('Content-Type: application/json');

// Simulate admin session for testing
$_SESSION['is_admin'] = true;

// Database connection test
try {
    require_once __DIR__ . '/../config.php';
    
    // Test MySQLi connection
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_error) {
        throw new Exception("MySQLi Connection failed: " . $mysqli->connect_error);
    }
    
    // Test query
    $result = $mysqli->query("SELECT COUNT(*) as count FROM fault_reports");
    $count = $result ? $result->fetch_assoc()['count'] : 0;
    
    $response = [
        'success' => true,
        'message' => 'Database connection successful',
        'table_exists' => $result !== false,
        'record_count' => (int)$count,
        'php_version' => phpversion(),
        'extensions' => get_loaded_extensions()
    ];
    
    $mysqli->close();
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'php_version' => phpversion(),
        'extensions' => get_loaded_extensions()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
