<?php
/**
 * ConstructLink Permissions Configuration
 *
 * This configuration file defines role-based permissions for all modules.
 * Each permission maps to a list of roles that are authorized to perform that action.
 *
 * IMPORTANT: Changes to this configuration affect system security.
 * Always test permission changes in a development environment first.
 */

return [
    /**
     * Borrowed Tools Module Permissions
     *
     * Defines which roles can perform specific actions in the borrowed tools module.
     */

    /**
     * Create Batch
     * Initiate a new borrowed tools request
     */
    'borrowed_tools.create' => [
        'System Admin',
        'Warehouseman',
        'Site Inventory Clerk',
    ],

    /**
     * View Batches
     * View borrowed tools batches and their details
     */
    'borrowed_tools.view' => [
        'System Admin',
        'Warehouseman',
        'Site Inventory Clerk',
        'Project Manager',
        'Asset Director',
        'Finance Director',
    ],

    /**
     * Verify Batch
     * Manager verification step in MVA workflow (critical tools only)
     */
    'borrowed_tools.verify' => [
        'System Admin',
        'Project Manager',
    ],

    /**
     * Approve Batch
     * Director approval step in MVA workflow (critical tools only)
     */
    'borrowed_tools.approve' => [
        'System Admin',
        'Asset Director',
        'Finance Director',
    ],

    /**
     * Release Tools
     * Mark tools as physically released to borrower
     */
    'borrowed_tools.release' => [
        'System Admin',
        'Warehouseman',
    ],

    /**
     * Return Tools
     * Process tool returns and update condition
     */
    'borrowed_tools.return' => [
        'System Admin',
        'Warehouseman',
        'Site Inventory Clerk',
    ],

    /**
     * Cancel Batch
     * Cancel a borrowed tools batch (status-dependent)
     */
    'borrowed_tools.cancel' => [
        'System Admin',
        'Warehouseman',
        'Project Manager',
        'Asset Director',
        'Finance Director',
    ],

    /**
     * Edit Batch
     * Modify batch details (status-dependent)
     */
    'borrowed_tools.edit' => [
        'System Admin',
        'Warehouseman',
    ],

    /**
     * Delete Batch
     * Permanently delete a batch (requires specific status)
     */
    'borrowed_tools.delete' => [
        'System Admin',
    ],

    /**
     * View Statistics
     * Access to statistics dashboard and analytics
     */
    'borrowed_tools.view_statistics' => [
        'System Admin',
        'Warehouseman',
        'Site Inventory Clerk',
        'Project Manager',
        'Asset Director',
        'Finance Director',
    ],

    /**
     * MVA Oversight
     * View and manage MVA workflow stages
     */
    'borrowed_tools.mva_oversight' => [
        'System Admin',
        'Finance Director',
        'Asset Director',
    ],

    /**
     * Print Borrowed Tools
     * Generate printable reports and documents
     */
    'borrowed_tools.print' => [
        'System Admin',
        'Warehouseman',
        'Site Inventory Clerk',
        'Project Manager',
        'Asset Director',
        'Finance Director',
    ],

    /**
     * Extend Borrowing Period
     * Request extension for borrowed tools
     */
    'borrowed_tools.extend' => [
        'System Admin',
        'Warehouseman',
        'Project Manager',
        'Asset Director',
    ],

    /**
     * Verify Critical Tools
     * Manager-level verification for high-value tools
     */
    'borrowed_tools.verify_critical' => [
        'System Admin',
        'Project Manager',
    ],

    /**
     * Approve Critical Tools
     * Director-level approval for high-value tools
     */
    'borrowed_tools.approve_critical' => [
        'System Admin',
        'Asset Director',
        'Finance Director',
    ],

    /**
     * Create Across Any Project
     * Create borrowed tool requests for any project (not limited to assigned project)
     */
    'borrowed_tools.create_any_project' => [
        'System Admin',
        'Asset Director',
        'Finance Director',
    ],

    /**
     * View All Projects
     * View borrowed tools across all projects (oversight access)
     */
    'borrowed_tools.view_all_projects' => [
        'System Admin',
        'Asset Director',
        'Finance Director',
    ],

    /**
     * Assets Module Permissions
     *
     * Defines which roles can perform specific actions in the assets/inventory module.
     * Permissions are mapped to controller methods for centralized access control.
     */

    /**
     * Asset Listing & Viewing
     */
    'assets.index' => [
        'System Admin',
        'Finance Director',
        'Asset Director',
        'Procurement Officer',
        'Warehouseman',
        'Project Manager',
        'Site Inventory Clerk',
    ],

    'assets.view' => [
        'System Admin',
        'Finance Director',
        'Asset Director',
        'Procurement Officer',
        'Warehouseman',
        'Project Manager',
        'Site Inventory Clerk',
    ],

    /**
     * Asset Creation & Editing
     */
    'assets.create' => [
        'System Admin',
        'Asset Director',
        'Warehouseman',
        'Project Manager',
    ],

    'assets.edit' => [
        'System Admin',
        'Asset Director',
        'Warehouseman',
        'Project Manager',
    ],

    'assets.delete' => [
        'System Admin',
        'Asset Director',
    ],

    /**
     * Legacy Asset Creation (Quantity Addition)
     */
    'assets.legacy_create' => [
        'Warehouseman',
        'System Admin',
    ],

    /**
     * Asset Status Management
     */
    'assets.update_status' => [
        'System Admin',
        'Asset Director',
        'Warehouseman',
        'Project Manager',
    ],

    /**
     * Bulk Operations
     */
    'assets.bulk_update' => [
        'System Admin',
        'Asset Director',
    ],

    /**
     * Asset Reports & Analytics
     */
    'assets.export' => [
        'System Admin',
        'Finance Director',
        'Asset Director',
        'Procurement Officer',
    ],

    'assets.utilization' => [
        'System Admin',
        'Finance Director',
        'Asset Director',
        'Project Manager',
    ],

    'assets.depreciation' => [
        'System Admin',
        'Finance Director',
        'Asset Director',
    ],

    /**
     * Procurement Integration
     */
    'assets.generate_from_procurement' => [
        'System Admin',
        'Procurement Officer',
        'Warehouseman',
    ],

    /**
     * Scanner & QR Operations
     */
    'assets.scanner' => [
        'System Admin',
        'Finance Director',
        'Asset Director',
        'Procurement Officer',
        'Warehouseman',
        'Project Manager',
        'Site Inventory Clerk',
    ],

    /**
     * Location Assignment
     */
    'assets.assign_location' => [
        'Warehouseman',
        'Site Inventory Clerk',
        'System Admin',
    ],

    /**
     * MVA Workflow - Verification (Manager Level)
     */
    'assets.verify_asset' => [
        'Project Manager',
        'System Admin',
    ],

    'assets.batch_verify' => [
        'Site Inventory Clerk',
        'Project Manager',
        'System Admin',
    ],

    'assets.verification_dashboard' => [
        'Site Inventory Clerk',
        'Project Manager',
        'System Admin',
    ],

    'assets.verification_data' => [
        'Site Inventory Clerk',
        'Project Manager',
        'System Admin',
    ],

    'assets.legacy_verify' => [
        'Site Inventory Clerk',
        'Project Manager',
        'System Admin',
    ],

    /**
     * MVA Workflow - Authorization (Director Level)
     */
    'assets.authorize_asset' => [
        'Asset Director',
        'System Admin',
    ],

    'assets.batch_authorize' => [
        'Project Manager',
        'System Admin',
    ],

    'assets.authorization_dashboard' => [
        'Project Manager',
        'System Admin',
    ],

    'assets.authorization_data' => [
        'Project Manager',
        'System Admin',
    ],

    'assets.legacy_authorize' => [
        'Project Manager',
        'System Admin',
    ],

    /**
     * MVA Workflow - Approval (Finance Level)
     */
    'assets.verify' => [
        'Asset Director',
        'System Admin',
    ],

    'assets.authorize' => [
        'Finance Director',
        'System Admin',
    ],

    /**
     * Quality & Condition Validation
     */
    'assets.validate_quality' => [
        'Site Inventory Clerk',
        'Project Manager',
        'System Admin',
    ],

    'assets.reject_verification' => [
        'Site Inventory Clerk',
        'Project Manager',
        'System Admin',
    ],

    'assets.approve_with_conditions' => [
        'Site Inventory Clerk',
        'Project Manager',
        'System Admin',
    ],

    /**
     * Projects Module Permissions
     * (Future expansion - placeholder)
     */
    'projects' => [
        'create' => ['System Admin', 'Project Manager'],
        'view' => ['System Admin', 'Project Manager', 'Warehouseman', 'Site Inventory Clerk', 'Asset Director', 'Finance Director'],
        'edit' => ['System Admin', 'Project Manager'],
        'delete' => ['System Admin'],
    ],

    /**
     * Reports Module Permissions
     * (Future expansion - placeholder)
     */
    'reports' => [
        'view_basic' => ['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager'],
        'view_financial' => ['System Admin', 'Finance Director', 'Asset Director'],
        'export' => ['System Admin', 'Finance Director', 'Asset Director', 'Project Manager'],
    ],
];
