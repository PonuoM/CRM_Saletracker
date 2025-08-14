<?php
/**
 * Product Management - List Products
 * แสดงรายการสินค้าทั้งหมด
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-box me-2"></i>
        จัดการสินค้า
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="admin.php?action=products&subaction=create" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>เพิ่มสินค้าใหม่
            </a>
            <a href="admin.php?action=products&subaction=import" class="btn btn-info">
                <i class="fas fa-upload me-2"></i>นำเข้า
            </a>
            <a href="admin.php?action=products&subaction=export" class="btn btn-success">
                <i class="fas fa-download me-2"></i>ส่งออก
            </a>
        </div>
    </div>
</div>

                <!-- Alert Messages -->
                <?php if (isset($_GET['message'])): ?>
                    <?php
                    $message = $_GET['message'];
                    $alertClass = 'alert-success';
                    $alertMessage = '';

                    switch ($message) {
                        case 'product_created':
                            $alertMessage = 'สร้างสินค้าใหม่สำเร็จ';
                            break;
                        case 'product_updated':
                            $alertMessage = 'อัปเดตข้อมูลสินค้าสำเร็จ';
                            break;
                        case 'product_deleted':
                            $alertMessage = 'ลบสินค้าสำเร็จ';
                            break;
                        case 'products_imported':
                            $count = $_GET['count'] ?? 0;
                            $alertMessage = "นำเข้าสินค้า $count รายการสำเร็จ";
                            break;
                        default:
                            $alertClass = 'alert-info';
                            $alertMessage = 'ดำเนินการสำเร็จ';
                    }
                    ?>
                    <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $alertMessage; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filter + KPI Summary Row -->
                <div class="row g-3 align-items-stretch mb-3">
                    <!-- Category Filter (left) -->
                    <div class="col-lg-8">
                        <div class="card shadow h-100">
                            <div class="card-header py-2">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-filter me-2"></i>กรองตามหมวดหมู่
                                </h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="form-check-inline">
                                    <input class="form-check-input category-filter" type="checkbox" value="all" id="catAll" checked>
                                    <label class="form-check-label" for="catAll">
                                        <span class="badge bg-light text-dark border">ทั้งหมด</span>
                                    </label>
                                </div>
                                <?php foreach ($categories as $idx => $cat): ?>
                                <div class="form-check-inline">
                                    <input class="form-check-input category-filter" type="checkbox" value="<?php echo htmlspecialchars($cat); ?>" id="cat_<?php echo $idx; ?>">
                                    <label class="form-check-label" for="cat_<?php echo $idx; ?>">
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($cat); ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <!-- KPI Summary (right) -->
                    <div class="col-lg-4">
                        <div class="card shadow h-100">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-xs text-uppercase text-muted mb-1">สรุปสินค้า</div>
                                        <div class="small text-muted">
                                            ทั้งหมด: <strong><?php echo count($products); ?></strong> |
                                            เปิดใช้งาน: <strong><?php echo count(array_filter($products, function($p){ return $p['is_active']; })); ?></strong> |
                                            หมวดหมู่: <strong><?php echo count($categories); ?></strong> |
                                            หมดสต็อก: <strong><?php echo count(array_filter($products, function($p){ return $p['stock_quantity'] <= 0; })); ?></strong>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <i class="fas fa-boxes fa-lg text-secondary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>รายการสินค้าทั้งหมด
                        </h6>
                        <div class="text-muted">
                            <small>แสดงสูงสุด 7 รายการต่อหน้า</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Fixed height container with scroll -->
                        <div class="table-container" style="max-height: 420px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem;">
                            <table class="table table-bordered table-hover mb-0" id="productsTable">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="min-width: 60px;">ID</th>
                                        <th style="min-width: 120px;">รหัสสินค้า</th>
                                        <th style="min-width: 160px;">ชื่อสินค้า</th>
                                        <th style="min-width: 140px;">หมวดหมู่</th>
                                        <th style="min-width: 100px;">หน่วย</th>
                                        <th style="min-width: 100px;">ต้นทุน</th>
                                        <th style="min-width: 100px;">ราคาขาย</th>
                                        <th style="min-width: 120px;">จำนวนคงเหลือ</th>
                                        <th style="min-width: 120px;">สถานะ</th>
                                        <th style="min-width: 120px;">การดำเนินการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo $product['product_id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($product['product_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td>
                                            <?php $cat = $product['category'] ?? '-'; ?>
                                            <span class="badge bg-light text-dark border" data-category="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['unit']); ?></td>
                                        <td class="text-end">฿<?php echo number_format($product['cost_price'], 2); ?></td>
                                        <td class="text-end">฿<?php echo number_format($product['selling_price'], 2); ?></td>
                                        <td class="text-center">
                                            <span class="badge <?php echo ($product['stock_quantity'] > 0) ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo number_format($product['stock_quantity']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($product['is_active']): ?>
                                                <span class="badge bg-success">เปิดใช้งาน</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">ปิดใช้งาน</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="admin.php?action=products&subaction=edit&id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-primary" title="แก้ไข">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="admin.php?action=products&subaction=delete&id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-danger" title="ลบ" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบสินค้านี้?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Controls -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <small class="text-muted">
                                    แสดง <span id="showingStart">1</span> ถึง <span id="showingEnd">7</span>
                                    จาก <span id="totalRecords"><?php echo count($products); ?></span> รายการ
                                    (<span id="filteredRecords"><?php echo count($products); ?></span> รายการที่กรอง)
                                </small>
                            </div>
                            <nav aria-label="Products pagination">
                                <ul class="pagination pagination-sm mb-0" id="productPagination"></ul>
                            </nav>
                        </div>
                    </div>

<style>
.table-container .table thead th {
  background-color: #f8f9fa !important;
  border-bottom: 2px solid #dee2e6;
  position: sticky; top: 0; z-index: 10;
}
.table-container .table tbody tr:hover { background-color: #f8f9fa; }
.badge { font-size: 0.75rem; padding: 0.35rem 0.6rem; border-radius: 0.375rem; }
.form-check-inline { margin-right: 1rem; margin-bottom: .5rem; }
</style>

<script>
(function waitForJQ(){
  if (typeof window.jQuery === 'undefined') { setTimeout(waitForJQ, 50); return; }
  $(function(){
    const rowsPerPage = 7;
    let currentPage = 1;
    let allRows = [];
    let filteredRows = [];

    // Collect rows with category
    $('#productsTable tbody tr').each(function(){
      const $row = $(this);
      const cat = $row.find('[data-category]').attr('data-category') || '-';
      allRows.push({ el: $row, cat: cat });
    });

    function updateDisplay(){
      allRows.forEach(r => r.el.hide());
      const start = (currentPage - 1) * rowsPerPage;
      const end = Math.min(start + rowsPerPage, filteredRows.length);
      for (let i = start; i < end; i++){
        if (filteredRows[i]) filteredRows[i].el.show();
      }
      $('#showingStart').text(filteredRows.length ? start + 1 : 0);
      $('#showingEnd').text(end);
      $('#filteredRecords').text(filteredRows.length);
    }

    function updatePagination(){
      const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
      const $ul = $('#productPagination');
      $ul.empty();
      if (totalPages <= 1) return;

      $ul.append(`<li class="page-item ${currentPage===1?'disabled':''}"><a class="page-link" href="#" data-page="${currentPage-1}">ก่อนหน้า</a></li>`);
      for (let i=1;i<=totalPages;i++){
        if (i===1 || i===totalPages || (i>=currentPage-1 && i<=currentPage+1)){
          $ul.append(`<li class="page-item ${i===currentPage?'active':''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`);
        } else if (i===currentPage-2 || i===currentPage+2){
          $ul.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
      }
      $ul.append(`<li class="page-item ${currentPage===totalPages?'disabled':''}"><a class="page-link" href="#" data-page="${currentPage+1}">ถัดไป</a></li>`);
    }

    function applyFilter(){
      const isAll = $('#catAll').is(':checked');
      if (isAll){
        filteredRows = [...allRows];
      } else {
        const selected = $('.category-filter:not(#catAll):checked').map(function(){ return $(this).val(); }).get();
        filteredRows = allRows.filter(r => selected.includes(r.cat));
        if (selected.length===0){ $('#catAll').prop('checked', true); filteredRows = [...allRows]; }
      }
      currentPage = 1;
      updateDisplay();
      updatePagination();
    }

    $('.category-filter').on('change', function(){
      if ($(this).val()==='all'){
        if ($(this).is(':checked')) $('.category-filter:not(#catAll)').prop('checked', false);
      } else {
        if ($(this).is(':checked')) $('#catAll').prop('checked', false);
        else if ($('.category-filter:not(#catAll):checked').length===0) $('#catAll').prop('checked', true);
      }
      applyFilter();
    });

    $(document).on('click', '#productPagination .page-link', function(e){
      e.preventDefault();
      const page = parseInt($(this).data('page'));
      if (page && page!==currentPage){
        currentPage = page;
        updateDisplay();
        updatePagination();
        $('.table-container').scrollTop(0);
      }
    });

    // init
    $('#totalRecords').text(allRows.length);
    $('#filteredRecords').text(allRows.length);
    applyFilter();
  });
})();
</script>
                </div>



<script>
$(document).ready(function() {
    $('#productsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/th.json'
        },
        pageLength: 25,
        order: [[0, 'desc']]
    });
});
</script>