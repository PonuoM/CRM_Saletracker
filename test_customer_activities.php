<?php
/**
 * ทดสอบระบบกิจกรรมลูกค้าใหม่
 */

session_start();

// Include required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/controllers/CustomerController.php';

// Set up test session (simulate login as admin)
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'admin';
$_SESSION['username'] = 'admin';

// Initialize controller
$controller = new CustomerController();

// Test customer ID (ลูกค้าที่มีข้อมูลครบถ้วน)
$customerId = 65;

echo "<h1>ทดสอบระบบกิจกรรมลูกค้าใหม่ (Fixed Container + Scroll)</h1>";
echo "<h2>ลูกค้า ID: {$customerId}</h2>";
echo "<p><strong>การปรับปรุง:</strong> กรอบกิจกรรมมีความสูงคงที่ 500px พร้อม scroll bar</p>";

try {
    // Create reflection to access private method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('getCombinedCustomerActivities');
    $method->setAccessible(true);
    
    // Call the method
    $activities = $method->invoke($controller, $customerId);
    
    echo "<h3>ผลลัพธ์:</h3>";
    echo "<p>จำนวนกิจกรรมทั้งหมด: <strong>" . count($activities) . "</strong> รายการ</p>";
    
    if (!empty($activities)) {
        echo "<div style='max-height: 600px; overflow-y: auto; border: 1px solid #ddd; padding: 15px;'>";
        
        foreach ($activities as $index => $activity) {
            $bgColor = '';
            switch ($activity['activity_type']) {
                case 'call':
                    $bgColor = '#e3f2fd';
                    break;
                case 'appointment':
                    $bgColor = '#e0f2f1';
                    break;
                case 'order':
                    $bgColor = '#f3e5f5';
                    break;
                default:
                    $bgColor = '#f5f5f5';
            }
            
            echo "<div style='background: {$bgColor}; margin: 10px 0; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;'>";
            echo "<div style='display: flex; align-items: center; margin-bottom: 8px;'>";
            echo "<i class='{$activity['icon']}' style='margin-right: 10px; color: #666;'></i>";
            echo "<strong style='color: #333;'>{$activity['user_name']}</strong>";
            echo "<span style='margin-left: auto; color: #666; font-size: 0.9em;'>";
            echo date('d/m/Y H:i', strtotime($activity['created_at']));
            echo "</span>";
            echo "</div>";
            echo "<div style='color: #555; line-height: 1.4;'>";
            echo htmlspecialchars($activity['activity_description']);
            echo "</div>";
            echo "<div style='margin-top: 5px; font-size: 0.8em; color: #888;'>";
            echo "ประเภท: {$activity['activity_type']} | ID: {$activity['id']}";
            echo "</div>";
            echo "</div>";
        }
        
        echo "</div>";
    } else {
        echo "<p style='color: #666; font-style: italic;'>ไม่พบกิจกรรม</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; color: #c62828; padding: 15px; border-radius: 4px; margin: 10px 0;'>";
    echo "<strong>เกิดข้อผิดพลาด:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "<hr>";
echo "<h3>ข้อมูลเพิ่มเติม:</h3>";

// Test database connection
try {
    $db = new Database();
    
    // Test call_logs
    $callLogs = $db->fetchAll("SELECT COUNT(*) as count FROM call_logs WHERE customer_id = ?", [$customerId]);
    echo "<p>📞 Call logs: " . ($callLogs[0]['count'] ?? 0) . " รายการ</p>";
    
    // Test appointments
    $appointments = $db->fetchAll("SELECT COUNT(*) as count FROM appointments WHERE customer_id = ?", [$customerId]);
    echo "<p>📅 Appointments: " . ($appointments[0]['count'] ?? 0) . " รายการ</p>";
    
    // Test orders
    $orders = $db->fetchAll("SELECT COUNT(*) as count FROM orders WHERE customer_id = ?", [$customerId]);
    echo "<p>🛒 Orders: " . ($orders[0]['count'] ?? 0) . " รายการ</p>";
    
    // Test customer_activities
    $customerActivities = $db->fetchAll("SELECT COUNT(*) as count FROM customer_activities WHERE customer_id = ?", [$customerId]);
    echo "<p>📋 Customer activities: " . ($customerActivities[0]['count'] ?? 0) . " รายการ</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='customers.php?action=show&id={$customerId}' target='_blank'>ดูหน้าลูกค้าจริง</a></p>";
?>
