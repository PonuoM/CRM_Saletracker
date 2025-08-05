<?php
/**
 * Test Appointment System
 * ทดสอบระบบนัดหมาย
 */

session_start();

// ตรวจสอบการ login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/services/AppointmentService.php';
require_once __DIR__ . '/app/services/CustomerService.php';

$appointmentService = new AppointmentService();
$customerService = new CustomerService();

// ดึงข้อมูลลูกค้าทดสอบ
$customers = $customerService->getAllCustomers(5);
$testCustomer = $customers['data'][0] ?? null;

// ทดสอบการสร้างตาราง
$testCreateTable = false;
if (isset($_POST['create_table'])) {
    try {
        $sql = file_get_contents(__DIR__ . '/database/appointments_table.sql');
        $db = new Database();
        $db->query($sql);
        $testCreateTable = true;
    } catch (Exception $e) {
        $testCreateTable = false;
        $tableError = $e->getMessage();
    }
}

// ทดสอบการสร้างนัดหมาย
$testCreateAppointment = null;
if (isset($_POST['test_create']) && $testCustomer) {
    $testData = [
        'customer_id' => $testCustomer['customer_id'],
        'user_id' => $_SESSION['user_id'],
        'appointment_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
        'appointment_type' => 'meeting',
        'title' => 'ทดสอบนัดหมาย',
        'description' => 'นัดหมายทดสอบระบบ',
        'notes' => 'ทดสอบการสร้างนัดหมาย'
    ];
    
    $testCreateAppointment = $appointmentService->createAppointment($testData);
}

// ทดสอบการดึงรายการนัดหมาย
$testGetAppointments = null;
if (isset($_POST['test_get']) && $testCustomer) {
    $testGetAppointments = $appointmentService->getAppointmentsByCustomer($testCustomer['customer_id']);
}

// ทดสอบการดึงนัดหมายที่ใกล้ถึงกำหนด
$testUpcoming = null;
if (isset($_POST['test_upcoming'])) {
    $testUpcoming = $appointmentService->getUpcomingAppointments($_SESSION['user_id'], 7);
}

