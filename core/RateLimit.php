<?php
/**
 * ConstructLink™ Rate Limiting
 * Simple rate limiting for login attempts and API calls
 */

class RateLimit {
    private static $storage = [];
    
    /**
     * Check if request is within rate limit
     */
    public static function check($key, $maxAttempts, $timeWindow) {
        $now = time();
        
        // Clean up old entries
        self::cleanup($key, $timeWindow);
        
        // Get current attempts
        $attempts = self::getAttempts($key);
        
        if (count($attempts) >= $maxAttempts) {
            return false;
        }
        
        // Record this attempt
        self::recordAttempt($key);
        
        return true;
    }
    
    /**
     * Get IP-based key for rate limiting
     */
    public static function getIPKey($action) {
        $ip = self::getClientIP();
        return $action . '_' . $ip;
    }
    
    /**
     * Get user-based key for rate limiting
     */
    public static function getUserKey($action, $userId) {
        return $action . '_user_' . $userId;
    }
    
    /**
     * Record an attempt
     */
    private static function recordAttempt($key) {
        if (!isset(self::$storage[$key])) {
            self::$storage[$key] = [];
        }
        
        self::$storage[$key][] = time();
    }
    
    /**
     * Get attempts for a key
     */
    private static function getAttempts($key) {
        return self::$storage[$key] ?? [];
    }
    
    /**
     * Clean up old attempts outside time window
     */
    private static function cleanup($key, $timeWindow) {
        if (!isset(self::$storage[$key])) {
            return;
        }
        
        $cutoff = time() - $timeWindow;
        self::$storage[$key] = array_filter(self::$storage[$key], function($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });
        
        // Remove empty arrays
        if (empty(self::$storage[$key])) {
            unset(self::$storage[$key]);
        }
    }
    
    /**
     * Reset attempts for a key
     */
    public static function reset($key) {
        unset(self::$storage[$key]);
    }
    
    /**
     * Get remaining attempts
     */
    public static function getRemainingAttempts($key, $maxAttempts, $timeWindow) {
        self::cleanup($key, $timeWindow);
        $attempts = self::getAttempts($key);
        return max(0, $maxAttempts - count($attempts));
    }
    
    /**
     * Get time until reset
     */
    public static function getTimeUntilReset($key, $timeWindow) {
        $attempts = self::getAttempts($key);
        if (empty($attempts)) {
            return 0;
        }
        
        $oldestAttempt = min($attempts);
        $resetTime = $oldestAttempt + $timeWindow;
        
        return max(0, $resetTime - time());
    }
    
    /**
     * Get client IP address
     */
    private static function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Check API rate limit
     */
    public static function checkAPI($endpoint, $maxRequests = 60, $timeWindow = 60) {
        $key = 'api_' . $endpoint . '_' . self::getClientIP();
        return self::check($key, $maxRequests, $timeWindow);
    }
    
    /**
     * Middleware for rate limiting
     */
    public static function middleware($maxRequests = 60, $timeWindow = 60) {
        $key = 'general_' . self::getClientIP();
        
        if (!self::check($key, $maxRequests, $timeWindow)) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => self::getTimeUntilReset($key, $timeWindow)
            ]);
            exit;
        }
    }
}
?>
