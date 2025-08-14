<?php
/**
 * ทดสอบข้อมูลบริษัทและพนักงาน
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once 'config/config.php';
require_once 'app/core/Database.php';

echo "<h1>🧪 ทดสอบข้อมูลบริษัทและพนักงาน</h1>";

try {
    $db = new Database();
    
    echo "<h2>📊 ข้อมูลบริษัท</h2>";
    $companies = $db->fetchAll("SELECT * FROM companies WHERE is_active = 1");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>ชื่อบริษัท</th><th>รหัส</th><th>สถานะ</th></tr>";
    
    foreach ($companies as $company) {
        echo "<tr>";
        echo "<td>{$company['company_id']}</td>";
        echo "<td>{$company['company_name']}</td>";
        echo "<td>{$company['company_code']}</td>";
        echo "<td>" . ($company['is_active'] ? 'ใช้งาน' : 'ไม่ใช้งาน') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>👥 ข้อมูลพนักงาน Telesales (role_id = 4)</h2>";
    $telesales = $db->fetchAll("
        SELECT u.user_id, u.full_name, u.company_id, c.company_name, c.company_code, u.is_active
        FROM users u 
        LEFT JOIN companies c ON u.company_id = c.company_id
        WHERE u.role_id = 4
        ORDER BY u.company_id, u.full_name
    ");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>ชื่อ</th><th>บริษัท ID</th><th>ชื่อบริษัท</th><th>รหัสบริษัท</th><th>สถานะ</th></tr>";
    
    foreach ($telesales as $user) {
        $statusColor = $user['is_active'] ? 'green' : 'red';
        echo "<tr>";
        echo "<td>{$user['user_id']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['company_id']}</td>";
        echo "<td>{$user['company_name']}</td>";
        echo "<td>{$user['company_code']}</td>";
        echo "<td style='color: {$statusColor};'>" . ($user['is_active'] ? 'ใช้งาน' : 'ไม่ใช้งาน') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🔍 ทดสอบ Query สำหรับแต่ละบริษัท</h2>";
    
    foreach (['prima', 'prionic'] as $company) {
        echo "<h3>บริษัท: " . strtoupper($company) . "</h3>";
        
        $sql = "
            SELECT u.user_id, u.full_name, u.email,
                   COUNT(c.customer_id) as current_customers_count
            FROM users u
            JOIN companies comp ON u.company_id = comp.company_id
            LEFT JOIN customers c ON u.user_id = c.assigned_to
                AND c.basket_type = 'assigned'
                AND c.is_active = 1
                AND c.source = ?
            WHERE u.role_id = 4 AND u.is_active = 1
            AND (comp.company_name LIKE ? OR comp.company_code LIKE ?)
            GROUP BY u.user_id, u.full_name, u.email
            ORDER BY current_customers_count ASC, u.full_name ASC
        ";

        $companySource = strtoupper($company);
        $companyPattern = "%{$company}%";
        
        echo "<p><strong>Parameters:</strong></p>";
        echo "<ul>";
        echo "<li>companySource: {$companySource}</li>";
        echo "<li>companyPattern: {$companyPattern}</li>";
        echo "</ul>";
        
        try {
            $result = $db->fetchAll($sql, [$companySource, $companyPattern, $companyPattern]);
            
            echo "<p><strong>ผลลัพธ์:</strong> " . count($result) . " คน</p>";
            
            if (!empty($result)) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>ID</th><th>ชื่อ</th><th>อีเมล</th><th>ลูกค้าปัจจุบัน</th></tr>";
                
                foreach ($result as $user) {
                    echo "<tr>";
                    echo "<td>{$user['user_id']}</td>";
                    echo "<td>{$user['full_name']}</td>";
                    echo "<td>{$user['email']}</td>";
                    echo "<td>{$user['current_customers_count']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='color: red;'>❌ ไม่พบพนักงาน Telesales สำหรับบริษัทนี้</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
        }
        
        echo "<hr>";
    }
    
    echo "<h2>🔍 ทดสอบ Query แบบง่าย</h2>";
    
    // ทดสอบ query แบบง่าย
    echo "<h3>พนักงาน Telesales ทั้งหมด</h3>";
    $allTelesales = $db->fetchAll("
        SELECT u.user_id, u.full_name, u.company_id, c.company_name, c.company_code
        FROM users u 
        LEFT JOIN companies c ON u.company_id = c.company_id
        WHERE u.role_id = 4 AND u.is_active = 1
        ORDER BY u.company_id, u.full_name
    ");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>ชื่อ</th><th>บริษัท</th><th>รหัส</th></tr>";
    
    foreach ($allTelesales as $user) {
        echo "<tr>";
        echo "<td>{$user['user_id']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['company_name']}</td>";
        echo "<td>{$user['company_code']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>พนักงาน Telesales แยกตาม company_id</h3>";
    foreach ([1, 2] as $companyId) {
        $companyTelesales = $db->fetchAll("
            SELECT u.user_id, u.full_name, c.company_name, c.company_code
            FROM users u 
            LEFT JOIN companies c ON u.company_id = c.company_id
            WHERE u.role_id = 4 AND u.is_active = 1 AND u.company_id = ?
            ORDER BY u.full_name
        ", [$companyId]);
        
        echo "<h4>บริษัท ID: {$companyId} (" . count($companyTelesales) . " คน)</h4>";
        
        if (!empty($companyTelesales)) {
            echo "<ul>";
            foreach ($companyTelesales as $user) {
                echo "<li>{$user['full_name']} - {$user['company_name']} ({$user['company_code']})</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>ไม่พบพนักงาน</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo "<div style='margin: 20px 0;'>";
echo "<a href='admin.php?action=customer_distribution' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔙 กลับไปหน้าแจกลูกค้า</a>";
echo "</div>";

?>
