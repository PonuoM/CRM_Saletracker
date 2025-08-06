-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 06, 2025 at 04:39 AM
-- Server version: 10.6.19-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `primacom_Customer`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `district` varchar(50) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `temperature_status` enum('hot','warm','cold','frozen') DEFAULT 'hot',
  `customer_grade` enum('A+','A','B','C','D') DEFAULT 'D',
  `total_purchase_amount` decimal(12,2) DEFAULT 0.00,
  `assigned_to` int(11) DEFAULT NULL,
  `basket_type` enum('distribution','waiting','assigned') DEFAULT 'distribution',
  `assigned_at` timestamp NULL DEFAULT NULL,
  `last_contact_at` timestamp NULL DEFAULT NULL,
  `next_followup_at` timestamp NULL DEFAULT NULL,
  `recall_at` timestamp NULL DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_purchase` decimal(12,2) DEFAULT 0.00,
  `appointment_count` int(11) DEFAULT 0 COMMENT 'จำนวนการนัดหมายที่ทำไปแล้ว',
  `appointment_extension_count` int(11) DEFAULT 0 COMMENT 'จำนวนครั้งที่ต่อเวลาจากการนัดหมาย',
  `last_appointment_date` timestamp NULL DEFAULT NULL COMMENT 'วันที่นัดหมายล่าสุด',
  `appointment_extension_expiry` timestamp NULL DEFAULT NULL COMMENT 'วันหมดอายุการต่อเวลาจากการนัดหมาย',
  `max_appointment_extensions` int(11) DEFAULT 3 COMMENT 'จำนวนครั้งสูงสุดที่สามารถต่อเวลาได้ (default: 3)',
  `appointment_extension_days` int(11) DEFAULT 30 COMMENT 'จำนวนวันที่ต่อเวลาต่อการนัดหมาย 1 ครั้ง (default: 30 วัน)',
  `customer_status` enum('new','existing') DEFAULT 'new' COMMENT 'สถานะลูกค้า: new=ลูกค้าใหม่, existing=ลูกค้าเก่า',
  `customer_time_extension` int(11) DEFAULT 0 COMMENT 'จำนวนวันที่ต่อเวลาแล้ว',
  `customer_time_base` timestamp NULL DEFAULT NULL COMMENT 'วันเริ่มต้นการดูแลลูกค้า',
  `customer_time_expiry` timestamp NULL DEFAULT NULL COMMENT 'วันหมดอายุการดูแลลูกค้า'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_code`, `first_name`, `last_name`, `phone`, `email`, `address`, `district`, `province`, `postal_code`, `temperature_status`, `customer_grade`, `total_purchase_amount`, `assigned_to`, `basket_type`, `assigned_at`, `last_contact_at`, `next_followup_at`, `recall_at`, `source`, `notes`, `is_active`, `created_at`, `updated_at`, `total_purchase`, `appointment_count`, `appointment_extension_count`, `last_appointment_date`, `appointment_extension_expiry`, `max_appointment_extensions`, `appointment_extension_days`, `customer_status`, `customer_time_extension`, `customer_time_base`, `customer_time_expiry`) VALUES
