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
            
            $file = $_FILES['csv_file'];
            error_log("File info: " . json_encode($file));
            
            // Create upload directory
            $uploadDir = __DIR__ . '/../../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
                error_log("Created upload directory: " . $uploadDir);
            }
            
            // Move uploaded file
            $uploadedFile = $uploadDir . 'sales_' . date('Y-m-d_H-i-s') . '.csv';
            if (!move_uploaded_file($file['tmp_name'], $uploadedFile)) {
                error_log("Failed to move uploaded file");
                echo json_encode([
                    'error' => 'ไม่สามารถอัปโหลดไฟล์ได้',
                    'success' => 0,
                    'total' => 0,
                    'customers_updated' => 0,
                    'customers_created' => 0,
                    'orders_created' => 0,
                    'errors' => ['ไม่สามารถอัปโหลดไฟล์ได้']
                ]);
                return;
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
            
            $file = $_FILES['csv_file'];
            
            // Create upload directory
            $uploadDir = __DIR__ . '/../../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Move uploaded file
            $uploadedFile = $uploadDir . 'customers_only_' . date('Y-m-d_H-i-s') . '.csv';
            if (!move_uploaded_file($file['tmp_name'], $uploadedFile)) {
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
                'headers' => ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'จังหวัด', 'ชื่อสินค้า', 'จำนวน', 'ราคาต่อชิ้น', 'ยอดรวม', 'วันที่สั่งซื้อ'],
                'sample' => ['สมชาย', 'ใจดี', '081-111-1111', 'somchai@email.com', '123 ถนนสุขุมวิท', 'กรุงเทพฯ', 'เสื้อโปโล', '1', '250', '250', '2025-01-01']
            ],
            'customers_only' => [
                'filename' => 'customers_only_template.csv',
                'headers' => ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'จังหวัด', 'หมายเหตุ'],
                'sample' => ['สมหญิง', 'รักดี', '081-222-2222', 'somying@email.com', '456 ถนนรัชดา', 'กรุงเทพฯ', 'ลูกค้าใหม่']
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
