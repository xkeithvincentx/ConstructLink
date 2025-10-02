<?php
/**
 * ConstructLink™ Authentication System - FIXED VERSION
 * Handles user authentication, session management, and security
 */

class Auth {
    private $db;
    private static $instance = null;
    
    private function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Authenticate user with username and password
     */
    public function login($username, $password, $rememberMe = false) {
        try {
            // Check for too many failed attempts
            if ($this->isAccountLocked($username)) {
                return [
                    'success' => false,
                    'message' => 'Account is temporarily locked due to too many failed login attempts. Please try again later.'
                ];
            }
            
            // Get user data with role information - FIXED QUERY
            $stmt = $this->db->prepare("
                SELECT u.*, r.name as role_name, r.permissions 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE u.username = ? AND u.is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $this->recordFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Invalid username or password.'
                ];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->recordFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Invalid username or password.'
                ];
            }
            
            // Reset failed attempts on successful login
            $this->resetFailedAttempts($user['id']);
            
            // Create session
            $this->createSession($user, $rememberMe);
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Log successful login
            $this->logAuditEvent('user_login', 'users', $user['id'], null, [
                'username' => $username,
                'ip_address' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            return [
                'success' => true,
                'message' => 'Login successful.',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role_name'],
                    'permissions' => json_decode($user['permissions'] ?? '[]', true)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during login. Please try again.'
            ];
        }
    }
    
    /**
     * Logout current user
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            // Log logout event
            $this->logAuditEvent('user_logout', 'users', $_SESSION['user_id'], null, [
                'ip_address' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        }
        
        // Clear session data
        session_unset();
        
        // Destroy session if it exists
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        return true;
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        try {
            // Check if user still exists and is active
            $stmt = $this->db->prepare("SELECT id, is_active FROM users WHERE id = ? AND is_active = 1");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $this->logout();
                return false;
            }
            
            // Check session timeout - FIXED: Use defined constants or defaults
            if (isset($_SESSION['session_start_time'])) {
                $sessionLifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600; // 1 hour default
                $sessionAge = time() - $_SESSION['session_start_time'];
                if ($sessionAge > $sessionLifetime) {
                    $this->logout();
                    return false;
                }
            }
            
            // Update session activity time
            $_SESSION['last_activity'] = time();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Authentication check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get current authenticated user - FIXED VERSION
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, r.name as role_name, r.permissions 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE u.id = ? AND u.is_active = 1
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // FIXED: Properly handle permissions JSON
                $user['permissions'] = json_decode($user['permissions'] ?? '[]', true);
                if (!is_array($user['permissions'])) {
                    $user['permissions'] = [];
                }
                
                // FIXED: Handle missing role properly
                if (!$user['role_name']) {
                    error_log("Auth::getCurrentUser - User ID {$user['id']} has no role assigned or role not found");
                    $user['role_name'] = 'User';
                    $user['permissions'] = [];
                }
                
                // Remove sensitive data
                unset($user['password_hash']);
                
                return $user;
            } else {
                error_log("Auth::getCurrentUser - No user found for session user_id: " . ($_SESSION['user_id'] ?? 'null'));
                return null;
            }
            
        } catch (Exception $e) {
            error_log("Auth::getCurrentUser error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if user has specific permission - FIXED VERSION
     */
    public function hasPermission($permission) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        // System admin has all permissions
        if ($user['role_name'] === 'System Admin') {
            return true;
        }
        
