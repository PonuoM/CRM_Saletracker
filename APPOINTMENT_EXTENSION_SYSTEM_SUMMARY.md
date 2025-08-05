# 📅 ระบบการต่อเวลาการนัดหมาย - CRM SalesTracker

## 🎯 สรุประบบ

**วันที่พัฒนา:** 2025-01-02  
**สถานะ:** ✅ **เสร็จสิ้น 100%**  
**เวอร์ชัน:** 1.0.0

---

## 📋 กฎการทำงานหลัก

### 🎯 **กฎการต่อเวลาการนัดหมาย:**
1. **การนัดหมาย 1 ครั้ง = ต่อเวลา 1 เดือน (30 วัน)**
2. **สูงสุด 3 ครั้ง** ต่อลูกค้า
3. **รีเซ็ตตัวนับเมื่อปิดการขาย** - ให้สามารถนัดหมายต่อได้
4. **ต่อเวลาอัตโนมัติ** เมื่อนัดหมายเสร็จสิ้น
5. **ตั้งค่ากฎได้** ตามเกรดลูกค้าและสถานะอุณหภูมิ

---

## 🗄️ โครงสร้างฐานข้อมูล

### ตาราง `customers` (เพิ่มฟิลด์ใหม่)
```sql
-- ฟิลด์ใหม่ที่เพิ่ม
appointment_count INT DEFAULT 0                    -- จำนวนการนัดหมายที่ทำไปแล้ว
appointment_extension_count INT DEFAULT 0          -- จำนวนครั้งที่ต่อเวลาจากการนัดหมาย
last_appointment_date TIMESTAMP NULL               -- วันที่นัดหมายล่าสุด
appointment_extension_expiry TIMESTAMP NULL        -- วันหมดอายุการต่อเวลาจากการนัดหมาย
max_appointment_extensions INT DEFAULT 3           -- จำนวนครั้งสูงสุดที่สามารถต่อเวลาได้
appointment_extension_days INT DEFAULT 30          -- จำนวนวันที่ต่อเวลาต่อการนัดหมาย 1 ครั้ง
```

### ตาราง `appointment_extensions` (ใหม่)
```sql
CREATE TABLE appointment_extensions (
    extension_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    appointment_id INT NULL,                        -- ID ของการนัดหมาย (NULL ถ้าเป็นการต่อเวลาอัตโนมัติ)
    
    -- Extension Details
    extension_type ENUM('appointment', 'sale', 'manual') NOT NULL,
    extension_days INT NOT NULL,
    extension_reason VARCHAR(200),
    
    -- Previous and New Dates
    previous_expiry TIMESTAMP NULL,
    new_expiry TIMESTAMP NOT NULL,
    
    -- Extension Count
    extension_count_before INT NOT NULL,
    extension_count_after INT NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL
);
```

