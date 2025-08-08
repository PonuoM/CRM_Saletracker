# Customer Import Enhancement Summary

## คำถามจากผู้ใช้

ผู้ใช้ถามเกี่ยวกับระบบ Import/Export ที่ `https://www.prima49.com/Customer/import-export.php`:

1. **สามารถเพิ่มฟิลด์ใหม่ใน Customer Import CSV ได้หรือไม่?**
   - รหัสสินค้า (product_code)
   - รหัสไปรษณีย์ (postal_code) - มีอยู่แล้ว
   - ผู้ติดตาม (ถ้ามี) (assigned_to)

2. **การจัดการข้อมูลลูกค้าที่มีรายการมากกว่า 2 รายการ**
   - เมื่อ Import เข้าหน้า Order จะสร้างกี่รายการ?
   - เมื่อ Import เข้ารายชื่อลูกค้า (กรณียังไม่มีรายชื่อ) จะสร้างกี่รายการ?

## คำตอบและการปรับปรุง

### ✅ ฟิลด์ใหม่ที่เพิ่มเข้ามา

#### 1. รหัสสินค้า (Product Code)
- **ฟิลด์:** `รหัสสินค้า` หรือ `Product Code`
- **การใช้งาน:** เก็บรหัสสินค้าที่ลูกค้าสนใจ (เช่น P001, P002)
- **รองรับใน:** Customer Import, Sales Import, Customers Only Import

#### 2. ผู้ติดตาม (Follower/Tracker)
- **ฟิลด์:** `ผู้ติดตาม`, `Follower`, หรือ `Tracker`
- **การใช้งาน:** ระบุพนักงานที่จะติดตามลูกค้า
- **รูปแบบ:** ชื่อพนักงาน (เช่น "สมชาย ใจดี") หรือรหัสพนักงาน (เช่น "1")
- **รองรับใน:** Customer Import, Sales Import, Customers Only Import

#### 3. รหัสไปรษณีย์ (Postal Code)
- **สถานะ:** มีอยู่แล้วในระบบ
- **ฟิลด์:** `รหัสไปรษณีย์` หรือ `Postal Code`

### 📊 การจัดการข้อมูลลูกค้าที่มีหลายรายการ

#### สำหรับ Sales Import:
- **ลูกค้าใหม่:** สร้าง 1 รายชื่อ + สร้างออเดอร์ตามจำนวนรายการในไฟล์
- **ลูกค้าเก่า:** อัพเดทข้อมูล + สร้างออเดอร์ใหม่ตามจำนวนรายการ

#### สำหรับ Customer Import:
- **ลูกค้าใหม่:** สร้าง 1 รายชื่อเท่านั้น (ไม่มีออเดอร์)

### 🔧 การเปลี่ยนแปลงในโค้ด

#### 1. อัปเดต Column Mapping
```php
// ใน ImportExportService.php
private function getCustomerColumnMap() {
    return [
        // ... ฟิลด์เดิม ...
        'รหัสสินค้า' => 'product_code',
        'Product Code' => 'product_code',
        'ผู้ติดตาม' => 'assigned_to',
        'Follower' => 'assigned_to',
        'Tracker' => 'assigned_to',
        // ... ฟิลด์อื่นๆ ...
    ];
}
```

#### 2. อัปเดตการสร้างลูกค้า
```php
// เพิ่มการจัดการ assigned_to field
$assignedTo = null;
if (!empty($customerData['assigned_to'])) {
    if (is_numeric($customerData['assigned_to'])) {
        $assignedTo = (int)$customerData['assigned_to'];
    } else {
        // ค้นหาพนักงานจากชื่อ
        $userSql = "SELECT user_id FROM users WHERE CONCAT(first_name, ' ', last_name) LIKE ? OR username LIKE ?";
        $userResult = $this->db->fetchOne($userSql, [$customerData['assigned_to'], $customerData['assigned_to']]);
        if ($userResult) {
            $assignedTo = (int)$userResult['user_id'];
        }
    }
}
```

#### 3. อัปเดต SQL Query
```sql
INSERT INTO customers (
    first_name, last_name, phone, email, address, 
    district, province, postal_code, customer_status, 
    temperature_status, customer_grade, basket_type, 
    assigned_to, is_active, created_at, updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
```

### 📁 ไฟล์ที่อัปเดต

#### 1. Service Layer
- `app/services/ImportExportService.php`
  - อัปเดต `getCustomerColumnMap()`
  - อัปเดต `getSalesColumnMap()`
  - อัปเดต `getCustomersOnlyColumnMap()`
  - อัปเดต `createNewCustomer()`
  - อัปเดต `createNewCustomerOnly()`
  - อัปเดต `importCustomersFromCSV()`

#### 2. Templates
- `templates/customers_template.csv` - เพิ่มฟิลด์ใหม่
- `templates/sales_import_template.csv` - เพิ่มฟิลด์ใหม่
- `templates/customers_only_template.csv` - เพิ่มฟิลด์ใหม่

#### 3. View
- `app/views/import-export/index.php` - อัปเดตคำแนะนำ

### 📋 ตัวอย่างข้อมูลใหม่

#### Customer Template:
```csv
ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,รหัสสินค้า,ผู้ติดตาม,สถานะ,อุณหภูมิ,เกรด,หมายเหตุ
สมชาย,ใจดี,0812345678,somchai@example.com,123 ถ.สุขุมวิท,คลองเตย,กรุงเทพฯ,10110,P001,สมชาย ใจดี,new,cold,C,ลูกค้าใหม่
```

#### Sales Template:
```csv
ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,รหัสสินค้า,ชื่อสินค้า,จำนวน,ราคาต่อชิ้น,ยอดรวม,วันที่สั่งซื้อ,ช่องทางการขาย,ผู้ติดตาม,หมายเหตุ
สมชาย,ใจดี,0812345678,somchai@example.com,123 ถ.สุขุมวิท,คลองเตย,กรุงเทพฯ,10110,P001,สินค้า A,2,1500,3000,2025-08-06,TikTok,สมชาย ใจดี,ลูกค้าใหม่
```

### 🎯 ประโยชน์ที่ได้รับ

1. **การติดตามลูกค้า:** สามารถระบุพนักงานที่จะติดตามลูกค้าได้ทันที
2. **การวิเคราะห์สินค้า:** รู้ว่าลูกค้าสนใจสินค้าอะไร
3. **การจัดการข้อมูล:** ข้อมูลครบถ้วนมากขึ้น
4. **ความยืดหยุ่น:** รองรับทั้งชื่อพนักงานและรหัสพนักงาน

### ⚠️ หมายเหตุสำคัญ

1. **ฟิลด์ใหม่เป็น Optional:** ไม่จำเป็นต้องกรอกทุกฟิลด์
2. **การค้นหาพนักงาน:** ระบบจะค้นหาพนักงานจากชื่อหรือ username
3. **การจัดการข้อมูลซ้ำ:** ยังคงใช้ชื่อและเบอร์โทรเป็นหลักในการตรวจสอบ
4. **Backward Compatibility:** ยังรองรับไฟล์ CSV แบบเดิม

### 🔄 การทดสอบ

ระบบพร้อมใช้งานแล้ว สามารถทดสอบได้โดย:
1. ดาวน์โหลด Template ใหม่
2. เพิ่มข้อมูลในฟิลด์ใหม่
3. Import ไฟล์ CSV
4. ตรวจสอบผลลัพธ์ในฐานข้อมูล

---

**วันที่อัปเดต:** 2025-01-27  
**สถานะ:** ✅ เสร็จสิ้น  
**ผู้พัฒนา:** AI Assistant
