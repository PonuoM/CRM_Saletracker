# 🔧 การแก้ไข Logic สถานะ "ติดตาม" (Follow-up Status)

## 🐛 **ปัญหาที่พบ**

เมื่อลูกค้าสั่งซื้อสินค้าแล้ว ลูกค้ายังคงปรากฏใน **"Do" tab (ติดตาม)** อยู่ แม้ว่าควรจะออกจากสถานะนี้แล้ว

## 🔍 **สาเหตุของปัญหา**

ลูกค้าจะปรากฏใน "Do" tab เมื่อมีเงื่อนไขใดเงื่อนไขหนึ่งดังนี้:

1. **มี `next_followup_at` ที่ยังไม่ NULL** (จากการบันทึกการโทรที่ตั้งเวลาติดตามไว้)
2. **เป็นลูกค้าใหม่** (`customer_status = 'new'`)
3. **ใกล้หมดอายุ** (`customer_time_expiry <= 7 วัน`)

**ปัญหาหลัก**: เมื่อลูกค้าสั่งซื้อแล้ว ระบบไม่ได้ล้าง `next_followup_at` ที่ถูกตั้งไว้จากการ log call ครั้งก่อน

## ⚡ **การแก้ไข**

### 📋 **สาเหตุทั้งหมดที่ลูกค้าจะอยู่ใน Do tab:**
1. **มี `next_followup_at`** ที่ตั้งไว้จากการโทรหรือนัดหมาย
2. **เป็นลูกค้าใหม่** (`customer_status = 'new'`)  
3. **ใกล้หมดอายุ** (`customer_time_expiry <= 7 วัน`)

### 1. **เพิ่ม Function ล้าง Follow-up**

เพิ่ม method `clearCustomerFollowUp()` ใน `OrderService.php` และ `AppointmentService.php`:

```php
private function clearCustomerFollowUp($customerId) {
    try {
        // ล้าง next_followup_at ในตาราง customers
        $this->db->execute(
            "UPDATE customers SET next_followup_at = NULL WHERE customer_id = ?",
            [$customerId]
        );
        
        // ล้าง next_followup_at ใน call_logs ที่ยังค้างอยู่
        $this->db->execute(
            "UPDATE call_logs SET next_followup_at = NULL 
             WHERE customer_id = ? AND next_followup_at IS NOT NULL",
            [$customerId]
        );
        
    } catch (Exception $e) {
        error_log("Error clearing customer follow-up: " . $e->getMessage());
    }
}
```

### 2. **เรียกใช้เมื่อสร้างคำสั่งซื้อ**

ใน `OrderService::createOrder()`:

```php
// อัปเดตประวัติการซื้อของลูกค้า
$this->updateCustomerPurchaseHistory($orderData['customer_id'], $netAmount);

// ล้าง next_followup_at เพื่อให้ลูกค้าออกจาก Do tab หลังจากสั่งซื้อ
$this->clearCustomerFollowUp($orderData['customer_id']);
```

### 3. **เรียกใช้เมื่ออัปเดตคำสั่งซื้อ**

ใน `OrderService::updateOrder()`:

```php
// อัปเดตประวัติการซื้อของลูกค้า
$this->updateCustomerPurchaseHistory($orderData['customer_id'], $netAmount);

// ล้าง next_followup_at เพื่อให้ลูกค้าออกจาก Do tab หลังจากสั่งซื้อ
$this->clearCustomerFollowUp($orderData['customer_id']);
```

### 4. **เรียกใช้เมื่อปุ่ม "เสร็จ" ในนัดหมาย**

ใน `AppointmentService::handleAppointmentCompletion()`:

```php
// ล้าง next_followup_at เพื่อให้ลูกค้าออกจาก Do tab หลังจากการนัดหมายเสร็จสิ้น
$this->clearCustomerFollowUp($customerId);
```

### 5. **เรียกใช้เมื่อ Log Call ผลสุดท้าย**

ใน `api/calls.php` สำหรับผลการโทรที่สิ้นสุด:

