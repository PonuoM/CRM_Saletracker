<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'CRM SalesTracker'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #7C9885;
            --text-color: #4A5568;
        }
        
        body {
            color: var(--text-color);
        }
        
        .btn-primary, .btn-success, .btn-info, .btn-warning, .btn-secondary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: white !important;
        }
        
        .btn-primary:hover, .btn-success:hover, .btn-info:hover, .btn-warning:hover, .btn-secondary:hover {
            background-color: #6a8573 !important;
            border-color: #6a8573 !important;
        }
        
        .btn-outline-primary, .btn-outline-secondary {
            color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        
        .btn-outline-primary:hover, .btn-outline-secondary:hover {
            background-color: var(--primary-color) !important;
            color: white !important;
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-marker {
            position: absolute;
            left: -35px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: var(--primary-color);
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px var(--primary-color);
        }
        
        .timeline-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 3px solid var(--primary-color);
        }
        
        .pagination .page-link {
            color: var(--primary-color);
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .table th {
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .table td {
            font-size: 0.875rem;
        }
        
        .badge {
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <!-- Include Header Component -->
    <?php include APP_VIEWS . 'components/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar Component -->
            <?php include APP_VIEWS . 'components/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <?php echo $content ?? ''; ?>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/customer-detail.js"></script>
    <script src="assets/js/customers.js"></script>
    
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