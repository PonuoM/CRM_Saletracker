-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 14, 2025 at 07:52 AM
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
  `description` text DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `activity_type`, `table_name`, `record_id`, `action`, `description`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'grade_change', 'customers', 11, 'update', NULL, '{\"customer_grade\":\"B\"}', '{\"customer_grade\":\"A+\"}', NULL, NULL, '2025-08-04 09:07:27'),
(2, NULL, 'temperature_change', 'customers', 12, 'update', NULL, '{\"temperature_status\":\"warm\"}', '{\"temperature_status\":\"frozen\"}', NULL, NULL, '2025-08-04 09:07:27');

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
-- Table structure for table `call_followup_queue`
--

CREATE TABLE `call_followup_queue` (
  `queue_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `call_log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ผู้ที่ต้องติดตาม',
  `followup_date` date NOT NULL COMMENT 'วันที่ต้องติดตาม',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `call_followup_rules`
--

CREATE TABLE `call_followup_rules` (
  `rule_id` int(11) NOT NULL,
  `call_result` enum('interested','not_interested','callback','order','complaint') NOT NULL,
  `followup_days` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนวันที่ต้องติดตามกลับ',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `call_followup_rules`
--

INSERT INTO `call_followup_rules` (`rule_id`, `call_result`, `followup_days`, `priority`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'not_interested', 30, 'low', 1, '2025-08-08 14:14:23', '2025-08-08 14:14:23'),
(2, 'callback', 3, 'high', 1, '2025-08-08 14:14:23', '2025-08-08 14:14:23'),
(3, 'interested', 7, 'medium', 1, '2025-08-08 14:14:23', '2025-08-08 14:14:23'),
(4, 'complaint', 1, 'urgent', 1, '2025-08-08 14:14:23', '2025-08-08 14:14:23'),
(5, 'order', 0, 'low', 1, '2025-08-08 14:14:23', '2025-08-08 14:14:23');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `followup_notes` text DEFAULT NULL,
  `followup_days` int(11) DEFAULT 0,
  `followup_priority` enum('low','medium','high','urgent') DEFAULT 'medium' COMMENT 'ความสำคัญของการติดตาม'
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
  `recall_reason` varchar(100) DEFAULT NULL,
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
  `customer_status` enum('new','existing','followup','call_followup') DEFAULT 'new',
  `customer_time_extension` int(11) DEFAULT 0 COMMENT 'จำนวนวันที่ต่อเวลาแล้ว',
  `customer_time_base` timestamp NULL DEFAULT NULL COMMENT 'วันเริ่มต้นการดูแลลูกค้า',
  `customer_time_expiry` timestamp NULL DEFAULT NULL COMMENT 'วันหมดอายุการดูแลลูกค้า'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_code`, `first_name`, `last_name`, `phone`, `email`, `address`, `district`, `province`, `postal_code`, `temperature_status`, `customer_grade`, `total_purchase_amount`, `assigned_to`, `basket_type`, `assigned_at`, `last_contact_at`, `next_followup_at`, `recall_at`, `recall_reason`, `source`, `notes`, `is_active`, `created_at`, `updated_at`, `appointment_count`, `appointment_extension_count`, `last_appointment_date`, `appointment_extension_expiry`, `max_appointment_extensions`, `appointment_extension_days`, `customer_status`, `customer_time_extension`, `customer_time_base`, `customer_time_expiry`) VALUES
(3207, 'CUS980954755', 'สุวรรณตรี', 'พรหมทอง', '980954755', '', '35 ม.1 ต.เขาไพร อ.รัษฎา จ.ตรัง 92160', '', 'ตรัง', '92160', 'hot', 'A', 10900.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3208, 'CUS616424159', 'นายอภิรักษ์', '', '616424159', '', 'ตลาดพระระยองร้านช่างต๊ะ ต.ท่าประดู่ อ.เมืองระยอง จ.ระยอง 21000', '', 'ระยอง', '21000', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3209, 'CUS655288948', 'พะเยาว์', 'เกิดมี', '655288948', '', '(สามแยกศูนย์สร้างทางลำปาง) 90 หมู่ 1 ต.ดอนไฟ อ.แม่ทะ จ.ลำปาง 52150', '', 'ลำปาง', '52150', 'hot', 'C', 2025.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3210, 'CUS824437075', 'ชนะโชค', 'ขาวคล้ายเงิน', '824437075', '', 'เลขที่ 70/1 หมู่ที่ 2 ต.บ้านเกาะ อ.เมืองสมุทรสาคร จ.สมุทรสาคร 74000', '', 'สมุทรสาคร', '74000', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3211, 'CUS869653844', 'สะอาด', 'รัตนวงศ์', '869653844', '', '292 ม.6 ถ.ปุณณกัณฑ์ ต.ทุ่งใหญ่ อ.หาดใหญ่ จ.สงขลา 90110', '', 'สงขลา', '90110', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3212, 'CUS814726849', 'สุรชาติ', 'เขียวพลายเวช', '814726849', '', '34 ม.12 ต.พะโต๊ะ อ.พะโต๊ะ จ.ชุมพร 86180', '', 'ชุมพร', '86180', 'hot', 'C', 3495.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3213, 'CUS806727785', 'เสวี', 'ยอดออน', '806727785', '', '151 หมู่ที่ 4 ต.เจดีย์ชัย อ.ปัว จ.น่าน 55120', '', 'น่าน', '55120', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3214, 'CUS847500938', 'รานีย์', 'มะยาแมง', '847500938', '', '2/1 ม.5 ต.จอเบาะ อ.ยี่งอ จ.นราธิวาส 96180', '', 'นราธิวาส', '96180', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3215, 'CUS842368987', 'กมลชนก', 'ง่วนหอม', '842368987', '', '\"ร้านเสริมสวย พอใจบิวตี้\" 1/3 หมู่ 4 ต.เบิกไพร อ.บ้านโป่ง จ.ราชบุรี 70110', '', 'ราชบุรี', '70110', 'hot', 'D', 445.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3216, 'CUS807002716', 'สุธา', 'สว่างภพ', '807002716', '', '91/1 ม.2 ต.กำโลน อ.ลานสกา จ.นครศรีธรรมราช 80230', '', 'นครศรีธรรมราช', '80230', 'hot', 'C', 3495.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3217, 'CUS657417766', 'อรสา', 'วันหลัง', '657417766', '', '16 หมู่ 4 บ้านป่าหวาย ต.ป่าโมง อ.เดชอุดม จ.อุบลราชธานี 34160', '', 'อุบลราชธานี', '34160', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3218, 'CUS935481776', 'อัมรัน', 'เบญญามินทร์', '935481776', '', '54/2 หมู่ที่2 ต.หน้าถ้ำ อ.เมืองยะลา จ.ยะลา 95000', '', 'ยะลา', '95000', 'hot', 'D', 445.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3219, 'CUS822669376', 'วีระพงษ์', 'กวดกิจการ', '822669376', '', 'จุดตรวจร่วม กม.23 ต.ตาเนาะแมเราะ อ.เบตง จ.ยะลา 95110', '', 'ยะลา', '95110', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3220, 'CUS952153160', 'วไลพร', 'จักกาวิละ', '952153160', '', '296/11 บ้านรั้วต้นไม้ท้ายซอยริมคลองคู่ขนาครัวดาดา หมู่6 ต.สระแก้ว อ.เมืองกำแพงเพชร จ.กำแพงเพชร 62000', '', 'กำแพงเพชร', '62000', 'hot', 'D', 445.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3221, 'CUS979692469', 'อัมพร', 'ชีปู', '979692469', '', '(บ้านมีไก่) 181/4 หมู่10 บ้านโนนม่วง ต.นครชุม อ.เมืองกำแพงเพชร จ.กำแพงเพชร 62000', '', 'กำแพงเพชร', '62000', 'hot', 'D', 445.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3222, 'CUS656647645', 'ชลธิชา', 'พนะสัน', '656647645', '', '22 ม.1 ต.ภูคา อ.ปัว จ.น่าน 55120', '', 'น่าน', '55120', 'hot', 'C', 2025.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3223, 'CUS823659391', 'ประชา', 'สุขนิพิฐพงษ์', '823659391', '', '154 ถ.สระแก้ว ต.พระประโทน อ.เมืองนครปฐม จ.นครปฐม 73000', '', 'นครปฐม', '73000', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3224, 'CUS842356728', 'สมบัติ', 'โชติมงคล', '842356728', '', '353 ม.1 ต.น้ำเป็น อ.เขาชะเมา จ.ระยอง 21110', '', 'ระยอง', '21110', 'hot', 'D', 1975.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3225, 'CUS812512133', 'ปรีชา', 'ธะนะถุง', '812512133', '', '( 0882514439 ) 130 หมู่3 บ้านน้ำปั้ว ต.น้ำปั้ว อ.เวียงสา จ.น่าน 55110', '', 'น่าน', '55110', 'hot', 'A', 16400.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3226, 'CUS846446654', 'เอกรินทร์', 'มิตรเรืองศิลป์', '846446654', '', '52/1 ซอยรามคำแหง 142/1 ถนนรามคำแหง ต.สะพานสูง อ.สะพานสูง จ.กรุงเทพมหานคร 10240', '', 'กรุงเทพมหานคร', '10240', 'hot', 'D', 445.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3227, 'CUS933135490', 'รัชฏา​ภร​', 'สังวร​ลิ', '933135490', '', '73​ ม​.13​ บ้าน​ทรัพย์​น้อย​ ต.เขาชนกัน อ.แม่วงก์ จ.นครสวรรค์ 60150', '', 'นครสวรรค์', '60150', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3228, 'CUS990029252', 'จงกล​', 'บุญ​เกตุ', '990029252', '', '8​ หมู่​ 6 ต.คลองนารายณ์ อ.เมืองจันทบุรี จ.จันทบุรี 22000', '', 'จันทบุรี', '22000', 'hot', 'C', 2200.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3229, 'CUS861339894', 'คุณทักษิณ', 'เดชะ', '861339894', '', 'บริษัท อิโตเซอิโค ประเทศไทยจำกัด 700/898 ม.3 ต.หนองกะขะ อ.พานทอง จ.ชลบุรี 20160', '', 'ชลบุรี', '20160', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3230, 'CUS923483620', 'จันทร์แรม', 'รื่นรมย์', '923483620', '', '123 ม.3 ต.พวา อ.แก่งหางแมว จ.จันทบุรี 22160', '', 'จันทบุรี', '22160', 'hot', 'A', 21400.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3231, 'CUS615876891', 'เเอ๊ด', 'โพธิ์พ่วง', '615876891', '', '2/4 ม.1 ต.สามพี่น้อง อ.แก่งหางแมว จ.จันทบุรี 22160', '', 'จันทบุรี', '22160', 'hot', 'D', 445.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3232, 'CUS854859324', 'ผู้ใหญ่วิษณุ', 'วงศ์ตรุษ', '854859324', '', '98/6 หมู่10 ต.กระแสบน อ.แกลง จ.ระยอง 21110', '', 'ระยอง', '21110', 'hot', 'A', 21800.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3233, 'CUS941529165', 'Issaree', 'Patkam', '941529165', '', '146 หมู่2 ต.จุมจัง อ.กุฉินารายณ์ จ.กาฬสินธุ์ 46110', '', 'กาฬสินธุ์', '46110', 'hot', 'D', 870.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3234, 'CUS814303954', 'กิ็ฟ', 'มังกรน้อย', '814303954', '', '82 ม.11 ต.บางพระ อ.ศรีราชา จ.ชลบุรี 20110', '', 'ชลบุรี', '20110', 'hot', 'D', 445.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3235, 'CUS878682524', 'อําภา', 'สิงห์จันทร์', '878682524', '', '14 ม.4 ต.ท่าตูม อ.ท่าตูม จ.สุรินทร์ 32120', '', 'สุรินทร์', '32120', 'hot', 'D', 445.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3236, 'CUS852965925', 'คุณ', 'ภัทรเดช', '852965925', '', '29 ม.5 ต.หินดาด อ.ทองผาภูมิ จ.กาญจนบุรี 71180', '', 'กาญจนบุรี', '71180', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3237, 'CUS947243335', 'สุพร', 'ปาลี', '947243335', '', '39 หมู่ 8 บ้านห้วยไซ ต.ห้วยยาบ อ.บ้านธิ จ.ลำพูน 51180', '', 'ลำพูน', '51180', 'hot', 'D', 1145.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3238, 'CUS898204199', 'คุณ', 'ภาวัฒ จันทร์ศาต', '898204199', '', '86 ม.3 ต.โพนทอง อ.บ้านหมี่ จ.ลพบุรี 15110', '', 'ลพบุรี', '15110', 'hot', 'D', 1145.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3239, 'CUS987121132', 'อานีตา', 'อาเกะ', '987121132', '936012055', '25 ม.4 ต.ประจัน อ.ยะรัง จ.ปัตตานี 94160', '', 'ปัตตานี', '94160', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3240, 'CUS989646162', 'ณิชนันท์', 'คำพุด', '989646162', '', '109 หมู่17 ต.บ้านพระ อ.เมืองปราจีนบุรี จ.ปราจีนบุรี 25230', '', 'ปราจีนบุรี', '25230', 'hot', 'D', 445.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3241, 'CUS897552714', 'วินัย', 'สุภิมล', '897552714', '', '16 หมู่ 5 ต.โคกสว่าง อ.เมืองสระบุรี จ.สระบุรี 18000', '', 'สระบุรี', '18000', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3242, 'CUS926517988', 'ปรียาภรณ์', 'อินต๊ะ', '926517988', '', '32 หมู่2 ต.ผาสิงห์ อ.เมืองน่าน จ.น่าน 55000', '', 'น่าน', '55000', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3243, 'CUS935704540', 'จงจิตสี', 'นุรัก', '935704540', '', '(บ้านสวน) 73 หมู่6 บ้านสามแยกขันอาสา ต.คูสะคาม อ.วานรนิวาส จ.สกลนคร 47120', '', 'สกลนคร', '47120', 'hot', 'D', 445.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3244, 'CUS850260117', 'นายศักดิ์สิทธิ์', 'ทรงชนม์', '850260117', '', '29 ม. 7 ต.บ้านโคก อ.โคกโพธิ์ไชย จ.ขอนแก่น 40160', '', 'ขอนแก่น', '40160', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3245, 'CUS994076908', 'เปรมฤทัย', 'เจริญทรัพย์', '994076908', '', '48 ม.14 บ้านเด่นไม้ชุง ต.เวียงมอก อ.เถิน จ.ลำปาง 52160', '', 'ลำปาง', '52160', 'hot', 'D', 445.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3246, 'CUS889741979', 'วิสุทธิ์', 'เขาทอง', '889741979', '', '167/1 ม.16 บ้านยางเจริญ ต.ผาสุก อ.วังสามหมอ จ.อุดรธานี 41280', '', 'อุดรธานี', '41280', 'hot', 'D', 0.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3247, 'CUS817224352', 'สมวรรณ', 'อุไรรัตน์', '817224352', '', '214/6 หมู่ 4 คลอง 6 ถนนรังสิต นครนายก ต.รังสิต อ.ธัญบุรี จ.ปทุมธานี 12110', '', 'ปทุมธานี', '12110', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:18', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:18', '2025-11-12 05:57:18'),
(3248, 'CUS861062163', 'ทวี', 'ศรีวงศ์สุข', '861062163', '', '35 หมู่ที่ 2 ต.บ้านพริก อ.บ้านนา จ.นครนายก 26110', '', 'นครนายก', '26110', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3249, 'CUS899111559', 'ชัญญานุช', 'ยิ้มรอด', '899111559', '', '38/212 ร้านกอล์ฟ ซ.68โรงเหล็ก ต.หัวหิน อ.หัวหิน จ.ประจวบคีรีขันธ์ 77110', '', 'ประจวบคีรีขันธ์', '77110', 'hot', 'B', 5050.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3250, 'CUS652593962', 'ณิชาภัทร', 'ศรีสมบุญ', '652593962', '', '10/1ม.9 ซ.ก๋งหลี ต.บางช้าง อ.สามพราน จ.นครปฐม 73110', '', 'นครปฐม', '73110', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3251, 'CUS635942866', 'อาอีเส๊าะ', 'ลานง', '635942866', '', '302 ม.5 ต.กาลิซา อ.ระแงะ จ.นราธิวาส 96130', '', 'นราธิวาส', '96130', 'hot', 'D', 1610.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3252, 'CUS843710704', 'ทวีป', 'นันต๊ะสี', '843710704', '', '99 หมู่ที่ 1 ต.ต้นธงชัย อ.เมืองลำปาง จ.ลำปาง 52000', '', 'ลำปาง', '52000', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3253, 'CUS634493290', 'ไกวัล', 'กล้วยวิเชียร', '634493290', '', '59/202 ม.7 ต.ราไวย์ อ.เมืองภูเก็ต จ.ภูเก็ต 83130', '', 'ภูเก็ต', '83130', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3254, 'CUS863066251', 'สุพิน', 'วันชเอม', '863066251', '', '71 ม.7 ต.นางแก้ว อ.โพธาราม จ.ราชบุรี 70120', '', 'ราชบุรี', '70120', 'hot', 'B', 5475.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3255, 'CUS816021409', 'ณัฐพงศ์', 'ติ๊บจ๊ะ', '816021409', '', '111/1 ม.8 ต.โชคชัย อ.ดอยหลวง จ.เชียงราย 57110', '', 'เชียงราย', '57110', 'hot', 'D', 0.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3256, 'CUS868039374', 'บัวทอง', 'พรมนาม', '868039374', '', '11/2 ม.6 ต.ดอนใหญ่ อ.บางแพ จ.ราชบุรี 70160', '', 'ราชบุรี', '70160', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3257, 'CUS819477292', 'บุญเรือน', 'สารพันโชติวิทยา', '819477292', '', '84 ม.2 ต.หนองดินแดง อ.เมืองนครปฐม จ.นครปฐม 73000', '', 'นครปฐม', '73000', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3258, 'CUS905716633', 'ธีรเดช', 'จันทร์อ่อน', '905716633', '', 'วัดมหาธาตุยุวราชรังสฤษฏ์ คณะ 18 ต.พระบรมมหาราชวัง อ.พระนคร จ.กรุงเทพมหานคร 10200', '', 'กรุงเทพมหานคร', '10200', 'hot', 'C', 2025.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3259, 'CUS839584499', 'สาคร', 'คำคง', '839584499', '', '92 หมู่ 2 ต.พลงตาเอี่ยม อ.วังจันทร์ จ.ระยอง 21210', '', 'ระยอง', '21210', 'hot', 'C', 3495.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3260, 'CUS970015453', 'กฤษณา', 'ศรมยุรา', '970015453', '', '32/30 หมู่บ้านกาสลอง ม.4 ต.ดอนตะโก อ.เมืองราชบุรี จ.ราชบุรี 70000', '', 'ราชบุรี', '70000', 'hot', 'C', 3100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3261, 'CUS841113155', 'จิระนันท์', 'พิริยะพงศ์พิพัฒน์', '841113155', '', 'บริษัท วัฒนาวาณิชย์ จำกัด 184 หมู่ 4 ซอยศรีพิทักษ์1 ต.ขามใหญ่ อ.เมืองอุบลราชธานี จ.อุบลราชธานี 34000', '', 'อุบลราชธานี', '34000', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3262, 'CUS870731656', 'วินิจ', 'น้อยเจริญ', '870731656', '', '5 ซ.หลังเกษตร ต.ห้วยโป่ง อ.เมืองระยอง จ.ระยอง 21150', '', 'ระยอง', '21150', 'hot', 'B', 5340.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3263, 'CUS820238791', 'ประทุม', 'ทิพย์ยอแล๊ะ', '820238791', '', 'ร้านน้ำปั่นหน้าโรงเรียนเทศบาล 9 บ้านสามพระยา ต.ชะอำ อ.ชะอำ จ.เพชรบุรี 76120', '', 'เพชรบุรี', '76120', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3264, 'CUS639463664', 'คุณสุรดา', 'ชัยชนะมงคล', '639463664', '', '18/41 หมู่3 หมู่บ้านมายพราด์ ซ.2/2 ต.นามะตูม อ.พนัสนิคม จ.ชลบุรี 20140', '', 'ชลบุรี', '20140', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3265, 'CUS982464298', 'นงนุช', 'แท่นนิล', '982464298', '', '61/1 หมู่5 ต.หลักสาม อ.บ้านแพ้ว จ.สมุทรสาคร 74120', '', 'สมุทรสาคร', '74120', 'hot', 'C', 3700.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3266, 'CUS654896419', 'สายรุ้ง', 'ชินวงค์', '654896419', '', '89 ม.3 บ.กมลศิลป์ ต.บ้านม่วง อ.บ้านดุง จ.อุดรธานี 41190', '', 'อุดรธานี', '41190', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3267, 'CUS626249326', 'ณัฐกฤษฏ์', 'ธนสรรค์พงศ์', '626249326', '', '152 หมู่ 1 บ้านสันขี้เหล็ก ต.แม่นาเรือ อ.เมืองพะเยา จ.พะเยา 56000', '', 'พะเยา', '56000', 'hot', 'C', 2200.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3268, 'CUS621636501', 'สังวาล', 'สาลีเกิด', '621636501', '', '187 ม.5 ต.ห้วยเฮี้ย อ.นครไทย จ.พิษณุโลก 65120', '', 'พิษณุโลก', '65120', 'hot', 'B', 5575.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3269, 'CUS863033254', 'โอ๋', 'ใยสำลี', '863033254', '', '143 ม.3 ซ.เทวา 3(ติดถนนฝั่งคลอง) ถ.เทพารักษ์ ต.เทพารักษ์ อ.เมืองสมุทรปราการ จ.สมุทรปราการ 10270', '', 'สมุทรปราการ', '10270', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3270, 'CUS802110005', 'วสันต์', 'อังสิทธากุล', '802110005', '', '44 หมู่6 บ้านคลองทุเรียน ต.วังน้ำเขียว อ.วังน้ำเขียว จ.นครราชสีมา 30370', '', 'นครราชสีมา', '30370', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3271, 'CUS872868385', 'สรานันท์', 'บุญชู', '872868385', '', '66/1 ม.5 ซ.เจริญพรร่วมมิตร ทางเข้าร.ร.บ้านคลองหลวง ถ.สวนส้มเนรมิตร ต.อำแพง อ.บ้านแพ้ว จ.สมุทรสาคร 74120', '', 'สมุทรสาคร', '74120', 'hot', 'D', 435.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3272, 'CUS945962974', 'นายประกิจ', 'ทองรักษ์', '945962974', '', '250 ม.11 ต.หารเทา อ.ปากพะยูน จ.พัทลุง 93120', '', 'พัทลุง', '93120', 'hot', 'A', 13135.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3273, 'CUS831030700', 'สง่า', 'ผุดวัฒ', '831030700', '', '106/2 หมู่5 ต.สระแก้ว อ.ท่าศาลา จ.นครศรีธรรมราช 80160', '', 'นครศรีธรรมราช', '80160', 'hot', 'D', 890.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3274, 'CUS869451744', 'นัยนา', 'แก้วแท้', '869451744', '', '32/4 ม.7 ต.ที่วัง อ.ทุ่งสง จ.นครศรีธรรมราช 80110', '', 'นครศรีธรรมราช', '80110', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3275, 'CUS849983648', 'นูรฮาซีเกน', 'อารงค์', '849983648', '', '7 ม.1 ต.กะลุวอเหนือ อ.เมืองนราธิวาส จ.นราธิวาส 96000', '', 'นราธิวาส', '96000', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3276, 'CUS810278271', 'จงรักษ์', 'โอ๊ะเรือนแก้ว', '810278271', '', '78 ม.9 บ.ตีนธาตุ ต.ทุ่งยาว อ.ปาย จ.แม่ฮ่องสอน 58130', '', 'แม่ฮ่องสอน', '58130', 'hot', 'B', 5475.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3277, 'CUS814373284', 'สายชล', 'เขียวสอาด', '814373284', '', '316/6 ซ.ศรีมงคล ต.บางปลาสร้อย อ.เมืองชลบุรี จ.ชลบุรี 20000', '', 'ชลบุรี', '20000', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3278, 'CUS819791652', 'วิเชียร', 'รุ่งโรจน์', '819791652', '819445256', '(0819445256) 246 ม.9 ถ.โยธาธิการ ต.ท่าไม้รวก อ.ท่ายาง จ.เพชรบุรี 76130', '', 'เพชรบุรี', '76130', 'hot', 'B', 7405.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3279, 'CUS972058374', 'สายหยุด', 'จันทร', '972058374', '870702978', 'บ้านเลขที่18 หมู่14 ต.หนองจอก อ.บ้านไร่ จ.อุทัยธานี 61180', '', 'อุทัยธานี', '61180', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3280, 'CUS988282545', 'กร', 'ภู่คง', '988282545', '', '40 ม.7 บ้านหนองวัวดำ ต.โกสัมพี อ.โกสัมพีนคร จ.กำแพงเพชร 62000', '', 'กำแพงเพชร', '62000', 'hot', 'D', 1145.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3281, 'CUS816858569', 'พ.ต.ท.อภิชาติ', 'นาคสุข', '816858569', '', '10 หมู่ 2 ต.บางครก อ.บ้านแหลม จ.เพชรบุรี 76110', '', 'เพชรบุรี', '76110', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3282, 'CUS862554696', 'สุชญา', '', '862554696', '', 'ร้านอัครบุตร (สุชญา) 202 หมู่1 ต.หนองบัวศาลา อ.เมืองนครราชสีมา จ.นครราชสีมา 30000', '', 'นครราชสีมา', '30000', 'hot', 'D', 1890.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3283, 'CUS930174413', 'กรรณิกา', 'แสงสว่าง', '930174413', '', '211 หมู่3 บ้านตาลกุด ต.โพนแพง อ.ธาตุพนม จ.นครพนม 48110', '', 'นครพนม', '48110', 'hot', 'D', 1145.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3284, 'CUS645709240', 'พร​ศรี​', '​​ พัน​เทศ', '645709240', '', '495/147 หมู่4 ต.บ้านเลื่อม อ.เมืองอุดรธานี จ.อุดรธานี 41000', '', 'อุดรธานี', '41000', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3285, 'CUS618236198', 'กุหลาบ', 'ขุนไชย', '618236198', '632051886', '( 0632051886 ) 33 หมู่6 ต.ทุ่งหลวง อ.ปากท่อ จ.ราชบุรี 70140', '', 'ราชบุรี', '70140', 'hot', 'D', 1145.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3286, 'CUS816933116', 'วิเชียร', 'ชูกมล', '816933116', '', '63/1 ม.2 ต.ชุมพล อ.สทิงพระ จ.สงขลา 90190', '', 'สงขลา', '90190', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3287, 'CUS983371682', 'วิเวียน', 'สังเวียน', '983371682', '', '11/1 หมู่ 2 ต.หนองแก้ว อ.ประจันตคาม จ.ปราจีนบุรี 25130', '', 'ปราจีนบุรี', '25130', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3288, 'CUS924292661', 'ศรันย์​รัชต์​', 'พิศาล​วิริยะ​กุล', '924292661', '', '120/75 หมู่บ้าน​ศรี​เจริญ​วิลล่า​ ถ.​เทพารักษ์​60 ซ.ศรี​เจริญ​30 ต.บางเมือง อ.เมืองสมุทรปราการ จ.สมุทรปราการ 10270', '', 'สมุทรปราการ', '10270', 'hot', 'D', 1145.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3289, 'CUS897286883', 'คุณบุญเรือน', 'ภูมิมาตร', '897286883', '', '22/9 หมู่9 ต.โคกกลอย อ.ตะกั่วทุ่ง จ.พังงา 82140', '', 'พังงา', '82140', 'hot', 'A', 16350.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3290, 'CUS657379949', 'ปะวินา', 'ดำสงวน', '657379949', '', '81 หมู่3 ต.เมืองแฝก อ.ลำปลายมาศ จ.บุรีรัมย์ 31130', '', 'บุรีรัมย์', '31130', 'hot', 'A', 10900.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3291, 'CUS928791595', 'เฉลิมชัย', 'ยี่สาร', '928791595', '', '444/15 ม.1 ต.ท่ายาง อ.ท่ายาง จ.เพชรบุรี 76130', '', 'เพชรบุรี', '76130', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3292, 'CUS873215855', 'นพดล', 'โป๊ฟ้า', '873215855', '', '156/3 หมู่ 3 ต.แหลมฟ้าผ่า อ.พระสมุทรเจดีย์ จ.สมุทรปราการ 10290', '', 'สมุทรปราการ', '10290', 'hot', 'D', 1580.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3293, 'CUS899733182', 'สุเมตตา', 'แก้วสวัสดิ์', '899733182', '', '9/14 ม.6 ต.บ่อผุด อ.เกาะสมุย จ.สุราษฎร์ธานี 84320', '', 'สุราษฎร์ธานี', '84320', 'hot', 'A+', 100800.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3294, 'CUS991592389', 'ระเบียบ', 'ละมุด', '991592389', '', '282 ม.1 ต.พวา อ.แก่งหางแมว จ.จันทบุรี 22160', '', 'จันทบุรี', '22160', 'hot', 'C', 2745.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3295, 'CUS899729707', 'วัชรินทร์', 'คชเชนทร์', '899729707', '', '82 ม.2 ต.พ่วงพรมคร อ.เคียนซา จ.สุราษฎร์ธานี 84210', '', 'สุราษฎร์ธานี', '84210', 'hot', 'A', 14800.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3296, 'CUS612437872', 'พรเสริม', 'อินธิแสน', '612437872', '', 'ฟาร์มกบ บ.ไทรงาม 249 หมู่10 ต.นิคมห้วยผึ้ง อ.ห้วยผึ้ง จ.กาฬสินธุ์ 46240', '', 'กาฬสินธุ์', '46240', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3297, 'CUS817464145', 'ชนพัฒย์', 'ตันธิเน', '817464145', '', '131 หมู่10 ต.บ้านแหวน อ.หางดง จ.เชียงใหม่ 50230', '', 'เชียงใหม่', '50230', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3298, 'CUS816648469', 'นายกอบ', 'นพแก้ว', '816648469', '', '11/2 หมู่ 1 ซอย หัชฬีวิลล์ ถนน ท่าเรือจ้าง ต.วังกระแจะ อ.เมืองตราด จ.ตราด 23000', '', 'ตราด', '23000', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3299, 'CUS903569318', 'สุมลรัตน์', 'ไชคลัง', '903569318', '818981132', '60 หมู่8 ต.คันธารราษฎร์ อ.กันทรวิชัย จ.มหาสารคาม 44150', '', 'มหาสารคาม', '44150', 'hot', 'A', 10500.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3300, 'CUS822579928', 'เทพ', 'โพธิ์เจริญ', '822579928', '', '8/2 ม.7 บ.เกาะตะเคียน ต.เกาะขวาง อ.เมืองจันทบุรี จ.จันทบุรี 22000', '', 'จันทบุรี', '22000', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3301, 'CUS650647314', 'มยุรี', 'วงศ์ณศรี', '650647314', '', '129/2 ม.3 ต.กรุงหยัน อ.ทุ่งใหญ่ จ.นครศรีธรรมราช 80240', '', 'นครศรีธรรมราช', '80240', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3302, 'CUS918809745', 'แม่ลำดวน', 'มาตรมณีวงศ์', '918809745', '', '230 หมู่7 บ้านถ่อน ต.บ้านถ่อน อ.ท่าบ่อ จ.หนองคาย 43110', '', 'หนองคาย', '43110', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3303, 'CUS913051033', 'คิด', 'ชายบุญยวัจน์', '913051033', '', '349 ม.1 ต.หมอกจำแป่ อ.เมืองแม่ฮ่องสอน จ.แม่ฮ่องสอน 58000', '', 'แม่ฮ่องสอน', '58000', 'hot', 'C', 2200.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3304, 'CUS805666681', 'โปโล', 'พีรวศินสกุล', '805666681', '', '379 ม.4 ต.แม่ระมาด อ.แม่ระมาด จ.ตาก 63140', '', 'ตาก', '63140', 'hot', 'C', 2310.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3305, 'CUS877781710', 'หนูเพชร', 'รุนแรง', '877781710', '', 'บ้านเลขที่ 1 หมู่ 7 ต.อ่างศิลา อ.พิบูลมังสาหาร จ.อุบลราชธานี 34110', '', 'อุบลราชธานี', '34110', 'hot', 'D', 1145.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3306, 'CUS842219149', 'อภิกษณา', 'ศรีสวัสดิ์', '842219149', '', 'บ้านไร่อมรพงษ์167 หมู่ 3 สถานที่ใกล้เคียงสถานปฏิบัติธรรมปรียานันท์ ( เขาลัง ) ต.พยุหะ อ.พยุหะคีรี จ.นครสวรรค์ 60130', '', 'นครสวรรค์', '60130', 'hot', 'C', 2200.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3307, 'CUS826717921', 'สุทิน', 'สุขไสยาสน์', '826717921', '', '143 ม.1 สนง.อบต.ถนนหัก ต.ถนนหัก อ.นางรอง จ.บุรีรัมย์ 31110', '', 'บุรีรัมย์', '31110', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3308, 'CUS897540930', 'วรรณพร', 'สุขสมภาพ', '897540930', '', '9/208 ม.6 ต.บ้านฉาง อ.บ้านฉาง จ.ระยอง 21130', '', 'ระยอง', '21130', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3309, 'CUS650148175', 'ปวเรศ', 'ช่วยนุกุล', '650148175', '', '82 ม.1 (ป่าไผ่) ต.โคกสว่าง อ.หนองกี่ จ.บุรีรัมย์ 31210', '', 'บุรีรัมย์', '31210', 'hot', 'D', 1955.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3310, 'CUS816494326', 'สุดา', 'มิตรกุล', '816494326', '', '129 หมู่ 11 ต.ปากช่อง อ.จอมบึง จ.ราชบุรี 70150', '', 'ราชบุรี', '70150', 'hot', 'D', 1145.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3311, 'CUS968895209', 'สุปรียา', 'ชาญชัย', '968895209', '', '21 หมู่9 บ้านอำปึล ต.เทนมีย์ อ.เมืองสุรินทร์ จ.สุรินทร์ 32000', '', 'สุรินทร์', '32000', 'hot', 'D', 1145.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3312, 'CUS872472490', 'วัน', 'ชุ่มเสนา', '872472490', '', '1 หมู่16 ต.ปะเคียบ อ.คูเมือง จ.บุรีรัมย์ 31190', '', 'บุรีรัมย์', '31190', 'hot', 'D', 1145.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3313, 'CUS841114566', 'อนุชา', 'โชติสุข', '841114566', '', 'บ.scg รูฟฟิ่ง 9/3 ม.5 ต.หนองปลาหมอ อ.หนองแค จ.สระบุรี 18140', '', 'สระบุรี', '18140', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3314, 'CUS868134180', 'แม่ชีฉลวยพร', 'พรหมพันธุ์', '868134180', '', 'สำนักวิปัสสนา พุทธะจันทาราม ต.หินซ้อน อ.แก่งคอย จ.สระบุรี 18110', '', 'สระบุรี', '18110', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3315, 'CUS618295678', 'ณัฐ​ธ​ยา​น์​', 'อาริยะไตรสิน', '618295678', '', '69​ หมู่4​ ต.บางแค อ.อัมพวา จ.สมุทรสงคราม 75110', '', 'สมุทรสงคราม', '75110', 'hot', 'D', 1955.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3316, 'CUS811875253', 'สุุระนะ', 'ไพจิตรกาญจนา', '811875253', '', '124 ม.2 ต.หงาว อ.เมืองระนอง จ.ระนอง 85000', '', 'ระนอง', '85000', 'hot', 'B', 5575.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3317, 'CUS993190905', 'สุภาวดี', 'บุญจิตร์', '993190905', '', '50/1 ม.5 ต.ทุ่งรัง อ.กาญจนดิษฐ์ จ.สุราษฎร์ธานี 84290', '', 'สุราษฎร์ธานี', '84290', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3318, 'CUS871855745', 'กวีวรรณ', 'เหลืองศิริเธียร', '871855745', '', 'สนง.เทศบาลเมืองเพชรบูรณ์ 26 ถ.เกษมราษฏร์ ต.ในเมือง อ.เมืองเพชรบูรณ์ จ.เพชรบูรณ์ 67000', '', 'เพชรบูรณ์', '67000', 'hot', 'D', 1935.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3319, 'CUS859202710', 'กมลกร', 'สิมศิริ', '859202710', '', '89 หมู่1 ต.นาประดู่ อ.โคกโพธิ์ จ.ปัตตานี 94180', '', 'ปัตตานี', '94180', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3320, 'CUS613464147', 'กรุณา', 'มีกรณ์', '613464147', '', '256 หมู่ที่1 ต.เขาต่อ อ.ปลายพระยา จ.กระบี่ 81160', '', 'กระบี่', '81160', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3321, 'CUS899701342', 'วิลาวัลย์', 'เกตุแก้ว', '899701342', '', '464/8 ม.1 ต.ปากพูน อ.เมืองนครศรีธรรมราช จ.นครศรีธรรมราช 80000', '', 'นครศรีธรรมราช', '80000', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3322, 'CUS959700934', 'สุธรรม', 'วัดป่าคลองลึก', '959700934', '', '111 ม.2 ต.ปางตาไว อ.ปางศิลาทอง จ.กำแพงเพชร 62120', '', 'กำแพงเพชร', '62120', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3323, 'CUS615500050', 'ยุทธนา', 'แต่งตั้ง', '615500050', '', '44 หมู่8 ต.โคกเจริญ อ.โคกเจริญ จ.ลพบุรี 15250', '', 'ลพบุรี', '15250', 'hot', 'B', 5250.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3324, 'CUS904787130', 'หมูด​หมัด​เ​ร๊า​ะ​', '', '904787130', '', '​141 ม.​2 ต.ลำไพล อ.เทพา จ.สงขลา 90260', '', 'สงขลา', '90260', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3325, 'CUS856504018', 'สุภาพร', 'เสขุนทด', '856504018', '', '(P&N Melon Farm) 124 ม.5 ต.สระจรเข้ อ.ด่านขุนทด จ.นครราชสีมา 30210', '', 'นครราชสีมา', '30210', 'hot', 'D', 0.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3326, 'CUS954289554', 'ธนสร', 'กอวรพันธุ์', '954289554', '', '2 ซอยพระราม2 ซอย 59 ต.แสมดำ อ.บางขุนเทียน จ.กรุงเทพมหานคร 10150', '', 'กรุงเทพมหานคร', '10150', 'hot', 'B', 7400.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3327, 'CUS896943845', 'สมคิด', 'ธำรงศักดิ์', '896943845', '', '76/4 ม.7 (สวนศรีชมภู) ต.ไม้เค็ด อ.เมืองปราจีนบุรี จ.ปราจีนบุรี 25230', '', 'ปราจีนบุรี', '25230', 'hot', 'A', 10500.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3328, 'CUS935020730', 'มาหามะ', 'การีอูมา', '935020730', '', '84/2 ม.11 ต.จะแนะ อ.จะแนะ จ.นราธิวาส 96220', '', 'นราธิวาส', '96220', 'hot', 'D', 1165.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3329, 'CUS616643895', 'บุญเทียม', '', '616643895', '819417325', '(0819417325) 75/4 ม.3 ต.ศรีมงคล อ.ไทรโยค จ.กาญจนบุรี 71150', '', 'กาญจนบุรี', '71150', 'hot', 'D', 1100.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3330, 'CUS983923700', 'ศรัญญา', 'สีปูเต๊ะ', '983923700', '', '75/2 ม.6 ต.ทรายขาว อ.โคกโพธิ์ จ.ปัตตานี 94120', '', 'ปัตตานี', '94120', 'hot', 'A', 21000.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3331, 'CUS964987995', 'นายศรัณย์ณัฐ', 'รุ่งรัตนาอุบล', '964987995', '', '46/14 ซอย 2/4 หมู่8 (แยกขวา) บ้านว่านเหลือง ( สวนมณีจันทร์ ) ต.ชากไทย อ.เขาคิชฌกูฏ จ.จันทบุรี 22210', '', 'จันทบุรี', '22210', 'hot', 'A', 42000.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3332, 'CUS815934861', 'สมจิตร', 'ปานขุนรักษ์', '815934861', '', '162/2 ม.6 ต.เขาพระทอง อ.ชะอวด จ.นครศรีธรรมราช 80180', '', 'นครศรีธรรมราช', '80180', 'hot', 'A', 21400.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3333, 'CUS851818273', 'ลือชา', 'ดีปัญญา', '851818273', '', '81/33 ม.4 ต.เขาน้อย อ.ปราณบุรี จ.ประจวบคีรีขันธ์ 77120', '', 'ประจวบคีรีขันธ์', '77120', 'hot', 'D', 0.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3334, 'CUS653176229', 'สุกัญญา', 'ตะวงษา', '653176229', '', '146/4 หมู่4 บ.ปากราง ต.นายูง อ.นายูง จ.อุดรธานี 41380', '', 'อุดรธานี', '41380', 'hot', 'C', 3435.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3335, 'CUS945789224', 'แสงเทียน', 'ก๋งชิน', '945789224', '', '65 ม.4 ต.ปากคลอง อ.ปะทิว จ.ชุมพร 86210', '', 'ชุมพร', '86210', 'hot', 'D', 0.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3336, 'CUS994791363', 'ปรีชา', 'ทองอิ่ม', '994791363', '', '12/9 หมู่9 ต.กรูด อ.กาญจนดิษฐ์ จ.สุราษฎร์ธานี 84160', '', 'สุราษฎร์ธานี', '84160', 'hot', 'B', 7900.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3337, 'CUS819773988', 'ตี๋งช่วง', 'สูงเนิน', '819773988', '', '131/2 หมู่4 ต.แชะ อ.ครบุรี จ.นครราชสีมา 30250', '', 'นครราชสีมา', '30250', 'hot', 'C', 2300.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3338, 'CUS984277595', 'ถิรเดช​', 'วงศ์​คนงาม', '984277595', '', '909 หมู่​ 4​ (ถนนวัดตำหรุ-บางพลี) ต.บางปูใหม่ อ.เมืองสมุทรปราการ จ.สมุทรปราการ 10280', '', 'สมุทรปราการ', '10280', 'hot', 'D', 1150.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3339, 'CUS817814125', 'ปัญจพล', 'นุโรจน์', '817814125', '', '84/3 ม.1 ต.แก่งหางแมว อ.แก่งหางแมว จ.จันทบุรี 22160', '', 'จันทบุรี', '22160', 'hot', 'C', 3860.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3340, 'CUS952908489', 'สงบ', 'การะศรี', '952908489', '917468884', '5/7 หมู่3 ต.พรหมโลก อ.พรหมคีรี จ.นครศรีธรรมราช 80320', '', 'นครศรีธรรมราช', '80320', 'hot', 'A', 21000.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19');
INSERT INTO `customers` (`customer_id`, `customer_code`, `first_name`, `last_name`, `phone`, `email`, `address`, `district`, `province`, `postal_code`, `temperature_status`, `customer_grade`, `total_purchase_amount`, `assigned_to`, `basket_type`, `assigned_at`, `last_contact_at`, `next_followup_at`, `recall_at`, `recall_reason`, `source`, `notes`, `is_active`, `created_at`, `updated_at`, `appointment_count`, `appointment_extension_count`, `last_appointment_date`, `appointment_extension_expiry`, `max_appointment_extensions`, `appointment_extension_days`, `customer_status`, `customer_time_extension`, `customer_time_base`, `customer_time_expiry`) VALUES
(3341, 'CUS910071437', 'คุณ', 'อนันต์ กลางณรงค์', '910071437', '', '3 ซ.7 ถ.พลูศิริ ต.นาสาร อ.บ้านนาสาร จ.สุราษฎร์ธานี 84120', '', 'สุราษฎร์ธานี', '84120', 'hot', 'A', 22650.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3342, 'CUS922528287', 'อาภรณ์', 'สายสวาสดิ์', '922528287', '', '38 ม.9 ต.คลองเขิน อ.เมืองสมุทรสงคราม จ.สมุทรสงคราม 75000', '', 'สมุทรสงคราม', '75000', 'hot', 'D', 690.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3343, 'CUS878440397', 'คุณอุไรวรรณ', 'ศรีเดช', '878440397', '', '8/5 ม.5 บ้านสีบุญเรือง ต.หนองป่าก่อ อ.ดอยหลวง จ.เชียงราย 57110', '', 'เชียงราย', '57110', 'hot', 'D', 0.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3344, 'CUS856733551', 'สุทิน', 'หนูสนิท', '856733551', '', '70/1 ม.1 ต.นาเกตุ อ.โคกโพธิ์ จ.ปัตตานี 94120', '', 'ปัตตานี', '94120', 'hot', 'D', 1215.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3345, 'CUS899198133', 'พ.อ.สิริศักดิ์', 'สุกใส', '899198133', '', '53 ม.3 บ้านสิริวรรณวรรษ ซ.10 ท่าพะเนียด ต.แก่งเสี้ยน อ.เมืองกาญจนบุรี จ.กาญจนบุรี 71000', '', 'กาญจนบุรี', '71000', 'hot', 'D', 1215.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3346, 'CUS845771162', 'คุณสิยาภา', 'สอนนิล', '845771162', '', '15/1 หมู่10 ต.สองพี่น้อง อ.ท่าใหม่ จ.จันทบุรี 22120', '', 'จันทบุรี', '22120', 'hot', 'A', 11000.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3347, 'CUS871695779', 'อำนาจ', 'เอื้อเฟื้อ', '871695779', '', '78/4 ม.3 ต.ดอนตูม อ.บางเลน จ.นครปฐม 73130', '', 'นครปฐม', '73130', 'hot', 'A', 11650.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3348, 'CUS960077456', 'แสงยันต์', 'สุขวรรณ', '960077456', '955283759', '( 0955283759 ) 187 ม.3 บ.ดงมะไฟ ต.ดงมะไฟ อ.สุวรรณคูหา จ.หนองบัวลำภู 39270', '', 'หนองบัวลำภู', '39270', 'hot', 'D', 690.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3349, 'CUS617688885', 'ศุภชัย', 'คุ้มปิยะผล', '617688885', '', '331/31 ซอยดอนไพร ต.ท่าวัง อ.เมืองนครศรีธรรมราช จ.นครศรีธรรมราช 80000', '', 'นครศรีธรรมราช', '80000', 'hot', 'C', 2430.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3350, 'CUS872697224', 'นิระพา', 'สีเทพ', '872697224', '', '180 ม.8 ต.หัวรอ อ.เมืองพิษณุโลก จ.พิษณุโลก 65000', '', 'พิษณุโลก', '65000', 'hot', 'D', 445.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3351, 'CUS909716658', 'พรพนาไพร', 'มังสา', '909716658', '', '71 ม.3 ต.ชากไทย อ.เขาคิชฌกูฏ จ.จันทบุรี 22210', '', 'จันทบุรี', '22210', 'hot', 'D', 0.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3352, 'CUS814289390', 'ผ่องพักตร์', 'พันธ์นุช', '814289390', '', '277 ม.10 ติดวัดบุญนาคโพธิ์เพชร ต.วังดาล อ.กบินทร์บุรี จ.ปราจีนบุรี 25110', '', 'ปราจีนบุรี', '25110', 'hot', 'D', 790.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3353, 'CUS867636562', 'ณัฐฐิญา', 'ประจักษ์จิตต์', '867636562', '', '15 ม.5 ถ.สมุทรสงครามบางแพ ซ.บ้านรื่นรมณ์ ต.บางกระบือ อ.บางคนที จ.สมุทรสงคราม 75120', '', 'สมุทรสงคราม', '75120', 'hot', 'D', 1215.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3354, 'CUS898790545', 'ประโยง', 'ชายชุม', '898790545', '', '170 ม.5 ต.ชัยบุรี อ.เมืองพัทลุง จ.พัทลุง 93000', '', 'พัทลุง', '93000', 'hot', 'C', 2290.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3355, 'CUS858498099', 'จีรนันท์', 'แคนจา', '858498099', '', '29/1 หมู่3 ต.ตลาดจินดา อ.สามพราน จ.นครปฐม 73110', '', 'นครปฐม', '73110', 'hot', 'D', 0.00, 9, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:57:19', '2025-08-14 05:57:20', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:57:19', '2025-11-12 05:57:19'),
(3356, 'CUS927879497', 'อำนาจ', 'ศุภผล', '927879497', '', '101.หมู่.12.บ้านสันดอน ต.รางบัว.อ.จอมบึง ราชบุรี 70150 ต.รางบัว อ.จอมบึง จ.ราชบุรี 70150', '', 'ราชบุรี', '70150', 'hot', 'B', 6640.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 07:50:34', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3357, 'CUS869038460', 'ไซบะห์', 'เสน่หา', '869038460', '', '74 ซ.กรุงเทพกรีฑา15 แยก 4 เส้นตัดใหม่ ร่มเกล้า - ศรีนครินทร์ อยู่ตรงยูเทรินใต้สะพาน เข้าซอยตึกสีชมพู ข้างเต้นรถ ต.หัวหมาก อ.บางกะปิ จ.กรุงเทพมหานคร 10250', '', 'กรุงเทพมหานคร', '10250', 'hot', 'D', 445.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3358, 'CUS868104111', 'สมาน', 'วัฒนชัยวรรณ์', '868104111', '', '222ถ.เสือป่า ซ.ทิพย์นิเวศน์5 ต.หน้าเมือง อ.เมืองราชบุรี จ.ราชบุรี 70000', '', 'ราชบุรี', '70000', 'hot', 'D', 790.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3359, 'CUS924656116', 'ไก่', 'ปริศณา', '924656116', '', '227 ม.9 ต.นาข่า อ.เมืองอุดรธานี จ.อุดรธานี 41000', '', 'อุดรธานี', '41000', 'hot', 'D', 790.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3360, 'CUS818615809', 'ปัญฑิต', 'กิตติสุทรโลภาค', '818615809', '', '117/6 ม.7 ต.บ้านใหญ่ อ.เมืองนครนายก จ.นครนายก 26000', '', 'นครนายก', '26000', 'hot', 'D', 1165.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3361, 'CUS929865556', 'มาโนด', 'สวัสดิ์ธรรม', '929865556', '', '67/1 ม.1 ต.ปรังเผล อ.สังขละบุรี จ.กาญจนบุรี 71240', '', 'กาญจนบุรี', '71240', 'hot', 'A', 21200.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3362, 'CUS902799079', 'ครูบาศักดิ์', '', '902799079', '959634001', '187ม.3 วัดป่าบ้านกลาง ต.ปลาบ่า อ.ภูเรือ จ.เลย 42160', '', 'เลย', '42160', 'hot', 'D', 1100.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3363, 'CUS956878134', 'นส.นวพร', 'ปะละใจ', '956878134', '', '250 ม.1 ต.หนองม่วงไข่ อ.หนองม่วงไข่ จ.แพร่ 54170', '', 'แพร่', '54170', 'hot', 'D', 445.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3364, 'CUS818780633', 'สนิท', 'วอกลาง', '818780633', '', '332/13 ม.13 ต.ขามสะแกแสง อ.ขามสะแกแสง จ.นครราชสีมา 30290', '', 'นครราชสีมา', '30290', 'hot', 'D', 1935.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3365, 'CUS622566220', 'สุปราณี', 'ทักษี', '622566220', '', '81 หมู่2 บ.หนองทุ่ม ต.ศรีชมภู อ.พรเจริญ จ.บึงกาฬ 38180', '', 'บึงกาฬ', '38180', 'hot', 'A', 10900.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3366, 'CUS818518797', 'วิธะกิตติ์', 'ไชยสิงห์ทอง', '818518797', '', 'PTT Station ปตท.นครหลวง-ภาชี ต.พระแก้ว อ.ภาชี จ.พระนครศรีอยุธยา 13140', '', 'พระนครศรีอยุธยา', '13140', 'hot', 'A', 35800.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3367, 'CUS800132636', 'พัชรี', 'นิมิตบุญอนันต์', '800132636', '', '104/58 หมู่ 6 (บ้านพัทยา 6 ) ต.ห้วยใหญ่ อ.บางละมุง จ.ชลบุรี 20150', '', 'ชลบุรี', '20150', 'hot', 'D', 890.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3368, 'CUS929609306', 'วสันต์​', '(ปาล์ม) พรอินทร์', '929609306', '', '​26/1​ ม.1​ (บ้านไอร์ตุ้ย) ต.ศรีบรรพต อ.ศรีสาคร จ.นราธิวาส 96210', '', 'นราธิวาส', '96210', 'hot', 'C', 2265.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3369, 'CUS887608417', 'เคบี', 'ก็อปปีแอนด์เซอรวิส', '887608417', '', '19 ถนนมนตรี ต.ท้ายช้าง อ.เมืองพังงา จ.พังงา 82000', '', 'พังงา', '82000', 'hot', 'D', 1975.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3370, 'CUS851381850', 'มณฑนา', 'เนตรทัศน์', '851381850', '', '99/181 หมู่ 2 ต.พันท้ายนรสิงห์ อ.เมืองสมุทรสาคร จ.สมุทรสาคร 74000', '', 'สมุทรสาคร', '74000', 'hot', 'C', 2025.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3371, 'CUS992847877', 'กฤษฎากร', 'ถนอมเมฆ', '992847877', '', '486 ม.4 ต.วัดหลวง อ.โพนพิสัย จ.หนองคาย 43120', '', 'หนองคาย', '43120', 'hot', 'C', 2330.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3372, 'CUS942826599', 'กอบโชค', 'เตียตระกูล', '942826599', '', '714/22 ม.4 ต.ชนแดน อ.ชนแดน จ.เพชรบูรณ์ 67150', '', 'เพชรบูรณ์', '67150', 'hot', 'D', 435.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3373, 'CUS838416567', 'ภูรัต', 'สุวรรณวงค์', '838416567', '', 'บ้านบนเนิน 235 ม.3 ต.นาป่า อ.เมืองเพชรบูรณ์ จ.เพชรบูรณ์ 67000', '', 'เพชรบูรณ์', '67000', 'hot', 'B', 7785.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3374, 'CUS957924625', 'อมรเทพ', 'พิทักษ์ปิยะวรรณ', '957924625', '', '116/16 ถ.ท่าเมือง ต.เขานิเวศน์ อ.เมืองระนอง จ.ระนอง 85000', '', 'ระนอง', '85000', 'hot', 'D', 445.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3375, 'CUS950404363', 'ชัชชัย', 'เริงเขตต์กรรม', '950404363', '', '19/7 ซอยลาดพร้าว 126 ถนนลาดพร้าว ต.พลับพลา อ.วังทองหลาง จ.กรุงเทพมหานคร 10310', '', 'กรุงเทพมหานคร', '10310', 'hot', 'D', 1100.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3376, 'CUS945953874', 'ชอ', 'ราชสาส์น', '945953874', '', '( อยู่เขตท่าเรือแหลมฉบัง ติดรั้วอู่ซ่อมเรือบริษัทยูนีไทย ประตู5 เขตเทศบาลนครแหลมฉบัง ) ม.3 ต.ทุ่งสุขลา อ.ศรีราชา จ.ชลบุรี 20230', '', 'ชลบุรี', '20230', 'hot', 'D', 445.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3377, 'CUS882054989', 'โต', '', '882054989', '', '15/2 ต.หนองบอนแดง อ.บ้านบึง จ.ชลบุรี 20170', '', 'ชลบุรี', '20170', 'hot', 'D', 790.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3378, 'CUS854569915', 'พุด', 'ธีร', '854569915', '', '162 หมู่1 บ้านท่าลี่ ต.ชุมช้าง อ.โพนพิสัย จ.หนองคาย 43120', '', 'หนองคาย', '43120', 'hot', 'D', 1100.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3379, 'CUS814023613', 'สกุณา', 'วิสุทธิรัตนกุล', '814023613', '', 'บ้านเลขที่ 176 ซอย 24 สายเอก หมู่ 4 ต.หนองบัว อ.พัฒนานิคม จ.ลพบุรี 15140', '', 'ลพบุรี', '15140', 'hot', 'C', 4785.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3380, 'CUS813979036', 'ทรงวุฒิ', 'บุญทอง ทรงวุฒิ บุญทอง', '813979036', '', '21/41 หมู่ที่ 5 ต.ปากนคร อ.เมืองนครศรีธรรมราช จ.นครศรีธรรมราช 80000', '', 'นครศรีธรรมราช', '80000', 'hot', 'B', 5475.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3381, 'CUS862881540', 'หนูชีพ', 'รัตนพันธ์', '862881540', '', '66 ม.12 ต.ชุมพล อ.ศรีนครินทร์ จ.พัทลุง 93000', '', 'พัทลุง', '93000', 'hot', 'D', 1145.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3382, 'CUS815778490', 'มงคล', 'นันทะมงคล', '815778490', '', '229 ม.2 ต.ทุ่งโพธิ์ อ.นาดี จ.ปราจีนบุรี 25220', '', 'ปราจีนบุรี', '25220', 'hot', 'D', 1165.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3383, 'CUS876745900', 'ม่อน', '', '876745900', '', '93 ม.1 ต.บัวปากท่า อ.บางเลน จ.นครปฐม 73130', '', 'นครปฐม', '73130', 'hot', 'A', 21490.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3384, 'CUS856243052', 'สุรีรัตน์', 'ธรรมวงค์', '856243052', '', '458 บ้านฟาร์ม ซอย 4 หมู่ 1 ต.ริมกก อ.เมืองเชียงราย จ.เชียงราย 57100', '', 'เชียงราย', '57100', 'hot', 'B', 8770.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3385, 'CUS817908712', 'วาณี', '', '817908712', '', '72 หมู่ 12 ต.ละลาย อ.กันทรลักษ์ จ.ศรีสะเกษ 33110', '', 'ศรีสะเกษ', '33110', 'hot', 'A', 10900.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3386, 'CUS822991551', 'ไกรสิทธิ์', 'เพิกโสภณ', '822991551', '', '449/54 ม.3 ต.ดอนตะโก อ.เมืองราชบุรี จ.ราชบุรี 70000', '', 'ราชบุรี', '70000', 'hot', 'D', 1145.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3387, 'CUS894592040', 'อนันต์', 'หวังเกษม', '894592040', '', '141/1 ช.ดำเนินกลางเหนือ ถ.ราชดำเนินกลาง ต.บวรนิเวศ อ.พระนคร จ.กรุงเทพมหานคร 10200', '', 'กรุงเทพมหานคร', '10200', 'hot', 'C', 3495.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3388, 'CUS848459373', 'กล้วย', 'กะทิสด', '848459373', '', '12/160 ม.1 ต.รัษฎา อ.เมืองภูเก็ต จ.ภูเก็ต 83000', '', 'ภูเก็ต', '83000', 'hot', 'C', 2330.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3389, 'CUS919049208', 'อรทัย', 'เพ็งสมบูรณ์', '919049208', '', 'บ้านเลขที่ 7 หมู่.7 ต.คลองหก อ.คลองหลวง จ.ปทุมธานี 12120', '', 'ปทุมธานี', '12120', 'hot', 'D', 790.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3390, 'CUS635823644', 'ศุภกร', 'สุปายนันต์', '635823644', '', '70/1 ม.9 ต.หนองนกแก้ว อ.เลาขวัญ จ.กาญจนบุรี 71210', '', 'กาญจนบุรี', '71210', 'hot', 'A', 10700.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3391, 'CUS616592944', 'ทองรี', 'บุญวรรณ', '616592944', '', '123 หมู่2 บ้านหนอขอน ต.หัวตะพาน อ.หัวตะพาน จ.อำนาจเจริญ 37240', '', 'อำนาจเจริญ', '37240', 'hot', 'A', 10500.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3392, 'CUS639257809', 'คุณพรรณี', '', '639257809', '', '8 หมู่15 บ้านไทรทอง ต.บ้านส้อง อ.เวียงสระ จ.สุราษฎร์ธานี 84190', '', 'สุราษฎร์ธานี', '84190', 'hot', 'B', 5515.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3393, 'CUS803959599', 'มานะ', 'กลิ่นสอาด มานะ กลิ่นสอาด', '803959599', '', '34/2 ม.1 ต.พังตรุ อ.ท่าม่วง จ.กาญจนบุรี 71110', '', 'กาญจนบุรี', '71110', 'hot', 'C', 2245.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3394, 'CUS816719264', 'ประภาศรี', 'คุณะวัฒนกุล', '816719264', '', '429/6 ม.5 ต.ริมกก อ.เมืองเชียงราย จ.เชียงราย 57100', '', 'เชียงราย', '57100', 'hot', 'C', 2310.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3395, 'CUS932236063', 'บูญเรือง', 'จันทร์เส็ง', '932236063', '', '111 ซอย 3 ถ. สมานมิตร ต.ท่าอิฐ อ.เมืองอุตรดิตถ์ จ.อุตรดิตถ์ 53000', '', 'อุตรดิตถ์', '53000', 'hot', 'D', 1100.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3396, 'CUS989826499', 'จ่าเอกกฤติเดชา', 'สวียานนท์', '989826499', '', 'อบต.อ่างทอง ม.3 ต.อ่างทอง อ.ทับสะแก จ.ประจวบคีรีขันธ์ 77130', '', 'ประจวบคีรีขันธ์', '77130', 'hot', 'C', 3475.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3397, 'CUS848622517', 'ศานติ์', 'จาระวรรณ', '848622517', '', '12 ม.2 มบ.ประกายทอง(ทุ่งเขียวหวาน) ต.ควนลัง อ.หาดใหญ่ จ.สงขลา 90110', '', 'สงขลา', '90110', 'hot', 'C', 3495.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3398, 'CUS815136217', 'คำพร', 'มิ่งขวัญ', '815136217', '', '85 หมู่9 บ้านสันติสุข ต.น้ำร้อน อ.วิเชียรบุรี จ.เพชรบูรณ์ 67130', '', 'เพชรบูรณ์', '67130', 'hot', 'D', 0.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3399, 'CUS836066456', 'พัชรี', '', '836066456', '', 'แม่บ้านโครงการ 46/128 นวลจันทร์33 ต.คลองกุ่ม อ.บึงกุ่ม จ.กรุงเทพมหานคร 10230', '', 'กรุงเทพมหานคร', '10230', 'hot', 'C', 2290.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3400, 'CUS986707916', 'ครูสะอิ้ง', 'ทิพย์ประชาบาล', '986707916', '', '188 ม.6 ต.ควนศรี อ.บ้านนาสาร จ.สุราษฎร์ธานี 84270', '', 'สุราษฎร์ธานี', '84270', 'hot', 'C', 2330.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3401, 'CUS952536619', 'พนา', 'นุ้ยตูม', '952536619', '', '88/54 ม.6 หมู่บ้านเดอะทรัสต์ คลอง 4 ต.ลาดสวาย อ.ลำลูกกา จ.ปทุมธานี 12150', '', 'ปทุมธานี', '12150', 'hot', 'D', 445.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3402, 'CUS642982955', 'ณัฎฐชัย', 'เนตรจู', '642982955', '', '67/2ม.10 (ซ.อ๊อฟฟิตโรงอิฐ) ต.เที่ยงแท้ อ.สรรคบุรี จ.ชัยนาท 17140', '', 'ชัยนาท', '17140', 'hot', 'D', 1145.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3403, 'CUS852462252', 'กฤษฎา', 'กมุทะรัตน์', '852462252', '', '11/1 ม.4 ต.คลองสิบสอง อ.หนองจอก จ.กรุงเทพมหานคร 10530', '', 'กรุงเทพมหานคร', '10530', 'hot', 'D', 790.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3404, 'CUS833872815', 'ทองพูน', 'ดวงสำราญ', '833872815', '', '112 หมู่9 บ้านม่วงสามัคคี ต.หนองชัยศรี อ.หนองหงส์ จ.บุรีรัมย์ 31240', '', 'บุรีรัมย์', '31240', 'hot', 'C', 2330.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3405, 'CUS955123203', 'อาแบซีย์', 'คนใหม่', '955123203', '', '45/17 ถ.แสงจันทร์ ต.บางนาค อ.เมืองนราธิวาส จ.นราธิวาส 96000', '', 'นราธิวาส', '96000', 'hot', 'C', 2330.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3406, 'CUS947976139', 'สุรเดช', 'ดิษฐ์พิบูลย์', '947976139', '', 'สวนอินทผลัมคุณเดช 87 / 13 ม.8 ต.ปากแพรก อ.เมืองกาญจนบุรี จ.กาญจนบุรี 71000', '', 'กาญจนบุรี', '71000', 'hot', 'B', 7400.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3407, 'CUS864214287', 'นุจรินทร์', 'คำภักดี', '864214287', '', '145 ม.7 ต.สระใคร อ.สระใคร จ.หนองคาย 43100', '', 'หนองคาย', '43100', 'hot', 'D', 790.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3408, 'CUS829934262', 'วิจัย', '', '829934262', '', '31 หมุ่ 8 ต.บางตะเคียน อ.สองพี่น้อง จ.สุพรรณบุรี 72110', '', 'สุพรรณบุรี', '72110', 'hot', 'B', 6690.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3409, 'CUS818744890', 'สาโรจน์', 'สายน้อย', '818744890', '', '95/157มบ.พฤษลดาบางใหญ่ ม.10 ต.บางแม่นาง อ.บางใหญ่ จ.นนทบุรี 11140', '', 'นนทบุรี', '11140', 'hot', 'D', 0.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3410, 'CUS948919424', 'ภัคภรณ์วดี​', '​ รัก​อยู่​ประเสริฐ', '948919424', '', '347​ หมู่ 1 ต.งิ้วด่อน อ.เมืองสกลนคร จ.สกลนคร 47000', '', 'สกลนคร', '47000', 'hot', 'D', 435.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3411, 'CUS855824567', 'มารอศักดิ์', 'เหล็มเจริญ', '855824567', '', '96/1 ม.4 ต.เกาะแต้ว อ.เมืองสงขลา จ.สงขลา 90000', '', 'สงขลา', '90000', 'hot', 'D', 445.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3412, 'CUS641487115', 'ประนอม', 'สุขประโคน', '641487115', '', '22 หมุ่ 8 สายตรี 6 ต.บึงเจริญ อ.บ้านกรวด จ.บุรีรัมย์ 31180', '', 'บุรีรัมย์', '31180', 'hot', 'D', 445.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3413, 'CUS865239589', 'ณรงค์ฤทธิ์', 'รัตนวงศ์', '865239589', '', '137 ม.4 ต.ไทยสามัคคี อ.วังน้ำเขียว จ.นครราชสีมา 30370', '', 'นครราชสีมา', '30370', 'hot', 'D', 1165.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3414, 'CUS831881266', 'พุทธชาติ', 'ถ่ำพิพัฒน์', '831881266', '', '68/9 ม.4 ซ. บางกร่าง62 ถ.ราชพฤกษ์ ต.บางกร่าง อ.เมืองนนทบุรี จ.นนทบุรี 11000', '', 'นนทบุรี', '11000', 'hot', 'D', 0.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3415, 'CUS657480280', 'นวลอนงค์', 'อินยา', '657480280', '', '11/2 ม.8 ซอย8 บ้านใหม่สันมะนะ ต.ต้นธง อ.เมืองลำพูน จ.ลำพูน 51000', '', 'ลำพูน', '51000', 'hot', 'D', 790.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3416, 'CUS952584937', 'ชาญณรงค์', 'เจริญสุข', '952584937', '', '295 ม.2 ต.ป่าพะยอม อ.ป่าพะยอม จ.พัทลุง 93210', '', 'พัทลุง', '93210', 'hot', 'C', 2200.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3417, 'CUS928989256', 'นายไพรัช', 'บัญญัตินันทกุล', '928989256', '', '242 ม.6 บ้านดงหลง ต.แคมป์สน อ.เขาค้อ จ.เพชรบูรณ์ 67280', '', 'เพชรบูรณ์', '67280', 'hot', 'D', 1165.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3418, 'CUS970605589', 'ประทีป', 'สีดา', '970605589', '', '163 ม.7 บ.ซำตารมย์ ต.ตระกาจ อ.กันทรลักษ์ จ.ศรีสะเกษ 33110', '', 'ศรีสะเกษ', '33110', 'hot', 'C', 2290.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3419, 'CUS848553610', 'Ruam', 'Mulim', '848553610', '899759655', 'หจก.ร่วมมุสลิม(หะยีแอ) 131/4 บ้านตำเสา ม.6 ต.ฆอเลาะ อ.แว้ง จ.นราธิวาส 96160', '', 'นราธิวาส', '96160', 'hot', 'B', 7400.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3420, 'CUS898121022', 'ศิริพันธ์', 'บุญฉ่ำ', '898121022', '', 'สวนกระท้อนห่อศิริพันธ์ ม.3 ซ.ท่าอิฐ ต.ไทรม้า อ.เมืองนนทบุรี จ.นนทบุรี 11000 ต.ไทรม้า อ.เมืองนนทบุรี จ.นนทบุรี 11000', '', 'นนทบุรี', '11000', 'hot', 'B', 7400.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3421, 'CUS821375253', 'นส.ธัฐนินทร์', 'ธานี', '821375253', '', '49 ม.8 ต.ไพร อ.ขุนหาญ จ.ศรีสะเกษ 33150', '', 'ศรีสะเกษ', '33150', 'hot', 'A', 12040.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3422, 'CUS993265936', 'สมร', 'ศรีสกุล', '993265936', '', '70 ม.8 ต.เกาะศาลพระ อ.วัดเพลง จ.ราชบุรี 70170', '', 'ราชบุรี', '70170', 'hot', 'A', 10700.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3423, 'CUS818498997', 'เมตตา', 'เลขาวิจิตร์', '818498997', '', '1 ม.5 ต.โรงหีบ อ.บางคนที จ.สมุทรสงคราม 75120', '', 'สมุทรสงคราม', '75120', 'hot', 'C', 4410.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3424, 'CUS620934492', 'น.ส.บุษราพร', 'พรมบุตร', '620934492', '', '39/1 ม.7 ต.ช่องไม้แก้ว อ.ทุ่งตะโก จ.ชุมพร 86220', '', 'ชุมพร', '86220', 'hot', 'D', 1145.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3425, 'CUS819965154', 'ดุสิต', 'มั่นธรรม', '819965154', '', '116/66 หมู่ 2 ต.พลูตาหลวง อ.สัตหีบ จ.ชลบุรี 20180', '', 'ชลบุรี', '20180', 'hot', 'C', 3700.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3426, 'CUS801653329', 'นางผล', 'สมหมาย', '801653329', '', '98 ม.1 ต.วังหิน อ.วังหิน จ.ศรีสะเกษ 33270', '', 'ศรีสะเกษ', '33270', 'hot', 'D', 445.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3427, 'CUS655708059', 'อุทัย', 'ชาลี', '655708059', '', '126 ม.8 บ้านราษฎร์ ต.เสิงสาง อ.เสิงสาง จ.นครราชสีมา 30330', '', 'นครราชสีมา', '30330', 'hot', 'C', 3700.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3428, 'CUS899396963', 'คุณรัตติกาล', 'กงกล้า', '899396963', '', '273 ซ.สวนยาง หมู่ 5 หมู่บ้านวังรี ต.แก่งดินสอ อ.นาดี จ.ปราจีนบุรี 25220', '', 'ปราจีนบุรี', '25220', 'hot', 'C', 2430.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3429, 'CUS865705652', 'ชัยภัทร', 'คำนวน', '865705652', '', '178 ม.3 ต.สำโรง อ.พระประแดง จ.สมุทรปราการ 10130', '', 'สมุทรปราการ', '10130', 'hot', 'C', 2290.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3430, 'CUS819585893', 'กรรณิการ์', 'ลิมปนะ', '819585893', '', 'วิลัยการอาชีพปรานบุรี 99 ม.7 ต.ปราณบุรี อ.ปราณบุรี จ.ประจวบคีรีขันธ์ 77120', '', 'ประจวบคีรีขันธ์', '77120', 'hot', 'D', 790.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3431, 'CUS925915692', 'วัชรเกียรติ', 'สุทธิจันทร์', '925915692', '', '( ทางเข้าอบต.ท่าขึ้น ประมาณ7กม ) 37/8 หมู่10 บ้านในโคร๊ะ ต.ท่าขึ้น อ.ท่าศาลา จ.นครศรีธรรมราช 80160', '', 'นครศรีธรรมราช', '80160', 'hot', 'C', 2430.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3432, 'CUS848512864', 'นายอานนท์', 'รักษ์บำรุง', '848512864', '', '85/59 ม.1 ต.เวียงสระ อ.เวียงสระ จ.สุราษฎร์ธานี 84190', '', 'สุราษฎร์ธานี', '84190', 'hot', 'B', 7665.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3433, 'CUS809378830', 'นางทองพูล', 'สัยยาน้อย', '809378830', '', '13/28 ม.7 ต.ห้วยเขย่ง อ.ทองผาภูมิ จ.กาญจนบุรี 71180', '', 'กาญจนบุรี', '71180', 'hot', 'C', 2360.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3434, 'CUS817364790', 'กัลยา', 'ทิพประเสริฐ', '817364790', '', '2 หมู่ 9 ต.เมืองใหม่ อ.ราชสาส์น จ.ฉะเชิงเทรา 24120', '', 'ฉะเชิงเทรา', '24120', 'hot', 'B', 5260.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3435, 'CUS925629127', 'นพมาศ', 'นุชน้อย', '925629127', '', '194/1บ้านหินสี ต.ยางหัก อ.ปากท่อ จ.ราชบุรี 70140 ต.ยางหัก อ.ปากท่อ จ.ราชบุรี 70140', '', 'ราชบุรี', '70140', 'hot', 'C', 2370.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3436, 'CUS955050474', 'ซอและอีตำ', '', '955050474', '', '152/4 ม.7 ซอยซียง1 ต.ยะรม อ.เบตง จ.ยะลา 95110', '', 'ยะลา', '95110', 'hot', 'A', 11650.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3437, 'CUS843597569', 'วนิดา', 'อินทรสุระ', '843597569', '', '99/99 46-13 รามคำแหง 118 ต.สะพานสูง อ.สะพานสูง จ.กรุงเทพมหานคร 10240', '', 'กรุงเทพมหานคร', '10240', 'hot', 'D', 0.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3438, 'CUS855497914', 'สรพงษ์', 'กริชชัยศักดิ์', '855497914', '', '40 หมู่ 1 ต.สามตุ่ม อ.เสนา จ.พระนครศรีอยุธยา 13110', '', 'พระนครศรีอยุธยา', '13110', 'hot', 'D', 445.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3439, 'CUS854664074', 'สุปรียา', 'แดนสลัด', '854664074', '', '42 หมู่ 8 บ้านหนองแวง ต.หนองแวง อ.บ้านผือ จ.อุดรธานี 41160', '', 'อุดรธานี', '41160', 'hot', 'B', 5890.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3440, 'CUS630824914', 'พงษ์ศักดิ์', 'แก้วชนะ พงษ์ศักดิ์ แก้วชนะ', '630824914', '', '4/2 หมู่ 4 ต.พิจิตร อ.นาหม่อม จ.สงขลา 90310', '', 'สงขลา', '90310', 'hot', 'D', 1215.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3441, 'CUS822815999', 'สมหมาย', 'วารีทิพย์ขจร', '822815999', '', '341 ม.3 ต.ท่าเสา อ.ไทรโยค จ.กาญจนบุรี 71150', '', 'กาญจนบุรี', '71150', 'hot', 'D', 1145.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3442, 'CUS638682516', 'นายเอกราช', 'เศวตะดุล', '638682516', '', '3 ม.1 ต.บางด้วน อ.ปะเหลียน จ.ตรัง 92140', '', 'ตรัง', '92140', 'hot', 'D', 1215.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3443, 'CUS661682295', 'มยุรี', 'เรืองหิรีญ มยุรี เรืองหิรีญ', '661682295', '', '9 ม.5 ต.บ้านแลง อ.เมืองระยอง จ.ระยอง 21000', '', 'ระยอง', '21000', 'hot', 'D', 0.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3444, 'CUS984961955', 'ปริยพร', 'โรจน์วิรุฬห์', '984961955', '', '549/15ฃอย 1/4(หมู่บ้านไลฟ์อินเดอะการ์เด้น) หมู่ที 3 ต.หนองขาม อ.ศรีราชา จ.ชลบุรี 20230', '', 'ชลบุรี', '20230', 'hot', 'D', 790.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3445, 'CUS876111597', 'สิรินทร์', 'ชมทอง', '876111597', '', '9/3 ม.1 ต.ปากน้ำแหลมสิงห์ อ.แหลมสิงห์ จ.จันทบุรี 22130', '', 'จันทบุรี', '22130', 'hot', 'D', 0.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3446, 'CUS879964439', 'น้ำอ้อย', 'รอดเชื้อจีน', '879964439', '', '2/15 ม.6 ต.หลักสอง อ.บ้านแพ้ว จ.สมุทรสาคร 74120', '', 'สมุทรสาคร', '74120', 'hot', 'B', 5475.00, 6, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:14', '2025-08-14 05:58:15', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:14', '2025-11-12 05:58:14'),
(3447, 'CUS808706916', 'ประเสริฐ', 'ยี่สุ่นแสง', '808706916', '', '102 ม.6 ต.ทุ่งนุ้ย อ.ควนกาหลง จ.สตูล 91130', '', 'สตูล', '91130', 'hot', 'A', 25450.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3448, 'CUS818118368', 'อุบลรัตน์', 'สมานจิต', '818118368', '', '188/40 ม.11 ซอย วัดทรงเมตตา 4 เทศบาลเกล็ดแก้ว ต.บางเสร่ อ.สัตหีบ จ.ชลบุรี 20250', '', 'ชลบุรี', '20250', 'hot', 'D', 445.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3449, 'CUS894740969', 'ธำรง', 'คุ้มเพชร', '894740969', '', '131/1 หมู่ 3 ต.ควนชุม อ.ร่อนพิบูลย์ จ.นครศรีธรรมราช 80130', '', 'นครศรีธรรมราช', '80130', 'hot', 'A', 16175.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3450, 'CUS922862565', 'นายพรชัย', 'วันโท', '922862565', '', '221 หมู่ 19 ต.ขามใหญ่ อ.เมืองอุบลราชธานี จ.อุบลราชธานี 34000', '', 'อุบลราชธานี', '34000', 'hot', 'D', 790.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3451, 'CUS841933712', 'จารินี', 'ทันจำถิ่น', '841933712', '', '47 หมู่ 1 ต.ทุ่งคล้า อ.สายบุรี จ.ปัตตานี 94190', '', 'ปัตตานี', '94190', 'hot', 'D', 1990.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3452, 'CUS824792915', 'อัมเรศ', 'ศรีสมิต', '824792915', '', '470ม.9 ถนนพหลโยธิน ต.เมืองพาน อ.พาน จ.เชียงราย 57120', '', 'เชียงราย', '57120', 'hot', 'D', 445.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3453, 'CUS894068097', 'กล้อง', '', '894068097', '', '15/2 หมู่ 1 สำนักปฏิบัติธรรม ป่าโชติสุนทร ต.บางเตย อ.เมืองฉะเชิงเทรา จ.ฉะเชิงเทรา 24000', '', 'ฉะเชิงเทรา', '24000', 'hot', 'B', 5575.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3454, 'CUS649747295', 'ธรรมนูญ', 'วิชัยดิษฐ์', '649747295', '', '8 ม.10 ต.เขาค่าย อ.สวี จ.ชุมพร 86130', '', 'ชุมพร', '86130', 'hot', 'C', 3075.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3455, 'CUS892461634', 'ทรัพย์นารา', 'ทิวรักษา', '892461634', '', '29/3 ม.1 ซ.ลุงคล้าย ต.ห้วยทับมอญ อ.เขาชะเมา จ.ระยอง 21110', '', 'ระยอง', '21110', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3456, 'CUS629515199', 'สุวิทย์', 'ผ่องพักตร์', '629515199', '', '21/1 ม.5 ต.หนองละลอก อ.บ้านค่าย จ.ระยอง 21120', '', 'ระยอง', '21120', 'hot', 'C', 3075.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3457, 'CUS969428791', 'คุณภูมิ', '', '969428791', '', '653/1 หมู่ 12 ต.หนองปรือ อ.บางละมุง จ.ชลบุรี 20150', '', 'ชลบุรี', '20150', 'hot', 'B', 5575.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3458, 'CUS812509777', 'นายทองหล่อ', 'บุญปองหา', '812509777', '', '302/2 หมู่5 ต.บ้านซ่อง อ.พนมสารคาม จ.ฉะเชิงเทรา ต.บ้านซ่อง อ.พนมสารคาม จ.ฉะเชิงเทรา 24120', '', 'ฉะเชิงเทรา', '24120', 'hot', 'D', 1145.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3459, 'CUS897369571', 'ธิดาพร', 'ชัยอดิศัย', '897369571', '', '2 ถนนจันทน์นิเวศน์ 1 ซอย 2 ต.หาดใหญ่ อ.หาดใหญ่ จ.สงขลา 90110', '', 'สงขลา', '90110', 'hot', 'D', 1145.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3460, 'CUS854807468', 'วันทนา', 'สุพานิช', '854807468', '', '23/230 ซอยช่างอากาศอุทิศ 14 ต.ดอนเมือง อ.ดอนเมือง จ.กรุงเทพมหานคร 10210', '', 'กรุงเทพมหานคร', '10210', 'hot', 'D', 790.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3461, 'CUS857992971', 'สุนันท์', 'เพชรไกร', '857992971', '', '20 หมู่ 6 ต.โละจูด อ.แว้ง จ.นราธิวาส 96160', '', 'นราธิวาส', '96160', 'hot', 'D', 1145.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3462, 'CUS823639829', 'คุณจิดาภา', 'โสมสิริรักษ์', '823639829', '', '170 ม.6 ต.ทุ่งควายกิน อ.แกลง จ.ระยอง 21110', '', 'ระยอง', '21110', 'hot', 'A', 41600.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3463, 'CUS918261752', 'รติมา', 'เจียวก๊ก', '918261752', '', '138/9 ซอยแสงอรุณ ต.มะขามเตี้ย อ.เมืองสุราษฎร์ธานี จ.สุราษฎร์ธานี 84000', '', 'สุราษฎร์ธานี', '84000', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3464, 'CUS829782693', 'เรืองวิทย์', 'รักษศรี', '829782693', '', '5/33 ถ.คลองน้ำ 7 ต.ทับเที่ยง อ.เมืองตรัง จ.ตรัง 92000', '', 'ตรัง', '92000', 'hot', 'D', 1165.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3465, 'CUS819782324', 'ศิริรัตน์', 'คงชาญกิจ', '819782324', '', '6 ม.1 ต.คลองปาง อ.รัษฎา จ.ตรัง 92160 ต.คลองปาง อ.รัษฎา จ.ตรัง 92160', '', 'ตรัง', '92160', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3466, 'CUS898379141', 'พ.ท.นิกร', 'เชื้อบุญมี', '898379141', '', '105 ม.4 ต.นาพึง อ.นาแห้ว จ.เลย 42170', '', 'เลย', '42170', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3467, 'CUS810406213', 'เสงี่ยม', 'อินต๊ะ', '810406213', '', '235 หมู่ 1 ต.น้ำริด อ.เมืองอุตรดิตถ์ จ.อุตรดิตถ์ 53000', '', 'อุตรดิตถ์', '53000', 'hot', 'D', 1165.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3468, 'CUS847731832', 'สามารถ', 'จาดพิมาย', '847731832', '', '135/21 หมู่ 6 ต.ป่าตาล อ.เมืองลพบุรี จ.ลพบุรี 15000', '', 'ลพบุรี', '15000', 'hot', 'D', 1145.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3469, 'CUS884236441', 'ร.ต.อ.สมชาย', 'ขุนศรีธรรมรา', '884236441', '', '5/2 ม.1 ต.นาขุม อ.บ้านโคก จ.อุตรดิตถ์ 53180', '', 'อุตรดิตถ์', '53180', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3470, 'CUS994136882', 'มะลิวัล', 'จันชา', '994136882', '', '18 ม.9 ต.นาตาล อ.ท่าคันโท จ.กาฬสินธุ์ 46190', '', 'กาฬสินธุ์', '46190', 'hot', 'D', 1145.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3471, 'CUS892964511', 'อารียานา', 'กูดู', '892964511', '', '217/2 ม.1 ต.ลำใหม่ อ.เมืองยะลา จ.ยะลา 95160', '', 'ยะลา', '95160', 'hot', 'C', 3700.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3472, 'CUS819409271', 'นิชานาถ', 'วินิจฉัย', '819409271', '', '196/2 หมู่ 7 ตึก 3 ชั้นมีตาข่ายกันนก ถ.ฉะเชิงเทรา-กบินทร์บุรี ต.ท่าถ่าน อ.พนมสารคาม จ.ฉะเชิงเทรา 24120', '', 'ฉะเชิงเทรา', '24120', 'hot', 'D', 445.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3473, 'CUS807505584', 'เพชรรัตดา', 'ถามะนาศาสตร์', '807505584', '', '99/1 ม.4 ต.ทรัพย์ไพวัลย์ อ.เอราวัณ จ.เลย 42220', '', 'เลย', '42220', 'hot', 'D', 1165.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48');
INSERT INTO `customers` (`customer_id`, `customer_code`, `first_name`, `last_name`, `phone`, `email`, `address`, `district`, `province`, `postal_code`, `temperature_status`, `customer_grade`, `total_purchase_amount`, `assigned_to`, `basket_type`, `assigned_at`, `last_contact_at`, `next_followup_at`, `recall_at`, `recall_reason`, `source`, `notes`, `is_active`, `created_at`, `updated_at`, `appointment_count`, `appointment_extension_count`, `last_appointment_date`, `appointment_extension_expiry`, `max_appointment_extensions`, `appointment_extension_days`, `customer_status`, `customer_time_extension`, `customer_time_base`, `customer_time_expiry`) VALUES
(3474, 'CUS896204898', 'ทศพล', 'คนทำนา', '896204898', '', '(กรุณานำส่งที่หลังสถานีอนามัยสีกายนะครับ) 151 ม.6 ต.สีกาย อ.เมืองหนองคาย จ.หนองคาย 43000', '', 'หนองคาย', '43000', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3475, 'CUS898972268', 'ศรีวิไล', 'ตันติวัชราชัย', '898972268', '', '79/3 ม.1 ต.ซับตะเคียน อ.ชัยบาดาล จ.ลพบุรี 15130', '', 'ลพบุรี', '15130', 'hot', 'D', 435.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3476, 'CUS849492126', 'นายพงษ์สิริ', 'บุญญาทวีทวีทรัพย์', '849492126', '823155050', '189 หมู่ 1 ต.ผาสิงห์ อ.เมืองน่าน จ.น่าน 55000', '', 'น่าน', '55000', 'hot', 'C', 2200.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3477, 'CUS815320420', 'คาโล', 'อาหมัด', '815320420', '', '252 ม.2 ต.ตาคลี อ.ตาคลี จ.นครสวรรค์ 60140', '', 'นครสวรรค์', '60140', 'hot', 'C', 3700.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3478, 'CUS988406020', 'คุณจุติมา', 'อุดมศรี', '988406020', '', '152 ม.10 ต.พรุเตียว อ.เขาพนม จ.กระบี่ 81140', '', 'กระบี่', '81140', 'hot', 'C', 2330.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3479, 'CUS894626166', 'กิตติ​ศักดิ์​', 'ลำสา', '894626166', '', '53 ถนน​สุข​ยางค์ ต.สะเตง อ.เมืองยะลา จ.ยะลา 95000', '', 'ยะลา', '95000', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3480, 'CUS835435459', 'กมลภัทร', 'จิตอำพัน', '835435459', '', '158/9 ม.2 ต.หลักสาม อ.บ้านแพ้ว จ.สมุทรสาคร 74120', '', 'สมุทรสาคร', '74120', 'hot', 'A', 10700.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3481, 'CUS855548749', 'วรรณธิดา', 'วงษ์มหิงค์', '855548749', '', '(สำนักสงฆ์ธารน้ำตก) 19/3 ม.5 ต.ท่ามะปราง อ.แก่งคอย จ.สระบุรี 18110', '', 'สระบุรี', '18110', 'hot', 'A', 21600.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3482, 'CUS814875473', 'ธนัง', 'ประเสริฐลาภ', '814875473', '', 'บ.สวัสดีบรรจุภัณฑ์ จำกัด เลขที่ 78/1 ม.6 ต.ท่าอ่าง อ.โชคชัย จ.นครราชสีมา 30190', '', 'นครราชสีมา', '30190', 'hot', 'D', 0.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3483, 'CUS847000347', 'วีรพงษ์', 'ซื่อสัตยาภิรมย์', '847000347', '', 'บจ.ซีอาร์ซี ไทวัสดุ 88/88 ม.13 ต.บางแก้ว อ.บางพลี จ.สมุทรปราการ 10540', '', 'ซีอาร์ซี', '10540', 'hot', 'D', 790.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3484, 'CUS807709276', 'สรกฤช', 'เรืองทอง', '807709276', '', 'โรงเรียนวัดพิศาลนฤมิต ต.ร่อนพิบูลย์ อ.ร่อนพิบูลย์ จ.นครศรีธรรมราช 80130', '', 'นครศรีธรรมราช', '80130', 'hot', 'D', 445.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3485, 'CUS928363997', 'คุณโสภิดา', 'แก้วลา', '928363997', '', '288 ม.9 ต.วัดธาตุ อ.เมืองหนองคาย จ.หนองคาย 43000', '', 'หนองคาย', '43000', 'hot', 'C', 2330.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3486, 'CUS851441955', 'สุภากาญจน์', 'อึงรัตนากร', '851441955', '', '42/80 หมู่บ้านปาล์มสปริงวิลล์ ซ.1 ถ.สายเอเชีย ต.บ้านพรุ อ.หาดใหญ่ จ.สงขลา 90250', '', 'สงขลา', '90250', 'hot', 'D', 790.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3487, 'CUS966341019', 'หนูแดง', 'อุประ', '966341019', '', '39 ม.6 ต.บ้านดุง อ.บ้านดุง จ.อุดรธานี 41190', '', 'อุดรธานี', '41190', 'hot', 'C', 3700.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3488, 'CUS931438298', 'หลิว', '(ส่วนยางใส่หมวก)', '931438298', '', '179 หมู่ 5 บ.แก้งสว่าง ต.ห้วยข่า อ.บุณฑริก จ.อุบลราชธานี 34230', '', 'อุบลราชธานี', '34230', 'hot', 'A', 14600.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3489, 'CUS819381972', 'นายคำรณ', 'บุญสร้อย', '819381972', '', '134/4 หมู่ 4 ต.บึง อ.ศรีราชา จ.ชลบุรี 20230', '', 'ชลบุรี', '20230', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3490, 'CUS810922499', 'มะยาเตง', 'เจะมะสะแล', '810922499', '', '4/6 ม.1 ต.มะนังยง อ.ยะหริ่ง จ.ปัตตานี 94150', '', 'ปัตตานี', '94150', 'hot', 'D', 1890.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3491, 'CUS829703397', 'ปาลินีณัช', 'พิพัฒน์ชนนท์', '829703397', '', 'ร้านเจ้นันท์ 601/2 หมูที่ 6 ต.คลองวาฬ อ.เมืองประจวบคีรีขันธ์ จ.ประจวบคีรีขันธ์ 77000', '', 'ประจวบคีรีขันธ์', '77000', 'hot', 'D', 1590.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3492, 'CUS814316507', 'สุชาติ', 'พลูศิริ', '814316507', '', 'ต.ห้วยเขย่ง อ.ทองผาภูมิ ต.ห้วยเขย่ง อ.ทองผาภูมิ จ.กาญจนบุรี 71180', '', 'กาญจนบุรี', '71180', 'hot', 'B', 7545.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3493, 'CUS820266054', 'สุวรรณี', 'หน่อชาย', '820266054', '', '1/3 หมู่ 10 ต.บ้านกลาง อ.เมืองลำพูน จ.ลำพูน 51000', '', 'ลำพูน', '51000', 'hot', 'D', 1165.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3494, 'CUS869795574', 'รภัสศา', 'อินทร์คง', '869795574', '', '88/106 ซ.4 หมู่บ้านปาล์มสปริงส์ไลฟ์ ต.คลองแห อ.หาดใหญ่ จ.สงขลา 90110', '', 'สงขลา', '90110', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3495, 'CUS817636897', 'อนุชา', 'อ่อนวงษ์', '817636897', '', '166/4 หมู่บ้านบ่อฝ้าย ต.หัวหิน อ.หัวหิน จ.ประจวบคีรีขันธ์ 77110', '', 'ประจวบคีรีขันธ์', '77110', 'hot', 'D', 1145.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3496, 'CUS893366078', 'ทวีชัย', 'แก้ว', '893366078', '', '45/6 ม.4 ต.หนองราชวัตร อ.หนองหญ้าไซ จ.สุพรรณบุรี 72240', '', 'สุพรรณบุรี', '72240', 'hot', 'D', 870.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3497, 'CUS892727959', 'วิลสันติ์', 'ฝั้นกาศ', '892727959', '', 'เลขที่ 149/15 หมู่ 2 ต.นครชุม อ.เมืองกำแพงเพชร จ.กำแพงเพชร 62000', '', 'กำแพงเพชร', '62000', 'hot', 'D', 890.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3498, 'CUS936787857', 'อัดุลซานิง', 'มะลี', '936787857', '', '73 ม.7 ถ.กาแปะฮูลู ต.เบตง อ.เบตง จ.ยะลา 95110', '', 'ยะลา', '95110', 'hot', 'B', 5575.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3499, 'CUS876619496', 'นายญาณวรุฒน์', 'อินปิ่น', '876619496', '', '252 หมู่ 4 ต.หลวงเหนือ อ.งาว จ.ลำปาง 52110', '', 'ลำปาง', '52110', 'hot', 'D', 790.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3500, 'CUS944585561', 'จีระยุทธ', 'กรทรวง', '944585561', '', '101 ม.8 ต.หลักสาม อ.บ้านแพ้ว จ.สมุทรสาคร 74120', '', 'สมุทรสาคร', '74120', 'hot', 'C', 4580.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3501, 'CUS896096790', 'รอยา', 'ยาเสน', '896096790', '', '34 ม.4 ถ.เกาะดอน ซ.ทรัพย์เสรี ต.ละหาร อ.บางบัวทอง จ.นนทบุรี 11110', '', 'นนทบุรี', '11110', 'hot', 'D', 790.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3502, 'CUS980108663', 'เจริญ', 'ขัมภบูลย์', '980108663', '', '77​ ม.5 ต.เขาขาว อ.ละงู จ.สตูล 91110', '', 'สตูล', '91110', 'hot', 'C', 4620.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3503, 'CUS611174647', 'ป้าใหญ่', '', '611174647', '', '371 หมู่ 1 ต.มะลิกา อ.แม่อาย จ.เชียงใหม่ ต.มะลิกา อ.แม่อาย จ.เชียงใหม่ 50280', '', 'เชียงใหม่', '50280', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3504, 'CUS854192616', 'จักราช', 'บุญโร', '854192616', '', '84 ม.6 ต.นาทับ อ.จะนะ จ.สงขลา 90130', '', 'สงขลา', '90130', 'hot', 'B', 5200.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3505, 'CUS860371765', 'สมนิตย์', 'อุ่นใจ', '860371765', '', '230 หมู่ 1 บ้านนาก้อ ต.เจดีย์ชัย อ.ปัว จ.น่าน 55120', '', 'น่าน', '55120', 'hot', 'C', 3700.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3506, 'CUS989965182', 'อาภรณ์', 'มีสำราญ', '989965182', '', '58 ม.13 ต.วังน้ำเย็น อ.วังน้ำเย็น จ.สระแก้ว 27210', '', 'สระแก้ว', '27210', 'hot', 'D', 435.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3507, 'CUS832728416', 'นายสมยง', 'ชวนรัมย์', '832728416', '', '37 ม.6 ต.โคกกลาง อ.ลำปลายมาศ จ.บุรีรัมย์ 31130', '', 'บุรีรัมย์', '31130', 'hot', 'D', 1145.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3508, 'CUS612476997', 'คุณสมหวัง', 'แสนสุข', '612476997', '', '170 ม.1 บ้านตาล ต.โพทะเล อ.โพทะเล จ.พิจิตร 66130', '', 'พิจิตร', '66130', 'hot', 'C', 4050.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3509, 'CUS890564382', 'คุณมะลิวัน', '', '890564382', '', '7/1 ม.1 ต.อาฮี อ.ท่าลี่ จ.เลย 42140', '', 'เลย', '42140', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3510, 'CUS910199217', 'สันติ', 'แซ่เจียง', '910199217', '', '39 ซ.3 ถ.อัยเยอร์เบอร์จัง ต.เบตง อ.เบตง จ.ยะลา 95110', '', 'ยะลา', '95110', 'hot', 'B', 5575.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3511, 'CUS890952461', 'รองทอง', 'อีเซอร์', '890952461', '', '65/1 ม.5 ต.ทรายขาว อ.สอยดาว จ.จันทบุรี 22180', '', 'จันทบุรี', '22180', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3512, 'CUS818782388', 'อนุวัฒน์', '', '818782388', '', '333 หมู่ 11 ต.ชุมเห็ด อ.เมืองบุรีรัมย์ จ.บุรีรัมย์ 31000', '', 'บุรีรัมย์', '31000', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3513, 'CUS816593131', 'สมบัติ', 'โสอุดร', '816593131', '', 'หมู่บ้านอาภากร 2 บ้านเลขที่ 72/145 หมู่ 2 ต.ศาลายา อ.พุทธมณฑล จ.นครปฐม 73170', '', 'นครปฐม', '73170', 'hot', 'D', 445.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3514, 'CUS863150710', 'ร้านนพรวมช่าง', '', '863150710', '', '31/6 ม.3 ต.มะขามเตี้ย อ.เมืองสุราษฎร์ธานี จ.สุราษฎร์ธานี 84000', '', 'สุราษฎร์ธานี', '84000', 'hot', 'B', 5575.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3515, 'CUS806033719', 'ธนากร', 'อ่วมดีสุด', '806033719', '', '2/6 ม.7 ต.ท่าตลาด อ.สามพราน จ.นครปฐม 73110', '', 'นครปฐม', '73110', 'hot', 'D', 0.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3516, 'CUS819458227', 'สุณี', 'ขำเจริญ', '819458227', '', '27/10 ม.4 ต.สุรศักดิ์ อ.ศรีราชา จ.ชลบุรี 20110', '', 'ชลบุรี', '20110', 'hot', 'D', 890.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3517, 'CUS988031669', 'พงศกร', 'ประเสริฐสงค์', '988031669', '', '8 หมู่ 2 ต.หินตั้ง อ.เมืองนครนายก จ.นครนายก 26000', '', 'นครนายก', '26000', 'hot', 'D', 445.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3518, 'CUS982678321', 'ชาญชัย', 'อนันตชัยศิริ', '982678321', '', '24/7 ม.6 ต.ทุ่งตะไคร อ.ทุ่งตะโก จ.ชุมพร 86220', '', 'ชุมพร', '86220', 'hot', 'A', 17900.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3519, 'CUS637768596', 'จินตนา', 'เพ็งแก้ว', '637768596', '', '305 ม.6 ต.ควนขนุน อ.เขาชัยสน จ.พัทลุง 93130', '', 'พัทลุง', '93130', 'hot', 'D', 1890.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3520, 'CUS986823095', 'เกษม', 'มุทิตาธรรม', '986823095', '', '5 ม.1 ต.กะเปอร์ อ.กะเปอร์ จ.ระนอง 85120', '', 'ระนอง', '85120', 'hot', 'C', 3700.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3521, 'CUS897430906', 'บุญเยี่ยม', 'เทพเทียมทัศ', '897430906', '', '(อู่ช่างโหน่ง) 185 หมู่ 3 ต.พุสวรรค์ อ.แก่งกระจาน จ.เพชรบุรี 76170', '', 'เพชรบุรี', '76170', 'hot', 'C', 4050.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3522, 'CUS831685682', 'สุจินต์', 'มณีรัตน์', '831685682', '', '2/1 ม.1 ต.บางเขา อ.หนองจิก จ.ปัตตานี 94170', '', 'ปัตตานี', '94170', 'hot', 'D', 445.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3523, 'CUS813263321', 'จีรวัลย์', 'งอกผล', '813263321', '', '78/1 ถ.เทพกระษัตรี ซ.รัษฎารำลึก ม.6 ต.รัษฎา อ.เมืองภูเก็ต จ.ภูเก็ต 83000', '', 'ภูเก็ต', '83000', 'hot', 'D', 445.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3524, 'CUS898557493', 'ชาติวิตย์', '', '898557493', '', '154 ม.4 ต.นาน้อย อ.นาน้อย จ.น่าน 55150', '', 'น่าน', '55150', 'hot', 'D', 1145.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3525, 'CUS818677220', 'ผู้จัดการ', 'โน๊ต', '818677220', '', '129 ม.5 ต.นางบวช อ.เดิมบางนางบวช จ.สุพรรณบุรี 72120', '', 'สุพรรณบุรี', '72120', 'hot', 'D', 540.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3526, 'CUS892941893', 'นายไชยรัตน์', 'คงสีพุทธ', '892941893', '', '65 ม.6 ต.รือเสาะ อ.รือเสาะ จ.นราธิวาส 96150', '', 'นราธิวาส', '96150', 'hot', 'C', 2200.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3527, 'CUS818286911', 'ศุภชัย', '', '818286911', '', '184 ถ.ราชมนตรี ต.บางด้วน อ.ภาษีเจริญ จ.กรุงเทพมหานคร 10160', '', 'กรุงเทพมหานคร', '10160', 'hot', 'D', 445.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3528, 'CUS898903792', 'สมัย', 'ปลิวสูงเนิน', '898903792', '', '65 ม.1 ต.หนองกะทิง อ.ลำปลายมาศ จ.บุรีรัมย์ 31130', '', 'บุรีรัมย์', '31130', 'hot', 'C', 2025.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3529, 'CUS815384705', 'สมภพ', 'ยีมูดา ยีมูดา', '815384705', '', '85/1 ม.7 (ร้านปราณี เซอร์วิส) ถ.ศักดิเดช ต.วิชิต อ.เมืองภูเก็ต จ.ภูเก็ต 83000', '', 'ภูเก็ต', '83000', 'hot', 'B', 5050.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3530, 'CUS818923407', 'ทรัพย์สิน', 'ภูผา', '818923407', '', '65/1 ม.1 ต.วิชิต อ.เมืองภูเก็ต จ.ภูเก็ต 83000', '', 'ภูเก็ต', '83000', 'hot', 'C', 3300.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3531, 'CUS909307666', 'คุณศิริญญา', 'ทิพช่วย', '909307666', '', '86/1 หมู่ 2 ต.กะหรอ อ.นบพิตำ จ.นครศรีธรรมราช 80160', '', 'นครศรีธรรมราช', '80160', 'hot', 'D', 0.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:48', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3532, 'CUS800453939', 'สุดชาย', '', '800453939', '', '129 หมู่ 2 ถ.นครอินนทร์ ต.บางขุนกอง อ.บางกรวย จ.นนทบุรี 11130', '', 'นนทบุรี', '11130', 'hot', 'B', 5250.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3533, 'CUS840803054', 'นันทา', 'นิมาล', '840803054', '', '88 หมู่ 2 ต.หนองแรด อ.เทิง จ.เชียงราย 57230', '', 'เชียงราย', '57230', 'hot', 'D', 1145.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3534, 'CUS879743528', 'ปุณิกา', 'อุบล', '879743528', '', '224/26 ม.6 ต.สุรศักดิ์ อ.ศรีราชา จ.ชลบุรี 20110', '', 'ชลบุรี', '20110', 'hot', 'D', 0.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3535, 'CUS640299764', 'คุณอ๊อด', 'แสนคํา', '640299764', '', '88 ม.18 ต.ท่าพล อ.เมืองเพชรบูรณ์ จ.เพชรบูรณ์ 67250', '', 'เพชรบูรณ์', '67250', 'hot', 'D', 1580.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3536, 'CUS897908667', 'สดับภิน', 'พวงมาลัย', '897908667', '', 'บ้านสวนสดับภิน 29/1 ม.10 ต.บางเตย อ.สามโคก จ.ปทุมธานี 12160', '', 'ปทุมธานี', '12160', 'hot', 'C', 2310.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3537, 'CUS892025492', 'ชูชาติ', 'กัทลี', '892025492', '', '173 ม.6 ถ.สุวรรณศร ต.ประจันตคาม อ.ประจันตคาม จ.ปราจีนบุรี 25130', '', 'ปราจีนบุรี', '25130', 'hot', 'C', 2200.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3538, 'CUS817807400', 'สุชาติ', 'อินทร์เผ่า', '817807400', '', '65/8 ม.3 ต.โพธิ์เก้าต้น อ.เมืองลพบุรี จ.ลพบุรี 15000', '', 'ลพบุรี', '15000', 'hot', 'D', 1145.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3539, 'CUS844425512', 'สำรวย', 'รุ่งเรือง', '844425512', '', '46/27 หมู่ 3 ต.ถ้ำน้ำผุด อ.เมืองพังงา จ.พังงา 82000', '', 'พังงา', '82000', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3540, 'CUS653173539', 'ซากีนา', 'มะเกะ', '653173539', '', '132 ม.5 ต.ช้างเผือก อ.จะแนะ จ.นราธิวาส 96220', '', 'นราธิวาส', '96220', 'hot', 'D', 445.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3541, 'CUS922686714', 'ยุวรัตน์', '', '922686714', '', '55-57 ถ.พงษ์สุริยา ต.ท่าราบ อ.เมืองเพชรบุรี จ.เพชรบุรี 76000', '', 'เพชรบุรี', '76000', 'hot', 'B', 5250.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3542, 'CUS616174416', 'สุพิศ', 'บุญสืบ', '616174416', '', '99/1 ม.5 ต.ม่วงงาม อ.สิงหนคร จ.สงขลา 90330', '', 'สงขลา', '90330', 'hot', 'D', 1165.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3543, 'CUS847234311', 'แสงดาว', 'พรจักราภัทท์', '847234311', '', '77 หมู่ 8 บ.แม่จว้า ต.แม่สุก อ.แม่ใจ จ.พะเยา 56130', '', 'พะเยา', '56130', 'hot', 'D', 1100.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3544, 'CUS980300289', 'อีสเฮาะ', 'มะนุ', '980300289', '', '89 ม.7 ต.ลาโละ อ.รือเสาะ จ.นราธิวาส 96150', '', 'นราธิวาส', '96150', 'hot', 'B', 7900.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3545, 'CUS892157272', 'แต้ม', 'ครุธหอม', '892157272', '', '70 หมู่ 10 ต.คลองม่วง อ.ปากช่อง จ.นครราชสีมา 30130', '', 'นครราชสีมา', '30130', 'hot', 'D', 1215.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3546, 'CUS648965924', 'สิริพร', 'แสงอนันต์', '648965924', '', '96 หมู่18 ตรงข้ามเยื้องร้านปิยพันธ์ รับซื้อของเก่า บ้านทับหกพัฒนา ต.หนองน้ำใส อ.สีคิ้ว จ.นครราชสีมา 30140', '', 'นครราชสีมา', '30140', 'hot', 'D', 790.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3547, 'CUS818371927', 'พิบูลย์', 'ธาระพุทธิ', '818371927', '', '13/22 ซ.1/1 ม.นันทวัน ต.บางไผ่ อ.บางแค จ.กรุงเทพมหานคร 10160', '', 'กรุงเทพมหานคร', '10160', 'hot', 'D', 890.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3548, 'CUS962465659', 'ธัญรัศม์', 'ตั้งวัฒนสมบูรณ์', '962465659', '', 'บ้านตลาดเขต 305 ม.3 ต.จรเข้สามพัน อ.อู่ทอง จ.สุพรรณบุรี 71170 ต.จรเข้สามพัน อ.อู่ทอง จ.สุพรรณบุรี 71170', '', 'สุพรรณบุรี', '71170', 'hot', 'C', 2290.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3549, 'CUS819636270', 'อำไพ', 'เอกนก', '819636270', '', 'ร้านเอนกไดนาโม 7 ม.6 ต.นาประดู่ อ.โคกโพธิ์ จ.ปัตตานี 94180', '', 'ปัตตานี', '94180', 'hot', 'C', 2300.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3550, 'CUS926291997', 'บุญใส', 'บูระพันธ์', '926291997', '', '50 ม.2 บ้านขามใต้ ต.บึงนคร อ.ธวัชบุรี จ.ร้อยเอ็ด 45170', '', 'ร้อยเอ็ด', '45170', 'hot', 'C', 2290.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3551, 'CUS819085135', 'วนิดา', 'ธรรมโชติ', '819085135', '', '34/21​ หมู่​ 3 ต.ท่าช้าง อ.เมืองจันทบุรี จ.จันทบุรี 22000', '', 'จันทบุรี', '22000', 'hot', 'D', 445.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3552, 'CUS819093925', 'เสถียร', 'แก้วลา', '819093925', '', '62 ม.4 บ้านจอมมณี ต.พิมาน อ.นาแก จ.นครพนม 48130', '', 'นครพนม', '48130', 'hot', 'C', 2300.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3553, 'CUS899967319', 'ตะวัน', 'แปลกพรมราช', '899967319', '', '508 ม.1 ต.วังหมี อ.วังน้ำเขียว จ.นครราชสีมา 30370', '', 'นครราชสีมา', '30370', 'hot', 'D', 790.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3554, 'CUS621181960', 'แป้น', 'ช้างเจริญ', '621181960', '', '13 หมู่ 1 ต.หนองระเวียง อ.พิมาย จ.นครราชสีมา 30110', '', 'นครราชสีมา', '30110', 'hot', 'C', 2430.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3555, 'CUS897842628', 'ภัทริกา', 'ศรีกาหลง', '897842628', '', '37 หมู่5 ซอย14 (เข้าซอยข้างฝายน้ำล้น) ต.มะขามคู่ อ.นิคมพัฒนา จ.ระยอง 21180', '', 'ระยอง', '21180', 'hot', 'C', 4550.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3556, 'CUS871282471', 'เนิน', 'งามเลิศ', '871282471', '', '48 ม.1 ต.ช้างทูน อ.บ่อไร่ จ.ตราด 23140', '', 'ตราด', '23140', 'hot', 'D', 0.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3557, 'CUS895925694', 'ยุทธพงศ์', 'ยายี', '895925694', '', '64/6 ม.5 ต.กมลา อ.กะทู้ จ.ภูเก็ต 83150', '', 'ภูเก็ต', '83150', 'hot', 'D', 1150.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3558, 'CUS818998400', 'นายสุวัฒน์', 'เผื่อนพังงา', '818998400', '', '72/5 ม.2 ถ.ราชพฤกษ์ ต.บางรักน้อย อ.เมืองนนทบุรี จ.นนทบุรี 11000', '', 'นนทบุรี', '11000', 'hot', 'B', 5825.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3559, 'CUS810025485', 'จรูญ', 'ปิ่นตา', '810025485', '', 'แผงของฝากที่ 26 2/35 ม.4 ต.เขาวัว อ.ท่าใหม่ จ.จันทบุรี 22120', '', 'จันทบุรี', '22120', 'hot', 'D', 1215.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3560, 'CUS800759758', 'นายดนัย', 'มนเพียรจันทร์', '800759758', '', '58 หมู่ที่ 1 บ้านทะเมนชัย ต.ทะเมนชัย อ.ลำปลายมาศ จ.บุรีรัมย์ 31130', '', 'บุรีรัมย์', '31130', 'hot', 'D', 1145.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3561, 'CUS819598586', 'ทรงเกียรติ', 'ทิพย์แก้ว', '819598586', '', '199 หมู่ 7 หมู่บ้าน บ้านเทวี ต.บ้านเดื่อ อ.ท่าบ่อ จ.หนองคาย 43110', '', 'หนองคาย', '43110', 'hot', 'D', 790.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3562, 'CUS806626665', 'อำนวย', 'มีสุข', '806626665', '', '50/1 ม.1 ต.สวนพริก อ.พระนครศรีอยุธยา จ.พระนครศรีอยุธยา 13000', '', 'พระนครศรีอยุธยา', '13000', 'hot', 'D', 1145.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3563, 'CUS929923982', 'จรูญ', 'โคกหอม', '929923982', '', '372/7 ม.1 ต.ปากตะโก อ.ทุ่งตะโก จ.ชุมพร 86220', '', 'ชุมพร', '86220', 'hot', 'C', 2300.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3564, 'CUS853727693', 'อุษา', 'ใจปลื้ม ชิตรกุล', '853727693', '', '74/34 ม.5 ต.บ่อผุด อ.เกาะสมุย จ.สุราษฎร์ธานี 84320', '', 'สุราษฎร์ธานี', '84320', 'hot', 'D', 445.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3565, 'CUS926613454', 'วิเชียร', 'หวังหิรัญกุล', '926613454', '', '82/3 ม.10 ต.บ้านเสด็จ อ.เคียนซา จ.สุราษฎร์ธานี 84260', '', 'สุราษฎร์ธานี', '84260', 'hot', 'A', 30700.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3566, 'CUS942413568', 'คุณวรินทร์รตา', 'ธนาชัยเมธาวัฒน์', '942413568', '', '107/7 ซ.ขุนหาญ 1 ม.5 ต.อรัญญิก อ.เมืองพิษณุโลก จ.พิษณุโลก 65000', '', 'พิษณุโลก', '65000', 'hot', 'D', 890.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3567, 'CUS861453753', 'นายธีระศักดิ์', 'จันทวงษ์', '861453753', '', '154/13 ม.1 ต.นิคมพัฒนา อ.นิคมพัฒนา จ.ระยอง 21180', '', 'ระยอง', '21180', 'hot', 'D', 1215.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3568, 'CUS925838049', 'กิตติ', 'เหลืองเสงี่ยม', '925838049', '', '174/1 ม.1 ต.หนองสาหร่าย อ.พนมทวน จ.กาญจนบุรี 71140', '', 'กาญจนบุรี', '71140', 'hot', 'A', 11245.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:48', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:48', '2025-11-12 05:58:48'),
(3569, 'CUS966071789', 'เด่นนภา', 'สาติยะ', '966071789', '', '35 ม.2 ต.ห้วยยั้ง อ.พรานกระต่าย จ.กำแพงเพชร 62110', '', 'กำแพงเพชร', '62110', 'hot', 'D', 1145.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:49', '2025-11-12 05:58:49'),
(3570, 'CUS984795733', 'อภิชาติ', '', '984795733', '', '200 ม.7 ต.ปอ อ.เวียงแก่น จ.เชียงราย 57310', '', 'เชียงราย', '57310', 'hot', 'D', 0.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:49', '2025-11-12 05:58:49'),
(3571, 'CUS622292697', 'ธันยพร', 'มั่นแร่', '622292697', '', '5ม.1 ต.ดอนคา อ.บางแพ จ.ราชบุรี 70160', '', 'ราชบุรี', '70160', 'hot', 'C', 4410.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:49', '2025-11-12 05:58:49'),
(3572, 'CUS937928309', 'สุรเชษฐ์', 'หลีมังสา', '937928309', '', '65 ม.7 ต.ท่าแพ อ.ท่าแพ จ.สตูล 91150', '', 'สตูล', '91150', 'hot', 'C', 2430.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:49', '2025-11-12 05:58:49'),
(3573, 'CUS899158058', 'ประสพโชค', 'กิจเจริญ', '899158058', '', '79 ม.3 ต.คลองชะอุ่น อ.พนม จ.สุราษฎร์ธานี 84250', '', 'สุราษฎร์ธานี', '84250', 'hot', 'B', 5825.00, 8, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:58:49', '2025-11-12 05:58:49'),
(3574, 'CUS843917353', 'เสริมศักดิ์', 'คงกุลทอง', '843917353', '', '56 ม.3 ต.สินปุน อ.พระแสง จ.สุราษฎร์ธานี 84210', '', 'สุราษฎร์ธานี', '84210', 'hot', 'D', 1305.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3575, 'CUS926433362', 'รจสวรรณ', 'อู่เจริญ', '926433362', '', '19 ม.9 ต.บ้านพระ อ.เมืองปราจีนบุรี จ.ปราจีนบุรี 25230', '', 'ปราจีนบุรี', '25230', 'hot', 'C', 2200.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3576, 'CUS819402480', 'วันชัย', 'พิริยะประภากุล', '819402480', '', '37/29 หมู่ 1 ต.บ้านฉาง อ.บ้านฉาง จ.ระยอง 21130', '', 'ระยอง', '21130', 'hot', 'D', 1165.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3577, 'CUS818905445', 'จักรพงษ์', 'เพิ่มวงษ์วานิช', '818905445', '', 'สวนอาหารไพเราะ 192 ม.8 ต.ทุ่งสุขลา อ.ศรีราชา จ.ชลบุรี 20230', '', 'ชลบุรี', '20230', 'hot', 'D', 790.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3578, 'CUS904145029', 'เล็ก', 'ด้วงใหญ่', '904145029', '', '6 ฉลองกรุง ซ.10 ต.ลำปลาทิว อ.ลาดกระบัง จ.กรุงเทพมหานคร 10520', '', 'กรุงเทพมหานคร', '10520', 'hot', 'D', 445.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3579, 'CUS897225338', 'ชาติชาย', 'แก้วมุข', '897225338', '', '89/32 ม.8 บ้านกลางเมือง ต.หมูม่น อ.เมืองอุดรธานี จ.อุดรธานี 41000', '', 'อุดรธานี', '41000', 'hot', 'D', 1545.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3580, 'CUS869280670', 'สุพัฒน์', 'นากแก้ว', '869280670', '', '816 หมู่ 2 ต.พุเตย อ.วิเชียรบุรี จ.เพชรบูรณ์ 67180', '', 'เพชรบูรณ์', '67180', 'hot', 'D', 445.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3581, 'CUS819811831', 'Wu', 'wan chiang', '819811831', '', '88 หมู่ที่ 11 ต.หนองบัว อ.บ้านค่าย จ.ระยอง 21120', '', 'ระยอง', '21120', 'hot', 'C', 4050.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3582, 'CUS945956996', 'เล้ง', 'หลานเจ้าขุนด่าน เบอร์มงคล', '945956996', '', '27 ถ.สาครมงคล2 ซ.23 ต.หาดใหญ่ อ.หาดใหญ่ จ.สงขลา 90110', '', 'สงขลา', '90110', 'hot', 'C', 2025.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3583, 'CUS613629446', 'รุ้งนภา', 'อินทรง', '613629446', '', '39/5 ม 5 ต.หลักสาม อ.บ้านแพ้ว จ.สมุทรสาคร 74120', '', 'สมุทรสาคร', '74120', 'hot', 'C', 2025.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3584, 'CUS992268963', 'พรเทพ', 'ชูสุวรรณ', '992268963', '', '102/13 ถ.เทศบาล 4 ต.ปากเพรียว อ.เมืองสระบุรี จ.สระบุรี 18000', '', 'สระบุรี', '18000', 'hot', 'D', 1100.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3585, 'CUS994566325', 'ทรงสรร', 'เรืองวานิช', '994566325', '', '473 ม.8 บ.ดงงูใหญ่ ต.เนินมะปราง อ.เนินมะปราง จ.พิษณุโลก 65190', '', 'พิษณุโลก', '65190', 'hot', 'D', 1545.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3586, 'CUS984075078', 'อ๊อด', 'ยมหา ทรัพย์พารวย', '984075078', '', 'วัดวังจันทร์ ต.วังจันทร์ อ.วังจันทร์ จ.ระยอง 21210', '', 'ระยอง', '21210', 'hot', 'D', 0.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3587, 'CUS810052500', 'โสภี', 'ป้านสกุล', '810052500', '', '76 หมู่ 9 ต.วัดเพลง อ.วัดเพลง จ.ราชบุรี 70170', '', 'ราชบุรี', '70170', 'hot', 'D', 0.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3588, 'CUS836652224', 'ณัชชา', 'บุญเพ็ชร์', '836652224', '', '599 ม.4 ต.แม่น้ำคู้ อ.ปลวกแดง จ.ระยอง 21140', '', 'ระยอง', '21140', 'hot', 'D', 0.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3589, 'CUS831902888', 'เญาวนา', 'วงศ์ชวลิต', '831902888', '', '57/1 ม.2 ต.ฉลุง อ.เมืองสตูล จ.สตูล 91140', '', 'สตูล', '91140', 'hot', 'B', 5575.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3590, 'CUS979181308', 'มานพ', '', '979181308', '', '68 ม.12 ต.แม่สลองใน อ.แม่ฟ้าหลวง จ.เชียงราย 57110', '', 'เชียงราย', '57110', 'hot', 'D', 980.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3591, 'CUS817504970', 'สมพงษ์', 'สีชัยมงคล', '817504970', '', '114/4ม.6 (บ้านโนนสะอาด) ต.หนองไฮ อ.เมืองอุดรธานี จ.อุดรธานี 41000', '', 'อุดรธานี', '41000', 'hot', 'D', 0.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3592, 'CUS803817066', 'สุรศักดิ์', 'น้ําขาว', '803817066', '', '216/1 ม.1 ต.ปากพนังฝั่งตะวันออก อ.ปากพนัง จ.นครศรีธรรมราช 80140', '', 'นครศรีธรรมราช', '80140', 'hot', 'B', 5575.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3593, 'CUS848631473', 'รุ่งทิพย์', 'สีหาบุตร', '848631473', '', '96 ม.8 ต.บ้านหอย อ.ประจันตคาม จ.ปราจีนบุรี 25130', '', 'ปราจีนบุรี', '25130', 'hot', 'B', 8775.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3594, 'CUS854145985', 'อาจ', 'อุ่นเนื้อ', '854145985', '', 'บ้านเลขที่ 11 ซอยภาษีเจริญหนึ่ง ถนนภาษีเจริญ ต.ในเมือง อ.เมืองอุบลราชธานี จ.อุบลราชธานี 34000', '', 'อุบลราชธานี', '34000', 'hot', 'B', 5475.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3595, 'CUS910498874', 'นายสมพร', 'เอียดประพาล', '910498874', '', '43/29 ม. 1 ต.พะวง อ.เมืองสงขลา จ.สงขลา 90100', '', 'สงขลา', '90100', 'hot', 'D', 1165.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3596, 'CUS906891011', 'ธรรมเนียบ', '', '906891011', '', 'ร้าน ธรรมเนียบ 88 ม.6 ต.ทุ่งสมอ อ.เขาค้อ จ.เพชรบูรณ์ 67270', '', 'เพชรบูรณ์', '67270', 'hot', 'D', 1580.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3597, 'CUS817432541', 'คุณณรงค์', 'วินตะขบ', '817432541', '', '645 หมู่1 บ้านตะขบ ต.ตะขบ อ.ปักธงชัย จ.นครราชสีมา 30150', '', 'นครราชสีมา', '30150', 'hot', 'D', 1215.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3598, 'CUS817559933', 'พจนา', 'วนรักษ์', '817559933', '', '372 ซอยนาทอง 6 ถ.รัชดาภิเษก ต.ดินแดง อ.ดินแดง จ.กรุงเทพมหานคร 10400', '', 'กรุงเทพมหานคร', '10400', 'hot', 'D', 1150.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3599, 'CUS982958154', 'ฉวีวรรณ', 'บุญคง', '982958154', '', '175 ม. 9 ต.หนองใหญ่ อ.ปราสาท จ.สุรินทร์ 32140', '', 'สุรินทร์', '32140', 'hot', 'D', 0.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3600, 'CUS887828550', 'นายโกมล', 'บินหมัด', '887828550', '', '94/2 ม.11 บ้านกลับใต้ ต.จะโหนง อ.จะนะ จ.สงขลา 90130', '', 'สงขลา', '90130', 'hot', 'D', 445.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3601, 'CUS863857813', 'ชาญณรงค์', 'ครองราช', '863857813', '992235185', '74/49​ หมู่​ 6 ต.ทับมา อ.เมืองระยอง จ.ระยอง 21000', '', 'ระยอง', '21000', 'hot', 'D', 445.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3602, 'CUS947873344', 'สุกัลยา', 'ไชยศักดิ์หาร', '947873344', '', 'หมู่บ้านกฤษดานครเทพารักษ์ กม.16 บ้านเลขที่ 250/218 ม.3 ซ.4 ต.บางปลา อ.บางพลี จ.สมุทรปราการ 10540', '', 'สมุทรปราการ', '10540', 'hot', 'D', 445.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3603, 'CUS849400490', 'พิรา​ภรณ์', 'มา​สอน', '849400490', '', 'โครงการ​รี​วา ​88​ ถ.​กลั่น​น​้​ำ​เค็ม ​ติด​ป​ั​้้ม​PT ต.ชะอำ อ.ชะอำ จ.เพชรบุรี 76120', '', 'เพชรบุรี', '76120', 'hot', 'A', 11000.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3604, 'CUS932127562', 'มนัส', 'อินอ้าย', '932127562', '', '135 ม.2 ต.ทุ่งกล้วย อ.ภูซาง จ.พะเยา 56110', '', 'พะเยา', '56110', 'hot', 'D', 0.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3605, 'CUS631738949', 'กรรณิกา', 'แน่นอน', '631738949', '', '60 ม.1 ต.วิสัยใต้ อ.สวี จ.ชุมพร 86130', '', 'ชุมพร', '86130', 'hot', 'D', 690.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3606, 'CUS811914525', 'มลชยา', 'แสงไข่', '811914525', '', '26 หมู่ 1 ต.อำแพง อ.บ้านแพ้ว จ.สมุทรสาคร 74120', '', 'สมุทรสาคร', '74120', 'hot', 'D', 690.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3607, 'CUS653200195', 'ศรรักษ์', 'นวลสุทธิ์', '653200195', '', '51/6 ม.6 ต.ไทรโสภา อ.พระแสง จ.สุราษฎร์ธานี 84210', '', 'สุราษฎร์ธานี', '84210', 'hot', 'B', 5825.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3608, 'CUS857557377', 'พิฆะเนตร', 'ยอดจันทร์', '857557377', '', '220 หมู่ที่ 2 บ้านโชคชัย ต.สามัคคี อ.น้ำโสม จ.อุดรธานี 41210', '', 'อุดรธานี', '41210', 'hot', 'D', 790.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3609, 'CUS857557378', 'พิฆะเนตร', 'ยอดจันทร์', '857557378', '', '221 หมู่ที่ 2 บ้านโชคชัย ต.สามัคคี อ.น้ำโสม จ.อุดรธานี 41210', '', 'อุดรธานี', '41210', 'hot', 'D', 690.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00');
INSERT INTO `customers` (`customer_id`, `customer_code`, `first_name`, `last_name`, `phone`, `email`, `address`, `district`, `province`, `postal_code`, `temperature_status`, `customer_grade`, `total_purchase_amount`, `assigned_to`, `basket_type`, `assigned_at`, `last_contact_at`, `next_followup_at`, `recall_at`, `recall_reason`, `source`, `notes`, `is_active`, `created_at`, `updated_at`, `appointment_count`, `appointment_extension_count`, `last_appointment_date`, `appointment_extension_expiry`, `max_appointment_extensions`, `appointment_extension_days`, `customer_status`, `customer_time_extension`, `customer_time_base`, `customer_time_expiry`) VALUES
(3610, 'CUS801362942', 'ฟาริสา​', 'หะยีมอ', '801362942', '', '21​/1 ม.5 ต.ลำใหม่ อ.เมืองยะลา จ.ยะลา 95160', '', 'ยะลา', '95160', 'hot', 'C', 2005.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3611, 'CUS980651834', 'นายชุม', 'ขวัญจุล', '980651834', '', '27/4 ม.2 ต.ควนโนรี อ.โคกโพธิ์ จ.ปัตตานี 94180', '', 'ปัตตานี', '94180', 'hot', 'D', 1215.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3612, 'CUS812951893', 'วิไล', 'หลิมเพียน', '812951893', '', '214/56 ม.11 ต.หนองขาม อ.ศรีราชา จ.ชลบุรี 20230', '', 'ชลบุรี', '20230', 'hot', 'D', 1150.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3613, 'CUS654781233', 'สราวุทธ', 'ศรีจันทร์', '654781233', '', '73 ม.14 ต.น้ำรอบ อ.ลานสัก จ.อุทัยธานี 61160', '', 'อุทัยธานี', '61160', 'hot', 'D', 0.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3614, 'CUS613465312', 'ศุภชัย', 'บุญนำ', '613465312', '', '14/6 ม.6 ต.สินเจริญ อ.พระแสง จ.สุราษฎร์ธานี 84210', '', 'สุราษฎร์ธานี', '84210', 'hot', 'C', 2370.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3615, 'CUS923787055', 'นายอับดุลเลาะห์', 'สามะ', '923787055', '', '24/8 ม.4 ต.สะเตงนอก อ.เมืองยะลา จ.ยะลา 95000', '', 'ยะลา', '95000', 'hot', 'D', 0.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3616, 'CUS819907818', 'ดรุณีดินเด็ม', '', '819907818', '', '707 ถนนเลียบคลองรอ 1 ต.ควนลัง อ.หาดใหญ่ จ.สงขลา 90110', '', 'สงขลา', '90110', 'hot', 'D', 1215.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3617, 'CUS659257322', 'ฮาซัน', 'ระเอะ', '659257322', '', '111/4 ม.7 ต.ลาโละ อ.รือเสาะ จ.นราธิวาส 96150', '', 'นราธิวาส', '96150', 'hot', 'D', 1150.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3618, 'CUS820129728', 'ช่างเอียด', 'ท่าดินแดง', '820129728', '840372315', '333 ม.4 ต.วังใหม่ อ.ป่าบอน จ.พัทลุง 93170', '', 'พัทลุง', '93170', 'hot', 'D', 0.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00'),
(3619, 'CUS895472828', 'ธานินท์', 'สอนส่งกลิ่น', '895472828', '', '57 หมู่ 2 ต.วัดยางงาม อ.ปากท่อ จ.ราชบุรี 70140', '', 'ราชบุรี', '70140', 'hot', 'A', 14800.00, 7, 'assigned', NULL, NULL, NULL, NULL, NULL, 'PRIONIC', NULL, 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00', 0, 0, NULL, NULL, 3, 30, 'existing', 0, '2025-08-14 05:59:00', '2025-11-12 05:59:00');

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
-- Stand-in structure for view `customer_call_followup_list`
-- (See below for the actual view)
--
CREATE TABLE `customer_call_followup_list` (
`customer_id` int(11)
,`customer_code` varchar(50)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`phone` varchar(20)
,`email` varchar(100)
,`province` varchar(50)
,`temperature_status` enum('hot','warm','cold','frozen')
,`customer_grade` enum('A+','A','B','C','D')
,`assigned_to` int(11)
,`assigned_to_name` varchar(100)
,`call_log_id` int(11)
,`call_result` enum('interested','not_interested','callback','order','complaint')
,`call_status` enum('answered','no_answer','busy','invalid')
,`last_call_date` timestamp
,`next_followup_at` timestamp
,`followup_notes` text
,`followup_days` int(11)
,`followup_priority` enum('low','medium','high','urgent')
,`queue_id` int(11)
,`followup_date` date
,`queue_status` enum('pending','in_progress','completed','cancelled')
,`queue_priority` enum('low','medium','high','urgent')
,`days_until_followup` int(8)
,`urgency_status` varchar(7)
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
  `payment_status` enum('pending','paid','partial','cancelled','returned') DEFAULT 'pending',
  `delivery_date` date DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `delivery_status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_number`, `customer_id`, `created_by`, `order_date`, `total_amount`, `discount_amount`, `discount_percentage`, `net_amount`, `payment_method`, `payment_status`, `delivery_date`, `delivery_address`, `delivery_status`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(4764, 'EXT-20250814125719-6034', 3207, 9, '2025-06-02', 10900.00, 0.00, 0.00, 10900.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4765, 'EXT-20250814125719-8511', 3208, 9, '2025-06-02', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4766, 'EXT-20250814125719-1191', 3209, 9, '2025-06-02', 2025.00, 0.00, 0.00, 2025.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4767, 'EXT-20250814125719-1217', 3210, 9, '2025-06-02', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4768, 'EXT-20250814125719-8248', 3211, 9, '2025-06-02', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4769, 'EXT-20250814125719-2218', 3212, 9, '2025-06-02', 3495.00, 0.00, 0.00, 3495.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4770, 'EXT-20250814125719-3408', 3213, 9, '2025-06-02', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4771, 'EXT-20250814125719-8674', 3214, 9, '2025-06-02', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4772, 'EXT-20250814125719-3681', 3215, 9, '2025-06-02', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4773, 'EXT-20250814125719-3686', 3216, 9, '2025-06-02', 3495.00, 0.00, 0.00, 3495.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4774, 'EXT-20250814125719-6815', 3217, 9, '2025-06-02', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4775, 'EXT-20250814125719-6884', 3218, 9, '2025-06-02', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4776, 'EXT-20250814125719-9338', 3219, 9, '2025-06-02', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4777, 'EXT-20250814125719-3246', 3220, 9, '2025-06-02', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4778, 'EXT-20250814125719-3195', 3221, 9, '2025-06-02', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4779, 'EXT-20250814125719-5134', 3222, 9, '2025-06-02', 2025.00, 0.00, 0.00, 2025.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4780, 'EXT-20250814125719-6353', 3223, 9, '2025-06-02', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4781, 'EXT-20250814125719-1095', 3224, 9, '2025-06-02', 1975.00, 0.00, 0.00, 1975.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4782, 'EXT-20250814125719-7881', 3225, 9, '2025-06-02', 16400.00, 0.00, 0.00, 16400.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4783, 'EXT-20250814125719-3137', 3226, 9, '2025-06-02', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4784, 'EXT-20250814125719-2682', 3227, 9, '2025-06-02', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4785, 'EXT-20250814125719-5902', 3228, 9, '2025-06-02', 2200.00, 0.00, 0.00, 2200.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4786, 'EXT-20250814125719-9721', 3229, 9, '2025-06-02', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4787, 'EXT-20250814125719-7258', 3230, 9, '2025-06-02', 21400.00, 0.00, 0.00, 21400.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4788, 'EXT-20250814125719-6309', 3231, 9, '2025-06-02', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4789, 'EXT-20250814125719-3546', 3232, 9, '2025-06-02', 14400.00, 0.00, 0.00, 14400.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4790, 'EXT-20250814125719-4211', 3233, 9, '2025-06-04', 870.00, 0.00, 0.00, 870.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4791, 'EXT-20250814125719-4863', 3234, 9, '2025-06-04', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4792, 'EXT-20250814125719-8333', 3235, 9, '2025-06-04', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4793, 'EXT-20250814125719-6029', 3236, 9, '2025-06-04', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4794, 'EXT-20250814125719-8552', 3237, 9, '2025-06-04', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4795, 'EXT-20250814125719-4455', 3238, 9, '2025-06-04', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4796, 'EXT-20250814125719-8105', 3239, 9, '2025-06-04', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4797, 'EXT-20250814125719-1902', 3240, 9, '2025-06-04', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4798, 'EXT-20250814125719-6593', 3241, 9, '2025-06-04', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4799, 'EXT-20250814125719-2887', 3242, 9, '2025-06-04', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4800, 'EXT-20250814125719-4632', 3243, 9, '2025-06-04', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4801, 'EXT-20250814125719-3343', 3244, 9, '2025-06-04', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4802, 'EXT-20250814125719-3452', 3245, 9, '2025-06-04', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4803, 'EXT-20250814125719-1597', 3246, 9, '2025-06-04', 3950.00, 0.00, 0.00, 3950.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4804, 'EXT-20250814125719-5728', 3247, 9, '2025-06-04', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4805, 'EXT-20250814125719-8856', 3248, 9, '2025-06-04', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4806, 'EXT-20250814125719-2001', 3249, 9, '2025-06-04', 5050.00, 0.00, 0.00, 5050.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4807, 'EXT-20250814125719-2729', 3250, 9, '2025-06-04', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4808, 'EXT-20250814125719-2439', 3251, 9, '2025-06-04', 1610.00, 0.00, 0.00, 1610.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4809, 'EXT-20250814125719-5328', 3252, 9, '2025-06-04', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4810, 'EXT-20250814125719-5440', 3253, 9, '2025-06-04', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4811, 'EXT-20250814125719-7804', 3254, 9, '2025-06-05', 5475.00, 0.00, 0.00, 5475.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4812, 'EXT-20250814125719-1497', 3255, 9, '2025-06-05', 790.00, 0.00, 0.00, 790.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4813, 'EXT-20250814125719-3372', 3256, 9, '2025-06-06', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4814, 'EXT-20250814125719-2582', 3257, 9, '2025-06-06', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4815, 'EXT-20250814125719-8762', 3258, 9, '2025-06-06', 2025.00, 0.00, 0.00, 2025.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4816, 'EXT-20250814125719-9695', 3259, 9, '2025-06-07', 3495.00, 0.00, 0.00, 3495.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4817, 'EXT-20250814125719-4561', 3260, 9, '2025-06-07', 3100.00, 0.00, 0.00, 3100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4818, 'EXT-20250814125719-5932', 3261, 9, '2025-06-07', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4819, 'EXT-20250814125719-5512', 3262, 9, '2025-06-09', 5340.00, 0.00, 0.00, 5340.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4820, 'EXT-20250814125719-5356', 3263, 9, '2025-06-10', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4821, 'EXT-20250814125719-6552', 3264, 9, '2025-06-10', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4822, 'EXT-20250814125719-9587', 3265, 9, '2025-06-10', 3700.00, 0.00, 0.00, 3700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4823, 'EXT-20250814125719-6687', 3266, 9, '2025-06-10', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4824, 'EXT-20250814125719-4786', 3267, 9, '2025-06-11', 2200.00, 0.00, 0.00, 2200.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4825, 'EXT-20250814125719-3376', 3268, 9, '2025-06-12', 5575.00, 0.00, 0.00, 5575.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4826, 'EXT-20250814125719-9173', 3269, 9, '2025-06-12', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4827, 'EXT-20250814125719-6007', 3270, 9, '2025-06-13', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4828, 'EXT-20250814125719-4101', 3271, 9, '2025-06-13', 435.00, 0.00, 0.00, 435.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4829, 'EXT-20250814125719-8321', 3272, 9, '2025-06-14', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4830, 'EXT-20250814125719-8609', 3273, 9, '2025-06-14', 890.00, 0.00, 0.00, 890.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4831, 'EXT-20250814125719-3497', 3274, 9, '2025-06-14', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4832, 'EXT-20250814125719-3556', 3275, 9, '2025-06-14', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4833, 'EXT-20250814125719-6642', 3276, 9, '2025-06-16', 5475.00, 0.00, 0.00, 5475.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4834, 'EXT-20250814125719-4770', 3277, 9, '2025-06-17', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4835, 'EXT-20250814125719-5919', 3278, 9, '2025-06-17', 7405.00, 0.00, 0.00, 7405.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4836, 'EXT-20250814125719-8386', 3279, 9, '2025-06-17', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4837, 'EXT-20250814125719-3479', 3280, 9, '2025-06-17', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4838, 'EXT-20250814125719-4230', 3281, 9, '2025-06-17', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4839, 'EXT-20250814125719-4164', 3282, 9, '2025-06-17', 1890.00, 0.00, 0.00, 1890.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4840, 'EXT-20250814125719-8471', 3283, 9, '2025-06-30', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4841, 'EXT-20250814125719-4735', 3284, 9, '2025-06-18', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4842, 'EXT-20250814125719-5637', 3285, 9, '2025-06-18', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4843, 'EXT-20250814125719-9063', 3286, 9, '2025-06-19', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4844, 'EXT-20250814125719-1635', 3287, 9, '2025-06-30', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4845, 'EXT-20250814125719-3842', 3288, 9, '2025-06-19', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4846, 'EXT-20250814125719-9196', 3289, 9, '2025-06-19', 16350.00, 0.00, 0.00, 16350.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4847, 'EXT-20250814125719-9793', 3290, 9, '2025-06-20', 10900.00, 0.00, 0.00, 10900.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4848, 'EXT-20250814125719-2953', 3291, 9, '2025-06-20', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4849, 'EXT-20250814125719-7905', 3292, 9, '2025-06-20', 1580.00, 0.00, 0.00, 1580.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4850, 'EXT-20250814125719-3757', 3293, 9, '2025-06-20', 100800.00, 0.00, 0.00, 100800.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4851, 'EXT-20250814125719-3762', 3276, 9, '2025-06-21', 5475.00, 0.00, 0.00, 5475.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4852, 'EXT-20250814125719-2448', 3294, 9, '2025-06-21', 2745.00, 0.00, 0.00, 2745.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4853, 'EXT-20250814125719-8767', 3232, 9, '2025-06-23', 7400.00, 0.00, 0.00, 7400.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4854, 'EXT-20250814125719-9157', 3295, 9, '2025-06-23', 14800.00, 0.00, 0.00, 14800.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:19', '2025-08-14 05:57:19'),
(4855, 'EXT-20250814125720-7595', 3296, 9, '2025-06-24', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4856, 'EXT-20250814125720-1087', 3297, 9, '2025-06-24', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4857, 'EXT-20250814125720-7545', 3298, 9, '2025-06-25', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4858, 'EXT-20250814125720-2756', 3299, 9, '2025-06-26', 10500.00, 0.00, 0.00, 10500.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4859, 'EXT-20250814125720-4283', 3272, 9, '2025-06-26', 11990.00, 0.00, 0.00, 11990.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4860, 'EXT-20250814125720-4374', 3300, 9, '2025-06-30', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4861, 'EXT-20250814125720-6938', 3301, 9, '2025-06-27', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4862, 'EXT-20250814125720-9969', 3302, 9, '2025-06-28', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4863, 'EXT-20250814125720-5474', 3303, 9, '2025-06-30', 2200.00, 0.00, 0.00, 2200.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4864, 'EXT-20250814125720-7170', 3304, 9, '2025-06-30', 2310.00, 0.00, 0.00, 2310.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4865, 'EXT-20250814125720-4430', 3305, 9, '2025-06-30', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4866, 'EXT-20250814125720-5898', 3306, 9, '2025-06-30', 2200.00, 0.00, 0.00, 2200.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4867, 'EXT-20250814125720-1312', 3307, 9, '2025-07-01', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4868, 'EXT-20250814125720-1795', 3308, 9, '2025-07-02', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4869, 'EXT-20250814125720-9776', 3309, 9, '2025-07-02', 1955.00, 0.00, 0.00, 1955.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4870, 'EXT-20250814125720-8436', 3310, 9, '2025-07-03', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4871, 'EXT-20250814125720-2241', 3311, 9, '2025-07-03', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4872, 'EXT-20250814125720-2506', 3312, 9, '2025-07-03', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4873, 'EXT-20250814125720-4789', 3313, 9, '2025-07-04', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4874, 'EXT-20250814125720-1213', 3314, 9, '2025-07-05', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4875, 'EXT-20250814125720-1473', 3315, 9, '2025-07-05', 1955.00, 0.00, 0.00, 1955.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4876, 'EXT-20250814125720-9001', 3316, 9, '2025-07-07', 5575.00, 0.00, 0.00, 5575.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4877, 'EXT-20250814125720-8158', 3317, 9, '2025-07-07', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4878, 'EXT-20250814125720-1209', 3318, 9, '2025-07-07', 1935.00, 0.00, 0.00, 1935.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4879, 'EXT-20250814125720-5401', 3319, 9, '2025-07-07', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4880, 'EXT-20250814125720-6268', 3320, 9, '2025-07-08', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4881, 'EXT-20250814125720-2196', 3321, 9, '2025-07-09', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4882, 'EXT-20250814125720-8123', 3322, 9, '2025-07-09', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4883, 'EXT-20250814125720-2182', 3323, 9, '2025-07-10', 5250.00, 0.00, 0.00, 5250.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4884, 'EXT-20250814125720-5491', 3324, 9, '2025-07-14', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4885, 'EXT-20250814125720-5692', 3325, 9, '2025-07-14', 790.00, 0.00, 0.00, 790.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4886, 'EXT-20250814125720-1997', 3326, 9, '2025-07-14', 7400.00, 0.00, 0.00, 7400.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4887, 'EXT-20250814125720-7976', 3327, 9, '2025-07-14', 10500.00, 0.00, 0.00, 10500.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4888, 'EXT-20250814125720-5150', 3328, 9, '2025-07-15', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4889, 'EXT-20250814125720-6027', 3329, 9, '2025-07-15', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4890, 'EXT-20250814125720-3051', 3330, 9, '2025-07-31', 21000.00, 0.00, 0.00, 21000.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4891, 'EXT-20250814125720-4863', 3331, 9, '2025-07-15', 42000.00, 0.00, 0.00, 42000.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4892, 'EXT-20250814125720-1780', 3332, 9, '2025-07-31', 21400.00, 0.00, 0.00, 21400.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4894, 'EXT-20250814125720-2952', 3334, 9, '2025-07-16', 3435.00, 0.00, 0.00, 3435.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4895, 'EXT-20250814125720-2471', 3335, 9, '2025-07-31', 11650.00, 0.00, 0.00, 11650.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4896, 'EXT-20250814125720-6499', 3336, 9, '2025-07-31', 7900.00, 0.00, 0.00, 7900.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4897, 'EXT-20250814125720-2015', 3337, 9, '2025-07-18', 2300.00, 0.00, 0.00, 2300.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4898, 'EXT-20250814125720-7145', 3338, 9, '2025-07-18', 1150.00, 0.00, 0.00, 1150.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4899, 'EXT-20250814125720-8374', 3339, 9, '2025-07-19', 3860.00, 0.00, 0.00, 3860.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4900, 'EXT-20250814125720-3963', 3340, 9, '2025-07-26', 21000.00, 0.00, 0.00, 21000.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4901, 'EXT-20250814125720-2999', 3341, 9, '2025-07-21', 22650.00, 0.00, 0.00, 22650.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4902, 'EXT-20250814125720-5846', 3342, 9, '2025-07-21', 690.00, 0.00, 0.00, 690.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4903, 'EXT-20250814125720-7348', 3343, 9, '2025-07-31', 11650.00, 0.00, 0.00, 11650.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4904, 'EXT-20250814125720-1243', 3344, 9, '2025-07-24', 1215.00, 0.00, 0.00, 1215.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4905, 'EXT-20250814125720-7848', 3345, 9, '2025-07-24', 1215.00, 0.00, 0.00, 1215.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4906, 'EXT-20250814125720-6601', 3346, 9, '2025-07-25', 11000.00, 0.00, 0.00, 11000.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4907, 'EXT-20250814125720-3415', 3347, 9, '2025-07-25', 11650.00, 0.00, 0.00, 11650.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4908, 'EXT-20250814125720-6078', 3348, 9, '2025-07-25', 690.00, 0.00, 0.00, 690.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4909, 'EXT-20250814125720-3677', 3349, 9, '2025-07-25', 2430.00, 0.00, 0.00, 2430.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4910, 'EXT-20250814125720-1207', 3350, 9, '2025-07-25', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4911, 'EXT-20250814125720-5496', 3351, 9, '2025-07-31', 22475.00, 0.00, 0.00, 22475.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4912, 'EXT-20250814125720-1981', 3352, 9, '2025-07-29', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4913, 'EXT-20250814125720-9136', 3353, 9, '2025-07-31', 1215.00, 0.00, 0.00, 1215.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4914, 'EXT-20250814125720-9033', 3354, 9, '2025-07-30', 2290.00, 0.00, 0.00, 2290.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4915, 'EXT-20250814125720-8809', 3355, 9, '2025-07-31', 445.00, 0.00, 0.00, 445.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:57:20', '2025-08-14 05:57:20'),
(4916, 'EXT-20250814125814-1637', 3356, 6, '2025-06-06', 6640.00, 0.00, 0.00, 6640.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4917, 'EXT-20250814125814-3292', 3357, 6, '2025-06-07', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4918, 'EXT-20250814125814-6697', 3358, 6, '2025-06-07', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4919, 'EXT-20250814125814-2126', 3359, 6, '2025-06-07', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4920, 'EXT-20250814125814-8972', 3360, 6, '2025-06-10', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4921, 'EXT-20250814125814-3643', 3361, 6, '2025-06-11', 21200.00, 0.00, 0.00, 21200.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4922, 'EXT-20250814125814-4535', 3362, 6, '2025-06-12', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4923, 'EXT-20250814125814-2669', 3363, 6, '2025-06-12', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4924, 'EXT-20250814125814-4152', 3364, 6, '2025-06-12', 1935.00, 0.00, 0.00, 1935.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4925, 'EXT-20250814125814-7916', 3365, 6, '2025-06-12', 10900.00, 0.00, 0.00, 10900.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4926, 'EXT-20250814125814-2338', 3366, 6, '2025-06-13', 35800.00, 0.00, 0.00, 35800.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4927, 'EXT-20250814125814-5399', 3367, 6, '2025-06-14', 890.00, 0.00, 0.00, 890.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4928, 'EXT-20250814125814-2476', 3368, 6, '2025-06-17', 2265.00, 0.00, 0.00, 2265.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4929, 'EXT-20250814125814-3316', 3369, 6, '2025-06-17', 1975.00, 0.00, 0.00, 1975.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4930, 'EXT-20250814125814-4830', 3370, 6, '2025-06-17', 2025.00, 0.00, 0.00, 2025.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4931, 'EXT-20250814125814-5779', 3371, 6, '2025-06-17', 2330.00, 0.00, 0.00, 2330.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4932, 'EXT-20250814125814-4290', 3372, 6, '2025-06-18', 435.00, 0.00, 0.00, 435.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4933, 'EXT-20250814125814-1046', 3373, 6, '2025-06-19', 4140.00, 0.00, 0.00, 4140.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4934, 'EXT-20250814125814-1151', 3374, 6, '2025-06-19', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4935, 'EXT-20250814125814-1882', 3375, 6, '2025-06-27', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4936, 'EXT-20250814125814-6081', 3376, 6, '2025-06-28', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4937, 'EXT-20250814125814-3302', 3377, 6, '2025-06-28', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4938, 'EXT-20250814125814-5858', 3378, 6, '2025-06-21', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4939, 'EXT-20250814125814-5762', 3379, 6, '2025-07-03', 4785.00, 0.00, 0.00, 4785.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4940, 'EXT-20250814125814-3380', 3380, 6, '2025-06-21', 5475.00, 0.00, 0.00, 5475.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4941, 'EXT-20250814125814-8923', 3381, 6, '2025-06-21', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4942, 'EXT-20250814125814-4338', 3382, 6, '2025-06-23', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4943, 'EXT-20250814125814-1450', 3383, 6, '2025-07-07', 21490.00, 0.00, 0.00, 21490.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4944, 'EXT-20250814125814-3250', 3384, 6, '2025-06-24', 8770.00, 0.00, 0.00, 8770.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4945, 'EXT-20250814125814-1824', 3385, 6, '2025-06-24', 10900.00, 0.00, 0.00, 10900.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4946, 'EXT-20250814125814-7253', 3386, 6, '2025-06-26', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4947, 'EXT-20250814125814-2227', 3387, 6, '2025-07-22', 3495.00, 0.00, 0.00, 3495.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4948, 'EXT-20250814125814-9443', 3388, 6, '2025-06-27', 2330.00, 0.00, 0.00, 2330.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4949, 'EXT-20250814125814-7820', 3389, 6, '2025-06-28', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4950, 'EXT-20250814125814-1309', 3390, 6, '2025-07-05', 10700.00, 0.00, 0.00, 10700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4951, 'EXT-20250814125814-8920', 3391, 6, '2025-07-05', 10500.00, 0.00, 0.00, 10500.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4952, 'EXT-20250814125814-5143', 3392, 6, '2025-06-30', 5515.00, 0.00, 0.00, 5515.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4953, 'EXT-20250814125814-8813', 3393, 6, '2025-06-30', 2245.00, 0.00, 0.00, 2245.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4954, 'EXT-20250814125814-7772', 3394, 6, '2025-06-30', 2310.00, 0.00, 0.00, 2310.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4955, 'EXT-20250814125814-1163', 3395, 6, '2025-07-01', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4956, 'EXT-20250814125814-5286', 3396, 6, '2025-07-01', 3475.00, 0.00, 0.00, 3475.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4957, 'EXT-20250814125814-4067', 3397, 6, '2025-07-01', 3495.00, 0.00, 0.00, 3495.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4958, 'EXT-20250814125814-7066', 3398, 6, '2025-07-09', 2310.00, 0.00, 0.00, 2310.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4959, 'EXT-20250814125814-9401', 3399, 6, '2025-07-02', 2290.00, 0.00, 0.00, 2290.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4960, 'EXT-20250814125814-5213', 3400, 6, '2025-07-02', 2330.00, 0.00, 0.00, 2330.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4961, 'EXT-20250814125814-8989', 3401, 6, '2025-07-04', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4962, 'EXT-20250814125814-7956', 3402, 6, '2025-07-04', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4963, 'EXT-20250814125814-2183', 3403, 6, '2025-07-05', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4964, 'EXT-20250814125814-6985', 3404, 6, '2025-07-05', 2330.00, 0.00, 0.00, 2330.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4965, 'EXT-20250814125814-8587', 3405, 6, '2025-07-05', 2330.00, 0.00, 0.00, 2330.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4966, 'EXT-20250814125814-6107', 3406, 6, '2025-07-07', 7400.00, 0.00, 0.00, 7400.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4967, 'EXT-20250814125814-6975', 3407, 6, '2025-07-07', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4968, 'EXT-20250814125814-3454', 3408, 6, '2025-07-07', 6690.00, 0.00, 0.00, 6690.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4969, 'EXT-20250814125814-3887', 3409, 6, '2025-07-29', 5575.00, 0.00, 0.00, 5575.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4970, 'EXT-20250814125814-8755', 3410, 6, '2025-07-08', 435.00, 0.00, 0.00, 435.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4971, 'EXT-20250814125814-5128', 3411, 6, '2025-07-09', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4972, 'EXT-20250814125814-9465', 3412, 6, '2025-07-09', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4973, 'EXT-20250814125814-6144', 3413, 6, '2025-07-30', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4974, 'EXT-20250814125814-7089', 3414, 6, '2025-07-29', 1955.00, 0.00, 0.00, 1955.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4975, 'EXT-20250814125814-2686', 3415, 6, '2025-07-10', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4976, 'EXT-20250814125814-5247', 3416, 6, '2025-07-10', 2200.00, 0.00, 0.00, 2200.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4977, 'EXT-20250814125814-5408', 3417, 6, '2025-07-10', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4978, 'EXT-20250814125814-9067', 3418, 6, '2025-07-14', 2290.00, 0.00, 0.00, 2290.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4979, 'EXT-20250814125814-3473', 3419, 6, '2025-07-15', 7400.00, 0.00, 0.00, 7400.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4980, 'EXT-20250814125814-7365', 3420, 6, '2025-07-15', 7400.00, 0.00, 0.00, 7400.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4981, 'EXT-20250814125814-1937', 3421, 6, '2025-07-16', 12040.00, 0.00, 0.00, 12040.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4982, 'EXT-20250814125814-9720', 3422, 6, '2025-07-16', 10700.00, 0.00, 0.00, 10700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4983, 'EXT-20250814125814-7852', 3423, 6, '2025-07-18', 4410.00, 0.00, 0.00, 4410.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4984, 'EXT-20250814125814-9216', 3424, 6, '2025-07-18', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4985, 'EXT-20250814125814-4782', 3425, 6, '2025-07-18', 3700.00, 0.00, 0.00, 3700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4986, 'EXT-20250814125814-3284', 3426, 6, '2025-07-18', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4987, 'EXT-20250814125814-2673', 3427, 6, '2025-07-19', 3700.00, 0.00, 0.00, 3700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4988, 'EXT-20250814125814-7731', 3428, 6, '2025-07-21', 2430.00, 0.00, 0.00, 2430.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:14', '2025-08-14 05:58:14'),
(4989, 'EXT-20250814125815-3619', 3429, 6, '2025-07-22', 2290.00, 0.00, 0.00, 2290.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(4990, 'EXT-20250814125815-1222', 3430, 6, '2025-07-22', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(4991, 'EXT-20250814125815-2157', 3431, 6, '2025-07-22', 2430.00, 0.00, 0.00, 2430.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(4992, 'EXT-20250814125815-6157', 3432, 6, '2025-07-22', 7665.00, 0.00, 0.00, 7665.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(4993, 'EXT-20250814125815-2003', 3433, 6, '2025-07-22', 2360.00, 0.00, 0.00, 2360.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(4994, 'EXT-20250814125815-8078', 3434, 6, '2025-07-23', 5260.00, 0.00, 0.00, 5260.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(4995, 'EXT-20250814125815-9891', 3435, 6, '2025-07-24', 2370.00, 0.00, 0.00, 2370.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(4996, 'EXT-20250814125815-4786', 3436, 6, '2025-07-24', 11650.00, 0.00, 0.00, 11650.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(4997, 'EXT-20250814125815-2875', 3437, 6, '2025-07-31', 445.00, 0.00, 0.00, 445.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 06:04:12'),
(4998, 'EXT-20250814125815-3683', 3438, 6, '2025-07-25', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(4999, 'EXT-20250814125815-1501', 3439, 6, '2025-07-25', 5890.00, 0.00, 0.00, 5890.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(5000, 'EXT-20250814125815-3233', 3440, 6, '2025-07-30', 1215.00, 0.00, 0.00, 1215.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(5001, 'EXT-20250814125815-3019', 3441, 6, '2025-07-26', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(5002, 'EXT-20250814125815-9051', 3442, 6, '2025-07-29', 1215.00, 0.00, 0.00, 1215.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(5003, 'EXT-20250814125815-7347', 3443, 6, '2025-07-29', 1215.00, 0.00, 0.00, 1215.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(5004, 'EXT-20250814125815-2848', 3444, 6, '2025-07-30', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(5005, 'EXT-20250814125815-2588', 3445, 6, '2025-07-30', 1150.00, 0.00, 0.00, 1150.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(5006, 'EXT-20250814125815-5620', 3446, 6, '2025-07-31', 5475.00, 0.00, 0.00, 5475.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(5007, 'EXT-20250814125815-3241', 3373, 6, '2025-07-31', 3645.00, 0.00, 0.00, 3645.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:15', '2025-08-14 05:58:15'),
(5008, 'EXT-20250814125849-1812', 3447, 8, '2025-06-04', 25450.00, 0.00, 0.00, 25450.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5009, 'EXT-20250814125849-1391', 3448, 8, '2025-06-04', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5010, 'EXT-20250814125849-5293', 3449, 8, '2025-06-04', 10700.00, 0.00, 0.00, 10700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5011, 'EXT-20250814125849-2522', 3450, 8, '2025-06-06', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49');
INSERT INTO `orders` (`order_id`, `order_number`, `customer_id`, `created_by`, `order_date`, `total_amount`, `discount_amount`, `discount_percentage`, `net_amount`, `payment_method`, `payment_status`, `delivery_date`, `delivery_address`, `delivery_status`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(5012, 'EXT-20250814125849-5883', 3451, 8, '2025-06-06', 1990.00, 0.00, 0.00, 1990.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5013, 'EXT-20250814125849-1201', 3452, 8, '2025-06-07', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5014, 'EXT-20250814125849-2488', 3453, 8, '2025-06-09', 5575.00, 0.00, 0.00, 5575.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5015, 'EXT-20250814125849-1946', 3454, 8, '2025-06-10', 3075.00, 0.00, 0.00, 3075.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5016, 'EXT-20250814125849-5347', 3455, 8, '2025-06-10', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5017, 'EXT-20250814125849-6874', 3456, 8, '2025-06-11', 3075.00, 0.00, 0.00, 3075.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5018, 'EXT-20250814125849-9592', 3457, 8, '2025-06-11', 5575.00, 0.00, 0.00, 5575.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5019, 'EXT-20250814125849-9545', 3458, 8, '2025-06-11', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5020, 'EXT-20250814125849-3184', 3459, 8, '2025-06-11', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5021, 'EXT-20250814125849-2546', 3460, 8, '2025-06-11', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5022, 'EXT-20250814125849-1861', 3461, 8, '2025-06-11', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5023, 'EXT-20250814125849-2928', 3462, 8, '2025-06-12', 41600.00, 0.00, 0.00, 41600.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5024, 'EXT-20250814125849-3750', 3463, 8, '2025-06-12', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5025, 'EXT-20250814125849-1872', 3464, 8, '2025-06-12', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5026, 'EXT-20250814125849-9065', 3465, 8, '2025-06-12', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5027, 'EXT-20250814125849-9496', 3466, 8, '2025-06-13', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5028, 'EXT-20250814125849-2889', 3467, 8, '2025-06-13', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5029, 'EXT-20250814125849-5104', 3468, 8, '2025-06-23', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5030, 'EXT-20250814125849-2656', 3469, 8, '2025-06-14', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5031, 'EXT-20250814125849-6073', 3470, 8, '2025-06-14', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5032, 'EXT-20250814125849-5667', 3471, 8, '2025-06-16', 3700.00, 0.00, 0.00, 3700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5033, 'EXT-20250814125849-1238', 3472, 8, '2025-06-16', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5034, 'EXT-20250814125849-7371', 3473, 8, '2025-06-16', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5035, 'EXT-20250814125849-2978', 3474, 8, '2025-06-16', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5036, 'EXT-20250814125849-4354', 3475, 8, '2025-06-17', 435.00, 0.00, 0.00, 435.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5037, 'EXT-20250814125849-1833', 3476, 8, '2025-06-17', 2200.00, 0.00, 0.00, 2200.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5038, 'EXT-20250814125849-9768', 3477, 8, '2025-06-17', 3700.00, 0.00, 0.00, 3700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5039, 'EXT-20250814125849-9058', 3478, 8, '2025-06-18', 2330.00, 0.00, 0.00, 2330.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5040, 'EXT-20250814125849-4132', 3479, 8, '2025-06-18', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5041, 'EXT-20250814125849-7057', 3480, 8, '2025-06-18', 10700.00, 0.00, 0.00, 10700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5042, 'EXT-20250814125849-2651', 3481, 8, '2025-06-18', 21600.00, 0.00, 0.00, 21600.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5043, 'EXT-20250814125849-1314', 3482, 8, '2025-06-30', 4050.00, 0.00, 0.00, 4050.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5044, 'EXT-20250814125849-6841', 3483, 8, '2025-06-19', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5045, 'EXT-20250814125849-8545', 3484, 8, '2025-06-20', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5046, 'EXT-20250814125849-4239', 3485, 8, '2025-06-20', 2330.00, 0.00, 0.00, 2330.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5047, 'EXT-20250814125849-4000', 3486, 8, '2025-06-20', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5048, 'EXT-20250814125849-4540', 3487, 8, '2025-06-20', 3700.00, 0.00, 0.00, 3700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5049, 'EXT-20250814125849-8898', 3488, 8, '2025-06-23', 10900.00, 0.00, 0.00, 10900.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5050, 'EXT-20250814125849-2191', 3489, 8, '2025-06-23', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5051, 'EXT-20250814125849-6512', 3490, 8, '2025-06-24', 1890.00, 0.00, 0.00, 1890.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5052, 'EXT-20250814125849-3632', 3491, 8, '2025-06-24', 1590.00, 0.00, 0.00, 1590.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5053, 'EXT-20250814125849-9263', 3492, 8, '2025-06-24', 7545.00, 0.00, 0.00, 7545.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5054, 'EXT-20250814125849-8710', 3493, 8, '2025-06-25', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5055, 'EXT-20250814125849-2610', 3494, 8, '2025-06-25', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5056, 'EXT-20250814125849-2347', 3495, 8, '2025-07-02', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5057, 'EXT-20250814125849-9493', 3496, 8, '2025-06-25', 870.00, 0.00, 0.00, 870.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5058, 'EXT-20250814125849-7575', 3497, 8, '2025-06-28', 890.00, 0.00, 0.00, 890.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5059, 'EXT-20250814125849-5920', 3498, 8, '2025-06-26', 5575.00, 0.00, 0.00, 5575.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5060, 'EXT-20250814125849-8400', 3499, 8, '2025-06-27', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5061, 'EXT-20250814125849-7856', 3500, 8, '2025-06-27', 4580.00, 0.00, 0.00, 4580.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5062, 'EXT-20250814125849-1848', 3501, 8, '2025-06-27', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5063, 'EXT-20250814125849-4566', 3502, 8, '2025-06-30', 4620.00, 0.00, 0.00, 4620.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5064, 'EXT-20250814125849-4030', 3503, 8, '2025-06-28', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5065, 'EXT-20250814125849-5282', 3504, 8, '2025-06-30', 5200.00, 0.00, 0.00, 5200.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5066, 'EXT-20250814125849-4300', 3505, 8, '2025-06-30', 3700.00, 0.00, 0.00, 3700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5067, 'EXT-20250814125849-9792', 3506, 8, '2025-07-02', 435.00, 0.00, 0.00, 435.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5068, 'EXT-20250814125849-9741', 3507, 8, '2025-07-02', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5069, 'EXT-20250814125849-1676', 3508, 8, '2025-07-03', 4050.00, 0.00, 0.00, 4050.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5070, 'EXT-20250814125849-5038', 3509, 8, '2025-07-03', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5071, 'EXT-20250814125849-5820', 3510, 8, '2025-07-04', 5575.00, 0.00, 0.00, 5575.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5072, 'EXT-20250814125849-1744', 3511, 8, '2025-07-10', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5073, 'EXT-20250814125849-5489', 3512, 8, '2025-07-04', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5074, 'EXT-20250814125849-5283', 3513, 8, '2025-07-05', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5075, 'EXT-20250814125849-4511', 3514, 8, '2025-07-31', 5575.00, 0.00, 0.00, 5575.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5076, 'EXT-20250814125849-5734', 3515, 8, '2025-07-30', 2200.00, 0.00, 0.00, 2200.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5077, 'EXT-20250814125849-9759', 3516, 8, '2025-07-05', 890.00, 0.00, 0.00, 890.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5078, 'EXT-20250814125849-6242', 3517, 8, '2025-07-05', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5079, 'EXT-20250814125849-5986', 3518, 8, '2025-07-07', 17900.00, 0.00, 0.00, 17900.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5080, 'EXT-20250814125849-2948', 3519, 8, '2025-07-07', 1890.00, 0.00, 0.00, 1890.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5081, 'EXT-20250814125849-3159', 3520, 8, '2025-07-07', 3700.00, 0.00, 0.00, 3700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5082, 'EXT-20250814125849-9694', 3521, 8, '2025-07-07', 4050.00, 0.00, 0.00, 4050.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5083, 'EXT-20250814125849-9893', 3522, 8, '2025-07-07', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5084, 'EXT-20250814125849-2663', 3523, 8, '2025-07-08', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5085, 'EXT-20250814125849-7049', 3524, 8, '2025-07-08', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5086, 'EXT-20250814125849-9584', 3525, 8, '2025-07-08', 540.00, 0.00, 0.00, 540.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5087, 'EXT-20250814125849-5616', 3526, 8, '2025-07-08', 2200.00, 0.00, 0.00, 2200.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5088, 'EXT-20250814125849-3256', 3527, 8, '2025-07-09', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5089, 'EXT-20250814125849-8425', 3528, 8, '2025-07-10', 2025.00, 0.00, 0.00, 2025.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5090, 'EXT-20250814125849-9216', 3529, 8, '2025-07-09', 5050.00, 0.00, 0.00, 5050.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5091, 'EXT-20250814125849-3833', 3530, 8, '2025-07-21', 3300.00, 0.00, 0.00, 3300.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5093, 'EXT-20250814125849-3378', 3532, 8, '2025-07-11', 5250.00, 0.00, 0.00, 5250.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5094, 'EXT-20250814125849-5622', 3533, 8, '2025-07-14', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5095, 'EXT-20250814125849-5917', 3534, 8, '2025-07-31', 5575.00, 0.00, 0.00, 5575.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5096, 'EXT-20250814125849-9174', 3535, 8, '2025-07-15', 1580.00, 0.00, 0.00, 1580.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5097, 'EXT-20250814125849-1849', 3536, 8, '2025-07-15', 2310.00, 0.00, 0.00, 2310.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5098, 'EXT-20250814125849-2580', 3537, 8, '2025-07-31', 2200.00, 0.00, 0.00, 2200.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5099, 'EXT-20250814125849-7850', 3538, 8, '2025-07-15', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5100, 'EXT-20250814125849-2807', 3539, 8, '2025-07-15', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5101, 'EXT-20250814125849-4346', 3449, 8, '2025-07-15', 5475.00, 0.00, 0.00, 5475.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5102, 'EXT-20250814125849-4875', 3540, 8, '2025-07-15', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5103, 'EXT-20250814125849-2179', 3541, 8, '2025-07-15', 5250.00, 0.00, 0.00, 5250.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5104, 'EXT-20250814125849-8598', 3542, 8, '2025-07-19', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5105, 'EXT-20250814125849-6178', 3543, 8, '2025-07-31', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5106, 'EXT-20250814125849-1172', 3544, 8, '2025-07-16', 7900.00, 0.00, 0.00, 7900.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5107, 'EXT-20250814125849-9633', 3545, 8, '2025-07-16', 1215.00, 0.00, 0.00, 1215.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5108, 'EXT-20250814125849-9680', 3546, 8, '2025-07-16', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5109, 'EXT-20250814125849-9935', 3452, 8, '2025-07-29', 445.00, 0.00, 0.00, 445.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5110, 'EXT-20250814125849-5508', 3547, 8, '2025-07-17', 890.00, 0.00, 0.00, 890.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5111, 'EXT-20250814125849-7694', 3548, 8, '2025-07-18', 2290.00, 0.00, 0.00, 2290.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5112, 'EXT-20250814125849-2067', 3549, 8, '2025-07-18', 2300.00, 0.00, 0.00, 2300.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5113, 'EXT-20250814125849-6498', 3550, 8, '2025-07-18', 2290.00, 0.00, 0.00, 2290.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5114, 'EXT-20250814125849-7252', 3551, 8, '2025-07-19', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5115, 'EXT-20250814125849-5395', 3552, 8, '2025-07-19', 2300.00, 0.00, 0.00, 2300.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5116, 'EXT-20250814125849-1834', 3553, 8, '2025-07-21', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5117, 'EXT-20250814125849-3614', 3554, 8, '2025-07-21', 2430.00, 0.00, 0.00, 2430.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5118, 'EXT-20250814125849-4301', 3555, 8, '2025-07-21', 4550.00, 0.00, 0.00, 4550.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5119, 'EXT-20250814125849-7820', 3556, 8, '2025-07-22', 1215.00, 0.00, 0.00, 1215.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5120, 'EXT-20250814125849-5783', 3557, 8, '2025-07-22', 1150.00, 0.00, 0.00, 1150.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5121, 'EXT-20250814125849-4051', 3558, 8, '2025-07-22', 5825.00, 0.00, 0.00, 5825.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5122, 'EXT-20250814125849-8730', 3559, 8, '2025-07-22', 1215.00, 0.00, 0.00, 1215.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5123, 'EXT-20250814125849-3204', 3560, 8, '2025-07-23', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5124, 'EXT-20250814125849-7720', 3561, 8, '2025-07-23', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5125, 'EXT-20250814125849-8292', 3562, 8, '2025-07-24', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5126, 'EXT-20250814125849-1781', 3563, 8, '2025-07-24', 2300.00, 0.00, 0.00, 2300.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5127, 'EXT-20250814125849-8503', 3564, 8, '2025-07-24', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5128, 'EXT-20250814125849-3816', 3565, 8, '2025-07-24', 30700.00, 0.00, 0.00, 30700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5129, 'EXT-20250814125849-2273', 3467, 8, '2025-07-24', 2290.00, 0.00, 0.00, 2290.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5130, 'EXT-20250814125849-6646', 3566, 8, '2025-07-25', 890.00, 0.00, 0.00, 890.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5131, 'EXT-20250814125849-2221', 3567, 8, '2025-07-25', 1215.00, 0.00, 0.00, 1215.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5132, 'EXT-20250814125849-8373', 3568, 8, '2025-07-26', 11245.00, 0.00, 0.00, 11245.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5133, 'EXT-20250814125849-3485', 3569, 8, '2025-07-29', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5134, 'EXT-20250814125849-1746', 3570, 8, '2025-07-29', 21400.00, 0.00, 0.00, 21400.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5135, 'EXT-20250814125849-7949', 3488, 8, '2025-07-29', 3700.00, 0.00, 0.00, 3700.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5136, 'EXT-20250814125849-5351', 3571, 8, '2025-07-29', 4410.00, 0.00, 0.00, 4410.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5137, 'EXT-20250814125849-1191', 3572, 8, '2025-07-29', 2430.00, 0.00, 0.00, 2430.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5138, 'EXT-20250814125849-7897', 3573, 8, '2025-07-30', 5825.00, 0.00, 0.00, 5825.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:58:49', '2025-08-14 05:58:49'),
(5139, 'EXT-20250814125900-3934', 3574, 7, '2025-06-19', 1305.00, 0.00, 0.00, 1305.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5140, 'EXT-20250814125900-6470', 3575, 7, '2025-06-21', 2200.00, 0.00, 0.00, 2200.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5141, 'EXT-20250814125900-9278', 3576, 7, '2025-06-23', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5142, 'EXT-20250814125900-5164', 3577, 7, '2025-06-25', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5143, 'EXT-20250814125900-5430', 3578, 7, '2025-06-30', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5144, 'EXT-20250814125900-7563', 3579, 7, '2025-06-27', 1545.00, 0.00, 0.00, 1545.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5145, 'EXT-20250814125900-9570', 3580, 7, '2025-06-27', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5146, 'EXT-20250814125900-4175', 3581, 7, '2025-06-28', 4050.00, 0.00, 0.00, 4050.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5147, 'EXT-20250814125900-9861', 3582, 7, '2025-06-28', 2025.00, 0.00, 0.00, 2025.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5148, 'EXT-20250814125900-2164', 3583, 7, '2025-06-28', 2025.00, 0.00, 0.00, 2025.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5149, 'EXT-20250814125900-7185', 3584, 7, '2025-07-02', 1100.00, 0.00, 0.00, 1100.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5150, 'EXT-20250814125900-5527', 3585, 7, '2025-07-02', 1545.00, 0.00, 0.00, 1545.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5151, 'EXT-20250814125900-8933', 3586, 7, '2025-07-30', 5250.00, 0.00, 0.00, 5250.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5152, 'EXT-20250814125900-9712', 3587, 7, '2025-07-30', 3700.00, 0.00, 0.00, 3700.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5153, 'EXT-20250814125900-4180', 3588, 7, '2025-07-08', 790.00, 0.00, 0.00, 790.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5154, 'EXT-20250814125900-7429', 3589, 7, '2025-07-09', 5575.00, 0.00, 0.00, 5575.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5155, 'EXT-20250814125900-4548', 3590, 7, '2025-07-10', 980.00, 0.00, 0.00, 980.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5156, 'EXT-20250814125900-6044', 3591, 7, '2025-07-31', 2310.00, 0.00, 0.00, 2310.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5157, 'EXT-20250814125900-5326', 3592, 7, '2025-07-31', 5575.00, 0.00, 0.00, 5575.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5158, 'EXT-20250814125900-4418', 3593, 7, '2025-07-31', 8775.00, 0.00, 0.00, 8775.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5159, 'EXT-20250814125900-4728', 3594, 7, '2025-07-14', 5475.00, 0.00, 0.00, 5475.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5160, 'EXT-20250814125900-8262', 3595, 7, '2025-07-15', 1165.00, 0.00, 0.00, 1165.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5161, 'EXT-20250814125900-7071', 3596, 7, '2025-07-16', 1580.00, 0.00, 0.00, 1580.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5162, 'EXT-20250814125900-9720', 3597, 7, '2025-07-18', 1215.00, 0.00, 0.00, 1215.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5163, 'EXT-20250814125900-5931', 3598, 7, '2025-07-18', 1150.00, 0.00, 0.00, 1150.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5164, 'EXT-20250814125900-1244', 3599, 7, '2025-07-31', 1145.00, 0.00, 0.00, 1145.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5165, 'EXT-20250814125900-8321', 3600, 7, '2025-07-21', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5166, 'EXT-20250814125900-1854', 3601, 7, '2025-07-22', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5167, 'EXT-20250814125900-9152', 3602, 7, '2025-07-22', 445.00, 0.00, 0.00, 445.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5168, 'EXT-20250814125900-1799', 3603, 7, '2025-07-29', 11000.00, 0.00, 0.00, 11000.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5169, 'EXT-20250814125900-8335', 3604, 7, '2025-07-30', 11650.00, 0.00, 0.00, 11650.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5170, 'EXT-20250814125900-4503', 3605, 7, '2025-07-22', 690.00, 0.00, 0.00, 690.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5171, 'EXT-20250814125900-1602', 3606, 7, '2025-07-23', 690.00, 0.00, 0.00, 690.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5172, 'EXT-20250814125900-4661', 3607, 7, '2025-07-30', 5825.00, 0.00, 0.00, 5825.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5173, 'EXT-20250814125900-2380', 3608, 7, '2025-07-25', 790.00, 0.00, 0.00, 790.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5174, 'EXT-20250814125900-6102', 3609, 7, '2025-07-25', 690.00, 0.00, 0.00, 690.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5175, 'EXT-20250814125900-1838', 3610, 7, '2025-07-25', 2005.00, 0.00, 0.00, 2005.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5176, 'EXT-20250814125900-4866', 3611, 7, '2025-07-30', 1215.00, 0.00, 0.00, 1215.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5177, 'EXT-20250814125900-3786', 3612, 7, '2025-07-25', 1150.00, 0.00, 0.00, 1150.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5178, 'EXT-20250814125900-3739', 3613, 7, '2025-07-29', 1580.00, 0.00, 0.00, 1580.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5179, 'EXT-20250814125900-6672', 3614, 7, '2025-07-29', 2370.00, 0.00, 0.00, 2370.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5180, 'EXT-20250814125900-2357', 3615, 7, '2025-07-31', 445.00, 0.00, 0.00, 445.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5181, 'EXT-20250814125900-2978', 3616, 7, '2025-07-30', 1215.00, 0.00, 0.00, 1215.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5182, 'EXT-20250814125900-1722', 3617, 7, '2025-07-30', 1150.00, 0.00, 0.00, 1150.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5183, 'EXT-20250814125900-8201', 3618, 7, '2025-07-30', 790.00, 0.00, 0.00, 790.00, 'cash', 'pending', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00'),
(5184, 'EXT-20250814125900-7129', 3619, 7, '2025-07-31', 14800.00, 0.00, 0.00, 14800.00, 'cash', 'paid', NULL, NULL, 'delivered', 'นำเข้าจาก External - ', 1, '2025-08-14 05:59:00', '2025-08-14 05:59:00');

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
(140, 4997, 6, 'status_update', 'อัปเดต payment_status เป็น: returned', '2025-08-14 06:01:18'),
(141, 4997, 6, 'status_update', 'อัปเดต payment_status เป็น: returned', '2025-08-14 06:01:23'),
(142, 4997, 6, 'status_update', 'อัปเดต payment_status เป็น: partial', '2025-08-14 06:02:07'),
(143, 4997, 6, 'status_update', 'อัปเดต payment_status เป็น: pending', '2025-08-14 06:02:13'),
(144, 4997, 6, 'status_update', 'อัปเดต payment_status เป็น: returned', '2025-08-14 06:04:06'),
(145, 4997, 6, 'status_update', 'อัปเดต payment_status เป็น: pending', '2025-08-14 06:04:12');

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
(4685, 4764, 9, 10, 1090.00, 10900.00),
(4686, 4764, 14, 1, 0.00, 0.00),
(4687, 4765, 12, 1, 1100.00, 1100.00),
(4688, 4766, 11, 1, 405.00, 405.00),
(4689, 4766, 11, 1, 405.00, 405.00),
(4690, 4766, 11, 1, 405.00, 405.00),
(4691, 4766, 11, 1, 405.00, 405.00),
(4692, 4766, 11, 1, 405.00, 405.00),
(4693, 4767, 10, 1, 790.00, 790.00),
(4694, 4768, 12, 1, 1100.00, 1100.00),
(4695, 4769, 9, 3, 1165.00, 3495.00),
(4696, 4770, 12, 1, 1100.00, 1100.00),
(4697, 4771, 12, 1, 1100.00, 1100.00),
(4698, 4772, 11, 1, 445.00, 445.00),
(4699, 4773, 9, 3, 1165.00, 3495.00),
(4700, 4774, 9, 1, 1165.00, 1165.00),
(4701, 4775, 11, 1, 445.00, 445.00),
(4702, 4776, 9, 1, 1165.00, 1165.00),
(4703, 4777, 11, 1, 445.00, 445.00),
(4704, 4778, 11, 1, 445.00, 445.00),
(4705, 4779, 11, 1, 405.00, 405.00),
(4706, 4779, 11, 1, 405.00, 405.00),
(4707, 4779, 11, 1, 405.00, 405.00),
(4708, 4779, 11, 1, 405.00, 405.00),
(4709, 4779, 11, 1, 405.00, 405.00),
(4710, 4780, 9, 1, 1165.00, 1165.00),
(4711, 4781, 16, 5, 395.00, 1975.00),
(4712, 4782, 10, 10, 720.00, 7200.00),
(4713, 4782, 12, 5, 1050.00, 5250.00),
(4714, 4782, 14, 1, 0.00, 0.00),
(4715, 4782, 16, 1, 395.00, 395.00),
(4716, 4782, 16, 1, 395.00, 395.00),
(4717, 4782, 16, 1, 395.00, 395.00),
(4718, 4782, 16, 1, 395.00, 395.00),
(4719, 4782, 16, 1, 395.00, 395.00),
(4720, 4782, 16, 1, 395.00, 395.00),
(4721, 4782, 16, 1, 395.00, 395.00),
(4722, 4782, 16, 1, 395.00, 395.00),
(4723, 4782, 16, 1, 395.00, 395.00),
(4724, 4782, 16, 1, 395.00, 395.00),
(4725, 4783, 11, 1, 445.00, 445.00),
(4726, 4784, 12, 1, 1100.00, 1100.00),
(4727, 4785, 12, 2, 1100.00, 2200.00),
(4728, 4786, 9, 1, 1165.00, 1165.00),
(4729, 4787, 12, 10, 1050.00, 10500.00),
(4730, 4787, 9, 10, 1090.00, 10900.00),
(4731, 4787, 13, 1, 0.00, 0.00),
(4732, 4787, 14, 1, 0.00, 0.00),
(4733, 4788, 11, 1, 445.00, 445.00),
(4734, 4789, 10, 20, 720.00, 14400.00),
(4735, 4789, 15, 1, 0.00, 0.00),
(4736, 4789, 14, 1, 0.00, 0.00),
(4737, 4790, 16, 1, 435.00, 435.00),
(4738, 4790, 16, 1, 435.00, 435.00),
(4739, 4791, 11, 1, 445.00, 445.00),
(4740, 4792, 11, 1, 445.00, 445.00),
(4741, 4793, 12, 1, 1100.00, 1100.00),
(4742, 4794, 8, 1, 1145.00, 1145.00),
(4743, 4795, 8, 1, 1145.00, 1145.00),
(4744, 4796, 12, 1, 1100.00, 1100.00),
(4745, 4797, 11, 1, 445.00, 445.00),
(4746, 4798, 10, 1, 790.00, 790.00),
(4747, 4799, 10, 1, 790.00, 790.00),
(4748, 4800, 11, 1, 445.00, 445.00),
(4749, 4801, 9, 1, 1165.00, 1165.00),
(4750, 4802, 11, 1, 445.00, 445.00),
(4751, 4803, 16, 1, 395.00, 395.00),
(4752, 4803, 16, 1, 395.00, 395.00),
(4753, 4803, 16, 1, 395.00, 395.00),
(4754, 4803, 16, 1, 395.00, 395.00),
(4755, 4803, 16, 1, 395.00, 395.00),
(4756, 4803, 16, 1, 395.00, 395.00),
(4757, 4803, 16, 1, 395.00, 395.00),
(4758, 4803, 16, 1, 395.00, 395.00),
(4759, 4803, 16, 1, 395.00, 395.00),
(4760, 4803, 16, 1, 395.00, 395.00),
(4761, 4803, 14, 1, 0.00, 0.00),
(4762, 4804, 10, 1, 790.00, 790.00),
(4763, 4805, 10, 1, 790.00, 790.00),
(4764, 4806, 12, 2, 1050.00, 2100.00),
(4765, 4806, 10, 1, 740.00, 740.00),
(4766, 4806, 8, 1, 1095.00, 1095.00),
(4767, 4806, 9, 1, 1115.00, 1115.00),
(4768, 4807, 10, 1, 790.00, 790.00),
(4769, 4808, 9, 1, 1165.00, 1165.00),
(4770, 4808, 11, 1, 445.00, 445.00),
(4771, 4809, 10, 1, 790.00, 790.00),
(4772, 4810, 12, 1, 1100.00, 1100.00),
(4773, 4811, 8, 5, 1095.00, 5475.00),
(4774, 4812, 16, 1, 395.00, 395.00),
(4775, 4812, 16, 1, 395.00, 395.00),
(4776, 4813, 12, 1, 1100.00, 1100.00),
(4777, 4814, 9, 1, 1165.00, 1165.00),
(4778, 4815, 11, 5, 405.00, 2025.00),
(4779, 4816, 9, 3, 1165.00, 3495.00),
(4780, 4817, 10, 1, 790.00, 790.00),
(4781, 4817, 8, 1, 1145.00, 1145.00),
(4782, 4817, 9, 1, 1165.00, 1165.00),
(4783, 4818, 10, 1, 790.00, 790.00),
(4784, 4819, 12, 3, 1050.00, 3150.00),
(4785, 4819, 8, 2, 1095.00, 2190.00),
(4786, 4820, 12, 1, 1100.00, 1100.00),
(4787, 4821, 12, 1, 1100.00, 1100.00),
(4788, 4822, 10, 5, 740.00, 3700.00),
(4789, 4823, 9, 1, 1165.00, 1165.00),
(4790, 4824, 12, 2, 1100.00, 2200.00),
(4791, 4825, 9, 5, 1115.00, 5575.00),
(4792, 4826, 10, 1, 790.00, 790.00),
(4793, 4827, 10, 1, 790.00, 790.00),
(4794, 4828, 16, 1, 435.00, 435.00),
(4795, 4829, 8, 1, 1145.00, 1145.00),
(4796, 4830, 11, 2, 445.00, 890.00),
(4797, 4831, 10, 1, 790.00, 790.00),
(4798, 4832, 9, 1, 1165.00, 1165.00),
(4799, 4833, 8, 5, 1095.00, 5475.00),
(4800, 4834, 12, 1, 1100.00, 1100.00),
(4801, 4835, 10, 3, 740.00, 2220.00),
(4802, 4835, 9, 3, 1115.00, 3345.00),
(4803, 4835, 12, 1, 1050.00, 1050.00),
(4804, 4835, 16, 2, 395.00, 790.00),
(4805, 4836, 9, 1, 1165.00, 1165.00),
(4806, 4837, 8, 1, 1145.00, 1145.00),
(4807, 4838, 12, 1, 1100.00, 1100.00),
(4808, 4839, 10, 1, 790.00, 790.00),
(4809, 4839, 12, 1, 1100.00, 1100.00),
(4810, 4840, 8, 1, 1145.00, 1145.00),
(4811, 4841, 9, 1, 1165.00, 1165.00),
(4812, 4842, 8, 1, 1145.00, 1145.00),
(4813, 4843, 10, 1, 790.00, 790.00),
(4814, 4844, 12, 1, 1100.00, 1100.00),
(4815, 4845, 8, 1, 1145.00, 1145.00),
(4816, 4846, 9, 15, 1090.00, 16350.00),
(4817, 4846, 14, 1, 0.00, 0.00),
(4818, 4847, 9, 10, 1090.00, 10900.00),
(4819, 4847, 14, 1, 0.00, 0.00),
(4820, 4848, 9, 1, 1165.00, 1165.00),
(4821, 4849, 10, 2, 790.00, 1580.00),
(4822, 4850, 8, 20, 1070.00, 21400.00),
(4823, 4850, 8, 20, 1070.00, 21400.00),
(4824, 4850, 8, 20, 1070.00, 21400.00),
(4825, 4850, 9, 20, 1090.00, 21800.00),
(4826, 4850, 10, 20, 740.00, 14800.00),
(4827, 4850, 17, 1, 0.00, 0.00),
(4828, 4850, 15, 1, 0.00, 0.00),
(4829, 4850, 15, 1, 0.00, 0.00),
(4830, 4850, 14, 1, 0.00, 0.00),
(4831, 4851, 8, 5, 1095.00, 5475.00),
(4832, 4852, 10, 2, 790.00, 1580.00),
(4833, 4852, 9, 1, 1165.00, 1165.00),
(4834, 4853, 10, 10, 740.00, 7400.00),
(4835, 4853, 15, 1, 0.00, 0.00),
(4836, 4853, 14, 1, 0.00, 0.00),
(4837, 4854, 10, 20, 740.00, 14800.00),
(4838, 4854, 15, 1, 0.00, 0.00),
(4839, 4854, 14, 1, 0.00, 0.00),
(4840, 4855, 12, 1, 1100.00, 1100.00),
(4841, 4856, 12, 1, 1100.00, 1100.00),
(4842, 4857, 9, 1, 1165.00, 1165.00),
(4843, 4858, 12, 10, 1050.00, 10500.00),
(4844, 4858, 13, 1, 0.00, 0.00),
(4845, 4858, 14, 1, 0.00, 0.00),
(4846, 4859, 9, 11, 1090.00, 11990.00),
(4847, 4859, 14, 1, 0.00, 0.00),
(4848, 4860, 9, 1, 1165.00, 1165.00),
(4849, 4861, 10, 1, 790.00, 790.00),
(4850, 4862, 9, 1, 1165.00, 1165.00),
(4851, 4863, 12, 2, 1100.00, 2200.00),
(4852, 4864, 8, 1, 1145.00, 1145.00),
(4853, 4864, 9, 1, 1165.00, 1165.00),
(4854, 4865, 8, 1, 1145.00, 1145.00),
(4855, 4866, 12, 2, 1100.00, 2200.00),
(4856, 4867, 12, 1, 1100.00, 1100.00),
(4857, 4868, 9, 1, 1165.00, 1165.00),
(4858, 4869, 10, 1, 790.00, 790.00),
(4859, 4869, 9, 1, 1165.00, 1165.00),
(4860, 4870, 8, 1, 1145.00, 1145.00),
(4861, 4871, 8, 1, 1145.00, 1145.00),
(4862, 4872, 8, 1, 1145.00, 1145.00),
(4863, 4873, 10, 1, 790.00, 790.00),
(4864, 4874, 10, 1, 790.00, 790.00),
(4865, 4875, 9, 1, 1165.00, 1165.00),
(4866, 4875, 10, 1, 790.00, 790.00),
(4867, 4876, 9, 5, 1115.00, 5575.00),
(4868, 4876, 14, 1, 0.00, 0.00),
(4869, 4877, 9, 1, 1165.00, 1165.00),
(4870, 4878, 10, 1, 790.00, 790.00),
(4871, 4878, 8, 1, 1145.00, 1145.00),
(4872, 4879, 9, 1, 1165.00, 1165.00),
(4873, 4880, 9, 1, 1165.00, 1165.00),
(4874, 4881, 9, 1, 1165.00, 1165.00),
(4875, 4882, 12, 1, 1100.00, 1100.00),
(4876, 4883, 12, 5, 1050.00, 5250.00),
(4877, 4884, 9, 1, 1165.00, 1165.00),
(4878, 4885, 10, 1, 790.00, 790.00),
(4879, 4886, 10, 10, 740.00, 7400.00),
(4880, 4886, 15, 1, 0.00, 0.00),
(4881, 4886, 14, 1, 0.00, 0.00),
(4882, 4887, 12, 10, 1050.00, 10500.00),
(4883, 4887, 13, 1, 0.00, 0.00),
(4884, 4887, 14, 1, 0.00, 0.00),
(4885, 4888, 9, 1, 1165.00, 1165.00),
(4886, 4889, 12, 1, 1100.00, 1100.00),
(4887, 4890, 12, 20, 1050.00, 21000.00),
(4888, 4890, 13, 1, 0.00, 0.00),
(4889, 4890, 14, 1, 0.00, 0.00),
(4890, 4891, 12, 20, 1050.00, 21000.00),
(4891, 4891, 12, 20, 1050.00, 21000.00),
(4892, 4891, 13, 1, 0.00, 0.00),
(4893, 4891, 14, 1, 0.00, 0.00),
(4894, 4892, 8, 20, 1070.00, 21400.00),
(4895, 4892, 17, 1, 0.00, 0.00),
(4896, 4892, 14, 1, 0.00, 0.00),
(4897, 4894, 8, 3, 1145.00, 3435.00),
(4898, 4895, 9, 10, 1165.00, 11650.00),
(4899, 4895, 15, 1, 0.00, 0.00),
(4900, 4895, 14, 1, 0.00, 0.00),
(4901, 4896, 11, 20, 395.00, 7900.00),
(4902, 4896, 20, 1, 0.00, 0.00),
(4903, 4896, 14, 1, 0.00, 0.00),
(4904, 4897, 12, 2, 1150.00, 2300.00),
(4905, 4898, 12, 1, 1150.00, 1150.00),
(4906, 4899, 10, 2, 740.00, 1480.00),
(4907, 4899, 11, 1, 405.00, 405.00),
(4908, 4899, 16, 5, 395.00, 1975.00),
(4909, 4899, 14, 1, 0.00, 0.00),
(4910, 4900, 12, 20, 1050.00, 21000.00),
(4911, 4900, 13, 1, 0.00, 0.00),
(4912, 4900, 14, 1, 0.00, 0.00),
(4913, 4901, 9, 10, 1165.00, 11650.00),
(4914, 4901, 12, 10, 1100.00, 11000.00),
(4915, 4901, 15, 1, 0.00, 0.00),
(4916, 4901, 13, 1, 0.00, 0.00),
(4917, 4901, 14, 1, 0.00, 0.00),
(4918, 4901, 18, 1, 0.00, 0.00),
(4919, 4902, 23, 2, 345.00, 690.00),
(4920, 4903, 9, 5, 1165.00, 5825.00),
(4921, 4903, 9, 5, 1165.00, 5825.00),
(4922, 4904, 9, 1, 1215.00, 1215.00),
(4923, 4905, 9, 1, 1215.00, 1215.00),
(4924, 4906, 12, 10, 1100.00, 11000.00),
(4925, 4906, 13, 1, 0.00, 0.00),
(4926, 4906, 14, 1, 0.00, 0.00),
(4927, 4907, 9, 10, 1165.00, 11650.00),
(4928, 4907, 15, 1, 0.00, 0.00),
(4929, 4907, 14, 1, 0.00, 0.00),
(4930, 4908, 23, 2, 345.00, 690.00),
(4931, 4909, 9, 2, 1215.00, 2430.00),
(4932, 4910, 11, 1, 445.00, 445.00),
(4933, 4911, 10, 5, 740.00, 3700.00),
(4934, 4911, 9, 5, 1165.00, 5825.00),
(4935, 4911, 8, 5, 1095.00, 5475.00),
(4936, 4911, 12, 5, 1100.00, 5500.00),
(4937, 4911, 16, 5, 395.00, 1975.00),
(4938, 4911, 15, 1, 0.00, 0.00),
(4939, 4911, 13, 1, 0.00, 0.00),
(4940, 4911, 14, 1, 0.00, 0.00),
(4941, 4912, 10, 1, 790.00, 790.00),
(4942, 4913, 9, 1, 1215.00, 1215.00),
(4943, 4914, 8, 2, 1145.00, 2290.00),
(4944, 4915, 11, 1, 445.00, 445.00),
(4945, 4916, 8, 2, 1095.00, 2190.00),
(4946, 4916, 9, 2, 1115.00, 2230.00),
(4947, 4916, 10, 3, 740.00, 2220.00),
(4948, 4917, 11, 1, 445.00, 445.00),
(4949, 4918, 10, 1, 790.00, 790.00),
(4950, 4919, 10, 1, 790.00, 790.00),
(4951, 4920, 9, 1, 1165.00, 1165.00),
(4952, 4921, 8, 10, 1070.00, 10700.00),
(4953, 4921, 12, 10, 1050.00, 10500.00),
(4954, 4921, 13, 1, 0.00, 0.00),
(4955, 4921, 14, 1, 0.00, 0.00),
(4956, 4922, 12, 1, 1100.00, 1100.00),
(4957, 4923, 11, 1, 445.00, 445.00),
(4958, 4924, 8, 1, 1145.00, 1145.00),
(4959, 4924, 10, 1, 790.00, 790.00),
(4960, 4925, 9, 10, 1090.00, 10900.00),
(4961, 4925, 14, 1, 0.00, 0.00),
(4962, 4926, 10, 20, 740.00, 14800.00),
(4963, 4926, 15, 1, 0.00, 0.00),
(4964, 4926, 14, 1, 0.00, 0.00),
(4965, 4926, 12, 20, 1050.00, 21000.00),
(4966, 4926, 13, 1, 0.00, 0.00),
(4967, 4927, 11, 2, 445.00, 890.00),
(4968, 4928, 9, 1, 1165.00, 1165.00),
(4969, 4928, 12, 1, 1100.00, 1100.00),
(4970, 4929, 16, 5, 395.00, 1975.00),
(4971, 4930, 11, 5, 405.00, 2025.00),
(4972, 4931, 9, 2, 1165.00, 2330.00),
(4973, 4932, 16, 1, 435.00, 435.00),
(4974, 4933, 9, 1, 1115.00, 1115.00),
(4975, 4933, 12, 1, 1050.00, 1050.00),
(4976, 4933, 16, 5, 395.00, 1975.00),
(4977, 4934, 11, 1, 445.00, 445.00),
(4978, 4935, 12, 1, 1100.00, 1100.00),
(4979, 4936, 11, 1, 445.00, 445.00),
(4980, 4937, 10, 1, 790.00, 790.00),
(4981, 4938, 12, 1, 1100.00, 1100.00),
(4982, 4939, 10, 2, 740.00, 1480.00),
(4983, 4939, 8, 2, 1095.00, 2190.00),
(4984, 4939, 9, 1, 1115.00, 1115.00),
(4985, 4940, 8, 5, 1095.00, 5475.00),
(4986, 4941, 8, 1, 1145.00, 1145.00),
(4987, 4942, 9, 1, 1165.00, 1165.00),
(4988, 4943, 10, 20, 740.00, 14800.00),
(4989, 4943, 15, 1, 0.00, 0.00),
(4990, 4943, 14, 1, 0.00, 0.00),
(4991, 4943, 9, 6, 1115.00, 6690.00),
(4992, 4944, 9, 5, 1115.00, 5575.00),
(4993, 4944, 12, 2, 1050.00, 2100.00),
(4994, 4944, 8, 1, 1095.00, 1095.00),
(4995, 4945, 9, 10, 1090.00, 10900.00),
(4996, 4945, 14, 1, 0.00, 0.00),
(4997, 4946, 8, 1, 1145.00, 1145.00),
(4998, 4947, 9, 3, 1165.00, 3495.00),
(4999, 4948, 9, 2, 1165.00, 2330.00),
(5000, 4949, 10, 1, 790.00, 790.00),
(5001, 4950, 8, 10, 1070.00, 10700.00),
(5002, 4950, 14, 1, 0.00, 0.00),
(5003, 4951, 12, 10, 1050.00, 10500.00),
(5004, 4951, 13, 1, 0.00, 0.00),
(5005, 4951, 14, 1, 0.00, 0.00),
(5006, 4952, 8, 3, 1095.00, 3285.00),
(5007, 4952, 9, 2, 1115.00, 2230.00),
(5008, 4953, 8, 1, 1145.00, 1145.00),
(5009, 4953, 12, 1, 1100.00, 1100.00),
(5010, 4954, 8, 1, 1145.00, 1145.00),
(5011, 4954, 9, 1, 1165.00, 1165.00),
(5012, 4955, 12, 1, 1100.00, 1100.00),
(5013, 4956, 9, 2, 1165.00, 2330.00),
(5014, 4956, 8, 1, 1145.00, 1145.00),
(5015, 4957, 9, 3, 1165.00, 3495.00),
(5016, 4958, 8, 1, 1145.00, 1145.00),
(5017, 4958, 9, 1, 1165.00, 1165.00),
(5018, 4959, 8, 2, 1145.00, 2290.00),
(5019, 4960, 9, 2, 1165.00, 2330.00),
(5020, 4961, 11, 1, 445.00, 445.00),
(5021, 4962, 8, 1, 1145.00, 1145.00),
(5022, 4963, 10, 1, 790.00, 790.00),
(5023, 4964, 9, 2, 1165.00, 2330.00),
(5024, 4965, 9, 2, 1165.00, 2330.00),
(5025, 4966, 10, 10, 740.00, 7400.00),
(5026, 4966, 15, 1, 0.00, 0.00),
(5027, 4966, 14, 1, 0.00, 0.00),
(5028, 4967, 10, 1, 790.00, 790.00),
(5029, 4968, 9, 6, 1115.00, 6690.00),
(5030, 4969, 9, 5, 1115.00, 5575.00),
(5031, 4970, 16, 1, 435.00, 435.00),
(5032, 4971, 11, 1, 445.00, 445.00),
(5033, 4972, 11, 1, 445.00, 445.00),
(5034, 4973, 9, 1, 1165.00, 1165.00),
(5035, 4974, 9, 1, 1165.00, 1165.00),
(5036, 4974, 10, 1, 790.00, 790.00),
(5037, 4975, 10, 1, 790.00, 790.00),
(5038, 4976, 12, 2, 1100.00, 2200.00),
(5039, 4977, 9, 1, 1165.00, 1165.00),
(5040, 4978, 8, 2, 1145.00, 2290.00),
(5041, 4979, 10, 10, 740.00, 7400.00),
(5042, 4979, 15, 1, 0.00, 0.00),
(5043, 4979, 14, 1, 0.00, 0.00),
(5044, 4980, 10, 10, 740.00, 7400.00),
(5045, 4980, 15, 1, 0.00, 0.00),
(5046, 4980, 14, 1, 0.00, 0.00),
(5047, 4981, 8, 5, 1095.00, 5475.00),
(5048, 4981, 9, 5, 1165.00, 5825.00),
(5049, 4981, 10, 1, 740.00, 740.00),
(5050, 4981, 17, 1, 0.00, 0.00),
(5051, 4981, 14, 1, 0.00, 0.00),
(5052, 4982, 8, 10, 1070.00, 10700.00),
(5053, 4982, 14, 1, 0.00, 0.00),
(5054, 4983, 10, 3, 740.00, 2220.00),
(5055, 4983, 8, 2, 1095.00, 2190.00),
(5056, 4983, 14, 1, 0.00, 0.00),
(5057, 4984, 8, 1, 1145.00, 1145.00),
(5058, 4985, 10, 5, 740.00, 3700.00),
(5059, 4986, 11, 1, 445.00, 445.00),
(5060, 4987, 10, 5, 740.00, 3700.00),
(5061, 4988, 9, 2, 1215.00, 2430.00),
(5062, 4989, 8, 2, 1145.00, 2290.00),
(5063, 4990, 10, 1, 790.00, 790.00),
(5064, 4991, 9, 2, 1215.00, 2430.00),
(5065, 4992, 8, 7, 1095.00, 7665.00),
(5066, 4992, 18, 1, 0.00, 0.00),
(5067, 4993, 9, 1, 1215.00, 1215.00),
(5068, 4993, 8, 1, 1145.00, 1145.00),
(5069, 4994, 8, 2, 1095.00, 2190.00),
(5070, 4994, 9, 2, 1165.00, 2330.00),
(5071, 4994, 10, 1, 740.00, 740.00),
(5072, 4995, 10, 3, 790.00, 2370.00),
(5073, 4996, 9, 10, 1165.00, 11650.00),
(5074, 4996, 13, 1, 0.00, 0.00),
(5075, 4996, 14, 1, 0.00, 0.00),
(5076, 4997, 11, 1, 445.00, 445.00),
(5077, 4998, 11, 1, 445.00, 445.00),
(5078, 4999, 10, 5, 740.00, 3700.00),
(5079, 4999, 8, 2, 1095.00, 2190.00),
(5080, 5000, 9, 1, 1215.00, 1215.00),
(5081, 5001, 8, 1, 1145.00, 1145.00),
(5082, 5002, 9, 1, 1215.00, 1215.00),
(5083, 5003, 9, 1, 1215.00, 1215.00),
(5084, 5004, 10, 1, 790.00, 790.00),
(5085, 5005, 12, 1, 1150.00, 1150.00),
(5086, 5006, 8, 5, 1095.00, 5475.00),
(5087, 5006, 14, 1, 0.00, 0.00),
(5088, 5007, 9, 3, 1215.00, 3645.00),
(5089, 5008, 11, 10, 405.00, 4050.00),
(5090, 5008, 9, 10, 1090.00, 10900.00),
(5091, 5008, 12, 10, 1050.00, 10500.00),
(5092, 5008, 13, 1, 0.00, 0.00),
(5093, 5008, 14, 1, 0.00, 0.00),
(5094, 5009, 11, 1, 445.00, 445.00),
(5095, 5010, 8, 10, 1070.00, 10700.00),
(5096, 5010, 14, 1, 0.00, 0.00),
(5097, 5011, 10, 1, 790.00, 790.00),
(5098, 5012, 11, 2, 445.00, 890.00),
(5099, 5012, 12, 1, 1100.00, 1100.00),
(5100, 5013, 11, 1, 445.00, 445.00),
(5101, 5014, 9, 5, 1115.00, 5575.00),
(5102, 5015, 12, 1, 1050.00, 1050.00),
(5103, 5015, 11, 5, 405.00, 2025.00),
(5104, 5016, 12, 1, 1100.00, 1100.00),
(5105, 5017, 11, 5, 405.00, 2025.00),
(5106, 5017, 12, 1, 1050.00, 1050.00),
(5107, 5018, 9, 5, 1115.00, 5575.00),
(5108, 5019, 8, 1, 1145.00, 1145.00),
(5109, 5020, 8, 1, 1145.00, 1145.00),
(5110, 5021, 10, 1, 790.00, 790.00),
(5111, 5022, 8, 1, 1145.00, 1145.00),
(5112, 5023, 10, 20, 520.00, 10400.00),
(5113, 5023, 10, 20, 520.00, 10400.00),
(5114, 5023, 10, 20, 520.00, 10400.00),
(5115, 5023, 10, 20, 520.00, 10400.00),
(5116, 5023, 14, 1, 0.00, 0.00),
(5117, 5024, 12, 1, 1100.00, 1100.00),
(5118, 5025, 9, 1, 1165.00, 1165.00),
(5119, 5026, 12, 1, 1100.00, 1100.00),
(5120, 5027, 12, 1, 1100.00, 1100.00),
(5121, 5028, 9, 1, 1165.00, 1165.00),
(5122, 5029, 8, 1, 1145.00, 1145.00),
(5123, 5030, 12, 1, 1100.00, 1100.00),
(5124, 5031, 8, 1, 1145.00, 1145.00),
(5125, 5032, 10, 5, 740.00, 3700.00),
(5126, 5033, 11, 1, 445.00, 445.00),
(5127, 5034, 9, 1, 1165.00, 1165.00),
(5128, 5035, 12, 1, 1100.00, 1100.00),
(5129, 5036, 16, 1, 435.00, 435.00),
(5130, 5037, 12, 2, 1100.00, 2200.00),
(5131, 5038, 10, 5, 740.00, 3700.00),
(5132, 5039, 9, 2, 1165.00, 2330.00),
(5133, 5040, 12, 1, 1100.00, 1100.00),
(5134, 5041, 8, 10, 1070.00, 10700.00),
(5135, 5041, 14, 1, 0.00, 0.00),
(5136, 5042, 8, 10, 1070.00, 10700.00),
(5137, 5042, 9, 10, 1090.00, 10900.00),
(5138, 5042, 14, 1, 0.00, 0.00),
(5139, 5043, 11, 10, 405.00, 4050.00),
(5140, 5044, 10, 1, 790.00, 790.00),
(5141, 5045, 11, 1, 445.00, 445.00),
(5142, 5046, 9, 2, 1165.00, 2330.00),
(5143, 5047, 10, 1, 790.00, 790.00),
(5144, 5048, 10, 5, 740.00, 3700.00),
(5145, 5049, 9, 10, 1090.00, 10900.00),
(5146, 5049, 14, 1, 0.00, 0.00),
(5147, 5050, 12, 1, 1100.00, 1100.00),
(5148, 5051, 10, 1, 790.00, 790.00),
(5149, 5051, 12, 1, 1100.00, 1100.00),
(5150, 5052, 8, 1, 1145.00, 1145.00),
(5151, 5052, 11, 1, 445.00, 445.00),
(5152, 5053, 9, 3, 1115.00, 3345.00),
(5153, 5053, 12, 4, 1050.00, 4200.00),
(5154, 5054, 9, 1, 1165.00, 1165.00),
(5155, 5055, 12, 1, 1100.00, 1100.00),
(5156, 5056, 8, 1, 1145.00, 1145.00),
(5157, 5057, 16, 2, 435.00, 870.00),
(5158, 5058, 11, 2, 445.00, 890.00),
(5159, 5059, 9, 5, 1115.00, 5575.00),
(5160, 5059, 14, 1, 0.00, 0.00),
(5161, 5060, 10, 1, 790.00, 790.00),
(5162, 5061, 8, 4, 1145.00, 4580.00),
(5163, 5062, 10, 1, 790.00, 790.00),
(5164, 5063, 8, 2, 1145.00, 2290.00),
(5165, 5063, 9, 2, 1165.00, 2330.00),
(5166, 5064, 12, 1, 1100.00, 1100.00),
(5167, 5065, 9, 4, 1115.00, 4460.00),
(5168, 5065, 10, 1, 740.00, 740.00),
(5169, 5066, 10, 5, 740.00, 3700.00),
(5170, 5067, 16, 1, 435.00, 435.00),
(5171, 5068, 8, 1, 1145.00, 1145.00),
(5172, 5069, 11, 10, 405.00, 4050.00),
(5173, 5069, 14, 1, 0.00, 0.00),
(5174, 5070, 12, 1, 1100.00, 1100.00),
(5175, 5071, 9, 5, 1115.00, 5575.00),
(5176, 5071, 18, 1, 0.00, 0.00),
(5177, 5072, 12, 1, 1100.00, 1100.00),
(5178, 5073, 12, 1, 1100.00, 1100.00),
(5179, 5074, 11, 1, 445.00, 445.00),
(5180, 5075, 9, 5, 1115.00, 5575.00),
(5181, 5076, 12, 2, 1100.00, 2200.00),
(5182, 5077, 11, 2, 445.00, 890.00),
(5183, 5078, 11, 1, 445.00, 445.00),
(5184, 5079, 10, 10, 740.00, 7400.00),
(5185, 5079, 12, 10, 1050.00, 10500.00),
(5186, 5079, 15, 1, 0.00, 0.00),
(5187, 5079, 13, 1, 0.00, 0.00),
(5188, 5079, 14, 1, 0.00, 0.00),
(5189, 5080, 10, 1, 790.00, 790.00),
(5190, 5080, 12, 1, 1100.00, 1100.00),
(5191, 5081, 10, 5, 740.00, 3700.00),
(5192, 5082, 11, 10, 405.00, 4050.00),
(5193, 5082, 14, 1, 0.00, 0.00),
(5194, 5083, 11, 1, 445.00, 445.00),
(5195, 5084, 11, 1, 445.00, 445.00),
(5196, 5085, 8, 1, 1145.00, 1145.00),
(5197, 5086, 22, 1, 540.00, 540.00),
(5198, 5087, 12, 2, 1100.00, 2200.00),
(5199, 5088, 11, 1, 445.00, 445.00),
(5200, 5089, 11, 5, 405.00, 2025.00),
(5201, 5090, 8, 1, 1095.00, 1095.00),
(5202, 5090, 9, 1, 1115.00, 1115.00),
(5203, 5090, 12, 2, 1050.00, 2100.00),
(5204, 5090, 10, 1, 740.00, 740.00),
(5205, 5091, 12, 3, 1100.00, 3300.00),
(5206, 5093, 12, 5, 1050.00, 5250.00),
(5207, 5094, 8, 1, 1145.00, 1145.00),
(5208, 5095, 9, 5, 1115.00, 5575.00),
(5209, 5096, 10, 2, 790.00, 1580.00),
(5210, 5097, 8, 1, 1145.00, 1145.00),
(5211, 5097, 9, 1, 1165.00, 1165.00),
(5212, 5098, 12, 2, 1100.00, 2200.00),
(5213, 5099, 8, 1, 1145.00, 1145.00),
(5214, 5100, 12, 1, 1100.00, 1100.00),
(5215, 5101, 8, 5, 1095.00, 5475.00),
(5216, 5102, 11, 1, 445.00, 445.00),
(5217, 5103, 12, 5, 1050.00, 5250.00),
(5218, 5104, 9, 1, 1165.00, 1165.00),
(5219, 5105, 12, 1, 1100.00, 1100.00),
(5220, 5106, 11, 20, 395.00, 7900.00),
(5221, 5106, 20, 1, 0.00, 0.00),
(5222, 5106, 14, 1, 0.00, 0.00),
(5223, 5107, 9, 1, 1215.00, 1215.00),
(5224, 5108, 10, 1, 790.00, 790.00),
(5225, 5109, 11, 1, 445.00, 445.00),
(5226, 5110, 11, 2, 445.00, 890.00),
(5227, 5111, 8, 2, 1145.00, 2290.00),
(5228, 5112, 12, 2, 1150.00, 2300.00),
(5229, 5113, 8, 2, 1145.00, 2290.00),
(5230, 5114, 11, 1, 445.00, 445.00),
(5231, 5115, 12, 2, 1150.00, 2300.00),
(5232, 5116, 10, 1, 790.00, 790.00),
(5233, 5117, 9, 2, 1215.00, 2430.00),
(5234, 5118, 10, 3, 740.00, 2220.00),
(5235, 5118, 9, 2, 1165.00, 2330.00),
(5236, 5119, 9, 1, 1215.00, 1215.00),
(5237, 5120, 12, 1, 1150.00, 1150.00),
(5238, 5121, 9, 5, 1165.00, 5825.00),
(5239, 5122, 9, 1, 1215.00, 1215.00),
(5240, 5123, 8, 1, 1145.00, 1145.00),
(5241, 5124, 10, 1, 790.00, 790.00),
(5242, 5125, 8, 1, 1145.00, 1145.00),
(5243, 5126, 12, 2, 1150.00, 2300.00),
(5244, 5127, 11, 1, 445.00, 445.00),
(5245, 5128, 10, 20, 740.00, 14800.00),
(5246, 5128, 10, 20, 740.00, 14800.00),
(5247, 5128, 15, 1, 0.00, 0.00),
(5248, 5128, 14, 1, 0.00, 0.00),
(5249, 5128, 18, 1, 0.00, 0.00),
(5250, 5128, 12, 1, 1100.00, 1100.00),
(5251, 5129, 8, 2, 1145.00, 2290.00),
(5252, 5130, 11, 2, 445.00, 890.00),
(5253, 5131, 9, 1, 1215.00, 1215.00),
(5254, 5132, 9, 4, 1165.00, 4660.00),
(5255, 5132, 8, 3, 1095.00, 3285.00),
(5256, 5132, 12, 3, 1100.00, 3300.00),
(5257, 5132, 15, 1, 0.00, 0.00),
(5258, 5132, 18, 1, 0.00, 0.00),
(5259, 5133, 8, 1, 1145.00, 1145.00),
(5260, 5134, 8, 20, 1070.00, 21400.00),
(5261, 5134, 17, 1, 0.00, 0.00),
(5262, 5134, 14, 1, 0.00, 0.00),
(5263, 5135, 10, 5, 740.00, 3700.00),
(5264, 5136, 10, 3, 740.00, 2220.00),
(5265, 5136, 8, 2, 1095.00, 2190.00),
(5266, 5137, 9, 2, 1215.00, 2430.00),
(5267, 5138, 9, 5, 1165.00, 5825.00),
(5268, 5139, 16, 3, 435.00, 1305.00),
(5269, 5140, 12, 2, 1100.00, 2200.00),
(5270, 5141, 9, 1, 1165.00, 1165.00),
(5271, 5142, 10, 1, 790.00, 790.00),
(5272, 5143, 11, 1, 445.00, 445.00),
(5273, 5144, 12, 1, 1100.00, 1100.00),
(5274, 5144, 11, 1, 445.00, 445.00),
(5275, 5145, 11, 1, 445.00, 445.00),
(5276, 5146, 11, 10, 405.00, 4050.00),
(5277, 5146, 14, 1, 0.00, 0.00),
(5278, 5147, 11, 5, 405.00, 2025.00),
(5279, 5148, 11, 5, 405.00, 2025.00),
(5280, 5149, 12, 1, 1100.00, 1100.00),
(5281, 5150, 12, 1, 1100.00, 1100.00),
(5282, 5150, 11, 1, 445.00, 445.00),
(5283, 5151, 12, 5, 1050.00, 5250.00),
(5284, 5152, 10, 5, 740.00, 3700.00),
(5285, 5153, 10, 1, 790.00, 790.00),
(5286, 5154, 9, 5, 1115.00, 5575.00),
(5287, 5155, 22, 2, 490.00, 980.00),
(5288, 5156, 9, 1, 1165.00, 1165.00),
(5289, 5156, 8, 1, 1145.00, 1145.00),
(5290, 5157, 9, 5, 1115.00, 5575.00),
(5291, 5158, 8, 3, 1095.00, 3285.00),
(5292, 5158, 12, 1, 1050.00, 1050.00),
(5293, 5158, 10, 6, 740.00, 4440.00),
(5294, 5158, 14, 1, 0.00, 0.00),
(5295, 5158, 18, 1, 0.00, 0.00),
(5296, 5158, 15, 1, 0.00, 0.00),
(5297, 5159, 8, 5, 1095.00, 5475.00),
(5298, 5160, 9, 1, 1165.00, 1165.00),
(5299, 5161, 10, 2, 790.00, 1580.00),
(5300, 5162, 9, 1, 1215.00, 1215.00),
(5301, 5163, 12, 1, 1150.00, 1150.00),
(5302, 5164, 8, 1, 1145.00, 1145.00),
(5303, 5165, 11, 1, 445.00, 445.00),
(5304, 5166, 11, 1, 445.00, 445.00),
(5305, 5167, 11, 1, 445.00, 445.00),
(5306, 5168, 12, 10, 1100.00, 11000.00),
(5307, 5168, 13, 1, 0.00, 0.00),
(5308, 5168, 14, 1, 0.00, 0.00),
(5309, 5169, 9, 10, 1165.00, 11650.00),
(5310, 5169, 15, 1, 0.00, 0.00),
(5311, 5169, 14, 1, 0.00, 0.00),
(5312, 5170, 23, 2, 345.00, 690.00),
(5313, 5171, 23, 2, 345.00, 690.00),
(5314, 5172, 9, 5, 1165.00, 5825.00),
(5315, 5173, 10, 1, 790.00, 790.00),
(5316, 5174, 23, 2, 345.00, 690.00),
(5317, 5175, 9, 1, 1215.00, 1215.00),
(5318, 5175, 10, 1, 790.00, 790.00),
(5319, 5176, 9, 1, 1215.00, 1215.00),
(5320, 5177, 12, 1, 1150.00, 1150.00),
(5321, 5178, 10, 2, 790.00, 1580.00),
(5322, 5179, 10, 3, 790.00, 2370.00),
(5323, 5180, 11, 1, 445.00, 445.00),
(5324, 5181, 9, 1, 1215.00, 1215.00),
(5325, 5182, 12, 1, 1150.00, 1150.00),
(5326, 5183, 10, 1, 790.00, 790.00),
(5327, 5184, 10, 20, 740.00, 14800.00),
(5328, 5184, 15, 1, 0.00, 0.00),
(5329, 5184, 14, 1, 0.00, 0.00),
(5330, 5184, 18, 1, 0.00, 0.00);

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
(28, '1SOSR025001', 'สิงห์ทอง 25 กก.', 'ปุ๋ยกระสอบเล็ก', '', 'กระสอบ', 0.00, 0.00, 0, 0, '2025-08-07 13:28:18', '2025-08-07 13:28:46'),
(29, 'IMPORT-UNKNOWN', 'ไม่ระบุสินค้า', 'IMPORT', NULL, 'ชิ้น', 0.00, 0.00, 0, 1, '2025-08-14 03:46:07', '2025-08-14 03:46:07');

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
(4, 'telesales', 'Telesales Representative', '[\"customer_management\", \"order_creation\", \"personal_reports\"]', '2025-08-03 07:19:20'),
(5, 'admin_page', 'Admin Page Department', '[\"sales_import\", \"customer_tracking\", \"sales_reports\"]', '2025-08-12 04:43:02');

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
(7, 'waiting_basket_days', '30', 'number', 'Days customers stay in waiting basket', 1, NULL, '2025-08-03 07:19:20'),
(8, 'call_followup_enabled', '1', 'boolean', 'เปิดใช้งานระบบติดตามการโทร', 1, NULL, '2025-08-08 14:25:36'),
(9, 'call_followup_auto_queue', '1', 'boolean', 'สร้างคิวการติดตามอัตโนมัติ', 1, NULL, '2025-08-08 14:25:36'),
(10, 'call_followup_notification', '1', 'boolean', 'แจ้งเตือนการติดตาม', 1, NULL, '2025-08-08 14:25:36'),
(11, 'call_followup_max_days', '30', '', 'จำนวนวันสูงสุดในการติดตาม', 1, NULL, '2025-08-08 14:25:36');

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
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin@prima49.com', '081-234-5678', 1, NULL, NULL, 1, '2025-08-03 07:19:20', '2025-08-14 06:04:34', '2025-08-14 06:04:34'),
(2, 'supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'หัวหน้าทีมขาย', 'supervisor@prima49.com', '081-234-5679', 3, 1, NULL, 1, '2025-08-03 07:19:20', '2025-08-11 06:33:21', '2025-08-11 06:33:21'),
(3, 'telesales1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'พนักงานขาย 1', 'telesales1@prima49.com', '081-234-5680', 4, 1, 2, 1, '2025-08-03 07:19:20', '2025-08-14 06:05:16', '2025-08-14 06:05:16'),
(4, 'telesales2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'พนักงานขาย 2', 'telesales2@prima49.com', '081-234-5681', 4, 1, 2, 1, '2025-08-03 07:19:20', '2025-08-07 05:32:13', NULL),
(5, 'thanu', '$2y$10$6tbVyo5RJdCYGaflQBmnmOkfXlnQx3V8Jg6xaZi2ieaHdfQlLwK76', 'ธนู สุริวงศ์', 'prima.thanu.s@gmail.com', '0952519797', 1, 1, NULL, 1, '2025-08-07 04:25:43', '2025-08-07 04:26:18', '2025-08-07 04:26:18'),
(6, 'gif', '$2y$10$GLCrR7q.uR1seJ1Vm6YcUuMZB9HGi1vSxCUhwzAV9oEY.wedIiIYC', 'กิ๊ฟ Telesale', 'gif-prionic@gmail.com', '-', 4, 2, NULL, 1, '2025-08-07 13:31:30', '2025-08-14 06:05:28', '2025-08-14 06:05:28'),
(7, 'poz', '$2y$10$FCU60hz0YpmGOMPFGK.vR.m7Rc5JQMgVTS6OZ2jaEEoMHxdRRxcWm', 'พลอย Telesale', 'poz_ponic@gmail.com', '', 4, 2, NULL, 1, '2025-08-12 07:11:20', '2025-08-13 07:12:12', '2025-08-13 07:12:12'),
(8, 'mew', '$2y$10$7BRh0DOD4xSRQXZaVguTOO2sGeolYfc1vTleRoDLe1jH7TswJy24m', 'เหมียว Telesale', 'mew-peonic@gmail.com', '', 4, 2, NULL, 1, '2025-08-12 07:12:48', '2025-08-13 07:13:09', '2025-08-13 07:13:09'),
(9, 'ice', '$2y$10$ku0GvZq4l1ks6BG3ihMzC.mPNZNNSfQLEMxS1ph8wN5aB2UAybgGK', 'ไอซ์ Telesale', 'ice-peonic@gmail.com', '-', 4, 2, NULL, 1, '2025-08-12 07:13:48', '2025-08-14 04:38:08', '2025-08-14 04:38:08'),
(10, 'ben', '$2y$10$Iz.ZpNtN9fNFOFV83QaAGuCnyjCP0CnGU2RzI0BxWPVbd08wyGSKa', 'เบญ Admin page', 'ben-peonic@gmail.com', '-', 5, 2, NULL, 1, '2025-08-13 04:38:00', '2025-08-13 04:38:00', NULL),
(11, 'mpp', '$2y$10$C89ZGqRhC9P1wxfQZwbyZu18tbFPb0s5sQspHFOFKrbPnwNTJzoYC', 'ปราย Admin page', 'mpp-peonic@gmail.com', '-', 5, 2, NULL, 1, '2025-08-13 04:40:03', '2025-08-13 04:40:03', NULL),
(12, 'jan', '$2y$10$iZoHgY05lv6m8tkmQWYOXeT6ygbg.Uf4zVfs9RbRw0z3tqiiFy/MO', 'แจนนี่ Adminpage', 'jan-peonic@gmail.com', '-', 5, 2, NULL, 1, '2025-08-13 04:41:32', '2025-08-13 04:41:32', NULL);

-- --------------------------------------------------------

--
-- Structure for view `customer_appointment_extensions`
--
DROP TABLE IF EXISTS `customer_appointment_extensions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`primacom_bloguser`@`localhost` SQL SECURITY DEFINER VIEW `customer_appointment_extensions`  AS SELECT `c`.`customer_id` AS `customer_id`, concat(`c`.`first_name`,' ',`c`.`last_name`) AS `customer_name`, `c`.`customer_grade` AS `customer_grade`, `c`.`temperature_status` AS `temperature_status`, `c`.`appointment_count` AS `appointment_count`, `c`.`appointment_extension_count` AS `appointment_extension_count`, `c`.`max_appointment_extensions` AS `max_appointment_extensions`, `c`.`appointment_extension_expiry` AS `appointment_extension_expiry`, `c`.`appointment_extension_days` AS `appointment_extension_days`, `c`.`last_appointment_date` AS `last_appointment_date`, CASE WHEN `c`.`appointment_extension_expiry` is null THEN 'ไม่มีวันหมดอายุ' WHEN `c`.`appointment_extension_expiry` < current_timestamp() THEN 'หมดอายุแล้ว' ELSE 'ยังไม่หมดอายุ' END AS `expiry_status`, CASE WHEN `c`.`appointment_extension_count` >= `c`.`max_appointment_extensions` THEN 'ไม่สามารถต่อเวลาได้แล้ว' ELSE 'สามารถต่อเวลาได้' END AS `extension_status`, `u`.`username` AS `assigned_user` FROM (`customers` `c` left join `users` `u` on(`c`.`assigned_to` = `u`.`user_id`)) WHERE `c`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `customer_call_followup_list`
--
DROP TABLE IF EXISTS `customer_call_followup_list`;

CREATE ALGORITHM=UNDEFINED DEFINER=`primacom_bloguser`@`localhost` SQL SECURITY DEFINER VIEW `customer_call_followup_list`  AS SELECT `c`.`customer_id` AS `customer_id`, `c`.`customer_code` AS `customer_code`, `c`.`first_name` AS `first_name`, `c`.`last_name` AS `last_name`, `c`.`phone` AS `phone`, `c`.`email` AS `email`, `c`.`province` AS `province`, `c`.`temperature_status` AS `temperature_status`, `c`.`customer_grade` AS `customer_grade`, `c`.`assigned_to` AS `assigned_to`, `u`.`full_name` AS `assigned_to_name`, `cl`.`log_id` AS `call_log_id`, `cl`.`call_result` AS `call_result`, `cl`.`call_status` AS `call_status`, `cl`.`created_at` AS `last_call_date`, `cl`.`next_followup_at` AS `next_followup_at`, `cl`.`followup_notes` AS `followup_notes`, `cl`.`followup_days` AS `followup_days`, `cl`.`followup_priority` AS `followup_priority`, `cfq`.`queue_id` AS `queue_id`, `cfq`.`followup_date` AS `followup_date`, `cfq`.`status` AS `queue_status`, `cfq`.`priority` AS `queue_priority`, to_days(`cl`.`next_followup_at`) - to_days(current_timestamp()) AS `days_until_followup`, CASE WHEN `cl`.`next_followup_at` <= current_timestamp() THEN 'overdue' WHEN `cl`.`next_followup_at` <= current_timestamp() + interval 3 day THEN 'urgent' WHEN `cl`.`next_followup_at` <= current_timestamp() + interval 7 day THEN 'soon' ELSE 'normal' END AS `urgency_status` FROM (((`customers` `c` left join `users` `u` on(`c`.`assigned_to` = `u`.`user_id`)) left join `call_logs` `cl` on(`c`.`customer_id` = `cl`.`customer_id`)) left join `call_followup_queue` `cfq` on(`c`.`customer_id` = `cfq`.`customer_id` and `cfq`.`status` = 'pending')) WHERE `c`.`is_active` = 1 AND `c`.`assigned_to` is not null AND `cl`.`next_followup_at` is not null AND `cl`.`next_followup_at` <= current_timestamp() + interval 30 day AND `cl`.`call_result` in ('not_interested','callback','interested','complaint') ORDER BY `cl`.`next_followup_at` ASC, `cl`.`followup_priority` DESC ;

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
-- Indexes for table `call_followup_queue`
--
ALTER TABLE `call_followup_queue`
  ADD PRIMARY KEY (`queue_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_followup_date` (`followup_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `call_log_id` (`call_log_id`);

--
-- Indexes for table `call_followup_rules`
--
ALTER TABLE `call_followup_rules`
  ADD PRIMARY KEY (`rule_id`),
  ADD UNIQUE KEY `unique_call_result` (`call_result`);

--
-- Indexes for table `call_logs`
--
ALTER TABLE `call_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_call_date` (`created_at`),
  ADD KEY `idx_next_followup` (`next_followup_at`),
  ADD KEY `idx_call_logs_customer_followup` (`customer_id`,`next_followup_at`),
  ADD KEY `idx_call_logs_result_followup` (`call_result`,`next_followup_at`);

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
  ADD KEY `idx_assigned_at` (`assigned_at`),
  ADD KEY `idx_customers_status_followup` (`customer_status`,`next_followup_at`);

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
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `appointment_activities`
--
ALTER TABLE `appointment_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

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
-- AUTO_INCREMENT for table `call_followup_queue`
--
ALTER TABLE `call_followup_queue`
  MODIFY `queue_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `call_followup_rules`
--
ALTER TABLE `call_followup_rules`
  MODIFY `rule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `call_logs`
--
ALTER TABLE `call_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3623;

--
-- AUTO_INCREMENT for table `customer_activities`
--
ALTER TABLE `customer_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

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
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5188;

--
-- AUTO_INCREMENT for table `order_activities`
--
ALTER TABLE `order_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5337;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sales_history`
--
ALTER TABLE `sales_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
-- Constraints for table `call_followup_queue`
--
ALTER TABLE `call_followup_queue`
  ADD CONSTRAINT `call_followup_queue_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `call_followup_queue_ibfk_2` FOREIGN KEY (`call_log_id`) REFERENCES `call_logs` (`log_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `call_followup_queue_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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