### ตาราง `appointment_extension_rules` (ใหม่)
```sql
CREATE TABLE appointment_extension_rules (
    rule_id INT PRIMARY KEY AUTO_INCREMENT,
    rule_name VARCHAR(100) NOT NULL,
    rule_description TEXT,
    
    -- Rule Settings
    extension_days INT NOT NULL DEFAULT 30,        -- จำนวนวันที่ต่อเวลาต่อการนัดหมาย 1 ครั้ง
    max_extensions INT NOT NULL DEFAULT 3,         -- จำนวนครั้งสูงสุดที่สามารถต่อเวลาได้
    reset_on_sale BOOLEAN DEFAULT TRUE,            -- รีเซ็ตตัวนับเมื่อมีการขาย
    
    -- Conditions
    min_appointment_duration INT DEFAULT 0,        -- ระยะเวลาขั้นต่ำของการนัดหมาย (นาที)
    required_appointment_status ENUM('completed', 'confirmed', 'scheduled') DEFAULT 'completed',
    
    -- Customer Filters
    min_customer_grade ENUM('A+', 'A', 'B', 'C', 'D') DEFAULT 'D',
    temperature_status_filter JSON,                -- สถานะอุณหภูมิที่ใช้ได้ (JSON array)
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 🔧 ไฟล์ที่สร้าง/แก้ไข

### ไฟล์ใหม่:
1. **`database/appointment_extension_system.sql`** - SQL สำหรับสร้างระบบ
2. **`app/services/AppointmentExtensionService.php`** - Service class สำหรับจัดการการต่อเวลา
3. **`api/appointment-extensions.php`** - API endpoints สำหรับการต่อเวลา
4. **`test_appointment_extension_system.php`** - ไฟล์ทดสอบระบบ
5. **`APPOINTMENT_EXTENSION_SYSTEM_SUMMARY.md`** - เอกสารสรุป (ไฟล์นี้)

### ไฟล์ที่แก้ไข:
1. **`app/services/AppointmentService.php`** - เพิ่มการต่อเวลาอัตโนมัติเมื่อนัดหมายเสร็จสิ้น
2. **`app/services/OrderService.php`** - เพิ่มการรีเซ็ตตัวนับเมื่อมีการขาย

---

## 🚀 ฟีเจอร์ที่เพิ่ม

### 1. **การต่อเวลาอัตโนมัติ**
- ✅ ต่อเวลาอัตโนมัติเมื่อนัดหมายเสร็จสิ้น
- ✅ ตรวจสอบกฎการต่อเวลาก่อนต่อเวลา
- ✅ บันทึกประวัติการต่อเวลา

### 2. **การรีเซ็ตตัวนับ**
- ✅ รีเซ็ตตัวนับเมื่อมีการขาย
- ✅ บันทึกประวัติการรีเซ็ต
- ✅ ให้สามารถนัดหมายต่อได้

### 3. **การต่อเวลาด้วยตนเอง**
- ✅ ต่อเวลาด้วยตนเอง
- ✅ ตรวจสอบขีดจำกัดก่อนต่อเวลา
- ✅ บันทึกเหตุผลการต่อเวลา

### 4. **การติดตามและรายงาน**
- ✅ ดึงข้อมูลการต่อเวลาของลูกค้า
- ✅ ดึงสถิติการต่อเวลา
- ✅ ดึงรายการลูกค้าที่ใกล้หมดอายุ
- ✅ ดึงประวัติการต่อเวลา

### 5. **การตั้งค่ากฎ**
- ✅ ตั้งค่ากฎการต่อเวลา
- ✅ กำหนดเกรดลูกค้าขั้นต่ำ
- ✅ กำหนดสถานะอุณหภูมิที่ใช้ได้
- ✅ กำหนดจำนวนครั้งสูงสุด

---

## 📡 API Endpoints

### **GET Endpoints:**
- `GET api/appointment-extensions.php?action=get_customer_info&customer_id=1` - ดึงข้อมูลการต่อเวลาของลูกค้า
- `GET api/appointment-extensions.php?action=get_extension_history&customer_id=1&limit=10` - ดึงประวัติการต่อเวลา
- `GET api/appointment-extensions.php?action=get_near_expiry&days=7&limit=50` - ดึงรายการลูกค้าที่ใกล้หมดอายุ
- `GET api/appointment-extensions.php?action=get_expired&limit=50` - ดึงรายการลูกค้าที่หมดอายุแล้ว
- `GET api/appointment-extensions.php?action=get_stats` - ดึงสถิติการต่อเวลา
- `GET api/appointment-extensions.php?action=get_rules` - ดึงกฎการต่อเวลา
- `GET api/appointment-extensions.php?action=can_extend&customer_id=1` - ตรวจสอบว่าสามารถต่อเวลาได้หรือไม่

### **POST Endpoints:**
- `POST api/appointment-extensions.php?action=extend_from_appointment` - ต่อเวลาจากการนัดหมาย
- `POST api/appointment-extensions.php?action=reset_on_sale` - รีเซ็ตตัวนับเมื่อมีการขาย
- `POST api/appointment-extensions.php?action=extend_manually` - ต่อเวลาด้วยตนเอง
- `POST api/appointment-extensions.php?action=update_rule` - อัปเดตกฎการต่อเวลา

---

## 🔄 การทำงานของระบบ

### **1. การต่อเวลาอัตโนมัติเมื่อนัดหมายเสร็จสิ้น:**
1. ผู้ใช้อัปเดตสถานะนัดหมายเป็น "completed"
2. ระบบตรวจสอบว่าควรต่อเวลาหรือไม่
3. ตรวจสอบกฎการต่อเวลา (เกรดลูกค้า, สถานะอุณหภูมิ)
4. ตรวจสอบว่าสามารถต่อเวลาได้หรือไม่ (ไม่เกินขีดจำกัด)
5. ต่อเวลาอัตโนมัติ 30 วัน
6. บันทึกประวัติการต่อเวลา

### **2. การรีเซ็ตตัวนับเมื่อมีการขาย:**
1. ผู้ใช้สร้างคำสั่งซื้อใหม่
2. ระบบรีเซ็ตตัวนับการต่อเวลาเป็น 0
3. ล้างวันหมดอายุการต่อเวลา
4. บันทึกประวัติการรีเซ็ต
5. ให้สามารถนัดหมายต่อได้

### **3. การต่อเวลาด้วยตนเอง:**
1. ผู้ใช้เลือกต่อเวลาด้วยตนเอง
2. ระบบตรวจสอบว่าสามารถต่อเวลาได้หรือไม่
3. คำนวณวันหมดอายุใหม่
4. อัปเดตจำนวนครั้งที่ต่อเวลา
5. บันทึกประวัติการต่อเวลา

---

## 🎨 การใช้งานในหน้าเว็บ

### **ในหน้ารายละเอียดลูกค้า:**
- แสดงข้อมูลการต่อเวลาของลูกค้า
- แสดงจำนวนครั้งที่ต่อเวลาแล้ว
- แสดงวันหมดอายุการต่อเวลา
- ปุ่มต่อเวลาด้วยตนเอง (ถ้าสามารถต่อได้)

### **ในหน้านัดหมาย:**
- อัปเดตสถานะนัดหมายเป็น "completed"
- ระบบต่อเวลาอัตโนมัติ
- แสดงผลการต่อเวลา

### **ในหน้าคำสั่งซื้อ:**
- สร้างคำสั่งซื้อใหม่
- ระบบรีเซ็ตตัวนับอัตโนมัติ
- แสดงผลการรีเซ็ต

---

## 🧪 การทดสอบ

### **ไฟล์ทดสอบ:** `test_appointment_extension_system.php`

### **การทดสอบที่ครอบคลุม:**
1. ✅ **ทดสอบดึงข้อมูลการต่อเวลาของลูกค้า**
2. ✅ **ทดสอบดึงสถิติการต่อเวลา**
3. ✅ **ทดสอบดึงรายการลูกค้าที่ใกล้หมดอายุ**
4. ✅ **ทดสอบดึงประวัติการต่อเวลา**
5. ✅ **ทดสอบการต่อเวลาด้วยตนเอง**
6. ✅ **ทดสอบดึงกฎการต่อเวลา**

---

## 📊 ข้อมูลตัวอย่าง

### **กฎการต่อเวลามาตรฐาน:**
```sql
INSERT INTO appointment_extension_rules (
    rule_name, rule_description, extension_days, max_extensions, 
    reset_on_sale, required_appointment_status, min_customer_grade, 
    temperature_status_filter
) VALUES (
    'Default Appointment Extension Rule',
    'กฎการต่อเวลามาตรฐาน: ต่อเวลา 30 วันต่อการนัดหมาย 1 ครั้ง สูงสุด 3 ครั้ง รีเซ็ตเมื่อมีการขาย',
    30, 3, TRUE, 'completed', 'D', '["hot", "warm", "cold"]'
);
```

### **ข้อมูลตัวอย่างลูกค้า:**
```sql
-- ลูกค้าที่มีการต่อเวลา 1 ครั้ง
UPDATE customers SET 
    appointment_count = 2,
    appointment_extension_count = 1,
    last_appointment_date = DATE_SUB(NOW(), INTERVAL 10 DAY),
    appointment_extension_expiry = DATE_ADD(NOW(), INTERVAL 20 DAY)
