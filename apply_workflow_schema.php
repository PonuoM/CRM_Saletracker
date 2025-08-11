<?php
/**
 * Apply Workflow Schema Changes
 * ใช้การเปลี่ยนแปลงโครงสร้างฐานข้อมูลสำหรับ Workflow
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

echo "<h1>🔧 Apply Workflow Schema Changes</h1>";

try {
    $db = new Database();
    
    echo "<h2>1. ตรวจสอบโครงสร้างปัจจุบัน</h2>";
    
    // ตรวจสอบคอลัมน์ที่มีอยู่
    $structure = $db->fetchAll("DESCRIBE customers");
    $existingColumns = array_column($structure, 'Field');
    
    $requiredColumns = ['basket_type', 'assigned_at', 'assigned_to', 'recall_at', 'customer_time_expiry', 'customer_time_extension'];
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    echo "<p><strong>คอลัมน์ที่มีอยู่:</strong> " . implode(', ', $existingColumns) . "</p>";
    echo "<p><strong>คอลัมน์ที่ขาดหายไป:</strong> " . (empty($missingColumns) ? "ไม่มี" : implode(', ', $missingColumns)) . "</p>";
    
    if (empty($missingColumns)) {
        echo "<p>✅ โครงสร้างตารางถูกต้องแล้ว</p>";
    } else {
        echo "<h2>2. เพิ่มคอลัมน์ที่ขาดหายไป</h2>";
        
        $sqlCommands = [];
        
        if (in_array('basket_type', $missingColumns)) {
            $sqlCommands[] = "ALTER TABLE customers ADD COLUMN basket_type ENUM('distribution', 'assigned', 'waiting') DEFAULT 'distribution' COMMENT 'สถานะของลูกค้า'";
        }
        
        if (in_array('assigned_at', $missingColumns)) {
            $sqlCommands[] = "ALTER TABLE customers ADD COLUMN assigned_at DATETIME NULL COMMENT 'วันที่มอบหมายลูกค้า'";
        }
        
        if (in_array('assigned_to', $missingColumns)) {
            $sqlCommands[] = "ALTER TABLE customers ADD COLUMN assigned_to INT NULL COMMENT 'ID ของเซลส์ที่ได้รับมอบหมาย'";
        }
        
        if (in_array('recall_at', $missingColumns)) {
            $sqlCommands[] = "ALTER TABLE customers ADD COLUMN recall_at DATETIME NULL COMMENT 'วันที่เรียกคืนลูกค้า'";
        }
        
        if (in_array('customer_time_expiry', $missingColumns)) {
            $sqlCommands[] = "ALTER TABLE customers ADD COLUMN customer_time_expiry DATETIME NULL COMMENT 'วันที่หมดอายุของลูกค้า'";
        }
        
        if (in_array('customer_time_extension', $missingColumns)) {
            $sqlCommands[] = "ALTER TABLE customers ADD COLUMN customer_time_extension INT DEFAULT 0 COMMENT 'จำนวนวันที่ต่อเวลา'";
        }
        
        foreach ($sqlCommands as $sql) {
            try {
                echo "<p>รัน: <code>" . htmlspecialchars($sql) . "</code></p>";
                $db->execute($sql);
                echo "<p>✅ สำเร็จ</p>";
            } catch (Exception $e) {
                echo "<p>❌ ผิดพลาด: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<h2>3. เพิ่ม Index</h2>";
        
        $indexCommands = [
            "CREATE INDEX idx_customers_basket_type ON customers(basket_type)",
            "CREATE INDEX idx_customers_assigned_at ON customers(assigned_at)",
            "CREATE INDEX idx_customers_assigned_to ON customers(assigned_to)",
            "CREATE INDEX idx_customers_recall_at ON customers(recall_at)"
        ];
        
        foreach ($indexCommands as $sql) {
            try {
                echo "<p>รัน: <code>" . htmlspecialchars($sql) . "</code></p>";
                $db->execute($sql);
                echo "<p>✅ สำเร็จ</p>";
            } catch (Exception $e) {
                echo "<p>⚠️ อาจมี Index อยู่แล้ว: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<h2>4. อัปเดตข้อมูลเริ่มต้น</h2>";
        
        // ตรวจสอบว่ามีคอลัมน์ assigned_to หรือไม่
        $hasAssignedTo = in_array('assigned_to', $existingColumns) || in_array('assigned_to', $requiredColumns);
        
        if ($hasAssignedTo) {
            try {
                // อัปเดตลูกค้าที่มี assigned_to ให้เป็น assigned
                $sql1 = "UPDATE customers 
                        SET basket_type = 'assigned', 
                            assigned_at = COALESCE(assigned_at, created_at, NOW())
                        WHERE assigned_to IS NOT NULL AND (basket_type IS NULL OR basket_type = 'distribution')";
                
                echo "<p>รัน: <code>" . htmlspecialchars($sql1) . "</code></p>";
                $result1 = $db->execute($sql1);
                echo "<p>✅ อัปเดต assigned customers: " . $result1 . " รายการ</p>";
                
                // อัปเดตลูกค้าที่ไม่มี assigned_to ให้เป็น distribution
                $sql2 = "UPDATE customers 
                        SET basket_type = 'distribution'
                        WHERE assigned_to IS NULL AND (basket_type IS NULL OR basket_type = '')";
                
                echo "<p>รัน: <code>" . htmlspecialchars($sql2) . "</code></p>";
                $result2 = $db->execute($sql2);
                echo "<p>✅ อัปเดต distribution customers: " . $result2 . " รายการ</p>";
                
            } catch (Exception $e) {
                echo "<p>❌ ผิดพลาดในการอัปเดตข้อมูล: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h2>5. ตรวจสอบผลลัพธ์</h2>";
    
    // ตรวจสอบโครงสร้างใหม่
    $newStructure = $db->fetchAll("DESCRIBE customers");
    $newColumns = array_column($newStructure, 'Field');
    $stillMissing = array_diff($requiredColumns, $newColumns);
    
    if (empty($stillMissing)) {
        echo "<p>✅ โครงสร้างตารางครบถ้วนแล้ว</p>";
        
        // แสดงสถิติ
        $stats = $db->fetchAll("SELECT basket_type, COUNT(*) as count FROM customers GROUP BY basket_type");
        echo "<h3>สถิติ Basket Type:</h3>";
        echo "<ul>";
        foreach ($stats as $stat) {
            echo "<li><strong>" . htmlspecialchars($stat['basket_type'] ?? 'NULL') . ":</strong> " . $stat['count'] . " รายการ</li>";
        }
        echo "</ul>";
        
        // ทดสอบ WorkflowService
        echo "<h3>ทดสอบ WorkflowService:</h3>";
        require_once __DIR__ . '/app/services/WorkflowService.php';
        $workflowService = new WorkflowService();
        $workflowStats = $workflowService->getWorkflowStats();
        
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        print_r($workflowStats);
        echo "</pre>";
        
    } else {
        echo "<p>❌ ยังขาดคอลัมน์: " . implode(', ', $stillMissing) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<p><em>หมายเหตุ: ไฟล์นี้ใช้สำหรับการติดตั้งเท่านั้น ควรลบออกหลังจากใช้งานแล้ว</em></p>";
?>
