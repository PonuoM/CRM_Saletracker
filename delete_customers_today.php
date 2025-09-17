<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🗑️ Delete Customers Today</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            color: #2c3e50;
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fafafa;
        }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        .info { color: #3498db; font-weight: bold; }
        .result-box {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 12px;
        }
        .sql-box {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
        }
        .btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #c0392b; }
        .btn-success { background: #27ae60; }
        .btn-warning { background: #f39c12; }
        .btn-info { background: #3498db; }
        .danger-zone {
            border: 2px solid #e74c3c;
            background-color: #fdf2f2;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗑️ Delete Customers Today</h1>
            <p>ลบข้อมูลลูกค้าที่สร้างวันนี้ในบริษัท 1</p>
        </div>

        <?php
        // Initialize database
        try {
            $db = new Database();
            echo '<div class="success">✅ เชื่อมต่อฐานข้อมูลสำเร็จ</div>';
        } catch (Exception $e) {
            echo '<div class="error">❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้: ' . htmlspecialchars($e->getMessage()) . '</div>';
            exit;
        }

        $action = $_GET['action'] ?? '';
        $targetDate = '2025-09-04'; // วันที่ 04/09/2025
        $companyId = 1;

        function previewCustomersToDelete($db, $targetDate, $companyId) {
            $output = "";
            $output .= "🔍 Preview Customers to Delete\n";
            $output .= str_repeat("=", 50) . "\n\n";
            $output .= "Target Date: {$targetDate}\n";
            $output .= "Company ID: {$companyId}\n\n";

            try {
                // Find customers created today in company 1
                $customers = $db->fetchAll("
                    SELECT 
                        customer_id,
                        customer_name,
                        customer_code,
                        phone,
                        company_id,
                        created_at,
                        assigned_to
                    FROM customers 
                    WHERE DATE(created_at) = ? 
                    AND company_id = ?
                    ORDER BY created_at DESC
                ", [$targetDate, $companyId]);

                if ($customers) {
                    $output .= "Found " . count($customers) . " customers to delete:\n\n";
                    foreach ($customers as $customer) {
                        $output .= "- ID: {$customer['customer_id']}\n";
                        $output .= "  Name: {$customer['customer_name']}\n";
                        $output .= "  Code: {$customer['customer_code']}\n";
                        $output .= "  Phone: {$customer['phone']}\n";
                        $output .= "  Created: {$customer['created_at']}\n";
                        $output .= "  Assigned to: " . ($customer['assigned_to'] ?: 'None') . "\n\n";
                    }
                } else {
                    $output .= "✅ No customers found with these criteria\n";
                }

            } catch (Exception $e) {
                $output .= "❌ Error: " . $e->getMessage() . "\n";
            }

            return $output;
        }

        function deleteCustomersToday($db, $targetDate, $companyId) {
            $output = "";
            $output .= "🗑️ Deleting Customers\n";
            $output .= str_repeat("=", 50) . "\n\n";

            try {
                // Start transaction
                $db->query("START TRANSACTION");

                // Get customers to delete first
                $customers = $db->fetchAll("
                    SELECT customer_id, customer_name, customer_code
                    FROM customers 
                    WHERE DATE(created_at) = ? 
                    AND company_id = ?
                ", [$targetDate, $companyId]);

                if (!$customers) {
                    $output .= "✅ No customers found to delete\n";
                    $db->query("ROLLBACK");
                    return $output;
                }

                $customerIds = array_column($customers, 'customer_id');
                $customerIdsStr = implode(',', $customerIds);

                $output .= "Deleting " . count($customers) . " customers...\n\n";

                // Delete related records first (Foreign Key Constraints)
                
                // 1. Delete order_items related to orders of these customers
                $orderItemsDeleted = $db->query("
                    DELETE oi FROM order_items oi
                    INNER JOIN orders o ON oi.order_id = o.order_id
                    WHERE o.customer_id IN ($customerIdsStr)
                ");
                $output .= "- Deleted order_items: " . $db->getAffectedRows() . "\n";

                // 2. Delete orders
                $ordersDeleted = $db->query("
                    DELETE FROM orders 
                    WHERE customer_id IN ($customerIdsStr)
                ");
                $output .= "- Deleted orders: " . $db->getAffectedRows() . "\n";

                // 3. Delete customer_activities (สำคัญ! Foreign Key Constraint)
                $activitiesDeleted = $db->query("
                    DELETE FROM customer_activities 
                    WHERE customer_id IN ($customerIdsStr)
                ");
                $output .= "- Deleted customer_activities: " . $db->getAffectedRows() . "\n";

                // 4. Delete call_logs (ถ้ามี)
                try {
                    $callLogsDeleted = $db->query("
                        DELETE FROM call_logs 
                        WHERE customer_id IN ($customerIdsStr)
                    ");
                    $output .= "- Deleted call_logs: " . $db->getAffectedRows() . "\n";
                } catch (Exception $e) {
                    $output .= "- call_logs table not found or no records\n";
                }

                // 5. Delete customer_notes (ถ้ามี)
                try {
                    $notesDeleted = $db->query("
                        DELETE FROM customer_notes 
                        WHERE customer_id IN ($customerIdsStr)
                    ");
                    $output .= "- Deleted customer_notes: " . $db->getAffectedRows() . "\n";
                } catch (Exception $e) {
                    $output .= "- customer_notes table not found or no records\n";
                }

                // 6. Delete customers
                $customersDeleted = $db->query("
                    DELETE FROM customers 
                    WHERE DATE(created_at) = ? 
                    AND company_id = ?
                ", [$targetDate, $companyId]);
                $deletedCount = $db->getAffectedRows();
                $output .= "- Deleted customers: {$deletedCount}\n\n";

                // Commit transaction
                $db->query("COMMIT");

                $output .= "✅ Successfully deleted all related records\n";
                $output .= "Total customers deleted: {$deletedCount}\n";

                // Show deleted customers
                $output .= "\nDeleted customers:\n";
                foreach ($customers as $customer) {
                    $output .= "- {$customer['customer_name']} ({$customer['customer_code']})\n";
                }

            } catch (Exception $e) {
                $db->query("ROLLBACK");
                $output .= "❌ Error: " . $e->getMessage() . "\n";
                $output .= "Transaction rolled back\n";
            }

            return $output;
        }

        if ($action === 'preview') {
            echo '<div class="test-section">';
            echo '<h3>🔍 Preview Results</h3>';
            $result = previewCustomersToDelete($db, $targetDate, $companyId);
            echo '<div class="result-box">' . htmlspecialchars($result) . '</div>';
            echo '</div>';
        } elseif ($action === 'delete') {
            echo '<div class="test-section">';
            echo '<h3>🗑️ Delete Results</h3>';
            $result = deleteCustomersToday($db, $targetDate, $companyId);
            echo '<div class="result-box">' . htmlspecialchars($result) . '</div>';
            echo '</div>';
        }
        ?>

        <!-- SQL Commands -->
        <div class="test-section">
            <h3>📋 SQL Commands</h3>
            
            <h4>1. Preview Query (ดูข้อมูลก่อนลบ):</h4>
            <div class="sql-box">SELECT 
    customer_id,
    customer_name,
    customer_code,
    phone,
    company_id,
    created_at
FROM customers 
WHERE DATE(created_at) = '2025-09-04' 
AND company_id = 1
ORDER BY created_at DESC;</div>

            <h4>2. Delete Commands (ลำดับการลบ - แก้ไข Foreign Key):</h4>
            <div class="sql-box">-- เริ่ม Transaction
START TRANSACTION;

-- 1. ลบ order_items ที่เกี่ยวข้อง
DELETE oi FROM order_items oi
INNER JOIN orders o ON oi.order_id = o.order_id
INNER JOIN customers c ON o.customer_id = c.customer_id
WHERE DATE(c.created_at) = '2025-09-04' 
AND c.company_id = 1;

-- 2. ลบ orders ที่เกี่ยวข้อง
DELETE o FROM orders o
INNER JOIN customers c ON o.customer_id = c.customer_id
WHERE DATE(c.created_at) = '2025-09-04' 
AND c.company_id = 1;

-- 3. ลบ customer_activities ที่เกี่ยวข้อง (สำคัญ!)
DELETE ca FROM customer_activities ca
INNER JOIN customers c ON ca.customer_id = c.customer_id
WHERE DATE(c.created_at) = '2025-09-04' 
AND c.company_id = 1;

-- 4. ลบข้อมูลอื่นๆ ที่อาจเกี่ยวข้อง (ถ้ามี)
-- ลบ call logs
DELETE cl FROM call_logs cl
INNER JOIN customers c ON cl.customer_id = c.customer_id
WHERE DATE(c.created_at) = '2025-09-04' 
AND c.company_id = 1;

-- ลบ customer notes (ถ้ามี)
DELETE cn FROM customer_notes cn
INNER JOIN customers c ON cn.customer_id = c.customer_id
WHERE DATE(c.created_at) = '2025-09-04' 
AND c.company_id = 1;

-- 5. ลบ customers
DELETE FROM customers 
WHERE DATE(created_at) = '2025-09-04' 
AND company_id = 1;

-- ยืนยันการลบ
COMMIT;</div>

            <h4>3. Simple Delete (ลบเฉพาะลูกค้า - ถ้าไม่มี foreign key):</h4>
            <div class="sql-box">DELETE FROM customers 
WHERE DATE(created_at) = '2025-09-04' 
AND company_id = 1;</div>
        </div>

        <!-- Current Info -->
        <div class="test-section">
            <h3>📋 Current Settings</h3>
            <div class="info">
                <strong>Target Date:</strong> <?= $targetDate ?> (04/09/2025)<br>
                <strong>Company ID:</strong> <?= $companyId ?><br>
                <strong>Current Server Time:</strong> <?= date('Y-m-d H:i:s') ?>
            </div>
        </div>

        <!-- Actions -->
        <div class="test-section">
            <h3>🚀 Actions</h3>
            <a href="?action=preview" class="btn btn-info">🔍 Preview Customers</a>
        </div>

        <!-- Danger Zone -->
        <div class="danger-zone">
            <h3>⚠️ Danger Zone</h3>
            <div class="error">
                <strong>คำเตือน:</strong> การลบข้อมูลไม่สามารถย้อนกลับได้!<br>
                กรุณาตรวจสอบข้อมูลด้วย Preview ก่อนลบ
            </div>
            <a href="?action=delete" class="btn" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบข้อมูลลูกค้าทั้งหมดที่สร้างวันนี้ในบริษัท 1?\n\nการกระทำนี้ไม่สามารถย้อนกลับได้!')">🗑️ Delete Customers Today</a>
        </div>

        <!-- Notes -->
        <div class="test-section">
            <h3>📝 หมายเหตุ</h3>
            <ul>
                <li><strong>การลบจะรวม:</strong> ลูกค้า, ออเดอร์, และรายการสินค้าที่เกี่ยวข้อง</li>
                <li><strong>เงื่อนไข:</strong> <code>DATE(created_at) = '2025-09-04'</code> และ <code>company_id = 1</code></li>
                <li><strong>Transaction:</strong> ใช้ Transaction เพื่อความปลอดภัย</li>
                <li><strong>Foreign Key:</strong> ลบข้อมูลที่เกี่ยวข้องก่อนลบลูกค้า</li>
            </ul>
        </div>

    </div>
</body>
</html>
