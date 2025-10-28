<?php
/**
 * BrandingHelper - Database-Driven Branding Management
 *
 * Loads and manages system branding from the database instead of hardcoded config files.
 * Implements caching for performance.
 *
 * @package ConstructLink
 * @subpackage Helpers
 * @version 1.0.0
 * @since 2025-10-28
 */

class BrandingHelper {
    /**
     * @var array Cached branding data
     */
    private static $brandingCache = null;

    /**
     * @var int Cache duration in seconds (1 hour)
     */
    private static $cacheDuration = 3600;

    /**
     * Load branding data from database with caching
     *
     * @return array Branding configuration array
     * @throws Exception if database connection fails
     */
    public static function loadBranding() {
        // Return cached data if available and not expired
        if (self::$brandingCache !== null) {
            return self::$brandingCache;
        }

        // Check session cache
        if (isset($_SESSION['branding_cache']) && isset($_SESSION['branding_cache_time'])) {
            $cacheAge = time() - $_SESSION['branding_cache_time'];
            if ($cacheAge < self::$cacheDuration) {
                self::$brandingCache = $_SESSION['branding_cache'];
                return self::$brandingCache;
            }
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Load branding from database
            $stmt = $db->prepare("
                SELECT
                    company_name,
                    app_name,
                    tagline,
                    logo_url,
                    favicon_url,
                    primary_color,
                    secondary_color,
                    accent_color,
                    success_color,
                    warning_color,
                    danger_color,
                    info_color,
                    contact_email,
                    contact_phone,
                    address,
                    footer_text
                FROM system_branding
                WHERE id = 1
                LIMIT 1
            ");

            $stmt->execute();
            $branding = $stmt->fetch(PDO::FETCH_ASSOC);

            // Fallback to defaults if no record found
            if (!$branding) {
                error_log('[BrandingHelper] No branding record found, using defaults');
                $branding = self::getDefaultBranding();
            }

            // Cache in memory and session
            self::$brandingCache = $branding;
            $_SESSION['branding_cache'] = $branding;
            $_SESSION['branding_cache_time'] = time();

            return $branding;

        } catch (Exception $e) {
            error_log('[BrandingHelper] Database error: ' . $e->getMessage());
            // Return defaults on error
            return self::getDefaultBranding();
        }
    }

    /**
     * Get default branding values (fallback)
     *
     * @return array Default branding configuration
     */
    private static function getDefaultBranding() {
        return [
            'company_name' => 'V CUTAMORA CONSTRUCTION INC.',
            'app_name' => 'ConstructLink™',
            'tagline' => 'QUALITY WORKS AND CLIENT SATISFACTION IS OUR GAME',
            'logo_url' => '/assets/images/company-logo.png',
            'favicon_url' => '/assets/images/favicon.ico',
            'primary_color' => '#6B7280',
            'secondary_color' => '#9CA3AF',
            'accent_color' => '#059669',
            'success_color' => '#059669',
            'warning_color' => '#D97706',
            'danger_color' => '#DC2626',
            'info_color' => '#2563EB',
            'contact_email' => 'info@vcutamora.com',
            'contact_phone' => '+63 XXX XXX XXXX',
            'address' => '',
            'footer_text' => '© ' . date('Y') . ' V CUTAMORA CONSTRUCTION INC. All rights reserved. Powered by ConstructLink™'
        ];
    }

    /**
     * Get specific branding value
     *
     * @param string $key Branding key
     * @param mixed $default Default value if key not found
     * @return mixed Branding value
     */
    public static function get($key, $default = null) {
        $branding = self::loadBranding();
        return $branding[$key] ?? $default;
    }

    /**
     * Get page title with app name
     *
     * @param string $pageTitle Page-specific title
     * @return string Full page title
     */
    public static function getPageTitle($pageTitle = '') {
        $appName = self::get('app_name', 'ConstructLink™');
        return $pageTitle ? $pageTitle . ' - ' . $appName : $appName;
    }

    /**
     * Get full company info (name + tagline)
     *
     * @return string Company name with tagline
     */
    public static function getCompanyInfo() {
        $company = self::get('company_name', 'V CUTAMORA CONSTRUCTION INC.');
        $tagline = self::get('tagline', '');
        return $tagline ? $company . ' - ' . $tagline : $company;
    }

    /**
     * Clear branding cache (use after updating branding in database)
     *
     * @return void
     */
    public static function clearCache() {
        self::$brandingCache = null;
        unset($_SESSION['branding_cache']);
        unset($_SESSION['branding_cache_time']);
    }

    /**
     * Update branding in database
     *
     * @param array $data Branding data to update
     * @return bool Success status
     */
    public static function updateBranding(array $data) {
        try {
            $db = Database::getInstance()->getConnection();

            // Build SET clause dynamically based on provided data
            $allowedFields = [
                'company_name', 'app_name', 'tagline', 'logo_url', 'favicon_url',
                'primary_color', 'secondary_color', 'accent_color',
                'success_color', 'warning_color', 'danger_color', 'info_color',
                'contact_email', 'contact_phone', 'address', 'footer_text'
            ];

            $setClauses = [];
            $params = [];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $setClauses[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }

            if (empty($setClauses)) {
                return false;
            }

            $sql = "UPDATE system_branding SET " . implode(', ', $setClauses) . " WHERE id = 1";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($params);

            if ($result) {
                self::clearCache();
            }

            return $result;

        } catch (Exception $e) {
            error_log('[BrandingHelper] Update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate CSS variables from branding colors
     *
     * @return string CSS :root variables
     */
    public static function generateCSSVariables() {
        $branding = self::loadBranding();

        $css = "<style>\n:root {\n";
        $css .= "    --primary-color: " . $branding['primary_color'] . ";\n";
        $css .= "    --secondary-color: " . $branding['secondary_color'] . ";\n";
        $css .= "    --accent-color: " . $branding['accent_color'] . ";\n";
        $css .= "    --success-color: " . $branding['success_color'] . ";\n";
        $css .= "    --warning-color: " . $branding['warning_color'] . ";\n";
        $css .= "    --danger-color: " . $branding['danger_color'] . ";\n";
        $css .= "    --info-color: " . $branding['info_color'] . ";\n";
        $css .= "}\n</style>\n";

        return $css;
    }
}
