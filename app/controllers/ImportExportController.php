<?php
/**
 * Import/Export Controller
 * จัดการการนำเข้าและส่งออกข้อมูล
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../services/ImportExportService.php';

class ImportExportController {
    private $importExportService;
    private $db;

    public function __construct() {
        $this->importExportService = new ImportExportService();
        $this->db = new Database();
    }

    /**
     * แสดงหน้า Import/Export
     */
    public function index() {
        // Check permissions
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        // Get backup files list
        $backupDir = __DIR__ . '/../../backups/';
        $backupFiles = [];

        if (is_dir($backupDir)) {
            $files = glob($backupDir . '*.sql');
            foreach ($files as $file) {
                $backupFiles[] = [
                    'name' => basename($file),
                    'size' => filesize($file),
                    'date' => date('Y-m-d H:i:s', filemtime($file))
                ];
            }
        }

        // Sort by date (newest first)
        usort($backupFiles, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        // Set page title and prepare content for layout
        $pageTitle = 'นำเข้า/ส่งออกข้อมูล - CRM SalesTracker';
        $currentPage = 'import-export';

        // Read role and company for UI logic (for super_admin dropdown)
        $roleName = $_SESSION['role_name'] ?? '';
        $companyName = $_SESSION['company_name'] ?? '';
        $companies = [];
        if ($roleName === 'super_admin') {
            try {
                $companies = $this->db->fetchAll("SELECT company_id, company_name, company_code FROM companies WHERE is_active = 1 ORDER BY company_name ASC");
            } catch (Exception $e) {
                error_log("Error fetching companies: " . $e->getMessage());
                $companies = [];
            }
        }

        // Capture import-export content
        ob_start();
        include __DIR__ . '/../views/import-export/index.php';
        $content = ob_get_clean();

        // Use main layout
        include __DIR__ . '/../views/layouts/main.php';
    }

    /**
     * Check import permission: only super_admin and admin
     */
    public function requireImportPermission() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        $roleName = $_SESSION['role_name'] ?? '';
        if (!in_array($roleName, ['super_admin', 'admin'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
    }


    /**
     * นำเข้าข้อมูลลูกค้าจาก CSV
     */
    public function importCustomers() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'กรุณาเลือกไฟล์ CSV']);
            return;
        }

        $file = $_FILES['csv_file'];
        $allowedTypes = ['text/csv', 'application/csv', 'text/plain'];

        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['error' => 'ไฟล์ต้องเป็นรูปแบบ CSV เท่านั้น']);
            return;
        }

        // Optional: super_admin company override
        if (isset($_POST['company_override'])) {
            if (!isset($_SESSION)) { session_start(); }
            $name = trim($_POST['company_override']);
            if ($name !== '') {
                $_SESSION['override_company_source'] = $name; // e.g., 'Prima' or 'Prionic'
            } else {
                unset($_SESSION['override_company_source']);
            }
        }

        // Create upload directory with error handling
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                error_log("Failed to create upload directory: " . $uploadDir);
                echo json_encode(['error' => 'ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้']);
                return;
            }
        }

        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            chmod($uploadDir, 0777);
            if (!is_writable($uploadDir)) {
                echo json_encode(['error' => 'โฟลเดอร์อัปโหลดไม่สามารถเขียนได้']);
                return;
            }
        }

        // Handle uploaded file with robust approach
        $uploadedFile = $uploadDir . 'customers_' . date('Y-m-d_H-i-s') . '.csv';
        $file_moved = false;

        // ลองใช้ move_uploaded_file ก่อน (สำหรับ real HTTP uploads)
        if (is_uploaded_file($file['tmp_name'])) {
            if (move_uploaded_file($file['tmp_name'], $uploadedFile)) {
                $file_moved = true;
                error_log("Successfully moved uploaded file for customers");
            } else {
                error_log("move_uploaded_file() failed for customers, trying copy()");
            }
        } else {
            error_log("Not a real uploaded file for customers, using copy()");
        }

        // ถ้า move_uploaded_file ล้มเหลว หรือไม่ใช่ uploaded file ให้ใช้ copy
        if (!$file_moved) {
            if (copy($file['tmp_name'], $uploadedFile)) {
                $file_moved = true;
                error_log("Successfully copied file for customers import");
            } else {
                error_log("Both move_uploaded_file() and copy() failed for customers");
                echo json_encode(['error' => 'ไม่สามารถอัปโหลดไฟล์ได้']);
                return;
            }
        }

            // Optional: super_admin company override
            if (isset($_POST['company_override'])) {
                if (!isset($_SESSION)) { session_start(); }
                $name = trim($_POST['company_override']);
                if ($name !== '') {
                    $_SESSION['override_company_source'] = $name; // e.g., 'Prima' or 'Prionic'
                } else {
                    unset($_SESSION['override_company_source']);
                }
            }

        // Import data
        $results = $this->importExportService->importCustomersFromCSV($uploadedFile);

        // Clean up uploaded file
        unlink($uploadedFile);

        echo json_encode($results);
    }

    /**
     * นำเข้ายอดขายจาก CSV
     */
    public function importSales() {
        try {
            // Log the request
            error_log("ImportSales called - Method: " . $_SERVER['REQUEST_METHOD']);
            error_log("FILES: " . json_encode($_FILES));

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed', 'success' => 0]);
                return;
            }

            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                error_log("CSV file error: " . ($_FILES['csv_file']['error'] ?? 'No file'));
                echo json_encode([
                    'error' => 'กรุณาเลือกไฟล์ CSV',
                    'success' => 0,
                    'total' => 0,
                    'customers_updated' => 0,
                    'customers_created' => 0,
                    'orders_created' => 0,
                    'errors' => ['ไม่พบไฟล์ CSV ที่อัปโหลด']
                ]);
                return;
            }


            // Optional: super_admin company override (global dropdown)
            if (isset($_POST['company_override'])) {
                if (!isset($_SESSION)) { session_start(); }
                $name = trim($_POST['company_override']);
                if ($name !== '') {
                    $_SESSION['override_company_source'] = $name; // usually company_code (e.g., PRIMA49, A02)
                } else {
                    unset($_SESSION['override_company_source']);
                }
            }

            $file = $_FILES['csv_file'];
            error_log("File info: " . json_encode($file));

            // Create upload directory with better error handling
            $uploadDir = __DIR__ . '/../../uploads/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("Failed to create upload directory: " . $uploadDir);
                    echo json_encode([
                        'error' => 'ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้',
                        'success' => 0,
                        'total' => 0,
                        'customers_updated' => 0,
                        'customers_created' => 0,
                        'orders_created' => 0,
                        'errors' => ['ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้']
                    ]);
                    return;
                }
                error_log("Created upload directory: " . $uploadDir);
            }

            // Check if directory is writable
            if (!is_writable($uploadDir)) {
                error_log("Upload directory not writable: " . $uploadDir);
                // Try to fix permissions
                if (!chmod($uploadDir, 0777)) {
                    error_log("Failed to fix upload directory permissions");
                }
                // Check again
                if (!is_writable($uploadDir)) {
                    echo json_encode([
                        'error' => 'โฟลเดอร์อัปโหลดไม่สามารถเขียนได้',
                        'success' => 0,
                        'total' => 0,
                        'customers_updated' => 0,
                        'customers_created' => 0,
                        'orders_created' => 0,
                        'errors' => ['โฟลเดอร์อัปโหลดไม่สามารถเขียนได้']
                    ]);
                    return;
                }
            }

            // Check source file
            if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
                error_log("Source file not readable: " . $file['tmp_name']);
                echo json_encode([
                    'error' => 'ไฟล์ต้นฉบับไม่สามารถอ่านได้',
                    'success' => 0,
                    'total' => 0,
                    'customers_updated' => 0,
                    'customers_created' => 0,
                    'orders_created' => 0,
                    'errors' => ['ไฟล์ต้นฉบับไม่สามารถอ่านได้']
                ]);
                return;
            }

            // Handle uploaded file with robust approach
            $uploadedFile = $uploadDir . 'sales_' . date('Y-m-d_H-i-s') . '.csv';
            error_log("Attempting to move file from " . $file['tmp_name'] . " to " . $uploadedFile);

            $file_moved = false;

            // ลองใช้ move_uploaded_file ก่อน (สำหรับ real HTTP uploads)
            if (is_uploaded_file($file['tmp_name'])) {
                error_log("Real uploaded file detected, using move_uploaded_file()");
                if (move_uploaded_file($file['tmp_name'], $uploadedFile)) {
                    $file_moved = true;
                    error_log("Successfully moved uploaded file");
                } else {
                    error_log("move_uploaded_file() failed, trying copy() as fallback");
                }
            } else {
                error_log("Not a real uploaded file, using copy() directly");
            }

            // ถ้า move_uploaded_file ล้มเหลว หรือไม่ใช่ uploaded file ให้ใช้ copy
            if (!$file_moved) {
                if (copy($file['tmp_name'], $uploadedFile)) {
                    $file_moved = true;
                    error_log("Successfully copied file using copy()");
                } else {
                    error_log("Both move_uploaded_file() and copy() failed");
                    echo json_encode([
                        'error' => 'ไม่สามารถอัปโหลดไฟล์ได้ (ทั้ง move และ copy ล้มเหลว)',
                        'success' => 0,
                        'total' => 0,
                        'customers_updated' => 0,
                        'customers_created' => 0,
                        'orders_created' => 0,
                        'errors' => ['ไม่สามารถอัปโหลดไฟล์ได้']
                    ]);
                    return;
                }
            }

            error_log("File uploaded successfully: " . $uploadedFile);

            // Import data
            $results = $this->importExportService->importSalesFromCSV($uploadedFile);

            error_log("Import results: " . json_encode($results));

            // Clean up uploaded file
            if (file_exists($uploadedFile)) {
                unlink($uploadedFile);
                error_log("Cleaned up uploaded file");
            }

            echo json_encode($results);

        } catch (Exception $e) {
            error_log("Import Sales Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            echo json_encode([
                'error' => 'เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์: ' . $e->getMessage(),
                'success' => 0,
                'total' => 0,
                'customers_updated' => 0,
                'customers_created' => 0,
                'orders_created' => 0,
                'errors' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * นำเข้าเฉพาะรายชื่อลูกค้าจาก CSV
     */
    public function importCustomersOnly() {
        try {
            error_log("ImportCustomersOnly called - Method: " . $_SERVER['REQUEST_METHOD']);

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed', 'success' => 0]);
                return;
            }

            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode([
                    'error' => 'กรุณาเลือกไฟล์ CSV',
                    'success' => 0,
                    'total' => 0,
                    'customers_created' => 0,
                    'customers_skipped' => 0,
                    'errors' => ['ไม่พบไฟล์ CSV ที่อัปโหลด']
                ]);
                return;
            }

            // Optional: super_admin company override (global dropdown)
            if (isset($_POST['company_override'])) {
                if (!isset($_SESSION)) { session_start(); }
                $name = trim($_POST['company_override']);
                if ($name !== '') {
                    $_SESSION['override_company_source'] = $name; // usually company_code (e.g., PRIMA49, A02)
                } else {
                    unset($_SESSION['override_company_source']);
                }
            }


            $file = $_FILES['csv_file'];

            // Create upload directory with better error handling
            $uploadDir = __DIR__ . '/../../uploads/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("Failed to create upload directory: " . $uploadDir);
                    echo json_encode([
                        'error' => 'ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้',
                        'success' => 0,
                        'total' => 0,
                        'customers_created' => 0,
                        'customers_skipped' => 0,
                        'errors' => ['ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้']
                    ]);
                    return;
                }
            }

            // Check if directory is writable
            if (!is_writable($uploadDir)) {
                error_log("Upload directory not writable: " . $uploadDir);
                chmod($uploadDir, 0777);
                if (!is_writable($uploadDir)) {
                    echo json_encode([
                        'error' => 'โฟลเดอร์อัปโหลดไม่สามารถเขียนได้',
                        'success' => 0,
                        'total' => 0,
                        'customers_created' => 0,
                        'customers_skipped' => 0,
                        'errors' => ['โฟลเดอร์อัปโหลดไม่สามารถเขียนได้']
                    ]);
                    return;
                }
            }

            // Handle uploaded file with robust approach
            $uploadedFile = $uploadDir . 'customers_only_' . date('Y-m-d_H-i-s') . '.csv';
            $file_moved = false;

            // ลองใช้ move_uploaded_file ก่อน (สำหรับ real HTTP uploads)
            if (is_uploaded_file($file['tmp_name'])) {
                if (move_uploaded_file($file['tmp_name'], $uploadedFile)) {
                    $file_moved = true;
                    error_log("Successfully moved uploaded file for customers only");
                } else {
                    error_log("move_uploaded_file() failed for customers only, trying copy()");
                }
            } else {
                error_log("Not a real uploaded file for customers only, using copy()");
            }

            // ถ้า move_uploaded_file ล้มเหลว หรือไม่ใช่ uploaded file ให้ใช้ copy
            if (!$file_moved) {
                if (copy($file['tmp_name'], $uploadedFile)) {
                    $file_moved = true;
                    error_log("Successfully copied file for customers only import");
                } else {
                    error_log("Both move_uploaded_file() and copy() failed for customers only");
                    echo json_encode([
                        'error' => 'ไม่สามารถอัปโหลดไฟล์ได้',
                        'success' => 0,
                        'total' => 0,
                        'customers_created' => 0,
                        'customers_skipped' => 0,
                        'errors' => ['ไม่สามารถอัปโหลดไฟล์ได้']
                    ]);
                    return;
                }
            }

            // Import data
            $results = $this->importExportService->importCustomersOnlyFromCSV($uploadedFile);

            // Clean up uploaded file
            if (file_exists($uploadedFile)) {
                unlink($uploadedFile);
            }

            echo json_encode($results);

        } catch (Exception $e) {
            error_log("Import Customers Only Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            echo json_encode([
                'error' => 'เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์: ' . $e->getMessage(),
                'success' => 0,
                'total' => 0,
                'customers_created' => 0,
                'customers_skipped' => 0,
                'errors' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * ส่งออกรายชื่อลูกค้าเป็น CSV
     */
    public function exportCustomers() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $filters = [
            'customer_status' => $_POST['customer_status'] ?? null,
            'temperature_status' => $_POST['temperature_status'] ?? null,
            'customer_grade' => $_POST['customer_grade'] ?? null,
            'basket_type' => $_POST['basket_type'] ?? null
        ];

        $customers = $this->importExportService->exportCustomersToCSV($filters);

        // Set headers for CSV download with proper encoding
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        // Create CSV output
        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Headers
        fputcsv($output, [
            'ID', 'ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'ตำบล', 'อำเภอ', 'จังหวัด', 'รหัสไปรษณีย์',
            'สถานะ', 'อุณหภูมิ', 'เกรด', 'ประเภทตะกร้า', 'ยอดซื้อรวม', 'วันที่สร้าง', 'วันที่อัปเดต'
        ]);

        // Data
        foreach ($customers as $customer) {
            fputcsv($output, [
                $customer['customer_id'],
                $customer['first_name'] ?? '',
                $customer['last_name'] ?? '',
                $customer['phone'] ?? '',
                $customer['email'] ?? '',
                $customer['address'] ?? '',
                $customer['district'] ?? '',
                $customer['province'] ?? '',
                $customer['postal_code'] ?? '',
                $this->getStatusText($customer['customer_status'] ?? ''),
                $this->getTemperatureText($customer['temperature_status'] ?? ''),
                $customer['customer_grade'] ?? '',
                $customer['basket_type'] ?? '',
                number_format($customer['total_purchase_amount'] ?? 0, 2),
                $customer['created_at'] ?? '',
                $customer['updated_at'] ?? ''
            ]);
        }

        fclose($output);
    }

    /**
     * ส่งออกรายงานคำสั่งซื้อเป็น CSV
     */
    public function exportOrders() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $filters = [
            'delivery_status' => $_POST['delivery_status'] ?? null,
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null
        ];

        $orders = $this->importExportService->exportOrdersToCSV($filters);

        // Set headers for CSV download with proper encoding
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        // Create CSV output
        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Headers
        fputcsv($output, [
            'ID', 'ลูกค้า', 'เบอร์โทรศัพท์', 'หมายเลขคำสั่งซื้อ', 'ยอดรวม', 'ส่วนลด', 'ยอดสุทธิ',
            'สถานะการชำระ', 'สถานะการจัดส่ง', 'ผู้สร้าง', 'วันที่สร้าง'
        ]);

        // Data
        foreach ($orders as $order) {
            fputcsv($output, [
                $order['order_id'] ?? '',
                $order['customer_name'] ?? '',
                $order['customer_phone'] ?? '',
                $order['order_number'] ?? '',
                number_format($order['total_amount'] ?? 0, 2),
                number_format($order['discount_amount'] ?? 0, 2),
                number_format($order['net_amount'] ?? 0, 2),
                $order['payment_status'] ?? '',
                $this->getDeliveryStatusText($order['delivery_status'] ?? ''),
                $order['created_by_name'] ?? '',
                $order['created_at'] ?? ''
            ]);
        }

        fclose($output);
    }

    /**
     * สร้างรายงานสรุป
     */
    public function exportSummaryReport() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;

        $reports = $this->importExportService->exportSummaryReport($startDate, $endDate);

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="summary_report_' . date('Y-m-d_H-i-s') . '.csv"');

        // Create CSV output
        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Customer Statistics
        fputcsv($output, ['สถิติลูกค้า']);
        fputcsv($output, ['รายการ', 'จำนวน']);
        fputcsv($output, ['ลูกค้าทั้งหมด', $reports['customer_stats']['total_customers']]);
        fputcsv($output, ['ลูกค้า Hot', $reports['customer_stats']['hot_customers']]);
        fputcsv($output, ['ลูกค้า Warm', $reports['customer_stats']['warm_customers']]);
        fputcsv($output, ['ลูกค้า Cold', $reports['customer_stats']['cold_customers']]);
        fputcsv($output, ['ลูกค้า Frozen', $reports['customer_stats']['frozen_customers']]);
        fputcsv($output, []); // Empty row

        // Order Statistics
        fputcsv($output, ['สถิติคำสั่งซื้อ']);
        fputcsv($output, ['รายการ', 'จำนวน']);
        fputcsv($output, ['คำสั่งซื้อทั้งหมด', $reports['order_stats']['total_orders']]);
        fputcsv($output, ['รอดำเนินการ', $reports['order_stats']['pending_orders']]);
        fputcsv($output, ['จัดส่งแล้ว', $reports['order_stats']['shipped_orders']]);
        fputcsv($output, ['สำเร็จแล้ว', $reports['order_stats']['delivered_orders']]);
        fputcsv($output, ['ยกเลิก', $reports['order_stats']['cancelled_orders']]);
        fputcsv($output, []); // Empty row

        // Revenue Statistics
        fputcsv($output, ['สถิติรายได้']);
        fputcsv($output, ['รายการ', 'จำนวน']);
        fputcsv($output, ['รายได้รวม', number_format($reports['revenue_stats']['total_revenue'], 2)]);
        fputcsv($output, ['ยอดเฉลี่ยต่อคำสั่ง', number_format($reports['revenue_stats']['average_order_value'], 2)]);
        fputcsv($output, ['จำนวนคำสั่งซื้อ', $reports['revenue_stats']['total_orders']]);

        fclose($output);
    }

    /**
     * ดาวน์โหลด Template CSV
     */
    public function downloadTemplate() {
        $type = $_GET['type'] ?? 'sales';

        $templates = [
            'sales' => [
                'filename' => 'sales_import_template.csv',
                'headers' => ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'จังหวัด', 'รหัสไปรษณีย์', 'ชื่อสินค้า', 'จำนวน', 'ราคาต่อชิ้น', 'ยอดรวม', 'เลขที่คำสั่งซื้อ', 'วันที่สั่งซื้อ', 'รหัสสินค้า', 'ผู้ติดตาม', 'ผู้ขาย', 'วิธีการชำระเงิน', 'สถานะการชำระเงิน'],
                'sample' => ['สมชาย', 'ใจดี', '081-111-1111', 'somchai@email.com', '123 ถนนสุขุมวิท', 'กรุงเทพฯ', '10110', 'เสื้อโปโล', '1', '250', '250', 'PO-2025-0001', '2025-01-01', 'PROD001', 'พนักงานขาย1', 'พนักงานขาย1', 'เงินสด', 'ชำระแล้ว']
            ],
            'sales_simple' => [
                'filename' => 'sales_import_template_simple.csv',
                'headers' => ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'จังหวัด', 'รหัสไปรษณีย์', 'ชื่อสินค้า', 'จำนวน', 'ยอดรวม', 'เลขที่คำสั่งซื้อ', 'วันที่สั่งซื้อ', 'รหัสสินค้า', 'ผู้ติดตาม', 'ผู้ขาย', 'วิธีการชำระเงิน', 'สถานะการชำระเงิน'],
                'sample' => ['สมชาย', 'ใจดี', '081-111-1111', 'somchai@email.com', '123 ถนนสุขุมวิท', 'กรุงเทพฯ', '10110', 'เสื้อโปโล', '1', '250', 'PO-2025-0001', '2025-01-01', 'PROD001', 'พนักงานขาย1', 'พนักงานขาย1', 'เงินสด', 'ชำระแล้ว']
            ],
            'customers_only' => [
                'filename' => 'customers_only_template.csv',
                'headers' => ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'จังหวัด', 'รหัสไปรษณีย์', 'หมายเหตุ', 'รหัสสินค้า', 'ผู้ติดตาม'],
                'sample' => ['สมหญิง', 'รักดี', '081-222-2222', 'somying@email.com', '456 ถนนรัชดา', 'กรุงเทพฯ', '10400', 'ลูกค้าใหม่', 'PROD002', 'พนักงานขาย2']
            ],
            'customers' => [
                'filename' => 'customers_template.csv',
                'headers' => ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'จังหวัด', 'รหัสไปรษณีย์', 'หมายเหตุ', 'รหัสสินค้า', 'ผู้ติดตาม'],
                'sample' => ['สมหญิง', 'รักดี', '081-222-2222', 'somying@email.com', '456 ถนนรัชดา', 'กรุงเทพฯ', '10400', 'ลูกค้าใหม่', 'PROD002', 'พนักงานขาย2']
            ],
            'call_logs' => [
                'filename' => 'call_logs_template.csv',
                'headers' => ['customer_code','call_type','call_status','call_result','duration_minutes','notes','next_action','next_followup_at','called_at','recorded_by'],
                'sample' => ['CUS812345678','outbound','answered','interested','3','สนใจสินค้า A','นัดติดตาม','2025-08-20 14:00:00','2025-08-14 10:30:00','telesales1']
            ]
        ];

        if (!isset($templates[$type])) {
            http_response_code(404);
            echo "Template not found";
            return;
        }

        $template = $templates[$type];

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $template['filename'] . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        // Create CSV output
        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write header and sample data
        fputcsv($output, $template['headers']);
        fputcsv($output, $template['sample']);

        fclose($output);
    }

    /**
     * นำเข้าประวัติการโทรจาก CSV (อ้างอิง customer_code)
     */
    public function importCallLogs() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }

            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['error' => 'กรุณาเลือกไฟล์ CSV']);
                return;
            }

            $file = $_FILES['csv_file'];
            $uploadDir = __DIR__ . '/../../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $uploadedFile = $uploadDir . 'call_logs_' . date('Y-m-d_H-i-s') . '.csv';
            if (is_uploaded_file($file['tmp_name'])) {
                move_uploaded_file($file['tmp_name'], $uploadedFile);
            } else {
                copy($file['tmp_name'], $uploadedFile);
            }

            $results = $this->importExportService->importCallLogsFromCSV($uploadedFile);
            if (file_exists($uploadedFile)) unlink($uploadedFile);
            echo json_encode($results);
        } catch (Exception $e) {
            echo json_encode(['success'=>0,'error'=>$e->getMessage()]);
        }
    }

    /**
     * สร้าง Backup ฐานข้อมูล
     */
    public function createBackup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $result = $this->importExportService->createDatabaseBackup();
        echo json_encode($result);
    }

    /**
     * Restore ฐานข้อมูลจาก backup
     */
    public function restoreBackup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $backupFile = $_POST['backup_file'] ?? '';
        if (empty($backupFile)) {
            echo json_encode(['error' => 'กรุณาเลือกไฟล์ backup']);
            return;
        }

        $backupPath = __DIR__ . '/../../backups/' . $backupFile;
        $result = $this->importExportService->restoreDatabaseFromBackup($backupPath);
        echo json_encode($result);
    }

    /**
     * แปลงสถานะเป็นข้อความ
     */
    private function getStatusText($status) {
        $statuses = [
            'new' => 'ใหม่',
            'existing' => 'เก่า',
            'active' => 'ใช้งาน',
            'inactive' => 'ไม่ใช้งาน'
        ];
        return $statuses[$status] ?? $status;
    }

    /**
     * แปลงสถานะอุณหภูมิเป็นข้อความ
     */
    private function getTemperatureText($temperature) {
        $temperatures = [
            'hot' => 'ร้อน',
            'warm' => 'อุ่น',
            'cold' => 'เย็น',
            'frozen' => 'แข็ง'
        ];
        return $temperatures[$temperature] ?? $temperature;
    }

    /**
     * แปลงสถานะการจัดส่งเป็นข้อความ
     */
    private function getDeliveryStatusText($status) {
        $statuses = [
            'pending' => 'รอดำเนินการ',
            'processing' => 'กำลังเตรียม',
            'shipped' => 'จัดส่งแล้ว',
            'delivered' => 'สำเร็จแล้ว',
            'cancelled' => 'ยกเลิก'
        ];
        return $statuses[$status] ?? $status;
    }
}
?>
