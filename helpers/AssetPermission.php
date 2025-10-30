<?php
/**
 * ConstructLink™ Asset Permission Helper
 *
 * This helper class provides a centralized permission system for asset operations,
 * eliminating hardcoded role checks throughout the application.
 *
 * PERMISSION STRUCTURE:
 * - Permissions are stored as JSON arrays in the roles table
 * - Auth class provides hasPermission() method for checking permissions
 * - This class defines all asset-related permission constants
 *
 * USAGE PATTERN:
 *
 * Before (Hardcoded roles):
 * ```php
 * if (in_array($userRole, ['System Admin', 'Asset Director'])) {
 *     // allow action
 * }
 * ```
 *
 * After (Permission-based):
 * ```php
 * if (AssetPermission::can('asset.edit')) {
 *     // allow action
 * }
 * ```
 *
 * @package ConstructLink
 * @version 1.0.0
 */

class AssetPermission {
    /**
     * Asset Permission Constants
     */

    // Asset Viewing Permissions
    const VIEW_ALL_ASSETS = 'view_all_assets';
    const VIEW_PROJECT_ASSETS = 'view_project_assets';
    const VIEW_FINANCIAL_DATA = 'view_financial_data';

    // Asset Management Permissions
    const CREATE_ASSET = 'edit_assets'; // Uses edit_assets permission
    const EDIT_ASSET = 'edit_assets';
    const DELETE_ASSET = 'delete_assets';
    const FLAG_IDLE_ASSETS = 'flag_idle_assets';

    // Asset Operations Permissions
    const RELEASE_ASSETS = 'release_assets';
    const RECEIVE_ASSETS = 'receive_assets';
    const APPROVE_TRANSFERS = 'approve_transfers';
    const INITIATE_TRANSFERS = 'initiate_transfers';

    // Asset Workflow Permissions (MVA)
    const SUBMIT_FOR_VERIFICATION = 'edit_assets'; // Same as create/edit
    const VERIFY_ASSET = 'view_financial_data'; // Finance Director role
    const AUTHORIZE_ASSET = 'approve_transfers'; // Asset Director role

    // Asset Disposal Permissions
    const APPROVE_DISPOSAL = 'approve_disposal';

    // Asset Maintenance Permissions
    const MANAGE_MAINTENANCE = 'manage_maintenance';

    // Withdrawal Permissions
    const MANAGE_WITHDRAWALS = 'manage_withdrawals';
    const REQUEST_WITHDRAWALS = 'request_withdrawals';

    // Borrowed Tools Permissions
    const MANAGE_BORROWED_TOOLS = 'manage_borrowed_tools';

    // Incident Permissions
    const MANAGE_INCIDENTS = 'manage_incidents';

    // Report Permissions
    const VIEW_REPORTS = 'view_reports';
    const VIEW_PROJECT_REPORTS = 'view_project_reports';

    /**
     * Check if current user has permission
     *
     * @param string $permission Permission to check
     * @return bool True if user has permission
     */
    public static function can($permission) {
        $auth = Auth::getInstance();
        return $auth->hasPermission($permission);
    }