// ทดสอบสถิติ
$testStats = null;
if (isset($_POST['test_stats'])) {
    $testStats = $appointmentService->getAppointmentStats($_SESSION['user_id'], 'month');
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบระบบนัดหมาย - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                แดชบอร์ด
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="customers.php">
                                <i class="fas fa-users me-2"></i>
                                ลูกค้า
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="fas fa-shopping-cart me-2"></i>
                                คำสั่งซื้อ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="test_appointment_system.php">
                                <i class="fas fa-calendar me-2"></i>
                                ทดสอบนัดหมาย
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">ทดสอบระบบนัดหมาย</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>กลับ
                        </a>
                    </div>
                </div>

                <!-- Test Results -->
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">ผลการทดสอบระบบนัดหมาย</h5>
                            </div>
                            <div class="card-body">
                                
                                <!-- 1. ทดสอบการสร้างตาราง -->
                                <div class="mb-4">
                                    <h6>1. ทดสอบการสร้างตาราง appointments</h6>
                                    <form method="post" class="d-inline">
                                        <button type="submit" name="create_table" class="btn btn-primary btn-sm">
                                            สร้างตาราง appointments
                                        </button>
                                    </form>
                                    
                                    <?php if (isset($_POST['create_table'])): ?>
                                        <?php if ($testCreateTable): ?>
                                            <div class="alert alert-success mt-2">
                                                ✅ สร้างตาราง appointments สำเร็จ
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-danger mt-2">
                                                ❌ เกิดข้อผิดพลาด: <?php echo $tableError ?? 'ไม่ทราบสาเหตุ'; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- 2. ทดสอบการสร้างนัดหมาย -->
                                <div class="mb-4">
                                    <h6>2. ทดสอบการสร้างนัดหมาย</h6>
                                    <?php if ($testCustomer): ?>
                                        <p class="text-muted">ลูกค้าทดสอบ: <?php echo $testCustomer['first_name'] . ' ' . $testCustomer['last_name']; ?></p>
                                        <form method="post" class="d-inline">
                                            <button type="submit" name="test_create" class="btn btn-success btn-sm">
                                                สร้างนัดหมายทดสอบ
                                            </button>
                                        </form>
                                        
                                        <?php if ($testCreateAppointment): ?>
                                            <?php if ($testCreateAppointment['success']): ?>
                                                <div class="alert alert-success mt-2">
                                                    ✅ สร้างนัดหมายสำเร็จ<br>
                                                    Appointment ID: <?php echo $testCreateAppointment['appointment_id']; ?><br>
                                                    ข้อความ: <?php echo $testCreateAppointment['message']; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-danger mt-2">
                                                    ❌ เกิดข้อผิดพลาด: <?php echo $testCreateAppointment['message']; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            ⚠️ ไม่พบข้อมูลลูกค้าสำหรับทดสอบ
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- 3. ทดสอบการดึงรายการนัดหมาย -->
                                <div class="mb-4">
                                    <h6>3. ทดสอบการดึงรายการนัดหมาย</h6>
                                    <?php if ($testCustomer): ?>
                                        <form method="post" class="d-inline">
                                            <button type="submit" name="test_get" class="btn btn-info btn-sm">
                                                ดึงรายการนัดหมาย
                                            </button>
                                        </form>
                                        
                                        <?php if ($testGetAppointments): ?>
                                            <?php if ($testGetAppointments['success']): ?>
                                                <div class="alert alert-success mt-2">
                                                    ✅ ดึงรายการนัดหมายสำเร็จ<br>
                                                    จำนวน: <?php echo count($testGetAppointments['data']); ?> รายการ
                                                </div>
                                                
                                                <?php if (!empty($testGetAppointments['data'])): ?>
                                                    <div class="table-responsive mt-2">
                                                        <table class="table table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>วันที่</th>
                                                                    <th>ประเภท</th>
                                                                    <th>สถานะ</th>
                                                                    <th>หัวข้อ</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($testGetAppointments['data'] as $appointment): ?>
                                                                    <tr>
                                                                        <td><?php echo date('d/m/Y H:i', strtotime($appointment['appointment_date'])); ?></td>
                                                                        <td><?php echo $appointment['appointment_type']; ?></td>
                                                                        <td>
                                                                            <span class="badge bg-<?php echo $appointment['appointment_status'] === 'scheduled' ? 'warning' : 'success'; ?>">
                                                                                <?php echo $appointment['appointment_status']; ?>
                                                                            </span>
                                                                        </td>
                                                                        <td><?php echo $appointment['title'] ?? '-'; ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="alert alert-danger mt-2">
                                                    ❌ เกิดข้อผิดพลาด: <?php echo $testGetAppointments['message']; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- 4. ทดสอบการดึงนัดหมายที่ใกล้ถึงกำหนด -->
                                <div class="mb-4">
                                    <h6>4. ทดสอบการดึงนัดหมายที่ใกล้ถึงกำหนด</h6>
                                    <form method="post" class="d-inline">
                                        <button type="submit" name="test_upcoming" class="btn btn-warning btn-sm">
                                            ดึงนัดหมายที่ใกล้ถึงกำหนด
                                        </button>
                                    </form>
                                    
                                    <?php if ($testUpcoming): ?>
                                        <?php if ($testUpcoming['success']): ?>
                                            <div class="alert alert-success mt-2">
                                                ✅ ดึงนัดหมายที่ใกล้ถึงกำหนดสำเร็จ<br>
                                                จำนวน: <?php echo count($testUpcoming['data']); ?> รายการ
                                            </div>
                                            
                                            <?php if (!empty($testUpcoming['data'])): ?>
                                                <div class="table-responsive mt-2">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>วันที่</th>
                                                                <th>ลูกค้า</th>
                                                                <th>ประเภท</th>
                                                                <th>สถานะ</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($testUpcoming['data'] as $appointment): ?>
                                                                <tr>
                                                                    <td><?php echo date('d/m/Y H:i', strtotime($appointment['appointment_date'])); ?></td>
                                                                    <td><?php echo $appointment['first_name'] . ' ' . $appointment['last_name']; ?></td>
                                                                    <td><?php echo $appointment['appointment_type']; ?></td>
                                                                    <td>
                                                                        <span class="badge bg-<?php echo $appointment['appointment_status'] === 'scheduled' ? 'warning' : 'info'; ?>">
                                                                            <?php echo $appointment['appointment_status']; ?>
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="alert alert-danger mt-2">
                                                ❌ เกิดข้อผิดพลาด: <?php echo $testUpcoming['message']; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- 5. ทดสอบสถิติ -->
                                <div class="mb-4">
                                    <h6>5. ทดสอบสถิตินัดหมาย</h6>
                                    <form method="post" class="d-inline">
                                        <button type="submit" name="test_stats" class="btn btn-secondary btn-sm">
                                            ดึงสถิติ
                                        </button>
                                    </form>
                                    
                                    <?php if ($testStats): ?>
                                        <?php if ($testStats['success']): ?>
                                            <div class="alert alert-success mt-2">
                                                ✅ ดึงสถิตินัดหมายสำเร็จ
                                            </div>
                                            
                                            <?php if (!empty($testStats['data'])): ?>
                                                <div class="table-responsive mt-2">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>สถานะ</th>
                                                                <th>ประเภท</th>
                                                                <th>จำนวน</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($testStats['data'] as $stat): ?>
                                                                <tr>
                                                                    <td>
                                                                        <span class="badge bg-<?php echo $stat['appointment_status'] === 'scheduled' ? 'warning' : ($stat['appointment_status'] === 'completed' ? 'success' : 'info'); ?>">
                                                                            <?php echo $stat['appointment_status']; ?>
                                                                        </span>
                                                                    </td>
                                                                    <td><?php echo $stat['appointment_type']; ?></td>
                                                                    <td><?php echo $stat['count']; ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="alert alert-danger mt-2">
                                                ❌ เกิดข้อผิดพลาด: <?php echo $testStats['message']; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Testing -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">ทดสอบ API Endpoints</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>API Endpoints ที่พร้อมใช้งาน:</h6>
                                        <ul class="list-unstyled">
                                            <li><code>GET api/appointments.php?action=get_by_customer&customer_id=1</code></li>
                                            <li><code>GET api/appointments.php?action=get_by_user&user_id=1</code></li>
                                            <li><code>GET api/appointments.php?action=get_upcoming&days=7</code></li>
                                            <li><code>GET api/appointments.php?action=get_stats&period=month</code></li>
                                            <li><code>POST api/appointments.php?action=create</code></li>
                                            <li><code>POST api/appointments.php?action=update_status</code></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>การใช้งาน:</h6>
                                        <p class="text-muted">
                                            1. สร้างตาราง appointments ก่อน<br>
                                            2. ทดสอบการสร้างนัดหมาย<br>
                                            3. ทดสอบการดึงข้อมูล<br>
                                            4. ตรวจสอบในหน้ารายละเอียดลูกค้า
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 