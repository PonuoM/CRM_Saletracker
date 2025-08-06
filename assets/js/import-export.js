/**
 * Import/Export JavaScript
 * จัดการการนำเข้าและส่งออกข้อมูล
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize forms
    initializeSalesImportForm();
    initializeCustomersOnlyImportForm();
    initializeBackupRestore();
    
    // Initialize backup button
    const createBackupBtn = document.getElementById('createBackupBtn');
    if (createBackupBtn) {
        createBackupBtn.addEventListener('click', createBackup);
    }
});

/**
 * Initialize Sales Import Form
 */
function initializeSalesImportForm() {
    const form = document.getElementById('importSalesForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const resultsDiv = document.getElementById('salesImportResults');
        const messageDiv = document.getElementById('salesImportMessage');
        const detailsDiv = document.getElementById('salesImportDetails');
        
        // Show loading
        resultsDiv.style.display = 'block';
        resultsDiv.className = 'alert alert-info';
        messageDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังนำเข้ายอดขาย...';
        detailsDiv.innerHTML = '';
        
        fetch('import-export.php?action=importSales', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                // Error
                resultsDiv.className = 'alert alert-danger';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>เกิดข้อผิดพลาด';
                detailsDiv.innerHTML = data.error;
            } else if (data.success !== undefined) {
                // Success
                resultsDiv.className = 'alert alert-success';
                messageDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>นำเข้ายอดขายสำเร็จ!';
                
                let details = `
                    <div class="row">
                        <div class="col-md-3">
                            <strong>รวมทั้งหมด:</strong> ${data.total} รายการ
                        </div>
                        <div class="col-md-3">
                            <strong>สำเร็จ:</strong> ${data.success} รายการ
                        </div>
                        <div class="col-md-3">
                            <strong>ลูกค้าใหม่:</strong> ${data.customers_created || 0} ราย
                        </div>
                        <div class="col-md-3">
                            <strong>อัพเดทลูกค้า:</strong> ${data.customers_updated || 0} ราย
                        </div>
                    </div>
                `;
                
                if (data.errors && data.errors.length > 0) {
                    details += `
                        <div class="mt-3">
                            <strong>ข้อผิดพลาด:</strong>
                            <ul class="mb-0">
                                ${data.errors.map(error => `<li>${error}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                }
                
                detailsDiv.innerHTML = details;
                
                // Reset form
                form.reset();
            } else {
                // Unknown response
                resultsDiv.className = 'alert alert-warning';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>ไม่ทราบผลลัพธ์';
                detailsDiv.innerHTML = 'กรุณาลองใหม่อีกครั้ง';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultsDiv.className = 'alert alert-danger';
            messageDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>เกิดข้อผิดพลาดในการเชื่อมต่อ';
            detailsDiv.innerHTML = 'กรุณาลองใหม่อีกครั้ง';
        });
    });
}

/**
 * Initialize Customers Only Import Form
 */
function initializeCustomersOnlyImportForm() {
    const form = document.getElementById('importCustomersOnlyForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const resultsDiv = document.getElementById('customersOnlyImportResults');
        const messageDiv = document.getElementById('customersOnlyImportMessage');
        const detailsDiv = document.getElementById('customersOnlyImportDetails');
        
        // Show loading
        resultsDiv.style.display = 'block';
        resultsDiv.className = 'alert alert-info';
        messageDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังนำเข้าส่วนรายชื่อ...';
        detailsDiv.innerHTML = '';
        
        fetch('import-export.php?action=importCustomersOnly', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                // Error
                resultsDiv.className = 'alert alert-danger';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>เกิดข้อผิดพลาด';
                detailsDiv.innerHTML = data.error;
            } else if (data.success !== undefined) {
                // Success
                resultsDiv.className = 'alert alert-success';
                messageDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>นำเข้าส่วนรายชื่อสำเร็จ!';
                
                let details = `
                    <div class="row">
                        <div class="col-md-3">
                            <strong>รวมทั้งหมด:</strong> ${data.total} รายการ
                        </div>
                        <div class="col-md-3">
                            <strong>สำเร็จ:</strong> ${data.success} รายการ
                        </div>
                        <div class="col-md-3">
                            <strong>ลูกค้าใหม่:</strong> ${data.customers_created || 0} ราย
                        </div>
                        <div class="col-md-3">
                            <strong>ข้าม:</strong> ${data.customers_skipped || 0} ราย
                        </div>
                    </div>
                `;
                
                if (data.errors && data.errors.length > 0) {
                    details += `
                        <div class="mt-3">
                            <strong>ข้อผิดพลาด:</strong>
                            <ul class="mb-0">
                                ${data.errors.map(error => `<li>${error}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                }
                
                detailsDiv.innerHTML = details;
                
                // Reset form
                form.reset();
            } else {
                // Unknown response
                resultsDiv.className = 'alert alert-warning';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>ไม่ทราบผลลัพธ์';
                detailsDiv.innerHTML = 'กรุณาลองใหม่อีกครั้ง';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultsDiv.className = 'alert alert-danger';
            messageDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>เกิดข้อผิดพลาดในการเชื่อมต่อ';
            detailsDiv.innerHTML = 'กรุณาลองใหม่อีกครั้ง';
        });
    });
}

