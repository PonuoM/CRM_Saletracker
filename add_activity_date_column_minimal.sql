-- เพิ่มคอลัมน์ activity_date ในตาราง customer_activities
-- 
-- วิธีแก้ไขปัญหา: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'activity_date' in 'field list'
-- 
-- รันคำสั่งนี้ใน phpMyAdmin หรือ MySQL client

ALTER TABLE customer_activities 
ADD COLUMN activity_date DATE NULL 
AFTER activity_type; 