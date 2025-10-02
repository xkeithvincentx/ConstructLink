-- Migration: Add Comprehensive File Upload Support
-- Date: 2025-01-07
-- Description: Add multiple file attachment columns for procurement orders

-- Add comprehensive file support to procurement_orders table
ALTER TABLE `procurement_orders` 
ADD COLUMN `purchase_receipt_file` VARCHAR(255) NULL 
COMMENT 'Purchase receipt/sales invoice file' AFTER `quote_file`,

ADD COLUMN `supporting_evidence_file` VARCHAR(255) NULL 
COMMENT 'Additional supporting documentation' AFTER `purchase_receipt_file`,

ADD COLUMN `file_upload_notes` TEXT NULL
COMMENT 'Notes about uploaded documents' AFTER `supporting_evidence_file`,

ADD COLUMN `retroactive_current_state` ENUM('not_delivered', 'delivered', 'received') NULL 
COMMENT 'Current state of items when creating retroactive PO' AFTER `retroactive_reason`,

ADD COLUMN `retroactive_target_status` VARCHAR(50) NULL 
COMMENT 'Target status for retroactive PO after approval' AFTER `retroactive_current_state`;

-- Add indexes for performance on file-related queries
ALTER TABLE `procurement_orders` 
ADD KEY `quote_file` (`quote_file`),
ADD KEY `purchase_receipt_file` (`purchase_receipt_file`),
ADD KEY `supporting_evidence_file` (`supporting_evidence_file`);

-- Update fillable array reminder for developer
-- Remember to update ProcurementOrderModel fillable array to include:
-- 'purchase_receipt_file', 'supporting_evidence_file', 'file_upload_notes'