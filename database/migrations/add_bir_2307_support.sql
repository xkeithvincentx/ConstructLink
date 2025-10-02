-- BIR Form 2307 Integration Migration
-- Adds support for Certificate of Creditable Tax Withheld at Source
-- Date: 2025-08-22

-- ============================================
-- 1. Update vendors table for BIR requirements
-- ============================================

ALTER TABLE `vendors` 
ADD COLUMN `vendor_type` ENUM('Company', 'Sole Proprietor', 'Partnership', 'Cooperative', 'Government') DEFAULT 'Company' AFTER `name`,
ADD COLUMN `tin` VARCHAR(20) COMMENT 'Taxpayer Identification Number (XXX-XXX-XXX-XXX format)' AFTER `tax_id`,
ADD COLUMN `first_name` VARCHAR(100) COMMENT 'For sole proprietors only' AFTER `contact_person`,
ADD COLUMN `middle_name` VARCHAR(100) COMMENT 'For sole proprietors only' AFTER `first_name`,
ADD COLUMN `last_name` VARCHAR(100) COMMENT 'For sole proprietors only' AFTER `middle_name`,
ADD COLUMN `registered_name` VARCHAR(255) COMMENT 'Official registered business name' AFTER `name`,
ADD COLUMN `rdo_code` VARCHAR(10) COMMENT 'Revenue District Office code' AFTER `tin`,
ADD COLUMN `zip_code` VARCHAR(10) AFTER `address`,
ADD INDEX `idx_vendor_type` (`vendor_type`),
ADD INDEX `idx_tin` (`tin`);

-- ============================================
-- 2. Create ATC codes table
-- ============================================

