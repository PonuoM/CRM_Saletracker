/**
 * Tag Management JavaScript
 * จัดการ Tags สำหรับลูกค้า
 */

// สีที่ใช้สำหรับ tags
const TAG_COLORS = [
    '#007bff', '#28a745', '#dc3545', '#ffc107', '#6f42c1',
    '#fd7e14', '#20c997', '#e83e8c', '#6c757d', '#17a2b8'
];

// ตัวแปรสำหรับเก็บ tags ที่ user เคยใช้
let userTags = [];
let predefinedTags = [];

/**
 * โหลด tags ที่ user เคยใช้
 */
async function loadUserTags() {
    try {
        const response = await fetch('api/tags.php?action=user_tags');
        const data = await response.json();
        
        if (response.ok) {
            userTags = data.user_tags || [];
            predefinedTags = data.predefined_tags || [];
        } else {
            console.error('Error loading user tags:', data.error);
        }
    } catch (error) {
        console.error('Error loading user tags:', error);
    }
}

/**
 * แสดง tags ของลูกค้า
 */
async function displayCustomerTags(customerId, container) {
    try {
        const response = await fetch(`api/tags.php?action=get&customer_id=${customerId}`);
        const data = await response.json();
        
        if (response.ok) {
            const tags = data.tags || [];
            container.innerHTML = renderTagsHTML(tags, customerId);
        } else {
            console.error('Error loading customer tags:', data.error);
        }
    } catch (error) {
        console.error('Error loading customer tags:', error);
    }
}

/**
 * สร้าง HTML สำหรับแสดง tags
 */
function renderTagsHTML(tags, customerId) {
    let html = '';
    
    // แสดง tags ที่มีอยู่
    tags.forEach(tag => {
        html += `
            <span class="badge me-1 mb-1" style="background-color: ${tag.tag_color}; cursor: pointer;" 
                  onclick="removeCustomerTag(${customerId}, '${tag.tag_name}', this)"
                  title="คลิกเพื่อลบ tag">
                ${escapeHtml(tag.tag_name)} <i class="fas fa-times ms-1"></i>
            </span>
        `;
    });
    
    // ปุ่มเพิ่ม tag
    html += `
        <button class="btn btn-sm btn-outline-secondary me-1 mb-1" 
                onclick="showAddTagModal(${customerId})" 
                title="เพิ่ม tag">
            <i class="fas fa-plus"></i>
        </button>
    `;
    
    return html;
}

/**
 * เพิ่ม tag ให้ลูกค้า
 * @param {number} customerId - ID ลูกค้า
 * @param {string} tagName - ชื่อ tag
 * @param {string} tagColor - สี tag (hex)
 * @param {boolean} showNotification - แสดง notification หรือไม่ (default: true)
 */
async function addCustomerTag(customerId, tagName, tagColor = '#007bff', showNotification = true) {
    try {
        const response = await fetch('api/tags.php?action=add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                customer_id: customerId,
                tag_name: tagName,
                tag_color: tagColor
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // รีเฟรช tags ของลูกค้า
            const container = document.querySelector(`[data-customer-tags="${customerId}"]`);
            if (container) {
                await displayCustomerTags(customerId, container);
            }
            
            // แสดงข้อความสำเร็จ (ถ้าต้องการ)
            if (showNotification) {
                showSuccessMessage(data.message || 'เพิ่ม Tag สำเร็จ');
            }
            
            // อัปเดต user tags
            await loadUserTags();
            
            // รีเฟรช tag filter dropdown
            console.log('Refreshing tag filter dropdown...');
            await loadTagFilterOptions();
            console.log('Tag filter dropdown refreshed');
            
            // รีเฟรชตารางหลัก (ถ้าอยู่ในหน้า All Customers)
            if (typeof loadAllCustomers === 'function') {
                loadAllCustomers();
            }
        } else {
            showErrorMessage(data.message || 'เกิดข้อผิดพลาดในการเพิ่ม Tag');
        }
    } catch (error) {
        console.error('Error adding tag:', error);
        showErrorMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    }
}

/**
 * ลบ tag ของลูกค้า
 */
