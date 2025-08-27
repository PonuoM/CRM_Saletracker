# 🗂️ Customer Basket Management System - การอัปเดตระบบจัดการตะกร้าลูกค้า

## 📋 สรุปการอัปเดต

**วันที่:** 12 สิงหาคม 2025  
**การอัปเดต:** เพิ่มระบบจัดการตะกร้าลูกค้าอัตโนมัติใน Cron Jobs

---

## 🎯 ปัญหาที่แก้ไข

### ❌ **ปัญหาเดิม:**
1. **ไม่มีการย้ายตะกร้าอัตโนมัติ** - ลูกค้าที่หมดเวลาถือครองไม่ถูกดึงกลับ
2. **ระบบ Recall แยกกัน** - มี 2 ระบบที่ทำงานไม่สอดคล้องกัน
3. **ไม่มีการจัดการตะกร้ารอ** - ลูกค้าใน waiting basket ไม่ถูกย้ายกลับ

### ✅ **การแก้ไข:**
เพิ่มฟังก์ชัน `customerBasketManagement()` ใน `CronJobService.php` ที่ทำงาน **3 ขั้นตอน:**

---

## 🔄 ระบบการจัดการตะกร้าใหม่

### **ขั้นตอนที่ 1: ดึงลูกค้าใหม่กลับ**
```sql
-- ลูกค้าใหม่ที่หมดเวลาถือครอง (>30 วัน) → distribution
UPDATE customers 
SET basket_type = 'distribution',
    assigned_to = NULL,
    assigned_at = NULL,
    recall_at = NOW(),
    recall_reason = 'new_customer_timeout'
WHERE basket_type = 'assigned'
AND assigned_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
AND customer_id NOT IN (SELECT DISTINCT customer_id FROM orders WHERE created_at > assigned_at)
AND customer_id NOT IN (SELECT DISTINCT customer_id FROM appointments WHERE created_at > assigned_at)
```

### **ขั้นตอนที่ 2: ดึงลูกค้าเก่าไปตะกร้ารอ**
```sql
-- ลูกค้าเก่าที่ไม่มีออเดอร์ใน 90 วัน → waiting
UPDATE customers 
SET basket_type = 'waiting',
    assigned_to = NULL,
    assigned_at = NULL,
    recall_at = NOW(),
    recall_reason = 'existing_customer_timeout'
WHERE basket_type = 'assigned'
AND customer_id IN (
    SELECT customer_id FROM (
        SELECT customer_id FROM orders 
        GROUP BY customer_id 
        HAVING MAX(order_date) < DATE_SUB(NOW(), INTERVAL 90 DAY)
    ) as old_customers
)
```

### **ขั้นตอนที่ 3: ย้ายจากตะกร้ารอไปตะกร้าพร้อมแจก**
```sql
-- ลูกค้าในตะกร้ารอครบ 30 วัน → distribution
UPDATE customers 
SET basket_type = 'distribution',
    recall_at = NULL,
    recall_reason = NULL
WHERE basket_type = 'waiting'
AND recall_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
```

---

## 📊 Business Logic

### **ตะกร้าลูกค้า (Customer Baskets):**

#### **1. Distribution Basket (ตะกร้าพร้อมแจก)**
- ลูกค้าใหม่ที่รอการมอบหมาย
- ลูกค้าที่ถูกดึงกลับจาก assigned (หมดเวลา 30 วัน)
- ลูกค้าที่ย้ายมาจาก waiting (รอครบ 30 วัน)

#### **2. Assigned Basket (ตะกร้าที่มอบหมายแล้ว)**
- ลูกค้าที่ได้รับมอบหมายให้ Telesales
- มีเวลาถือครอง 30 วัน สำหรับลูกค้าใหม่
- มีเวลาถือครอง 90 วัน สำหรับลูกค้าเก่า (ที่มีออเดอร์)

#### **3. Waiting Basket (ตะกร้ารอ)**
- ลูกค้าเก่าที่ไม่มีออเดอร์ใน 90 วัน
- รอ 30 วัน แล้วจะกลับไป distribution

---

## 🔧 ไฟล์ที่อัปเดต

### **1. `app/services/CronJobService.php`**
- ✅ เพิ่มฟังก์ชัน `customerBasketManagement()`
- ✅ เพิ่มฟังก์ชัน `logBasketActivity()`
- ✅ อัปเดต `runAllJobs()` ให้เรียกใช้ฟังก์ชันใหม่

