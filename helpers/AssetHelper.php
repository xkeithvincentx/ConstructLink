<?php
/**
 * AssetHelper - Centralized Asset Loading
 * Handles loading of modular CSS and JavaScript files with cache busting
 *
 * PURPOSE:
 * - Eliminate inline styles/scripts from PHP views
 * - Enable browser caching with version control
 * - Provide consistent asset loading across the application
 * - Support CDN deployment in the future
 *
 * USAGE:
 * ```php
 * // In view file
 * echo AssetHelper::loadModuleCSS('borrowed-tools');
 * echo AssetHelper::loadModuleJS('index');
 * ```
 *
 * @package ConstructLink
 */

class AssetHelper
{
    /**
     * Base URL for assets
     * Override in config if using CDN
     */
    private static $assetsBaseUrl = '/assets';

    /**
     * Application version for cache busting
     * Updated automatically on deployment
     */
    private static $version = null;

    /**
     * Get the current application version
     * @return string
     */
    private static function getVersion(): string
    {
        if (self::$version === null) {
            // Try to get from config first
            if (defined('APP_VERSION')) {
                self::$version = APP_VERSION;
            } else {
                // Fallback: use file modification time for development
                self::$version = time();
            }
        }
        return self::$version;
    }

    /**
     * Get the assets base URL
     * @return string
     */
    private static function getAssetsBaseUrl(): string
    {
        // Check if ASSETS_URL is defined in config
        if (defined('ASSETS_URL')) {
            return ASSETS_URL;
        }
        return self::$assetsBaseUrl;
    }

    /**
     * Load module CSS file
     *
     * @param string $module - Module name (e.g., 'borrowed-tools', 'borrowed-tools-forms')
     * @param bool $print - Whether to echo or return the HTML
     * @return string HTML link tag
     */
    public static function loadModuleCSS(string $module, bool $print = true): string
    {
        $baseUrl = self::getAssetsBaseUrl();
        $version = self::getVersion();
        $href = "{$baseUrl}/css/modules/{$module}.css?v={$version}";

        $html = sprintf(
            '<link rel="stylesheet" href="%s">',
            htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
        );

        if ($print) {
            echo $html . "\n";
        }

        return $html;
    }

    /**
     * Load multiple module CSS files at once
     *
     * @param array $modules - Array of module names
     * @param bool $print - Whether to echo or return the HTML
     * @return string HTML link tags
     */
    public static function loadModuleCSSMultiple(array $modules, bool $print = true): string
    {
        $html = '';
        foreach ($modules as $module) {
            $html .= self::loadModuleCSS($module, false);
        }

        if ($print) {
            echo $html;
        }

        return $html;
    }

    /**
     * Load module JavaScript file
     *
     * @param string $module - Module name (e.g., 'index', 'ajax-handler', 'transfers')
     * @param array|string $options - Options array or type string for backward compatibility
     * @param bool $print - Whether to echo or return the HTML
     * @return string HTML script tag
     */
    public static function loadModuleJS(string $module, $options = 'module', bool $print = true): string
    {
        // Handle options - can be string (type) or array (options)
        if (is_array($options)) {
            $type = $options['type'] ?? 'module';
        } else {
            $type = $options;
        }

        $baseUrl = self::getAssetsBaseUrl();
        $version = self::getVersion();

        // Determine JS directory (borrowed-tools has priority for backward compatibility)
        $directories = ['borrowed-tools', 'modules'];
        $jsDirectory = 'modules'; // default

        // Check which directory contains the file
        foreach ($directories as $dir) {
            $filePath = APP_ROOT . "/assets/js/{$dir}/{$module}.js";
            if (file_exists($filePath)) {
                $jsDirectory = $dir;
                break;
            }
        }

        $src = "{$baseUrl}/js/{$jsDirectory}/{$module}.js?v={$version}";

        $html = sprintf(
            '<script type="%s" src="%s"></script>',
            htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($src, ENT_QUOTES, 'UTF-8')
        );

        if ($print) {
            echo $html . "\n";
        }

        return $html;
    }

