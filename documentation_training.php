<?php
/**
 * Documentation & Training Script
 * งาน 17: Documentation & Training
 * 
 * สร้างคู่มือผู้ใช้และคู่มือแอดมิน
 */

class DocumentationTraining {
    private $docs = [];

    public function __construct() {
        $this->generateDocumentation();
    }

    private function generateDocumentation() {
        $this->createUserManual();
        $this->createAdminGuide();
        $this->createAPIDocumentation();
        $this->createTroubleshootingGuide();
    }

    private function createUserManual() {
        $content = "# คู่มือผู้ใช้ระบบ CRM SalesTracker\n\n";
        $content .= "## 📋 สารบัญ\n";
        $content .= "1. [การเข้าสู่ระบบ](#การเข้าสู่ระบบ)\n";
        $content .= "2. [แดชบอร์ด](#แดชบอร์ด)\n";
        $content .= "3. [จัดการลูกค้า](#จัดการลูกค้า)\n";
        $content .= "4. [จัดการออเดอร์](#จัดการออเดอร์)\n";
        $content .= "5. [รายงาน](#รายงาน)\n";
        $content .= "6. [การนำเข้า/ส่งออกข้อมูล](#การนำเข้าส่งออกข้อมูล)\n\n";

        $content .= "## 🔐 การเข้าสู่ระบบ\n\n";
        $content .= "### ขั้นตอนการเข้าสู่ระบบ\n";
        $content .= "1. เปิดเว็บเบราว์เซอร์และไปที่: `https://www.prima49.com/Customer/`\n";
        $content .= "2. กรอก Username และ Password\n";
        $content .= "3. เลือก Role (Admin, Supervisor, Telesales)\n";
        $content .= "4. คลิกปุ่ม \"เข้าสู่ระบบ\"\n\n";

        $content .= "### ระดับสิทธิ์ผู้ใช้\n";
        $content .= "- **Admin**: เข้าถึงทุกฟีเจอร์\n";
        $content .= "- **Supervisor**: จัดการลูกค้าและทีม\n";
        $content .= "- **Telesales**: จัดการลูกค้าที่ได้รับมอบหมาย\n\n";

        $content .= "## 📊 แดชบอร์ด\n\n";
        $content .= "### KPI Cards\n";
        $content .= "- **ยอดขายรวม**: ยอดขายทั้งหมดในเดือนปัจจุบัน\n";
        $content .= "- **จำนวนลูกค้า**: จำนวนลูกค้าทั้งหมดในระบบ\n";
        $content .= "- **ออเดอร์ใหม่**: จำนวนออเดอร์ใหม่ในวันนี้\n";
        $content .= "- **ลูกค้า Hot**: จำนวนลูกค้าที่มีสถานะ Hot\n\n";

        $content .= "### กราฟและสถิติ\n";
        $content .= "- **กราฟยอดขาย**: แสดงยอดขายรายเดือน\n";
        $content .= "- **กราฟลูกค้า**: แสดงจำนวนลูกค้าแยกตามเกรด\n";
        $content .= "- **ตารางออเดอร์ล่าสุด**: แสดงออเดอร์ 10 รายการล่าสุด\n\n";

        $content .= "## 👥 จัดการลูกค้า\n\n";
        $content .= "### การดูรายการลูกค้า\n";
        $content .= "1. คลิกเมนู \"ลูกค้า\" ในแถบด้านข้าง\n";
        $content .= "2. ใช้ฟิลเตอร์เพื่อค้นหาลูกค้า\n";
        $content .= "3. คลิกชื่อลูกค้าเพื่อดูรายละเอียด\n\n";

        $content .= "### การเพิ่มลูกค้าใหม่\n";
        $content .= "1. คลิกปุ่ม \"เพิ่มลูกค้าใหม่\"\n";
        $content .= "2. กรอกข้อมูลลูกค้า\n";
        $content .= "3. คลิกปุ่ม \"บันทึก\"\n\n";

        $content .= "### การบันทึกการโทร\n";
        $content .= "1. เปิดหน้าลูกค้า\n";
        $content .= "2. คลิกปุ่ม \"บันทึกการโทร\"\n";
        $content .= "3. เลือกผลการโทร\n";
        $content .= "4. กรอกหมายเหตุ (ถ้ามี)\n";
        $content .= "5. คลิกปุ่ม \"บันทึก\"\n\n";

        $content .= "## 📦 จัดการออเดอร์\n\n";
        $content .= "### การสร้างออเดอร์ใหม่\n";
        $content .= "1. คลิกเมนู \"ออเดอร์\"\n";
        $content .= "2. คลิกปุ่ม \"สร้างออเดอร์ใหม่\"\n";
        $content .= "3. เลือกลูกค้า\n";
        $content .= "4. เพิ่มสินค้าและจำนวน\n";
        $content .= "5. คลิกปุ่ม \"สร้างออเดอร์\"\n\n";

        $content .= "### การแก้ไขออเดอร์\n";
        $content .= "1. คลิกออเดอร์ที่ต้องการแก้ไข\n";
        $content .= "2. คลิกปุ่ม \"แก้ไข\"\n";
        $content .= "3. แก้ไขข้อมูล\n";
        $content .= "4. คลิกปุ่ม \"บันทึก\"\n\n";

        $content .= "## 📈 รายงาน\n\n";
        $content .= "### รายงานยอดขาย\n";
        $content .= "- แสดงยอดขายรายเดือน\n";
        $content .= "- กราฟเปรียบเทียบยอดขาย\n";
        $content .= "- ส่งออกเป็น Excel\n\n";

        $content .= "### รายงานลูกค้า\n";
        $content .= "- จำนวนลูกค้าแยกตามเกรด\n";
        $content .= "- สถิติการโทร\n";
        $content .= "- ลูกค้าที่ต้องติดตาม\n\n";

        $content .= "## 📥 การนำเข้า/ส่งออกข้อมูล\n\n";
        $content .= "### การนำเข้าข้อมูลลูกค้า\n";
        $content .= "1. คลิกเมนู \"นำเข้า/ส่งออก\"\n";
        $content .= "2. เลือกไฟล์ CSV\n";
        $content .= "3. คลิกปุ่ม \"นำเข้า\"\n";
        $content .= "4. ตรวจสอบผลลัพธ์\n\n";

        $content .= "### การส่งออกข้อมูล\n";
        $content .= "1. เลือกประเภทข้อมูล\n";
        $content .= "2. กำหนดช่วงวันที่\n";
        $content .= "3. คลิกปุ่ม \"ส่งออก\"\n";
        $content .= "4. ดาวน์โหลดไฟล์ Excel\n\n";

        $this->docs['user_manual'] = $content;
    }

