<?php
/**
 * CRM SalesTracker - Orders List Content
 * เนื้อหาหน้ารายการคำสั่งซื้อ (เฉพาะ content)
 */

$orders = $orderList ?? [];
$roleName = $_SESSION['role_name'] ?? 'user';
$userId = $_SESSION['user_id'] ?? 0;
?>

<!-- Orders Content -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">จัดการคำสั่งซื้อ</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        
        <?php if (in_array(($roleName ?? ''), ['telesales','supervisor','admin','super_admin'])): ?>
            <a href="orders.php?action=create&guest=1" class="btn btn-primary btn-sm">
                <i class="fas fa-user-plus me-1"></i>สร้างคำสั่งซื้อ (ลูกค้าใหม่)
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">ค้นหา</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="หมายเลขคำสั่งซื้อ, ชื่อลูกค้า">
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">สถานะ</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">ทั้งหมด</option>
                            <option value="pending">รอดำเนินการ</option>
                            <option value="confirmed">ยืนยันแล้ว</option>
                            <option value="shipped">จัดส่งแล้ว</option>
                            <option value="delivered">ส่งมอบแล้ว</option>
                            <option value="cancelled">ยกเลิก</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="payment_status" class="form-label">การชำระเงิน</label>
                        <select class="form-select" id="payment_status" name="payment_status">
                            <option value="">ทั้งหมด</option>
                            <option value="pending">รอชำระ</option>
                            <option value="paid">ชำระแล้ว</option>
                            <option value="partial">ชำระบางส่วน</option>
                            <option value="cancelled">ยกเลิก</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">วันที่เริ่มต้น</label>
                        <input type="date" class="form-control" id="date_from" name="date_from">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">วันที่สิ้นสุด</label>
                        <input type="date" class="form-control" id="date_to" name="date_to">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toggle for Paid Orders Only -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="paidOnlyToggle">
                        <label class="form-check-label" for="paidOnlyToggle">
                            <i class="fas fa-money-check-alt me-1"></i>
                            แสดงเฉพาะคำสั่งซื้อที่ชำระเงินแล้ว
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="unpaidOnlyToggle">
                        <label class="form-check-label" for="unpaidOnlyToggle">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            แสดงเฉพาะคำสั่งซื้อที่ยังไม่ชำระเงิน
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>รายการคำสั่งซื้อ
                </h5>
                <span class="badge bg-primary" id="orderCount">0 รายการ</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 620px; overflow-y: auto; overflow-x: unset;">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>หมายเลขคำสั่งซื้อ</th>
                                <th>ลูกค้า</th>
                                <th>วันที่สั่งซื้อ</th>
                                <th>ยอดรวม</th>
                                <th>สถานะ</th>
                                <th>การชำระเงิน</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <!-- Orders will be loaded here via JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Loading indicator -->
                <div id="loadingIndicator" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                    <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
                </div>
                
                <!-- No data message -->
                <div id="noDataMessage" class="text-center py-4" style="display: none;">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">ไม่พบข้อมูลคำสั่งซื้อ</p>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center p-3">
                    <div class="text-muted small">แสดง 10 รายการต่อหน้า</div>
                    <ul class="pagination pagination-sm mb-0" id="orders-pagination"></ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">รายละเอียดคำสั่งซื้อ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailContent">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal removed as delete functionality is moved to edit page -->

