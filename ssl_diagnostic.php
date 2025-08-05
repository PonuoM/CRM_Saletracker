<?php
/**
 * SSL Certificate and File Manager Diagnostic Script
 * สำหรับตรวจสอบปัญหา SSL Certificate และ File Manager Access
 */

class SSLDiagnostic {
    private $results = [];
    private $domain = 'prima49.com';
    private $fileManagerUrl = 'https://www.prima49.com/Customer/';

    public function __construct() {
        echo "🔍 SSL Certificate และ File Manager Diagnostic\n";
        echo "=============================================\n\n";
    }

    public function runDiagnostic() {
        $this->checkSSLCertificate();
        $this->checkFileManagerAccess();
        $this->checkDomainDNS();
        $this->checkHTTPSConfiguration();
        $this->displayResults();
        $this->provideSolutions();
    }

    private function checkSSLCertificate() {
        echo "📋 1. ตรวจสอบ SSL Certificate\n";
        echo "--------------------------------\n";

        // Check if we can connect via HTTPS
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'capture_peer_cert' => true
            ]
        ]);

        $result = @file_get_contents($this->fileManagerUrl, false, $context);
        
        if ($result === false) {
            $this->addResult('SSL Connection', '❌', 'ไม่สามารถเชื่อมต่อผ่าน HTTPS ได้');
        } else {
            $this->addResult('SSL Connection', '✅', 'เชื่อมต่อผ่าน HTTPS ได้สำเร็จ');
        }

        // Check certificate details
        $certInfo = stream_context_get_params($context);
        if (isset($certInfo['options']['ssl']['peer_certificate'])) {
            $cert = openssl_x509_parse($certInfo['options']['ssl']['peer_certificate']);
            if ($cert) {
                $this->addResult('Certificate Valid', '✅', 'SSL Certificate ถูกต้อง');
                $this->addResult('Certificate Subject', 'ℹ️', $cert['subject']['CN'] ?? 'Unknown');
                $this->addResult('Certificate Issuer', 'ℹ️', $cert['issuer']['CN'] ?? 'Unknown');
                $this->addResult('Valid From', 'ℹ️', date('Y-m-d H:i:s', $cert['validFrom_time_t']));
                $this->addResult('Valid Until', 'ℹ️', date('Y-m-d H:i:s', $cert['validTo_time_t']));
            } else {
                $this->addResult('Certificate Valid', '❌', 'SSL Certificate ไม่ถูกต้อง');
            }
        } else {
            $this->addResult('Certificate Valid', '❌', 'ไม่พบ SSL Certificate');
        }
    }

    private function checkFileManagerAccess() {
        echo "\n📋 2. ตรวจสอบ File Manager Access\n";
        echo "--------------------------------\n";

        // Try to access the file manager URL
        $headers = @get_headers($this->fileManagerUrl, 1);
        
        if ($headers === false) {
            $this->addResult('File Manager Access', '❌', 'ไม่สามารถเข้าถึง File Manager ได้');
        } else {
            $httpCode = $this->extractHttpCode($headers);
            if ($httpCode >= 200 && $httpCode < 300) {
                $this->addResult('File Manager Access', '✅', 'เข้าถึง File Manager ได้สำเร็จ (HTTP ' . $httpCode . ')');
            } else {
                $this->addResult('File Manager Access', '❌', 'File Manager ส่งคืน HTTP Code: ' . $httpCode);
            }
        }

        // Check if directory exists locally
        $localPath = __DIR__ . '/Customer/';
        if (is_dir($localPath)) {
            $this->addResult('Local Directory', '✅', 'โฟลเดอร์ Customer มีอยู่ในเครื่อง');
        } else {
            $this->addResult('Local Directory', '❌', 'โฟลเดอร์ Customer ไม่มีอยู่ในเครื่อง');
        }
    }

    private function checkDomainDNS() {
        echo "\n📋 3. ตรวจสอบ Domain และ DNS\n";
        echo "-----------------------------\n";

        // Check domain resolution
        $ip = gethostbyname($this->domain);
        if ($ip !== $this->domain) {
            $this->addResult('DNS Resolution', '✅', 'Domain แปลงเป็น IP: ' . $ip);
        } else {
            $this->addResult('DNS Resolution', '❌', 'ไม่สามารถแปลง Domain เป็น IP ได้');
        }

        // Check www subdomain
        $wwwIp = gethostbyname('www.' . $this->domain);
        if ($wwwIp !== 'www.' . $this->domain) {
            $this->addResult('WWW Subdomain', '✅', 'www subdomain แปลงเป็น IP: ' . $wwwIp);
        } else {
            $this->addResult('WWW Subdomain', '❌', 'ไม่สามารถแปลง www subdomain เป็น IP ได้');
        }
    }

    private function checkHTTPSConfiguration() {
        echo "\n📋 4. ตรวจสอบ HTTPS Configuration\n";
        echo "---------------------------------\n";

        // Check if HTTPS is enabled
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $this->addResult('HTTPS Protocol', '✅', 'HTTPS เปิดใช้งานอยู่');
        } else {
            $this->addResult('HTTPS Protocol', '⚠️', 'HTTPS ไม่ได้เปิดใช้งาน (อาจเป็นเพราะรันบน localhost)');
        }

        // Check .htaccess file
        $htaccessPath = __DIR__ . '/.htaccess';
        if (file_exists($htaccessPath)) {
            $this->addResult('.htaccess File', '✅', 'ไฟล์ .htaccess มีอยู่');
            
            // Check if HTTPS redirect is configured
            $htaccessContent = file_get_contents($htaccessPath);
            if (strpos($htaccessContent, 'RewriteRule.*https') !== false) {
                $this->addResult('HTTPS Redirect', '✅', 'HTTPS Redirect ถูกตั้งค่าใน .htaccess');
            } else {
                $this->addResult('HTTPS Redirect', '⚠️', 'ไม่พบ HTTPS Redirect ใน .htaccess');
            }
        } else {
            $this->addResult('.htaccess File', '❌', 'ไฟล์ .htaccess ไม่มีอยู่');
        }
    }

    private function extractHttpCode($headers) {
        if (is_array($headers)) {
            foreach ($headers as $header) {
                if (is_string($header) && preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    return (int)$matches[1];
                }
            }
        }
        return 0;
    }

    private function addResult($test, $status, $message) {
        $this->results[] = [
            'test' => $test,
            'status' => $status,
            'message' => $message
        ];
    }

    private function displayResults() {
        echo "\n📊 ผลการตรวจสอบ\n";
        echo "==============\n";
        
        $success = 0;
        $warning = 0;
        $error = 0;

        foreach ($this->results as $result) {
            echo $result['status'] . ' ' . $result['test'] . ': ' . $result['message'] . "\n";
            
            if ($result['status'] === '✅') $success++;
            elseif ($result['status'] === '⚠️') $warning++;
            elseif ($result['status'] === '❌') $error++;
        }

        echo "\n📈 สรุปผลการตรวจสอบ:\n";
        echo "✅ สำเร็จ: " . $success . "\n";
        echo "⚠️ เตือน: " . $warning . "\n";
        echo "❌ ผิดพลาด: " . $error . "\n";
        echo "📊 รวม: " . count($this->results) . "\n";
    }

    private function provideSolutions() {
        echo "\n🔧 แนวทางแก้ไขปัญหา\n";
        echo "==================\n";

        $hasSSLIssue = false;
        $hasFileManagerIssue = false;

        foreach ($this->results as $result) {
            if (strpos($result['test'], 'SSL') !== false && $result['status'] === '❌') {
                $hasSSLIssue = true;
            }
            if (strpos($result['test'], 'File Manager') !== false && $result['status'] === '❌') {
                $hasFileManagerIssue = true;
            }
        }

        if ($hasSSLIssue) {
            echo "\n🔒 แก้ไขปัญหา SSL Certificate:\n";
            echo "1. ติดต่อ hosting provider เพื่อขอ SSL Certificate\n";
            echo "2. หรือใช้ Let's Encrypt (ฟรี) สำหรับ SSL Certificate\n";
            echo "3. ตรวจสอบว่า domain ชี้ไปยัง server ที่ถูกต้อง\n";
            echo "4. รอ DNS propagation (อาจใช้เวลา 24-48 ชั่วโมง)\n";
        }

        if ($hasFileManagerIssue) {
            echo "\n📁 แก้ไขปัญหา File Manager Access:\n";
            echo "1. ตรวจสอบว่าไฟล์ถูกอัปโหลดไปยัง server แล้ว\n";
            echo "2. ตรวจสอบ file permissions (755 สำหรับโฟลเดอร์, 644 สำหรับไฟล์)\n";
            echo "3. ตรวจสอบว่า .htaccess ไม่ได้บล็อกการเข้าถึง\n";
            echo "4. ตรวจสอบ error logs ของ web server\n";
        }

        echo "\n📞 ติดต่อ Support:\n";
        echo "หากยังมีปัญหา กรุณาติดต่อ hosting provider หรือ development team\n";
    }
}

// Run diagnostic
$diagnostic = new SSLDiagnostic();
$diagnostic->runDiagnostic();
?> 