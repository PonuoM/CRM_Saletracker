<?php
/**
 * Debug JavaScript AJAX - ตรวจสอบ JavaScript และ AJAX request
 * เน้นการ debug ปัญหาที่อาจเกิดจาก client-side
 */

// เปิด error reporting แบบเต็ม
error_reporting(E_ALL);
ini_set('display_errors', 0); // ปิดการแสดง error เพื่อป้องกัน HTML output
ini_set('log_errors', 1);

// จำลองการส่ง JSON response
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$testResponse = [
    'success' => 1,
    'total' => 1,
    'customers_updated' => 0,
    'customers_created' => 1,
    'orders_created' => 1,
    'errors' => []
];

echo json_encode($testResponse, JSON_UNESCAPED_UNICODE);
exit;
?> 