    private function createAdminGuide() {
        $content = "# คู่มือแอดมินระบบ CRM SalesTracker\n\n";
        $content .= "## 🔧 การจัดการระบบ\n\n";
        $content .= "### การจัดการผู้ใช้\n";
        $content .= "1. เข้าสู่ระบบด้วยสิทธิ์ Admin\n";
        $content .= "2. ไปที่เมนู \"แอดมิน\" > \"ผู้ใช้\"\n";
        $content .= "3. คลิกปุ่ม \"เพิ่มผู้ใช้ใหม่\"\n";
        $content .= "4. กรอกข้อมูลผู้ใช้และกำหนดสิทธิ์\n\n";

        $content .= "### การตั้งค่าระบบ\n";
        $content .= "1. ไปที่เมนู \"แอดมิน\" > \"ตั้งค่า\"\n";
        $content .= "2. แก้ไขการตั้งค่าต่างๆ\n";
        $content .= "3. คลิกปุ่ม \"บันทึก\"\n\n";

        $content .= "### การจัดการสินค้า\n";
        $content .= "1. ไปที่เมนู \"แอดมิน\" > \"สินค้า\"\n";
        $content .= "2. เพิ่ม/แก้ไข/ลบสินค้า\n";
        $content .= "3. กำหนดราคาและรายละเอียด\n\n";

        $content .= "## ⚙️ การตั้งค่า Cron Jobs\n\n";
        $content .= "### การตั้งค่า Cron Jobs บน Server\n";
        $content .= "```bash\n";
        $content .= "# อัปเดตเกรดลูกค้า (ทุกวันเวลา 02:00)\n";
        $content .= "0 2 * * * php /path/to/cron/update_customer_grades.php\n\n";
        $content .= "# อัปเดตอุณหภูมิลูกค้า (ทุกวันเวลา 03:00)\n";
        $content .= "0 3 * * * php /path/to/cron/update_customer_temperatures.php\n\n";
        $content .= "# ส่งการแจ้งเตือน (ทุกวันเวลา 09:00)\n";
        $content .= "0 9 * * * php /path/to/cron/send_recall_notifications.php\n";
        $content .= "```\n\n";

        $content .= "### การตรวจสอบ Cron Jobs\n";
        $content .= "1. ตรวจสอบไฟล์ log ใน `/logs/`\n";
        $content .= "2. ใช้คำสั่ง `crontab -l` เพื่อดู cron jobs\n";
        $content .= "3. ทดสอบด้วยไฟล์ `test_cron_jobs.php`\n\n";

        $content .= "## 🔒 ความปลอดภัย\n\n";
        $content .= "### การตั้งค่า SSL\n";
        $content .= "1. ติดตั้ง SSL Certificate\n";
        $content .= "2. ตั้งค่า HTTPS redirect\n";
        $content .= "3. ตรวจสอบ Security Headers\n\n";

        $content .= "### การสำรองข้อมูล\n";
        $content .= "1. ตั้งค่า Backup อัตโนมัติ\n";
        $content .= "2. ตรวจสอบไฟล์ใน `/backups/`\n";
        $content .= "3. ทดสอบการกู้คืนข้อมูล\n\n";

        $content .= "## 📊 การติดตามประสิทธิภาพ\n\n";
        $content .= "### การตรวจสอบ Logs\n";
        $content .= "- Error logs: `/logs/error.log`\n";
        $content .= "- Access logs: `/logs/access.log`\n";
        $content .= "- Cron logs: `/logs/cron.log`\n\n";

        $content .= "### การตรวจสอบฐานข้อมูล\n";
        $content .= "1. ตรวจสอบขนาดฐานข้อมูล\n";
        $content .= "2. ตรวจสอบประสิทธิภาพ queries\n";
        $content .= "3. อัปเดต indexes ตามความจำเป็น\n\n";

        $this->docs['admin_guide'] = $content;
    }

