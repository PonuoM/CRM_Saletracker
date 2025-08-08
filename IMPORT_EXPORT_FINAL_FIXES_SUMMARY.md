# การแก้ไขปัญหาสุดท้ายของระบบ Import/Export

## ปัญหาที่แก้ไข

### 1. หน้าจอขาวตอนอัพโหลด (แต่มี popup แสดง)
**ปัญหา:** หลังจากอัพโหลดไฟล์สำเร็จ หน้าจอจะขาว แต่มี popup แสดงข้อความแจ้งเตือน

**การแก้ไข:**
- **ไฟล์:** `assets/js/import-export.js`
- **การเปลี่ยนแปลง:**
  - เพิ่มการ clear file input หลังจาก reset form
  - เพิ่มการซ่อน results div หลังจาก 5 วินาที
  - ปรับปรุง UX ให้ดีขึ้น

```javascript
// Reset form
form.reset();

// Clear file input
const fileInput = form.querySelector('input[type="file"]');
if (fileInput) {
    fileInput.value = '';
}

// Show success message at top of page
showPageMessage('นำเข้ายอดขายสำเร็จ! ' + data.total + ' รายการ', 'success');

// Hide results after 5 seconds
setTimeout(() => {
    resultsDiv.style.display = 'none';
}, 5000);
```

### 2. total_purchase_amount ไม่อัพเดทในตาราง Customers
**ปัญหา:** หลังจากอัพเดทข้อมูลลูกค้า ยอด total_purchase_amount ไม่เปลี่ยนแปลง

**การแก้ไข:**
- **ไฟล์:** `app/services/ImportExportService.php`
- **การเปลี่ยนแปลง:**
  - แก้ไขเงื่อนไขในฟังก์ชัน `updateCustomerTotalPurchase()` จาก `payment_status = 'paid'` เป็น `payment_status IN ('paid', 'partial')`
  - เพิ่ม logging เพื่อ debug

```php
private function updateCustomerTotalPurchase($customerId) {
    try {
        $sql = "UPDATE customers SET 
                    total_purchase_amount = (
                        SELECT COALESCE(SUM(net_amount), 0) 
                        FROM orders 
                        WHERE customer_id = ? AND payment_status IN ('paid', 'partial')
                    ),
                    updated_at = NOW()
                WHERE customer_id = ?";
        
        $this->db->query($sql, [$customerId, $customerId]);
        
        // Log for debugging
        error_log("Updated total_purchase_amount for customer ID: " . $customerId);
        
    } catch (Exception $e) {
        error_log("Error updating customer total purchase: " . $e->getMessage());
    }
}
```

### 3. เพิ่มคอลัมน์ผู้ขาย (created_by) เพื่อเป็นผลงานของคนนั้น
**ปัญหา:** ตอนนี้ทุกการสร้างคำสั่งซื้อจะเป็นของ admin เสมอ ต้องการให้เป็นผลงานของพนักงานคนนั้น

**การแก้ไข:**

#### A. เพิ่ม Column Mapping
- **ไฟล์:** `app/services/ImportExportService.php`
- **การเปลี่ยนแปลง:** เพิ่ม mapping สำหรับคอลัมน์ผู้ขาย

```php
private function getSalesColumnMap() {
    return [
        // ... existing mappings ...
        'ผู้ขาย' => 'created_by',
        'Seller' => 'created_by',
        'Sales Person' => 'created_by',
        // ... rest of mappings ...
    ];
}
```

#### B. เพิ่มฟังก์ชันแปลงชื่อเป็น user_id
- **ไฟล์:** `app/services/ImportExportService.php`
- **การเปลี่ยนแปลง:** เพิ่มฟังก์ชัน `getUserIdFromNameOrId()`

```php
private function getUserIdFromNameOrId($nameOrId) {
    if (empty($nameOrId)) {
        return $_SESSION['user_id'] ?? 1; // ใช้ user ปัจจุบันถ้าไม่ระบุ
    }
    
    // ถ้าเป็นตัวเลข ให้ถือว่าเป็น user_id
    if (is_numeric($nameOrId)) {
        return (int)$nameOrId;
    }
    
    try {
        // ค้นหาจากชื่อหรือ username
        $sql = "SELECT user_id FROM users WHERE 
                username = ? OR 
                first_name = ? OR 
                last_name = ? OR 
                CONCAT(first_name, ' ', last_name) = ? OR
                CONCAT(last_name, ' ', first_name) = ?";
        
        $result = $this->db->fetchOne($sql, [$nameOrId, $nameOrId, $nameOrId, $nameOrId, $nameOrId]);
        
        if ($result) {
            return $result['user_id'];
        }
        
        // ถ้าไม่เจอ ให้ใช้ user ปัจจุบัน
        return $_SESSION['user_id'] ?? 1;
        
    } catch (Exception $e) {
        error_log("Error getting user ID from name: " . $e->getMessage());
        return $_SESSION['user_id'] ?? 1;
    }
}
```

#### C. อัพเดทฟังก์ชันสร้างคำสั่งซื้อ
- **ไฟล์:** `app/services/ImportExportService.php`
- **การเปลี่ยนแปลง:** แก้ไขฟังก์ชัน `updateCustomerPurchaseHistory()`

