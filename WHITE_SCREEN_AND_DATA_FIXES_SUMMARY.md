# สรุปการแก้ไขปัญหา Import/Export และข้อมูล

## ปัญหาที่แก้ไข

### 1. ปัญหาหน้าจอขาวหลังอัพโหลด
**ปัญหา:** หลังอัพโหลดไฟล์สำเร็จ หน้าจอจะขาวและต้องสลับเมนูเพื่อรีเฟรช

**การแก้ไข:**
- แก้ไข `assets/js/import-export.js`
- เพิ่มการรีเฟรชหน้าหลังจากแสดงข้อความสำเร็จ 2 วินาที
- ใช้ `window.location.reload()` เพื่อแก้ปัญหาหน้าจอขาว

```javascript
// Show success message at top of page
showPageMessage('นำเข้ายอดขายสำเร็จ! ' + data.total + ' รายการ', 'success');

// Refresh page after 2 seconds to fix white screen issue
setTimeout(() => {
    window.location.reload();
}, 2000);
```

### 2. สร้าง customer_code อัตโนมัติจากเบอร์โทร
**ปัญหา:** คอลัมน์ `customer_code` ในตาราง `customers` ว่างเปล่า

**การแก้ไข:**
- เพิ่มฟังก์ชัน `generateCustomerCode()` ใน `ImportExportService.php`
- สร้างรหัสลูกค้าจากเบอร์โทร 9 หลัก ขึ้นต้นด้วย "Cus-"
- อัปเดตฟังก์ชัน `createNewCustomer()` และ `createNewCustomerOnly()`
- สร้างไฟล์ `update_customer_codes.sql` สำหรับอัปเดตลูกค้าเดิม

**ตัวอย่าง:**
- เบอร์โทร: `0812345678` → `customer_code`: `Cus-812345678`
- เบอร์โทร: `0898765432` → `customer_code`: `Cus-898765432`

### 3. แก้ไขปัญหา total_amount ในตาราง Orders
**ปัญหา:** `total_amount` มีค่าผันผวน บางครั้งเป็นจำนวนชิ้น บางครั้งเป็นราคาต่อชิ้น

**การแก้ไข:**
- แก้ไขฟังก์ชัน `updateCustomerPurchaseHistory()` ใน `ImportExportService.php`
- เพิ่มการคำนวณ `total_amount` ใหม่จาก `จำนวน × ราคาต่อชิ้น`
- ใช้ค่าที่คำนวณได้แทนค่าจาก CSV โดยตรง

```php
// คำนวณ total_amount จาก จำนวน × ราคาต่อชิ้น
$quantity = floatval($salesData['quantity'] ?? 1);
$unitPrice = floatval($salesData['unit_price'] ?? 0);
$calculatedTotal = $quantity * $unitPrice;

// ใช้ค่าที่คำนวณได้ หรือใช้ค่าจาก CSV ถ้าคำนวณไม่ได้
$totalAmount = $calculatedTotal > 0 ? $calculatedTotal : floatval($salesData['total_amount'] ?? 0);
```

## ไฟล์ที่แก้ไข

1. **`assets/js/import-export.js`**
   - เพิ่มการรีเฟรชหน้าหลังอัพโหลดสำเร็จ

2. **`app/services/ImportExportService.php`**
   - เพิ่มฟังก์ชัน `generateCustomerCode()`
   - อัปเดต `createNewCustomer()` และ `createNewCustomerOnly()`
   - แก้ไข `updateCustomerPurchaseHistory()` สำหรับการคำนวณ total_amount

3. **`update_customer_codes.sql`** (ใหม่)
   - สคริปต์ SQL สำหรับอัปเดต customer_code ของลูกค้าเดิม

## วิธีการใช้งาน

### สำหรับลูกค้าเดิม
รันไฟล์ SQL เพื่ออัปเดต customer_code:
```sql
-- รันไฟล์ update_customer_codes.sql ใน MySQL
```

### สำหรับลูกค้าใหม่
ระบบจะสร้าง customer_code อัตโนมัติเมื่อนำเข้าข้อมูลใหม่

## ผลลัพธ์ที่คาดหวัง

1. **หน้าจอขาว:** แก้ไขแล้ว - หลังอัพโหลดจะแสดงข้อความสำเร็จและรีเฟรชหน้าอัตโนมัติ
2. **customer_code:** สร้างอัตโนมัติจากเบอร์โทร 9 หลัก ขึ้นต้นด้วย "Cus-"
3. **total_amount:** คำนวณถูกต้องจาก จำนวน × ราคาต่อชิ้น

## การทดสอบ

1. ทดสอบอัพโหลดไฟล์ CSV ใหม่
2. ตรวจสอบ customer_code ในตาราง customers
3. ตรวจสอบ total_amount ในตาราง orders
4. ตรวจสอบการแสดงผลหลังอัพโหลด