    private function createAPIDocumentation() {
        $content = "# API Documentation\n\n";
        $content .= "## 🔗 API Endpoints\n\n";
        $content .= "### Authentication\n";
        $content .= "```\n";
        $content .= "POST /api/auth/login\n";
        $content .= "Content-Type: application/json\n\n";
        $content .= "{\n";
        $content .= "  \"username\": \"user@example.com\",\n";
        $content .= "  \"password\": \"password123\"\n";
        $content .= "}\n";
        $content .= "```\n\n";

        $content .= "### Customers API\n";
        $content .= "```\n";
        $content .= "GET /api/customers - ดึงรายการลูกค้า\n";
        $content .= "GET /api/customers/{id} - ดึงข้อมูลลูกค้า\n";
        $content .= "POST /api/customers - สร้างลูกค้าใหม่\n";
        $content .= "PUT /api/customers/{id} - แก้ไขลูกค้า\n";
        $content .= "DELETE /api/customers/{id} - ลบลูกค้า\n";
        $content .= "```\n\n";

        $content .= "### Orders API\n";
        $content .= "```\n";
        $content .= "GET /api/orders - ดึงรายการออเดอร์\n";
        $content .= "GET /api/orders/{id} - ดึงข้อมูลออเดอร์\n";
        $content .= "POST /api/orders - สร้างออเดอร์ใหม่\n";
        $content .= "PUT /api/orders/{id} - แก้ไขออเดอร์\n";
        $content .= "DELETE /api/orders/{id} - ลบออเดอร์\n";
        $content .= "```\n\n";

        $content .= "## 📝 Response Format\n\n";
        $content .= "### Success Response\n";
        $content .= "```json\n";
        $content .= "{\n";
        $content .= "  \"success\": true,\n";
        $content .= "  \"data\": {\n";
        $content .= "    \"id\": 1,\n";
        $content .= "    \"name\": \"John Doe\",\n";
        $content .= "    \"email\": \"john@example.com\"\n";
        $content .= "  },\n";
        $content .= "  \"message\": \"Customer created successfully\"\n";
        $content .= "}\n";
        $content .= "```\n\n";

        $content .= "### Error Response\n";
        $content .= "```json\n";
        $content .= "{\n";
        $content .= "  \"success\": false,\n";
        $content .= "  \"error\": {\n";
        $content .= "    \"code\": 400,\n";
        $content .= "    \"message\": \"Invalid input data\"\n";
        $content .= "  }\n";
        $content .= "}\n";
        $content .= "```\n\n";

        $this->docs['api_docs'] = $content;
    }

