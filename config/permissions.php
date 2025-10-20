<?php
/**
 * ConstructLink Permissions Configuration
 * Developed by: Ranoa Digital Solutions
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
    'borrowed_tools' => [
        /**
         * Create Batch
         * Initiate a new borrowed tools request
         */
        'create' => [
            'System Admin',
            'Warehouseman',
            'Site Inventory Clerk',
        ],

        /**
         * View Batches
         * View borrowed tools batches and their details
         */
        'view' => [
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
        'verify' => [
            'System Admin',
            'Project Manager',
        ],

        /**
         * Approve Batch
         * Director approval step in MVA workflow (critical tools only)
         */
        'approve' => [
            'System Admin',
            'Asset Director',
            'Finance Director',
        ],

        /**
         * Release Tools
         * Mark tools as physically released to borrower
         */
        'release' => [
            'System Admin',
            'Warehouseman',
        ],

        /**
         * Return Tools
         * Process tool returns and update condition
         */
        'return' => [
            'System Admin',
            'Warehouseman',
            'Site Inventory Clerk',
        ],

        /**
         * Cancel Batch
         * Cancel a borrowed tools batch (status-dependent)
         */
        'cancel' => [
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
        'edit' => [
            'System Admin',
            'Warehouseman',
        ],

        /**
         * Delete Batch
         * Permanently delete a batch (requires specific status)
         */
        'delete' => [
            'System Admin',
        ],

        /**
         * View Statistics
         * Access to statistics dashboard and analytics
         */
        'view_statistics' => [
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
        'mva_oversight' => [
            'System Admin',
            'Finance Director',
            'Asset Director',
        ],
    ],

    /**
     * Assets Module Permissions
     * (Future expansion - placeholder)
     */
    'assets' => [
        'create' => ['System Admin', 'Warehouseman'],
        'view' => ['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager', 'Asset Director', 'Finance Director'],
        'edit' => ['System Admin', 'Warehouseman'],
        'delete' => ['System Admin'],
        'transfer' => ['System Admin', 'Warehouseman'],
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
