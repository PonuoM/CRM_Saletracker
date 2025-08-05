-- สร้างข้อมูลทดลองสำหรับทดสอบ Cron Jobs
-- รันไฟล์นี้เพื่อเพิ่มข้อมูลลูกค้าสำหรับทดสอบ

-- เพิ่มลูกค้าทดลองที่มียอดซื้อต่างกัน (เพื่อทดสอบการอัปเดตเกรด)
INSERT INTO customers (first_name, last_name, phone, email, address, 
                      temperature_status, customer_grade, total_purchase_amount,
                      last_contact_at, is_active, created_at) VALUES

-- ลูกค้าเกรด A+ (ยอดซื้อสูง แต่ไม่ได้ติดต่อนาน)
('สมชาย', 'รวยมาก', '081-111-1111', 'somchai.rich@example.com', 
 '123 ถ.สุขุมวิท กรุงเทพฯ', 'hot', 'A+', 150000.00, 
 DATE_SUB(NOW(), INTERVAL 45 DAY), 1, DATE_SUB(NOW(), INTERVAL 60 DAY)),

-- ลูกค้าเกรด A (ยอดซื้อปานกลาง ไม่ได้ติดต่อ 35 วัน)
('สมหญิง', 'ใจดี', '081-222-2222', 'somying.kind@example.com', 
 '456 ถ.รัชดา กรุงเทพฯ', 'warm', 'A', 75000.00, 
 DATE_SUB(NOW(), INTERVAL 35 DAY), 1, DATE_SUB(NOW(), INTERVAL 50 DAY)),

-- ลูกค้าเกรด B (ยอดซื้อน้อย แต่ติดต่อเมื่อเร็วๆ นี้)
('สมศักดิ์', 'มั่นคง', '081-333-3333', 'somsak.stable@example.com', 
 '789 ถ.ลาดพร้าว กรุงเทพฯ', 'hot', 'B', 25000.00, 
 DATE_SUB(NOW(), INTERVAL 5 DAY), 1, DATE_SUB(NOW(), INTERVAL 40 DAY)),

-- ลูกค้าเกรด C (ยอดซื้อน้อย ไม่ได้ติดต่อ 60 วัน)
('สมใจ', 'ประหยัด', '081-444-4444', 'somjai.save@example.com', 
 '101 ถ.พระราม 4 กรุงเทพฯ', 'cold', 'C', 8000.00, 
 DATE_SUB(NOW(), INTERVAL 60 DAY), 1, DATE_SUB(NOW(), INTERVAL 70 DAY)),

-- ลูกค้าเกรด D (ยอดซื้อต่ำ ไม่ได้ติดต่อนานมาก)
('สมหมาย', 'รอดี', '081-555-5555', 'sommai.wait@example.com', 
 '202 ถ.เพชรบุรี กรุงเทพฯ', 'frozen', 'D', 2000.00, 
 DATE_SUB(NOW(), INTERVAL 100 DAY), 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),

-- ลูกค้าที่ควรอัปเกรดเป็น A+ (ยอดซื้อสูงแต่เกรดยังต่ำ)
('สมทรง', 'เศรษฐี', '081-666-6666', 'somsong.wealthy@example.com', 
 '303 ถ.สีลม กรุงเทพฯ', 'warm', 'B', 120000.00, 
 DATE_SUB(NOW(), INTERVAL 20 DAY), 1, DATE_SUB(NOW(), INTERVAL 30 DAY)),

-- ลูกค้าที่ควรเปลี่ยนอุณหภูมิเป็น frozen (ไม่ได้ติดต่อนานมาก)
('สมพร', 'หายไป', '081-777-7777', 'somporn.lost@example.com', 
 '404 ถ.บางนา กรุงเทพฯ', 'warm', 'C', 15000.00, 
 DATE_SUB(NOW(), INTERVAL 95 DAY), 1, DATE_SUB(NOW(), INTERVAL 100 DAY));

-- อัปเดตสถิติ
SELECT 'Sample data created successfully!' as Status;

-- แสดงข้อมูลที่เพิ่งสร้าง
SELECT 
    CONCAT(first_name, ' ', last_name) as customer_name,
    customer_grade,
    temperature_status,
    total_purchase_amount,
    DATEDIFF(NOW(), COALESCE(last_contact_at, created_at)) as days_since_contact,
    last_contact_at
FROM customers 
WHERE first_name IN ('สมชาย', 'สมหญิง', 'สมศักดิ์', 'สมใจ', 'สมหมาย', 'สมทรง', 'สมพร')
ORDER BY total_purchase_amount DESC;