<?php
/**
 * Telesales User Management - List Users
 * แสดงรายการผู้ใช้สำหรับ telesales
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-users me-2"></i>
        จัดการผู้ใช้
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="telesales.php?action=users&subaction=create" class="btn btn-primary">
            <i class="fas fa-user-plus me-2"></i>เพิ่มผู้ใช้ใหม่
        </a>
    </div>
</div>

<!-- Alert Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php
    $message = $_GET['message'];
    $alertClass = 'alert-success';
    $alertMessage = '';

    switch ($message) {
        case 'user_created':
            $alertMessage = 'สร้างผู้ใช้ใหม่สำเร็จ';
            break;
        case 'user_updated':
            $alertMessage = 'อัปเดตข้อมูลผู้ใช้สำเร็จ';
            break;
        case 'user_deleted':
            $alertMessage = 'ลบผู้ใช้สำเร็จ';
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

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($_GET['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Users Table -->
<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list me-2"></i>รายการผู้ใช้
        </h6>
    </div>
    <div class="card-body">
        <?php if (!empty($users)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="usersTable">
                    <thead class="table-light">
                        <tr>
                            <th>ชื่อผู้ใช้</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>อีเมล</th>
                            <th>โทรศัพท์</th>
                            <th>บทบาท</th>
                            <th>ผู้ดูแล</th>
                            <th>สถานะ</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user['phone']); ?>
                                </td>
                                <td>
                                    <?php if ($user['role_id'] == 3): ?>
                                        <span class="badge bg-warning">Supervisor</span>
                                    <?php elseif ($user['role_id'] == 4): ?>
                                        <span class="badge bg-info">Telesales</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($user['role_name']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['supervisor_id']): ?>
                                        <?php
                                        $supervisor = array_filter($users, function($u) use ($user) {
                                            return $u['user_id'] == $user['supervisor_id'];
                                        });
                                        $supervisor = reset($supervisor);
                                        echo $supervisor ? htmlspecialchars($supervisor['full_name']) : '-';
                                        ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">ใช้งาน</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">ไม่ใช้งาน</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="telesales.php?action=users&subaction=edit&id=<?php echo $user['user_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="แก้ไข">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="telesales.php?action=users&subaction=delete&id=<?php echo $user['user_id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           title="ลบ"
                                           onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบผู้ใช้นี้?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">ยังไม่มีผู้ใช้</h5>
                <p class="text-muted">เริ่มต้นด้วยการเพิ่มผู้ใช้ใหม่</p>
                <a href="telesales.php?action=users&subaction=create" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i>เพิ่มผู้ใช้ใหม่
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
        },
        "pageLength": 25,
        "order": [[1, "asc"]],
        "columnDefs": [
            { "orderable": false, "targets": 7 }
        ]
    });
});
</script>
