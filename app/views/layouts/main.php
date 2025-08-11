<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'CRM SalesTracker'; ?></title>

    <!-- Font Preloading to prevent FOUT -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Sukhumvit+Set:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sukhumvit+Set:wght@300;400;500;600;700&display=swap"></noscript>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="<?php echo $bodyClass ?? ''; ?> fonts-loading">
    <!-- Include Header Component -->
    <?php include APP_VIEWS . 'components/header.php'; ?>

    <!-- Mobile Sidebar Toggle -->
    <button class="mobile-sidebar-toggle d-md-none" id="mobileSidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container-fluid p-0">
        <!-- Include Sidebar Component -->
        <?php include APP_VIEWS . 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php echo $content ?? ''; ?>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sidebar.js"></script>
    <?php if (isset($bodyClass) && $bodyClass === 'customer-page-body'): ?>
        <script src="assets/js/customers.js"></script>
    <?php endif; ?>
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

        // FOUT Prevention Script
        (function() {
            'use strict';

            // Check if Font Loading API is supported
            if ('fonts' in document) {
                // Use Font Loading API
                document.fonts.ready.then(function() {
                    console.log('✓ Fonts loaded successfully');
                    document.body.classList.remove('fonts-loading');
                    document.body.classList.add('fonts-loaded');
                });

                // Fallback timeout (in case fonts fail to load)
                setTimeout(function() {
                    if (document.body.classList.contains('fonts-loading')) {
                        console.log('⚠ Font loading timeout - using fallback');
                        document.body.classList.remove('fonts-loading');
                        document.body.classList.add('fonts-fallback');
                    }
                }, 3000);

            } else {
                // Fallback for browsers without Font Loading API
                console.log('⚠ Font Loading API not supported - using fallback');
                setTimeout(function() {
                    document.body.classList.remove('fonts-loading');
                    document.body.classList.add('fonts-fallback');
                }, 100);
            }

            // Additional check for Sukhumvit Set specifically
            if ('fonts' in document) {
                document.fonts.load('400 16px "Sukhumvit Set"').then(function() {
                    console.log('✓ Sukhumvit Set loaded');
                }).catch(function() {
                    console.log('⚠ Sukhumvit Set failed to load');
                });
            }
        })();
    </script>
</body>
</html> 