<?php
/**
 * ทดสอบ Dynamic Sidebar
 */

session_start();

// Set up test session (simulate login as admin)
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'admin';
$_SESSION['username'] = 'admin';

// Include required files
require_once __DIR__ . '/config/config.php';

$pageTitle = 'ทดสอบ Dynamic Sidebar - CRM SalesTracker';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-line me-2"></i>
                CRM SalesTracker
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                </a>
            </div>
        </div>
    </nav>

    <!-- Mobile Sidebar Toggle -->
    <button class="mobile-sidebar-toggle d-md-none" id="mobileSidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container-fluid p-0">
        <!-- Include Sidebar Component -->
        <?php include __DIR__ . '/app/views/components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                            <h1 class="h2">ทดสอบ Dynamic Sidebar</h1>
                        </div>

                        <!-- Test Content -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-cogs me-2"></i>
                                            ฟีเจอร์ Dynamic Sidebar
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <strong>โหมด Mini:</strong> หุบเหลือไอคอนเท่านั้น
                                            </li>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <strong>Hover Expand:</strong> ขยายอัตโนมัติเมื่อ hover
                                            </li>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <strong>ปุ่มปักหมุด:</strong> คงสถานะขยาย/หุบ
                                            </li>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <strong>localStorage:</strong> จำสถานะการตั้งค่า
                                            </li>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <strong>Responsive:</strong> ปรับตัวบนมือถือ
                                            </li>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <strong>Smooth Animation:</strong> เคลื่อนไหวนุ่มนวล
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-mobile-alt me-2"></i>
                                            วิธีการใช้งาน
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-desktop me-2"></i>บนเดสก์ท็อป:</h6>
                                            <ul class="mb-0">
                                                <li>Sidebar จะอยู่ในโหมด Mini (แสดงเฉพาะไอคอน)</li>
                                                <li>เลื่อนเมาส์ไปที่ Sidebar เพื่อขยายชั่วคราว</li>
                                                <li>คลิกปุ่มปักหมุด (📌) เพื่อคงสถานะขยาย</li>
                                                <li>คลิกปุ่มปักหมุดอีกครั้งเพื่อกลับไปโหมด Mini</li>
                                            </ul>
                                        </div>
                                        
                                        <div class="alert alert-warning">
                                            <h6><i class="fas fa-mobile-alt me-2"></i>บนมือถือ:</h6>
                                            <ul class="mb-0">
                                                <li>คลิกปุ่ม ☰ เพื่อเปิด/ปิด Sidebar</li>
                                                <li>Sidebar จะแสดงแบบเต็มจอ</li>
                                                <li>คลิกนอก Sidebar เพื่อปิด</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sample Content -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chart-bar me-2"></i>
                                            เนื้อหาตัวอย่าง
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p>เนื้อหาส่วนนี้จะปรับความกว้างอัตโนมัติตามสถานะของ Sidebar:</p>
                                        <ul>
                                            <li><strong>โหมด Mini:</strong> เนื้อหาจะใช้พื้นที่เกือบเต็มหน้าจอ</li>
                                            <li><strong>โหมดขยาย:</strong> เนื้อหาจะปรับให้เหลือพื้นที่สำหรับ Sidebar</li>
                                            <li><strong>การเปลี่ยนแปลง:</strong> มี smooth transition ทำให้ดูนุ่มนวล</li>
                                        </ul>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="card bg-primary text-white">
                                                    <div class="card-body text-center">
                                                        <h3>250px</h3>
                                                        <p class="mb-0">ความกว้าง Sidebar แบบขยาย</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card bg-success text-white">
                                                    <div class="card-body text-center">
                                                        <h3>70px</h3>
                                                        <p class="mb-0">ความกว้าง Sidebar แบบ Mini</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card bg-info text-white">
                                                    <div class="card-body text-center">
                                                        <h3>0.3s</h3>
                                                        <p class="mb-0">ระยะเวลา Transition</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sidebar.js"></script>
    
    <script>
    // Additional test script
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Dynamic Sidebar Test Page Loaded');
        
        // Log sidebar state changes
        const sidebar = document.getElementById('mainSidebar');
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const classes = sidebar.className;
                    console.log('Sidebar classes changed:', classes);
                }
            });
        });
        
        observer.observe(sidebar, { attributes: true });
    });
    </script>
</body>
</html>