    /**
     * Load multiple module JS files at once
     *
     * @param array $modules - Array of module names
     * @param string $type - Script type
     * @param bool $print - Whether to echo or return the HTML
     * @return string HTML script tags
     */
    public static function loadModuleJSMultiple(array $modules, string $type = 'module', bool $print = true): string
    {
        $html = '';
        foreach ($modules as $module) {
            $html .= self::loadModuleJS($module, $type, false);
        }

        if ($print) {
            echo $html;
        }

        return $html;
    }

    /**
     * Load inline JavaScript with CSRF token
     * Use sparingly - prefer external modules
     *
     * @param string $jsCode - JavaScript code to execute
     * @param array $config - Configuration array to pass to JS
     * @param bool $print - Whether to echo or return the HTML
     * @return string HTML script tag
     */
    public static function loadInlineJS(string $jsCode, array $config = [], bool $print = true): string
    {
        $configJson = !empty($config) ? json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP) : '{}';

        $html = sprintf(
            "<script type=\"module\">\n// Configuration\nconst config = %s;\n\n%s\n</script>",
            $configJson,
            $jsCode
        );

        if ($print) {
            echo $html . "\n";
        }

        return $html;
    }

    /**
     * Generate preload link for critical CSS
     * Improves performance by loading critical CSS early
     *
     * @param string $module - Module name
     * @param bool $print - Whether to echo or return the HTML
     * @return string HTML preload link tag
     */
    public static function preloadCSS(string $module, bool $print = true): string
    {
        $baseUrl = self::getAssetsBaseUrl();
        $version = self::getVersion();
        $href = "{$baseUrl}/css/modules/{$module}.css?v={$version}";

        $html = sprintf(
            '<link rel="preload" href="%s" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">',
            htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
        );

        if ($print) {
            echo $html . "\n";
        }

        return $html;
    }

    /**
     * Generate integrity hash for CSS file (for CDN)
     * @param string $filePath - Path to CSS file
     * @return string|null SHA384 hash
     */
    public static function generateIntegrityHash(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        $hash = base64_encode(hash('sha384', $content, true));
        return "sha384-{$hash}";
    }

    /**
     * Load external library from CDN
     *
     * @param string $url - CDN URL
     * @param string $integrity - Subresource Integrity hash (optional)
     * @param string $crossorigin - Crossorigin attribute value
     * @param bool $print - Whether to echo or return the HTML
     * @return string HTML link tag
     */
    public static function loadExternalCSS(string $url, string $integrity = '', string $crossorigin = 'anonymous', bool $print = true): string
    {
        $html = sprintf(
            '<link rel="stylesheet" href="%s"%s%s>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            $integrity ? ' integrity="' . htmlspecialchars($integrity, ENT_QUOTES, 'UTF-8') . '"' : '',
            $crossorigin ? ' crossorigin="' . htmlspecialchars($crossorigin, ENT_QUOTES, 'UTF-8') . '"' : ''
        );

        if ($print) {
            echo $html . "\n";
        }

        return $html;
    }

    /**
     * Get Bootstrap badge class for asset status
     * Single source of truth for status badge styling
     *
     * @param string $status - Asset status
     * @return string Bootstrap badge class
     */
    public static function getAssetStatusBadgeClass(string $status): string
    {
        $statusClasses = [
            'available' => 'bg-success',
            'in_use' => 'bg-primary',
            'borrowed' => 'bg-info',
            'under_maintenance' => 'bg-warning',
            'retired' => 'bg-secondary',
            'disposed' => 'bg-dark',
            'pending' => 'bg-warning',
            'lost' => 'bg-danger',
            'damaged' => 'bg-danger'
        ];

        return $statusClasses[strtolower($status)] ?? 'bg-secondary';
    }

    /**
     * Get Bootstrap badge class for withdrawal status
     *
     * @param string $status - Withdrawal status
     * @return string Bootstrap badge class
     */
    public static function getWithdrawalStatusBadgeClass(string $status): string
    {
        $statusClasses = [
            'pending' => 'bg-warning',
            'released' => 'bg-success',
            'returned' => 'bg-info',
            'canceled' => 'bg-secondary',
            'cancelled' => 'bg-secondary'
        ];

        return $statusClasses[strtolower($status)] ?? 'bg-secondary';
    }

    /**
     * Get Bootstrap badge class for borrowing status
     *
     * @param string $status - Borrowing status
     * @return string Bootstrap badge class
     */
    public static function getBorrowingStatusBadgeClass(string $status): string
    {
        $statusClasses = [
            'borrowed' => 'bg-primary',
            'returned' => 'bg-success',
            'overdue' => 'bg-danger',
            'canceled' => 'bg-secondary',
            'cancelled' => 'bg-secondary',
            'partially_returned' => 'bg-warning'
        ];

        return $statusClasses[strtolower($status)] ?? 'bg-secondary';
    }

    /**
     * Get Bootstrap badge class for transfer status
     *
     * @param string $status - Transfer status
     * @return string Bootstrap badge class
     */
    public static function getTransferStatusBadgeClass(string $status): string
    {
        $statusClasses = [
            'pending' => 'bg-warning',
            'approved' => 'bg-info',
            'completed' => 'bg-success',
            'rejected' => 'bg-danger',
            'canceled' => 'bg-secondary',
            'cancelled' => 'bg-secondary'
        ];

        return $statusClasses[strtolower($status)] ?? 'bg-secondary';
    }

    /**
     * Get Bootstrap badge class for maintenance status
     *
     * @param string $status - Maintenance status
     * @return string Bootstrap badge class
     */
    public static function getMaintenanceStatusBadgeClass(string $status): string
    {
        $statusClasses = [
            'scheduled' => 'bg-info',
            'in_progress' => 'bg-warning',
            'completed' => 'bg-success',
            'canceled' => 'bg-secondary',
            'cancelled' => 'bg-secondary',
            'pending' => 'bg-warning'
        ];

        return $statusClasses[strtolower($status)] ?? 'bg-secondary';
    }

    /**
     * Get Bootstrap badge class for incident status
     *
     * @param string $status - Incident status
     * @return string Bootstrap badge class
     */
    public static function getIncidentStatusBadgeClass(string $status): string
    {
        $statusClasses = [
            'reported' => 'bg-warning',
            'investigating' => 'bg-info',
            'resolved' => 'bg-success',
            'closed' => 'bg-secondary',
            'pending' => 'bg-warning'
        ];

        return $statusClasses[strtolower($status)] ?? 'bg-secondary';
    }

    /**
     * Format date for display with consistent formatting
     *
     * @param string|null $date - Date string
     * @param string $format - Date format (default: 'M j, Y')
     * @return string Formatted date or 'N/A'
     */
    public static function formatDate(?string $date, string $format = 'M j, Y'): string
    {
        if (empty($date)) {
            return 'N/A';
        }

        try {
            return date($format, strtotime($date));
        } catch (Exception $e) {
            error_log("Date formatting error: " . $e->getMessage());
            return 'N/A';
        }
    }

    /**
     * Format datetime for display with consistent formatting
     *
     * @param string|null $datetime - Datetime string
     * @param string $format - Datetime format (default: 'M j, Y g:i A')
     * @return string Formatted datetime or 'N/A'
     */
    public static function formatDateTime(?string $datetime, string $format = 'M j, Y g:i A'): string
    {
        if (empty($datetime)) {
            return 'N/A';
        }

        try {
            return date($format, strtotime($datetime));
        } catch (Exception $e) {
            error_log("DateTime formatting error: " . $e->getMessage());
            return 'N/A';
        }
    }
}
