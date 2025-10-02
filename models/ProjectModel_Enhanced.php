<?php
/**
 * ConstructLink™ Enhanced Project Model
 * Handles project management with multi-inventory support, user assignments, and enhanced features
 */

class ProjectModel extends BaseModel {
    protected $table = 'projects';
    protected $fillable = [
        'name', 'code', 'location', 'description', 'start_date', 'end_date', 
        'budget', 'project_manager_id', 'is_active'
    ];
    
    /**
     * Create project with validation and user assignment
     */
    public function createProject($data) {
        $validation = $this->validate($data, [
            'name' => 'required|max:200',
            'code' => 'required|max:20',
            'location' => 'required'
        ]);
        
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        // Check for unique code
        if ($this->isCodeExists($data['code'])) {
            return ['success' => false, 'errors' => ['Project code already exists']];
        }
        
        // Validate date range
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if (strtotime($data['start_date']) > strtotime($data['end_date'])) {
                return ['success' => false, 'message' => 'End date must be after start date'];
            }
        }
        
        try {
            $this->beginTransaction();
            
            $data['is_active'] = isset($data['is_active']) ? (int)$data['is_active'] : 1;
            
            // Set project manager if provided
            if (!empty($data['project_manager_id'])) {
                $userModel = new UserModel();
                $manager = $userModel->find($data['project_manager_id']);
                if (!$manager || !in_array($manager['role_name'], ['Project Manager', 'System Admin'])) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Invalid project manager selected'];
                }
            }
            
            $project = $this->create($data);
            
