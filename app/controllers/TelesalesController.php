<?php
/**
 * TelesalesController Class
 * จัดการฟีเจอร์สำหรับ role = 5 (telesales)
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../services/OrderService.php';
require_once __DIR__ . '/../services/CustomerService.php';
require_once __DIR__ . '/../services/ImportExportService.php';
require_once __DIR__ . '/../services/CustomerDistributionService.php';

class TelesalesController {
    private $db;
    private $auth;
    private $orderService;
    private $customerService;
    private $importExportService;
    private $customerDistributionService;
    
    public function __construct() {
        $this->db = new Database();
        $this->auth = new Auth($this->db);
        $this->orderService = new OrderService();
        $this->customerService = new CustomerService();
        $this->importExportService = new ImportExportService();
        $this->customerDistributionService = new CustomerDistributionService();
    }
    
    /**
     * ตรวจสอบสิทธิ์การเข้าถึง
     */
    private function checkAccess() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName !== 'telesales') {
            header('Location: dashboard.php');
            exit;
        }
    }
    
    /**
     * หน้า จัดการสินค้า (ในบริษัทตัวเอง)
     */
    public function products() {
        $this->checkAccess();
        
        $companyId = $_SESSION['company_id'] ?? null;
        if (!$companyId) {
            header('Location: dashboard.php');
            exit;
        }
        
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
        $companyId = $_SESSION['company_id'] ?? null;
        
        // ดึงข้อมูลสินค้าของบริษัทตัวเอง
        $products = $this->getProductsByCompany($companyId);
        $categories = $this->getProductCategories($companyId);
        
        // Set page title and prepare content for layout
        $pageTitle = 'จัดการสินค้า - CRM SalesTracker';
        $currentPage = 'telesales_products';
        $currentAction = 'products';

        // Capture products content
        ob_start();
        include __DIR__ . '/../views/telesales/products/index.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * สร้างสินค้าใหม่
     */
    private function createProduct() {
        $companyId = $_SESSION['company_id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productData = [
                'product_code' => $_POST['product_code'] ?? '',
                'product_name' => $_POST['product_name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'price' => $_POST['price'] ?? 0,
                'category' => $_POST['category'] ?? '',
                'unit' => $_POST['unit'] ?? '',
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'company_id' => $companyId
            ];
            
            $result = $this->createProductRecord($productData);
            
            if ($result['success']) {
                header('Location: telesales.php?action=products&message=product_created');
                exit;
            } else {
                $error = $result['message'];
            }
        }
        
        $categories = $this->getProductCategories($companyId);
        
        // Set page title and prepare content for layout
        $pageTitle = 'สร้างสินค้าใหม่ - CRM SalesTracker';
        $currentPage = 'telesales_products';
        $currentAction = 'products';

        // Capture create product content
        ob_start();
        include __DIR__ . '/../views/telesales/products/create.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * แก้ไขสินค้า
     */
    private function editProduct() {
        $companyId = $_SESSION['company_id'] ?? null;
        $productId = $_GET['id'] ?? 0;
        
        // ตรวจสอบว่าสินค้าเป็นของบริษัทตัวเองหรือไม่
        $product = $this->getProductById($productId, $companyId);
        if (!$product) {
            header('Location: telesales.php?action=products');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productData = [
                'product_id' => $productId,
                'product_code' => $_POST['product_code'] ?? '',
                'product_name' => $_POST['product_name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'price' => $_POST['price'] ?? 0,
                'category' => $_POST['category'] ?? '',
                'unit' => $_POST['unit'] ?? '',
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'company_id' => $companyId
            ];
            
            $result = $this->updateProductRecord($productData);
            
            if ($result['success']) {
                header('Location: telesales.php?action=products&message=product_updated');
                exit;
            } else {
                $error = $result['message'];
            }
        }
        
        $categories = $this->getProductCategories($companyId);
        
        // Set page title and prepare content for layout
        $pageTitle = 'แก้ไขสินค้า - CRM SalesTracker';
        $currentPage = 'telesales_products';
        $currentAction = 'products';

        // Capture edit product content
        ob_start();
        include __DIR__ . '/../views/telesales/products/edit.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * ลบสินค้า
     */
    private function deleteProduct() {
        $companyId = $_SESSION['company_id'] ?? null;
        $productId = $_GET['id'] ?? 0;
        
        // ตรวจสอบว่าสินค้าเป็นของบริษัทตัวเองหรือไม่
        $product = $this->getProductById($productId, $companyId);
        if (!$product) {
            header('Location: telesales.php?action=products');
            exit;
        }
        
        $result = $this->deleteProductRecord($productId, $companyId);
        
        if ($result['success']) {
            header('Location: telesales.php?action=products&message=product_deleted');
        } else {
            header('Location: telesales.php?action=products&error=' . urlencode($result['message']));
        }
        exit;
    }
    
    /**
     * นำเข้าสินค้า
     */
    private function importProducts() {
        $companyId = $_SESSION['company_id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
            $result = $this->importProductsFromCSV($_FILES['csv_file'], $companyId);
            
            if ($result['success']) {
                header('Location: telesales.php?action=products&message=products_imported&count=' . $result['count']);
                exit;
            } else {
                $error = $result['message'];
            }
        }
        
        // Set page title and prepare content for layout
        $pageTitle = 'นำเข้าสินค้า - CRM SalesTracker';
        $currentPage = 'telesales_products';
        $currentAction = 'products';

        // Capture import products content
        ob_start();
        include __DIR__ . '/../views/telesales/products/import.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * ส่งออกสินค้า
     */
    private function exportProducts() {
        $companyId = $_SESSION['company_id'] ?? null;
        
        $result = $this->exportProductsToCSV($companyId);
        
        if ($result['success']) {
            // ส่งไฟล์ CSV กลับไป
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="products_' . date('Y-m-d') . '.csv"');
            echo $result['csv_content'];
            exit;
        } else {
            header('Location: telesales.php?action=products&error=' . urlencode($result['message']));
            exit;
        }
    }
    
    /**
     * หน้า สร้าง user (เฉพาะ telesale = 4, supervisor = 3) ในบริษัทตัวเอง
     */
    public function users() {
        $this->checkAccess();
        
        $companyId = $_SESSION['company_id'] ?? null;
        if (!$companyId) {
            header('Location: dashboard.php');
            exit;
        }
        
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
        $companyId = $_SESSION['company_id'] ?? null;
        
        // ดึงข้อมูลผู้ใช้ของบริษัทตัวเอง (เฉพาะ telesales และ supervisor)
        $users = $this->getUsersByCompany($companyId);
        $roles = $this->getAllowedRoles();
        
        // Set page title and prepare content for layout
        $pageTitle = 'จัดการผู้ใช้ - CRM SalesTracker';
        $currentPage = 'telesales_users';
        $currentAction = 'users';

        // Capture users content
        ob_start();
        include __DIR__ . '/../views/telesales/users/index.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * สร้างผู้ใช้ใหม่
     */
    private function createUser() {
        $companyId = $_SESSION['company_id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userData = [
                'username' => $_POST['username'] ?? '',
                'password' => $_POST['password'] ?? '',
                'full_name' => $_POST['full_name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'role_id' => $_POST['role_id'] ?? '',
                'company_id' => $companyId,
                'supervisor_id' => $_POST['supervisor_id'] ?? null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            // ตรวจสอบว่า role ที่เลือกอนุญาตหรือไม่
            if (!in_array($userData['role_id'], [3, 4])) { // 3 = supervisor, 4 = telesales
                $error = 'สามารถสร้างได้เฉพาะ Supervisor และ Telesales เท่านั้น';
            } else {
                $result = $this->createUserRecord($userData);
                
                if ($result['success']) {
                    header('Location: telesales.php?action=users&message=user_created');
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
        
        $roles = $this->getAllowedRoles();
        $supervisors = $this->getSupervisorsByCompany($companyId);
        
        // Set page title and prepare content for layout
        $pageTitle = 'สร้างผู้ใช้ใหม่ - CRM SalesTracker';
        $currentPage = 'telesales_users';
        $currentAction = 'users';

        // Capture create user content
        ob_start();
        include __DIR__ . '/../views/telesales/users/create.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * แก้ไขผู้ใช้
     */
    private function editUser() {
        $companyId = $_SESSION['company_id'] ?? null;
        $userId = $_GET['id'] ?? 0;
        
        // ตรวจสอบว่าผู้ใช้เป็นของบริษัทตัวเองหรือไม่
        $user = $this->getUserById($userId, $companyId);
        if (!$user) {
            header('Location: telesales.php?action=users');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userData = [
                'user_id' => $userId,
                'username' => $_POST['username'] ?? '',
                'full_name' => $_POST['full_name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'role_id' => $_POST['role_id'] ?? '',
                'company_id' => $companyId,
                'supervisor_id' => $_POST['supervisor_id'] ?? null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            // ถ้ามีการเปลี่ยนรหัสผ่าน
            if (!empty($_POST['password'])) {
                $userData['password'] = $_POST['password'];
            }
            
            // ตรวจสอบว่า role ที่เลือกอนุญาตหรือไม่
            if (!in_array($userData['role_id'], [3, 4])) { // 3 = supervisor, 4 = telesales
                $error = 'สามารถแก้ไขได้เฉพาะ Supervisor และ Telesales เท่านั้น';
            } else {
                $result = $this->updateUserRecord($userData);
                
                if ($result['success']) {
                    header('Location: telesales.php?action=users&message=user_updated');
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
        
        $roles = $this->getAllowedRoles();
        $supervisors = $this->getSupervisorsByCompany($companyId);
        
        // Set page title and prepare content for layout
        $pageTitle = 'แก้ไขผู้ใช้ - CRM SalesTracker';
        $currentPage = 'telesales_users';
        $currentAction = 'users';

        // Capture edit user content
        ob_start();
        include __DIR__ . '/../views/telesales/users/edit.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * ลบผู้ใช้
     */
    private function deleteUser() {
        $companyId = $_SESSION['company_id'] ?? null;
        $userId = $_GET['id'] ?? 0;
        
        // ตรวจสอบว่าผู้ใช้เป็นของบริษัทตัวเองหรือไม่
        $user = $this->getUserById($userId, $companyId);
        if (!$user) {
            header('Location: telesales.php?action=users');
            exit;
        }
        
        $result = $this->deleteUserRecord($userId, $companyId);
        
        if ($result['success']) {
            header('Location: telesales.php?action=users&message=user_deleted');
        } else {
            header('Location: telesales.php?action=users&error=' . urlencode($result['message']));
        }
        exit;
    }
    
    /**
     * หน้า นำข้อมูลเข้า import
     */
    public function import() {
        $this->checkAccess();
        
        $companyId = $_SESSION['company_id'] ?? null;
        if (!$companyId) {
            header('Location: dashboard.php');
            exit;
        }
        
        $subaction = $_GET['subaction'] ?? 'customers';
        
        switch ($subaction) {
            case 'customers':
                $this->importCustomers();
                break;
            case 'products':
                $this->importProducts();
                break;
            default:
                $this->importCustomers();
                break;
        }
    }
    
    /**
     * นำเข้าลูกค้า
     */
    private function importCustomers() {
        $companyId = $_SESSION['company_id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
            $result = $this->importCustomersFromCSV($_FILES['csv_file'], $companyId);
            
            if ($result['success']) {
                header('Location: telesales.php?action=import&subaction=customers&message=customers_imported&count=' . $result['count']);
                exit;
            } else {
                $error = $result['message'];
            }
        }
        
        // Set page title and prepare content for layout
        $pageTitle = 'นำเข้าลูกค้า - CRM SalesTracker';
        $currentPage = 'telesales_import';
        $currentAction = 'import';

        // Capture import customers content
        ob_start();
        include __DIR__ . '/../views/telesales/import/customers.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * หน้า แจกรายชื่อ
     */
    public function distribution() {
        $this->checkAccess();
        
        $companyId = $_SESSION['company_id'] ?? null;
        if (!$companyId) {
            header('Location: dashboard.php');
            exit;
        }
        
        $subaction = $_GET['subaction'] ?? 'list';
        
        switch ($subaction) {
            case 'assign':
                $this->assignCustomers();
                break;
            case 'bulk_assign':
                $this->bulkAssignCustomers();
                break;
            default:
                $this->listDistribution();
                break;
        }
    }
    
    /**
     * แสดงรายการการแจกลูกค้า
     */
    private function listDistribution() {
        $companyId = $_SESSION['company_id'] ?? null;
        
        // ดึงข้อมูลการแจกลูกค้าของบริษัทตัวเอง
        $distributions = $this->getCustomerDistributions($companyId);
        $telesales = $this->getTelesalesByCompany($companyId);
        
        // Set page title and prepare content for layout
        $pageTitle = 'ระบบแจกลูกค้า - CRM SalesTracker';
        $currentPage = 'telesales_distribution';
        $currentAction = 'distribution';

        // Capture distribution content
        ob_start();
        include __DIR__ . '/../views/telesales/distribution/index.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * แจกลูกค้า
     */
    private function assignCustomers() {
        $companyId = $_SESSION['company_id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $customerIds = $_POST['customer_ids'] ?? [];
            $telesalesId = $_POST['telesales_id'] ?? null;
            
            if (!empty($customerIds) && $telesalesId) {
                $result = $this->assignCustomersToTelesales($customerIds, $telesalesId, $companyId);
                
                if ($result['success']) {
                    header('Location: telesales.php?action=distribution&message=customers_assigned&count=' . $result['count']);
                    exit;
                } else {
                    $error = $result['message'];
                }
            } else {
                $error = 'กรุณาเลือกลูกค้าและผู้ขาย';
            }
        }
        
        // ดึงข้อมูลลูกค้าที่ยังไม่ได้แจก
        $availableCustomers = $this->getAvailableCustomers($companyId);
        $telesales = $this->getTelesalesByCompany($companyId);
        
        // Set page title and prepare content for layout
        $pageTitle = 'แจกลูกค้า - CRM SalesTracker';
        $currentPage = 'telesales_distribution';
        $currentAction = 'distribution';

        // Capture assign customers content
        ob_start();
        include __DIR__ . '/../views/telesales/distribution/assign.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * แจกลูกค้าแบบกลุ่ม
     */
    private function bulkAssignCustomers() {
        $companyId = $_SESSION['company_id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $criteria = [
                'province' => $_POST['province'] ?? '',
                'district' => $_POST['district'] ?? '',
                'customer_grade' => $_POST['customer_grade'] ?? '',
                'temperature_status' => $_POST['temperature_status'] ?? ''
            ];
            $telesalesId = $_POST['telesales_id'] ?? null;
            
            if ($telesalesId) {
                $result = $this->bulkAssignCustomersByCriteria($criteria, $telesalesId, $companyId);
                
                if ($result['success']) {
                    header('Location: telesales.php?action=distribution&message=customers_bulk_assigned&count=' . $result['count']);
                    exit;
                } else {
                    $error = $result['message'];
                }
            } else {
                $error = 'กรุณาเลือกผู้ขาย';
            }
        }
        
        $telesales = $this->getTelesalesByCompany($companyId);
        $provinces = $this->getProvincesByCompany($companyId);
        
        // Set page title and prepare content for layout
        $pageTitle = 'แจกลูกค้าแบบกลุ่ม - CRM SalesTracker';
        $currentPage = 'telesales_distribution';
        $currentAction = 'distribution';

        // Capture bulk assign customers content
        ob_start();
        include __DIR__ . '/../views/telesales/distribution/bulk_assign.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    // Helper Methods
    
    /**
     * ดึงข้อมูลสินค้าตามบริษัท
     */
    private function getProductsByCompany($companyId) {
        return $this->db->fetchAll(
            "SELECT * FROM products WHERE company_id = ? AND is_active = 1 ORDER BY product_name",
            [$companyId]
        );
    }
    
    /**
     * ดึงข้อมูลหมวดหมู่สินค้า
     */
    private function getProductCategories($companyId) {
        return $this->db->fetchAll(
            "SELECT DISTINCT category FROM products WHERE company_id = ? AND category IS NOT NULL AND category != '' ORDER BY category",
            [$companyId]
        );
    }
    
    /**
     * ดึงข้อมูลสินค้าตาม ID
     */
    private function getProductById($productId, $companyId) {
        return $this->db->fetchOne(
            "SELECT * FROM products WHERE product_id = ? AND company_id = ?",
            [$productId, $companyId]
        );
    }
    
    /**
     * สร้างสินค้าใหม่
     */
    private function createProductRecord($productData) {
        try {
            // ตรวจสอบรหัสสินค้าซ้ำ
            $existing = $this->db->fetchOne(
                "SELECT product_id FROM products WHERE product_code = ? AND company_id = ?",
                [$productData['product_code'], $productData['company_id']]
            );
            
            if ($existing) {
                return ['success' => false, 'message' => 'รหัสสินค้านี้มีอยู่แล้ว'];
            }
            
            $this->db->query(
                "INSERT INTO products (product_code, product_name, description, price, category, unit, is_active, company_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $productData['product_code'],
                    $productData['product_name'],
                    $productData['description'],
                    $productData['price'],
                    $productData['category'],
                    $productData['unit'],
                    $productData['is_active'],
                    $productData['company_id']
                ]
            );
            
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
            // ตรวจสอบรหัสสินค้าซ้ำ (ยกเว้นตัวเอง)
            $existing = $this->db->fetchOne(
                "SELECT product_id FROM products WHERE product_code = ? AND company_id = ? AND product_id != ?",
                [$productData['product_code'], $productData['company_id'], $productData['product_id']]
            );
            
            if ($existing) {
                return ['success' => false, 'message' => 'รหัสสินค้านี้มีอยู่แล้ว'];
            }
            
            $this->db->query(
                "UPDATE products SET product_code = ?, product_name = ?, description = ?, price = ?, category = ?, unit = ?, is_active = ?, updated_at = NOW() WHERE product_id = ? AND company_id = ?",
                [
                    $productData['product_code'],
                    $productData['product_name'],
                    $productData['description'],
                    $productData['price'],
                    $productData['category'],
                    $productData['unit'],
                    $productData['is_active'],
                    $productData['product_id'],
                    $productData['company_id']
                ]
            );
            
            return ['success' => true, 'message' => 'อัปเดตสินค้าสำเร็จ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * ลบสินค้า
     */
    private function deleteProductRecord($productId, $companyId) {
        try {
            $this->db->query(
                "UPDATE products SET is_active = 0, updated_at = NOW() WHERE product_id = ? AND company_id = ?",
                [$productId, $companyId]
            );
            
            return ['success' => true, 'message' => 'ลบสินค้าสำเร็จ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * นำเข้าสินค้าจาก CSV
     */
    private function importProductsFromCSV($file, $companyId) {
        try {
            $csvData = $this->parseCSV($file['tmp_name']);
            $count = 0;
            
            foreach ($csvData as $row) {
                $productData = [
                    'product_code' => $row['product_code'] ?? '',
                    'product_name' => $row['product_name'] ?? '',
                    'description' => $row['description'] ?? '',
                    'price' => $row['price'] ?? 0,
                    'category' => $row['category'] ?? '',
                    'unit' => $row['unit'] ?? '',
                    'is_active' => 1,
                    'company_id' => $companyId
                ];
                
                $result = $this->createProductRecord($productData);
                if ($result['success']) {
                    $count++;
                }
            }
            
            return ['success' => true, 'count' => $count, 'message' => "นำเข้าสินค้า $count รายการสำเร็จ"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * ส่งออกสินค้าเป็น CSV
     */
    private function exportProductsToCSV($companyId) {
        try {
            $products = $this->getProductsByCompany($companyId);
            $csvContent = $this->generateCSV($products, [
                'product_code' => 'รหัสสินค้า',
                'product_name' => 'ชื่อสินค้า',
                'description' => 'รายละเอียด',
                'price' => 'ราคา',
                'category' => 'หมวดหมู่',
                'unit' => 'หน่วย'
            ]);
            
            return ['success' => true, 'csv_content' => $csvContent];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * ดึงข้อมูลผู้ใช้ตามบริษัท
     */
    private function getUsersByCompany($companyId) {
        return $this->db->fetchAll(
            "SELECT u.*, r.role_name FROM users u 
             LEFT JOIN roles r ON u.role_id = r.role_id 
             WHERE u.company_id = ? AND u.role_id IN (3, 4) AND u.is_active = 1 
             ORDER BY u.role_id, u.full_name",
            [$companyId]
        );
    }
    
    /**
     * ดึงข้อมูล role ที่อนุญาต
     */
    private function getAllowedRoles() {
        return $this->db->fetchAll(
            "SELECT * FROM roles WHERE role_id IN (3, 4) ORDER BY role_id"
        );
    }
    
    /**
     * ดึงข้อมูล supervisor ตามบริษัท
     */
    private function getSupervisorsByCompany($companyId) {
        return $this->db->fetchAll(
            "SELECT user_id, full_name FROM users WHERE company_id = ? AND role_id = 3 AND is_active = 1 ORDER BY full_name",
            [$companyId]
        );
    }
    
    /**
     * ดึงข้อมูลผู้ใช้ตาม ID
     */
    private function getUserById($userId, $companyId) {
        return $this->db->fetchOne(
            "SELECT u.*, r.role_name FROM users u 
             LEFT JOIN roles r ON u.role_id = r.role_id 
             WHERE u.user_id = ? AND u.company_id = ? AND u.role_id IN (3, 4)",
            [$userId, $companyId]
        );
    }
    
    /**
     * สร้างผู้ใช้ใหม่
     */
    private function createUserRecord($userData) {
        try {
            // ตรวจสอบชื่อผู้ใช้ซ้ำ
            $existing = $this->db->fetchOne(
                "SELECT user_id FROM users WHERE username = ?",
                [$userData['username']]
            );
            
            if ($existing) {
                return ['success' => false, 'message' => 'ชื่อผู้ใช้นี้มีอยู่แล้ว'];
            }
            
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            $this->db->query(
                "INSERT INTO users (username, password_hash, full_name, email, phone, role_id, company_id, supervisor_id, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $userData['username'],
                    $hashedPassword,
                    $userData['full_name'],
                    $userData['email'],
                    $userData['phone'],
                    $userData['role_id'],
                    $userData['company_id'],
                    $userData['supervisor_id'],
                    $userData['is_active']
                ]
            );
            
            return ['success' => true, 'message' => 'สร้างผู้ใช้ใหม่สำเร็จ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * อัปเดตผู้ใช้
     */
    private function updateUserRecord($userData) {
        try {
            // ตรวจสอบชื่อผู้ใช้ซ้ำ (ยกเว้นตัวเอง)
            $existing = $this->db->fetchOne(
                "SELECT user_id FROM users WHERE username = ? AND user_id != ?",
                [$userData['username'], $userData['user_id']]
            );
            
            if ($existing) {
                return ['success' => false, 'message' => 'ชื่อผู้ใช้นี้มีอยู่แล้ว'];
            }
            
            $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, role_id = ?, supervisor_id = ?, is_active = ?, updated_at = NOW()";
            $params = [
                $userData['username'],
                $userData['full_name'],
                $userData['email'],
                $userData['phone'],
                $userData['role_id'],
                $userData['supervisor_id'],
                $userData['is_active']
            ];
            
            // ถ้ามีการเปลี่ยนรหัสผ่าน
            if (!empty($userData['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($userData['password'], PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE user_id = ? AND company_id = ?";
            $params[] = $userData['user_id'];
            $params[] = $userData['company_id'];
            
            $this->db->query($sql, $params);
            
            return ['success' => true, 'message' => 'อัปเดตผู้ใช้สำเร็จ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * ลบผู้ใช้
     */
    private function deleteUserRecord($userId, $companyId) {
        try {
            $this->db->query(
                "UPDATE users SET is_active = 0, updated_at = NOW() WHERE user_id = ? AND company_id = ?",
                [$userId, $companyId]
            );
            
            return ['success' => true, 'message' => 'ลบผู้ใช้สำเร็จ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * นำเข้าลูกค้าจาก CSV
     */
    private function importCustomersFromCSV($file, $companyId) {
        try {
            $csvData = $this->parseCSV($file['tmp_name']);
            $count = 0;
            
            foreach ($csvData as $row) {
                $customerData = [
                    'first_name' => $row['first_name'] ?? '',
                    'last_name' => $row['last_name'] ?? '',
                    'phone' => $row['phone'] ?? '',
                    'email' => $row['email'] ?? '',
                    'address' => $row['address'] ?? '',
                    'province' => $row['province'] ?? '',
                    'district' => $row['district'] ?? '',
                    'subdistrict' => $row['subdistrict'] ?? '',
                    'postal_code' => $row['postal_code'] ?? '',
                    'company_id' => $companyId,
                    'basket_type' => 'distribution'
                ];
                
                $result = $this->createCustomerRecord($customerData);
                if ($result['success']) {
                    $count++;
                }
            }
            
            return ['success' => true, 'count' => $count, 'message' => "นำเข้าลูกค้า $count รายการสำเร็จ"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * ดึงข้อมูลการแจกลูกค้า
     */
    private function getCustomerDistributions($companyId) {
        return $this->db->fetchAll(
            "SELECT cd.*, c.first_name, c.last_name, c.phone, u.full_name as telesales_name 
             FROM customer_transfer_details cd
             LEFT JOIN customers c ON cd.customer_id = c.customer_id
             LEFT JOIN users u ON cd.transferred_to = u.user_id
             WHERE c.company_id = ? AND cd.status = 'completed'
             ORDER BY cd.transferred_at DESC",
            [$companyId]
        );
    }
    
    /**
     * ดึงข้อมูล telesales ตามบริษัท
     */
    private function getTelesalesByCompany($companyId) {
        return $this->db->fetchAll(
            "SELECT user_id, full_name FROM users WHERE company_id = ? AND role_id = 4 AND is_active = 1 ORDER BY full_name",
            [$companyId]
        );
    }
    
    /**
     * ดึงข้อมูลลูกค้าที่ยังไม่ได้แจก
     */
    private function getAvailableCustomers($companyId) {
        return $this->db->fetchAll(
            "SELECT customer_id, first_name, last_name, phone, province, district 
             FROM customers 
             WHERE company_id = ? AND basket_type = 'distribution' AND is_active = 1 
             ORDER BY first_name, last_name",
            [$companyId]
        );
    }
    
    /**
     * แจกลูกค้าให้ telesales
     */
    private function assignCustomersToTelesales($customerIds, $telesalesId, $companyId) {
        try {
            $count = 0;
            
            foreach ($customerIds as $customerId) {
                // ตรวจสอบว่าลูกค้าเป็นของบริษัทตัวเองหรือไม่
                $customer = $this->db->fetchOne(
                    "SELECT customer_id FROM customers WHERE customer_id = ? AND company_id = ?",
                    [$customerId, $companyId]
                );
                
                if ($customer) {
                    // อัปเดตลูกค้าให้เป็น assigned
                    $this->db->query(
                        "UPDATE customers SET basket_type = 'assigned', assigned_to = ?, updated_at = NOW() WHERE customer_id = ?",
                        [$telesalesId, $customerId]
                    );
                    $count++;
                }
            }
            
            return ['success' => true, 'count' => $count, 'message' => "แจกลูกค้า $count รายการสำเร็จ"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * ดึงข้อมูลจังหวัดตามบริษัท
     */
    private function getProvincesByCompany($companyId) {
        return $this->db->fetchAll(
            "SELECT DISTINCT province FROM customers WHERE company_id = ? AND province IS NOT NULL AND province != '' ORDER BY province",
            [$companyId]
        );
    }
    
    /**
     * แจกลูกค้าแบบกลุ่มตามเงื่อนไข
     */
    private function bulkAssignCustomersByCriteria($criteria, $telesalesId, $companyId) {
        try {
            $whereConditions = ['company_id = ?', 'basket_type = "distribution"', 'is_active = 1'];
            $params = [$companyId];
            
            if (!empty($criteria['province'])) {
                $whereConditions[] = 'province = ?';
                $params[] = $criteria['province'];
            }
            
            if (!empty($criteria['district'])) {
                $whereConditions[] = 'district = ?';
                $params[] = $criteria['district'];
            }
            
            if (!empty($criteria['customer_grade'])) {
                $whereConditions[] = 'customer_grade = ?';
                $params[] = $criteria['customer_grade'];
            }
            
            if (!empty($criteria['temperature_status'])) {
                $whereConditions[] = 'temperature_status = ?';
                $params[] = $criteria['temperature_status'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $this->db->query(
                "UPDATE customers SET basket_type = 'assigned', assigned_to = ?, updated_at = NOW() WHERE $whereClause",
                array_merge([$telesalesId], $params)
            );
            
            $count = $this->db->getRowCount();
            
            return ['success' => true, 'count' => $count, 'message' => "แจกลูกค้า $count รายการสำเร็จ"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * Parse CSV file
     */
    private function parseCSV($filePath) {
        $data = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle) {
            $headers = fgetcsv($handle);
            if ($headers) {
                while (($row = fgetcsv($handle)) !== false) {
                    $data[] = array_combine($headers, $row);
                }
            }
            fclose($handle);
        }
        
        return $data;
    }
    
    /**
     * Generate CSV content
     */
    private function generateCSV($data, $headers = []) {
        $output = '';
        
        // Add headers
        if (!empty($headers)) {
            $output .= implode(',', array_values($headers)) . "\n";
        }
        
        // Add data rows
        foreach ($data as $row) {
            $output .= implode(',', array_values($row)) . "\n";
        }
        
        return $output;
    }
    
    /**
     * Create customer record
     */
    private function createCustomerRecord($customerData) {
        try {
            // ตรวจสอบเบอร์โทรศัพท์ซ้ำ
            $existing = $this->db->fetchOne(
                "SELECT customer_id FROM customers WHERE phone = ? AND company_id = ?",
                [$customerData['phone'], $customerData['company_id']]
            );
            
            if ($existing) {
                return ['success' => false, 'message' => 'เบอร์โทรศัพท์นี้มีอยู่แล้ว'];
            }
            
            $this->db->query(
                "INSERT INTO customers (first_name, last_name, phone, email, address, province, district, subdistrict, postal_code, company_id, basket_type, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())",
                [
                    $customerData['first_name'],
                    $customerData['last_name'],
                    $customerData['phone'],
                    $customerData['email'],
                    $customerData['address'],
                    $customerData['province'],
                    $customerData['district'],
                    $customerData['subdistrict'],
                    $customerData['postal_code'],
                    $customerData['company_id'],
                    $customerData['basket_type']
                ]
            );
            
            return ['success' => true, 'message' => 'สร้างลูกค้าใหม่สำเร็จ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
}
?>
