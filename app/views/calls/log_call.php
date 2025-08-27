<?php
// ตรวจสอบว่ามีข้อมูลที่จำเป็นหรือไม่
if (!isset($customer)) {
    header('Location: customers.php');
    exit;
}
?>

<!-- Main Content -->
<div class="page-transition call-log-page">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">บันทึกการโทร</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="customers.php?action=show&id=<?php echo $customer['customer_id']; ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> กลับ
                </a>
            </div>
        </div>
    </div>

    <!-- ข้อมูลลูกค้า -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user"></i> ข้อมูลลูกค้า</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-4"><strong>ชื่อ:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>เบอร์โทร:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($customer['phone'] ?? ''); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>อีเมล:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($customer['email'] ?? ''); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>จังหวัด:</strong></div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($customer['province'] ?? ''); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4"><strong>เกรด:</strong></div>
                        <div class="col-sm-8">
                            <span class="badge bg-<?php echo getGradeColor($customer['customer_grade']); ?>">
                                <?php echo htmlspecialchars($customer['customer_grade']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> ประวัติการโทรล่าสุด</h5>
                </div>
                <div class="card-body">
                    <div id="callHistory">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> กำลังโหลดประวัติ...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ฟอร์มบันทึกการโทร -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-phone"></i> บันทึกการโทร</h5>
        </div>
        <div class="card-body">
            <form id="callLogForm">
                <input type="hidden" name="customer_id" value="<?php echo $customer['customer_id']; ?>">
                
                <div class="row g-3">
                    <!-- สถานะการโทร -->
                    <div class="col-md-6">
                        <label for="callStatus" class="form-label">สถานะการโทร <span class="text-danger">*</span></label>
                        <select class="form-select" id="callStatus" name="call_status" required>
                            <option value="">เลือกสถานะการโทร</option>
                            <option value="answered">รับสาย</option>
                            <option value="no_answer">ไม่รับสาย</option>
                            <option value="busy">สายไม่ว่าง</option>
                            <option value="invalid">เบอร์ผิด</option>
                            <option value="hang_up">ตัดสายทิ้ง</option>
                        </select>
                    </div>
                    
                    <!-- ผลการโทร -->
                    <div class="col-md-6">
                        <label for="callResult" class="form-label">ผลการโทร</label>
                        <select class="form-select" id="callResult" name="call_result">
                            <option value="">เลือกผลการโทร</option>
                            <option value="สนใจ">สนใจ</option>
                            <option value="ไม่สนใจ">ไม่สนใจ</option>
                            <option value="ลังเล">ลังเล</option>
                            <option value="เบอร์ผิด">เบอร์ผิด</option>
                            <option value="ได้คุย">ได้คุย</option>
                            <option value="ตัดสายทิ้ง">ตัดสายทิ้ง</option>
                        </select>
                    </div>
                    
                    <!-- ระยะเวลาการโทร -->
                    <div class="col-md-6">
                        <label for="durationMinutes" class="form-label">ระยะเวลา (นาที)</label>
                        <input type="number" class="form-control" id="durationMinutes" name="duration_minutes" min="0" max="480" placeholder="0">
                    </div>
                    
                    <!-- วันที่คาดว่าจะติดต่อครั้งถัดไป -->
                    <div class="col-md-6">
                        <label for="nextFollowup" class="form-label">วันที่คาดว่าจะติดต่อครั้งถัดไป</label>
                        <input type="datetime-local" class="form-control" id="nextFollowup" name="next_followup_at">
                    </div>
                    
                    <!-- หมายเหตุ -->
                    <div class="col-12">
                        <label for="notes" class="form-label">หมายเหตุ</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="บันทึกรายละเอียดการสนทนา..."></textarea>
                    </div>
                    
                    <!-- เพิ่ม Tag -->
                    <div class="col-12">
                        <label for="callTags" class="form-label">เพิ่ม Tag</label>
                        <div class="d-flex gap-1 mb-2">
                            <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1" onclick="showAddTagModalFromCall()">
                                <i class="fas fa-plus"></i> เพิ่ม Tag
                            </button>
                        </div>
                        <!-- Preview area สำหรับ Tags ที่เพิ่มแล้ว -->
                        <div id="callTagsPreview" class="border rounded p-2 bg-light min-height-40" style="min-height: 40px;">
                            <small class="text-muted">Tags ที่เพิ่มจะแสดงที่นี่</small>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกการโทร
                    </button>
                    <a href="customers.php?action=show&id=<?php echo $customer['customer_id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> ยกเลิก
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// โหลดประวัติการโทรเมื่อหน้าโหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    loadCallHistory();
    setupFormValidation();
    setupCallStatusAutoFill();
});

