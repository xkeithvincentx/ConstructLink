-- ================================================================================
-- ConstructLink™ Database Migration
-- Migration: 001_update_asset_permissions
-- Description: Update roles table with comprehensive asset permissions
-- Author: Database Refactor Agent
-- Date: 2025-10-30
-- ================================================================================

-- Set SQL mode and disable autocommit for transaction safety
SET SQL_MODE = "STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- ================================================================================
-- FORWARD MIGRATION (UP)
-- ================================================================================

-- Update System Admin role with comprehensive permissions
UPDATE `roles`
SET `permissions` = JSON_ARRAY(
    'view_all_assets',
    'view_project_assets',
    'edit_assets',
    'delete_assets',
    'approve_transfers',
    'approve_disposal',
    'manage_users',
    'view_reports',
    'view_project_reports',
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
    'view_all_projects',
    'flag_idle_assets',
    'initiate_transfers',
    'manage_borrowed_tools'
),
`description` = 'Full system access and administration'
WHERE `name` = 'System Admin';

-- Update Finance Director role
UPDATE `roles`
SET `permissions` = JSON_ARRAY(
    'view_all_assets',
    'view_project_assets',
    'approve_disposal',
    'view_reports',
    'view_financial_data',
    'approve_high_value_transfers',
    'view_project_assets',
    'approve_procurement',
    'view_procurement_reports',
    'view_all_projects'
),
`description` = 'Financial oversight and verification authority'
WHERE `name` = 'Finance Director';

-- Update Asset Director role
UPDATE `roles`
SET `permissions` = JSON_ARRAY(
    'view_all_assets',
    'view_project_assets',
    'edit_assets',
    'approve_transfers',
    'view_reports',
    'view_project_reports',
    'manage_maintenance',
    'manage_incidents',
    'flag_idle_assets',
    'manage_withdrawals',
    'release_assets',
    'receive_assets',
    'request_withdrawals',
    'view_all_projects',
    'initiate_transfers'
),
`description` = 'Asset management, authorization and oversight'
WHERE `name` = 'Asset Director';

-- Update Procurement Officer role
UPDATE `roles`
SET `permissions` = JSON_ARRAY(
    'view_all_assets',
    'view_project_assets',
    'manage_procurement',
    'receive_assets',
    'manage_vendors',
    'manage_makers',
    'view_procurement_reports',
    'create_procurement',
    'manage_requests',
    'view_all_projects'
),
`description` = 'Procurement and vendor management'
WHERE `name` = 'Procurement Officer';

-- Update Warehouseman role
UPDATE `roles`
SET `permissions` = JSON_ARRAY(
    'view_project_assets',
    'view_all_assets',
    'release_assets',
    'receive_assets',
    'manage_withdrawals',
    'basic_asset_logs',
    'manage_borrowed_tools',
    'request_withdrawals',
    'receive_procurement'
),
`description` = 'Warehouse operations and asset handling'
WHERE `name` = 'Warehouseman';

-- Update Project Manager role
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

-- Update Site Inventory Clerk role
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

-- Verify all roles have been updated
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
-- ROLLBACK MIGRATION (DOWN)
-- ================================================================================
-- To rollback this migration, restore the original permissions from backup
-- Run: 002_rollback_asset_permissions.sql

-- ================================================================================
-- VERIFICATION QUERIES
-- ================================================================================

-- Verify all roles have permissions
-- SELECT name, JSON_PRETTY(permissions) FROM roles ORDER BY id;

-- Check if any roles are missing permissions
-- SELECT name FROM roles WHERE permissions IS NULL OR JSON_LENGTH(permissions) = 0;

-- Count users by role with their permissions
-- SELECT
--     r.name as role_name,
--     COUNT(u.id) as user_count,
--     JSON_LENGTH(r.permissions) as permission_count
-- FROM roles r
-- LEFT JOIN users u ON r.id = u.role_id
-- GROUP BY r.id, r.name
-- ORDER BY r.id;

-- ================================================================================
-- MIGRATION NOTES
-- ================================================================================
--
-- This migration updates the existing roles table with comprehensive asset permissions.
--
-- IMPORTANT:
-- 1. All hardcoded role checks in controllers should be replaced with permission checks
-- 2. Use AssetPermission::can('permission_name') instead of role checks
-- 3. The Auth class already has hasPermission() method implemented
-- 4. Permissions are stored as JSON arrays in the roles.permissions column
--
-- AFFECTED AREAS:
-- - AssetController.php (142+ role checks to be refactored)
-- - AssetTagController.php (multiple role checks)
-- - View files in views/assets/partials/
--
-- TESTING PLAN:
-- 1. Verify all roles have correct permissions
-- 2. Test permission checks in controllers
-- 3. Test workflow transitions (draft -> pending_verification -> pending_authorization -> approved)
-- 4. Test role-specific dashboard views
-- 5. Test asset creation, editing, deletion with different roles
--
-- ================================================================================