### **2. `cron/run_all_jobs.php`**
- ✅ อัปเดตการแสดงผลให้รองรับ basket management

---

## 📈 ผลลัพธ์ที่คาดหวัง

### **เมื่อ Cron Job รัน จะได้ผลลัพธ์:**
```
✅ basket_management: New recalled: 5, Existing recalled: 3, Moved to distribution: 2
✅ grade_update: Updated 15 records
✅ temperature_update: Updated 8 records
✅ recall_list: Found 10 customers
✅ notifications: Sent 3 notifications
✅ call_followup: Updated 7 records
```

### **การบันทึก Activity Logs:**
- `new_customer_recall` - ดึงลูกค้าใหม่กลับไปตะกร้าพร้อมแจก
- `existing_customer_recall` - ดึงลูกค้าเก่าไปตะกร้ารอ
- `waiting_to_distribution` - ย้ายลูกค้าจากตะกร้ารอไปตะกร้าพร้อมแจก

---

## 🕐 การทำงานของ Cron Jobs

### **ลำดับการทำงาน (ใหม่):**
1. **🗂️ Basket Management** - จัดการตะกร้าลูกค้า
2. **📊 Grade Update** - อัปเดตเกรดลูกค้า
3. **🌡️ Temperature Update** - อัปเดตอุณหภูมิลูกค้า
4. **📋 Recall List** - สร้างรายการติดตาม
5. **🔔 Notifications** - ส่งการแจ้งเตือน
6. **📞 Call Follow-up** - อัปเดตการติดตามการโทร
7. **🧹 Data Cleanup** - ทำความสะอาดข้อมูล (วันอาทิตย์)

---

## 🧪 การทดสอบ

### **ทดสอบผ่าน Web:**
```
http://localhost/CRM_Saletracker/fix_cron_setup.php
→ กดปุ่ม "ทดสอบ Cron Jobs"
```

### **ทดสอบผ่าน Command Line:**
```bash
php /path/to/cron/run_all_jobs.php
```

### **ตรวจสอบผลลัพธ์:**
1. **Log File:** `logs/cron.log`
2. **Database:** ตาราง `activity_logs`
3. **Customer Status:** ตรวจสอบ `basket_type` ในตาราง `customers`

---

## 📊 สถิติการอัปเดต

### **ไฟล์ที่แก้ไข:**
- ✅ `app/services/CronJobService.php` - เพิ่ม 110+ บรรทัด
- ✅ `cron/run_all_jobs.php` - แก้ไข 3 บรรทัด

### **ฟังก์ชันใหม่:**
- ✅ `customerBasketManagement()` - ฟังก์ชันหลัก
- ✅ `logBasketActivity()` - บันทึกกิจกรรม

### **การปรับปรุง:**
- ✅ ระบบจัดการตะกร้าอัตโนมัติ
- ✅ การบันทึก activity logs
- ✅ การแสดงผลที่ละเอียดขึ้น

---

## ⚠️ หมายเหตุสำคัญ

### **เงื่อนไขการดึงกลับ:**
1. **ลูกค้าใหม่** - ไม่มีออเดอร์หรือนัดหมายหลังจากได้รับมอบหมาย
2. **ลูกค้าเก่า** - ไม่มีออเดอร์ใน 90 วันล่าสุด
3. **ตะกร้ารอ** - รอครบ 30 วันแล้วจะกลับไป distribution

### **การป้องกันข้อผิดพลาด:**
- ✅ ใช้ Transaction เพื่อความปลอดภัย
- ✅ บันทึก Error Logs
- ✅ ตรวจสอบเงื่อนไขก่อนย้าย

---

## 🎉 สรุป

ระบบจัดการตะกร้าลูกค้าได้รับการอัปเดตให้ทำงานอัตโนมัติแล้ว! 

**ประโยชน์:**
- ✅ ลูกค้าไม่ค้างใน assigned เกินเวลา
- ✅ ระบบ recall ทำงานอัตโนมัติ
- ✅ การจัดการตะกร้ารอที่มีประสิทธิภาพ
- ✅ การบันทึกกิจกรรมที่ครบถ้วน

**ขั้นตอนต่อไป:**
1. ทดสอบระบบใหม่
2. ตรวจสอบ log การทำงาน
3. ปรับแต่งเวลาหากจำเป็น

---

**วันที่อัปเดต:** 12 สิงหาคม 2025  
**ผู้อัปเดต:** AI Assistant  
**เวอร์ชัน:** 1.1.0
