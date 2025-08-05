/**
 * Import/Export JavaScript
 * จัดการการนำเข้าและส่งออกข้อมูล
 */

$(document).ready(function() {
    
    // Import Customers Form
    $('#importCustomersForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>กำลังนำเข้าข้อมูล...');
        submitBtn.prop('disabled', true);
        
        // Hide previous results
        $('#importResults').hide();
        
        $.ajax({
            url: 'import-export.php?action=importCustomers',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    showImportResults(result);
                } catch (e) {
                    showImportResults({ error: 'เกิดข้อผิดพลาดในการประมวลผลข้อมูล' });
                }
            },
            error: function(xhr, status, error) {
                showImportResults({ error: 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error });
            },
            complete: function() {
                // Reset button state
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        });
    });
    
    // Create Backup
    $('#createBackupBtn').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();
        
        // Show loading state
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i>กำลังสร้าง Backup...');
        btn.prop('disabled', true);
        
        // Hide previous results
        $('#backupResult').hide();
        
        $.ajax({
            url: 'import-export.php?action=createBackup',
            type: 'POST',
            success: function(response) {
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    showBackupResult(result);
                } catch (e) {
                    showBackupResult({ success: false, error: 'เกิดข้อผิดพลาดในการประมวลผลข้อมูล' });
                }
            },
            error: function(xhr, status, error) {
                showBackupResult({ success: false, error: 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error });
            },
            complete: function() {
                // Reset button state
                btn.html(originalText);
                btn.prop('disabled', false);
            }
        });
    });
    
    // Restore Backup
    $('#restoreBackupBtn').on('click', function() {
        const backupFile = $('#backupFile').val();
        if (!backupFile) {
            alert('กรุณาเลือกไฟล์ backup');
            return;
        }
        
        if (!confirm('คุณแน่ใจหรือไม่ที่จะ restore ข้อมูล? การดำเนินการนี้จะทับข้อมูลปัจจุบันทั้งหมด!')) {
            return;
        }
        
        const btn = $(this);
        const originalText = btn.html();
        
        // Show loading state
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i>กำลัง Restore...');
        btn.prop('disabled', true);
        
        // Hide previous results
        $('#restoreResult').hide();
        
        $.ajax({
            url: 'import-export.php?action=restoreBackup',
            type: 'POST',
            data: { backup_file: backupFile },
            success: function(response) {
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    showRestoreResult(result);
                } catch (e) {
                    showRestoreResult({ success: false, error: 'เกิดข้อผิดพลาดในการประมวลผลข้อมูล' });
                }
            },
            error: function(xhr, status, error) {
                showRestoreResult({ success: false, error: 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error });
            },
            complete: function() {
                // Reset button state
                btn.html(originalText);
                btn.prop('disabled', false);
            }
        });
    });
    
    // Restore from table button
    $('.restore-file-btn').on('click', function() {
        const fileName = $(this).data('file');
        $('#backupFile').val(fileName);
        $('#restoreBackupBtn').prop('disabled', false);
        
        // Switch to backup tab
        $('#backup-tab').tab('show');
        
        // Scroll to restore section
        $('html, body').animate({
            scrollTop: $('#restoreBackupBtn').offset().top - 100
        }, 500);
    });
    
    // Enable/disable restore button based on file selection
    $('#backupFile').on('change', function() {
        const hasFile = $(this).val() !== '';
        $('#restoreBackupBtn').prop('disabled', !hasFile);
    });
    
    // File input change handler
    $('#csvFile').on('change', function() {
        const file = this.files[0];
        if (file) {
            // Validate file type
            if (!file.name.toLowerCase().endsWith('.csv')) {
                alert('กรุณาเลือกไฟล์ CSV เท่านั้น');
                this.value = '';
                return;
            }
            
            // Validate file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 5MB)');
                this.value = '';
                return;
            }
        }
    });
    
    // Date range validation
    $('#startDate, #endDate').on('change', function() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        if (startDate && endDate && startDate > endDate) {
            alert('วันที่เริ่มต้นต้องไม่เกินวันที่สิ้นสุด');
            $(this).val('');
        }
    });
    
    $('#summaryStartDate, #summaryEndDate').on('change', function() {
        const startDate = $('#summaryStartDate').val();
        const endDate = $('#summaryEndDate').val();
        
        if (startDate && endDate && startDate > endDate) {
            alert('วันที่เริ่มต้นต้องไม่เกินวันที่สิ้นสุด');
            $(this).val('');
        }
    });
    
    // Helper functions
    function showImportResults(result) {
        const resultsDiv = $('#importResults');
        const messageDiv = $('#importMessage');
        const detailsDiv = $('#importDetails');
        
        if (result.error) {
            messageDiv.html('<i class="fas fa-exclamation-triangle me-2"></i>' + result.error);
            resultsDiv.find('.alert').removeClass('alert-success').addClass('alert-danger');
        } else {
            const successRate = result.total > 0 ? ((result.success / result.total) * 100).toFixed(1) : 0;
            messageDiv.html('<i class="fas fa-check-circle me-2"></i>นำเข้าข้อมูลสำเร็จ ' + result.success + ' รายการ จาก ' + result.total + ' รายการ (' + successRate + '%)');
            resultsDiv.find('.alert').removeClass('alert-danger').addClass('alert-success');
            
            if (result.errors && result.errors.length > 0) {
                let errorList = '<ul class="mb-0 mt-2">';
                result.errors.slice(0, 5).forEach(function(error) {
                    errorList += '<li>' + error + '</li>';
                });
                if (result.errors.length > 5) {
                    errorList += '<li>และอีก ' + (result.errors.length - 5) + ' ข้อผิดพลาด</li>';
                }
                errorList += '</ul>';
                detailsDiv.html('<strong>ข้อผิดพลาด:</strong>' + errorList);
            } else {
                detailsDiv.html('');
            }
        }
        
        resultsDiv.show();
        
        // Scroll to results
        $('html, body').animate({
            scrollTop: resultsDiv.offset().top - 100
        }, 500);
    }
    
    function showBackupResult(result) {
        const resultsDiv = $('#backupResult');
        const messageDiv = $('#backupMessage');
        
        if (result.success) {
            messageDiv.html('<i class="fas fa-check-circle me-2"></i>สร้าง Backup สำเร็จ<br>' +
                          '<strong>ไฟล์:</strong> ' + result.file + '<br>' +
                          '<strong>ขนาด:</strong> ' + formatFileSize(result.size) + '<br>' +
                          '<strong>เวลา:</strong> ' + result.timestamp);
            resultsDiv.find('.alert').removeClass('alert-danger').addClass('alert-success');
            
            // Reload page after 2 seconds to show new backup file
            setTimeout(function() {
                location.reload();
            }, 2000);
        } else {
            messageDiv.html('<i class="fas fa-exclamation-triangle me-2"></i>' + (result.error || 'เกิดข้อผิดพลาดในการสร้าง Backup'));
            resultsDiv.find('.alert').removeClass('alert-success').addClass('alert-danger');
        }
        
        resultsDiv.show();
    }
    
    function showRestoreResult(result) {
        const resultsDiv = $('#restoreResult');
        const messageDiv = $('#restoreMessage');
        
        if (result.success) {
            messageDiv.html('<i class="fas fa-check-circle me-2"></i>' + (result.message || 'Restore สำเร็จ'));
            resultsDiv.find('.alert').removeClass('alert-danger').addClass('alert-success');
            
            // Reload page after 2 seconds
            setTimeout(function() {
                location.reload();
            }, 2000);
        } else {
            messageDiv.html('<i class="fas fa-exclamation-triangle me-2"></i>' + (result.error || 'เกิดข้อผิดพลาดในการ Restore'));
            resultsDiv.find('.alert').removeClass('alert-success').addClass('alert-danger');
        }
        
        resultsDiv.show();
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Auto-hide alerts after 10 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 10000);
}); 