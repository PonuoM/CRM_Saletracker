<?php
/**
 * Test script for Customer Distribution Fix
 * ทดสอบการแก้ไขปัญหา SQLSTATE[HY093] ในระบบแจกลูกค้า
 */

require_once __DIR__ . '/app/services/CustomerDistributionService.php';

try {
    echo "=== ทดสอบระบบแจกลูกค้า ===\n";
    
    $service = new CustomerDistributionService();
    
    // ทดสอบดึงสถิติ
    echo "1. ทดสอบดึงสถิติการแจกลูกค้า...\n";
    $stats = $service->getDistributionStats();
    echo "   - ลูกค้าใน Distribution: " . $stats['distribution_count'] . "\n";
    echo "   - Telesales ที่พร้อมรับงาน: " . $stats['available_telesales_count'] . "\n";
    echo "   - ลูกค้า Hot: " . $stats['hot_customers_count'] . "\n";
    echo "   - ลูกค้า Warm: " . $stats['warm_customers_count'] . "\n";
    
    // ทดสอบดึง Telesales
    echo "\n2. ทดสอบดึงรายการ Telesales...\n";
    $telesales = $service->getAvailableTelesales();
    echo "   - พบ Telesales: " . count($telesales) . " คน\n";
    
    if (!empty($telesales)) {
        echo "   - รายชื่อ:\n";
        foreach ($telesales as $telesale) {
            echo "     * {$telesale['full_name']} (ลูกค้าปัจจุบัน: {$telesale['current_customers_count']})\n";
        }
    }
    
    // ทดสอบดึงลูกค้าที่พร้อมแจก
    echo "\n3. ทดสอบดึงลูกค้าที่พร้อมแจก...\n";
    $customers = $service->getAvailableCustomers('hot_warm_cold', 5);
    echo "   - พบลูกค้าที่พร้อมแจก: " . count($customers) . " คน\n";
    
    if (!empty($customers)) {
        echo "   - รายชื่อ:\n";
        foreach ($customers as $customer) {
            echo "     * {$customer['first_name']} {$customer['last_name']} (สถานะ: {$customer['temperature_status']})\n";
        }
    }
    
    echo "\n=== การทดสอบเสร็จสิ้น ===\n";
    echo "หากไม่พบข้อผิดพลาด SQLSTATE[HY093] แสดงว่าการแก้ไขสำเร็จ\n";
    
} catch (Exception $e) {
    echo "เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?> 