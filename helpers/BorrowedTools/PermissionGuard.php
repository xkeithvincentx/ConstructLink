<?php
/**
 * ConstructLink™ Borrowed Tools Permission Guard
 * Centralized RBAC permission checking for borrowed tools operations
 * Created during Phase 2.3 refactoring - extracted from BorrowedToolController
 */

class BorrowedToolsPermissionGuard {
    private $auth;
    private $roleConfig;
    private $permissionsConfig;

    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->roleConfig = require APP_ROOT . '/config/roles.php';
        $this->permissionsConfig = require APP_ROOT . '/config/permissions.php';
    }

    /**
     * Check if current user has permission for borrowed tools action
     * Uses permissions configuration from config/permissions.php
     *
     * @param string $action Permission action to check
     * @param mixed $tool Optional tool/batch data for context-specific checks
     * @return bool True if user has permission
     */
    public function hasPermission($action, $tool = null) {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        // System Admin has all permissions
        if ($userRole === config('business_rules.roles.super_admin')) {
            return true;
        }

        // Check if tool is critical (requires full MVA workflow)
        $isCritical = false;
        if ($tool && isset($tool['asset_id'])) {
            require_once APP_ROOT . '/models/BorrowedToolModel.php';
            $borrowedToolModel = new BorrowedToolModel();
            $isCritical = $borrowedToolModel->isCriticalTool(
                $tool['asset_id'],
                $tool['acquisition_cost'] ?? null
            );
        }

        // Handle MVA workflow permissions using configuration
        // NOTE: Using direct permission key lookup (e.g., 'borrowed_tools.create')
        // because permissions.php uses flat keys, not nested arrays
        switch ($action) {
            case 'create':
                // Maker roles from config
                $allowedRoles = $this->permissionsConfig['borrowed_tools.create'] ?? [];
                return in_array($userRole, $allowedRoles);

            case 'create_any_project':
                // Only roles with cross-project access
                $allowedRoles = $this->permissionsConfig['borrowed_tools.create_any_project'] ?? [];
                return in_array($userRole, $allowedRoles);

            case 'verify':
                // Verifier roles (streamlined: auto-approve, critical: require approval)
                if ($isCritical) {
                    $allowedRoles = $this->permissionsConfig['borrowed_tools.verify_critical'] ?? [];
                } else {
                    // Streamlined workflow: makers can verify their own
                    $allowedRoles = array_merge(
                        $this->permissionsConfig['borrowed_tools.verify'] ?? [],
                        $this->permissionsConfig['borrowed_tools.create'] ?? []
                    );
                }
                return in_array($userRole, $allowedRoles);

            case 'approve':
                // Authorizer roles (only for critical tools)
                if (!$isCritical) {
                    return false; // Streamlined tools don't need approval
                }
                $allowedRoles = $this->permissionsConfig['borrowed_tools.approve_critical'] ?? [];
                return in_array($userRole, $allowedRoles);

            case 'borrow':
                // Release to borrower (Equipment Custodian)
                $allowedRoles = $this->permissionsConfig['borrowed_tools.release'] ?? [];
                return in_array($userRole, $allowedRoles);

            case 'return':
                // Anyone who can release can also process returns
                $allowedRoles = $this->permissionsConfig['borrowed_tools.return'] ?? [];
                return in_array($userRole, $allowedRoles);

            case 'extend':
                // Extension requires approval from authorizer
                $allowedRoles = $this->permissionsConfig['borrowed_tools.extend'] ?? [];
                return in_array($userRole, $allowedRoles);

            case 'cancel':
                // Cancel permissions
                $allowedRoles = $this->permissionsConfig['borrowed_tools.cancel'] ?? [];

                // Original maker can cancel if not yet verified
                if (isset($tool['status']) &&
                    $tool['status'] === 'pending_verification' &&
                    isset($tool['created_by']) &&
                    $tool['created_by'] == $currentUser['id']) {
                    return true;
                }

                return in_array($userRole, $allowedRoles);

            case 'view':
                // View permissions (project-scoped)
                $allowedRoles = $this->permissionsConfig['borrowed_tools.view'] ?? [];
                return in_array($userRole, $allowedRoles);

            case 'view_all_projects':
                // View across all projects (oversight roles)
                $allowedRoles = $this->permissionsConfig['borrowed_tools.view_all_projects'] ?? [];
                return in_array($userRole, $allowedRoles);

            case 'print':
                // Print permissions
                $allowedRoles = $this->permissionsConfig['borrowed_tools.print'] ?? [];
                return in_array($userRole, $allowedRoles);

            default:
                // Unknown action, deny by default
                return false;
        }
    }

    /**
     * Require permission or terminate with error
     *
     * @param string $action Permission action to check
     * @param mixed $tool Optional tool/batch data
     * @param int $errorCode HTTP error code (default: 403)
     * @return void Terminates with error if permission denied
     */
    public function requirePermission($action, $tool = null, $errorCode = 403) {
        if (!$this->hasPermission($action, $tool)) {
            require_once APP_ROOT . '/helpers/BorrowedTools/ResponseHelper.php';
            BorrowedToolsResponseHelper::renderError(
                $errorCode,
                'You do not have permission to perform this action'
            );
        }
    }

    /**
     * Check if current user is assigned to a project
     *
     * @return bool True if user has project assignment
     */
    public function hasProjectAssignment() {
        $currentUser = $this->auth->getCurrentUser();
        return !empty($currentUser['current_project_id']);
    }

    /**
     * Require project assignment or terminate with error
     *
     * @return void Terminates with error if no project assignment
     */
    public function requireProjectAssignment() {
        if (!$this->hasProjectAssignment()) {
            require_once APP_ROOT . '/helpers/BorrowedTools/ResponseHelper.php';
            BorrowedToolsResponseHelper::renderError(
                403,
                'You must be assigned to a project to access this feature'
            );
        }
    }

    /**
     * Get project filter for current user
     * Returns null for users with cross-project access
     *
     * @return int|null Project ID or null for all-project access
     */
    public function getProjectFilter() {
        if ($this->hasPermission('view_all_projects')) {
            return null; // Can view all projects
        }

        $currentUser = $this->auth->getCurrentUser();
        return !empty($currentUser['current_project_id'])
            ? $currentUser['current_project_id'] : null;
    }

    /**
     * Get current user data
     *
     * @return array User data
     */
    public function getCurrentUser() {
        return $this->auth->getCurrentUser();
    }

    /**
     * Check if user is authenticated
     *
     * @return bool True if authenticated
     */
    public function isAuthenticated() {
        return $this->auth->isAuthenticated();
    }
}
