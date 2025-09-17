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
                            <option value="got_talk">ได้คุย</option>
                            <option value="no_answer">ไม่รับสาย</option>
                            <option value="busy">สายไม่ว่าง</option>
                            <option value="hang_up">ตัดสายทิ้ง</option>
                            <option value="no_signal">ไม่มีสัญญาณ</option>
                        </select>
                    </div>
                    
                    <!-- ผลการโทร -->
                    <div class="col-md-6">
                        <label for="callResult" class="form-label">ผลการโทร</label>
                        <select class="form-select" id="callResult" name="call_result">
                            <option value="">เลือกผลการโทร</option>
                            <!-- ตัวเลือกจะถูกอัปเดตตามสถานะการโทร -->
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
                    
                    <!-- ข้อมูลพืชพันธุ์และขนาดสวน -->
                    <div class="col-md-6">
                        <label for="plantVariety" class="form-label">พืชพันธุ์</label>
                        <input type="text" class="form-control" id="plantVariety" name="plant_variety" placeholder="เช่น มะม่วง, ทุเรียน, ลำใย">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="gardenSize" class="form-label">ขนาดสวน</label>
                        <input type="text" class="form-control" id="gardenSize" name="garden_size" placeholder="เช่น 5 ไร่, 2,000 ตารางวา">
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
            // อัปเดตตัวเลือกผลการโทรตามสถานะการโทร
            updateCallResultOptions(this.value);
            
            // ถ้าเลือกสถานะที่ไม่ใช่ "รับสาย" ให้ auto-fill ผลการโทร
            if (this.value && this.value !== 'answered') {
                const statusValueMap = {
                    'got_talk': 'ได้คุย',
                    'no_answer': 'ไม่รับสาย',
                    'busy': 'สายไม่ว่าง',
                    'hang_up': 'ตัดสายทิ้ง',
                    'no_signal': 'ไม่มีสัญญาณ'
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
                        ${call.call_result_display || call.call_result}
                    </span>
                    <span class="badge bg-${getCallStatusColor(call.call_status)} ms-1">
                        ${call.call_status_display || getCallStatusText(call.call_status)}
                    </span>
                </div>
                <small class="text-muted">${formatDate(call.created_at)}</small>
            </div>
            ${call.notes ? `<div class="mt-1 small text-muted">${escapeHtml(call.notes)}</div>` : ''}
            ${call.duration_minutes > 0 ? `<div class="mt-1 small text-muted">ใช้เวลา ${call.duration_minutes} นาที</div>` : ''}
            ${call.plant_variety ? `<div class="mt-1 small"><span class="badge bg-info me-1">พืชพันธุ์:</span> ${escapeHtml(call.plant_variety)}</div>` : ''}
            ${call.garden_size ? `<div class="mt-1 small"><span class="badge bg-success me-1">ขนาดสวน:</span> ${escapeHtml(call.garden_size)}</div>` : ''}
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
            <option value="สินค้ายังไม่หมด">สินค้ายังไม่หมด</option>
            <option value="ใช้แล้วไม่เห็นผล">ใช้แล้วไม่เห็นผล</option>
            <option value="ยังไม่ได้ลองใช้">ยังไม่ได้ลองใช้</option>
            <option value="ยังไม่ถึงรอบใช้งาน">ยังไม่ถึงรอบใช้งาน</option>
            <option value="สั่งช่องทางอื่นแล้ว">สั่งช่องทางอื่นแล้ว</option>
            <option value="ไม่สะดวกคุย">ไม่สะดวกคุย</option>
            <option value="ฝากสั่งไม่ได้ใช้เอง">ฝากสั่งไม่ได้ใช้เอง</option>
            <option value="คนอื่นรับสายแทน">คนอื่นรับสายแทน</option>
            <option value="เลิกทำสวน">เลิกทำสวน</option>
            <option value="ไม่สนใจ">ไม่สนใจ</option>
            <option value="ห้ามติดต่อ">ห้ามติดต่อ</option>
        `;
    } else if (callStatus === 'got_talk') {
        // ถ้าได้คุย - auto-fill เป็น "ได้คุย"
        callResult.innerHTML += `
            <option value="ได้คุย">ได้คุย</option>
        `;
    } else if (callStatus === 'no_answer') {
        // ถ้าไม่รับสาย - auto-fill เป็น "ไม่รับสาย"
        callResult.innerHTML += `
            <option value="ไม่รับสาย">ไม่รับสาย</option>
        `;
    } else if (callStatus === 'busy') {
        // ถ้าสายไม่ว่าง - auto-fill เป็น "สายไม่ว่าง"
        callResult.innerHTML += `
            <option value="สายไม่ว่าง">สายไม่ว่าง</option>
        `;
    } else if (callStatus === 'hang_up') {
        // ถ้าตัดสายทิ้ง - auto-fill เป็น "ตัดสายทิ้ง"
        callResult.innerHTML += `
            <option value="ตัดสายทิ้ง">ตัดสายทิ้ง</option>
        `;
    } else if (callStatus === 'no_signal') {
        // ถ้าไม่มีสัญญาณ - auto-fill เป็น "ไม่มีสัญญาณ"
        callResult.innerHTML += `
            <option value="ไม่มีสัญญาณ">ไม่มีสัญญาณ</option>
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
    
    // แปลง FormData เป็น JSON เพื่อส่งข้อมูลฟิลด์ใหม่
    const data = {
        customer_id: formData.get('customer_id'),
        call_type: 'outbound',
        call_status: formData.get('call_status'),
        call_result: formData.get('call_result'),
        duration_minutes: formData.get('duration_minutes') || 0,
        notes: formData.get('notes'),
        next_followup_at: formData.get('next_followup_at'),
        plant_variety: formData.get('plant_variety'),
        garden_size: formData.get('garden_size')
    };
    
    fetch('api/calls.php?action=log_call', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
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
        'สินค้ายังไม่หมด': 'info',
        'ใช้แล้วไม่เห็นผล': 'warning',
        'ยังไม่ได้ลองใช้': 'primary',
        'ยังไม่ถึงรอบใช้งาน': 'info',
        'สั่งช่องทางอื่นแล้ว': 'success',
        'ไม่สะดวกคุย': 'secondary',
        'ตัดสายทิ้ง': 'danger',
        'ฝากสั่งไม่ได้ใช้เอง': 'warning',
        'คนอื่นรับสายแทน': 'info',
        'เลิกทำสวน': 'secondary',
        'ไม่สนใจ': 'secondary',
        'ห้ามติดต่อ': 'danger',
        'ได้คุย': 'success',
        'ไม่รับสาย': 'warning',
        'สายไม่ว่าง': 'info',
        'ไม่มีสัญญาณ': 'secondary'
    };
    return colors[result] || 'secondary';
}

function getCallResultText(result) {
    // ถ้าเป็นข้อความภาษาไทยแล้ว ให้คืนค่าเดิม
    if (result && /[\u0E00-\u0E7F]/.test(result)) {
        return result;
    }
    
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
        'got_talk': 'success',
        'no_answer': 'warning',
        'busy': 'info',
        'invalid': 'danger',
        'hang_up': 'secondary',
        'no_signal': 'secondary'
    };
    return colors[status] || 'secondary';
}

