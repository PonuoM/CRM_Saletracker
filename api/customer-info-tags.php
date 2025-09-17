<?php
// Customer Info Tags API: list/add/remove tags bound to customer profile
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/services/TagService.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $action = $_GET['action'] ?? 'list';
    $service = new TagService();

    switch ($action) {
        case 'list': {
            $customerId = (int)($_GET['customer_id'] ?? 0);
            if ($customerId <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid customer_id']); break; }
            $tags = $service->getCustomerInfoTags($customerId);
            echo json_encode(['success' => true, 'data' => $tags]);
            break;
        }
        case 'add': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); break; }
            $payload = json_decode(file_get_contents('php://input'), true) ?: [];
            $customerId = (int)($payload['customer_id'] ?? 0);
            $tagName = trim((string)($payload['tag_name'] ?? ''));
            $tagColor = trim((string)($payload['tag_color'] ?? '#6c757d'));
            if ($customerId <= 0 || $tagName === '') { echo json_encode(['success'=>false,'message'=>'Invalid parameters']); break; }
            $res = $service->addCustomerInfoTag($customerId, $userId, $tagName, $tagColor);
            echo json_encode($res + ['success' => $res['success'] ?? false]);
            break;
        }
        case 'remove': {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); break; }
            $payload = json_decode(file_get_contents('php://input'), true) ?: [];
            $customerId = (int)($payload['customer_id'] ?? 0);
            $tagName = trim((string)($payload['tag_name'] ?? ''));
            if ($customerId <= 0 || $tagName === '') { echo json_encode(['success'=>false,'message'=>'Invalid parameters']); break; }
            $res = $service->removeCustomerInfoTag($customerId, $tagName);
            echo json_encode($res + ['success' => $res['success'] ?? false]);
            break;
        }
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

