<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Include database connection - CORRECTED PATH
    require_once __DIR__ . '/../includes/db_connect.php';

    // Get user profile data
    $stmt = $pdo->prepare("
        SELECT username, email, phone, password, first_name, middle_name, surname, flat_no, role, status, created_at 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();

    if (!$profile) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }

    // Remove sensitive data
    unset($profile['password']);

    echo json_encode([
        'success' => true,
        'profile' => $profile
    ]);

} catch (Exception $e) {
    // Log the error but don't expose details to client
    error_log("Profile data error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load profile data'
    ]);
}
?>