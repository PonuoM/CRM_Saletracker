#!/usr/bin/env php
<?php
/**
 * Update Customer Grades Cron Job
 * อัปเดตเกรดลูกค้าอัตโนมัติ
 * 
 * Usage: php cron/update_customer_grades.php
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line.\n");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/services/CronJobService.php';

echo "Starting customer grade update...\n";

try {
    $cronService = new CronJobService();
    $result = $cronService->updateCustomerGrades();
    
    if ($result['success']) {
        echo "✅ Updated {$result['updated_count']} customer grades\n";
        
        if (!empty($result['changes'])) {
            echo "\nGrade changes:\n";
            foreach ($result['changes'] as $change) {
                echo "- {$change['customer_name']}: {$change['old_grade']} → {$change['new_grade']} (฿" . number_format($change['total_purchase']) . ")\n";
            }
        }
    } else {
        echo "❌ Error: " . $result['error'] . "\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Customer grade update completed.\n";
?>