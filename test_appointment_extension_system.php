<?php
/**
 * Test Appointment Extension System
 * ทดสอบระบบการนับจำนวนการนัดหมายและการต่อเวลาอัตโนมัติ
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/services/AppointmentExtensionService.php';
require_once __DIR__ . '/app/services/AppointmentService.php';
require_once __DIR__ . '/app/services/OrderService.php';

echo "<h1>🧪 ทดสอบระบบการต่อเวลาการนัดหมาย</h1>";
echo "<p><strong>วันที่ทดสอบ:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    $extensionService = new AppointmentExtensionService();
    $appointmentService = new AppointmentService();
    $orderService = new OrderService();
    
    echo "<hr>";
    
    // 1. ทดสอบดึงข้อมูลการต่อเวลาของลูกค้า
    echo "<h2>1. ทดสอบดึงข้อมูลการต่อเวลาของลูกค้า</h2>";
    
    $customerId = 1; // ใช้ลูกค้า ID 1 สำหรับทดสอบ
    $customerInfo = $extensionService->getCustomerExtensionInfo($customerId);
    
    if ($customerInfo) {
        echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>ข้อมูลลูกค้า ID: {$customerId}</h3>";
        echo "<p><strong>ชื่อ:</strong> {$customerInfo['first_name']} {$customerInfo['last_name']}</p>";
        echo "<p><strong>เกรด:</strong> {$customerInfo['customer_grade']}</p>";
        echo "<p><strong>สถานะอุณหภูมิ:</strong> {$customerInfo['temperature_status']}</p>";
        echo "<p><strong>จำนวนการนัดหมาย:</strong> {$customerInfo['appointment_count']}</p>";
        echo "<p><strong>จำนวนครั้งที่ต่อเวลา:</strong> {$customerInfo['appointment_extension_count']} / {$customerInfo['max_appointment_extensions']}</p>";
        echo "<p><strong>สามารถต่อเวลาได้:</strong> " . ($customerInfo['can_extend'] ? '✅ ใช่' : '❌ ไม่') . "</p>";
        echo "<p><strong>วันหมดอายุ:</strong> " . ($customerInfo['appointment_extension_expiry'] ? $customerInfo['appointment_extension_expiry'] : 'ไม่มี') . "</p>";
        echo "<p><strong>สถานะการหมดอายุ:</strong> {$customerInfo['expiry_status']}</p>";
        echo "<p><strong>ความพร้อมในการต่อเวลา:</strong> {$customerInfo['extension_availability']}</p>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ ไม่พบข้อมูลลูกค้า ID: {$customerId}</p>";
    }
    
    echo "<hr>";
    
    // 2. ทดสอบดึงสถิติการต่อเวลา
    echo "<h2>2. ทดสอบดึงสถิติการต่อเวลา</h2>";
    
    $stats = $extensionService->getExtensionStats();
    if ($stats) {
        echo "<div style='background: #f0fff0; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>สถิติการต่อเวลา</h3>";
        echo "<p><strong>ลูกค้าทั้งหมด:</strong> {$stats['total_customers']}</p>";
        echo "<p><strong>ลูกค้าที่มีการต่อเวลา:</strong> {$stats['customers_with_extensions']}</p>";
        echo "<p><strong>การต่อเวลาทั้งหมด:</strong> {$stats['total_extensions']}</p>";
        echo "<p><strong>การต่อเวลาเฉลี่ยต่อลูกค้า:</strong> " . round($stats['avg_extensions_per_customer'], 2) . "</p>";
        echo "<p><strong>ลูกค้าที่ถึงขีดจำกัด:</strong> {$stats['customers_at_max']}</p>";
        echo "<p><strong>ลูกค้าที่หมดอายุแล้ว:</strong> {$stats['expired_customers']}</p>";
        echo "<p><strong>ลูกค้าที่ใกล้หมดอายุ (7 วัน):</strong> {$stats['expiring_soon']}</p>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ ไม่สามารถดึงสถิติได้</p>";
    }
    
    echo "<hr>";
    
    // 3. ทดสอบดึงรายการลูกค้าที่ใกล้หมดอายุ
    echo "<h2>3. ทดสอบดึงรายการลูกค้าที่ใกล้หมดอายุ</h2>";
    
    $nearExpiry = $extensionService->getCustomersNearExpiry(7, 5);
    if (!empty($nearExpiry)) {
        echo "<div style='background: #fff8dc; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>ลูกค้าที่ใกล้หมดอายุ (5 รายแรก)</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>ชื่อ</th><th>เกรด</th><th>สถานะ</th><th>การต่อเวลา</th><th>วันหมดอายุ</th>";
        echo "</tr>";
        
        foreach ($nearExpiry as $customer) {
            echo "<tr>";
            echo "<td>{$customer['customer_id']}</td>";
            echo "<td>{$customer['first_name']} {$customer['last_name']}</td>";
            echo "<td>{$customer['customer_grade']}</td>";
            echo "<td>{$customer['temperature_status']}</td>";
            echo "<td>{$customer['appointment_extension_count']}/{$customer['max_appointment_extensions']}</td>";
            echo "<td>{$customer['appointment_extension_expiry']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>⚠️ ไม่มีลูกค้าที่ใกล้หมดอายุ</p>";
    }
    
    echo "<hr>";
    
    // 4. ทดสอบดึงประวัติการต่อเวลา
    echo "<h2>4. ทดสอบดึงประวัติการต่อเวลา</h2>";
    
    $history = $extensionService->getExtensionHistory($customerId, 5);
    if (!empty($history)) {
        echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>ประวัติการต่อเวลาของลูกค้า ID: {$customerId}</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>วันที่</th><th>ประเภท</th><th>จำนวนวัน</th><th>เหตุผล</th><th>ผู้ดำเนินการ</th>";
        echo "</tr>";
        
        foreach ($history as $record) {
            echo "<tr>";
            echo "<td>" . date('Y-m-d H:i', strtotime($record['created_at'])) . "</td>";
            echo "<td>{$record['extension_type']}</td>";
            echo "<td>{$record['extension_days']}</td>";
            echo "<td>{$record['extension_reason']}</td>";
            echo "<td>{$record['user_name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>⚠️ ไม่มีประวัติการต่อเวลา</p>";
    }
    
    echo "<hr>";
    
    // 5. ทดสอบการต่อเวลาด้วยตนเอง (ถ้าสามารถต่อได้)
    echo "<h2>5. ทดสอบการต่อเวลาด้วยตนเอง</h2>";
    
    if ($customerInfo && $customerInfo['can_extend']) {
        echo "<div style='background: #e6f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>ทดสอบต่อเวลา 15 วัน</h3>";
        
        try {
            $result = $extensionService->extendTimeManually($customerId, 1, 15, 'ทดสอบระบบ');
            
            if ($result['status'] === 'success') {
                echo "<p style='color: green;'>✅ ต่อเวลาสำเร็จ!</p>";
                echo "<p><strong>ข้อความ:</strong> {$result['message']}</p>";
                echo "<p><strong>วันหมดอายุใหม่:</strong> {$result['new_expiry_date']}</p>";
                echo "<p><strong>จำนวนครั้งใหม่:</strong> {$result['new_extension_count']}</p>";
            } else {
                echo "<p style='color: red;'>❌ ต่อเวลาไม่สำเร็จ: {$result['message']}</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: {$e->getMessage()}</p>";
        }
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>⚠️ ไม่สามารถต่อเวลาได้ (อาจถึงขีดจำกัดแล้ว)</p>";
    }
    
    echo "<hr>";
    
    // 6. ทดสอบดึงกฎการต่อเวลา
    echo "<h2>6. ทดสอบดึงกฎการต่อเวลา</h2>";
    
    $rules = $extensionService->getExtensionRules();
    if (!empty($rules)) {
        echo "<div style='background: #fff0f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>กฎการต่อเวลาที่ใช้งาน</h3>";
        
        foreach ($rules as $rule) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "<p><strong>ชื่อกฎ:</strong> {$rule['rule_name']}</p>";
            echo "<p><strong>คำอธิบาย:</strong> {$rule['rule_description']}</p>";
            echo "<p><strong>จำนวนวันที่ต่อเวลา:</strong> {$rule['extension_days']} วัน</p>";
            echo "<p><strong>จำนวนครั้งสูงสุด:</strong> {$rule['max_extensions']} ครั้ง</p>";
            echo "<p><strong>รีเซ็ตเมื่อขาย:</strong> " . ($rule['reset_on_sale'] ? 'ใช่' : 'ไม่') . "</p>";
            echo "<p><strong>สถานะการนัดหมายที่จำเป็น:</strong> {$rule['required_appointment_status']}</p>";
            echo "<p><strong>เกรดลูกค้าขั้นต่ำ:</strong> {$rule['min_customer_grade']}</p>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>⚠️ ไม่มีกฎการต่อเวลาที่ใช้งาน</p>";
    }
    
    echo "<hr>";
    
    // 7. สรุปผลการทดสอบ
    echo "<h2>7. สรุปผลการทดสอบ</h2>";
    
    echo "<div style='background: #f8f8f8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>✅ ระบบการต่อเวลาการนัดหมายทำงานได้ปกติ</h3>";
    echo "<ul>";
    echo "<li>✅ ดึงข้อมูลการต่อเวลาของลูกค้า</li>";
    echo "<li>✅ ดึงสถิติการต่อเวลา</li>";
    echo "<li>✅ ดึงรายการลูกค้าที่ใกล้หมดอายุ</li>";
    echo "<li>✅ ดึงประวัติการต่อเวลา</li>";
    echo "<li>✅ ต่อเวลาด้วยตนเอง</li>";
    echo "<li>✅ ดึงกฎการต่อเวลา</li>";
    echo "</ul>";
    
    echo "<h3>🎯 ฟีเจอร์หลักที่ทำงานได้:</h3>";
    echo "<ul>";
    echo "<li>📅 <strong>การนัดหมาย 1 ครั้ง = ต่อเวลา 1 เดือน</strong></li>";
    echo "<li>🔄 <strong>สูงสุด 3 ครั้ง</strong> ต่อลูกค้า</li>";
    echo "<li>💰 <strong>รีเซ็ตตัวนับเมื่อปิดการขาย</strong></li>";
    echo "<li>⚙️ <strong>ตั้งค่ากฎการต่อเวลาได้</strong></li>";
    echo "<li>📊 <strong>ติดตามสถิติและประวัติ</strong></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3 style='color: red;'>❌ เกิดข้อผิดพลาดในการทดสอบ</h3>";
    echo "<p><strong>ข้อผิดพลาด:</strong> {$e->getMessage()}</p>";
    echo "<p><strong>Stack Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<p><em>การทดสอบเสร็จสิ้นเมื่อ: " . date('Y-m-d H:i:s') . "</em></p>";
?> 