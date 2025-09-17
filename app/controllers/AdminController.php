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
        $roleId = $_SESSION['role_id'] ?? 0;
        
        // Allow admin, company_admin, super_admin roles and specific role IDs (1 and 6)
        if (!in_array($roleName, ['admin', 'company_admin', 'super_admin']) && !in_array($roleId, [1, 6])) {
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
        $roleName = $_SESSION['role_name'] ?? '';
        $companyId = $_SESSION['company_id'] ?? null;
        
        // ดึงข้อมูลสถิติสำหรับ Admin
        $stats = [
            'total_users' => $this->getTotalUsers($companyId),
            'total_customers' => $this->getTotalCustomers($companyId),
            'total_orders' => $this->getTotalOrders($companyId),
            'total_products' => $this->getTotalProducts($companyId),
            'recent_activities' => $this->getRecentActivities($companyId),
            'system_health' => $this->getSystemHealth($companyId)
        ];
        
        // ดึงข้อมูลผู้ใช้ทั้งหมด
        if ($roleName === 'super_admin') {
            $users = $this->auth->getAllUsers();
        } else {
            $users = $this->getUsersByCompany($companyId);
        }
        
        // ดึงข้อมูลสินค้าทั้งหมด
        $products = $this->getAllProducts($companyId);
        
        // ดึงข้อมูลการตั้งค่าระบบ (เฉพาะ super_admin)
        $settings = [];
        if ($roleName === 'super_admin') {
            $settings = $this->getSystemSettings($companyId);
        }
        
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
        
        $roleName = $_SESSION['role_name'] ?? '';
        
        // อนุญาตให้จัดการผู้ใช้เฉพาะ super_admin และ company_admin เท่านั้น
        if (!in_array($roleName, ['super_admin', 'company_admin'])) {
            header('Location: admin.php');
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
        $roleName = $_SESSION['role_name'] ?? '';
        $companyId = $_SESSION['company_id'] ?? null;
        
        // ดึงข้อมูลผู้ใช้ตามสิทธิ์
        if ($roleName === 'super_admin') {
            $users = $this->auth->getAllUsers();
            $companies = $this->getAllCompanies();
        } else {
            // company_admin เห็นเฉพาะผู้ใช้ของบริษัทตัวเอง
            $users = $this->getUsersByCompany($companyId);
            $companies = []; // ไม่ต้องแสดงรายการบริษัท
        }
        
        $roles = $this->getAllRoles();
        
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
        $roleName = $_SESSION['role_name'] ?? '';
        $companyId = $_SESSION['company_id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userData = [
                'username' => trim($_POST['username'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'full_name' => trim($_POST['full_name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'role_id' => $_POST['role_id'] ?? '',
                'company_id' => $roleName === 'super_admin' ? ($_POST['company_id'] ?? null) : $companyId,
                'supervisor_id' => $_POST['supervisor_id'] ?: null
            ];
            
            // Basic validation
            if (empty($userData['username'])) {
                $error = 'กรุณากรอกชื่อผู้ใช้';
            } elseif (empty($userData['password'])) {
                $error = 'กรุณากรอกรหัสผ่าน';
            } elseif (strlen($userData['password']) < 6) {
                $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
            } elseif (empty($userData['full_name'])) {
                $error = 'กรุณากรอกชื่อ-นามสกุล';
            } elseif (empty($userData['role_id'])) {
                $error = 'กรุณาเลือกบทบาท';
            } elseif ($roleName === 'company_admin' && !in_array($userData['role_id'], [3, 4])) {
                $error = 'สามารถสร้างได้เฉพาะ Supervisor และ Telesales เท่านั้น';
            } else {
                // Check for duplicate username
                $existingUser = $this->db->fetchOne(
                    "SELECT user_id FROM users WHERE username = ?", 
                    [$userData['username']]
                );
                
                if ($existingUser) {
                    $error = 'ชื่อผู้ใช้นี้มีอยู่แล้ว กรุณาใช้ชื่ออื่น';
                } else {
                    $result = $this->auth->createUser($userData);
                    
                    if ($result['success']) {
                        header('Location: admin.php?action=users&message=user_created');
                        exit;
                    } else {
                        $error = $result['message'];
                    }
                }
            }
        }
        
        // ดึงข้อมูล roles และ companies ตามสิทธิ์
        if ($roleName === 'super_admin') {
            $roles = $this->getAllRoles();
            $companies = $this->getAllCompanies();
            $supervisors = $this->getAllSupervisors(); // ทุกบริษัท
        } else {
            // company_admin เห็นเฉพาะ role 3 และ 4
            $roles = $this->getAllowedRoles();
            $companies = []; // ไม่ต้องแสดงรายการบริษัท
            $supervisors = $this->getSupervisorsByCompany($companyId); // เฉพาะบริษัทตัวเอง
        }

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
        $roleName = $_SESSION['role_name'] ?? '';
        $companyId = $_SESSION['company_id'] ?? null;
        
        // ตรวจสอบว่าผู้ใช้เป็นของบริษัทตัวเองหรือไม่ (สำหรับ company_admin)
        if ($roleName === 'company_admin') {
            $user = $this->getUserById($userId, $companyId);
            if (!$user) {
                header('Location: admin.php?action=users');
                exit;
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userData = [
                'user_id' => $userId,
                'username' => $_POST['username'] ?? '',
                'full_name' => $_POST['full_name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'role_id' => $_POST['role_id'] ?? '',
                'company_id' => $roleName === 'super_admin' ? ($_POST['company_id'] ?? null) : $companyId,
                'supervisor_id' => $_POST['supervisor_id'] ?? null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            // ถ้ามีการเปลี่ยนรหัสผ่าน
            if (!empty($_POST['password'])) {
                $userData['password'] = $_POST['password'];
            }
            
            // ตรวจสอบว่า role ที่เลือกอนุญาตหรือไม่ (สำหรับ company_admin)
            if ($roleName === 'company_admin' && !in_array($userData['role_id'], [3, 4])) {
                $error = 'สามารถแก้ไขได้เฉพาะ Supervisor และ Telesales เท่านั้น';
            } else {
                $result = $this->auth->updateUser($userData);
                
                if ($result['success']) {
                    header('Location: admin.php?action=users&message=user_updated');
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
        
        $user = $this->auth->getUserById($userId);
        
        // ดึงข้อมูล roles และ companies ตามสิทธิ์
        if ($roleName === 'super_admin') {
            $roles = $this->getAllRoles();
            $companies = $this->getAllCompanies();
            $supervisors = $this->getAllSupervisors(); // ทุกบริษัท
        } else {
            // company_admin เห็นเฉพาะ role 3 และ 4
            $roles = $this->getAllowedRoles();
            $companies = []; // ไม่ต้องแสดงรายการบริษัท
            $supervisors = $this->getSupervisorsByCompany($companyId); // เฉพาะบริษัทตัวเอง
        }

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
        $roleName = $_SESSION['role_name'] ?? '';
        $companyId = $_SESSION['company_id'] ?? null;
        
        // ตรวจสอบว่าผู้ใช้เป็นของบริษัทตัวเองหรือไม่ (สำหรับ company_admin)
        if ($roleName === 'company_admin') {
            $user = $this->getUserById($userId, $companyId);
            if (!$user) {
                header('Location: admin.php?action=users');
                exit;
            }
        }
        
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
        
        $roleName = $_SESSION['role_name'] ?? '';
        $companyId = $_SESSION['company_id'] ?? null;
        
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
        $roleName = $_SESSION['role_name'] ?? '';
        $companyId = $_SESSION['company_id'] ?? null;
        
        $products = $this->getAllProducts($companyId);
        $categories = $this->getProductCategories($companyId);
        
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
                'stock_quantity' => $_POST['stock_quantity'] ?? 0,
                'company_id' => $_SESSION['company_id'] ?? null
            ];
            
            // ตรวจสอบรหัสสินค้าที่ซ้ำก่อนสร้าง
            $duplicateCheck = $this->checkDuplicateProductCode($productData['product_code'], $productData['company_id']);
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
        
        $categories = $this->getProductCategories($_SESSION['company_id'] ?? null);
        
        // Set page title and prepare content for layout
        $pageTitle = 'เพิ่มสินค้าใหม่ - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'products';

        // Capture create product content
        ob_start();
        include __DIR__ . '/../views/admin/products/create.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
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
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'company_id' => $_SESSION['company_id'] ?? null
            ];
            
            $result = $this->updateProductRecord($productData);
            
            if ($result['success']) {
                header('Location: admin.php?action=products&message=product_updated');
                exit;
            } else {
                $error = $result['message'];
            }
        }
        
        $product = $this->getProductById($productId, $_SESSION['company_id'] ?? null);
        $categories = $this->getProductCategories($_SESSION['company_id'] ?? null);
        
        // Set page title and prepare content for layout
        $pageTitle = 'แก้ไขสินค้า - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'products';

        // Capture edit product content
        ob_start();
        include __DIR__ . '/../views/admin/products/edit.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * ลบสินค้า
     */
    private function deleteProduct() {
        $productId = $_GET['id'] ?? 0;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->deleteProductRecord($productId, $_SESSION['company_id'] ?? null);
            
            if ($result['success']) {
                header('Location: admin.php?action=products&message=product_deleted');
                exit;
            } else {
                $error = $result['message'];
            }
        }
        
        $product = $this->getProductById($productId, $_SESSION['company_id'] ?? null);
        
        // Set page title and prepare content for layout
        $pageTitle = 'ลบสินค้า - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'products';

        // Capture delete product content
        ob_start();
        include __DIR__ . '/../views/admin/products/delete.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
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
        
        // Set page title and prepare content for layout
        $pageTitle = 'นำเข้าสินค้า - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'products';

        // Capture import products content
        ob_start();
        include __DIR__ . '/../views/admin/products/import.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * ส่งออกสินค้า
     */
    private function exportProducts() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roleName = $_SESSION['role_name'] ?? '';
            $companyId = $_SESSION['company_id'] ?? null;
            
            // ดาวน์โหลดไฟล์
            $products = $this->getAllProducts($companyId);
            
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
        $products = $this->getAllProducts($_SESSION['company_id'] ?? null);
        $categories = $this->getProductCategories($_SESSION['company_id'] ?? null);
        
        // คำนวณสถิติ
        $totalProducts = count($products);
        $activeProducts = count(array_filter($products, function($p) { return $p['is_active']; }));
        $inactiveProducts = $totalProducts - $activeProducts;
        $outOfStockProducts = count(array_filter($products, function($p) { return $p['stock_quantity'] <= 0; }));
        
        // Set page title and prepare content for layout
        $pageTitle = 'ส่งออกสินค้า - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'products';

        // Capture export products content
        ob_start();
        include __DIR__ . '/../views/admin/products/export.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * จัดการการตั้งค่าระบบ
     */
    public function settings() {
        $this->checkAdminPermission();
        
        $roleName = $_SESSION['role_name'] ?? '';
        
        // อนุญาตให้ตั้งค่าระบบเฉพาะ super_admin เท่านั้น
        if ($roleName !== 'super_admin') {
            header('Location: admin.php');
            exit;
        }

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

        $roleName = $_SESSION['role_name'] ?? '';
        $companyId = $_SESSION['company_id'] ?? null;

        // Load WorkflowService
        require_once __DIR__ . '/../services/WorkflowService.php';
        $workflowService = new WorkflowService();

        // ดึงสถิติ (กรองตาม company_id สำหรับ role=6)
        if ($roleName === 'super_admin') {
            $stats = $workflowService->getWorkflowStats();
        } else {
            $stats = $workflowService->getWorkflowStats($companyId);
        }

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
    private function getTotalUsers($companyId = null) {
        if ($companyId) {
            $sql = "SELECT COUNT(*) as total FROM users WHERE is_active = 1 AND company_id = ?";
            $result = $this->db->fetchOne($sql, [$companyId]);
        } else {
            $sql = "SELECT COUNT(*) as total FROM users WHERE is_active = 1";
            $result = $this->db->fetchOne($sql);
        }
        return $result['total'] ?? 0;
    }
    
    /**
     * ดึงจำนวนลูกค้าทั้งหมด
     */
    private function getTotalCustomers($companyId = null) {
        if ($companyId) {
            $sql = "SELECT COUNT(*) as total FROM customers WHERE is_active = 1 AND company_id = ?";
            $result = $this->db->fetchOne($sql, [$companyId]);
        } else {
            $sql = "SELECT COUNT(*) as total FROM customers WHERE is_active = 1";
            $result = $this->db->fetchOne($sql);
        }
        return $result['total'] ?? 0;
    }
    
    /**
     * ดึงจำนวนคำสั่งซื้อทั้งหมด
     */
    private function getTotalOrders($companyId = null) {
        if ($companyId) {
            $sql = "SELECT COUNT(*) as total FROM orders WHERE is_active = 1 AND company_id = ?";
            $result = $this->db->fetchOne($sql, [$companyId]);
        } else {
            $sql = "SELECT COUNT(*) as total FROM orders WHERE is_active = 1";
            $result = $this->db->fetchOne($sql);
        }
        return $result['total'] ?? 0;
    }
    
    /**
     * ดึงจำนวนสินค้าทั้งหมด
     */
    private function getTotalProducts($companyId = null) {
        if ($companyId) {
            $sql = "SELECT COUNT(*) as total FROM products WHERE is_active = 1 AND company_id = ?";
            $result = $this->db->fetchOne($sql, [$companyId]);
        } else {
            $sql = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
            $result = $this->db->fetchOne($sql);
        }
        return $result['total'] ?? 0;
    }
    
    /**
     * ดึงผู้ใช้ตามบริษัท
     */
    private function getUsersByCompany($companyId) {
        $sql = "SELECT u.*, r.role_name, c.company_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.role_id 
                LEFT JOIN companies c ON u.company_id = c.company_id 
                WHERE u.company_id = ?
                ORDER BY u.created_at DESC";
        
        return $this->db->fetchAll($sql, [$companyId]);
    }
    
    /**
     * ดึงกิจกรรมล่าสุด
     */
    private function getRecentActivities($companyId = null) {
        if ($companyId) {
            $sql = "SELECT ca.*, u.full_name as user_name, c.first_name, c.last_name
                    FROM customer_activities ca
                    LEFT JOIN users u ON ca.user_id = u.user_id
                    LEFT JOIN customers c ON ca.customer_id = c.customer_id
                    WHERE c.company_id = ?
                    ORDER BY ca.created_at DESC
                    LIMIT 10";
            return $this->db->fetchAll($sql, [$companyId]);
        } else {
            $sql = "SELECT ca.*, u.full_name as user_name, c.first_name, c.last_name
                    FROM customer_activities ca
                    LEFT JOIN users u ON ca.user_id = u.user_id
                    LEFT JOIN customers c ON ca.customer_id = c.customer_id
                    ORDER BY ca.created_at DESC
                    LIMIT 10";
            return $this->db->fetchAll($sql);
        }
    }
    
    /**
     * ดึงสถานะระบบ
     */
    private function getSystemHealth($companyId = null) {
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
     * ดึงบทบาทที่อนุญาตสำหรับ company_admin
     */
    private function getAllowedRoles() {
        $sql = "SELECT * FROM roles WHERE role_id IN (3, 4) ORDER BY role_name";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * ดึงข้อมูลผู้ใช้ตาม ID และ company_id
     */
    private function getUserById($userId, $companyId) {
        $sql = "SELECT u.*, r.role_name FROM users u 
                LEFT JOIN roles r ON u.role_id = r.role_id 
                WHERE u.user_id = ? AND u.company_id = ?";
        return $this->db->fetchOne($sql, [$userId, $companyId]);
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
    private function getAllProducts($companyId = null) {
        if ($companyId) {
            $sql = "SELECT * FROM products WHERE company_id = ? ORDER BY product_name";
            return $this->db->fetchAll($sql, [$companyId]);
        } else {
            $sql = "SELECT * FROM products ORDER BY product_name";
            return $this->db->fetchAll($sql);
        }
    }
    
    /**
     * ดึงสินค้าตาม ID
     */
    private function getProductById($productId, $companyId = null) {
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName === 'super_admin') {
            $sql = "SELECT * FROM products WHERE product_id = :product_id";
            return $this->db->fetchOne($sql, ['product_id' => $productId]);
        }
        $sql = "SELECT * FROM products WHERE product_id = :product_id AND company_id = :company_id";
        return $this->db->fetchOne($sql, ['product_id' => $productId, 'company_id' => ($companyId ?? $_SESSION['company_id'] ?? 2)]);
    }
    
    /**
     * ดึงหมวดหมู่สินค้า
     */
    private function getProductCategories($companyId = null) {
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
    private function checkDuplicateProductCode($productCode, $companyId = null) {
        try {
            $roleName = $_SESSION['role_name'] ?? '';
            if ($roleName === 'super_admin') {
                $sql = "SELECT COUNT(*) as count FROM products WHERE product_code = :product_code";
                $result = $this->db->fetchOne($sql, ['product_code' => $productCode]);
            } else {
                $sql = "SELECT COUNT(*) as count FROM products WHERE product_code = :product_code AND company_id = :company_id";
                $result = $this->db->fetchOne($sql, ['product_code' => $productCode, 'company_id' => ($companyId ?? $_SESSION['company_id'] ?? 2)]);
            }
            
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
            $sql = "INSERT INTO products (company_id, product_code, product_name, category, description, unit, cost_price, selling_price, stock_quantity, is_active, created_at, updated_at) 
                    VALUES (:company_id, :product_code, :product_name, :category, :description, :unit, :cost_price, :selling_price, :stock_quantity, 1, NOW(), NOW())";
            
            $params = $productData;
            if (!isset($params['company_id'])) {
                $params['company_id'] = $_SESSION['company_id'] ?? 2;
            }
            $this->db->query($sql, $params);
            
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
            $roleName = $_SESSION['role_name'] ?? '';
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
                    WHERE product_id = :product_id" . ($roleName === 'super_admin' ? "" : " AND company_id = :company_id");
            
            $params = $productData;
            if ($roleName !== 'super_admin') { 
                if (!isset($params['company_id'])) {
                    $params['company_id'] = $_SESSION['company_id'] ?? 2;
                }
            }
            $this->db->query($sql, $params);
            
            return ['success' => true, 'message' => 'อัปเดตสินค้าสำเร็จ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * ลบสินค้า
     */
    private function deleteProductRecord($productId, $companyId = null) {
        try {
            // ตรวจสอบว่าสินค้าถูกใช้ในคำสั่งซื้อหรือไม่
            $sql = "SELECT COUNT(*) as count FROM order_details WHERE product_id = :product_id";
            $result = $this->db->fetchOne($sql, ['product_id' => $productId]);
            
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'ไม่สามารถลบสินค้าได้ เนื่องจากถูกใช้ในคำสั่งซื้อ'];
            }
            
            $roleName = $_SESSION['role_name'] ?? '';
            if ($roleName === 'super_admin') {
                $sql = "DELETE FROM products WHERE product_id = :product_id";
                $this->db->query($sql, ['product_id' => $productId]);
            } else {
                $sql = "DELETE FROM products WHERE product_id = :product_id AND company_id = :company_id";
                $this->db->query($sql, ['product_id' => $productId, 'company_id' => ($companyId ?? $_SESSION['company_id'] ?? 2)]);
            }
            
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
                        'stock_quantity' => intval($data[7]),
                        'company_id' => $_SESSION['company_id'] ?? null
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
    private function getSystemSettings($companyId = null) {
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
        if (!in_array($roleName, ['admin', 'company_admin', 'supervisor', 'super_admin'])) {
            header('Location: index.php');
            exit;
        }

        // อนุญาตให้ override เฉพาะ super_admin เท่านั้น
        if ($roleName === 'super_admin' && isset($_REQUEST['company_override_id'])) {
            $id = (int)$_REQUEST['company_override_id'];
            if ($id > 0) {
                $_SESSION['override_company_id'] = $id;
            } else {
                unset($_SESSION['override_company_id']);
            }
        }

        // Set page title and prepare content for layout
        $pageTitle = 'ระบบแจกลูกค้า - CRM SalesTracker';
        $currentPage = 'admin';
        $currentAction = 'customer_distribution';

        // Capture customer distribution content
        ob_start();
        include __DIR__ . '/../views/admin/customer_distribution.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }

    /**
     * ดึงข้อมูลหัวหน้าทีมทั้งหมด (สำหรับ super_admin)
     */
    private function getAllSupervisors() {
        try {
            return $this->db->fetchAll("
                SELECT u.user_id, u.full_name, c.company_name
                FROM users u
                LEFT JOIN companies c ON u.company_id = c.company_id
                WHERE u.role_id = 3 AND u.is_active = 1
                ORDER BY c.company_name, u.full_name
            ");
        } catch (Exception $e) {
            error_log("Error fetching all supervisors: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ดึงข้อมูลหัวหน้าทีมตามบริษัท (สำหรับ company_admin)
     */
    private function getSupervisorsByCompany($companyId) {
        try {
            if (!$companyId) {
                return [];
            }
            
            return $this->db->fetchAll("
                SELECT u.user_id, u.full_name, c.company_name
                FROM users u
                LEFT JOIN companies c ON u.company_id = c.company_id
                WHERE u.role_id = 3 AND u.company_id = ? AND u.is_active = 1
                ORDER BY u.full_name
            ", [$companyId]);
        } catch (Exception $e) {
            error_log("Error fetching supervisors by company: " . $e->getMessage());
            return [];
        }
    }
}
?> 
