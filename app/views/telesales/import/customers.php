<?php
/**
 * Telesales Import - Import Customers
 * นำเข้าลูกค้าสำหรับ telesales
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-upload me-2"></i>
        นำเข้าลูกค้า
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="telesales.php?action=distribution" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>กลับไประบบแจกลูกค้า
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
        case 'customers_imported':
            $count = $_GET['count'] ?? 0;
            $alertMessage = "นำเข้าลูกค้า $count รายการสำเร็จ";
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

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Import Form -->
<div class="card shadow">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-file-csv me-2"></i>นำเข้าลูกค้าจากไฟล์ CSV
        </h6>
    </div>
    <div class="card-body">
        <form method="POST" action="telesales.php?action=import&subaction=customers" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">
                            <i class="fas fa-file-upload me-1"></i>เลือกไฟล์ CSV <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" 
                               accept=".csv" required>
                        <div class="form-text">รองรับเฉพาะไฟล์ CSV เท่านั้น</div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>นำเข้าข้อมูล
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <hr>
        
        <!-- CSV Format Guide -->
        <div class="row">
            <div class="col-12">
                <h5 class="text-primary mb-3">
                    <i class="fas fa-info-circle me-2"></i>รูปแบบไฟล์ CSV
                </h5>
                <p class="text-muted">ไฟล์ CSV ต้องมีคอลัมน์ตามรูปแบบต่อไปนี้:</p>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>คอลัมน์</th>
                                <th>คำอธิบาย</th>
                                <th>ตัวอย่าง</th>
                                <th>จำเป็น</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>first_name</code></td>
                                <td>ชื่อ</td>
                                <td>สมชาย</td>
                                <td><span class="badge bg-danger">จำเป็น</span></td>
                            </tr>
                            <tr>
                                <td><code>last_name</code></td>
                                <td>นามสกุล</td>
                                <td>ใจดี</td>
                                <td><span class="badge bg-danger">จำเป็น</span></td>
                            </tr>
                            <tr>
                                <td><code>phone</code></td>
                                <td>เบอร์โทรศัพท์</td>
                                <td>0812345678</td>
                                <td><span class="badge bg-warning">แนะนำ</span></td>
                            </tr>
                            <tr>
                                <td><code>email</code></td>
                                <td>อีเมล</td>
                                <td>somchai@example.com</td>
                                <td><span class="badge bg-secondary">ไม่จำเป็น</span></td>
                            </tr>
                            <tr>
                                <td><code>address</code></td>
                                <td>ที่อยู่</td>
                                <td>123 ถนนสุขุมวิท</td>
                                <td><span class="badge bg-secondary">ไม่จำเป็น</span></td>
                            </tr>
                            <tr>
                                <td><code>province</code></td>
                                <td>จังหวัด</td>
                                <td>กรุงเทพมหานคร</td>
                                <td><span class="badge bg-secondary">ไม่จำเป็น</span></td>
                            </tr>
                            <tr>
                                <td><code>district</code></td>
                                <td>อำเภอ/เขต</td>
                                <td>วัฒนา</td>
                                <td><span class="badge bg-secondary">ไม่จำเป็น</span></td>
                            </tr>
                            <tr>
                                <td><code>subdistrict</code></td>
                                <td>ตำบล/แขวง</td>
                                <td>คลองตัน</td>
                                <td><span class="badge bg-secondary">ไม่จำเป็น</span></td>
                            </tr>
                            <tr>
                                <td><code>postal_code</code></td>
                                <td>รหัสไปรษณีย์</td>
                                <td>10110</td>
                                <td><span class="badge bg-secondary">ไม่จำเป็น</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info">
                    <h6><i class="fas fa-lightbulb me-2"></i>คำแนะนำ:</h6>
                    <ul class="mb-0">
                        <li>ไฟล์ CSV ต้องมี header row (แถวแรกเป็นชื่อคอลัมน์)</li>
                        <li>ใช้เครื่องหมายจุลภาค (,) เป็นตัวคั่น</li>
                        <li>ข้อมูลที่มีเครื่องหมายจุลภาคต้องใส่ในเครื่องหมายคำพูด ("")</li>
                        <li>ระบบจะตรวจสอบข้อมูลซ้ำตามเบอร์โทรศัพท์</li>
                        <li>ลูกค้าที่นำเข้าจะอยู่ในสถานะ "รอแจก" (distribution)</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>ข้อควรระวัง:</h6>
                    <ul class="mb-0">
                        <li>ตรวจสอบข้อมูลให้ถูกต้องก่อนนำเข้า</li>
                        <li>ข้อมูลที่นำเข้าแล้วไม่สามารถแก้ไขได้จากหน้านี้</li>
                        <li>หากมีข้อมูลซ้ำ ระบบจะข้ามรายการนั้น</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Sample CSV Download -->
        <div class="row">
            <div class="col-12">
                <h5 class="text-primary mb-3">
                    <i class="fas fa-download me-2"></i>ดาวน์โหลดไฟล์ตัวอย่าง
                </h5>
                <p class="text-muted">ดาวน์โหลดไฟล์ CSV ตัวอย่างเพื่อใช้เป็นแม่แบบ:</p>
                <a href="data:text/csv;charset=utf-8,first_name,last_name,phone,email,address,province,district,subdistrict,postal_code%0Aสมชาย,ใจดี,0812345678,somchai@example.com,123 ถนนสุขุมวิท,กรุงเทพมหานคร,วัฒนา,คลองตัน,10110%0Aสมหญิง,รักดี,0823456789,somying@example.com,456 ถนนเพชรบุรี,กรุงเทพมหานคร,ราชเทวี,ลุมพินี,10330" 
                   class="btn btn-outline-primary" download="sample_customers.csv">
                    <i class="fas fa-download me-2"></i>ดาวน์โหลดไฟล์ตัวอย่าง
                </a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // File validation
    $('#csv_file').on('change', function() {
        const file = this.files[0];
        if (file) {
            const fileName = file.name.toLowerCase();
            if (!fileName.endsWith('.csv')) {
                alert('กรุณาเลือกไฟล์ CSV เท่านั้น');
                this.value = '';
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) { // 5MB
                alert('ขนาดไฟล์ต้องไม่เกิน 5MB');
                this.value = '';
                return;
            }
        }
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        const fileInput = $('#csv_file')[0];
        if (!fileInput.files.length) {
            e.preventDefault();
            alert('กรุณาเลือกไฟล์ CSV');
            return false;
        }
        
        const file = fileInput.files[0];
        if (!file.name.toLowerCase().endsWith('.csv')) {
            e.preventDefault();
            alert('กรุณาเลือกไฟล์ CSV เท่านั้น');
            return false;
        }
        
        // Show loading
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>กำลังนำเข้า...');
    });
});
</script>
