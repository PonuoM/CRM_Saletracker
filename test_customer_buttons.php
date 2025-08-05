<?php
/**
 * Test Customer Detail Buttons
 * ทดสอบการทำงานของปุ่มทั้งหมดในหน้ารายละเอียดลูกค้า
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
    <title>ทดสอบปุ่มลูกค้า - CRM SalesTracker</title>
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
        .test-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .button-test {
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🔧 ทดสอบปุ่มลูกค้า</h1>
        <p class="text-muted">ทดสอบการทำงานของปุ่มทั้งหมดในหน้ารายละเอียดลูกค้า</p>
        
        <div class="test-section">
            <h3>1. ปุ่มหลัก (Main Buttons)</h3>
            <div class="row">
                <div class="col-md-3">
                    <button class="btn btn-success w-100 button-test" id="logCallBtn" data-customer-id="<?php echo $testCustomerId; ?>">
                        <i class="fas fa-phone me-1"></i>บันทึกการโทร
                    </button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-info w-100 button-test" id="createAppointmentBtn" data-customer-id="<?php echo $testCustomerId; ?>">
                        <i class="fas fa-calendar me-1"></i>นัดหมาย
                    </button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-warning w-100 button-test" id="createOrderBtn" data-customer-id="<?php echo $testCustomerId; ?>">
                        <i class="fas fa-shopping-cart me-1"></i>สร้างคำสั่งซื้อ
                    </button>
                </div>
                <div class="col-md-3">
                    <a href="customers.php?action=edit&id=<?php echo $testCustomerId; ?>" class="btn btn-primary w-100 button-test">
                        <i class="fas fa-edit me-1"></i>แก้ไข
                    </a>
                </div>
            </div>
            <div id="mainButtonsResult" class="mt-3"></div>
        </div>

        <div class="test-section">
            <h3>2. ปุ่มเพิ่ม (Add Buttons)</h3>
            <div class="row">
                <div class="col-md-6">
                    <button class="btn btn-sm btn-success w-100 button-test" id="addCallLogBtn" data-customer-id="<?php echo $testCustomerId; ?>">
                        <i class="fas fa-plus me-1"></i>เพิ่ม (บันทึกการโทร)
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-sm btn-warning w-100 button-test" id="addOrderBtn" data-customer-id="<?php echo $testCustomerId; ?>">
                        <i class="fas fa-plus me-1"></i>สร้าง (คำสั่งซื้อ)
                    </button>
                </div>
            </div>
            <div id="addButtonsResult" class="mt-3"></div>
        </div>

        <div class="test-section">
            <h3>3. ตรวจสอบการโหลดไฟล์ JavaScript</h3>
            <div id="fileLoadResult"></div>
        </div>

        <div class="test-section">
            <h3>4. ตรวจสอบฟังก์ชันที่โหลด</h3>
            <button class="btn btn-secondary" onclick="checkLoadedFunctions()">
                <i class="fas fa-check me-1"></i>ตรวจสอบฟังก์ชัน
            </button>
            <div id="functionResult"></div>
        </div>

        <div class="test-section">
            <h3>5. ทดสอบ API Endpoints</h3>
            <button class="btn btn-info" onclick="testAPIEndpoints()">
                <i class="fas fa-api me-1"></i>ทดสอบ API
            </button>
            <div id="apiResult"></div>
        </div>

        <div class="test-section">
            <h3>6. Console Logs</h3>
            <div id="consoleLogs" style="background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;">
                <div>Console logs จะแสดงที่นี่...</div>
            </div>
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
                    <button type="button" class="btn btn-primary" id="submitCallLogBtn">บันทึก</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/customer-detail.js"></script>
    <script src="assets/js/customers.js"></script>
    
    <script>
        // Override console.log to capture logs
        const originalConsoleLog = console.log;
        const originalConsoleError = console.error;
        const consoleLogsDiv = document.getElementById('consoleLogs');
        
        console.log = function(...args) {
            originalConsoleLog.apply(console, args);
            const logEntry = document.createElement('div');
            logEntry.style.color = '#28a745';
            logEntry.textContent = 'LOG: ' + args.join(' ');
            consoleLogsDiv.appendChild(logEntry);
            consoleLogsDiv.scrollTop = consoleLogsDiv.scrollHeight;
        };
        
        console.error = function(...args) {
            originalConsoleError.apply(console, args);
            const logEntry = document.createElement('div');
            logEntry.style.color = '#dc3545';
            logEntry.textContent = 'ERROR: ' + args.join(' ');
            consoleLogsDiv.appendChild(logEntry);
            consoleLogsDiv.scrollTop = consoleLogsDiv.scrollHeight;
        };

        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Customer buttons test page loaded');
            
            // Test main buttons
            testMainButtons();
            
            // Test add buttons
            testAddButtons();
            
            // Check file loading
            checkFileLoading();
            
            // Log Call Button
            document.getElementById('logCallBtn').addEventListener('click', function() {
                const customerId = this.getAttribute('data-customer-id');
                console.log('Log Call button clicked for customer:', customerId);
                if (typeof window.logCall === 'function') {
                    window.logCall(customerId);
                    updateResult('mainButtonsResult', 'success', '✅ ปุ่มบันทึกการโทรทำงานได้ปกติ');
                } else {
                    console.error('logCall function not found');
                    updateResult('mainButtonsResult', 'error', '❌ ฟังก์ชัน logCall ไม่ได้ถูกโหลด');
                }
            });
            
            // Add Call Log Button
            document.getElementById('addCallLogBtn').addEventListener('click', function() {
                const customerId = this.getAttribute('data-customer-id');
                console.log('Add Call Log button clicked for customer:', customerId);
                if (typeof window.logCall === 'function') {
                    window.logCall(customerId);
                    updateResult('addButtonsResult', 'success', '✅ ปุ่มเพิ่ม (บันทึกการโทร) ทำงานได้ปกติ');
                } else {
                    console.error('logCall function not found');
                    updateResult('addButtonsResult', 'error', '❌ ฟังก์ชัน logCall ไม่ได้ถูกโหลด');
                }
            });
            
            // Create Appointment Button
            document.getElementById('createAppointmentBtn').addEventListener('click', function() {
                const customerId = this.getAttribute('data-customer-id');
                console.log('Create Appointment button clicked for customer:', customerId);
                if (typeof window.createAppointment === 'function') {
                    window.createAppointment(customerId);
                    updateResult('mainButtonsResult', 'success', '✅ ปุ่มนัดหมายทำงานได้ปกติ');
                } else {
                    console.error('createAppointment function not found');
                    updateResult('mainButtonsResult', 'error', '❌ ฟังก์ชัน createAppointment ไม่ได้ถูกโหลด');
                }
            });
            
            // Create Order Button
            document.getElementById('createOrderBtn').addEventListener('click', function() {
                const customerId = this.getAttribute('data-customer-id');
                console.log('Create Order button clicked for customer:', customerId);
                if (typeof window.createOrder === 'function') {
                    window.createOrder(customerId);
                    updateResult('mainButtonsResult', 'success', '✅ ปุ่มสร้างคำสั่งซื้อทำงานได้ปกติ');
                } else {
                    console.error('createOrder function not found');
                    updateResult('mainButtonsResult', 'error', '❌ ฟังก์ชัน createOrder ไม่ได้ถูกโหลด');
                }
            });
            
            // Add Order Button
            document.getElementById('addOrderBtn').addEventListener('click', function() {
                const customerId = this.getAttribute('data-customer-id');
                console.log('Add Order button clicked for customer:', customerId);
                if (typeof window.createOrder === 'function') {
                    window.createOrder(customerId);
                    updateResult('addButtonsResult', 'success', '✅ ปุ่มสร้าง (คำสั่งซื้อ) ทำงานได้ปกติ');
                } else {
                    console.error('createOrder function not found');
                    updateResult('addButtonsResult', 'error', '❌ ฟังก์ชัน createOrder ไม่ได้ถูกโหลด');
                }
            });
            
            // Submit Call Log Button
            document.getElementById('submitCallLogBtn').addEventListener('click', function() {
                console.log('Submit Call Log button clicked');
                if (typeof window.submitCallLog === 'function') {
                    window.submitCallLog();
                } else {
                    console.error('submitCallLog function not found');
                    alert('เกิดข้อผิดพลาด: ฟังก์ชัน submitCallLog ไม่ได้ถูกโหลด');
                }
            });
        });

        function testMainButtons() {
            console.log('Testing main buttons...');
            const buttons = ['logCallBtn', 'createAppointmentBtn', 'createOrderBtn'];
            let allWorking = true;
            
            buttons.forEach(btnId => {
                const btn = document.getElementById(btnId);
                if (!btn) {
                    console.error('Button not found:', btnId);
                    allWorking = false;
                } else {
                    console.log('Button found:', btnId);
                }
            });
            
            if (allWorking) {
                updateResult('mainButtonsResult', 'info', 'ℹ️ ปุ่มหลักทั้งหมดพร้อมใช้งาน');
            }
        }

        function testAddButtons() {
            console.log('Testing add buttons...');
            const buttons = ['addCallLogBtn', 'addOrderBtn'];
            let allWorking = true;
            
            buttons.forEach(btnId => {
                const btn = document.getElementById(btnId);
                if (!btn) {
                    console.error('Button not found:', btnId);
                    allWorking = false;
                } else {
                    console.log('Button found:', btnId);
                }
            });
            
            if (allWorking) {
                updateResult('addButtonsResult', 'info', 'ℹ️ ปุ่มเพิ่มทั้งหมดพร้อมใช้งาน');
            }
        }

        function checkFileLoading() {
            console.log('Checking file loading...');
            
            // Check if customer-detail.js is loaded
            if (typeof window.logCall === 'function') {
                updateResult('fileLoadResult', 'success', '✅ ไฟล์ customer-detail.js โหลดสำเร็จ');
            } else {
                updateResult('fileLoadResult', 'error', '❌ ไฟล์ customer-detail.js ไม่ได้ถูกโหลด');
            }
        }

        function checkLoadedFunctions() {
            console.log('Checking loaded functions...');
            const requiredFunctions = ['logCall', 'createAppointment', 'createOrder', 'submitCallLog', 'submitAppointment'];
            const missingFunctions = [];
            
            requiredFunctions.forEach(funcName => {
                if (typeof window[funcName] !== 'function') {
                    missingFunctions.push(funcName);
                }
            });
            
            if (missingFunctions.length > 0) {
                updateResult('functionResult', 'error', '❌ ฟังก์ชันที่ขาดหาย: ' + missingFunctions.join(', '));
            } else {
                updateResult('functionResult', 'success', '✅ ฟังก์ชันทั้งหมดโหลดสำเร็จ');
            }
        }

        function testAPIEndpoints() {
            console.log('Testing API endpoints...');
            
            // Test log_call endpoint
            fetch('api/customers.php?action=log_call', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    customer_id: <?php echo $testCustomerId; ?>,
                    call_type: 'outbound',
                    call_status: 'answered',
                    call_result: 'interested',
                    duration: 5,
                    notes: 'Test call from button test',
                    next_action: 'Follow up',
                    next_followup: null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateResult('apiResult', 'success', '✅ API log_call ทำงานได้ปกติ');
                } else {
                    updateResult('apiResult', 'error', '❌ API log_call error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('API Error:', error);
                updateResult('apiResult', 'error', '❌ API log_call error: ' + error.message);
            });
        }

        function updateResult(elementId, type, message) {
            const element = document.getElementById(elementId);
            if (element) {
                element.innerHTML = `<div class="test-result test-${type}">${message}</div>`;
            }
        }
    </script>
</body>
</html> 