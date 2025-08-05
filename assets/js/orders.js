/**
 * Order Management JavaScript
 * จัดการฟังก์ชัน JavaScript สำหรับระบบจัดการคำสั่งซื้อ
 */

class OrderManager {
    constructor() {
        this.initializeEventListeners();
        this.loadExistingData();
    }

    /**
     * เริ่มต้น Event Listeners
     */
    initializeEventListeners() {
        // Product search
        const productSearch = document.getElementById('product_search');
        if (productSearch) {
            productSearch.addEventListener('input', this.handleProductSearch.bind(this));
        }

        // Hide product results when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.product-search')) {
                const results = document.getElementById('productResults');
                if (results) {
                    results.style.display = 'none';
                }
            }
        });

        // Discount percentage change
        const discountPercentage = document.getElementById('discount_percentage');
        if (discountPercentage) {
            discountPercentage.addEventListener('input', this.updateSummary.bind(this));
        }

        // Form submission
        const orderForm = document.getElementById('orderForm');
        if (orderForm) {
            orderForm.addEventListener('submit', this.handleFormSubmission.bind(this));
        }

        // Use customer address checkbox
        const useCustomerAddress = document.getElementById('use_customer_address');
        if (useCustomerAddress) {
            useCustomerAddress.addEventListener('change', this.handleUseCustomerAddressChange.bind(this));
        }

        // Customer selection change
        const customerSelect = document.getElementById('customer_id');
        if (customerSelect) {
            customerSelect.addEventListener('change', this.handleCustomerChange.bind(this));
            
            // โหลดข้อมูลลูกค้าถ้ามีการเลือกไว้แล้ว (กรณีที่มาจากหน้ารายละเอียดลูกค้า)
            if (customerSelect.value) {
                this.loadCustomerData(customerSelect.value);
            }
        }
    }

    /**
     * โหลดข้อมูลเดิม (สำหรับหน้าแก้ไข)
     */
    loadExistingData() {
        // รอให้ DOM พร้อมก่อน
        setTimeout(() => {
            // ถ้ามีข้อมูล orderItems อยู่แล้ว (หน้าแก้ไข)
            if (window.orderItems && window.orderItems.length > 0) {
                console.log('Loading existing order items:', window.orderItems);
                // แปลงข้อมูลให้เป็นตัวเลข
                window.orderItems.forEach(item => {
                    item.unit_price = parseFloat(item.unit_price || 0);
                    item.discount_amount = parseFloat(item.discount_amount || 0);
                    item.total_price = parseFloat(item.total_price || 0);
                    item.quantity = parseInt(item.quantity || 0);
                });
                console.log('Converted order items:', window.orderItems);
                this.updateOrderItemsTable();
                this.updateSummary();
            } else {
                console.log('No existing order items found');
            }
        }, 100);
    }

    /**
     * โหลดข้อมูลลูกค้าเมื่อมีการเลือก
     */
    loadCustomerData(customerId) {
        if (!customerId) {
            return;
        }
        
        // ดึงข้อมูลลูกค้าจาก API หรือจากข้อมูลที่มีอยู่
        const customerSelect = document.getElementById('customer_id');
        const selectedOption = customerSelect.options[customerSelect.selectedIndex];
        
        if (selectedOption && selectedOption.text) {
            // แสดงข้อมูลลูกค้าที่เลือก
            console.log('Selected customer:', selectedOption.text);
            
            // ถ้ามีการเลือกใช้ที่อยู่ลูกค้า ให้โหลดข้อมูลที่อยู่
            const useCustomerAddressCheckbox = document.getElementById('use_customer_address');
            if (useCustomerAddressCheckbox && useCustomerAddressCheckbox.checked) {
                this.loadCustomerAddress(customerId);
            }
        }
    }
    
    /**
     * โหลดที่อยู่ลูกค้า
     */
    async loadCustomerAddress(customerId) {
        try {
            const response = await fetch(`customers.php?action=get_customer_address&id=${customerId}`);
            const data = await response.json();
            
            if (data.success && data.customer) {
                const deliveryAddress = document.getElementById('delivery_address');
                if (deliveryAddress) {
                    const address = data.customer.address || '';
                    const province = data.customer.province || '';
                    deliveryAddress.value = `${address}${province ? ', ' + province : ''}`;
                }
            }
        } catch (error) {
            console.error('Error loading customer address:', error);
        }
    }

    /**
     * จัดการการเปลี่ยนแปลงการเลือกลูกค้า
     */
    handleCustomerChange(event) {
        const customerId = event.target.value;
        this.loadCustomerData(customerId);
    }

    /**
     * จัดการการเปลี่ยนแปลงการเลือกใช้ที่อยู่ลูกค้า
     */
    handleUseCustomerAddressChange(event) {
        const customerId = document.getElementById('customer_id').value;
        const deliveryAddress = document.getElementById('delivery_address');
        
        if (event.target.checked && customerId) {
            // ใช้ที่อยู่ลูกค้า
            this.loadCustomerAddress(customerId);
            if (deliveryAddress) {
                deliveryAddress.readOnly = true;
                deliveryAddress.style.backgroundColor = '#f8f9fa';
            }
        } else {
            // ใช้ที่อยู่ที่กรอกเอง
            if (deliveryAddress) {
                deliveryAddress.readOnly = false;
                deliveryAddress.style.backgroundColor = '';
                deliveryAddress.value = '';
            }
        }
    }

    /**
     * จัดการการค้นหาสินค้า
     */
    handleProductSearch(event) {
        const searchTerm = event.target.value.toLowerCase();
        const results = document.getElementById('productResults');
        
        if (searchTerm.length < 2) {
            results.style.display = 'none';
            return;
        }
        
        // Filter products
        const filteredProducts = window.products.filter(product => 
            product.product_name.toLowerCase().includes(searchTerm) ||
            product.product_code.toLowerCase().includes(searchTerm)
        );
        
        if (filteredProducts.length > 0) {
            results.innerHTML = filteredProducts.map(product => `
                <div class="product-item" onclick="orderManager.selectProduct(${product.product_id})">
                    <strong>${product.product_name}</strong><br>
                    <small class="text-muted">${product.product_code} - ${product.selling_price} บาท</small>
                </div>
            `).join('');
            results.style.display = 'block';
        } else {
            results.innerHTML = '<div class="product-item">ไม่พบสินค้า</div>';
            results.style.display = 'block';
        }
    }

    /**
     * เลือกสินค้า
     */
    selectProduct(productId) {
        const product = window.products.find(p => p.product_id == productId);
        if (product) {
            const searchInput = document.getElementById('product_search');
            searchInput.value = product.product_name;
            searchInput.dataset.productId = productId;
            searchInput.dataset.productPrice = product.selling_price;
            document.getElementById('productResults').style.display = 'none';
        }
    }

    /**
     * เพิ่มสินค้าลงในรายการ
     */
    addProduct() {
        const productId = document.getElementById('product_search').dataset.productId;
        const productName = document.getElementById('product_search').value;
        const quantity = parseInt(document.getElementById('quantity').value);
        
        if (!productId || !productName || quantity <= 0) {
            this.showAlert('กรุณาเลือกสินค้าและระบุจำนวน', 'warning');
            return;
        }
        
        const product = window.products.find(p => p.product_id == productId);
        if (!product) {
            this.showAlert('ไม่พบสินค้า', 'error');
            return;
        }
        
        // Check if product already exists
        const existingItem = window.orderItems.find(item => item.product_id == productId);
        if (existingItem) {
            existingItem.quantity += quantity;
            const discountPercentage = parseFloat(document.getElementById('discount_percentage').value) || 0;
            const unitPrice = parseFloat(existingItem.unit_price || 0);
            const subtotal = existingItem.quantity * unitPrice;
            existingItem.discount_amount = (subtotal * discountPercentage) / 100;
            existingItem.total_price = subtotal - existingItem.discount_amount;
        } else {
            const discountPercentage = parseFloat(document.getElementById('discount_percentage').value) || 0;
            const unitPrice = parseFloat(product.selling_price || 0);
            const subtotal = quantity * unitPrice;
            const discountAmount = (subtotal * discountPercentage) / 100;
            const totalPrice = subtotal - discountAmount;
            
            window.orderItems.push({
                product_id: productId,
                product_name: product.product_name,
                product_code: product.product_code,
                quantity: quantity,
                unit_price: unitPrice,
                discount_amount: discountAmount,
                total_price: totalPrice
            });
        }
        
        this.updateOrderItemsTable();
        this.updateSummary();
        this.clearProductForm();
    }

    /**
     * ลบสินค้าออกจากรายการ
     */
    removeItem(index) {
        window.orderItems.splice(index, 1);
        this.updateOrderItemsTable();
        this.updateSummary();
    }

    /**
     * อัปเดตจำนวนสินค้า
     */
    updateQuantity(index, quantity) {
        const item = window.orderItems[index];
        item.quantity = parseInt(quantity);
        const discountPercentage = parseFloat(document.getElementById('discount_percentage').value) || 0;
        const unitPrice = parseFloat(item.unit_price || 0);
        const subtotal = item.quantity * unitPrice;
        item.discount_amount = (subtotal * discountPercentage) / 100;
        item.total_price = subtotal - item.discount_amount;
        this.updateOrderItemsTable();
        this.updateSummary();
    }

    /**
     * อัปเดตตารางรายการสินค้า
     */
    updateOrderItemsTable() {
        const tbody = document.getElementById('orderItemsBody');
        
        if (window.orderItems.length === 0) {
            tbody.innerHTML = `
                <tr id="noItemsRow">
                    <td colspan="6" class="text-center py-4">
                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">ยังไม่มีรายการสินค้า</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = window.orderItems.map((item, index) => `
            <tr>
                <td>
                    <strong>${item.product_name}</strong>
                    <small class="text-muted d-block">${item.product_code}</small>
                </td>
                <td class="text-center">
                    <input type="number" class="form-control form-control-sm quantity-input" 
                           value="${parseInt(item.quantity || 0)}" min="1" max="999"
                           onchange="orderManager.updateQuantity(${index}, this.value)">
                </td>
                <td class="text-end">฿${parseFloat(item.unit_price || 0).toFixed(2)}</td>
                <td class="text-end">฿${parseFloat(item.discount_amount || 0).toFixed(2)}</td>
                <td class="text-end">
                    <strong>฿${parseFloat(item.total_price || 0).toFixed(2)}</strong>
                </td>
                <td class="text-center">
                    <i class="fas fa-trash remove-item" onclick="orderManager.removeItem(${index})" title="ลบ"></i>
                </td>
            </tr>
        `).join('');
    }

    /**
     * อัปเดตสรุปคำสั่งซื้อ
     */
    updateSummary() {
        const subtotal = window.orderItems.reduce((sum, item) => {
            const totalPrice = parseFloat(item.total_price || 0);
            return sum + totalPrice;
        }, 0);
        
        // คำนวณส่วนลดรวมจากรายการสินค้าแต่ละรายการ
        const totalDiscount = window.orderItems.reduce((sum, item) => {
            const discountAmount = parseFloat(item.discount_amount || 0);
            return sum + discountAmount;
        }, 0);
        
        // คำนวณยอดรวมก่อนส่วนลด
        const grossTotal = window.orderItems.reduce((sum, item) => {
            const unitPrice = parseFloat(item.unit_price || 0);
            const quantity = parseInt(item.quantity || 0);
            return sum + (unitPrice * quantity);
        }, 0);
        
        // ตรวจสอบว่า element มีอยู่หรือไม่ก่อนเข้าถึง
        const itemCountElement = document.getElementById('item_count');
        const subtotalElement = document.getElementById('subtotal');
        const discountAmountElement = document.getElementById('discount_amount');
        const netAmountElement = document.getElementById('net_amount');
        
        if (itemCountElement) {
            itemCountElement.textContent = `${window.orderItems.length} รายการ`;
        }
        if (subtotalElement) {
            subtotalElement.textContent = `฿${grossTotal.toFixed(2)}`;
        }
        if (discountAmountElement) {
            discountAmountElement.textContent = `฿${totalDiscount.toFixed(2)}`;
        }
        if (netAmountElement) {
            netAmountElement.textContent = `฿${subtotal.toFixed(2)}`;
        }
        
        // Enable/disable submit button
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = window.orderItems.length === 0;
        }
    }

    /**
     * ล้างฟอร์มสินค้า
     */
    clearProductForm() {
        const searchInput = document.getElementById('product_search');
        searchInput.value = '';
        searchInput.dataset.productId = '';
        searchInput.dataset.productPrice = '';
        document.getElementById('quantity').value = '1';
    }

    /**
     * จัดการการส่งฟอร์ม
     */
    handleFormSubmission(event) {
        event.preventDefault();
        
        // ป้องกันการบันทึกข้อมูลซ้ำ
        const submitBtn = document.getElementById('submitBtn');
        if (!submitBtn) {
            console.error('Submit button not found');
            return;
        }
        
        if (submitBtn.disabled) {
            return;
        }
        
        // ปิดปุ่มและเปลี่ยนข้อความ
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>กำลังบันทึก...';
        
        const customerId = document.getElementById('customer_id').value;
        if (!customerId) {
            this.showAlert('กรุณาเลือกลูกค้า', 'warning');
            this.resetSubmitButton();
            return;
        }
        
        if (window.orderItems.length === 0) {
            this.showAlert('กรุณาเพิ่มสินค้าอย่างน้อย 1 รายการ', 'warning');
            this.resetSubmitButton();
            return;
        }
        
        const formData = {
            customer_id: customerId,
            items: window.orderItems,
            payment_method: document.getElementById('payment_method').value,
            payment_status: 'pending', // Default value
            delivery_date: document.getElementById('order_date').value || null,
            delivery_address: document.getElementById('delivery_address').value || null,
            use_customer_address: document.getElementById('use_customer_address').checked,
            discount_percentage: parseFloat(document.getElementById('discount_percentage').value) || 0,
            notes: document.getElementById('notes').value || null
        };
        
        // เพิ่ม order_id สำหรับหน้าแก้ไข
        if (window.orderId) {
            formData.order_id = window.orderId;
        }
        
        this.submitOrder(formData);
    }

    /**
     * ส่งคำสั่งซื้อ
     */
    async submitOrder(formData) {
        try {
            // ตรวจสอบว่าเป็นหน้าแก้ไขหรือสร้างใหม่
            const isEditPage = window.location.href.includes('action=edit');
            const action = isEditPage ? 'update' : 'store';
            const orderId = window.orderId || formData.order_id;
            
            let url = `orders.php?action=${action}`;
            if (isEditPage && orderId) {
                url += `&id=${orderId}`;
            }
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                const message = isEditPage ? 'แก้ไขคำสั่งซื้อสำเร็จ' : 'สร้างคำสั่งซื้อสำเร็จ\nหมายเลข: ' + result.order_number;
                this.showAlert(message, 'success');
                setTimeout(() => {
                    window.location.href = 'orders.php?action=show&id=' + result.order_id;
                }, 2000);
            } else {
                this.showAlert('เกิดข้อผิดพลาด: ' + result.message, 'error');
                this.resetSubmitButton();
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            this.resetSubmitButton();
        }
    }

    /**
     * รีเซ็ตปุ่มบันทึก
     */
    resetSubmitButton() {
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            // ตรวจสอบว่าเป็นหน้าแก้ไขหรือสร้างใหม่
            const isEditPage = window.location.href.includes('action=edit');
            const buttonText = isEditPage ? 'บันทึกการแก้ไข' : 'บันทึกคำสั่งซื้อ';
            submitBtn.innerHTML = `<i class="fas fa-save me-1"></i>${buttonText}`;
        }
    }

    /**
     * อัปเดตสถานะคำสั่งซื้อ
     */
    updateStatus(orderId) {
        // ตรวจสอบว่าอยู่ในหน้า show หรือไม่
        const statusModal = document.getElementById('statusModal');
        if (statusModal) {
            // อยู่ในหน้า show - ใช้ modal ที่มีอยู่
            document.getElementById('orderId').value = orderId;
            new bootstrap.Modal(statusModal).show();
        } else {
            // อยู่ในหน้าอื่น - ใช้ modal แบบเดิม
            document.getElementById('orderId').value = orderId;
            document.getElementById('statusType').value = 'payment_status';
            this.updateFieldName();
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }
    }

    /**
     * อัปเดตชื่อฟิลด์ตามประเภทสถานะ
     */
    updateFieldName() {
        const statusType = document.getElementById('statusType');
        const statusValue = document.getElementById('statusValue');
        
        // ตรวจสอบว่า element มีอยู่หรือไม่
        if (!statusType || !statusValue) {
            return;
        }
        
        const fieldName = document.getElementById('fieldName');
        if (fieldName) {
            fieldName.value = statusType.value;
        }
        
        // อัปเดตตัวเลือกตามประเภทสถานะ
        statusValue.innerHTML = '';
        
        if (statusType.value === 'payment_status') {
            const options = [
                {value: 'pending', text: 'รอชำระเงิน'},
                {value: 'paid', text: 'ชำระเงินแล้ว'},
                {value: 'partial', text: 'ชำระเงินบางส่วน'},
                {value: 'cancelled', text: 'ยกเลิก'}
            ];
            options.forEach(option => {
                const opt = document.createElement('option');
                opt.value = option.value;
                opt.textContent = option.text;
                statusValue.appendChild(opt);
            });
        } else {
            const options = [
                {value: 'pending', text: 'รอจัดส่ง'},
                {value: 'shipped', text: 'จัดส่งแล้ว'},
                {value: 'delivered', text: 'จัดส่งสำเร็จ'},
                {value: 'cancelled', text: 'ยกเลิก'}
            ];
            options.forEach(option => {
                const opt = document.createElement('option');
                opt.value = option.value;
                opt.textContent = option.text;
                statusValue.appendChild(opt);
            });
        }
    }

    /**
     * บันทึกสถานะ
     */
    async saveStatus() {
        const formData = new FormData(document.getElementById('statusForm'));
        const data = Object.fromEntries(formData.entries());
        
        try {
            const response = await fetch('orders.php?action=update_status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('อัปเดตสถานะสำเร็จ', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                this.showAlert('เกิดข้อผิดพลาด: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        }
    }

    /**
     * แสดงข้อความแจ้งเตือน
     */
    showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.main-content') || document.body;
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

// Global functions for backward compatibility
function addProduct() {
    if (window.orderManager) {
        window.orderManager.addProduct();
    }
}

function updateStatus(orderId) {
    if (window.orderManager) {
        window.orderManager.updateStatus(orderId);
    }
}

function updateFieldName() {
    if (window.orderManager) {
        window.orderManager.updateFieldName();
    }
}

function saveStatus() {
    if (window.orderManager) {
        window.orderManager.saveStatus();
    }
}

/**
 * แสดงข้อความแจ้งเตือน (Global function)
 */
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.main-content') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

/**
 * ลบคำสั่งซื้อ
 */
function deleteOrder(orderId) {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบคำสั่งซื้อนี้? การดำเนินการนี้ไม่สามารถยกเลิกได้')) {
        fetch(`orders.php?action=delete&id=${orderId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('ลบคำสั่งซื้อสำเร็จ', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showAlert('เกิดข้อผิดพลาดในการลบคำสั่งซื้อ: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize global variables
    window.orderItems = window.orderItems || [];
    window.products = window.products || [];
    
    // Initialize OrderManager
    window.orderManager = new OrderManager();
}); 