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
                if (empty($customerData['first_name']) || empty($customerData['phone'])) {
                    $results['errors'][] = "แถวที่ {$rowNumber}: ชื่อและเบอร์โทรศัพท์เป็นข้อมูลที่จำเป็น";
                    continue;
                }
                
                // Set default values
                $customerData['created_at'] = date('Y-m-d H:i:s');
                $customerData['updated_at'] = date('Y-m-d H:i:s');
                $customerData['customer_status'] = $customerData['customer_status'] ?? 'new';
                $customerData['temperature_status'] = $customerData['temperature_status'] ?? 'cold';
                $customerData['customer_grade'] = $customerData['customer_grade'] ?? 'C';
                $customerData['basket_type'] = 'distribution';
                $customerData['is_active'] = 1;
                
                // Insert customer
                $sql = "INSERT INTO customers (first_name, last_name, phone, email, address, district, province, postal_code, customer_status, temperature_status, customer_grade, basket_type, is_active, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->query($sql, [
                    $customerData['first_name'],
                    $customerData['last_name'] ?? '',
                    $customerData['phone'],
                    $customerData['email'] ?? '',
                    $customerData['address'] ?? '',
                    $customerData['district'] ?? '',
                    $customerData['province'] ?? '',
                    $customerData['postal_code'] ?? '',
                    $customerData['customer_status'],
                    $customerData['temperature_status'],
                    $customerData['customer_grade'],
                    $customerData['basket_type'],
                    $customerData['is_active'],
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
        $sql = "SELECT c.*, CONCAT(c.first_name, ' ', c.last_name) as full_name
                FROM customers c 
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['customer_status'])) {
            $sql .= " AND c.customer_status = ?";
            $params[] = $filters['customer_status'];
        }
        
        if (!empty($filters['temperature_status'])) {
            $sql .= " AND c.temperature_status = ?";
            $params[] = $filters['temperature_status'];
        }
        
        if (!empty($filters['customer_grade'])) {
            $sql .= " AND c.customer_grade = ?";
            $params[] = $filters['customer_grade'];
        }
        
        if (!empty($filters['basket_type'])) {
            $sql .= " AND c.basket_type = ?";
            $params[] = $filters['basket_type'];
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        $customers = $this->db->fetchAll($sql, $params);
        
        return $customers;
    }
    
    /**
     * ส่งออกรายงานคำสั่งซื้อเป็น CSV
     */
    public function exportOrdersToCSV($filters = []) {
        $sql = "SELECT o.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.phone as customer_phone,
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.customer_id
                LEFT JOIN users u ON o.created_by = u.user_id
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
        
        $orders = $this->db->fetchAll($sql, $params);
        
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
     * นำเข้ายอดขายจาก CSV
     */
    public function importSalesFromCSV($filePath) {
        error_log("ImportSalesFromCSV started with file: " . $filePath);
        
        $results = [
            'success' => 0,
            'errors' => [],
            'total' => 0,
            'customers_updated' => 0,
            'customers_created' => 0,
            'orders_created' => 0
        ];
        
        if (!file_exists($filePath)) {
            error_log("File not found: " . $filePath);
            $results['errors'][] = 'ไฟล์ไม่พบ';
            return $results;
        }
        
        // Set internal encoding
        mb_internal_encoding('UTF-8');
        
        try {
            // Read file content and handle encoding
            $content = file_get_contents($filePath);
            if ($content === false) {
                error_log("Failed to read file: " . $filePath);
                $results['errors'][] = 'ไม่สามารถอ่านไฟล์ได้';
                return $results;
            }
            
            error_log("File content length: " . strlen($content));
            
            // Skip encoding detection to avoid compatibility issues
            // Assume UTF-8 and handle BOM if present
            if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
                $content = substr($content, 3); // Remove UTF-8 BOM
                error_log("Removed UTF-8 BOM from file");
            }
            
            // Ensure content is treated as UTF-8
            if (!mb_check_encoding($content, 'UTF-8')) {
                // Try to convert from common Thai encodings
                $content = @iconv('CP874', 'UTF-8//IGNORE', $content);
                if ($content === false) {
                    $content = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $content);
                }
                error_log("Content converted to UTF-8");
            }
            
            // Remove BOM if present
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
            
            // Split content into lines
            $lines = explode("\n", $content);
            
            // Remove empty lines
            $lines = array_filter($lines, function($line) {
                return trim($line) !== '';
            });
            
            error_log("Number of non-empty lines: " . count($lines));
            
            if (empty($lines)) {
                error_log("No data lines found in CSV");
                $results['errors'][] = 'ไฟล์ CSV ว่างเปล่า';
                return $results;
            }
            
            // Process header
            $headerLine = array_shift($lines);
            $headers = str_getcsv($headerLine);
            
            error_log("Headers found: " . json_encode($headers));
            
            // Clean headers
            $headers = array_map(function($header) {
                return trim($header);
            }, $headers);
            
            // Map headers to database columns
            $columnMap = $this->getSalesColumnMap();
            $mappedHeaders = [];
            
            foreach ($headers as $header) {
                if (isset($columnMap[$header])) {
                    $mappedHeaders[] = $columnMap[$header];
                } else {
                    $mappedHeaders[] = null;
                }
            }
            
            error_log("Mapped headers: " . json_encode($mappedHeaders));
            
            $rowNumber = 1; // Header row
            foreach ($lines as $line) {
                $rowNumber++;
                $results['total']++;
                
                try {
                    $data = str_getcsv($line);
                    $salesData = [];
                    
                    foreach ($mappedHeaders as $index => $column) {
                        if ($column && isset($data[$index])) {
                            $value = trim($data[$index]);
                            $salesData[$column] = $value;
                        }
                    }
                    
                    // Debug: Log the data being processed
                    error_log("Processing row {$rowNumber}: " . json_encode($salesData));
                    
                    // Validate required fields
                    if (empty($salesData['first_name'])) {
                        $results['errors'][] = "แถวที่ {$rowNumber}: ชื่อเป็นข้อมูลที่จำเป็น";
                        continue;
                    }
                    
                    if (empty($salesData['phone'])) {
                        $results['errors'][] = "แถวที่ {$rowNumber}: เบอร์โทรศัพท์เป็นข้อมูลที่จำเป็น";
                        continue;
                    }
                    
                    // Check if customer exists by phone
                    $existingCustomer = $this->db->fetchOne(
                        "SELECT customer_id, first_name, last_name FROM customers WHERE phone = ?",
                        [$salesData['phone']]
                    );
                    
                    if ($existingCustomer) {
                        error_log("Updating existing customer: " . $existingCustomer['customer_id']);
                        // Update existing customer's purchase history
                        $this->updateCustomerPurchaseHistory($existingCustomer['customer_id'], $salesData);
                        $results['customers_updated']++;
                    } else {
                        error_log("Creating new customer for phone: " . $salesData['phone']);
                        // Create new customer and add to distribution basket
                        $customerId = $this->createNewCustomer($salesData);
                        if ($customerId) {
                            $this->updateCustomerPurchaseHistory($customerId, $salesData);
                            $results['customers_created']++;
                        }
                    }
                    
                    $results['orders_created']++;
                    $results['success']++;
                    
                } catch (Exception $e) {
                    error_log("Error processing row {$rowNumber}: " . $e->getMessage());
                    $results['errors'][] = "แถวที่ {$rowNumber}: " . $e->getMessage();
                }
            }
            
            error_log("Import completed. Results: " . json_encode($results));
            return $results;
            
        } catch (Exception $e) {
            error_log("Fatal error in importSalesFromCSV: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $results['errors'][] = 'เกิดข้อผิดพลาดในการประมวลผลไฟล์: ' . $e->getMessage();
            return $results;
        }
    }
    
    /**
     * นำเข้าเฉพาะรายชื่อจาก CSV
     */
    public function importCustomersOnlyFromCSV($filePath) {
        $results = [
            'success' => 0,
            'errors' => [],
            'total' => 0,
            'customers_created' => 0,
            'customers_skipped' => 0
        ];
        
        if (!file_exists($filePath)) {
            $results['errors'][] = 'ไฟล์ไม่พบ';
            return $results;
        }
        
        // Set internal encoding
        mb_internal_encoding('UTF-8');
        
        // Read file content and handle encoding
        $content = file_get_contents($filePath);
        
        // Skip encoding detection to avoid compatibility issues
        // Assume UTF-8 and handle BOM if present
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3); // Remove UTF-8 BOM
        }
        
        // Ensure content is treated as UTF-8
        if (!mb_check_encoding($content, 'UTF-8')) {
            // Try to convert from common Thai encodings
            $content = @iconv('CP874', 'UTF-8//IGNORE', $content);
            if ($content === false) {
                $content = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $content);
            }
        }
        
        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // Split content into lines
        $lines = explode("\n", $content);
        
        // Remove empty lines
        $lines = array_filter($lines, function($line) {
            return trim($line) !== '';
        });
        
        if (empty($lines)) {
            $results['errors'][] = 'ไฟล์ CSV ว่างเปล่า';
            return $results;
        }
        
        // Process header
        $headerLine = array_shift($lines);
        $headers = str_getcsv($headerLine);
        
        // Clean headers
        $headers = array_map(function($header) {
            return trim($header);
        }, $headers);
        
        // Map headers to database columns
        $columnMap = $this->getCustomersOnlyColumnMap();
        $mappedHeaders = [];
        
        foreach ($headers as $header) {
            if (isset($columnMap[$header])) {
                $mappedHeaders[] = $columnMap[$header];
            } else {
                $mappedHeaders[] = null;
            }
        }
        
        $rowNumber = 1; // Header row
        foreach ($lines as $line) {
            $rowNumber++;
            $results['total']++;
            
            try {
                $data = str_getcsv($line);
                $customerData = [];
                
                foreach ($mappedHeaders as $index => $column) {
                    if ($column && isset($data[$index])) {
                        $value = trim($data[$index]);
                        $customerData[$column] = $value;
                    }
                }
                
                // Debug: Log the data being processed
                error_log("Processing row {$rowNumber}: " . json_encode($customerData));
                
                // Validate required fields
                if (empty($customerData['first_name'])) {
                    $results['errors'][] = "แถวที่ {$rowNumber}: ชื่อเป็นข้อมูลที่จำเป็น";
                    continue;
                }
                
                if (empty($customerData['phone'])) {
                    $results['errors'][] = "แถวที่ {$rowNumber}: เบอร์โทรศัพท์เป็นข้อมูลที่จำเป็น";
                    continue;
                }
                
                // Check if customer exists (by phone only)
                $existingCustomer = $this->db->fetchOne(
                    "SELECT customer_id FROM customers WHERE phone = ?",
                    [$customerData['phone']]
                );
                
                if ($existingCustomer) {
                    // Skip existing customer
                    $results['customers_skipped']++;
                    continue;
                }
                
                // Create new customer in distribution basket
                $customerId = $this->createNewCustomerOnly($customerData);
                if ($customerId) {
                    $results['customers_created']++;
                    $results['success']++;
                }
                
            } catch (Exception $e) {
                $results['errors'][] = "แถวที่ {$rowNumber}: " . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    /**
     * Map CSV headers to database columns for sales import
     */
    private function getSalesColumnMap() {
        return [
            'ชื่อ' => 'first_name',
            'นามสกุล' => 'last_name',
            'เบอร์โทรศัพท์' => 'phone',
            'อีเมล' => 'email',
            'ที่อยู่' => 'address',
            'เขต' => 'district',
            'ตำบล' => 'district',  // Support old column name
            'จังหวัด' => 'province',
            'อำเภอ' => 'province',  // Support old column name
            'รหัสไปรษณีย์' => 'postal_code',
            'ชื่อสินค้า' => 'product_name',
            'จำนวน' => 'quantity',
            'ราคาต่อชิ้น' => 'unit_price',
            'ยอดรวม' => 'total_amount',
            'วันที่สั่งซื้อ' => 'order_date',
            'ช่องทางการขาย' => 'sales_channel',
            'หมายเหตุ' => 'notes'
        ];
    }
    
    /**
     * Map CSV headers to database columns for customers only import
     */
    private function getCustomersOnlyColumnMap() {
        return [
            'ชื่อ' => 'first_name',
            'นามสกุล' => 'last_name',
            'เบอร์โทรศัพท์' => 'phone',
            'อีเมล' => 'email',
            'ที่อยู่' => 'address',
            'เขต' => 'district',
            'ตำบล' => 'district',  // Support old column name
            'จังหวัด' => 'province',
            'อำเภอ' => 'province',  // Support old column name
            'รหัสไปรษณีย์' => 'postal_code',
            'หมายเหตุ' => 'notes'
        ];
    }
    
    /**
     * Map CSV headers to database columns
     */
    private function getCustomerColumnMap() {
        return [
            'ชื่อ' => 'first_name',
            'Name' => 'first_name',
            'นามสกุล' => 'last_name',
            'Last Name' => 'last_name',
            'เบอร์โทรศัพท์' => 'phone',
            'Phone' => 'phone',
            'อีเมล' => 'email',
            'Email' => 'email',
            'ที่อยู่' => 'address',
            'Address' => 'address',
            'เขต' => 'district',
            'ตำบล' => 'district',  // Support old column name
            'District' => 'district',
            'จังหวัด' => 'province',
            'อำเภอ' => 'province',  // Support old column name
            'Province' => 'province',
            'รหัสไปรษณีย์' => 'postal_code',
            'Postal Code' => 'postal_code',
            'สถานะ' => 'customer_status',
            'Status' => 'customer_status',
            'อุณหภูมิ' => 'temperature_status',
            'Temperature' => 'temperature_status',
            'เกรด' => 'customer_grade',
            'Grade' => 'customer_grade',
            'หมายเหตุ' => 'notes',
            'Notes' => 'notes'
        ];
    }
    
    /**
     * Get customer statistics
     */
    private function getCustomerStatistics($startDate = null, $endDate = null) {
        $sql = "SELECT 
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN customer_status = 'existing' THEN 1 END) as existing_customers,
                    COUNT(CASE WHEN temperature_status = 'hot' THEN 1 END) as hot_customers,
                    COUNT(CASE WHEN temperature_status = 'warm' THEN 1 END) as warm_customers,
                    COUNT(CASE WHEN temperature_status = 'cold' THEN 1 END) as cold_customers,
                    COUNT(CASE WHEN temperature_status = 'frozen' THEN 1 END) as frozen_customers
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
        
        return $this->db->fetchOne($sql, $params);
    }
    
    /**
     * Get order statistics
     */
    private function getOrderStatistics($startDate = null, $endDate = null) {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    COUNT(CASE WHEN delivery_status = 'pending' THEN 1 END) as pending_orders,
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
        
        return $this->db->fetchOne($sql, $params);
    }
    
    /**
     * สร้างลูกค้าใหม่สำหรับ Sales Import
     */
    private function createNewCustomer($salesData) {
        try {
            $customerData = [
                'first_name' => $salesData['first_name'],
                'last_name' => $salesData['last_name'] ?? '',
                'phone' => $salesData['phone'],
                'email' => $salesData['email'] ?? '',
                'address' => $salesData['address'] ?? '',
                'district' => $salesData['district'] ?? '',
                'province' => $salesData['province'] ?? '',
                'postal_code' => $salesData['postal_code'] ?? '',
                'basket_type' => 'distribution', // เข้าตะกร้าแจก
                'temperature_status' => 'hot', // ลูกค้าใหม่
                'customer_grade' => 'D', // เกรดเริ่มต้น
                'customer_status' => 'new',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $sql = "INSERT INTO customers (first_name, last_name, phone, email, address, district, province, postal_code, basket_type, temperature_status, customer_grade, customer_status, is_active, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->query($sql, [
                $customerData['first_name'],
                $customerData['last_name'],
                $customerData['phone'],
                $customerData['email'],
                $customerData['address'],
                $customerData['district'],
                $customerData['province'],
                $customerData['postal_code'],
                $customerData['basket_type'],
                $customerData['temperature_status'],
                $customerData['customer_grade'],
                $customerData['customer_status'],
                $customerData['is_active'],
                $customerData['created_at'],
                $customerData['updated_at']
            ]);
            
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Error creating new customer: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * สร้างลูกค้าใหม่สำหรับ Customers Only Import
     */
    private function createNewCustomerOnly($customerData) {
        try {
            $data = [
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'] ?? '',
                'phone' => $customerData['phone'],
                'email' => $customerData['email'] ?? '',
                'address' => $customerData['address'] ?? '',
                'district' => $customerData['district'] ?? '',
                'province' => $customerData['province'] ?? '',
                'postal_code' => $customerData['postal_code'] ?? '',
                'basket_type' => 'distribution', // เข้าตะกร้าแจก
                'temperature_status' => 'cold', // ลูกค้าเย็น (ยังไม่มียอดขาย)
                'customer_grade' => 'D', // เกรดเริ่มต้น
                'customer_status' => 'new',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $sql = "INSERT INTO customers (first_name, last_name, phone, email, address, district, province, postal_code, basket_type, temperature_status, customer_grade, customer_status, is_active, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->query($sql, [
                $data['first_name'],
                $data['last_name'],
                $data['phone'],
                $data['email'],
                $data['address'],
                $data['district'],
                $data['province'],
                $data['postal_code'],
                $data['basket_type'],
                $data['temperature_status'],
                $data['customer_grade'],
                $data['customer_status'],
                $data['is_active'],
                $data['created_at'],
                $data['updated_at']
            ]);
            
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Error creating new customer only: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * อัปเดตประวัติการซื้อของลูกค้า
     */
    private function updateCustomerPurchaseHistory($customerId, $salesData) {
        try {
            // สร้างคำสั่งซื้อ
            $orderData = [
                'customer_id' => $customerId,
                'order_number' => 'EXT-' . date('YmdHis') . '-' . rand(1000, 9999),
                'order_date' => $salesData['order_date'] ?? date('Y-m-d'),
                'total_amount' => $salesData['total_amount'] ?? 0,
                'net_amount' => $salesData['total_amount'] ?? 0,
                'payment_status' => 'paid',
                'delivery_status' => 'delivered',
                'notes' => 'นำเข้าจาก ' . ($salesData['sales_channel'] ?? 'External') . ' - ' . ($salesData['notes'] ?? ''),
                'created_by' => $_SESSION['user_id'] ?? 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $sql = "INSERT INTO orders (customer_id, order_number, order_date, total_amount, net_amount, payment_status, delivery_status, notes, created_by, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->query($sql, [
                $orderData['customer_id'],
                $orderData['order_number'],
                $orderData['order_date'],
                $orderData['total_amount'],
                $orderData['net_amount'],
                $orderData['payment_status'],
                $orderData['delivery_status'],
                $orderData['notes'],
                $orderData['created_by'],
                $orderData['created_at'],
                $orderData['updated_at']
            ]);
            
            $orderId = $this->db->lastInsertId();
            
            // สร้างรายการสินค้า (ถ้าตาราง order_items มีอยู่)
            if (!empty($salesData['product_name']) && $this->tableExists('order_items')) {
                $itemData = [
                    'order_id' => $orderId,
                    'product_name' => $salesData['product_name'],
                    'quantity' => $salesData['quantity'] ?? 1,
                    'unit_price' => $salesData['unit_price'] ?? 0,
                    'total_price' => $salesData['total_amount'] ?? 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $sql = "INSERT INTO order_items (order_id, product_name, quantity, unit_price, total_price, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                
                $this->db->query($sql, [
                    $itemData['order_id'],
                    $itemData['product_name'],
                    $itemData['quantity'],
                    $itemData['unit_price'],
                    $itemData['total_price'],
                    $itemData['created_at']
                ]);
            }
            
            // อัปเดตยอดซื้อรวมของลูกค้า
            $this->updateCustomerTotalPurchase($customerId);
            
        } catch (Exception $e) {
            error_log("Error updating customer purchase history: " . $e->getMessage());
        }
    }
    
    /**
     * อัปเดตยอดซื้อรวมของลูกค้า
     */
    private function updateCustomerTotalPurchase($customerId) {
        try {
            $sql = "UPDATE customers SET 
                        total_purchase_amount = (
                            SELECT COALESCE(SUM(net_amount), 0) 
                            FROM orders 
                            WHERE customer_id = ? AND payment_status = 'paid'
                        ),
                        updated_at = NOW()
                    WHERE customer_id = ?";
            
            $this->db->query($sql, [$customerId, $customerId]);
            
        } catch (Exception $e) {
            error_log("Error updating customer total purchase: " . $e->getMessage());
        }
    }
    
    /**
     * Get database instance
     */
    public function getDatabase() {
        return $this->db;
    }
    
    /**
     * Check if table exists
     */
    private function tableExists($tableName) {
        try {
            // ใช้ information_schema แทน SHOW TABLES LIKE เพื่อความปลอดภัย
            $sql = "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
            $result = $this->db->fetchOne($sql, [$tableName]);
            return $result && $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking table existence: " . $e->getMessage());
            return false;
        }
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
        
        return $this->db->fetchOne($sql, $params);
    }
} 