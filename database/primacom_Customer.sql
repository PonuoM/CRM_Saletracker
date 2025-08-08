-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 08, 2025 at 05:26 AM
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
(15, 66, 6, '2025-08-08 12:00:00', 'call', 'scheduled', NULL, NULL, NULL, NULL, NULL, '', 0, NULL, '2025-08-08 04:57:11', '2025-08-08 04:57:11'),
(16, 65, 6, '2025-08-08 13:05:00', 'call', 'scheduled', NULL, NULL, NULL, NULL, NULL, 'ทดสอบการจับข้อมูล', 0, NULL, '2025-08-08 05:04:51', '2025-08-08 05:04:51'),
(17, 65, 6, '2025-08-08 13:12:00', 'call', 'scheduled', NULL, NULL, NULL, NULL, NULL, 'ทดสอบ', 0, NULL, '2025-08-08 05:11:25', '2025-08-08 05:11:25');

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
(14, 15, 6, 'created', 'สร้างนัดหมายใหม่', NULL, '2025-08-08 04:57:11'),
(15, 16, 6, 'created', 'สร้างนัดหมายใหม่', NULL, '2025-08-08 05:04:51'),
(16, 17, 6, 'created', 'สร้างนัดหมายใหม่', NULL, '2025-08-08 05:11:25');

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
(1, 'พรีม่าแพสชั่น49', 'PRIMA49', '123 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110', '02-123-4567', 'info@prima49.com', 1, '2025-08-03 07:19:20'),
(2, 'พรีออนิค', 'A02', '71/13, บรมราชชนนี, อรุณอมรินทร์, บางกอกน้อย, กรุงเทพมหานคร 10700', '0989999999', 'peionic@gmail.com', 1, '2025-08-07 04:50:35');

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

