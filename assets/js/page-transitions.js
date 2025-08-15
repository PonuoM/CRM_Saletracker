/**
 * Page Transition Animations
 * อนิเมชั่นการเปลี่ยนหน้าสำหรับ CRM SalesTracker
 */

$(document).ready(function() {
    // Add subtle fade-in (reduced distance/time)
    const $pt = $('.page-transition');
    $pt.css({'opacity':'0.96','transform':'translateY(2px)'});
    requestAnimationFrame(function(){
        $pt.css({
            'opacity': '1',
            'transform': 'translateY(0)',
            'transition': 'opacity 120ms ease-out, transform 120ms ease-out'
        });
    });
    
    // Smooth page transitions for all internal links (same origin)
    $('a[href]:not([target])').on('click', function(e) {
        const href = $(this).attr('href');
        if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
        const isSameOrigin = this.origin === window.location.origin;
        if (isSameOrigin) {
            e.preventDefault();
            
            // Add fade-out animation
            $pt.css({
                'opacity': '0.0',
                'transform': 'translateY(3px)',
                'transition': 'opacity 100ms ease-in, transform 100ms ease-in'
            });
            
            // Navigate after animation
            setTimeout(function() {
                window.location.href = href;
            }, 100);
        }
    });
    
    // Smooth transitions for form submissions (skip AJAX/no-transition forms)
    $('form').on('submit', function() {
        const $f = $(this);
        if ($f.hasClass('no-transition') || $f.attr('data-transition') === 'none' || $f.hasClass('ajax-form') || $f.data('ajax') === true) {
            return; // do not fade-out for AJAX forms
        }
        $('.page-transition').css({
            'opacity': '0',
            'transform': 'translateY(-6px)',
            'transition': 'opacity 100ms ease-in, transform 100ms ease-in'
        });
    });
    
    // Remove specific-page selector; handled by global handler above
    
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
