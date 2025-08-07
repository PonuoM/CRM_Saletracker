/**
 * Sidebar JavaScript
 * จัดการ sidebar functionality และ navigation
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle functionality
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('main');
    
    if (sidebarToggle && sidebar && mainContent) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
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
    const mobileToggle = document.querySelector('.mobile-sidebar-toggle');
    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show-mobile');
        });
    }
    
    // Close mobile sidebar when clicking outside
    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('show-mobile')) {
            if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                sidebar.classList.remove('show-mobile');
            }
        }
    });
});
