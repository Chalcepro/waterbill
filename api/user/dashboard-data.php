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
require_once '../includes/db_connect.php';

// Allow any authenticated non-admin to access (e.g., user, tenant, landlord)
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') === 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    // Get subscription status and end date
    $stmt = $pdo->prepare("SELECT end_date, status FROM subscriptions WHERE user_id = ? ORDER BY end_date DESC LIMIT 1");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch();
    
    $subscriptionStatus = 'Inactive';
    $subscriptionEnd = 'N/A';
    
    if ($subscription) {
        $endDate = new DateTime($subscription['end_date']);
        $now = new DateTime();
        
        if ($endDate > $now && $subscription['status'] === 'active') {
            $subscriptionStatus = 'Active';
            $subscriptionEnd = $endDate->format('M d, Y');
        } else {
            $subscriptionStatus = 'Expired';
            $subscriptionEnd = $endDate->format('M d, Y');
        }
    }
    
    // Get pending payments count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    $pendingPayments = $stmt->fetchColumn();
    
    // Get total paid this year
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE user_id = ? AND status = 'approved' AND YEAR(created_at) = YEAR(CURDATE())");
    $stmt->execute([$userId]);
    $totalPaid = $stmt->fetchColumn() ?: 0;
    
    echo json_encode([
        'success' => true,
        'subscription_status' => $subscriptionStatus,
        'subscription_end' => $subscriptionEnd,
        'pending_payments' => $pendingPayments,
        'total_paid' => number_format($totalPaid)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load dashboard data'
    ]);
}
?>