/**
 * Initialize Backup Restore
 */
function initializeBackupRestore() {
    const backupFileSelect = document.getElementById('backupFile');
    const restoreBtn = document.getElementById('restoreBackupBtn');
    const restoreResultDiv = document.getElementById('restoreResult');
    const restoreMessageDiv = document.getElementById('restoreMessage');
    
    if (!backupFileSelect || !restoreBtn) return;
    
    // Enable/disable restore button based on file selection
    backupFileSelect.addEventListener('change', function() {
        restoreBtn.disabled = !this.value;
    });
    
    // Handle restore button click
    restoreBtn.addEventListener('click', function() {
        const selectedFile = backupFileSelect.value;
        if (!selectedFile) return;
        
        if (!confirm('คุณแน่ใจหรือไม่ที่จะ Restore ข้อมูล? การดำเนินการนี้จะทับข้อมูลปัจจุบันทั้งหมด!')) {
            return;
        }
        
        // Show loading
        restoreResultDiv.style.display = 'block';
        restoreResultDiv.className = 'alert alert-info';
        restoreMessageDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลัง Restore ข้อมูล...';
        
        const formData = new FormData();
        formData.append('backup_file', selectedFile);
        
        fetch('import-export.php?action=restoreBackup', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                restoreResultDiv.className = 'alert alert-success';
                restoreMessageDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>Restore สำเร็จ!';
                
                // Reload page after 2 seconds
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                restoreResultDiv.className = 'alert alert-danger';
                restoreMessageDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Restore ล้มเหลว';
                if (data.error) {
                    restoreMessageDiv.innerHTML += '<br>' + data.error;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            restoreResultDiv.className = 'alert alert-danger';
            restoreMessageDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>เกิดข้อผิดพลาดในการเชื่อมต่อ';
        });
    });
    
    // Handle restore from table buttons
    const restoreFileBtns = document.querySelectorAll('.restore-file-btn');
    restoreFileBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const fileName = this.getAttribute('data-file');
            if (!fileName) return;
            
            if (!confirm(`คุณแน่ใจหรือไม่ที่จะ Restore จากไฟล์ "${fileName}"? การดำเนินการนี้จะทับข้อมูลปัจจุบันทั้งหมด!`)) {
                return;
            }
            
            // Set the select value and trigger restore
            backupFileSelect.value = fileName;
            restoreBtn.disabled = false;
            restoreBtn.click();
        });
    });
}

/**
 * Create Backup
 */
function createBackup() {
    const resultDiv = document.getElementById('backupResult');
    const messageDiv = document.getElementById('backupMessage');
    
    if (!resultDiv || !messageDiv) return;
    
    // Show loading
    resultDiv.style.display = 'block';
    resultDiv.className = 'alert alert-info';
    messageDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังสร้าง Backup...';
    
    fetch('import-export.php?action=createBackup', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.className = 'alert alert-success';
            messageDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>สร้าง Backup สำเร็จ!';
            
            // Reload page after 2 seconds to show new backup file
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            resultDiv.className = 'alert alert-danger';
            messageDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>สร้าง Backup ล้มเหลว';
            if (data.error) {
                messageDiv.innerHTML += '<br>' + data.error;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resultDiv.className = 'alert alert-danger';
        messageDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>เกิดข้อผิดพลาดในการเชื่อมต่อ';
    });
} 