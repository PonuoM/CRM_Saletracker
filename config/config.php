<?php
/**
 * CRM SalesTracker - Configuration File
 * การตั้งค่าระบบสำหรับ Development และ Production
 */

// ตรวจสอบ environment
$is_production = (isset($_SERVER['HTTP_HOST']) && 
                 strpos($_SERVER['HTTP_HOST'], 'prima49.com') !== false);

if ($is_production) {
    // Production Configuration
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'primacom_Customer');
    define('DB_USER', 'primacom_bloguser');
    define('DB_PASS', 'pJnL53Wkhju2LaGPytw8');
    define('ENVIRONMENT', 'production');
    define('BASE_URL', 'https://www.prima49.com/Customer/');
} else {
    // Development Configuration (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_PORT', '4424'); // หรือ 3307
    define('DB_NAME', 'crm_development');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('ENVIRONMENT', 'development');
    define('BASE_URL', 'http://localhost:33308/CRM-CURSOR/');
}

// Common settings
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');
define('APP_NAME', 'CRM SalesTracker');
define('APP_VERSION', '1.0.0');

// Error reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (ENVIRONMENT === 'production') {
    ini_set('session.cookie_secure', 1);
}

// Timezone
date_default_timezone_set('Asia/Bangkok');

// Application paths
define('APP_ROOT', __DIR__ . '/../');
define('APP_CORE', APP_ROOT . 'app/core/');
define('APP_CONTROLLERS', APP_ROOT . 'app/controllers/');
define('APP_MODELS', APP_ROOT . 'app/models/');
define('APP_VIEWS', APP_ROOT . 'app/views/');
define('APP_SERVICES', APP_ROOT . 'app/services/');
define('ASSETS_PATH', APP_ROOT . 'assets/');
define('UPLOADS_PATH', APP_ROOT . 'uploads/');

// Create directories if they don't exist
$directories = [
    APP_CORE,
    APP_CONTROLLERS,
    APP_MODELS,
    APP_VIEWS,
    APP_SERVICES,
    ASSETS_PATH . 'css/',
    ASSETS_PATH . 'js/',
    ASSETS_PATH . 'images/',
    UPLOADS_PATH,
    UPLOADS_PATH . 'customers/',
    UPLOADS_PATH . 'products/',
    UPLOADS_PATH . 'orders/'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
?> 