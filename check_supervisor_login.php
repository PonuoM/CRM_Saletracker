<?php
/**
 * Check Supervisor Login Credentials
 * ตรวจสอบข้อมูลการเข้าสู่ระบบของ Supervisor
 */

require_once 'config/config.php';
require_once 'app/core/Auth.php';
require_once 'app/core/Database.php';

echo "<h1>ตรวจสอบข้อมูลการเข้าสู่ระบบของ Supervisor</h1>";

try {
    $db = new Database();
    echo "✅ Database connection successful<br><br>";
    
    // Get supervisor account details
    $sql = "SELECT u.user_id, u.username, u.full_name, u.email, u.role_id, r.role_name, u.is_active, u.password_hash
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            WHERE r.role_name = 'supervisor' AND u.is_active = 1";
    
    $supervisors = $db->fetchAll($sql);
    
    if (!empty($supervisors)) {
        echo "<h3>✅ พบบัญชี Supervisor:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th><th>Password Hash</th>";
        echo "</tr>";
        
        foreach ($supervisors as $supervisor) {
            echo "<tr>";
            echo "<td>{$supervisor['user_id']}</td>";
            echo "<td><strong>{$supervisor['username']}</strong></td>";
            echo "<td>{$supervisor['full_name']}</td>";
            echo "<td>" . ($supervisor['email'] ?? 'N/A') . "</td>";
            echo "<td>{$supervisor['role_name']}</td>";
            echo "<td>" . ($supervisor['is_active'] ? 'Active' : 'Inactive') . "</td>";
            echo "<td>" . substr($supervisor['password_hash'], 0, 20) . "...</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        echo "<h3>📋 วิธีการทดสอบ:</h3>";
        echo "<ol>";
        echo "<li><strong>เข้าสู่ระบบด้วยบัญชี Supervisor:</strong><br>";
        echo "   - ไปที่: <a href='login.php' target='_blank'>login.php</a><br>";
        echo "   - Username: <strong>{$supervisors[0]['username']}</strong><br>";
        echo "   - Password: (ตรวจสอบในฐานข้อมูล)<br><br>";
        
        echo "<li><strong>ทดสอบการเข้าถึงหน้า team.php:</strong><br>";
        echo "   - หลังจากเข้าสู่ระบบแล้ว ไปที่: <a href='team.php' target='_blank'>team.php</a><br><br>";
        
        echo "<li><strong>ทดสอบการเข้าถึงหน้า team.php โดยตรง:</strong><br>";
        echo "   - ไปที่: <a href='test_team_page.php' target='_blank'>test_team_page.php</a><br><br>";
        echo "</ol>";
        
        echo "<h3>🔧 ข้อมูลเพิ่มเติม:</h3>";
        echo "<p><strong>Supervisor ID:</strong> {$supervisors[0]['user_id']}</p>";
        echo "<p><strong>Role ID:</strong> {$supervisors[0]['role_id']}</p>";
        echo "<p><strong>Status:</strong> " . ($supervisors[0]['is_active'] ? 'Active' : 'Inactive') . "</p>";
        
    } else {
        echo "<h3>❌ ไม่พบบัญชี Supervisor</h3>";
        echo "<p>ไม่พบผู้ใช้ที่มี role_name = 'supervisor' ในระบบ</p>";
        
        // Show all users for reference
        $sql = "SELECT u.user_id, u.username, u.full_name, r.role_name, u.is_active 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.role_id 
                ORDER BY u.user_id";
        
        $users = $db->fetchAll($sql);
        
        echo "<h3>ผู้ใช้ทั้งหมดในระบบ:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Status</th>";
        echo "</tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['user_id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td>{$user['role_name']}</td>";
            echo "<td>" . ($user['is_active'] ? 'Active' : 'Inactive') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='background-color: #ffe6e6; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<br><hr><br>";
echo "<h2>Debug Information:</h2>";
echo "<p>Current session data:</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>
