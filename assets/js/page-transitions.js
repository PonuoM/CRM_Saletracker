/**
 * Page Transition Animations
 * อนิเมชั่นการเปลี่ยนหน้าสำหรับ CRM SalesTracker
 */

$(document).ready(function() {
    // Add fade-in animation to main content
    $('.page-transition').addClass('fadeIn');
    
    // Smooth page transitions for all admin links
    $('a[href*="admin.php"]').on('click', function(e) {
        const href = $(this).attr('href');
        if (href && !href.includes('#')) {
            e.preventDefault();
            
            // Add fade-out animation
            $('.page-transition').css({
                'opacity': '0',
                'transform': 'translateY(-10px)',
                'transition': 'all 0.2s ease-out'
            });
            
            // Navigate after animation
            setTimeout(function() {
                window.location.href = href;
            }, 200);
        }
    });
    
    // Smooth transitions for form submissions
    $('form').on('submit', function() {
        $('.page-transition').css({
            'opacity': '0',
            'transform': 'translateY(-10px)',
            'transition': 'all 0.2s ease-out'
        });
    });
    
    // Smooth transitions for other navigation links
    $('a[href*="dashboard.php"], a[href*="customers.php"], a[href*="orders.php"], a[href*="reports.php"], a[href*="import-export.php"]').on('click', function(e) {
        const href = $(this).attr('href');
        if (href && !href.includes('#')) {
            e.preventDefault();
            
            // Add fade-out animation
            $('.page-transition').css({
                'opacity': '0',
                'transform': 'translateY(-10px)',
                'transition': 'all 0.2s ease-out'
            });
            
            // Navigate after animation
            setTimeout(function() {
                window.location.href = href;
            }, 200);
        }
    });
    
    // Add hover effects to buttons
    $('.btn').hover(
        function() {
            $(this).css({
                'transform': 'translateY(-2px)',
                'transition': 'all 0.2s ease'
            });
        },
        function() {
            $(this).css({
                'transform': 'translateY(0)',
                'transition': 'all 0.2s ease'
            });
        }
    );
    
    // Add hover effects to cards
    $('.card').hover(
        function() {
            $(this).css({
                'transform': 'translateY(-3px)',
                'box-shadow': '0 8px 25px rgba(0,0,0,0.15)',
                'transition': 'all 0.3s ease'
            });
        },
        function() {
            $(this).css({
                'transform': 'translateY(0)',
                'box-shadow': '0 4px 6px -1px rgba(0,0,0,0.1)',
                'transition': 'all 0.3s ease'
            });
        }
    );
    
    // Add loading animation for DataTables
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.dataTables_wrapper').on('processing.dt', function(e, settings, processing) {
            if (processing) {
                $(this).find('.dataTables_processing').addClass('loading');
            } else {
                $(this).find('.dataTables_processing').removeClass('loading');
            }
        });
    }
});

// Global function for page transitions
function navigateWithTransition(url) {
    $('.page-transition').css({
        'opacity': '0',
        'transform': 'translateY(-10px)',
        'transition': 'all 0.2s ease-out'
    });
    
    setTimeout(function() {
        window.location.href = url;
    }, 200);
}
