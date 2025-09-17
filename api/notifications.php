<?php
// Notifications API - minimal endpoints: list, mark_read, mark_all_read
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = new Database();
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $action = $_GET['action'] ?? 'list';

    switch ($action) {
        case 'list':
            $rows = $db->fetchAll(
                "SELECT id, user_id, type, title, message, is_read, created_at
                 FROM notifications
                 WHERE user_id = ?
                 ORDER BY created_at DESC
                 LIMIT 50",
                [$userId]
            );
            echo json_encode(['success' => true, 'data' => $rows]);
            break;

        case 'mark_read':
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid id']); exit; }
            $db->update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', [$id, $userId]);
            echo json_encode(['success' => true]);
            break;

        case 'mark_all_read':
            $db->execute("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$userId]);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

