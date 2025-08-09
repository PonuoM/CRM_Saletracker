# การแก้ไขปัญหาวันที่ติดตามในหน้า การโทรติดตาม

## ปัญหาที่พบ

ในหน้า "การโทรติดตาม" (`calls.php`) คอลัมน์ "วันที่ติดตาม" ไม่แสดงข้อมูล หรือแสดงข้อมูลไม่ถูกต้อง

## สาเหตุของปัญหา

1. **API ไม่ส่งข้อมูลที่จำเป็น**: API endpoint `api/calls.php?action=get_followup_customers` ไม่ส่งฟิลด์ `next_followup_at` กลับไป
2. **การคำนวณ urgency_status ผิด**: ใช้ `last_call_date` แทน `next_followup_at` ในการคำนวณความเร่งด่วน
3. **ขาดข้อมูล assigned_to_name**: ไม่มีการ JOIN กับตาราง `users` เพื่อดึงชื่อผู้รับผิดชอบ
4. **ขาดข้อมูล queue_status**: ไม่มีการ JOIN กับตาราง `call_followup_queue` เพื่อดึงสถานะคิว

## วิธีแก้ไข

### 1. ปรับปรุง API Endpoint (`api/calls.php`)

**ก่อนแก้ไข:**
```php
$sql = "SELECT 
            c.customer_id,
            c.customer_code,
            c.first_name,
            c.last_name,
            c.phone,
            c.email,
            c.province,
            c.temperature_status,
            c.customer_grade,
            cl.call_result,
            cl.call_status,
            cl.created_at as last_call_date,
            cl.notes,
            DATEDIFF(NOW(), cl.created_at) as days_since_call,
            CASE 
                WHEN cl.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'overdue'
                WHEN cl.created_at < DATE_SUB(NOW(), INTERVAL 3 DAY) THEN 'urgent'
                WHEN cl.created_at < DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 'soon'
                ELSE 'normal'
            END as urgency_status,
            'medium' as followup_priority
        FROM call_logs cl
        JOIN customers c ON cl.customer_id = c.customer_id
        WHERE {$whereClause}
        AND cl.call_result IN ('not_interested', 'callback', 'interested')
        ORDER BY cl.created_at ASC
        LIMIT 50";
```

**หลังแก้ไข:**
```php
// ใช้ customer_call_followup_list view ถ้ามีอยู่
try {
    $sql = "SELECT * FROM customer_call_followup_list WHERE 1=1";
    // ... กรองตามเงื่อนไขต่างๆ
} catch (Exception $e) {
    // ถ้า view ไม่มีอยู่ ใช้ query แบบเดิมแต่ปรับปรุง
    $sql = "SELECT 
                c.customer_id,
                c.customer_code,
                c.first_name,
                c.last_name,
                c.phone,
                c.email,
                c.province,
                c.temperature_status,
                c.customer_grade,
                u.full_name as assigned_to_name,
                cl.call_result,
                cl.call_status,
                cl.created_at as last_call_date,
                cl.next_followup_at,
                cl.notes,
                cl.followup_priority,
                cfq.status as queue_status,
                DATEDIFF(cl.next_followup_at, NOW()) as days_until_followup,
                CASE 
                    WHEN cl.next_followup_at <= NOW() THEN 'overdue'
                    WHEN cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 3 DAY) THEN 'urgent'
                    WHEN cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'soon'
                    ELSE 'normal'
                END as urgency_status
            FROM call_logs cl
            JOIN customers c ON cl.customer_id = c.customer_id
            LEFT JOIN users u ON c.assigned_to = u.user_id
            LEFT JOIN call_followup_queue cfq ON c.customer_id = cfq.customer_id AND cfq.status = 'pending'
            WHERE cl.next_followup_at IS NOT NULL
            AND cl.call_result IN ('not_interested', 'callback', 'interested', 'complaint')";
}
```

### 2. ปรับปรุง Frontend JavaScript (`app/views/calls/index.php`)

**ก่อนแก้ไข:**
```javascript
.then(data => {
    if (data.success) {
        followupCustomers = data.data;
        renderFollowupTable();
        updateCustomerCount();
    } else {
        showAlert('error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล: ' + data.message);
    }
})
```

