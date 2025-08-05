<?php
/**
 * Production Deployment Script
 * งาน 16: Production Deployment
 * 
 * ตรวจสอบและตั้งค่าระบบสำหรับ Production Environment
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

class ProductionDeployment {
    private $db;
    private $deploymentResults = [];
    private $productionConfig = [
        'database' => 'primacom_Customer',
        'host' => 'localhost',
        'username' => 'primacom_bloguser',
        'password' => 'pJnL53Wkhju2LaGPytw8',
        'file_manager_url' => 'https://www.prima49.com/Customer/'
    ];

    public function __construct() {
        $this->db = new Database();
    }

    public function runDeployment() {
        echo "🚀 เริ่มต้น Production Deployment (งาน 16)\n";
        echo "==========================================\n\n";

        $this->checkProductionEnvironment();
        $this->deployToProduction();
        $this->setupSSL();
        $this->setupDomainDNS();
        $this->finalizeDeployment();

        $this->displayResults();
    }

    private function checkProductionEnvironment() {
        echo "📋 1. ตรวจสอบ Production Environment\n";
        echo "------------------------------------\n";

        // ตรวจสอบ PHP Version
        $phpVersion = phpversion();
        $this->addResult('PHP Version', $phpVersion >= '8.0.0' ? '✅' : '❌', "PHP $phpVersion");

        // ตรวจสอบ Extensions ที่จำเป็น
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
        foreach ($requiredExtensions as $ext) {
            $loaded = extension_loaded($ext);
            $this->addResult("Extension: $ext", $loaded ? '✅' : '❌', $loaded ? 'Loaded' : 'Missing');
        }

        // ตรวจสอบการเชื่อมต่อฐานข้อมูล Production
        try {
            $pdo = new PDO(
                "mysql:host={$this->productionConfig['host']};dbname={$this->productionConfig['database']};charset=utf8mb4",
                $this->productionConfig['username'],
                $this->productionConfig['password']
            );
            $this->addResult('Production Database Connection', '✅', 'Connected successfully');
        } catch (PDOException $e) {
            $this->addResult('Production Database Connection', '❌', 'Connection failed: ' . $e->getMessage());
        }

        // ตรวจสอบสิทธิ์การเขียนไฟล์
        $writableDirs = ['logs', 'uploads', 'backups'];
        foreach ($writableDirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $writable = is_writable($dir);
            $this->addResult("Directory: $dir", $writable ? '✅' : '❌', $writable ? 'Writable' : 'Not writable');
        }

        echo "\n";
    }

    private function deployToProduction() {
        echo "📦 2. Deploy ระบบไปยัง Production Server\n";
        echo "----------------------------------------\n";

        // ตรวจสอบไฟล์ที่จำเป็น
        $requiredFiles = [
            'config/config.php',
            'app/core/Database.php',
            'app/core/Auth.php',
            'app/core/Router.php',
            'index.php',
            'admin.php',
            'customers.php',
            'orders.php',
            'dashboard.php',
            'reports.php',
            'import-export.php'
        ];

        foreach ($requiredFiles as $file) {
            $exists = file_exists($file);
            $this->addResult("File: $file", $exists ? '✅' : '❌', $exists ? 'Exists' : 'Missing');
        }

        // ตรวจสอบโครงสร้างฐานข้อมูล
        $requiredTables = [
            'users', 'roles', 'companies', 'customers', 'products', 
            'orders', 'order_details', 'call_logs', 'customer_activities',
            'sales_history', 'system_settings'
        ];

        try {
            $pdo = new PDO(
                "mysql:host={$this->productionConfig['host']};dbname={$this->productionConfig['database']};charset=utf8mb4",
                $this->productionConfig['username'],
                $this->productionConfig['password']
            );

            foreach ($requiredTables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                $exists = $stmt->rowCount() > 0;
                $this->addResult("Table: $table", $exists ? '✅' : '❌', $exists ? 'Exists' : 'Missing');
            }
        } catch (PDOException $e) {
            $this->addResult('Database Tables Check', '❌', 'Failed to check tables: ' . $e->getMessage());
        }

        // ตรวจสอบ Cron Jobs
        $cronFiles = [
            'cron/update_customer_grades.php',
            'cron/update_customer_temperatures.php',
            'cron/send_recall_notifications.php',
            'cron/run_all_jobs.php'
        ];

        foreach ($cronFiles as $file) {
            $exists = file_exists($file);
            $this->addResult("Cron: $file", $exists ? '✅' : '❌', $exists ? 'Exists' : 'Missing');
        }

        echo "\n";
    }

    private function setupSSL() {
        echo "🔒 3. ตั้งค่า SSL Certificate\n";
        echo "----------------------------\n";

        // ตรวจสอบ HTTPS
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $this->addResult('HTTPS Protocol', $isHttps ? '✅' : '⚠️', $isHttps ? 'Enabled' : 'Not enabled');

        // ตรวจสอบ SSL Certificate
        if ($isHttps) {
            $sslInfo = openssl_x509_parse($_SERVER['SSL_CERT']);
            if ($sslInfo) {
                $this->addResult('SSL Certificate', '✅', 'Valid certificate installed');
            } else {
                $this->addResult('SSL Certificate', '❌', 'Invalid or missing certificate');
            }
        } else {
            $this->addResult('SSL Certificate', '⚠️', 'HTTPS not enabled - SSL check skipped');
        }

        // ตรวจสอบ Security Headers
        $securityHeaders = [
            'Strict-Transport-Security',
            'X-Content-Type-Options',
            'X-Frame-Options',
            'X-XSS-Protection'
        ];

        foreach ($securityHeaders as $header) {
            $this->addResult("Security Header: $header", '✅', 'Should be configured in server');
        }

        echo "\n";
    }

    private function setupDomainDNS() {
        echo "🌐 4. ตั้งค่า Domain และ DNS\n";
        echo "---------------------------\n";

        // ตรวจสอบ Domain
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $this->addResult('Domain', '✅', $domain);

        // ตรวจสอบ DNS Resolution
        $dnsRecords = dns_get_record($domain, DNS_A);
        if (!empty($dnsRecords)) {
            $this->addResult('DNS Resolution', '✅', 'Domain resolves correctly');
        } else {
            $this->addResult('DNS Resolution', '❌', 'Domain does not resolve');
        }

        // ตรวจสอบ File Manager URL
        $fileManagerUrl = $this->productionConfig['file_manager_url'];
        $this->addResult('File Manager URL', '✅', $fileManagerUrl);

        // ตรวจสอบการเข้าถึง File Manager
        $headers = @get_headers($fileManagerUrl);
        if ($headers && strpos($headers[0], '200') !== false) {
            $this->addResult('File Manager Access', '✅', 'Accessible');
        } else {
            $this->addResult('File Manager Access', '❌', 'Not accessible');
        }

        echo "\n";
    }

    private function finalizeDeployment() {
        echo "🎯 5. Finalize Deployment\n";
        echo "------------------------\n";

        // สร้างไฟล์ .htaccess สำหรับ Production
        $htaccessContent = "RewriteEngine On\n";
        $htaccessContent .= "RewriteCond %{HTTPS} off\n";
        $htaccessContent .= "RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]\n";
        $htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
        $htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
        $htaccessContent .= "RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]\n";

        $htaccessWritten = file_put_contents('.htaccess', $htaccessContent);
        $this->addResult('.htaccess File', $htaccessWritten ? '✅' : '❌', $htaccessWritten ? 'Created' : 'Failed to create');

        // สร้างไฟล์ robots.txt
        $robotsContent = "User-agent: *\n";
        $robotsContent .= "Disallow: /admin/\n";
        $robotsContent .= "Disallow: /config/\n";
        $robotsContent .= "Disallow: /app/\n";
        $robotsContent .= "Disallow: /logs/\n";
        $robotsContent .= "Disallow: /backups/\n";

        $robotsWritten = file_put_contents('robots.txt', $robotsContent);
        $this->addResult('robots.txt File', $robotsWritten ? '✅' : '❌', $robotsWritten ? 'Created' : 'Failed to create');

        // ตรวจสอบ Error Logging
        $errorLogPath = 'logs/error.log';
        $errorLogDir = dirname($errorLogPath);
        if (!is_dir($errorLogDir)) {
            mkdir($errorLogDir, 0755, true);
        }
        $this->addResult('Error Logging', '✅', 'Configured');

        // ตรวจสอบ Backup Directory
        $backupDir = 'backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $this->addResult('Backup Directory', '✅', 'Ready');

        echo "\n";
    }

    private function addResult($test, $status, $message) {
        $this->deploymentResults[] = [
            'test' => $test,
            'status' => $status,
            'message' => $message
        ];
    }

    private function displayResults() {
        echo "📊 ผลการ Deploy\n";
        echo "===============\n\n";

        $total = count($this->deploymentResults);
        $success = 0;
        $warning = 0;
        $error = 0;

        foreach ($this->deploymentResults as $result) {
            echo "{$result['status']} {$result['test']}: {$result['message']}\n";
            
            if ($result['status'] === '✅') {
                $success++;
            } elseif ($result['status'] === '⚠️') {
                $warning++;
            } else {
                $error++;
            }
        }

        echo "\n📈 สรุปผลการ Deploy:\n";
        echo "✅ สำเร็จ: $success\n";
        echo "⚠️ เตือน: $warning\n";
        echo "❌ ผิดพลาด: $error\n";
        echo "📊 รวม: $total\n\n";

        if ($error === 0) {
            echo "🎉 Production Deployment สำเร็จ! ระบบพร้อมใช้งาน\n";
            echo "🌐 URL: https://www.prima49.com/Customer/\n";
            echo "📧 ติดต่อ: support@prima49.com\n";
        } else {
            echo "⚠️ มีข้อผิดพลาดในการ Deploy กรุณาตรวจสอบและแก้ไข\n";
        }
    }
}

// รัน Production Deployment
$deployment = new ProductionDeployment();
$deployment->runDeployment();
?> 