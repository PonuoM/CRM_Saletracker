# Payment Method Enhancement Summary

## Overview
เพิ่มฟีเจอร์ "รับสินค้าก่อนชำระ" (Receive Goods Before Payment) ในการจัดการคำสั่งซื้อและการนำเข้าข้อมูล

## Changes Made

### 1. Database Schema Update
- **File:** `update_payment_method.php`
- **Change:** เพิ่ม 'receive_before_payment' ใน ENUM ของ payment_method
- **New ENUM values:** `('cash', 'transfer', 'cod', 'credit', 'receive_before_payment', 'other')`

### 2. Import/Export Service Updates
- **File:** `app/services/ImportExportService.php`
- **Changes:**
  - เพิ่ม mapping สำหรับ `วิธีการชำระเงิน` และ `สถานะการชำระเงิน` ใน `getSalesColumnMap()`
  - อัปเดต `updateCustomerPurchaseHistory()` เพื่อรองรับ payment_method และ payment_status จาก CSV
  - เพิ่ม helper methods:
    - `mapPaymentMethod()` - แปลงชื่อภาษาไทยเป็นภาษาอังกฤษ
    - `mapPaymentStatus()` - แปลงสถานะภาษาไทยเป็นภาษาอังกฤษ

### 3. CSV Template Updates
- **File:** `templates/sales_import_template.csv`
- **Changes:** เพิ่มคอลัมน์ `วิธีการชำระเงิน` และ `สถานะการชำระเงิน`
- **Example data:** แสดงตัวอย่างการใช้ "รับสินค้าก่อนชำระ" และ "รอดำเนินการ"

### 4. Order Management UI Updates
- **Files:** 
  - `app/views/orders/create.php`
  - `app/views/orders/edit.php`
  - `app/views/orders/index.php`
- **Changes:**
  - เพิ่มตัวเลือก "รับสินค้าก่อนชำระ" ใน dropdown
  - อัปเดตการแสดงผลในตารางรายการคำสั่งซื้อ
  - เพิ่ม badge styling สำหรับ payment methods พิเศษ

### 5. Import Instructions Update
- **File:** `app/views/import-export/index.php`
- **Changes:** เพิ่มคำอธิบายเกี่ยวกับฟิลด์ใหม่ในการนำเข้าข้อมูล

## Payment Method Options

### Available Payment Methods:
1. **เงินสด** (cash)
2. **โอนเงิน** (transfer)
3. **เก็บเงินปลายทาง** (cod)
4. **รับสินค้าก่อนชำระ** (receive_before_payment) - **NEW**
5. **เครดิต** (credit)
6. **อื่นๆ** (other)

### Payment Status Options:
1. **รอดำเนินการ** (pending)
2. **ชำระแล้ว** (paid)
3. **ชำระบางส่วน** (partial)
4. **ยกเลิก** (cancelled)

## CSV Import Support

### New Columns in Sales Import:
- `วิธีการชำระเงิน` / `Payment Method`
- `สถานะการชำระเงิน` / `Payment Status`

### Example CSV Row:
```csv
สมชาย,ใจดี,0812345678,somchai@example.com,123 ถ.สุขุมวิท,คลองเตย,กรุงเทพฯ,10110,P001,สินค้า A,2,1500,3000,2025-08-06,TikTok,สมชาย ใจดี,รับสินค้าก่อนชำระ,รอดำเนินการ,ลูกค้าใหม่
```

## Usage Instructions

### 1. Database Update
รันไฟล์ `update_payment_method.php` เพื่ออัปเดต database schema

### 2. Import Data
- ใช้ template ใหม่ที่มีคอลัมน์ payment fields
- ระบบจะแปลงชื่อภาษาไทยเป็นภาษาอังกฤษโดยอัตโนมัติ
- ถ้าไม่ระบุ payment method จะใช้ค่า default เป็น 'cash'
- ถ้าไม่ระบุ payment status จะใช้ค่า default เป็น 'pending'

### 3. Manual Order Creation
- เลือก "รับสินค้าก่อนชำระ" จาก dropdown ในหน้าสร้าง/แก้ไขคำสั่งซื้อ
- ระบบจะแสดงผลด้วย badge สีเหลืองเพื่อแยกแยะจาก payment methods อื่น

## Technical Notes

### Mapping Logic:
- รองรับทั้งภาษาไทยและภาษาอังกฤษ
- มี fallback เป็นค่า default ถ้าไม่พบ mapping
- ใช้ array mapping เพื่อประสิทธิภาพ

### Database Compatibility:
- ใช้ ALTER TABLE เพื่อเพิ่ม ENUM value ใหม่
- ไม่กระทบข้อมูลเดิม
- รองรับ backward compatibility

## Testing Recommendations

1. **Database Update Test:**
   - รัน `update_payment_method.php`
   - ตรวจสอบว่า ENUM ถูกอัปเดตแล้ว

2. **Import Test:**
   - ทดสอบ import CSV ที่มี payment fields ใหม่
   - ตรวจสอบว่าข้อมูลถูกบันทึกถูกต้อง

3. **UI Test:**
   - ทดสอบการสร้าง/แก้ไขคำสั่งซื้อด้วย payment method ใหม่
   - ตรวจสอบการแสดงผลในตารางรายการ

4. **Edge Cases:**
   - ทดสอบ import โดยไม่ระบุ payment fields
   - ทดสอบ import ด้วยค่า payment method ที่ไม่ถูกต้อง
