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

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $firstName = $_POST['first_name'] ?? '';
    $middleName = $_POST['middle_name'] ?? '';
    $surname = $_POST['surname'] ?? '';
    $flatNo = $_POST['flat_no'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if (empty($firstName) || empty($middleName) || empty($surname) || empty($flatNo) || empty($email) || empty($phone) || empty($currentPassword)) {
        echo json_encode(['success' => false, 'message' => 'All fields except new password are required']);
        exit;
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Validate phone
    if (!preg_match('/^[0-9]{11}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Phone number must be 11 digits']);
        exit;
    }

    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }

    // Check if email/phone already exists for other users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (email = ? OR phone = ?) AND id != ?");
    $stmt->execute([$email, $phone, $userId]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email or phone number already in use by another user']);
        exit;
    }

    // Validate new password if provided
    if (!empty($newPassword)) {
        if (strlen($newPassword) < 8) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters']);
            exit;
        }
        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
            exit;
        }
    }

    // Update profile
    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, middle_name = ?, surname = ?, flat_no = ?, email = ?, phone = ?, password = ? WHERE id = ?");
        $stmt->execute([$firstName, $middleName, $surname, $flatNo, $email, $phone, $hashedPassword, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, middle_name = ?, surname = ?, flat_no = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$firstName, $middleName, $surname, $flatNo, $email, $phone, $userId]);
    }

    // Update session name
    $_SESSION['name'] = $firstName . ' ' . $middleName . ' ' . $surname;

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully!'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update profile. Please try again.'
    ]);
}
?>