            if (!$project) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to create project'];
            }
            
            // Auto-assign project manager to project if specified
            if (!empty($data['project_manager_id'])) {
                $this->assignUserToProject($data['project_manager_id'], $project['id'], 
                    Auth::getInstance()->getCurrentUser()['id'], 'Project Manager Assignment');
            }
            
            // Log activity
            $this->logActivity('project_created', 'Project created: ' . $data['name'], 'projects', $project['id']);
            
            $this->commit();
            
            return ['success' => true, 'project' => $project];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Project creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create project'];
        }
    }
    
    /**
     * Update project with enhanced validation
     */
    public function updateProject($id, $data) {
        $validation = $this->validate($data, [
            'name' => 'required|max:200',
            'code' => 'required|max:20',
            'location' => 'required'
        ]);
        
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        // Check for unique code (excluding current project)
        if (isset($data['code']) && $this->isCodeExists($data['code'], $id)) {
            return ['success' => false, 'errors' => ['Project code already exists']];
        }
        
        // Validate date range
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if (strtotime($data['start_date']) > strtotime($data['end_date'])) {
                return ['success' => false, 'message' => 'End date must be after start date'];
            }
        }
        
        try {
            $this->beginTransaction();
            
            $oldProject = $this->find($id);
            if (!$oldProject) {
                $this->rollback();
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            if (isset($data['is_active'])) {
                $data['is_active'] = (int)$data['is_active'];
            }
            
            // Handle project manager change
            if (isset($data['project_manager_id']) && $data['project_manager_id'] != $oldProject['project_manager_id']) {
                if (!empty($data['project_manager_id'])) {
                    $userModel = new UserModel();
                    $manager = $userModel->find($data['project_manager_id']);
                    if (!$manager || !in_array($manager['role_name'], ['Project Manager', 'System Admin'])) {
                        $this->rollback();
                        return ['success' => false, 'message' => 'Invalid project manager selected'];
                    }
                    
                    // Assign new project manager
                    $this->assignUserToProject($data['project_manager_id'], $id, 
                        Auth::getInstance()->getCurrentUser()['id'], 'Project Manager Updated');
                }
            }
            
            $updated = $this->update($id, $data);
            
            if (!$updated) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update project'];
            }
            
            // Log activity
            $this->logActivity('project_updated', 'Project updated: ' . $data['name'], 'projects', $id);
            
            $this->commit();
            
            return ['success' => true, 'project' => $updated];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Project update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update project'];
        }
    }
    
    /**
     * Get project with comprehensive details including user assignments
     */
    public function getProjectWithDetails($id) {
        try {
            $sql = "
                SELECT p.*,
                       pm.full_name as project_manager_name,
                       pm.email as project_manager_email,
                       COUNT(DISTINCT a.id) as assets_count,
                       COUNT(DISTINCT up.user_id) as assigned_users_count,
                       SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available_assets,
                       SUM(CASE WHEN a.status = 'in_use' THEN 1 ELSE 0 END) as in_use_assets,
                       SUM(CASE WHEN a.status = 'under_maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
                       COALESCE(SUM(a.acquisition_cost), 0) as total_asset_value,
                       COUNT(DISTINCT w.id) as total_withdrawals,
                       COUNT(DISTINCT CASE WHEN w.status = 'pending' THEN w.id END) as pending_withdrawals,
                       COUNT(DISTINCT CASE WHEN w.status = 'released' THEN w.id END) as active_withdrawals,
                       COUNT(DISTINCT po.id) as procurement_orders_count,
                       COUNT(DISTINCT r.id) as requests_count
                FROM projects p
                LEFT JOIN users pm ON p.project_manager_id = pm.id
                LEFT JOIN assets a ON p.id = a.project_id
                LEFT JOIN user_projects up ON p.id = up.project_id AND up.is_active = 1
                LEFT JOIN withdrawals w ON p.id = w.project_id
                LEFT JOIN procurement_orders po ON p.id = po.project_id
                LEFT JOIN requests r ON p.id = r.project_id
                WHERE p.id = ?
                GROUP BY p.id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Get project with details error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get projects with enhanced filters and pagination
     */
    public function getProjectsWithFilters($filters = [], $page = 1, $perPage = 20) {
        try {
            $conditions = [];
            $params = [];
            
            // Apply role-based filtering
            $currentUser = Auth::getInstance()->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            
            // Project Managers can only see their assigned projects unless they're System Admin
            if ($userRole === 'Project Manager' && !in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
                $conditions[] = "(p.project_manager_id = ? OR up.user_id = ?)";
                $params[] = $currentUser['id'];
                $params[] = $currentUser['id'];
            }
            
            // Apply filters
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'active') {
                    $conditions[] = "p.is_active = 1";
                } elseif ($filters['status'] === 'inactive') {
                    $conditions[] = "p.is_active = 0";
                }
            }
            
            if (!empty($filters['manager_id'])) {
                $conditions[] = "p.project_manager_id = ?";
                $params[] = $filters['manager_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $conditions[] = "DATE(p.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions[] = "DATE(p.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['search'])) {
                $conditions[] = "(p.name LIKE ? OR p.location LIKE ? OR p.code LIKE ? OR p.description LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            // Count total records
            $countSql = "
                SELECT COUNT(DISTINCT p.id) 
                FROM projects p
                LEFT JOIN user_projects up ON p.id = up.project_id AND up.is_active = 1
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();
            
            // Get paginated data
            $offset = ($page - 1) * $perPage;
            $orderBy = $filters['order_by'] ?? 'p.name ASC';
            
            $dataSql = "
                SELECT p.*,
                       pm.full_name as project_manager_name,
                       COUNT(DISTINCT a.id) as assets_count,
                       COUNT(DISTINCT up.user_id) as assigned_users_count,
                       SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available_count,
                       SUM(CASE WHEN a.status = 'in_use' THEN 1 ELSE 0 END) as in_use_count,
                       COALESCE(SUM(a.acquisition_cost), 0) as total_value,
                       COUNT(DISTINCT w.id) as withdrawals_count,
                       COUNT(DISTINCT po.id) as procurement_count
                FROM projects p
                LEFT JOIN users pm ON p.project_manager_id = pm.id
                LEFT JOIN assets a ON p.id = a.project_id
                LEFT JOIN user_projects up ON p.id = up.project_id AND up.is_active = 1
                LEFT JOIN withdrawals w ON p.id = w.project_id
                LEFT JOIN procurement_orders po ON p.id = po.project_id
                {$whereClause}
                GROUP BY p.id, p.name, p.code, p.location, p.description, p.start_date, p.end_date, 
                         p.budget, p.project_manager_id, p.is_active, p.created_at, p.updated_at, 
                         pm.full_name
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
            
        } catch (Exception $e) {
            error_log("Get projects with filters error: " . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_next' => false,
                    'has_prev' => false
                ]
            ];
        }
    }
    
    /**
     * Get project statistics with role-based filtering
     */
    public function getProjectStatistics($projectId = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($projectId) {
                $conditions[] = "p.id = ?";
                $params[] = $projectId;
            }
            
            // Apply role-based filtering
            $currentUser = Auth::getInstance()->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            
            if ($userRole === 'Project Manager' && !in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
                $conditions[] = "(p.project_manager_id = ? OR up.user_id = ?)";
                $params[] = $currentUser['id'];
                $params[] = $currentUser['id'];
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT 
                    COUNT(DISTINCT p.id) as total_projects,
                    COUNT(DISTINCT CASE WHEN p.is_active = 1 THEN p.id END) as active_projects,
                    COUNT(DISTINCT CASE WHEN p.is_active = 0 THEN p.id END) as inactive_projects,
                    COUNT(DISTINCT a.id) as total_assets,
                    SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available_assets,
                    SUM(CASE WHEN a.status = 'in_use' THEN 1 ELSE 0 END) as in_use_assets,
                    COALESCE(SUM(a.acquisition_cost), 0) as total_asset_value,
                    COUNT(DISTINCT w.id) as total_withdrawals,
                    COUNT(DISTINCT po.id) as total_procurement_orders,
                    COUNT(DISTINCT r.id) as total_requests
                FROM projects p
                LEFT JOIN user_projects up ON p.id = up.project_id AND up.is_active = 1
                LEFT JOIN assets a ON p.id = a.project_id
                LEFT JOIN withdrawals w ON p.id = w.project_id
                LEFT JOIN procurement_orders po ON p.id = po.project_id
                LEFT JOIN requests r ON p.id = r.project_id
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result ?: [
                'total_projects' => 0,
                'active_projects' => 0,
                'inactive_projects' => 0,
                'total_assets' => 0,
                'available_assets' => 0,
                'in_use_assets' => 0,
                'total_asset_value' => 0,
                'total_withdrawals' => 0,
                'total_procurement_orders' => 0,
                'total_requests' => 0
            ];
            
        } catch (Exception $e) {
            error_log("Get project statistics error: " . $e->getMessage());
            return [
                'total_projects' => 0,
                'active_projects' => 0,
                'inactive_projects' => 0,
                'total_assets' => 0,
                'available_assets' => 0,
                'in_use_assets' => 0,
                'total_asset_value' => 0,
                'total_withdrawals' => 0,
                'total_procurement_orders' => 0,
                'total_requests' => 0
            ];
        }
    }
    
    /**
     * Assign user to project
     */
    public function assignUserToProject($userId, $projectId, $assignedBy, $notes = null) {
        try {
            // Check if assignment already exists
            $existingSql = "SELECT id FROM user_projects WHERE user_id = ? AND project_id = ? AND is_active = 1";
            $stmt = $this->db->prepare($existingSql);
            $stmt->execute([$userId, $projectId]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'User is already assigned to this project'];
            }
            
            // Create new assignment
            $sql = "INSERT INTO user_projects (user_id, project_id, assigned_by, notes) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$userId, $projectId, $assignedBy, $notes]);
            
            if ($result) {
                // Update user's current project
                $userSql = "UPDATE users SET current_project_id = ? WHERE id = ?";
                $userStmt = $this->db->prepare($userSql);
                $userStmt->execute([$projectId, $userId]);
                
                // Log assignment
                $logSql = "INSERT INTO user_project_logs (user_id, new_project_id, action, reason, changed_by) VALUES (?, ?, 'assigned', ?, ?)";
                $logStmt = $this->db->prepare($logSql);
                $logStmt->execute([$userId, $projectId, $notes, $assignedBy]);
                
                return ['success' => true, 'message' => 'User assigned to project successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to assign user to project'];
            
        } catch (Exception $e) {
            error_log("Assign user to project error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to assign user to project'];
        }
    }
    
    /**
     * Get project assigned users
     */
    public function getProjectUsers($projectId) {
        try {
            $sql = "
                SELECT u.id, u.username, u.full_name, u.email, u.phone, u.department,
                       r.name as role_name,
                       up.assigned_at, up.notes,
                       assignedBy.full_name as assigned_by_name
                FROM user_projects up
                JOIN users u ON up.user_id = u.id
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN users assignedBy ON up.assigned_by = assignedBy.id
                WHERE up.project_id = ? AND up.is_active = 1
                ORDER BY up.assigned_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$projectId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get project users error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get available project managers
     */
    public function getAvailableProjectManagers() {
        try {
            $sql = "
                SELECT u.id, u.full_name, u.email, u.department,
                       COUNT(p.id) as managed_projects_count
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN projects p ON u.id = p.project_manager_id AND p.is_active = 1
                WHERE r.name IN ('Project Manager', 'System Admin') AND u.is_active = 1
                GROUP BY u.id, u.full_name, u.email, u.department
                ORDER BY u.full_name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get available project managers error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get active projects for dropdown (role-based)
     */
    public function getActiveProjects() {
        try {
            $currentUser = Auth::getInstance()->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            
            $conditions = ["p.is_active = 1"];
            $params = [];
            
            // Project Managers can only see their assigned projects
            if ($userRole === 'Project Manager' && !in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
                $conditions[] = "(p.project_manager_id = ? OR up.user_id = ?)";
                $params[] = $currentUser['id'];
                $params[] = $currentUser['id'];
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT DISTINCT p.id, p.name, p.code, p.location
                FROM projects p
                LEFT JOIN user_projects up ON p.id = up.project_id AND up.is_active = 1
                {$whereClause}
                ORDER BY p.name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("ProjectModel::getActiveProjects error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if project code exists
     */
    public function isCodeExists($code, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE code = ?";
        $params = [$code];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Code exists check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete project with validation
     */
    public function deleteProject($id) {
        try {
            $this->beginTransaction();
            
            // Check if project has assets
            $assetCount = $this->db->prepare("SELECT COUNT(*) FROM assets WHERE project_id = ?");
            $assetCount->execute([$id]);
            $count = $assetCount->fetchColumn();
            
            if ($count > 0) {
                $this->rollback();
                return ['success' => false, 'message' => 'Cannot delete project with assigned assets'];
            }
            
            // Check if project has active procurement orders
            $procurementCount = $this->db->prepare("SELECT COUNT(*) FROM procurement_orders WHERE project_id = ? AND status NOT IN ('Delivered', 'Received', 'Rejected')");
            $procurementCount->execute([$id]);
            $procCount = $procurementCount->fetchColumn();
            
            if ($procCount > 0) {
                $this->rollback();
                return ['success' => false, 'message' => 'Cannot delete project with active procurement orders'];
            }
            
            // Deactivate user assignments
            $deactivateAssignments = $this->db->prepare("UPDATE user_projects SET is_active = 0 WHERE project_id = ?");
            $deactivateAssignments->execute([$id]);
            
            // Clear current project assignments from users
            $clearCurrentProject = $this->db->prepare("UPDATE users SET current_project_id = NULL WHERE current_project_id = ?");
            $clearCurrentProject->execute([$id]);
            
            $deleted = $this->delete($id);
            
            if ($deleted) {
                // Log activity
                $this->logActivity('project_deleted', 'Project deleted', 'projects', $id);
                
                $this->commit();
                return ['success' => true, 'message' => 'Project deleted successfully'];
            } else {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to delete project'];
            }
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Project deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete project'];
        }
    }
    
    /**
     * Toggle project status
     */
    public function toggleProjectStatus($id) {
        try {
            $project = $this->find($id);
            if (!$project) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            $newStatus = $project['is_active'] ? 0 : 1;
            $updated = $this->update($id, ['is_active' => $newStatus]);
            
            if ($updated) {
                $statusText = $newStatus ? 'activated' : 'deactivated';
                $this->logActivity('project_status_changed', "Project {$statusText}", 'projects', $id);
                
                return ['success' => true, 'message' => 'Project status updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update project status'];
            }
            
        } catch (Exception $e) {
            error_log("Project status toggle error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update project status'];
        }
    }
    
    /**
     * Get project activity summary with enhanced metrics
     */
    public function getProjectActivity($projectId, $dateFrom = null, $dateTo = null) {
        try {
            $conditions = ["p.id = ?"];
            $params = [$projectId];
            
            if ($dateFrom) {
                $conditions[] = "DATE(w.created_at) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $conditions[] = "DATE(w.created_at) <= ?";
                $params[] = $dateTo;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT 
                    COUNT(DISTINCT w.id) as total_withdrawals,
                    COUNT(DISTINCT CASE WHEN w.status = 'pending' THEN w.id END) as pending_withdrawals,
                    COUNT(DISTINCT CASE WHEN w.status = 'released' THEN w.id END) as active_withdrawals,
                    COUNT(DISTINCT tin.id) as total_transfers_in,
                    COUNT(DISTINCT tout.id) as total_transfers_out,
                    COUNT(DISTINCT po.id) as procurement_orders,
                    COUNT(DISTINCT r.id) as requests_count,
                    COALESCE(SUM(po.net_total), 0) as total_procurement_value
                FROM projects p
                LEFT JOIN withdrawals w ON p.id = w.project_id
                LEFT JOIN transfers tin ON p.id = tin.to_project
                LEFT JOIN transfers tout ON p.id = tout.from_project
                LEFT JOIN procurement_orders po ON p.id = po.project_id
                LEFT JOIN requests r ON p.id = r.project_id
                {$whereClause}
                GROUP BY p.id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() ?: [
                'total_withdrawals' => 0,
                'pending_withdrawals' => 0,
                'active_withdrawals' => 0,
                'total_transfers_in' => 0,
                'total_transfers_out' => 0,
                'procurement_orders' => 0,
                'requests_count' => 0,
                'total_procurement_value' => 0
            ];
            
        } catch (Exception $e) {
            error_log("Get project activity error: " . $e->getMessage());
            return [
                'total_withdrawals' => 0,
                'pending_withdrawals' => 0,
                'active_withdrawals' => 0,
                'total_transfers_in' => 0,
                'total_transfers_out' => 0,
                'procurement_orders' => 0,
                'requests_count' => 0,
                'total_procurement_value' => 0
            ];
        }
    }
    
    /**
     * Log activity for audit trail
     */
    private function logActivity($action, $description, $table, $recordId) {
        try {
            $auth = Auth::getInstance();
            $user = $auth->getCurrentUser();
            
            $sql = "INSERT INTO activity_logs (user_id, action, description, table_name, record_id, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $user['id'] ?? null,
                $action,
                $description,
                $table,
                $recordId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Activity logging error: " . $e->getMessage());
        }
    }
}
?>
