/**
 * Dynamic Sidebar JavaScript
 * จัดการ sidebar functionality, pin/unpin, และ localStorage
 */

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('mainSidebar');
    const mainContent = document.querySelector('.main-content');
    const pinBtn = document.getElementById('sidebarPinBtn');

    // ตรวจสอบสถานะจาก localStorage
    const isPinned = localStorage.getItem('sidebarPinned') === 'true';
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

    // ตั้งค่าเริ่มต้น
    if (isPinned) {
        sidebar.classList.add('pinned');
        pinBtn.classList.add('pinned');
        pinBtn.title = 'ยกเลิกปักหมุด';
        mainContent.classList.remove('sidebar-mini');
    } else if (isCollapsed) {
        sidebar.classList.add('mini');
        mainContent.classList.add('sidebar-mini');
        pinBtn.classList.remove('pinned');
        pinBtn.title = 'ปักหมุด Sidebar';
    } else {
        // Default: mini mode
        sidebar.classList.add('mini');
        mainContent.classList.add('sidebar-mini');
    }

    // Pin/Unpin functionality
    if (pinBtn) {
        pinBtn.addEventListener('click', function() {
            const isCurrentlyPinned = sidebar.classList.contains('pinned');

            if (isCurrentlyPinned) {
                // Unpin - กลับไปโหมด mini
                sidebar.classList.remove('pinned');
                sidebar.classList.add('mini');
                mainContent.classList.add('sidebar-mini');
                pinBtn.classList.remove('pinned');
                pinBtn.title = 'ปักหมุด Sidebar';
                localStorage.setItem('sidebarPinned', 'false');
                localStorage.setItem('sidebarCollapsed', 'true');
            } else {
                // Pin - ขยายและปักหมุด
                sidebar.classList.remove('mini');
                sidebar.classList.add('pinned');
                mainContent.classList.remove('sidebar-mini');
                pinBtn.classList.add('pinned');
                pinBtn.title = 'ยกเลิกปักหมุด';
                localStorage.setItem('sidebarPinned', 'true');
                localStorage.setItem('sidebarCollapsed', 'false');
            }
        });
    }
    
    // Active link highlighting - More precise logic
    const currentPath = window.location.pathname;
    const currentSearch = window.location.search;
    const currentUrl = window.location.href;
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    
    // Remove any existing active classes first
    navLinks.forEach(link => {
        link.classList.remove('active');
    });
    
    // Find and highlight the correct active link
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href) {
            let isActive = false;
            
            // For admin.php pages with action parameters
            if (currentPath.includes('admin.php') && href.includes('admin.php')) {
                const urlParams = new URLSearchParams(currentSearch);
                const hrefParams = new URLSearchParams(href.split('?')[1] || '');
                
                const currentAction = urlParams.get('action');
                const hrefAction = hrefParams.get('action');
                
                // If no action in current URL, highlight admin dashboard
                if (!currentAction && href === 'admin.php') {
                    isActive = true;
                }
                // If action matches exactly
                else if (currentAction && hrefAction && currentAction === hrefAction) {
                    isActive = true;
                }
            }
            // For other pages, exact path match
            else if (currentPath.endsWith(href) || currentUrl.endsWith(href)) {
                isActive = true;
            }
            
            if (isActive) {
                link.classList.add('active');
            }
        }
    });
    
    // Mobile sidebar toggle
    const mobileToggle = document.getElementById('mobileSidebarToggle');
    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show-mobile');
        });
    }

    // Close mobile sidebar when clicking outside
    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('show-mobile')) {
            if (!sidebar.contains(e.target) && !mobileToggle?.contains(e.target)) {
                sidebar.classList.remove('show-mobile');
            }
        }
    });

    // Hover effects for mini mode (desktop only)
    if (window.innerWidth > 768) {
        let hoverTimeout;

        sidebar.addEventListener('mouseenter', function() {
            if (this.classList.contains('mini') && !this.classList.contains('pinned')) {
                clearTimeout(hoverTimeout);
                this.style.width = '250px';
            }
        });

        sidebar.addEventListener('mouseleave', function() {
            if (this.classList.contains('mini') && !this.classList.contains('pinned')) {
                hoverTimeout = setTimeout(() => {
                    this.style.width = '70px';
                }, 300);
            }
        });
    }

    // Window resize handler
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            // Mobile mode
            sidebar.classList.remove('mini', 'pinned');
            mainContent.classList.remove('sidebar-mini');
            sidebar.style.width = '';
        } else {
            // Desktop mode - restore saved state
            const isPinned = localStorage.getItem('sidebarPinned') === 'true';
            if (isPinned) {
                sidebar.classList.add('pinned');
                sidebar.classList.remove('mini');
                mainContent.classList.remove('sidebar-mini');
            } else {
                sidebar.classList.add('mini');
                sidebar.classList.remove('pinned');
                mainContent.classList.add('sidebar-mini');
            }
            sidebar.classList.remove('show-mobile');
        }
    });
});
