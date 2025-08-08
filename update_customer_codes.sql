-- อัปเดต customer_code สำหรับลูกค้าที่มีอยู่แล้ว
-- สร้างจากเบอร์โทร 9 หลัก (ไม่รวม 0 ข้างหน้า) ขึ้นต้นด้วย "Cus-"

UPDATE customers 
SET customer_code = CONCAT('Cus-', 
    CASE 
        WHEN LENGTH(REPLACE(phone, '-', '')) > 9 
        THEN RIGHT(REPLACE(phone, '-', ''), 9)
        ELSE LPAD(REPLACE(phone, '-', ''), 9, '0')
    END
)
WHERE customer_code IS NULL OR customer_code = '';

-- ตรวจสอบผลลัพธ์
SELECT customer_id, first_name, last_name, phone, customer_code 
FROM customers 
WHERE customer_code IS NOT NULL 
ORDER BY customer_id 
LIMIT 10;
