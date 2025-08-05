#!/usr/bin/env php
<?php
/**
 * Send Customer Recall Notifications Cron Job
 * ส่งการแจ้งเตือนลูกค้าที่ต้องติดตาม
 * 
 * Usage: php cron/send_recall_notifications.php
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line.\n");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/services/CronJobService.php';

echo "Starting customer recall notifications...\n";

try {
    $cronService = new CronJobService();
    
    // สร้างรายการลูกค้าที่ต้องติดตาม
    $recallResult = $cronService->createCustomerRecallList();
    
    if ($recallResult['success']) {
        echo "📋 Found {$recallResult['recall_count']} customers for recall\n";
        
        // ส่งการแจ้งเตือน
        $notificationResult = $cronService->sendCustomerRecallNotifications();
        
        if ($notificationResult['success']) {
            echo "✅ Sent {$notificationResult['notification_count']} notifications to {$notificationResult['recipient_count']} users\n";
            
            if ($recallResult['recall_count'] > 0) {
                echo "\nCustomers requiring follow-up:\n";
                foreach (array_slice($recallResult['customers'], 0, 10) as $customer) {
                    echo "- {$customer['name']} ({$customer['grade']}, {$customer['days_since_contact']} days)\n";
                }
                
                if ($recallResult['recall_count'] > 10) {
                    echo "... and " . ($recallResult['recall_count'] - 10) . " more\n";
                }
            }
        } else {
            echo "❌ Error sending notifications: " . $notificationResult['error'] . "\n";
            exit(1);
        }
    } else {
        echo "❌ Error creating recall list: " . $recallResult['error'] . "\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Customer recall notifications completed.\n";
?>