CREATE TABLE IF NOT EXISTS `atc_codes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(10) NOT NULL UNIQUE COMMENT 'Alphanumeric Tax Code',
  `description` TEXT NOT NULL COMMENT 'Full description of the tax code',
  `rate` DECIMAL(5,2) NOT NULL COMMENT 'Tax rate percentage',
  `category` ENUM('Professional/Talent Fees', 'Rental', 'Goods', 'Services', 'Commission', 'Other') NOT NULL,
  `nature_of_payment` VARCHAR(255) COMMENT 'Nature of payment description',
  `is_vat_inclusive` TINYINT(1) DEFAULT 1 COMMENT 'Whether rate applies to VAT-inclusive amount',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_code` (`code`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. Insert common ATC codes from the image
-- ============================================

INSERT INTO `atc_codes` (`code`, `description`, `rate`, `category`, `nature_of_payment`) VALUES
-- Professional/Talent Fees
('WI010', 'Professional fees (lawyers, CPAs, engineers, etc)', 10.00, 'Professional/Talent Fees', 'Professional fees paid to individuals'),
('WI011', 'Professional fees (lawyers, CPAs, engineers, etc)', 15.00, 'Professional/Talent Fees', 'Professional fees paid to non-individuals'),
('WC010', 'Professional entertainers such as, but not limited to actors and actresses, singers, lyricists, composers', 10.00, 'Professional/Talent Fees', 'Talent fees'),
('WC011', 'Professional entertainers such as, but not limited to actors and actresses, singers, lyricists, composers', 15.00, 'Professional/Talent Fees', 'Talent fees'),

-- Rental
('WI030', 'Rental - Real property', 5.00, 'Rental', 'Rental payments made to the government and government-owned and controlled corporations (GOCCs)'),
('WI031', 'Rental - Cinematographic film', 5.00, 'Rental', 'Rental of cinematographic films'),
('WC030', 'Rental - Machinery and equipment', 5.00, 'Rental', 'Rental of machinery and equipment'),
('WI050', 'Rental of Personal Property', 5.00, 'Rental', 'All gross rentals for the use of personal property (movable tangible and intangible property)'),
('WI051', 'Rental - Motor vehicles', 5.00, 'Rental', 'Rental of motor vehicles including buses and trucks'),

-- Services  
('WC040', 'Management and technical consultants', 10.00, 'Services', 'Gross payments to management and technical consultants'),
('WI040', 'Management and technical consultants', 15.00, 'Services', 'Gross payments to management and technical consultants'),
('WC050', 'Government', 5.00, 'Services', 'Gross income for the current year did not exceed P720,000'),
('WC052', 'Government', 10.00, 'Services', 'Gross income for the current year exceeds P720,000'),
('WC057', 'Commercial Brokers and Agencies', 10.00, 'Services', 'All gross receipts of brokers from their commissions'),
('WI057', 'Commercial Brokers and Agencies', 15.00, 'Services', 'All gross receipts of brokers from their commissions'),

-- Commission
('WC060', 'Prizes', 10.00, 'Commission', 'All gross amount of prizes (except prizes amounting to P10,000 or less)'),
('WC061', 'Other winnings', 20.00, 'Commission', 'All gross amount of winnings'),
('WC062', 'Commission', 10.00, 'Commission', 'Commission paid to partner/salesman'),
('WI062', 'Commission', 15.00, 'Commission', 'Commission paid to partners'),

-- Goods/Services
('WI070', 'Gross payments for purchases of minerals, mineral products and quarry resources', 10.00, 'Goods', 'Gross payments for purchases of minerals'),
('WI071', 'Gross payments for purchases of minerals, mineral products and quarry resources', 15.00, 'Goods', 'Gross payments for purchases of minerals'),
('WC090', 'Income payments to partners of General Professional Partnerships (GPPs)', 10.00, 'Services', 'Income payments made to the government and government-owned and controlled corporations (GOCCs)'),
('WI090', 'Income payments to partners of General Professional Partnerships (GPPs)', 15.00, 'Services', 'Income payments made to non-government entities'),

-- Government/Others
('WC100', 'Government', 1.00, 'Goods', 'Gross amount of interest on the refund of motor deposit exceed paid directly to purchase'),
('WC101', 'Government', 2.00, 'Goods', 'All gross amount of interest on applied against customer payment'),
('WC102', 'Private', 1.00, 'Goods', 'Gross amount of interest on the refund of motor deposit exceed paid directly to purchase'),
('WC103', 'Private', 2.00, 'Goods', 'All gross amount of interest on applied against customer payment'),

-- Common goods and services
('WC110', 'All payments/fees', 1.00, 'Goods', 'Gross payments required to be withheld under existing laws, regulations, revenue issuances'),
('WC111', 'All payments/fees', 2.00, 'Services', 'Gross payments required to be withheld under existing laws, regulations, revenue issuances'),
('WC156', 'Payment to suppliers of goods', 1.00, 'Goods', 'Payment to regular suppliers for goods'),
('WC157', 'Payment to suppliers of services other than those covered by other rates', 2.00, 'Services', 'Payment to regular suppliers for services'),
('WC158', 'Payment to suppliers of goods', 2.00, 'Goods', 'Payments to suppliers with certain threshold');

-- ============================================
-- 4. Update procurement_items for purchase types
-- ============================================

ALTER TABLE `procurement_items`
ADD COLUMN `purchase_type` ENUM('Goods', 'Services', 'Rental', 'Professional Services', 'Mixed', 'Other') DEFAULT 'Goods' AFTER `category_id`,
ADD COLUMN `atc_code_id` INT(11) AFTER `purchase_type`,
ADD COLUMN `ewt_rate` DECIMAL(5,2) COMMENT 'Expanded Withholding Tax rate for this item' AFTER `atc_code_id`,
ADD COLUMN `ewt_amount` DECIMAL(15,2) COMMENT 'Calculated EWT amount for this item' AFTER `ewt_rate`,
ADD KEY `idx_purchase_type` (`purchase_type`),
ADD KEY `fk_atc_code` (`atc_code_id`),
ADD CONSTRAINT `procurement_items_atc_fk` FOREIGN KEY (`atc_code_id`) REFERENCES `atc_codes` (`id`) ON DELETE SET NULL;

-- ============================================
-- 5. Create BIR 2307 forms table
-- ============================================

CREATE TABLE IF NOT EXISTS `bir_2307_forms` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `form_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique form number/reference',
  `procurement_order_id` INT(11) NOT NULL,
  `vendor_id` INT(11) NOT NULL,
  `period_from` DATE NOT NULL COMMENT 'Start of period covered',
  `period_to` DATE NOT NULL COMMENT 'End of period covered',
  `quarter` ENUM('1st', '2nd', '3rd', '4th') NOT NULL,
  `year` YEAR NOT NULL,
  
  -- Payee Information (Part I)
  `payee_tin` VARCHAR(20) NOT NULL,
  `payee_name` VARCHAR(255) NOT NULL COMMENT 'Company name or individual name',
  `payee_registered_name` VARCHAR(255) COMMENT 'For non-individual entities',
  `payee_first_name` VARCHAR(100) COMMENT 'For individual payees',
  `payee_middle_name` VARCHAR(100) COMMENT 'For individual payees',
  `payee_last_name` VARCHAR(100) COMMENT 'For individual payees',
  `payee_address` TEXT NOT NULL,
  `payee_zip_code` VARCHAR(10),
  `payee_foreign_address` TEXT COMMENT 'If applicable',
  
  -- Payor Information (Part II)
  `payor_tin` VARCHAR(20) NOT NULL DEFAULT '007-608-972-000' COMMENT 'V Cutamora TIN',
  `payor_name` VARCHAR(255) NOT NULL DEFAULT 'V CUTAMORA CONSTRUCTION INC.',
  `payor_address` TEXT NOT NULL,
  `payor_zip_code` VARCHAR(10) DEFAULT '1605',
  
  -- Income Payments and Tax Withheld (Part III)
  `income_payments` JSON COMMENT 'Array of {atc_code, month, amount, tax_withheld}',
  `total_amount` DECIMAL(15,2) NOT NULL COMMENT 'Total amount of income payments',
  `total_tax_withheld` DECIMAL(15,2) NOT NULL COMMENT 'Total tax withheld for the quarter',
  
  -- Money Payments Subject to Withholding
  `money_payments` JSON COMMENT 'Array of business tax and private payments',
  
  -- Status and metadata
  `status` ENUM('Draft', 'Generated', 'Printed', 'Submitted', 'Cancelled') DEFAULT 'Draft',
  `generated_by` INT(11) COMMENT 'User who generated the form',
  `generated_at` DATETIME COMMENT 'When the form was generated',
  `submitted_at` DATETIME COMMENT 'When submitted to BIR',
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_number` (`form_number`),
  KEY `idx_procurement_order` (`procurement_order_id`),
  KEY `idx_vendor` (`vendor_id`),
  KEY `idx_period` (`period_from`, `period_to`),
  KEY `idx_quarter_year` (`quarter`, `year`),
  KEY `idx_status` (`status`),
  CONSTRAINT `bir_2307_po_fk` FOREIGN KEY (`procurement_order_id`) REFERENCES `procurement_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bir_2307_vendor_fk` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bir_2307_user_fk` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. Add BIR 2307 support to procurement_orders
-- ============================================

ALTER TABLE `procurement_orders`
ADD COLUMN `bir_2307_generated` TINYINT(1) DEFAULT 0 COMMENT 'Whether BIR 2307 has been generated' AFTER `payment_released_at`,
ADD COLUMN `bir_2307_form_id` INT(11) COMMENT 'Link to generated BIR 2307 form' AFTER `bir_2307_generated`,
ADD KEY `fk_bir_2307_form` (`bir_2307_form_id`),
ADD CONSTRAINT `po_bir_2307_fk` FOREIGN KEY (`bir_2307_form_id`) REFERENCES `bir_2307_forms` (`id`) ON DELETE SET NULL;

-- ============================================
-- 7. Create BIR 2307 configuration table
-- ============================================

CREATE TABLE IF NOT EXISTS `bir_2307_config` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `config_key` VARCHAR(100) NOT NULL UNIQUE,
  `config_value` TEXT,
  `description` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default configuration
INSERT INTO `bir_2307_config` (`config_key`, `config_value`, `description`) VALUES
('form_series_prefix', '2307-', 'Prefix for BIR 2307 form numbers'),
('form_series_counter', '1', 'Current counter for form numbering'),
('default_signatory_name', 'Gerald S. Cutamora', 'Default authorized signatory'),
('default_signatory_title', 'Treasurer / Director', 'Default signatory title'),
('default_tax_agent_no', '', 'Tax Agent Accreditation Number if applicable'),
('auto_calculate_ewt', '1', 'Automatically calculate EWT based on ATC codes'),
('include_vat_in_ewt', '1', 'Include VAT in EWT calculation base');

-- ============================================
-- 8. Add indexes for performance
-- ============================================

ALTER TABLE `procurement_items` ADD INDEX `idx_ewt_calculation` (`procurement_order_id`, `atc_code_id`, `subtotal`);
ALTER TABLE `bir_2307_forms` ADD INDEX `idx_form_lookup` (`form_number`, `status`);

-- ============================================
-- 9. Create audit trail for BIR 2307
-- ============================================

CREATE TABLE IF NOT EXISTS `bir_2307_audit_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `form_id` INT(11) NOT NULL,
  `action` VARCHAR(50) NOT NULL COMMENT 'created, updated, printed, submitted, cancelled',
  `user_id` INT(11),
  `details` JSON COMMENT 'Additional details about the action',
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_form` (`form_id`),
  KEY `idx_action` (`action`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `bir_audit_form_fk` FOREIGN KEY (`form_id`) REFERENCES `bir_2307_forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bir_audit_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;