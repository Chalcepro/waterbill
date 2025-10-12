<?php
// Admin bulk notifications sender
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
// Force JSON-only responses
header('Content-Type: application/json');
header('Cache-Control: no-store');
@ini_set('display_errors', '0');
@ini_set('html_errors', '0');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    // Accept JSON or form-encoded
    $rawBody = file_get_contents('php://input');
    $input = json_decode($rawBody, true);
    if (!is_array($input) || empty($input)) {
        // fallback to POST form fields
        $input = $_POST;
    }
    if (!is_array($input) || empty($input)) {
        echo json_encode(['success' => false, 'error' => 'Empty request body']);
        exit;
    }

    $type = isset($input['type']) ? trim($input['type']) : 'general';
    $recipients = isset($input['recipients']) ? trim($input['recipients']) : 'all';
    $subject = isset($input['subject']) ? trim($input['subject']) : '';
    $message = isset($input['message']) ? trim($input['message']) : '';
    $userId = isset($input['user_id']) ? intval($input['user_id']) : 0;

    if (!$message) {
        echo json_encode(['success' => false, 'error' => 'Message is required']);
        exit;
    }

    // Prefix subject if provided
    if ($subject) {
        $message = $subject . ': ' . $message;
    }

    $targetUsers = [];

    if ($userId > 0) {
        $targetUsers = [ $userId ];
    } else {
        // Build recipients query with resilient fallbacks
        try {
            if ($recipients === 'active') {
                try {
                    $stmt = $pdo->query("SELECT id FROM users WHERE subscription_end >= CURDATE()");
                } catch (Exception $e) {
                    // Fallback if column doesn't exist
                    $stmt = $pdo->query("SELECT id FROM users");
                }
                $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($recipients === 'inactive') {
                try {
                    $stmt = $pdo->query("SELECT id FROM users WHERE subscription_end < CURDATE()");
                } catch (Exception $e) {
                    $stmt = $pdo->query("SELECT id FROM users");
                }
                $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($recipients === 'pending') {
                try {
                    $stmt = $pdo->query("SELECT DISTINCT user_id FROM payments WHERE status = 'pending'");
                    $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    if (!$targetUsers) {
                        $stmt = $pdo->query("SELECT id FROM users");
                        $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    }
                } catch (Exception $e) {
                    $stmt = $pdo->query("SELECT id FROM users");
                    $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
            } else { // all
                $stmt = $pdo->query("SELECT id FROM users");
                $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
        } catch (Exception $e) {
            // Final fallback
            try {
                $stmt = $pdo->query("SELECT id FROM users");
                $targetUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (Exception $ignored) {
                echo json_encode(['success' => false, 'error' => 'No recipients found']);
                exit;
            }
        }
    }

    if (!$targetUsers) {
        echo json_encode(['success' => false, 'error' => 'No recipients found']);
        exit;
    }

    // Create notifications via helper (which inserts and triggers outbound). Avoid duplicates.
    $sent = 0;
    foreach ($targetUsers as $uid) {
        try {
            // send_notification already inserts into notifications table
            send_notification($uid, $type, $message);
            $sent++;
        } catch (Exception $e) {
            // Continue with others even if one fails
        }
    }

    echo json_encode(['success' => true, 'count' => $sent]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
