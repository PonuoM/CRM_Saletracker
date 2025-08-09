# การแก้ไขปัญหา Followup Customers - สรุป

## ปัญหาที่พบ

**อาการ**: ในหน้า Customer Management แถบ "การโทรติดตาม"
- **KPI Card** แสดงจำนวน 2 รายชื่อ
- **ตาราง** แสดงข้อความ "ไม่มีลูกค้าที่ต้องติดตาม"

## สาเหตุของปัญหา

### 1. **ความไม่สอดคล้องของเงื่อนไขการคำนวณ**

#### **KPI Card (DashboardService.php)**
```php
// เงื่อนไขเดิม (ผิด)
"SELECT COUNT(*) as count FROM customers 
 WHERE assigned_to = :user_id AND basket_type = 'follow_up'"
```

#### **ตาราง (CustomerService.php)**
```php
// เงื่อนไขที่ถูกต้อง
"SELECT c.*, u.full_name as assigned_to_name,
        DATEDIFF(c.customer_time_expiry, NOW()) as days_remaining,
        DATEDIFF(c.next_followup_at, NOW()) as followup_days,
        CASE 
            WHEN c.customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'expiry'
            WHEN c.next_followup_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'appointment'
            ELSE 'other'
        END as reason_type
 FROM customers c 
 LEFT JOIN users u ON c.assigned_to = u.user_id
 WHERE c.assigned_to = :telesales_id 
 AND c.basket_type = 'assigned'
 AND c.is_active = 1
 AND (
     c.customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 7 DAY) OR
     c.next_followup_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
 )"
```

### 2. **ปัญหาในฐานข้อมูล**

จากไฟล์ `primacom_Customer.sql`:
- คอลัมน์ `basket_type` เป็น `enum('distribution','waiting','assigned')`
- **ไม่มีค่า `'follow_up'`** ในฐานข้อมูล
- ดังนั้นเงื่อนไข `basket_type = 'follow_up'` จะไม่พบข้อมูลใดๆ

## การแก้ไข

### ไฟล์ที่แก้ไข: `app/services/DashboardService.php`

#### **ก่อนแก้ไข**
```php
private function getFollowUpCustomers($userId) {
    $result = $this->db->fetchOne(
        "SELECT COUNT(*) as count FROM customers WHERE assigned_to = :user_id AND basket_type = 'follow_up'",
        ['user_id' => $userId]
    );
    return $result['count'] ?? 0;
}
```

#### **หลังแก้ไข**
```php
private function getFollowUpCustomers($userId) {
    $result = $this->db->fetchOne(
        "SELECT COUNT(*) as count FROM customers 
         WHERE assigned_to = :user_id 
         AND basket_type = 'assigned'
         AND is_active = 1
         AND (
             customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 7 DAY) OR
             next_followup_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
         )",
        ['user_id' => $userId]
    );
    return $result['count'] ?? 0;
}
```

## ผลลัพธ์ที่คาดหวัง

### หลังการแก้ไข:
1. **KPI Card** และ **ตาราง** จะแสดงจำนวนลูกค้าที่ต้องติดตามเดียวกัน
2. ระบบจะนับลูกค้าที่:
   - มี `basket_type = 'assigned'`
   - มี `is_active = 1`
   - มี `customer_time_expiry` ใกล้หมดอายุ (ภายใน 7 วัน) **หรือ**
   - มี `next_followup_at` ที่ถึงกำหนดแล้ว

### ข้อมูลที่ใช้ในการคำนวณ:
- **customer_time_expiry**: วันหมดอายุการดูแลลูกค้า
- **next_followup_at**: วันนัดหมายติดตามครั้งถัดไป
- **basket_type**: ประเภทตะกร้า (assigned = มอบหมายแล้ว)
- **is_active**: สถานะการใช้งาน

## การทดสอบ

ใช้ไฟล์ `test_followup_fix.php` เพื่อทดสอบ:
1. ตรวจสอบข้อมูลในฐานข้อมูล
2. ทดสอบ DashboardService (KPI Card)
3. ทดสอบ CustomerService (ตาราง)
4. เปรียบเทียบผลลัพธ์

## สรุป

ปัญหานี้เกิดจาก **ความไม่สอดคล้องของเงื่อนไขการคำนวณ** ระหว่าง KPI Card และตาราง โดย KPI Card ใช้เงื่อนไขที่ไม่ถูกต้อง (`basket_type = 'follow_up'`) ในขณะที่ตารางใช้เงื่อนไขที่ถูกต้อง การแก้ไขโดยปรับ DashboardService ให้ใช้เงื่อนไขเดียวกับ CustomerService จะทำให้ระบบแสดงข้อมูลที่สอดคล้องกัน