**หลังแก้ไข:**
```javascript
.then(data => {
    if (data.success) {
        followupCustomers = data.data || data.customers || [];
        renderFollowupTable();
        updateCustomerCount();
    } else {
        showAlert('error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล: ' + (data.message || data.error));
    }
})
```

## ข้อมูลที่ Frontend ต้องการ

### ฟิลด์ที่จำเป็น:
1. **`next_followup_at`**: วันเวลาที่ต้องติดตาม (สำหรับคอลัมน์ "วันที่ติดตาม")
2. **`assigned_to_name`**: ชื่อผู้รับผิดชอบ (สำหรับคอลัมน์ "ผู้รับผิดชอบ")
3. **`urgency_status`**: สถานะความเร่งด่วน (overdue, urgent, soon, normal)
4. **`queue_status`**: สถานะคิวการติดตาม (pending, in_progress, completed, cancelled)
5. **`followup_priority`**: ความสำคัญของการติดตาม (low, medium, high, urgent)

### การคำนวณ urgency_status:
```sql
CASE 
    WHEN cl.next_followup_at <= NOW() THEN 'overdue'      -- เกินกำหนด
    WHEN cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 3 DAY) THEN 'urgent'  -- เร่งด่วน (3 วัน)
    WHEN cl.next_followup_at <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'soon'    -- เร็วๆ นี้ (7 วัน)
    ELSE 'normal'                                          -- ปกติ
END as urgency_status
```

## วิธีการทำงานของระบบ

### 1. การกำหนดวันติดตาม
วันติดตาม (`next_followup_at`) ถูกกำหนดตามกฎในตาราง `call_followup_rules`:

- **not_interested**: 30 วัน
- **callback**: 3 วัน  
- **interested**: 7 วัน
- **complaint**: 1 วัน
- **order**: 0 วัน (ไม่ต้องติดตาม)

### 2. การคำนวณความเร่งด่วน
ความเร่งด่วนคำนวณจาก `next_followup_at` เทียบกับวันปัจจุบัน:

- **overdue**: เกินกำหนด (next_followup_at <= วันนี้)
- **urgent**: เร่งด่วน (ภายใน 3 วัน)
- **soon**: เร็วๆ นี้ (ภายใน 7 วัน)
- **normal**: ปกติ (เกิน 7 วัน)

### 3. การแสดงผลในหน้า
- **คอลัมน์ "วันที่ติดตาม"**: แสดง `next_followup_at`
- **คอลัมน์ "ผู้รับผิดชอบ"**: แสดง `assigned_to_name`
- **คอลัมน์ "ความสำคัญ"**: แสดง `followup_priority`
- **คอลัมน์ "สถานะ"**: แสดง `queue_status`

## การทดสอบ

ใช้ไฟล์ `test_call_followup_date_fix.php` เพื่อทดสอบ:

1. ตรวจสอบข้อมูล call_logs ที่มี next_followup_at
2. ตรวจสอบ customer_call_followup_list view
3. ทดสอบ API endpoint โดยตรง
4. ตรวจสอบความครบถ้วนของข้อมูล
5. ตรวจสอบการคำนวณ urgency_status

## ผลลัพธ์ที่คาดหวัง

✅ **คอลัมน์ "วันที่ติดตาม"** จะแสดงวันที่ที่ต้องติดตามตาม `next_followup_at`
✅ **คอลัมน์ "ผู้รับผิดชอบ"** จะแสดงชื่อผู้รับผิดชอบตาม `assigned_to_name`
✅ **สถานะความเร่งด่วน** จะคำนวณจาก `next_followup_at` แทน `last_call_date`
✅ **การกรองข้อมูล** จะทำงานได้ถูกต้องตาม urgency, call_result, priority

## ไฟล์ที่แก้ไข

1. `api/calls.php` - ปรับปรุง getFollowupCustomers function
2. `app/views/calls/index.php` - ปรับปรุง JavaScript เพื่อรองรับข้อมูลใหม่

## ไฟล์ที่สร้าง

1. `test_call_followup_date_issue.php` - ไฟล์ทดสอบปัญหา
2. `test_call_followup_date_fix.php` - ไฟล์ทดสอบการแก้ไข
3. `CALL_FOLLOWUP_DATE_FIX_SUMMARY.md` - เอกสารสรุปการแก้ไข
