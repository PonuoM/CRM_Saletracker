# การแก้ไขปัญหา assigned_to_name ในแท็บติดตาม

## ปัญหาที่พบ
ในหน้า customers.php แท็บ "ติดตาม" คอลัมน์ "ผู้รับผิดชอบ" ไม่แสดงข้อมูล (แสดงเป็น "-" หรือค่าว่าง)

## สาเหตุของปัญหา
1. ในฟังก์ชัน `getFollowUpCustomers()` ใน `CustomerService.php` ไม่มีการ JOIN กับตาราง `users` เพื่อดึง `assigned_to_name`
2. ในฟังก์ชัน `getFollowups()` ใน `CustomerController.php` สำหรับ supervisor/admin ไม่มีการ JOIN กับตาราง `users` เช่นกัน

## การแก้ไข

### 1. แก้ไข CustomerService.php
**ไฟล์:** `app/services/CustomerService.php`
**ฟังก์ชัน:** `getFollowUpCustomers()`

**ก่อนแก้ไข:**
```sql
SELECT c.*, 
       DATEDIFF(c.customer_time_expiry, NOW()) as days_remaining,
       DATEDIFF(c.next_followup_at, NOW()) as followup_days,
       CASE 
           WHEN c.customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'expiry'
           WHEN c.next_followup_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'appointment'
           ELSE 'other'
       END as reason_type
FROM customers c 
WHERE c.assigned_to = :telesales_id 
AND c.basket_type = 'assigned'
AND c.is_active = 1
AND (
    c.customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 7 DAY) OR
    c.next_followup_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
)
ORDER BY c.customer_time_expiry ASC, c.next_followup_at ASC
```

**หลังแก้ไข:**
```sql
SELECT c.*, u.full_name as assigned_to_name,
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
)
ORDER BY c.customer_time_expiry ASC, c.next_followup_at ASC
```

### 2. แก้ไข CustomerController.php
**ไฟล์:** `app/controllers/CustomerController.php`
**ฟังก์ชัน:** `getFollowups()`

**ก่อนแก้ไข:**
```sql
SELECT c.*, 
        DATEDIFF(c.customer_time_expiry, NOW()) as days_remaining,
        DATEDIFF(c.next_followup_at, NOW()) as followup_days,
        CASE 
            WHEN c.customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'expiry'
            WHEN c.next_followup_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'appointment'
            ELSE 'other'
        END as reason_type
FROM customers c
WHERE $where
ORDER BY c.customer_time_expiry ASC, c.next_followup_at ASC
```

**หลังแก้ไข:**
```sql
SELECT c.*, u.full_name as assigned_to_name,
        DATEDIFF(c.customer_time_expiry, NOW()) as days_remaining,
        DATEDIFF(c.next_followup_at, NOW()) as followup_days,
        CASE 
            WHEN c.customer_time_expiry <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'expiry'
            WHEN c.next_followup_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'appointment'
            ELSE 'other'
        END as reason_type
FROM customers c
LEFT JOIN users u ON c.assigned_to = u.user_id
WHERE $where
ORDER BY c.customer_time_expiry ASC, c.next_followup_at ASC
```

## ผลลัพธ์ที่คาดหวัง
หลังจากแก้ไขแล้ว คอลัมน์ "ผู้รับผิดชอบ" ในแท็บ "ติดตาม" จะแสดงชื่อของ Telesales ที่รับผิดชอบลูกค้าแต่ละรายการ

## การทดสอบ
สร้างไฟล์ `test_assigned_to_name_fix.php` เพื่อทดสอบการแก้ไข โดยจะตรวจสอบ:
1. ฟังก์ชัน `getFollowUpCustomers()` ใน CustomerService
2. API `getFollowups()` ใน CustomerController
3. ข้อมูลในฐานข้อมูล

## ไฟล์ที่แก้ไข
- `app/services/CustomerService.php` - บรรทัด 253-275
- `app/controllers/CustomerController.php` - บรรทัด 477-495

## สถานะ
✅ **แก้ไขเสร็จสิ้น** - ปัญหาคอลัมน์ "ผู้รับผิดชอบ" ในแท็บติดตามได้รับการแก้ไขแล้ว
