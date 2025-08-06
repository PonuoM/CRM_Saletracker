<?php
/**
 * Test Template Encoding
 * ทดสอบการเข้ารหัสของไฟล์ Template
 */

// Set headers for CSV download
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="test_template.csv"');

$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Test data with Thai characters
$headers = ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล', 'ที่อยู่', 'ตำบล', 'อำเภอ', 'จังหวัด', 'รหัสไปรษณีย์', 'หมายเหตุ'];
$sampleData = ['สมชาย', 'ใจดี', '0812345678', 'somchai@example.com', '123 ถ.สุขุมวิท', 'คลองเตย', 'คลองเตย', 'กรุงเทพฯ', '10110', 'ลูกค้าจาก Facebook'];

// Write headers and sample data
fputcsv($output, $headers);
fputcsv($output, $sampleData);

fclose($output);
?> 