<?php
/**
 * Import/Export Controller
 * จัดการการนำเข้าและส่งออกข้อมูล
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../services/ImportExportService.php';

class ImportExportController {
    private $importExportService;
    
    public function __construct() {
        $this->importExportService = new ImportExportService();
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
        
        include __DIR__ . '/../views/import-export/index.php';
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
        
        // Create upload directory
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Move uploaded file
        $uploadedFile = $uploadDir . 'customers_' . date('Y-m-d_H-i-s') . '.csv';
        if (!move_uploaded_file($file['tmp_name'], $uploadedFile)) {
            echo json_encode(['error' => 'ไม่สามารถอัปโหลดไฟล์ได้']);
            return;
        }
        
        // Import data
        $results = $this->importExportService->importCustomersFromCSV($uploadedFile);
        
        // Clean up uploaded file
        unlink($uploadedFile);
        
        echo json_encode($results);
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
        fputcsv($output, ['ลูกค้าที่ใช้งาน', $reports['customer_stats']['active_customers']]);
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
        fputcsv($output, ['กำลังดำเนินการ', $reports['order_stats']['processing_orders']]);
        fputcsv($output, ['จัดส่งแล้ว', $reports['order_stats']['shipped_orders']]);
        fputcsv($output, ['จัดส่งสำเร็จ', $reports['order_stats']['delivered_orders']]);
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
        
        $backupPath = __DIR__ . '/../../backups/' . basename($backupFile);
        $result = $this->importExportService->restoreDatabaseFromBackup($backupPath);
        echo json_encode($result);
    }
    
    /**
     * ดาวน์โหลด Template CSV
     */
    public function downloadTemplate() {
        $type = $_GET['type'] ?? 'customers';
        
        // Set filename based on type
        $filename = '';
        switch ($type) {
            case 'sales':
                $filename = 'sales_import_template.csv';
                break;
            case 'customers_only':
                $filename = 'customers_only_template.csv';
                break;
            default:
                $filename = 'customers_template.csv';
                break;
        }
        
        // Set headers for CSV download with proper encoding
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if ($type === 'sales') {
            // Template สำหรับนำเข้ายอดขาย
            $templateFile = __DIR__ . '/../../templates/sales_import_template.csv';
            if (file_exists($templateFile)) {
                // Read file content and ensure proper encoding
                $content = file_get_contents($templateFile);
                // Remove existing BOM if present
                $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
                
                // Split content into lines and process
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $data = str_getcsv($line);
                        fputcsv($output, $data);
                    }
                }
            } else {
                // Fallback template
                fputcsv($output, ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'เขต', 'จังหวัด', 'รหัสไปรษณีย์', 'ชื่อสินค้า', 'จำนวน', 'ราคาต่อชิ้น', 'ยอดรวม', 'วันที่สั่งซื้อ', 'ช่องทางการขาย', 'หมายเหตุ']);
                fputcsv($output, ['สมชาย', 'ใจดี', '0812345678', 'somchai@example.com', '123 ถ.สุขุมวิท', 'คลองเตย', 'กรุงเทพฯ', '10110', 'สินค้า A', '2', '1500', '3000', '2025-08-06', 'TikTok', 'ลูกค้าใหม่']);
            }
        } elseif ($type === 'customers_only') {
            // Template สำหรับเฉพาะรายชื่อ
            $templateFile = __DIR__ . '/../../templates/customers_only_template.csv';
            if (file_exists($templateFile)) {
                // Read file content and ensure proper encoding
                $content = file_get_contents($templateFile);
                // Remove existing BOM if present
                $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
                
                // Split content into lines and process
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $data = str_getcsv($line);
                        fputcsv($output, $data);
                    }
                }
            } else {
                // Fallback template
                fputcsv($output, ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'เขต', 'จังหวัด', 'รหัสไปรษณีย์', 'หมายเหตุ']);
                fputcsv($output, ['สมชาย', 'ใจดี', '0812345678', 'somchai@example.com', '123 ถ.สุขุมวิท', 'คลองเตย', 'กรุงเทพฯ', '10110', 'ลูกค้าจาก Facebook']);
            }
        } else {
            // Template เดิมสำหรับ customers
            $templateFile = __DIR__ . '/../../templates/customers_template.csv';
            if (file_exists($templateFile)) {
                // Read file content and ensure proper encoding
                $content = file_get_contents($templateFile);
                // Remove existing BOM if present
                $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
                
                // Split content into lines and process
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $data = str_getcsv($line);
                        fputcsv($output, $data);
                    }
                }
            } else {
                // Fallback to inline template
                fputcsv($output, ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'เขต', 'จังหวัด', 'รหัสไปรษณีย์', 'สถานะ', 'อุณหภูมิ', 'เกรด', 'หมายเหตุ']);
                fputcsv($output, ['สมชาย', 'ใจดี', '0812345678', 'somchai@example.com', '123 ถ.สุขุมวิท', 'คลองเตย', 'กรุงเทพฯ', '10110', 'new', 'cold', 'C', 'ลูกค้าใหม่']);
            }
        }
        
        fclose($output);
    }
    
    /**
     * นำเข้ายอดขาย (Sales Import)
     */
    public function importSales() {
        // Enable error reporting for debugging (but don't display errors)
        error_reporting(E_ALL);
        ini_set('display_errors', 0); // Don't display errors to prevent HTML output
        
        // Log the request
        error_log("Import Sales Request Started");
        error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
        error_log("Files: " . json_encode($_FILES));
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Method not allowed: " . $_SERVER['REQUEST_METHOD']);
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        if (!isset($_FILES['csv_file'])) {
            error_log("No CSV file uploaded");
            http_response_code(400);
            echo json_encode(['error' => 'กรุณาเลือกไฟล์ CSV']);
            return;
        }
        
        if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            error_log("File upload error: " . $_FILES['csv_file']['error']);
            http_response_code(400);
            echo json_encode(['error' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์']);
            return;
        }
        
        $file = $_FILES['csv_file'];
        $allowedTypes = ['text/csv', 'application/csv', 'text/plain'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            error_log("Invalid file type: " . $file['type']);
            http_response_code(400);
            echo json_encode(['error' => 'ไฟล์ต้องเป็นรูปแบบ CSV เท่านั้น']);
            return;
        }
        
        // Create upload directory
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("Failed to create upload directory: " . $uploadDir);
                http_response_code(500);
                echo json_encode(['error' => 'ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้']);
                return;
            }
        }
        
        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            error_log("Upload directory not writable: " . $uploadDir);
            http_response_code(500);
            echo json_encode(['error' => 'โฟลเดอร์อัปโหลดไม่สามารถเขียนได้']);
            return;
        }
        
        // Move uploaded file
        $uploadedFile = $uploadDir . 'sales_' . date('Y-m-d_H-i-s') . '.csv';
        if (!move_uploaded_file($file['tmp_name'], $uploadedFile)) {
            error_log("Failed to move uploaded file from " . $file['tmp_name'] . " to " . $uploadedFile);
            http_response_code(500);
            echo json_encode(['error' => 'ไม่สามารถอัปโหลดไฟล์ได้']);
            return;
        }
        
        error_log("File uploaded successfully: " . $uploadedFile);
        
        try {
            // Import data
            error_log("Starting import process");
            $results = $this->importExportService->importSalesFromCSV($uploadedFile);
            error_log("Import completed successfully: " . json_encode($results));
            
            // Clean up uploaded file
            if (file_exists($uploadedFile)) {
                unlink($uploadedFile);
                error_log("Cleaned up uploaded file: " . $uploadedFile);
            }
            
            // Set success status code
            http_response_code(200);
            echo json_encode($results);
        } catch (Exception $e) {
            // Clean up uploaded file
            if (file_exists($uploadedFile)) {
                unlink($uploadedFile);
                error_log("Cleaned up uploaded file after error: " . $uploadedFile);
            }
            
            // Log error for debugging
            error_log("Import Sales Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Set error status code
            http_response_code(500);
            echo json_encode([
                'error' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage(),
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
     * นำเข้าเฉพาะรายชื่อ (Customers Only Import)
     */
    public function importCustomersOnly() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'กรุณาเลือกไฟล์ CSV']);
            return;
        }
        
        $file = $_FILES['csv_file'];
        $allowedTypes = ['text/csv', 'application/csv', 'text/plain'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'ไฟล์ต้องเป็นรูปแบบ CSV เท่านั้น']);
            return;
        }
        
        // Create upload directory
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Move uploaded file
        $uploadedFile = $uploadDir . 'customers_only_' . date('Y-m-d_H-i-s') . '.csv';
        if (!move_uploaded_file($file['tmp_name'], $uploadedFile)) {
            http_response_code(500);
            echo json_encode(['error' => 'ไม่สามารถอัปโหลดไฟล์ได้']);
            return;
        }
        
        try {
            // Import data
            $results = $this->importExportService->importCustomersOnlyFromCSV($uploadedFile);
            
            // Clean up uploaded file
            if (file_exists($uploadedFile)) {
                unlink($uploadedFile);
            }
            
            echo json_encode($results);
        } catch (Exception $e) {
            // Clean up uploaded file
            if (file_exists($uploadedFile)) {
                unlink($uploadedFile);
            }
            
            // Log error for debugging
            error_log("Import Customers Only Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            echo json_encode([
                'error' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage(),
                'success' => 0,
                'total' => 0,
                'customers_created' => 0,
                'customers_skipped' => 0,
                'errors' => [$e->getMessage()]
            ]);
        }
    }
    
    /**
     * Helper functions for text conversion
     */
    private function getStatusText($status) {
        $statusMap = [
            'new' => 'ลูกค้าใหม่',
            'existing' => 'ลูกค้าเก่า',
            'active' => 'ใช้งาน',
            'inactive' => 'ไม่ใช้งาน'
        ];
        return $statusMap[$status] ?? $status;
    }
    
    private function getTemperatureText($temperature) {
        $tempMap = [
            'hot' => 'ร้อน',
            'warm' => 'อุ่น',
            'cold' => 'เย็น',
            'frozen' => 'แช่แข็ง'
        ];
        return $tempMap[$temperature] ?? $temperature;
    }
    
    private function getDeliveryStatusText($status) {
        $statusMap = [
            'pending' => 'รอดำเนินการ',
            'processing' => 'กำลังดำเนินการ',
            'shipped' => 'จัดส่งแล้ว',
            'delivered' => 'จัดส่งสำเร็จ',
            'cancelled' => 'ยกเลิก'
        ];
        return $statusMap[$status] ?? $status;
    }
} 