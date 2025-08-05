<?php
/**
 * CRM SalesTracker - Login API
 * API สำหรับการเข้าสู่ระบบ
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Start session
session_start();

try {
    // Load configuration
    $configPath = '../../config/config.php';
    if (!file_exists($configPath)) {
        throw new Exception('Configuration file not found');
    }
    require_once $configPath;
    
    // Load core classes
    $dbPath = '../../app/core/Database.php';
    $authPath = '../../app/core/Auth.php';
    
    if (!file_exists($dbPath)) {
        throw new Exception('Database class file not found');
    }
    if (!file_exists($authPath)) {
        throw new Exception('Auth class file not found');
    }
    
    require_once $dbPath;
    require_once $authPath;
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Fallback to POST data
        $input = $_POST;
    }
    
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน',
            'debug' => 'Missing username or password'
        ]);
        exit;
    }
    
    // Initialize database and auth
    $db = new Database();
    $auth = new Auth($db);
    
    // Attempt login
    $loginResult = $auth->login($username, $password);
    
    if ($loginResult) {
        // Get user data
        $user = $auth->getCurrentUser();
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'user' => [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'role_name' => $user['role_name'],
                    'email' => $user['email']
                ],
                'redirect' => 'dashboard.php'
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'ไม่สามารถดึงข้อมูลผู้ใช้ได้',
                'debug' => 'User data not found after login'
            ]);
        }
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง',
            'debug' => 'Login failed'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?> 