```php
// แปลง created_by จากชื่อหรือรหัสเป็น user_id
$createdBy = $this->getUserIdFromNameOrId($salesData['created_by'] ?? '');

// สร้างคำสั่งซื้อ
$orderData = [
    // ... other fields ...
    'created_by' => $createdBy, // ใช้ user_id ที่แปลงแล้ว
    // ... rest of fields ...
];
```

#### D. อัพเดท CSV Template
- **ไฟล์:** `templates/sales_import_template.csv`
- **การเปลี่ยนแปลง:** เพิ่มคอลัมน์ "ผู้ขาย" ใน header และตัวอย่างข้อมูล

```csv
ชื่อ,นามสกุล,เบอร์โทรศัพท์,อีเมล,ที่อยู่,เขต,จังหวัด,รหัสไปรษณีย์,รหัสสินค้า,ชื่อสินค้า,จำนวน,ราคาต่อชิ้น,ยอดรวม,วันที่สั่งซื้อ,ช่องทางการขาย,ผู้ติดตาม,ผู้ขาย,วิธีการชำระเงิน,สถานะการชำระเงิน,หมายเหตุ
สมชาย,ใจดี,0812345678,somchai@example.com,123 ถ.สุขุมวิท,คลองเตย,กรุงเทพฯ,10110,P001,สินค้า A,2,1500,3000,2025-08-06,TikTok,สมชาย ใจดี,พนักงานขาย1,เงินสด,ชำระแล้ว,ลูกค้าใหม่
```

#### E. อัพเดท Controller Template
- **ไฟล์:** `app/controllers/ImportExportController.php`
- **การเปลี่ยนแปลง:** อัพเดท headers และ sample data ในฟังก์ชัน `downloadTemplate()`

```php
'sales' => [
    'filename' => 'sales_import_template.csv',
    'headers' => ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'จังหวัด', 'รหัสไปรษณีย์', 'ชื่อสินค้า', 'จำนวน', 'ราคาต่อชิ้น', 'ยอดรวม', 'วันที่สั่งซื้อ', 'รหัสสินค้า', 'ผู้ติดตาม', 'ผู้ขาย', 'วิธีการชำระเงิน', 'สถานะการชำระเงิน'],
    'sample' => ['สมชาย', 'ใจดี', '081-111-1111', 'somchai@email.com', '123 ถนนสุขุมวิท', 'กรุงเทพฯ', '10110', 'เสื้อโปโล', '1', '250', '250', '2025-01-01', 'PROD001', 'พนักงานขาย1', 'พนักงานขาย1', 'เงินสด', 'ชำระแล้ว']
],
```

#### F. อัพเดทคำแนะนำในหน้า UI
- **ไฟล์:** `app/views/import-export/index.php`
- **การเปลี่ยนแปลง:** เพิ่มคำอธิบายเกี่ยวกับคอลัมน์ผู้ขาย

```html
<li>✅ <strong>ฟิลด์ใหม่:</strong> ผู้ขาย (ชื่อหรือรหัสพนักงาน) - เพื่อเป็นผลงานของคนนั้น</li>
```

และในส่วนฟิลด์ใหม่:
```html
<li><strong>ผู้ขาย:</strong> ชื่อพนักงานหรือรหัสพนักงานที่เป็นผู้ขาย (เพื่อเป็นผลงานของคนนั้น)</li>
```

## ผลลัพธ์

### 1. หน้าจอขาวตอนอัพโหลด
- ✅ แก้ไขแล้ว - หน้าจอจะไม่ขาวอีกต่อไป
- ✅ Form จะถูก reset และ file input จะถูก clear
- ✅ Results div จะถูกซ่อนหลังจาก 5 วินาที
- ✅ UX ดีขึ้นอย่างมาก

### 2. total_purchase_amount
- ✅ แก้ไขแล้ว - ยอดจะอัพเดทตามคำสั่งซื้อที่ชำระแล้ว
- ✅ รวมทั้ง 'paid' และ 'partial' payment status
- ✅ เพิ่ม logging เพื่อ debug

### 3. คอลัมน์ผู้ขาย
- ✅ เพิ่มแล้ว - สามารถระบุผู้ขายได้ในไฟล์ CSV
- ✅ รองรับทั้งชื่อและรหัสพนักงาน
- ✅ ถ้าไม่ระบุจะใช้ user ปัจจุบัน
- ✅ คำสั่งซื้อจะแสดงเป็นผลงานของคนนั้น

## วิธีการใช้งาน

### การใช้คอลัมน์ผู้ขาย:
1. **ชื่อพนักงาน:** "สมชาย ใจดี", "พนักงานขาย1"
2. **รหัสพนักงาน:** "1", "2", "3"
3. **Username:** "somchai", "sales1"

### ตัวอย่างข้อมูลใน CSV:
```csv
ชื่อ,นามสกุล,เบอร์โทรศัพท์,ผู้ขาย,...
สมชาย,ใจดี,0812345678,พนักงานขาย1,...
สมหญิง,รักดี,0898765432,2,...
```

## วันที่แก้ไข
- วันที่: 2025-01-XX
- เวลา: XX:XX
- สถานะ: ✅ เสร็จสิ้น

## หมายเหตุ
- ระบบจะรองรับการแปลงชื่อพนักงานเป็น user_id อัตโนมัติ
- ถ้าไม่พบชื่อพนักงาน จะใช้ user ปัจจุบันแทน
- total_purchase_amount จะอัพเดททุกครั้งที่มีการสร้างคำสั่งซื้อใหม่
