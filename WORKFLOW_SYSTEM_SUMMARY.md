# ระบบ Workflow Management - สรุป

**วันที่สร้าง:** 2024  
**สถานะ:** ✅ เสร็จสมบูรณ์  
**เวอร์ชัน:** 1.0.0

---

## 🎯 ภาพรวม

ระบบ **Workflow Management** เป็นระบบจัดการการเรียกข้อมูลลูกค้าคืน (Customer Recall) และการต่อเวลาอัตโนมัติเมื่อมีการ Active (สร้างนัดหมาย/การขาย) สำหรับ CRM SalesTracker

### **คุณสมบัติหลัก:**
- 📊 **Dashboard สถิติ** - แสดงข้อมูลลูกค้าที่ต้อง Recall และ Active
- ⏰ **ระบบต่อเวลา** - ต่อเวลาอัตโนมัติเมื่อมีการ Active
- 🔄 **Manual Recall** - รันการดึงลูกค้ากลับด้วยตนเอง
- 📋 **รายการลูกค้า** - แสดงรายการลูกค้าที่เกินเวลา
- 📈 **กิจกรรมล่าสุด** - ติดตามกิจกรรมทั้งหมด
- 🤖 **Cron Job** - ทำงานอัตโนมัติ

---

## 📁 ไฟล์ที่สร้าง/แก้ไข

### **ไฟล์ใหม่:**
1. `app/views/admin/workflow.php` - หน้า Workflow Management
2. `app/services/WorkflowService.php` - Service สำหรับจัดการ Workflow
3. `api/workflow.php` - API endpoint สำหรับ Workflow
4. `assets/js/workflow.js` - JavaScript สำหรับหน้า Workflow
5. `cron/customer_recall_workflow.php` - Cron Job อัตโนมัติ
6. `WORKFLOW_SYSTEM_SUMMARY.md` - เอกสารสรุปนี้

### **ไฟล์ที่แก้ไข:**
1. `app/controllers/AdminController.php` - เพิ่มเมธอด workflow()
2. `admin.php` - เพิ่ม action workflow
3. `app/views/components/sidebar.php` - เพิ่มเมนู Workflow Management

---

## 🔧 กฎการทำงาน Workflow

### **ลูกค้าใหม่ (30 วัน):**
- หากไม่มีการอัปเดตภายใน 30 วัน → ดึงกลับไป Distribution Basket
- หากมีการสร้างนัดหมาย → ต่อเวลา 30 วัน
- หากมีการขาย → ต่อเวลา 90 วัน

### **ลูกค้าเก่า (90 วัน):**
- หากไม่มีคำสั่งซื้อภายใน 90 วัน → ดึงกลับไป Waiting Basket
- หากมีการสร้างนัดหมาย → ต่อเวลา 30 วัน
- หากมีการขาย → ต่อเวลา 90 วัน

---

## 🚀 การใช้งาน

### **1. เข้าถึงหน้า Workflow Management:**
```
URL: admin.php?action=workflow
สิทธิ์: Admin, Supervisor, Super Admin
```

### **2. ฟีเจอร์หลัก:**

#### **📊 Dashboard สถิติ:**
- ลูกค้าที่ต้อง Recall
- ลูกค้าใหม่เกิน 30 วัน
- ลูกค้าเก่าเกิน 90 วัน
- ลูกค้า Active วันนี้

#### **🔄 Manual Recall:**
- ปุ่ม "รัน Recall เอง" - ดึงลูกค้าที่เกินเวลากลับอัตโนมัติ
- แสดงผลลัพธ์การดำเนินการ

#### **⏰ ต่อเวลา:**
- ปุ่ม "ต่อเวลา" - เปิด Modal เลือกลูกค้าและต่อเวลา
- ต่อเวลาด่วน - คลิกปุ่มต่อเวลาตรงในตาราง

#### **📋 รายการลูกค้า:**
- ลูกค้าใหม่เกิน 30 วัน (แสดงในตาราง)
- ลูกค้าเก่าเกิน 90 วัน (แสดงในตาราง)
- ปุ่มดำเนินการ: ต่อเวลา, ดึงกลับ

#### **📈 กิจกรรมล่าสุด:**
- แสดงกิจกรรมทั้งหมด (คำสั่งซื้อ, นัดหมาย, Recall)
- เรียงตามเวลาล่าสุด

---

## 🔌 API Endpoints

