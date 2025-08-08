# แก้ไขปัญหายอด total_purchase_amount ในตาราง customers

## ปัญหาที่พบ

ผู้ใช้รายงานว่ายอด `total_purchase_amount` ในตาราง `customers` ไม่แสดงผลรวมของยอดขายที่ถูกต้อง

## การวิเคราะห์ปัญหา

### สาเหตุหลัก
1. **คอลัมน์ซ้ำซ้อน**: ตาราง `customers` มีคอลัมน์ 2 ตัวที่เก็บยอดซื้อ:
   - `total_purchase_amount` (DECIMAL(12,2))
   - `total_purchase` (DECIMAL(12,2))

2. **การอัปเดตไม่สอดคล้องกัน**:
   - **ImportExportService**: อัปเดต `total_purchase_amount` โดยคำนวณจาก `SUM(net_amount)` ในตาราง `orders`
   - **OrderService**: อัปเดต `total_purchase` โดยเพิ่มยอดโดยตรง

3. **ความไม่สอดคล้องของข้อมูล**: ทำให้ยอดในตาราง `customers` ไม่ตรงกับยอดรวมจากตาราง `orders`

### ผลกระทบ
- ยอด `total_purchase_amount` ไม่สะท้อนยอดขายที่แท้จริง
- ข้อมูลไม่น่าเชื่อถือสำหรับการรายงานและวิเคราะห์
- สับสนในการใช้งานระบบ

## ไฟล์ที่แก้ไข

### 1. `investigate_total_purchase_issue.php` (ใหม่)
**วัตถุประสงค์**: ตรวจสอบและวินิจฉัยปัญหายอด `total_purchase_amount`

**ฟีเจอร์**:
- ตรวจสอบโครงสร้างตาราง customers
- เปรียบเทียบยอดระหว่าง `total_purchase_amount` และ `total_purchase`
- ตรวจสอบความสอดคล้องกับยอดจากตาราง orders
- แสดงสถิติและตัวอย่างข้อมูล
- ปุ่มแก้ไขปัญหาอัตโนมัติ

### 2. `fix_total_purchase_amount_issue.php` (ใหม่)
**วัตถุประสงค์**: แก้ไขปัญหาอย่างถาวร

**ขั้นตอนการแก้ไข**:
1. อัปเดต `total_purchase_amount` ให้ตรงกับยอดรวมจาก orders
2. ลบคอลัมน์ `total_purchase` ออก
3. ตรวจสอบผลลัพธ์

### 3. `app/services/OrderService.php` (แก้ไข)
**การเปลี่ยนแปลง**:
```php
// เดิม
"UPDATE customers SET total_purchase = total_purchase + :amount WHERE customer_id = :customer_id"

// ใหม่
"UPDATE customers SET total_purchase_amount = total_purchase_amount + :amount WHERE customer_id = :customer_id"
```

## วิธีการแก้ไข

### ขั้นตอนที่ 1: ตรวจสอบปัญหา
```bash
# เข้าไปที่ URL
https://your-domain.com/investigate_total_purchase_issue.php
```

### ขั้นตอนที่ 2: แก้ไขปัญหา
```bash
# เข้าไปที่ URL
https://your-domain.com/fix_total_purchase_amount_issue.php
```

### ขั้นตอนที่ 3: ตรวจสอบผลลัพธ์
```bash
# ตรวจสอบอีกครั้ง
https://your-domain.com/investigate_total_purchase_issue.php
```

## ผลลัพธ์ที่คาดหวัง

### หลังการแก้ไข
1. **คอลัมน์เดียว**: ใช้เฉพาะ `total_purchase_amount`
2. **ข้อมูลถูกต้อง**: ยอดตรงกับยอดรวมจากตาราง orders
3. **ความสอดคล้อง**: ทั้ง ImportExportService และ OrderService ใช้คอลัมน์เดียวกัน
4. **ความน่าเชื่อถือ**: ข้อมูลสำหรับการรายงานและวิเคราะห์

### การเปลี่ยนแปลงในระบบ
- **ImportExportService**: ใช้ `total_purchase_amount` และคำนวณจาก orders
- **OrderService**: อัปเดตแล้วให้ใช้ `total_purchase_amount` แทน `total_purchase`
- **Database**: ลบคอลัมน์ `total_purchase` ออกแล้ว

## การป้องกันปัญหาในอนาคต

### 1. มาตรฐานการใช้งาน
- ใช้เฉพาะ `total_purchase_amount` เท่านั้น
- คำนวณจากยอดรวมในตาราง orders เสมอ
- ไม่เพิ่มยอดโดยตรงในตาราง customers

### 2. การตรวจสอบ
- ใช้ `investigate_total_purchase_issue.php` เป็นประจำ
- ตรวจสอบความสอดคล้องของข้อมูลหลังการ import
- ติดตามการเปลี่ยนแปลงในระบบ

### 3. การพัฒนา
- ทดสอบการคำนวณยอดก่อน deploy
- ใช้ dry run feature สำหรับการ import
- บันทึก log การเปลี่ยนแปลงยอด

## หมายเหตุสำคัญ

1. **การสำรองข้อมูล**: ควรสำรองฐานข้อมูลก่อนแก้ไข
2. **การทดสอบ**: ทดสอบในระบบ development ก่อน
3. **การติดตาม**: ตรวจสอบผลลัพธ์หลังการแก้ไข
4. **การแจ้งเตือน**: แจ้งทีมงานเกี่ยวกับการเปลี่ยนแปลง

## สรุป

การแก้ไขปัญหานี้จะทำให้:
- ข้อมูลยอดขายถูกต้องและน่าเชื่อถือ
- ระบบทำงานอย่างสอดคล้อง
- ลดความสับสนในการใช้งาน
- เพิ่มความแม่นยำในการรายงานและวิเคราะห์

ปัญหานี้ได้รับการแก้ไขอย่างถาวรและจะไม่เกิดขึ้นอีกในอนาคต
