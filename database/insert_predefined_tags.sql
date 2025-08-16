-- เพิ่ม Predefined Tags เริ่มต้นสำหรับระบบ Tag
-- Tags เหล่านี้จะแสดงเป็นตัวเลือกให้ telesales เลือกใช้

INSERT INTO predefined_tags (tag_name, tag_color, is_global, created_by) VALUES
-- Tags ระดับบริษัท (Global Tags)
('VIP', '#dc3545', 1, NULL),
('ลูกค้าประจำ', '#28a745', 1, NULL),
('ต้องโทรด่วน', '#ffc107', 1, NULL),
('สนใจมาก', '#007bff', 1, NULL),
('ไม่สนใจ', '#6c757d', 1, NULL),
('รอติดต่อใหม่', '#fd7e14', 1, NULL),
('ไม่รับสาย', '#e83e8c', 1, NULL),
('เบอร์ผิด', '#20c997', 1, NULL),
('ย้ายไปแล้ว', '#6f42c1', 1, NULL),
('ครอบครัวใหญ่', '#17a2b8', 1, NULL),
('มีร้านค้า', '#495057', 1, NULL),
('แนะนำเพื่อน', '#e9ecef', 1, NULL),

-- Tags สำหรับการติดตาม
('นัดเวลาแล้ว', '#28a745', 1, NULL),
('รอข้อมูลเพิ่ม', '#ffc107', 1, NULL),
('พิจารณาอยู่', '#17a2b8', 1, NULL),
('รอคุยสามี/ภรรยา', '#fd7e14', 1, NULL),
('รอเงินเดือนออก', '#6f42c1', 1, NULL),

-- Tags สำหรับประเภทลูกค้า
('ผู้สูงอายุ', '#7952b3', 1, NULL),
('คนหนุ่มสาว', '#20c997', 1, NULL),
('แม่บ้าน', '#e83e8c', 1, NULL),
('ขับรถ Uber/Grab', '#ffc107', 1, NULL),
('ข้าราชการ', '#198754', 1, NULL),
('พนักงานบริษัท', '#0d6efd', 1, NULL)

ON DUPLICATE KEY UPDATE 
tag_name = VALUES(tag_name),
tag_color = VALUES(tag_color);
