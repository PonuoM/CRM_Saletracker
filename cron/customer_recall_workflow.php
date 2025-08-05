<?php
/**
 * Customer Recall Workflow Cron Job
 * รันอัตโนมัติเพื่อจัดการระบบ Recall และต่อเวลา
 * 
 * การตั้งค่า Cron:
 * ทุกชั่วโมง: 0 * * * * php /path/to/cron/customer_recall_workflow.php
 * ทุกวันเวลา 3:00 น.: 0 3 * * * php /path/to/cron/customer_recall_workflow.php
 */

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/services/WorkflowService.php';

// Set timezone
date_default_timezone_set('Asia/Bangkok');

// Start logging
$logFile = __DIR__ . '/../logs/customer_recall_workflow.log';
$startTime = date('Y-m-d H:i:s');

echo "=== Customer Recall Workflow Cron Job Started at {$startTime} ===\n";
file_put_contents($logFile, "=== Customer Recall Workflow Cron Job Started at {$startTime} ===\n", FILE_APPEND);

try {
    $workflowService = new WorkflowService();
    
    // 1. รัน Manual Recall
    echo "Running manual recall...\n";
    file_put_contents($logFile, "Running manual recall...\n", FILE_APPEND);
    
    $recallResult = $workflowService->runManualRecall();
    
    if ($recallResult['success']) {
        $results = $recallResult['results'];
        $message = "Manual recall completed successfully:\n";
        $message .= "- New customers recalled: {$results['new_customers_recalled']}\n";
        $message .= "- Existing customers recalled: {$results['existing_customers_recalled']}\n";
        $message .= "- Moved to distribution: {$results['moved_to_distribution']}\n";
        
        echo $message;
        file_put_contents($logFile, $message, FILE_APPEND);
    } else {
        $errorMessage = "Manual recall failed: " . $recallResult['message'] . "\n";
        echo $errorMessage;
        file_put_contents($logFile, $errorMessage, FILE_APPEND);
    }
    
    // 2. ดึงสถิติปัจจุบัน
    echo "Getting current workflow stats...\n";
    file_put_contents($logFile, "Getting current workflow stats...\n", FILE_APPEND);
    
    $stats = $workflowService->getWorkflowStats();
    
    $statsMessage = "Current workflow stats:\n";
    $statsMessage .= "- Pending recall: {$stats['pending_recall']}\n";
    $statsMessage .= "- New customer timeout: {$stats['new_customer_timeout']}\n";
    $statsMessage .= "- Existing customer timeout: {$stats['existing_customer_timeout']}\n";
    $statsMessage .= "- Active today: {$stats['active_today']}\n";
    
    echo $statsMessage;
    file_put_contents($logFile, $statsMessage, FILE_APPEND);
    
    // 3. ตรวจสอบและส่งการแจ้งเตือน (ถ้าจำเป็น)
    if ($stats['pending_recall'] > 0) {
        $notificationMessage = "⚠️ มีลูกค้า {$stats['pending_recall']} รายที่ต้อง Recall\n";
        echo $notificationMessage;
        file_put_contents($logFile, $notificationMessage, FILE_APPEND);
        
        // ส่งการแจ้งเตือนไปยัง Admin/Supervisor (ถ้ามีระบบ notification)
        // sendNotificationToAdmins($stats);
    }
    
    // 4. บันทึกสถิติการทำงาน
    $endTime = date('Y-m-d H:i:s');
    $completionMessage = "=== Customer Recall Workflow Cron Job Completed at {$endTime} ===\n\n";
    
    echo $completionMessage;
    file_put_contents($logFile, $completionMessage, FILE_APPEND);
    
} catch (Exception $e) {
    $errorMessage = "Error in customer recall workflow cron job: " . $e->getMessage() . "\n";
    $errorMessage .= "Stack trace: " . $e->getTraceAsString() . "\n";
    
    echo $errorMessage;
    file_put_contents($logFile, $errorMessage, FILE_APPEND);
    
    // ส่งการแจ้งเตือนข้อผิดพลาด (ถ้ามีระบบ notification)
    // sendErrorNotification($e->getMessage());
}

/**
 * ส่งการแจ้งเตือนไปยัง Admin/Supervisor
 */
function sendNotificationToAdmins($stats) {
    // TODO: Implement notification system
    // เช่น ส่ง email, LINE Notify, หรือบันทึกลง database
    echo "Notification would be sent to admins about pending recalls\n";
}

/**
 * ส่งการแจ้งเตือนข้อผิดพลาด
 */
function sendErrorNotification($errorMessage) {
    // TODO: Implement error notification system
    echo "Error notification would be sent: {$errorMessage}\n";
} 