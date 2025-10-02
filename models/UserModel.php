<?php
/**
 * ConstructLink™ User Model - Enhanced with Project Assignment
 * Handles user management, authentication, and project assignments
 */

class UserModel extends BaseModel {
    protected $table = 'users';
    protected $fillable = [
        'username', 'password_hash', 'role_id', 'full_name', 'email', 'phone', 
        'department', 'is_active', 'failed_login_attempts', 'locked_until', 'current_project_id'
    ];
    protected $hidden = ['password_hash'];
    
    /**
     * Create new user with password hashing and optional project assignment
     */
    public function createUser($data) {
        try {
            // Validate required fields
            $errors = [];
            
            if (empty($data['username'])) {
                $errors[] = 'Username is required';
            } elseif (strlen($data['username']) < 3) {
                $errors[] = 'Username must be at least 3 characters';
            } elseif ($this->findFirst(['username' => $data['username']])) {
                $errors[] = 'Username already exists';
            }
            
            if (empty($data['password'])) {
                $errors[] = 'Password is required';
            } elseif (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
                $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
            }
            
            if (empty($data['full_name'])) {
                $errors[] = 'Full name is required';
            }
            
            if (empty($data['email'])) {
                $errors[] = 'Email is required';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            } elseif ($this->findFirst(['email' => $data['email']])) {
                $errors[] = 'Email already exists';
            }
            
            if (empty($data['role_id'])) {
                $errors[] = 'Role is required';
            }
            
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            $this->beginTransaction();
            
            // Hash password
            $userData = [
                'username' => Validator::sanitize($data['username']),
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role_id' => (int)$data['role_id'],
                'full_name' => Validator::sanitize($data['full_name']),
                'email' => Validator::sanitize($data['email']),
                'phone' => Validator::sanitize($data['phone'] ?? ''),
                'department' => Validator::sanitize($data['department'] ?? ''),
                'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
                'failed_login_attempts' => 0,
                'current_project_id' => !empty($data['current_project_id']) ? (int)$data['current_project_id'] : null
            ];
            
            $createdUser = $this->create($userData);
            
            if (!$createdUser) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to create user'];
            }
            
            // Create project assignment if specified
            if (!empty($data['current_project_id'])) {
                $assignmentResult = $this->assignUserToProject(
                    $createdUser['id'], 
                    $data['current_project_id'], 
                    $data['assigned_by'] ?? 1, // Default to admin
                    'Initial assignment during user creation'
                );
                
                if (!$assignmentResult['success']) {
                    $this->rollback();
                    return $assignmentResult;
                }
            }
            
            // Log activity
            if (function_exists('logActivity')) {
                logActivity('user_created', "User '{$userData['username']}' created");
            }
            
            $this->commit();
            return ['success' => true, 'user' => $createdUser];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("User creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create user'];
        }
    }
    
    /**
     * Update user with validation and project reassignment support
     */
    public function updateUser($id, $data) {
        try {
            $user = $this->find($id);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Validate fields
            $errors = [];
            
            if (isset($data['username'])) {
                if (empty($data['username'])) {
                    $errors[] = 'Username is required';
                } elseif (strlen($data['username']) < 3) {
                    $errors[] = 'Username must be at least 3 characters';
                } else {
                    $existing = $this->findFirst(['username' => $data['username']]);
                    if ($existing && $existing['id'] != $id) {
                        $errors[] = 'Username already exists';
                    }
                }
            }
            
            if (isset($data['email'])) {
                if (empty($data['email'])) {
                    $errors[] = 'Email is required';
                } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email format';
                } else {
                    $existing = $this->findFirst(['email' => $data['email']]);
                    if ($existing && $existing['id'] != $id) {
                        $errors[] = 'Email already exists';
                    }
                }
            }
            
            if (isset($data['password']) && !empty($data['password'])) {
                if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
                    $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
                }
            }
            
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            $this->beginTransaction();
            
