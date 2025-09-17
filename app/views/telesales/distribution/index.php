<?php
/**
 * Telesales Customer Distribution - List Distribution
 * แสดงรายการการแจกลูกค้าสำหรับ telesales
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-share-alt me-2"></i>
        ระบบแจกลูกค้า
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="telesales.php?action=distribution&subaction=assign" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i>แจกลูกค้า
            </a>
            <a href="telesales.php?action=distribution&subaction=bulk_assign" class="btn btn-info">
                <i class="fas fa-users me-2"></i>แจกแบบกลุ่ม
            </a>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php
    $message = $_GET['message'];
    $alertClass = 'alert-success';
    $alertMessage = '';

    switch ($message) {
        case 'customers_assigned':
            $count = $_GET['count'] ?? 0;
            $alertMessage = "แจกลูกค้า $count รายการสำเร็จ";
            break;
        case 'customers_bulk_assigned':
            $count = $_GET['count'] ?? 0;
            $alertMessage = "แจกลูกค้าแบบกลุ่ม $count รายการสำเร็จ";
            break;
        default:
            $alertMessage = $message;
            break;
    }
    ?>
    <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $alertMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Distribution Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            ลูกค้ารอแจก
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php
                            $availableCount = 0;
                            foreach ($distributions as $dist) {
                                if ($dist['status'] === 'distribution') {
                                    $availableCount++;
                                }
                            }
                            echo $availableCount;
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            ลูกค้าแจกแล้ว
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php
                            $assignedCount = 0;
                            foreach ($distributions as $dist) {
                                if ($dist['status'] === 'completed') {
                                    $assignedCount++;
                                }
                            }
                            echo $assignedCount;
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            ผู้ขายทั้งหมด
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo count($telesales); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            ลูกค้าทั้งหมด
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo count($distributions); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-friends fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Distribution Table -->
<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list me-2"></i>รายการการแจกลูกค้า
        </h6>
    </div>
    <div class="card-body">
        <?php if (!empty($distributions)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="distributionTable">
                    <thead class="table-light">
                        <tr>
                            <th>ลูกค้า</th>
                            <th>เบอร์โทรศัพท์</th>
                            <th>จังหวัด</th>
                            <th>อำเภอ</th>
                            <th>ผู้ขาย</th>
                            <th>วันที่แจก</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($distributions as $dist): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($dist['first_name'] . ' ' . $dist['last_name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($dist['phone']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($dist['province'] ?? '-'); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($dist['district'] ?? '-'); ?>
                                </td>
                                <td>
                                    <?php if ($dist['telesales_name']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($dist['telesales_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">ยังไม่ได้แจก</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($dist['transferred_at']): ?>
                                        <?php echo date('d/m/Y H:i', strtotime($dist['transferred_at'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($dist['status'] === 'completed'): ?>
                                        <span class="badge bg-success">แจกแล้ว</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">รอแจก</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-share-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">ยังไม่มีข้อมูลการแจกลูกค้า</h5>
                <p class="text-muted">เริ่มต้นด้วยการนำเข้าลูกค้าหรือแจกลูกค้า</p>
                <div class="btn-group">
                    <a href="telesales.php?action=import&subaction=customers" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>นำเข้าลูกค้า
                    </a>
                    <a href="telesales.php?action=distribution&subaction=assign" class="btn btn-info">
                        <i class="fas fa-user-plus me-2"></i>แจกลูกค้า
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#distributionTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
        },
        "pageLength": 25,
        "order": [[5, "desc"]]
    });
});
</script>