        // FIXED: Ensure permissions is array
        $permissions = is_array($user['permissions']) ? $user['permissions'] : [];
        return in_array($permission, $permissions);
    }
    
    /**
     * Check if user has any of the specified roles - FIXED VERSION
     */
    public function hasRole($roles) {
        $user = $this->getCurrentUser();
        if (!$user) {
            error_log("Auth::hasRole - No user found in session");
            return false;
        }
        
        // FIXED: Handle both string and array input
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        if (!is_array($roles)) {
            error_log("Auth::hasRole - Invalid roles parameter: " . print_r($roles, true));
            return false;
        }
        
        $userRole = $user['role_name'] ?? '';
        
        // System Admin always has access to everything
        if ($userRole === 'System Admin') {
            return true;
        }
        
        $hasRole = in_array($userRole, $roles);
        
        if (!$hasRole) {
            error_log("Auth::hasRole - Access denied. User role: '$userRole', Required roles: " . implode(', ', $roles));
        }
        
        return $hasRole;
    }
    
    /**
     * Create user session - FIXED VERSION
     */
    private function createSession($user, $rememberMe = false) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role_name'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['session_start_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $this->getClientIP();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // FIXED: Handle session cookie parameters safely
        $cookieLifetime = $rememberMe ? (30 * 24 * 3600) : 0; // 30 days or session
        
        if (function_exists('session_set_cookie_params')) {
            try {
                session_set_cookie_params([
                    'lifetime' => $cookieLifetime,
                    'path' => '/',
                    'domain' => '',
                    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            } catch (Exception $e) {
                error_log("Session cookie params error: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Check if account is locked due to failed attempts - FIXED VERSION
     */
    private function isAccountLocked($username) {
        try {
            $stmt = $this->db->prepare("
                SELECT failed_login_attempts, locked_until 
                FROM users 
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false;
            }
            
            // Check if account is currently locked
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                return true;
            }
            
            // Reset lock if time has passed
            if ($user['locked_until'] && strtotime($user['locked_until']) <= time()) {
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET failed_login_attempts = 0, locked_until = NULL 
                    WHERE username = ?
                ");
                $stmt->execute([$username]);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Account lock check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Record failed login attempt - FIXED VERSION
     */
    private function recordFailedAttempt($username) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET failed_login_attempts = failed_login_attempts + 1 
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            
            // Check if we need to lock the account
            $stmt = $this->db->prepare("
                SELECT id, failed_login_attempts 
                FROM users 
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $maxAttempts = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;
                $lockoutTime = defined('LOGIN_LOCKOUT_TIME') ? LOGIN_LOCKOUT_TIME : 900; // 15 minutes
                
                if ($user['failed_login_attempts'] >= $maxAttempts) {
                    $lockUntil = date('Y-m-d H:i:s', time() + $lockoutTime);
                    $stmt = $this->db->prepare("
                        UPDATE users 
                        SET locked_until = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$lockUntil, $user['id']]);
                    
                    // Log account lock event
                    $this->logAuditEvent('account_locked', 'users', $user['id'], null, [
                        'username' => $username,
                        'failed_attempts' => $user['failed_login_attempts'],
                        'locked_until' => $lockUntil,
                        'ip_address' => $this->getClientIP()
                    ]);
                }
            }
        } catch (Exception $e) {
            error_log("Record failed attempt error: " . $e->getMessage());
        }
    }
    
    /**
     * Reset failed login attempts
     */
    private function resetFailedAttempts($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET failed_login_attempts = 0, locked_until = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Reset failed attempts error: " . $e->getMessage());
        }
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Log audit event - FIXED VERSION
     */
    private function logAuditEvent($action, $tableName, $recordId, $oldValues = null, $newValues = null) {
        try {
            // Check if audit_logs table exists before trying to insert
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'audit_logs'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                // Table doesn't exist, skip audit logging
                return;
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $action,
                $tableName,
                $recordId,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null,
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Audit log error: " . $e->getMessage());
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Change user password - FIXED VERSION
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current password hash
            $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found.'];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Current password is incorrect.'];
            }
            
            // Validate new password
            $minLength = defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8;
            if (strlen($newPassword) < $minLength) {
                return ['success' => false, 'message' => 'New password must be at least ' . $minLength . ' characters long.'];
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $this->db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newPasswordHash, $userId]);
            
            // Log password change
            $this->logAuditEvent('password_changed', 'users', $userId, null, [
                'changed_by' => $_SESSION['user_id'] ?? $userId,
                'ip_address' => $this->getClientIP()
            ]);
            
            return ['success' => true, 'message' => 'Password changed successfully.'];
            
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while changing password.'];
        }
    }
    
    /**
     * Clean up expired sessions (simplified for file-based sessions)
     */
    public function cleanupExpiredSessions() {
        // For file-based sessions, PHP handles cleanup automatically
        // This method is kept for compatibility but doesn't need database operations
        return 0;
    }
}
?>
