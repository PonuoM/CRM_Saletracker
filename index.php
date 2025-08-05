<?php
/**
 * CRM SalesTracker - Main Entry Point
 * สำหรับบริษัท พรีม่าแพสชั่น 49 จำกัด
 * 
 * @version 1.0.0
 * @author CRM Development Team
 */

// Start session
session_start();

// Load configuration
require_once 'config/config.php';

// Load core classes
require_once 'app/core/Database.php';
require_once 'app/core/Router.php';
require_once 'app/core/Auth.php';

// Initialize application
try {
    // Create database connection
    $db = new Database();
    
    // Initialize router
    $router = new Router();
    
    // Initialize authentication
    $auth = new Auth($db);
    
    // Route the request
    $router->handleRequest();
    
} catch (Exception $e) {
    // Handle errors
    if (ENVIRONMENT === 'development') {
        echo "<h1>System Error</h1>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
        echo "<p>File: " . $e->getFile() . "</p>";
        echo "<p>Line: " . $e->getLine() . "</p>";
    } else {
        echo "<h1>System Maintenance</h1>";
        echo "<p>ระบบกำลังอยู่ในระหว่างการบำรุงรักษา กรุณาลองใหม่อีกครั้งในภายหลัง</p>";
    }
}
?> 