async function removeCustomerTag(customerId, tagName, element) {
    // แสดง confirmation
    if (!confirm(`ต้องการลบ Tag "${tagName}" หรือไม่?`)) {
        return;
    }
    
    try {
        const response = await fetch('api/tags.php?action=remove', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                customer_id: customerId,
                tag_name: tagName
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // ลบ element ออกจาก DOM
            element.remove();
            
            // แสดงข้อความสำเร็จ
            showSuccessMessage(data.message || 'ลบ Tag สำเร็จ');
            
            // หมายเหตุ: ไม่ต้องรีเฟรช tag filter dropdown เมื่อลบ tag จากลูกค้า
            // เพราะจะทำให้สถานะ tag filter ที่เลือกไว้หายไป
            console.log('Tag removed, preserving tag filter state...');
            
            // รักษา tag filter state เมื่อลบ tag จากลูกค้า
            // ไม่ลบ tag ออกจาก filter เพราะ user อาจต้องการกรอง tag นั้นต่อไป
            const savedTagFilters = sessionStorage.getItem('selectedTagFilters');
            console.log('🔍 Preserving tag filter state:', savedTagFilters);
            console.log('🔍 Tag removed from customer:', tagName, 'but keeping filter active');
            
            // รีเฟรชตารางหลัก โดยใช้ tag filter ที่มีอยู่
            const finalTagFilters = sessionStorage.getItem('selectedTagFilters');
            console.log('🔍 Final tag filters for refresh:', finalTagFilters);
            if (finalTagFilters) {
                try {
                    const selectedTags = JSON.parse(finalTagFilters);
                    console.log('🔍 Applying tag filter with tags:', selectedTags);
                    if (selectedTags.length > 0) {
                        // ยังมี tag filters ให้ใช้ต่อ
                        if (typeof searchCustomersByTags === 'function' && typeof renderStandardTable === 'function') {
                            console.log('🔍 Searching customers by tags...');
                            searchCustomersByTags(selectedTags).then(customers => {
                                renderStandardTable(customers, 'allCustomersTable', 'ไม่มีลูกค้าที่มี tags ที่เลือก');
                                
                                // อัปเดต all customers count
                                const allCountBadge = document.getElementById('allCustomersCount');
                                if (allCountBadge) {
                                    allCountBadge.textContent = customers.length;
                                }
                                
                                // เพิ่ม pagination
                                setTimeout(() => {
                                    const table = document.querySelector('#allCustomersTable table');
                                    const paginationContainer = document.getElementById('allCustomersTable-pagination');
                                    if (table && paginationContainer) {
                                        paginationContainer.innerHTML = '';
                                        if (typeof paginateTable === 'function') {
                                            paginateTable(table, 'allCustomersTable-pagination', 10, 'customers_page_allCustomersTable');
                                        }
                                    }
                                }, 100);
                            });
                        }
                    } else {
                        // ไม่มี tag filters แล้ว โหลดลูกค้าทั้งหมด
                        if (typeof getAllCustomersFilters === 'function' && typeof loadAllCustomersWithFilters === 'function') {
                            const currentFilters = getAllCustomersFilters();
                            loadAllCustomersWithFilters(currentFilters);
                        } else if (typeof loadAllCustomers === 'function') {
                            loadAllCustomers();
                        }
                    }
                } catch (e) {
                    console.error('Error applying updated tag filter:', e);
                    // fallback ใช้ filters ปกติ
                    if (typeof getAllCustomersFilters === 'function' && typeof loadAllCustomersWithFilters === 'function') {
                        const currentFilters = getAllCustomersFilters();
                        loadAllCustomersWithFilters(currentFilters);
                    } else if (typeof loadAllCustomers === 'function') {
                        loadAllCustomers();
                    }
                }
            } else {
                // ไม่มี tag filters โหลดลูกค้าทั้งหมด
                if (typeof getAllCustomersFilters === 'function' && typeof loadAllCustomersWithFilters === 'function') {
                    const currentFilters = getAllCustomersFilters();
                    loadAllCustomersWithFilters(currentFilters);
                } else if (typeof loadAllCustomers === 'function') {
                    loadAllCustomers();
                }
            }
        } else {
            showErrorMessage(data.message || 'เกิดข้อผิดพลาดในการลบ Tag');
        }
    } catch (error) {
        console.error('Error removing tag:', error);
        showErrorMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    }
}

/**
 * แสดง modal สำหรับเพิ่ม tag
 */
