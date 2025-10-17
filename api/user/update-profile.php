<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
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

    // Get POST data - handle both JSON and form data
    $input = [];
    if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $input = $_POST;
    }
    
    $first_name = trim($input['first_name'] ?? '');
    $middle_name = trim($input['middle_name'] ?? '');
    $surname = trim($input['surname'] ?? '');
    $flat_no = trim($input['flat_no'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $current_password = $input['current_password'] ?? '';
    $new_password = $input['new_password'] ?? '';

    // Validate required fields
    if (empty($first_name) || empty($surname) || empty($email) || empty($phone) || empty($current_password)) {
        echo json_encode(['success' => false, 'message' => 'All fields except middle name are required']);
        exit;
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Get current user data
    $stmt = $pdo->prepare("SELECT password, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }

    // Check if email is already taken by another user
    $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $emailCheck->execute([$email, $user_id]);
    if ($emailCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email is already taken by another user']);
        exit;
    }

    // Prepare update query
    $updateFields = [
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'surname' => $surname,
        'flat_no' => $flat_no,
        'email' => $email,
        'phone' => $phone
    ];

    // Update password if provided
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
            exit;
        }
        $updateFields['password'] = password_hash($new_password, PASSWORD_DEFAULT);
    }

    // Build dynamic update query
    $setClause = [];
    $params = [];
    foreach ($updateFields as $field => $value) {
        $setClause[] = "$field = ?";
        $params[] = $value;
    }
    $params[] = $user_id;

    $sql = "UPDATE users SET " . implode(', ', $setClause) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);

} catch (Exception $e) {
    // Log the error but don't expose details to client
    error_log("Update profile error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update profile'
    ]);
}
?>