# การแก้ไขประวัติคำสั่งซื้อในหน้าแสดงรายละเอียดลูกค้า

## ปัญหาที่พบ

1. **สถานะคำสั่งซื้อแสดง "ไม่ระบุ"** ทั้งๆที่มีสถานะในฐานข้อมูล
2. **ไม่มีคอลัมน์ "ผู้ขาย"** ในตารางประวัติคำสั่งซื้อ

## การแก้ไข

### 1. เพิ่มคอลัมน์ "ผู้ขาย"

**ไฟล์ที่แก้ไข:** `app/controllers/CustomerController.php`

**การเปลี่ยนแปลง:**
- แก้ไข SQL query ใน method `show()` ให้ดึงข้อมูลผู้ขายจากตาราง `users`
- ใช้ `created_by` เป็น foreign key เชื่อมกับ `users.user_id`

```sql
-- ก่อนแก้ไข
SELECT o.*, COUNT(oi.item_id) as item_count 
FROM orders o 
LEFT JOIN order_items oi ON o.order_id = oi.order_id 
WHERE o.customer_id = :customer_id 
GROUP BY o.order_id 
ORDER BY o.order_date DESC

-- หลังแก้ไข
SELECT o.*, COUNT(oi.item_id) as item_count, u.full_name as salesperson_name 
FROM orders o 
LEFT JOIN order_items oi ON o.order_id = oi.order_id 
LEFT JOIN users u ON o.created_by = u.user_id 
WHERE o.customer_id = :customer_id 
GROUP BY o.order_id 
ORDER BY o.order_date DESC
```

### 2. แก้ไขการแสดงผลในหน้า View

**ไฟล์ที่แก้ไข:** `app/views/customers/show.php`

**การเปลี่ยนแปลง:**

#### 2.1 เพิ่มคอลัมน์ "ผู้ขาย" ในตาราง
```html
<!-- เพิ่มคอลัมน์ใน thead -->
<th style="font-size: 14px;">ผู้ขาย</th>

<!-- เพิ่มข้อมูลใน tbody -->
<td style="font-size: 14px;">
    <?php if (!empty($order['salesperson_name'])): ?>
        <span class="badge bg-info"><?php echo htmlspecialchars($order['salesperson_name']); ?></span>
    <?php else: ?>
        <span class="text-muted">ไม่ระบุ</span>
    <?php endif; ?>
</td>
```

#### 2.2 แก้ไขการแสดงสถานะ
- เปลี่ยนจากการใช้ `$order['status']` เป็น `$order['payment_status']`
- เพิ่มการรองรับสถานะต่างๆ ตาม ENUM ในฐานข้อมูล

```php
$orderStatus = $order['payment_status'] ?? $order['status'] ?? $order['order_status'] ?? '';
switch($orderStatus) {
    case 'paid':
        $statusText = 'ชำระแล้ว';
        $statusClass = 'success';
        break;
    case 'pending':
        $statusText = 'รอชำระ';
        $statusClass = 'warning';
        break;
    case 'partial':
        $statusText = 'ชำระบางส่วน';
        $statusClass = 'info';
        break;
    case 'cancelled':
    case 'canceled':
        $statusText = 'ยกเลิก';
        $statusClass = 'danger';
        break;
    // ... อื่นๆ
}
```

## ผลลัพธ์

### ก่อนแก้ไข:
- ตารางมี 4 คอลัมน์: เลขที่, วันที่, ยอดรวม, สถานะ
- สถานะแสดง "ไม่ระบุ" เสมอ
- ไม่ทราบว่าใครเป็นผู้ขาย

### หลังแก้ไข:
- ตารางมี 5 คอลัมน์: เลขที่, วันที่, **ผู้ขาย**, ยอดรวม, สถานะ
- สถานะแสดงถูกต้องตามข้อมูลในฐานข้อมูล
- แสดงชื่อผู้ขายด้วย badge สีฟ้า

## สถานะที่รองรับ

| ค่าในฐานข้อมูล | แสดงผล | สี |
|---------------|--------|-----|
| `paid` | ชำระแล้ว | เขียว |
| `pending` | รอชำระ | เหลือง |
| `partial` | ชำระบางส่วน | ฟ้า |
| `cancelled` | ยกเลิก | แดง |
| `completed` | เสร็จสิ้น | เขียว |
| `processing` | กำลังดำเนินการ | น้ำเงิน |

## การทดสอบ

1. เข้าหน้าแสดงรายละเอียดลูกค้า: `customers.php?action=show&id=65`
2. ไปที่แถบ "ประวัติคำสั่งซื้อ"
3. ตรวจสอบว่า:
   - มีคอลัมน์ "ผู้ขาย" แสดงชื่อผู้ขาย
   - สถานะแสดงถูกต้องไม่ใช่ "ไม่ระบุ"
   - ข้อมูลครบถ้วนและถูกต้อง
