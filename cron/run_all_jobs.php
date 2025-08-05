#!/usr/bin/env php
<?php
/**
 * Run All Cron Jobs
 * เรียกใช้งาน cron jobs ทั้งหมด
 * 
 * Usage: php cron/run_all_jobs.php
 */

// เช็คว่ารันจาก command line
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line.\n");
}

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/services/CronJobService.php';

echo "=== CRM SalesTracker Cron Jobs ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Initialize cron job service
    $cronService = new CronJobService();
    
    // Log job start
    logCronJob('run_all_jobs', 'running', null, null, null);
    
    $startTime = microtime(true);
    
    // Run all jobs
    $results = $cronService->runAllJobs();
    
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    if ($results['success']) {
        echo "✅ All cron jobs completed successfully in {$executionTime} seconds\n\n";
        
        // แสดงผลสรุป
        if (isset($results['results'])) {
            foreach ($results['results'] as $jobName => $result) {
                if (isset($result['success'])) {
                    $status = $result['success'] ? '✅' : '❌';
                    echo "{$status} {$jobName}: ";
                    
                    if ($result['success']) {
                        if (isset($result['updated_count'])) {
                            echo "Updated {$result['updated_count']} records";
                        } elseif (isset($result['recall_count'])) {
                            echo "Found {$result['recall_count']} customers";
                        } elseif (isset($result['notification_count'])) {
                            echo "Sent {$result['notification_count']} notifications";
                        } elseif (isset($result['cleanup_results'])) {
                            $cleanup = $result['cleanup_results'];
                            echo "Cleaned up: {$cleanup['deleted_logs']} logs, {$cleanup['deleted_notifications']} notifications, {$cleanup['deleted_backups']} backups";
                        } else {
                            echo "Success";
                        }
                    } else {
                        echo "Error: " . ($result['error'] ?? 'Unknown error');
                    }
                    echo "\n";
                }
            }
        }
        
        // Log successful completion
        logCronJob('run_all_jobs', 'success', $startTime, $endTime, json_encode($results));
        
    } else {
        echo "❌ Cron jobs failed: " . ($results['error'] ?? 'Unknown error') . "\n";
        
        // Log failure
        logCronJob('run_all_jobs', 'failed', $startTime, $endTime, null, $results['error'] ?? 'Unknown error');
    }
    
} catch (Exception $e) {
    $errorMessage = "Fatal error: " . $e->getMessage();
    echo "❌ {$errorMessage}\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Log fatal error
    logCronJob('run_all_jobs', 'failed', microtime(true), null, null, $errorMessage);
    
    exit(1);
}

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
echo "=== End of Cron Jobs ===\n";

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
        
        $sql = "INSERT INTO cron_job_logs (job_name, status, start_time, end_time, execution_time, output, error_message) 
                VALUES (?, ?, FROM_UNIXTIME(?), ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $jobName,
            $status,
            $startTime,
            $endTime ? date('Y-m-d H:i:s', $endTime) : null,
            $executionTime,
            $output,
            $errorMessage
        ]);
        
        // Update last run time in settings
        if ($status === 'success') {
            $updateSql = "UPDATE cron_job_settings SET last_run = NOW() WHERE job_name = ?";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([$jobName]);
        }
        
    } catch (Exception $e) {
        error_log("Failed to log cron job: " . $e->getMessage());
    }
}
?>