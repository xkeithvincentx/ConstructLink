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
    
    public function login($username, $password, $rememberMe = false) {
        try {
            if ($this->isAccountLocked($username)) {
                return [
                    'success' => false,
                    'message' => 'Account is temporarily locked due to too many failed login attempts. Please try again later.'
                ];
            }
            
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
            
            if (!password_verify($password, $user['password_hash'])) {
                $this->recordFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Invalid username or password.'
                ];
            }
            
            $this->resetFailedAttempts($user['id']);
            $this->createSession($user, $rememberMe);
            $this->updateLastLogin($user['id']);
            
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
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logAuditEvent('user_logout', 'users', $_SESSION['user_id'], null, [
                'ip_address' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        }
        
        session_unset();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        return true;
    }
    
    public function isAuthenticated() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT id, is_active FROM users WHERE id = ? AND is_active = 1");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $this->logout();
                return false;
            }
            
            if (isset($_SESSION['session_start_time'])) {
                $sessionLifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600;
                $sessionAge = time() - $_SESSION['session_start_time'];
                if ($sessionAge > $sessionLifetime) {
                    $this->logout();
                    return false;
                }
            }
            
            $_SESSION['last_activity'] = time();
            return true;
            
        } catch (Exception $e) {
            error_log("Authentication check error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, r.name as role_name, r.permissions, p.name as project_name
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                LEFT JOIN projects p ON u.current_project_id = p.id
                WHERE u.id = ? AND u.is_active = 1
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $user['permissions'] = json_decode($user['permissions'] ?? '[]', true);
                if (!is_array($user['permissions'])) {
                    $user['permissions'] = [];
                }
                
                if (!$user['role_name']) {
                    error_log("Auth::getCurrentUser - User ID {$user['id']} has no role assigned or role not found");
                    $user['role_name'] = 'User';
                    $user['permissions'] = [];
                }
                
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
    
    public function hasPermission($permission) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        if ($user['role_name'] === 'System Admin') {
            return true;
        }
        
        $permissions = is_array($user['permissions']) ? $user['permissions'] : [];
        return in_array($permission, $permissions);
    }
    
    public function hasRole($roles) {
        $user = $this->getCurrentUser();
        if (!$user) {
            error_log("Auth::hasRole - No user found in session");
            return false;
        }
        
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        if (!is_array($roles)) {
            error_log("Auth::hasRole - Invalid roles parameter: " . print_r($roles, true));
            return false;
        }
        
        $userRole = $user['role_name'] ?? '';
        if ($userRole === 'System Admin') {
            return true;
        }
        
        $hasRole = in_array($userRole, $roles);
        if (!$hasRole) {
            error_log("Auth::hasRole - Access denied. User role: '$userRole', Required roles: " . implode(', ', $roles));
        }
        
        return $hasRole;
    }
    
    private function createSession($user, $rememberMe = false) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role_name'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['session_start_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $this->getClientIP();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $cookieLifetime = $rememberMe ? (30 * 24 * 3600) : 0;
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
            
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                return true;
            }
            
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
    
    private function recordFailedAttempt($username) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET failed_login_attempts = failed_login_attempts + 1 
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            
            $stmt = $this->db->prepare("
                SELECT id, failed_login_attempts 
                FROM users 
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $maxAttempts = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;
                $lockoutTime = defined('LOGIN_LOCKOUT_TIME') ? LOGIN_LOCKOUT_TIME : 900;
                
                if ($user['failed_login_attempts'] >= $maxAttempts) {
                    $lockUntil = date('Y-m-d H:i:s', time() + $lockoutTime);
                    $stmt = $this->db->prepare("
                        UPDATE users 
                        SET locked_until = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$lockUntil, $user['id']]);
                    
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
    
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
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
    
    private function logAuditEvent($action, $tableName, $recordId, $oldValues = null, $newValues = null) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'audit_logs'");
            $stmt->execute();
            if (!$stmt->fetch()) {
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
    
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found.'];
            }
            
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Current password is incorrect.'];
            }
            
            $minLength = defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8;
            if (strlen($newPassword) < $minLength) {
                return ['success' => false, 'message' => 'New password must be at least ' . $minLength . ' characters long.'];
            }
            
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newPasswordHash, $userId]);
            
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
    
    public function cleanupExpiredSessions() {
        return 0;
    }
}
?>