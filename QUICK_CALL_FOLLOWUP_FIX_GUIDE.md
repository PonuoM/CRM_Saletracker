# Quick Call Follow-up Fix Guide

## Problem
หน้า "การโทรติดตามลูกค้า" แสดงว่ามี "ต้องติดตาม" 1 คน แต่รายชื่อไม่ขึ้นมาแสดง

## Root Cause
- มี call_logs ที่มี call_result ที่ต้องติดตาม (เช่น 'callback', 'interested') แต่ไม่มี `next_followup_at`
- API query ใช้เงื่อนไข `WHERE cl.next_followup_at IS NOT NULL` ทำให้ข้อมูลไม่ถูกแสดง

## Quick Fix

### 1. ตรวจสอบปัญหา
รันสคริปต์ `quick_test_call_followup.php` เพื่อตรวจสอบ:
```bash
php quick_test_call_followup.php
```

### 2. แก้ไขปัญหา
รันสคริปต์ `quick_fix_call_followup.php` เพื่อแก้ไข:
```bash
php quick_fix_call_followup.php
```

### 3. ตรวจสอบผลลัพธ์
- รีเฟรชหน้า "การโทรติดตามลูกค้า"
- รายการลูกค้าควรจะแสดงขึ้นมา

## What the Fix Does
สคริปต์จะ:
1. ค้นหา call_logs ที่มี call_result ที่ต้องติดตามแต่ไม่มี next_followup_at
2. เพิ่ม next_followup_at ตาม call_result:
   - `not_interested`: 30 วัน (priority: low)
   - `callback`: 3 วัน (priority: high)
   - `interested`: 7 วัน (priority: medium)
   - `complaint`: 1 วัน (priority: urgent)
3. เพิ่ม followup_priority และ followup_days
4. ตรวจสอบผลลัพธ์

## Expected Result
- ✅ รายการลูกค้าที่ต้องติดตามจะแสดงขึ้นมา
- ✅ สถิติจะตรงกับข้อมูลที่แสดง
- ✅ วันที่ติดตามจะถูกต้องตาม call_result

## Files Created
- `quick_test_call_followup.php` - ตรวจสอบปัญหา
- `quick_fix_call_followup.php` - แก้ไขปัญหา
- `QUICK_CALL_FOLLOWUP_FIX_GUIDE.md` - คู่มือนี้
