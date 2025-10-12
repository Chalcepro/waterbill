<?php
// Simple API for notifications (fetch/send)
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';
// Force JSON-only responses and prevent HTML error output from breaking JSON
header('Content-Type: application/json');
header('Cache-Control: no-store');
@ini_set('display_errors', '0');
@ini_set('html_errors', '0');

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // Fetch notifications for a user (admin can fetch all)
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        // Sanitize limit (MySQL prohibits placeholders in LIMIT in native prepares)
        $limit = max(1, min(100, $limit));

        if ($user_id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT $limit");
            $stmt->execute([$user_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Admin: fetch all
            $sql = "SELECT n.*, u.first_name, u.middle_name, u.surname, u.flat_no, u.username 
                    FROM notifications n 
                    LEFT JOIN users u ON n.user_id = u.id 
                    ORDER BY n.id DESC 
                    LIMIT $limit";
            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode(['success' => true, 'notifications' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        // Send notification (admin or user)
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
        $type = isset($input['type']) ? $input['type'] : 'general';
        $message = isset($input['message']) ? $input['message'] : '';
        if ($user_id > 0 && $message) {
            send_notification($user_id, $type, $message);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing user_id or message']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid request']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
