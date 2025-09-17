-- Create customer_transfers table for tracking customer transfers
-- สร้างตาราง customer_transfers สำหรับติดตามการโอนย้ายลูกค้า

CREATE TABLE IF NOT EXISTS customer_transfers (
    transfer_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    source_telesales_id INT NOT NULL,
    target_telesales_id INT NOT NULL,
    reason TEXT NOT NULL,
    transferred_by INT NOT NULL,
    new_status VARCHAR(50) NOT NULL,
    transferred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_customer_id (customer_id),
    INDEX idx_source_telesales (source_telesales_id),
    INDEX idx_target_telesales (target_telesales_id),
    INDEX idx_transferred_by (transferred_by),
    INDEX idx_transferred_at (transferred_at),
    
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (source_telesales_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (target_telesales_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (transferred_by) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
