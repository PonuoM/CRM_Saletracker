-- สร้างตาราง products สำหรับจัดการสินค้า
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'ชิ้น',
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `idx_category` (`category`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- เพิ่มข้อมูลตัวอย่าง
INSERT INTO `products` (`product_code`, `product_name`, `category`, `description`, `unit`, `cost_price`, `selling_price`, `stock_quantity`, `is_active`) VALUES
('P001', 'สินค้าตัวอย่าง 1', 'อิเล็กทรอนิกส์', 'สินค้าตัวอย่างสำหรับทดสอบระบบ', 'ชิ้น', 100.00, 150.00, 50, 1),
('P002', 'สินค้าตัวอย่าง 2', 'เสื้อผ้า', 'สินค้าตัวอย่างสำหรับทดสอบระบบ', 'ชิ้น', 200.00, 300.00, 30, 1),
('P003', 'สินค้าตัวอย่าง 3', 'อาหาร', 'สินค้าตัวอย่างสำหรับทดสอบระบบ', 'กล่อง', 50.00, 80.00, 100, 1);

-- สร้างตาราง order_details สำหรับเชื่อมโยงกับสินค้า (ถ้ายังไม่มี)
CREATE TABLE IF NOT EXISTS `order_details` (
  `order_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`order_detail_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_order_details_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