            // Prepare update data
            $updateData = [];
            $allowedFields = ['username', 'full_name', 'email', 'phone', 'department', 'role_id', 'is_active', 'current_project_id'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = Validator::sanitize($data[$field]);
                }
            }
            
            // Handle password update if provided
            if (!empty($data['password'])) {
                $updateData['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            // Handle checkbox fields
            if (isset($data['is_active'])) {
                $updateData['is_active'] = (isset($data['is_active']) && $data['is_active'] == '1') ? 1 : 0;
            }
            
            // Handle project reassignment
            if (isset($data['current_project_id']) && $data['current_project_id'] != $user['current_project_id']) {
                $reassignResult = $this->reassignUserToProject(
                    $id,
                    $user['current_project_id'],
                    $data['current_project_id'],
                    $data['reassigned_by'] ?? 1,
                    $data['reassignment_reason'] ?? 'Project reassignment'
                );
                
                if (!$reassignResult['success']) {
                    $this->rollback();
                    return $reassignResult;
                }
            }
            
            $updatedUser = $this->update($id, $updateData);
            
            if (!$updatedUser) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update user'];
            }
            
            // Log activity
            if (function_exists('logActivity')) {
                logActivity('user_updated', "User '{$updatedUser['username']}' updated");
            }
            
            $this->commit();
            return ['success' => true, 'user' => $updatedUser];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("User update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update user'];
        }
    }
    
    /**
     * Assign user to project
     */
    public function assignUserToProject($userId, $projectId, $assignedBy, $reason = null) {
        try {
            // Check if assignment already exists
            $sql = "SELECT id FROM user_projects WHERE user_id = ? AND project_id = ? AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $projectId]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'User is already assigned to this project'];
            }
            
            // Create project assignment
            $sql = "INSERT INTO user_projects (user_id, project_id, assigned_by, assigned_at, is_active) VALUES (?, ?, ?, NOW(), 1)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$userId, $projectId, $assignedBy]);
            
            if ($result) {
                // Log the assignment
                $this->logProjectAssignment($userId, null, $projectId, 'assigned', $reason, $assignedBy);
                return ['success' => true, 'message' => 'User assigned to project successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to assign user to project'];
            
        } catch (Exception $e) {
            error_log("Project assignment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to assign user to project'];
        }
    }
    
    /**
     * Reassign user to different project
     */
    public function reassignUserToProject($userId, $oldProjectId, $newProjectId, $changedBy, $reason = null) {
        try {
            // Deactivate old assignment if exists
            if ($oldProjectId) {
                $sql = "UPDATE user_projects SET is_active = 0 WHERE user_id = ? AND project_id = ? AND is_active = 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId, $oldProjectId]);
            }
            
            // Create new assignment if new project specified
            if ($newProjectId) {
                // Check if assignment already exists
                $sql = "SELECT id FROM user_projects WHERE user_id = ? AND project_id = ? AND is_active = 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId, $newProjectId]);
                
                if (!$stmt->fetch()) {
                    // Create project assignment directly without nested transaction
                    $sql = "INSERT INTO user_projects (user_id, project_id, assigned_by, assigned_at, is_active) VALUES (?, ?, ?, NOW(), 1)";
                    $stmt = $this->db->prepare($sql);
                    $result = $stmt->execute([$userId, $newProjectId, $changedBy]);
                    
                    if (!$result) {
                        return ['success' => false, 'message' => 'Failed to assign user to new project'];
                    }
                }
            }
            
            // Log the reassignment
            $this->logProjectAssignment($userId, $oldProjectId, $newProjectId, 'reassigned', $reason, $changedBy);
            
            return ['success' => true, 'message' => 'User reassigned successfully'];
            
        } catch (Exception $e) {
            error_log("Project reassignment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reassign user'];
        }
    }
    
    /**
     * Log project assignment changes
     */
    private function logProjectAssignment($userId, $oldProjectId, $newProjectId, $action, $reason, $changedBy) {
        try {
            $sql = "INSERT INTO user_project_logs (user_id, old_project_id, new_project_id, action, reason, changed_by, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $oldProjectId, $newProjectId, $action, $reason, $changedBy]);
        } catch (Exception $e) {
            error_log("Project assignment logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Get user with role and project information
     */
    public function getUserWithRole($id) {
        $sql = "
            SELECT u.*, r.name as role_name, r.description as role_description, r.permissions,
                   p.name as project_name, p.code as project_code,
                   p.name as current_project_name, p.location as project_location
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN projects p ON u.current_project_id = p.id
            WHERE u.id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if ($user) {
            $user['permissions'] = json_decode($user['permissions'], true) ?? [];
            unset($user['password_hash']);
        }
        
        return $user;
    }
    
    /**
     * Get all users with role and project information
     */
    public function getAllUsersWithRoles($filters = [], $page = 1, $perPage = 20) {
        $conditions = [];
        $params = [];
        
        // Apply filters
        if (!empty($filters['role_id'])) {
            $conditions[] = "u.role_id = ?";
            $params[] = $filters['role_id'];
        }
        
        if (!empty($filters['project_id'])) {
            $conditions[] = "u.current_project_id = ?";
            $params[] = $filters['project_id'];
        }
        
        if (isset($filters['is_active'])) {
            $conditions[] = "u.is_active = ?";
            $params[] = $filters['is_active'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        // Count total records
        $countSql = "SELECT COUNT(*) FROM users u {$whereClause}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get paginated data
        $offset = ($page - 1) * $perPage;
        $orderBy = $filters['order_by'] ?? 'u.full_name ASC';
        
        $dataSql = "
            SELECT u.id, u.username, u.full_name, u.email, u.phone, u.is_active, 
                   u.last_login, u.created_at, u.failed_login_attempts, u.locked_until,
                   r.name as role_name, r.description as role_description,
                   p.name as project_name, p.code as project_code,
                   p.name as current_project_name, p.location as project_location
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN projects p ON u.current_project_id = p.id
            {$whereClause}
            ORDER BY {$orderBy}
            LIMIT {$perPage} OFFSET {$offset}
        ";
        
        $stmt = $this->db->prepare($dataSql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1
            ]
        ];
    }
    
    /**
     * Get users by project (for project-scoped operations)
     */
    public function getUsersByProject($projectId) {
        try {
            $sql = "
                SELECT u.*, r.name as role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.current_project_id = ? AND u.is_active = 1
                ORDER BY u.full_name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$projectId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get users by project error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user project assignment history
     */
    public function getUserProjectHistory($userId) {
        try {
            $sql = "
                SELECT upl.*, 
                       op.name as old_project_name, op.code as old_project_code,
                       np.name as new_project_name, np.code as new_project_code,
                       u.full_name as changed_by_name
                FROM user_project_logs upl
                LEFT JOIN projects op ON upl.old_project_id = op.id
                LEFT JOIN projects np ON upl.new_project_id = np.id
                LEFT JOIN users u ON upl.changed_by = u.id
                WHERE upl.user_id = ?
                ORDER BY upl.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get user project history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if user has access to project-scoped data
     */
    public function hasProjectAccess($userId, $projectId) {
        try {
            $user = $this->getUserWithRole($userId);
            
            // System Admin has access to all projects
            if ($user && $user['role_name'] === 'System Admin') {
                return true;
            }
            
            // Finance Director and Asset Director have cross-project access
            if ($user && in_array($user['role_name'], ['Finance Director', 'Asset Director'])) {
                return true;
            }
            
            // Check if user is assigned to the project
            return $user && $user['current_project_id'] == $projectId;
            
        } catch (Exception $e) {
            error_log("Project access check error: " . $e->getMessage());
            return false;
        }
    }
    
    // Keep all existing methods from the original UserModel
    
    /**
     * Change user password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->find($userId);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Get full user data including password hash
            $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userData = $stmt->fetch();
            
            // Verify current password
            if (!password_verify($currentPassword, $userData['password_hash'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Validate new password
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                return ['success' => false, 'message' => 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'];
            }
            
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $result = $this->update($userId, ['password_hash' => $newPasswordHash]);
            
            if ($result) {
                // Log activity
                if (function_exists('logActivity')) {
                    logActivity('password_changed', "Password changed for user ID: {$userId}");
                }
                return ['success' => true, 'message' => 'Password changed successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to change password'];
            }
            
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    }
    
    /**
     * Reset user password (admin function)
     */
    public function resetPassword($userId, $newPassword) {
        try {
            $user = $this->find($userId);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'];
            }
            
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $result = $this->update($userId, [
                'password_hash' => $newPasswordHash,
                'failed_login_attempts' => 0,
                'locked_until' => null
            ]);
            
            if ($result) {
                // Log activity
                if (function_exists('logActivity')) {
                    logActivity('password_reset', "Password reset for user ID: {$userId}");
                }
                return ['success' => true, 'message' => 'Password reset successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to reset password'];
            }
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
    }
    
    /**
     * Reset user password with generated password (admin function)
     */
    public function resetUserPassword($userId) {
        try {
            $user = $this->find($userId);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Generate random password
            $newPassword = $this->generateRandomPassword();
            
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $result = $this->update($userId, [
                'password_hash' => $newPasswordHash,
                'failed_login_attempts' => 0,
                'locked_until' => null
            ]);
            
            if ($result) {
                // Log activity
                if (function_exists('logActivity')) {
                    logActivity('password_reset', "Password reset for user: {$user['username']}");
                }
                return [
                    'success' => true, 
                    'message' => 'Password reset successfully',
                    'new_password' => $newPassword
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to reset password'];
            }
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser($userId) {
        try {
            $user = $this->find($userId);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Check if user has related records
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM withdrawals WHERE withdrawn_by = ?");
            $stmt->execute([$userId]);
            $withdrawalCount = $stmt->fetchColumn();
            
            if ($withdrawalCount > 0) {
                return ['success' => false, 'message' => 'Cannot delete user with existing withdrawal records'];
            }
            
            $result = $this->delete($userId);
            
            if ($result) {
                // Log activity
                if (function_exists('logActivity')) {
                    logActivity('user_deleted', "User '{$user['username']}' deleted");
                }
                return ['success' => true, 'message' => 'User deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete user'];
            }
            
        } catch (Exception $e) {
            error_log("User deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete user'];
        }
    }
    
    /**
     * Generate random password
     */
    private function generateRandomPassword($length = 12) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $password;
    }
    
    /**
     * Lock/unlock user account
     */
    public function toggleAccountLock($userId, $lock = true) {
        try {
            $user = $this->find($userId);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            $updateData = [];
            
            if ($lock) {
                $updateData['locked_until'] = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $updateData['is_active'] = 0;
                $action = 'account_locked';
                $message = 'Account locked successfully';
            } else {
                $updateData['locked_until'] = null;
                $updateData['failed_login_attempts'] = 0;
                $updateData['is_active'] = 1;
                $action = 'account_unlocked';
                $message = 'Account unlocked successfully';
            }
            
            $result = $this->update($userId, $updateData);
            
            if ($result) {
                // Log activity
                if (function_exists('logActivity')) {
                    logActivity($action, "User ID: {$userId} - {$message}");
                }
                return ['success' => true, 'message' => $message];
            } else {
                return ['success' => false, 'message' => 'Failed to update account status'];
            }
            
        } catch (Exception $e) {
            error_log("Account lock toggle error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update account status'];
        }
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats() {
        $sql = "
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users,
                SUM(CASE WHEN locked_until IS NOT NULL AND locked_until > NOW() THEN 1 ELSE 0 END) as locked_users,
                SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as recent_logins
            FROM users
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Get users by role
     */
    public function getUsersByRole() {
        $sql = "
            SELECT r.name as role_name, COUNT(u.id) as user_count
            FROM roles r
            LEFT JOIN users u ON r.id = u.role_id AND u.is_active = 1
            GROUP BY r.id, r.name
            ORDER BY user_count DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Search users
     */
    public function searchUsers($searchTerm, $limit = 20) {
        $sql = "
            SELECT u.id, u.username, u.full_name, u.email, u.is_active,
                   r.name as role_name, p.name as project_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN projects p ON u.current_project_id = p.id
            WHERE (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)
            ORDER BY u.full_name ASC
            LIMIT ?
        ";
        
        $searchPattern = "%{$searchTerm}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get users for dropdown/select options
     */
    public function getUsersForSelect($roleIds = [], $projectId = null) {
        $sql = "SELECT u.id, u.full_name, u.username FROM users u WHERE u.is_active = 1";
        $params = [];
        
        if (!empty($roleIds)) {
            $placeholders = str_repeat('?,', count($roleIds) - 1) . '?';
            $sql .= " AND u.role_id IN ({$placeholders})";
            $params = array_merge($params, $roleIds);
        }
        
        if ($projectId) {
            $sql .= " AND u.current_project_id = ?";
            $params[] = $projectId;
        }
        
        $sql .= " ORDER BY u.full_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        // Only allow certain fields to be updated in profile
        $allowedFields = ['full_name', 'email', 'phone'];
        $profileData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($profileData)) {
            return ['success' => false, 'message' => 'No valid fields to update'];
        }
        
        // Validate email uniqueness if provided
        if (isset($profileData['email'])) {
            $existingUser = $this->findFirst(['email' => $profileData['email']]);
            if ($existingUser && $existingUser['id'] != $userId) {
                return ['success' => false, 'message' => 'Email address is already in use'];
            }
        }
        
        try {
            $result = $this->update($userId, $profileData);
            
            if ($result) {
                return ['success' => true, 'user' => $result, 'message' => 'Profile updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update profile'];
            }
            
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update profile'];
        }
    }
    
    /**
     * Get user activity logs
     */
    public function getUserActivityLogs($userId, $limit = 50) {
        try {
            $sql = "
                SELECT al.*, u.full_name as user_name
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.user_id = ?
                ORDER BY al.created_at DESC
                LIMIT ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get user activity logs error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user login history
     */
    public function getUserLoginHistory($userId, $limit = 20) {
        try {
            $sql = "
                SELECT login_time, ip_address, user_agent, success
                FROM user_login_logs
                WHERE user_id = ?
                ORDER BY login_time DESC
                LIMIT ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get user login history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update user last login
     */
    public function updateLastLogin($userId, $ipAddress = null, $userAgent = null) {
        try {
            $updateData = [
                'last_login' => date('Y-m-d H:i:s'),
                'failed_login_attempts' => 0
            ];
            
            $result = $this->update($userId, $updateData);
            
            // Log successful login
            if ($result) {
                $sql = "INSERT INTO user_login_logs (user_id, login_time, ip_address, user_agent, success) VALUES (?, NOW(), ?, ?, 1)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId, $ipAddress, $userAgent]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Update last login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Record failed login attempt
     */
    public function recordFailedLogin($username, $ipAddress = null, $userAgent = null) {
        try {
            $user = $this->findFirst(['username' => $username]);
            
            if ($user) {
                $failedAttempts = $user['failed_login_attempts'] + 1;
                $updateData = ['failed_login_attempts' => $failedAttempts];
                
                // Lock account after 5 failed attempts
                if ($failedAttempts >= 5) {
                    $updateData['locked_until'] = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                }
                
                $this->update($user['id'], $updateData);
                
                // Log failed login
                $sql = "INSERT INTO user_login_logs (user_id, login_time, ip_address, user_agent, success) VALUES (?, NOW(), ?, ?, 0)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$user['id'], $ipAddress, $userAgent]);
            }
            
        } catch (Exception $e) {
            error_log("Record failed login error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user account is locked
     */
    public function isAccountLocked($userId) {
        try {
            $user = $this->find($userId);
            
            if (!$user) {
                return true; // Treat non-existent users as locked
            }
            
            if (!$user['is_active']) {
                return true;
            }
            
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Account lock check error: " . $e->getMessage());
            return true; // Err on the side of caution
        }
    }
    
    /**
     * Get user permissions based on role
     */
    public function getUserPermissions($userId) {
        try {
            $sql = "
                SELECT r.permissions
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.id = ? AND u.is_active = 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            if ($result && $result['permissions']) {
                return json_decode($result['permissions'], true) ?? [];
            }
            
            return [];
            
        } catch (Exception $e) {
            error_log("Get user permissions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if user has specific permission
     */
    public function hasPermission($userId, $permission) {
        $permissions = $this->getUserPermissions($userId);
        return in_array($permission, $permissions);
    }
    
    /**
     * Get users with specific permission
     */
    public function getUsersWithPermission($permission) {
        try {
            $sql = "
                SELECT u.*, r.name as role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.is_active = 1 
                AND JSON_CONTAINS(r.permissions, JSON_QUOTE(?))
                ORDER BY u.full_name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$permission]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get users with permission error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Bulk update user status
     */
    public function bulkUpdateStatus($userIds, $status) {
        try {
            if (empty($userIds) || !is_array($userIds)) {
                return ['success' => false, 'message' => 'No users selected'];
            }
            
            $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
            $sql = "UPDATE users SET is_active = ? WHERE id IN ({$placeholders})";
            
            $params = array_merge([$status], $userIds);
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                $action = $status ? 'activated' : 'deactivated';
                $count = count($userIds);
                
                // Log activity
                if (function_exists('logActivity')) {
                    logActivity('bulk_user_update', "{$count} users {$action}");
                }
                
                return ['success' => true, 'message' => "{$count} users {$action} successfully"];
            }
            
            return ['success' => false, 'message' => 'Failed to update users'];
            
        } catch (Exception $e) {
            error_log("Bulk update user status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update users'];
        }
    }
    
    /**
     * Export users to CSV
     */
    public function exportUsers($filters = []) {
        try {
            $result = $this->getAllUsersWithRoles($filters, 1, 10000); // Get all users
            $users = $result['data'];
            
            $csvData = [];
            $csvData[] = ['ID', 'Username', 'Full Name', 'Email', 'Phone', 'Role', 'Project', 'Status', 'Last Login', 'Created'];
            
            foreach ($users as $user) {
                $csvData[] = [
                    $user['id'],
                    $user['username'],
                    $user['full_name'],
                    $user['email'],
                    $user['phone'] ?? '',
                    $user['role_name'] ?? '',
                    $user['project_name'] ?? '',
                    $user['is_active'] ? 'Active' : 'Inactive',
                    $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never',
                    date('Y-m-d', strtotime($user['created_at']))
                ];
            }
            
            return $csvData;
            
        } catch (Exception $e) {
            error_log("Export users error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user dashboard statistics
     */
    public function getUserDashboardStats($userId) {
        try {
            $user = $this->getUserWithRole($userId);
            
            if (!$user) {
                return [];
            }
            
            $stats = [];
            
            // Get user's project-specific stats if assigned to a project
            if ($user['current_project_id']) {
                // Assets in user's project
                $sql = "SELECT COUNT(*) FROM assets WHERE project_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$user['current_project_id']]);
                $stats['project_assets'] = $stmt->fetchColumn();
                
                // Active withdrawals in user's project
                $sql = "SELECT COUNT(*) FROM withdrawals WHERE project_id = ? AND status = 'released'";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$user['current_project_id']]);
                $stats['active_withdrawals'] = $stmt->fetchColumn();
                
                // User's own withdrawals
                $sql = "SELECT COUNT(*) FROM withdrawals WHERE withdrawn_by = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId]);
                $stats['my_withdrawals'] = $stmt->fetchColumn();
            }
            
            // Recent activity count
            $sql = "SELECT COUNT(*) FROM activity_logs WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $stats['recent_activity'] = $stmt->fetchColumn();
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Get user dashboard stats error: " . $e->getMessage());
            return [];
        }
    }
}
?>
