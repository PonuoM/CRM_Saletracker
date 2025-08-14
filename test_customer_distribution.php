<?php
/**
 * Test Customer Distribution Functionality
 * ทดสอบระบบแจกลูกค้าหลังจากแก้ไขข้อผิดพลาด SQL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/services/CustomerDistributionService.php';

echo "<h1>ทดสอบระบบแจกลูกค้า</h1>";

try {
    $distributionService = new CustomerDistributionService();
    
    echo "<h2>1. ทดสอบการดึงสถิติการแจกลูกค้า</h2>";
    $stats = $distributionService->getDistributionStats();
    echo "<pre>";
    print_r($stats);
    echo "</pre>";
    
    echo "<h2>2. ทดสอบการดึงรายการ Telesales ที่พร้อมรับงาน</h2>";
    $telesales = $distributionService->getAvailableTelesales();
    echo "<pre>";
    print_r($telesales);
    echo "</pre>";
    
    echo "<h2>3. ทดสอบการดึงรายการลูกค้าที่พร้อมแจก</h2>";
    $customers = $distributionService->getAvailableCustomers('hot_warm_cold', 5);
    echo "<pre>";
    print_r($customers);
    echo "</pre>";
    
    echo "<h2>4. ทดสอบการดึงสถิติของบริษัท Prima</h2>";
    $companyStats = $distributionService->getCompanyStats('prima');
    echo "<pre>";
    print_r($companyStats);
    echo "</pre>";
    
    echo "<h2>5. ทดสอบการดึงรายการ Telesales ตามบริษัท Prima</h2>";
    $telesalesByCompany = $distributionService->getTelesalesByCompany('prima');
    echo "<pre>";
    print_r($telesalesByCompany);
    echo "</pre>";
    
    echo "<h2>✅ การทดสอบเสร็จสิ้น - ไม่มีข้อผิดพลาด SQL</h2>";
    
} catch (Exception $e) {
    echo "<h2>❌ เกิดข้อผิดพลาด</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . htmlspecialchars($e->getLine()) . "</p>";
    echo "<pre>";
    print_r($e->getTrace());
    echo "</pre>";
}
?>