(1, 'C001', 'สมชาย', 'ใจดี', '081-111-1111', 'somchai@email.com', '123 ถนนสุขุมวิท', 'คลองเตย', 'กรุงเทพฯ', NULL, 'hot', 'D', 0.00, NULL, 'waiting', '2025-11-04 04:27:06', '2025-08-04 17:06:27', NULL, NULL, 'facebook', NULL, 1, '2025-08-03 07:19:20', '2025-08-06 04:27:06', 49805.00, 1, 0, '2025-07-21 08:32:14', '2025-08-20 08:32:14', 3, 30, 'existing', 90, '2025-08-03 07:19:20', '2025-11-04 04:27:06'),
(2, 'C002', 'สมหญิง', 'รักดี', '081-222-2222', 'somying@email.com', '456 ถนนรัชดาภิเษก', 'ดินแดง', 'กรุงเทพฯ', NULL, 'warm', 'B', 0.00, 4, 'assigned', '2025-11-04 03:42:31', NULL, NULL, '2025-09-04 09:48:59', 'import', NULL, 1, '2025-08-03 07:19:20', '2025-08-06 03:42:31', 9000.00, 1, 0, '2025-07-21 08:32:14', '2025-08-20 08:32:14', 3, 30, 'existing', 90, '2025-08-05 09:48:59', '2029-05-16 09:48:59'),
(3, 'C003', 'สมศักดิ์', 'มั่งมี', '081-333-3333', 'somsak@email.com', '789 ถนนลาดพร้าว', 'วังทองหลาง', 'กรุงเทพฯ', NULL, 'cold', 'C', 0.00, NULL, 'waiting', NULL, NULL, NULL, NULL, 'manual', NULL, 1, '2025-08-03 07:19:20', '2025-08-05 12:23:51', 450.00, 1, 0, '2025-07-21 08:32:14', '2025-08-20 08:32:14', 3, 30, 'existing', 0, '2025-08-03 07:19:20', '2025-09-02 07:19:20'),
(4, 'C004', 'สมปอง', 'ใจเย็น', '081-444-4444', 'sompong@email.com', '321 ถนนเพชรบุรี', 'ห้วยขวาง', 'กรุงเทพฯ', NULL, 'frozen', 'D', 0.00, NULL, 'distribution', NULL, NULL, NULL, NULL, 'facebook', NULL, 1, '2025-08-03 07:19:20', '2025-08-05 12:23:51', 5400.00, 2, 1, '2025-07-26 08:32:14', '2025-08-25 08:32:14', 3, 30, 'existing', 0, '2025-08-03 07:19:20', '2025-03-01 17:00:00'),
(5, 'C005', 'สมใจ', 'รักงาน', '081-555-5555', 'somjai@email.com', '654 ถนนพระราม 9', 'ห้วยขวาง', 'กรุงเทพฯ', NULL, 'hot', 'A+', 0.00, NULL, 'waiting', '2025-11-04 03:42:31', NULL, NULL, NULL, 'import', NULL, 1, '2025-08-03 07:19:20', '2025-08-06 03:42:31', 1000.00, 3, 2, '2025-07-31 08:32:14', '2025-08-30 08:32:14', 3, 30, 'existing', 90, '2025-08-03 07:19:20', '2027-05-27 12:32:36'),
(6, 'C006', 'สมชาย', 'รวยมาก', '081-111-1111', 'somchai.rich@example.com', '123 ถ.สุขุมวิท กรุงเทพฯ', NULL, NULL, NULL, 'hot', 'A+', 150000.00, 3, 'assigned', '2025-11-04 04:31:19', '2025-06-19 17:22:31', NULL, '2025-09-04 09:48:27', NULL, NULL, 1, '2025-06-04 17:22:31', '2025-08-06 04:31:19', 450.00, 0, 0, NULL, NULL, 3, 30, 'existing', 90, '2025-08-05 09:48:27', '2025-11-04 04:31:19'),
(7, 'C007', 'สมหญิง', 'ใจดี', '081-222-2222', 'somying.kind@example.com', '456 ถ.รัชดา กรุงเทพฯ', NULL, NULL, NULL, 'warm', 'A', 75000.00, 4, 'assigned', '2025-11-04 03:42:31', '2025-06-29 17:22:31', NULL, '2025-09-04 09:48:46', NULL, NULL, 1, '2025-06-14 17:22:31', '2025-08-06 03:42:31', 0.00, 0, 0, NULL, NULL, 3, 30, 'existing', 90, '2025-08-05 09:48:46', '2027-08-25 09:48:46'),
(8, 'C008', 'สมศักดิ์', 'มั่นคง', '081-333-3333', 'somsak.stable@example.com', '789 ถ.ลาดพร้าว กรุงเทพฯ', NULL, NULL, NULL, 'hot', 'B', 25000.00, NULL, 'distribution', NULL, '2025-07-29 17:22:31', NULL, NULL, NULL, NULL, 1, '2025-06-24 17:22:31', '2025-08-05 12:23:51', 0.00, 0, 0, NULL, NULL, 3, 30, 'new', 0, '2025-06-24 17:22:31', '2025-07-24 17:22:31'),
(9, 'C009', 'สมใจ', 'ประหยัด', '081-444-4444', 'somjai.save@example.com', '101 ถ.พระราม 4 กรุงเทพฯ', NULL, NULL, NULL, 'cold', 'C', 8000.00, NULL, 'distribution', NULL, '2025-06-04 17:22:31', NULL, NULL, NULL, NULL, 1, '2025-05-25 17:22:31', '2025-08-05 12:23:51', 0.00, 0, 0, NULL, NULL, 3, 30, 'new', 0, '2025-05-25 17:22:31', '2025-06-24 17:22:31'),
(10, 'C010', 'สมหมาย', 'รอดี', '081-555-5555', 'sommai.wait@example.com', '202 ถ.เพชรบุรี กรุงเทพฯ', NULL, NULL, NULL, 'frozen', 'D', 2000.00, NULL, 'distribution', NULL, '2025-04-25 17:22:31', NULL, NULL, NULL, NULL, 1, '2025-05-05 17:22:31', '2025-08-05 12:23:51', 0.00, 0, 0, NULL, NULL, 3, 30, 'new', 0, '2025-05-05 17:22:31', '2025-06-04 17:22:31'),
(11, 'C011', 'สมทรง', 'เศรษฐี', '081-666-6666', 'somsong.wealthy@example.com', '303 ถ.สีลม กรุงเทพฯ', NULL, NULL, NULL, 'warm', 'A+', 120000.00, 3, 'assigned', '2025-11-04 04:28:50', '2025-08-05 09:50:00', NULL, '2025-09-04 09:48:59', NULL, NULL, 1, '2025-07-04 17:22:31', '2025-08-06 04:28:50', 11650.00, 0, 0, NULL, NULL, 3, 30, 'existing', 90, '2025-08-05 09:48:59', '2025-11-04 04:28:50'),
(12, 'C012', 'สมพร', 'หายไป', '081-777-7777', 'somporn.lost@example.com', '404 ถ.บางนา กรุงเทพฯ', NULL, NULL, NULL, 'frozen', 'C', 15000.00, NULL, 'distribution', NULL, '2025-04-30 17:22:31', NULL, NULL, NULL, NULL, 1, '2025-04-25 17:22:31', '2025-08-05 12:23:51', 0.00, 0, 0, NULL, NULL, 3, 30, 'new', 0, '2025-04-25 17:22:31', '2025-05-25 17:22:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_basket_type` (`basket_type`),
  ADD KEY `idx_temperature` (`temperature_status`),
  ADD KEY `idx_grade` (`customer_grade`),
  ADD KEY `idx_province` (`province`),
  ADD KEY `idx_recall_at` (`recall_at`),
  ADD KEY `idx_next_followup` (`next_followup_at`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_customers_phone` (`phone`),
  ADD KEY `idx_customers_email` (`email`),
  ADD KEY `idx_customers_total_purchase` (`total_purchase`),
  ADD KEY `idx_customer_status` (`customer_status`),
  ADD KEY `idx_customer_time_expiry` (`customer_time_expiry`),
  ADD KEY `idx_customer_time_base` (`customer_time_base`),
  ADD KEY `idx_assigned_at` (`assigned_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