<script>
// Orders JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10);
    let isLoading = false;
    let hasMoreData = true;
    let currentFilters = {};
    let totalPages = 1;

    // Utility: escape HTML (prevent XSS and rendering issues)
    function escapeHtml(text) {
        if (text === undefined || text === null) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    // Initialize
    loadOrders();

    // Filter form submission
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        hasMoreData = true;
        loadOrders(true);
    });

    // Paid only toggle
    document.getElementById('paidOnlyToggle').addEventListener('change', function() {
        // ถ้าเลือก paid only ให้ยกเลิก unpaid only
        if (this.checked) {
            document.getElementById('unpaidOnlyToggle').checked = false;
        }
        currentPage = 1;
        hasMoreData = true;
        loadOrders(true);
    });

    // Unpaid only toggle
    document.getElementById('unpaidOnlyToggle').addEventListener('change', function() {
        // ถ้าเลือก unpaid only ให้ยกเลิก paid only
        if (this.checked) {
            document.getElementById('paidOnlyToggle').checked = false;
        }
        currentPage = 1;
        hasMoreData = true;
        loadOrders(true);
    });

    // Render minimal pager
    function renderPager() {
        const pager = document.getElementById('orders-pagination');
        if (!pager) return;
        pager.innerHTML = '';

        const createBtn = (label, page, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.setAttribute('data-page', page);
            a.textContent = label;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (disabled) return;
                const target = parseInt(e.currentTarget.getAttribute('data-page'), 10);
                if (!isNaN(target) && target >= 1 && target <= totalPages && target !== currentPage) {
                    currentPage = target;
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', String(currentPage));
                    history.replaceState(null, '', url.toString());
                    loadOrders(true);
                }
            });
            li.appendChild(a);
            pager.appendChild(li);
        };

        // «« and «
        createBtn('««', 1, currentPage === 1);
        createBtn('«', Math.max(1, currentPage - 1), currentPage === 1);

        // Select in the middle
        const liSelect = document.createElement('li');
        liSelect.className = 'page-item';
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm page-select';
        for (let i = 1; i <= totalPages; i++) {
            const opt = document.createElement('option');
            opt.value = String(i);
            opt.textContent = String(i);
            if (i === currentPage) opt.selected = true;
            select.appendChild(opt);
        }
        select.addEventListener('change', (e) => {
            const val = parseInt(e.target.value, 10);
            if (!isNaN(val) && val >= 1 && val <= totalPages) {
                currentPage = val;
                const url = new URL(window.location.href);
                url.searchParams.set('page', String(currentPage));
                history.replaceState(null, '', url.toString());
                loadOrders(true);
            }
        });
        liSelect.appendChild(select);
        pager.appendChild(liSelect);

        // » and »»
        createBtn('»', Math.min(totalPages, currentPage + 1), currentPage === totalPages);
        createBtn('»»', totalPages, currentPage === totalPages);
    }



    function loadOrders(reset = false) {
        if (isLoading) return;
        
        isLoading = true;
        
        if (reset) {
            document.getElementById('ordersTableBody').innerHTML = '';
        }
        
        document.getElementById('loadingIndicator').style.display = 'block';
        document.getElementById('noDataMessage').style.display = 'none';

        // Collect filter data
        const formData = new FormData(document.getElementById('filterForm'));
        const paidOnly = document.getElementById('paidOnlyToggle').checked;
        const unpaidOnly = document.getElementById('unpaidOnlyToggle').checked;

        const params = new URLSearchParams();
        params.append('page', currentPage);
        params.append('limit', 10); // 10 ต่อหน้า
        params.append('paid_only', paidOnly ? '1' : '0');
        params.append('unpaid_only', unpaidOnly ? '1' : '0');

        for (let [key, value] of formData.entries()) {
            if (value) params.append(key, value);
        }

        fetch(`orders.php?action=list&${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (reset || currentPage === 1) {
                        document.getElementById('ordersTableBody').innerHTML = '';
                    }
                    
                    if (data.orders.length > 0) {
                        appendOrdersToTable(data.orders);
                        totalPages = data.total_pages || 1;
                        renderPager();
                    } else if (currentPage === 1) {
                        document.getElementById('noDataMessage').style.display = 'block';
                    }

                    document.getElementById('orderCount').textContent = `${data.total} รายการ`;
                } else {
                    console.error('Error loading orders:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            })
            .finally(() => {
                isLoading = false;
                document.getElementById('loadingIndicator').style.display = 'none';
            });
    }

    function appendOrdersToTable(orders) {
        const tbody = document.getElementById('ordersTableBody');

        orders.forEach(order => {
            const row = createOrderRow(order);
            tbody.appendChild(row);
        });

        // Attach inline dropdown handlers
        document.querySelectorAll('select[data-field]')
            .forEach(sel => {
                sel.addEventListener('change', function() {
                    const orderId = this.dataset.orderId;
                    const field = this.dataset.field;
                    const value = this.value;
                    fetch('orders.php?action=update_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order_id: orderId, field, value })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success) { alert('อัปเดตไม่สำเร็จ'); return; }
                        // Update badge color/text inline
                        if (field === 'payment_status') {
                            const badge = document.getElementById('payment-badge-' + orderId);
                            if (badge) {
                                const color = ({ pending: 'warning', paid: 'success', partial: 'info', cancelled: 'danger', returned: 'secondary' })[value] || 'warning';
                                const text = ({ pending: 'รอชำระ', paid: 'ชำระแล้ว', partial: 'ชำระบางส่วน', cancelled: 'ยกเลิก', returned: 'ตีกลับ' })[value] || 'รอชำระ';
                                badge.className = 'badge bg-' + color + ' text-dark';
                                badge.textContent = text;
                            }
                        } else if (field === 'delivery_status') {
                            const badge = document.getElementById('status-badge-' + orderId);
                            if (badge) {
                                const color = ({ pending: 'warning', confirmed: 'info', shipped: 'primary', delivered: 'success', cancelled: 'danger' })[value] || 'warning';
                                const text = ({ pending: 'รอดำเนินการ', confirmed: 'ยืนยันแล้ว', shipped: 'จัดส่งแล้ว', delivered: 'ส่งมอบแล้ว', cancelled: 'ยกเลิก' })[value] || 'รอดำเนินการ';
                                badge.className = 'badge bg-' + color + ' text-dark';
                                badge.textContent = text;
                            }
                        }
                    })
                    .catch(() => alert('เกิดข้อผิดพลาดในการเชื่อมต่อ'));
                });
            });


    }

    function createOrderRow(order) {
        const row = document.createElement('tr');
        
        // Status badge colors
        const statusColors = {
            'pending': 'warning',
            'confirmed': 'info',
            'shipped': 'primary',
            'delivered': 'success',
            'cancelled': 'danger'
        };

        const paymentColors = {
            'pending': 'warning',
            'paid': 'success',
            'partial': 'info',
            'cancelled': 'danger',
            'returned': 'secondary'
        };

        // Ensure we have valid status values
        const deliveryStatus = order.delivery_status || 'pending';
        const paymentStatus = order.payment_status || 'pending';

        row.innerHTML = `
            <td>
                <a href="orders.php?action=show&id=${order.order_id}&page=${currentPage}" class="text-decoration-none">
                    ${order.order_number || order.orders_number || 'N/A'}
                </a>
            </td>
            <td>${order.customer_name || 'N/A'}</td>
            <td>${(order.order_date ? new Date(order.order_date) : new Date(order.created_at)).toLocaleDateString('th-TH-u-ca-gregory')}</td>
            <td>฿${parseFloat(order.net_amount || order.total_amount || 0).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
            <td>
                <span id="status-badge-${order.order_id}" class="badge bg-${statusColors[deliveryStatus] || 'warning'} text-dark">${getStatusText(deliveryStatus)}</span>
            </td>
            <td>
                <span id="payment-badge-${order.order_id}" class="badge bg-${paymentColors[paymentStatus] || 'warning'} text-dark">${getPaymentStatusText(paymentStatus)}</span>
            </td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <!-- Action Buttons -->
                    <div class="btn-group btn-group-sm">
                        <a href="orders.php?action=show&id=${order.order_id}&page=${currentPage}" class="btn btn-outline-primary btn-sm" title="ดูรายละเอียด">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="orders.php?action=edit&id=${order.order_id}" class="btn btn-outline-warning btn-sm" title="แก้ไข">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                </div>
            </td>
        `;
        
        return row;
    }

    function getStatusText(status) {
        const statusTexts = {
            'pending': 'รอดำเนินการ',
            'confirmed': 'ยืนยันแล้ว',
            'shipped': 'จัดส่งแล้ว',
            'delivered': 'ส่งมอบแล้ว',
            'cancelled': 'ยกเลิก'
        };
        return statusTexts[status] || 'รอดำเนินการ';
    }

        function getPaymentStatusText(status) {
        const paymentTexts = {
            'pending': 'รอชำระ',
            'paid': 'ชำระแล้ว',
            'partial': 'ชำระบางส่วน',
                'cancelled': 'ยกเลิก',
                'returned': 'ตีกลับ'
        };
        return paymentTexts[status] || 'รอชำระ';
    }

    // Global functions - Delete functions removed as delete button is removed

    // removed legacy updatePaymentStatus()

    window.exportOrders = function() {
        const params = new URLSearchParams();
        const formData = new FormData(document.getElementById('filterForm'));
        const paidOnly = document.getElementById('paidOnlyToggle').checked;

        params.append('paid_only', paidOnly ? '1' : '0');

        for (let [key, value] of formData.entries()) {
            if (value) params.append(key, value);
        }

        window.location.href = `orders.php?action=export&${params.toString()}`;
    };
});
</script>
