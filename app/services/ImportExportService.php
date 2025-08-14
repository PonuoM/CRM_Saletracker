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
                $existingCustomer = $this->db->query("SELECT id FROM customers WHERE phone = ?", [$phone])->fetch();

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
                // Handle assigned_to field (convert name to user_id if needed)
                $assignedTo = null;
                if (!empty($customerData['assigned_to'])) {
                    // Check if it's a user ID or name
                    if (is_numeric($customerData['assigned_to'])) {
                        $assignedTo = $customerData['assigned_to'];
                    } else {
                        // Try to find user by name
                        $userSql = "SELECT user_id FROM users WHERE CONCAT(first_name, ' ', last_name) LIKE ? OR username LIKE ?";
                        $userResult = $this->db->fetchOne($userSql, [$customerData['assigned_to'], $customerData['assigned_to']]);
                        if ($userResult) {
                            $assignedTo = $userResult['user_id'];
                        }
                    }
                }

                // Set basket_type based on whether a follower is assigned
                if ($assignedTo) {
                    $customerData['basket_type'] = 'assigned'; // มีผู้ติดตามแล้ว
                } else {
                    $customerData['basket_type'] = 'distribution'; // อยู่ในตะกร้าแจก
                }

                $customerData['is_active'] = 1;

                // Determine company source from current user
                $companySource = $this->getCurrentCompanySource();

                // Insert customer with company source
                $sql = "INSERT INTO customers (first_name, last_name, phone, email, address, district, province, postal_code, source, customer_status, temperature_status, customer_grade, basket_type, assigned_to, is_active, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $this->db->query($sql, [
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

                    // Resolve or create customer by phone
                    $existingCustomer = $this->db->fetchOne(
                        "SELECT customer_id FROM customers WHERE phone = ?",
                        [$salesData['phone']]
                    );

                    if ($existingCustomer) {
                        $customerId = (int)$existingCustomer['customer_id'];
                        $results['customers_updated']++;
                    } else {
                        $customerId = $this->createNewCustomer($salesData);
                        if ($customerId) {
                            $results['customers_created']++;
                        } else {
                            $results['errors'][] = "แถวที่ {$rowNumber}: ไม่สามารถสร้างลูกค้าได้";
                            continue;
                        }
                    }

                    // Build grouping key
                    $providedOrderNumber = trim($salesData['order_number'] ?? '');
                    if ($providedOrderNumber !== '') {
                        $groupKey = 'NUM:' . $providedOrderNumber;
                    } else {
                        $dateStr = trim($salesData['order_date'] ?? date('Y-m-d'));
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
                            'order_date' => $salesData['order_date'] ?? date('Y-m-d'),
                            'payment_method' => $this->mapPaymentMethod($salesData['payment_method'] ?? 'cash'),
                            'payment_status' => $this->mapPaymentStatus($salesData['payment_status'] ?? 'pending'),
                            'created_by' => $this->getUserIdFromNameOrId($salesData['created_by'] ?? ''),
                            'sales_channel' => $salesData['sales_channel'] ?? 'External',
                            'notes' => $salesData['notes'] ?? ''
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

                    // Determine order number
                    $orderNumber = trim($meta['order_number'] ?? '');
                    if ($orderNumber !== '') {
                        $orderNumber = mb_substr($orderNumber, 0, 50);
                        try {
                            $exists = $this->db->fetchOne("SELECT order_id FROM orders WHERE order_number = ? LIMIT 1", [$orderNumber]);
                            if ($exists) {
                                $orderNumber = mb_substr($orderNumber . '-' . rand(1000, 9999), 0, 50);
                            }
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

                    // Insert order
                    $orderSql = "INSERT INTO orders (customer_id, order_number, order_date, total_amount, net_amount, payment_method, payment_status, delivery_status, notes, created_by, created_at, updated_at)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $orderParams = [
                        $customerId,
                        $orderNumber,
                        substr($meta['order_date'] ?? date('Y-m-d'), 0, 10),
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
            // Handle assigned_to field (convert name to user_id if needed)
            $assignedTo = null;
            if (!empty($salesData['assigned_to'])) {
                // Check if it's a user ID or name
                if (is_numeric($salesData['assigned_to'])) {
                    $assignedTo = (int)$salesData['assigned_to'];
                } else {
                    // Try to find user by name
                    $userSql = "SELECT user_id FROM users WHERE CONCAT(first_name, ' ', last_name) LIKE ? OR username LIKE ?";
                    $userResult = $this->db->fetchOne($userSql, [$salesData['assigned_to'], $salesData['assigned_to']]);
                    if ($userResult) {
                        $assignedTo = (int)$userResult['user_id'];
                    }
                }
            }

            // Set basket_type based on whether a follower is assigned
            $basketType = $assignedTo ? 'assigned' : 'distribution';

            // Generate customer_code from phone number
            $customerCode = $this->generateCustomerCode($salesData['phone']);

            // Determine customer_status based on assignment
            $customerStatus = $assignedTo ? 'existing' : 'new';

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
                'customer_code' => $customerCode,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Determine company source from current user
            $companySource = $this->getCurrentCompanySource();

            $sql = "INSERT INTO customers (first_name, last_name, phone, email, address, district, province, postal_code, source, basket_type, temperature_status, customer_grade, customer_status, assigned_to, customer_code, is_active, created_at, updated_at)
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
                $companySource,
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
            return false;
        }
    }

    /**
     * สร้างลูกค้าใหม่สำหรับ Customers Only Import
     */
    private function createNewCustomerOnly($customerData) {
        try {
            // Handle assigned_to field (convert name to user_id if needed)
            $assignedTo = null;
            if (!empty($customerData['assigned_to'])) {
                // Check if it's a user ID or name
                if (is_numeric($customerData['assigned_to'])) {
                    $assignedTo = $customerData['assigned_to'];
                } else {
                    // Try to find user by name
                    $userSql = "SELECT user_id FROM users WHERE CONCAT(first_name, ' ', last_name) LIKE ? OR username LIKE ?";
                    $userResult = $this->db->fetchOne($userSql, [$customerData['assigned_to'], $customerData['assigned_to']]);
                    if ($userResult) {
                        $assignedTo = $userResult['user_id'];
                    }
                }
            }

            // Set basket_type based on whether a follower is assigned
            $basketType = $assignedTo ? 'assigned' : 'distribution';

            // Generate customer_code from phone number
            $customerCode = $this->generateCustomerCode($customerData['phone']);

            // Determine customer_status based on assignment
            $customerStatus = $assignedTo ? 'existing' : 'new';

            $data = [
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'] ?? '',
                'phone' => $customerData['phone'],
                'email' => $customerData['email'] ?? '',
                'address' => $customerData['address'] ?? '',
                'district' => $customerData['district'] ?? '',
                'province' => $customerData['province'] ?? '',
                'postal_code' => $customerData['postal_code'] ?? '',
                'basket_type' => $basketType, // กำหนดตามการมีผู้ติดตาม
                'temperature_status' => 'cold', // ลูกค้าเย็น (ยังไม่มียอดขาย)
                'customer_grade' => 'D', // เกรดเริ่มต้น
                'customer_status' => $customerStatus,
                'assigned_to' => $assignedTo,
                'customer_code' => $customerCode,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Determine company source from current user
            $companySource = $this->getCurrentCompanySource();

            $sql = "INSERT INTO customers (first_name, last_name, phone, email, address, district, province, postal_code, source, basket_type, temperature_status, customer_grade, customer_status, assigned_to, customer_code, is_active, created_at, updated_at)
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
                $companySource,
                $data['basket_type'],
                $data['temperature_status'],
                $data['customer_grade'],
                $data['customer_status'],
                $data['assigned_to'],
                $data['customer_code'],
                $data['is_active'],
                $data['created_at'],
                $data['updated_at']
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
                    error_log("Failed to set customer time window (90d) for new customer {$newCustomerId}: " . $e->getMessage());
                }
            } else {
                // ไม่มีผู้ติดตาม: 30 วัน และสถานะ new
                try {
                    $this->db->query(
                        "UPDATE customers
                         SET customer_time_base = NOW(),
                             customer_time_expiry = DATE_ADD(NOW(), INTERVAL 30 DAY)
                         WHERE customer_id = ?",
                        [$newCustomerId]
                    );
                } catch (Exception $e) {
                    error_log("Failed to set customer time window (30d) for new customer {$newCustomerId}: " . $e->getMessage());
                }
            }

            return $newCustomerId;

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
                'order_date' => $salesData['order_date'] ?? date('Y-m-d'),
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

            $sql = "INSERT INTO orders (customer_id, order_number, order_date, total_amount, net_amount, payment_method, payment_status, delivery_status, notes, created_by, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->query($sql, [
                $orderData['customer_id'],
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

                // Insert
                $this->db->insert('call_logs', [
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
                    first_name = ? OR
                    last_name = ? OR
                    CONCAT(first_name, ' ', last_name) = ? OR
                    CONCAT(last_name, ' ', first_name) = ?";

            $result = $this->db->fetchOne($sql, [$nameOrId, $nameOrId, $nameOrId, $nameOrId, $nameOrId]);

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
     * Generate customer code from phone number
     */
    private function generateCustomerCode($phone) {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Take last 9 digits (remove leading 0 if present)
        if (strlen($phone) > 9) {
            $phone = substr($phone, -9);
        }

        return 'CUS' . str_pad($phone, 9, '0', STR_PAD_LEFT);
    }
}