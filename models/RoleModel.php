<?php
/**
 * ConstructLink™ Role Model
 * Handles user roles and permissions
 */

class RoleModel extends BaseModel {
    protected $table = 'roles';
    protected $fillable = ['name', 'description', 'permissions'];
    
    /**
     * Get all roles with user count
     */
    public function getRolesWithUserCount() {
        $sql = "
            SELECT 
                r.*,
                COUNT(u.id) as user_count
            FROM {$this->table} r
            LEFT JOIN users u ON r.id = u.role_id
            GROUP BY r.id
            ORDER BY r.id
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get roles for dropdown
     */
    public function getRolesForDropdown() {
        return $this->findAll([], 'name ASC');
    }
    
    /**
     * Get role permissions
     */
    public function getRolePermissions($roleId) {
        $role = $this->findById($roleId);
        if (!$role || empty($role['permissions'])) {
            return [];
        }
        
        return json_decode($role['permissions'], true) ?: [];
    }
    
    /**
     * Check if role has permission
     */
    public function hasPermission($roleId, $permission) {
        $permissions = $this->getRolePermissions($roleId);
        return in_array($permission, $permissions);
    }
    
    /**
     * Get default role permissions based on role name
     */
    public function getDefaultPermissions($roleName) {
        $permissions = [
            'System Admin' => [
                'view_all_assets', 'edit_assets', 'delete_assets',
                'approve_transfers', 'approve_disposal', 'manage_users',
                'view_reports', 'manage_procurement', 'release_assets',
                'receive_assets', 'manage_maintenance', 'manage_incidents',
                'manage_master_data', 'system_administration', 'manage_withdrawals', 'request_withdrawals'
            ],
            'Finance Director' => [
                'view_all_assets', 'approve_disposal', 'view_reports',
                'view_financial_data', 'approve_high_value_transfers', 'manage_withdrawals'
            ],
            'Asset Director' => [
                'view_all_assets', 'edit_assets', 'approve_transfers',
                'view_reports', 'manage_maintenance', 'manage_incidents',
                'flag_idle_assets', 'release_assets', 'receive_assets', 'manage_withdrawals'
            ],
            'Procurement Officer' => [
                'view_all_assets', 'manage_procurement', 'receive_assets',
                'manage_vendors', 'manage_makers', 'view_procurement_reports'
            ],
            'Warehouseman' => [
                'view_project_assets', 'release_assets', 'receive_assets',
                'manage_withdrawals', 'basic_asset_logs', 'manage_borrowed_tools'
            ],
            'Project Manager' => [
                'view_project_assets', 'request_withdrawals', 'approve_site_actions',
                'initiate_transfers', 'manage_incidents', 'view_project_reports', 'receive_assets'
            ],
            'Site Inventory Clerk' => [
                'view_project_assets', 'request_withdrawals', 'scan_qr_codes',
                'log_borrower_info', 'manage_incidents'
            ]
        ];
        
        return $permissions[$roleName] ?? [];
    }
    
    /**
     * Create role with validation
     */
    public function createRole($data) {
        try {
            // Validate required fields
            $errors = [];
            
            if (empty($data['name'])) {
                $errors[] = 'Role name is required';
            }
            
            // Check for duplicate name
            if ($this->findFirst(['name' => $data['name']])) {
                $errors[] = 'Role name already exists';
            }
            
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Prepare permissions
            $permissions = $data['permissions'] ?? [];
            if (is_array($permissions)) {
                $permissions = json_encode($permissions);
            }
            
            // Prepare data
            $roleData = [
                'name' => Validator::sanitize($data['name']),
                'description' => Validator::sanitize($data['description'] ?? ''),
                'permissions' => $permissions,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $roleId = $this->create($roleData);
            
            if ($roleId) {
                logActivity('role_created', "Role '{$roleData['name']}' created");
                
                return [
                    'success' => true,
                    'role' => array_merge($roleData, ['id' => $roleId]),
                    'message' => 'Role created successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create role'];
            }
            
        } catch (Exception $e) {
            error_log("Role creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Update role
     */
    public function updateRole($id, $data) {
        try {
            $role = $this->findById($id);
            if (!$role) {
                return ['success' => false, 'message' => 'Role not found'];
            }
            
            // Validate required fields
            $errors = [];
            
            if (empty($data['name'])) {
                $errors[] = 'Role name is required';
            }
            
            // Check for duplicate name (excluding current role)
            $existing = $this->findFirst(['name' => $data['name']]);
            if ($existing && $existing['id'] != $id) {
                $errors[] = 'Role name already exists';
            }
            
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Prepare permissions
            $permissions = $data['permissions'] ?? [];
            if (is_array($permissions)) {
                $permissions = json_encode($permissions);
            }
            
            // Prepare data
            $roleData = [
                'name' => Validator::sanitize($data['name']),
                'description' => Validator::sanitize($data['description'] ?? ''),
                'permissions' => $permissions,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $success = $this->update($id, $roleData);
            
            if ($success) {
                logActivity('role_updated', "Role '{$roleData['name']}' updated");
                
                return [
                    'success' => true,
                    'role' => array_merge($roleData, ['id' => $id]),
                    'message' => 'Role updated successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update role'];
            }
            
        } catch (Exception $e) {
            error_log("Role update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Delete role
     */
    public function deleteRole($id) {
        try {
            $role = $this->findById($id);
            if (!$role) {
                return ['success' => false, 'message' => 'Role not found'];
            }
            
            // Check if role has users
            $userCount = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
            $userCount->execute([$id]);
            
            if ($userCount->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Cannot delete role with existing users'];
            }
            
            // Prevent deletion of system roles
            $systemRoles = ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'];
            if (in_array($role['name'], $systemRoles)) {
                return ['success' => false, 'message' => 'Cannot delete system role'];
            }
            
            $success = $this->delete($id);
            
            if ($success) {
                logActivity('role_deleted', "Role '{$role['name']}' deleted");
                return ['success' => true, 'message' => 'Role deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete role'];
            }
            
        } catch (Exception $e) {
            error_log("Role deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get all available permissions
     */
    public function getAllPermissions() {
        return [
            'Assets' => [
                'view_all_assets' => 'View All Assets',
                'view_project_assets' => 'View Project Assets',
                'edit_assets' => 'Edit Assets',
                'delete_assets' => 'Delete Assets',
                'flag_idle_assets' => 'Flag Idle Assets'
            ],
            'Operations' => [
                'manage_withdrawals' => 'Manage Withdrawals',
                'request_withdrawals' => 'Request Withdrawals',
                'release_assets' => 'Release Assets',
                'receive_assets' => 'Receive Assets',
                'approve_transfers' => 'Approve Transfers',
                'initiate_transfers' => 'Initiate Transfers',
                'manage_maintenance' => 'Manage Maintenance',
                'manage_incidents' => 'Manage Incidents',
                'manage_borrowed_tools' => 'Manage Borrowed Tools'
            ],
            'Procurement' => [
                'manage_procurement' => 'Manage Procurement',
                'manage_vendors' => 'Manage Vendors',
                'manage_makers' => 'Manage Makers'
            ],
            'Reports' => [
                'view_reports' => 'View Reports',
                'view_project_reports' => 'View Project Reports',
                'view_procurement_reports' => 'View Procurement Reports',
                'view_financial_data' => 'View Financial Data'
            ],
            'Administration' => [
                'manage_users' => 'Manage Users',
                'manage_master_data' => 'Manage Master Data',
                'system_administration' => 'System Administration',
                'approve_disposal' => 'Approve Asset Disposal',
                'approve_high_value_transfers' => 'Approve High Value Transfers'
            ],
            'Other' => [
                'scan_qr_codes' => 'Scan QR Codes',
                'basic_asset_logs' => 'Basic Asset Logs',
                'log_borrower_info' => 'Log Borrower Info',
                'approve_site_actions' => 'Approve Site Actions'
            ]
        ];
    }
}
?>
