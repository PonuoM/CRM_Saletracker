# 🔍 **Debug 500 Error Comprehensive Guide - คู่มือการแก้ไขปัญหา 500 Error แบบครบถ้วน**

## 📋 **สถานะปัญหา**

ระบบเกิด 500 Internal Server Error เมื่อทำการ import ไฟล์ CSV ผ่านหน้า Import/Export

### 🚨 **ข้อผิดพลาดที่พบ:**
- `POST https://www.prima49.com/Customer/import-export.php?action=importSales 500 (Internal Server Error)`
- `Error: Network response was not ok`
- `import-export.js:101 Error: Error: Network response was not ok`

## 🎯 **จุดที่ทีมพัฒนาควรโฟกัส**

### **1. ตรวจสอบ Server Error Log (ลำดับความสำคัญสูงสุด)**
```bash
# Apache Error Log
tail -f /var/log/apache2/error.log

# Nginx Error Log  
tail -f /var/log/nginx/error.log

# PHP Error Log
tail -f /var/log/php_errors.log
```

### **2. เปิด PHP Error Reporting ในไฟล์ที่เกี่ยวข้อง**
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
```

### **3. ตรวจสอบการ Import Process**
จาก log แสดงว่า:
- ✅ Database connection สำเร็จ
- ✅ ตารางทั้งหมดมีอยู่
- ✅ CSV file ถูกสร้างสำเร็จ
- ❌ หยุดที่ขั้นตอน "เริ่มการ import..."

## 🔧 **จุดที่ต้องตรวจสอบเฉพาะ**

### **A. ImportExportService.php**
- ตรวจสอบ method ที่ทำการ import CSV
- ดู SQL queries ที่อาจมีปัญหา syntax
- ตรวจสอบ memory usage (large file import)

### **B. ImportExportController.php**
- ตรวจสอบ POST request handling
- ดู JSON response formatting
- ตรวจสอบ file upload validation

### **C. Database Operations**
- ตรวจสอบ INSERT/UPDATE statements
- ดู foreign key constraints
- ตรวจสอบ transaction handling

## 🛠️ **วิธีแก้ปัญหาทันที**

### **สร้างไฟล์ debug แยก:**

1. **`debug_import_step_by_step.php`** - ตรวจสอบจุดที่เกิดปัญหาในการ import
2. **`debug_sql_queries.php`** - ตรวจสอบ SQL queries และ database operations
3. **`debug_javascript_ajax.php`** - ตรวจสอบ JavaScript และ AJAX request
4. **`debug_ajax_test.html`** - ทดสอบ JavaScript และ AJAX functionality

## 🔍 **ตรวจสอบ JavaScript**

จากข้อความ error ใน console:
```
import-export.js:101 Error: Error: Network response was not ok
```

ให้ตรวจสอบว่า AJAX request มีการ handle error อย่างถูกต้องหรือไม่

## 📝 **ขั้นตอนการแก้ปัญหาแบบ Step-by-Step**

### **1. เปิด error log → ดูข้อความ error จริง**
```bash
# ตรวจสอบ error log
tail -f /var/log/apache2/error.log
```

### **2. ทดสอบ import data เล็กๆ → หาจุดที่ error**
```bash
# รันไฟล์ debug
php debug_import_step_by_step.php
```

### **3. ตรวจสอบ SQL queries → แก้ syntax error**
```bash
# รันไฟล์ debug SQL
php debug_sql_queries.php
```

### **4. ทดสอบ memory limit → เพิ่ม memory หากจำเป็น**
```php
// เพิ่ม memory limit
ini_set('memory_limit', '512M');
```

### **5. ตรวจสอบ file permissions → chmod 755/644**
```bash
chmod 755 uploads/
chown www-data:www-data uploads/
```

## 🧪 **ไฟล์ Debug ที่สร้าง**

### **1. debug_import_step_by_step.php**
- ตรวจสอบการโหลดไฟล์ที่จำเป็น
- ทดสอบการสร้าง Service และ Controller
- ทดสอบการ import ผ่าน Service แบบ step by step
- ทดสอบการ import ผ่าน Controller แบบ step by step
- ตรวจสอบ memory usage และ PHP settings
- ตรวจสอบ file permissions
- ทดสอบการอ่านไฟล์ CSV

### **2. debug_sql_queries.php**
- ทดสอบการเชื่อมต่อฐานข้อมูล
- ทดสอบ tableExists method
- ทดสอบการ query ตารางต่างๆ
- ทดสอบการ INSERT/UPDATE ข้อมูล
- ทดสอบ complex queries ที่ใช้ใน ImportExportService
- ทดสอบ transaction handling
- ตรวจสอบ database connection status

### **3. debug_javascript_ajax.php**
- ทดสอบการส่ง JSON response
- ตรวจสอบ Content-Type headers
- ทดสอบ CORS headers

### **4. debug_ajax_test.html**
- ทดสอบการเชื่อมต่อ AJAX พื้นฐาน
- ทดสอบ JSON Response
- ทดสอบการ import CSV
- ทดสอบ Error Handling
- ตรวจสอบ Browser Console

## 🔍 **การวิเคราะห์ปัญหาเพิ่มเติม**

### **สำคัญที่สุด: ต้องดู server error log ก่อน**
เพราะจะบอกสาเหตุที่แท้จริงของ HTTP 500 error

### **สาเหตุที่เป็นไปได้:**
1. **PHP Fatal Error** - Syntax error, undefined function
2. **Memory Limit Exceeded** - ไฟล์ CSV ใหญ่เกินไป
3. **Database Connection Error** - ปัญหาการเชื่อมต่อฐานข้อมูล
4. **File Permission Error** - ไม่สามารถเขียนไฟล์ได้
5. **Timeout Error** - การประมวลผลใช้เวลานานเกินไป

## 📊 **การตรวจสอบเพิ่มเติม**

### **1. ตรวจสอบ PHP Configuration**
```php
<?php
phpinfo();
?>
```

### **2. ตรวจสอบ Database Connection**
```php
<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=your_db", "user", "pass");
    echo "Database connection successful";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