### **GET Requests:**
- `api/workflow.php?action=stats` - ดึงสถิติ Workflow
- `api/workflow.php?action=new_customer_timeout&limit=10` - ลูกค้าใหม่เกิน 30 วัน
- `api/workflow.php?action=existing_customer_timeout&limit=10` - ลูกค้าเก่าเกิน 90 วัน
- `api/workflow.php?action=recent_activities&limit=20` - กิจกรรมล่าสุด
- `api/workflow.php?action=customers_for_extension` - ลูกค้าที่พร้อมต่อเวลา

### **POST Requests:**
- `api/workflow.php?action=run_recall` - รัน Manual Recall
- `api/workflow.php?action=extend_time` - ต่อเวลาลูกค้า
- `api/workflow.php?action=auto_extend` - ต่อเวลาอัตโนมัติ

---

## 🤖 Cron Job

### **ไฟล์:** `cron/customer_recall_workflow.php`

### **การตั้งค่า:**
```bash
# ทุกชั่วโมง
0 * * * * php /path/to/cron/customer_recall_workflow.php

# ทุกวันเวลา 3:00 น.
0 3 * * * php /path/to/cron/customer_recall_workflow.php
```

### **หน้าที่:**
1. รัน Manual Recall อัตโนมัติ
2. บันทึกสถิติการทำงาน
3. ส่งการแจ้งเตือน (ถ้าจำเป็น)
4. บันทึก Log

---

## 🎨 UI/UX Features

### **Responsive Design:**
- รองรับทุกขนาดหน้าจอ
- Bootstrap 5 components
- Font Awesome icons

### **Interactive Elements:**
- Modal สำหรับต่อเวลา
- Alert messages
- Loading indicators
- Real-time updates

### **Color Coding:**
- 🔴 แดง - ลูกค้าใหม่เกิน 30 วัน
- 🟡 เหลือง - ลูกค้าเก่าเกิน 90 วัน
- 🟢 เขียว - ลูกค้า Active
- 🔵 น้ำเงิน - ข้อมูลทั่วไป

---

## 🔒 ความปลอดภัย

### **การยืนยันตัวตน:**
- ตรวจสอบ session
- ตรวจสอบสิทธิ์ (Admin/Supervisor เท่านั้น)

### **การตรวจสอบข้อมูล:**
- Validate input data
- SQL injection protection
- XSS protection

---

## 📊 การติดตามและ Logging

### **Log Files:**
- `logs/customer_recall_workflow.log` - Cron job logs
- PHP error logs

### **Database Tables:**
- `customers` - ข้อมูลลูกค้าและสถานะ
- `customer_activities` - บันทึกกิจกรรม
- `orders` - ข้อมูลคำสั่งซื้อ
- `appointments` - ข้อมูลนัดหมาย

---

## 🧪 การทดสอบ

### **ทดสอบ Manual:**
1. เข้าไปที่ `admin.php?action=workflow`
2. ตรวจสอบสถิติแสดงผลถูกต้อง
3. ทดสอบปุ่ม "รัน Recall เอง"
4. ทดสอบการต่อเวลา
5. ตรวจสอบกิจกรรมล่าสุด

### **ทดสอบ Cron Job:**
```bash
php cron/customer_recall_workflow.php
```

---

## 🔄 การอัปเดตในอนาคต

### **ฟีเจอร์ที่อาจเพิ่ม:**
- ระบบการแจ้งเตือน (Email, LINE Notify)
- รายงานสถิติรายเดือน/รายปี
- การตั้งค่าเวลาอัตโนมัติ
- Dashboard แบบ Real-time
- การ Export ข้อมูล

---

## ✅ สรุป

ระบบ **Workflow Management** ได้รับการพัฒนาสำเร็จแล้วและพร้อมใช้งาน ระบบนี้จะช่วยให้:

1. **Admin/Supervisor** สามารถจัดการลูกค้าที่เกินเวลาได้อย่างมีประสิทธิภาพ
2. **ระบบต่อเวลาอัตโนมัติ** เมื่อมีการ Active ช่วยลดงานซ้ำซ้อน
3. **การติดตามกิจกรรม** ช่วยให้เห็นภาพรวมการทำงาน
4. **Cron Job อัตโนมัติ** ช่วยให้ระบบทำงานได้ตลอด 24 ชั่วโมง

**ระบบพร้อมใช้งานแล้ว!** 🚀 