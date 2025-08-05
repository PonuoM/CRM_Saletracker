<?php
/**
 * Admin Workflow Management
 * จัดการระบบ Workflow สำหรับการเรียกข้อมูลลูกค้าคืน
 */

$user = $_SESSION['user'] ?? null;
$workflowService = new WorkflowService();
$stats = $workflowService->getWorkflowStats();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workflow Management - CRM SalesTracker</title>
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
                        <i class="fas fa-project-diagram me-2"></i>
                        Workflow Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="runManualRecall()">
                                <i class="fas fa-sync me-1"></i>รัน Recall เอง
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="extendCustomerTime()">
                                <i class="fas fa-clock me-1"></i>ต่อเวลา
                            </button>
                            <button class="btn btn-sm btn-outline-info" onclick="refreshStats()">
                                <i class="fas fa-refresh me-1"></i>อัปเดตสถิติ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Workflow Stats -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            ลูกค้าที่ต้อง Recall
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['pending_recall']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            ลูกค้าใหม่เกิน 30 วัน
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['new_customer_timeout']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-clock fa-2x text-gray-300"></i>
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
                                            ลูกค้าเก่าเกิน 90 วัน
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['existing_customer_timeout']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-times fa-2x text-gray-300"></i>
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
                                            ลูกค้า Active วันนี้
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['active_today']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Workflow Rules -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-cogs me-2"></i>
                                    กฎการทำงาน Workflow
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary">📅 ลูกค้าใหม่ (30 วัน)</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-arrow-right text-success me-2"></i>หากไม่มีการอัปเดตภายใน 30 วัน → ดึงกลับไป Distribution Basket</li>
                                            <li><i class="fas fa-arrow-right text-success me-2"></i>หากมีการสร้างนัดหมาย → ต่อเวลา 30 วัน</li>
                                            <li><i class="fas fa-arrow-right text-success me-2"></i>หากมีการขาย → ต่อเวลา 90 วัน</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-warning">⏰ ลูกค้าเก่า (90 วัน)</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-arrow-right text-warning me-2"></i>หากไม่มีคำสั่งซื้อภายใน 90 วัน → ดึงกลับไป Waiting Basket</li>
                                            <li><i class="fas fa-arrow-right text-warning me-2"></i>หากมีการสร้างนัดหมาย → ต่อเวลา 30 วัน</li>
                                            <li><i class="fas fa-arrow-right text-warning me-2"></i>หากมีการขาย → ต่อเวลา 90 วัน</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Lists -->
                <div class="row">
                    <!-- New Customers Timeout -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-clock me-2"></i>
                                    ลูกค้าใหม่เกิน 30 วัน
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="newCustomerTimeoutList">
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

                    <!-- Existing Customers Timeout -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-times me-2"></i>
                                    ลูกค้าเก่าเกิน 90 วัน
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="existingCustomerTimeoutList">
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

                <!-- Recent Activities -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-history me-2"></i>
                                    กิจกรรมล่าสุด
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="recentActivities">
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

    <!-- Extend Time Modal -->
    <div class="modal fade" id="extendTimeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ต่อเวลาลูกค้า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="extendTimeForm">
                        <div class="mb-3">
                            <label for="customerId" class="form-label">ลูกค้า</label>
                            <select class="form-select" id="customerId" name="customer_id" required>
                                <option value="">เลือกลูกค้า...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="extensionDays" class="form-label">จำนวนวันที่ต้องการต่อ</label>
                            <select class="form-select" id="extensionDays" name="extension_days" required>
                                <option value="30">30 วัน</option>
                                <option value="60">60 วัน</option>
                                <option value="90">90 วัน</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="extensionReason" class="form-label">เหตุผล</label>
                            <textarea class="form-control" id="extensionReason" name="reason" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="submitExtendTime()">ต่อเวลา</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/workflow.js"></script>
</body>
</html> 