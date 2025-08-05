<?php
/**
 * Simple API Test
 * ทดสอบ API แบบง่าย
 */

session_start();

// Simulate session
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'telesales';
$_SESSION['full_name'] = 'Test User';

// Include required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/controllers/CustomerController.php';

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบ API แบบง่าย - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
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
        .console-log {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🧪 ทดสอบ API แบบง่าย</h1>
        <p class="text-muted">ทดสอบ API endpoints แบบง่าย</p>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>ทดสอบ API log_call</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary" onclick="testLogCallAPI()">
                            <i class="fas fa-phone me-1"></i>ทดสอบ log_call
                        </button>
                        <div id="logCallResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>ทดสอบ API getCustomersByBasket</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-info" onclick="testGetCustomersAPI()">
                            <i class="fas fa-users me-1"></i>ทดสอบ getCustomersByBasket
                        </button>
                        <div id="getCustomersResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Console Logs</h5>
            </div>
            <div class="card-body">
                <div id="consoleLogs" class="console-log">
                    <div>Console logs จะแสดงที่นี่...</div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>ข้อมูล Session</h5>
            </div>
            <div class="card-body">
                <pre><?php print_r($_SESSION); ?></pre>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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

        function testLogCallAPI() {
            console.log('Testing log_call API...');
            
            const testData = {
                customer_id: 1,
                call_type: 'outbound',
                call_status: 'answered',
                call_result: 'interested',
                duration: 5,
                notes: 'Test call from simple API test',
                next_action: 'Follow up',
                next_followup: null
            };
            
            fetch('api/customers.php?action=log_call', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(testData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        updateResult('logCallResult', 'success', '✅ API log_call ทำงานได้ปกติ: ' + data.message);
                    } else {
                        updateResult('logCallResult', 'error', '❌ API log_call error: ' + (data.message || data.error || 'Unknown error'));
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    updateResult('logCallResult', 'error', '❌ API log_call error: ไม่สามารถ parse JSON ได้ - ' + text.substring(0, 200));
                }
            })
            .catch(error => {
                console.error('API Error:', error);
                updateResult('logCallResult', 'error', '❌ API log_call error: ' + error.message);
            });
        }

        function testGetCustomersAPI() {
            console.log('Testing getCustomersByBasket API...');
            
            fetch('api/customers.php?basket_type=distribution')
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        updateResult('getCustomersResult', 'success', '✅ API getCustomersByBasket ทำงานได้ปกติ: ได้ข้อมูล ' + (data.data ? data.data.length : 0) + ' รายการ');
                    } else {
                        updateResult('getCustomersResult', 'error', '❌ API getCustomersByBasket error: ' + (data.message || data.error || 'Unknown error'));
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    updateResult('getCustomersResult', 'error', '❌ API getCustomersByBasket error: ไม่สามารถ parse JSON ได้ - ' + text.substring(0, 200));
                }
            })
            .catch(error => {
                console.error('API Error:', error);
                updateResult('getCustomersResult', 'error', '❌ API getCustomersByBasket error: ' + error.message);
            });
        }

        function updateResult(elementId, type, message) {
            const element = document.getElementById(elementId);
            if (element) {
                element.innerHTML = `<div class="test-result test-${type}">${message}</div>`;
            }
        }

        // Auto test on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Simple API test page loaded');
            console.log('Session data:', <?php echo json_encode($_SESSION); ?>);
        });
    </script>
</body>
</html> 