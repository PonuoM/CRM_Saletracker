# การแก้ไขปัญหา Schema และ CSV Encoding - สรุป

## ปัญหาที่พบ
1. **CSV Encoding**: ไฟล์ CSV ที่ดาวน์โหลดแสดงภาษาไทยเป็นตัวอักษรแปลกๆ (ï»¿à¸Šà¸·à¹ˆà¸­) ใน Excel
2. **Schema Mismatch**: ไฟล์ Template มีคอลัมน์ที่ไม่ตรงกับ Database Schema จริง

## การวิเคราะห์ Schema จริง

### จากไฟล์ `database/customers .sql`:
```sql
CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `district` varchar(50) DEFAULT NULL,        -- ไม่ใช่ 'ตำบล'
  `province` varchar(50) DEFAULT NULL,        -- ไม่ใช่ 'อำเภอ'
  `postal_code` varchar(10) DEFAULT NULL,
  -- ... อื่นๆ
);
```

### จากไฟล์ `database/orders.sql`:
```sql
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `net_amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','transfer','cod','credit','other') DEFAULT 'cash',
  `payment_status` enum('pending','paid','partial','cancelled') DEFAULT 'pending',
  `delivery_status` enum('pending','shipped','delivered','cancelled') DEFAULT 'pending',
  -- ... อื่นๆ
);
```

## การแก้ไขที่ทำ

### 1. แก้ไขไฟล์ Template ให้ตรงกับ Schema

#### Sales Import Template (`templates/sales_import_template.csv`):
```csv
ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,ชื่อสินค้า,จำนวน,ราคาต่อชิ้น,ยอดรวม,วันที่สั่งซื้อ,ช่องทางการขาย,หมายเหตุ
```

#### Customers Only Template (`templates/customers_only_template.csv`):
```csv
ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,หมายเหตุ
```

#### Customers Template (`templates/customers_template.csv`):
```csv
ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,สถานะ,อุณหภูมิ,เกรด,หมายเหตุ
```

### 2. แก้ไข ImportExportService.php

#### Column Mapping:
```php
// เปลี่ยนจาก
'ตำบล' => 'district',
'อำเภอ' => 'province',

// เป็น
'เขต' => 'district',
'จังหวัด' => 'province',
```

### 3. แก้ไข ImportExportController.php

#### Fallback Templates:
```php
// เปลี่ยนจาก
fputcsv($output, ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'ตำบล', 'อำเภอ', 'จังหวัด', 'รหัสไปรษณีย์', ...]);

// เป็น
fputcsv($output, ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'เขต', 'จังหวัด', 'รหัสไปรษณีย์', ...]);
```

### 4. เพิ่ม UTF-8 BOM ในทุกไฟล์ Template

ทุกไฟล์ Template ถูกสร้างใหม่ด้วย UTF-8 BOM เพื่อให้ Excel แสดงภาษาไทยได้ถูกต้อง

## ไฟล์ที่แก้ไข

1. `templates/sales_import_template.csv` - สร้างใหม่ด้วย BOM และ Schema ถูกต้อง
2. `templates/customers_only_template.csv` - สร้างใหม่ด้วย BOM และ Schema ถูกต้อง
3. `templates/customers_template.csv` - สร้างใหม่ด้วย BOM และ Schema ถูกต้อง
4. `app/services/ImportExportService.php` - แก้ไข Column Mapping
5. `app/controllers/ImportExportController.php` - แก้ไข Fallback Templates
6. `test_schema_fix.php` - ไฟล์ทดสอบใหม่

## การทดสอบ

### ไฟล์ทดสอบ:
- `test_schema_fix.php` - ทดสอบ Schema และ BOM

### ขั้นตอนการทดสอบ:
1. รัน `test_schema_fix.php` เพื่อตรวจสอบไฟล์ Template
2. ดาวน์โหลดไฟล์ Template จากระบบ
3. เปิดไฟล์ใน Excel เพื่อตรวจสอบภาษาไทย

## ผลลัพธ์ที่คาดหวัง

1. **CSV Encoding**: ไฟล์ CSV แสดงภาษาไทยได้ถูกต้องใน Excel
2. **Schema**: คอลัมน์ตรงกับ Database Schema จริง
3. **Import/Export**: ระบบนำเข้าและส่งออกข้อมูลได้ถูกต้อง

## หมายเหตุสำหรับเว็บไซต์ Live

หากเว็บไซต์ Live (prima49.com) ยังแสดงไฟล์เก่า:

1. **ล้าง Cache เบราว์เซอร์**
   - กด `Ctrl+F5` (Windows) หรือ `Cmd+Shift+R` (Mac)
   - หรือเปิด Developer Tools > Network > Disable cache

2. **ตรวจสอบการอัปโหลด**
   - ตรวจสอบว่าไฟล์ใหม่ถูกอัปโหลดไปยังเซิร์ฟเวอร์แล้ว
   - ตรวจสอบสิทธิ์การเข้าถึงไฟล์ (file permissions)

3. **ตรวจสอบ Server Cache**
   - ตรวจสอบการตั้งค่า Cache ของเว็บเซิร์ฟเวอร์
   - ตรวจสอบการตั้งค่า PHP OPcache (หากเปิดใช้งาน)

## สรุป

การแก้ไขนี้จะทำให้:
- ไฟล์ CSV แสดงภาษาไทยได้ถูกต้องใน Excel
- Schema ตรงกับ Database จริง
- ระบบ Import/Export ทำงานได้อย่างถูกต้อง
- ไม่มีปัญหาการเข้ารหัสอีกต่อไป 