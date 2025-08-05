<?php
/**
 * Production Fix Script
 * สำหรับแก้ไขปัญหาการ Deploy ใน Production
 */

class ProductionFix {
    private $results = [];
    private $basePath;

    public function __construct() {
        $this->basePath = __DIR__;
        echo "🔧 Production Fix Script\n";
        echo "========================\n\n";
    }

    public function runFixes() {
        $this->fixFilePermissions();
        $this->fixDirectoryStructure();
        $this->fixConfigurationFiles();
        $this->createMissingFiles();
        $this->displayResults();
    }

    private function fixFilePermissions() {
        echo "📋 1. แก้ไข File Permissions\n";
        echo "---------------------------\n";

        $directories = [
            'logs',
            'uploads', 
            'backups',
            'assets',
            'docs'
        ];

        $files = [
            'index.php',
            'admin.php',
            'customers.php',
            'orders.php',
            'dashboard.php',
            'reports.php',
            'import-export.php'
        ];

        // Fix directory permissions
        foreach ($directories as $dir) {
            $path = $this->basePath . '/' . $dir;
            if (!is_dir($path)) {
                if (mkdir($path, 0755, true)) {
                    $this->addResult("Create Directory: $dir", '✅', 'สร้างโฟลเดอร์สำเร็จ');
                } else {
                    $this->addResult("Create Directory: $dir", '❌', 'ไม่สามารถสร้างโฟลเดอร์ได้');
                }
            } else {
                if (chmod($path, 0755)) {
                    $this->addResult("Directory Permissions: $dir", '✅', 'ตั้งค่า permissions สำเร็จ');
                } else {
                    $this->addResult("Directory Permissions: $dir", '❌', 'ไม่สามารถตั้งค่า permissions ได้');
                }
            }
        }

        // Fix file permissions
        foreach ($files as $file) {
            $path = $this->basePath . '/' . $file;
            if (file_exists($path)) {
                if (chmod($path, 0644)) {
                    $this->addResult("File Permissions: $file", '✅', 'ตั้งค่า permissions สำเร็จ');
                } else {
                    $this->addResult("File Permissions: $file", '❌', 'ไม่สามารถตั้งค่า permissions ได้');
                }
            } else {
                $this->addResult("File Exists: $file", '❌', 'ไฟล์ไม่มีอยู่');
            }
        }
    }

    private function fixDirectoryStructure() {
        echo "\n📋 2. ตรวจสอบ Directory Structure\n";
        echo "--------------------------------\n";

        $requiredDirs = [
            'app/core',
            'app/controllers',
            'app/models',
            'app/views',
            'app/services',
            'config',
            'cron'
        ];

        foreach ($requiredDirs as $dir) {
            $path = $this->basePath . '/' . $dir;
            if (!is_dir($path)) {
                if (mkdir($path, 0755, true)) {
                    $this->addResult("Create App Directory: $dir", '✅', 'สร้างโฟลเดอร์สำเร็จ');
                } else {
                    $this->addResult("Create App Directory: $dir", '❌', 'ไม่สามารถสร้างโฟลเดอร์ได้');
                }
            } else {
                $this->addResult("App Directory: $dir", '✅', 'โฟลเดอร์มีอยู่แล้ว');
            }
        }
    }

    private function fixConfigurationFiles() {
        echo "\n📋 3. ตรวจสอบ Configuration Files\n";
        echo "--------------------------------\n";

        // Check .htaccess
        $htaccessPath = $this->basePath . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = $this->generateHtaccess();
            if (file_put_contents($htaccessPath, $htaccessContent)) {
                $this->addResult(".htaccess File", '✅', 'สร้างไฟล์ .htaccess สำเร็จ');
            } else {
                $this->addResult(".htaccess File", '❌', 'ไม่สามารถสร้างไฟล์ .htaccess ได้');
            }
        } else {
            $this->addResult(".htaccess File", '✅', 'ไฟล์ .htaccess มีอยู่แล้ว');
        }