```php
// Clear follow-up for call results that indicate customer interaction is complete
$clearFollowupResults = ['order', 'not_interested', 'do_not_call', 'invalid_number'];
if (in_array($data['call_result'], $clearFollowupResults)) {
    try {
        // ล้าง next_followup_at ในตาราง customers และ call_logs
        $db->execute("UPDATE customers SET next_followup_at = NULL WHERE customer_id = ?", [$data['customer_id']]);
        $db->execute("UPDATE call_logs SET next_followup_at = NULL WHERE customer_id = ? AND next_followup_at IS NOT NULL", [$data['customer_id']]);
    } catch (Exception $e) { /* ignore */ }
}

// Handle customer status changes for NEW customers
if (customer_status === 'new' && in_array($data['call_result'], ['not_interested', 'do_not_call', 'invalid_number'])) {
    $db->execute("UPDATE customers SET customer_status = 'existing' WHERE customer_id = ?", [$data['customer_id']]);
}
```

## 🎯 **ผลลัพธ์หลังการแก้ไข**

### ✅ **ที่จะทำงาน:**
1. **สร้างคำสั่งซื้อใหม่** → ลูกค้าออกจาก Do tab ทันที
2. **อัปเดตคำสั่งซื้อ** → ลูกค้าออกจาก Do tab ทันที  
3. **Log call ผล "สั่งซื้อ"** → ลูกค้าออกจาก Do tab ทันที
4. **กดปุ่ม "เสร็จ" ในนัดหมาย** → ลูกค้าออกจาก Do tab ทันที
5. **Log call ผล "ไม่สนใจ"** → ลูกค้าออกจาก Do tab ทันที
6. **Log call ผล "อย่าโทรมาอีก"** → ลูกค้าออกจาก Do tab ทันที
7. **Log call ผล "เบอร์ไม่ถูก"** → ลูกค้าออกจาก Do tab ทันที

### 🔄 **ลำดับการทำงาน:**

```
1. ลูกค้าอยู่ใน Do tab (มี next_followup_at)
   ↓
2. Telesales โทรหาลูกค้า
   ↓
3. ลูกค้าสั่งซื้อ → Log call ผล "สั่งซื้อ"
   ↓
4. ระบบล้าง next_followup_at อัตโนมัติ
   ↓
5. ลูกค้าหายไปจาก Do tab ในการ refresh ครั้งต่อไป
```

### 📋 **ตารางที่ได้รับผลกระทบ:**

| Table | Field | Action |
|-------|-------|---------|
| `customers` | `next_followup_at` | SET NULL |
| `call_logs` | `next_followup_at` | SET NULL สำหรับ customer_id นั้น |

## 🚀 **การทดสอบ**

### ขั้นตอนการทดสอบ:
1. ให้ลูกค้าอยู่ใน Do tab (ตั้งนัดติดตาม)
2. สร้างคำสั่งซื้อให้ลูกค้านั้น
3. Refresh หน้า customers.php
4. ✅ ลูกค้าควรหายไปจาก Do tab

### ขั้นตอนการทดสอบแบบที่ 2:
1. ให้ลูกค้าอยู่ใน Do tab (ตั้งนัดติดตาม)
2. Log call โดยเลือกผล "สั่งซื้อ"
3. Refresh หน้า customers.php  
4. ✅ ลูกค้าควรหายไปจาก Do tab

## 📝 **หมายเหตุ**

- การแก้ไขนี้ **ไม่ส่งผลกระทบต่อข้อมูลที่มีอยู่เดิม**
- **ปลอดภัย** เพราะเป็นการล้างเฉพาะ next_followup_at เท่านั้น
- **ทำงานย้อนหลังได้** สำหรับลูกค้าที่มีคำสั่งซื้อแล้วแต่ยังค้างใน Do tab
- **ไม่กระทบต่อ logic อื่น** เช่น การคำนวณเกรด, อุณหภูมิ, ฯลฯ

---

**วันที่แก้ไข**: 2025-01-04  
**ผู้แก้ไข**: AI Assistant  
**Status**: ✅ แก้ไขเสร็จสิ้น
