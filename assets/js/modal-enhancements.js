/**
 * Modal Enhancements JavaScript
 * สำหรับปรับปรุง popup ในหน้าลูกค้า
 */

// ฟังก์ชันแสดง modal ที่ปรับปรุงแล้ว
function showEnhancedModal(title, bodyHtml, options = {}) {
    // Clean up any existing backdrops first
    cleanupModalBackdrops();
    
    const id = options.id || 'enhancedModal';
    let modal = document.getElementById(id);
    
    if (!modal) {
        modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = id;
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('aria-labelledby', id + 'Label');
        modal.setAttribute('aria-hidden', 'true');
        
        const size = options.size || 'lg';
        const headerColor = options.headerColor || 'white';
        const showFooter = options.showFooter !== false;
        
        modal.innerHTML = `
            <div class="modal-dialog modal-${size} modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #ffffff; color: #333333; border-bottom: 1px solid #dee2e6;">
                        <h5 class="modal-title" id="${id}Label">
                            <i class="${options.icon || 'fas fa-info-circle'} me-2"></i>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4"></div>
                    ${showFooter ? `
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>ปิด
                        </button>
                    </div>
                    ` : ''}
                </div>
            </div>`;
        
        document.body.appendChild(modal);
        
        // Add event listeners for proper cleanup
        modal.addEventListener('hidden.bs.modal', function() {
            setTimeout(cleanupModalBackdrops, 100);
        });
        
        modal.addEventListener('hide.bs.modal', function() {
            cleanupModalBackdrops();
        });
    }
    
    // Set title and content
    const titleElement = modal.querySelector('.modal-title');
    titleElement.innerHTML = `<i class="${options.icon || 'fas fa-info-circle'} me-2"></i>${title}`;
    modal.querySelector('.modal-body').innerHTML = bodyHtml;
    
    // Show modal with proper centering and backdrop management
    const bsModal = new bootstrap.Modal(modal, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    
    // เพิ่ม event listeners สำหรับจัดการ backdrop
    modal.addEventListener('shown.bs.modal', function() {
        // ตรวจสอบว่า backdrop ถูกสร้างแล้ว
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.style.zIndex = '1040';
            backdrop.style.position = 'fixed';
            backdrop.style.top = '0';
            backdrop.style.left = '0';
            backdrop.style.width = '100vw';
            backdrop.style.height = '100vh';
        }
        
        // ป้องกัน scroll
        document.body.classList.add('modal-open');
    });
    
    modal.addEventListener('hidden.bs.modal', function() {
        // ลบ backdrop และเปิด scroll กลับ
        cleanupModalBackdrops();
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });
    
    bsModal.show();
    
    return modal;
}