    private function createTroubleshootingGuide() {
        $content = "# คู่มือแก้ไขปัญหา\n\n";
        $content .= "## 🚨 ปัญหาที่พบบ่อย\n\n";
        $content .= "### ปัญหาการเข้าสู่ระบบ\n";
        $content .= "**อาการ**: ไม่สามารถเข้าสู่ระบบได้\n";
        $content .= "**วิธีแก้ไข**:\n";
        $content .= "1. ตรวจสอบ Username และ Password\n";
        $content .= "2. ล้าง Cache ของเบราว์เซอร์\n";
        $content .= "3. ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต\n";
        $content .= "4. ติดต่อแอดมิน\n\n";

        $content .= "### ปัญหาการโหลดหน้าเว็บช้า\n";
        $content .= "**อาการ**: หน้าเว็บโหลดช้า\n";
        $content .= "**วิธีแก้ไข**:\n";
        $content .= "1. ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต\n";
        $content .= "2. ล้าง Cache ของเบราว์เซอร์\n";
        $content .= "3. ลองใช้เบราว์เซอร์อื่น\n";
        $content .= "4. ติดต่อแอดมิน\n\n";

        $content .= "### ปัญหาการบันทึกข้อมูล\n";
        $content .= "**อาการ**: ไม่สามารถบันทึกข้อมูลได้\n";
        $content .= "**วิธีแก้ไข**:\n";
        $content .= "1. ตรวจสอบการกรอกข้อมูลให้ครบ\n";
        $content .= "2. ตรวจสอบรูปแบบข้อมูล\n";
        $content .= "3. ลองรีเฟรชหน้าเว็บ\n";
        $content .= "4. ติดต่อแอดมิน\n\n";

        $content .= "## 📞 ติดต่อฝ่ายสนับสนุน\n\n";
        $content .= "### ข้อมูลการติดต่อ\n";
        $content .= "- **อีเมล**: support@prima49.com\n";
        $content .= "- **โทรศัพท์**: 02-XXX-XXXX\n";
        $content .= "- **เวลาทำการ**: จันทร์-ศุกร์ 9:00-18:00 น.\n\n";

        $content .= "### ข้อมูลที่ต้องเตรียม\n";
        $content .= "เมื่อติดต่อฝ่ายสนับสนุน กรุณาเตรียมข้อมูลดังนี้:\n";
        $content .= "1. ชื่อผู้ใช้\n";
        $content .= "2. รายละเอียดปัญหา\n";
        $content .= "3. ขั้นตอนที่ทำให้เกิดปัญหา\n";
        $content .= "4. ข้อความแสดงข้อผิดพลาด (ถ้ามี)\n";
        $content .= "5. เบราว์เซอร์และเวอร์ชัน\n\n";

        $this->docs['troubleshooting'] = $content;
    }

    public function saveDocumentation() {
        $files = [
            'docs/user_manual.md' => $this->docs['user_manual'],
            'docs/admin_guide.md' => $this->docs['admin_guide'],
            'docs/api_documentation.md' => $this->docs['api_docs'],
            'docs/troubleshooting_guide.md' => $this->docs['troubleshooting']
        ];

        // สร้างโฟลเดอร์ docs ถ้ายังไม่มี
        if (!is_dir('docs')) {
            mkdir('docs', 0755, true);
        }

        foreach ($files as $file => $content) {
            file_put_contents($file, $content);
            echo "✅ สร้างไฟล์: $file\n";
        }

        echo "\n🎉 สร้างเอกสารเสร็จสิ้น!\n";
        echo "📁 ไฟล์ทั้งหมดอยู่ในโฟลเดอร์: docs/\n";
    }
}

// รันการสร้างเอกสาร
$docs = new DocumentationTraining();
$docs->saveDocumentation();
?> 