<?php
/**
 * ConstructLink™ Configuration
 * Main application configuration file
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Load environment configuration if it exists
if (file_exists(__DIR__ . '/.env.php')) {
    require_once __DIR__ . '/.env.php';
}

// Set default environment values if not defined
if (!defined('ENV_DEBUG')) define('ENV_DEBUG', false);
if (!defined('ENV_DB_HOST')) define('ENV_DB_HOST', '127.0.0.1');
if (!defined('ENV_DB_NAME')) define('ENV_DB_NAME', 'constructlink_db');
if (!defined('ENV_DB_USER')) define('ENV_DB_USER', 'root');
if (!defined('ENV_DB_PASS')) define('ENV_DB_PASS', '');
if (!defined('ENV_HMAC_SECRET')) define('ENV_HMAC_SECRET', 'default-secret-key-change-this');
if (!defined('ENV_CACHE_ENABLED')) define('ENV_CACHE_ENABLED', true);
if (!defined('ENV_MAIL_HOST')) define('ENV_MAIL_HOST', '');
if (!defined('ENV_MAIL_PORT')) define('ENV_MAIL_PORT', 587);
if (!defined('ENV_MAIL_USERNAME')) define('ENV_MAIL_USERNAME', '');
if (!defined('ENV_MAIL_PASSWORD')) define('ENV_MAIL_PASSWORD', '');
if (!defined('ENV_MAIL_FROM_EMAIL')) define('ENV_MAIL_FROM_EMAIL', 'noreply@constructlink.com');
if (!defined('ENV_MAIL_FROM_NAME')) define('ENV_MAIL_FROM_NAME', 'ConstructLink™ System');

// Application Configuration
if (!defined('APP_VERSION')) define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'Asia/Manila');
define('APP_LOCALE', 'en_US');
define('APP_DEBUG', ENV_DEBUG ?? false);

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Database Configuration
define('DB_HOST', ENV_DB_HOST);
define('DB_NAME', ENV_DB_NAME);
define('DB_USER', ENV_DB_USER);
define('DB_PASS', ENV_DB_PASS);
define('DB_CHARSET', 'utf8mb4');

// Security Configuration
define('CSRF_TOKEN_NAME', '_token');
define('SESSION_LIFETIME', 3600 * 8); // 8 hours
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// SecureLink™ HMAC Configuration
define('HMAC_SECRET_KEY', ENV_HMAC_SECRET);
define('HMAC_ALGORITHM', 'sha256');
define('QR_CODE_SIZE', 200);
define('QR_CODE_MARGIN', 2);

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
define('UPLOAD_PATH', APP_ROOT . '/uploads/');

// Logging Configuration
define('LOG_PATH', APP_ROOT . '/logs/');
define('LOG_LEVEL', APP_DEBUG ? 'DEBUG' : 'INFO');
define('LOG_ROTATION_DAYS', 7);

// Cache Configuration
define('CACHE_ENABLED', ENV_CACHE_ENABLED ?? true);
define('CACHE_PATH', APP_ROOT . '/cache/');
define('CACHE_LIFETIME', 3600); // 1 hour

// Email Configuration (for notifications)
define('MAIL_HOST', ENV_MAIL_HOST ?? '');
define('MAIL_PORT', ENV_MAIL_PORT ?? 587);
define('MAIL_USERNAME', ENV_MAIL_USERNAME ?? '');
define('MAIL_PASSWORD', ENV_MAIL_PASSWORD ?? '');
define('MAIL_FROM_EMAIL', ENV_MAIL_FROM_EMAIL ?? 'noreply@constructlink.com');
define('MAIL_FROM_NAME', ENV_MAIL_FROM_NAME ?? 'ConstructLink™ System');

// Asset Configuration - ISO 55000:2024 Compliant
if (!defined('ASSET_ORG_CODE')) define('ASSET_ORG_CODE', 'CON'); // ISO 55000:2024 Organization Code
if (!defined('ASSET_REF_PREFIX')) define('ASSET_REF_PREFIX', 'CL'); // Legacy compatibility
if (!defined('ASSET_REF_LENGTH')) define('ASSET_REF_LENGTH', 10); // Legacy compatibility
if (!defined('ASSET_REF_INCLUDE_YEAR')) define('ASSET_REF_INCLUDE_YEAR', true); // Legacy compatibility

// Maintenance Configuration
if (!defined('MAINTENANCE_MODE')) define('MAINTENANCE_MODE', false);
if (!defined('MAINTENANCE_MESSAGE')) define('MAINTENANCE_MESSAGE', 'We are currently performing system maintenance. Please check back shortly.');
if (!defined('MAINTENANCE_ALLOWED_IPS')) define('MAINTENANCE_ALLOWED_IPS', ['127.0.0.1', '::1']);

// Assets URL Configuration
if (!defined('ASSETS_URL')) define('ASSETS_URL', '/assets');

// Asset Status Constants
define('ASSET_STATUS_AVAILABLE', 'available');
define('ASSET_STATUS_IN_USE', 'in_use');
define('ASSET_STATUS_BORROWED', 'borrowed');
define('ASSET_STATUS_MAINTENANCE', 'under_maintenance');
define('ASSET_STATUS_RETIRED', 'retired');

// Withdrawal Status Constants
define('WITHDRAWAL_STATUS_PENDING', 'pending');
define('WITHDRAWAL_STATUS_RELEASED', 'released');
define('WITHDRAWAL_STATUS_RETURNED', 'returned');
define('WITHDRAWAL_STATUS_CANCELED', 'canceled');

// Transfer Type Constants
define('TRANSFER_TYPE_TEMPORARY', 'temporary');
define('TRANSFER_TYPE_PERMANENT', 'permanent');

// Incident Type Constants
define('INCIDENT_TYPE_LOST', 'lost');
define('INCIDENT_TYPE_DAMAGED', 'damaged');
define('INCIDENT_TYPE_OTHER', 'other');

// Maintenance Type Constants
define('MAINTENANCE_TYPE_PREVENTIVE', 'preventive');
define('MAINTENANCE_TYPE_CORRECTIVE', 'corrective');

// Role Constants
define('ROLE_SYSTEM_ADMIN', 1);
define('ROLE_FINANCE_DIRECTOR', 2);
define('ROLE_ASSET_DIRECTOR', 3);
define('ROLE_PROCUREMENT_OFFICER', 4);
define('ROLE_WAREHOUSEMAN', 5);
define('ROLE_PROJECT_MANAGER', 6);
define('ROLE_SITE_INVENTORY_CLERK', 7);

// Permission Constants
define('PERM_VIEW_ALL_ASSETS', 'view_all_assets');
define('PERM_EDIT_ASSETS', 'edit_assets');
define('PERM_DELETE_ASSETS', 'delete_assets');
define('PERM_APPROVE_TRANSFERS', 'approve_transfers');
define('PERM_APPROVE_DISPOSAL', 'approve_disposal');
define('PERM_MANAGE_USERS', 'manage_users');
define('PERM_VIEW_REPORTS', 'view_reports');
define('PERM_MANAGE_PROCUREMENT', 'manage_procurement');
define('PERM_RELEASE_ASSETS', 'release_assets');
define('PERM_RECEIVE_ASSETS', 'receive_assets');

// Pagination Configuration
define('PAGINATION_PER_PAGE_DEFAULT', 20);
define('PAGINATION_PER_PAGE_ASSETS', 20);
define('PAGINATION_PER_PAGE_BORROWED_TOOLS', 5); // Reduced to 5 for better desktop viewport fit
define('PAGINATION_PER_PAGE_WITHDRAWALS', 20);
define('PAGINATION_PER_PAGE_TRANSFERS', 5); // Match borrowed-tools default for better desktop viewport fit

// Error Handling
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_PATH . 'php_errors.log');
}

// Set memory and execution limits
ini_set('memory_limit', '1G');
ini_set('max_execution_time', 120);

// Configuration cache for loaded config files
$GLOBALS['_config_cache'] = [];

/**
 * Get configuration value with default
 * Supports both constants and config file keys using dot notation
 *
 * @param string $key Configuration key (e.g., 'business_rules.critical_tool_threshold')
 * @param mixed $default Default value if key not found
 * @return mixed Configuration value or default
 */
function config($key, $default = null) {
    // First check if it's a constant
    if (defined($key)) {
        return constant($key);
    }

    // Handle dot notation for config files (e.g., 'business_rules.critical_tool_threshold')
    if (strpos($key, '.') !== false) {
        $parts = explode('.', $key, 2);
        $configFile = $parts[0];
        $configKey = $parts[1];

        // Load config file if not already cached
        if (!isset($GLOBALS['_config_cache'][$configFile])) {
            $configPath = APP_ROOT . '/config/' . $configFile . '.php';
            if (file_exists($configPath)) {
                $GLOBALS['_config_cache'][$configFile] = require $configPath;
            } else {
                $GLOBALS['_config_cache'][$configFile] = [];
            }
        }

        // Navigate through nested keys (e.g., 'mva_workflow.critical_requires_verification')
        $value = $GLOBALS['_config_cache'][$configFile];
        foreach (explode('.', $configKey) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    return $default;
}

// Function to check if feature is enabled
function feature_enabled($feature) {
    $key = 'FEATURE_' . strtoupper($feature);
    return defined($key) ? constant($key) : false;
}
?>
