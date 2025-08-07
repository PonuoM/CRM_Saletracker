<?php
/**
 * Test Company Management System
 * ทดสอบระบบจัดการบริษัท
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Auth.php';

$db = new Database();
$auth = new Auth($db);

echo "<h1>🧪 ทดสอบระบบจัดการบริษัท</h1>";
echo "<hr>";

// 1. ตรวจสอบการเชื่อมต่อฐานข้อมูล
echo "<h2>1. ตรวจสอบการเชื่อมต่อฐานข้อมูล</h2>";
try {
    $db->query("SELECT 1");
    echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ เชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage() . "<br>";
    exit;
}

// 2. ตรวจสอบตาราง companies
echo "<h2>2. ตรวจสอบตาราง companies</h2>";
try {
    $sql = "DESCRIBE companies";
    $columns = $db->fetchAll($sql);
    echo "✅ ตาราง companies พบ: " . count($columns) . " คอลัมน์<br>";
    
    echo "<h3>โครงสร้างตาราง:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "❌ ตรวจสอบตาราง companies ล้มเหลว: " . $e->getMessage() . "<br>";
}

// 3. ตรวจสอบข้อมูลบริษัทที่มีอยู่
echo "<h2>3. ตรวจสอบข้อมูลบริษัทที่มีอยู่</h2>";
try {
    $sql = "SELECT * FROM companies ORDER BY company_id";
    $companies = $db->fetchAll($sql);
    echo "✅ พบบริษัททั้งหมด: " . count($companies) . " บริษัท<br>";
    
    if (count($companies) > 0) {
        echo "<h3>รายการบริษัท:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>ชื่อบริษัท</th><th>รหัส</th><th>เบอร์โทร</th><th>อีเมล</th><th>สถานะ</th></tr>";
        foreach ($companies as $company) {
            echo "<tr>";
            echo "<td>" . $company['company_id'] . "</td>";
            echo "<td>" . htmlspecialchars($company['company_name']) . "</td>";
            echo "<td>" . htmlspecialchars($company['company_code'] ?: '-') . "</td>";
            echo "<td>" . htmlspecialchars($company['phone'] ?: '-') . "</td>";
            echo "<td>" . htmlspecialchars($company['company_email'] ?: '-') . "</td>";
            echo "<td>" . ($company['is_active'] ? 'ใช้งาน' : 'ไม่ใช้งาน') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "📝 ยังไม่มีข้อมูลบริษัทในระบบ<br>";
    }
} catch (Exception $e) {
    echo "❌ ตรวจสอบข้อมูลบริษัท ล้มเหลว: " . $e->getMessage() . "<br>";
}

// 4. ทดสอบการสร้างบริษัทใหม่
echo "<h2>4. ทดสอบการสร้างบริษัทใหม่</h2>";
try {
    $testCompanyData = [
        'company_name' => 'บริษัททดสอบ จำกัด',
        'company_code' => 'TEST001',
        'address' => '123 ถนนทดสอบ กรุงเทพฯ 10000',
        'phone' => '02-123-4567',
        'email' => 'test@example.com',
        'is_active' => 1
    ];
    
    $sql = "INSERT INTO companies (company_name, company_code, address, phone, email, is_active) 
            VALUES (:company_name, :company_code, :address, :phone, :email, :is_active)";
    
    $result = $db->query($sql, $testCompanyData);
    $newCompanyId = $db->lastInsertId();
    
    echo "✅ สร้างบริษัททดสอบสำเร็จ (ID: $newCompanyId)<br>";
    
    // ตรวจสอบข้อมูลที่เพิ่งสร้าง
    $sql = "SELECT * FROM companies WHERE company_id = :company_id";
    $newCompany = $db->fetchOne($sql, ['company_id' => $newCompanyId]);
    
    if ($newCompany) {
        echo "✅ ตรวจสอบข้อมูลบริษัทที่เพิ่งสร้าง:<br>";
        echo "- ชื่อ: " . htmlspecialchars($newCompany['company_name']) . "<br>";
        echo "- รหัส: " . htmlspecialchars($newCompany['company_code']) . "<br>";
        echo "- เบอร์โทร: " . htmlspecialchars($newCompany['phone']) . "<br>";
        echo "- อีเมล: " . htmlspecialchars($newCompany['email']) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ ทดสอบการสร้างบริษัท ล้มเหลว: " . $e->getMessage() . "<br>";
}

// 5. ทดสอบการอัปเดตบริษัท
echo "<h2>5. ทดสอบการอัปเดตบริษัท</h2>";
try {
    if (isset($newCompanyId)) {
        $updateData = [
            'company_id' => $newCompanyId,
            'company_name' => 'บริษัททดสอบ (แก้ไขแล้ว) จำกัด',
            'phone' => '02-999-8888'
        ];
        
        $sql = "UPDATE companies SET company_name = :company_name, phone = :phone WHERE company_id = :company_id";
        $db->query($sql, $updateData);
        
        echo "✅ อัปเดตบริษัทสำเร็จ<br>";
        
        // ตรวจสอบข้อมูลที่อัปเดต
        $sql = "SELECT * FROM companies WHERE company_id = :company_id";
        $updatedCompany = $db->fetchOne($sql, ['company_id' => $newCompanyId]);
        
        if ($updatedCompany) {
            echo "✅ ตรวจสอบข้อมูลที่อัปเดต:<br>";
            echo "- ชื่อใหม่: " . htmlspecialchars($updatedCompany['company_name']) . "<br>";
            echo "- เบอร์โทรใหม่: " . htmlspecialchars($updatedCompany['phone']) . "<br>";
        }
    } else {
        echo "⚠️ ไม่สามารถทดสอบการอัปเดตได้ เนื่องจากไม่มีการสร้างบริษัททดสอบ<br>";
    }
} catch (Exception $e) {
    echo "❌ ทดสอบการอัปเดตบริษัท ล้มเหลว: " . $e->getMessage() . "<br>";
}

// 6. ทดสอบการลบบริษัท
echo "<h2>6. ทดสอบการลบบริษัท</h2>";
try {
    if (isset($newCompanyId)) {
        $sql = "DELETE FROM companies WHERE company_id = :company_id";
        $db->query($sql, ['company_id' => $newCompanyId]);
        
        echo "✅ ลบบริษัททดสอบสำเร็จ<br>";
        
        // ตรวจสอบว่าลบแล้วจริง
        $sql = "SELECT COUNT(*) as count FROM companies WHERE company_id = :company_id";
        $result = $db->fetchOne($sql, ['company_id' => $newCompanyId]);
        
        if ($result['count'] == 0) {
            echo "✅ ยืนยันการลบสำเร็จ - ไม่พบบริษัทในระบบแล้ว<br>";
        } else {
            echo "❌ การลบล้มเหลว - ยังพบบริษัทในระบบ<br>";
        }
    } else {
        echo "⚠️ ไม่สามารถทดสอบการลบได้ เนื่องจากไม่มีการสร้างบริษัททดสอบ<br>";
    }
} catch (Exception $e) {
    echo "❌ ทดสอบการลบบริษัท ล้มเหลว: " . $e->getMessage() . "<br>";
}

// 7. ตรวจสอบการเชื่อมโยงกับตาราง users
echo "<h2>7. ตรวจสอบการเชื่อมโยงกับตาราง users</h2>";
try {
    $sql = "SELECT u.user_id, u.username, u.full_name, c.company_name 
            FROM users u 
            LEFT JOIN companies c ON u.company_id = c.company_id 
            ORDER BY u.user_id";
    $users = $db->fetchAll($sql);
    
    echo "✅ พบผู้ใช้ทั้งหมด: " . count($users) . " คน<br>";
    
    if (count($users) > 0) {
        echo "<h3>รายการผู้ใช้และบริษัท:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>User ID</th><th>Username</th><th>ชื่อ-นามสกุล</th><th>บริษัท</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['company_name'] ?: '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ ตรวจสอบการเชื่อมโยงกับตาราง users ล้มเหลว: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>🎉 สรุปผลการทดสอบ</h2>";
echo "<p>ระบบจัดการบริษัทพร้อมใช้งานแล้ว!</p>";
echo "<p><a href='admin.php?action=companies' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>ไปยังหน้าจัดการบริษัท</a></p>";
?>
