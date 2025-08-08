<?php
/**
 * Fix Import Calculation Issues
 * แก้ไขปัญหาการคำนวณ total_amount และ net_amount ในการ import ข้อมูล CSV
 */

require_once 'app/core/Database.php';

$db = new Database();

echo "<h2>แก้ไขปัญหาการคำนวณ total_amount และ net_amount</h2>";

// ตรวจสอบปัญหาก่อนแก้ไข
echo "<h3>1. ตรวจสอบปัญหาก่อนแก้ไข</h3>";

// ตรวจสอบคำสั่งซื้อที่ total_amount ไม่เท่ากับ net_amount
$query = "
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN total_amount != net_amount THEN 1 END) as mismatched_orders,
        COUNT(CASE WHEN total_amount = net_amount THEN 1 END) as correct_orders
    FROM orders 
    WHERE order_number LIKE 'EXT-%'
";

$orderStats = $db->fetchOne($query);

echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
echo "<h4>สถิติคำสั่งซื้อ:</h4>";
echo "<ul>";
echo "<li>คำสั่งซื้อทั้งหมด: " . number_format($orderStats['total_orders']) . "</li>";
echo "<li>คำสั่งซื้อที่ total_amount ≠ net_amount: " . number_format($orderStats['mismatched_orders']) . "</li>";
echo "<li>คำสั่งซื้อที่ถูกต้อง: " . number_format($orderStats['correct_orders']) . "</li>";
echo "</ul>";
echo "</div>";

// ตรวจสอบลูกค้าที่มียอดรวมไม่ตรงกัน
$query = "
    SELECT 
        COUNT(*) as total_customers,
        COUNT(CASE WHEN ABS(c.total_purchase_amount - COALESCE(order_totals.calculated_total, 0)) > 0.01 THEN 1 END) as mismatched_customers
    FROM customers c
    LEFT JOIN (
        SELECT 
            customer_id,
            SUM(total_amount) as calculated_total
        FROM orders 
        WHERE order_number LIKE 'EXT-%'
        GROUP BY customer_id
    ) order_totals ON c.customer_id = order_totals.customer_id
";

$customerStats = $db->fetchOne($query);

echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
echo "<h4>สถิติลูกค้า:</h4>";
echo "<ul>";
echo "<li>ลูกค้าทั้งหมด: " . number_format($customerStats['total_customers']) . "</li>";
echo "<li>ลูกค้าที่ยอดรวมไม่ตรงกัน: " . number_format($customerStats['mismatched_customers']) . "</li>";
echo "</ul>";
echo "</div>";

// แสดงตัวอย่างปัญหาที่พบ
echo "<h3>2. ตัวอย่างปัญหาที่พบ</h3>";

$query = "
    SELECT 
        o.order_id,
        o.order_number,
        o.total_amount,
        o.net_amount,
        o.created_at,
        CONCAT(c.first_name, ' ', c.last_name) as customer_name,
        c.phone
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    WHERE o.order_number LIKE 'EXT-%'
    AND o.total_amount != o.net_amount
    ORDER BY o.created_at DESC
    LIMIT 10
";

$problemOrders = $db->fetchAll($query);

