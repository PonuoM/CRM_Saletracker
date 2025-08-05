<?php
/**
 * Import/Export Service
 * จัดการการนำเข้าและส่งออกข้อมูล
 */

require_once __DIR__ . '/../core/Database.php';

class ImportExportService {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * นำเข้าข้อมูลลูกค้าจาก CSV
     */
    public function importCustomersFromCSV($filePath) {
        $results = [
            'success' => 0,
            'errors' => [],
            'total' => 0
        ];
        
        if (!file_exists($filePath)) {
            $results['errors'][] = 'ไฟล์ไม่พบ';
            return $results;
        }
        
        // Set internal encoding
        mb_internal_encoding('UTF-8');
        
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $results['errors'][] = 'ไม่สามารถเปิดไฟล์ได้';
            return $results;
        }
        
        // อ่าน header
        $headers = fgetcsv($handle);
        
        // Check for UTF-8 BOM and skip if present
        if ($headers && !empty($headers[0]) && substr($headers[0], 0, 3) === "\xEF\xBB\xBF") {
            $headers[0] = substr($headers[0], 3);
        }
        
        if (!$headers) {
            $results['errors'][] = 'ไฟล์ CSV ไม่ถูกต้อง';
            fclose($handle);
            return $results;
        }
        
        // Map headers to database columns
        $columnMap = $this->getCustomerColumnMap();
        $mappedHeaders = [];
        
        foreach ($headers as $header) {
            $header = trim($header);
            // Ensure proper UTF-8 encoding for header
            if (!mb_check_encoding($header, 'UTF-8')) {
                $header = mb_convert_encoding($header, 'UTF-8', 'auto');
            }
            if (isset($columnMap[$header])) {
                $mappedHeaders[] = $columnMap[$header];
            } else {
                $mappedHeaders[] = null;
            }
        }
        
        $rowNumber = 1; // Header row
        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $results['total']++;
            
