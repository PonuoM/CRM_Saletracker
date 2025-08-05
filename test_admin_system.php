<?php
/**
 * Test Admin System
 * ทดสอบระบบ Admin และการตั้งค่า
 */

session_start();

// ตรวจสอบการยืนยันตัวตน
if (!isset($_SESSION['user_id'])) {
    echo "<h1>❌ ไม่ได้เข้าสู่ระบบ</h1>";
    echo "<p>กรุณาเข้าสู่ระบบก่อนทดสอบ</p>";
    echo "<a href='login.php'>เข้าสู่ระบบ</a>";
    exit;
}

// ตรวจสอบสิทธิ์ Admin
$roleName = $_SESSION['role_name'] ?? '';
if (!in_array($roleName, ['admin', 'super_admin'])) {
    echo "<h1>❌ ไม่มีสิทธิ์เข้าถึง</h1>";
    echo "<p>คุณไม่มีสิทธิ์เข้าถึงระบบ Admin</p>";
    echo "<p>บทบาทปัจจุบัน: $roleName</p>";
    echo "<a href='dashboard.php'>กลับไป Dashboard</a>";
    exit;
}

echo "<h1>✅ ระบบ Admin - การทดสอบ</h1>";
echo "<hr>";

echo "<h2>📊 ข้อมูลผู้ใช้ปัจจุบัน</h2>";
echo "<ul>";
echo "<li><strong>User ID:</strong> " . $_SESSION['user_id'] . "</li>";
echo "<li><strong>Username:</strong> " . $_SESSION['username'] . "</li>";
echo "<li><strong>Full Name:</strong> " . $_SESSION['full_name'] . "</li>";
echo "<li><strong>Role:</strong> " . $_SESSION['role_name'] . "</li>";
echo "</ul>";

echo "<h2>🔗 ลิงก์ทดสอบระบบ Admin</h2>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='admin.php' style='display: inline-block; margin: 10px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>";
echo "🏠 Admin Dashboard";
echo "</a>";

echo "<a href='admin.php?action=users' style='display: inline-block; margin: 10px; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>";
echo "👥 จัดการผู้ใช้";
echo "</a>";

echo "<a href='admin.php?action=products' style='display: inline-block; margin: 10px; padding: 10px 20px; background: #ffc107; color: black; text-decoration: none; border-radius: 5px;'>";
echo "📦 จัดการสินค้า";
echo "</a>";

echo "<a href='admin.php?action=settings' style='display: inline-block; margin: 10px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;'>";
echo "⚙️ ตั้งค่าระบบ";
echo "</a>";
echo "</div>";

echo "<h2>📋 รายการทดสอบ</h2>";
echo "<ol>";
echo "<li><strong>Admin Dashboard:</strong> ตรวจสอบหน้า Dashboard หลัก</li>";
echo "<li><strong>User Management:</strong> ทดสอบการสร้าง/แก้ไข/ลบผู้ใช้</li>";
echo "<li><strong>Product Management:</strong> ทดสอบการจัดการสินค้า</li>";
echo "<li><strong>System Settings:</strong> ทดสอบการตั้งค่าระบบ</li>";
echo "</ol>";

echo "<h2>⚠️ หมายเหตุ</h2>";
echo "<ul>";
echo "<li>ระบบ Admin เปิดใช้งานสำหรับ Admin และ Super Admin เท่านั้น</li>";
echo "<li>การลบผู้ใช้จะตรวจสอบความสัมพันธ์กับข้อมูลอื่นก่อน</li>";
echo "<li>การตั้งค่าระบบจะส่งผลต่อการทำงานของระบบทันที</li>";
echo "<li>กรุณาทดสอบอย่างระมัดระวังในระบบ Production</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='dashboard.php'>← กลับไป Dashboard</a> | <a href='logout.php'>ออกจากระบบ</a></p>";
?> 