if (!empty($problemOrders)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Order ID</th>";
    echo "<th>Order Number</th>";
    echo "<th>Customer</th>";
    echo "<th>Phone</th>";
    echo "<th>Total Amount</th>";
    echo "<th>Net Amount</th>";
    echo "<th>Difference</th>";
    echo "<th>Created At</th>";
    echo "</tr>";
    
    foreach ($problemOrders as $order) {
        $difference = $order['total_amount'] - $order['net_amount'];
        echo "<tr>";
        echo "<td>" . htmlspecialchars($order['order_id']) . "</td>";
        echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
        echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
        echo "<td>" . htmlspecialchars($order['phone']) . "</td>";
        echo "<td>" . number_format($order['total_amount'], 2) . "</td>";
        echo "<td>" . number_format($order['net_amount'], 2) . "</td>";
        echo "<td>" . number_format($difference, 2) . "</td>";
        echo "<td>" . htmlspecialchars($order['created_at']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>ไม่พบปัญหาการคำนวณ total_amount และ net_amount</p>";
}

// ปุ่มแก้ไขปัญหา
echo "<h3>3. แก้ไขปัญหา</h3>";

echo "<form method='POST' style='margin-bottom: 20px;'>";
echo "<input type='hidden' name='action' value='fix_calculation'>";
echo "<button type='submit' style='background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;'>";
echo "แก้ไขปัญหาการคำนวณ";
echo "</button>";
echo "</form>";

// จัดการการแก้ไขปัญหา
if ($_POST['action'] ?? '' === 'fix_calculation') {
    echo "<h3>กำลังแก้ไขปัญหา...</h3>";
    
    try {
        $db->beginTransaction();
        
        // 1. แก้ไข net_amount ให้เท่ากับ total_amount สำหรับคำสั่งซื้อที่ import จาก CSV
        $updateQuery = "
            UPDATE orders 
            SET net_amount = total_amount 
            WHERE order_number LIKE 'EXT-%' 
            AND net_amount != total_amount
        ";
        
        $result = $db->query($updateQuery);
        $affectedRows = $db->rowCount();
        echo "<p>✅ แก้ไข net_amount ให้ตรงกับ total_amount สำเร็จ: " . $affectedRows . " รายการ</p>";
        
        // 2. อัปเดตยอดรวมลูกค้าจากข้อมูล orders
        $updateCustomerTotalQuery = "
            UPDATE customers c
            SET total_purchase_amount = (
                SELECT COALESCE(SUM(o.total_amount), 0)
                FROM orders o
                WHERE o.customer_id = c.customer_id
                AND o.order_number LIKE 'EXT-%'
            )
        ";
        
        $result = $db->query($updateCustomerTotalQuery);
        $affectedRows = $db->rowCount();
        echo "<p>✅ อัปเดตยอดรวมลูกค้าสำเร็จ: " . $affectedRows . " รายการ</p>";
        
        // 3. แก้ไขข้อมูลในตาราง order_items (ถ้ามี)
        if ($db->tableExists('order_items')) {
            $updateOrderItemsQuery = "
                UPDATE order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                SET oi.total_price = o.total_amount
                WHERE o.order_number LIKE 'EXT-%'
                AND oi.total_price != o.total_amount
            ";
            
            $result = $db->query($updateOrderItemsQuery);
            $affectedRows = $db->rowCount();
            echo "<p>✅ แก้ไขข้อมูล order_items สำเร็จ: " . $affectedRows . " รายการ</p>";
        }
        
        // 4. ลบคำสั่งซื้อที่ซ้ำกัน (ถ้ามี)
        $deleteDuplicatesQuery = "
            DELETE o1 FROM orders o1
            INNER JOIN orders o2
            WHERE o1.order_id < o2.order_id
            AND o1.customer_id = o2.customer_id
            AND o1.created_at = o2.created_at
            AND o1.order_number LIKE 'EXT-%'
            AND o2.order_number LIKE 'EXT-%'
        ";
        
        $result = $db->query($deleteDuplicatesQuery);
        $affectedRows = $db->rowCount();
        if ($affectedRows > 0) {
            echo "<p>✅ ลบคำสั่งซื้อที่ซ้ำกันสำเร็จ: " . $affectedRows . " รายการ</p>";
        } else {
            echo "<p>✅ ไม่พบคำสั่งซื้อที่ซ้ำกัน</p>";
        }
        
        $db->commit();
        
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>✅ แก้ไขปัญหาเสร็จสิ้น</h4>";
        echo "<p>การแก้ไขปัญหาการคำนวณ total_amount และ net_amount เสร็จสิ้นแล้ว</p>";
        echo "</div>";
        
        echo "<script>setTimeout(() => window.location.reload(), 3000);</script>";
        
    } catch (Exception $e) {
        $db->rollback();
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>❌ เกิดข้อผิดพลาด</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}

// ตรวจสอบผลลัพธ์หลังแก้ไข
if ($_POST['action'] ?? '' === 'fix_calculation') {
    echo "<h3>4. ผลลัพธ์หลังแก้ไข</h3>";
    
    // ตรวจสอบคำสั่งซื้อที่ total_amount ไม่เท่ากับ net_amount
    $query = "
        SELECT 
            COUNT(*) as total_orders,
            COUNT(CASE WHEN total_amount != net_amount THEN 1 END) as mismatched_orders,
            COUNT(CASE WHEN total_amount = net_amount THEN 1 END) as correct_orders
        FROM orders 
        WHERE order_number LIKE 'EXT-%'
    ";
    
    $orderStatsAfter = $db->fetchOne($query);
    
    echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<h4>สถิติหลังแก้ไข:</h4>";
    echo "<ul>";
    echo "<li>คำสั่งซื้อทั้งหมด: " . number_format($orderStatsAfter['total_orders']) . "</li>";
    echo "<li>คำสั่งซื้อที่ total_amount ≠ net_amount: " . number_format($orderStatsAfter['mismatched_orders']) . "</li>";
    echo "<li>คำสั่งซื้อที่ถูกต้อง: " . number_format($orderStatsAfter['correct_orders']) . "</li>";
    echo "</ul>";
    echo "</div>";
}

// แนวทางป้องกันปัญหาในอนาคต
echo "<h3>5. แนวทางป้องกันปัญหาในอนาคต</h3>";

echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
echo "<h4>คำแนะนำ:</h4>";
echo "<ul>";
echo "<li><strong>ตรวจสอบ CSV ก่อนนำเข้า:</strong> ตรวจสอบว่าคอลัมน์ 'ยอดรวม' มีข้อมูลถูกต้อง</li>";
echo "<li><strong>ใช้ Template ที่ถูกต้อง:</strong> ใช้ Template ที่มีคอลัมน์ครบถ้วน</li>";
echo "<li><strong>ตรวจสอบข้อมูลหลังนำเข้า:</strong> ตรวจสอบยอดรวมหลังนำเข้าทุกครั้ง</li>";
echo "<li><strong>หลีกเลี่ยงการนำเข้าซ้ำ:</strong> ตรวจสอบว่าลูกค้ามีอยู่แล้วก่อนนำเข้า</li>";
echo "</ul>";
echo "</div>";

// ตรวจสอบ Template ที่แนะนำ
echo "<h3>6. Template ที่แนะนำสำหรับการนำเข้า</h3>";

echo "<div style='background-color: #e2e3e5; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
echo "<h4>คอลัมน์ที่จำเป็นใน CSV:</h4>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>คอลัมน์</th>";
echo "<th>คำอธิบาย</th>";
echo "<th>ตัวอย่าง</th>";
echo "</tr>";
echo "<tr>";
echo "<td>ชื่อ</td>";
echo "<td>ชื่อลูกค้า</td>";
echo "<td>สมชาย</td>";
echo "</tr>";
echo "<tr>";
echo "<td>นามสกุล</td>";
echo "<td>นามสกุลลูกค้า</td>";
echo "<td>ใจดี</td>";
echo "</tr>";
echo "<tr>";
echo "<td>เบอร์โทรศัพท์</td>";
echo "<td>เบอร์โทรศัพท์ลูกค้า</td>";
echo "<td>0812345678</td>";
echo "</tr>";
echo "<tr>";
echo "<td>ชื่อสินค้า</td>";
echo "<td>ชื่อสินค้าที่ขาย</td>";
echo "<td>สินค้า A</td>";
echo "</tr>";
echo "<tr>";
echo "<td>จำนวน</td>";
echo "<td>จำนวนสินค้า</td>";
echo "<td>2</td>";
echo "</tr>";
echo "<tr>";
echo "<td>ราคาต่อชิ้น</td>";
echo "<td>ราคาต่อชิ้นสินค้า</td>";
echo "<td>500.00</td>";
echo "</tr>";
echo "<tr>";
echo "<td>ยอดรวม</td>";
echo "<td>ยอดรวมทั้งหมด (สำคัญมาก)</td>";
echo "<td>1000.00</td>";
echo "</tr>";
echo "<tr>";
echo "<td>วันที่สั่งซื้อ</td>";
echo "<td>วันที่สั่งซื้อ</td>";
echo "<td>2024-01-15</td>";
echo "</tr>";
echo "<tr>";
echo "<td>วิธีการชำระเงิน</td>";
echo "<td>วิธีการชำระเงิน</td>";
echo "<td>เงินสด</td>";
echo "</tr>";
echo "<tr>";
echo "<td>สถานะการชำระเงิน</td>";
echo "<td>สถานะการชำระเงิน</td>";
echo "<td>ชำระแล้ว</td>";
echo "</tr>";
echo "</table>";
echo "</div>";

echo "<hr>";
echo "<h3>หมายเหตุ</h3>";
echo "<ul>";
echo "<li>ปัญหาหลักคือการคำนวณ total_amount และ net_amount ไม่ตรงกัน</li>";
echo "<li>ควรใช้คอลัมน์ 'ยอดรวม' ใน CSV แทนการคำนวณจาก จำนวน × ราคาต่อชิ้น</li>";
echo "<li>ตรวจสอบข้อมูลหลังนำเข้าเสมอเพื่อความถูกต้อง</li>";
echo "<li>หากยังมีปัญหา ให้ตรวจสอบ CSV ต้นฉบับอีกครั้ง</li>";
echo "</ul>";
?>
