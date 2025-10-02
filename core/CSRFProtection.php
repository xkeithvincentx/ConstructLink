<?php
/**
 * ConstructLink™ CSRF Protection
 * Cross-Site Request Forgery protection implementation
 */

class CSRFProtection {
    private static $tokenName = '_csrf_token';
    private static $sessionKey = 'csrf_tokens';
    
    /**
     * Generate CSRF token
     */
    public static function generateToken() {
        if (!isset($_SESSION[self::$sessionKey])) {
            $_SESSION[self::$sessionKey] = [];
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::$sessionKey][$token] = time();
        
        // Clean old tokens (older than 1 hour)
        self::cleanOldTokens();
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateToken($token) {
        if (!isset($_SESSION[self::$sessionKey])) {
            return false;
        }
        
        if (!isset($_SESSION[self::$sessionKey][$token])) {
            return false;
        }
        
        // Check if token is not older than 1 hour
        $tokenTime = $_SESSION[self::$sessionKey][$token];
        if (time() - $tokenTime > 3600) {
            unset($_SESSION[self::$sessionKey][$token]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate request CSRF token
     */
    public static function validateRequest() {
        if (!self::isEnabled()) {
            return true;
        }
        
        $token = $_POST[self::$tokenName] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!self::validateToken($token)) {
            http_response_code(403);
            
            if (self::isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid CSRF token',
                    'error_code' => 'CSRF_TOKEN_INVALID'
                ]);
            } else {
                include APP_ROOT . '/views/errors/403.php';
            }
            exit;
        }
        
        return true;
    }
    
    /**
     * Get token field HTML
     */
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Get token meta tag HTML
     */
    public static function getTokenMeta() {
        $token = self::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Clean old tokens
     */
    private static function cleanOldTokens() {
        if (!isset($_SESSION[self::$sessionKey])) {
            return;
        }
        
        $currentTime = time();
        foreach ($_SESSION[self::$sessionKey] as $token => $time) {
            if ($currentTime - $time > 3600) { // 1 hour
                unset($_SESSION[self::$sessionKey][$token]);
            }
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Get current token for JavaScript
     */
    public static function getTokenForJS() {
        return self::generateToken();
    }
    
    /**
     * Invalidate all tokens for user
     */
    public static function invalidateAllTokens() {
        $_SESSION[self::$sessionKey] = [];
    }
    
    /**
     * Check if CSRF protection is enabled
     */
    public static function isEnabled() {
        // Default to enabled if not explicitly disabled
        if (!defined('ENV_CSRF_PROTECTION')) {
            return true;
        }
        return ENV_CSRF_PROTECTION === true;
    }
}
?>
