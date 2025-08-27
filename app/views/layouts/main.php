<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'CRM SalesTracker'; ?></title>

    <!-- Preload local Sukhumvit Set fonts to reduce FOUT/FOIT -->
    <link rel="preload" href="assets/fonts/SukhumvitSet-Light.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="assets/fonts/SukhumvitSet-Text.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="assets/fonts/SukhumvitSet-Medium.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="assets/fonts/SukhumvitSet-SemiBold.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="assets/fonts/SukhumvitSet-Bold.ttf" as="font" type="font/ttf" crossorigin>

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
        <main class="main-content page-transition">
            <?php echo $content ?? ''; ?>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/page-transitions.js"></script>
    <script src="assets/js/sidebar.js"></script>
    <script src="assets/js/tags.js"></script>
    <?php if (isset($bodyClass) && $bodyClass === 'customer-page-body'): ?>
        <script src="assets/js/customers.js"></script>
        <script src="assets/js/customer-detail.js"></script>
    <?php elseif (isset($bodyClass) && $bodyClass === 'search-page-body'): ?>
        <script src="assets/js/search.js"></script>
    <?php elseif (isset($bodyClass) && strpos($bodyClass, 'customer-detail') !== false): ?>
        <script src="assets/js/customer-detail.js"></script>
    <?php elseif (isset($bodyClass) && $bodyClass === 'transfer-page-body'): ?>
        <script src="assets/js/customer-transfer.js"></script>
    <?php endif; ?>
    
    <script>
        // Global event listeners - only load for appropriate pages
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Main layout loaded');
            
            <?php if (isset($bodyClass) && ($bodyClass === 'customer-page-body' || strpos($bodyClass, 'customer-detail') !== false)): ?>
            // Log Call Buttons (support multiple on page)
            document.querySelectorAll('.log-call-btn').forEach(function(btn){
                btn.addEventListener('click', function() {
                    const customerId = this.getAttribute('data-customer-id');
                    if (typeof window.logCall === 'function') {
                        window.logCall(customerId);
                    } else {
                        console.error('logCall function not found');
                        alert('เกิดข้อผิดพลาด: ฟังก์ชัน logCall ไม่ได้ถูกโหลด');
                    }
                });
            });
            
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
            
            // Create Appointment Buttons
            document.querySelectorAll('.add-appointment-btn, #createAppointmentBtn').forEach(function(btn){
                btn.addEventListener('click', function(){
                    const customerId = this.getAttribute('data-customer-id');
                    if (typeof window.createAppointment === 'function') {
                        window.createAppointment(customerId);
                    } else {
                        console.error('createAppointment function not found');
                        alert('เกิดข้อผิดพลาด: ฟังก์ชัน createAppointment ไม่ได้ถูกโหลด');
                    }
                });
            });
            
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
            
            // Add Order Buttons
            document.querySelectorAll('.add-order-btn, #addOrderBtn, #createOrderBtn').forEach(function(btn){
                btn.addEventListener('click', function(){
                    const customerId = this.getAttribute('data-customer-id');
                    if (typeof window.createOrder === 'function') {
                        window.createOrder(customerId);
                    } else {
                        console.error('createOrder function not found');
                        alert('เกิดข้อผิดพลาด: ฟังก์ชัน createOrder ไม่ได้ถูกโหลด');
                    }
                });
            });
            
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
            <?php endif; ?>
            
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
<script>
// Backdrop watchdog: if there's a backdrop but no visible modal, remove the backdrop
function cleanupBackdrops(){
  const anyVisibleModal = !!document.querySelector('.modal.show');
  if (!anyVisibleModal) {
    document.querySelectorAll('.modal-backdrop').forEach(b=>b.remove());
  }
}
window.addEventListener('load', cleanupBackdrops);
document.addEventListener('shown.bs.modal', cleanupBackdrops);
document.addEventListener('hidden.bs.modal', cleanupBackdrops);
</script>
</html> 