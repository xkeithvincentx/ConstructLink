-- ==================================================================================
-- ConstructLink™ Database Migration
-- Migration: Add Borrowed Tool Batches System
-- Date: 2025-01-13
-- Description: Enable multi-item borrowing with batch management and quantity tracking
-- Developed by: Ranoa Digital Solutions
-- ==================================================================================

-- ==================================================================================
-- 1. CREATE BORROWED_TOOL_BATCHES TABLE
-- ==================================================================================

CREATE TABLE IF NOT EXISTS `borrowed_tool_batches` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `batch_reference` varchar(50) NOT NULL UNIQUE COMMENT 'Format: BRW-YYYY-NNNN',

    -- Borrower Information
    `borrower_name` varchar(100) NOT NULL,
    `borrower_contact` varchar(100) DEFAULT NULL,
    `borrower_signature_image` varchar(255) DEFAULT NULL COMMENT 'Path to uploaded signature photo',
    `borrower_photo` varchar(255) DEFAULT NULL COMMENT 'Optional: Photo of borrower for verification',

    -- Borrowing Details
    `expected_return` date NOT NULL,
    `actual_return` date DEFAULT NULL,
    `purpose` text DEFAULT NULL,

    -- MVA Workflow Fields
    `status` enum(
        'Draft',
        'Pending Verification',
        'Pending Approval',
        'Approved',
        'Released',
        'Partially Returned',
        'Returned',
        'Overdue',
        'Canceled'
    ) NOT NULL DEFAULT 'Pending Verification',

    -- Maker (Warehouseman creates the batch)
    `issued_by` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Verifier (Project Manager for critical tools)
    `verified_by` int(11) DEFAULT NULL,
    `verification_date` timestamp NULL DEFAULT NULL,
    `verification_notes` text DEFAULT NULL,

    -- Authorizer (Asset Director/Finance Director for critical tools)
    `approved_by` int(11) DEFAULT NULL,
    `approval_date` timestamp NULL DEFAULT NULL,
    `approval_notes` text DEFAULT NULL,

    -- Release (Warehouseman confirms physical handover)
    `released_by` int(11) DEFAULT NULL,
    `release_date` timestamp NULL DEFAULT NULL,
    `release_notes` text DEFAULT NULL,

    -- Return Processing
    `returned_by` int(11) DEFAULT NULL COMMENT 'Staff who processed the return',
    `return_date` timestamp NULL DEFAULT NULL,
    `return_notes` text DEFAULT NULL,

    -- Cancellation
    `canceled_by` int(11) DEFAULT NULL,
    `cancellation_date` timestamp NULL DEFAULT NULL,
    `cancellation_reason` text DEFAULT NULL,

    -- Metadata
    `is_critical_batch` tinyint(1) DEFAULT 0 COMMENT '1 if batch contains any critical tools >50K',
    `total_items` int(11) DEFAULT 0 COMMENT 'Total number of line items in batch',
    `total_quantity` int(11) DEFAULT 0 COMMENT 'Total quantity across all items',
    `printed_at` timestamp NULL DEFAULT NULL COMMENT 'When the handwritten form was printed',
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_borrower_name` (`borrower_name`),
    KEY `idx_status` (`status`),
    KEY `idx_expected_return` (`expected_return`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_issued_by` (`issued_by`),
    KEY `idx_verified_by` (`verified_by`),
    KEY `idx_approved_by` (`approved_by`),
    KEY `idx_released_by` (`released_by`),
    KEY `idx_returned_by` (`returned_by`),

    CONSTRAINT `fk_batches_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_batches_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_batches_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_batches_released_by` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_batches_returned_by` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_batches_canceled_by` FOREIGN KEY (`canceled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Batch records for multi-item tool borrowing';

-- ==================================================================================
-- 2. ALTER BORROWED_TOOLS TABLE FOR BATCH SUPPORT
-- ==================================================================================

-- Note: borrowed_tools table already has batch-related columns
-- Only add missing constraints if needed

-- Check and add foreign key if it doesn't exist
SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = 'constructlink_db'
    AND TABLE_NAME = 'borrowed_tools'
    AND CONSTRAINT_NAME = 'fk_borrowed_tools_batch');

SET @sql = IF(@constraint_exists = 0,
    'ALTER TABLE `borrowed_tools` ADD CONSTRAINT `fk_borrowed_tools_batch` FOREIGN KEY (`batch_id`) REFERENCES `borrowed_tool_batches` (`id`) ON DELETE CASCADE',
    'SELECT "Constraint already exists"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ==================================================================================
-- 3. CREATE SEQUENCE TABLE FOR BATCH REFERENCE GENERATION
-- ==================================================================================

CREATE TABLE IF NOT EXISTS `borrowed_tool_batch_sequences` (
    `year` int(4) NOT NULL,
    `last_sequence` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Sequence tracker for batch reference numbers';

-- ==================================================================================
-- 4. MIGRATE EXISTING DATA TO BATCH FORMAT
-- ==================================================================================

-- Create batches for existing individual borrowed_tools records
-- Each existing record becomes a single-item batch for backward compatibility

INSERT INTO `borrowed_tool_batches` (
    `batch_reference`,
    `borrower_name`,
    `borrower_contact`,
    `expected_return`,
    `actual_return`,
    `purpose`,
    `status`,
    `issued_by`,
    `created_at`,
    `verified_by`,
    `verification_date`,
    `approved_by`,
    `approval_date`,
    `released_by`,
    `release_date`,
    `returned_by`,
    `return_date`,
    `canceled_by`,
    `cancellation_date`,
    `cancellation_reason`,
    `is_critical_batch`,
    `total_items`,
    `total_quantity`
)
SELECT
    CONCAT('BRW-LEGACY-', LPAD(bt.id, 6, '0')) as batch_reference,
    bt.borrower_name,
    bt.borrower_contact,
    bt.expected_return,
    bt.actual_return,
    bt.purpose,
    -- Map old status values to new enum values
    CASE
        WHEN bt.status = 'Borrowed' THEN 'Released'
        WHEN bt.status = 'Returned' THEN 'Returned'
        WHEN bt.status = 'Canceled' THEN 'Canceled'
        ELSE 'Released'
    END as status,
    bt.issued_by,
    bt.created_at,
    bt.verified_by,
    bt.verification_date,
    bt.approved_by,
    bt.approval_date,
    bt.borrowed_by as released_by,
    bt.borrowed_date as release_date,
    bt.returned_by,
    bt.return_date,
    bt.canceled_by,
    bt.cancellation_date,
    bt.cancellation_reason,
    CASE WHEN a.acquisition_cost > 50000 THEN 1 ELSE 0 END as is_critical_batch,
    1 as total_items,
    1 as total_quantity
FROM borrowed_tools bt
LEFT JOIN assets a ON bt.asset_id = a.id
WHERE bt.batch_id IS NULL; -- Only migrate records not already in a batch

-- Update borrowed_tools to link to their new batches
UPDATE borrowed_tools bt
INNER JOIN borrowed_tool_batches btb ON btb.batch_reference = CONCAT('BRW-LEGACY-', LPAD(bt.id, 6, '0'))
SET bt.batch_id = btb.id,
    bt.quantity = 1,
    bt.quantity_returned = CASE WHEN bt.status = 'Returned' THEN 1 ELSE 0 END
WHERE bt.batch_id IS NULL;

-- ==================================================================================
-- 5. CREATE VIEWS FOR REPORTING
-- ==================================================================================

-- View: Active Batches Summary
CREATE OR REPLACE VIEW `view_active_borrowed_batches` AS
SELECT
    btb.id,
    btb.batch_reference,
    btb.borrower_name,
    btb.borrower_contact,
    btb.expected_return,
    btb.status,
    btb.total_items,
    btb.total_quantity,
    btb.is_critical_batch,
    btb.created_at,
    u_issued.full_name as issued_by_name,
    u_verified.full_name as verified_by_name,
    u_approved.full_name as approved_by_name,
    u_released.full_name as released_by_name,
    COUNT(bt.id) as items_count,
    SUM(bt.quantity) as total_borrowed,
    SUM(bt.quantity_returned) as total_returned,
    CASE
        WHEN btb.status = 'Released' AND btb.expected_return < CURDATE() THEN 'Overdue'
        WHEN btb.status = 'Partially Returned' AND btb.expected_return < CURDATE() THEN 'Partially Overdue'
        ELSE btb.status
    END as current_status
FROM borrowed_tool_batches btb
LEFT JOIN borrowed_tools bt ON btb.id = bt.batch_id
LEFT JOIN users u_issued ON btb.issued_by = u_issued.id
LEFT JOIN users u_verified ON btb.verified_by = u_verified.id
LEFT JOIN users u_approved ON btb.approved_by = u_approved.id
LEFT JOIN users u_released ON btb.released_by = u_released.id
WHERE btb.status IN ('Pending Verification', 'Pending Approval', 'Approved', 'Released', 'Partially Returned', 'Overdue')
GROUP BY btb.id
ORDER BY btb.created_at DESC;

-- ==================================================================================
-- 6. CREATE INDEXES FOR PERFORMANCE
-- ==================================================================================

-- Composite indexes for common queries
CREATE INDEX `idx_batch_status_date` ON `borrowed_tool_batches` (`status`, `expected_return`);
CREATE INDEX `idx_batch_borrower_status` ON `borrowed_tool_batches` (`borrower_name`, `status`);
CREATE INDEX `idx_batch_critical_status` ON `borrowed_tool_batches` (`is_critical_batch`, `status`);

-- Full-text search index for borrower names (for autocomplete)
ALTER TABLE `borrowed_tool_batches` ADD FULLTEXT KEY `ft_borrower_search` (`borrower_name`, `borrower_contact`);

-- ==================================================================================
-- 7. COMMIT CHANGES
-- ==================================================================================

COMMIT;

-- ==================================================================================
-- END OF MIGRATION
-- ==================================================================================
