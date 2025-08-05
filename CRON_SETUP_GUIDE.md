# 🤖 คำแนะนำการตั้งค่า Cron Jobs สำหรับ CRM SalesTracker

## 📋 ขั้นตอนการเตรียมการสำหรับคืนนี้

### 1. **สร้างข้อมูลทดลอง**
```sql
-- รันไฟล์ create_sample_data.sql ใน phpMyAdmin
-- เพื่อเพิ่มลูกค้าทดลองสำหรับดูผลการทำงาน
```

### 2. **ทดสอบระบบก่อน**
```bash
# เข้าไปดูหน้าทดสอบ
http://your-domain.com/manual_test_cron.php

# หรือ
http://your-domain.com/test_cron_jobs.php
```

## 🖥️ การตั้งค่า Cron Jobs ใน Server

### **วิธีที่ 1: ใช้ cPanel (แนะนำสำหรับ Shared Hosting)**

1. **เข้า cPanel** → **Cron Jobs**

2. **เพิ่ม Cron Job ใหม่:**
   - **Minute:** 0
   - **Hour:** 1  
   - **Day:** *
   - **Month:** *
   - **Weekday:** *
   - **Command:** `/usr/bin/php /home/primacom/domains/prima49.com/public_html/Customer/cron/run_all_jobs.php`

3. **หรือ เพิ่มแยกทีละงาน:**
   ```bash
   # อัปเดตเกรดลูกค้า - ทุกวัน 2:00 น.
   0 2 * * * /usr/bin/php /home/primacom/domains/prima49.com/public_html/Customer/cron/update_customer_grades.php
   
   # อัปเดตอุณหภูมิลูกค้า - ทุกวัน 2:30 น.
   30 2 * * * /usr/bin/php /home/primacom/domains/prima49.com/public_html/Customer/cron/update_customer_temperatures.php
   
   # ส่งการแจ้งเตือน - ทุกวัน 3:00 น.
   0 3 * * * /usr/bin/php /home/primacom/domains/prima49.com/public_html/Customer/cron/send_recall_notifications.php
   ```

### **วิธีที่ 2: ใช้ Command Line (VPS/Dedicated Server)**

1. **เข้า SSH**
2. **แก้ไข crontab:**
   ```bash
   crontab -e
   ```

3. **เพิ่มบรรทัดนี้:**
   ```bash
   # CRM SalesTracker Cron Jobs
   0 1 * * * /usr/bin/php /path/to/your/project/cron/run_all_jobs.php >> /path/to/your/project/logs/cron.log 2>&1
   ```

4. **บันทึกและออก** (Ctrl+X, Y, Enter)

### **วิธีที่ 3: ใช้ URL Cron (Backup Plan)**

ถ้าไม่สามารถใช้ PHP CLI ได้ ใช้ URL cron แทน:

1. **สร้างไฟล์ `cron_web.php`:**
   ```php
   <?php
   // ตรวจสอบ IP หรือ token เพื่อความปลอดภัย
   if ($_GET['token'] !== 'your-secret-token-here') {
       die('Unauthorized');
   }
   
   require_once 'app/services/CronJobService.php';
   $cronService = new CronJobService();
   $result = $cronService->runAllJobs();
   echo json_encode($result);
   ?>
   ```

2. **ตั้งค่าใน cPanel เรียก URL:**
   ```bash
   0 1 * * * curl -s "http://your-domain.com/cron_web.php?token=your-secret-token-here"
   ```

## 🔍 การตรวจสอบการทำงาน

### **1. ตรวจสอบ Log Files**
```bash
# ดู log การรัน cron jobs
tail -f logs/cron.log

# ดูใน database
SELECT * FROM cron_job_logs ORDER BY created_at DESC LIMIT 10;
```

### **2. ตรวจสอบผลการทำงาน**
```sql
-- ดูการเปลี่ยนแปลงเกรดลูกค้า
SELECT * FROM activity_logs WHERE activity_type = 'grade_change' ORDER BY created_at DESC;

-- ดูการเปลี่ยนแปลงอุณหภูมิลูกค้า  
SELECT * FROM activity_logs WHERE activity_type = 'temperature_change' ORDER BY created_at DESC;

-- ดูรายการลูกค้าที่ต้องติดตาม
SELECT * FROM customer_recall_list WHERE created_date = CURDATE();

-- ดูการแจ้งเตือนที่ส่งไป
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10;
```

### **3. ตรวจสอบการตั้งค่า**
```sql
-- ดูการตั้งค่า cron jobs
SELECT * FROM cron_job_settings;

-- อัปเดตเวลารันล่าสุด
UPDATE cron_job_settings SET last_run = NOW() WHERE job_name = 'update_customer_grades';
```

## 📊 สิ่งที่คาดหวังจะเห็นพรุ่งนี้เช้า

### **ถ้าข้อมูลทดลองถูกสร้าง และ Cron Jobs ทำงาน:**

1. **การเปลี่ยนแปลงเกรด:**
   - สมทรง เศรษฐี: B → A+ (ยอดซื้อ ฿120,000)
   - ลูกค้าอื่นๆ อาจมีการปรับเกรด

2. **การเปลี่ยนแปลงอุณหภูมิ:**
   - สมพร หายไป: warm → frozen (95+ วัน)
   - สมหมาย รอดี: frozen (100+ วัน)
   - สมใจ ประหยัด: cold (60+ วัน)

3. **รายการลูกค้าที่ต้องติดตาม:**
   - จะมีลูกค้า 5-6 รายที่ไม่ได้ติดต่อเกิน 30 วัน

4. **การแจ้งเตือน:**
   - ผู้ใช้ที่เป็น telesales และ supervisor จะได้รับการแจ้งเตือน

## 🚨 การแก้ไขปัญหา

### **ถ้า Cron Jobs ไม่ทำงาน:**

1. **ตรวจสอบ PHP Path:**
   ```bash
   which php
   # หรือ
   whereis php
   ```

2. **ตรวจสอบสิทธิ์ไฟล์:**
   ```bash
   chmod +x cron/*.php
   ```

3. **ทดสอบรันด้วยตนเอง:**
   ```bash
   php cron/run_all_jobs.php
   ```

4. **ดู Error Log:**
   ```bash
   tail -f /var/log/cron.log
   # หรือ
   tail -f logs/cron.log
   ```

## 📞 การติดต่อหากมีปัญหา

ถ้ามีปัญหาในการตั้งค่า:
1. ส่งภาพหน้าจอ cPanel Cron Jobs
2. ส่งผลการรัน `manual_test_cron.php`
3. ส่ง error log ที่เกิดขึ้น

---

**หมายเหตุ:** การตั้งค่า Cron Jobs อาจแตกต่างกันในแต่ละ hosting provider กรุณาปรับตาม environment ของคุณ