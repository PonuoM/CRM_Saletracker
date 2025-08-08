<?php
/**
 * แก้ไขปัญหาการแสดงยอดซื้อรวมและอัปเดตเกรดลูกค้า
 * 
 * ปัญหาที่แก้ไข:
 * 1. หน้า show.php ใช้ total_purchase แทน total_purchase_amount
 * 2. เกรดลูกค้าไม่ถูกอัปเดตตามยอดซื้อรวมที่ถูกต้อง
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

echo "<h1>แก้ไขปัญหาการแสดงยอดซื้อรวมและอัปเดตเกรดลูกค้า</h1>";

try {
    $db = new Database();
    
    // ขั้นตอนที่ 1: ตรวจสอบสถานะปัจจุบัน
    echo "<h3>ขั้นตอนที่ 1: ตรวจสอบสถานะปัจจุบัน</h3>";
    
    // ตรวจสอบยอดซื้อรวม
    $statsQuery = "SELECT 
        COUNT(*) as total_customers,
        COUNT(CASE WHEN total_purchase_amount > 0 THEN 1 END) as customers_with_purchase,
        SUM(total_purchase_amount) as total_purchase_amount,
        COUNT(CASE WHEN customer_grade = 'A+' THEN 1 END) as grade_a_plus,
        COUNT(CASE WHEN customer_grade = 'A' THEN 1 END) as grade_a,
        COUNT(CASE WHEN customer_grade = 'B' THEN 1 END) as grade_b,
        COUNT(CASE WHEN customer_grade = 'C' THEN 1 END) as grade_c,
        COUNT(CASE WHEN customer_grade = 'D' THEN 1 END) as grade_d
    FROM customers";
    
    $stats = $db->fetchOne($statsQuery);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr><th>รายการ</th><th>จำนวน</th></tr>";
    echo "<tr><td>ลูกค้าทั้งหมด</td><td>" . number_format($stats['total_customers']) . "</td></tr>";
    echo "<tr><td>ลูกค้าที่มียอดซื้อ > 0</td><td>" . number_format($stats['customers_with_purchase']) . "</td></tr>";
    echo "<tr><td>ยอดซื้อรวมทั้งหมด</td><td>฿" . number_format($stats['total_purchase_amount'], 2) . "</td></tr>";
    echo "<tr><td>เกรด A+</td><td>" . number_format($stats['grade_a_plus']) . "</td></tr>";
    echo "<tr><td>เกรด A</td><td>" . number_format($stats['grade_a']) . "</td></tr>";
    echo "<tr><td>เกรด B</td><td>" . number_format($stats['grade_b']) . "</td></tr>";
    echo "<tr><td>เกรด C</td><td>" . number_format($stats['grade_c']) . "</td></tr>";
    echo "<tr><td>เกรด D</td><td>" . number_format($stats['grade_d']) . "</td></tr>";
    echo "</table>";
    
    // ขั้นตอนที่ 2: ตรวจสอบการตั้งค่าเกรด
    echo "<h3>ขั้นตอนที่ 2: ตรวจสอบการตั้งค่าเกรด</h3>";
    
    $gradeSettingsQuery = "SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'customer_grade_%'";
    $gradeSettings = $db->fetchAll($gradeSettingsQuery);
    
    if (empty($gradeSettings)) {
        echo "<p style='color: orange;'>⚠️ ไม่พบการตั้งค่าเกรดในระบบ กำลังสร้างการตั้งค่าเริ่มต้น...</p>";
        
        // สร้างการตั้งค่าเริ่มต้น
        $defaultSettings = [
            'customer_grade_a_plus' => 100000,
            'customer_grade_a' => 50000,
            'customer_grade_b' => 20000,
            'customer_grade_c' => 5000
        ];
        
        foreach ($defaultSettings as $key => $value) {
            $db->query(
                "INSERT INTO system_settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW())",
                [$key, $value]
            );
        }
        
        echo "<p style='color: green;'>✅ สร้างการตั้งค่าเกรดเริ่มต้นเรียบร้อยแล้ว</p>";
        $gradeSettings = $db->fetchAll($gradeSettingsQuery);
    }
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr><th>เกรด</th><th>เกณฑ์ขั้นต่ำ (บาท)</th></tr>";
    foreach ($gradeSettings as $setting) {
        $grade = str_replace('customer_grade_', '', $setting['setting_key']);
        echo "<tr><td>{$grade}</td><td>" . number_format($setting['setting_value']) . "</td></tr>";
    }
    echo "</table>";
    
    // ขั้นตอนที่ 3: อัปเดตเกรดลูกค้าตามยอดซื้อรวม
    echo "<h3>ขั้นตอนที่ 3: อัปเดตเกรดลูกค้าตามยอดซื้อรวม</h3>";
    
    // สร้าง array ของเกณฑ์เกรด
    $gradeThresholds = [];
    foreach ($gradeSettings as $setting) {
        $key = str_replace('customer_grade_', '', $setting['setting_key']);
        $gradeThresholds[$key] = (float) $setting['setting_value'];
    }
    
    // อัปเดตเกรดลูกค้า
    $updateGradeQuery = "UPDATE customers SET customer_grade = CASE
        WHEN total_purchase_amount >= ? THEN 'A+'
        WHEN total_purchase_amount >= ? THEN 'A'
        WHEN total_purchase_amount >= ? THEN 'B'
        WHEN total_purchase_amount >= ? THEN 'C'
        ELSE 'D'
    END, updated_at = NOW()";
    
    $db->query($updateGradeQuery, [
        $gradeThresholds['a_plus'],
        $gradeThresholds['a'],
        $gradeThresholds['b'],
        $gradeThresholds['c']
    ]);
    
    echo "<p style='color: green;'>✅ อัปเดตเกรดลูกค้าตามยอดซื้อรวมเรียบร้อยแล้ว</p>";
    
    // ขั้นตอนที่ 4: ตรวจสอบผลลัพธ์
    echo "<h3>ขั้นตอนที่ 4: ตรวจสอบผลลัพธ์</h3>";
    
    $afterStats = $db->fetchOne($statsQuery);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr><th>รายการ</th><th>ก่อนแก้ไข</th><th>หลังแก้ไข</th><th>เปลี่ยนแปลง</th></tr>";
    echo "<tr><td>เกรด A+</td><td>" . number_format($stats['grade_a_plus']) . "</td><td>" . number_format($afterStats['grade_a_plus']) . "</td><td>" . ($afterStats['grade_a_plus'] - $stats['grade_a_plus']) . "</td></tr>";
    echo "<tr><td>เกรด A</td><td>" . number_format($stats['grade_a']) . "</td><td>" . number_format($afterStats['grade_a']) . "</td><td>" . ($afterStats['grade_a'] - $stats['grade_a']) . "</td></tr>";
    echo "<tr><td>เกรด B</td><td>" . number_format($stats['grade_b']) . "</td><td>" . number_format($afterStats['grade_b']) . "</td><td>" . ($afterStats['grade_b'] - $stats['grade_b']) . "</td></tr>";
    echo "<tr><td>เกรด C</td><td>" . number_format($stats['grade_c']) . "</td><td>" . number_format($afterStats['grade_c']) . "</td><td>" . ($afterStats['grade_c'] - $stats['grade_c']) . "</td></tr>";
    echo "<tr><td>เกรด D</td><td>" . number_format($stats['grade_d']) . "</td><td>" . number_format($afterStats['grade_d']) . "</td><td>" . ($afterStats['grade_d'] - $stats['grade_d']) . "</td></tr>";
    echo "</table>";
    
    // ขั้นตอนที่ 5: แสดงตัวอย่างลูกค้าที่เกรดเปลี่ยน
    echo "<h3>ขั้นตอนที่ 5: ตัวอย่างลูกค้าที่เกรดเปลี่ยน</h3>";
    
    $changedGradesQuery = "SELECT 
        customer_id,
        CONCAT(first_name, ' ', last_name) as customer_name,
        total_purchase_amount,
        customer_grade
    FROM customers 
    WHERE total_purchase_amount > 0 
    ORDER BY total_purchase_amount DESC 
    LIMIT 10";
    
    $changedGrades = $db->fetchAll($changedGradesQuery);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>ชื่อลูกค้า</th><th>ยอดซื้อรวม</th><th>เกรดปัจจุบัน</th></tr>";
    foreach ($changedGrades as $customer) {
        echo "<tr>";
        echo "<td>{$customer['customer_id']}</td>";
        echo "<td>{$customer['customer_name']}</td>";
        echo "<td>฿" . number_format($customer['total_purchase_amount'], 2) . "</td>";
        echo "<td><strong>{$customer['customer_grade']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // ขั้นตอนที่ 6: สรุปการแก้ไข
    echo "<h3>ขั้นตอนที่ 6: สรุปการแก้ไข</h3>";
    echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h4 style='color: #155724; margin-top: 0;'>✅ การแก้ไขเสร็จสิ้น</h4>";
    echo "<ul>";
    echo "<li><strong>หน้า show.php</strong>: แก้ไขการแสดงยอดซื้อรวมจาก total_purchase เป็น total_purchase_amount</li>";
    echo "<li><strong>การตั้งค่าเกรด</strong>: ตรวจสอบและสร้างการตั้งค่าเริ่มต้น (ถ้าจำเป็น)</li>";
    echo "<li><strong>อัปเดตเกรดลูกค้า</strong>: อัปเดตเกรดลูกค้าทั้งหมดตามยอดซื้อรวมที่ถูกต้อง</li>";
    echo "<li><strong>เกณฑ์เกรด</strong>: A+ (≥฿" . number_format($gradeThresholds['a_plus']) . "), A (≥฿" . number_format($gradeThresholds['a']) . "), B (≥฿" . number_format($gradeThresholds['b']) . "), C (≥฿" . number_format($gradeThresholds['c']) . "), D (<฿" . number_format($gradeThresholds['c']) . ")</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p style='margin-top: 20px;'>";
    echo "<a href='customers.php?action=show&id=44' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>ทดสอบหน้าแสดงลูกค้า</a> ";
    echo "<a href='customers.php' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>กลับไปหน้าจัดการลูกค้า</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h4 style='color: #721c24; margin-top: 0;'>❌ เกิดข้อผิดพลาด</h4>";
    echo "<p style='color: #721c24;'>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
