<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Set session cookie parameters for consistency
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 3600 * 24 * 30, // 30 days
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../includes/db_connect.php';

try {
    $username = $_POST['username'] ?? '';
    $firstName = $_POST['first_name'] ?? '';
    $middleName = $_POST['middle_name'] ?? '';
    $surname = $_POST['surname'] ?? '';
    $flat_no = $_POST['flat_no'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    $errors = [];

    // Validation
    if (empty($username)) $errors[] = "Username is required";
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($middleName)) $errors[] = "Middle name is required";
    if (empty($surname)) $errors[] = "Surname is required";
    if (empty($flat_no)) $errors[] = "Flat number is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($phone) || !preg_match('/^[0-9]{11}$/', $phone)) $errors[] = "Valid 11-digit phone number is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($password !== $confirm) $errors[] = "Passwords don't match";
    if (empty($role) || !in_array($role, ['user', 'admin'])) $errors[] = "Please select a valid role";

    // Check if email/phone exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR phone = ?");
    $stmt->execute([$email, $phone]);
    if ($stmt->fetchColumn() > 0) $errors[] = "Email or phone already registered";

    // Check if username exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) $errors[] = "Username already taken";

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (first_name, middle_name, surname, flat_no, email, phone, password, role, username) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$firstName, $middleName, $surname, $flat_no, $email, $phone, $hashed, $role, $username])) {
        // Add notification for admin if function exists
        if (function_exists('add_notification')) {
            add_notification(1, 'registration', "New user registered: $firstName $middleName $surname ($username)");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! Please login with your credentials.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Registration failed. Please try again.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
?>
