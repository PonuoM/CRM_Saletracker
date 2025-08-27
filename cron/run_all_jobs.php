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

// Enhanced logging function
function logMessage($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$type}] {$message}\n";
    
    // Log to file
    $logFile = __DIR__ . '/cron_execution_log.txt';
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Also output to console
    echo $logEntry;
}

logMessage("=== CRM SalesTracker Cron Jobs ===");
logMessage("Started at: " . date('Y-m-d H:i:s'));
logMessage("Environment: " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'undefined'));
logMessage("DB Configuration - Host: " . (defined('DB_HOST') ? DB_HOST : 'undefined') . 
           ", Port: " . (defined('DB_PORT') ? DB_PORT : 'undefined') . 
           ", DB: " . (defined('DB_NAME') ? DB_NAME : 'undefined') . 
           ", User: " . (defined('DB_USER') ? DB_USER : 'undefined'));

try {
    logMessage("Initializing CronJobService...");
    // Initialize cron job service
    $cronService = new CronJobService();
    
    logMessage("CronJobService initialized successfully");
    
    // Log job start
    logCronJob('run_all_jobs', 'running', null, null, null);
    
    $startTime = microtime(true);
    
    // Run all jobs
    logMessage("Running all cron jobs...");
    $results = $cronService->runAllJobs();
    
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    if ($results['success']) {
        logMessage("✅ All cron jobs completed successfully in {$executionTime} seconds");
        
        // แสดงผลสรุป
        if (isset($results['results'])) {
            foreach ($results['results'] as $jobName => $result) {
                if (isset($result['success'])) {
                    $status = $result['success'] ? '✅' : '❌';
                    $message = "{$status} {$jobName}: ";
                    
                    if ($result['success']) {
                        if (isset($result['updated_count'])) {
                            $message .= "Updated {$result['updated_count']} records";
                        } elseif (isset($result['recall_count'])) {
                            $message .= "Found {$result['recall_count']} customers";
                        } elseif (isset($result['notification_count'])) {
                            $message .= "Sent {$result['notification_count']} notifications";
                        } elseif (isset($result['timeout_customers_moved_to_waiting'])) {
                            $message .= "Timeout moved to waiting: {$result['timeout_customers_moved_to_waiting']} customers";
                        } elseif (isset($result['waiting_to_distribution_not_expired'])) {
                            $message .= "Waiting to distribution (not expired): {$result['waiting_to_distribution_not_expired']} customers";
                        } elseif (isset($result['new_customers_recalled'])) {
                            $message .= "New recalled: {$result['new_customers_recalled']}, Existing recalled: {$result['existing_customers_recalled']}, Moved to distribution: {$result['moved_to_distribution']}";
                        } elseif (isset($result['cleanup_results'])) {
                            $cleanup = $result['cleanup_results'];
                            $message .= "Cleaned up: {$cleanup['deleted_logs']} logs, {$cleanup['deleted_notifications']} notifications, {$cleanup['deleted_backups']} backups";
                        } else {
                            $message .= "Success";
                        }
                    } else {
                        $message .= "Error: " . ($result['error'] ?? 'Unknown error');
                    }
                    logMessage($message);
                }
            }
        }
        
        // Log successful completion
        logCronJob('run_all_jobs', 'success', $startTime, $endTime, json_encode($results));
        
    } else {
        $errorMessage = "❌ Cron jobs failed: " . ($results['error'] ?? 'Unknown error');
        logMessage($errorMessage, 'ERROR');
        
        // Log failure
        logCronJob('run_all_jobs', 'failed', $startTime, $endTime, null, $results['error'] ?? 'Unknown error');
    }
    
} catch (Exception $e) {
    $errorMessage = "Fatal error: " . $e->getMessage();
    logMessage("❌ {$errorMessage}", 'ERROR');
    logMessage("Stack trace:\n" . $e->getTraceAsString(), 'ERROR');
    
    // Log fatal error
    logCronJob('run_all_jobs', 'failed', microtime(true), null, null, $errorMessage);
    
    exit(1);
}

logMessage("Completed at: " . date('Y-m-d H:i:s'));
logMessage("=== End of Cron Jobs ===");

/**
 * Log cron job execution
 */
function logCronJob($jobName, $status, $startTime, $endTime = null, $output = null, $errorMessage = null) {
    try {
        // ใช้ Database connection ที่มีอยู่แล้วจาก CronJobService
        global $cronService;
        if ($cronService && method_exists($cronService, 'getDatabase')) {
            $db = $cronService->getDatabase();
        } else {
            // ถ้าไม่มี connection ที่มีอยู่ ให้สร้างใหม่
            $db = new Database();
        }
        
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
        $logMessage = "Failed to log cron job: " . $e->getMessage();
        error_log($logMessage);
        // Also log to our custom log file
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [ERROR] {$logMessage}\n";
        $logFile = __DIR__ . '/cron_execution_log.txt';
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>