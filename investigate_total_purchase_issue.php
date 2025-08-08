<?php
/**
 * ตรวจสอบปัญหายอด total_purchase_amount ในตาราง customers
 * เปรียบเทียบกับยอดรวมจากตาราง orders
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

$db = new Database();

echo "<h1>ตรวจสอบปัญหายอด total_purchase_amount ในตาราง customers</h1>";

try {
    // 1. ตรวจสอบโครงสร้างตาราง customers
    echo "<h2>1. โครงสร้างตาราง customers</h2>";
    $sql = "DESCRIBE customers";
    $columns = $db->fetchAll($sql);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 2. ตรวจสอบสถิติทั่วไป
    echo "<h2>2. สถิติทั่วไป</h2>";
    $sql = "SELECT 
                COUNT(*) as total_customers,
                COUNT(CASE WHEN total_purchase_amount > 0 THEN 1 END) as customers_with_total_purchase_amount,
                COUNT(CASE WHEN total_purchase > 0 THEN 1 END) as customers_with_total_purchase,
                SUM(total_purchase_amount) as sum_total_purchase_amount,
                SUM(total_purchase) as sum_total_purchase
            FROM customers 
            WHERE is_active = 1";
    
    $stats = $db->fetchOne($sql);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>รายการ</th><th>จำนวน</th></tr>";
    echo "<tr><td>ลูกค้าทั้งหมด</td><td>{$stats['total_customers']}</td></tr>";
    echo "<tr><td>ลูกค้าที่มียอด total_purchase_amount > 0</td><td>{$stats['customers_with_total_purchase_amount']}</td></tr>";
    echo "<tr><td>ลูกค้าที่มียอด total_purchase > 0</td><td>{$stats['customers_with_total_purchase']}</td></tr>";
    echo "<tr><td>ยอดรวม total_purchase_amount</td><td>" . number_format($stats['sum_total_purchase_amount'], 2) . "</td></tr>";
    echo "<tr><td>ยอดรวม total_purchase</td><td>" . number_format($stats['sum_total_purchase'], 2) . "</td></tr>";
    echo "</table>";

    // 3. ตรวจสอบลูกค้าที่มียอดต่างกัน
    echo "<h2>3. ลูกค้าที่มียอด total_purchase_amount ต่างจาก total_purchase</h2>";
    $sql = "SELECT 
                customer_id,
                CONCAT(first_name, ' ', last_name) as customer_name,
                total_purchase_amount,
                total_purchase,
                ABS(total_purchase_amount - total_purchase) as difference
            FROM customers 
            WHERE is_active = 1 
            AND ABS(total_purchase_amount - total_purchase) > 0.01
            ORDER BY difference DESC";
    
    $mismatched = $db->fetchAll($sql);
    
    if (empty($mismatched)) {
        echo "<p style='color: green;'>✅ ไม่พบลูกค้าที่มียอดต่างกัน</p>";
    } else {
        echo "<p style='color: red;'>❌ พบลูกค้า " . count($mismatched) . " รายที่มียอดต่างกัน</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>ชื่อลูกค้า</th><th>total_purchase_amount</th><th>total_purchase</th><th>ความต่าง</th></tr>";
        foreach ($mismatched as $customer) {
            echo "<tr>";
            echo "<td>{$customer['customer_id']}</td>";
            echo "<td>{$customer['customer_name']}</td>";
            echo "<td>" . number_format($customer['total_purchase_amount'], 2) . "</td>";
            echo "<td>" . number_format($customer['total_purchase'], 2) . "</td>";
            echo "<td>" . number_format($customer['difference'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // 4. ตรวจสอบยอดรวมจากตาราง orders
    echo "<h2>4. ยอดรวมจากตาราง orders</h2>";
    $sql = "SELECT 
                COUNT(DISTINCT customer_id) as customers_with_orders,
                SUM(net_amount) as total_net_amount,
                SUM(total_amount) as total_amount,
                COUNT(*) as total_orders
            FROM orders 
            WHERE payment_status IN ('paid', 'partial')";
    
    $orderStats = $db->fetchOne($sql);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>รายการ</th><th>จำนวน</th></tr>";
    echo "<tr><td>ลูกค้าที่มีคำสั่งซื้อ</td><td>{$orderStats['customers_with_orders']}</td></tr>";
    echo "<tr><td>ยอดรวม net_amount</td><td>" . number_format($orderStats['total_net_amount'], 2) . "</td></tr>";
    echo "<tr><td>ยอดรวม total_amount</td><td>" . number_format($orderStats['total_amount'], 2) . "</td></tr>";
    echo "<tr><td>จำนวนคำสั่งซื้อทั้งหมด</td><td>{$orderStats['total_orders']}</td></tr>";
    echo "</table>";

    // 5. ตรวจสอบลูกค้าที่มียอดใน customers ไม่ตรงกับ orders
    echo "<h2>5. ลูกค้าที่มียอดใน customers ไม่ตรงกับ orders</h2>";
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
            ORDER BY difference DESC";
    
    $inconsistent = $db->fetchAll($sql);
    
    if (empty($inconsistent)) {
        echo "<p style='color: green;'>✅ ไม่พบลูกค้าที่มียอดไม่ตรงกับ orders</p>";
    } else {
        echo "<p style='color: red;'>❌ พบลูกค้า " . count($inconsistent) . " รายที่มียอดไม่ตรงกับ orders</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>ชื่อลูกค้า</th><th>total_purchase_amount</th><th>total_purchase</th><th>ยอดจาก orders</th><th>ความต่าง</th></tr>";
        foreach ($inconsistent as $customer) {
            echo "<tr>";
            echo "<td>{$customer['customer_id']}</td>";
            echo "<td>{$customer['customer_name']}</td>";
            echo "<td>" . number_format($customer['customer_total_purchase_amount'], 2) . "</td>";
            echo "<td>" . number_format($customer['customer_total_purchase'], 2) . "</td>";
            echo "<td>" . number_format($customer['orders_calculated_total'], 2) . "</td>";
            echo "<td>" . number_format($customer['difference'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // 6. ตัวอย่างข้อมูลลูกค้าและคำสั่งซื้อ
    echo "<h2>6. ตัวอย่างข้อมูลลูกค้าและคำสั่งซื้อ (5 รายแรก)</h2>";
    $sql = "SELECT 
                c.customer_id,
                CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                c.total_purchase_amount,
                c.total_purchase,
                COUNT(o.order_id) as order_count,
                COALESCE(SUM(o.net_amount), 0) as orders_total
            FROM customers c
            LEFT JOIN orders o ON c.customer_id = o.customer_id AND o.payment_status IN ('paid', 'partial')
            WHERE c.is_active = 1
            GROUP BY c.customer_id
            ORDER BY c.customer_id
            LIMIT 5";
    
    $examples = $db->fetchAll($sql);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>ชื่อลูกค้า</th><th>total_purchase_amount</th><th>total_purchase</th><th>จำนวนคำสั่งซื้อ</th><th>ยอดจาก orders</th></tr>";
    foreach ($examples as $example) {
        echo "<tr>";
        echo "<td>{$example['customer_id']}</td>";
        echo "<td>{$example['customer_name']}</td>";
        echo "<td>" . number_format($example['total_purchase_amount'], 2) . "</td>";
        echo "<td>" . number_format($example['total_purchase'], 2) . "</td>";
        echo "<td>{$example['order_count']}</td>";
        echo "<td>" . number_format($example['orders_total'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 7. สรุปปัญหาและแนวทางแก้ไข
    echo "<h2>7. สรุปปัญหาและแนวทางแก้ไข</h2>";
    
    $totalMismatched = count($mismatched);
    $totalInconsistent = count($inconsistent);
    
    if ($totalMismatched == 0 && $totalInconsistent == 0) {
        echo "<p style='color: green;'>✅ ระบบทำงานปกติ ไม่พบปัญหา</p>";
    } else {
        echo "<div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px;'>";
        echo "<h3>ปัญหาที่พบ:</h3>";
        echo "<ul>";
        if ($totalMismatched > 0) {
            echo "<li>พบลูกค้า {$totalMismatched} รายที่มียอด total_purchase_amount ต่างจาก total_purchase</li>";
        }
        if ($totalInconsistent > 0) {
            echo "<li>พบลูกค้า {$totalInconsistent} รายที่มียอดใน customers ไม่ตรงกับยอดรวมจาก orders</li>";
        }
        echo "</ul>";
        
        echo "<h3>สาเหตุ:</h3>";
        echo "<ul>";
        echo "<li>ImportExportService อัปเดต total_purchase_amount โดยคำนวณจาก orders</li>";
        echo "<li>OrderService อัปเดต total_purchase โดยเพิ่มยอดโดยตรง</li>";
        echo "<li>เกิดความไม่สอดคล้องระหว่างสองคอลัมน์</li>";
        echo "</ul>";
        
        echo "<h3>แนวทางแก้ไข:</h3>";
        echo "<ul>";
        echo "<li>ใช้คอลัมน์เดียว (total_purchase_amount) และคำนวณจาก orders เสมอ</li>";
        echo "<li>ลบหรือไม่ใช้คอลัมน์ total_purchase</li>";
        echo "<li>อัปเดต OrderService ให้ใช้ total_purchase_amount แทน</li>";
        echo "</ul>";
        echo "</div>";
        
        // ปุ่มแก้ไขปัญหา
        echo "<form method='post' style='margin-top: 20px;'>";
        echo "<input type='submit' name='fix_issue' value='แก้ไขปัญหา' style='background-color: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
        echo "</form>";
    }

    // จัดการการแก้ไขปัญหา
    if (isset($_POST['fix_issue'])) {
        echo "<h2>8. กำลังแก้ไขปัญหา...</h2>";
        
        try {
            $db->beginTransaction();
            
            // อัปเดต total_purchase_amount ให้ตรงกับยอดรวมจาก orders
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
            
            // ลบคอลัมน์ total_purchase (ถ้าต้องการ)
            // $sql = "ALTER TABLE customers DROP COLUMN total_purchase";
            // $db->query($sql);
            
            $db->commit();
            
            echo "<p style='color: green;'>✅ แก้ไขปัญหาเรียบร้อยแล้ว</p>";
            echo "<p>อัปเดตลูกค้า {$updatedRows} ราย</p>";
            echo "<p><a href='investigate_total_purchase_issue.php'>ตรวจสอบอีกครั้ง</a></p>";
            
        } catch (Exception $e) {
            $db->rollback();
            echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}
?>