function showAddTagModal(customerId) {
    const modalHtml = `
        <div class="modal fade" id="addTagModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">เพิ่ม Tag</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addTagForm">
                            <div class="mb-3">
                                <label for="tagName" class="form-label">ชื่อ Tag</label>
                                <input type="text" class="form-control" id="tagName" placeholder="ใส่ชื่อ tag" required>
                                <div class="form-text">หรือเลือกจาก tags ที่เคยใช้:</div>
                                <div id="suggestedTags" class="mt-2">
                                    ${renderSuggestedTags()}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="tagColor" class="form-label">สี</label>
                                <div class="d-flex flex-wrap gap-2">
                                    ${renderColorPicker()}
                                </div>
                                <input type="hidden" id="selectedColor" value="#007bff">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" class="btn btn-primary" onclick="submitAddTag(${customerId})">เพิ่ม Tag</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // ลบ modal เก่า (ถ้ามี)
    const existingModal = document.getElementById('addTagModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // เพิ่ม modal ใหม่
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // แสดง modal
    const modal = new bootstrap.Modal(document.getElementById('addTagModal'));
    modal.show();
    
    // Focus ที่ input
    document.getElementById('tagName').focus();
}

/**
 * สร้าง HTML สำหรับ suggested tags
 */
function renderSuggestedTags() {
    let html = '';
    
    // Predefined tags
    predefinedTags.forEach(tag => {
        html += `
            <span class="badge me-1 mb-1" style="background-color: ${tag.tag_color}; cursor: pointer;" 
                  onclick="selectSuggestedTag('${tag.tag_name}', '${tag.tag_color}')">
                ${escapeHtml(tag.tag_name)}
            </span>
        `;
    });
    
    // User tags
    userTags.slice(0, 10).forEach(tag => {
        html += `
            <span class="badge me-1 mb-1" style="background-color: ${tag.tag_color}; cursor: pointer;" 
                  onclick="selectSuggestedTag('${tag.tag_name}', '${tag.tag_color}')">
                ${escapeHtml(tag.tag_name)} <small>(${tag.usage_count})</small>
            </span>
        `;
    });
    
    return html || '<small class="text-muted">ยังไม่มี tags ที่เคยใช้</small>';
}

/**
 * สร้าง HTML สำหรับเลือกสี
 */
function renderColorPicker() {
    let html = '';
    
    TAG_COLORS.forEach(color => {
        html += `
            <button type="button" class="btn btn-sm color-picker" 
                    style="background-color: ${color}; width: 30px; height: 30px; border: 2px solid #ddd;" 
                    onclick="selectColor('${color}')" 
                    data-color="${color}">
            </button>
        `;
    });
    
    return html;
}

/**
 * เลือก suggested tag
 */
function selectSuggestedTag(tagName, tagColor) {
    document.getElementById('tagName').value = tagName;
    selectColor(tagColor);
}

/**
 * เลือกสี
 */
function selectColor(color) {
    // ลบ border จาก buttons ทั้งหมด
    document.querySelectorAll('.color-picker').forEach(btn => {
        btn.style.border = '2px solid #ddd';
    });
    
    // เพิ่ม border ให้ button ที่เลือก
    const selectedBtn = document.querySelector(`[data-color="${color}"]`);
    if (selectedBtn) {
        selectedBtn.style.border = '3px solid #000';
    }
    
    // บันทึกสีที่เลือก
    document.getElementById('selectedColor').value = color;
}

/**
 * Submit การเพิ่ม tag
 */
async function submitAddTag(customerId) {
    const tagName = document.getElementById('tagName').value.trim();
    const tagColor = document.getElementById('selectedColor').value;
    
    if (!tagName) {
        showErrorMessage('กรุณาใส่ชื่อ Tag');
        return;
    }
    
    // ปิด modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('addTagModal'));
    modal.hide();
    
    // เพิ่ม tag
    await addCustomerTag(customerId, tagName, tagColor);
}

/**
 * ค้นหาลูกค้าตาม tags
 */
async function searchCustomersByTags(tagNames, additionalFilters = {}) {
    try {
        const params = new URLSearchParams();
        
        // เพิ่ม tags
        if (tagNames && tagNames.length > 0) {
            params.append('tags', tagNames.join(','));
        }
        
        // เพิ่ม filters เพิ่มเติม
        Object.entries(additionalFilters).forEach(([key, value]) => {
            if (value) {
                params.append(key, value);
            }
        });
        
        const response = await fetch(`api/tags.php?action=search&${params.toString()}`);
        const data = await response.json();
        
        if (response.ok) {
            return data.customers || [];
        } else {
            console.error('Error searching customers by tags:', data.error);
            return [];
        }
    } catch (error) {
        console.error('Error searching customers by tags:', error);
        return [];
    }
}

/**
 * Bulk add tags
 */
async function bulkAddTags(customerIds, tagName, tagColor = '#007bff') {
    try {
        const response = await fetch('api/tags.php?action=bulk_add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                customer_ids: customerIds,
                tag_name: tagName,
                tag_color: tagColor
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showSuccessMessage(data.message || 'เพิ่ม Tags สำเร็จ');
            
            // รีเฟรช tag filter dropdown
            console.log('Refreshing tag filter dropdown after remove...');
            loadTagFilterOptions();
            console.log('Tag filter dropdown refreshed after remove');
            
            // รีเฟรชตาราง
            if (typeof loadAllCustomers === 'function') {
                loadAllCustomers();
            } else if (typeof applyFilters === 'function') {
                applyFilters();
            }
        } else {
            showErrorMessage(data.message || 'เกิดข้อผิดพลาดในการเพิ่ม Tags');
        }
    } catch (error) {
        console.error('Error bulk adding tags:', error);
        showErrorMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    }
}

/**
 * Bulk remove tags
 */
async function bulkRemoveTags(customerIds, tagNames) {
    try {
        const response = await fetch('api/tags.php?action=bulk_remove', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                customer_ids: customerIds,
                tag_names: tagNames
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showSuccessMessage(data.message || 'ลบ Tags สำเร็จ');
            
            // รีเฟรช tag filter dropdown
            console.log('Refreshing tag filter dropdown after remove...');
            loadTagFilterOptions();
            console.log('Tag filter dropdown refreshed after remove');
            
            // รีเฟรชตาราง
            if (typeof loadAllCustomers === 'function') {
                loadAllCustomers();
            } else if (typeof applyFilters === 'function') {
                applyFilters();
            }
        } else {
            showErrorMessage(data.message || 'เกิดข้อผิดพลาดในการลบ Tags');
        }
    } catch (error) {
        console.error('Error bulk removing tags:', error);
        showErrorMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    }
}

/**
 * Utility functions
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showSuccessMessage(message) {
    // ใช้ระบบ notification ที่มีอยู่ หรือใช้ alert ชั่วคราว
    if (typeof showNotification === 'function') {
        showNotification(message, 'success');
    } else {
        alert(message);
    }
}

function showErrorMessage(message) {
    // ใช้ระบบ notification ที่มีอยู่ หรือใช้ alert ชั่วคราว
    if (typeof showNotification === 'function') {
        showNotification(message, 'error');
    } else {
        alert(message);
    }
}

/**
 * โหลด tag options สำหรับ filter dropdown
 */
async function loadTagFilterOptions() {
    try {
        const response = await fetch('api/tags.php?action=user_tags');
        const data = await response.json();
        
        if (response.ok) {
            const tagOptionsContainer = document.getElementById('tagFilterOptions');
            if (!tagOptionsContainer) return;
            
            let html = '';
            
            // เพิ่มช่องค้นหา
            html += `
                <div class="p-2 border-bottom">
                    <input type="text" class="form-control form-control-sm" id="tagSearchInput" 
                           placeholder="ค้นหา tag..." onkeyup="filterTagOptions()">
                </div>
            `;
            
            html += '<div id="tagFilterList">';
            
            // User tags (แสดงก่อน และเฉพาะที่มี usage_count > 0)
            const usedUserTags = data.user_tags ? data.user_tags.filter(tag => tag.usage_count > 0) : [];
            if (usedUserTags.length > 0) {
                html += '<h6 class="dropdown-header">Tags ของฉัน</h6>';
                usedUserTags.forEach(tag => {
                    html += `
                        <div class="form-check tag-option" data-tag-name="${tag.tag_name.toLowerCase()}">
                            <input class="form-check-input" type="checkbox" value="${tag.tag_name}" id="user_${tag.tag_name}">
                            <label class="form-check-label" for="user_${tag.tag_name}">
                                <span class="badge me-1" style="background-color: ${tag.tag_color}">${tag.tag_name}</span>
                                <small class="text-muted">(${tag.usage_count})</small>
                            </label>
                        </div>
                    `;
                });
            }
            
            // Predefined tags (เฉพาะที่ใช้งานอยู่)
            const usedPredefinedTags = data.predefined_tags ? data.predefined_tags.filter(tag => tag.usage_count > 0) : [];
            if (usedPredefinedTags.length > 0) {
                html += '<h6 class="dropdown-header mt-2">Tags ของระบบ</h6>';
                usedPredefinedTags.forEach(tag => {
                    html += `
                        <div class="form-check tag-option" data-tag-name="${tag.tag_name.toLowerCase()}">
                            <input class="form-check-input" type="checkbox" value="${tag.tag_name}" id="predefined_${tag.tag_name}">
                            <label class="form-check-label" for="predefined_${tag.tag_name}">
                                <span class="badge me-1" style="background-color: ${tag.tag_color}">${tag.tag_name}</span>
                                <small class="text-muted">(${tag.usage_count || 0})</small>
                            </label>
                        </div>
                    `;
                });
            }
            
            html += '</div>'; // close tagFilterList
            
            if (usedUserTags.length === 0 && usedPredefinedTags.length === 0) {
                html += '<p class="text-muted small p-2">ยังไม่มี tags ที่ใช้งาน</p>';
            }
            
            tagOptionsContainer.innerHTML = html;
            
            // กู้คืนสถานะการกรองที่บันทึกไว้
            if (typeof restoreTagFilterState === 'function') {
                setTimeout(() => restoreTagFilterState(), 100);
            }
        }
    } catch (error) {
        console.error('Error loading tag filter options:', error);
    }
}

/**
 * กรอง tag options ตามคำค้นหา
 */
function filterTagOptions() {
    const searchInput = document.getElementById('tagSearchInput');
    const tagOptions = document.querySelectorAll('.tag-option');
    
    if (!searchInput) return;
    
    const searchTerm = searchInput.value.toLowerCase();
    
    tagOptions.forEach(option => {
        const tagName = option.getAttribute('data-tag-name');
        if (tagName.includes(searchTerm)) {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    });
}

/**
 * แสดง modal สำหรับ tag filter
 */
function showTagFilterModal() {
    const modalHtml = `
        <div class="modal fade" id="tagFilterModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-tags me-2"></i>เลือก Tags สำหรับกรอง
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Search Box -->
                        <div class="mb-3">
                            <input type="text" class="form-control" id="modalTagSearchInput" 
                                   placeholder="ค้นหา tag..." onkeyup="filterModalTagOptions()">
                        </div>
                        
                        <!-- Tag Options -->
                        <div id="modalTagFilterOptions" style="max-height: 400px; overflow-y: auto;">
                            <!-- Tag options will be loaded here -->
                        </div>
                        
                        <!-- Selected Count -->
                        <div class="mt-3 text-muted">
                            <div class="form-text">เลือกแล้ว: <span id="modalSelectedCount">0</span> tags</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" onclick="clearModalTagFilter()">
                            <i class="fas fa-times me-1"></i>ล้างทั้งหมด
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" class="btn btn-primary" onclick="applyModalTagFilter()">
                            <i class="fas fa-filter me-1"></i>กรอง
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // ลบ modal เก่า (ถ้ามี)
    const existingModal = document.getElementById('tagFilterModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // เพิ่ม modal ใหม่
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // โหลด tag options ใน modal
    loadModalTagOptions();
    
    // แสดง modal
    const modal = new bootstrap.Modal(document.getElementById('tagFilterModal'));
    modal.show();
    
    // โหลดสถานะ tag filters ที่เลือกไว้ก่อนหน้า
    loadPreviousTagFilters();
}

/**
 * โหลด tag options ใน modal
 */
async function loadModalTagOptions() {
    try {
        const response = await fetch('api/tags.php?action=user_tags');
        const data = await response.json();
        
        if (response.ok) {
            const container = document.getElementById('modalTagFilterOptions');
            if (!container) return;
            
            let html = '';
            
            // User tags (แสดงก่อน และเฉพาะที่มี usage_count > 0)
            const usedUserTags = data.user_tags ? data.user_tags.filter(tag => tag.usage_count > 0) : [];
            if (usedUserTags.length > 0) {
                html += '<div class="mb-4">';
                html += '<h6 class="text-primary mb-3"><i class="fas fa-user me-2"></i>Tags ของฉัน</h6>';
                html += '<div class="d-flex flex-wrap gap-2">';
                usedUserTags.forEach(tag => {
                    html += `
                        <span class="badge tag-selectable" 
                              data-tag-name="${tag.tag_name}" 
                              data-tag-color="${tag.tag_color}"
                              style="background-color: ${tag.tag_color}; cursor: pointer; border: 2px solid transparent; padding: 8px 12px; font-size: 0.9rem;"
                              onclick="toggleTagSelectionNew('${tag.tag_name}', '${tag.tag_color}', this)"
                              title="คลิกเพื่อเลือก/ยกเลิก">
                            ${tag.tag_name} <small class="ms-1">(${tag.usage_count})</small>
                        </span>
                    `;
                });
                html += '</div></div>';
            }
            
            // เอา Tags ของระบบออก - แสดงเฉพาะ Tags ของฉัน
            
            if (usedUserTags.length === 0) {
                html = '<div class="text-center text-muted p-4">ยังไม่มี tags ที่ใช้งาน</div>';
            }
            
            container.innerHTML = html;
            
            // กู้คืนสถานะการเลือก
            restoreModalTagState();
        }
    } catch (error) {
        console.error('Error loading modal tag options:', error);
    }
}

/**
 * ฟังก์ชันเสริมสำหรับ modal
 */
function filterModalTagOptions() {
    const searchInput = document.getElementById('modalTagSearchInput');
    const tagOptions = document.querySelectorAll('#modalTagFilterOptions .tag-selectable');
    
    if (!searchInput) return;
    
    const searchTerm = searchInput.value.toLowerCase();
    
    tagOptions.forEach(option => {
        const tagName = option.getAttribute('data-tag-name');
        if (tagName.includes(searchTerm)) {
            option.style.display = 'inline-flex';
        } else {
            option.style.display = 'none';
        }
    });
}

function updateModalSelectedCount() {
    const checkedTags = document.querySelectorAll('#modalTagFilterOptions input[type="checkbox"]:checked');
    const countSpan = document.getElementById('modalSelectedCount');
    if (countSpan) {
        countSpan.textContent = checkedTags.length;
    }
}

/**
 * ใช้สีดำสำหรับการไฮไลท์ทั้งหมด
 */
function getOppositeColor(hexColor) {
    // ใช้สีดำสำหรับทุก tag เพื่อให้มองเห็นชัด
    return '#000000';
}

/**
 * คำนวณสีข้อความที่เหมาะสมสำหรับพื้นหลัง
 */
function getTextColor(backgroundColor) {
    // Remove # if present
    const hex = backgroundColor.replace('#', '');
    
    // Convert to RGB
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);
    
    // Calculate brightness
    const brightness = (r * 299 + g * 587 + b * 114) / 1000;
    
    // Return white text for dark backgrounds, black for light backgrounds
    return brightness > 128 ? '#000000' : '#ffffff';
}

