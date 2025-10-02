<?php
/**
 * ConstructLink™ User Controller - Enhanced with Project Assignment
 * Handles user management and administration with project scoping
 */

class UserController {
    private $auth;
    private $userModel;
    private $roleModel;
    private $projectModel;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        $this->projectModel = new ProjectModel();
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }
    
    /**
     * Display user listing
     */
    public function index() {
        // Check permissions - only System Admin can manage users
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        
        // Build filters
        $filters = [];
        if (!empty($_GET['role_id'])) $filters['role_id'] = $_GET['role_id'];
        if (!empty($_GET['project_id'])) $filters['project_id'] = $_GET['project_id'];
        if (isset($_GET['is_active'])) $filters['is_active'] = $_GET['is_active'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        
        try {
            // Get users with pagination
            $result = $this->userModel->getAllUsersWithRoles($filters, $page, $perPage);
            $users = $result['data'];
            $pagination = $result['pagination'];
            
            // Get roles for filter dropdown
            $roles = $this->roleModel->getRolesForDropdown();
            
            // Get projects for filter dropdown
            $projects = $this->projectModel->getActiveProjects();
            
            // Get user statistics
            $userStats = $this->userModel->getUserStats();
            
            $pageTitle = 'User Management - ConstructLink™';
            $pageHeader = 'User Management';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Users', 'url' => '?route=users']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/users/index.php';
            
        } catch (Exception $e) {
            error_log("User listing error: " . $e->getMessage());
            $error = 'Failed to load users';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display create user form
     */
    public function create() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        $formData = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            $formData = [
                'username' => Validator::sanitize($_POST['username'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'password_confirm' => $_POST['password_confirm'] ?? '',
                'full_name' => Validator::sanitize($_POST['full_name'] ?? ''),
                'email' => Validator::sanitize($_POST['email'] ?? ''),
                'role_id' => (int)($_POST['role_id'] ?? 0),
                'phone' => Validator::sanitize($_POST['phone'] ?? ''),
                'department' => Validator::sanitize($_POST['department'] ?? ''),
                'current_project_id' => !empty($_POST['current_project_id']) ? (int)$_POST['current_project_id'] : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'assigned_by' => $this->auth->getCurrentUser()['id']
            ];
            
            // Validate password confirmation
            if ($formData['password'] !== $formData['password_confirm']) {
                $errors[] = 'Passwords do not match';
            }
            
            if (empty($errors)) {
                $result = $this->userModel->createUser($formData);
                
                if ($result['success']) {
                    header('Location: ?route=users/view&id=' . $result['user']['id'] . '&message=user_created');
                    exit;
                } else {
                    if (isset($result['errors'])) {
                        $errors = $result['errors'];
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
        }
        
        try {
            // Get roles for dropdown
            $roles = $this->roleModel->getRolesForDropdown();
            
            // Get projects for dropdown
            $projects = $this->projectModel->getActiveProjects();
            
            $pageTitle = 'Create User - ConstructLink™';
            $pageHeader = 'Create User';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Users', 'url' => '?route=users'],
                ['title' => 'Create User', 'url' => '?route=users/create']
            ];
            
            include APP_ROOT . '/views/users/create.php';
            
        } catch (Exception $e) {
            error_log("User create form error: " . $e->getMessage());
            $error = 'Failed to load form data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display edit user form
     */
    public function edit() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $userId = $_GET['id'] ?? 0;
        
        if (!$userId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        $formData = [];
        
        try {
            $user = $this->userModel->getUserWithRole($userId);
            
            if (!$user) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $formData = [
                    'username' => Validator::sanitize($_POST['username'] ?? ''),
                    'full_name' => Validator::sanitize($_POST['full_name'] ?? ''),
                    'email' => Validator::sanitize($_POST['email'] ?? ''),
                    'role_id' => (int)($_POST['role_id'] ?? 0),
                    'phone' => Validator::sanitize($_POST['phone'] ?? ''),
                    'department' => Validator::sanitize($_POST['department'] ?? ''),
                    'current_project_id' => !empty($_POST['current_project_id']) ? (int)$_POST['current_project_id'] : null,
                    'is_active' => isset($_POST['is_active']) ? 1 : 0,
                    'reassigned_by' => $this->auth->getCurrentUser()['id'],
                    'reassignment_reason' => Validator::sanitize($_POST['reassignment_reason'] ?? 'Administrative update')
                ];
                
                // Add password if provided
                if (!empty($_POST['password'])) {
                    $formData['password'] = $_POST['password'];
                    $formData['password_confirm'] = $_POST['password_confirm'] ?? '';
                    
                    if ($formData['password'] !== $formData['password_confirm']) {
                        $errors[] = 'Passwords do not match';
                    }
                }
                
                if (empty($errors)) {
                    $result = $this->userModel->updateUser($userId, $formData);
                    
                    if ($result['success']) {
                        header('Location: ?route=users/view&id=' . $userId . '&message=user_updated');
                        exit;
                    } else {
                        if (isset($result['errors'])) {
                            $errors = $result['errors'];
                        } else {
                            $errors[] = $result['message'];
                        }
                    }
                }
            } else {
                // Pre-populate form with existing data
                $formData = $user;
            }
            
            // Get roles for dropdown
            $roles = $this->roleModel->getRolesForDropdown();
            
            // Get projects for dropdown
            $projects = $this->projectModel->getActiveProjects();
            
            $pageTitle = 'Edit User - ConstructLink™';
            $pageHeader = 'Edit User';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Users', 'url' => '?route=users'],
                ['title' => 'Edit User', 'url' => '?route=users/edit&id=' . $userId]
            ];
            
            include APP_ROOT . '/views/users/edit.php';
            
        } catch (Exception $e) {
            error_log("User edit error: " . $e->getMessage());
            $error = 'Failed to load user data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * View user details
     */
    public function view() {
        $userId = $_GET['id'] ?? 0;
        
        if (!$userId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $user = $this->userModel->getUserWithRole($userId);
            
            if (!$user) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check permissions - users can view their own profile, admins can view all
            if (!$this->auth->hasRole(['System Admin']) && $_SESSION['user_id'] != $userId) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            
            // Get user project assignment history
            $projectHistory = $this->userModel->getUserProjectHistory($userId);
            
            $pageTitle = 'User Profile - ConstructLink™';
            $pageHeader = $user['full_name'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Users', 'url' => '?route=users'],
                ['title' => 'View Profile', 'url' => '?route=users/view&id=' . $userId]
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/users/view.php';
            
        } catch (Exception $e) {
            error_log("User view error: " . $e->getMessage());
            $error = 'Failed to load user details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Assign user to project
     */
    public function assignProject() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        try {
            $userId = (int)($_POST['user_id'] ?? 0);
            $projectId = (int)($_POST['project_id'] ?? 0);
            $reason = Validator::sanitize($_POST['reason'] ?? 'Project assignment');
            $assignedBy = $this->auth->getCurrentUser()['id'];
            
            if (!$userId || !$projectId) {
                echo json_encode(['success' => false, 'message' => 'User ID and Project ID are required']);
                return;
            }
            
            $result = $this->userModel->assignUserToProject($userId, $projectId, $assignedBy, $reason);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Project assignment error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to assign user to project']);
        }
    }
    
    /**
     * Reassign user to different project
     */
    public function reassignProject() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        try {
            $userId = (int)($_POST['user_id'] ?? 0);
            $oldProjectId = (int)($_POST['old_project_id'] ?? 0);
            $newProjectId = (int)($_POST['new_project_id'] ?? 0);
            $reason = Validator::sanitize($_POST['reason'] ?? 'Project reassignment');
            $changedBy = $this->auth->getCurrentUser()['id'];
            
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                return;
            }
            
            $result = $this->userModel->reassignUserToProject($userId, $oldProjectId, $newProjectId, $changedBy, $reason);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Project reassignment error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to reassign user']);
        }
    }
    
    /**
     * Delete user
     */
    public function delete() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $userId = $_GET['id'] ?? 0;
        
        if (!$userId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        // Prevent self-deletion
        if ($userId == $_SESSION['user_id']) {
            $_SESSION['error'] = 'You cannot delete your own account';
            header('Location: ?route=users');
            exit;
        }
        
        try {
            $result = $this->userModel->deleteUser($userId);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            
            header('Location: ?route=users');
            exit;
            
        } catch (Exception $e) {
            error_log("User deletion error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete user';
            header('Location: ?route=users');
            exit;
        }
    }
    
    /**
     * Reset user password
     */
    public function resetPassword() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $userId = $_GET['id'] ?? 0;
        
        if (!$userId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $result = $this->userModel->resetUserPassword($userId);
            
            if ($result['success']) {
                $_SESSION['success'] = 'Password reset successfully. New password: ' . $result['new_password'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            
            header('Location: ?route=users/view&id=' . $userId);
            exit;
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to reset password';
            header('Location: ?route=users/view&id=' . $userId);
            exit;
        }
    }
    
    /**
     * Toggle user status (activate/deactivate)
     */
    public function toggleStatus() {
        // Check permissions
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        $userId = $_GET['id'] ?? 0;
        $activate = $_GET['activate'] ?? 0;
        
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            return;
        }
        
        // Prevent self-deactivation
        if ($userId == $_SESSION['user_id'] && !$activate) {
            echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account']);
            return;
        }
        
        try {
            $result = $this->userModel->toggleAccountLock($userId, !$activate);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("User status toggle error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
        }
    }
    
    /**
     * User profile (current user)
     */
    public function profile() {
        $userId = $_SESSION['user_id'];
        
        try {
            $user = $this->userModel->getUserWithRole($userId);
            
            if (!$user) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            $errors = [];
            $messages = [];
            $formData = $user;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $updateData = [
                    'full_name' => Validator::sanitize($_POST['full_name'] ?? ''),
                    'email' => Validator::sanitize($_POST['email'] ?? ''),
                    'phone' => Validator::sanitize($_POST['phone'] ?? '')
                ];
                
                // Handle password change separately if provided
                if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
                    $currentPassword = $_POST['current_password'];
                    $newPassword = $_POST['new_password'];
                    $confirmPassword = $_POST['new_password_confirm'] ?? '';
                    
                    if ($newPassword !== $confirmPassword) {
                        $errors[] = 'New passwords do not match';
                    } else {
                        $passwordResult = $this->userModel->changePassword($userId, $currentPassword, $newPassword);
                        if (!$passwordResult['success']) {
                            $errors[] = $passwordResult['message'];
                        }
                    }
                }
                
                // Update profile data if no errors
                if (empty($errors)) {
                    $result = $this->userModel->updateProfile($userId, $updateData);
                } else {
                    $result = ['success' => false, 'errors' => $errors];
                }
                
                if ($result['success']) {
                    $_SESSION['success'] = 'Profile updated successfully';
                    header('Location: ?route=users/profile');
                    exit;
                } else {
                    if (isset($result['errors'])) {
                        $errors = $result['errors'];
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            // Get user project history
            $projectHistory = $this->userModel->getUserProjectHistory($userId);
            
            $pageTitle = 'My Profile - ConstructLink™';
            $pageHeader = 'My Profile';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'My Profile', 'url' => '?route=users/profile']
            ];
            
            include APP_ROOT . '/views/users/profile.php';
            
        } catch (Exception $e) {
            error_log("User profile error: " . $e->getMessage());
            $error = 'Failed to load profile';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Get users by project (AJAX endpoint)
     */
    public function getUsersByProject() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $projectId = $_GET['project_id'] ?? 0;
        
        if (!$projectId) {
            echo json_encode(['success' => false, 'message' => 'Project ID required']);
            return;
        }
        
        try {
            $users = $this->userModel->getUsersByProject($projectId);
            
            echo json_encode([
                'success' => true,
                'users' => $users
            ]);
            
        } catch (Exception $e) {
            error_log("Get users by project error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load users']);
        }
    }
    
    /**
     * Search users (AJAX endpoint)
     */
    public function searchUsers() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $searchTerm = $_GET['q'] ?? '';
        $limit = (int)($_GET['limit'] ?? 20);
        
        if (strlen($searchTerm) < 2) {
            echo json_encode(['success' => false, 'message' => 'Search term must be at least 2 characters']);
            return;
        }
        
        try {
            $users = $this->userModel->searchUsers($searchTerm, $limit);
            
            echo json_encode([
                'success' => true,
                'users' => $users
            ]);
            
        } catch (Exception $e) {
            error_log("Search users error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to search users']);
        }
    }
}
?>