            try {
                $customerData = [];
                foreach ($mappedHeaders as $index => $column) {
                    if ($column && isset($data[$index])) {
                        $value = trim($data[$index]);
                        // Ensure proper UTF-8 encoding
                        if (!mb_check_encoding($value, 'UTF-8')) {
                            $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                        }
                        $customerData[$column] = $value;
                    }
                }
                
                // Validate required fields
                if (empty($customerData['name']) || empty($customerData['phone'])) {
                    $results['errors'][] = "แถวที่ {$rowNumber}: ชื่อและเบอร์โทรศัพท์เป็นข้อมูลที่จำเป็น";
                    continue;
                }
                
                // Set default values
                $customerData['created_at'] = date('Y-m-d H:i:s');
                $customerData['updated_at'] = date('Y-m-d H:i:s');
                $customerData['status'] = $customerData['status'] ?? 'active';
                $customerData['temperature'] = $customerData['temperature'] ?? 'cold';
                $customerData['grade'] = $customerData['grade'] ?? 'C';
                
                // Insert customer
                $sql = "INSERT INTO customers (name, phone, email, address, company_id, status, temperature, grade, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $customerData['name'],
                    $customerData['phone'],
                    $customerData['email'] ?? '',
                    $customerData['address'] ?? '',
                    $customerData['company_id'] ?? 1,
                    $customerData['status'],
                    $customerData['temperature'],
                    $customerData['grade'],
                    $customerData['created_at'],
                    $customerData['updated_at']
                ]);
                
                $results['success']++;
                
            } catch (Exception $e) {
                $results['errors'][] = "แถวที่ {$rowNumber}: " . $e->getMessage();
            }
        }
        
        fclose($handle);
        return $results;
    }
    
    /**
     * ส่งออกข้อมูลลูกค้าเป็น CSV
     */
    public function exportCustomersToCSV($filters = []) {
        $sql = "SELECT c.*, co.name as company_name 
                FROM customers c 
                LEFT JOIN companies co ON c.company_id = co.id 
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['temperature'])) {
            $sql .= " AND c.temperature = ?";
            $params[] = $filters['temperature'];
        }
        
        if (!empty($filters['grade'])) {
            $sql .= " AND c.grade = ?";
            $params[] = $filters['grade'];
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $customers;
    }
    
    /**
     * ส่งออกรายงานคำสั่งซื้อเป็น CSV
     */
    public function exportOrdersToCSV($filters = []) {
        $sql = "SELECT o.*, c.name as customer_name, c.phone as customer_phone,
                       u.name as created_by_name, co.name as company_name
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN users u ON o.created_by = u.id
                LEFT JOIN companies co ON o.company_id = co.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['delivery_status'])) {
            $sql .= " AND o.delivery_status = ?";
            $params[] = $filters['delivery_status'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(o.created_at) >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(o.created_at) <= ?";
            $params[] = $filters['end_date'];
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $orders;
    }
    
    /**
     * สร้างรายงานสรุปเป็น CSV
     */
    public function exportSummaryReport($startDate = null, $endDate = null) {
        $reports = [];
        
        // สถิติลูกค้า
        $customerStats = $this->getCustomerStatistics($startDate, $endDate);
        $reports['customer_stats'] = $customerStats;
        
        // สถิติคำสั่งซื้อ
        $orderStats = $this->getOrderStatistics($startDate, $endDate);
        $reports['order_stats'] = $orderStats;
        
        // รายได้
        $revenueStats = $this->getRevenueStatistics($startDate, $endDate);
        $reports['revenue_stats'] = $revenueStats;
        
        return $reports;
    }
    
    /**
     * สร้าง Backup ฐานข้อมูล
     */
    public function createDatabaseBackup() {
        $backupDir = __DIR__ . '/../../backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . "backup_{$timestamp}.sql";
        
        // Get database configuration
        $host = DB_HOST;
        $dbname = DB_NAME;
        $username = DB_USER;
        $password = DB_PASS;
        
        // Create backup command
        $command = "mysqldump -h {$host} -u {$username} -p{$password} {$dbname} > {$backupFile}";
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            return [
                'success' => true,
                'file' => $backupFile,
                'size' => filesize($backupFile),
                'timestamp' => $timestamp
            ];
        } else {
            return [
                'success' => false,
                'error' => 'ไม่สามารถสร้าง backup ได้'
            ];
        }
    }
    
    /**
     * Restore ฐานข้อมูลจาก backup
     */
    public function restoreDatabaseFromBackup($backupFile) {
        if (!file_exists($backupFile)) {
            return ['success' => false, 'error' => 'ไฟล์ backup ไม่พบ'];
        }
        
        // Get database configuration
        $host = DB_HOST;
        $dbname = DB_NAME;
        $username = DB_USER;
        $password = DB_PASS;
        
        // Create restore command
        $command = "mysql -h {$host} -u {$username} -p{$password} {$dbname} < {$backupFile}";
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            return ['success' => true, 'message' => 'Restore สำเร็จ'];
        } else {
            return ['success' => false, 'error' => 'ไม่สามารถ restore ได้'];
        }
    }
    
    /**
     * Map CSV headers to database columns
     */
    private function getCustomerColumnMap() {
        return [
            'ชื่อ' => 'name',
            'Name' => 'name',
            'เบอร์โทรศัพท์' => 'phone',
            'Phone' => 'phone',
            'อีเมล' => 'email',
            'Email' => 'email',
            'ที่อยู่' => 'address',
            'Address' => 'address',
            'สถานะ' => 'status',
            'Status' => 'status',
            'อุณหภูมิ' => 'temperature',
            'Temperature' => 'temperature',
            'เกรด' => 'grade',
            'Grade' => 'grade',
            'บริษัท' => 'company_id',
            'Company' => 'company_id'
        ];
    }
    
    /**
     * Get customer statistics
     */
    private function getCustomerStatistics($startDate = null, $endDate = null) {
        $sql = "SELECT 
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_customers,
                    COUNT(CASE WHEN temperature = 'hot' THEN 1 END) as hot_customers,
                    COUNT(CASE WHEN temperature = 'warm' THEN 1 END) as warm_customers,
                    COUNT(CASE WHEN temperature = 'cold' THEN 1 END) as cold_customers,
                    COUNT(CASE WHEN temperature = 'frozen' THEN 1 END) as frozen_customers
                FROM customers";
        
        $params = [];
        if ($startDate) {
            $sql .= " WHERE created_at >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= $startDate ? " AND" : " WHERE";
            $sql .= " created_at <= ?";
            $params[] = $endDate;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get order statistics
     */
    private function getOrderStatistics($startDate = null, $endDate = null) {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    COUNT(CASE WHEN delivery_status = 'pending' THEN 1 END) as pending_orders,
                    COUNT(CASE WHEN delivery_status = 'processing' THEN 1 END) as processing_orders,
                    COUNT(CASE WHEN delivery_status = 'shipped' THEN 1 END) as shipped_orders,
                    COUNT(CASE WHEN delivery_status = 'delivered' THEN 1 END) as delivered_orders,
                    COUNT(CASE WHEN delivery_status = 'cancelled' THEN 1 END) as cancelled_orders
                FROM orders";
        
        $params = [];
        if ($startDate) {
            $sql .= " WHERE created_at >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= $startDate ? " AND" : " WHERE";
            $sql .= " created_at <= ?";
            $params[] = $endDate;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get revenue statistics
     */
    private function getRevenueStatistics($startDate = null, $endDate = null) {
        $sql = "SELECT 
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as average_order_value,
                    COUNT(*) as total_orders
                FROM orders";
        
        $params = [];
        if ($startDate) {
            $sql .= " WHERE created_at >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= $startDate ? " AND" : " WHERE";
            $sql .= " created_at <= ?";
            $params[] = $endDate;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
} 