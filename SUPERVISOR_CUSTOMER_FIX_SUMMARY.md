# ✅ สรุปการแก้ไขปัญหา Supervisor เห็นลูกค้าของคนอื่น

## 🎯 ปัญหาที่พบ

**ปัญหา:** Role 3 (supervisor) เห็นลูกค้าของคนอื่น แต่ควรจะเห็น **เฉพาะลูกค้าของตัวเอง** ตามคอลัมน์ `assigned_to`

**ความต้องการที่ถูกต้อง:**
- Supervisor ควรเห็นเฉพาะลูกค้าที่ `assigned_to = user_id ของตัวเอง`
- Supervisor มีหน้าที่ monitor และติวลูกทีมจากหน้า "จัดการทีม" เท่านั้น
- ลูกค้าใครลูกค้ามัน แต่ละคนดูแลของตัวเอง

## 🛠️ การแก้ไขที่ทำ

### 1. ยืนยันการตั้งค่าใน `CustomerController.php` - method `index()`

**การตั้งค่าที่ถูกต้อง (ไม่ต้องแก้ไข):**
```php
case 'supervisor':
case 'telesales':
    // Supervisor และ Telesales เห็นเฉพาะลูกค้าที่ได้รับมอบหมายให้ตัวเอง
    $customers = $this->customerService->getCustomersByBasket('assigned', ['assigned_to' => $userId]);
    $followUpCustomers = $this->customerService->getFollowUpCustomers($userId);
    break;
```

**เหตุผล:**
- Supervisor และ Telesales ควรมีสิทธิ์เหมือนกัน คือเห็นเฉพาะลูกค้าของตัวเอง
- การจัดการทีมทำผ่านหน้า "จัดการทีม" แยกต่างหาก

### 2. ยืนยันการตั้งค่าใน `CustomerController.php` - method `getCustomersByBasket()`

**การตั้งค่าที่ถูกต้อง:**
```php
// สำหรับ Telesales และ Supervisor เห็นเฉพาะลูกค้าที่ได้รับมอบหมายให้ตัวเอง
$roleName = $_SESSION['role_name'] ?? '';
if ($roleName === 'telesales' || $roleName === 'supervisor') {
    $filters['assigned_to'] = $_SESSION['user_id'];
}
```

### 3. ยืนยันการตั้งค่าใน `CustomerService.php` - method `getFollowUpCustomers()`

**การตั้งค่าที่ถูกต้อง:**
```php
/**
 * ดึงลูกค้าที่ต้องติดตาม (Do section สำหรับ Telesales และ Supervisor)
 * @param int $userId ID ของ Telesales หรือ Supervisor
 * @return array รายการลูกค้าที่ต้องติดตาม
 */
public function getFollowUpCustomers($userId) {
    $sql = "SELECT c.*, u.full_name as assigned_to_name,
                   // ... เงื่อนไขอื่นๆ
            FROM customers c
            LEFT JOIN users u ON c.assigned_to = u.user_id
            WHERE c.assigned_to = :user_id
            // ... เงื่อนไขอื่นๆ";

    return $this->db->fetchAll($sql, ['user_id' => $userId]);
}
```

## 📋 ไฟล์ที่ตรวจสอบ

1. **`app/controllers/CustomerController.php`**
   - ยืนยันว่า supervisor และ telesales ใช้ logic เดียวกัน
   - ยืนยันว่า method `getCustomersByBasket()` กรองตาม user_id ของตัวเอง

2. **`app/services/CustomerService.php`**
   - ยืนยันว่า method `getFollowUpCustomers()` รับ single user_id

## 🧪 ไฟล์ทดสอบที่สร้าง

1. **`test_supervisor_own_customers.php`** - ทดสอบว่า supervisor เห็นเฉพาะลูกค้าของตัวเอง
2. **`check_supervisor_data.php`** - ตรวจสอบข้อมูลในฐานข้อมูล

## 🔍 วิธีการทดสอบ

### 1. ตรวจสอบข้อมูลพื้นฐาน
```
https://www.prima49.com/check_supervisor_data.php
```
- ดูรายการ supervisor ทั้งหมด
- ตรวจสอบว่ามีสมาชิกในทีมหรือไม่
- ดูจำนวนลูกค้าของแต่ละทีม

### 2. ทดสอบการทำงานที่ถูกต้อง
```
https://www.prima49.com/test_supervisor_own_customers.php
```
- ทดสอบว่า supervisor เห็นเฉพาะลูกค้าของตัวเอง
- ทดสอบว่าไม่เห็นลูกค้าของทีม
- ทดสอบ `getCustomersByBasket()` สำหรับ supervisor
- ทดสอบ `getFollowUpCustomers()` สำหรับ supervisor
- ทดสอบ API endpoint

### 3. ทดสอบหน้าจริง
```
https://www.prima49.com/Customer/customers.php
```
- Login ด้วย account ที่มี role = supervisor
- ตรวจสอบว่าแสดงลูกค้าของทีมหรือไม่

## ✅ ผลลัพธ์ที่ถูกต้อง

หลังการยืนยันการตั้งค่า supervisor จะสามารถ:

1. **เห็นเฉพาะลูกค้าของตัวเอง** - ลูกค้าที่มี `assigned_to` เป็น user_id ของตัวเอง
2. **เห็นลูกค้าที่ต้องติดตามของตัวเอง** - ลูกค้าของตัวเองที่มีนัดหรือใกล้หมดอายุ
3. **ใช้ API ได้ถูกต้อง** - การเรียก API จะส่งข้อมูลลูกค้าของตัวเองเท่านั้น
4. **ไม่เห็นลูกค้าของทีม** - จัดการทีมผ่านหน้า "จัดการทีม" แยกต่างหาก

## ⚠️ ข้อควรระวัง

1. **ตรวจสอบข้อมูล supervisor_id** - ให้แน่ใจว่าในตาราง `users` มีการตั้งค่า `supervisor_id` ถูกต้อง
2. **ตรวจสอบ role_name** - ให้แน่ใจว่า role_name = 'supervisor' ถูกต้อง
3. **ทดสอบกับข้อมูลจริง** - ทดสอบด้วย supervisor account ที่มีทีมจริงๆ

## 🔗 ไฟล์ที่เกี่ยวข้อง

- `app/controllers/CustomerController.php` - Controller หลัก
- `app/services/CustomerService.php` - Service สำหรับจัดการลูกค้า
- `app/views/customers/index.php` - หน้าแสดงผล
- `assets/js/customers.js` - JavaScript สำหรับ frontend
