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
     * ส่งออกข้อมูลลูกค้าเป็น CSV
     */
    public function exportCustomers() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $filters = [
            'status' => $_POST['status'] ?? null,
            'temperature' => $_POST['temperature'] ?? null,
            'grade' => $_POST['grade'] ?? null
        ];
        
        $customers = $this->importExportService->exportCustomersToCSV($filters);
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d_H-i-s') . '.csv"');
        
        // Create CSV output
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Set internal encoding
        mb_internal_encoding('UTF-8');
        
        // Headers
        fputcsv($output, [
            'ID', 'ชื่อ', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'บริษัท', 
            'สถานะ', 'อุณหภูมิ', 'เกรด', 'วันที่สร้าง', 'วันที่อัปเดต'
        ]);
        
        // Data
        foreach ($customers as $customer) {
            fputcsv($output, [
                $customer['id'],
                mb_convert_encoding($customer['name'], 'UTF-8', 'auto'),
                $customer['phone'],
                $customer['email'],
                mb_convert_encoding($customer['address'], 'UTF-8', 'auto'),
                mb_convert_encoding($customer['company_name'], 'UTF-8', 'auto'),
                $this->getStatusText($customer['status']),
                $this->getTemperatureText($customer['temperature']),
                $customer['grade'],
                $customer['created_at'],
                $customer['updated_at']
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
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d_H-i-s') . '.csv"');
        
        // Create CSV output
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'ID', 'ลูกค้า', 'เบอร์โทรศัพท์', 'ยอดรวม', 'ส่วนลด', 'ยอดสุทธิ',
            'สถานะการจัดส่ง', 'ผู้สร้าง', 'บริษัท', 'วันที่สร้าง'
        ]);
        
        // Data
        foreach ($orders as $order) {
            fputcsv($output, [
                $order['id'],
                mb_convert_encoding($order['customer_name'], 'UTF-8', 'auto'),
                $order['customer_phone'],
                number_format($order['total_amount'], 2),
                number_format($order['discount_amount'], 2),
                number_format($order['net_amount'], 2),
                $this->getDeliveryStatusText($order['delivery_status']),
                mb_convert_encoding($order['created_by_name'], 'UTF-8', 'auto'),
                mb_convert_encoding($order['company_name'], 'UTF-8', 'auto'),
                $order['created_at']
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
     * ดาวน์โหลดไฟล์ CSV Template
     */
    public function downloadTemplate() {
        $type = $_GET['type'] ?? 'customers';
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="template_' . $type . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if ($type === 'customers') {
            // Read from template file
            $templateFile = __DIR__ . '/../../templates/customers_template.csv';
            if (file_exists($templateFile)) {
                $handle = fopen($templateFile, 'r');
                while (($data = fgetcsv($handle)) !== false) {
                    fputcsv($output, $data);
                }
                fclose($handle);
            } else {
                // Fallback to inline template
                fputcsv($output, ['ชื่อ', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'สถานะ', 'อุณหภูมิ', 'เกรด']);
                fputcsv($output, ['สมชาย ใจดี', '0812345678', 'somchai@example.com', '123 ถ.สุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110', 'active', 'cold', 'C']);
            }
        }
        
        fclose($output);
    }
    
    /**
     * Helper functions for text conversion
     */
    private function getStatusText($status) {
        $statusMap = [
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