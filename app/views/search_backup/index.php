<?php
/**
 * Search Index Page
 * หน้าค้นหาลูกค้าและยอดขาย
 */

// ตรวจสอบตัวแปรที่จำเป็น
if (!isset($userSource)) {
    require_once APP_ROOT . '/app/services/SearchService.php';
    $searchService = new SearchService();
    $userSource = $searchService->getCurrentUserSource();
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- Header -->
            <div class="card border-0 shadow-lg mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white text-center py-4">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-search fa-2x me-3"></i>
                        <div>
                            <h2 class="mb-1 fw-bold">ค้นหาลูกค้า</h2>
                            <p class="mb-0 opacity-75">ระบบค้นหาข้อมูลลูกค้าและประวัติการสั่งซื้อ</p>
                        </div>
                    </div>
                    <span class="badge bg-light text-dark px-3 py-2">
                        <i class="fas fa-building me-1"></i>
                        บริษัท: <?php echo htmlspecialchars($userSource ?? 'Unknown'); ?>
                    </span>
                </div>
            </div>

            <!-- Search Form -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
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
                                <button type="submit" class="btn btn-lg w-100 shadow-sm" 
                                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white;">
                                    <i class="fas fa-search me-2"></i>ค้นหา
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Loading Card -->
            <div id="loadingCard" class="card shadow-sm mb-4" style="display: none;">
                <div class="card-body text-center py-4">
                    <div class="spinner-border text-primary mb-3"></div>
                    <h6>กำลังค้นหา...</h6>
                </div>
            </div>

            <!-- No Results Card -->
            <div id="noResultsCard" class="card shadow-sm mb-4" style="display: none;">
                <div class="card-body text-center py-4">
                    <i class="fas fa-search-minus text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-muted">ไม่พบข้อมูล</h5>
                    <p class="text-muted">ลองค้นหาด้วยคำอื่นหรือตรวจสอบการสะกดคำ</p>
                </div>
            </div>

            <!-- Customer Results -->
            <div id="customerContainer" style="display: none;">
                <!-- Customer Details -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header border-0 text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-user me-2"></i>ข้อมูลลูกค้า
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div id="customerDetails"></div>
                    </div>
                </div>

                <!-- Order History -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 text-white" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-shopping-cart me-2"></i>ประวัติการสั่งซื้อ
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

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">รายละเอียดคำสั่งซื้อ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="orderDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">กำลังโหลด...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.customer-info-item {
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 0.375rem;
    border-left: 3px solid #007bff;
    margin-bottom: 0.5rem;
}

.badge {
    font-size: 0.75em;
}

/* Fix visibility issues */
#customerContainer {
    display: none !important;
    opacity: 1 !important;
    visibility: visible !important;
    transition: none !important;
    background: transparent !important;
    z-index: 1 !important;
}

#customerContainer.show {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
}

#loadingCard {
    display: none !important;
}

#loadingCard.show {
    display: block !important;
}

#noResultsCard {
    display: none !important;
}

#noResultsCard.show {
    display: block !important;
}

/* Disable problematic transitions */
.fade {
    transition: none !important;
}

/* Force content visibility */
.container-fluid,
.container-fluid .row,
.container-fluid .col-md-8 {
    background: transparent !important;
    min-height: auto !important;
}

/* Ensure cards are visible */
.card {
    background: white !important;
    opacity: 1 !important;
    z-index: 1 !important;
}

/* Debug: Add visible border to check positioning */
#customerContainer .card {
    border: 1px solid #ddd !important;
    margin-bottom: 1rem !important;
}

@media (max-width: 768px) {
    .container-fluid .col-md-8 {
        padding: 10px;
    }
}
</style>