function toggleTagSelectionNew(tagName, tagColor, element) {
    const isSelected = element.classList.contains('selected');
    
    if (isSelected) {
        // ยกเลิกการเลือก
        element.classList.remove('selected');
        element.style.border = '2px solid transparent';
        element.style.boxShadow = 'none';
    } else {
        // เลือก tag - ใช้สีตรงข้ามสำหรับขอบ
        element.classList.add('selected');
        const oppositeColor = getOppositeColor(tagColor);
        element.style.border = `3px solid ${oppositeColor}`;
        element.style.boxShadow = `0 0 0 1px ${oppositeColor}`;
    }
    
    updateModalSelectedCountNew();
}

function updateModalSelectedCountNew() {
    const selectedTags = document.querySelectorAll('#modalTagFilterOptions .tag-selectable.selected');
    const countSpan = document.getElementById('modalSelectedCount');
    if (countSpan) {
        countSpan.textContent = selectedTags.length;
    }
}

// ===== TAG PREVIEW FUNCTIONALITY FOR CALL LOG FORMS =====

/**
 * แสดง Modal เลือก Tag สำหรับฟอร์มบันทึกการโทร
 */
function showAddTagModalFromCall() {
    const modalHtml = `
        <div class="modal fade" id="callLogTagModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">เพิ่ม Tag สำหรับการโทร</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Tab Navigation -->
                        <ul class="nav nav-tabs" id="tagModalTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="existing-tags-tab" data-bs-toggle="tab" data-bs-target="#existing-tags" type="button" role="tab">
                                    เลือก Tag ที่มีอยู่
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="new-tag-tab" data-bs-toggle="tab" data-bs-target="#new-tag" type="button" role="tab">
                                    สร้าง Tag ใหม่
                                </button>
                            </li>
                        </ul>
                        
                        <!-- Tab Content -->
                        <div class="tab-content mt-3" id="tagModalTabContent">
                            <!-- Existing Tags Tab -->
                            <div class="tab-pane fade show active" id="existing-tags" role="tabpanel">
                                <div class="mb-3">
                                    <input type="text" class="form-control" id="tagSearchInput" placeholder="ค้นหา tag...">
                                </div>
                                <div id="existingTagsList" style="max-height: 300px; overflow-y: auto;">
                                    <!-- Tags will be loaded here -->
                                </div>
                            </div>
                            
                            <!-- New Tag Tab -->
                            <div class="tab-pane fade" id="new-tag" role="tabpanel">
                                <form>
                                    <div class="mb-3">
                                        <label for="newTagName" class="form-label">ชื่อ Tag</label>
                                        <input type="text" class="form-control" id="newTagName" placeholder="ชื่อ tag..." maxlength="50">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">สี</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            ${renderColorPicker()}
                                        </div>
                                        <input type="hidden" id="newTagColor" value="#007bff">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" class="btn btn-primary" onclick="addSelectedTagsToCallLog()">เพิ่ม Tag</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // ลบ modal เก่า (ถ้ามี)
    const existingModal = document.getElementById('callLogTagModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // เพิ่ม modal ใหม่
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // โหลด existing tags
    loadExistingTagsForCallLog();
    
    // แสดง modal
    const modal = new bootstrap.Modal(document.getElementById('callLogTagModal'));
    modal.show();
    
    // Add search functionality
    document.getElementById('tagSearchInput').addEventListener('input', function() {
        filterCallLogTags(this.value);
    });
    
    // Add color picker functionality for new tag
    document.querySelectorAll('.color-picker').forEach(btn => {
        btn.addEventListener('click', function() {
            const color = this.getAttribute('data-color');
            selectCallLogTagColor(color);
        });
    });
}

/**
 * โหลด Tags ที่มีอยู่สำหรับ Call Log Modal
 */
async function loadExistingTagsForCallLog() {
    try {
        const response = await fetch('api/tags.php?action=user_tags');
        const data = await response.json();
        
        if (response.ok) {
            const container = document.getElementById('existingTagsList');
            let html = '';
            
            // User tags
            if (data.user_tags && data.user_tags.length > 0) {
                html += '<h6 class="text-muted mb-2">Tags ของฉัน</h6>';
                data.user_tags.forEach(tag => {
                    html += `
                        <div class="tag-item mb-1" data-tag-name="${tag.tag_name.toLowerCase()}">
                            <span class="badge tag-selectable" 
                                  style="background-color: ${tag.tag_color}; color: ${getTextColor(tag.tag_color)}; cursor: pointer; padding: 8px 12px;"
                                  onclick="toggleCallLogTagSelection('${tag.tag_name}', '${tag.tag_color}', this)">
                                ${escapeHtml(tag.tag_name)} <small>(${tag.usage_count || 0})</small>
                            </span>
                        </div>
                    `;
                });
            }
            
            // Predefined tags
            if (data.predefined_tags && data.predefined_tags.length > 0) {
                html += '<h6 class="text-muted mb-2 mt-3">Tags ของระบบ</h6>';
                data.predefined_tags.forEach(tag => {
                    html += `
                        <div class="tag-item mb-1" data-tag-name="${tag.tag_name.toLowerCase()}">
                            <span class="badge tag-selectable" 
                                  style="background-color: ${tag.tag_color}; color: ${getTextColor(tag.tag_color)}; cursor: pointer; padding: 8px 12px;"
                                  onclick="toggleCallLogTagSelection('${tag.tag_name}', '${tag.tag_color}', this)">
                                ${escapeHtml(tag.tag_name)}
                            </span>
                        </div>
                    `;
                });
            }
            
            if (html === '') {
                html = '<p class="text-muted">ยังไม่มี tags ในระบบ</p>';
            }
            
            container.innerHTML = html;
        }
    } catch (error) {
        console.error('Error loading tags for call log:', error);
        document.getElementById('existingTagsList').innerHTML = '<p class="text-danger">เกิดข้อผิดพลาดในการโหลด tags</p>';
    }
}

/**
 * Toggle Tag Selection ใน Call Log Modal
 */
let selectedCallLogTags = [];

function toggleCallLogTagSelection(tagName, tagColor, element) {
    const index = selectedCallLogTags.findIndex(tag => tag.name === tagName);
    
    if (index > -1) {
        // ยกเลิกการเลือก
        selectedCallLogTags.splice(index, 1);
        element.style.border = '2px solid transparent';
        element.style.boxShadow = 'none';
    } else {
        // เลือก tag
        selectedCallLogTags.push({ name: tagName, color: tagColor });
        element.style.border = '3px solid #000';
        element.style.boxShadow = '0 0 0 1px #000';
    }
}

/**
 * กรอง Tags ใน Call Log Modal
 */
function filterCallLogTags(searchTerm) {
    const tagItems = document.querySelectorAll('#existingTagsList .tag-item');
    const lowerSearchTerm = searchTerm.toLowerCase();
    
    tagItems.forEach(item => {
        const tagName = item.getAttribute('data-tag-name');
        if (tagName.includes(lowerSearchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

/**
 * เลือกสีสำหรับ Tag ใหม่ใน Call Log Modal
 */
function selectCallLogTagColor(color) {
    document.getElementById('newTagColor').value = color;
    
    // Update visual feedback
    document.querySelectorAll('.color-picker').forEach(btn => {
        btn.style.border = '2px solid #ddd';
    });
    document.querySelector(`[data-color="${color}"]`).style.border = '3px solid #000';
}

/**
 * เพิ่ม Tags ที่เลือกไปยัง Call Log Preview
 */
function addSelectedTagsToCallLog() {
    // Add selected existing tags
    selectedCallLogTags.forEach(tag => {
        addCallLogTag(tag.name, tag.color);
    });
    
    // Add new tag if specified
    const newTagName = document.getElementById('newTagName').value.trim();
    const newTagColor = document.getElementById('newTagColor').value;
    
    if (newTagName) {
        addCallLogTag(newTagName, newTagColor);
    }
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('callLogTagModal'));
    modal.hide();
    
    // Clear selections
    selectedCallLogTags = [];
}

// Global array สำหรับเก็บ tags ที่เพิ่มในฟอร์มบันทึกการโทร
window.callLogTags = window.callLogTags || [];

/**
 * แสดง Tag Preview ในฟอร์มบันทึกการโทร
 */
function updateCallTagsPreview() {
    const previewContainer = document.getElementById('callTagsPreview');
    if (!previewContainer) return;
    
    if (window.callLogTags.length === 0) {
        previewContainer.innerHTML = '<small class="text-muted">Tags ที่เพิ่มจะแสดงที่นี่</small>';
        return;
    }
    
    let html = '';
    window.callLogTags.forEach((tag, index) => {
        html += `
            <span class="badge me-1 mb-1" style="background-color: ${tag.color}; color: ${getTextColor(tag.color)};">
                ${escapeHtml(tag.name)}
                <i class="fas fa-times ms-1" onclick="removeCallLogTag(${index})" style="cursor: pointer;" title="ลบ tag"></i>
            </span>
        `;
    });
    
    previewContainer.innerHTML = html;
}

/**
 * เพิ่ม Tag ใน Preview (ไม่บันทึกลงฐานข้อมูลทันที)
 */
function addCallLogTag(tagName, tagColor) {
    // ตรวจสอบว่ามี tag นี้แล้วหรือไม่
    const existingTag = window.callLogTags.find(tag => tag.name === tagName);
    if (existingTag) {
        showErrorMessage(`Tag "${tagName}" มีอยู่แล้ว`);
        return;
    }
    
    window.callLogTags.push({
        name: tagName,
        color: tagColor || '#007bff'
    });
    
    updateCallTagsPreview();
    // ไม่แสดง notification ที่นี่ เพราะจะแสดงตอนบันทึกรวม
    // showSuccessMessage(`เพิ่ม Tag "${tagName}" แล้ว`);
}

/**
 * ลบ Tag จาก Preview
 */
function removeCallLogTag(index) {
    if (index >= 0 && index < window.callLogTags.length) {
        const removedTag = window.callLogTags.splice(index, 1)[0];
        updateCallTagsPreview();
        // ไม่แสดง notification ที่นี่ เพราะเป็นการลบ preview
        // showSuccessMessage(`ลบ Tag "${removedTag.name}" แล้ว`);
    }
}

/**
 * ล้าง Tags Preview (เรียกใช้เมื่อเปิด modal ใหม่)
 */
function clearCallLogTags() {
    window.callLogTags = [];
    updateCallTagsPreview();
}

/**
 * บันทึก Tags ทั้งหมดหลังจากบันทึกการโทรสำเร็จ
 */
async function saveCallLogTags(customerId) {
    if (!window.callLogTags || window.callLogTags.length === 0) {
        return true; // ไม่มี tags ให้บันทึก
    }
    
    try {
        const tagCount = window.callLogTags.length;
        
        for (const tag of window.callLogTags) {
            // ส่ง showNotification = false เพื่อไม่ให้แสดง notification แยก
            await addCustomerTag(customerId, tag.name, tag.color, false);
        }
        
        // แสดง notification รวมครั้งเดียว
        if (tagCount === 1) {
            showSuccessMessage(`บันทึก Tag สำเร็จ`);
        } else {
            showSuccessMessage(`บันทึก ${tagCount} Tags สำเร็จ`);
        }
        
        // ล้าง preview หลังบันทึกสำเร็จ
        clearCallLogTags();
        return true;
    } catch (error) {
        console.error('Error saving call log tags:', error);
        showErrorMessage('เกิดข้อผิดพลาดในการบันทึก Tags');
        return false;
    }
}

/**
 * คำนวณสีตัวอักษรที่เหมาะสมตามพื้นหลัง
 */
function getTextColor(backgroundColor) {
    // แปลง hex เป็น RGB
    const hex = backgroundColor.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);
    
    // คำนวณ brightness
    const brightness = (r * 299 + g * 587 + b * 114) / 1000;
    
    // ถ้า brightness มาก ใช้ตัวอักษรสีดำ ถ้าน้อย ใช้สีขาว
    return brightness > 128 ? '#000000' : '#ffffff';
}

/**
 * แสดง modal สำหรับเพิ่ม tag ในฟอร์มบันทึกการโทร (Preview mode)
 */
function showAddTagModalForCallLog() {
    const modalHtml = `
        <div class="modal fade" id="addTagCallLogModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">เพิ่ม Tag สำหรับการโทร</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Tag จะถูกบันทึกหลังจากที่คุณบันทึกการโทรสำเร็จ
                        </div>
                        <form id="addTagCallLogForm">
                            <div class="mb-3">
                                <label for="tagNameCallLog" class="form-label">ชื่อ Tag</label>
                                <input type="text" class="form-control" id="tagNameCallLog" placeholder="ใส่ชื่อ tag" required>
                                <div class="form-text">หรือเลือกจาก tags ที่เคยใช้:</div>
                                <div id="suggestedTagsCallLog" class="mt-2">
                                    ${renderSuggestedTags()}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="tagColorCallLog" class="form-label">สี</label>
                                <div class="d-flex flex-wrap gap-2">
                                    ${renderColorPickerForCallLog()}
                                </div>
                                <input type="hidden" id="selectedColorCallLog" value="#007bff">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" class="btn btn-primary" onclick="submitAddTagForCallLog()">เพิ่ม Tag</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // ลบ modal เก่า (ถ้ามี)
    const existingModal = document.getElementById('addTagCallLogModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // เพิ่ม modal ใหม่
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // แสดง modal
    const modal = new bootstrap.Modal(document.getElementById('addTagCallLogModal'));
    modal.show();
}

/**
 * สร้าง Color Picker สำหรับ Call Log Modal
 */
function renderColorPickerForCallLog() {
    let html = '';
    TAG_COLORS.forEach((color, index) => {
        const isSelected = index === 0 ? 'selected' : '';
        html += `
            <div class="color-option ${isSelected}" 
                 style="background-color: ${color}; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; border: 3px solid ${index === 0 ? '#000' : 'transparent'};"
                 onclick="selectColorForCallLog('${color}', this)">
            </div>
        `;
    });
    return html;
}

/**
 * เลือกสีสำหรับ Call Log Tag
 */
function selectColorForCallLog(color, element) {
    // ลบการเลือกสีเก่า
    document.querySelectorAll('#addTagCallLogModal .color-option').forEach(option => {
        option.style.border = '3px solid transparent';
        option.classList.remove('selected');
    });
    
    // เลือกสีใหม่
    element.style.border = '3px solid #000';
    element.classList.add('selected');
    document.getElementById('selectedColorCallLog').value = color;
}

/**
 * บันทึก Tag ใน Preview (Call Log Modal)
 */
function submitAddTagForCallLog() {
    const tagName = document.getElementById('tagNameCallLog').value.trim();
    const tagColor = document.getElementById('selectedColorCallLog').value;
    
    if (!tagName) {
        showErrorMessage('กรุณาใส่ชื่อ Tag');
        return;
    }
    
    // เพิ่มใน Preview
    addCallLogTag(tagName, tagColor);
    
    // ปิด modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('addTagCallLogModal'));
    modal.hide();
    
    // ล้างฟอร์ม
    document.getElementById('addTagCallLogForm').reset();
}

// เก็บฟังก์ชันเก่าไว้เผื่อใช้ที่อื่น
function toggleTagSelection(checkboxId) {
    const checkbox = document.getElementById(checkboxId);
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        updateModalSelectedCount();
    }
}

