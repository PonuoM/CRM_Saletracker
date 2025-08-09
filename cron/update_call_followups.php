#!/usr/bin/env php
<?php
/**
 * Update Call Follow-ups Cron Job
 * อัปเดตการติดตามการโทรอัตโนมัติ
 * 
 * Usage: php cron/update_call_followups.php
 */

// เช็คว่ารันจาก command line
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line.\n");
}

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/services/CallService.php';

echo "=== Call Follow-up Update Cron Job ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Initialize call service
    $callService = new CallService();
    
    // Log job start
    logCronJob('update_call_followups', 'running', null, null, null);
    
    $startTime = microtime(true);
    
    // สร้างคิวการติดตาม
    $result = createCallFollowupQueue();
    
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    if ($result['success']) {
        echo "✅ Call follow-up update completed successfully in {$executionTime} seconds\n";
        echo "📊 Summary:\n";
        echo "   - New follow-up queues created: {$result['new_queues']}\n";
        echo "   - Overdue follow-ups: {$result['overdue_count']}\n";
        echo "   - Urgent follow-ups: {$result['urgent_count']}\n";
        
        // Log successful completion
        logCronJob('update_call_followups', 'success', $startTime, $endTime, json_encode($result));
        
    } else {
        echo "❌ Call follow-up update failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        
        // Log failure
        logCronJob('update_call_followups', 'failed', $startTime, $endTime, null, $result['error'] ?? 'Unknown error');
    }
    
} catch (Exception $e) {
    $errorMessage = "Fatal error: " . $e->getMessage();
    echo "❌ {$errorMessage}\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Log fatal error
    logCronJob('update_call_followups', 'failed', microtime(true), null, null, $errorMessage);
    
    exit(1);
}

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
echo "=== End of Call Follow-up Update ===\n";

/**
 * สร้างคิวการติดตามการโทร
 */
function createCallFollowupQueue() {
    try {
        $db = new Database();
        
        // ดึงลูกค้าที่ต้องติดตาม
        $sql = "SELECT 
                    cl.customer_id,
                    cl.log_id,
                    c.assigned_to,
                    cl.next_followup_at,
                    cl.followup_priority
                FROM call_logs cl
                JOIN customers c ON cl.customer_id = c.customer_id
                WHERE cl.next_followup_at IS NOT NULL
                    AND cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 30 DAY)
                    AND cl.call_result IN ('not_interested', 'callback', 'interested', 'complaint')
                    AND NOT EXISTS (
                        SELECT 1 FROM call_followup_queue cfq 
                        WHERE cfq.customer_id = cl.customer_id 
                        AND cfq.status = 'pending'
                    )";
        
        $customers = $db->fetchAll($sql);
        
        $newQueues = 0;
        $overdueCount = 0;
        $urgentCount = 0;
        
        foreach ($customers as $customer) {
            // สร้างคิวการติดตาม
            $queueData = [
                'customer_id' => $customer['customer_id'],
                'call_log_id' => $customer['log_id'],
                'user_id' => $customer['assigned_to'],
                'followup_date' => date('Y-m-d', strtotime($customer['next_followup_at'])),
                'priority' => $customer['followup_priority'],
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $db->insert('call_followup_queue', $queueData);
            $newQueues++;
            
            // นับจำนวนเกินกำหนดและเร่งด่วน
            $followupDate = strtotime($customer['next_followup_at']);
            $today = time();
            
            if ($followupDate <= $today) {
                $overdueCount++;
            } elseif ($followupDate <= strtotime('+3 days')) {
                $urgentCount++;
            }
        }
        
        return [
            'success' => true,
            'new_queues' => $newQueues,
            'overdue_count' => $overdueCount,
            'urgent_count' => $urgentCount
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Log cron job execution
 */
function logCronJob($jobName, $status, $startTime, $endTime = null, $output = null, $errorMessage = null) {
    try {
        $db = new Database();
        
        $executionTime = null;
        if ($startTime && $endTime) {
            $executionTime = round($endTime - $startTime, 2);
        }
        
        $data = [
            'job_name' => $jobName,
            'status' => $status,
            'start_time' => $startTime ? date('Y-m-d H:i:s', $startTime) : date('Y-m-d H:i:s'),
            'end_time' => $endTime ? date('Y-m-d H:i:s', $endTime) : null,
            'execution_time' => $executionTime,
            'output' => $output,
            'error_message' => $errorMessage,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('cron_job_logs', $data);
        
    } catch (Exception $e) {
        error_log("Failed to log cron job: " . $e->getMessage());
    }
}
?>
