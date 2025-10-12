<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/session_boot.php';
session_boot();
require_once __DIR__ . '/../includes/db_connect.php';

try {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
        exit;
    }

    // Try to find user by username, email, or phone
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? OR phone = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['surname'];
        
        echo json_encode([
            'success' => true,
            'role' => $user['role'],
            'name' => $_SESSION['name'],
            'message' => 'Login successful'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid credentials. Please check your username/email/phone and password.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
?>
