<?php
header('Content-Type: application/json');

// Allow from any origin (keep behavior but simplify)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

require_once __DIR__ . '/../includes/session_boot.php';
session_boot();

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    echo json_encode([
        'authenticated' => true,
        'user_id' => $_SESSION['user_id'],
        'role' => $_SESSION['role'],
        'name' => $_SESSION['name'] ?? 'User'
    ]);
} else {
    echo json_encode([
        'authenticated' => false
    ]);
}

