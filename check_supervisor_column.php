<?php
/**
 * Check and add supervisor_id column if needed
 */

require_once 'config/config.php';
require_once 'app/core/Database.php';

try {
    $db = new Database();
    
    echo "<h2>ตรวจสอบคอลัมน์ supervisor_id</h2>";
    
    // Check if column exists
    $sql = "SHOW COLUMNS FROM users LIKE 'supervisor_id'";
    $result = $db->fetchOne($sql);
    
    if ($result) {
        echo "✅ คอลัมน์ supervisor_id มีอยู่แล้ว<br>";
    } else {
        echo "❌ คอลัมน์ supervisor_id ยังไม่มี กำลังเพิ่ม...<br>";
        
        // Add the column
        $sql = "ALTER TABLE `users` 
                ADD COLUMN `supervisor_id` INT NULL AFTER `company_id`,
                ADD FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL";
        $db->execute($sql);
        
        // Add index
        $sql = "ALTER TABLE `users` ADD INDEX `idx_supervisor_id` (`supervisor_id`)";
        $db->execute($sql);
        
        // Update existing telesales users
        $sql = "UPDATE `users` SET `supervisor_id` = 2 WHERE `role_id` = 4 AND `user_id` IN (3, 4)";
        $db->execute($sql);
        
        // Add comment
        $sql = "ALTER TABLE `users` MODIFY COLUMN `supervisor_id` INT NULL COMMENT 'References user_id of supervisor who manages this user'";
        $db->execute($sql);
        
        echo "✅ เพิ่มคอลัมน์ supervisor_id เสร็จสิ้น<br>";
    }
    
    // Show current users with supervisor_id
    echo "<h3>ข้อมูลผู้ใช้ปัจจุบัน:</h3>";
    $sql = "SELECT u.*, r.role_name, c.company_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            LEFT JOIN companies c ON u.company_id = c.company_id 
            ORDER BY u.user_id";
    
    $users = $db->fetchAll($sql);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Company</th><th>Supervisor ID</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['user_id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role_name']}</td>";
        echo "<td>{$user['company_name']}</td>";
        echo "<td>" . ($user['supervisor_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='background-color: #ffe6e6; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ เกิดข้อผิดพลาด:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
