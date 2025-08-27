<?php
/**
 * Products API
 * API สำหรับจัดการสินค้า
 */

header('Content-Type: application/json; charset=utf-8');

// อนุญาต CORS สำหรับ development
if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

// จัดการ OPTIONS request สำหรับ CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/config.php';
require_once '../app/core/Database.php';

try {
    $db = new Database();
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'check_code':
            // ตรวจสอบรหัสสินค้าที่ซ้ำ
            $productCode = $_GET['product_code'] ?? '';
            
            if (empty($productCode)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาระบุรหัสสินค้า'
                ]);
                exit;
            }
            
            $sql = "SELECT COUNT(*) as count FROM products WHERE product_code = :product_code";
            $result = $db->fetchOne($sql, ['product_code' => $productCode]);
            
            echo json_encode([
                'success' => true,
                'exists' => $result['count'] > 0,
                'count' => $result['count'],
                'product_code' => $productCode
            ]);
            break;
            
        case 'list':
            // ดึงรายการสินค้าทั้งหมด
            $sql = "SELECT product_id, product_code, product_name, category, unit, cost_price, selling_price, stock_quantity, is_active 
                    FROM products 
                    WHERE is_active = 1 
                    ORDER BY product_name";
            
            $products = $db->fetchAll($sql);
            
            echo json_encode([
                'success' => true,
                'data' => $products,
                'total' => count($products)
            ]);
            break;
            
        case 'get_by_id':
            // ดึงข้อมูลสินค้าตาม ID
            $productId = $_GET['id'] ?? 0;
            
            if (empty($productId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาระบุ ID สินค้า'
                ]);
                exit;
            }
            
            $sql = "SELECT * FROM products WHERE product_id = :product_id AND is_active = 1";
            $product = $db->fetchOne($sql, ['product_id' => $productId]);
            
            if ($product) {
                echo json_encode([
                    'success' => true,
                    'data' => $product
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบสินค้า'
                ]);
            }
            break;
            
        case 'search':
            // ค้นหาสินค้า
            $query = $_GET['q'] ?? '';
            $category = $_GET['category'] ?? '';
            
            if (empty($query) && empty($category)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'กรุณาระบุคำค้นหาหรือหมวดหมู่'
                ]);
                exit;
            }
            
            $sql = "SELECT product_id, product_code, product_name, category, unit, cost_price, selling_price, stock_quantity 
                    FROM products 
                    WHERE is_active = 1";
            
            $params = [];
            
            if (!empty($query)) {
                $sql .= " AND (product_name LIKE :query OR product_code LIKE :query OR description LIKE :query)";
                $params['query'] = "%{$query}%";
            }
            
            if (!empty($category)) {
                $sql .= " AND category = :category";
                $params['category'] = $category;
            }
            
            $sql .= " ORDER BY product_name";
            
            $products = $db->fetchAll($sql, $params);
            
            echo json_encode([
                'success' => true,
                'data' => $products,
                'total' => count($products),
                'query' => $query,
                'category' => $category
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบ action ที่ระบุ',
                'available_actions' => [
                    'check_code' => 'ตรวจสอบรหัสสินค้าที่ซ้ำ',
                    'list' => 'ดึงรายการสินค้าทั้งหมด',
                    'get_by_id' => 'ดึงข้อมูลสินค้าตาม ID',
                    'search' => 'ค้นหาสินค้า'
                ]
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage()
    ]);
}
?>
