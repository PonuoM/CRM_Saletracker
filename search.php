<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check user role - only allow supervisor, telesales, admin, super_admin
$allowedRoles = ['supervisor', 'telesales', 'admin', 'super_admin'];
if (!isset($_SESSION['role_name']) || !in_array($_SESSION['role_name'], $allowedRoles)) {
    header("Location: dashboard.php");
    exit;
}

require_once 'config/config.php';
require_once 'app/core/Database.php';

// Get user's company id
function getCurrentCompanyId() {
    try {
        $db = new Database();
        $user = $db->fetchOne("
            SELECT u.*, c.company_name
            FROM users u 
            LEFT JOIN companies c ON u.company_id = c.company_id 
            WHERE u.user_id = ?
        ", [$_SESSION['user_id']]);
        
        if (!$user || !$user['company_id']) {
            return null;
        }
        return (int)$user['company_id'];
    } catch (Exception $e) {
        error_log("Error getting company id: " . $e->getMessage());
        return null; // Default fallback
    }
}

$companyId = getCurrentCompanyId();
// Backward compatibility shim for legacy references
$userSource = $companyId;

// Handle AJAX search request
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    header('Content-Type: application/json');
    
    try {
        $term = $_GET['term'] ?? '';
        if (empty($term)) {
            echo json_encode(['success' => false, 'message' => 'ไม่มีคำค้นหา']);
            exit;
        }
        
        $db = new Database();
        
        // Search query with user's company
        $sql = "
            SELECT 
                c.customer_id,
                c.customer_code,
                CONCAT(c.first_name, ' ', c.last_name) as full_name,
                c.phone,
                c.email,
                c.customer_grade,
                c.total_purchase_amount,
                c.basket_type,
                u.full_name AS assigned_to_name,
                COUNT(DISTINCT o.order_id) as total_orders
            FROM customers c
            LEFT JOIN orders o ON c.customer_id = o.customer_id AND o.is_active = 1
            LEFT JOIN users u ON c.assigned_to = u.user_id
            WHERE c.is_active = 1 AND c.company_id = ? AND (
                CONCAT(c.first_name, ' ', c.last_name) LIKE ? 
                OR c.phone LIKE ?
                OR c.first_name LIKE ?
                OR c.last_name LIKE ?
            ) 
            GROUP BY c.customer_id
            ORDER BY c.total_purchase_amount DESC, c.updated_at DESC
            LIMIT 20
        ";
        
        $searchTerm = "%{$term}%";
        $params = [$companyId, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
        
        $results = $db->fetchAll($sql, $params);
        
        if (!empty($results)) {
            echo json_encode([
                'success' => true,
                'customers' => $results,
                'total' => count($results),
                'message' => 'พบข้อมูล ' . count($results) . ' รายการ'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'customers' => [],
                'total' => 0,
                'message' => 'ไม่พบข้อมูลการขาย'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            'customers' => []
        ]);
    }
    
    exit;
}

// Handle customer details request  
if (isset($_GET['action']) && $_GET['action'] === 'customer_details') {
    header('Content-Type: application/json');
    
    try {
        $customerId = $_GET['customer_id'] ?? 0;
        
        $db = new Database();
        
        // Check if customer exists and belongs to user's company
        $customer = $db->fetchOne("
            SELECT customer_id, CONCAT(first_name, ' ', last_name) as name
            FROM customers 
WHERE customer_id = ? AND company_id = ? AND is_active = 1
        ", [$customerId, $companyId]);
        
        if (!$customer) {
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบลูกค้าหรือไม่มีสิทธิ์เข้าถึง',
                'orders' => []
            ]);
            exit;
        }
        
        // Get customer orders
        $orders = $db->fetchAll("
            SELECT 
                o.order_id,
                o.order_number,
                o.order_date,
                o.total_amount,
                o.net_amount,
                o.payment_status,
                o.delivery_status,
                o.created_by,
                COALESCE(users.full_name, CONCAT('User ', o.created_by)) as seller_name
            FROM orders o
            JOIN customers c ON o.customer_id = c.customer_id
            LEFT JOIN users ON o.created_by = users.user_id
            WHERE o.customer_id = ? AND c.company_id = ? AND o.is_active = 1
            ORDER BY o.order_date DESC
        ", [$customerId, $companyId]);
        
        if (!empty($orders)) {
            echo json_encode([
                'success' => true,
                'customer' => $customer,
                'orders' => $orders,
                'message' => 'พบข้อมูล ' . count($orders) . ' รายการ'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'customer' => $customer,
                'orders' => [],
                'message' => 'ไม่มีประวัติการสั่งซื้อ'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            'orders' => []
        ]);
    }
    
    exit;
}

// Handle order details modal request
if (isset($_GET['action']) && $_GET['action'] === 'order_details') {
    header('Content-Type: application/json');
    
    try {
        $orderId = $_GET['order_id'] ?? 0;
        
        $db = new Database();
        
        // Get order details with customer verification
        $order = $db->fetchOne("
            SELECT o.*, 
                   CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                   users.full_name as seller_name
            FROM orders o
            JOIN customers c ON o.customer_id = c.customer_id
            LEFT JOIN users ON o.created_by = users.user_id
WHERE o.order_id = ? AND c.company_id = ? AND o.is_active = 1
        ", [$orderId, $userSource]);
        
        if (!$order) {
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบข้อมูลออเดอร์หรือไม่มีสิทธิ์เข้าถึง'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'order' => $order
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค้นหาลูกค้า - CRM System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    
    <style>
    /* Force customer container visibility */
    #customerContainer {
        opacity: 1 !important;
        visibility: visible !important;
        position: relative !important;
        z-index: 1 !important;
        background: transparent !important;
        min-height: auto !important;
        transform: none !important;
        transition: none !important;
    }
    
    #customerContainer.show,
    #customerContainer[style*="display: block"] {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Force cards visibility */
    #customerContainer .card {
        opacity: 1 !important;
        visibility: visible !important;
        display: block !important;
        background: white !important;
        border: 1px solid #dee2e6 !important;
        min-height: 50px !important;
    }
    
    /* Force table visibility */
    #customerContainer .table-responsive,
    #customerContainer .table {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Fix main-content layout issues */
    .search-page-body .main-content {
        margin-left: 250px !important;
        padding: 1rem !important;
        position: relative !important;
        z-index: 1 !important;
        background: #f8f9fa !important;
        min-height: 100vh !important;
        transition: none !important;
        transform: none !important;
        opacity: 1 !important;
    }
    
    .search-page-body .container-fluid {
        position: relative !important;
        z-index: 2 !important;
        background: transparent !important;
        max-width: none !important;
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    
    /* Customer details compact styling */
    #customerDetails .row .col-md-2 {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }

    /* Customer details inline full-width layout */
    #customerDetails .customer-inline {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        width: 100%;
    }
    #customerDetails .customer-inline > div {
        flex: 1 1 0;
        min-width: 120px;
    }
    
    #customerDetails small.text-muted {
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    #customerDetails strong {
        font-size: 0.9rem;
        display: block;
        margin-top: 2px;
    }
    
    /* Responsive adjustments for mobile */
    @media (max-width: 768px) {
        .search-page-body .main-content {
            margin-left: 0 !important;
            padding: 0.5rem !important;
        }
        
        .search-page-body .container-fluid {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        
        #customerContainer .row {
            margin: 0 !important;
        }
        
        #customerDetails .col-md-2 {
            flex: 0 0 50% !important;
            max-width: 50% !important;
            margin-bottom: 1rem !important;
        }
    }
    
    /* Fix modal backdrop issues - backdrop must be BEHIND modal */
    .modal-backdrop {
        z-index: 1040 !important;
        opacity: 0.5 !important;
    }
    
    .modal-backdrop.fade {
        opacity: 0.5 !important;
        z-index: 1040 !important;
    }
    
    .modal-backdrop.show {
        opacity: 0.5 !important;
        z-index: 1040 !important;
    }
    
    /* Modal must be ABOVE backdrop */
    .modal {
        z-index: 1050 !important;
    }
    
    .modal-dialog {
        z-index: 1051 !important;
        position: relative;
    }
    
    .modal-content {
        z-index: 1052 !important;
        position: relative;
    }
    
    /* Debug elements removed - search working properly */
    </style>
</head>

<body class="search-page-body fonts-loading">
    <?php
    $content = ob_start();
    ?>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Search Form -->
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="text-center mb-3">
                                    <h4 class="text-dark mb-2">ค้นหาลูกค้า</h4>
                                    <p class="text-muted mb-0">ระบบค้นหาข้อมูลลูกค้าและประวัติการสั่งซื้อ - บริษัท: <?php echo htmlspecialchars($userSource); ?></p>
                                </div>
                                <form id="searchForm">
                                    <div class="row g-3">
                                        <div class="col-md-9">
                                            <label for="searchTerm" class="form-label fw-semibold">
                                                <i class="fas fa-search me-2 text-primary"></i>ค้นหาด้วยชื่อหรือเบอร์โทร
                                            </label>
                                            <input type="text" class="form-control form-control-lg border-0 shadow-sm" id="searchTerm" 
                                                   placeholder="กรุณาใส่ชื่อลูกค้าหรือเบอร์โทรศัพท์..." required
                                                   style="background: #f8f9fa;">
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">
                                                <i class="fas fa-search me-2"></i>ค้นหา
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div id="loadingCard" class="card shadow-sm mb-4" style="display: none;">
                    <div class="card-body text-center py-4">
                        <div class="spinner-border text-primary mb-3"></div>
                        <h6>กำลังค้นหา...</h6>
                    </div>
                </div>

                <!-- No Results -->
                <div id="noResultsCard" class="card shadow-sm mb-4" style="display: none;">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-search-minus text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">ไม่พบข้อมูล</h5>
                    </div>
                </div>

                <!-- Customer Results -->
                <div id="customerContainer" style="display: none; opacity: 1 !important; visibility: visible !important; position: relative !important; z-index: 1 !important;">
                    <!-- Customer Details (Top Section) -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light border-0">
                                    <h5 class="mb-0 text-dark">
                                        <i class="fas fa-user me-2 text-primary"></i>ข้อมูลลูกค้า
                                    </h5>
                                </div>
                                <div class="card-body p-4">
                                    <div id="customerDetails"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order History (Full Width Bottom Section) -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light border-0">
                                    <h5 class="mb-0 text-dark">
                                        <i class="fas fa-shopping-cart me-2 text-primary"></i>ประวัติการสั่งซื้อ
                                    </h5>
                                </div>
                                <div class="card-body p-4">
                                    <div id="orderHistory">
                                        <div class="text-center py-3">
                                            <i class="fas fa-box text-muted" style="font-size: 2rem;"></i>
                                            <p class="text-muted mt-2">ไม่มีประวัติการสั่งซื้อ</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">รายละเอียดออเดอร์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <?php
    $content = ob_get_clean();
    
    // Include the main layout
    $pageTitle = 'ค้นหาลูกค้า';
    $bodyClass = 'search-page-body';
    include 'app/views/layouts/main.php';
    ?>

    <script>
    // Search functionality initialized
    
    document.addEventListener('DOMContentLoaded', function() {
        // Clean up any stray modal backdrops on page load
        function cleanupModalBackdrops() {
            // Remove all modal backdrops
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                backdrop.remove();
            });
            
            // Also remove modal-open class from body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            document.body.style.marginRight = '';
            
            // Force remove any remaining backdrop elements
            setTimeout(() => {
                const remainingBackdrops = document.querySelectorAll('.modal-backdrop, .fade.show');
                remainingBackdrops.forEach(element => {
                    if (element.classList.contains('modal-backdrop')) {
                        element.remove();
                    }
                });
            }, 100);
        }
        
        // Clean up on page load
        cleanupModalBackdrops();
        
        // Global click handler to detect and fix backdrop issues
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-backdrop')) {
                // If clicking on backdrop, ensure it's behind modal
                e.target.style.zIndex = '1040';
                
                // Check if modal is properly positioned
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    modal.style.zIndex = '1050';
                });
            }
        });
        
        const searchForm = document.getElementById('searchForm');
        
        // Auto-search for URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const testTerm = urlParams.get('test');
        if (testTerm) {
            document.getElementById('searchTerm').value = testTerm;
            setTimeout(() => {
                searchForm.dispatchEvent(new Event('submit'));
            }, 500);
        }
        
        searchForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const term = document.getElementById('searchTerm').value.trim();
            if (!term) {
                alert('กรุณาใส่คำที่ต้องการค้นหา');
                return;
            }
            
            // Show loading
            showLoading();
            hideResults();
            
            try {
                const response = await fetch(`search.php?action=search&term=${encodeURIComponent(term)}`);
                const data = await response.json();
                
                hideLoading();
                
                if (data.success && data.customers && data.customers.length > 0) {
                    // Clean up any modal backdrops before displaying results
                    cleanupModalBackdrops();
                    displaySearchResults(data.customers);
                } else {
                    showNoResults();
                }
            } catch (error) {
                console.error('Search error:', error);
                hideLoading();
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        });
        
        function showLoading() {
            document.getElementById('loadingCard').style.display = 'block';
        }
        
        function hideLoading() {
            document.getElementById('loadingCard').style.display = 'none';
        }
        
        function showNoResults() {
            document.getElementById('noResultsCard').style.display = 'block';
        }
        
        function hideResults() {
            document.getElementById('noResultsCard').style.display = 'none';
            document.getElementById('customerContainer').style.display = 'none';
        }
        
        function displaySearchResults(customers) {
            if (customers.length === 1) {
                displayCustomerDetails(customers[0]);
            } else {
                displayCustomerList(customers);
            }
        }
        
        function displayCustomerList(customers) {
            const customerDetails = document.getElementById('customerDetails');
            
            // keep last results for selection with full fields
            window._lastSearchCustomers = customers;
            
            let html = '<h6 class="mb-3">พบลูกค้าหลายรายการ กรุณาเลือก:</h6>';
            html += '<div class="list-group">';
            
            customers.forEach(customer => {
                html += `
                    <button type="button" class="list-group-item list-group-item-action" 
                            onclick="selectCustomer(${customer.customer_id})">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">${customer.full_name}</h6>
                            <small>รหัส: ${customer.customer_code}</small>
                        </div>
                        <p class="mb-1">${customer.phone}</p>
                        <small>ยอดซื้อ: ${parseInt(customer.total_purchase_amount || 0).toLocaleString()} บาท</small>
                    </button>
                `;
            });
            
            html += '</div>';
            customerDetails.innerHTML = html;
            document.getElementById('customerContainer').style.display = 'block';
        }
        
        function displayCustomerDetails(customer) {
            const customerDetails = document.getElementById('customerDetails');
            customerDetails.innerHTML = `
                <div class="customer-inline">
                    <div>
                        <small class="text-muted">ชื่อ:</small><br>
                        <strong>${customer.full_name || 'ไม่ระบุ'}</strong>
                    </div>
                    <div>
                        <small class="text-muted">รหัสลูกค้า:</small><br>
                        <strong>${customer.customer_code || 'ไม่ระบุ'}</strong>
                    </div>
                    <div>
                        <small class="text-muted">เบอร์โทร:</small><br>
                        <strong>${customer.phone || 'ไม่ระบุ'}</strong>
                    </div>
                    <div>
                        <small class="text-muted">เกรดลูกค้า:</small><br>
                        <span class="badge ${getGradeBadgeClass(customer.customer_grade)}">
                            ${customer.customer_grade || 'D'}
                        </span>
                    </div>
                    <div>
                        <small class="text-muted">ยอดซื้อรวม:</small><br>
                        <strong class="text-success">${parseInt(customer.total_purchase_amount || 0).toLocaleString()} บาท</strong>
                    </div>
                    <div>
                        <small class="text-muted">จำนวนครั้งที่ซื้อ:</small><br>
                        <strong class="text-info">${customer.total_orders || 0} ครั้ง</strong>
                    </div>
                    <div>
                        <small class="text-muted">ผู้ดูแลปัจจุบัน:</small><br>
                        <strong>${(customer.basket_type === 'assigned' && customer.assigned_to_name) ? customer.assigned_to_name : 'ไม่มี (ตะกร้ารอ/พร้อมแจก)'}</strong>
                    </div>
                </div>
            `;
            
            const customerContainer = document.getElementById('customerContainer');
            customerContainer.style.display = 'block';
            customerContainer.style.opacity = '1';
            customerContainer.style.visibility = 'visible';
            customerContainer.style.position = 'relative';
            customerContainer.style.zIndex = '1';
            customerContainer.classList.add('show');
            
            // Force all child elements to be visible
            const cards = customerContainer.querySelectorAll('.card');
            cards.forEach((card) => {
                card.style.display = 'block';
                card.style.opacity = '1';
                card.style.visibility = 'visible';
            });
            
            loadOrderHistory(customer.customer_id);
        }
        
        async function loadOrderHistory(customerId) {
            try {
                const response = await fetch(`search.php?action=customer_details&customer_id=${customerId}`);
                const data = await response.json();
                
                if (data.success && data.orders && data.orders.length > 0) {
                    displayOrderHistory(data.orders);
                } else {
                    
                    let debugInfo = '';
                    if (data.debug) {
                        debugInfo = `<small class="text-muted">Debug: ${JSON.stringify(data.debug)}</small>`;
                    }
                    
                    document.getElementById('orderHistory').innerHTML = `
                        <div class="text-center py-3">
                            <i class="fas fa-box text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">ไม่มีประวัติการสั่งซื้อ</p>
                            ${debugInfo}
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading order history:', error);
                document.getElementById('orderHistory').innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ไม่สามารถโหลดประวัติการสั่งซื้อได้: ${error.message}
                    </div>
                `;
            }
        }
        
        function displayOrderHistory(orders) {
            let html = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>รหัสออเดอร์</th>
                                <th>เลขที่ออเดอร์</th>
                                <th>วันที่</th>
                                <th>ผู้ขาย</th>
                                <th>ยอดรวม</th>
                                <th>สถานะการชำระ</th>
                                <th>สถานะการส่ง</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            orders.forEach(order => {
                html += `
                    <tr>
                        <td><strong>${order.order_id}</strong></td>
                        <td><small class="text-muted">${order.order_number || 'ไม่ระบุ'}</small></td>
                        <td>${formatDate(order.order_date)}</td>
                        <td>${order.seller_name || 'ไม่ระบุ'}</td>
                        <td><strong>${parseInt(order.total_amount || 0).toLocaleString()} บาท</strong></td>
                        <td>
                            <span class="badge ${getPaymentStatusClass(order.payment_status)}" style="font-size: 10px;">
                                ${getPaymentStatusText(order.payment_status)}
                            </span>
                        </td>
                        <td>
                            <span class="badge ${getDeliveryStatusClass(order.delivery_status)}" style="font-size: 10px;">
                                ${getDeliveryStatusText(order.delivery_status)}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="showOrderDetails(${order.order_id})">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            document.getElementById('orderHistory').innerHTML = html;
        }
        
        // Order details modal
        async function showOrderDetails(orderId) {
            try {
                // Clean up any existing modal backdrops first
                cleanupModalBackdrops();
                
                const response = await fetch(`search.php?action=order_details&order_id=${orderId}`);
                const data = await response.json();
                
                if (data.success && data.order) {
                    const order = data.order;
                    document.getElementById('orderDetailsContent').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>ข้อมูลออเดอร์</h6>
                                <p><strong>รหัสออเดอร์:</strong> ${order.order_id}</p>
                                <p><strong>เลขที่ออเดอร์:</strong> ${order.order_number || 'ไม่ระบุ'}</p>
                                <p><strong>วันที่สั่ง:</strong> ${formatDate(order.order_date)}</p>
                                <p><strong>ลูกค้า:</strong> ${order.customer_name}</p>
                                <p><strong>ผู้ขาย:</strong> ${order.seller_name || 'ไม่ระบุ'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>ข้อมูลการเงิน</h6>
                                <p><strong>ยอดรวม:</strong> ${parseInt(order.total_amount || 0).toLocaleString()} บาท</p>
                                <p><strong>ยอดสุทธิ:</strong> ${parseInt(order.net_amount || 0).toLocaleString()} บาท</p>
                                <p><strong>ส่วนลด:</strong> ${parseInt(order.discount_amount || 0).toLocaleString()} บาท</p>
                                <p><strong>วิธีชำระ:</strong> ${order.payment_method || 'ไม่ระบุ'}</p>
                                <p><strong>สถานะการชำระ:</strong> 
                                    <span class="badge ${getPaymentStatusClass(order.payment_status)}">
                                        ${getPaymentStatusText(order.payment_status)}
                                    </span>
                                </p>
                            </div>
                        </div>
                        ${order.notes ? `<div class="mt-3"><h6>หมายเหตุ</h6><p>${order.notes}</p></div>` : ''}
                    `;
                    
                    const modalElement = document.getElementById('orderDetailsModal');
                    
                    // Force correct z-index before showing
                    modalElement.style.zIndex = '1050';
                    
                    const modal = new bootstrap.Modal(modalElement, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });
                    
                    // Add event listeners for proper cleanup
                    modalElement.addEventListener('shown.bs.modal', function () {
                        // Ensure modal is above backdrop after showing
                        modalElement.style.zIndex = '1050';
                        const modalDialog = modalElement.querySelector('.modal-dialog');
                        if (modalDialog) {
                            modalDialog.style.zIndex = '1051';
                        }
                        
                        // Fix any backdrop z-index issues
                        const backdrops = document.querySelectorAll('.modal-backdrop');
                        backdrops.forEach(backdrop => {
                            backdrop.style.zIndex = '1040';
                        });
                    }, { once: true });
                    
                    modalElement.addEventListener('hidden.bs.modal', function () {
                        cleanupModalBackdrops();
                    }, { once: true });
                    
                    modal.show();
                } else {
                    alert('ไม่สามารถโหลดรายละเอียดออเดอร์ได้');
                }
            } catch (error) {
                console.error('Error loading order details:', error);
                alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            }
        }
        
        // Utility functions
        function getGradeBadgeClass(grade) {
            const classes = {
                'A+': 'bg-danger',
                'A': 'bg-warning',
                'B': 'bg-info', 
                'C': 'bg-success',
                'D': 'bg-secondary'
            };
            return classes[grade] || 'bg-secondary';
        }
        
        function getPaymentStatusClass(status) {
            const classes = {
                'pending': 'bg-warning',
                'paid': 'bg-success',
                'partial': 'bg-info',
                'cancelled': 'bg-danger',
                'returned': 'bg-secondary'
            };
            return classes[status] || 'bg-secondary';
        }
        
        function getPaymentStatusText(status) {
            const texts = {
                'pending': 'รอชำระ',
                'paid': 'ชำระแล้ว',
                'partial': 'ชำระบางส่วน',
                'cancelled': 'ยกเลิก',
                'returned': 'คืนเงิน'
            };
            return texts[status] || status;
        }
        
        function getDeliveryStatusClass(status) {
            const classes = {
                'pending': 'bg-warning',
                'confirmed': 'bg-info',
                'shipped': 'bg-primary',
                'delivered': 'bg-success',
                'cancelled': 'bg-danger'
            };
            return classes[status] || 'bg-secondary';
        }
        
        function getDeliveryStatusText(status) {
            const texts = {
                'pending': 'รอจัดส่ง',
                'confirmed': 'ยืนยันแล้ว',
                'shipped': 'จัดส่งแล้ว',
                'delivered': 'จัดส่งสำเร็จ',
                'cancelled': 'ยกเลิกการส่ง'
            };
            return texts[status] || status;
        }
        
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        }
        
        // Global functions for onclick
        window.selectCustomer = function(id) {
            if (Array.isArray(window._lastSearchCustomers)) {
                const found = window._lastSearchCustomers.find(c => String(c.customer_id) === String(id));
                if (found) {
                    displayCustomerDetails(found);
                    return;
                }
            }
            // fallback minimal
            displayCustomerDetails({ customer_id: id });
        };
        
        window.showOrderDetails = showOrderDetails;
    });
    </script>

</body>
</html>
