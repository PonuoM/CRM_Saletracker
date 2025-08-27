<?php
/**
 * Search Page - Fixed Version
 * ระบบค้นหาลูกค้าและยอดขาย (เวอร์ชันแก้ไข)
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration
require_once __DIR__ . '/config/config.php';
session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check permissions
$roleName = $_SESSION['role_name'] ?? '';
if (!in_array($roleName, ['supervisor', 'telesales', 'admin', 'super_admin'])) {
    header('Location: dashboard.php');
    exit;
}

// Handle AJAX requests
$action = $_GET['action'] ?? 'index';

if ($action !== 'index') {
    try {
        require_once APP_ROOT . '/app/controllers/SearchController.php';
        $controller = new SearchController();
        
        header('Content-Type: application/json');
        
        switch ($action) {
            case 'search':
                $controller->search();
                break;
            case 'customer_details':
                $controller->getCustomerDetails();
                break;
            case 'order_details':
                $controller->getOrderDetails();
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        }
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Show main page
$pageTitle = 'ค้นหาลูกค้า';

// Get user's company source
try {
    require_once APP_ROOT . '/app/services/SearchService.php';
    $searchService = new SearchService();
    $userSource = $searchService->getCurrentUserSource();
} catch (Exception $e) {
    $userSource = 'ไม่ระบุบริษัท';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - CRM SalesTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <!-- Include Header -->
    <?php include APP_ROOT . '/app/views/components/header.php'; ?>

    <div class="container-fluid p-0">
        <!-- Include Sidebar -->
        <?php include APP_ROOT . '/app/views/components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <!-- Page Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h1 class="h3 mb-0">🔍 ค้นหาลูกค้าและยอดขาย</h1>
                                <p class="text-muted mb-0">ค้นหาข้อมูลลูกค้าและประวัติการซื้อภายในบริษัท</p>
                            </div>
                            <div class="badge bg-info fs-6">
                                <i class="fas fa-building me-1"></i>
                                <?php echo $userSource; ?>
                            </div>
                        </div>

                        <!-- Search Section -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-search me-2"></i>ค้นหาลูกค้า
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="searchForm" class="row g-3">
                                    <div class="col-md-8">
                                        <label for="searchTerm" class="form-label">ค้นหาด้วยชื่อหรือเบอร์โทร</label>
                                        <input type="text" 
                                               class="form-control form-control-lg" 
                                               id="searchTerm" 
                                               name="searchTerm"
                                               placeholder="พิมพ์ชื่อลูกค้าหรือเบอร์โทรศัพท์..." 
                                               autocomplete="off">
                                        <div class="form-text">สามารถพิมพ์บางส่วนของชื่อหรือเบอร์ได้</div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary btn-lg w-100">
                                            <i class="fas fa-search me-2"></i>ค้นหา
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Search Results -->
                        <div id="searchResults" style="display: none;">
                            <!-- Customer Info Card -->
                            <div id="customerInfoCard" class="card mb-4" style="display: none;">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user me-2"></i>ข้อมูลลูกค้า
                                    </h5>
                                </div>
                                <div class="card-body" id="customerInfoContent">
                                    <!-- Customer details will be loaded here -->
                                </div>
                            </div>

                            <!-- Orders Table -->
                            <div id="ordersTableCard" class="card" style="display: none;">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-shopping-cart me-2"></i>ประวัติคำสั่งซื้อ
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="ordersTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>เลขที่</th>
                                                    <th>วันที่</th>
                                                    <th>ผู้ขาย</th>
                                                    <th>ยอดรวม</th>
                                                    <th>รายละเอียด</th>
                                                </tr>
                                            </thead>
                                            <tbody id="ordersTableBody">
                                                <!-- Orders will be loaded here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Multiple Results -->
                        <div id="multipleResultsCard" class="card" style="display: none;">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>ผลการค้นหา
                                    <span class="badge bg-secondary ms-2" id="resultsCount">0</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="customersTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ชื่อลูกค้า</th>
                                                <th>เบอร์โทร</th>
                                                <th>เกรด</th>
                                                <th>ยอดซื้อรวม</th>
                                                <th>จำนวนคำสั่งซื้อ</th>
                                                <th>การดำเนินการ</th>
                                            </tr>
                                        </thead>
                                        <tbody id="customersTableBody">
                                            <!-- Multiple customers will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- No Results -->
                        <div id="noResultsCard" class="card" style="display: none;">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">ไม่พบข้อมูลการขาย</h5>
                                <p class="text-muted mb-0">ลองค้นหาด้วยคำอื่น หรือตรวจสอบการสะกดอีกครั้ง</p>
                            </div>
                        </div>

                        <!-- Loading -->
                        <div id="loadingCard" class="card" style="display: none;">
                            <div class="card-body text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">กำลังค้นหา...</span>
                                </div>
                                <p class="mt-3 mb-0 text-muted">กำลังค้นหาข้อมูล...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-receipt me-2"></i>รายละเอียดคำสั่งซื้อ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/page-transitions.js"></script>
    <script src="assets/js/sidebar.js"></script>
    <script src="assets/js/search.js"></script>
    
    <script>
    // Debug search functionality
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Search page loaded');
        
        // Auto-search for URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const testTerm = urlParams.get('test');
        if (testTerm) {
            console.log('Auto-searching for:', testTerm);
            document.getElementById('searchTerm').value = testTerm;
            setTimeout(() => {
                // Trigger search
                const searchForm = document.getElementById('searchForm');
                if (searchForm) {
                    searchForm.dispatchEvent(new Event('submit'));
                }
            }, 1000);
        }
        
        // Override search functionality for debugging
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const searchTerm = document.getElementById('searchTerm').value.trim();
                console.log('Search term:', searchTerm);
                
                if (!searchTerm) {
                    alert('กรุณาใส่คำที่ต้องการค้นหา');
                    return;
                }
                
                // Show loading
                const loadingCard = document.getElementById('loadingCard');
                if (loadingCard) {
                    loadingCard.style.display = 'block';
                }
                
                try {
                    const url = `search_fixed.php?action=search&term=${encodeURIComponent(searchTerm)}`;
                    console.log('Fetching:', url);
                    
                    const response = await fetch(url);
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    const data = await response.json();
                    console.log('Response data:', data);
                    
                    if (loadingCard) {
                        loadingCard.style.display = 'none';
                    }
                    
                    if (data.success) {
                        alert(`พบข้อมูล ${data.customers.length} รายการ`);
                        console.log('Customers found:', data.customers);
                    } else {
                        alert('ไม่พบข้อมูล: ' + data.message);
                        const noResultsCard = document.getElementById('noResultsCard');
                        if (noResultsCard) {
                            noResultsCard.style.display = 'block';
                        }
                    }
                } catch (error) {
                    console.error('Search error:', error);
                    if (loadingCard) {
                        loadingCard.style.display = 'none';
                    }
                    alert('เกิดข้อผิดพลาด: ' + error.message);
                }
            });
        }
    });
    </script>

    <style>
    .badge {
        font-size: 0.85em;
    }

    .customer-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }

    .info-item {
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 0.375rem;
        border-left: 4px solid #007bff;
    }

    .info-label {
        font-weight: 600;
        color: #495057;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }

    .info-value {
        font-size: 1rem;
        color: #212529;
    }

    .table th {
        border-top: none;
        font-weight: 600;
    }

    #searchTerm:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    </style>
</body>
</html>