// Auto-fill ผลการโทรเมื่อเปลี่ยนสถานะการโทร
function setupCallStatusAutoFill() {
    const callStatus = document.getElementById('callStatus');
    const callResult = document.getElementById('callResult');
    
    if (callStatus && callResult) {
        callStatus.addEventListener('change', function() {
            // ถ้าเลือกสถานะที่ไม่ใช่ "รับสาย" ให้ auto-fill ผลการโทร
            if (this.value && this.value !== 'answered') {
                const statusValueMap = {
                    'no_answer': 'ไม่รับสาย',
                    'busy': 'สายไม่ว่าง',
                    'invalid': 'เบอร์ผิด',
                    'hang_up': 'ตัดสายทิ้ง'
                };
                const autoFillValue = statusValueMap[this.value];
                if (autoFillValue) {
                    // ตรวจสอบว่ามี option นี้อยู่ใน callResult หรือไม่
                    const option = Array.from(callResult.options).find(opt => opt.value === autoFillValue);
                    if (option) {
                        callResult.value = autoFillValue;
                    }
                }
            }
        });
    }
}

// โหลดประวัติการโทร
function loadCallHistory() {
    const customerId = <?php echo $customer['customer_id']; ?>;
    
    fetch(`api/calls.php?action=get_history&customer_id=${customerId}&limit=5`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCallHistory(data.data);
            } else {
                document.getElementById('callHistory').innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-exclamation-triangle"></i> ไม่สามารถโหลดประวัติได้
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('callHistory').innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-exclamation-triangle"></i> เกิดข้อผิดพลาดในการโหลด
                </div>
            `;
        });
}

// แสดงประวัติการโทร
function renderCallHistory(calls) {
    const container = document.getElementById('callHistory');
    
    if (calls.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-inbox"></i> ยังไม่มีประวัติการโทร
            </div>
        `;
        return;
    }
    
    container.innerHTML = calls.map(call => `
        <div class="border-bottom pb-2 mb-2">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="badge bg-${getCallResultColor(call.call_result)}">
                        ${getCallResultText(call.call_result)}
                    </span>
                    <span class="badge bg-${getCallStatusColor(call.call_status)} ms-1">
                        ${getCallStatusText(call.call_status)}
                    </span>
                </div>
                <small class="text-muted">${formatDate(call.created_at)}</small>
            </div>
            ${call.notes ? `<div class="mt-1 small text-muted">${escapeHtml(call.notes)}</div>` : ''}
            ${call.duration_minutes > 0 ? `<div class="mt-1 small text-muted">ใช้เวลา ${call.duration_minutes} นาที</div>` : ''}
        </div>
    `).join('');
}

// ตั้งค่าการตรวจสอบฟอร์ม
function setupFormValidation() {
    const form = document.getElementById('callLogForm');
    const callStatus = document.getElementById('callStatus');
    const callResult = document.getElementById('callResult');
    
    // อัปเดตตัวเลือกผลการโทรตามสถานะการโทร
    callStatus.addEventListener('change', function() {
        updateCallResultOptions(this.value);
    });
    
    // ส่งฟอร์ม
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            submitCallLog();
        }
    });
}

