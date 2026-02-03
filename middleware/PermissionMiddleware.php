<?php
/**
 * ConstructLink™ Permission Middleware
 *
 * Centralized permission checking middleware for role-based access control.
 * Eliminates code duplication across controllers by providing a single method
 * for permission verification.
 *
 * FEATURES:
 * - Role-based access control using config/permissions.php
 * - Automatic AJAX vs HTML request detection
 * - Appropriate error responses (JSON for AJAX, 403 page for HTML)
 * - Clean exit after permission denial
 *
 * USAGE:
 * ```php
 * // In controller method
 * PermissionMiddleware::requirePermission('assets.index');
 * ```
 *
 * CONFIGURATION:
 * Permissions are defined in /config/permissions.php
 *
 * @package ConstructLink
 * @version 1.0.0
 */

class PermissionMiddleware
{
    /**
     * Require specific permission for current user
     *
     * Checks if the current authenticated user has the required permission.
     * If not, returns appropriate error response based on request type (AJAX/HTML).
     *
     * @param string $permissionKey Permission key in format 'module.action' (e.g., 'assets.index')
     * @return void Exits script if permission denied
     */
    public static function requirePermission(string $permissionKey): void
    {
        // Get current user
        $auth = Auth::getInstance();
        $currentUser = $auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        // Load permissions configuration
        $permissions = self::loadPermissions();

        // Check if permission exists in config
        if (!isset($permissions[$permissionKey])) {
            error_log("Permission key not found in config: {$permissionKey}");
            self::denyAccess("Permission configuration error");
            return;
        }

        // Get allowed roles for this permission
        $allowedRoles = $permissions[$permissionKey];

        // Check if user's role is in allowed roles
        if (!in_array($userRole, $allowedRoles)) {
            self::denyAccess("Access denied: insufficient permissions");
            return;
        }

        // Permission granted - continue execution
    }

    /**
     * Check if current user has permission (without exiting)
     *
     * @param string $permissionKey Permission key in format 'module.action'
     * @return bool True if user has permission, false otherwise
     */
    public static function hasPermission(string $permissionKey): bool
    {
        $auth = Auth::getInstance();
        $currentUser = $auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        $permissions = self::loadPermissions();

        if (!isset($permissions[$permissionKey])) {
            return false;
        }

        $allowedRoles = $permissions[$permissionKey];
        return in_array($userRole, $allowedRoles);
    }

    /**
     * Check if current user has any of the given permissions
     *
     * @param array $permissionKeys Array of permission keys
     * @return bool True if user has at least one permission
     */
    public static function hasAnyPermission(array $permissionKeys): bool
    {
        foreach ($permissionKeys as $key) {
            if (self::hasPermission($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if current user has all of the given permissions
     *
     * @param array $permissionKeys Array of permission keys
     * @return bool True if user has all permissions
     */
    public static function hasAllPermissions(array $permissionKeys): bool
    {
        foreach ($permissionKeys as $key) {
            if (!self::hasPermission($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Load permissions from configuration file
     *
     * @return array Permissions configuration array
     */
    private static function loadPermissions(): array
    {
        static $permissions = null;

        if ($permissions === null) {
            $configFile = APP_ROOT . '/config/permissions.php';

            if (!file_exists($configFile)) {
                error_log("Permissions config file not found: {$configFile}");
                return [];
            }

            $config = require $configFile;
            $permissions = $config;
        }

        return $permissions;
    }

    /**
     * Deny access with appropriate response
     *
     * Detects if request is AJAX and returns JSON or HTML response accordingly.
     * Exits script after denying access.
     *
     * @param string $message Error message
     * @return void Exits script
     */
    private static function denyAccess(string $message = 'Access denied'): void
    {
        // Set HTTP 403 Forbidden status
        http_response_code(403);

        // Check if this is an AJAX request
        if (self::isAjaxRequest()) {
            // Return JSON response for AJAX
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $message,
                'code' => 403
            ]);
            exit;
        }

        // Return HTML 403 page for regular requests
        $errorPage = APP_ROOT . '/views/errors/403.php';
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            // Fallback if 403 page doesn't exist
            echo '<!DOCTYPE html>
<html>
<head>
    <title>403 Forbidden</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #d9534f; }
    </style>
</head>
<body>
    <h1>403 - Access Denied</h1>
    <p>You do not have permission to access this resource.</p>
    <p><a href="?route=dashboard">Return to Dashboard</a></p>
</body>
</html>';
        }
        exit;
    }

    /**
     * Detect if current request is AJAX
     *
     * @return bool True if AJAX request
     */
    private static function isAjaxRequest(): bool
    {
        // Check X-Requested-With header
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }

        // Check Accept header for JSON
        if (!empty($_SERVER['HTTP_ACCEPT']) &&
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            return true;
        }

        // Check Content-Type header for JSON
        if (!empty($_SERVER['CONTENT_TYPE']) &&
            strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Get all permissions for current user
     *
     * @return array Array of permission keys the user has access to
     */
    public static function getUserPermissions(): array
    {
        $auth = Auth::getInstance();
        $currentUser = $auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        $permissions = self::loadPermissions();
        $userPermissions = [];

        foreach ($permissions as $key => $allowedRoles) {
            if (in_array($userRole, $allowedRoles)) {
                $userPermissions[] = $key;
            }
        }

        return $userPermissions;
    }

    /**
     * Get allowed roles for a specific permission
     *
     * @param string $permissionKey Permission key
     * @return array Array of role names allowed for this permission
     */
    public static function getAllowedRoles(string $permissionKey): array
    {
        $permissions = self::loadPermissions();
        return $permissions[$permissionKey] ?? [];
    }
}
