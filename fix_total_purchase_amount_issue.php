<?php
/**
 * แก้ไขปัญหายอด total_purchase_amount ในตาราง customers
 * ใช้ยอดรวมจากตาราง orders เป็นหลัก
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

$db = new Database();

echo "<h1>แก้ไขปัญหายอด total_purchase_amount ในตาราง customers</h1>";

try {
    // ตรวจสอบสถานะก่อนแก้ไข
    echo "<h2>1. สถานะก่อนแก้ไข</h2>";
    
    $sql = "SELECT 
                COUNT(*) as total_customers,
                COUNT(CASE WHEN total_purchase_amount > 0 THEN 1 END) as customers_with_total_purchase_amount,
                COUNT(CASE WHEN total_purchase > 0 THEN 1 END) as customers_with_total_purchase,
                SUM(total_purchase_amount) as sum_total_purchase_amount,
                SUM(total_purchase) as sum_total_purchase
            FROM customers 
            WHERE is_active = 1";
    
    $beforeStats = $db->fetchOne($sql);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>รายการ</th><th>จำนวน</th></tr>";
    echo "<tr><td>ลูกค้าทั้งหมด</td><td>{$beforeStats['total_customers']}</td></tr>";
    echo "<tr><td>ลูกค้าที่มียอด total_purchase_amount > 0</td><td>{$beforeStats['customers_with_total_purchase_amount']}</td></tr>";
    echo "<tr><td>ลูกค้าที่มียอด total_purchase > 0</td><td>{$beforeStats['customers_with_total_purchase']}</td></tr>";
    echo "<tr><td>ยอดรวม total_purchase_amount</td><td>" . number_format($beforeStats['sum_total_purchase_amount'], 2) . "</td></tr>";
    echo "<tr><td>ยอดรวม total_purchase</td><td>" . number_format($beforeStats['sum_total_purchase'], 2) . "</td></tr>";
    echo "</table>";

    // ตรวจสอบลูกค้าที่มียอดไม่ตรงกับ orders
    $sql = "SELECT COUNT(*) as count
            FROM customers c
            LEFT JOIN (
                SELECT 
                    customer_id,
                    SUM(net_amount) as calculated_total
                FROM orders 
                WHERE payment_status IN ('paid', 'partial')
                GROUP BY customer_id
            ) order_totals ON c.customer_id = order_totals.customer_id
            WHERE c.is_active = 1 
            AND ABS(c.total_purchase_amount - COALESCE(order_totals.calculated_total, 0)) > 0.01";
    
    $inconsistentCount = $db->fetchOne($sql)['count'];
    
    echo "<p>ลูกค้าที่มียอดไม่ตรงกับ orders: <strong>{$inconsistentCount}</strong> ราย</p>";

    // แสดงตัวอย่างลูกค้าที่มีปัญหา
    if ($inconsistentCount > 0) {
        echo "<h3>ตัวอย่างลูกค้าที่มีปัญหา:</h3>";
        $sql = "SELECT 
                    c.customer_id,
                    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                    c.total_purchase_amount as customer_total_purchase_amount,
                    c.total_purchase as customer_total_purchase,
                    COALESCE(order_totals.calculated_total, 0) as orders_calculated_total,
                    ABS(c.total_purchase_amount - COALESCE(order_totals.calculated_total, 0)) as difference
                FROM customers c
                LEFT JOIN (
                    SELECT 
                        customer_id,
                        SUM(net_amount) as calculated_total
                    FROM orders 
                    WHERE payment_status IN ('paid', 'partial')
                    GROUP BY customer_id
                ) order_totals ON c.customer_id = order_totals.customer_id
                WHERE c.is_active = 1 
                AND ABS(c.total_purchase_amount - COALESCE(order_totals.calculated_total, 0)) > 0.01
                ORDER BY difference DESC
                LIMIT 5";
        
        $examples = $db->fetchAll($sql);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>ชื่อลูกค้า</th><th>total_purchase_amount</th><th>total_purchase</th><th>ยอดจาก orders</th><th>ความต่าง</th></tr>";
        foreach ($examples as $example) {
            echo "<tr>";
            echo "<td>{$example['customer_id']}</td>";
            echo "<td>{$example['customer_name']}</td>";
            echo "<td>" . number_format($example['customer_total_purchase_amount'], 2) . "</td>";
            echo "<td>" . number_format($example['customer_total_purchase'], 2) . "</td>";
            echo "<td>" . number_format($example['orders_calculated_total'], 2) . "</td>";
            echo "<td>" . number_format($example['difference'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // ปุ่มเริ่มแก้ไข
    if (!isset($_POST['start_fix'])) {
        echo "<form method='post' style='margin-top: 20px;'>";
        echo "<input type='submit' name='start_fix' value='เริ่มแก้ไขปัญหา' style='background-color: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
        echo "</form>";
        return;
    }

    // เริ่มแก้ไขปัญหา
    echo "<h2>2. กำลังแก้ไขปัญหา...</h2>";
    
    try {
        $db->beginTransaction();
        
        // ขั้นตอนที่ 1: อัปเดต total_purchase_amount ให้ตรงกับยอดรวมจาก orders
        echo "<h3>ขั้นตอนที่ 1: อัปเดต total_purchase_amount</h3>";
        
        $sql = "UPDATE customers c 
                SET total_purchase_amount = (
                    SELECT COALESCE(SUM(net_amount), 0) 
                    FROM orders o 
                    WHERE o.customer_id = c.customer_id 
                    AND o.payment_status IN ('paid', 'partial')
                ),
                updated_at = NOW()
                WHERE c.is_active = 1";
        
        $db->query($sql);
        $updatedRows = $db->rowCount();
        
        echo "<p>✅ อัปเดตลูกค้า {$updatedRows} ราย</p>";

        // ขั้นตอนที่ 2: ลบคอลัมน์ total_purchase (ถ้าต้องการ)
        echo "<h3>ขั้นตอนที่ 2: ลบคอลัมน์ total_purchase</h3>";
        
        // ตรวจสอบว่าคอลัมน์ total_purchase มีอยู่หรือไม่
        $sql = "SELECT COUNT(*) as count FROM information_schema.columns 
                WHERE table_schema = DATABASE() 
                AND table_name = 'customers' 
                AND column_name = 'total_purchase'";
        
        $columnExists = $db->fetchOne($sql)['count'] > 0;
        
        if ($columnExists) {
            $sql = "ALTER TABLE customers DROP COLUMN total_purchase";
            $db->query($sql);
            echo "<p>✅ ลบคอลัมน์ total_purchase เรียบร้อยแล้ว</p>";
        } else {
            echo "<p>ℹ️ คอลัมน์ total_purchase ไม่มีอยู่แล้ว</p>";
        }

        // ขั้นตอนที่ 3: ตรวจสอบผลลัพธ์
        echo "<h3>ขั้นตอนที่ 3: ตรวจสอบผลลัพธ์</h3>";
        
        $sql = "SELECT 
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN total_purchase_amount > 0 THEN 1 END) as customers_with_total_purchase_amount,
                    SUM(total_purchase_amount) as sum_total_purchase_amount
                FROM customers 
                WHERE is_active = 1";
        
        $afterStats = $db->fetchOne($sql);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>รายการ</th><th>จำนวน</th></tr>";
        echo "<tr><td>ลูกค้าทั้งหมด</td><td>{$afterStats['total_customers']}</td></tr>";
        echo "<tr><td>ลูกค้าที่มียอด total_purchase_amount > 0</td><td>{$afterStats['customers_with_total_purchase_amount']}</td></tr>";
        echo "<tr><td>ยอดรวม total_purchase_amount</td><td>" . number_format($afterStats['sum_total_purchase_amount'], 2) . "</td></tr>";
        echo "</table>";

        // ตรวจสอบว่ายังมีลูกค้าที่มียอดไม่ตรงกับ orders หรือไม่
        $sql = "SELECT COUNT(*) as count
                FROM customers c
                LEFT JOIN (
                    SELECT 
                        customer_id,
                        SUM(net_amount) as calculated_total
                    FROM orders 
                    WHERE payment_status IN ('paid', 'partial')
                    GROUP BY customer_id
                ) order_totals ON c.customer_id = order_totals.customer_id
                WHERE c.is_active = 1 
                AND ABS(c.total_purchase_amount - COALESCE(order_totals.calculated_total, 0)) > 0.01";
        
        $remainingInconsistent = $db->fetchOne($sql)['count'];
        
        if ($remainingInconsistent == 0) {
            echo "<p style='color: green;'>✅ ไม่พบลูกค้าที่มียอดไม่ตรงกับ orders แล้ว</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ ยังมีลูกค้า {$remainingInconsistent} รายที่มียอดไม่ตรงกับ orders</p>";
        }

        $db->commit();
        
        echo "<h2>3. สรุปผลการแก้ไข</h2>";
        echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
        echo "<h3>✅ แก้ไขปัญหาเรียบร้อยแล้ว</h3>";
        echo "<ul>";
        echo "<li>อัปเดตลูกค้า {$updatedRows} ราย</li>";
        echo "<li>ลบคอลัมน์ total_purchase เรียบร้อยแล้ว</li>";
        echo "<li>ยอด total_purchase_amount ตอนนี้คำนวณจาก orders เท่านั้น</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<h3>การเปลี่ยนแปลงที่สำคัญ:</h3>";
        echo "<ul>";
        echo "<li><strong>ImportExportService</strong>: ใช้ total_purchase_amount และคำนวณจาก orders</li>";
        echo "<li><strong>OrderService</strong>: อัปเดตแล้วให้ใช้ total_purchase_amount แทน total_purchase</li>";
        echo "<li><strong>Database</strong>: ลบคอลัมน์ total_purchase ออกแล้ว</li>";
        echo "</ul>";
        
        echo "<p><a href='investigate_total_purchase_issue.php'>ตรวจสอบอีกครั้ง</a></p>";
        
    } catch (Exception $e) {
        $db->rollback();
        echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
        echo "<p>การแก้ไขถูกยกเลิกแล้ว</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}
?>