// อัปเดตตัวเลือกผลการโทร
function updateCallResultOptions(callStatus) {
    const callResult = document.getElementById('callResult');
    const currentValue = callResult.value;
    
    // รีเซ็ตตัวเลือก
    callResult.innerHTML = '<option value="">เลือกผลการโทร</option>';
    
    if (callStatus === 'answered') {
        // ถ้ารับสาย สามารถมีผลการโทรได้ทุกประเภท
        callResult.innerHTML += `
            <option value="interested">สนใจ</option>
            <option value="not_interested">ไม่สนใจ</option>
            <option value="callback">ขอโทรกลับ</option>
            <option value="order">สั่งซื้อ</option>
            <option value="complaint">ร้องเรียน</option>
        `;
    } else if (callStatus === 'no_answer') {
        // ถ้าไม่รับสาย
        callResult.innerHTML += `
            <option value="callback">ขอโทรกลับ</option>
        `;
    } else if (callStatus === 'busy') {
        // ถ้าสายไม่ว่าง
        callResult.innerHTML += `
            <option value="callback">ขอโทรกลับ</option>
        `;
    } else if (callStatus === 'invalid') {
        // ถ้าเบอร์ไม่ถูกต้อง
        callResult.innerHTML += `
            <option value="not_interested">ไม่สนใจ</option>
        `;
    }
    
    // คืนค่าที่เลือกไว้เดิมถ้ายังมีอยู่
    if (currentValue && callResult.querySelector(`option[value="${currentValue}"]`)) {
        callResult.value = currentValue;
    }
}

// ตรวจสอบฟอร์ม
function validateForm() {
    const callStatus = document.getElementById('callStatus').value;
    const callResult = document.getElementById('callResult').value;
    
    if (!callStatus) {
        showAlert('error', 'กรุณาเลือกสถานะการโทร');
        return false;
    }
    
    if (!callResult) {
        showAlert('error', 'กรุณาเลือกผลการโทร');
        return false;
    }
    
    return true;
}

// ส่งข้อมูลการโทร
function submitCallLog() {
    const form = document.getElementById('callLogForm');
    const formData = new FormData(form);
    
    // แสดง loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
    submitBtn.disabled = true;
    
    fetch('api/calls.php?action=log_call', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'บันทึกการโทรสำเร็จ');
            
            // รีเซ็ตฟอร์ม
            form.reset();
            
            // โหลดประวัติใหม่
            loadCallHistory();
            
            // กลับไปหน้าลูกค้าหลังจาก 2 วินาที
            setTimeout(() => {
                window.location.href = `customers.php?action=show&id=${formData.get('customer_id')}`;
            }, 2000);
            
        } else {
            showAlert('error', 'เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
    })
    .finally(() => {
        // คืนค่าปุ่ม
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// ฟังก์ชันช่วยเหลือ
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH') + ' ' + date.toLocaleTimeString('th-TH', {hour: '2-digit', minute: '2-digit'});
}

function getCallResultColor(result) {
    const colors = {
        'interested': 'success',
        'not_interested': 'secondary',
        'callback': 'warning',
        'order': 'primary',
        'complaint': 'danger'
    };
    return colors[result] || 'secondary';
}

function getCallResultText(result) {
    const texts = {
        'interested': 'สนใจ',
        'not_interested': 'ไม่สนใจ',
        'callback': 'ขอโทรกลับ',
        'order': 'สั่งซื้อ',
        'complaint': 'ร้องเรียน'
    };
    return texts[result] || result;
}

function getCallStatusColor(status) {
    const colors = {
        'answered': 'success',
        'no_answer': 'warning',
        'busy': 'info',
        'invalid': 'danger',
        'hang_up': 'secondary'
    };
    return colors[status] || 'secondary';
}

function getCallStatusText(status) {
    const texts = {
        'answered': 'รับสาย',
        'no_answer': 'ไม่รับสาย',
        'busy': 'สายไม่ว่าง',
        'invalid': 'เบอร์ไม่ถูกต้อง',
        'hang_up': 'ตัดสายทิ้ง'
    };
    return texts[status] || status;
}

function getGradeColor(grade) {
    const colors = {
        'A+': 'success',
        'A': 'primary',
        'B': 'info',
        'C': 'warning',
        'D': 'secondary'
    };
    return colors[grade] || 'secondary';
}

function showAlert(type, message) {
    // สร้าง alert แบบ Bootstrap
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // เพิ่ม alert ที่ด้านบนของหน้า
    const container = document.querySelector('.call-log-page');
    container.insertBefore(alertDiv, container.firstChild);
    
    // ลบ alert หลังจาก 5 วินาที
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>
