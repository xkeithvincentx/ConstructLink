-- ===============================================================
-- ConstructLink™ Request Module MVA Workflow Migration
-- ===============================================================
-- Adds fields to support dynamic Maker-Verifier-Authorizer workflow
-- with role-based routing for request approval process
--
-- Workflow Scenarios:
--   A) Warehouseman: Draft → Submitted → Verified → Authorized → Approved → Procured → Fulfilled
--   B) Site Inventory Clerk: Draft → Submitted → Verified → Approved → Procured → Fulfilled
--   C) Project Manager: Draft → Submitted → Approved → Procured → Fulfilled
--
-- @version 1.0.0
-- @date 2025-01-07
-- ===============================================================

USE constructlink_db;

-- Step 1: Update ENUM to add new workflow statuses
ALTER TABLE requests
MODIFY COLUMN status ENUM(
    'Draft',
    'Submitted',
    'Verified',
    'Authorized',
    'Approved',
    'Declined',
    'Procured',
    'Fulfilled'
) DEFAULT 'Draft';

-- Step 2: Add workflow tracking fields
ALTER TABLE requests
ADD COLUMN IF NOT EXISTS verified_by INT(11) NULL COMMENT 'User who verified the request (Site Inventory Clerk or Project Manager)' AFTER approved_by,
ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL COMMENT 'Timestamp when request was verified' AFTER verified_by,
ADD COLUMN IF NOT EXISTS authorized_by INT(11) NULL COMMENT 'User who authorized the request (Project Manager or Finance Director)' AFTER verified_at,
ADD COLUMN IF NOT EXISTS authorized_at TIMESTAMP NULL COMMENT 'Timestamp when request was authorized' AFTER authorized_by,
ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL COMMENT 'Timestamp when request was approved' AFTER approved_by,
ADD COLUMN IF NOT EXISTS declined_by INT(11) NULL COMMENT 'User who declined the request' AFTER approved_at,
ADD COLUMN IF NOT EXISTS declined_at TIMESTAMP NULL COMMENT 'Timestamp when request was declined' AFTER declined_by,
ADD COLUMN IF NOT EXISTS decline_reason TEXT NULL COMMENT 'Reason for declining the request' AFTER declined_at;

-- Step 3: Add foreign key constraints for audit trail (with existence check)
SET @constraint_check = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'constructlink_db'
    AND TABLE_NAME = 'requests'
    AND CONSTRAINT_NAME = 'fk_requests_verified_by');

SET @sql_fk1 = IF(@constraint_check = 0,
    'ALTER TABLE requests ADD CONSTRAINT fk_requests_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_requests_verified_by already exists" AS message');
PREPARE stmt FROM @sql_fk1;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_check = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'constructlink_db'
    AND TABLE_NAME = 'requests'
    AND CONSTRAINT_NAME = 'fk_requests_authorized_by');

SET @sql_fk2 = IF(@constraint_check = 0,
    'ALTER TABLE requests ADD CONSTRAINT fk_requests_authorized_by FOREIGN KEY (authorized_by) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_requests_authorized_by already exists" AS message');
PREPARE stmt FROM @sql_fk2;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_check = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'constructlink_db'
    AND TABLE_NAME = 'requests'
    AND CONSTRAINT_NAME = 'fk_requests_declined_by');

SET @sql_fk3 = IF(@constraint_check = 0,
    'ALTER TABLE requests ADD CONSTRAINT fk_requests_declined_by FOREIGN KEY (declined_by) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_requests_declined_by already exists" AS message');
PREPARE stmt FROM @sql_fk3;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 4: Create index for workflow queries (performance optimization)
CREATE INDEX IF NOT EXISTS idx_requests_workflow
    ON requests(status, verified_by, authorized_by, approved_by);

-- Step 5: Create index for maker queries
CREATE INDEX IF NOT EXISTS idx_requests_maker
    ON requests(requested_by, status, created_at);

-- Step 6: Backfill approved_at for existing approved requests (data integrity)
UPDATE requests
SET approved_at = updated_at
WHERE status = 'Approved'
  AND approved_at IS NULL
  AND updated_at IS NOT NULL;

-- Step 7: Create activity log entries for migration (audit trail)
INSERT INTO request_logs (request_id, user_id, action, old_status, new_status, remarks, created_at)
SELECT
    r.id,
    NULL,
    'mva_migration',
    'legacy',
    r.status,
    'Migrated to MVA workflow structure',
    NOW()
FROM requests r
WHERE NOT EXISTS (
    SELECT 1 FROM request_logs rl
    WHERE rl.request_id = r.id
      AND rl.action = 'mva_migration'
)
LIMIT 1000;

-- ===============================================================
-- Verification Queries (Run these to verify migration success)
-- ===============================================================

-- Verify new columns exist
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'constructlink_db'
  AND TABLE_NAME = 'requests'
  AND COLUMN_NAME IN ('verified_by', 'verified_at', 'authorized_by', 'authorized_at', 'approved_at', 'declined_by', 'declined_at', 'decline_reason');

-- Verify ENUM values updated
SELECT COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'constructlink_db'
  AND TABLE_NAME = 'requests'
  AND COLUMN_NAME = 'status';

-- Verify foreign keys created
SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'constructlink_db'
  AND TABLE_NAME = 'requests'
  AND CONSTRAINT_NAME LIKE 'fk_requests_%'
  AND CONSTRAINT_NAME IN ('fk_requests_verified_by', 'fk_requests_authorized_by', 'fk_requests_declined_by');

-- Verify indexes created
SHOW INDEX FROM requests WHERE Key_name IN ('idx_requests_workflow', 'idx_requests_maker');

-- Check request status distribution
SELECT status, COUNT(*) as count
FROM requests
GROUP BY status
ORDER BY count DESC;

-- ===============================================================
-- Rollback Script (Use only if migration needs to be reverted)
-- ===============================================================
/*
-- WARNING: This will remove all MVA workflow data
-- Backup your database before running this!

ALTER TABLE requests
DROP FOREIGN KEY IF EXISTS fk_requests_verified_by,
DROP FOREIGN KEY IF EXISTS fk_requests_authorized_by,
DROP FOREIGN KEY IF EXISTS fk_requests_declined_by;

ALTER TABLE requests
DROP INDEX IF EXISTS idx_requests_workflow,
DROP INDEX IF EXISTS idx_requests_maker;

ALTER TABLE requests
DROP COLUMN IF EXISTS verified_by,
DROP COLUMN IF EXISTS verified_at,
DROP COLUMN IF EXISTS authorized_by,
DROP COLUMN IF EXISTS authorized_at,
DROP COLUMN IF EXISTS approved_at,
DROP COLUMN IF EXISTS declined_by,
DROP COLUMN IF EXISTS declined_at,
DROP COLUMN IF EXISTS decline_reason;

ALTER TABLE requests
MODIFY COLUMN status ENUM('Draft', 'Submitted', 'Reviewed', 'Forwarded', 'Approved', 'Declined', 'Procured') DEFAULT 'Draft';

DELETE FROM request_logs WHERE action = 'mva_migration';
*/
