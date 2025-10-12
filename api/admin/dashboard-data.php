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
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get stats
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $pendingPayments = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
    $activeSubscriptions = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE end_date > CURDATE() AND status = 'active'")->fetchColumn();
    $reportedFaults = $pdo->query("SELECT COUNT(*) FROM fault_reports WHERE status = 'open'")->fetchColumn();

    // Get recent payments (limit to last 24 hours, max 5)
    $recentPayments = $pdo->prepare("SELECT p.*, u.first_name, u.middle_name, u.surname 
        FROM payments p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'pending' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        ORDER BY p.created_at DESC
        LIMIT 5");
    $recentPayments->execute();
    $payments = $recentPayments->fetchAll();

    // Format payments for frontend
    $formattedPayments = array_map(function($payment) {
        return [
            'user_name' => trim($payment['first_name'] . ' ' . $payment['middle_name']),
            'amount' => number_format($payment['amount']),
            'method' => ucfirst($payment['method']),
            'date' => date('M d, Y', strtotime($payment['created_at'])),
            'status' => $payment['status']
        ];
    }, $payments);

    // Get system settings
    $settings = $pdo->query("SELECT name, value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Format settings for display
    $formattedSettings = [
        'Minimum Payment' => '₦' . number_format($settings['min_payment'] ?? 0),
        'Company Name' => $settings['company_name'] ?? 'WaterBill NG',
        'Support Email' => $settings['support_email'] ?? 'N/A',
        'Auto-Approval' => ($settings['auto_approval'] ?? '0') === '1' ? 'Enabled' : 'Disabled'
    ];

    echo json_encode([
        'success' => true,
        'total_users' => $totalUsers,
        'pending_payments' => $pendingPayments,
        'active_subscriptions' => $activeSubscriptions,
        'open_faults' => $reportedFaults,
        'recent_payments' => $formattedPayments,
        'system_settings' => $formattedSettings
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load dashboard data'
    ]);
}
?>
