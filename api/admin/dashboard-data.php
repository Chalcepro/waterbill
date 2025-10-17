<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
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
require_once __DIR__ . '/../db_connect.php';

// Check if user is logged in (any role)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get user info including flat_no
    $userQuery = $pdo->prepare("SELECT id, username, email, first_name, middle_name, surname, flat_no FROM users WHERE id = ?");
    $userQuery->execute([$user_id]);
    $user = $userQuery->fetch();

    if (!$user) {
        throw new Exception('User not found');
    }

    // Initialize default values
    $subscription_status = 'Inactive';
    $subscription_end = null;
    $pending_payments = 0;
    $total_paid = 0;
    $has_subscription = false;

    // Check for active subscription (using your actual table structure)
    $subscriptionQuery = $pdo->prepare("
        SELECT end_date, status 
        FROM subscriptions 
        WHERE user_id = ? AND status = 'active'
        ORDER BY end_date DESC 
        LIMIT 1
    ");
    $subscriptionQuery->execute([$user_id]);
    $subscription = $subscriptionQuery->fetch();

    if ($subscription) {
        $has_subscription = true;
        $end_date = new DateTime($subscription['end_date']);
        $now = new DateTime();
        
        if ($end_date > $now) {
            $subscription_status = 'Active';
            $subscription_end = $subscription['end_date'];
        } else {
            $subscription_status = 'Expired';
            $subscription_end = $subscription['end_date'];
        }
    }

    // Get payment statistics (using your actual payments table structure)
    $paymentStatsQuery = $pdo->prepare("
        SELECT 
            COUNT(*) as total_payments,
            COALESCE(SUM(amount), 0) as total_paid,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_payments
        FROM payments 
        WHERE user_id = ?
    ");
    $paymentStatsQuery->execute([$user_id]);
    $paymentStats = $paymentStatsQuery->fetch();

    if ($paymentStats) {
        $pending_payments = $paymentStats['pending_payments'] ?? 0;
        $total_paid = $paymentStats['total_paid'] ?? 0;
    }

    $response = [
        'success' => true,
        'subscription_status' => $subscription_status,
        'subscription_end' => $subscription_end ? date('M d, Y', strtotime($subscription_end)) : null,
        'pending_payments' => $pending_payments,
        'total_paid' => $total_paid,
        'user_name' => trim($user['first_name'] . ' ' . ($user['middle_name'] ?? '') . ' ' . $user['surname']),
        'user_email' => $user['email'],
        'user_flat' => $user['flat_no'] ?? 'N/A',
        'has_subscription' => $has_subscription,
        'raw_subscription_end' => $subscription_end, // For the countdown timer
        'no_subscription' => !$has_subscription // Explicit flag for no subscription
    ];

    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load dashboard data: ' . $e->getMessage(),
        'no_subscription' => true
    ]);
}
?>