```

### **3. ตรวจสอบ File System**
```bash
ls -la uploads/
df -h
```

## 🚀 **การแก้ไขปัญหาแบบเร่งด่วน**

### **1. เพิ่ม Error Logging**
```php
error_log("Debug: " . $variable);
error_log("Error: " . $e->getMessage());
```

### **2. เพิ่ม Memory Limit**
```php
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
```

### **3. เพิ่ม Error Handling**
```php
try {
    // code here
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
```

## 📋 **Checklist การ Debug**

- [ ] ตรวจสอบ Server Error Log
- [ ] รัน debug_import_step_by_step.php
- [ ] รัน debug_sql_queries.php
- [ ] เปิด debug_ajax_test.html ใน browser
- [ ] ตรวจสอบ PHP Configuration
- [ ] ตรวจสอบ Database Connection
- [ ] ตรวจสอบ File Permissions
- [ ] ตรวจสอบ Memory Usage
- [ ] ตรวจสอบ Timeout Settings

## 🎯 **ผลลัพธ์ที่คาดหวัง**

### **หากการ debug สำเร็จ:**
- พบสาเหตุที่แท้จริงของ 500 error
- สามารถแก้ไขปัญหาได้ตรงจุด
- การ import CSV ทำงานได้ปกติ

### **หากยังมีปัญหา:**
- มีข้อมูลเพียงพอสำหรับการแก้ไขเพิ่มเติม
- ทราบจุดที่เกิดปัญหาที่แน่ชัด
- สามารถระบุสาเหตุได้

## 🎯 **ผลลัพธ์การ Debug และการแก้ไข**

### **ปัญหาที่พบจากการ Debug:**
1. **SQL Column Not Found Error**: `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'activity_date' in 'field list'`
2. **JSON Response Error**: HTML output interfering with JSON response in `debug_javascript_ajax.php`

### **การแก้ไขที่ทำ:**
1. **สร้างไฟล์แก้ไขฐานข้อมูล:**
   - `fix_customer_activities_schema.php` - แก้ไขผ่าน PHP script
   - `add_activity_date_column.sql` - แก้ไขผ่าน SQL script

2. **แก้ไข HTML output:**
   - แก้ไข `debug_javascript_ajax.php` เพื่อลบ HTML output ที่รบกวน JSON response

### **วิธีแก้ไขปัญหา:**

#### **1. แก้ไขฐานข้อมูล (เลือกวิธีใดวิธีหนึ่ง):**

**วิธีที่ 1: ใช้ PHP Script**
```bash
# รันไฟล์ PHP script
php fix_customer_activities_schema.php
```

**วิธีที่ 2: ใช้ SQL Script**
```sql
-- รันคำสั่ง SQL ใน phpMyAdmin หรือ MySQL client
ALTER TABLE customer_activities 
ADD COLUMN activity_date DATE NULL 
AFTER activity_type;
```

#### **2. ทดสอบการแก้ไข:**
```bash
# ทดสอบการ import หลังจากแก้ไข
php test_full_import_process.php
```

## 📞 **การขอความช่วยเหลือเพิ่มเติม**

หากยังมีปัญหา 500 error หลังจากใช้ไฟล์ debug ทั้งหมดนี้ กรุณา:

1. **แชร์ Server Error Log** - ข้อมูลจาก error log ของเซิร์ฟเวอร์
2. **แชร์ผลลัพธ์จากไฟล์ debug** - ผลลัพธ์จากไฟล์ debug ทั้งหมด
3. **แชร์ PHP Configuration** - ข้อมูลจาก phpinfo()
4. **ระบุรายละเอียดของไฟล์ CSV** - ขนาดและรูปแบบของไฟล์ที่พยายาม import
5. **แชร์ Browser Console Log** - ข้อมูลจาก Developer Tools

---

**การ debug นี้ครอบคลุมทุกด้านที่เป็นไปได้ของ 500 error และได้ระบุสาเหตุที่แท้จริงแล้ว: การขาดหายไปของคอลัมน์ `activity_date` ในตาราง `customer_activities`** 