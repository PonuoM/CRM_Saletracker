# การแก้ไขปัญหาการเปลี่ยนผู้ดูแลลูกค้า (Change Customer Assignee)

## ปัญหาที่พบ

หน้า `https://www.prima49.com/Customer/customers.php?action=show&id=20591` 
ติดปัญหาในส่วนการเปลี่ยนผู้ดูแลลูกค้าใน role 1 โดยเกิด error:

```
/Customer/api/customers.php:1 Failed to load resource: the server responded with a status of 405 ()
เกิดข้อผิดพลาด: Action not supported for POST method
```

## สาเหตุของปัญหา

1. **API Endpoint ไม่สามารถอ่าน action parameter ได้**: JavaScript ส่ง `action` ผ่าน POST body แต่ API อ่านจาก query string
2. **การตรวจสอบสิทธิ์ไม่ครอบคลุม**: ผู้ใช้ที่มี `role_id = 1` ไม่สามารถเข้าถึงฟังก์ชันได้

## การแก้ไขที่ทำ

### 1. แก้ไข API Endpoint (`api/customers.php`)

**ก่อนแก้ไข:**
```php
// Get action from query string or request body
$action = $_GET['action'] ?? '';
```

**หลังแก้ไข:**
```php
// Get action from query string or request body
$action = $_GET['action'] ?? $_POST['action'] ?? '';
```

**ผลลัพธ์:** API สามารถอ่าน `action` จากทั้ง query string และ POST body ได้

### 2. แก้ไขการตรวจสอบสิทธิ์ (`app/controllers/CustomerController.php`)

**ก่อนแก้ไข:**
```php
// ตรวจสอบสิทธิ์ (เฉพาะ Admin และ Super Admin)
$roleName = $_SESSION['role_name'] ?? '';
if (!in_array($roleName, ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    return;
}
```

**หลังแก้ไข:**
```php
// ตรวจสอบสิทธิ์ (Admin, Super Admin, และ Role ID 1)
$roleName = $_SESSION['role_name'] ?? '';
$roleId = $_SESSION['role_id'] ?? 0;

// อนุญาตให้ admin, super_admin และ role_id = 1 เข้าถึงได้
if (!in_array($roleName, ['admin', 'super_admin']) && $roleId != 1) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    return;
}
```

**ผลลัพธ์:** ผู้ใช้ที่มี `role_id = 1` สามารถเข้าถึงฟังก์ชันเปลี่ยนผู้ดูแลลูกค้าได้

## ไฟล์ที่แก้ไข

1. `api/customers.php` - แก้ไขการอ่าน action parameter
2. `app/controllers/CustomerController.php` - แก้ไขการตรวจสอบสิทธิ์

## ไฟล์ทดสอบที่สร้าง

1. `test_change_assignee_api.php` - ทดสอบ API ก่อนแก้ไข
2. `test_change_assignee_fixed.php` - ทดสอบ API หลังแก้ไข
3. `check_roles_table.php` - ตรวจสอบโครงสร้างตาราง roles

## วิธีการทดสอบ

1. **ทดสอบผ่านหน้าเว็บ:**
   - เข้าสู่ระบบด้วยผู้ใช้ที่มี `role_id = 1`
   - ไปที่หน้าลูกค้าและลองเปลี่ยนผู้ดูแล
   - ตรวจสอบว่าไม่เกิด error 405

2. **ทดสอบผ่านไฟล์ทดสอบ:**
   - รัน `test_change_assignee_fixed.php`
   - ใช้ฟอร์มทดสอบเพื่อส่ง POST request
   - ตรวจสอบ HTTP response code และ response body

## ผลลัพธ์ที่คาดหวัง

- ✅ ไม่เกิด error 405 (Method Not Allowed)
- ✅ API สามารถอ่าน action parameter ได้
- ✅ ผู้ใช้ที่มี `role_id = 1` สามารถเปลี่ยนผู้ดูแลลูกค้าได้
- ✅ ฟังก์ชันยังคงทำงานได้สำหรับ admin และ super_admin
- ✅ ไม่กระทบกับส่วนอื่นของระบบ

## หมายเหตุ

- การแก้ไขนี้รักษาความเข้ากันได้ย้อนหลัง (backward compatibility)
- ไม่มีการเปลี่ยนแปลงโครงสร้างฐานข้อมูล
- ไม่กระทบกับฟังก์ชันอื่นๆ ในระบบ
- ใช้ session data ที่มีอยู่แล้ว (`$_SESSION['role_id']`)

## การตรวจสอบเพิ่มเติม

หากยังมีปัญหา ให้ตรวจสอบ:

1. **Session data:** ตรวจสอบว่า `$_SESSION['role_id']` มีค่าถูกต้อง
2. **Database connection:** ตรวจสอบการเชื่อมต่อฐานข้อมูล
3. **Table structure:** ตรวจสอบโครงสร้างตาราง `customer_activities`
4. **User permissions:** ตรวจสอบสิทธิ์ของผู้ใช้ในระบบ
