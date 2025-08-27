<?php
/**
 * AdminController Class
 * จัดการการเรียกใช้ Admin Services และหน้า Admin Management
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../services/CustomerService.php';
require_once __DIR__ . '/../services/OrderService.php';
require_once __DIR__ . '/../services/WorkflowService.php'; // Added for WorkflowService

class AdminController {
    private $db;
    private $auth;
    private $customerService;
    private $orderService;
    
    public function __construct() {
        $this->db = new Database();
        $this->auth = new Auth($this->db);
        $this->customerService = new CustomerService();
        $this->orderService = new OrderService();
    }
    
    /**
     * ตรวจสอบสิทธิ์ Admin
     */
    private function checkAdminPermission() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        
        $roleName = $_SESSION['role_name'] ?? '';
        if (!in_array($roleName, ['admin', 'super_admin'])) {
            header('Location: dashboard.php');
            exit;
        }
    }
    
    /**
     * แสดงหน้า Admin Dashboard
     */
    public function index() {
        $this->checkAdminPermission();
        
        $userId = $_SESSION['user_id'];
        
        // ดึงข้อมูลสถิติสำหรับ Admin
        $stats = [
            'total_users' => $this->getTotalUsers(),
            'total_customers' => $this->getTotalCustomers(),
            'total_orders' => $this->getTotalOrders(),
            'total_products' => $this->getTotalProducts(),
            'recent_activities' => $this->getRecentActivities(),
            'system_health' => $this->getSystemHealth()
        ];
        
        // ดึงข้อมูลผู้ใช้ทั้งหมด
        $users = $this->auth->getAllUsers();
        
        // ดึงข้อมูลสินค้าทั้งหมด
        $products = $this->getAllProducts();
        
        // ดึงข้อมูลการตั้งค่าระบบ
        $settings = $this->getSystemSettings();
        
        // Set page title and prepare content for layout
        $pageTitle = 'Admin Dashboard - CRM SalesTracker';
        $currentPage = 'admin';

        // Capture admin content
        ob_start();
        include __DIR__ . '/../views/admin/index.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * จัดการผู้ใช้
     */
    public function users() {
        $this->checkAdminPermission();
        
        $subaction = $_GET['subaction'] ?? 'list';
        
        switch ($subaction) {
            case 'create':
                $this->createUser();
                break;
            case 'edit':
                $this->editUser();
                break;
            case 'delete':
                $this->deleteUser();
                break;
            default:
                $this->listUsers();
                break;
        }
    }
    
    /**
     * แสดงรายการผู้ใช้
     */
    private function listUsers() {
        $users = $this->auth->getAllUsers();
        $roles = $this->getAllRoles();
        $companies = $this->getAllCompanies();
        
        // Set page title and prepare content for layout
        $pageTitle = 'จัดการผู้ใช้ - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'users';

        // Capture users content
        ob_start();
        include __DIR__ . '/../views/admin/users/index.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * สร้างผู้ใช้ใหม่
     */
    private function createUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userData = [
                'username' => $_POST['username'] ?? '',
                'password' => $_POST['password'] ?? '',
                'full_name' => $_POST['full_name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'role_id' => $_POST['role_id'] ?? '',
                'company_id' => $_POST['company_id'] ?? null
            ];
            
            $result = $this->auth->createUser($userData);
            
            if ($result['success']) {
                header('Location: admin.php?action=users&message=user_created');
                exit;
            } else {
                $error = $result['message'];
            }
        }
        
        $roles = $this->getAllRoles();
        $companies = $this->getAllCompanies();

        // Set page title and prepare content for layout
        $pageTitle = 'สร้างผู้ใช้ใหม่ - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'users';

        // Capture create user content
        ob_start();
        include __DIR__ . '/../views/admin/users/create.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * แก้ไขผู้ใช้
     */
    private function editUser() {
        $userId = $_GET['id'] ?? 0;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userData = [
                'user_id' => $userId,
                'username' => $_POST['username'] ?? '',
                'full_name' => $_POST['full_name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'role_id' => $_POST['role_id'] ?? '',
                'company_id' => $_POST['company_id'] ?? null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            // ถ้ามีการเปลี่ยนรหัสผ่าน
            if (!empty($_POST['password'])) {
                $userData['password'] = $_POST['password'];
            }
            
            $result = $this->auth->updateUser($userData);
            
            if ($result['success']) {
                header('Location: admin.php?action=users&message=user_updated');
                exit;
            } else {
                $error = $result['message'];
            }
        }
        
        $user = $this->auth->getUserById($userId);
        $roles = $this->getAllRoles();
        $companies = $this->getAllCompanies();

        // Set page title and prepare content for layout
        $pageTitle = 'แก้ไขผู้ใช้ - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'users';

        // Capture edit user content
        ob_start();
        include __DIR__ . '/../views/admin/users/edit.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * ลบผู้ใช้
     */
    private function deleteUser() {
        $userId = $_GET['id'] ?? 0;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->auth->deleteUser($userId);
            
            if ($result['success']) {
                header('Location: admin.php?action=users&message=user_deleted');
                exit;
            } else {
                $error = $result['message'];
            }
        }
        
        $user = $this->auth->getUserById($userId);

        // Set page title and prepare content for layout
        $pageTitle = 'ลบผู้ใช้ - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'users';

        // Capture delete user content
        ob_start();
        include __DIR__ . '/../views/admin/users/delete.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * จัดการสินค้า
     */
    public function products() {
        $this->checkAdminPermission();
        
        $subaction = $_GET['subaction'] ?? 'list';
        
        switch ($subaction) {
            case 'create':
                $this->createProduct();
                break;
            case 'edit':
                $this->editProduct();
                break;
            case 'delete':
                $this->deleteProduct();
                break;
            case 'import':
                $this->importProducts();
                break;
            case 'export':
                $this->exportProducts();
                break;
            default:
                $this->listProducts();
                break;
        }
    }
    
    /**
     * แสดงรายการสินค้า
     */
    private function listProducts() {
        $products = $this->getAllProducts();
        $categories = $this->getProductCategories();
        
        // Set page title and prepare content for layout
        $pageTitle = 'จัดการสินค้า - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'products';

        // Capture products content
        ob_start();
        include __DIR__ . '/../views/admin/products/index.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * สร้างสินค้าใหม่
     */
    private function createProduct() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productData = [
                'product_code' => $_POST['product_code'] ?? '',
                'product_name' => $_POST['product_name'] ?? '',
                'category' => $_POST['category'] ?? '',
                'description' => $_POST['description'] ?? '',
                'unit' => $_POST['unit'] ?? 'ชิ้น',
                'cost_price' => $_POST['cost_price'] ?? 0,
                'selling_price' => $_POST['selling_price'] ?? 0,
                'stock_quantity' => $_POST['stock_quantity'] ?? 0
            ];
            
            // ตรวจสอบรหัสสินค้าที่ซ้ำก่อนสร้าง
            $duplicateCheck = $this->checkDuplicateProductCode($productData['product_code']);
            if ($duplicateCheck['exists']) {
                $error = 'รหัสสินค้า "' . $productData['product_code'] . '" มีอยู่แล้วในระบบ กรุณาใช้รหัสใหม่';
            } else {
                $result = $this->createProductRecord($productData);
                
                if ($result['success']) {
                    header('Location: admin.php?action=products&message=product_created');
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
        
        $categories = $this->getProductCategories();
        include __DIR__ . '/../views/admin/products/create.php';
    }
    
    /**
     * แก้ไขสินค้า
     */
    private function editProduct() {
        $productId = $_GET['id'] ?? 0;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productData = [
                'product_id' => $productId,
                'product_code' => $_POST['product_code'] ?? '',
                'product_name' => $_POST['product_name'] ?? '',
                'category' => $_POST['category'] ?? '',
                'description' => $_POST['description'] ?? '',
                'unit' => $_POST['unit'] ?? 'ชิ้น',
                'cost_price' => $_POST['cost_price'] ?? 0,
                'selling_price' => $_POST['selling_price'] ?? 0,
                'stock_quantity' => $_POST['stock_quantity'] ?? 0,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            $result = $this->updateProductRecord($productData);
            
            if ($result['success']) {
                header('Location: admin.php?action=products&message=product_updated');
                exit;
            } else {
                $error = $result['message'];
            }
        }
        
        $product = $this->getProductById($productId);
        $categories = $this->getProductCategories();
        
        include __DIR__ . '/../views/admin/products/edit.php';
    }
    
    /**
     * ลบสินค้า
     */
    private function deleteProduct() {
        $productId = $_GET['id'] ?? 0;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->deleteProductRecord($productId);
            
            if ($result['success']) {
                header('Location: admin.php?action=products&message=product_deleted');
                exit;
            } else {
                $error = $result['message'];
            }
        }
        
        $product = $this->getProductById($productId);
        include __DIR__ . '/../views/admin/products/delete.php';
    }
    
    /**
     * นำเข้าสินค้า
     */
    private function importProducts() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $result = $this->importProductsFromCSV($_FILES['csv_file']['tmp_name']);
                
                if ($result['success']) {
                    header('Location: admin.php?action=products&message=products_imported&count=' . $result['count']);
                    exit;
                } else {
                    $error = $result['message'];
                }
            } else {
                $error = 'กรุณาเลือกไฟล์ CSV';
            }
        }
        
        include __DIR__ . '/../views/admin/products/import.php';
    }
    
    /**
     * ส่งออกสินค้า
     */
    private function exportProducts() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // ดาวน์โหลดไฟล์
            $products = $this->getAllProducts();
            
            // สร้างไฟล์ CSV
            $filename = 'products_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // เพิ่ม BOM สำหรับ UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // เขียน header
            fputcsv($output, [
                'รหัสสินค้า', 'ชื่อสินค้า', 'หมวดหมู่', 'คำอธิบาย', 
                'หน่วย', 'ต้นทุน', 'ราคาขาย', 'จำนวนคงเหลือ', 'สถานะ'
            ]);
            
            // เขียนข้อมูล
            foreach ($products as $product) {
                fputcsv($output, [
                    $product['product_code'],
                    $product['product_name'],
                    $product['category'],
                    $product['description'],
                    $product['unit'],
                    $product['cost_price'],
                    $product['selling_price'],
                    $product['stock_quantity'],
                    $product['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน'
                ]);
            }
            
            fclose($output);
            exit;
        }
        
        // แสดงหน้า export
        $products = $this->getAllProducts();
        $categories = $this->getProductCategories();
        
        // คำนวณสถิติ
        $totalProducts = count($products);
        $activeProducts = count(array_filter($products, function($p) { return $p['is_active']; }));
        $inactiveProducts = $totalProducts - $activeProducts;
        $outOfStockProducts = count(array_filter($products, function($p) { return $p['stock_quantity'] <= 0; }));
        
        include __DIR__ . '/../views/admin/products/export.php';
    }
    
    /**
     * จัดการการตั้งค่าระบบ
     */
    public function settings() {
        $this->checkAdminPermission();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settings = [
                'customer_grade_a_plus' => $_POST['customer_grade_a_plus'] ?? 50000,
                'customer_grade_a' => $_POST['customer_grade_a'] ?? 10000,
                'customer_grade_b' => $_POST['customer_grade_b'] ?? 5000,
                'customer_grade_c' => $_POST['customer_grade_c'] ?? 2000,
                'new_customer_recall_days' => $_POST['new_customer_recall_days'] ?? 30,
                'existing_customer_recall_days' => $_POST['existing_customer_recall_days'] ?? 90,
                'waiting_basket_days' => $_POST['waiting_basket_days'] ?? 30
            ];

            $result = $this->updateSystemSettings($settings);

            if ($result['success']) {
                header('Location: admin.php?action=settings&message=settings_updated');
                exit;
            } else {
                $error = $result['message'];
            }
        }

        $settings = $this->getSystemSettings();

        // Set page title and prepare content for layout
        $pageTitle = 'ตั้งค่าระบบ - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'settings';

        // Capture settings content
        ob_start();
        include __DIR__ . '/../views/admin/settings.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }

    /**
     * แสดงหน้า Workflow Management
     */
    public function workflow() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        // ตรวจสอบสิทธิ์ (เฉพาะ Admin และ Supervisor)
        $allowedRoles = ['admin', 'supervisor', 'super_admin'];
        if (!in_array($_SESSION['role_name'] ?? '', $allowedRoles)) {
            header('Location: dashboard.php');
            exit;
        }

        // Load WorkflowService
        require_once __DIR__ . '/../services/WorkflowService.php';
        $workflowService = new WorkflowService();

        // ดึงสถิติ
        $stats = $workflowService->getWorkflowStats();

        // ส่งข้อมูลไปยัง view
        $_SESSION['workflow_stats'] = $stats;

        // Set page title and prepare content for layout
        $pageTitle = 'Workflow Management - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'workflow';

        // Capture workflow content
        ob_start();
        include __DIR__ . '/../views/admin/workflow.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    // ==================== HELPER METHODS ====================
    
    /**
     * ดึงจำนวนผู้ใช้ทั้งหมด
     */
    private function getTotalUsers() {
        $sql = "SELECT COUNT(*) as total FROM users WHERE is_active = 1";
        $result = $this->db->fetchOne($sql);
        return $result['total'] ?? 0;
    }
    
    /**
     * ดึงจำนวนลูกค้าทั้งหมด
     */
    private function getTotalCustomers() {
        $sql = "SELECT COUNT(*) as total FROM customers WHERE is_active = 1";
        $result = $this->db->fetchOne($sql);
        return $result['total'] ?? 0;
    }
    
    /**
     * ดึงจำนวนคำสั่งซื้อทั้งหมด
     */
    private function getTotalOrders() {
        $sql = "SELECT COUNT(*) as total FROM orders WHERE is_active = 1";
        $result = $this->db->fetchOne($sql);
        return $result['total'] ?? 0;
    }
    
    /**
     * ดึงจำนวนสินค้าทั้งหมด
     */
    private function getTotalProducts() {
        $sql = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
        $result = $this->db->fetchOne($sql);
        return $result['total'] ?? 0;
    }
    
    /**
     * ดึงกิจกรรมล่าสุด
     */
    private function getRecentActivities() {
        $sql = "SELECT ca.*, u.full_name as user_name, c.first_name, c.last_name
                FROM customer_activities ca
                LEFT JOIN users u ON ca.user_id = u.user_id
                LEFT JOIN customers c ON ca.customer_id = c.customer_id
                ORDER BY ca.created_at DESC
                LIMIT 10";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * ดึงสถานะระบบ
     */
    private function getSystemHealth() {
        try {
            $this->db->query("SELECT 1");
            $dbConnected = true;
        } catch (Exception $e) {
            $dbConnected = false;
        }
        
        $health = [
            'database_connection' => $dbConnected,
            'php_version' => phpversion(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        ];
        
        return $health;
    }
    
    /**
     * ดึงบทบาททั้งหมด
     */
    private function getAllRoles() {
        $sql = "SELECT * FROM roles ORDER BY role_name";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * ดึงบริษัททั้งหมด
     */
    private function getAllCompanies() {
        $sql = "SELECT * FROM companies WHERE is_active = 1 ORDER BY company_name";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * จัดการบริษัท
     */
    public function companies() {
        $this->checkAdminPermission();
        
        $subaction = $_GET['subaction'] ?? 'list';
        
        switch ($subaction) {
            case 'create':
                $this->createCompany();
                break;
            case 'edit':
                $this->editCompany();
                break;
            case 'delete':
                $this->deleteCompany();
                break;
            default:
                $this->listCompanies();
                break;
        }
    }
    
    /**
     * แสดงรายการบริษัท
     */
    private function listCompanies() {
        $companies = $this->getAllCompanies();
        // Set page title and prepare content for layout
        $pageTitle = 'จัดการบริษัท - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'companies';

        // Capture companies content
        ob_start();
        include __DIR__ . '/../views/admin/companies/index.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * สร้างบริษัทใหม่
     */
    private function createCompany() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $companyData = [
                'company_name' => $_POST['company_name'] ?? '',
                'company_code' => $_POST['company_code'] ?? '',
                'address' => $_POST['address'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'email' => $_POST['email'] ?? '',
                'is_active' => 1
            ];
            
            $result = $this->createCompanyRecord($companyData);
            
            if ($result['success']) {
                header('Location: admin.php?action=companies&message=company_created');
                exit;
            } else {
                $error = $result['message'];
            }
        }
        
        include __DIR__ . '/../views/admin/companies/create.php';
    }
    
    /**
     * แก้ไขบริษัท
     */
    private function editCompany() {
        $companyId = $_GET['id'] ?? null;
        
        if (!$companyId) {
            header('Location: admin.php?action=companies&error=invalid_id');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $companyData = [
                'company_id' => $companyId,
                'company_name' => $_POST['company_name'] ?? '',
                'company_code' => $_POST['company_code'] ?? '',
                'address' => $_POST['address'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'email' => $_POST['email'] ?? '',
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            $result = $this->updateCompanyRecord($companyData);
            
            if ($result['success']) {
                header('Location: admin.php?action=companies&message=company_updated');
                exit;
            } else {
                $error = $result['message'];
            }
        }
        
        $company = $this->getCompanyById($companyId);
        if (!$company) {
            header('Location: admin.php?action=companies&error=company_not_found');
            exit;
        }
        
        include __DIR__ . '/../views/admin/companies/edit.php';
    }
    
    /**
     * ลบบริษัท
     */
    private function deleteCompany() {
        $companyId = $_GET['id'] ?? null;
        
        if (!$companyId) {
            header('Location: admin.php?action=companies&error=invalid_id');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->deleteCompanyRecord($companyId);
            
            if ($result['success']) {
                header('Location: admin.php?action=companies&message=company_deleted');
                exit;
            } else {
                $error = $result['message'];
            }
        }
        
        $company = $this->getCompanyById($companyId);
        if (!$company) {
            header('Location: admin.php?action=companies&error=company_not_found');
            exit;
        }
        
        include __DIR__ . '/../views/admin/companies/delete.php';
    }
    
    /**
     * ดึงบริษัทตาม ID
     */
    private function getCompanyById($companyId) {
        $sql = "SELECT * FROM companies WHERE company_id = :company_id";
        return $this->db->fetchOne($sql, ['company_id' => $companyId]);
    }
    
    /**
     * สร้างบริษัทใหม่
     */
    private function createCompanyRecord($companyData) {
        try {
            $sql = "INSERT INTO companies (company_name, company_code, address, phone, email, is_active) 
                    VALUES (:company_name, :company_code, :address, :phone, :email, :is_active)";
            
            $this->db->query($sql, $companyData);
            
            return ['success' => true, 'message' => 'สร้างบริษัทใหม่สำเร็จ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * อัปเดตบริษัท
     */
    private function updateCompanyRecord($companyData) {
        try {
            $sql = "UPDATE companies SET 
                    company_name = :company_name,
                    company_code = :company_code,
                    address = :address,
                    phone = :phone,
                    email = :email,
                    is_active = :is_active
                    WHERE company_id = :company_id";
            
            $this->db->query($sql, $companyData);
            
            return ['success' => true, 'message' => 'อัปเดตบริษัทสำเร็จ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * ลบบริษัท
     */
    private function deleteCompanyRecord($companyId) {
        try {
            // ตรวจสอบว่าบริษัทถูกใช้โดยผู้ใช้หรือไม่
            $sql = "SELECT COUNT(*) as count FROM users WHERE company_id = :company_id";
            $result = $this->db->fetchOne($sql, ['company_id' => $companyId]);
            
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'ไม่สามารถลบบริษัทได้ เนื่องจากมีผู้ใช้ที่เกี่ยวข้องอยู่'];
            }
            
            $sql = "DELETE FROM companies WHERE company_id = :company_id";
            $this->db->query($sql, ['company_id' => $companyId]);
            
            return ['success' => true, 'message' => 'ลบบริษัทสำเร็จ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * ดึงสินค้าทั้งหมด
     */
    private function getAllProducts() {
        $sql = "SELECT * FROM products ORDER BY product_name";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * ดึงสินค้าตาม ID
     */
    private function getProductById($productId) {
        $sql = "SELECT * FROM products WHERE product_id = :product_id";
        return $this->db->fetchOne($sql, ['product_id' => $productId]);
    }
    
    /**
     * ดึงหมวดหมู่สินค้า
     */
    private function getProductCategories() {
        // หมวดหมู่สินค้าที่กำหนดไว้
        return [
            'ปุ๋ยกระสอบใหญ่',
            'ปุ๋ยกระสอบเล็ก', 
            'ชีวภัณฑ์',
            'ของแถม'
        ];
    }
    
    /**
     * ตรวจสอบรหัสสินค้าที่ซ้ำ
     */
    private function checkDuplicateProductCode($productCode) {
        try {
            $sql = "SELECT COUNT(*) as count FROM products WHERE product_code = :product_code";
            $result = $this->db->fetchOne($sql, ['product_code' => $productCode]);
            
            return ['exists' => $result['count'] > 0, 'count' => $result['count']];
        } catch (Exception $e) {
            return ['exists' => false, 'count' => 0, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * สร้างสินค้าใหม่
     */
    private function createProductRecord($productData) {
        try {
            $sql = "INSERT INTO products (product_code, product_name, category, description, unit, cost_price, selling_price, stock_quantity) 
                    VALUES (:product_code, :product_name, :category, :description, :unit, :cost_price, :selling_price, :stock_quantity)";
            
            $this->db->query($sql, $productData);
            
            return ['success' => true, 'message' => 'สร้างสินค้าใหม่สำเร็จ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * อัปเดตสินค้า
     */
    private function updateProductRecord($productData) {
        try {
            $sql = "UPDATE products SET 
                    product_code = :product_code,
                    product_name = :product_name,
                    category = :category,
                    description = :description,
                    unit = :unit,
                    cost_price = :cost_price,
                    selling_price = :selling_price,
                    stock_quantity = :stock_quantity,
                    is_active = :is_active,
                    updated_at = NOW()
                    WHERE product_id = :product_id";
            
            $this->db->query($sql, $productData);
            
            return ['success' => true, 'message' => 'อัปเดตสินค้าสำเร็จ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * ลบสินค้า
     */
    private function deleteProductRecord($productId) {
        try {
            // ตรวจสอบว่าสินค้าถูกใช้ในคำสั่งซื้อหรือไม่
            $sql = "SELECT COUNT(*) as count FROM order_details WHERE product_id = :product_id";
            $result = $this->db->fetchOne($sql, ['product_id' => $productId]);
            
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'ไม่สามารถลบสินค้าได้ เนื่องจากถูกใช้ในคำสั่งซื้อ'];
            }
            
            $sql = "DELETE FROM products WHERE product_id = :product_id";
            $this->db->query($sql, ['product_id' => $productId]);
            
            return ['success' => true, 'message' => 'ลบสินค้าสำเร็จ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * นำเข้าสินค้าจาก CSV
     */
    private function importProductsFromCSV($filePath) {
        try {
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                return ['success' => false, 'message' => 'ไม่สามารถเปิดไฟล์ได้'];
            }
            
            // ข้าม header
            fgetcsv($handle);
            
            $count = 0;
            $errors = [];
            
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) >= 8) {
                    $productData = [
                        'product_code' => trim($data[0]),
                        'product_name' => trim($data[1]),
                        'category' => trim($data[2]),
                        'description' => trim($data[3]),
                        'unit' => trim($data[4]),
                        'cost_price' => floatval($data[5]),
                        'selling_price' => floatval($data[6]),
                        'stock_quantity' => intval($data[7])
                    ];
                    
                    $result = $this->createProductRecord($productData);
                    if ($result['success']) {
                        $count++;
                    } else {
                        $errors[] = "แถว " . ($count + 2) . ": " . $result['message'];
                    }
                }
            }
            
            fclose($handle);
            
            if ($count > 0) {
                return ['success' => true, 'message' => "นำเข้าสินค้า $count รายการสำเร็จ", 'count' => $count];
            } else {
                return ['success' => false, 'message' => 'ไม่สามารถนำเข้าสินค้าได้'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * ดึงการตั้งค่าระบบ
     */
    private function getSystemSettings() {
        $sql = "SELECT * FROM system_settings ORDER BY setting_key";
        $results = $this->db->fetchAll($sql);
        
        $settings = [];
        foreach ($results as $result) {
            $settings[$result['setting_key']] = $result['setting_value'];
        }
        
        return $settings;
    }
    
    /**
     * อัปเดตการตั้งค่าระบบ
     */
    private function updateSystemSettings($settings) {
        try {
            foreach ($settings as $key => $value) {
                $sql = "UPDATE system_settings SET setting_value = :value, updated_at = NOW() WHERE setting_key = :key";
                $this->db->query($sql, ['key' => $key, 'value' => $value]);
            }
            
            return ['success' => true, 'message' => 'อัปเดตการตั้งค่าระบบสำเร็จ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }

    /**
     * แสดงหน้าจัดการระบบแจกลูกค้า
     */
    public function customer_distribution() {
        // ตรวจสอบการยืนยันตัวตน
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        // ตรวจสอบสิทธิ์
        $roleName = $_SESSION['role_name'] ?? '';
        if (!in_array($roleName, ['admin', 'supervisor', 'super_admin'])) {
            header('Location: index.php');
            exit;
        }

        // Set page title and prepare content for layout
        $pageTitle = 'ระบบแจกลูกค้า - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'customer_distribution';

        // Capture customer distribution content
        ob_start();
        include __DIR__ . '/../views/admin/customer_distribution_new.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
}
?> 