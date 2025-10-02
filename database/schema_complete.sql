-- ConstructLink™ Complete Database Schema
-- Enhanced Asset and Inventory Management System for V CUTAMORA CONSTRUCTION INC.
-- Includes full procurement workflow, project assignments, and vendor enhancements

-- Set SQL mode compatible with MySQL 8.0+
SET SQL_MODE = "STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: constructlink_db
CREATE DATABASE IF NOT EXISTS `constructlink_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `constructlink_db`;

-- --------------------------------------------------------

-- Table structure for table `roles`
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `permissions` json,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert enhanced roles with complete permissions
INSERT INTO `roles` (`id`, `name`, `description`, `permissions`) VALUES
(1, 'System Admin', 'Full system access and administration', '["view_all_assets", "edit_assets", "delete_assets", "approve_transfers", "approve_disposal", "manage_users", "view_reports", "manage_procurement", "release_assets", "receive_assets", "manage_maintenance", "manage_incidents", "manage_master_data", "system_administration", "manage_withdrawals", "request_withdrawals", "view_financial_data", "approve_procurement", "manage_requests", "assign_projects", "view_all_projects"]'),
(2, 'Finance Director', 'Financial oversight and approval authority', '["view_all_assets", "approve_disposal", "view_reports", "view_financial_data", "approve_high_value_transfers", "view_project_assets", "approve_procurement", "view_procurement_reports", "view_all_projects"]'),
(3, 'Asset Director', 'Asset management and oversight', '["view_all_assets", "edit_assets", "approve_transfers", "view_reports", "manage_maintenance", "manage_incidents", "flag_idle_assets", "manage_withdrawals", "release_assets", "receive_assets", "request_withdrawals", "view_project_assets", "view_all_projects"]'),
(4, 'Procurement Officer', 'Procurement and vendor management', '["view_all_assets", "manage_procurement", "receive_assets", "manage_vendors", "manage_makers", "view_procurement_reports", "view_project_assets", "create_procurement", "manage_requests", "view_all_projects"]'),
(5, 'Warehouseman', 'Warehouse operations and asset handling', '["view_project_assets", "release_assets", "receive_assets", "manage_withdrawals", "basic_asset_logs", "manage_borrowed_tools", "request_withdrawals", "view_all_assets", "receive_procurement"]'),
(6, 'Project Manager', 'Project-level asset management', '["view_project_assets", "request_withdrawals", "approve_site_actions", "initiate_transfers", "manage_incidents", "view_project_reports", "receive_assets", "manage_withdrawals", "create_requests", "approve_requests"]'),
(7, 'Site Inventory Clerk', 'Site-level inventory operations', '["view_project_assets", "request_withdrawals", "scan_qr_codes", "log_borrower_info", "manage_incidents", "view_site_assets", "manage_borrowed_tools", "create_requests"]');

-- --------------------------------------------------------

-- Table structure for table `projects`
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(20) NOT NULL,
  `location` text,
  `description` text,
  `start_date` date,
  `end_date` date,
  `budget` decimal(15,2) DEFAULT NULL,
  `project_manager_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `is_active` (`is_active`),
  KEY `project_manager_id` (`project_manager_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `users` (Enhanced with project assignment)
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100),
  `phone` varchar(20),
  `department` varchar(100),
  `current_project_id` int(11) DEFAULT NULL COMMENT 'Current assigned project',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  KEY `current_project_id` (`current_project_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`current_project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`id`, `username`, `password_hash`, `role_id`, `full_name`, `email`, `failed_login_attempts`, `locked_until`) VALUES
(1, 'admin', '$2y$12$Pfr9Pdlc7uoMpSsUzepfh.RKpvsRCQetNMpDt6JshXxt3qTaaHx7i', 1, 'System Administrator', 'admin@constructlink.com', 0, NULL);

-- Add foreign key constraint for projects table after users table is created
ALTER TABLE `projects` ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`project_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- --------------------------------------------------------

-- Table structure for table `user_projects` (Project assignment tracking)
CREATE TABLE `user_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `project_id` (`project_id`),
  KEY `assigned_by` (`assigned_by`),
  CONSTRAINT `user_projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_projects_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_projects_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `user_project_logs` (Project assignment history)
CREATE TABLE `user_project_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `old_project_id` int(11) DEFAULT NULL,
  `new_project_id` int(11) DEFAULT NULL,
  `action` enum('assigned','reassigned','unassigned') NOT NULL,
  `reason` text,
  `changed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `old_project_id` (`old_project_id`),
  KEY `new_project_id` (`new_project_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `user_project_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_project_logs_ibfk_2` FOREIGN KEY (`old_project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_project_logs_ibfk_3` FOREIGN KEY (`new_project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_project_logs_ibfk_4` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `user_sessions`
CREATE TABLE `user_sessions` (
  `id` varchar(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45),
  `user_agent` text,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `last_activity` (`last_activity`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `user_login_logs` (Login history tracking)
CREATE TABLE `user_login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45),
  `user_agent` text,
  `success` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `login_time` (`login_time`),
  CONSTRAINT `user_login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `categories`
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `is_consumable` tinyint(1) NOT NULL DEFAULT 0,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `is_consumable` (`is_consumable`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `payment_terms`
CREATE TABLE `payment_terms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `term_name` varchar(100) NOT NULL,
  `description` text,
  `days` int(11) DEFAULT NULL COMMENT 'Payment due in days',
  `percentage_upfront` decimal(5,2) DEFAULT NULL COMMENT 'Percentage required upfront',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `term_name` (`term_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert enhanced payment terms
INSERT INTO `payment_terms` (`term_name`, `description`, `days`, `percentage_upfront`) VALUES
('Cash on Delivery (COD)', 'Payment due upon delivery of goods/services', 0, 100.00),
('Net 7', 'Payment due within 7 days of invoice date', 7, 0.00),
('Net 15', 'Payment due within 15 days of invoice date', 15, 0.00),
('Net 30', 'Payment due within 30 days of invoice date', 30, 0.00),
('50/50 Payment', '50% downpayment, 50% balance upon completion', 0, 50.00),
('30/70 Payment', '30% downpayment, 70% balance upon completion', 0, 30.00),
('Progress Billing', 'Payment based on project milestones', NULL, NULL),
('Post-Dated Check', 'Payment via post-dated checks', NULL, NULL),
('Bank Transfer upon Delivery', 'Bank transfer payment upon delivery confirmation', 0, 0.00);

-- --------------------------------------------------------

-- Table structure for table `vendors` (Enhanced)
CREATE TABLE `vendors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `contact_info` text,
  `address` text,
  `phone` varchar(50),
  `email` varchar(100),
  `contact_person` varchar(100),
  `payment_terms_id` int(11) DEFAULT NULL,
  `categories` json DEFAULT NULL COMMENT 'Vendor specializations/categories',
  `tax_id` varchar(50) DEFAULT NULL,
  `is_preferred` tinyint(1) NOT NULL DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL COMMENT 'Vendor rating out of 5.00',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `payment_terms_id` (`payment_terms_id`),
  KEY `is_preferred` (`is_preferred`),
  CONSTRAINT `vendors_ibfk_payment_terms` FOREIGN KEY (`payment_terms_id`) REFERENCES `payment_terms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `vendor_banks`
CREATE TABLE `vendor_banks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_number` varchar(100) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_type` enum('Checking','Savings','Corporate','Current') NOT NULL DEFAULT 'Checking',
  `currency` enum('PHP','USD','EUR','JPY') NOT NULL DEFAULT 'PHP',
  `bank_category` enum('Primary','Alternate','For International','Emergency') NOT NULL DEFAULT 'Primary',
  `swift_code` varchar(20) DEFAULT NULL,
  `branch` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `vendor_banks_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `vendor_categories` (Vendor specialization tags)
CREATE TABLE `vendor_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert vendor categories
INSERT INTO `vendor_categories` (`name`, `description`) VALUES
('Tools', 'Hand tools, power tools, and equipment'),
('Materials', 'Construction materials and supplies'),
('Equipment', 'Heavy machinery and equipment'),
('Services', 'Professional and technical services'),
('Safety', 'Safety equipment and supplies'),
('Electrical', 'Electrical components and systems'),
('Plumbing', 'Plumbing fixtures and supplies'),
('Hardware', 'General hardware and fasteners');

-- --------------------------------------------------------

-- Table structure for table `vendor_category_assignments`
CREATE TABLE `vendor_category_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_category_unique` (`vendor_id`, `category_id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `vendor_category_assignments_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_category_assignments_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `vendor_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `makers`
CREATE TABLE `makers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `country` varchar(100),
  `website` varchar(255),
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `clients`
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `contact_info` text,
  `address` text,
  `phone` varchar(50),
  `email` varchar(100),
  `contact_person` varchar(100),
  `company_type` varchar(100),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `requests` (Enhanced Material/Tool Request Module)
CREATE TABLE `requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `request_type` enum('Material','Tool','Equipment','Service','Petty Cash','Other') NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `urgency` enum('Normal','Urgent','Critical') NOT NULL DEFAULT 'Normal',
  `date_needed` date DEFAULT NULL,
  `status` enum('Draft','Submitted','Reviewed','Forwarded','Approved','Declined','Procured','Fulfilled') NOT NULL DEFAULT 'Draft',
  `requested_by` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `remarks` text,
  `estimated_cost` decimal(15,2) DEFAULT NULL,
  `actual_cost` decimal(15,2) DEFAULT NULL,
  `procurement_id` int(11) DEFAULT NULL,
  `priority_score` int(11) DEFAULT 0 COMMENT 'System calculated priority',
  `attachments` json DEFAULT NULL COMMENT 'File attachments',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `requested_by` (`requested_by`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `approved_by` (`approved_by`),
  KEY `procurement_id` (`procurement_id`),
  KEY `status` (`status`),
  KEY `urgency` (`urgency`),
  CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requests_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requests_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `request_logs`
CREATE TABLE `request_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `request_logs_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `request_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `procurement_orders` (Multi-item procurement header)
CREATE TABLE `procurement_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'Procurement order title/description',
  `quote_file` varchar(255) DEFAULT NULL,
  `delivery_status` enum('Pending','Scheduled','In Transit','Delivered','Received','Partial') DEFAULT 'Pending',
  `delivery_date` date DEFAULT NULL,
  `scheduled_delivery_date` date DEFAULT NULL COMMENT 'Scheduled delivery date by procurement officer',
  `actual_delivery_date` date DEFAULT NULL COMMENT 'Actual delivery date',
  `delivery_method` enum('Pickup','Direct Delivery','Batch Delivery','Airfreight','Bus Cargo','Courier','Other') DEFAULT 'Direct Delivery',
  `delivery_location` varchar(255) DEFAULT NULL COMMENT 'Delivery location (warehouse, project site, etc.)',
  `tracking_number` varchar(100) DEFAULT NULL COMMENT 'Tracking number for shipment',
  `delivery_notes` text COMMENT 'Delivery instructions and notes',
  `package_scope` text DEFAULT NULL,
  `work_breakdown` text DEFAULT NULL,
  `budget_allocation` decimal(15,2) DEFAULT NULL,
  `justification` text,
  `subtotal` decimal(15,2) DEFAULT 0.00 COMMENT 'Sum of all items subtotal',
  `vat_rate` decimal(5,2) DEFAULT 12.00,
  `vat_amount` decimal(15,2) DEFAULT 0.00 COMMENT 'Calculated VAT amount',
  `ewt_rate` decimal(5,2) DEFAULT 2.00,
  `ewt_amount` decimal(15,2) DEFAULT 0.00 COMMENT 'Calculated EWT amount',
  `handling_fee` decimal(15,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `net_total` decimal(15,2) DEFAULT 0.00 COMMENT 'Final total amount',
  `status` enum('Draft','Pending','Reviewed','For Revision','Approved','Rejected','Scheduled for Delivery','In Transit','Delivered','Received') NOT NULL DEFAULT 'Draft',
  `requested_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `scheduled_by` int(11) DEFAULT NULL COMMENT 'Procurement officer who scheduled delivery',
  `delivered_by` int(11) DEFAULT NULL COMMENT 'Person who confirmed delivery',
  `received_by` int(11) DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL COMMENT 'When delivery was scheduled',
  `delivered_at` timestamp NULL DEFAULT NULL COMMENT 'When delivery was confirmed',
  `date_needed` date DEFAULT NULL,
  `notes` text,
  `quality_check_notes` text,
  `delivery_discrepancy_notes` text COMMENT 'Notes about delivery discrepancies',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `request_id` (`request_id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `project_id` (`project_id`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`),
  KEY `scheduled_by` (`scheduled_by`),
  KEY `delivered_by` (`delivered_by`),
  KEY `received_by` (`received_by`),
  KEY `status` (`status`),
  KEY `delivery_status` (`delivery_status`),
  KEY `tracking_number` (`tracking_number`),
  CONSTRAINT `procurement_orders_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
  CONSTRAINT `procurement_orders_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `procurement_orders_ibfk_3` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  CONSTRAINT `procurement_orders_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procurement_orders_ibfk_5` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procurement_orders_ibfk_6` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procurement_orders_ibfk_7` FOREIGN KEY (`scheduled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procurement_orders_ibfk_8` FOREIGN KEY (`delivered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `procurement_items` (Individual items in procurement order)
CREATE TABLE `procurement_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `procurement_order_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text,
  `specifications` text,
  `model` varchar(100),
  `brand` varchar(100),
  `category_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(50) DEFAULT 'pcs',
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `delivery_status` enum('Pending','Partial','Complete') DEFAULT 'Pending',
  `quantity_received` int(11) DEFAULT 0,
  `quality_notes` text,
  `item_notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `procurement_order_id` (`procurement_order_id`),
  KEY `category_id` (`category_id`),
  KEY `delivery_status` (`delivery_status`),
  CONSTRAINT `procurement_items_ibfk_1` FOREIGN KEY (`procurement_order_id`) REFERENCES `procurement_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `procurement_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- --------------------------------------------------------

-- --------------------------------------------------------

-- Table structure for table `delivery_tracking`
CREATE TABLE `delivery_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `procurement_order_id` int(11) NOT NULL,
  `status` enum('Scheduled','In Transit','Delivered','Received','Discrepancy Reported','Resolved') NOT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `delivery_method` enum('Pickup','Direct Delivery','Batch Delivery','Airfreight','Bus Cargo','Courier','Other') DEFAULT NULL,
  `delivery_location` varchar(255) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `actual_date` date DEFAULT NULL,
  `updated_by` int(11) NOT NULL,
  `notes` text,
  `discrepancy_type` enum('Missing Items','Damaged Items','Wrong Items','Quantity Mismatch','Quality Issues','Other') DEFAULT NULL,
  `discrepancy_details` text,
  `resolution_notes` text,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `procurement_order_id` (`procurement_order_id`),
  KEY `updated_by` (`updated_by`),
  KEY `resolved_by` (`resolved_by`),
  KEY `status` (`status`),
  KEY `tracking_number` (`tracking_number`),
  CONSTRAINT `delivery_tracking_ibfk_1` FOREIGN KEY (`procurement_order_id`) REFERENCES `procurement_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `delivery_tracking_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`),
  CONSTRAINT `delivery_tracking_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `procurement_logs`
CREATE TABLE `procurement_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `procurement_order_id` int(11) NOT NULL COMMENT 'Multi-item procurement order ID',
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `procurement_order_id` (`procurement_order_id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `procurement_logs_ibfk_1` FOREIGN KEY (`procurement_order_id`) REFERENCES `procurement_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `procurement_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `assets` (Enhanced with procurement linkage and quantity support)
CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(50) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text,
  `project_id` int(11) NOT NULL,
  `maker_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `procurement_order_id` int(11) DEFAULT NULL COMMENT 'Link to multi-item procurement order',
  `procurement_item_id` int(11) DEFAULT NULL COMMENT 'Link to specific procurement item',
  `acquired_date` date NOT NULL,
  `status` enum('available','in_use','borrowed','under_maintenance','retired','disposed') NOT NULL DEFAULT 'available',
  `is_client_supplied` tinyint(1) NOT NULL DEFAULT 0,
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'Quantity for consumable items, defaults to 1 for non-consumable',
  `available_quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'Available quantity for consumable items',
  `acquisition_cost` decimal(15,2) DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL COMMENT 'Individual unit cost from procurement',
  `serial_number` varchar(100),
  `model` varchar(100),
  `specifications` text,
  `warranty_expiry` date DEFAULT NULL,
  `qr_code` varchar(255),
  `location` varchar(255) DEFAULT NULL,
  `condition_notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ref` (`ref`),
  KEY `category_id` (`category_id`),
  KEY `project_id` (`project_id`),
  KEY `maker_id` (`maker_id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `client_id` (`client_id`),
  KEY `procurement_order_id` (`procurement_order_id`),
  KEY `procurement_item_id` (`procurement_item_id`),
  KEY `status` (`status`),
  KEY `is_client_supplied` (`is_client_supplied`),
  KEY `quantity` (`quantity`),
  KEY `available_quantity` (`available_quantity`),
  CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `assets_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `assets_ibfk_3` FOREIGN KEY (`maker_id`) REFERENCES `makers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assets_ibfk_4` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assets_ibfk_5` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assets_ibfk_6` FOREIGN KEY (`procurement_order_id`) REFERENCES `procurement_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assets_ibfk_7` FOREIGN KEY (`procurement_item_id`) REFERENCES `procurement_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `procurement_assets` (Link procurement to generated assets)
CREATE TABLE `procurement_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `procurement_order_id` int(11) NOT NULL COMMENT 'Multi-item procurement order ID',
  `procurement_item_id` int(11) NOT NULL COMMENT 'Specific procurement item ID',
  `asset_id` int(11) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `quantity_generated` int(11) DEFAULT 1 COMMENT 'Number of assets generated from this procurement item',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `procurement_order_id` (`procurement_order_id`),
  KEY `procurement_item_id` (`procurement_item_id`),
  KEY `asset_id` (`asset_id`),
  CONSTRAINT `procurement_assets_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `procurement_assets_ibfk_2` FOREIGN KEY (`procurement_order_id`) REFERENCES `procurement_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `procurement_assets_ibfk_3` FOREIGN KEY (`procurement_item_id`) REFERENCES `procurement_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `withdrawals`
CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `purpose` text NOT NULL,
  `withdrawn_by` int(11) NOT NULL,
  `receiver_name` varchar(100) NOT NULL,
  `expected_return` date DEFAULT NULL,
  `actual_return` date DEFAULT NULL,
  `status` enum('pending','released','returned','canceled') NOT NULL DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `project_id` (`project_id`),
  KEY `withdrawn_by` (`withdrawn_by`),
  KEY `status` (`status`),
  CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `withdrawals_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `withdrawals_ibfk_3` FOREIGN KEY (`withdrawn_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `releases`
CREATE TABLE `releases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `withdrawal_id` int(11) NOT NULL,
  `released_by` int(11) NOT NULL,
  `release_doc` text,
  `notes` text,
  `released_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `withdrawal_id` (`withdrawal_id`),
  KEY `released_by` (`released_by`),
  CONSTRAINT `releases_ibfk_1` FOREIGN KEY (`withdrawal_id`) REFERENCES `withdrawals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `releases_ibfk_2` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Migration: Update withdrawals table for MVA workflow
-- Date: 2024-01-XX
-- Description: Update withdrawals table to support Maker-Verifier-Authorizer workflow

-- Update status enum to support MVA workflow
ALTER TABLE `withdrawals` 
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','Released','Returned','Canceled') NOT NULL DEFAULT 'Pending Verification';

-- Add new fields for MVA workflow
ALTER TABLE `withdrawals` 
ADD COLUMN `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'Quantity being withdrawn' AFTER `receiver_name`,
ADD COLUMN `unit` varchar(50) DEFAULT 'pcs' COMMENT 'Unit of measurement' AFTER `quantity`,
ADD COLUMN `verified_by` int(11) DEFAULT NULL AFTER `withdrawn_by`,
ADD COLUMN `verification_date` timestamp NULL DEFAULT NULL AFTER `verified_by`,
ADD COLUMN `approved_by` int(11) DEFAULT NULL AFTER `verification_date`,
ADD COLUMN `approval_date` timestamp NULL DEFAULT NULL AFTER `approved_by`,
ADD COLUMN `released_by` int(11) DEFAULT NULL AFTER `approval_date`,
ADD COLUMN `release_date` timestamp NULL DEFAULT NULL AFTER `released_by`,
ADD COLUMN `returned_by` int(11) DEFAULT NULL AFTER `release_date`,
ADD COLUMN `return_date` timestamp NULL DEFAULT NULL AFTER `returned_by`;

-- Add foreign key constraints for new fields
ALTER TABLE `withdrawals` 
ADD CONSTRAINT `withdrawals_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `withdrawals_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `withdrawals_ibfk_6` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `withdrawals_ibfk_7` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add indexes for new fields
ALTER TABLE `withdrawals` 
ADD KEY `quantity` (`quantity`),
ADD KEY `verified_by` (`verified_by`),
ADD KEY `approved_by` (`approved_by`),
ADD KEY `released_by` (`released_by`),
ADD KEY `returned_by` (`returned_by`),
ADD KEY `verification_date` (`verification_date`),
ADD KEY `approval_date` (`approval_date`),
ADD KEY `release_date` (`release_date`),
ADD KEY `return_date` (`return_date`);

-- Update existing withdrawals to use new status values
UPDATE `withdrawals` SET `status` = 'Pending Verification' WHERE `status` = 'pending';
UPDATE `withdrawals` SET `status` = 'Released' WHERE `status` = 'released';
UPDATE `withdrawals` SET `status` = 'Returned' WHERE `status` = 'returned';
UPDATE `withdrawals` SET `status` = 'Canceled' WHERE `status` = 'canceled';

-- Update the status enum to remove old values
ALTER TABLE `withdrawals` 
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','Released','Returned','Canceled') NOT NULL DEFAULT 'Pending Verification';

-- Table structure for table `transfers`
CREATE TABLE `transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `from_project` int(11) NOT NULL,
  `to_project` int(11) NOT NULL,
  `reason` text NOT NULL,
  `initiated_by` int(11) NOT NULL,
  `transfer_type` enum('temporary','permanent') NOT NULL DEFAULT 'permanent',
  `approved_by` int(11) DEFAULT NULL,
  `transfer_date` date NOT NULL,
  `expected_return` date DEFAULT NULL,
  `actual_return` date DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','completed','canceled') NOT NULL DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `from_project` (`from_project`),
  KEY `to_project` (`to_project`),
  KEY `initiated_by` (`initiated_by`),
  KEY `approved_by` (`approved_by`),
  KEY `status` (`status`),
  KEY `expected_return` (`expected_return`),
  CONSTRAINT `transfers_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `transfers_ibfk_2` FOREIGN KEY (`from_project`) REFERENCES `projects` (`id`),
  CONSTRAINT `transfers_ibfk_3` FOREIGN KEY (`to_project`) REFERENCES `projects` (`id`),
  CONSTRAINT `transfers_ibfk_4` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`),
  CONSTRAINT `transfers_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Migration: Update transfers table for MVA workflow
-- Date: 2024-01-XX
-- Description: Update transfers table to support Maker-Verifier-Authorizer workflow

-- Update status enum to support MVA workflow
ALTER TABLE `transfers` 
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','Received','Completed','Canceled') NOT NULL DEFAULT 'Pending Verification';

-- Add new fields for MVA workflow
ALTER TABLE `transfers` 
ADD COLUMN `verified_by` int(11) DEFAULT NULL AFTER `approved_by`,
ADD COLUMN `verification_date` timestamp NULL DEFAULT NULL AFTER `approval_date`,
ADD COLUMN `received_by` int(11) DEFAULT NULL AFTER `verified_by`,
ADD COLUMN `receipt_date` timestamp NULL DEFAULT NULL AFTER `verification_date`,
ADD COLUMN `completed_by` int(11) DEFAULT NULL AFTER `received_by`,
ADD COLUMN `completion_date` timestamp NULL DEFAULT NULL AFTER `receipt_date`;

-- Add foreign key constraints for new fields
ALTER TABLE `transfers` 
ADD CONSTRAINT `transfers_ibfk_6` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `transfers_ibfk_7` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `transfers_ibfk_8` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add indexes for new fields
ALTER TABLE `transfers` 
ADD KEY `verified_by` (`verified_by`),
ADD KEY `received_by` (`received_by`),
ADD KEY `completed_by` (`completed_by`),
ADD KEY `verification_date` (`verification_date`),
ADD KEY `receipt_date` (`receipt_date`),
ADD KEY `completion_date` (`completion_date`);

-- Update existing transfers to use new status values
UPDATE `transfers` SET `status` = 'Pending Verification' WHERE `status` = 'pending';
UPDATE `transfers` SET `status` = 'Approved' WHERE `status` = 'approved';
UPDATE `transfers` SET `status` = 'Completed' WHERE `status` = 'completed';
UPDATE `transfers` SET `status` = 'Canceled' WHERE `status` = 'canceled';

-- Update the status enum to remove old values
ALTER TABLE `transfers` 
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','Received','Completed','Canceled') NOT NULL DEFAULT 'Pending Verification';

-- Table structure for table `borrowed_tools`
CREATE TABLE `borrowed_tools` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `borrower_name` varchar(100) NOT NULL,
  `borrower_contact` varchar(50),
  `expected_return` date NOT NULL,
  `actual_return` date DEFAULT NULL,
  `issued_by` int(11) NOT NULL,
  `purpose` text,
  `condition_out` text,
  `condition_in` text,
  `status` enum('borrowed','returned','overdue') NOT NULL DEFAULT 'borrowed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `issued_by` (`issued_by`),
  KEY `status` (`status`),
  CONSTRAINT `borrowed_tools_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `borrowed_tools_ibfk_2` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Migration: Update borrowed_tools table for MVA workflow
-- Date: 2024-01-XX
-- Description: Update borrowed_tools table to support Maker-Verifier-Authorizer workflow

-- Update status enum to support MVA workflow
ALTER TABLE `borrowed_tools` 
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','Borrowed','Returned','Overdue','Canceled') NOT NULL DEFAULT 'Pending Verification';

-- Add new fields for MVA workflow
ALTER TABLE `borrowed_tools` 
ADD COLUMN `verified_by` int(11) DEFAULT NULL AFTER `issued_by`,
ADD COLUMN `verification_date` timestamp NULL DEFAULT NULL AFTER `verified_by`,
ADD COLUMN `approved_by` int(11) DEFAULT NULL AFTER `verification_date`,
ADD COLUMN `approval_date` timestamp NULL DEFAULT NULL AFTER `approved_by`,
ADD COLUMN `borrowed_by` int(11) DEFAULT NULL AFTER `approval_date`,
ADD COLUMN `borrowed_date` timestamp NULL DEFAULT NULL AFTER `borrowed_by`,
ADD COLUMN `returned_by` int(11) DEFAULT NULL AFTER `borrowed_date`,
ADD COLUMN `return_date` timestamp NULL DEFAULT NULL AFTER `returned_by`;

-- Add foreign key constraints for new fields
ALTER TABLE `borrowed_tools` 
ADD CONSTRAINT `borrowed_tools_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `borrowed_tools_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `borrowed_tools_ibfk_5` FOREIGN KEY (`borrowed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `borrowed_tools_ibfk_6` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add indexes for new fields
ALTER TABLE `borrowed_tools` 
ADD KEY `verified_by` (`verified_by`),
ADD KEY `approved_by` (`approved_by`),
ADD KEY `borrowed_by` (`borrowed_by`),
ADD KEY `returned_by` (`returned_by`),
ADD KEY `verification_date` (`verification_date`),
ADD KEY `approval_date` (`approval_date`),
ADD KEY `borrowed_date` (`borrowed_date`),
ADD KEY `return_date` (`return_date`);

-- Update existing borrowed_tools to use new status values
UPDATE `borrowed_tools` SET `status` = 'Borrowed' WHERE `status` = 'borrowed';
UPDATE `borrowed_tools` SET `status` = 'Returned' WHERE `status` = 'returned';
UPDATE `borrowed_tools` SET `status` = 'Overdue' WHERE `status` = 'overdue';

-- Update the status enum to remove old values
ALTER TABLE `borrowed_tools` 
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','Borrowed','Returned','Overdue','Canceled') NOT NULL DEFAULT 'Pending Verification';

-- Table structure for table `incidents`
CREATE TABLE `incidents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `reported_by` int(11) NOT NULL,
  `type` enum('lost','damaged','stolen','other') NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `description` text NOT NULL,
  `location` varchar(200),
  `witnesses` text,
  `date_reported` date NOT NULL,
  `status` enum('under_investigation','verified','resolved','closed') NOT NULL DEFAULT 'under_investigation',
  `resolution_notes` text,
  `resolved_by` int(11) DEFAULT NULL,
  `resolution_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `reported_by` (`reported_by`),
  KEY `resolved_by` (`resolved_by`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  CONSTRAINT `incidents_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `incidents_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`),
  CONSTRAINT `incidents_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Migration: Add MVA Workflow Support to Incidents Table
-- Date: 2024-01-XX
-- Description: Update incidents table to support Maker-Verifier-Authorizer workflow

-- Backup existing data
CREATE TABLE incidents_backup AS SELECT * FROM incidents;

-- Update status enum to support MVA workflow
ALTER TABLE incidents 
MODIFY COLUMN status ENUM(
    'Pending Verification',    -- Maker step completed, waiting for Verifier
    'Pending Authorization',   -- Verifier step completed, waiting for Authorizer  
    'Authorized',             -- Authorizer step completed, ready for resolution
    'Resolved',               -- Incident has been resolved
    'Closed',                 -- Incident is closed (final step)
    'Canceled'                -- Incident was canceled at any stage
) NOT NULL DEFAULT 'Pending Verification';

-- Add MVA workflow tracking fields
ALTER TABLE incidents 
ADD COLUMN verified_by INT(11) DEFAULT NULL AFTER resolved_by,
ADD COLUMN verification_date TIMESTAMP NULL DEFAULT NULL AFTER verified_by,
ADD COLUMN authorized_by INT(11) DEFAULT NULL AFTER verification_date,
ADD COLUMN authorization_date TIMESTAMP NULL DEFAULT NULL AFTER authorized_by,
ADD COLUMN closed_by INT(11) DEFAULT NULL AFTER authorization_date,
ADD COLUMN closure_date TIMESTAMP NULL DEFAULT NULL AFTER closed_by,
ADD COLUMN closure_notes TEXT AFTER closure_date,
ADD COLUMN canceled_by INT(11) DEFAULT NULL AFTER closure_notes,
ADD COLUMN cancellation_date TIMESTAMP NULL DEFAULT NULL AFTER canceled_by,
ADD COLUMN cancellation_reason TEXT AFTER cancellation_date;

-- Add foreign key constraints for workflow fields
ALTER TABLE incidents 
ADD CONSTRAINT incidents_ibfk_4 FOREIGN KEY (verified_by) REFERENCES users (id) ON DELETE SET NULL,
ADD CONSTRAINT incidents_ibfk_5 FOREIGN KEY (authorized_by) REFERENCES users (id) ON DELETE SET NULL,
ADD CONSTRAINT incidents_ibfk_6 FOREIGN KEY (closed_by) REFERENCES users (id) ON DELETE SET NULL,
ADD CONSTRAINT incidents_ibfk_7 FOREIGN KEY (canceled_by) REFERENCES users (id) ON DELETE SET NULL;

-- Add indexes for workflow fields
CREATE INDEX idx_incidents_verified_by ON incidents (verified_by);
CREATE INDEX idx_incidents_authorized_by ON incidents (authorized_by);
CREATE INDEX idx_incidents_closed_by ON incidents (closed_by);
CREATE INDEX idx_incidents_canceled_by ON incidents (canceled_by);
CREATE INDEX idx_incidents_status_workflow ON incidents (status, verified_by, authorized_by);

-- Update existing records to use new statuses
-- Convert legacy statuses to new MVA statuses
UPDATE incidents SET status = 'Pending Verification' WHERE status = 'under_investigation';
UPDATE incidents SET status = 'Pending Authorization' WHERE status = 'verified';
UPDATE incidents SET status = 'Resolved' WHERE status = 'resolved';
UPDATE incidents SET status = 'Closed' WHERE status = 'closed';

-- Add comments for documentation
ALTER TABLE incidents 
MODIFY COLUMN status ENUM(
    'Pending Verification',    -- Maker step completed, waiting for Verifier
    'Pending Authorization',   -- Verifier step completed, waiting for Authorizer  
    'Authorized',             -- Authorizer step completed, ready for resolution
    'Resolved',               -- Incident has been resolved
    'Closed',                 -- Incident is closed (final step)
    'Canceled'                -- Incident was canceled at any stage
) NOT NULL DEFAULT 'Pending Verification' COMMENT 'MVA Workflow Status';

-- Verify migration
SELECT 'Migration completed successfully' as status;
SELECT COUNT(*) as total_incidents FROM incidents;
SELECT status, COUNT(*) as count FROM incidents GROUP BY status;
-- Table structure for table `maintenance`
CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `type` enum('preventive','corrective','emergency') NOT NULL,
  `description` text NOT NULL,
  `scheduled_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `assigned_to` varchar(100),
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `actual_cost` decimal(10,2) DEFAULT NULL,
  `parts_used` text,
  `status` enum('scheduled','in_progress','completed','canceled') NOT NULL DEFAULT 'scheduled',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `completion_notes` text,
  `next_maintenance_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `scheduled_date` (`scheduled_date`),
  CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Migration: Update maintenance table for MVA workflow
-- Date: 2025-01-XX
-- Description: Update maintenance table to support Maker-Verifier-Authorizer workflow

-- Update status enum to support MVA workflow
ALTER TABLE `maintenance` 
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','scheduled','in_progress','completed','canceled') NOT NULL DEFAULT 'Pending Verification';

-- Add new fields for MVA workflow
ALTER TABLE `maintenance` 
ADD COLUMN `created_by` int(11) DEFAULT NULL AFTER `next_maintenance_date`,
ADD COLUMN `verified_by` int(11) DEFAULT NULL AFTER `created_by`,
ADD COLUMN `verification_date` timestamp NULL DEFAULT NULL AFTER `verified_by`,
ADD COLUMN `approved_by` int(11) DEFAULT NULL AFTER `verification_date`,
ADD COLUMN `approval_date` timestamp NULL DEFAULT NULL AFTER `approved_by`,
ADD COLUMN `notes` text DEFAULT NULL AFTER `approval_date`;

-- Add foreign key constraints for new fields
ALTER TABLE `maintenance` 
ADD CONSTRAINT `maintenance_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `maintenance_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `maintenance_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add indexes for new fields
ALTER TABLE `maintenance` 
ADD KEY `created_by` (`created_by`),
ADD KEY `verified_by` (`verified_by`),
ADD KEY `approved_by` (`approved_by`),
ADD KEY `verification_date` (`verification_date`),
ADD KEY `approval_date` (`approval_date`);

-- Update existing maintenance to use new status values
UPDATE `maintenance` SET `status` = 'Approved' WHERE `status` = 'scheduled';
UPDATE `maintenance` SET `status` = 'in_progress' WHERE `status` = 'in_progress';
UPDATE `maintenance` SET `status` = 'completed' WHERE `status` = 'completed';
UPDATE `maintenance` SET `status` = 'canceled' WHERE `status` = 'canceled';

-- Update the status enum to finalize workflow states
ALTER TABLE `maintenance` 
MODIFY COLUMN `status` enum('Pending Verification','Pending Approval','Approved','in_progress','completed','canceled') NOT NULL DEFAULT 'Pending Verification';

-- Table structure for table `audit_logs`
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50),
  `record_id` int(11),
  `old_values` json,
  `new_values` json,
  `description` text,
  `ip_address` varchar(45),
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `table_name` (`table_name`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `activity_logs`
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `table_name` varchar(50),
  `record_id` int(11),
  `ip_address` varchar(45),
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `vendor_logs`
CREATE TABLE `vendor_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `vendor_logs_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------



-- Table structure for table `system_settings`
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `description` text,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert enhanced system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`, `is_public`) VALUES
('system_name', 'ConstructLink™', 'System name displayed in interface', 1),
('company_name', 'V CUTAMORA CONSTRUCTION INC.', 'Company name', 1),
('system_version', '2.0.0', 'Current system version', 1),
('maintenance_mode', '0', 'System maintenance mode (0=off, 1=on)', 0),
('asset_ref_prefix', 'CL', 'Asset reference prefix', 0),
('qr_code_enabled', '1', 'QR code generation enabled', 0),
('email_notifications', '0', 'Email notifications enabled', 0),
('session_timeout', '28800', 'Session timeout in seconds (8 hours)', 0),
('po_prefix', 'PO', 'Purchase Order number prefix', 0),
('current_po_sequence', '1', 'Current PO sequence number', 0),
('request_prefix', 'REQ', 'Request number prefix', 0),
('current_request_sequence', '1', 'Current request sequence number', 0),
('auto_approve_low_value', '0', 'Auto-approve requests below threshold', 0),
('low_value_threshold', '5000.00', 'Low value auto-approval threshold', 0),
('require_justification_above', '10000.00', 'Require justification for amounts above this', 0),
('default_vat_rate', '12.00', 'Default VAT rate percentage', 0),
('default_ewt_rate', '2.00', 'Default EWT rate percentage', 0);

-- --------------------------------------------------------


-- Create indexes for better performance
CREATE INDEX `idx_assets_project_status` ON `assets` (`project_id`, `status`);
CREATE INDEX `idx_procurement_orders_status_project` ON `procurement_orders` (`status`, `project_id`);
CREATE INDEX `idx_procurement_items_order_status` ON `procurement_items` (`procurement_order_id`, `delivery_status`);
CREATE INDEX `idx_requests_status_project` ON `requests` (`status`, `project_id`);
CREATE INDEX `idx_withdrawals_status_project` ON `withdrawals` (`status`, `project_id`);
CREATE INDEX `idx_transfers_status_projects` ON `transfers` (`status`, `from_project`, `to_project`);
CREATE INDEX `idx_user_projects_active` ON `user_projects` (`is_active`, `user_id`, `project_id`);
CREATE INDEX `idx_vendor_banks_active` ON `vendor_banks` (`is_active`, `vendor_id`);
CREATE INDEX `idx_activity_logs_user_date` ON `activity_logs` (`user_id`, `created_at`);
CREATE INDEX `idx_procurement_logs_order_date` ON `procurement_logs` (`procurement_order_id`, `created_at`);
CREATE INDEX `idx_request_logs_date` ON `request_logs` (`request_id`, `created_at`);
CREATE INDEX `idx_assets_procurement_links` ON `assets` (`procurement_order_id`, `procurement_item_id`);
CREATE INDEX `idx_procurement_assets_links` ON `procurement_assets` (`procurement_order_id`, `procurement_item_id`);

-- --------------------------------------------------------

-- Create views for common queries
CREATE VIEW `v_active_users_with_projects` AS
SELECT
    u.id, u.username, u.full_name, u.email, u.phone, u.department,
    r.name as role_name, r.description as role_description,
    p.id as project_id, p.name as project_name, p.code as project_code,
    u.last_login, u.created_at
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
LEFT JOIN projects p ON u.current_project_id = p.id
WHERE u.is_active = 1;

-- --------------------------------------------------------

-- CREATE VIEW `v_procurement_summary` AS
-- SELECT
--     pr.id, pr.po_number, pr.item_name, pr.quantity, pr.unit_price, pr.net_total,
--     pr.status, pr.delivery_status, pr.created_at,
--     v.name as vendor_name, v.contact_person as vendor_contact,
--     p.name as project_name, p.code as project_code,
--     u.full_name as requested_by_name,
--     ua.full_name as approved_by_name,
--     ur.full_name as received_by_name,
--     r.description as request_description
-- FROM procurement pr
-- LEFT JOIN vendors v ON pr.vendor_id = v.id
-- LEFT JOIN projects p ON pr.project_id = p.id
-- LEFT JOIN users u ON pr.requested_by = u.id
-- LEFT JOIN users ua ON pr.approved_by = ua.id
-- LEFT JOIN users ur ON pr.received_by = ur.id
-- LEFT JOIN requests r ON pr.request_id = r.id;

-- -- --------------------------------------------------------

-- CREATE VIEW `v_asset_inventory` AS
-- SELECT
--     a.id, a.ref, a.name, a.description, a.status, a.acquired_date,
--     a.acquisition_cost, a.unit_cost, a.serial_number, a.model,
--     c.name as category_name,
--     p.name as project_name, p.code as project_code,
--     v.name as vendor_name,
--     m.name as maker_name,
--     pr.po_number,
--     CASE
--         WHEN a.status = 'in_use' THEN
--             (SELECT CONCAT('Withdrawn by ', w.receiver_name)
--              FROM withdrawals w
--              WHERE w.asset_id = a.id AND w.status = 'released'
--              ORDER BY w.created_at DESC LIMIT 1)
--         WHEN a.status = 'borrowed' THEN
--             (SELECT CONCAT('Borrowed by ', bt.borrower_name)
--              FROM borrowed_tools bt
--              WHERE bt.asset_id = a.id AND bt.status = 'borrowed'
--              ORDER BY bt.created_at DESC LIMIT 1)
--         ELSE NULL
--     END as current_user
-- FROM assets a
-- LEFT JOIN categories c ON a.category_id = c.id
-- LEFT JOIN projects p ON a.project_id = p.id
-- LEFT JOIN vendors v ON a.vendor_id = v.id
-- LEFT JOIN makers m ON a.maker_id = m.id
-- LEFT JOIN procurement pr ON a.procurement_id = pr.id;

-- --------------------------------------------------------

-- Create triggers for audit logging
DELIMITER $$

CREATE TRIGGER `audit_assets_insert` AFTER INSERT ON `assets`
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values, description, created_at)
    VALUES (
        @current_user_id,
        'INSERT',
        'assets',
        NEW.id,
        JSON_OBJECT(
            'ref', NEW.ref,
            'name', NEW.name,
            'status', NEW.status,
            'project_id', NEW.project_id
        ),
        CONCAT('Asset created: ', NEW.ref, ' - ', NEW.name),
        NOW()
    );
END$$

CREATE TRIGGER `audit_assets_update` AFTER UPDATE ON `assets`
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, description, created_at)
    VALUES (
        @current_user_id,
        'UPDATE',
        'assets',
        NEW.id,
        JSON_OBJECT(
            'ref', OLD.ref,
            'name', OLD.name,
            'status', OLD.status,
            'project_id', OLD.project_id
        ),
        JSON_OBJECT(
            'ref', NEW.ref,
            'name', NEW.name,
            'status', NEW.status,
            'project_id', NEW.project_id
        ),
        CONCAT('Asset updated: ', NEW.ref, ' - ', NEW.name),
        NOW()
    );
END$$

-- CREATE TRIGGER `audit_procurement_status_change` AFTER UPDATE ON `procurement`
-- FOR EACH ROW BEGIN
--     IF OLD.status != NEW.status THEN
--         INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, description, created_at)
--         VALUES (
--             @current_user_id,
--             'STATUS_CHANGE',
--             'procurement',
--             NEW.id,
--             JSON_OBJECT('status', OLD.status),
--             JSON_OBJECT('status', NEW.status),
--             CONCAT('Procurement status changed from ', OLD.status, ' to ', NEW.status, ' for PO: ', NEW.po_number),
--             NOW()
--         );
--     END IF;
-- END$$

DELIMITER ;


-- --------------------------------------------------------

COMMIT;

-- End of enhanced schema