function restoreModalTagState() {
    try {
        const savedTags = sessionStorage.getItem('selectedTagFilters');
        if (savedTags) {
            const selectedTags = JSON.parse(savedTags);
            selectedTags.forEach(tagName => {
                const tagElement = document.querySelector(`#modalTagFilterOptions .tag-selectable[data-tag-name="${tagName}"]`);
                if (tagElement) {
                    tagElement.classList.add('selected');
                    const tagColor = tagElement.getAttribute('data-tag-color');
                    const oppositeColor = getOppositeColor(tagColor);
                    tagElement.style.border = `3px solid ${oppositeColor}`;
                    tagElement.style.boxShadow = `0 0 0 1px ${oppositeColor}`;
                }
            });
            updateModalSelectedCountNew();
        }
    } catch (error) {
        console.error('Error restoring modal tag state:', error);
    }
}

function clearModalTagFilter() {
    const tagOptions = document.querySelectorAll('#modalTagFilterOptions .tag-selectable');
    tagOptions.forEach(option => {
        option.classList.remove('selected');
        option.style.border = '2px solid transparent';
        option.style.boxShadow = 'none';
    });
    updateModalSelectedCountNew();
}

function applyModalTagFilter() {
    // ดึง selected tags
    const selectedTags = Array.from(document.querySelectorAll('#modalTagFilterOptions .tag-selectable.selected'))
        .map(element => element.dataset.tagName);
    
    // บันทึกสถานะ
    sessionStorage.setItem('selectedTagFilters', JSON.stringify(selectedTags));
    
    // อัปเดต badge count
    const countBadge = document.getElementById('selectedTagsCount');
    if (countBadge) {
        countBadge.textContent = selectedTags.length;
    }
    
    // ปิด modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('tagFilterModal'));
    modal.hide();
    
    // ใช้การกรอง
    if (selectedTags.length > 0) {
        searchCustomersByTags(selectedTags).then(customers => {
            // ใช้ renderStandardTable แทน renderAllCustomersTable ที่ไม่มีอยู่
            if (typeof renderStandardTable === 'function') {
                renderStandardTable(customers, 'allCustomersTable', 'ไม่มีลูกค้าที่มี tags ที่เลือก');
                
                // อัปเดต badge count
                const countBadge = document.getElementById('allCustomersCount');
                if (countBadge) {
                    countBadge.textContent = customers.length;
                }
                
                // เพิ่ม pagination
                setTimeout(() => {
                    const table = document.querySelector('#allCustomersTable table');
                    const paginationContainer = document.getElementById('allCustomersTable-pagination');
                    if (table && paginationContainer) {
                        paginationContainer.innerHTML = ''; // ลบ pagination เก่า
                        if (typeof paginateTable === 'function') {
                            paginateTable(table, 'allCustomersTable-pagination', 10, 'customers_page_allCustomersTable');
                        }
                    }
                }, 100);
            } else {
                console.error('renderStandardTable function not found');
            }
        }).catch(error => {
            console.error('Error applying tag filter:', error);
            alert('เกิดข้อผิดพลาดในการกรอง tags');
        });
    } else {
        // ถ้าไม่มี tag ที่เลือก ให้โหลดลูกค้าทั้งหมด
        if (typeof loadAllCustomers === 'function') {
            loadAllCustomers();
        }
    }
}

