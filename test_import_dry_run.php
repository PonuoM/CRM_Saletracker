<?php
require_once 'config/config.php';
require_once 'app/core/Database.php';
require_once 'app/services/ImportExportService.php';

// เริ่มต้น session
session_start();

// ตรวจสอบการ login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';
$testResults = null;
$uploadedFile = null;

// จัดการการอัพโหลดไฟล์
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = $file['tmp_name'];
        $fileName = $file['name'];
        
        // ตรวจสอบประเภทไฟล์
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($fileExtension !== 'csv') {
            $error = 'กรุณาอัพโหลดไฟล์ CSV เท่านั้น';
        } else {
            try {
                // สร้าง instance ของ ImportExportService
                $importService = new ImportExportService();
                
                // ทดสอบการอ่านและประมวลผลไฟล์ CSV
                $testResults = $importService->testImportFromCSV($uploadedFile);
                
                $message = 'ทดสอบการนำเข้าข้อมูลสำเร็จ! ดูผลลัพธ์ด้านล่าง';
                
            } catch (Exception $e) {
                $error = 'เกิดข้อผิดพลาดในการทดสอบ: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'เกิดข้อผิดพลาดในการอัพโหลดไฟล์';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบการนำเข้าข้อมูล CSV - Dry Run</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .upload-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .results-section {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .calculation-details {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .file-input {
            margin: 10px 0;
        }
        .submit-btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background: #0056b3;
        }
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background: #545b62;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <a href="import-export.php" class="back-btn">← กลับไปหน้าจัดการ Import/Export</a>
        
        <h1>🧪 ทดสอบการนำเข้าข้อมูล CSV - Dry Run</h1>
        <p>อัพโหลดไฟล์ CSV เพื่อดูว่าข้อมูลจะถูกประมวลผลอย่างไร โดยไม่บันทึกลงฐานข้อมูล</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="upload-section">
            <h2>📁 อัพโหลดไฟล์ CSV</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="file-input">
                    <label for="csv_file">เลือกไฟล์ CSV:</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                </div>
                <button type="submit" class="submit-btn">🚀 เริ่มทดสอบการนำเข้าข้อมูล</button>
            </form>
        </div>
        
        <?php if ($testResults): ?>
            <div class="results-section">
                <h2>📊 ผลการทดสอบการนำเข้าข้อมูล</h2>
                
                <div class="calculation-details">
                    <h3>📈 สถิติการประมวลผล</h3>
                    <ul>
                        <li><strong>จำนวนแถวทั้งหมด:</strong> <?php echo $testResults['total_rows']; ?></li>
                        <li><strong>จำนวนแถวที่ประมวลผลสำเร็จ:</strong> <?php echo $testResults['processed_rows']; ?></li>
                        <li><strong>จำนวนแถวที่มีปัญหา:</strong> <?php echo $testResults['error_rows']; ?></li>
                        <li><strong>จำนวนลูกค้าใหม่:</strong> <?php echo $testResults['new_customers']; ?></li>
                        <li><strong>จำนวนลูกค้าที่มีอยู่แล้ว:</strong> <?php echo $testResults['existing_customers']; ?></li>
                        <li><strong>จำนวนคำสั่งซื้อที่จะสร้าง:</strong> <?php echo $testResults['orders_to_create']; ?></li>
                    </ul>
                </div>
                
                <?php if (!empty($testResults['warnings'])): ?>
                    <div class="warning">
                        <h3>⚠️ คำเตือน</h3>
                        <ul>
                            <?php foreach ($testResults['warnings'] as $warning): ?>
                                <li><?php echo htmlspecialchars($warning); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($testResults['errors'])): ?>
                    <div class="error">
                        <h3>❌ ข้อผิดพลาด</h3>
                        <ul>
                            <?php foreach ($testResults['errors'] as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <h3>📋 ตัวอย่างข้อมูลที่จะถูกประมวลผล</h3>
                <?php if (!empty($testResults['sample_data'])): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>แถว</th>
                                <th>ชื่อลูกค้า</th>
                                <th>เบอร์โทร</th>
                                <th>ชื่อสินค้า</th>
                                <th>จำนวน</th>
                                <th>ราคาต่อชิ้น</th>
                                <th>ยอดรวมจาก CSV</th>
                                <th>ยอดรวมที่คำนวณ</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testResults['sample_data'] as $index => $row): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['product_name'] ?? ''); ?></td>
                                    <td><?php echo $row['quantity'] ?? 0; ?></td>
                                    <td><?php echo number_format($row['unit_price'] ?? 0, 2); ?></td>
                                    <td><?php echo number_format($row['total_amount_from_csv'] ?? 0, 2); ?></td>
                                    <td><?php echo number_format($row['calculated_total'] ?? 0, 2); ?></td>
                                    <td>
                                        <?php if (isset($row['status'])): ?>
                                            <?php if ($row['status'] === 'success'): ?>
                                                <span style="color: green;">✅ สำเร็จ</span>
                                            <?php elseif ($row['status'] === 'warning'): ?>
                                                <span style="color: orange;">⚠️ คำเตือน</span>
                                            <?php else: ?>
                                                <span style="color: red;">❌ ผิดพลาด</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>ไม่มีข้อมูลตัวอย่าง</p>
                <?php endif; ?>
                
                <h3>🔍 รายละเอียดการคำนวณ</h3>
                <div class="calculation-details">
                    <p><strong>วิธีการคำนวณ:</strong></p>
                    <ol>
                        <li>หากมีคอลัมน์ "ยอดรวม" ใน CSV จะใช้ค่านั้นเป็น <code>total_amount</code></li>
                        <li>หากไม่มีคอลัมน์ "ยอดรวม" จะคำนวณจาก <code>จำนวน × ราคาต่อชิ้น</code></li>
                        <li><code>net_amount</code> จะเท่ากับ <code>total_amount</code> เสมอ</li>
                        <li><code>order_items.total_price</code> จะเท่ากับ <code>total_amount</code> ของคำสั่งซื้อ</li>
                    </ol>
                </div>
                
                <div class="warning">
                    <h3>💡 คำแนะนำ</h3>
                    <ul>
                        <li>ตรวจสอบข้อมูลในตารางด้านบนว่าถูกต้องหรือไม่</li>
                        <li>หากพบปัญหา ให้แก้ไขไฟล์ CSV แล้วทดสอบใหม่</li>
                        <li>เมื่อมั่นใจว่าข้อมูลถูกต้องแล้ว ให้ไปที่หน้า Import/Export เพื่อนำเข้าข้อมูลจริง</li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
