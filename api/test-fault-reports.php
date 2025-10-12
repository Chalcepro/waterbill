<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

// Include config
require_once __DIR__ . '/../config.php';

// Test database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Get filter parameters
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // First, check if users table exists and get its columns
    $usersTableExists = $conn->query("SHOW TABLES LIKE 'users'")->num_rows > 0;
    
    // Build the base query
    $whereClause = [];
    $params = [];
    $types = '';
    
    // Add status filter
    if (!empty($status)) {
        $whereClause[] = "r.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    // Add search filter
    if (!empty($search)) {
        $searchTerm = "%$search%";
        $whereClause[] = "(r.title LIKE ? OR r.description LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'ss';
    }
    
    $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';
    
    if ($usersTableExists) {
        // Get users table columns
        $columnsResult = $conn->query("SHOW COLUMNS FROM users");
        $userColumns = [];
        while ($col = $columnsResult->fetch_assoc()) {
            $userColumns[] = $col['Field'];
        }
        
        // Build the query based on available columns
        $selectFields = [];
        if (in_array('username', $userColumns)) {
            $selectFields[] = 'u.username as user_name';
        }
        if (in_array('email', $userColumns)) {
            $selectFields[] = 'u.email as user_email';
        }
        
        // Handle name fields
        $nameFields = [];
        if (in_array('first_name', $userColumns)) $nameFields[] = 'u.first_name';
        if (in_array('last_name', $userColumns)) $nameFields[] = 'u.last_name';
        
        if (count($nameFields) > 0) {
            $selectFields[] = 'CONCAT(' . implode(", ' ', ", $nameFields) . ') as user_full_name';
        }
        
        $query = "
            SELECT 
                r.*,
                " . (count($selectFields) > 0 ? implode(",\n                ", $selectFields) : "'N/A' as user_name, '' as user_email, 'N/A' as user_full_name") . "
            FROM fault_reports r
            LEFT JOIN users u ON r.user_id = u.id
            $whereSQL
            ORDER BY r.created_at DESC
            LIMIT 10
        ";
    } else {
        // Fallback if users table doesn't exist
        $query = "SELECT *, 'N/A' as user_name, '' as user_email, 'N/A' as user_full_name 
                 FROM fault_reports 
                 $whereSQL
                 ORDER BY created_at DESC 
                 LIMIT 10";
    }
    
    // Prepare and execute the query with parameters if any
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $reports = [];
    while ($row = $result->fetch_assoc()) {
        // Format the report data for the frontend
        $userName = !empty($row['user_full_name']) ? $row['user_full_name'] : 
                  (!empty($row['user_name']) ? $row['user_name'] : 'N/A');
        
        $report = [
            'id' => $row['id'],
            'user_id' => $row['user_id'] ?? null,
            'title' => $row['title'] ?? 'No title',
            'description' => $row['description'] ?? 'No description',
            'photo_path' => $row['photo_path'] ?? null,
            'status' => $row['status'] ?? 'open',
            'created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
            'user_name' => $userName,
            'email' => $row['user_email'] ?? ''
        ];
        $reports[] = $report;
    }
    
    // Return the data
    echo json_encode([
        'success' => true,
        'data' => $reports,
        'count' => count($reports)
    ]);
    
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
