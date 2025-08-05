<?php
/**
 * Customer Distribution Management
 * ระบบการแจกลูกค้าตามคำขอ
 */

$user = $_SESSION['user'] ?? null;
$roleName = $_SESSION['role_name'] ?? '';
$userId = $_SESSION['user_id'] ?? '';

// ตรวจสอบสิทธิ์
if (!in_array($roleName, ['admin', 'supervisor', 'super_admin'])) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบแจกลูกค้า - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../components/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-users me-2"></i>
                        ระบบแจกลูกค้า
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshDistributionStats()">
                                <i class="fas fa-refresh me-1"></i>อัปเดตสถิติ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Distribution Stats -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            ลูกค้าใน Distribution
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="distributionCount">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Telesales ที่พร้อมรับงาน
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="availableTelesalesCount">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            ลูกค้า Hot
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="hotCustomersCount">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-fire fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            ลูกค้า Warm
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="warmCustomersCount">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-sun fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Distribution Controls -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-random me-2"></i>
                                    แจกลูกค้าตามคำขอ
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="distributionForm">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="distributionQuantity" class="form-label">จำนวนลูกค้าที่ต้องการ</label>
                                            <input type="number" class="form-control" id="distributionQuantity" 
                                                   min="1" max="100" value="10" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="distributionPriority" class="form-label">ลำดับความสำคัญ</label>
                                            <select class="form-select" id="distributionPriority" required>
                                                <option value="hot_warm_cold">🔥 Hot → 🌤️ Warm → ❄️ Cold</option>
                                                <option value="hot_only">🔥 Hot เท่านั้น</option>
                                                <option value="warm_only">🌤️ Warm เท่านั้น</option>
                                                <option value="cold_only">❄️ Cold เท่านั้น</option>
                                                <option value="random">สุ่มทั้งหมด</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="distributionTelesales" class="form-label">Telesales ที่เลือก</label>
                                            <select class="form-select" id="distributionTelesales" multiple required>
                                                <option value="">กำลังโหลด...</option>
                                            </select>
                                            <small class="form-text text-muted">กด Ctrl+Click เพื่อเลือกหลายคน</small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">&nbsp;</label>
                                            <div>
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-random me-1"></i>แจกลูกค้า
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Distribution Results -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    ผลการแจกลูกค้า
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="distributionResults">
                                    <div class="text-center py-4">
                                        <i class="fas fa-info-circle text-muted fa-3x mb-3"></i>
                                        <h5>ยังไม่มีการแจกลูกค้า</h5>
                                        <p class="text-muted">กรุณาเลือกจำนวนและ Telesales แล้วกดแจกลูกค้า</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Customers Preview -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-eye me-2"></i>
                                    ตัวอย่างลูกค้าที่พร้อมแจก
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="availableCustomersPreview">
                                    <div class="text-center">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">กำลังโหลด...</span>
                                        </div>
                                        <span class="ms-2">กำลังโหลดข้อมูล...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/customer-distribution.js"></script>
</body>
</html> 