    /**
     * Check if current user has any of the given permissions
     *
     * @param array $permissions Array of permissions to check
     * @return bool True if user has at least one permission
     */
    public static function canAny(array $permissions) {
        foreach ($permissions as $permission) {
            if (self::can($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if current user has all of the given permissions
     *
     * @param array $permissions Array of permissions to check
     * @return bool True if user has all permissions
     */
    public static function canAll(array $permissions) {
        foreach ($permissions as $permission) {
            if (!self::can($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Throw 403 error if user doesn't have permission
     *
     * @param string $permission Permission to check
     * @return void
     * @throws void Redirects to 403 page if permission denied
     */
    public static function requirePermission($permission) {
        if (!self::can($permission)) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            exit;
        }
    }

    /**
     * Check if user can view assets
     *
     * @return bool True if user can view assets
     */
    public static function canViewAssets() {
        return self::canAny([
            self::VIEW_ALL_ASSETS,
            self::VIEW_PROJECT_ASSETS
        ]);
    }

    /**
     * Check if user can create/edit assets
     *
     * @return bool True if user can create/edit assets
     */
    public static function canEditAssets() {
        return self::can(self::EDIT_ASSET);
    }

    /**
     * Check if user can delete assets
     *
     * @return bool True if user can delete assets
     */
    public static function canDeleteAssets() {
        return self::can(self::DELETE_ASSET);
    }

    /**
     * Check if user can verify assets (Finance Director)
     *
     * @return bool True if user can verify assets
     */
    public static function canVerifyAssets() {
        return self::can(self::VERIFY_ASSET);
    }

    /**
     * Check if user can authorize assets (Asset Director)
     *
     * @return bool True if user can authorize assets
     */
    public static function canAuthorizeAssets() {
        return self::can(self::AUTHORIZE_ASSET);
    }

    /**
     * Check if user can manage withdrawals
     *
     * @return bool True if user can manage withdrawals
     */
    public static function canManageWithdrawals() {
        return self::can(self::MANAGE_WITHDRAWALS);
    }

    /**
     * Check if user can request withdrawals
     *
     * @return bool True if user can request withdrawals
     */
    public static function canRequestWithdrawals() {
        return self::can(self::REQUEST_WITHDRAWALS);
    }

    /**
     * Check if user can release assets
     *
     * @return bool True if user can release assets
     */
    public static function canReleaseAssets() {
        return self::can(self::RELEASE_ASSETS);
    }

    /**
     * Check if user can receive assets
     *
     * @return bool True if user can receive assets
     */
    public static function canReceiveAssets() {
        return self::can(self::RECEIVE_ASSETS);
    }

    /**
     * Check if user can approve transfers
     *
     * @return bool True if user can approve transfers
     */
    public static function canApproveTransfers() {
        return self::can(self::APPROVE_TRANSFERS);
    }

    /**
     * Check if user can initiate transfers
     *
     * @return bool True if user can initiate transfers
     */
    public static function canInitiateTransfers() {
        return self::can(self::INITIATE_TRANSFERS);
    }

    /**
     * Check if user can manage maintenance
     *
     * @return bool True if user can manage maintenance
     */
    public static function canManageMaintenance() {
        return self::can(self::MANAGE_MAINTENANCE);
    }

    /**
     * Check if user can manage borrowed tools
     *
     * @return bool True if user can manage borrowed tools
     */
    public static function canManageBorrowedTools() {
        return self::can(self::MANAGE_BORROWED_TOOLS);
    }

    /**
     * Check if user can manage incidents
     *
     * @return bool True if user can manage incidents
     */
    public static function canManageIncidents() {
        return self::can(self::MANAGE_INCIDENTS);
    }

    /**
     * Check if user can approve disposal
     *
     * @return bool True if user can approve disposal
     */
    public static function canApproveDisposal() {
        return self::can(self::APPROVE_DISPOSAL);
    }

    /**
     * Check if user can view reports
     *
     * @return bool True if user can view reports
     */
    public static function canViewReports() {
        return self::canAny([
            self::VIEW_REPORTS,
            self::VIEW_PROJECT_REPORTS
        ]);
    }

    /**
     * Check if user can view financial data
     *
     * @return bool True if user can view financial data
     */
    public static function canViewFinancialData() {
        return self::can(self::VIEW_FINANCIAL_DATA);
    }

    /**
     * Get permission display name
     *
     * @param string $permission Permission constant
     * @return string Display name
     */
    public static function getPermissionName($permission) {
        $names = [
            self::VIEW_ALL_ASSETS => 'View All Assets',
            self::VIEW_PROJECT_ASSETS => 'View Project Assets',
            self::VIEW_FINANCIAL_DATA => 'View Financial Data',
            self::EDIT_ASSET => 'Create/Edit Assets',
            self::DELETE_ASSET => 'Delete Assets',
            self::FLAG_IDLE_ASSETS => 'Flag Idle Assets',
            self::RELEASE_ASSETS => 'Release Assets',
            self::RECEIVE_ASSETS => 'Receive Assets',
            self::APPROVE_TRANSFERS => 'Approve Transfers',
            self::INITIATE_TRANSFERS => 'Initiate Transfers',
            self::APPROVE_DISPOSAL => 'Approve Disposal',
            self::MANAGE_MAINTENANCE => 'Manage Maintenance',
            self::MANAGE_WITHDRAWALS => 'Manage Withdrawals',
            self::REQUEST_WITHDRAWALS => 'Request Withdrawals',
            self::MANAGE_BORROWED_TOOLS => 'Manage Borrowed Tools',
            self::MANAGE_INCIDENTS => 'Manage Incidents',
            self::VIEW_REPORTS => 'View Reports',
            self::VIEW_PROJECT_REPORTS => 'View Project Reports',
        ];

        return $names[$permission] ?? $permission;
    }

    /**
     * Get all asset-related permissions
     *
     * @return array Array of permission constants
     */
    public static function getAllPermissions() {
        return [
            self::VIEW_ALL_ASSETS,
            self::VIEW_PROJECT_ASSETS,
            self::VIEW_FINANCIAL_DATA,
            self::EDIT_ASSET,
            self::DELETE_ASSET,
            self::FLAG_IDLE_ASSETS,
            self::RELEASE_ASSETS,
            self::RECEIVE_ASSETS,
            self::APPROVE_TRANSFERS,
            self::INITIATE_TRANSFERS,
            self::APPROVE_DISPOSAL,
            self::MANAGE_MAINTENANCE,
            self::MANAGE_WITHDRAWALS,
            self::REQUEST_WITHDRAWALS,
            self::MANAGE_BORROWED_TOOLS,
            self::MANAGE_INCIDENTS,
            self::VIEW_REPORTS,
            self::VIEW_PROJECT_REPORTS,
        ];
    }

    /**
     * Get permissions grouped by category
     *
     * @return array Permissions grouped by category
     */
    public static function getGroupedPermissions() {
        return [
            'Asset Viewing' => [
                self::VIEW_ALL_ASSETS => 'View All Assets',
                self::VIEW_PROJECT_ASSETS => 'View Project Assets',
                self::VIEW_FINANCIAL_DATA => 'View Financial Data',
            ],
            'Asset Management' => [
                self::EDIT_ASSET => 'Create/Edit Assets',
                self::DELETE_ASSET => 'Delete Assets',
                self::FLAG_IDLE_ASSETS => 'Flag Idle Assets',
            ],
            'Asset Operations' => [
                self::RELEASE_ASSETS => 'Release Assets',
                self::RECEIVE_ASSETS => 'Receive Assets',
                self::APPROVE_TRANSFERS => 'Approve Transfers',
                self::INITIATE_TRANSFERS => 'Initiate Transfers',
            ],
            'Withdrawals' => [
                self::MANAGE_WITHDRAWALS => 'Manage Withdrawals',
                self::REQUEST_WITHDRAWALS => 'Request Withdrawals',
            ],
            'Maintenance & Incidents' => [
                self::MANAGE_MAINTENANCE => 'Manage Maintenance',
                self::MANAGE_INCIDENTS => 'Manage Incidents',
                self::MANAGE_BORROWED_TOOLS => 'Manage Borrowed Tools',
            ],
            'Reports & Disposal' => [
                self::VIEW_REPORTS => 'View Reports',
                self::VIEW_PROJECT_REPORTS => 'View Project Reports',
                self::APPROVE_DISPOSAL => 'Approve Disposal',
            ],
        ];
    }

    /**
     * Map legacy role names to permissions for backward compatibility
     * This helps during migration phase
     *
     * @param string $roleName Role name
     * @return array Array of permissions
     */
    public static function getRolePermissions($roleName) {
        $rolePermissions = [
            'System Admin' => [
                self::VIEW_ALL_ASSETS,
                self::EDIT_ASSET,
                self::DELETE_ASSET,
                self::APPROVE_TRANSFERS,
                self::APPROVE_DISPOSAL,
                self::RELEASE_ASSETS,
                self::RECEIVE_ASSETS,
                self::MANAGE_MAINTENANCE,
                self::MANAGE_INCIDENTS,
                self::MANAGE_WITHDRAWALS,
                self::REQUEST_WITHDRAWALS,
                self::VIEW_REPORTS,
                self::VIEW_FINANCIAL_DATA,
            ],
            'Finance Director' => [
                self::VIEW_ALL_ASSETS,
                self::APPROVE_DISPOSAL,
                self::VIEW_REPORTS,
                self::VIEW_FINANCIAL_DATA,
            ],
            'Asset Director' => [
                self::VIEW_ALL_ASSETS,
                self::EDIT_ASSET,
                self::APPROVE_TRANSFERS,
                self::VIEW_REPORTS,
                self::MANAGE_MAINTENANCE,
                self::MANAGE_INCIDENTS,
                self::FLAG_IDLE_ASSETS,
                self::MANAGE_WITHDRAWALS,
                self::RELEASE_ASSETS,
                self::RECEIVE_ASSETS,
                self::REQUEST_WITHDRAWALS,
            ],
            'Procurement Officer' => [
                self::VIEW_ALL_ASSETS,
                self::RECEIVE_ASSETS,
            ],
            'Warehouseman' => [
                self::VIEW_PROJECT_ASSETS,
                self::RELEASE_ASSETS,
                self::RECEIVE_ASSETS,
                self::MANAGE_WITHDRAWALS,
                self::MANAGE_BORROWED_TOOLS,
                self::REQUEST_WITHDRAWALS,
                self::VIEW_ALL_ASSETS,
            ],
            'Project Manager' => [
                self::VIEW_PROJECT_ASSETS,
                self::REQUEST_WITHDRAWALS,
                self::INITIATE_TRANSFERS,
                self::MANAGE_INCIDENTS,
                self::VIEW_PROJECT_REPORTS,
                self::RECEIVE_ASSETS,
                self::MANAGE_WITHDRAWALS,
            ],
            'Site Inventory Clerk' => [
                self::VIEW_PROJECT_ASSETS,
                self::REQUEST_WITHDRAWALS,
                self::MANAGE_INCIDENTS,
                self::MANAGE_BORROWED_TOOLS,
            ],
        ];

        return $rolePermissions[$roleName] ?? [];
    }
}