WHERE customer_id = 4;

-- ลูกค้าที่มีการต่อเวลา 2 ครั้ง
UPDATE customers SET 
    appointment_count = 3,
    appointment_extension_count = 2,
    last_appointment_date = DATE_SUB(NOW(), INTERVAL 5 DAY),
    appointment_extension_expiry = DATE_ADD(NOW(), INTERVAL 25 DAY)
WHERE customer_id = 5;
```

---

## 🔄 ขั้นตอนการติดตั้ง

### **1. รันไฟล์ SQL:**
```bash
# รันไฟล์ SQL สำหรับสร้างระบบ
mysql -u username -p database_name < database/appointment_extension_system.sql
```

### **2. ทดสอบระบบ:**
```bash
# เข้าไปที่ไฟล์ทดสอบ
http://localhost/CRM-CURSOR/test_appointment_extension_system.php
```

### **3. ตรวจสอบการทำงาน:**
```bash
# ไปที่หน้ารายละเอียดลูกค้า
http://localhost/CRM-CURSOR/customers.php?action=show&id=1
```

---

## 🎯 ผลลัพธ์

### **ระบบการต่อเวลาการนัดหมายที่สมบูรณ์:**
- ✅ **ฐานข้อมูล** - ตารางและฟิลด์ใหม่ครบถ้วน
- ✅ **Backend Service** - AppointmentExtensionService สำหรับจัดการการต่อเวลา
- ✅ **API Endpoints** - RESTful API สำหรับการต่อเวลา
- ✅ **Auto Extension** - ต่อเวลาอัตโนมัติเมื่อนัดหมายเสร็จสิ้น
- ✅ **Reset on Sale** - รีเซ็ตตัวนับเมื่อมีการขาย
- ✅ **Manual Extension** - ต่อเวลาด้วยตนเอง
- ✅ **Rules Management** - จัดการกฎการต่อเวลา
- ✅ **Tracking & Reporting** - ติดตามและรายงาน
- ✅ **การทดสอบ** - ไฟล์ทดสอบครบถ้วน

### **ฟีเจอร์ที่ใช้งานได้:**
1. **ต่อเวลาอัตโนมัติ** - เมื่อนัดหมายเสร็จสิ้น
2. **รีเซ็ตตัวนับ** - เมื่อมีการขาย
3. **ต่อเวลาด้วยตนเอง** - ตามความต้องการ
4. **ติดตามประวัติ** - ดูประวัติการต่อเวลา
5. **รายงานสถิติ** - ดูสถิติการต่อเวลา
6. **ตั้งค่ากฎ** - ปรับกฎการต่อเวลา

---

## 📞 การสนับสนุน

### **หากมีปัญหา:**
1. ตรวจสอบการรันไฟล์ SQL ด้วย `test_appointment_extension_system.php`
2. ตรวจสอบ Console logs ใน Developer Tools
3. ตรวจสอบ Network requests ใน Developer Tools
4. ตรวจสอบ error logs ของ PHP

### **การแก้ไขปัญหา:**
1. **ตารางไม่ถูกสร้าง** - รันไฟล์ `database/appointment_extension_system.sql`
2. **API ไม่ทำงาน** - ตรวจสอบ file permissions และ database connection
3. **ไม่ต่อเวลาอัตโนมัติ** - ตรวจสอบกฎการต่อเวลาและสถานะการนัดหมาย
4. **ไม่รีเซ็ตตัวนับ** - ตรวจสอบการสร้างคำสั่งซื้อ

---

## 🎉 สรุป

**ระบบการต่อเวลาการนัดหมาย CRM SalesTracker ได้พัฒนาเสร็จสิ้นแล้ว!**

✅ **กฎการทำงาน** - การนัดหมาย 1 ครั้ง = ต่อเวลา 1 เดือน สูงสุด 3 ครั้ง  
✅ **รีเซ็ตตัวนับ** - เมื่อปิดการขาย ให้สามารถนัดหมายต่อได้  
✅ **ต่อเวลาอัตโนมัติ** - เมื่อนัดหมายเสร็จสิ้น  
✅ **ต่อเวลาด้วยตนเอง** - ตามความต้องการ  
✅ **ติดตามและรายงาน** - ประวัติและสถิติครบถ้วน  
✅ **ตั้งค่ากฎ** - ปรับกฎการต่อเวลาได้  

**ระบบพร้อมใช้งานจริงและสามารถจัดการการต่อเวลาการนัดหมายได้อย่างสมบูรณ์!** 🚀

---

## 🔍 สถานะการพัฒนา

### **✅ เสร็จสิ้นแล้ว:**
- ระบบฐานข้อมูล
- Service classes
- API endpoints
- การต่อเวลาอัตโนมัติ
- การรีเซ็ตตัวนับ
- การต่อเวลาด้วยตนเอง
- การติดตามและรายงาน
- การตั้งค่ากฎ
- การทดสอบระบบ

### **🎯 ฟีเจอร์หลักที่ทำงานได้:**
1. **📅 การนัดหมาย 1 ครั้ง = ต่อเวลา 1 เดือน**
2. **🔄 สูงสุด 3 ครั้ง** ต่อลูกค้า
3. **💰 รีเซ็ตตัวนับเมื่อปิดการขาย**
4. **⚙️ ตั้งค่ากฎการต่อเวลาได้**
5. **📊 ติดตามสถิติและประวัติ**

---

**พัฒนาโดย:** AI Assistant  
**วันที่เสร็จสิ้น:** 2025-01-02  
**เวอร์ชัน:** 1.0.0 (Complete) 