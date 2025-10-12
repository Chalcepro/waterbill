<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

// Include config
require_once __DIR__ . '/../config.php';

// Get report ID from query string
$reportId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$reportId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Report ID is required']);
    exit;
}

// Test database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Get report details
    $stmt = $conn->prepare("SELECT * FROM fault_reports WHERE id = ?");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit;
    }
    
    $report = $result->fetch_assoc();
    
    // Check if users table exists before trying to query it
    $usersTableExists = $conn->query("SHOW TABLES LIKE 'users'")->num_rows > 0;
    
    if ($usersTableExists && !empty($report['user_id'])) {
        $userId = $report['user_id'];
        $userStmt = $conn->prepare("SELECT id, username, email, first_name, last_name FROM users WHERE id = ?");
        if ($userStmt) {
            $userStmt->bind_param("i", $userId);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            
            if ($userResult && $userResult->num_rows > 0) {
                $report['user'] = $userResult->fetch_assoc();
            }
            $userStmt->close();
        }
    }
    
    // Return the data
    echo json_encode([
        'success' => true,
        'data' => $report
    ]);
    
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
