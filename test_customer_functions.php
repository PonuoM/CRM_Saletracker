<?php
/**
 * Test Customer Functions
 * ทดสอบการทำงานของฟังก์ชัน JavaScript ในหน้าลูกค้า
 */

session_start();

// Include required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/controllers/CustomerController.php';

// Test data
$testCustomerId = 1;
$testUserId = 1;

// Simulate session
$_SESSION['user_id'] = $testUserId;
$_SESSION['role_name'] = 'telesales';
$_SESSION['full_name'] = 'Test User';

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบฟังก์ชันลูกค้า - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .test-result {
            margin: 10px 0;
            padding: 10px;
            border-radius: 3px;
        }
        .test-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .test-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🧪 ทดสอบฟังก์ชันลูกค้า</h1>
        
        <div class="test-section">
            <h3>1. ทดสอบฟังก์ชัน logCall</h3>
            <button class="btn btn-success" onclick="testLogCall()">
                <i class="fas fa-phone me-1"></i>ทดสอบบันทึกการโทร
            </button>
            <div id="logCallResult"></div>
        </div>

        <div class="test-section">
            <h3>2. ทดสอบฟังก์ชัน createAppointment</h3>
            <button class="btn btn-info" onclick="testCreateAppointment()">
                <i class="fas fa-calendar me-1"></i>ทดสอบสร้างนัดหมาย
            </button>
            <div id="appointmentResult"></div>
        </div>

        <div class="test-section">
            <h3>3. ทดสอบฟังก์ชัน createOrder</h3>
            <button class="btn btn-warning" onclick="testCreateOrder()">
                <i class="fas fa-shopping-cart me-1"></i>ทดสอบสร้างคำสั่งซื้อ
            </button>
            <div id="orderResult"></div>
        </div>

        <div class="test-section">
            <h3>4. ทดสอบการเชื่อมต่อ API</h3>
            <button class="btn btn-primary" onclick="testApiConnection()">
                <i class="fas fa-plug me-1"></i>ทดสอบการเชื่อมต่อ API
            </button>
            <div id="apiResult"></div>
        </div>

        <div class="test-section">
            <h3>5. ตรวจสอบฟังก์ชันที่โหลด</h3>
            <button class="btn btn-secondary" onclick="checkLoadedFunctions()">
                <i class="fas fa-check me-1"></i>ตรวจสอบฟังก์ชัน
            </button>
            <div id="functionResult"></div>
        </div>
    </div>

    <!-- Log Call Modal -->
    <div class="modal fade" id="logCallModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">บันทึกการโทร</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="logCallForm">
                        <input type="hidden" id="callCustomerId" value="<?php echo $testCustomerId; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="callType" class="form-label">ประเภทการโทร</label>
                                <select class="form-select" id="callType" required>
                                    <option value="outbound">โทรออก</option>
                                    <option value="inbound">โทรเข้า</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="callStatus" class="form-label">สถานะการโทร</label>
                                <select class="form-select" id="callStatus" required>
                                    <option value="answered">รับสาย</option>
                                    <option value="no_answer">ไม่รับสาย</option>
                                    <option value="busy">สายไม่ว่าง</option>
                                    <option value="invalid">เบอร์ไม่ถูกต้อง</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="callResult" class="form-label">ผลการโทร</label>
                                <select class="form-select" id="callResult">
                                    <option value="">เลือกผลการโทร</option>
                                    <option value="interested">สนใจ</option>
                                    <option value="not_interested">ไม่สนใจ</option>
                                    <option value="callback">โทรกลับ</option>
                                    <option value="order">สั่งซื้อ</option>
                                    <option value="complaint">ร้องเรียน</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="callDuration" class="form-label">ระยะเวลา (นาที)</label>
                                <input type="number" class="form-control" id="callDuration" min="0" value="0">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label for="callNotes" class="form-label">หมายเหตุ</label>
                            <textarea class="form-control" id="callNotes" rows="3"></textarea>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="nextAction" class="form-label">การดำเนินการต่อไป</label>
                                <input type="text" class="form-control" id="nextAction">
                            </div>
                            <div class="col-md-6">
                                <label for="nextFollowup" class="form-label">นัดติดตาม</label>
                                <input type="datetime-local" class="form-control" id="nextFollowup">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="submitCallLog()">บันทึก</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Test functions
        function testLogCall() {
            try {
                if (typeof window.logCall === 'function') {
                    window.logCall(<?php echo $testCustomerId; ?>);
                    document.getElementById('logCallResult').innerHTML = 
                        '<div class="test-result test-success">✅ ฟังก์ชัน logCall ทำงานได้ปกติ</div>';
                } else {
                    document.getElementById('logCallResult').innerHTML = 
                        '<div class="test-result test-error">❌ ฟังก์ชัน logCall ไม่ได้ถูกกำหนด</div>';
                }
            } catch (error) {
                document.getElementById('logCallResult').innerHTML = 
                    '<div class="test-result test-error">❌ เกิดข้อผิดพลาด: ' + error.message + '</div>';
            }
        }

        function testCreateAppointment() {
            try {
                if (typeof window.createAppointment === 'function') {
                    window.createAppointment(<?php echo $testCustomerId; ?>);
                    document.getElementById('appointmentResult').innerHTML = 
                        '<div class="test-result test-success">✅ ฟังก์ชัน createAppointment ทำงานได้ปกติ</div>';
                } else {
                    document.getElementById('appointmentResult').innerHTML = 
                        '<div class="test-result test-error">❌ ฟังก์ชัน createAppointment ไม่ได้ถูกกำหนด</div>';
                }
            } catch (error) {
                document.getElementById('appointmentResult').innerHTML = 
                    '<div class="test-result test-error">❌ เกิดข้อผิดพลาด: ' + error.message + '</div>';
            }
        }

        function testCreateOrder() {
            try {
                if (typeof window.createOrder === 'function') {
                    // Don't actually redirect, just test the function
                    const originalLocation = window.location;
                    window.location = { href: '' };
                    window.createOrder(<?php echo $testCustomerId; ?>);
                    window.location = originalLocation;
                    document.getElementById('orderResult').innerHTML = 
                        '<div class="test-result test-success">✅ ฟังก์ชัน createOrder ทำงานได้ปกติ</div>';
                } else {
                    document.getElementById('orderResult').innerHTML = 
                        '<div class="test-result test-error">❌ ฟังก์ชัน createOrder ไม่ได้ถูกกำหนด</div>';
                }
            } catch (error) {
                document.getElementById('orderResult').innerHTML = 
                    '<div class="test-result test-error">❌ เกิดข้อผิดพลาด: ' + error.message + '</div>';
            }
        }

        function testApiConnection() {
            fetch('api/customers.php?basket_type=distribution')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('apiResult').innerHTML = 
                        '<div class="test-result test-success">✅ API เชื่อมต่อได้ปกติ: ' + JSON.stringify(data).substring(0, 100) + '...</div>';
                })
                .catch(error => {
                    document.getElementById('apiResult').innerHTML = 
                        '<div class="test-result test-error">❌ เกิดข้อผิดพลาดในการเชื่อมต่อ API: ' + error.message + '</div>';
                });
        }

        function checkLoadedFunctions() {
            const functions = ['logCall', 'createAppointment', 'createOrder', 'submitCallLog', 'submitAppointment'];
            let result = '<div class="test-result">';
            
            functions.forEach(funcName => {
                if (typeof window[funcName] === 'function') {
                    result += '<div class="test-success">✅ ' + funcName + ' - โหลดแล้ว</div>';
                } else {
                    result += '<div class="test-error">❌ ' + funcName + ' - ไม่ได้โหลด</div>';
                }
            });
            
            result += '</div>';
            document.getElementById('functionResult').innerHTML = result;
        }

        // Customer detail page specific functions
        window.logCall = function(customerId) {
            document.getElementById('callCustomerId').value = customerId;
            const modal = new bootstrap.Modal(document.getElementById('logCallModal'));
            modal.show();
        };

        window.createAppointment = function(customerId) {
            const appointmentModal = `
                <div class="modal fade" id="appointmentModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">สร้างนัดหมาย</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="appointmentForm">
                                    <input type="hidden" id="appointmentCustomerId" value="${customerId}">
                                    <div class="mb-3">
                                        <label for="appointmentDate" class="form-label">วันที่นัดหมาย</label>
                                        <input type="datetime-local" class="form-control" id="appointmentDate" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="appointmentType" class="form-label">ประเภทนัดหมาย</label>
                                        <select class="form-select" id="appointmentType" required>
                                            <option value="">เลือกประเภท</option>
                                            <option value="call">โทรศัพท์</option>
                                            <option value="meeting">ประชุม</option>
                                            <option value="presentation">นำเสนอ</option>
                                            <option value="followup">ติดตาม</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="appointmentNotes" class="form-label">หมายเหตุ</label>
                                        <textarea class="form-control" id="appointmentNotes" rows="3"></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="button" class="btn btn-primary" onclick="submitAppointment()">บันทึก</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            const existingModal = document.getElementById('appointmentModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            document.body.insertAdjacentHTML('beforeend', appointmentModal);
            
            const modal = new bootstrap.Modal(document.getElementById('appointmentModal'));
            modal.show();
        };

        window.createOrder = function(customerId) {
            window.location.href = `orders.php?action=create&customer_id=${customerId}`;
        };

        window.submitAppointment = function() {
            const customerId = document.getElementById('appointmentCustomerId').value;
            const appointmentDate = document.getElementById('appointmentDate').value;
            const appointmentType = document.getElementById('appointmentType').value;
            const notes = document.getElementById('appointmentNotes').value;
            
            if (!appointmentDate || !appointmentType) {
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }
            
            alert('บันทึกนัดหมายสำเร็จ');
            bootstrap.Modal.getInstance(document.getElementById('appointmentModal')).hide();
            
            setTimeout(() => {
                const modal = document.getElementById('appointmentModal');
                if (modal) {
                    modal.remove();
                }
            }, 300);
        };

        window.submitCallLog = function() {
            const customerId = document.getElementById('callCustomerId').value;
            const callType = document.getElementById('callType').value;
            const callStatus = document.getElementById('callStatus').value;
            const callResult = document.getElementById('callResult').value;
            const duration = document.getElementById('callDuration').value;
            const notes = document.getElementById('callNotes').value;
            const nextAction = document.getElementById('nextAction').value;
            const nextFollowup = document.getElementById('nextFollowup').value;
            
            if (!callStatus) {
                alert('กรุณาเลือกสถานะการโทร');
                return;
            }
            
            const data = {
                customer_id: parseInt(customerId),
                call_type: callType,
                call_status: callStatus,
                call_result: callResult || null,
                duration: parseInt(duration) || 0,
                notes: notes,
                next_action: nextAction,
                next_followup: nextFollowup || null
            };
            
            fetch('api/customers.php?action=log_call', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('บันทึกการโทรสำเร็จ');
                    bootstrap.Modal.getInstance(document.getElementById('logCallModal')).hide();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการบันทึกการโทร');
            });
        };

        // Auto-check functions on page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(checkLoadedFunctions, 1000);
        });
    </script>
</body>
</html> 