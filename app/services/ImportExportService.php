<?php
/**
 * Import/Export Service
 * จัดการการนำเข้าและส่งออกข้อมูล
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/CompanyContext.php';

class ImportExportService {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * ทดสอบการนำเข้าข้อมูลจาก CSV (Dry Run)
     * ไม่บันทึกลงฐานข้อมูล แต่แสดงผลการประมวลผล
     */
    public function testImportFromCSV($filePath) {
        $results = [
            'total_rows' => 0,
            'processed_rows' => 0,
            'error_rows' => 0,
            'new_customers' => 0,
            'existing_customers' => 0,
            'orders_to_create' => 0,
            'warnings' => [],
            'errors' => [],
            'sample_data' => []
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
        $columnMap = $this->getSalesColumnMap();
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
        $customerCounts = [];
        $processedCustomers = [];

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $results['total_rows']++;

            try {
                $salesData = [];
                foreach ($mappedHeaders as $index => $column) {
                    if ($column && isset($data[$index])) {
                        $value = trim($data[$index]);
                        // Ensure proper UTF-8 encoding
                        if (!mb_check_encoding($value, 'UTF-8')) {
                            $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                        }
                        $salesData[$column] = $value;
                    }
                }

                // Validate required fields
                if (empty($salesData['customer_name']) || empty($salesData['phone'])) {
                    $results['errors'][] = "แถวที่ {$rowNumber}: ชื่อลูกค้าและเบอร์โทรศัพท์เป็นข้อมูลที่จำเป็น";
                    $results['error_rows']++;
                    continue;
                }

                // ตรวจสอบว่าลูกค้ามีอยู่แล้วหรือไม่
                $phone = $salesData['phone'];
                // Check duplicates within the same company only
                $companyId = CompanyContext::getCompanyId($this->db);
                $existingCustomer = $this->db->fetchOne(
                    "SELECT customer_id FROM customers WHERE phone = ? AND company_id = ? LIMIT 1",
                    [$phone, $companyId]
                );

                if ($existingCustomer) {
                    $results['existing_customers']++;
                    $customerKey = 'existing_' . $existingCustomer['id'];
                } else {
                    $results['new_customers']++;
                    $customerKey = 'new_' . $phone;
                }

                // นับจำนวนคำสั่งซื้อที่จะสร้าง
                if (!isset($customerCounts[$customerKey])) {
                    $customerCounts[$customerKey] = 0;
                }
                $customerCounts[$customerKey]++;
                $results['orders_to_create']++;

                // คำนวณยอดเงิน
                $quantity = floatval($salesData['quantity'] ?? 0);
                $unitPrice = floatval($salesData['unit_price'] ?? 0);
                $totalAmountFromCSV = floatval($salesData['total_amount'] ?? 0);

                $totalAmount = 0;
                $calculatedTotal = 0;

                // ให้ความสำคัญกับคอลัมน์ 'ยอดรวม' ใน CSV ก่อน
                if ($totalAmountFromCSV > 0) {
                    $totalAmount = $totalAmountFromCSV;
                    // ถ้ามีราคาต่อชิ้นและจำนวน ให้ตรวจสอบความถูกต้อง
                    if ($unitPrice > 0 && $quantity > 0) {
                        $calculatedTotal = $quantity * $unitPrice;
                        // ถ้าคำนวณไม่ตรงกับยอดรวมใน CSV ให้ใช้ยอดรวมใน CSV
                        if (abs($calculatedTotal - $totalAmount) > 0.01) {
                            $results['warnings'][] = "แถวที่ {$rowNumber}: ยอดคำนวณ ({$calculatedTotal}) ไม่ตรงกับยอดใน CSV ({$totalAmount})";
                        }
                    }
                    // คำนวณราคาต่อชิ้นย้อนกลับ (สำหรับแสดงใน order_items)
                    $unitPrice = $quantity > 0 ? $totalAmount / $quantity : 0;
                }
                // ถ้าไม่มียอดรวมใน CSV แต่มีราคาต่อชิ้นและจำนวน
                elseif ($unitPrice > 0 && $quantity > 0) {
                    $totalAmount = $quantity * $unitPrice;
                    $calculatedTotal = $totalAmount;
                }
                // ถ้าไม่มีทั้งคู่ ให้ใช้ค่าเริ่มต้น
                else {
                    $totalAmount = 0;
                    $unitPrice = 0;
                    $results['warnings'][] = "แถวที่ {$rowNumber}: ไม่มียอดเงินหรือข้อมูลไม่ครบ";
                }

                // เพิ่มข้อมูลตัวอย่าง (แสดงแค่ 10 แถวแรก)
                if (count($results['sample_data']) < 10) {
                    $sampleRow = [
                        'customer_name' => $salesData['customer_name'],
                        'phone' => $salesData['phone'],
                        'product_name' => $salesData['product_name'] ?? '',
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_amount_from_csv' => $totalAmountFromCSV,
                        'calculated_total' => $calculatedTotal,
                        'status' => $totalAmount > 0 ? 'success' : 'warning'
                    ];

                    if (abs($calculatedTotal - $totalAmountFromCSV) > 0.01 && $totalAmountFromCSV > 0 && $calculatedTotal > 0) {
                        $sampleRow['status'] = 'warning';
                    }

                    $results['sample_data'][] = $sampleRow;
                }

                $results['processed_rows']++;

            } catch (Exception $e) {
                $results['errors'][] = "แถวที่ {$rowNumber}: " . $e->getMessage();
                $results['error_rows']++;
            }
        }

        fclose($handle);

        // ตรวจสอบลูกค้าที่มีหลายคำสั่งซื้อ
        foreach ($customerCounts as $customerKey => $count) {
            if ($count > 1) {
                $results['warnings'][] = "ลูกค้า {$customerKey} จะมีคำสั่งซื้อ {$count} รายการ";
            }
        }

        return $results;
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

        error_log("CSV Headers: " . json_encode($headers));
        error_log("Available column mappings: " . json_encode(array_keys($columnMap)));

        foreach ($headers as $header) {
            $header = trim($header);
            // Ensure proper UTF-8 encoding for header
            if (!mb_check_encoding($header, 'UTF-8')) {
                $header = mb_convert_encoding($header, 'UTF-8', 'auto');
            }
            if (isset($columnMap[$header])) {
                $mappedHeaders[] = $columnMap[$header];
                error_log("Mapped header '$header' to '" . $columnMap[$header] . "'");
            } else {
                $mappedHeaders[] = null;
                error_log("No mapping found for header '$header'");
            }
        }
        
        error_log("Final mapped headers: " . json_encode($mappedHeaders));

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

                // Debug: log mapped customer data
                error_log("Row {$rowNumber} - Raw CSV data: " . json_encode($data));
                error_log("Row {$rowNumber} - Mapped customer data: " . json_encode($customerData));
                
                // Additional debugging: check if we have any mapped data at all
                if (empty($customerData)) {
                    $results['errors'][] = "แถวที่ {$rowNumber}: ไม่สามารถแมปข้อมูลได้ ตรวจสอบ header ของไฟล์ CSV";
                    continue;
                }
                
                // Validate required fields
                if (empty($customerData['first_name']) || empty($customerData['phone'])) {
                    $results['errors'][] = "แถวที่ {$rowNumber}: ชื่อและเบอร์โทรศัพท์เป็นข้อมูลที่จำเป็น (ชื่อ: '" . ($customerData['first_name'] ?? '') . "', โทร: '" . ($customerData['phone'] ?? '') . "')";
                    continue;
                }
                
                // Normalize phone to 9 digits (drop leading 0)
                $rawPhone = preg_replace('/[^0-9]/', '', $customerData['phone']);
                if (strlen($rawPhone) === 10 && substr($rawPhone,0,1) === '0') {
                    $rawPhone = substr($rawPhone,1);
                }
                if (strlen($rawPhone) !== 9) {
                    $results['errors'][] = "แถวที่ {$rowNumber}: รูปแบบเบอร์โทรไม่ถูกต้อง (" . $customerData['phone'] . ") ต้องเป็นมาตรฐาน 9 หลัก (ไม่รวม 0 หน้า)";
                    continue;
                }
                $customerData['phone'] = $rawPhone;

                // Check for duplicate customer within the same company ONLY
                $companyId = CompanyContext::getCompanyId($this->db);
                
                if ($companyId) {
                    $existingCustomer = $this->db->fetchOne(
                        "SELECT customer_id FROM customers WHERE (phone = ? OR phone = CONCAT('0', ?)) AND company_id = ? LIMIT 1",
                        [$rawPhone, $rawPhone, $companyId]
                    );
                } else {
                    // No company_id means we can't determine which company to import to
                    $results['errors'][] = "แถวที่ {$rowNumber}: ไม่สามารถระบุบริษัทได้ - กรุณาตรวจสอบการเข้าสู่ระบบ";
                    continue;
                }

                if ($existingCustomer) {
                    // Skip duplicate customer within the same company
                    $results['errors'][] = "แถวที่ {$rowNumber}: ลูกค้าเบอร์ " . $rawPhone . " มีอยู่ในบริษัทนี้แล้ว (ข้าม)";
                    continue;
                }

                // Set default values
                $customerData['created_at'] = date('Y-m-d H:i:s');
                $customerData['updated_at'] = date('Y-m-d H:i:s');
                $customerData['customer_status'] = $customerData['customer_status'] ?? 'new';
                $customerData['temperature_status'] = $customerData['temperature_status'] ?? 'cold';
                $customerData['customer_grade'] = $customerData['customer_grade'] ?? 'C';
                // Handle assigned_to field (convert name to user_id if needed)
                $assignedTo = null;
                if (!empty($customerData['assigned_to'])) {
                    // Check if it's a user ID or name
                    if (is_numeric($customerData['assigned_to'])) {
                        $assignedTo = (int)$customerData['assigned_to'];
                    } else {
                        // Try to find user by username or full_name
                        $userSql = "SELECT user_id FROM users WHERE username LIKE ? OR full_name LIKE ? LIMIT 1";
                        $searchTerm = '%' . $customerData['assigned_to'] . '%';
                        $userResult = $this->db->fetchOne($userSql, [$searchTerm, $searchTerm]);
                        if ($userResult) {
                            $assignedTo = (int)$userResult['user_id'];
                        }
                    }
                }

                // Set basket_type based on whether a follower is assigned
                if ($assignedTo) {
                    $customerData['basket_type'] = 'assigned'; // มีผู้ติดตามแล้ว
                } else {
                    $customerData['basket_type'] = 'waiting'; // ไม่มีผู้ติดตาม → ตะกร้ารอแจก
                    $customerData['assigned_at'] = null;
                }

                $customerData['is_active'] = 1;

                // Determine company source from current user (company_id already retrieved above)
                $companySource = $this->getCurrentCompanySource();
                
                // Generate customer_code from phone number
                $customerCode = $this->generateCustomerCode($customerData['phone']);
                
                error_log("Import Customer - Company ID: " . ($companyId ?? 'NULL') . ", Source: " . ($companySource ?? 'NULL') . ", Customer Code: " . $customerCode . ", Assigned To: " . ($assignedTo ?? 'NULL'));

                // Insert customer with company source, company_id, and customer_code
                $sql = "INSERT INTO customers (first_name, last_name, phone, email, address, district, province, postal_code, source, customer_status, temperature_status, customer_grade, basket_type, assigned_to, company_id, customer_code, is_active, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $insertData = [
                    $customerData['first_name'],
                    $customerData['last_name'] ?? '',
                    $customerData['phone'],
                    $customerData['email'] ?? '',
                    $customerData['address'] ?? '',
                    $customerData['district'] ?? '',
                    $customerData['province'] ?? '',
                    $customerData['postal_code'] ?? '',
                    $companySource,
                    $customerData['customer_status'],
                    $customerData['temperature_status'],
                    $customerData['customer_grade'],
                    $customerData['basket_type'],
                    $assignedTo,
                    $companyId,
                    $customerCode,
                    $customerData['is_active'],
                    $customerData['created_at'],
                    $customerData['updated_at']
                ];
                
                error_log("Insert data: " . json_encode($insertData));
                
                $stmt = $this->db->query($sql, $insertData);
                
                if ($stmt) {
                    $results['success']++;
                    error_log("Customer inserted successfully for row {$rowNumber}");
                } else {
                    error_log("Failed to insert customer for row {$rowNumber}");
                    $results['errors'][] = "แถวที่ {$rowNumber}: ไม่สามารถบันทึกลูกค้าได้";
                }

            } catch (Exception $e) {
                $results['errors'][] = "แถวที่ {$rowNumber}: " . $e->getMessage();
            }
        }


        fclose($handle);
        return $results;
    }

    /**
     * อ่านบริษัทของผู้ใช้งานปัจจุบันจาก session และคืนค่าเป็น source string
     * ตัวอย่าง: 'Prima' หรือ 'Prionic'
     */
    private function getCurrentCompanySource() {
        try {
            if (!isset($_SESSION)) { @session_start(); }
            // Super admin override from controller
            if (!empty($_SESSION['override_company_source'])) {
                $ov = strtoupper(trim($_SESSION['override_company_source']));
                if (stripos($ov, 'PRIONIC') !== false || $ov === 'PRIONIC') return 'PRIONIC';
                if (stripos($ov, 'PRIMA') !== false || $ov === 'PRIMA') return 'PRIMA';
                // Map by company_code if provided
                if ($ov === 'A02') return 'PRIONIC';
                if ($ov === 'PRIMA49') return 'PRIMA';
                return $ov;
            }
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) return null;
            $row = $this->db->fetchOne("SELECT c.company_name, c.company_code FROM users u LEFT JOIN companies c ON u.company_id = c.company_id WHERE u.user_id = ?", [$userId]);
            $name = trim(($row['company_code'] ?? '') !== '' ? $row['company_code'] : ($row['company_name'] ?? ''));
            if ($name === '') return null;
            // มาตรฐานเป็นรหัสบริษัทสั้น เพื่อจับกลุ่มและเปรียบเทียบง่าย
            if (stripos($name, 'prionic') !== false) return 'PRIONIC';
            if (stripos($name, 'prima') !== false) return 'PRIMA';
            return strtoupper($name); // fallback: upper-case
        } catch (Exception $e) {
            return null;
        }
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
                       u.full_name as created_by_name
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
    public function importSalesFromCSV($filePath, $updateCustomerTimeExpiry = false) {
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

            // Fallback: if no explicit order_date mapping, try to auto-detect
            if (!in_array('order_date', $mappedHeaders, true)) {
                foreach ($headers as $idx => $h) {
                    $hn = mb_strtolower(trim((string)$h), 'UTF-8');
                    if ($hn === '') continue;
                    if (
                        strpos($hn, 'order date') !== false ||
                        strpos($hn, 'date') !== false ||
                        strpos($hn, 'วันที่') !== false ||
                        strpos($hn, 'วันสั่ง') !== false ||
                        strpos($hn, 'วันที่สั่งซื้อ') !== false ||
                        strpos($hn, 'วันที่ทำรายการ') !== false ||
                        strpos($hn, 'วันที่ออกบิล') !== false
                    ) {
                        $mappedHeaders[$idx] = 'order_date';
                        error_log("Auto-mapped header '{$headers[$idx]}' to 'order_date'");
                        break;
                    }
                }
            }

            error_log("Mapped headers: " . json_encode($mappedHeaders));

            // Prepare optional customer_status override from form (global for this import)
            $allowedStatuses = ['new','existing','existing_3m','followup','call_followup','daily_distribution'];
            $statusOverride = null;
            if (isset($_POST['customer_status'])) {
                $candidate = trim((string)$_POST['customer_status']);
                if ($candidate !== '' && in_array($candidate, $allowedStatuses, true)) {
                    $statusOverride = $candidate;
                }
            }

            // Grouping: combine rows without order_number by (order_date + phone) into one order
            $rowNumber = 1; // Header row
            $groups = [];
            $groupCustomerId = [];
            $groupMeta = [];

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

                    // Normalize phone to 9 digits (drop leading 0)
                    $phone9 = preg_replace('/[^0-9]/', '', $salesData['phone']);
                    if (strlen($phone9) === 10 && substr($phone9,0,1) === '0') { $phone9 = substr($phone9,1); }
                    if (strlen($phone9) !== 9) {
                        $results['errors'][] = "แถวที่ {$rowNumber}: รูปแบบเบอร์โทรไม่ถูกต้อง (" . $salesData['phone'] . ") ต้องเป็นมาตรฐาน 9 หลัก (ไม่รวม 0 หน้า)";
                        continue;
                    }
                    $salesData['phone'] = $phone9;

                    // Resolve or create customer by phone + company_id ONLY
                    $companyId = CompanyContext::getCompanyId($this->db);
                    
                    if ($companyId) {
                        $existingCustomer = $this->db->fetchOne(
                            "SELECT customer_id FROM customers WHERE (phone = ? OR phone = CONCAT('0', ?)) AND company_id = ? LIMIT 1",
                            [$salesData['phone'], $salesData['phone'], $companyId]
                        );
                    } else {
                        // No company_id means we can't determine which company to import to
                        $results['errors'][] = "แถวที่ {$rowNumber}: ไม่สามารถระบุบริษัทได้ - กรุณาตรวจสอบการเข้าสู่ระบบ";
                        continue;
                    }

                    if ($existingCustomer) {
                        $customerId = (int)$existingCustomer['customer_id'];
                        $results['customers_updated']++;
                    } else {
                        // Attach optional override to pass into creator
                        if ($statusOverride) { $salesData['customer_status_override'] = $statusOverride; }
                        $customerId = $this->createNewCustomer($salesData);
                        if ($customerId) {
                            $results['customers_created']++;
                        } else {
                            $results['errors'][] = "แถวที่ {$rowNumber}: ไม่สามารถสร้างลูกค้าได้";
                            continue;
                        }
                    }

                    // Build grouping key (ensure one order per customer per number)
                    $providedOrderNumber = trim($salesData['order_number'] ?? '');
                    if ($providedOrderNumber !== '') {
                        $groupKey = 'NUM:' . $providedOrderNumber . ':P:' . $salesData['phone'];
                    } else {
                        // Use parsed date; fallback handled inside parseDate
                        $dateStr = $this->parseDate($salesData['order_date'] ?? '');
                        $dateOnly = substr($dateStr, 0, 10);
                        $phoneDigits = preg_replace('/[^0-9]/', '', $salesData['phone']);
                        $groupKey = 'DP:' . $dateOnly . ':' . $phoneDigits;
                    }

                    if (!isset($groups[$groupKey])) {
                        $groups[$groupKey] = [];
                        $groupCustomerId[$groupKey] = $customerId;
                        // Store meta from first row
                        $groupMeta[$groupKey] = [
                            'order_number' => $providedOrderNumber,
                            'order_date' => $this->parseDate($salesData['order_date'] ?? ''),
                            'payment_method' => $this->mapPaymentMethod($salesData['payment_method'] ?? 'cash'),
                            'payment_status' => $this->mapPaymentStatus($salesData['payment_status'] ?? 'pending'),
                            'created_by' => $this->getUserIdFromNameOrId($salesData['created_by'] ?? ''),
                            'sales_channel' => $salesData['sales_channel'] ?? 'External',
                            'notes' => $salesData['notes'] ?? '',
                            'assigned_to_provided' => isset($salesData['assigned_to']) && trim((string)$salesData['assigned_to']) !== '',
                            'update_customer_time_expiry' => $updateCustomerTimeExpiry
                        ];
                    }

                    $groups[$groupKey][] = $salesData;

                } catch (Exception $e) {
                    error_log("Error staging row {$rowNumber}: " . $e->getMessage());
                    $results['errors'][] = "แถวที่ {$rowNumber}: " . $e->getMessage();
                }
            }

            // Create orders per group and insert items
            foreach ($groups as $groupKey => $rows) {
                try {
                    $customerId = $groupCustomerId[$groupKey];
                    $meta = $groupMeta[$groupKey];

                    // Determine order number (tolerant: auto-unique if duplicate)
                    $orderNumber = trim($meta['order_number'] ?? '');
                    if ($orderNumber !== '') {
                        $orderNumber = mb_substr($orderNumber, 0, 50);
                        try {
                            $try = $orderNumber;
                            $attempt = 0;
                            while ($this->db->fetchOne("SELECT order_id FROM orders WHERE order_number = ? LIMIT 1", [$try])) {
                                $attempt++;
                                $suffix = '-' . ($attempt < 5 ? rand(1000, 9999) : $attempt);
                                $try = mb_substr($orderNumber . $suffix, 0, 50);
                                if ($attempt > 20) { $try = 'EXT-' . date('YmdHis') . '-' . rand(1000,9999); break; }
                            }
                            $orderNumber = $try;
                        } catch (Exception $e) { /* ignore */ }
                    } else {
                        $orderNumber = 'EXT-' . date('YmdHis') . '-' . rand(1000, 9999);
                    }

                    // Calculate total for order and prepare per-item details
                    $orderTotal = 0.0;
                    $items = [];
                    foreach ($rows as $r) {
                        $qty = $this->parseNumber($r['quantity'] ?? 1);
                        $unit = $this->parseNumber($r['unit_price'] ?? 0);
                        $csvTotal = $this->parseNumber($r['total_amount'] ?? 0);

                        if ($csvTotal > 0) {
                            $lineTotal = $csvTotal;
                            $unit = ($qty > 0) ? ($lineTotal / $qty) : 0;
                        } elseif ($unit > 0 && $qty > 0) {
                            $lineTotal = $qty * $unit;
                        } else {
                            // default fallback
                            $qty = 1;
                            $unit = $csvTotal > 0 ? $csvTotal : 0;
                            $lineTotal = $unit;
                        }

                        $orderTotal += $lineTotal;
                        $items[] = [
                            'product_code' => $r['product_code'] ?? '',
                            'product_name' => $r['product_name'] ?? '',
                            'quantity' => $qty,
                            'unit_price' => $unit,
                            'total_price' => $lineTotal
                        ];
                    }

                    // Assignment policy based on current basket/owner and seller timing
                    $custNow = $this->db->fetchOne("SELECT assigned_to, basket_type FROM customers WHERE customer_id = ?", [$customerId]);
                    $currentOwner = (int)($custNow['assigned_to'] ?? 0);
                    $currentBasket = (string)($custNow['basket_type'] ?? '');
                    $sellerId = (int)($meta['created_by'] ?? 0);

                    if ($sellerId > 0 && ($groupMeta[$groupKey]['assigned_to_provided'] ?? false)) {
                        // เฉพาะกรณีมีคอลัมน์ผู้ติดตาม/assigned_to ในไฟล์จึงจะตั้งตามผู้ขาย
                        if (empty($currentOwner) || in_array($currentBasket, ['waiting','distribution'])) {
                            // ถ้าไม่มีผู้ติดตามอยู่แล้ว (ตะกร้ารอ/แจก) → ตั้งตามผู้ขาย
                            if ($sellerId !== (int)$currentOwner) {
                                $this->db->execute("UPDATE customers SET assigned_to = ?, basket_type = 'assigned', assigned_at = NOW(), updated_at = NOW() WHERE customer_id = ?", [$sellerId, $customerId]);
                                $currentOwner = $sellerId;
                                $currentBasket = 'assigned';
                                error_log("Sales Import - Assigned customer {$customerId} to seller {$sellerId}");
                            }
                        } else {
                            // ถ้ามีผู้ติดตามอยู่แล้ว → ตรวจสอบว่าผู้ขายเป็นผู้ดูแลเดิมหรือไม่
                            if ($currentOwner && $sellerId !== (int)$currentOwner) {
                                $results['errors'][] = "แจ้งเตือน: ผู้ขาย (ID {$sellerId}) สร้างออเดอร์ให้ลูกค้าที่ดูแลโดยผู้ใช้ ID {$currentOwner} - เก็บสิทธิการดูแลเดิมไว้";
                                error_log("Sales Import - Preserving existing assigned_to for customer {$customerId}: {$currentOwner} (seller: {$sellerId})");
                            } else {
                                // ผู้ขายเป็นผู้ดูแลเดิม → เก็บสิทธิการดูแลไว้
                                error_log("Sales Import - Preserving existing assigned_to for customer {$customerId}: {$currentOwner}");
                            }
                        }
                    } else if (!($groupMeta[$groupKey]['assigned_to_provided'] ?? false)) {
                        // ไม่มีผู้ติดตามจากไฟล์ → ตรวจสอบว่าลูกค้ามีผู้ติดตามอยู่แล้วหรือไม่
                        if (empty($currentOwner) || in_array($currentBasket, ['waiting','distribution'])) {
                            // ถ้าไม่มีผู้ติดตามอยู่แล้ว → เข้าตะกร้าพร้อมแจก (distribution)
                            $this->db->execute("UPDATE customers SET assigned_to = NULL, basket_type = 'distribution', assigned_at = NULL, updated_at = NOW() WHERE customer_id = ?", [$customerId]);
                            $currentOwner = 0;
                            $currentBasket = 'distribution';
                        } else {
                            // ถ้ามีผู้ติดตามอยู่แล้ว → ตรวจสอบว่าผู้ขายเป็นผู้ดูแลเดิมหรือไม่
                            if ($currentOwner && $sellerId !== (int)$currentOwner) {
                                // ผู้ขายไม่ใช่ผู้ดูแลเดิม → แจ้งเตือนและเก็บสิทธิการดูแลเดิมไว้
                                $results['errors'][] = "แจ้งเตือน: ผู้ขาย (ID {$sellerId}) สร้างออเดอร์ให้ลูกค้าที่ดูแลโดยผู้ใช้ ID {$currentOwner} - เก็บสิทธิการดูแลเดิมไว้";
                                error_log("Sales Import - Preserving existing assigned_to for customer {$customerId}: {$currentOwner} (seller: {$sellerId})");
                            } else {
                                // ผู้ขายเป็นผู้ดูแลเดิมหรือไม่มีผู้ขาย → เก็บสิทธิการดูแลไว้
                                error_log("Sales Import - Preserving existing assigned_to for customer {$customerId}: {$currentOwner}");
                            }
                        }
                    }

                    // Insert order
                    $custRow = $this->db->fetchOne("SELECT company_id FROM customers WHERE customer_id = ?", [$customerId]);
                    $orderCompanyId = $custRow['company_id'] ?? null;
                    $orderSql = "INSERT INTO orders (customer_id, company_id, order_number, order_date, total_amount, net_amount, payment_method, payment_status, delivery_status, notes, created_by, created_at, updated_at)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $orderParams = [
                        $customerId,
                        $orderCompanyId,
                        $orderNumber,
                        ($meta['order_date'] ?: date('Y-m-d')),
                        $orderTotal,
                        $orderTotal,
                        $meta['payment_method'],
                        $meta['payment_status'],
                        'delivered',
                        'นำเข้าจาก ' . ($meta['sales_channel'] ?? 'External') . ' - ' . ($meta['notes'] ?? ''),
                        $meta['created_by'],
                        date('Y-m-d H:i:s'),
                        date('Y-m-d H:i:s')
                    ];
                    $this->db->query($orderSql, $orderParams);
                    $orderId = (int)$this->db->lastInsertId();
                    
                    // อัปเดต customer_status และ customer_time_expiry หลังจากสร้างออเดอร์
                    if ($orderId > 0 && in_array($meta['payment_status'], ['paid', 'partial'])) {
                        try {
                            $updateCustomerTimeExpiry = $meta['update_customer_time_expiry'] ?? false;
                            
                            if ($updateCustomerTimeExpiry) {
                                // ติ๊กใช่ - อัปเดตวันคงเหลือเป็น 90 วัน (สำหรับข้อมูลใหม่)
                                $this->db->execute(
                                    "UPDATE customers 
                                     SET customer_status = 'existing_3m', 
                                         customer_time_expiry = DATE_ADD(NOW(), INTERVAL 90 DAY),
                                         updated_at = NOW()
                                      WHERE customer_id = ? 
                                        AND customer_status NOT IN ('followup','call_followup','daily_distribution')",
                                     [$customerId]
                                );
                                error_log("Sales Import - Updated customer {$customerId} status to existing_3m and time_expiry to 90 days after order {$orderId}");
                            } else {
                                // ไม่ติ๊ก - เพิ่มประวัติและอัปเดตสถานะเป็น existing_3m (สำหรับข้อมูลเก่า)
                                // ตรวจสอบว่าอยู่ในกรอบ 90 วันหรือไม่
                                $customer = $this->db->fetchOne(
                                    "SELECT customer_time_expiry FROM customers WHERE customer_id = ?",
                                    [$customerId]
                                );
                                
                                $isWithin90Days = true; // default
                                if ($customer && $customer['customer_time_expiry']) {
                                    $isWithin90Days = strtotime($customer['customer_time_expiry']) > time();
                                }
                                
                                if ($isWithin90Days) {
                                    $this->db->execute(
                                         "UPDATE customers 
                                         SET customer_status = 'existing_3m', updated_at = NOW()
                                         WHERE customer_id = ? 
                                           AND customer_status NOT IN ('followup','call_followup','daily_distribution')",
                                         [$customerId]
                                     );
                                    error_log("Sales Import - Updated customer {$customerId} status to existing_3m (historical data) after order {$orderId}");
                                } else {
                                    error_log("Sales Import - Customer {$customerId} outside 90-day window, skipping status update");
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Sales Import - Error updating customer status to existing_3m: " . $e->getMessage());
                        }
                    }

                    // Insert order items if table exists
                    if ($this->tableExists('order_items')) {
                        foreach ($items as $it) {
                            $productId = $this->resolveProductId($it['product_code'], $it['product_name']);
                            if (!$productId) {
                                $productId = $this->ensureFallbackProduct();
                            }
                            // Respect NOT NULL constraint on product_id
                            $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)";
                            $this->db->query($itemSql, [
                                $orderId,
                                $productId,
                                $it['quantity'],
                                $it['unit_price'],
                                $it['total_price']
                            ]);
                        }
                    }

                    // Update 3-month existing status if recent paid/partial order exists
                    try {
                        $this->db->execute(
                            "UPDATE customers c 
                             SET c.customer_status = 'existing_3m'
                              WHERE c.customer_id = ?
                                AND c.assigned_to IS NOT NULL
                                AND c.customer_status NOT IN ('followup','call_followup','daily_distribution')
                                AND EXISTS (
                                    SELECT 1 FROM orders o
                                    WHERE o.customer_id = c.customer_id
                                      AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                                      AND o.payment_status IN ('paid','partial')
                                      AND o.created_by = c.assigned_to
                                )",
                            [$customerId]
                        );
                    } catch (Exception $e) {
                        error_log('Status promote to existing_3m (grouped) failed for ' . $customerId . ': ' . $e->getMessage());
                    }

                    // Demote incorrect existing_3m if latest order/owner rule is not satisfied
                    try {
                        $this->db->execute(
                            "UPDATE customers c
                             LEFT JOIN (
                               SELECT o.customer_id, o.created_by, o.order_date
                               FROM orders o
                               WHERE o.customer_id = ? AND o.payment_status IN ('paid','partial')
                               ORDER BY o.order_date DESC, o.order_id DESC
                               LIMIT 1
                             ) last_paid ON last_paid.customer_id = c.customer_id
                             SET c.customer_status = 'existing'
                             WHERE c.customer_id = ?
                               AND c.customer_status = 'existing_3m'
                               AND (
                                   c.assigned_to IS NULL
                                   OR last_paid.order_date IS NULL
                                   OR last_paid.order_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                                   OR last_paid.created_by <> c.assigned_to
                               )",
                            [$customerId, $customerId]
                        );
                    } catch (Exception $e) { /* ignore */ }

                    // Update customer totals and grade
                    $this->updateCustomerTotalPurchase($customerId);
                    try {
                        require_once __DIR__ . '/CustomerService.php';
                        $customerService = new CustomerService();
                        $customerService->updateCustomerGrade($customerId);
                    } catch (Exception $e) {
                        error_log("Failed to update customer grade for {$customerId}: " . $e->getMessage());
                    }

                    $results['orders_created']++;
                    $results['success']++;

                } catch (Exception $e) {
                    error_log('Error creating grouped order: ' . $e->getMessage());
                    $results['errors'][] = 'ไม่สามารถสร้างคำสั่งซื้อแบบกลุ่มได้: ' . $e->getMessage();
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
    public function importCustomersOnlyFromCSV($filePath, $customerStatus = 'new') {
        $results = [
            'success' => 0,
            'errors' => [],
            'total' => 0,
            'customers_created' => 0,
            'customers_skipped' => 0,
            'customers_assigned' => 0
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

                // Normalize phone to 9 digits (drop leading 0)
                $phone9 = preg_replace('/[^0-9]/', '', $customerData['phone']);
                if (strlen($phone9) === 10 && substr($phone9,0,1) === '0') { $phone9 = substr($phone9,1); }
                if (strlen($phone9) !== 9) {
                    $results['errors'][] = "แถวที่ {$rowNumber}: รูปแบบเบอร์โทรไม่ถูกต้อง (" . $customerData['phone'] . ") ต้องเป็นมาตรฐาน 9 หลัก (ไม่รวม 0 หน้า)";
                    continue;
                }
                $customerData['phone'] = $phone9;

                // Check if customer exists within the same company ONLY (phone + company_id)
                $companyId = CompanyContext::getCompanyId($this->db);
                if ($companyId) {
                    $existingCustomer = $this->db->fetchOne(
                        "SELECT customer_id, basket_type, assigned_to FROM customers WHERE (phone = ? OR phone = CONCAT('0', ?)) AND company_id = ? LIMIT 1",
                        [$phone9, $phone9, $companyId]
                    );
                } else {
                    // No company_id means we can't determine which company to import to
                    $results['errors'][] = "แถวที่ {$rowNumber}: ไม่สามารถระบุบริษัทได้ - กรุณาตรวจสอบการเข้าสู่ระบบ";
                    continue;
                }

                if ($existingCustomer) {
                    // If existing and currently not under anyone (waiting/distribution) AND CSV provides an assignee
                    $assignedId = $this->resolveAssignedUserId($customerData['assigned_to'] ?? null, $companyId);
                    $isUnassigned = empty($existingCustomer['assigned_to']) || ($existingCustomer['basket_type'] !== 'assigned');

                    if ($assignedId && $isUnassigned) {
                        // Assign existing customer to the provided user
                        $updateFields = [
                            'basket_type' => 'assigned',
                            'assigned_to' => $assignedId,
                            'assigned_at' => date('Y-m-d H:i:s'),
                            'customer_time_base' => date('Y-m-d H:i:s'),
                            'customer_time_expiry' => date('Y-m-d H:i:s', strtotime('+30 days')),
                            'recall_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        $resolvedStatus = $this->mapCustomersOnlyStatus($customerData['customer_status'] ?? null, $customerStatus);
                        if (!empty($resolvedStatus)) { $updateFields['customer_status'] = $resolvedStatus; }
                        $this->db->update('customers', $updateFields, 'customer_id = ?', [$existingCustomer['customer_id']]);

                        $results['customers_assigned']++;
                        $results['success']++;
                        continue;
                    }

                    // Otherwise skip to avoid duplicates
                    $results['customers_skipped']++;
                    $results['errors'][] = "แถวที่ {$rowNumber}: ลูกค้าเบอร์ " . $phone9 . " มีอยู่ในบริษัทนี้แล้ว (ข้าม)";
                    continue;
                }

                // Create new customer in distribution basket
                $customerId = $this->createNewCustomerOnly($customerData, $customerStatus);
                
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
            'รหัสสินค้า' => 'product_code',
            'Product Code' => 'product_code',
            'ชื่อสินค้า' => 'product_name',
            'จำนวน' => 'quantity',
            'ราคาต่อชิ้น' => 'unit_price',
            'ราคาต่อหน่วย' => 'unit_price',
            'ราคา/หน่วย' => 'unit_price',
            'Unit Price' => 'unit_price',
            'ยอดรวม' => 'total_amount',
            // Order number / PO number mapping
            'เลขที่คำสั่งซื้อ' => 'order_number',
            'เลขที่คำสั่งซ์้อ' => 'order_number', // common typo
            'เลขที่ออเดอร์' => 'order_number',
            'เลขที่ใบสั่งซื้อ' => 'order_number',
            'หมายเลขคำสั่งซื้อ' => 'order_number',
            'Order Number' => 'order_number',
            'PO Number' => 'order_number',
            'Purchase Order' => 'order_number',
            'เลข PO' => 'order_number',
            'วันที่สั่งซื้อ' => 'order_date',
            'ช่องทางการขาย' => 'sales_channel',
            'ผู้ติดตาม' => 'assigned_to',
            'Follower' => 'assigned_to',
            'Tracker' => 'assigned_to',
            'ผู้ขาย' => 'created_by',
            'Seller' => 'created_by',
            'Sales Person' => 'created_by',
            'วิธีการชำระเงิน' => 'payment_method',
            'Payment Method' => 'payment_method',
            'สถานะการชำระเงิน' => 'payment_status',
            'Payment Status' => 'payment_status',
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
            'รหัสสินค้า' => 'product_code',
            'Product Code' => 'product_code',
            'ผู้ติดตาม' => 'assigned_to',
            'Follower' => 'assigned_to',
            'Tracker' => 'assigned_to',
            'หมายเหตุ' => 'notes',
            'วันคงเหลือ' => 'remaining_days',
            'Remaining Days' => 'remaining_days',
            'Days Left' => 'remaining_days',
            'สถานะลูกค้า' => 'customer_status',
            'Customer Status' => 'customer_status',
            'Status' => 'customer_status',
            'วันที่มอบหมาย' => 'assigned_at',
            'Assigned At' => 'assigned_at',
            'Assignment Date' => 'assigned_at',
            'วันที่แจก' => 'assigned_at'
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
            'รหัสสินค้า' => 'product_code',
            'Product Code' => 'product_code',
            'ผู้ติดตาม' => 'assigned_to',
            'Follower' => 'assigned_to',
            'Tracker' => 'assigned_to',
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
                    COUNT(CASE WHEN customer_status IN ('existing', 'existing_3m') THEN 1 END) as existing_customers,
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
            // Handle assigned_to field (convert name to user_id if needed)
            $assignedTo = null;
            if (!empty($salesData['assigned_to'])) {
                // Check if it's a user ID or name
                if (is_numeric($salesData['assigned_to'])) {
                    $assignedTo = (int)$salesData['assigned_to'];
                } else {
                    // Try to find user by username or full_name
                    $userSql = "SELECT user_id FROM users WHERE username LIKE ? OR full_name LIKE ?";
                    $userResult = $this->db->fetchOne($userSql, [$salesData['assigned_to'], $salesData['assigned_to']]);
                    if ($userResult) {
                        $assignedTo = (int)$userResult['user_id'];
                    }
                }
            }

            // Set basket_type based on whether a follower is assigned
            $basketType = $assignedTo ? 'assigned' : 'waiting';

            // Generate customer_code from phone number
            $customerCode = $this->generateCustomerCode($salesData['phone']);

            // Determine customer_status based on assignment, allow override from import form
            $customerStatus = $assignedTo ? 'existing' : 'new';
            if (!empty($salesData['customer_status_override'])) {
                $allowedStatuses = ['new','existing','existing_3m','followup','call_followup','daily_distribution'];
                if (in_array($salesData['customer_status_override'], $allowedStatuses, true)) {
                    $customerStatus = $salesData['customer_status_override'];
                }
            }

            $customerData = [
                'first_name' => $salesData['first_name'],
                'last_name' => $salesData['last_name'] ?? '',
                'phone' => $salesData['phone'],
                'email' => $salesData['email'] ?? '',
                'address' => $salesData['address'] ?? '',
                'district' => $salesData['district'] ?? '',
                'province' => $salesData['province'] ?? '',
                'postal_code' => $salesData['postal_code'] ?? '',
                'basket_type' => $basketType, // กำหนดตามการมีผู้ติดตาม
                'temperature_status' => 'hot', // ลูกค้าใหม่
                'customer_grade' => 'D', // เกรดเริ่มต้น
                'customer_status' => $customerStatus,
                'assigned_to' => $assignedTo,
                'assigned_at' => ($assignedTo ? date('Y-m-d H:i:s') : null),
                'customer_code' => $customerCode,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Determine company_id from session/override
            $companyId = CompanyContext::getCompanyId($this->db);
            error_log("CompanyContext::getCompanyId() returned: " . ($companyId ?? 'null'));
            error_log("Session data: " . json_encode([
                'user_id' => $_SESSION['user_id'] ?? 'null',
                'company_id' => $_SESSION['company_id'] ?? 'null',
                'role_name' => $_SESSION['role_name'] ?? 'null',
                'override_company_id' => $_SESSION['override_company_id'] ?? 'null'
            ]));

            $sql = "INSERT INTO customers (first_name, last_name, phone, email, address, district, province, postal_code, company_id, basket_type, temperature_status, customer_grade, customer_status, assigned_to, customer_code, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->query($sql, [
                $customerData['first_name'],
                $customerData['last_name'],
                $customerData['phone'],
                $customerData['email'],
                $customerData['address'],
                $customerData['district'],
                $customerData['province'],
                $customerData['postal_code'],
                $companyId,
                $customerData['basket_type'],
                $customerData['temperature_status'],
                $customerData['customer_grade'],
                $customerData['customer_status'],
                $customerData['assigned_to'],
                $customerData['customer_code'],
                $customerData['is_active'],
                $customerData['created_at'],
                $customerData['updated_at']
            ]);

            $newCustomerId = $this->db->lastInsertId();

            // ตั้งค่าเวลาฐานและหมดอายุตามกฎการนำเข้า
            if ($assignedTo) {
                // มีผู้ติดตาม: 90 วัน และสถานะ existing (ตั้งไว้ด้านบนแล้ว)
                try {
                    $this->db->query(
                        "UPDATE customers
                         SET customer_time_base = NOW(),
                             customer_time_expiry = DATE_ADD(NOW(), INTERVAL 90 DAY)
                         WHERE customer_id = ?",
                        [$newCustomerId]
                    );
                } catch (Exception $e) {
                    error_log("Failed to set customer time window for new customer {$newCustomerId}: " . $e->getMessage());
                }
            } else {
                // ไม่มีผู้ติดตาม: 30 วัน และสถานะ new (ตั้งไว้ด้านบนแล้ว)
                try {
                    $this->db->query(
                        "UPDATE customers
                         SET customer_time_base = NOW(),
                             customer_time_expiry = DATE_ADD(NOW(), INTERVAL 30 DAY)
                         WHERE customer_id = ?",
                        [$newCustomerId]
                    );
                } catch (Exception $e) {
                    error_log("Failed to set 30-day window for new customer {$newCustomerId}: " . $e->getMessage());
                }
            }

            return $newCustomerId;

        } catch (Exception $e) {
            error_log("Error creating new customer: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("Sales data: " . json_encode($salesData));
            return false;
        }
    }

    /**
     * สร้างลูกค้าใหม่สำหรับ Customers Only Import
     */
    private function createNewCustomerOnly($customerData, $customerStatus = 'new') {
        try {
            // Resolve assigned_to to user_id if provided
            $companyId = CompanyContext::getCompanyId($this->db);
            $assignedTo = $this->resolveAssignedUserId($customerData['assigned_to'] ?? null, $companyId);

            // Set basket_type based on whether a follower is assigned
            $basketType = $assignedTo ? 'assigned' : 'waiting';

            // Generate customer_code from phone number
            $customerCode = $this->generateCustomerCode($customerData['phone']);

            // Resolve customer status (accept Thai/English labels); fallback to method arg
            $customerStatus = $this->mapCustomersOnlyStatus($customerData['customer_status'] ?? null, $customerStatus);
            $temperatureStatus = 'hot';

            // Handle remaining days (default 30 if not specified)
            $remainingDays = 30; // Default
            if (isset($customerData['remaining_days']) && is_numeric($customerData['remaining_days'])) {
                $remainingDays = max(1, (int)$customerData['remaining_days']); // Minimum 1 day
            }

            // Handle assigned_at (วันที่มอบหมาย)
            $assignedAtDate = null;
            if (!empty($customerData['assigned_at'])) {
                try {
                    // ลองแปลงวันที่หลายรูปแบบ
                    $dateFormats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y-m-d H:i:s'];
                    foreach ($dateFormats as $format) {
                        $date = DateTime::createFromFormat($format, trim($customerData['assigned_at']));
                        if ($date !== false) {
                            $assignedAtDate = $date->format('Y-m-d H:i:s');
                            break;
                        }
                    }
                    // ถ้าแปลงไม่ได้ ให้ใช้วันที่ปัจจุบัน
                    if (!$assignedAtDate) {
                        $assignedAtDate = date('Y-m-d H:i:s');
                    }
                } catch (Exception $e) {
                    $assignedAtDate = date('Y-m-d H:i:s');
                }
            } else {
                // ถ้าไม่ได้ระบุวันที่มอบหมาย และไม่มีผู้ติดตาม ให้เว้นว่าง (เข้ารอแจก)
                $assignedAtDate = $assignedTo ? date('Y-m-d H:i:s') : null;
            }

            $data = [
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'] ?? '-',
                'phone' => $customerData['phone'],
                'email' => $customerData['email'] ?? '',
                'address' => $customerData['address'] ?? '',
                'district' => $customerData['district'] ?? '',
                'province' => $customerData['province'] ?? '',
                'postal_code' => $customerData['postal_code'] ?? '',
                'basket_type' => $basketType, // กำหนดตามการมีผู้ติดตาม
                'temperature_status' => $temperatureStatus, // ลูกค้าร้อน (สำหรับ customers-only import)
                'customer_grade' => 'D', // เกรดเริ่มต้น
                'customer_status' => $customerStatus, // สถานะตามที่เลือก/ส่งมา
                'assigned_to' => $assignedTo,
                'assigned_at' => ($assignedTo ? $assignedAtDate : null),
                'customer_code' => $customerCode,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Determine company_id from session/override
            $companyId = CompanyContext::getCompanyId($this->db);

            $sql = "INSERT INTO customers (first_name, last_name, phone, email, address, district, province, postal_code, company_id, basket_type, temperature_status, customer_grade, customer_status, assigned_to, assigned_at, customer_code, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->query($sql, [
                $data['first_name'],
                $data['last_name'],
                $data['phone'],
                $data['email'],
                $data['address'],
                $data['district'],
                $data['province'],
                $data['postal_code'],
                $companyId,
                $data['basket_type'],
                $data['temperature_status'],
                $data['customer_grade'],
                $data['customer_status'],
                $data['assigned_to'],
                $data['assigned_at'],
                $data['customer_code'],
                $data['is_active'],
                $data['created_at'],
                $data['updated_at']
            ]);

            if (!$stmt) {
                return false;
            }

            $newCustomerId = $this->db->lastInsertId();

            // ตั้งค่าเวลาฐานและหมดอายุตามวันคงเหลือที่กำหนด
            try {
                $this->db->query(
                    "UPDATE customers
                     SET customer_time_base = NOW(),
                         customer_time_expiry = DATE_ADD(NOW(), INTERVAL ? DAY)
                     WHERE customer_id = ?",
                    [$remainingDays, $newCustomerId]
                );
            } catch (Exception $e) {
                error_log("Failed to set customer time window ({$remainingDays}d) for new customer {$newCustomerId}: " . $e->getMessage());
            }

            return $newCustomerId;

        } catch (Exception $e) {
            error_log("Error creating new customer only: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Resolve an "assigned_to" input (id or name) to a valid user_id within the company
     */
    private function resolveAssignedUserId($rawAssigned, $companyId) {
        try {
            if (empty($rawAssigned) || empty($companyId)) { return null; }
            if (is_numeric($rawAssigned)) {
                $userCheck = $this->db->fetchOne("SELECT user_id FROM users WHERE user_id = ? AND company_id = ? AND is_active = 1", [$rawAssigned, $companyId]);
                return $userCheck ? (int)$rawAssigned : null;
            }
            $user = $this->db->fetchOne(
                "SELECT user_id FROM users WHERE (username = ? OR full_name = ?) AND company_id = ? AND is_active = 1",
                [$rawAssigned, $rawAssigned, $companyId]
            );
            return $user ? (int)$user['user_id'] : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Map customers-only import status input to canonical values
     * Accepts Thai labels and English keys; fallback to provided default
     */
    private function mapCustomersOnlyStatus($rawStatus, $default = 'new') {
        $map = [
            // English keys
            'new' => 'new',
            'existing' => 'existing',
            'existing_3m' => 'existing_3m',
            'existing_3_months' => 'existing_3m',
            'existing_90d' => 'existing_3m',
            'followup' => 'followup',
            'call_followup' => 'call_followup',
            'daily_distribution' => 'daily_distribution',
            // Thai labels (common variants)
            'ลูกค้าใหม่' => 'new',
            'ลูกค้าเก่า' => 'existing',
            'ติดตาม' => 'followup',
            'ติดตามการโทร' => 'call_followup',
            'ลูกค้าแจกรายวัน' => 'daily_distribution'
        ];
        if (empty($rawStatus)) { return $default; }
        $key = trim(mb_strtolower($rawStatus));
        // normalize spaces/underscore
        $key = str_replace([' ', '-'], '_', $key);
        return $map[$rawStatus] ?? $map[$key] ?? $default;
    }

    /**
     * อัปเดตประวัติการซื้อของลูกค้า
     */
    private function updateCustomerPurchaseHistory($customerId, $salesData) {
        try {
            // Deprecated by grouped import path; keep for backward compatibility when called directly
            // Map payment method from Thai to English
            $paymentMethod = $this->mapPaymentMethod($salesData['payment_method'] ?? 'cash');

            // Map payment status from Thai to English
            $paymentStatus = $this->mapPaymentStatus($salesData['payment_status'] ?? 'pending');

            // แปลง created_by จากชื่อหรือรหัสเป็น user_id
            $createdBy = $this->getUserIdFromNameOrId($salesData['created_by'] ?? '');

            // คำนวณ total_amount และ net_amount - ปรับปรุงการคำนวณ (รองรับตัวคั่นหลักพัน, สกุลเงิน, ข้อความ)
            $quantity = $this->parseNumber($salesData['quantity'] ?? 1);
            $unitPrice = $this->parseNumber($salesData['unit_price'] ?? 0);
            $totalAmountFromCSV = $this->parseNumber($salesData['total_amount'] ?? 0);

            // ให้ความสำคัญกับคอลัมน์ 'ยอดรวม' ใน CSV ก่อน
            if ($totalAmountFromCSV > 0) {
                $totalAmount = $totalAmountFromCSV;
                // ถ้ามีราคาต่อชิ้นและจำนวน ให้ตรวจสอบความถูกต้อง
                if ($unitPrice > 0 && $quantity > 0) {
                    $calculatedTotal = $quantity * $unitPrice;
                    // ถ้าคำนวณไม่ตรงกับยอดรวมใน CSV ให้ใช้ยอดรวมใน CSV
                    if (abs($calculatedTotal - $totalAmount) > 0.01) {
                        error_log("Warning: Calculated total ({$calculatedTotal}) doesn't match CSV total ({$totalAmount}) for customer {$customerId}");
                    }
                }
                // คำนวณราคาต่อชิ้นย้อนกลับ (สำหรับแสดงใน order_items)
                $unitPrice = $quantity > 0 ? $totalAmount / $quantity : 0;
            }
            // ถ้าไม่มียอดรวมใน CSV แต่มีราคาต่อชิ้นและจำนวน
            elseif ($unitPrice > 0 && $quantity > 0) {
                $totalAmount = $quantity * $unitPrice;
            }
            // ถ้าไม่มีทั้งคู่ ให้ใช้ค่าเริ่มต้น
            else {
                $totalAmount = 0;
                $unitPrice = 0;
            }

            // Resolve order number from CSV if provided, otherwise generate one
            $providedOrderNumber = trim($salesData['order_number'] ?? '');
            if ($providedOrderNumber !== '') {
                // Limit to 50 chars to fit column definition
                $orderNumber = mb_substr($providedOrderNumber, 0, 50);
                // Optional: avoid duplicates if possible
                try {
                    $exists = $this->db->fetchOne(
                        "SELECT order_id FROM orders WHERE order_number = ? LIMIT 1",
                        [$orderNumber]
                    );
                    if ($exists) {
                        $orderNumber = mb_substr($orderNumber . '-' . rand(1000, 9999), 0, 50);
                    }
                } catch (Exception $e) {
                    // ignore lookup failure and proceed
                }
            } else {
                $orderNumber = 'EXT-' . date('YmdHis') . '-' . rand(1000, 9999);
            }

            // สร้างคำสั่งซื้อ - ตรวจสอบให้ net_amount เท่ากับ total_amount
            $orderData = [
                'customer_id' => $customerId,
                'order_number' => $orderNumber,
                'order_date' => $this->parseDate($salesData['order_date'] ?? ''),
                'total_amount' => $totalAmount,
                'net_amount' => $totalAmount, // ให้ net_amount เท่ากับ total_amount เสมอ
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'delivery_status' => 'delivered',
                'notes' => 'นำเข้าจาก ' . ($salesData['sales_channel'] ?? 'External') . ' - ' . ($salesData['notes'] ?? ''),
                'created_by' => $createdBy,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Attach company_id from customer
            $custRow = $this->db->fetchOne("SELECT company_id FROM customers WHERE customer_id = ?", [$orderData['customer_id']]);
            $orderCompanyId = $custRow['company_id'] ?? null;
            $sql = "INSERT INTO orders (customer_id, company_id, order_number, order_date, total_amount, net_amount, payment_method, payment_status, delivery_status, notes, created_by, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->query($sql, [
                $orderData['customer_id'],
                $orderCompanyId,
                $orderData['order_number'],
                $orderData['order_date'],
                $orderData['total_amount'],
                $orderData['net_amount'],
                $orderData['payment_method'],
                $orderData['payment_status'],
                $orderData['delivery_status'],
                $orderData['notes'],
                $orderData['created_by'],
                $orderData['created_at'],
                $orderData['updated_at']
            ]);

            $orderId = $this->db->lastInsertId();

            // สร้างรายการสินค้า (ถ้าตาราง order_items มีอยู่)
            if ($this->tableExists('order_items')) {
                // เตรียมค่าเริ่มต้นของจำนวน/ราคา
                if (($quantity <= 0 || $unitPrice <= 0) && $totalAmount > 0) {
                    // ถ้าขาด quantity หรือ unit_price แต่มียอดรวม ให้ตั้ง quantity = 1 และ unit_price = totalAmount
                    if ($quantity <= 0) { $quantity = 1; }
                    if ($unitPrice <= 0) { $unitPrice = $totalAmount; }
                }

                // อ่านโครงสร้างตาราง order_items เพื่อเลือกคอลัมน์ที่ถูกต้อง
                $columns = [];
                try {
                    $structure = $this->db->getTableStructure('order_items');
                    foreach ($structure as $col) {
                        $columns[$col['Field']] = true;
                    }
                } catch (Exception $e) {
                    error_log('Failed to read order_items structure: ' . $e->getMessage());
                }

                $hasProductId = isset($columns['product_id']);
                $hasProductName = isset($columns['product_name']);
                $hasCreatedAt = isset($columns['created_at']);

                $productId = null;
                $resolvedProductName = trim($salesData['product_name'] ?? '');

                // หากมี products ให้พยายาม resolve product_id และ/หรือชื่อจาก product_code
                if ($this->tableExists('products')) {
                    if (!empty($salesData['product_code'])) {
                        try {
                            $product = $this->db->fetchOne(
                                "SELECT product_id, product_name FROM products WHERE product_code = ? LIMIT 1",
                                [$salesData['product_code']]
                            );
                            if ($product) {
                                $productId = (int)$product['product_id'];
                                if ($resolvedProductName === '' && !empty($product['product_name'])) {
                                    $resolvedProductName = $product['product_name'];
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Lookup product by code failed for order {$orderId}: " . $e->getMessage());
                        }
                    }
                }

                if ($resolvedProductName === '') {
                    $resolvedProductName = 'ไม่ระบุสินค้า';
                }

                if ($quantity > 0 || $totalAmount > 0) {
                    // ประกอบ SQL ตามคอลัมน์ที่มีจริง
                    $fields = ['order_id', 'quantity', 'unit_price', 'total_price'];
                    $params = [$orderId, $quantity, $unitPrice, $totalAmount];

                    if ($hasProductId) {
                        array_splice($fields, 1, 0, 'product_id');
                        array_splice($params, 1, 0, $productId);
                    } elseif ($hasProductName) {
                        array_splice($fields, 1, 0, 'product_name');
                        array_splice($params, 1, 0, $resolvedProductName);
                    }

                    if ($hasCreatedAt) {
                        $fields[] = 'created_at';
                        $params[] = date('Y-m-d H:i:s');
                    }

                    $placeholders = rtrim(str_repeat('?, ', count($fields)), ', ');
                    $sql = "INSERT INTO order_items (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
                    $this->db->query($sql, $params);
                }
            }

            // อัปเดตยอดซื้อรวมของลูกค้า
            try {
                $this->db->execute(
                    "UPDATE customers c 
                     SET c.customer_status = 'existing_3m'
                     WHERE c.customer_id = ?
                       AND c.assigned_to IS NOT NULL
                       AND c.customer_status NOT IN ('followup','call_followup')
                        AND EXISTS (
                             SELECT 1 FROM orders o
                             WHERE o.customer_id = c.customer_id
                               AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                               AND o.payment_status IN ('paid','partial')
                               AND o.created_by = c.assigned_to
                        )",
                    [$customerId]
                );
            } catch (Exception $e) {
                error_log('Status promote to existing_3m failed for ' . $customerId . ': ' . $e->getMessage());
            }

            $this->updateCustomerTotalPurchase($customerId);

            // อัปเดตเกรดลูกค้าทันทีหลังอัปเดตยอด (เพื่อให้สะท้อนผลหลัง import)
            try {
                require_once __DIR__ . '/CustomerService.php';
                $customerService = new CustomerService();
                $customerService->updateCustomerGrade($customerId);
            } catch (Exception $e) {
                error_log("Failed to update customer grade for {$customerId}: " . $e->getMessage());
            }

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
                            WHERE customer_id = ? AND payment_status IN ('paid', 'partial')
                        ),
                        updated_at = NOW()
                    WHERE customer_id = ?";

            $this->db->query($sql, [$customerId, $customerId]);

            // Log for debugging
            error_log("Updated total_purchase_amount for customer ID: " . $customerId);

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
            // Delegate to Database helper for consistency
            if (method_exists($this->db, 'tableExists')) {
                return $this->db->tableExists($tableName);
            }
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

    /**
     * Import call logs from CSV, matching customers by customer_code
     */
    public function importCallLogsFromCSV($filePath) {
        $results = ['success'=>1,'total'=>0,'inserted'=>0,'skipped'=>0,'errors'=>[]];
        try {
            if (!file_exists($filePath)) { throw new Exception('ไม่พบไฟล์อัปโหลด'); }
            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!$lines || count($lines) < 2) { throw new Exception('ไฟล์ว่างหรือไม่มีข้อมูล'); }
            // Strip UTF-8 BOM if present on first line
            if (isset($lines[0]) && substr($lines[0], 0, 3) === "\xEF\xBB\xBF") {
                $lines[0] = substr($lines[0], 3);
            }
            $headers = str_getcsv(trim($lines[0]));
            // Build both raw and normalized header maps
            $map = [];
            foreach ($headers as $idx => $h) {
                $clean = preg_replace('/^\xEF\xBB\xBF/u', '', (string)$h);
                $map[strtolower(trim($clean))] = $idx;
            }
            $normalize = function($s){ return preg_replace('/[^a-z0-9]+/','', strtolower((string)$s)); };
            $normalizedMap = [];
            foreach ($map as $k => $idx) { $normalizedMap[$normalize($k)] = $idx; }

            $userId = $_SESSION['user_id'] ?? 0;
            for ($i=1; $i<count($lines); $i++) {
                $results['total']++;
                $row = str_getcsv($lines[$i]);
                // Getter that tolerates header variants: underscores/spaces/case/BOM
                $get = function($key) use($normalizedMap,$row,$normalize){
                    $nk = $normalize($key);
                    if (!isset($normalizedMap[$nk])) { return ''; }
                    $idx = $normalizedMap[$nk];
                    return isset($row[$idx]) ? trim($row[$idx]) : '';
                };
                $customerCode = $get('customer_code');
                if ($customerCode === '') { $results['skipped']++; $results['errors'][] = "บรรทัด $i: ไม่มี customer_code"; continue; }
                $customer = $this->db->fetchOne("SELECT customer_id FROM customers WHERE customer_code = ? LIMIT 1", [$customerCode]);
                if (!$customer) { $results['skipped']++; $results['errors'][] = "บรรทัด $i: ไม่พบลูกค้า $customerCode"; continue; }
                $customerId = (int)$customer['customer_id'];

                // Normalize enums (accept Thai and English)
                $rawType = $get('call_type');
                $rawStatus = $get('call_status');
                $rawResult = $get('call_result');

                $callType = $this->mapCallTypeEnum($rawType);
                $callStatus = $this->mapCallStatusEnum($rawStatus);
                $callResult = $this->mapCallResultEnum($rawResult);

                if ($callStatus === '') { $results['skipped']++; $results['errors'][] = "บรรทัด $i: call_status ไม่ถูกต้อง"; continue; }
                if ($rawResult !== '' && $callResult === '') { $results['skipped']++; $results['errors'][] = "บรรทัด $i: call_result ไม่ถูกต้อง"; continue; }

                $duration = (int)$get('duration_minutes');
                $notes = $get('notes');
                $nextAction = $get('next_action');
                $nextFollowup = $get('next_followup_at');
                $createdAt = $get('called_at');
                $recordedBy = $get('recorded_by');
                $useUserId = $userId;
                if ($recordedBy !== '') {
                    if (ctype_digit($recordedBy)) { $useUserId = (int)$recordedBy; }
                    else { $u = $this->db->fetchOne("SELECT user_id FROM users WHERE username = ? OR full_name = ? LIMIT 1", [$recordedBy,$recordedBy]); if ($u) $useUserId = (int)$u['user_id']; }
                }

                // Insert (attach company_id from customer)
                $custRow = $this->db->fetchOne("SELECT company_id FROM customers WHERE customer_id = ?", [$customerId]);
                $callCompanyId = $custRow['company_id'] ?? null;
                $this->db->insert('call_logs', [
                    'company_id' => $callCompanyId,
                    'customer_id' => $customerId,
                    'user_id' => $useUserId,
                    'call_type' => $callType,
                    'call_status' => $callStatus,
                    'call_result' => ($callResult !== '' ? $callResult : null),
                    'duration_minutes' => $duration,
                    'notes' => $notes,
                    'next_action' => $nextAction,
                    'next_followup_at' => ($nextFollowup !== '' ? $nextFollowup : null),
                    'created_at' => ($createdAt !== '' ? $createdAt : date('Y-m-d H:i:s'))
                ]);

                // Update last_contact_at
                $this->db->execute("UPDATE customers SET last_contact_at = GREATEST(COALESCE(last_contact_at,'1970-01-01'), ?) WHERE customer_id = ?", [($createdAt !== '' ? $createdAt : date('Y-m-d H:i:s')), $customerId]);

                // Auto followup for new customers without order result
                if ($callResult !== 'order') {
                    try {
                        $cust = $this->db->fetchOne("SELECT customer_status FROM customers WHERE customer_id = ?", [$customerId]);
                        if (($cust['customer_status'] ?? '') === 'new') {
                            $this->db->execute("UPDATE customers SET customer_status = 'followup' WHERE customer_id = ?", [$customerId]);
                        }
                    } catch (Exception $e) { /* ignore */ }
                }

                $results['inserted']++;
            }

            return $results;
        } catch (Exception $e) {
            $results['success'] = 0;
            $results['errors'][] = $e->getMessage();
            return $results;
        }
    }
    /**
     * Resolve product_id from code or name; return null if not found
     */
    private function resolveProductId($productCode, $productName) {
        try {
            if ($this->tableExists('products')) {
                if (!empty($productCode)) {
                    $row = $this->db->fetchOne("SELECT product_id FROM products WHERE product_code = ? LIMIT 1", [trim($productCode)]);
                    if ($row && !empty($row['product_id'])) {
                        return (int)$row['product_id'];
                    }
                }
                if (!empty($productName)) {
                    $row = $this->db->fetchOne("SELECT product_id FROM products WHERE product_name = ? LIMIT 1", [trim($productName)]);
                    if ($row && !empty($row['product_id'])) {
                        return (int)$row['product_id'];
                    }
                }
            }
        } catch (Exception $e) {
            error_log('resolveProductId failed: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Ensure a fallback product exists and return its product_id
     */
    private function ensureFallbackProduct() {
        try {
            if (!$this->tableExists('products')) {
                return 1; // arbitrary
            }
            $row = $this->db->fetchOne("SELECT product_id FROM products WHERE product_code = ? LIMIT 1", ['IMPORT-UNKNOWN']);
            if ($row && !empty($row['product_id'])) {
                return (int)$row['product_id'];
            }
            // create minimal product
            $now = date('Y-m-d H:i:s');
            $sql = "INSERT INTO products (product_code, product_name, category, unit, cost_price, selling_price, stock_quantity, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, ['IMPORT-UNKNOWN', 'ไม่ระบุสินค้า', 'IMPORT', 'ชิ้น', 0, 0, 0, 1, $now, $now]);
            return (int)$this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('ensureFallbackProduct failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * แปลงวันที่จากรูปแบบต่างๆ ให้เป็น Y-m-d สำหรับ MySQL
     * รองรับรูปแบบ: d/m/Y, d-m-Y, d/m/y, d-m-y, Y-m-d, Y/m/d
     */
    private function parseDate($dateString) {
        if (empty($dateString)) {
            return date('Y-m-d');
        }

        // Normalize and strip timezones/non-breaking spaces
        $dateString = trim(preg_replace('/\s+/u', ' ', (string)$dateString));

        // Handle Excel serial numbers (days since 1899-12-30)
        if (is_numeric($dateString)) {
            $serial = (float)$dateString;
            if ($serial > 20000 && $serial < 60000) { // rough bounds 1954..2064
                $base = new DateTime('1899-12-30');
                $base->modify('+' . floor($serial) . ' days');
                return $base->format('Y-m-d');
            }
        }

        // Common case: "YYYY-MM-DD" (already normalized)
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $dateString)) {
            $date = DateTime::createFromFormat('Y-m-d', $dateString);
            if ($date && $date->format('Y-m-d') === $dateString) {
                return $dateString;
            }
        }

        // If has time part, try datetime formats first
        $dateTimeFormats = [
            'd/m/Y H:i:s', 'd/m/Y H:i',
            'd-m-Y H:i:s', 'd-m-Y H:i',
            'Y-m-d H:i:s', 'Y-m-d H:i',
            'Y/m/d H:i:s', 'Y/m/d H:i',
            'm/d/Y H:i:s', 'm/d/Y H:i',
            'm-d-Y H:i:s', 'm-d-Y H:i'
        ];
        foreach ($dateTimeFormats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $dateString);
            if ($dt && $dt->format($fmt) === $dateString) {
                return $dt->format('Y-m-d');
            }
        }

        // Try date-only formats (will also work after we strip time portion below)
        $dateOnlyFormats = [
            'd/m/Y', 'd-m-Y', 'd/m/y', 'd-m-y',
            'Y/m/d', 'Y-m-d', 'm/d/Y', 'm-d-Y'
        ];
        foreach ($dateOnlyFormats as $fmt) {
            $d = DateTime::createFromFormat($fmt, $dateString);
            if ($d && $d->format($fmt) === $dateString) {
                return $d->format('Y-m-d');
            }
        }

        // Last attempt: strip time portion if present and parse again
        $onlyDate = preg_replace('/[ T].*$/', '', $dateString);
        if ($onlyDate !== $dateString) {
            foreach ($dateOnlyFormats as $fmt) {
                $d2 = DateTime::createFromFormat($fmt, $onlyDate);
                if ($d2 && $d2->format($fmt) === $onlyDate) {
                    return $d2->format('Y-m-d');
                }
            }
        }

        // Fallback to current date if still unparseable
        error_log("Unable to parse date: " . $dateString . ", using current date");
        return date('Y-m-d');
    }

    /**
     * แปลงข้อความตัวเลขให้เป็น float อย่างปลอดภัย
     * - รองรับรูปแบบไทย: 1,095 (คอมมาเป็นตัวคั่นหลักพัน)
     * - รองรับรูปแบบสากล: 1,234.56 หรือ 1.234,56
     */
    private function parseNumber($value) {
        if ($value === null || $value === '') return 0.0;
        if (is_numeric($value)) return (float)$value;

        $str = trim((string)$value);
        if ($str === '' || $str === '-') return 0.0;

        // ลบอักขระที่ไม่ใช่ตัวเลข คอมมา จุด และลบ
        $str = preg_replace('/[^0-9,\.\-]/u', '', $str);
        if ($str === '' || $str === '-') return 0.0;

        // นับจำนวนคอมมาและจุด
        $commaCount = substr_count($str, ',');
        $dotCount = substr_count($str, '.');

        // กรณีที่ 1: มีเฉพาะคอมมา (รูปแบบไทย: 1,095 หรือ 10,500)
        if ($commaCount > 0 && $dotCount === 0) {
            // ลบคอมมาทั้งหมด (ถือว่าเป็นตัวคั่นหลักพัน)
            $str = str_replace(',', '', $str);
        }
        // กรณีที่ 2: มีทั้งคอมมาและจุด
        elseif ($commaCount > 0 && $dotCount > 0) {
            $lastComma = strrpos($str, ',');
            $lastDot = strrpos($str, '.');

            if ($lastComma > $lastDot) {
                // รูปแบบยุโรป: 1.234,56 → ลบจุด แทนคอมมาเป็นจุด
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } else {
                // รูปแบบอเมริกัน: 1,234.56 → ลบคอมมา
                $str = str_replace(',', '', $str);
            }
        }
        // กรณีที่ 3: มีเฉพาะจุด → ไม่ต้องแก้ไข

        return (float)$str;
    }

    /**
     * Map payment method from Thai to English
     */
    private function mapPaymentMethod($paymentMethod) {
        $mapping = [
            'เงินสด' => 'cash',
            'โอนเงิน' => 'transfer',
            'เก็บเงินปลายทาง' => 'cod',
            'เครดิต' => 'credit',
            'รับสินค้าก่อนชำระ' => 'receive_before_payment',
            'อื่นๆ' => 'other',
            // English mappings
            'cash' => 'cash',
            'transfer' => 'transfer',
            'cod' => 'cod',
            'credit' => 'credit',
            'receive_before_payment' => 'receive_before_payment',
            'other' => 'other'
        ];

        return $mapping[$paymentMethod] ?? 'cash';
    }

    /**
     * Map payment status from Thai to English
     */
    private function mapPaymentStatus($paymentStatus) {
        $mapping = [
            'รอดำเนินการ' => 'pending',
            'ชำระแล้ว' => 'paid',
            'ชำระบางส่วน' => 'partial',
            'ยกเลิก' => 'cancelled',
            // English mappings
            'pending' => 'pending',
            'paid' => 'paid',
            'partial' => 'partial',
            'cancelled' => 'cancelled'
        ];

        return $mapping[$paymentStatus] ?? 'pending';
    }

    /**
     * Map Call Type (Thai/English) → enum: outbound|inbound
     */
    private function mapCallTypeEnum($value) {
        $v = trim(mb_strtolower((string)$value));
        if ($v === '') return 'outbound';
        $mapping = [
            'ออกโทร' => 'outbound',
            'โทรออก' => 'outbound',
            'โทรออกไป' => 'outbound',
            'ขาออก' => 'outbound',
            'โทรเข้า' => 'inbound',
            'รับสายเข้า' => 'inbound',
            'ขาเข้า' => 'inbound',
            // English
            'outbound' => 'outbound',
            'inbound' => 'inbound'
        ];
        return $mapping[$v] ?? 'outbound';
    }

    /**
     * Map Call Status (Thai/English) → enum: answered|no_answer|busy|invalid
     */
    private function mapCallStatusEnum($value) {
        $v = trim(mb_strtolower((string)$value));
        $mapping = [
            // Thai
            'รับสาย' => 'answered',
            'รับแล้ว' => 'answered',
            'ติดสาย' => 'busy',
            'สายไม่ว่าง' => 'busy',
            'ไม่รับสาย' => 'no_answer',
            'ไม่รับ' => 'no_answer',
            'เบอร์ผิด' => 'invalid',
            'ไม่ถูกต้อง' => 'invalid',
            // English
            'answered' => 'answered',
            'no_answer' => 'no_answer',
            'busy' => 'busy',
            'invalid' => 'invalid'
        ];
        return $mapping[$v] ?? '';
    }

    /**
     * Map Call Result (Thai/English) → enum: interested|not_interested|callback|order|complaint
     */
    private function mapCallResultEnum($value) {
        $v = trim(mb_strtolower((string)$value));
        if ($v === '') return '';
        $mapping = [
            // Thai
            'สนใจ' => 'interested',
            'ไม่สนใจ' => 'not_interested',
            'โทรกลับ' => 'callback',
            'สั่งซื้อ' => 'order',
            'ร้องเรียน' => 'complaint',
            // English
            'interested' => 'interested',
            'not_interested' => 'not_interested',
            'callback' => 'callback',
            'order' => 'order',
            'complaint' => 'complaint'
        ];
        return $mapping[$v] ?? '';
    }

    /**
     * แปลงชื่อหรือรหัสพนักงานเป็น user_id
     */
    private function getUserIdFromNameOrId($nameOrId) {
        if (empty($nameOrId)) {
            return $_SESSION['user_id'] ?? 1; // ใช้ user ปัจจุบันถ้าไม่ระบุ
        }

        // ถ้าเป็นตัวเลข ให้ถือว่าเป็น user_id
        if (is_numeric($nameOrId)) {
            return (int)$nameOrId;
        }

        try {
            // ค้นหาจากชื่อหรือ username
            $sql = "SELECT user_id FROM users WHERE
                    username = ? OR
                    full_name = ?";

            $result = $this->db->fetchOne($sql, [$nameOrId, $nameOrId]);

            if ($result) {
                return $result['user_id'];
            }

            // ถ้าไม่เจอ ให้ใช้ user ปัจจุบัน
            return $_SESSION['user_id'] ?? 1;

        } catch (Exception $e) {
            error_log("Error getting user ID from name: " . $e->getMessage());
            return $_SESSION['user_id'] ?? 1;
        }
    }

    /**
     * Generate unique customer code from phone number
     */
    private function generateCustomerCode($phone) {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Take last 9 digits (remove leading 0 if present)
        if (strlen($phone) > 9) {
            $phone = substr($phone, -9);
        }

        $baseCode = 'CUS' . str_pad($phone, 9, '0', STR_PAD_LEFT);
        
        // Check if code already exists within the same company and make it unique
        $companyId = CompanyContext::getCompanyId($this->db);
        $counter = 1;
        $customerCode = $baseCode;
        
        // Check for existing customer_code within the same company
        if ($companyId) {
            while ($this->db->fetchOne("SELECT customer_id FROM customers WHERE customer_code = ? AND company_id = ? LIMIT 1", [$customerCode, $companyId])) {
                $customerCode = $baseCode . '_' . $counter;
                $counter++;
                
                // Prevent infinite loop
                if ($counter > 999) {
                    $customerCode = $baseCode . '_' . time();
                    break;
                }
            }
        } else {
            // If no company_id, check without company filter (fallback)
            while ($this->db->fetchOne("SELECT customer_id FROM customers WHERE customer_code = ? LIMIT 1", [$customerCode])) {
                $customerCode = $baseCode . '_' . $counter;
                $counter++;
                
                // Prevent infinite loop
                if ($counter > 999) {
                    $customerCode = $baseCode . '_' . time();
                    break;
                }
            }
        }
        
        return $customerCode;
    }
}
