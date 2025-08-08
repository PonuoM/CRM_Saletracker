<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'CRM SalesTracker'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="<?php echo $bodyClass ?? ''; ?>">
    <!-- Include Header Component -->
    <?php include APP_VIEWS . 'components/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar Component -->
            <?php include APP_VIEWS . 'components/sidebar.php'; ?>

            <!-- Main Content (grid sums to 12 with sidebar: 2 + 10) -->
            <main class="col-md-9 col-lg-10 main-content">
                <?php echo $content ?? ''; ?>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/customers.js"></script>
    <script src="assets/js/customer-detail.js"></script>
    
    <script>
        // Global event listeners for customer detail page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Main layout loaded');
            
            // Log Call Button
            const logCallBtn = document.getElementById('logCallBtn');
            if (logCallBtn) {
                logCallBtn.addEventListener('click', function() {
                    const customerId = this.getAttribute('data-customer-id');
                    console.log('Log Call button clicked for customer:', customerId);
                    if (typeof window.logCall === 'function') {
                        window.logCall(customerId);
                    } else {
                        console.error('logCall function not found');
                        alert('เกิดข้อผิดพลาด: ฟังก์ชัน logCall ไม่ได้ถูกโหลด');
                    }
                });
            }
            
            // Add Call Log Button
            const addCallLogBtn = document.getElementById('addCallLogBtn');
            if (addCallLogBtn) {
                addCallLogBtn.addEventListener('click', function() {
                    const customerId = this.getAttribute('data-customer-id');
                    console.log('Add Call Log button clicked for customer:', customerId);
                    if (typeof window.logCall === 'function') {
                        window.logCall(customerId);
                    } else {
                        console.error('logCall function not found');
                        alert('เกิดข้อผิดพลาด: ฟังก์ชัน logCall ไม่ได้ถูกโหลด');
                    }
                });
            }
            
            // Create Appointment Button
            const createAppointmentBtn = document.getElementById('createAppointmentBtn');
            if (createAppointmentBtn) {
                createAppointmentBtn.addEventListener('click', function() {
                    const customerId = this.getAttribute('data-customer-id');
                    console.log('Create Appointment button clicked for customer:', customerId);
                    if (typeof window.createAppointment === 'function') {
                        window.createAppointment(customerId);
                    } else {
                        console.error('createAppointment function not found');
                        alert('เกิดข้อผิดพลาด: ฟังก์ชัน createAppointment ไม่ได้ถูกโหลด');
                    }
                });
            }
            
            // Create Order Button
            const createOrderBtn = document.getElementById('createOrderBtn');
            if (createOrderBtn) {
                createOrderBtn.addEventListener('click', function() {
                    const customerId = this.getAttribute('data-customer-id');
                    console.log('Create Order button clicked for customer:', customerId);
                    if (typeof window.createOrder === 'function') {
                        window.createOrder(customerId);
                    } else {
                        console.error('createOrder function not found');
                        alert('เกิดข้อผิดพลาด: ฟังก์ชัน createOrder ไม่ได้ถูกโหลด');
                    }
                });
            }
            
            // Add Order Button
            const addOrderBtn = document.getElementById('addOrderBtn');
            if (addOrderBtn) {
                addOrderBtn.addEventListener('click', function() {
                    const customerId = this.getAttribute('data-customer-id');
                    console.log('Add Order button clicked for customer:', customerId);
                    if (typeof window.createOrder === 'function') {
                        window.createOrder(customerId);
                    } else {
                        console.error('createOrder function not found');
                        alert('เกิดข้อผิดพลาด: ฟังก์ชัน createOrder ไม่ได้ถูกโหลด');
                    }
                });
            }
            
            // Submit Call Log Button
            const submitCallLogBtn = document.getElementById('submitCallLogBtn');
            if (submitCallLogBtn) {
                submitCallLogBtn.addEventListener('click', function() {
                    console.log('Submit Call Log button clicked');
                    if (typeof window.submitCallLog === 'function') {
                        window.submitCallLog();
                    } else {
                        console.error('submitCallLog function not found');
                        alert('เกิดข้อผิดพลาด: ฟังก์ชัน submitCallLog ไม่ได้ถูกโหลด');
                    }
                });
            }
            
            // Check if all functions are loaded
            setTimeout(function() {
                const requiredFunctions = ['logCall', 'createAppointment', 'createOrder', 'submitCallLog', 'submitAppointment'];
                const missingFunctions = [];
                
                requiredFunctions.forEach(funcName => {
                    if (typeof window[funcName] !== 'function') {
                        missingFunctions.push(funcName);
                    }
                });
                
                if (missingFunctions.length > 0) {
                    console.error('Missing functions:', missingFunctions);
                } else {
                    console.log('All required functions are loaded successfully');
                }
            }, 1000);
            
            // Add global error handler
            window.addEventListener('error', function(event) {
                console.error('Global error:', event.error);
            });
            
            // Add unhandled promise rejection handler
            window.addEventListener('unhandledrejection', function(event) {
                console.error('Unhandled promise rejection:', event.reason);
            });
        });
    </script>
</body>
</html> 