function getCallStatusText(status) {
    const texts = {
        'answered': 'รับสาย',
        'got_talk': 'ได้คุย',
        'no_answer': 'ไม่รับสาย',
        'busy': 'สายไม่ว่าง',
        'invalid': 'เบอร์ไม่ถูกต้อง',
        'hang_up': 'ตัดสายทิ้ง',
        'no_signal': 'ไม่มีสัญญาณ'
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
<script>
// Override selects to use Thai labels consistently and mirror results for non-answered statuses
document.addEventListener('DOMContentLoaded', function() {
    try {
        const statusSel = document.getElementById('callStatus');
        const resultSel = document.getElementById('callResult');
        if (!statusSel || !resultSel) return;

        const statuses = ['รับสาย','ได้คุย','ไม่รับสาย','สายไม่ว่าง','ตัดสายทิ้ง','ไม่มีสัญญาณ'];
        statusSel.innerHTML = '<option value="">เลือกสถานะการโทร</option>' + statuses.map(s=>`<option value="${s}">${s}</option>`).join('');

        const RESULT_ANSWERED = [
            'สินค้ายังไม่หมด',
            'ใช้แล้วไม่เห็นผล',
            'ยังไม่ได้ลองใช้',
            'ยังไม่ถึงรอบใช้งาน',
            'สั่งช่องทางอื่นแล้ว',
            'ไม่สะดวกคุย',
            'ตัดสายทิ้ง',
            'ฝากสั่งไม่ได้ใช้เอง',
            'คนอื่นรับสายแทน',
            'เลิกทำสวน',
            'ไม่สนใจ',
            'ห้ามติดต่อ',
            'ได้คุย',
            'ขายได้'
        ];
        function updateResultByStatus(){
            const s = (statusSel.value||'').trim();
            let opts = [];
            if (s === 'รับสาย') opts = RESULT_ANSWERED;
            else if (s) opts = [s];
            resultSel.innerHTML = '<option value="">เลือกผลการโทร</option>' + opts.map(t=>`<option value="${t}">${t}</option>`).join('');
        }
        statusSel.addEventListener('change', updateResultByStatus);
        updateResultByStatus();
    } catch(_) {}
});
</script>
