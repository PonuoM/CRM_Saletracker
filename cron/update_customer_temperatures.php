#!/usr/bin/env php
<?php
/**
 * Update Customer Temperatures Cron Job
 * อัปเดตอุณหภูมิลูกค้าอัตโนมัติ
 * 
 * Usage: php cron/update_customer_temperatures.php
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line.\n");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/services/CronJobService.php';

echo "Starting customer temperature update...\n";

try {
    $cronService = new CronJobService();
    $result = $cronService->updateCustomerTemperatures();
    
    if ($result['success']) {
        echo "✅ Updated {$result['updated_count']} customer temperatures\n";
        
        if (!empty($result['changes'])) {
            echo "\nTemperature changes:\n";
            foreach ($result['changes'] as $change) {
                echo "- {$change['customer_name']}: {$change['old_temperature']} → {$change['new_temperature']} ({$change['days_since_contact']} days)\n";
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

echo "Customer temperature update completed.\n";
?>