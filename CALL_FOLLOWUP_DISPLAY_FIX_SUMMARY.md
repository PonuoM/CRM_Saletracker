# Call Follow-up Display Fix Summary

## Problem
หน้า "การโทรติดตามลูกค้า" แสดงว่ามี "ต้องติดตาม" 1 คน แต่รายชื่อไม่ขึ้นมาแสดง และแสดงข้อความ "ไม่มีลูกค้าที่ต้องติดตามการโทร" แทน

## Root Cause
ปัญหาหลักคือ:
1. **ข้อมูล call_logs ที่มี call_result ที่ต้องติดตาม แต่ไม่มี next_followup_at** - ระบบต้องการ `next_followup_at` เพื่อแสดงในรายการติดตาม
2. **API query ใช้เงื่อนไข `WHERE cl.next_followup_at IS NOT NULL`** - ทำให้ call_logs ที่ไม่มี next_followup_at ไม่ถูกแสดง
3. **การนับจำนวนใช้เงื่อนไขที่แตกต่างกัน** - สถิติแสดง 1 คน แต่ query ไม่พบข้อมูล

## Analysis
จากภาพที่แสดง:
- **การโทรทั้งหมด**: 1
- **ติดต่อได้**: 0  
- **ต้องติดตาม**: 1
- **เกินกำหนด**: 0

แต่รายการลูกค้าไม่แสดง แสดงว่ามี call_logs ที่มี call_result ที่ต้องติดตาม (เช่น 'callback', 'interested', 'not_interested', 'complaint') แต่ไม่มี `next_followup_at` ที่ตั้งค่าไว้

## Solution
สร้างสคริปต์ `fix_call_followup_data.php` เพื่อ:

1. **ค้นหา call_logs ที่ต้องติดตามแต่ไม่มี next_followup_at**
2. **เพิ่ม next_followup_at ตาม call_result**:
   - `not_interested`: 30 วัน (priority: low)
   - `callback`: 3 วัน (priority: high)
   - `interested`: 7 วัน (priority: medium)
   - `complaint`: 1 วัน (priority: urgent)
3. **เพิ่ม followup_priority และ followup_days**
4. **ตรวจสอบผลลัพธ์**

## Expected Outcome
หลังจากรันสคริปต์:
- ✅ รายการลูกค้าที่ต้องติดตามจะแสดงขึ้นมา
- ✅ สถิติจะตรงกับข้อมูลที่แสดง
- ✅ วันที่ติดตามจะถูกต้องตาม call_result
- ✅ ความสำคัญ (priority) จะถูกตั้งค่าตามประเภทการติดตาม

## Files Modified
- `fix_call_followup_data.php` - สคริปต์แก้ไขข้อมูล
- `test_call_followup_simple.php` - สคริปต์ทดสอบข้อมูล

## Next Steps
1. รัน `fix_call_followup_data.php` เพื่อแก้ไขข้อมูล
2. ตรวจสอบหน้า "การโทรติดตามลูกค้า" อีกครั้ง
3. ตรวจสอบว่ารายการลูกค้าแสดงขึ้นมาหรือไม่
