<?php
/**
 * Router Class
 * จัดการ routing และ redirect ตาม role ของผู้ใช้
 */

class Router {
    
    public function __construct() {
        // Initialize router
    }
    
    /**
     * Handle incoming request
     */
    public function handleRequest() {
        $requestUri = $_SERVER['REQUEST_URI'];
        $basePath = parse_url(BASE_URL, PHP_URL_PATH);
        
        // Remove base path from request URI
        $path = str_replace($basePath, '', $requestUri);
        $path = parse_url($path, PHP_URL_PATH);
        
        // Remove leading slash
        $path = ltrim($path, '/');
        
        // If no path, redirect to login or dashboard
        if (empty($path)) {
            $this->redirectToDefault();
            return;
        }
        
        // Handle specific routes
        switch ($path) {
            case 'login.php':
                $this->handleLogin();
                break;
                
            case 'logout.php':
                $this->handleLogout();
                break;
                
            case 'dashboard.php':
                $this->handleDashboard();
                break;
                
            case 'customers.php':
                $this->handleCustomers();
                break;
                
            case 'orders.php':
                $this->handleOrders();
                break;
                
            case 'admin.php':
                $this->handleAdmin();
                break;
                
            case 'api/':
                $this->handleApi();
                break;
                
            default:
                $this->handle404();
                break;
        }
    }
    
    /**
     * Redirect to default page based on authentication
     */
    private function redirectToDefault() {
        if (isset($_SESSION['user_id'])) {
            // User is logged in, redirect to dashboard
            $this->redirect('dashboard.php');
        } else {
            // User is not logged in, redirect to login
            $this->redirect('login.php');
        }
    }
    
    /**
     * Handle login page
     */
    private function handleLogin() {
        if (isset($_SESSION['user_id'])) {
            // User is already logged in, redirect to dashboard
            $this->redirect('dashboard.php');
            return;
        }
        
        // Include login page
        include APP_VIEWS . 'auth/login.php';
    }
    
    /**
     * Handle logout
     */
    private function handleLogout() {
        // Clear session
        session_unset();
        session_destroy();
        
        // Redirect to login
        $this->redirect('login.php');
    }
    
    /**
     * Handle dashboard
     */
    private function handleDashboard() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login.php');
            return;
        }
        
        // Include appropriate dashboard based on role
        $roleName = $_SESSION['role_name'] ?? '';
        
        switch ($roleName) {
            case 'super_admin':
            case 'admin':
                include APP_VIEWS . 'dashboard/admin.php';
                break;
                
            case 'supervisor':
                include APP_VIEWS . 'dashboard/supervisor.php';
                break;
                
            case 'telesales':
                include APP_VIEWS . 'dashboard/telesales.php';
                break;
                
            default:
                include APP_VIEWS . 'dashboard/index.php';
                break;
        }
    }
    
    /**
     * Handle customers page
     */
    private function handleCustomers() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login.php');
            return;
        }
        
        // Check permission
        $roleName = $_SESSION['role_name'] ?? '';
        if (!in_array($roleName, ['super_admin', 'admin', 'supervisor', 'telesales'])) {
            $this->showError('Access Denied', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
            return;
        }
        
        include APP_VIEWS . 'customers/index.php';
    }
    
    /**
     * Handle orders page
     */
    private function handleOrders() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login.php');
            return;
        }
        
        // Check permission
        $roleName = $_SESSION['role_name'] ?? '';
        if (!in_array($roleName, ['super_admin', 'admin', 'supervisor', 'telesales'])) {
            $this->showError('Access Denied', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
            return;
        }
        
        include APP_VIEWS . 'orders/index.php';
    }
    
    /**
     * Handle admin page
     */
    private function handleAdmin() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login.php');
            return;
        }
        
        // Check permission - only admin and super_admin
        $roleName = $_SESSION['role_name'] ?? '';
        if (!in_array($roleName, ['super_admin', 'admin'])) {
            $this->showError('Access Denied', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
            return;
        }
        
        include APP_VIEWS . 'admin/index.php';
    }
    
    /**
     * Handle API requests
     */
    private function handleApi() {
        // Set JSON header
        header('Content-Type: application/json');
        
        // Get API endpoint
        $requestUri = $_SERVER['REQUEST_URI'];
        $apiPath = str_replace('/api/', '', $requestUri);
        $apiPath = parse_url($apiPath, PHP_URL_PATH);
        
        // Handle API endpoints
        switch ($apiPath) {
            case 'login':
                $this->handleApiLogin();
                break;
                
            case 'customers':
                $this->handleApiCustomers();
                break;
                
            case 'orders':
                $this->handleApiOrders();
                break;
                
            default:
                http_response_code(404);
                echo json_encode(['error' => 'API endpoint not found']);
                break;
        }
    }
    
    /**
     * Handle API login
     */
    private function handleApiLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password are required']);
            return;
        }
        
        // Create database and auth instances
        $db = new Database();
        $auth = new Auth($db);
        
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            echo json_encode(['success' => true, 'user' => $result['user']]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => $result['message']]);
        }
    }
    
    /**
     * Handle API customers
     */
    private function handleApiCustomers() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        $db = new Database();
        
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                // Get customers
                $sql = "SELECT * FROM customers WHERE is_active = 1 ORDER BY created_at DESC";
                $customers = $db->fetchAll($sql);
                echo json_encode(['success' => true, 'data' => $customers]);
                break;
                
            case 'POST':
                // Create customer
                $input = json_decode(file_get_contents('php://input'), true);
                $customerId = $db->insert('customers', $input);
                echo json_encode(['success' => true, 'customer_id' => $customerId]);
                break;
                
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
        }
    }
    
    /**
     * Handle API orders
     */
    private function handleApiOrders() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        $db = new Database();
        
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                // Get orders
                $sql = "SELECT o.*, c.first_name, c.last_name, u.full_name as created_by_name 
                        FROM orders o 
                        LEFT JOIN customers c ON o.customer_id = c.customer_id 
                        LEFT JOIN users u ON o.created_by = u.user_id 
                        WHERE o.is_active = 1 
                        ORDER BY o.created_at DESC";
                $orders = $db->fetchAll($sql);
                echo json_encode(['success' => true, 'data' => $orders]);
                break;
                
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
        }
    }
    
    /**
     * Handle 404 error
     */
    private function handle404() {
        http_response_code(404);
        include APP_VIEWS . 'errors/404.php';
    }
    
    /**
     * Show error page
     */
    private function showError($title, $message) {
        http_response_code(403);
        echo "<h1>{$title}</h1>";
        echo "<p>{$message}</p>";
        echo "<p><a href='" . BASE_URL . "'>กลับหน้าหลัก</a></p>";
    }
    
    /**
     * Redirect to URL
     */
    public function redirect($url) {
        header('Location: ' . BASE_URL . $url);
        exit;
    }
    
    /**
     * Get current URL
     */
    public function getCurrentUrl() {
        return $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Get base URL
     */
    public function getBaseUrl() {
        return BASE_URL;
    }
}
?> 