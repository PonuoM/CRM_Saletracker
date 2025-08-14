<?php
/**
 * View Cron Database Logs
 * หน้าดูข้อมูล cron jobs จากฐานข้อมูล
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'app/core/Database.php';

echo "<h1>🗄️ CRM SalesTracker - Cron Jobs Database Logs</h1>";

try {
    $db = new Database();
    
    // ดึงข้อมูล cron job logs
    echo "<h2>📊 Cron Job Logs (10 ครั้งล่าสุด)</h2>";
    
    $sql = "SELECT * FROM cron_job_logs ORDER BY start_time DESC LIMIT 10";
    $cronLogs = $db->fetchAll($sql);
    
    if (!empty($cronLogs)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px;'>วันที่/เวลา</th>";
        echo "<th style='padding: 10px;'>Job Name</th>";
        echo "<th style='padding: 10px;'>Status</th>";
        echo "<th style='padding: 10px;'>เวลาที่ใช้</th>";
        echo "<th style='padding: 10px;'>ผลลัพธ์</th>";
        echo "</tr>";
        
        foreach ($cronLogs as $log) {
            $statusColor = $log['status'] === 'success' ? '#28a745' : '#dc3545';
            $statusIcon = $log['status'] === 'success' ? '✅' : '❌';
            
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . $log['start_time'] . "</td>";
            echo "<td style='padding: 8px;'>" . $log['job_name'] . "</td>";
            echo "<td style='padding: 8px; color: $statusColor;'>$statusIcon " . $log['status'] . "</td>";
            echo "<td style='padding: 8px;'>" . ($log['execution_time'] ? $log['execution_time'] . 's' : '-') . "</td>";
            echo "<td style='padding: 8px; max-width: 300px; overflow: hidden;'>";
            
            if ($log['output']) {
                $output = json_decode($log['output'], true);
                if ($output && isset($output['results'])) {
                    foreach ($output['results'] as $jobName => $result) {
                        if (isset($result['success']) && $result['success']) {
                            echo "<small>";
                            if (isset($result['new_customers_recalled'])) {
                                echo "🗂️ New: {$result['new_customers_recalled']}, Existing: {$result['existing_customers_recalled']}, Moved: {$result['moved_to_distribution']}<br>";
                            } elseif (isset($result['updated_count'])) {
                                echo "📊 $jobName: {$result['updated_count']} records<br>";
                            } elseif (isset($result['recall_count'])) {
                                echo "📋 $jobName: {$result['recall_count']} customers<br>";
                            }
                            echo "</small>";
                        }
                    }
                } else {
                    echo "<small>" . substr($log['output'], 0, 100) . "...</small>";
                }
            } else {
                echo "-";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px;'>";
        echo "<p>⚠️ ไม่พบข้อมูล cron job logs ในฐานข้อมูล</p>";
        echo "</div>";
    }
    
    // ดึงข้อมูล activity logs ที่เกี่ยวข้องกับ basket management
    echo "<h2>🗂️ Basket Management Activities (5 ครั้งล่าสุด)</h2>";
    
    $sql = "SELECT * FROM activity_logs 
            WHERE activity_type = 'basket_management' 
            ORDER BY created_at DESC 
            LIMIT 5";
    $activityLogs = $db->fetchAll($sql);
    
    if (!empty($activityLogs)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px;'>วันที่/เวลา</th>";
        echo "<th style='padding: 10px;'>Action</th>";
        echo "<th style='padding: 10px;'>รายละเอียด</th>";
        echo "</tr>";
        
        foreach ($activityLogs as $log) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . $log['created_at'] . "</td>";
            echo "<td style='padding: 8px;'>" . $log['action'] . "</td>";
            echo "<td style='padding: 8px;'>" . $log['description'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px;'>";
        echo "<p>⚠️ ยังไม่มีข้อมูล basket management activities</p>";
        echo "</div>";
    }
    
    // สถิติลูกค้าในแต่ละตะกร้า
    echo "<h2>📊 สถิติลูกค้าในตะกร้า (ปัจจุบัน)</h2>";
    
    $sql = "SELECT 
                basket_type,
                COUNT(*) as count,
                COUNT(CASE WHEN assigned_to IS NOT NULL THEN 1 END) as assigned_count
            FROM customers 
            WHERE is_active = 1
            GROUP BY basket_type";
    $basketStats = $db->fetchAll($sql);
    
    if (!empty($basketStats)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px;'>ตะกร้า</th>";
        echo "<th style='padding: 10px;'>จำนวนลูกค้า</th>";
        echo "<th style='padding: 10px;'>ที่มอบหมายแล้ว</th>";
        echo "<th style='padding: 10px;'>คำอธิบาย</th>";
        echo "</tr>";
        
        $basketDescriptions = [
            'distribution' => 'ตะกร้าพร้อมแจก - ลูกค้าที่รอการมอบหมาย',
            'assigned' => 'ตะกร้าที่มอบหมายแล้ว - ลูกค้าที่ Telesales ดูแลอยู่',
            'waiting' => 'ตะกร้ารอ - ลูกค้าเก่าที่พักชั่วคราว'
        ];
        
        foreach ($basketStats as $stat) {
            $basketType = $stat['basket_type'] ?: 'ไม่ระบุ';
            $description = $basketDescriptions[$basketType] ?? 'ไม่ทราบ';
            
            echo "<tr>";
            echo "<td style='padding: 8px;'><strong>$basketType</strong></td>";
            echo "<td style='padding: 8px; text-align: center;'>" . number_format($stat['count']) . "</td>";
            echo "<td style='padding: 8px; text-align: center;'>" . number_format($stat['assigned_count']) . "</td>";
            echo "<td style='padding: 8px;'><small>$description</small></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // ลูกค้าที่ถูก recall ล่าสุด
    echo "<h2>🔄 ลูกค้าที่ถูก Recall ล่าสุด (10 รายการ)</h2>";
    
    $sql = "SELECT 
                customer_id,
                CONCAT(first_name, ' ', last_name) as customer_name,
                phone,
                basket_type,
                recall_at,
                recall_reason,
                assigned_to
            FROM customers 
            WHERE recall_at IS NOT NULL 
            ORDER BY recall_at DESC 
            LIMIT 10";
    $recalledCustomers = $db->fetchAll($sql);
    
    if (!empty($recalledCustomers)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px;'>ชื่อลูกค้า</th>";
        echo "<th style='padding: 10px;'>เบอร์โทร</th>";
        echo "<th style='padding: 10px;'>ตะกร้าปัจจุบัน</th>";
        echo "<th style='padding: 10px;'>วันที่ Recall</th>";
        echo "<th style='padding: 10px;'>เหตุผล</th>";
        echo "</tr>";
        
        foreach ($recalledCustomers as $customer) {
            $reasonText = [
                'new_customer_timeout' => 'ลูกค้าใหม่หมดเวลา (30 วัน)',
                'existing_customer_timeout' => 'ลูกค้าเก่าไม่ซื้อนาน (90 วัน)'
            ];
            
            $reason = $reasonText[$customer['recall_reason']] ?? $customer['recall_reason'];
            
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . $customer['customer_name'] . "</td>";
            echo "<td style='padding: 8px;'>" . $customer['phone'] . "</td>";
            echo "<td style='padding: 8px;'>" . $customer['basket_type'] . "</td>";
            echo "<td style='padding: 8px;'>" . $customer['recall_at'] . "</td>";
            echo "<td style='padding: 8px;'><small>$reason</small></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px;'>";
        echo "<p>✅ ยังไม่มีลูกค้าที่ถูก recall หรือระบบยังไม่ได้รัน</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h3>❌ เกิดข้อผิดพลาด</h3>";
    echo "<p>ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $e->getMessage() . "</p>";
    echo "</div>";
}

// ปุ่มนำทาง
echo "<div style='margin: 20px 0;'>";
echo "<a href='?' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔄 รีเฟรช</a>";
echo "<a href='view_cron_logs.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>📄 ดู Log Files</a>";
echo "<a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 หน้าหลัก</a>";
echo "</div>";

?>
