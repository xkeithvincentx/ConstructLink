<?php
/**
 * ConstructLink™ Authentication Controller
 * Handles user login, logout, and authentication
 */

class AuthController {
    private $auth;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
    }
    
    /**
     * Display login page and handle login
     */
    public function login() {
        // If user is already authenticated, redirect to dashboard
        if ($this->auth->isAuthenticated()) {
            header('Location: ?route=dashboard');
            exit;
        }
        
        $errors = [];
        $messages = [];
        
        // Check for logout message
        if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
            $messages[] = 'You have been logged out successfully.';
        }
        
        // Check for installation complete message
        if (isset($_GET['message']) && $_GET['message'] === 'installation_complete') {
            $messages[] = 'Installation completed successfully. You can now log in.';
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            try {
                CSRFProtection::validateRequest();
            } catch (Exception $e) {
                $errors[] = 'Security token validation failed. Please try again.';
            }
            
            if (empty($errors)) {
                // Rate limiting
                if (!RateLimit::check(RateLimit::getIPKey('login'), 5, 300)) { // 5 attempts per 5 minutes
                    $errors[] = 'Too many login attempts. Please try again later.';
                } else {
                    $username = Validator::sanitize($_POST['username'] ?? '');
                    $password = $_POST['password'] ?? '';
                    $rememberMe = isset($_POST['remember_me']);
                    
                    if (empty($username) || empty($password)) {
                        $errors[] = 'Username and password are required.';
                    } else {
                        $result = $this->auth->login($username, $password, $rememberMe);
                        
                        if ($result['success']) {
                            // Redirect to intended page or dashboard
                            $redirectTo = $_SESSION['intended_url'] ?? '?route=dashboard';
                            unset($_SESSION['intended_url']);
                            header('Location: ' . $redirectTo);
                            exit;
                        } else {
                            $errors[] = $result['message'];
                        }
                    }
                }
            }
        }
        
        // Include simple login view (no sidebar)
        $pageTitle = 'Login - ConstructLink™';
        include APP_ROOT . '/views/auth/login-simple.php';
    }
    
    /**
     * Handle logout
     */
    public function logout() {
        $this->auth->logout();
        header('Location: ?route=login&message=logged_out');
        exit;
    }
    
    /**
     * Display forgot password page
     */
    public function forgotPassword() {
        $messages = [];
        $errors = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            $email = Validator::sanitize($_POST['email'] ?? '');
            
            if (empty($email)) {
                $errors[] = 'Email address is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            } else {
                // Check if email exists
                $userModel = new UserModel();
                $user = $userModel->findFirst(['email' => $email]);
                
                if ($user) {
                    // Generate reset token (in a real implementation, you'd send an email)
                    $resetToken = bin2hex(random_bytes(32));
                    
                    // Store reset token in session for demo purposes
                    $_SESSION['reset_token'] = $resetToken;
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_expires'] = time() + 3600; // 1 hour
                    
                    $messages[] = 'Password reset instructions have been sent to your email address.';
                } else {
                    // Don't reveal if email exists or not for security
                    $messages[] = 'If an account with that email exists, password reset instructions have been sent.';
                }
            }
        }
        
        $pageTitle = 'Forgot Password - ConstructLink™';
        include APP_ROOT . '/views/auth/forgot-password.php';
    }
    
    /**
     * Handle password reset
     */
    public function resetPassword() {
        $token = $_GET['token'] ?? '';
        $errors = [];
        $messages = [];
        
        // Validate token
        if (empty($token) || 
            !isset($_SESSION['reset_token']) || 
            !hash_equals($_SESSION['reset_token'], $token) ||
            time() > $_SESSION['reset_expires']) {
            $errors[] = 'Invalid or expired reset token.';
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
            CSRFProtection::validateRequest();
            
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($password) || empty($confirmPassword)) {
                $errors[] = 'Both password fields are required.';
            } elseif ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
                $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
            } else {
                // Find user and reset password
                $userModel = new UserModel();
                $user = $userModel->findFirst(['email' => $_SESSION['reset_email']]);
                
                if ($user) {
                    $result = $userModel->resetPassword($user['id'], $password);
                    
                    if ($result['success']) {
                        // Clear reset session data
                        unset($_SESSION['reset_token'], $_SESSION['reset_email'], $_SESSION['reset_expires']);
                        
                        $messages[] = 'Password has been reset successfully. You can now log in.';
                        
                        // Redirect to login after 3 seconds
                        header('refresh:3;url=?route=login');
                    } else {
                        $errors[] = $result['message'];
                    }
                } else {
                    $errors[] = 'User not found.';
                }
            }
        }
        
        $pageTitle = 'Reset Password - ConstructLink™';
        include APP_ROOT . '/views/auth/reset-password.php';
    }
    
    /**
     * Check authentication status (AJAX endpoint)
     */
    public function checkAuth() {
        header('Content-Type: application/json');
        
        $isAuthenticated = $this->auth->isAuthenticated();
        $user = $isAuthenticated ? $this->auth->getCurrentUser() : null;
        
        echo json_encode([
            'authenticated' => $isAuthenticated,
            'user' => $user ? [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role_name']
            ] : null
        ]);
    }
    
    /**
     * Change password
     */
    public function changePassword() {
        if (!$this->auth->isAuthenticated()) {
            header('Location: ?route=login');
            exit;
        }
        
        $errors = [];
        $messages = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $errors[] = 'All password fields are required.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords do not match.';
            } else {
                $result = $this->auth->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
                
                if ($result['success']) {
                    $messages[] = $result['message'];
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        $pageTitle = 'Change Password - ConstructLink™';
        include APP_ROOT . '/views/auth/change-password.php';
    }
}
?>
