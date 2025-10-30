-- ================================================================================
-- ConstructLink™ Database Migration Rollback
-- Migration: 002_rollback_asset_permissions
-- Description: Rollback to original asset permissions if needed
-- Author: Database Refactor Agent
-- Date: 2025-10-30
-- ================================================================================

-- Set SQL mode and disable autocommit for transaction safety
SET SQL_MODE = "STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- ================================================================================
-- ROLLBACK MIGRATION (Restore original permissions)
-- ================================================================================

-- Restore System Admin original permissions
UPDATE `roles`
SET `permissions` = JSON_ARRAY(
    'view_all_assets',
    'edit_assets',
    'delete_assets',
    'approve_transfers',
    'approve_disposal',
    'manage_users',
    'view_reports',
    'manage_procurement',
    'release_assets',
    'receive_assets',
    'manage_maintenance',
    'manage_incidents',
    'manage_master_data',
    'system_administration',
    'manage_withdrawals',
    'request_withdrawals',
    'view_financial_data',
    'approve_procurement',
    'manage_requests',
    'assign_projects',
    'view_all_projects'
),
`description` = 'Full system access and administration'
WHERE `name` = 'System Admin';

-- Restore Finance Director original permissions
UPDATE `roles`
SET `permissions` = JSON_ARRAY(
    'view_all_assets',
    'approve_disposal',
    'view_reports',
    'view_financial_data',
    'approve_high_value_transfers',
    'view_project_assets',
    'approve_procurement',
    'view_procurement_reports',
    'view_all_projects'
),
`description` = 'Financial oversight and approval authority'
WHERE `name` = 'Finance Director';

-- Restore Asset Director original permissions
UPDATE `roles`
SET `permissions` = JSON_ARRAY(
    'view_all_assets',
    'edit_assets',
    'approve_transfers',
    'view_reports',
    'manage_maintenance',
    'manage_incidents',
    'flag_idle_assets',
    'manage_withdrawals',
    'release_assets',
    'receive_assets',
    'request_withdrawals',
    'view_project_assets',
    'view_all_projects'
),
`description` = 'Asset management and oversight'
WHERE `name` = 'Asset Director';

-- Restore Procurement Officer original permissions
UPDATE `roles`
SET `permissions` = JSON_ARRAY(
    'view_all_assets',
    'manage_procurement',
    'receive_assets',
    'manage_vendors',
    'manage_makers',
    'view_procurement_reports',
    'view_project_assets',
    'create_procurement',
    'manage_requests',
    'view_all_projects'
),
`description` = 'Procurement and vendor management'
WHERE `name` = 'Procurement Officer';

-- Restore Warehouseman original permissions
UPDATE `roles`
SET `permissions` = JSON_ARRAY(
    'view_project_assets',
    'release_assets',
    'receive_assets',
    'manage_withdrawals',
    'basic_asset_logs',
    'manage_borrowed_tools',
    'request_withdrawals',
    'view_all_assets',
    'receive_procurement'
),
`description` = 'Warehouse operations and asset handling'
WHERE `name` = 'Warehouseman';

-- Restore Project Manager original permissions
UPDATE `roles`
SET `permissions` = JSON_ARRAY(
    'view_project_assets',
    'request_withdrawals',
    'approve_site_actions',
    'initiate_transfers',
    'manage_incidents',
    'view_project_reports',
    'receive_assets',
    'manage_withdrawals',
    'create_requests',
    'approve_requests'
),
`description` = 'Project-level asset management'
WHERE `name` = 'Project Manager';

-- Restore Site Inventory Clerk original permissions
UPDATE `roles`
SET `permissions` = JSON_ARRAY(
    'view_project_assets',
    'request_withdrawals',
    'scan_qr_codes',
    'log_borrower_info',
    'manage_incidents',
    'view_site_assets',
    'manage_borrowed_tools',
    'create_requests'
),
`description` = 'Site-level inventory operations'
WHERE `name` = 'Site Inventory Clerk';

-- Verify rollback
SELECT
    id,
    name,
    JSON_LENGTH(permissions) as permission_count,
    description
FROM roles
ORDER BY id;

-- Commit the transaction
COMMIT;

-- ================================================================================
-- ROLLBACK NOTES
-- ================================================================================
--
-- This script restores the original permissions from the database schema.
-- Use this if the new permission system causes issues.
--
-- WARNING: If you rollback this migration, you must also:
-- 1. Restore original controller code with hardcoded role checks
-- 2. Remove usage of AssetPermission helper class
-- 3. Restore original view files
--
-- ================================================================================