        // Check robots.txt
        $robotsPath = $this->basePath . '/robots.txt';
        if (!file_exists($robotsPath)) {
            $robotsContent = $this->generateRobotsTxt();
            if (file_put_contents($robotsPath, $robotsContent)) {
                $this->addResult("robots.txt File", '✅', 'สร้างไฟล์ robots.txt สำเร็จ');
            } else {
                $this->addResult("robots.txt File", '❌', 'ไม่สามารถสร้างไฟล์ robots.txt ได้');
            }
        } else {
            $this->addResult("robots.txt File", '✅', 'ไฟล์ robots.txt มีอยู่แล้ว');
        }
    }

    private function createMissingFiles() {
        echo "\n📋 4. สร้างไฟล์ที่ขาดหายไป\n";
        echo "---------------------------\n";

        // Create error pages
        $errorPages = [
            '404.php' => $this->generateErrorPage(404, 'Page Not Found'),
            '403.php' => $this->generateErrorPage(403, 'Access Forbidden'),
            '500.php' => $this->generateErrorPage(500, 'Internal Server Error')
        ];

        foreach ($errorPages as $file => $content) {
            $path = $this->basePath . '/' . $file;
            if (!file_exists($path)) {
                if (file_put_contents($path, $content)) {
                    $this->addResult("Error Page: $file", '✅', 'สร้างไฟล์สำเร็จ');
                } else {
                    $this->addResult("Error Page: $file", '❌', 'ไม่สามารถสร้างไฟล์ได้');
                }
            } else {
                $this->addResult("Error Page: $file", '✅', 'ไฟล์มีอยู่แล้ว');
            }
        }

        // Create .gitignore if not exists
        $gitignorePath = $this->basePath . '/.gitignore';
        if (!file_exists($gitignorePath)) {
            $gitignoreContent = $this->generateGitignore();
            if (file_put_contents($gitignorePath, $gitignoreContent)) {
                $this->addResult(".gitignore File", '✅', 'สร้างไฟล์ .gitignore สำเร็จ');
            } else {
                $this->addResult(".gitignore File", '❌', 'ไม่สามารถสร้างไฟล์ .gitignore ได้');
            }
        } else {
            $this->addResult(".gitignore File", '✅', 'ไฟล์ .gitignore มีอยู่แล้ว');
        }
    }

    private function generateHtaccess() {
        return 'RewriteEngine On

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Prevent access to sensitive files
<Files "*.log">
    Order allow,deny
    Deny from all
</Files>

<Files "*.sql">
    Order allow,deny
    Deny from all
</Files>

<Files "*.env">
    Order allow,deny
    Deny from all
</Files>

<Files "config.php">
    Order allow,deny
    Deny from all
</Files>

# Prevent directory listing
Options -Indexes

# Handle errors
ErrorDocument 404 /404.php
ErrorDocument 403 /403.php
ErrorDocument 500 /500.php

# URL rewriting for clean URLs
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Force HTTPS in production
<IfModule mod_rewrite.c>
    RewriteCond %{HTTPS} off
    RewriteCond %{HTTP_HOST} ^prima49\.com$ [NC]
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>

# Cache static assets
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>

# Compress files
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>';
    }

    private function generateRobotsTxt() {
        return 'User-agent: *
Disallow: /config/
Disallow: /logs/
Disallow: /backups/
Disallow: /app/
Disallow: /cron/
Disallow: /*.log
Disallow: /*.sql
Disallow: /*.env

Allow: /assets/
Allow: /docs/

Sitemap: https://www.prima49.com/Customer/sitemap.xml';
    }

    private function generateErrorPage($code, $message) {
        return '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error ' . $code . ' - ' . $message . '</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error-code { font-size: 72px; color: #e74c3c; margin-bottom: 20px; }
        .error-message { font-size: 24px; color: #2c3e50; margin-bottom: 30px; }
        .back-link { color: #3498db; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="error-code">' . $code . '</div>
    <div class="error-message">' . $message . '</div>
    <a href="/" class="back-link">← กลับไปหน้าหลัก</a>
</body>
</html>';
    }

    private function generateGitignore() {
        return '# Logs
*.log
logs/

# Database
*.sql
*.sqlite

# Environment files
.env
.env.local
.env.production

# Cache
cache/
tmp/

# Uploads (if needed)
uploads/

# Backups
backups/

# IDE files
.vscode/
.idea/
*.swp
*.swo

# OS files
.DS_Store
Thumbs.db

# Composer
vendor/
composer.lock

# Node modules
node_modules/
npm-debug.log

# Build files
dist/
build/';
    }

    private function addResult($test, $status, $message) {
        $this->results[] = [
            'test' => $test,
            'status' => $status,
            'message' => $message
        ];
    }

    private function displayResults() {
        echo "\n📊 ผลการแก้ไข\n";
        echo "=============\n";
        
        $success = 0;
        $error = 0;

        foreach ($this->results as $result) {
            echo $result['status'] . ' ' . $result['test'] . ': ' . $result['message'] . "\n";
            
            if ($result['status'] === '✅') $success++;
            elseif ($result['status'] === '❌') $error++;
        }

        echo "\n📈 สรุปผลการแก้ไข:\n";
        echo "✅ สำเร็จ: " . $success . "\n";
        echo "❌ ผิดพลาด: " . $error . "\n";
        echo "📊 รวม: " . count($this->results) . "\n";

        if ($error === 0) {
            echo "\n🎉 การแก้ไขเสร็จสิ้น! ระบบพร้อมใช้งาน\n";
        } else {
            echo "\n⚠️ มีข้อผิดพลาดบางอย่าง กรุณาตรวจสอบและแก้ไข\n";
        }
    }
}

// Run fixes
$fix = new ProductionFix();
$fix->runFixes();
?> 