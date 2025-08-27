# Customer System Update - README

## การเปลี่ยนแปลงที่ทำ

### 1. ลบระบบ Customer Transfer ออก
- ลบหน้า `admin.php?action=customer_transfer` ออก
- ลบ `TransferController.php` และ `CustomerTransferService.php`
- ลบไฟล์ view ที่เกี่ยวข้อง
- ลบ SQL scripts สำหรับสร้างตาราง transfer
- ลบเมนูและลิงก์ใน sidebar และ admin index

### 2. แก้ไขปัญหา 500 Error ใน Customer Distribution
- เพิ่ม error handling และ logging ที่ดีขึ้นใน `CustomerDistributionService.php`
- แก้ไขฟังก์ชัน `getCompanyStats()`, `getTelesalesByCompany()`, `getAvailableCustomersByDate()`, `getGradeAStats()`
- เพิ่ม parameter validation และ error logging
- ปรับปรุง API response format

### 3. เพิ่มฟังก์ชันเปลี่ยนผู้ดูแลในหน้า Customer Show
- เพิ่มปุ่ม "เปลี่ยนผู้ดูแล" สำหรับ admin และ super_admin
- สร้าง Modal สำหรับเลือกผู้ดูแลใหม่
- เพิ่ม API endpoints:
  - `GET /api/customers.php?action=get_telesales` - ดึงรายการ Telesales
  - `POST /api/customers.php?action=change_assignee` - เปลี่ยนผู้ดูแล
- เพิ่มฟังก์ชันใน `CustomerController.php`:
  - `getTelesalesList()` - ดึงรายการ Telesales
  - `changeCustomerAssignee()` - เปลี่ยนผู้ดูแลลูกค้า

## โครงสร้างฐานข้อมูลที่ใช้

### ตาราง customers
- `customer_id` - รหัสลูกค้า
- `assigned_to` - รหัสผู้ดูแล (user_id)
- `basket_type` - ประเภทตะกร้า (distribution, assigned, expired)
- `source` - แหล่งที่มา (PRIMA, PRIONIC)
- `customer_grade` - เกรดลูกค้า (A+, A, B, C, D)
- `temperature_status` - สถานะอุณหภูมิ (hot, warm, cold, frozen)

### ตาราง users
- `user_id` - รหัสผู้ใช้
- `role_id` - รหัสบทบาท (4 = Telesales)
- `company_id` - รหัสบริษัท
- `supervisor_id` - รหัสหัวหน้าทีม

### ตาราง companies
- `company_id` - รหัสบริษัท
- `company_name` - ชื่อบริษัท
- `company_code` - รหัสบริษัท

## การใช้งาน

### สำหรับ Admin
1. เข้าสู่ระบบด้วย role admin หรือ super_admin
2. ไปที่หน้า Customer Distribution: `admin.php?action=customer_distribution`
3. ดูสถิติและแจกลูกค้าได้ตามปกติ
4. ในหน้า Customer Detail สามารถเปลี่ยนผู้ดูแลได้

### สำหรับ Supervisor
1. เข้าสู่ระบบด้วย role supervisor
2. เข้าถึง Customer Distribution ได้
3. ไม่สามารถเปลี่ยนผู้ดูแลลูกค้าได้

### สำหรับ Telesales
1. เข้าสู่ระบบด้วย role telesales
2. เห็นเฉพาะลูกค้าที่ได้รับมอบหมายให้ตัวเอง
3. ไม่สามารถเข้าถึง Customer Distribution หรือเปลี่ยนผู้ดูแลได้

## การแก้ไขปัญหา

### ปัญหา 500 Error
- เพิ่ม error logging ในทุกฟังก์ชัน
- ตรวจสอบ parameter validation
- ปรับปรุง error handling

### ปัญหา JSON Parsing Error
- ตรวจสอบ response format
- เพิ่ม error logging
- ปรับปรุง API response structure

## หมายเหตุ
- ระบบ Transfer ถูกลบออกแล้ว เนื่องจากไม่จำเป็น
- การเปลี่ยนผู้ดูแลทำได้เฉพาะในหน้า Customer Detail
- ระบบ Distribution ยังคงทำงานได้ตามปกติ
- เพิ่ม logging เพื่อ debug และ monitor ระบบ