INSERT INTO `customers` (`customer_id`, `customer_code`, `first_name`, `last_name`, `phone`, `email`, `address`, `district`, `province`, `postal_code`, `temperature_status`, `customer_grade`, `total_purchase_amount`, `assigned_to`, `basket_type`, `assigned_at`, `last_contact_at`, `next_followup_at`, `recall_at`, `source`, `notes`, `is_active`, `created_at`, `updated_at`, `appointment_count`, `appointment_extension_count`, `last_appointment_date`, `appointment_extension_expiry`, `max_appointment_extensions`, `appointment_extension_days`, `customer_status`, `customer_time_extension`, `customer_time_base`, `customer_time_expiry`) VALUES
(65, 'Cus-927879497', 'อำนาจ', 'ศุภผล', '927879497', '', '101.หมู่.12.บ้านสันดอน ต.รางบัว.อ.จอมบึง ราชบุรี 70150 ต.รางบัว อ.จอมบึง จ.ราชบุรี 70150', '', 'ราชบุรี', '70150', 'hot', 'B', 6640.00, 6, 'assigned', NULL, NULL, '2025-08-08 06:12:00', NULL, NULL, NULL, 1, '2025-08-08 03:30:50', '2025-08-08 05:11:25', 2, 0, '2025-08-08 06:12:00', NULL, 3, 30, '', 0, '2025-08-08 03:30:50', '2025-11-06 03:30:50'),
(66, 'Cus-869038460', 'ไซบะห์', 'เสน่หา', '869038460', '', '74 ซ.กรุงเทพกรีฑา15 แยก 4 เส้นตัดใหม่ ร่มเกล้า - ศรีนครินทร์ อยู่ตรงยูเทรินใต้สะพาน เข้าซอยตึกสีชมพู ข้างเต้นรถ ต.หัวหมาก อ.บางกะปิ จ.กรุงเทพมหานคร 10250', '', 'กรุงเทพมหานคร', '10250', 'hot', 'D', 445.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-08-08 03:30:50', '2025-08-08 04:57:11', 1, 0, '2025-08-08 05:00:00', NULL, 3, 30, 'new', 0, '2025-08-08 03:30:50', '2025-11-06 03:30:50'),
(67, 'Cus-868104111', 'นายสมาน', 'วัฒนชัยวรรณ์', '868104111', '', '222ถ.เสือป่า ซ.ทิพย์นิเวศน์5 ต.หน้าเมือง อ.เมืองราชบุรี จ.ราชบุรี 70000', '', 'ราชบุรี', '70000', 'hot', 'D', 790.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50', 0, 0, NULL, NULL, 3, 30, 'new', 0, '2025-08-08 03:30:50', '2025-11-06 03:30:50'),
(68, 'Cus-924656116', 'ไก่', 'ปริศณา', '924656116', '', '227 ม.9 ต.นาข่า อ.เมืองอุดรธานี จ.อุดรธานี 41000', '', 'อุดรธานี', '41000', 'hot', 'D', 790.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50', 0, 0, NULL, NULL, 3, 30, 'new', 0, '2025-08-08 03:30:50', '2025-11-06 03:30:50'),
(69, 'Cus-818615809', 'ปัญฑิต', 'กิตติสุทรโลภาค', '818615809', '', '117/6 ม.7 ต.บ้านใหญ่ อ.เมืองนครนายก จ.นครนายก 26000', '', 'นครนายก', '26000', 'hot', 'D', 1165.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50', 0, 0, NULL, NULL, 3, 30, 'new', 0, '2025-08-08 03:30:50', '2025-11-06 03:30:50'),
(70, 'Cus-929865556', 'มาโนด', 'สวัสดิ์ธรรม', '929865556', '', '67/1 ม.1 ต.ปรังเผล อ.สังขละบุรี จ.กาญจนบุรี 71240', '', 'กาญจนบุรี', '71240', 'hot', 'A', 21200.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50', 0, 0, NULL, NULL, 3, 30, 'new', 0, '2025-08-08 03:30:50', '2025-11-06 03:30:50'),
(71, 'Cus-902799079', 'ครูบาศักดิ์', '', '902799079', '959634001', '187ม.3 วัดป่าบ้านกลาง ต.ปลาบ่า อ.ภูเรือ จ.เลย 42160', '', 'เลย', '42160', 'hot', 'D', 1100.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50', 0, 0, NULL, NULL, 3, 30, 'new', 0, '2025-08-08 03:30:50', '2025-11-06 03:30:50');

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
-- Table structure for table `customer_do_list`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`primacom_bloguser`@`localhost` SQL SECURITY DEFINER VIEW `customer_do_list`  AS SELECT `c`.`customer_id` AS `customer_id`, `c`.`customer_code` AS `customer_code`, `c`.`first_name` AS `first_name`, `c`.`last_name` AS `last_name`, `c`.`phone` AS `phone`, `c`.`email` AS `email`, `c`.`address` AS `address`, `c`.`district` AS `district`, `c`.`province` AS `province`, `c`.`postal_code` AS `postal_code`, `c`.`temperature_status` AS `temperature_status`, `c`.`customer_grade` AS `customer_grade`, `c`.`total_purchase_amount` AS `total_purchase_amount`, `c`.`assigned_to` AS `assigned_to`, `c`.`basket_type` AS `basket_type`, `c`.`assigned_at` AS `assigned_at`, `c`.`last_contact_at` AS `last_contact_at`, `c`.`next_followup_at` AS `next_followup_at`, `c`.`recall_at` AS `recall_at`, `c`.`source` AS `source`, `c`.`notes` AS `notes`, `c`.`is_active` AS `is_active`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at`, `c`.`total_purchase` AS `total_purchase`, `c`.`appointment_count` AS `appointment_count`, `c`.`appointment_extension_count` AS `appointment_extension_count`, `c`.`last_appointment_date` AS `last_appointment_date`, `c`.`appointment_extension_expiry` AS `appointment_extension_expiry`, `c`.`max_appointment_extensions` AS `max_appointment_extensions`, `c`.`appointment_extension_days` AS `appointment_extension_days`, `c`.`customer_status` AS `customer_status`, `c`.`customer_time_extension` AS `customer_time_extension`, `c`.`customer_time_base` AS `customer_time_base`, `c`.`customer_time_expiry` AS `customer_time_expiry`, to_days(`c`.`customer_time_expiry`) - to_days(current_timestamp()) AS `days_remaining`, CASE WHEN `c`.`customer_status` = 'new' THEN 'ลูกค้าใหม่' WHEN `c`.`customer_status` = 'existing' THEN 'ลูกค้าเก่า' END AS `status_text`, CASE WHEN `c`.`customer_time_expiry` <= current_timestamp() THEN 'เกินกำหนด' WHEN `c`.`customer_time_expiry` <= current_timestamp() + interval 7 day THEN 'ใกล้หมดเวลา' ELSE 'ปกติ' END AS `urgency_status` FROM `customers` AS `c` WHERE `c`.`assigned_to` is not null AND `c`.`basket_type` = 'assigned' AND `c`.`is_active` = 1 AND (`c`.`customer_time_expiry` <= current_timestamp() + interval 7 day OR `c`.`next_followup_at` <= current_timestamp()) ;
-- Error reading data for table primacom_Customer.customer_do_list: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `primacom_Customer`.`customer_do_list`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `customer_existing_list`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`primacom_bloguser`@`localhost` SQL SECURITY DEFINER VIEW `customer_existing_list`  AS SELECT `c`.`customer_id` AS `customer_id`, `c`.`customer_code` AS `customer_code`, `c`.`first_name` AS `first_name`, `c`.`last_name` AS `last_name`, `c`.`phone` AS `phone`, `c`.`email` AS `email`, `c`.`address` AS `address`, `c`.`district` AS `district`, `c`.`province` AS `province`, `c`.`postal_code` AS `postal_code`, `c`.`temperature_status` AS `temperature_status`, `c`.`customer_grade` AS `customer_grade`, `c`.`total_purchase_amount` AS `total_purchase_amount`, `c`.`assigned_to` AS `assigned_to`, `c`.`basket_type` AS `basket_type`, `c`.`assigned_at` AS `assigned_at`, `c`.`last_contact_at` AS `last_contact_at`, `c`.`next_followup_at` AS `next_followup_at`, `c`.`recall_at` AS `recall_at`, `c`.`source` AS `source`, `c`.`notes` AS `notes`, `c`.`is_active` AS `is_active`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at`, `c`.`total_purchase` AS `total_purchase`, `c`.`appointment_count` AS `appointment_count`, `c`.`appointment_extension_count` AS `appointment_extension_count`, `c`.`last_appointment_date` AS `last_appointment_date`, `c`.`appointment_extension_expiry` AS `appointment_extension_expiry`, `c`.`max_appointment_extensions` AS `max_appointment_extensions`, `c`.`appointment_extension_days` AS `appointment_extension_days`, `c`.`customer_status` AS `customer_status`, `c`.`customer_time_extension` AS `customer_time_extension`, `c`.`customer_time_base` AS `customer_time_base`, `c`.`customer_time_expiry` AS `customer_time_expiry`, to_days(`c`.`customer_time_expiry`) - to_days(current_timestamp()) AS `days_remaining` FROM `customers` AS `c` WHERE `c`.`assigned_to` is not null AND `c`.`basket_type` = 'assigned' AND `c`.`customer_status` = 'existing' AND `c`.`is_active` = 1 ;
-- Error reading data for table primacom_Customer.customer_existing_list: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `primacom_Customer`.`customer_existing_list`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `customer_followup_list`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`primacom_bloguser`@`localhost` SQL SECURITY DEFINER VIEW `customer_followup_list`  AS SELECT `c`.`customer_id` AS `customer_id`, `c`.`customer_code` AS `customer_code`, `c`.`first_name` AS `first_name`, `c`.`last_name` AS `last_name`, `c`.`phone` AS `phone`, `c`.`email` AS `email`, `c`.`address` AS `address`, `c`.`district` AS `district`, `c`.`province` AS `province`, `c`.`postal_code` AS `postal_code`, `c`.`temperature_status` AS `temperature_status`, `c`.`customer_grade` AS `customer_grade`, `c`.`total_purchase_amount` AS `total_purchase_amount`, `c`.`assigned_to` AS `assigned_to`, `c`.`basket_type` AS `basket_type`, `c`.`assigned_at` AS `assigned_at`, `c`.`last_contact_at` AS `last_contact_at`, `c`.`next_followup_at` AS `next_followup_at`, `c`.`recall_at` AS `recall_at`, `c`.`source` AS `source`, `c`.`notes` AS `notes`, `c`.`is_active` AS `is_active`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at`, `c`.`total_purchase` AS `total_purchase`, `c`.`appointment_count` AS `appointment_count`, `c`.`appointment_extension_count` AS `appointment_extension_count`, `c`.`last_appointment_date` AS `last_appointment_date`, `c`.`appointment_extension_expiry` AS `appointment_extension_expiry`, `c`.`max_appointment_extensions` AS `max_appointment_extensions`, `c`.`appointment_extension_days` AS `appointment_extension_days`, `c`.`customer_status` AS `customer_status`, `c`.`customer_time_extension` AS `customer_time_extension`, `c`.`customer_time_base` AS `customer_time_base`, `c`.`customer_time_expiry` AS `customer_time_expiry`, to_days(`c`.`next_followup_at`) - to_days(current_timestamp()) AS `followup_days_remaining` FROM `customers` AS `c` WHERE `c`.`assigned_to` is not null AND `c`.`basket_type` = 'assigned' AND `c`.`next_followup_at` is not null AND `c`.`next_followup_at` <= current_timestamp() AND `c`.`is_active` = 1 ;
-- Error reading data for table primacom_Customer.customer_followup_list: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `primacom_Customer`.`customer_followup_list`' at line 1

-- --------------------------------------------------------

--
-- Table structure for table `customer_new_list`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`primacom_bloguser`@`localhost` SQL SECURITY DEFINER VIEW `customer_new_list`  AS SELECT `c`.`customer_id` AS `customer_id`, `c`.`customer_code` AS `customer_code`, `c`.`first_name` AS `first_name`, `c`.`last_name` AS `last_name`, `c`.`phone` AS `phone`, `c`.`email` AS `email`, `c`.`address` AS `address`, `c`.`district` AS `district`, `c`.`province` AS `province`, `c`.`postal_code` AS `postal_code`, `c`.`temperature_status` AS `temperature_status`, `c`.`customer_grade` AS `customer_grade`, `c`.`total_purchase_amount` AS `total_purchase_amount`, `c`.`assigned_to` AS `assigned_to`, `c`.`basket_type` AS `basket_type`, `c`.`assigned_at` AS `assigned_at`, `c`.`last_contact_at` AS `last_contact_at`, `c`.`next_followup_at` AS `next_followup_at`, `c`.`recall_at` AS `recall_at`, `c`.`source` AS `source`, `c`.`notes` AS `notes`, `c`.`is_active` AS `is_active`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at`, `c`.`total_purchase` AS `total_purchase`, `c`.`appointment_count` AS `appointment_count`, `c`.`appointment_extension_count` AS `appointment_extension_count`, `c`.`last_appointment_date` AS `last_appointment_date`, `c`.`appointment_extension_expiry` AS `appointment_extension_expiry`, `c`.`max_appointment_extensions` AS `max_appointment_extensions`, `c`.`appointment_extension_days` AS `appointment_extension_days`, `c`.`customer_status` AS `customer_status`, `c`.`customer_time_extension` AS `customer_time_extension`, `c`.`customer_time_base` AS `customer_time_base`, `c`.`customer_time_expiry` AS `customer_time_expiry`, to_days(`c`.`customer_time_expiry`) - to_days(current_timestamp()) AS `days_remaining` FROM `customers` AS `c` WHERE `c`.`assigned_to` is not null AND `c`.`basket_type` = 'assigned' AND `c`.`customer_status` = 'new' AND `c`.`is_active` = 1 ;
-- Error reading data for table primacom_Customer.customer_new_list: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near 'FROM `primacom_Customer`.`customer_new_list`' at line 1

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
(233, 'EXT-20250808103050-2407', 65, 6, '2025-06-06', 2190.00, 0.00, 0.00, 2190.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50'),
(234, 'EXT-20250808103050-5444', 65, 6, '2025-06-06', 2230.00, 0.00, 0.00, 2230.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50'),
(235, 'EXT-20250808103050-5243', 65, 6, '2025-06-06', 2220.00, 0.00, 0.00, 2220.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50'),
(236, 'EXT-20250808103050-6938', 66, 6, '2025-06-06', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50'),
(237, 'EXT-20250808103050-7345', 67, 6, '2025-06-06', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50'),
(238, 'EXT-20250808103050-6484', 68, 6, '2025-06-06', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50'),
(239, 'EXT-20250808103050-9459', 69, 6, '2025-06-06', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50'),
(240, 'EXT-20250808103050-7950', 70, 6, '2025-06-06', 10700.00, 0.00, 0.00, 10700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50'),
(241, 'EXT-20250808103050-2882', 70, 6, '2025-06-06', 10500.00, 0.00, 0.00, 10500.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50'),
(242, 'EXT-20250808103050-5925', 70, 6, '2025-06-06', 0.00, 0.00, 0.00, 0.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50'),
(243, 'EXT-20250808103050-3150', 70, 6, '2025-06-06', 0.00, 0.00, 0.00, 0.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50'),
(244, 'EXT-20250808103050-5631', 71, 6, '2025-06-06', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-08 03:30:50', '2025-08-08 03:30:50');

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
(135, 233, 8, 2, 1095.00, 2190.00),
(136, 234, 9, 2, 1115.00, 2230.00),
(137, 235, 10, 3, 740.00, 2220.00),
(138, 236, 11, 1, 445.00, 445.00),
(139, 237, 10, 1, 790.00, 790.00),
(140, 238, 10, 1, 790.00, 790.00),
(141, 239, 9, 1, 1165.00, 1165.00),
(142, 240, 8, 10, 1070.00, 10700.00),
(143, 241, 12, 10, 1050.00, 10500.00),
(144, 242, 13, 1, 0.00, 0.00),
(145, 243, 14, 2, 0.00, 0.00),
(146, 244, 12, 1, 1100.00, 1100.00);

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
(4, 'P004', 'กระเป๋าถือ', 'กระเป๋า', 'กระเป๋าถือสไตล์แฟชั่น', 'ใบ', 200.00, 350.00, 25, 1, '2025-08-03 07:19:20', '2025-08-03 07:19:20'),
(8, 'SCSR050002', 'สิงห์เขียว 50 กก. 4-4-12', 'ปุ๋ยกระสอบใหญ่', '', 'กระสอบ', 0.00, 0.00, 0, 1, '2025-08-07 12:20:40', '2025-08-07 12:20:40'),
(9, 'SCSR050001', 'สิงห์ส้ม 50 กก. 12-4-4', 'ปุ๋ยกระสอบใหญ่', '', 'กระสอบ', 0.00, 0.00, 0, 1, '2025-08-07 12:21:14', '2025-08-07 12:21:14'),
(10, '3SOSR050001', 'สิงห์ทอง 50 กก.', 'ปุ๋ยกระสอบใหญ่', '', 'กระสอบ', 0.00, 0.00, 0, 1, '2025-08-07 12:21:29', '2025-08-07 12:22:23'),
(11, '3SOSR025001', 'สิงห์ทอง 25 กก.', 'ปุ๋ยกระสอบเล็ก', '', 'กระสอบ', 0.00, 0.00, 0, 1, '2025-08-07 12:21:51', '2025-08-07 12:21:51'),
(12, '1SDSR050001', 'สิงห์ชมพู สูตร 6-3-3 50 กก.', 'ปุ๋ยกระสอบใหญ่', '', 'กระสอบ', 0.00, 0.00, 0, 1, '2025-08-07 12:22:11', '2025-08-07 12:22:11'),
(13, 'FRSDSR050001', 'สิงห์ชมพู สูตร 6-3-3 50 กก. (แถม)', 'ของแถม', '', 'กระสอบ', 0.00, 0.00, 0, 1, '2025-08-07 12:22:59', '2025-08-07 12:22:59'),
(14, 'FR-PN001', 'เสื้อแสนราชสีห์ (พรีออนิค)', 'ของแถม', '', 'ชิ้น', 0.00, 0.00, 0, 1, '2025-08-07 12:23:27', '2025-08-07 12:23:27'),
(15, 'FRSOSR050001', 'แถม สิงห์ส้ม 50 กก. 12-4-4', 'ของแถม', '', 'กระสอบ', 0.00, 0.00, 0, 1, '2025-08-07 12:24:04', '2025-08-07 13:20:28'),
(16, '1SSSR025001', 'ปุ๋ยสารปรับปรุงดิน 25 กก.', 'ปุ๋ยกระสอบเล็ก', '', 'กระสอบ', 0.00, 0.00, 0, 1, '2025-08-07 12:24:46', '2025-08-07 12:24:46'),
(17, 'FRSCSR050002', 'แถม สิงห์เขียว 50 กก. 4-4-12', 'ปุ๋ยกระสอบใหญ่', '', 'กระสอบ', 0.00, 0.00, 0, 1, '2025-08-07 12:25:14', '2025-08-07 13:20:07'),
(18, 'FR-PN002', 'เสื้อแสนราชสีห์ (เทเลเซลล์)', 'ของแถม', '', 'ชิ้น', 0.00, 0.00, 0, 1, '2025-08-07 12:25:35', '2025-08-07 12:25:35'),
(19, 'FRSSSR025001', 'แถม ปุ๋ยสารปรับปรุงดิน 25 กก.', 'ของแถม', '', 'กระสอบ', 0.00, 0.00, 0, 1, '2025-08-07 13:23:33', '2025-08-07 13:23:33'),
(20, 'FRSOSR025001', 'แถม สิงห์ทอง 25 กก.', 'ของแถม', '', 'กระสอบ', 0.00, 0.00, 0, 1, '2025-08-07 13:23:49', '2025-08-07 13:23:49'),
(22, 'LASR01L001', 'อะมิโนเฟรช', 'ชีวภัณฑ์', '', 'ขวด', 0.00, 0.00, 0, 1, '2025-08-07 13:25:56', '2025-08-07 13:25:56'),
(23, 'PTSR01L001', 'จุลินทรีย์ปรับปรุงดินชนิดน้ำ สิงห์พลัส', 'ชีวภัณฑ์', '', 'ขวด', 0.00, 0.00, 0, 1, '2025-08-07 13:26:18', '2025-08-07 13:26:18'),
(24, 'LCSR050001', 'ไคโตซานพลัส ตราเเสนราชสีห์ ขนาด 500 ซีซี', 'ชีวภัณฑ์', '', 'ขวด', 0.00, 0.00, 0, 1, '2025-08-07 13:26:37', '2025-08-07 13:26:37'),
(25, 'FRLCSR050001', 'แถม ไคโตซานพลัส ตราเเสนราชสีห์ ขนาด 500 ซีซี', 'ของแถม', '', 'ขวด', 0.00, 0.00, 0, 1, '2025-08-07 13:27:16', '2025-08-07 13:27:16'),
(26, 'FRPTSR01L001', 'แถม จุลินทรีย์ปรับปรุงดินชนิดน้ำ สิงห์พลัส', 'ของแถม', '', 'ขวด', 0.00, 0.00, 0, 1, '2025-08-07 13:27:38', '2025-08-07 13:27:38'),
(27, 'FRLASR01L001', 'แถม อะมิโนเฟรช', 'ของแถม', '', 'ขวด', 0.00, 0.00, 0, 1, '2025-08-07 13:27:55', '2025-08-07 13:27:55'),
(28, '1SOSR025001', 'สิงห์ทอง 25 กก.', 'ปุ๋ยกระสอบเล็ก', '', 'กระสอบ', 0.00, 0.00, 0, 0, '2025-08-07 13:28:18', '2025-08-07 13:28:46');

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
  `supervisor_id` int(11) DEFAULT NULL COMMENT 'References user_id of supervisor who manages this user',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `role_id`, `company_id`, `supervisor_id`, `is_active`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin@prima49.com', '081-234-5678', 1, 1, NULL, 1, '2025-08-03 07:19:20', '2025-08-08 05:17:21', '2025-08-08 05:17:21'),
(2, 'supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'หัวหน้าทีมขาย', 'supervisor@prima49.com', '081-234-5679', 3, 1, NULL, 1, '2025-08-03 07:19:20', '2025-08-07 08:34:42', '2025-08-07 08:34:42'),
(3, 'telesales1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'พนักงานขาย 1', 'telesales1@prima49.com', '081-234-5680', 4, 1, 2, 1, '2025-08-03 07:19:20', '2025-08-07 14:50:14', '2025-08-07 14:50:14'),
(4, 'telesales2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'พนักงานขาย 2', 'telesales2@prima49.com', '081-234-5681', 4, 1, 2, 1, '2025-08-03 07:19:20', '2025-08-07 05:32:13', NULL),
(5, 'thanu', '$2y$10$6tbVyo5RJdCYGaflQBmnmOkfXlnQx3V8Jg6xaZi2ieaHdfQlLwK76', 'ธนู สุริวงศ์', 'prima.thanu.s@gmail.com', '0952519797', 1, 1, NULL, 1, '2025-08-07 04:25:43', '2025-08-07 04:26:18', '2025-08-07 04:26:18'),
(6, 'gif', '$2y$10$GLCrR7q.uR1seJ1Vm6YcUuMZB9HGi1vSxCUhwzAV9oEY.wedIiIYC', 'กิ๊ฟ Telesale', 'gif-prionic@gmail.com', '-', 4, 2, NULL, 1, '2025-08-07 13:31:30', '2025-08-08 05:08:37', '2025-08-08 05:08:37');

-- --------------------------------------------------------

--
-- Structure for view `customer_appointment_extensions`
--
DROP TABLE IF EXISTS `customer_appointment_extensions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`primacom_bloguser`@`localhost` SQL SECURITY DEFINER VIEW `customer_appointment_extensions`  AS SELECT `c`.`customer_id` AS `customer_id`, concat(`c`.`first_name`,' ',`c`.`last_name`) AS `customer_name`, `c`.`customer_grade` AS `customer_grade`, `c`.`temperature_status` AS `temperature_status`, `c`.`appointment_count` AS `appointment_count`, `c`.`appointment_extension_count` AS `appointment_extension_count`, `c`.`max_appointment_extensions` AS `max_appointment_extensions`, `c`.`appointment_extension_expiry` AS `appointment_extension_expiry`, `c`.`appointment_extension_days` AS `appointment_extension_days`, `c`.`last_appointment_date` AS `last_appointment_date`, CASE WHEN `c`.`appointment_extension_expiry` is null THEN 'ไม่มีวันหมดอายุ' WHEN `c`.`appointment_extension_expiry` < current_timestamp() THEN 'หมดอายุแล้ว' ELSE 'ยังไม่หมดอายุ' END AS `expiry_status`, CASE WHEN `c`.`appointment_extension_count` >= `c`.`max_appointment_extensions` THEN 'ไม่สามารถต่อเวลาได้แล้ว' ELSE 'สามารถต่อเวลาได้' END AS `extension_status`, `u`.`username` AS `assigned_user` FROM (`customers` `c` left join `users` `u` on(`c`.`assigned_to` = `u`.`user_id`)) WHERE `c`.`is_active` = 1 ;

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
  ADD KEY `idx_users_role_id` (`role_id`),
  ADD KEY `idx_supervisor_id` (`supervisor_id`);

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
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `appointment_activities`
--
ALTER TABLE `appointment_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `customer_activities`
--
ALTER TABLE `customer_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=245;

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
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

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
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`),
  ADD CONSTRAINT `users_ibfk_3` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
