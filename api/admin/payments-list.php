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

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';
    $where = '';
    $params = [];
    if (in_array($status, ['pending','approved','rejected','completed','failed'], true)) {
        $map = [ 'completed' => 'approved' ];
        $dbStatus = $map[$status] ?? $status;
        $where = 'WHERE p.status = ?';
        $params[] = $dbStatus;
    }

    // Discover columns for payments and users
    $pCols = array_map(function($r){ return strtolower($r['Field']); }, $pdo->query("SHOW COLUMNS FROM payments")->fetchAll(PDO::FETCH_ASSOC));
    $uCols = array_map(function($r){ return strtolower($r['Field']); }, $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC));
    $hasP = function($c) use ($pCols){ return in_array(strtolower($c), $pCols, true); };
    $hasU = function($c) use ($uCols){ return in_array(strtolower($c), $uCols, true); };

    $sel = ['p.id', 'p.user_id'];
    $sel[] = $hasP('amount') ? 'p.amount' : '0 AS amount';
    $sel[] = $hasP('method') ? 'p.method' : "'Unknown' AS method";
    $sel[] = $hasP('status') ? 'p.status' : "'pending' AS status";
    if ($hasP('created_at')) $sel[] = 'p.created_at';
    elseif ($hasP('date')) $sel[] = 'p.date AS created_at';
    else $sel[] = 'NOW() AS created_at';
    $sel[] = $hasP('reference') ? 'p.reference' : "'' AS reference";

    if ($hasU('first_name')) $sel[] = 'u.first_name'; else $sel[] = "'' AS first_name";
    if ($hasU('middle_name')) $sel[] = 'u.middle_name'; else $sel[] = "'' AS middle_name";
    if ($hasU('surname')) $sel[] = 'u.surname'; else $sel[] = "'' AS surname";
    if ($hasU('email')) $sel[] = 'u.email'; else $sel[] = "'' AS email";
    if ($hasU('flat_no')) $sel[] = 'u.flat_no'; else $sel[] = "'' AS flat_no";

    $orderCol = $hasP('created_at') ? 'p.created_at' : ($hasP('date') ? 'p.date' : 'p.id');

    $sql = 'SELECT '.implode(',', $sel)." FROM payments p LEFT JOIN users u ON p.user_id = u.id $where ORDER BY $orderCol DESC LIMIT 500";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = array_map(function($r) use ($hasP) {
        $name = trim(($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '') . ' ' . ($r['surname'] ?? ''));
        // Normalize status to UI terms
        $status = strtolower($r['status'] ?? 'pending');
        if ($status === 'approved') $status = 'completed';
        
        // Get payment method, default to 'manual_upload' if not specified
        $method = strtolower($r['method'] ?? 'manual_upload');
        
        return [
            'id' => (int)$r['id'],
            'user_id' => (int)$r['user_id'],
            'user' => $name ?: 'User#'.$r['user_id'],
            'email' => $r['email'] ?? '',
            'flat_no' => $r['flat_no'] ?? '',
            'amount' => (float)$r['amount'],
            'method' => $method,  // Changed to match frontend expectation
            'payment_method' => $method,  // Also include as payment_method for backward compatibility
            'status' => $status,
            'reference' => $r['reference'] ?? '',
            'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s'),
            'date' => date('Y-m-d', strtotime($r['created_at'] ?? 'now')),
            'time' => date('H:i:s', strtotime($r['created_at'] ?? 'now'))
        ];
    }, $rows);

    echo json_encode(['success' => true, 'payments' => $out]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load payments']);
}
