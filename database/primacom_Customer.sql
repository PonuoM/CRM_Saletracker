-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 06, 2025 at 04:04 PM
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

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`primacom_bloguser`@`localhost` PROCEDURE `CheckCustomerTimeouts` ()   BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_customer_id INT;
    DECLARE v_assigned_to INT;
    
    -- Cursor สำหรับลูกค้าที่เกินกำหนด
    DECLARE customer_cursor CURSOR FOR
        SELECT customer_id, assigned_to
        FROM customers
        WHERE assigned_to IS NOT NULL
          AND customer_time_expiry IS NOT NULL
          AND customer_time_expiry <= NOW()
          AND basket_type = 'assigned';
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN customer_cursor;
    
    read_loop: LOOP
        FETCH customer_cursor INTO v_customer_id, v_assigned_to;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- ดึงลูกค้ากลับไปยัง waiting basket
        CALL RecallCustomer(v_customer_id, v_assigned_to, 'timeout', 'เกินกำหนดเวลา', 'waiting');
        
    END LOOP;
    
    CLOSE customer_cursor;
    
END$$

CREATE DEFINER=`primacom_bloguser`@`localhost` PROCEDURE `ExtendCustomerTime` (IN `p_customer_id` INT, IN `p_user_id` INT, IN `p_extension_type` ENUM('sale','appointment','manual'), IN `p_extension_days` INT, IN `p_reason` VARCHAR(200))   BEGIN
    DECLARE v_previous_expiry TIMESTAMP;
    DECLARE v_new_expiry TIMESTAMP;
    DECLARE v_current_expiry TIMESTAMP;
    
    -- ดึงวันหมดอายุปัจจุบัน
    SELECT customer_time_expiry INTO v_current_expiry
    FROM customers 
    WHERE customer_id = p_customer_id;
    
    -- กำหนดวันหมดอายุใหม่
    IF v_current_expiry IS NULL OR v_current_expiry <= NOW() THEN
        SET v_new_expiry = DATE_ADD(NOW(), INTERVAL p_extension_days DAY);
    ELSE
        SET v_new_expiry = DATE_ADD(v_current_expiry, INTERVAL p_extension_days DAY);
    END IF;
    
    -- บันทึกการต่อเวลา
    INSERT INTO customer_time_extensions (
        customer_id, user_id, extension_type, extension_days,
        previous_expiry, new_expiry, reason
    ) VALUES (
        p_customer_id, p_user_id, p_extension_type, p_extension_days,
        v_current_expiry, v_new_expiry, p_reason
    );
    
    -- อัปเดตข้อมูลลูกค้า
    UPDATE customers 
    SET customer_time_expiry = v_new_expiry,
        customer_time_extension = customer_time_extension + p_extension_days,
        updated_at = NOW()
    WHERE customer_id = p_customer_id;
    
    -- ถ้าเป็นการต่อเวลาจากการขาย ให้เปลี่ยนเป็นลูกค้าเก่า
    IF p_extension_type = 'sale' THEN
        UPDATE customers 
        SET customer_status = 'existing'
        WHERE customer_id = p_customer_id;
    END IF;
    
    -- ถ้าเป็นการต่อเวลาจากการนัดหมาย ให้เพิ่มจำนวนครั้ง
    IF p_extension_type = 'appointment' THEN
        UPDATE customers 
        SET appointment_extension_count = appointment_extension_count + 1
        WHERE customer_id = p_customer_id;
    END IF;
    
END$$

CREATE DEFINER=`primacom_bloguser`@`localhost` PROCEDURE `ExtendCustomerTimeFromAppointment` (IN `p_customer_id` INT, IN `p_appointment_id` INT, IN `p_user_id` INT, IN `p_extension_days` INT)   BEGIN
       -- ... procedure content ...
   END$$

CREATE DEFINER=`primacom_bloguser`@`localhost` PROCEDURE `RecallCustomer` (IN `p_customer_id` INT, IN `p_user_id` INT, IN `p_recall_type` ENUM('timeout','manual','system'), IN `p_recall_reason` VARCHAR(200), IN `p_new_basket` ENUM('distribution','waiting','assigned'))   BEGIN
    DECLARE v_previous_basket ENUM('distribution', 'waiting', 'assigned');
    DECLARE v_assigned_to INT;
    
    -- ดึงข้อมูลปัจจุบัน
    SELECT basket_type, assigned_to INTO v_previous_basket, v_assigned_to
    FROM customers 
    WHERE customer_id = p_customer_id;
    
    -- บันทึกการดึงกลับ
    INSERT INTO customer_recalls (
        customer_id, user_id, recall_type, recall_reason,
        previous_basket, new_basket
    ) VALUES (
        p_customer_id, p_user_id, p_recall_type, p_recall_reason,
        v_previous_basket, p_new_basket
    );
    
    -- อัปเดตสถานะลูกค้า
    UPDATE customers 
    SET basket_type = p_new_basket,
        assigned_to = CASE WHEN p_new_basket = 'assigned' THEN assigned_to ELSE NULL END,
        customer_time_expiry = CASE WHEN p_new_basket = 'waiting' THEN DATE_ADD(NOW(), INTERVAL 30 DAY) ELSE NULL END,
        updated_at = NOW()
    WHERE customer_id = p_customer_id;
    
END$$

CREATE DEFINER=`primacom_bloguser`@`localhost` PROCEDURE `ResetAppointmentExtensionOnSale` (IN `p_customer_id` INT, IN `p_user_id` INT, IN `p_order_id` INT)   BEGIN
       -- ... procedure content ...
   END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `activity_type`, `table_name`, `record_id`, `action`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'grade_change', 'customers', 11, 'update', '{\"customer_grade\":\"B\"}', '{\"customer_grade\":\"A+\"}', NULL, NULL, '2025-08-04 09:07:27'),
(2, NULL, 'temperature_change', 'customers', 12, 'update', '{\"temperature_status\":\"warm\"}', '{\"temperature_status\":\"frozen\"}', NULL, NULL, '2025-08-04 09:07:27');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `appointment_type` enum('call','meeting','presentation','followup','other') NOT NULL,
  `appointment_status` enum('scheduled','confirmed','completed','cancelled','no_show') DEFAULT 'scheduled',
  `location` varchar(200) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `customer_id`, `user_id`, `appointment_date`, `appointment_type`, `appointment_status`, `location`, `contact_person`, `contact_phone`, `title`, `description`, `notes`, `reminder_sent`, `reminder_sent_at`, `created_at`, `updated_at`) VALUES