/**
 * โหลดสถานะ tag filters ที่เลือกไว้ก่อนหน้า
 */
function loadPreviousTagFilters() {
    setTimeout(() => {
        try {
            const savedTags = sessionStorage.getItem('selectedTagFilters');
            if (savedTags) {
                const selectedTags = JSON.parse(savedTags);
                
                selectedTags.forEach(tagName => {
                    const tagElement = document.querySelector(`#modalTagFilterOptions .tag-selectable[data-tag-name="${tagName}"]`);
                    if (tagElement) {
                        // เลือก tag
                        tagElement.classList.add('selected');
                        const tagColor = tagElement.dataset.tagColor || '#007bff';
                        const oppositeColor = getOppositeColor(tagColor);
                        tagElement.style.border = `3px solid ${oppositeColor}`;
                        tagElement.style.boxShadow = `0 0 0 1px ${oppositeColor}`;
                    }
                });
                
                // อัปเดต count
                updateModalSelectedCountNew();
            }
        } catch (error) {
            console.error('Error loading previous tag filters:', error);
        }
    }, 500); // รอให้ modal โหลดเสร็จก่อน
}

// โหลด user tags เมื่อเริ่มต้น
document.addEventListener('DOMContentLoaded', function() {
    loadUserTags();
    
    // โหลด tag filter options ถ้ามี element
    setTimeout(() => {
        if (document.getElementById('tagFilterOptions')) {
            loadTagFilterOptions();
        }
    }, 1000);
});