// ฟังก์ชันแสดง modal สำหรับรายการสินค้า
function showOrderItemsModal(orderId) {
    // แสดง loading state
    const loadingHtml = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">กำลังโหลด...</span>
            </div>
            <p class="mt-2 text-muted">กำลังโหลดรายการสินค้า...</p>
        </div>
    `;
    
    const modal = showEnhancedModal('รายละเอียดสินค้า', loadingHtml, {
        id: 'orderItemsModal',
        size: 'lg',
        headerColor: 'white',
        icon: 'fas fa-shopping-cart'
    });
    
    // โหลดข้อมูล
    fetch('orders.php?action=items&id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'ไม่สามารถโหลดรายการสินค้า');
            }
            
            const items = data.items || [];
            const orderNumber = data.order.order_number || orderId;
            
            if (items.length === 0) {
                const emptyHtml = `
                    <div class="text-center py-4">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <p class="text-muted">ไม่มีรายการสินค้าในคำสั่งซื้อนี้</p>
                    </div>
                `;
                modal.querySelector('.modal-body').innerHTML = emptyHtml;
                return;
            }
            
            const rows = items.map(item => `
                <tr>
                    <td>${escapeHtml(item.product_name || 'ไม่ทราบสินค้า')}</td>
                    <td>${escapeHtml(item.product_code || '')}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-end">฿${Number(item.unit_price).toLocaleString('th-TH', {minimumFractionDigits:2})}</td>
                    <td class="text-end">฿${Number(item.total_price).toLocaleString('th-TH', {minimumFractionDigits:2})}</td>
                </tr>
            `).join('');
            
            const tableHtml = `
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>สินค้า</th>
                                <th>รหัส</th>
                                <th class="text-center">จำนวน</th>
                                <th class="text-end">ราคาต่อหน่วย</th>
                                <th class="text-end">รวม</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            `;
            
            modal.querySelector('.modal-body').innerHTML = tableHtml;
            
            // อัปเดต title
            const titleElement = modal.querySelector('.modal-title');
            titleElement.innerHTML = `<i class="fas fa-shopping-cart me-2"></i>รายละเอียดสินค้าในคำสั่งซื้อ #${orderNumber}`;
        })
        .catch(error => {
            console.error('Error loading order items:', error);
            const errorHtml = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <p class="text-danger mb-2">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>
                    <small class="text-muted d-block mb-3">${error.message}</small>
                    <button class="btn btn-outline-primary" onclick="showOrderItemsModal(${orderId})">
                        <i class="fas fa-redo me-1"></i>ลองใหม่
                    </button>
                </div>
            `;
            modal.querySelector('.modal-body').innerHTML = errorHtml;
        });
}

// ฟังก์ชัน escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = String(text || '');
    return div.innerHTML;
}

// ฟังก์ชันทำความสะอาด modal backdrop
function cleanupModalBackdrops() {
    // ลบ backdrop ที่เหลืออยู่ทั้งหมด
    const backdrops = document.querySelectorAll('.modal-backdrop');
    let removedCount = 0;
    
    backdrops.forEach(backdrop => {
        // เพิ่ม fade out animation ก่อนลบ
        backdrop.classList.add('fade');
        backdrop.classList.remove('show');
        
        // ลบหลังจาก animation เสร็จ
        setTimeout(() => {
            if (backdrop.parentNode) {
                backdrop.remove();
            }
        }, 300);
        
        removedCount++;
    });
    
    // ลบ body class ที่ Bootstrap เพิ่มเข้ามา
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // แสดง log ถ้ามีการลบ backdrop
    if (removedCount > 0) {
        console.log(`Cleaned up ${removedCount} modal backdrop(s)`);
    }
    
    return removedCount;
}

// ฟังก์ชันทำความสะอาดแบบ aggressive สำหรับกรณีที่ backdrop ยังคงอยู่
function forceCleanupBackdrops() {
    // ลบ backdrop ทั้งหมด
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        backdrop.remove();
    });
    
    // ลบ body classes และ styles ทั้งหมด
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // ลบ backdrop ที่อาจซ่อนอยู่
    const hiddenBackdrops = document.querySelectorAll('.modal-backdrop[style*="display: none"]');
    hiddenBackdrops.forEach(backdrop => {
        backdrop.remove();
    });
    
    // ตรวจสอบและลบ backdrop ที่เหลืออยู่
    setTimeout(() => {
        const remainingBackdrops = document.querySelectorAll('.modal-backdrop');
        remainingBackdrops.forEach(backdrop => {
            backdrop.remove();
        });
    }, 100);
}

// เพิ่ม global functions
window.showEnhancedModal = showEnhancedModal;
window.showOrderItemsModal = showOrderItemsModal;
window.cleanupModalBackdrops = cleanupModalBackdrops;
window.forceCleanupBackdrops = forceCleanupBackdrops;

// Initialize modal enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Clean up backdrops when modals are hidden
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            setTimeout(cleanupModalBackdrops, 100);
        });
        
        modal.addEventListener('hide.bs.modal', function() {
            // Clean up immediately when hiding starts
            cleanupModalBackdrops();
        });
    });
    
    // Global backdrop cleanup on page load
    cleanupModalBackdrops();
    
    // Clean up backdrops every few seconds as a safety measure
    setInterval(cleanupModalBackdrops, 5000);
    
    // เพิ่ม keyboard shortcut สำหรับทำความสะอาด backdrop (Ctrl+Shift+B)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'B') {
            e.preventDefault();
            forceCleanupBackdrops();
            console.log('Backdrop cleanup triggered by keyboard shortcut');
        }
    });
    
    // เพิ่ม click handler สำหรับ backdrop ที่อาจเหลืออยู่
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            e.target.remove();
            cleanupModalBackdrops();
        }
    });
    
    // เพิ่ม error handler สำหรับ modal events
    window.addEventListener('error', function(e) {
        if (e.message.includes('modal') || e.message.includes('backdrop')) {
            setTimeout(forceCleanupBackdrops, 100);
        }
    });
});

// Override Bootstrap modal hide to ensure proper cleanup
if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
    const originalHide = bootstrap.Modal.prototype.hide;
    bootstrap.Modal.prototype.hide = function() {
        const result = originalHide.call(this);
        setTimeout(cleanupModalBackdrops, 100);
        return result;
    };
}