(1, 1, 3, '2025-08-07 09:21:51', 'meeting', 'scheduled', NULL, NULL, NULL, 'ประชุมนำเสนอสินค้าใหม่', 'ประชุมกับลูกค้าเพื่อนำเสนอสินค้าใหม่ที่เพิ่งเปิดตัว', 'ลูกค้าสนใจสินค้าใหม่มาก', 0, NULL, '2025-08-05 02:21:51', '2025-08-05 02:21:51'),
(2, 2, 4, '2025-08-06 09:21:51', 'call', 'scheduled', NULL, NULL, NULL, 'โทรติดตามการสั่งซื้อ', 'โทรติดตามลูกค้าเกี่ยวกับการสั่งซื้อที่ค้างอยู่', 'ลูกค้าบอกว่าจะโทรกลับมา', 0, NULL, '2025-08-05 02:21:51', '2025-08-05 02:21:51'),
(3, 5, 3, '2025-08-08 09:21:51', 'presentation', 'scheduled', NULL, NULL, NULL, 'นำเสนอโปรโมชั่นพิเศษ', 'นำเสนอโปรโมชั่นพิเศษสำหรับลูกค้าเกรด A+', 'โปรโมชั่นพิเศษ 20% สำหรับลูกค้าเกรด A+', 0, NULL, '2025-08-05 02:21:51', '2025-08-05 02:21:51'),
(4, 1, 3, '2025-08-07 09:34:18', 'meeting', 'scheduled', NULL, NULL, NULL, 'ประชุมนำเสนอสินค้าใหม่', 'ประชุมกับลูกค้าเพื่อนำเสนอสินค้าใหม่ที่เพิ่งเปิดตัว', 'ลูกค้าสนใจสินค้าใหม่มาก', 0, NULL, '2025-08-05 02:34:18', '2025-08-05 02:34:18'),
(5, 2, 4, '2025-08-06 09:34:18', 'call', 'scheduled', NULL, NULL, NULL, 'โทรติดตามการสั่งซื้อ', 'โทรติดตามลูกค้าเกี่ยวกับการสั่งซื้อที่ค้างอยู่', 'ลูกค้าบอกว่าจะโทรกลับมา', 0, NULL, '2025-08-05 02:34:18', '2025-08-05 02:34:18'),
(6, 5, 3, '2025-08-08 09:34:18', 'presentation', 'scheduled', NULL, NULL, NULL, 'นำเสนอโปรโมชั่นพิเศษ', 'นำเสนอโปรโมชั่นพิเศษสำหรับลูกค้าเกรด A+', 'โปรโมชั่นพิเศษ 20% สำหรับลูกค้าเกรด A+', 0, NULL, '2025-08-05 02:34:18', '2025-08-05 02:34:18'),
(7, 1, 3, '2025-08-07 09:37:14', 'meeting', 'scheduled', NULL, NULL, NULL, 'ประชุมนำเสนอสินค้าใหม่', 'ประชุมกับลูกค้าเพื่อนำเสนอสินค้าใหม่ที่เพิ่งเปิดตัว', 'ลูกค้าสนใจสินค้าใหม่มาก', 0, NULL, '2025-08-05 02:37:14', '2025-08-05 02:37:14'),
(8, 2, 4, '2025-08-06 09:37:14', 'call', 'scheduled', NULL, NULL, NULL, 'โทรติดตามการสั่งซื้อ', 'โทรติดตามลูกค้าเกี่ยวกับการสั่งซื้อที่ค้างอยู่', 'ลูกค้าบอกว่าจะโทรกลับมา', 0, NULL, '2025-08-05 02:37:14', '2025-08-05 02:37:14'),
(9, 5, 3, '2025-08-08 09:37:14', 'presentation', 'scheduled', NULL, NULL, NULL, 'นำเสนอโปรโมชั่นพิเศษ', 'นำเสนอโปรโมชั่นพิเศษสำหรับลูกค้าเกรด A+', 'โปรโมชั่นพิเศษ 20% สำหรับลูกค้าเกรด A+', 0, NULL, '2025-08-05 02:37:14', '2025-08-05 02:37:14'),
(10, 1, 3, '2025-08-06 10:11:00', 'call', 'scheduled', NULL, NULL, NULL, NULL, NULL, 'ทดสอบ', 0, NULL, '2025-08-05 03:11:43', '2025-08-05 03:11:43'),
(11, 1, 3, '2025-08-06 11:25:00', 'call', 'scheduled', NULL, NULL, NULL, NULL, NULL, 'ก', 0, NULL, '2025-08-05 04:25:18', '2025-08-05 04:25:18'),
(12, 1, 3, '2025-08-06 11:47:00', 'call', 'scheduled', NULL, NULL, NULL, NULL, NULL, '5555', 0, NULL, '2025-08-05 04:47:20', '2025-08-05 04:47:20'),
(13, 1, 3, '2025-08-07 15:08:00', 'call', 'scheduled', NULL, NULL, NULL, NULL, NULL, 'ทดสอบ', 0, NULL, '2025-08-05 08:08:43', '2025-08-05 08:08:43');

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `after_appointment_delete` AFTER DELETE ON `appointments` FOR EACH ROW BEGIN
       UPDATE customers 
       SET appointment_count = GREATEST(appointment_count - 1, 0),
           updated_at = NOW()
       WHERE customer_id = OLD.customer_id;
   END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_appointment_insert` AFTER INSERT ON `appointments` FOR EACH ROW BEGIN
       UPDATE customers 
       SET appointment_count = appointment_count + 1,
           last_appointment_date = NEW.appointment_date,
           updated_at = NOW()
       WHERE customer_id = NEW.customer_id;
   END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `appointment_activities`
--

CREATE TABLE `appointment_activities` (
  `activity_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('created','updated','confirmed','completed','cancelled','reminder_sent') NOT NULL,
  `activity_description` text NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointment_activities`
--

INSERT INTO `appointment_activities` (`activity_id`, `appointment_id`, `user_id`, `activity_type`, `activity_description`, `metadata`, `created_at`) VALUES
(1, 1, 3, 'created', 'สร้างนัดหมายประชุมนำเสนอสินค้าใหม่', NULL, '2025-08-05 02:21:51'),
(2, 2, 4, 'created', 'สร้างนัดหมายโทรติดตามการสั่งซื้อ', NULL, '2025-08-05 02:21:51'),
(3, 3, 3, 'created', 'สร้างนัดหมายนำเสนอโปรโมชั่นพิเศษ', NULL, '2025-08-05 02:21:51'),
(4, 1, 3, 'created', 'สร้างนัดหมายประชุมนำเสนอสินค้าใหม่', NULL, '2025-08-05 02:34:18'),
(5, 2, 4, 'created', 'สร้างนัดหมายโทรติดตามการสั่งซื้อ', NULL, '2025-08-05 02:34:18'),
(6, 3, 3, 'created', 'สร้างนัดหมายนำเสนอโปรโมชั่นพิเศษ', NULL, '2025-08-05 02:34:18'),
(7, 1, 3, 'created', 'สร้างนัดหมายประชุมนำเสนอสินค้าใหม่', NULL, '2025-08-05 02:37:14'),
(8, 2, 4, 'created', 'สร้างนัดหมายโทรติดตามการสั่งซื้อ', NULL, '2025-08-05 02:37:14'),
(9, 3, 3, 'created', 'สร้างนัดหมายนำเสนอโปรโมชั่นพิเศษ', NULL, '2025-08-05 02:37:14'),
(10, 11, 3, 'created', 'สร้างนัดหมายใหม่', NULL, '2025-08-05 04:25:18'),
(11, 12, 3, 'created', 'สร้างนัดหมายใหม่', NULL, '2025-08-05 04:47:20'),
(12, 13, 3, 'created', 'สร้างนัดหมายใหม่', NULL, '2025-08-05 08:08:43');

-- --------------------------------------------------------

--
-- Table structure for table `appointment_extensions`
--

CREATE TABLE `appointment_extensions` (
  `extension_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL COMMENT 'ID ของการนัดหมายที่ทำให้เกิดการต่อเวลา (NULL ถ้าเป็นการต่อเวลาอัตโนมัติ)',
  `extension_type` enum('appointment','sale','manual') NOT NULL COMMENT 'ประเภทการต่อเวลา: appointment=จากนัดหมาย, sale=จากการขาย, manual=ต่อเวลาด้วยตนเอง',
  `extension_days` int(11) NOT NULL COMMENT 'จำนวนวันที่ต่อเวลา',
  `extension_reason` varchar(200) DEFAULT NULL COMMENT 'เหตุผลการต่อเวลา',
  `previous_expiry` timestamp NULL DEFAULT NULL COMMENT 'วันหมดอายุเดิม',
  `new_expiry` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'วันหมดอายุใหม่',
  `extension_count_before` int(11) NOT NULL COMMENT 'จำนวนครั้งที่ต่อเวลาก่อนการต่อเวลานี้',
  `extension_count_after` int(11) NOT NULL COMMENT 'จำนวนครั้งที่ต่อเวลาหลังการต่อเวลานี้',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointment_extensions`
--

INSERT INTO `appointment_extensions` (`extension_id`, `customer_id`, `user_id`, `appointment_id`, `extension_type`, `extension_days`, `extension_reason`, `previous_expiry`, `new_expiry`, `extension_count_before`, `extension_count_after`, `created_at`) VALUES
(1, 4, 3, 1, 'appointment', 30, 'นัดหมายเสร็จสิ้น - ต่อเวลา 30 วัน', '2025-06-26 08:32:14', '2025-07-26 08:32:14', 0, 1, '2025-08-05 08:32:14'),
(2, 5, 3, 3, 'appointment', 30, 'นัดหมายเสร็จสิ้น - ต่อเวลา 30 วัน', '2025-07-01 08:32:14', '2025-07-31 08:32:14', 1, 2, '2025-08-05 08:32:14');

-- --------------------------------------------------------

--
-- Table structure for table `appointment_extension_rules`
--

CREATE TABLE `appointment_extension_rules` (
  `rule_id` int(11) NOT NULL,
  `rule_name` varchar(100) NOT NULL,
  `rule_description` text DEFAULT NULL,
  `extension_days` int(11) NOT NULL DEFAULT 30 COMMENT 'จำนวนวันที่ต่อเวลาต่อการนัดหมาย 1 ครั้ง',
  `max_extensions` int(11) NOT NULL DEFAULT 3 COMMENT 'จำนวนครั้งสูงสุดที่สามารถต่อเวลาได้',
  `reset_on_sale` tinyint(1) DEFAULT 1 COMMENT 'รีเซ็ตตัวนับเมื่อมีการขาย',
  `min_appointment_duration` int(11) DEFAULT 0 COMMENT 'ระยะเวลาขั้นต่ำของการนัดหมาย (นาที)',
  `required_appointment_status` enum('completed','confirmed','scheduled') DEFAULT 'completed' COMMENT 'สถานะการนัดหมายที่จำเป็น',
  `min_customer_grade` enum('A+','A','B','C','D') DEFAULT 'D' COMMENT 'เกรดลูกค้าขั้นต่ำ',
  `temperature_status_filter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'สถานะอุณหภูมิที่ใช้ได้ (JSON array)' CHECK (json_valid(`temperature_status_filter`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointment_extension_rules`
--

INSERT INTO `appointment_extension_rules` (`rule_id`, `rule_name`, `rule_description`, `extension_days`, `max_extensions`, `reset_on_sale`, `min_appointment_duration`, `required_appointment_status`, `min_customer_grade`, `temperature_status_filter`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Default Appointment Extension Rule', 'กฎการต่อเวลามาตรฐาน: ต่อเวลา 30 วันต่อการนัดหมาย 1 ครั้ง สูงสุด 3 ครั้ง รีเซ็ตเมื่อมีการขาย', 30, 3, 1, 0, 'completed', 'D', '[\"hot\", \"warm\", \"cold\"]', 1, '2025-08-05 08:32:13', '2025-08-05 08:32:13'),
(2, 'กฎเริ่มต้นการต่อเวลา', NULL, 30, 3, 1, 0, 'completed', 'D', '[\"hot\", \"warm\", \"cold\"]', 1, '2025-08-05 08:45:29', '2025-08-05 08:45:29');

-- --------------------------------------------------------

--
-- Table structure for table `call_logs`
--

CREATE TABLE `call_logs` (
  `log_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `call_type` enum('outbound','inbound') DEFAULT 'outbound',
  `call_status` enum('answered','no_answer','busy','invalid') NOT NULL,
  `call_result` enum('interested','not_interested','callback','order','complaint') DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `next_action` varchar(200) DEFAULT NULL,
  `next_followup_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `call_logs`
--

INSERT INTO `call_logs` (`log_id`, `customer_id`, `user_id`, `call_type`, `call_status`, `call_result`, `duration_minutes`, `notes`, `next_action`, `next_followup_at`, `created_at`) VALUES
(1, 1, 1, 'outbound', 'answered', 'interested', 5, 'Test call from system test', 'Follow up', NULL, '2025-08-04 16:53:05'),
(2, 1, 1, 'outbound', 'answered', 'interested', 5, 'Test call from simple API test', 'Follow up', NULL, '2025-08-04 16:58:33'),
(3, 1, 3, 'outbound', 'answered', 'interested', 5, 'ลองทดสอบ', '', NULL, '2025-08-04 17:06:27'),
(4, 11, 3, 'outbound', 'answered', 'interested', 0, ']ูกค้าซ์้อสินคาจ้า', '', NULL, '2025-08-05 09:50:00');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `company_id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `company_code` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`company_id`, `company_name`, `company_code`, `address`, `phone`, `email`, `is_active`, `created_at`) VALUES
(1, 'บริษัท พรีม่าแพสชั่น 49 จำกัด', 'PRIMA49', '123 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110', '02-123-4567', 'info@prima49.com', 1, '2025-08-03 07:19:20');

-- --------------------------------------------------------

--
-- Table structure for table `cron_job_logs`
--

CREATE TABLE `cron_job_logs` (
  `id` int(11) NOT NULL,
  `job_name` varchar(100) NOT NULL,
  `status` enum('running','success','failed') NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `end_time` timestamp NULL DEFAULT NULL,
  `execution_time` decimal(8,2) DEFAULT NULL,
  `output` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cron_job_settings`
--

CREATE TABLE `cron_job_settings` (
  `id` int(11) NOT NULL,
  `job_name` varchar(100) NOT NULL,
  `cron_expression` varchar(100) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `last_run` timestamp NULL DEFAULT NULL,
  `next_run` timestamp NULL DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cron_job_settings`
--

INSERT INTO `cron_job_settings` (`id`, `job_name`, `cron_expression`, `is_enabled`, `last_run`, `next_run`, `description`, `created_at`, `updated_at`) VALUES
(1, 'update_customer_grades', '0 2 * * *', 1, NULL, NULL, 'อัปเดตเกรดลูกค้าอัตโนมัติทุก 2:00 น.', '2025-08-03 17:17:31', '2025-08-03 17:17:31'),
(2, 'update_customer_temperatures', '30 2 * * *', 1, NULL, NULL, 'อัปเดตอุณหภูมิลูกค้าอัตโนมัติทุก 2:30 น.', '2025-08-03 17:17:31', '2025-08-03 17:17:31'),
(3, 'create_recall_list', '0 3 * * *', 1, NULL, NULL, 'สร้างรายการลูกค้าที่ต้องติดตามทุก 3:00 น.', '2025-08-03 17:17:31', '2025-08-03 17:17:31'),
(4, 'send_notifications', '30 3 * * *', 1, NULL, NULL, 'ส่งการแจ้งเตือนทุก 3:30 น.', '2025-08-03 17:17:31', '2025-08-03 17:17:31'),
(5, 'cleanup_old_data', '0 4 * * 0', 1, NULL, NULL, 'ทำความสะอาดข้อมูลเก่าทุกวันอาทิตย์ 4:00 น.', '2025-08-03 17:17:31', '2025-08-03 17:17:31');

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
(2, 'C002', 'สมหญิง', 'รักดี', '081-222-2222', 'somying@email.com', '456 ถนนรัชดาภิเษก', 'ดินแดง', 'กรุงเทพฯ', NULL, 'warm', 'B', 0.00, 4, 'assigned', '2025-11-04 06:54:02', NULL, NULL, '2025-09-04 09:48:59', 'import', NULL, 1, '2025-08-03 07:19:20', '2025-08-06 06:54:02', 9600.00, 1, 0, '2025-07-21 08:32:14', '2025-08-20 08:32:14', 3, 30, 'existing', 90, '2025-08-05 09:48:59', '2025-11-04 06:54:02'),
(3, 'C003', 'สมศักดิ์', 'มั่งมี', '081-333-3333', 'somsak@email.com', '789 ถนนลาดพร้าว', 'วังทองหลาง', 'กรุงเทพฯ', NULL, 'cold', 'C', 0.00, NULL, 'waiting', NULL, NULL, NULL, NULL, 'manual', NULL, 1, '2025-08-03 07:19:20', '2025-08-05 12:23:51', 450.00, 1, 0, '2025-07-21 08:32:14', '2025-08-20 08:32:14', 3, 30, 'existing', 0, '2025-08-03 07:19:20', '2025-09-02 07:19:20'),
(4, 'C004', 'สมปอง', 'ใจเย็น', '081-444-4444', 'sompong@email.com', '321 ถนนเพชรบุรี', 'ห้วยขวาง', 'กรุงเทพฯ', NULL, 'frozen', 'D', 0.00, NULL, 'distribution', NULL, NULL, NULL, NULL, 'facebook', NULL, 1, '2025-08-03 07:19:20', '2025-08-05 12:23:51', 5400.00, 2, 1, '2025-07-26 08:32:14', '2025-08-25 08:32:14', 3, 30, 'existing', 0, '2025-08-03 07:19:20', '2025-03-01 17:00:00'),
(5, 'C005', 'สมใจ', 'รักงาน', '081-555-5555', 'somjai@email.com', '654 ถนนพระราม 9', 'ห้วยขวาง', 'กรุงเทพฯ', NULL, 'hot', 'A+', 0.00, NULL, 'waiting', '2025-11-04 03:42:31', NULL, NULL, NULL, 'import', NULL, 1, '2025-08-03 07:19:20', '2025-08-06 03:42:31', 1000.00, 3, 2, '2025-07-31 08:32:14', '2025-08-30 08:32:14', 3, 30, 'existing', 90, '2025-08-03 07:19:20', '2027-05-27 12:32:36'),
(6, 'C006', 'สมชาย', 'รวยมาก', '081-111-1111', 'somchai.rich@example.com', '123 ถ.สุขุมวิท กรุงเทพฯ', NULL, NULL, NULL, 'hot', 'A+', 150000.00, 3, 'assigned', '2025-11-04 04:31:19', '2025-06-19 17:22:31', NULL, '2025-09-04 09:48:27', NULL, NULL, 1, '2025-06-04 17:22:31', '2025-08-06 04:31:19', 450.00, 0, 0, NULL, NULL, 3, 30, 'existing', 90, '2025-08-05 09:48:27', '2025-11-04 04:31:19'),
(7, 'C007', 'สมหญิง', 'ใจดี', '081-222-2222', 'somying.kind@example.com', '456 ถ.รัชดา กรุงเทพฯ', NULL, NULL, NULL, 'warm', 'A', 75000.00, 4, 'assigned', '2025-11-04 06:54:23', '2025-06-29 17:22:31', NULL, '2025-09-04 09:48:46', NULL, NULL, 1, '2025-06-14 17:22:31', '2025-08-06 06:54:23', 600.00, 0, 0, NULL, NULL, 3, 30, 'existing', 90, '2025-08-05 09:48:46', '2025-11-04 06:54:23'),
(8, 'C008', 'สมศักดิ์', 'มั่นคง', '081-333-3333', 'somsak.stable@example.com', '789 ถ.ลาดพร้าว กรุงเทพฯ', NULL, NULL, NULL, 'hot', 'B', 25000.00, NULL, 'distribution', NULL, '2025-07-29 17:22:31', NULL, NULL, NULL, NULL, 1, '2025-06-24 17:22:31', '2025-08-05 12:23:51', 0.00, 0, 0, NULL, NULL, 3, 30, 'new', 0, '2025-06-24 17:22:31', '2025-07-24 17:22:31'),
(9, 'C009', 'สมใจ', 'ประหยัด', '081-444-4444', 'somjai.save@example.com', '101 ถ.พระราม 4 กรุงเทพฯ', NULL, NULL, NULL, 'cold', 'C', 8000.00, NULL, 'distribution', NULL, '2025-06-04 17:22:31', NULL, NULL, NULL, NULL, 1, '2025-05-25 17:22:31', '2025-08-05 12:23:51', 0.00, 0, 0, NULL, NULL, 3, 30, 'new', 0, '2025-05-25 17:22:31', '2025-06-24 17:22:31'),
(10, 'C010', 'สมหมาย', 'รอดี', '081-555-5555', 'sommai.wait@example.com', '202 ถ.เพชรบุรี กรุงเทพฯ', NULL, NULL, NULL, 'frozen', 'D', 2000.00, NULL, 'distribution', NULL, '2025-04-25 17:22:31', NULL, NULL, NULL, NULL, 1, '2025-05-05 17:22:31', '2025-08-05 12:23:51', 0.00, 0, 0, NULL, NULL, 3, 30, 'new', 0, '2025-05-05 17:22:31', '2025-06-04 17:22:31'),
(11, 'C011', 'สมทรง', 'เศรษฐี', '081-666-6666', 'somsong.wealthy@example.com', '303 ถ.สีลม กรุงเทพฯ', NULL, NULL, NULL, 'warm', 'A+', 120000.00, 3, 'assigned', '2025-11-04 04:28:50', '2025-08-05 09:50:00', NULL, '2025-09-04 09:48:59', NULL, NULL, 1, '2025-07-04 17:22:31', '2025-08-06 04:28:50', 11650.00, 0, 0, NULL, NULL, 3, 30, 'existing', 90, '2025-08-05 09:48:59', '2025-11-04 04:28:50'),
(12, 'C012', 'สมพร', 'หายไป', '081-777-7777', 'somporn.lost@example.com', '404 ถ.บางนา กรุงเทพฯ', NULL, NULL, NULL, 'frozen', 'C', 15000.00, NULL, 'distribution', NULL, '2025-04-30 17:22:31', NULL, NULL, NULL, NULL, 1, '2025-04-25 17:22:31', '2025-08-05 12:23:51', 0.00, 0, 0, NULL, NULL, 3, 30, 'new', 0, '2025-04-25 17:22:31', '2025-05-25 17:22:31'),
(13, NULL, 'ทดสอบ', 'ระบบ', '0812345678', 'test@example.com', '123 ถ.ทดสอบ', 'เขตทดสอบ', 'จังหวัดทดสอบ', '10000', 'cold', 'C', 0.00, NULL, 'distribution', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-08-06 09:00:52', '2025-08-06 09:25:32', 0.00, 0, 0, NULL, NULL, 3, 30, 'new', 0, NULL, NULL),
(14, NULL, 'ทดสอบ', 'ระบบ', '0812345678', 'test@example.com', '123 ถ.ทดสอบ', 'เขตทดสอบ', 'จังหวัดทดสอบ', '10000', 'cold', 'C', 0.00, NULL, 'distribution', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-08-06 09:03:03', '2025-08-06 09:03:03', 0.00, 0, 0, NULL, NULL, 3, 30, 'new', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_activities`
--

CREATE TABLE `customer_activities` (
  `activity_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('status_change','assignment','call','order','note','recall') NOT NULL,
  `activity_date` date DEFAULT NULL,
  `activity_description` text NOT NULL,
  `old_value` varchar(200) DEFAULT NULL,
  `new_value` varchar(200) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_activities`
--

INSERT INTO `customer_activities` (`activity_id`, `customer_id`, `user_id`, `activity_type`, `activity_date`, `activity_description`, `old_value`, `new_value`, `metadata`, `created_at`) VALUES
(1, 1, 1, 'call', NULL, 'บันทึกการโทร: answered - Test call from simple API test', NULL, NULL, NULL, '2025-08-04 16:58:33'),
(2, 1, 3, 'call', NULL, 'บันทึกการโทร: answered - ลองทดสอบ', NULL, NULL, NULL, '2025-08-04 17:06:27'),
(3, 6, 1, '', NULL, 'ลูกค้าถูกแจกให้ Telesales ID: 3', NULL, NULL, NULL, '2025-08-05 09:48:27'),
(4, 7, 1, '', NULL, 'ลูกค้าถูกแจกให้ Telesales ID: 4', NULL, NULL, NULL, '2025-08-05 09:48:46'),
(5, 11, 1, '', NULL, 'ลูกค้าถูกแจกให้ Telesales ID: 3', NULL, NULL, NULL, '2025-08-05 09:48:59'),
(6, 2, 1, '', NULL, 'ลูกค้าถูกแจกให้ Telesales ID: 4', NULL, NULL, NULL, '2025-08-05 09:48:59'),
(7, 11, 3, 'call', NULL, 'บันทึกการโทร: answered - ]ูกค้าซ์้อสินคาจ้า', NULL, NULL, NULL, '2025-08-05 09:50:00');

-- --------------------------------------------------------

--
-- Stand-in structure for view `customer_appointment_extensions`
-- (See below for the actual view)
--
CREATE TABLE `customer_appointment_extensions` (
`customer_id` int(11)
,`customer_name` varchar(101)
,`customer_grade` enum('A+','A','B','C','D')
,`temperature_status` enum('hot','warm','cold','frozen')
,`appointment_count` int(11)
,`appointment_extension_count` int(11)
,`max_appointment_extensions` int(11)
,`appointment_extension_expiry` timestamp
,`appointment_extension_days` int(11)
,`last_appointment_date` timestamp
,`expiry_status` varchar(15)
,`extension_status` varchar(23)
,`assigned_user` varchar(50)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `customer_do_list`
-- (See below for the actual view)
--
CREATE TABLE `customer_do_list` (
`customer_id` int(11)
,`customer_code` varchar(50)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`phone` varchar(20)
,`email` varchar(100)
,`address` text
,`district` varchar(50)
,`province` varchar(50)
,`postal_code` varchar(10)
,`temperature_status` enum('hot','warm','cold','frozen')
,`customer_grade` enum('A+','A','B','C','D')
,`total_purchase_amount` decimal(12,2)
,`assigned_to` int(11)
,`basket_type` enum('distribution','waiting','assigned')
,`assigned_at` timestamp
,`last_contact_at` timestamp
,`next_followup_at` timestamp
,`recall_at` timestamp
,`source` varchar(50)
,`notes` text
,`is_active` tinyint(1)
,`created_at` timestamp
,`updated_at` timestamp
,`total_purchase` decimal(12,2)
,`appointment_count` int(11)
,`appointment_extension_count` int(11)
,`last_appointment_date` timestamp
,`appointment_extension_expiry` timestamp
,`max_appointment_extensions` int(11)
,`appointment_extension_days` int(11)
,`customer_status` enum('new','existing')
,`customer_time_extension` int(11)
,`customer_time_base` timestamp
,`customer_time_expiry` timestamp
,`days_remaining` int(8)
,`status_text` varchar(10)
,`urgency_status` varchar(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `customer_existing_list`
-- (See below for the actual view)
--
CREATE TABLE `customer_existing_list` (
`customer_id` int(11)
,`customer_code` varchar(50)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`phone` varchar(20)
,`email` varchar(100)
,`address` text
,`district` varchar(50)
,`province` varchar(50)
,`postal_code` varchar(10)
,`temperature_status` enum('hot','warm','cold','frozen')
,`customer_grade` enum('A+','A','B','C','D')
,`total_purchase_amount` decimal(12,2)
,`assigned_to` int(11)
,`basket_type` enum('distribution','waiting','assigned')
,`assigned_at` timestamp
,`last_contact_at` timestamp
,`next_followup_at` timestamp
,`recall_at` timestamp
,`source` varchar(50)
,`notes` text
,`is_active` tinyint(1)
,`created_at` timestamp
,`updated_at` timestamp
,`total_purchase` decimal(12,2)
,`appointment_count` int(11)
,`appointment_extension_count` int(11)
,`last_appointment_date` timestamp
,`appointment_extension_expiry` timestamp
,`max_appointment_extensions` int(11)
,`appointment_extension_days` int(11)
,`customer_status` enum('new','existing')
,`customer_time_extension` int(11)
,`customer_time_base` timestamp
,`customer_time_expiry` timestamp
,`days_remaining` int(8)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `customer_followup_list`
-- (See below for the actual view)
--
CREATE TABLE `customer_followup_list` (
`customer_id` int(11)
,`customer_code` varchar(50)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`phone` varchar(20)
,`email` varchar(100)
,`address` text
,`district` varchar(50)
,`province` varchar(50)
,`postal_code` varchar(10)
,`temperature_status` enum('hot','warm','cold','frozen')
,`customer_grade` enum('A+','A','B','C','D')
,`total_purchase_amount` decimal(12,2)
,`assigned_to` int(11)
,`basket_type` enum('distribution','waiting','assigned')
,`assigned_at` timestamp
,`last_contact_at` timestamp
,`next_followup_at` timestamp
,`recall_at` timestamp
,`source` varchar(50)
,`notes` text
,`is_active` tinyint(1)
,`created_at` timestamp
,`updated_at` timestamp
,`total_purchase` decimal(12,2)
,`appointment_count` int(11)
,`appointment_extension_count` int(11)
,`last_appointment_date` timestamp
,`appointment_extension_expiry` timestamp
,`max_appointment_extensions` int(11)
,`appointment_extension_days` int(11)
,`customer_status` enum('new','existing')
,`customer_time_extension` int(11)
,`customer_time_base` timestamp
,`customer_time_expiry` timestamp
,`followup_days_remaining` int(8)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `customer_new_list`
-- (See below for the actual view)
--
CREATE TABLE `customer_new_list` (
`customer_id` int(11)
,`customer_code` varchar(50)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`phone` varchar(20)
,`email` varchar(100)
,`address` text
,`district` varchar(50)
,`province` varchar(50)
,`postal_code` varchar(10)
,`temperature_status` enum('hot','warm','cold','frozen')
,`customer_grade` enum('A+','A','B','C','D')
,`total_purchase_amount` decimal(12,2)
,`assigned_to` int(11)
,`basket_type` enum('distribution','waiting','assigned')
,`assigned_at` timestamp
,`last_contact_at` timestamp
,`next_followup_at` timestamp
,`recall_at` timestamp
,`source` varchar(50)
,`notes` text
,`is_active` tinyint(1)
,`created_at` timestamp
,`updated_at` timestamp
,`total_purchase` decimal(12,2)
,`appointment_count` int(11)
,`appointment_extension_count` int(11)
,`last_appointment_date` timestamp
,`appointment_extension_expiry` timestamp
,`max_appointment_extensions` int(11)
,`appointment_extension_days` int(11)
,`customer_status` enum('new','existing')
,`customer_time_extension` int(11)
,`customer_time_base` timestamp
,`customer_time_expiry` timestamp
,`days_remaining` int(8)
);

-- --------------------------------------------------------

--
-- Table structure for table `customer_recalls`
--

CREATE TABLE `customer_recalls` (
  `recall_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `recall_type` enum('timeout','manual','system') NOT NULL,
  `recall_reason` varchar(200) DEFAULT NULL,
  `previous_basket` enum('distribution','waiting','assigned') NOT NULL,
  `new_basket` enum('distribution','waiting','assigned') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_recalls`
--

INSERT INTO `customer_recalls` (`recall_id`, `customer_id`, `user_id`, `recall_type`, `recall_reason`, `previous_basket`, `new_basket`, `created_at`) VALUES
(1, 1, 3, 'timeout', 'เกินกำหนดเวลา', 'assigned', 'waiting', '2025-08-05 12:32:24'),
(2, 5, 4, 'timeout', 'เกินกำหนดเวลา', 'assigned', 'waiting', '2025-08-05 12:32:36');

-- --------------------------------------------------------

--
-- Table structure for table `customer_recall_list`
--

CREATE TABLE `customer_recall_list` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT 0,
  `days_since_contact` int(11) NOT NULL,
  `created_date` date NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('pending','assigned','contacted','completed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_time_extensions`
--

CREATE TABLE `customer_time_extensions` (
  `extension_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `extension_type` enum('sale','appointment','manual') NOT NULL,
  `extension_days` int(11) NOT NULL,
  `previous_expiry` timestamp NULL DEFAULT NULL,
  `new_expiry` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `reason` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_time_extensions`
--

INSERT INTO `customer_time_extensions` (`extension_id`, `customer_id`, `user_id`, `extension_type`, `extension_days`, `previous_expiry`, `new_expiry`, `reason`, `created_at`) VALUES
(1, 1, 3, 'sale', 90, '2025-01-30 17:00:00', '2025-04-30 17:00:00', 'ขายได้ครั้งแรก', '2025-08-05 12:23:51'),
(2, 4, 3, 'appointment', 30, '2025-01-30 17:00:00', '2025-03-01 17:00:00', 'นัดหมายเสร็จสิ้น', '2025-08-05 12:23:51'),
(3, 5, 4, 'sale', 90, '2025-01-30 17:00:00', '2025-04-30 17:00:00', 'ขายได้ครั้งแรก', '2025-08-05 12:23:51'),
(4, 1, 3, 'manual', 30, '2025-04-30 17:00:00', '2025-09-04 12:32:10', 'ทดสอบต่อเวลาหลังหมดอายุ', '2025-08-05 12:32:10');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `net_amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','transfer','cod','credit','other') DEFAULT 'cash',
  `payment_status` enum('pending','paid','partial','cancelled') DEFAULT 'pending',
  `delivery_date` date DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `delivery_status` enum('pending','shipped','delivered','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_number`, `customer_id`, `created_by`, `order_date`, `total_amount`, `discount_amount`, `discount_percentage`, `net_amount`, `payment_method`, `payment_status`, `delivery_date`, `delivery_address`, `delivery_status`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(13, 'ORD20250803D8F695', 1, 1, '2025-08-03', 100.00, 0.00, 0.00, 100.00, 'cod', 'paid', NULL, 'Test Address', 'pending', 'Test order', 1, '2025-08-03 09:29:29', '2025-08-03 09:41:53'),
(14, 'ORD20250803F92373', 1, 1, '2025-08-03', 100.00, 0.00, 0.00, 100.00, 'cod', 'pending', NULL, 'Test Address', 'pending', 'Test order', 1, '2025-08-03 09:30:47', '2025-08-03 09:30:47'),
(15, 'ORD20250803A214EB', 4, 1, '2025-08-03', 450.00, 0.00, 0.00, 450.00, 'cod', 'paid', '2025-08-14', '321 ถนนเพชรบุรี', 'pending', '555', 1, '2025-08-03 09:33:22', '2025-08-03 09:33:22'),
(16, 'ORD2025080362976A', 4, 1, '2025-08-03', 450.00, 0.00, 0.00, 450.00, 'cod', 'paid', '2025-08-14', '321 ถนนเพชรบุรี', 'pending', '555', 1, '2025-08-03 09:34:12', '2025-08-03 09:34:12'),
(17, 'ORD202508037E9FFC', 4, 1, '2025-08-03', 450.00, 0.00, 0.00, 450.00, 'cod', 'paid', '2025-08-14', '321 ถนนเพชรบุรี', 'pending', '555', 1, '2025-08-03 09:35:02', '2025-08-03 09:35:02'),
(18, 'ORD202508039CA40F', 1, 1, '2025-08-03', 100.00, 0.00, 0.00, 100.00, 'cod', 'pending', NULL, 'Test Address', 'pending', 'Test order', 1, '2025-08-03 09:35:18', '2025-08-03 09:35:18'),
(20, 'ORD20250803389C62', 1, 1, '2025-08-03', 100.00, 0.00, 0.00, 100.00, 'cod', 'pending', NULL, 'Test Address', 'pending', 'Test order', 1, '2025-08-03 09:35:32', '2025-08-03 09:35:32'),
(21, 'ORD20250803C2B2B7', 4, 1, '2025-08-03', 450.00, 0.00, 0.00, 450.00, 'cod', 'paid', '2025-08-14', '321 ถนนเพชรบุรี', 'pending', '555', 1, '2025-08-03 09:35:52', '2025-08-03 09:35:52'),
(22, 'ORD20250803604933', 4, 1, '2025-08-03', 450.00, 0.00, 0.00, 450.00, 'cod', 'paid', '2025-08-14', '321 ถนนเพชรบุรี', 'pending', '555', 1, '2025-08-03 09:36:42', '2025-08-03 09:36:42'),
(23, 'ORD2025080341DFA0', 4, 1, '2025-08-03', 450.00, 0.00, 0.00, 450.00, 'cod', 'paid', '2025-08-14', '321 ถนนเพชรบุรี', 'pending', '555', 1, '2025-08-03 09:37:32', '2025-08-03 09:37:32'),
(24, 'ORD20250803889D05', 4, 1, '2025-08-03', 450.00, 0.00, 0.00, 450.00, 'cod', 'paid', '2025-08-14', '321 ถนนเพชรบุรี', 'pending', '555', 1, '2025-08-03 09:38:22', '2025-08-03 09:38:22'),
(25, 'ORD20250803196D0C', 4, 1, '2025-08-03', 450.00, 0.00, 0.00, 450.00, 'cod', 'paid', '2025-08-14', '321 ถนนเพชรบุรี', 'pending', '555', 1, '2025-08-03 09:39:12', '2025-08-03 09:39:12'),
(26, 'ORD20250803BEE348', 4, 1, '2025-08-03', 450.00, 0.00, 0.00, 450.00, 'cod', 'paid', '2025-08-14', '321 ถนนเพชรบุรี', 'pending', '555', 1, '2025-08-03 09:39:12', '2025-08-03 09:39:12'),
(27, 'ORD20250803D29033', 4, 1, '2025-08-03', 450.00, 0.00, 0.00, 450.00, 'cod', 'paid', '2025-08-14', '321 ถนนเพชรบุรี', 'pending', '555', 1, '2025-08-03 09:39:12', '2025-08-03 09:39:12'),
(28, 'ORD20250803778327', 4, 1, '2025-08-03', 450.00, 0.00, 0.00, 450.00, 'cod', 'paid', '2025-08-14', '321 ถนนเพชรบุรี', 'pending', '555', 1, '2025-08-03 09:39:12', '2025-08-03 09:39:12'),
(29, 'ORD20250803908207', 4, 1, '2025-08-03', 450.00, 0.00, 0.00, 450.00, 'cod', 'paid', '2025-08-14', '321 ถนนเพชรบุรี', 'pending', '555', 1, '2025-08-03 09:39:12', '2025-08-03 09:39:12'),
(31, 'ORD20250803D25910', 1, 1, '2025-08-03', 450.00, 45.00, 10.00, 405.00, 'cod', 'paid', '2025-08-11', '123 ถนนสุขุมวิท', 'pending', 'ทดสอบว่าช้ามั้ย', 1, '2025-08-03 09:41:19', '2025-08-03 09:41:19'),
(32, 'ORD20250803A46396', 3, 1, '2025-08-03', 450.00, 0.00, 0.00, 450.00, 'transfer', 'paid', '2025-08-05', '789 ถนนลาดพร้าว', 'pending', NULL, 1, '2025-08-03 10:44:30', '2025-08-03 10:44:30'),
(34, 'ORD202508036ABD4D', 1, 3, '2025-08-03', 700.00, 0.00, 0.00, 700.00, 'cash', 'pending', '2025-08-03', '123 ถนนสุขุมวิท', 'pending', '121', 1, '2025-08-03 13:24:46', '2025-08-03 13:24:46'),
(36, 'ORD202508032D6B8E', 1, 3, '2025-08-03', 450.00, 0.00, 0.00, 450.00, 'cash', 'pending', '2025-08-03', '123 ถนนสุขุมวิท', 'delivered', '555', 1, '2025-08-03 13:36:18', '2025-08-04 16:16:39'),
(37, 'ORD20250805420131', 1, 3, '2025-08-05', 1500.00, 0.00, 0.00, 1500.00, 'cod', 'pending', '2025-08-05', '123 ถนนสุขุมวิท', 'delivered', '22', 1, '2025-08-05 08:07:49', '2025-08-05 08:08:19'),
(39, 'ORD20250805A9F387', 11, 3, '2025-08-05', 450.00, 0.00, 0.00, 450.00, 'cod', 'pending', '2025-08-05', '303 ถ.สีลม กรุงเทพฯ', 'delivered', 'กกกกกก', 1, '2025-08-05 10:07:42', '2025-08-05 11:43:40'),
(40, 'ORD202508054E87F5', 11, 3, '2025-08-05', 450.00, 0.00, 0.00, 450.00, 'cod', 'pending', '2025-08-05', '303 ถ.สีลม กรุงเทพฯ', 'pending', '2222', 1, '2025-08-05 13:55:24', '2025-08-05 13:55:24'),
(41, 'ORD2025080664A1EE', 2, 1, '2025-08-06', 1000.00, 0.00, 0.00, 1000.00, 'cash', 'pending', NULL, NULL, 'delivered', 'ทดสอบการต่อเวลาอัตโนมัติ', 1, '2025-08-06 02:08:45', '2025-08-06 02:12:52'),
(42, 'ORD20250806D52EB3', 11, 1, '2025-08-06', 1000.00, 0.00, 0.00, 1000.00, 'cash', 'pending', NULL, NULL, 'delivered', 'ทดสอบการต่อเวลาอัตโนมัติ', 1, '2025-08-06 02:09:46', '2025-08-06 02:12:44');

-- --------------------------------------------------------

--
-- Table structure for table `order_activities`
--

CREATE TABLE `order_activities` (
  `activity_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('created','status_update','payment_update','delivery_update','cancelled') NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_activities`
--

INSERT INTO `order_activities` (`activity_id`, `order_id`, `user_id`, `activity_type`, `description`, `created_at`) VALUES
(1, 13, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250803D8F695', '2025-08-03 09:30:19'),
(2, 14, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250803F92373', '2025-08-03 09:31:37'),
(3, 15, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250803A214EB', '2025-08-03 09:34:12'),
(4, 16, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD2025080362976A', '2025-08-03 09:35:02'),
(5, 17, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD202508037E9FFC', '2025-08-03 09:35:52'),
(6, 18, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD202508039CA40F', '2025-08-03 09:36:08'),
(7, 21, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250803C2B2B7', '2025-08-03 09:36:42'),
(8, 20, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250803389C62', '2025-08-03 09:36:58'),
(9, 22, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250803604933', '2025-08-03 09:37:32'),
(10, 23, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD2025080341DFA0', '2025-08-03 09:38:22'),
(11, 24, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250803889D05', '2025-08-03 09:39:12'),
(12, 25, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250803196D0C', '2025-08-03 09:39:12'),
(13, 26, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250803BEE348', '2025-08-03 09:39:12'),
(14, 27, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250803D29033', '2025-08-03 09:39:12'),
(15, 28, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250803778327', '2025-08-03 09:39:12'),
(16, 29, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250803908207', '2025-08-03 09:39:12'),
(19, 31, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250803D25910', '2025-08-03 09:41:19'),
(20, 13, 1, 'status_update', 'อัปเดต payment_status เป็น: paid', '2025-08-03 09:41:53'),
(21, 32, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250803A46396', '2025-08-03 10:44:30'),
(23, 34, 3, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD202508036ABD4D', '2025-08-03 13:24:46'),
(25, 36, 3, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD202508032D6B8E', '2025-08-03 13:36:18'),
(26, 36, 3, 'status_update', 'อัปเดต delivery_status เป็น: delivered', '2025-08-03 14:25:51'),
(27, 36, 3, 'status_update', 'อัปเดต delivery_status เป็น: shipped', '2025-08-03 14:25:58'),
(28, 36, 3, 'status_update', 'อัปเดต delivery_status เป็น: cancelled', '2025-08-03 14:34:50'),
(29, 36, 3, 'status_update', 'อัปเดต delivery_status เป็น: delivered', '2025-08-04 16:16:39'),
(30, 37, 3, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250805420131', '2025-08-05 08:07:49'),
(31, 37, 3, 'status_update', 'อัปเดต delivery_status เป็น: delivered', '2025-08-05 08:08:19'),
(33, 39, 3, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250805A9F387', '2025-08-05 10:07:42'),
(34, 39, 3, 'status_update', 'อัปเดต delivery_status เป็น: delivered', '2025-08-05 11:43:40'),
(35, 40, 3, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD202508054E87F5', '2025-08-05 13:55:24'),
(36, 41, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD2025080664A1EE', '2025-08-06 02:09:35'),
(37, 42, 1, 'created', 'สร้างคำสั่งซื้อใหม่ หมายเลข: ORD20250806D52EB3', '2025-08-06 02:10:36'),
(38, 42, 1, 'status_update', 'อัปเดต delivery_status เป็น: delivered', '2025-08-06 02:12:44'),
(39, 41, 1, 'status_update', 'อัปเดต delivery_status เป็น: delivered', '2025-08-06 02:12:52');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `detail_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `net_price` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(15, 13, 1, 1, 100.00, 100.00),
(16, 14, 1, 1, 100.00, 100.00),
(17, 15, 2, 1, 450.00, 450.00),
(18, 16, 2, 1, 450.00, 450.00),
(19, 17, 2, 1, 450.00, 450.00),
(20, 18, 1, 1, 100.00, 100.00),
(21, 21, 2, 1, 450.00, 450.00),
(22, 20, 1, 1, 100.00, 100.00),
(24, 22, 2, 1, 450.00, 450.00),
(25, 23, 2, 1, 450.00, 450.00),
(26, 24, 2, 1, 450.00, 450.00),
(27, 25, 2, 1, 450.00, 450.00),
(28, 26, 2, 1, 450.00, 450.00),
(29, 27, 2, 1, 450.00, 450.00),
(30, 28, 2, 1, 450.00, 450.00),
(31, 29, 2, 1, 450.00, 450.00),
(33, 31, 2, 1, 450.00, 450.00),
(34, 32, 2, 1, 450.00, 450.00),
(36, 34, 2, 1, 450.00, 450.00),
(37, 34, 1, 1, 250.00, 250.00),
(40, 36, 2, 1, 450.00, 450.00),
(41, 37, 2, 2, 450.00, 900.00),
(42, 37, 3, 1, 600.00, 600.00),
(45, 39, 2, 1, 450.00, 450.00),
(46, 40, 2, 1, 450.00, 450.00),
(47, 41, 1, 1, 1000.00, 1000.00),
(48, 42, 1, 1, 1000.00, 1000.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(20) DEFAULT 'ชิ้น',
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `selling_price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_code`, `product_name`, `category`, `description`, `unit`, `cost_price`, `selling_price`, `stock_quantity`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'P001', 'เสื้อโปโล', 'เสื้อผ้า', 'เสื้อโปโลคุณภาพดี', 'ตัว', 150.00, 250.00, 100, 1, '2025-08-03 07:19:20', '2025-08-03 07:19:20'),
(2, 'P002', 'กางเกงยีนส์', 'เสื้อผ้า', 'กางเกงยีนส์สไตล์สวย', 'ตัว', 300.00, 450.00, 50, 1, '2025-08-03 07:19:20', '2025-08-03 07:19:20'),
(3, 'P003', 'รองเท้าผ้าใบ', 'รองเท้า', 'รองเท้าผ้าใบสไตล์สปอร์ต', 'คู่', 400.00, 600.00, 30, 1, '2025-08-03 07:19:20', '2025-08-03 07:19:20'),
(4, 'P004', 'กระเป๋าถือ', 'กระเป๋า', 'กระเป๋าถือสไตล์แฟชั่น', 'ใบ', 200.00, 350.00, 25, 1, '2025-08-03 07:19:20', '2025-08-03 07:19:20');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `role_description`, `permissions`, `created_at`) VALUES
(1, 'super_admin', 'Super Administrator', '[\"all\"]', '2025-08-03 07:19:20'),
(2, 'admin', 'Company Administrator', '[\"user_management\", \"product_management\", \"data_import\", \"reports\"]', '2025-08-03 07:19:20'),
(3, 'supervisor', 'Team Supervisor', '[\"team_overview\", \"lead_distribution\", \"team_reports\"]', '2025-08-03 07:19:20'),
(4, 'telesales', 'Telesales Representative', '[\"customer_management\", \"order_creation\", \"personal_reports\"]', '2025-08-03 07:19:20');

-- --------------------------------------------------------

--
-- Table structure for table `sales_history`
--

CREATE TABLE `sales_history` (
  `history_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `unassigned_at` timestamp NULL DEFAULT NULL,
  `reason` varchar(200) DEFAULT NULL,
  `total_orders` int(11) DEFAULT 0,
  `total_sales` decimal(12,2) DEFAULT 0.00,
  `is_current` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_editable` tinyint(1) DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_editable`, `updated_by`, `updated_at`) VALUES
(1, 'customer_grade_a_plus', '50000', 'number', 'Minimum purchase amount for A+ grade', 1, NULL, '2025-08-03 07:19:20'),
(2, 'customer_grade_a', '10000', 'number', 'Minimum purchase amount for A grade', 1, NULL, '2025-08-03 07:19:20'),
(3, 'customer_grade_b', '5000', 'number', 'Minimum purchase amount for B grade', 1, NULL, '2025-08-03 07:19:20'),
(4, 'customer_grade_c', '2000', 'number', 'Minimum purchase amount for C grade', 1, NULL, '2025-08-03 07:19:20'),
(5, 'new_customer_recall_days', '30', 'number', 'Days before recalling new customers', 1, NULL, '2025-08-03 07:19:20'),
(6, 'existing_customer_recall_days', '90', 'number', 'Days before recalling existing customers', 1, NULL, '2025-08-03 07:19:20'),
(7, 'waiting_basket_days', '30', 'number', 'Days customers stay in waiting basket', 1, NULL, '2025-08-03 07:19:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `role_id`, `company_id`, `is_active`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin@prima49.com', '081-234-5678', 1, 1, 1, '2025-08-03 07:19:20', '2025-08-06 14:15:30', '2025-08-06 14:15:30'),
(2, 'supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'หัวหน้าทีมขาย', 'supervisor@prima49.com', '081-234-5679', 3, 1, 1, '2025-08-03 07:19:20', '2025-08-03 07:19:20', NULL),
(3, 'telesales1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'พนักงานขาย 1', 'telesales1@prima49.com', '081-234-5680', 4, 1, 1, '2025-08-03 07:19:20', '2025-08-06 04:27:56', '2025-08-06 04:27:56'),
(4, 'telesales2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'พนักงานขาย 2', 'telesales2@prima49.com', '081-234-5681', 4, 1, 1, '2025-08-03 07:19:20', '2025-08-03 07:19:20', NULL);

-- --------------------------------------------------------

--
-- Structure for view `customer_appointment_extensions`
--
DROP TABLE IF EXISTS `customer_appointment_extensions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`primacom_bloguser`@`localhost` SQL SECURITY DEFINER VIEW `customer_appointment_extensions`  AS SELECT `c`.`customer_id` AS `customer_id`, concat(`c`.`first_name`,' ',`c`.`last_name`) AS `customer_name`, `c`.`customer_grade` AS `customer_grade`, `c`.`temperature_status` AS `temperature_status`, `c`.`appointment_count` AS `appointment_count`, `c`.`appointment_extension_count` AS `appointment_extension_count`, `c`.`max_appointment_extensions` AS `max_appointment_extensions`, `c`.`appointment_extension_expiry` AS `appointment_extension_expiry`, `c`.`appointment_extension_days` AS `appointment_extension_days`, `c`.`last_appointment_date` AS `last_appointment_date`, CASE WHEN `c`.`appointment_extension_expiry` is null THEN 'ไม่มีวันหมดอายุ' WHEN `c`.`appointment_extension_expiry` < current_timestamp() THEN 'หมดอายุแล้ว' ELSE 'ยังไม่หมดอายุ' END AS `expiry_status`, CASE WHEN `c`.`appointment_extension_count` >= `c`.`max_appointment_extensions` THEN 'ไม่สามารถต่อเวลาได้แล้ว' ELSE 'สามารถต่อเวลาได้' END AS `extension_status`, `u`.`username` AS `assigned_user` FROM (`customers` `c` left join `users` `u` on(`c`.`assigned_to` = `u`.`user_id`)) WHERE `c`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `customer_do_list`
--
DROP TABLE IF EXISTS `customer_do_list`;

CREATE ALGORITHM=UNDEFINED DEFINER=`primacom_bloguser`@`localhost` SQL SECURITY DEFINER VIEW `customer_do_list`  AS SELECT `c`.`customer_id` AS `customer_id`, `c`.`customer_code` AS `customer_code`, `c`.`first_name` AS `first_name`, `c`.`last_name` AS `last_name`, `c`.`phone` AS `phone`, `c`.`email` AS `email`, `c`.`address` AS `address`, `c`.`district` AS `district`, `c`.`province` AS `province`, `c`.`postal_code` AS `postal_code`, `c`.`temperature_status` AS `temperature_status`, `c`.`customer_grade` AS `customer_grade`, `c`.`total_purchase_amount` AS `total_purchase_amount`, `c`.`assigned_to` AS `assigned_to`, `c`.`basket_type` AS `basket_type`, `c`.`assigned_at` AS `assigned_at`, `c`.`last_contact_at` AS `last_contact_at`, `c`.`next_followup_at` AS `next_followup_at`, `c`.`recall_at` AS `recall_at`, `c`.`source` AS `source`, `c`.`notes` AS `notes`, `c`.`is_active` AS `is_active`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at`, `c`.`total_purchase` AS `total_purchase`, `c`.`appointment_count` AS `appointment_count`, `c`.`appointment_extension_count` AS `appointment_extension_count`, `c`.`last_appointment_date` AS `last_appointment_date`, `c`.`appointment_extension_expiry` AS `appointment_extension_expiry`, `c`.`max_appointment_extensions` AS `max_appointment_extensions`, `c`.`appointment_extension_days` AS `appointment_extension_days`, `c`.`customer_status` AS `customer_status`, `c`.`customer_time_extension` AS `customer_time_extension`, `c`.`customer_time_base` AS `customer_time_base`, `c`.`customer_time_expiry` AS `customer_time_expiry`, to_days(`c`.`customer_time_expiry`) - to_days(current_timestamp()) AS `days_remaining`, CASE WHEN `c`.`customer_status` = 'new' THEN 'ลูกค้าใหม่' WHEN `c`.`customer_status` = 'existing' THEN 'ลูกค้าเก่า' END AS `status_text`, CASE WHEN `c`.`customer_time_expiry` <= current_timestamp() THEN 'เกินกำหนด' WHEN `c`.`customer_time_expiry` <= current_timestamp() + interval 7 day THEN 'ใกล้หมดเวลา' ELSE 'ปกติ' END AS `urgency_status` FROM `customers` AS `c` WHERE `c`.`assigned_to` is not null AND `c`.`basket_type` = 'assigned' AND `c`.`is_active` = 1 AND (`c`.`customer_time_expiry` <= current_timestamp() + interval 7 day OR `c`.`next_followup_at` <= current_timestamp()) ;

-- --------------------------------------------------------

--
-- Structure for view `customer_existing_list`
--
DROP TABLE IF EXISTS `customer_existing_list`;

CREATE ALGORITHM=UNDEFINED DEFINER=`primacom_bloguser`@`localhost` SQL SECURITY DEFINER VIEW `customer_existing_list`  AS SELECT `c`.`customer_id` AS `customer_id`, `c`.`customer_code` AS `customer_code`, `c`.`first_name` AS `first_name`, `c`.`last_name` AS `last_name`, `c`.`phone` AS `phone`, `c`.`email` AS `email`, `c`.`address` AS `address`, `c`.`district` AS `district`, `c`.`province` AS `province`, `c`.`postal_code` AS `postal_code`, `c`.`temperature_status` AS `temperature_status`, `c`.`customer_grade` AS `customer_grade`, `c`.`total_purchase_amount` AS `total_purchase_amount`, `c`.`assigned_to` AS `assigned_to`, `c`.`basket_type` AS `basket_type`, `c`.`assigned_at` AS `assigned_at`, `c`.`last_contact_at` AS `last_contact_at`, `c`.`next_followup_at` AS `next_followup_at`, `c`.`recall_at` AS `recall_at`, `c`.`source` AS `source`, `c`.`notes` AS `notes`, `c`.`is_active` AS `is_active`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at`, `c`.`total_purchase` AS `total_purchase`, `c`.`appointment_count` AS `appointment_count`, `c`.`appointment_extension_count` AS `appointment_extension_count`, `c`.`last_appointment_date` AS `last_appointment_date`, `c`.`appointment_extension_expiry` AS `appointment_extension_expiry`, `c`.`max_appointment_extensions` AS `max_appointment_extensions`, `c`.`appointment_extension_days` AS `appointment_extension_days`, `c`.`customer_status` AS `customer_status`, `c`.`customer_time_extension` AS `customer_time_extension`, `c`.`customer_time_base` AS `customer_time_base`, `c`.`customer_time_expiry` AS `customer_time_expiry`, to_days(`c`.`customer_time_expiry`) - to_days(current_timestamp()) AS `days_remaining` FROM `customers` AS `c` WHERE `c`.`assigned_to` is not null AND `c`.`basket_type` = 'assigned' AND `c`.`customer_status` = 'existing' AND `c`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `customer_followup_list`
--
DROP TABLE IF EXISTS `customer_followup_list`;

CREATE ALGORITHM=UNDEFINED DEFINER=`primacom_bloguser`@`localhost` SQL SECURITY DEFINER VIEW `customer_followup_list`  AS SELECT `c`.`customer_id` AS `customer_id`, `c`.`customer_code` AS `customer_code`, `c`.`first_name` AS `first_name`, `c`.`last_name` AS `last_name`, `c`.`phone` AS `phone`, `c`.`email` AS `email`, `c`.`address` AS `address`, `c`.`district` AS `district`, `c`.`province` AS `province`, `c`.`postal_code` AS `postal_code`, `c`.`temperature_status` AS `temperature_status`, `c`.`customer_grade` AS `customer_grade`, `c`.`total_purchase_amount` AS `total_purchase_amount`, `c`.`assigned_to` AS `assigned_to`, `c`.`basket_type` AS `basket_type`, `c`.`assigned_at` AS `assigned_at`, `c`.`last_contact_at` AS `last_contact_at`, `c`.`next_followup_at` AS `next_followup_at`, `c`.`recall_at` AS `recall_at`, `c`.`source` AS `source`, `c`.`notes` AS `notes`, `c`.`is_active` AS `is_active`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at`, `c`.`total_purchase` AS `total_purchase`, `c`.`appointment_count` AS `appointment_count`, `c`.`appointment_extension_count` AS `appointment_extension_count`, `c`.`last_appointment_date` AS `last_appointment_date`, `c`.`appointment_extension_expiry` AS `appointment_extension_expiry`, `c`.`max_appointment_extensions` AS `max_appointment_extensions`, `c`.`appointment_extension_days` AS `appointment_extension_days`, `c`.`customer_status` AS `customer_status`, `c`.`customer_time_extension` AS `customer_time_extension`, `c`.`customer_time_base` AS `customer_time_base`, `c`.`customer_time_expiry` AS `customer_time_expiry`, to_days(`c`.`next_followup_at`) - to_days(current_timestamp()) AS `followup_days_remaining` FROM `customers` AS `c` WHERE `c`.`assigned_to` is not null AND `c`.`basket_type` = 'assigned' AND `c`.`next_followup_at` is not null AND `c`.`next_followup_at` <= current_timestamp() AND `c`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `customer_new_list`
--
DROP TABLE IF EXISTS `customer_new_list`;

CREATE ALGORITHM=UNDEFINED DEFINER=`primacom_bloguser`@`localhost` SQL SECURITY DEFINER VIEW `customer_new_list`  AS SELECT `c`.`customer_id` AS `customer_id`, `c`.`customer_code` AS `customer_code`, `c`.`first_name` AS `first_name`, `c`.`last_name` AS `last_name`, `c`.`phone` AS `phone`, `c`.`email` AS `email`, `c`.`address` AS `address`, `c`.`district` AS `district`, `c`.`province` AS `province`, `c`.`postal_code` AS `postal_code`, `c`.`temperature_status` AS `temperature_status`, `c`.`customer_grade` AS `customer_grade`, `c`.`total_purchase_amount` AS `total_purchase_amount`, `c`.`assigned_to` AS `assigned_to`, `c`.`basket_type` AS `basket_type`, `c`.`assigned_at` AS `assigned_at`, `c`.`last_contact_at` AS `last_contact_at`, `c`.`next_followup_at` AS `next_followup_at`, `c`.`recall_at` AS `recall_at`, `c`.`source` AS `source`, `c`.`notes` AS `notes`, `c`.`is_active` AS `is_active`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at`, `c`.`total_purchase` AS `total_purchase`, `c`.`appointment_count` AS `appointment_count`, `c`.`appointment_extension_count` AS `appointment_extension_count`, `c`.`last_appointment_date` AS `last_appointment_date`, `c`.`appointment_extension_expiry` AS `appointment_extension_expiry`, `c`.`max_appointment_extensions` AS `max_appointment_extensions`, `c`.`appointment_extension_days` AS `appointment_extension_days`, `c`.`customer_status` AS `customer_status`, `c`.`customer_time_extension` AS `customer_time_extension`, `c`.`customer_time_base` AS `customer_time_base`, `c`.`customer_time_expiry` AS `customer_time_expiry`, to_days(`c`.`customer_time_expiry`) - to_days(current_timestamp()) AS `days_remaining` FROM `customers` AS `c` WHERE `c`.`assigned_to` is not null AND `c`.`basket_type` = 'assigned' AND `c`.`customer_status` = 'new' AND `c`.`is_active` = 1 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_table_name` (`table_name`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_appointment_date` (`appointment_date`),
  ADD KEY `idx_appointment_status` (`appointment_status`),
  ADD KEY `idx_appointment_type` (`appointment_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `appointment_activities`
--
ALTER TABLE `appointment_activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_appointment_id` (`appointment_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `appointment_extensions`
--
ALTER TABLE `appointment_extensions`
  ADD PRIMARY KEY (`extension_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_appointment_id` (`appointment_id`),
  ADD KEY `idx_extension_type` (`extension_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `appointment_extension_rules`
--
ALTER TABLE `appointment_extension_rules`
  ADD PRIMARY KEY (`rule_id`),
  ADD KEY `idx_rule_name` (`rule_name`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `call_logs`
--
ALTER TABLE `call_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_call_date` (`created_at`),
  ADD KEY `idx_next_followup` (`next_followup_at`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`company_id`),
  ADD UNIQUE KEY `company_code` (`company_code`),
  ADD KEY `idx_company_code` (`company_code`);

--
-- Indexes for table `cron_job_logs`
--
ALTER TABLE `cron_job_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_job_name` (`job_name`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_time` (`start_time`);

--
-- Indexes for table `cron_job_settings`
--
ALTER TABLE `cron_job_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_name` (`job_name`),
  ADD KEY `idx_job_name` (`job_name`),
  ADD KEY `idx_is_enabled` (`is_enabled`),
  ADD KEY `idx_next_run` (`next_run`);

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
-- Indexes for table `customer_activities`
--
ALTER TABLE `customer_activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `customer_recalls`
--
ALTER TABLE `customer_recalls`
  ADD PRIMARY KEY (`recall_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_recall_type` (`recall_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_customer_recalls_customer_id` (`customer_id`),
  ADD KEY `idx_customer_recalls_created_at` (`created_at`);

--
-- Indexes for table `customer_recall_list`
--
ALTER TABLE `customer_recall_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_created_date` (`created_date`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_customer_recall_assigned_to` (`assigned_to`);

--
-- Indexes for table `customer_time_extensions`
--
ALTER TABLE `customer_time_extensions`
  ADD PRIMARY KEY (`extension_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_extension_type` (`extension_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_customer_time_extensions_customer_id` (`customer_id`),
  ADD KEY `idx_customer_time_extensions_created_at` (`created_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_order_date` (`order_date`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_delivery_status` (`delivery_status`),
  ADD KEY `idx_orders_customer_id` (`customer_id`),
  ADD KEY `idx_orders_created_by` (`created_by`),
  ADD KEY `idx_orders_order_date` (`order_date`),
  ADD KEY `idx_orders_payment_status` (`payment_status`),
  ADD KEY `idx_orders_delivery_status` (`delivery_status`),
  ADD KEY `idx_orders_created_at` (`created_at`);

--
-- Indexes for table `order_activities`
--
ALTER TABLE `order_activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_activity_date` (`created_at`),
  ADD KEY `idx_order_activities_order_id` (`order_id`),
  ADD KEY `idx_order_activities_user_id` (`user_id`),
  ADD KEY `idx_order_activities_created_at` (`created_at`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_order_items_order_id` (`order_id`),
  ADD KEY `idx_order_items_product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `product_code` (`product_code`),
  ADD KEY `idx_product_code` (`product_code`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_products_product_code` (`product_code`),
  ADD KEY `idx_products_is_active` (`is_active`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD KEY `idx_role_name` (`role_name`);

--
-- Indexes for table `sales_history`
--
ALTER TABLE `sales_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_assigned_at` (`assigned_at`),
  ADD KEY `idx_is_current` (`is_current`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `appointment_activities`
--
ALTER TABLE `appointment_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `appointment_extensions`
--
ALTER TABLE `appointment_extensions`
  MODIFY `extension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `appointment_extension_rules`
--
ALTER TABLE `appointment_extension_rules`
  MODIFY `rule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `call_logs`
--
ALTER TABLE `call_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cron_job_logs`
--
ALTER TABLE `cron_job_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cron_job_settings`
--
ALTER TABLE `cron_job_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `customer_activities`
--
ALTER TABLE `customer_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `customer_recalls`
--
ALTER TABLE `customer_recalls`
  MODIFY `recall_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer_recall_list`
--
ALTER TABLE `customer_recall_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_time_extensions`
--
ALTER TABLE `customer_time_extensions`
  MODIFY `extension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT for table `order_activities`
--
ALTER TABLE `order_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales_history`
--
ALTER TABLE `sales_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_logs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `appointment_activities`
--
ALTER TABLE `appointment_activities`
  ADD CONSTRAINT `appointment_activities_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_activities_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `appointment_extensions`
--
ALTER TABLE `appointment_extensions`
  ADD CONSTRAINT `appointment_extensions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_extensions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `appointment_extensions_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL;

--
-- Constraints for table `call_logs`
--
ALTER TABLE `call_logs`
  ADD CONSTRAINT `call_logs_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `call_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `customer_activities`
--
ALTER TABLE `customer_activities`
  ADD CONSTRAINT `customer_activities_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `customer_activities_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `customer_recalls`
--
ALTER TABLE `customer_recalls`
  ADD CONSTRAINT `customer_recalls_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_recalls_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `customer_recall_list`
--
ALTER TABLE `customer_recall_list`
  ADD CONSTRAINT `fk_customer_recall_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_customer_recall_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_time_extensions`
--
ALTER TABLE `customer_time_extensions`
  ADD CONSTRAINT `customer_time_extensions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_time_extensions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_activities`
--
ALTER TABLE `order_activities`
  ADD CONSTRAINT `order_activities_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_activities_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `sales_history`
--
ALTER TABLE `sales_history`
  ADD CONSTRAINT `sales_history_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `sales_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
