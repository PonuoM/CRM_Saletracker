<?php
/**
 * Header Component (Minimal)
 * เก็บข้อมูล session สำหรับใช้ในส่วนอื่น
 */

// ตรวจสอบ session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'] ?? null;
$role = $_SESSION['role_name'] ?? 'user';
$username = $_SESSION['username'] ?? 'User';
$fullName = $_SESSION['full_name'] ?? $username;

// ไม่มี HTML output - เก็บเฉพาะข้อมูล session
?>