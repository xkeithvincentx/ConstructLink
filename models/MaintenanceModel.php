<?php
/**
 * ConstructLink™ Maintenance Model
 * Handles asset maintenance scheduling and tracking
 */

class MaintenanceModel extends BaseModel {
    protected $table = 'maintenance';
    protected $fillable = [
        'asset_id', 'type', 'description', 'scheduled_date', 'completed_date',
        'assigned_to', 'estimated_cost', 'actual_cost', 'parts_used',
        'status', 'priority', 'completion_notes', 'next_maintenance_date',
        'created_by', 'verified_by', 'verification_date', 'approved_by', 'approval_date', 'notes'
    ];
    
    /**
     * Create maintenance record
     */
    public function createMaintenance($data) {
        $validation = $this->validate($data, [
            'asset_id' => 'required|exists:assets,id',
            'type' => 'required|in:preventive,corrective,emergency',
            'description' => 'required',
            'scheduled_date' => 'required|date',
            'priority' => 'in:low,medium,high,urgent',
            'estimated_cost' => 'numeric'
        ]);
        
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        try {
            $this->beginTransaction();
            
            // Check if asset exists and is not retired
            $assetModel = new AssetModel();
            $asset = $assetModel->find($data['asset_id']);
            
            if (!$asset) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            if ($asset['status'] === 'retired') {
                $this->rollback();
                return ['success' => false, 'message' => 'Cannot schedule maintenance for retired asset'];
            }
            
            // Intelligent validation - check if asset can be maintained
            $canBeMaintainedResult = $assetModel->canBeMaintained($data['asset_id']);
            if (!$canBeMaintainedResult['can_maintain']) {
                $this->rollback();
                return ['success' => false, 'message' => $canBeMaintainedResult['reason']];
            }
            
            // Check for conflicting maintenance schedules
            $conflictCheck = $this->checkMaintenanceConflicts($data['asset_id'], $data['scheduled_date']);
            if (!$conflictCheck['can_schedule']) {
                $this->rollback();
                return ['success' => false, 'message' => $conflictCheck['reason']];
            }
            
            // Intelligent cost validation based on asset value
            if (!empty($data['estimated_cost'])) {
                $costValidation = $this->validateMaintenanceCost($asset, $data['estimated_cost']);
                if (!$costValidation['valid']) {
                    $this->rollback();
                    return ['success' => false, 'message' => $costValidation['message']];
                }
            }
            
            // Set defaults according to schema
            $maintenanceData = [
                'asset_id' => (int)$data['asset_id'],
                'type' => $data['type'],
                'description' => Validator::sanitize($data['description']),
                'scheduled_date' => $data['scheduled_date'],
                'assigned_to' => Validator::sanitize($data['assigned_to'] ?? ''),
                'estimated_cost' => !empty($data['estimated_cost']) ? (float)$data['estimated_cost'] : null,
                'status' => 'Pending Verification',
                'priority' => $data['priority'] ?? 'medium',
                'created_by' => $data['created_by'] ?? null
            ];
            
            // Create maintenance record
            $maintenance = $this->create($maintenanceData);
            
            if (!$maintenance) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to create maintenance record'];
            }
            
            // If emergency maintenance, update asset status
            if ($data['type'] === 'emergency') {
                $assetModel->update($data['asset_id'], ['status' => 'under_maintenance']);
                logActivity('asset_status_changed', "Asset status changed to under_maintenance due to emergency maintenance", 'assets', $data['asset_id']);
            }
            
            // Log activity
            logActivity('maintenance_created', "Maintenance scheduled for asset {$asset['ref']}", 'maintenance', $maintenance['id']);
            
            $this->commit();
            
            return ['success' => true, 'maintenance' => $maintenance];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Maintenance creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create maintenance record'];
        }
    }
    
    /**
     * Verify maintenance request
     */
    public function verifyMaintenance($id, $verifiedBy, $notes = null) {
        try {
            $this->beginTransaction();

            $maintenance = $this->find($id);
            if (!$maintenance) {
                $this->rollback();
                return ['success' => false, 'message' => 'Maintenance record not found'];
            }

            // Get verifier user role for MVA validation
            $userModel = new UserModel();
            $verifier = $userModel->find($verifiedBy);
            $verifierRole = $verifier['role_name'] ?? '';

            // Validate MVA workflow transition
            $workflowValidation = $this->validateMVAWorkflow($id, 'Pending Approval', $verifierRole);
            if (!$workflowValidation['valid']) {
                $this->rollback();
                return ['success' => false, 'message' => $workflowValidation['message']];
            }

            // Business logic for verification
            // Check if asset is still available for maintenance
            $assetModel = new AssetModel();
            $asset = $assetModel->find($maintenance['asset_id']);
            
            if (!$asset) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset not found for verification'];
            }

            if ($asset['status'] === 'retired') {
                $this->rollback();
                return ['success' => false, 'message' => 'Cannot verify maintenance for retired asset'];
            }

            // Verify estimated cost is reasonable
            if ($maintenance['estimated_cost'] && $maintenance['estimated_cost'] > 100000) {
                if (empty($notes)) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Verification notes required for high-cost maintenance'];
                }
            }

            $updateResult = $this->update($id, [
                'status' => 'Pending Approval',
                'verified_by' => $verifiedBy,
                'verification_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ]);

            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to verify maintenance'];
            }

            logActivity('maintenance_verified', 'Maintenance verified for asset ' . $asset['ref'], 'maintenance', $id);
            $this->commit();

            return ['success' => true, 'message' => 'Maintenance verified successfully'];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Maintenance verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to verify maintenance'];
        }
    }
    
    /**
     * Authorize maintenance request
     */
    public function authorizeMaintenance($id, $authorizedBy, $notes = null) {
        try {
            $this->beginTransaction();

            $maintenance = $this->find($id);
            if (!$maintenance) {
                $this->rollback();
                return ['success' => false, 'message' => 'Maintenance record not found'];
            }

            // Get authorizer user role for MVA validation
            $userModel = new UserModel();
            $authorizer = $userModel->find($authorizedBy);
            $authorizerRole = $authorizer['role_name'] ?? '';

            // Validate MVA workflow transition
            $workflowValidation = $this->validateMVAWorkflow($id, 'Approved', $authorizerRole);
            if (!$workflowValidation['valid']) {
                $this->rollback();
                return ['success' => false, 'message' => $workflowValidation['message']];
            }

            // Business logic for authorization
            // Check if budget is available for maintenance
            if ($maintenance['estimated_cost'] && $maintenance['estimated_cost'] > 50000) {
                if (empty($notes)) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Authorization notes required for high-cost maintenance'];
                }
            }

            // Get asset information
            $assetModel = new AssetModel();
            $asset = $assetModel->find($maintenance['asset_id']);
            
            if (!$asset) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset not found for authorization'];
            }

            $updateResult = $this->update($id, [
                'status' => 'Approved',
                'approved_by' => $authorizedBy,
                'approval_date' => date('Y-m-d H:i:s'),
                'notes' => $maintenance['notes'] . ($notes ? "\n\nAuthorization Notes: " . $notes : '')
            ]);

            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to authorize maintenance'];
            }

            logActivity('maintenance_authorized', 'Maintenance authorized for asset ' . $asset['ref'], 'maintenance', $id);
            $this->commit();

            return ['success' => true, 'message' => 'Maintenance authorized successfully'];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Maintenance authorization error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to authorize maintenance'];
        }
    }
    
    /**
     * Start maintenance work
     */
    public function startMaintenance($maintenanceId, $assignedTo = null) {
        try {
            $this->beginTransaction();
            
            $maintenance = $this->getMaintenanceWithDetails($maintenanceId);
            if (!$maintenance) {
                $this->rollback();
                return ['success' => false, 'message' => 'Maintenance record not found'];
            }
            
            // Validate MVA workflow sequence - maintenance must be approved to start
            if (!in_array($maintenance['status'], ['Approved'])) {
                $this->rollback();
                return ['success' => false, 'message' => 'Maintenance must be approved before it can be started. Current status: ' . $maintenance['status']];
            }
            
            // Update maintenance status
            $updateData = ['status' => 'in_progress'];
            if ($assignedTo) {
                $updateData['assigned_to'] = Validator::sanitize($assignedTo);
            }
            
            $updateResult = $this->update($maintenanceId, $updateData);
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update maintenance status'];
            }
            
            // Update asset status to under_maintenance
            $assetModel = new AssetModel();
            $assetUpdateResult = $assetModel->update($maintenance['asset_id'], ['status' => 'under_maintenance']);
            
            if (!$assetUpdateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update asset status'];
            }
            
            // Log activity
            logActivity('maintenance_started', "Maintenance work started for asset {$maintenance['asset_ref']}", 'maintenance', $maintenanceId);
            
            $this->commit();
            
            return [
                'success' => true, 
                'maintenance' => $updateResult,
                'message' => 'Maintenance work started'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Maintenance start error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to start maintenance'];
        }
    }
    
    /**
     * Complete maintenance work
     */
    public function completeMaintenance($maintenanceId, $data) {
        $validation = $this->validate($data, [
            'completion_notes' => 'required',
            'actual_cost' => 'numeric',
            'next_maintenance_date' => 'date'
        ]);
        
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        try {
            $this->beginTransaction();
            
            $maintenance = $this->getMaintenanceWithDetails($maintenanceId);
            if (!$maintenance) {
                $this->rollback();
                return ['success' => false, 'message' => 'Maintenance record not found'];
            }
            
            if ($maintenance['status'] !== 'in_progress') {
                $this->rollback();
                return ['success' => false, 'message' => 'Maintenance is not in progress'];
            }
            
            // Update maintenance record according to schema
            $updateData = [
                'status' => 'completed',
                'completed_date' => date('Y-m-d'),
                'completion_notes' => Validator::sanitize($data['completion_notes']),
                'actual_cost' => !empty($data['actual_cost']) ? (float)$data['actual_cost'] : null,
                'parts_used' => Validator::sanitize($data['parts_used'] ?? ''),
                'next_maintenance_date' => $data['next_maintenance_date'] ?? null
            ];
            
            $updateResult = $this->update($maintenanceId, $updateData);
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update maintenance record'];
            }
            
            // Update asset status back to available
            $assetModel = new AssetModel();
            $assetUpdateResult = $assetModel->update($maintenance['asset_id'], ['status' => 'available']);
            
            if (!$assetUpdateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update asset status'];
            }
            
            // Log activity
            logActivity('maintenance_completed', "Maintenance completed for asset {$maintenance['asset_ref']}", 'maintenance', $maintenanceId);
            
            $this->commit();
            
            return [
                'success' => true, 
                'maintenance' => $updateResult,
                'message' => 'Maintenance completed successfully'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Maintenance completion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to complete maintenance'];
        }
    }
    
    /**
     * Cancel maintenance
     */
    public function cancelMaintenance($maintenanceId, $reason = null) {
        try {
            $this->beginTransaction();
            
            $maintenance = $this->find($maintenanceId);
            if (!$maintenance) {
                $this->rollback();
                return ['success' => false, 'message' => 'Maintenance record not found'];
            }
            
            if (!in_array($maintenance['status'], ['Pending Verification', 'Pending Approval', 'Approved', 'in_progress'])) {
                $this->rollback();
                return ['success' => false, 'message' => 'Cannot cancel maintenance in current status'];
            }
            
            $oldStatus = $maintenance['status'];
            
            // Update maintenance status
            $updateData = ['status' => 'canceled'];
            if ($reason) {
                $updateData['completion_notes'] = $maintenance['completion_notes'] . "\n\nCanceled: " . $reason;
            }
            
            $updateResult = $this->update($maintenanceId, $updateData);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to cancel maintenance'];
            }
            
            // If maintenance was in progress, return asset to available status
            if ($oldStatus === 'in_progress') {
                $assetModel = new AssetModel();
                $assetUpdateResult = $assetModel->update($maintenance['asset_id'], ['status' => 'available']);
                
                if (!$assetUpdateResult) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Failed to update asset status'];
                }
            }
            
            // Log activity
            logActivity('maintenance_canceled', "Maintenance canceled for maintenance ID {$maintenanceId}", 'maintenance', $maintenanceId);
            
            $this->commit();
            
            return [
                'success' => true, 
                'maintenance' => $updateResult,
                'message' => 'Maintenance canceled successfully'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Maintenance cancellation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to cancel maintenance'];
        }
    }
    
    /**
     * Get maintenance with detailed information
     */
    public function getMaintenanceWithDetails($id) {
        $sql = "
            SELECT m.*, 
                   a.ref as asset_ref, a.name as asset_name, a.status as asset_status,
                   c.name as category_name,
                   p.name as project_name,
                   uc.full_name as created_by_name,
                   uv.full_name as verified_by_name,
                   ua.full_name as approved_by_name
            FROM {$this->table} m
            LEFT JOIN assets a ON m.asset_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN projects p ON a.project_id = p.id
            LEFT JOIN users uc ON m.created_by = uc.id
            LEFT JOIN users uv ON m.verified_by = uv.id
            LEFT JOIN users ua ON m.approved_by = ua.id
            WHERE m.id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get maintenance records with filters and pagination
     */
    public function getMaintenanceWithFilters($filters = [], $page = 1, $perPage = 20) {
        $conditions = [];
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $conditions[] = "m.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['type'])) {
            $conditions[] = "m.type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['priority'])) {
            $conditions[] = "m.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['asset_id'])) {
            $conditions[] = "m.asset_id = ?";
            $params[] = $filters['asset_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(m.scheduled_date) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(m.scheduled_date) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(a.ref LIKE ? OR a.name LIKE ? OR m.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        // Count total records
        $countSql = "
            SELECT COUNT(*) 
            FROM {$this->table} m
            LEFT JOIN assets a ON m.asset_id = a.id
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get paginated data
        $offset = ($page - 1) * $perPage;
        $orderBy = $filters['order_by'] ?? 'm.scheduled_date DESC';
        
        $dataSql = "
            SELECT m.*, 
                   a.ref as asset_ref, a.name as asset_name,
                   c.name as category_name,
                   p.name as project_name
            FROM {$this->table} m
            LEFT JOIN assets a ON m.asset_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN projects p ON a.project_id = p.id
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
     * Get maintenance statistics
     */
    public function getMaintenanceStats($dateFrom = null, $dateTo = null) {
        $conditions = [];
        $params = [];
        
        if ($dateFrom) {
            $conditions[] = "DATE(scheduled_date) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $conditions[] = "DATE(scheduled_date) <= ?";
            $params[] = $dateTo;
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        $sql = "
            SELECT 
                COUNT(*) as total_maintenance,
                SUM(CASE WHEN status = 'Pending Verification' THEN 1 ELSE 0 END) as pending_verification,
                SUM(CASE WHEN status = 'Pending Approval' THEN 1 ELSE 0 END) as pending_approval,
                SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled,
                SUM(CASE WHEN type = 'preventive' THEN 1 ELSE 0 END) as preventive,
                SUM(CASE WHEN type = 'corrective' THEN 1 ELSE 0 END) as corrective,
                SUM(CASE WHEN type = 'emergency' THEN 1 ELSE 0 END) as emergency,
                SUM(CASE WHEN status IN ('Pending Verification', 'Pending Approval', 'Approved') AND scheduled_date < CURDATE() THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN status IN ('Pending Verification', 'Pending Approval', 'Approved') THEN 1 ELSE 0 END) as pending,
                COALESCE(SUM(actual_cost), 0) as total_cost,
                COALESCE(AVG(actual_cost), 0) as average_cost
            FROM {$this->table}
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get overdue maintenance
     */
    public function getOverdueMaintenance() {
        $sql = "
            SELECT m.*, 
                   a.ref as asset_ref, a.name as asset_name,
                   c.name as category_name,
                   p.name as project_name,
                   DATEDIFF(CURDATE(), m.scheduled_date) as days_overdue
            FROM {$this->table} m
            LEFT JOIN assets a ON m.asset_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN projects p ON a.project_id = p.id
            WHERE m.status IN ('Pending Verification', 'Pending Approval', 'Approved') 
              AND m.scheduled_date < CURDATE()
            ORDER BY m.scheduled_date ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get upcoming maintenance
     */
    public function getUpcomingMaintenance($days = 7) {
        $sql = "
            SELECT m.*, 
                   a.ref as asset_ref, a.name as asset_name,
                   c.name as category_name,
                   p.name as project_name,
                   DATEDIFF(m.scheduled_date, CURDATE()) as days_until_due
            FROM {$this->table} m
            LEFT JOIN assets a ON m.asset_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN projects p ON a.project_id = p.id
            WHERE m.status IN ('Pending Verification', 'Pending Approval', 'Approved') 
              AND m.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY m.scheduled_date ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get maintenance by type
     */
    public function getMaintenanceByType($dateFrom = null, $dateTo = null) {
        $conditions = [];
        $params = [];
        
        if ($dateFrom) {
            $conditions[] = "DATE(scheduled_date) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $conditions[] = "DATE(scheduled_date) <= ?";
            $params[] = $dateTo;
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        $sql = "
            SELECT type,
                   COUNT(*) as count,
                   COALESCE(SUM(actual_cost), 0) as total_cost,
                   COALESCE(AVG(actual_cost), 0) as average_cost,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
            FROM {$this->table}
            {$whereClause}
            GROUP BY type
            ORDER BY count DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get asset maintenance history
     */
    public function getAssetMaintenanceHistory($assetId) {
        $sql = "
            SELECT m.*
            FROM {$this->table} m
            WHERE m.asset_id = ?
            ORDER BY m.scheduled_date DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get maintenance cost analysis
     */
    public function getMaintenanceCostAnalysis($dateFrom, $dateTo) {
        $sql = "
            SELECT 
                c.name as category_name,
                COUNT(m.id) as maintenance_count,
                COALESCE(SUM(m.actual_cost), 0) as total_cost,
                COALESCE(AVG(m.actual_cost), 0) as average_cost,
                SUM(CASE WHEN m.type = 'preventive' THEN COALESCE(m.actual_cost, 0) ELSE 0 END) as preventive_cost,
                SUM(CASE WHEN m.type = 'corrective' THEN COALESCE(m.actual_cost, 0) ELSE 0 END) as corrective_cost,
                SUM(CASE WHEN m.type = 'emergency' THEN COALESCE(m.actual_cost, 0) ELSE 0 END) as emergency_cost
            FROM {$this->table} m
            LEFT JOIN assets a ON m.asset_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE m.scheduled_date BETWEEN ? AND ?
              AND m.status = 'completed'
              AND m.actual_cost IS NOT NULL
            GROUP BY c.id, c.name
            ORDER BY total_cost DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get recent maintenance activities
     */
    public function getRecentMaintenance($limit = 10, $projectId = null) {
        $whereClause = $projectId ? "WHERE a.project_id = ?" : "";
        $params = $projectId ? [$projectId, $limit] : [$limit];
        
        $sql = "
            SELECT m.*, 
                   a.ref as asset_ref, a.name as asset_name,
                   c.name as category_name,
                   p.name as project_name
            FROM {$this->table} m
            LEFT JOIN assets a ON m.asset_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN projects p ON a.project_id = p.id
            {$whereClause}
            ORDER BY m.created_at DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get maintenance schedule for calendar
     */
    public function getMaintenanceSchedule($startDate, $endDate) {
        try {
            $sql = "
                SELECT m.*, 
                       a.ref as asset_ref, a.name as asset_name,
                       c.name as category_name,
                       p.name as project_name
                FROM {$this->table} m
                LEFT JOIN assets a ON m.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                WHERE m.scheduled_date BETWEEN ? AND ?
                  AND m.status IN ('Pending Verification', 'Pending Approval', 'Approved', 'in_progress')
                ORDER BY m.scheduled_date ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Maintenance schedule error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get maintenance report data
     */
    public function getMaintenanceReport($dateFrom, $dateTo, $type = null, $status = null) {
        $conditions = ["m.scheduled_date BETWEEN ? AND ?"];
        $params = [$dateFrom, $dateTo];
        
        if ($type) {
            $conditions[] = "m.type = ?";
            $params[] = $type;
        }
        
        if ($status) {
            $conditions[] = "m.status = ?";
            $params[] = $status;
        }
        
        $whereClause = "WHERE " . implode(" AND ", $conditions);
        
        $sql = "
            SELECT m.*, 
                   a.ref as asset_ref, a.name as asset_name,
                   c.name as category_name,
                   p.name as project_name
            FROM {$this->table} m
            LEFT JOIN assets a ON m.asset_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN projects p ON a.project_id = p.id
            {$whereClause}
            ORDER BY m.scheduled_date DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Generate preventive maintenance schedule
     */
    public function generatePreventiveSchedule($assetId, $intervalMonths = 6, $scheduleMonths = 12) {
        try {
            $assetModel = new AssetModel();
            $asset = $assetModel->find($assetId);
            
            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            // Get last maintenance date
            $lastMaintenance = $this->findFirst(
                ['asset_id' => $assetId, 'type' => 'preventive', 'status' => 'completed'],
                'completed_date DESC'
            );
            
            $startDate = $lastMaintenance ? 
                date('Y-m-d', strtotime($lastMaintenance['completed_date'] . " + {$intervalMonths} months")) :
                date('Y-m-d', strtotime("+ {$intervalMonths} months"));
            
            $schedules = [];
            $currentDate = $startDate;
            
            for ($i = 0; $i < ($scheduleMonths / $intervalMonths); $i++) {
                $scheduleData = [
                    'asset_id' => $assetId,
                    'type' => 'preventive',
                    'description' => 'Scheduled preventive maintenance as per maintenance schedule',
                    'scheduled_date' => $currentDate,
                    'status' => 'Pending Verification',
                    'priority' => 'medium'
                ];
                
                $result = $this->create($scheduleData);
                if ($result) {
                    $schedules[] = $result;
                }
                
                $currentDate = date('Y-m-d', strtotime($currentDate . " + {$intervalMonths} months"));
            }
            
            return [
                'success' => true,
                'schedules' => $schedules,
                'message' => count($schedules) . ' maintenance schedules created'
            ];
            
        } catch (Exception $e) {
            error_log("Preventive maintenance schedule error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate maintenance schedule'];
        }
    }
    
    /**
     * Get intelligent maintenance schedule recommendations
     */
    public function getMaintenanceRecommendations($assetId) {
        try {
            $assetModel = new AssetModel();
            $asset = $assetModel->find($assetId);
            
            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            // Check if asset can be maintained
            $canBeMaintainedResult = $assetModel->canBeMaintained($assetId);
            if (!$canBeMaintainedResult['can_maintain']) {
                return [
                    'success' => false, 
                    'message' => $canBeMaintainedResult['reason']
                ];
            }
            
            $recommendations = [];
            
            // Get asset maintenance history
            $maintenanceHistory = $this->getAssetMaintenanceHistory($assetId);
            $lastMaintenance = !empty($maintenanceHistory) ? $maintenanceHistory[0] : null;
            
            // Calculate maintenance frequency based on asset type and usage
            $category = $asset['category_name'] ?? '';
            $assetAge = 0;
            
            if (!empty($asset['purchase_date'])) {
                $purchaseDate = strtotime($asset['purchase_date']);
                $assetAge = (time() - $purchaseDate) / (365.25 * 24 * 3600); // Years
            }
            
            // Determine recommended maintenance interval based on asset type
            $recommendedInterval = $this->getRecommendedMaintenanceInterval($category, $assetAge);
            
            // Calculate next recommended maintenance date
            $nextMaintenanceDate = $this->calculateNextMaintenanceDate($lastMaintenance, $recommendedInterval);
            
            $recommendations[] = [
                'type' => 'preventive',
                'recommended_date' => $nextMaintenanceDate,
                'interval_months' => $recommendedInterval,
                'priority' => $this->calculateMaintenancePriority($asset, $lastMaintenance),
                'reason' => $this->getMaintenanceReason($category, $assetAge, $lastMaintenance)
            ];
            
            // Check for overdue maintenance
            if ($lastMaintenance && $lastMaintenance['status'] === 'completed') {
                $lastMaintenanceDate = strtotime($lastMaintenance['completed_date']);
                $monthsSinceLastMaintenance = (time() - $lastMaintenanceDate) / (30.44 * 24 * 3600);
                
                if ($monthsSinceLastMaintenance > $recommendedInterval) {
                    $recommendations[] = [
                        'type' => 'overdue',
                        'recommended_date' => date('Y-m-d'),
                        'interval_months' => 0,
                        'priority' => 'high',
                        'reason' => sprintf(
                            'Maintenance is overdue by %.1f months. Last maintenance was on %s.',
                            $monthsSinceLastMaintenance - $recommendedInterval,
                            date('M j, Y', $lastMaintenanceDate)
                        )
                    ];
                }
            }
            
            return [
                'success' => true,
                'asset' => $asset,
                'recommendations' => $recommendations,
                'maintenance_history' => $maintenanceHistory
            ];
            
        } catch (Exception $e) {
            error_log("Maintenance recommendations error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate maintenance recommendations'];
        }
    }
    
    /**
     * Get recommended maintenance interval based on asset category and age
     */
    private function getRecommendedMaintenanceInterval($category, $assetAge) {
        // Default intervals based on common asset types
        $intervals = [
            'vehicles' => 6,
            'machinery' => 4,
            'equipment' => 6,
            'tools' => 12,
            'electronics' => 12,
            'furniture' => 24,
            'building' => 12,
            'hvac' => 3,
            'generator' => 3,
            'pump' => 6
        ];
        
        $categoryLower = strtolower($category);
        $baseInterval = 6; // Default 6 months
        
        // Find matching category
        foreach ($intervals as $cat => $interval) {
            if (strpos($categoryLower, $cat) !== false) {
                $baseInterval = $interval;
                break;
            }
        }
        
        // Adjust based on asset age
        if ($assetAge > 10) {
            $baseInterval = max(3, $baseInterval - 2); // More frequent for older assets
        } elseif ($assetAge > 5) {
            $baseInterval = max(3, $baseInterval - 1);
        }
        
        return $baseInterval;
    }
    
    /**
     * Calculate next maintenance date
     */
    private function calculateNextMaintenanceDate($lastMaintenance, $intervalMonths) {
        if ($lastMaintenance && $lastMaintenance['status'] === 'completed' && $lastMaintenance['completed_date']) {
            return date('Y-m-d', strtotime($lastMaintenance['completed_date'] . " + {$intervalMonths} months"));
        } elseif ($lastMaintenance && $lastMaintenance['next_maintenance_date']) {
            return $lastMaintenance['next_maintenance_date'];
        } else {
            return date('Y-m-d', strtotime("+ {$intervalMonths} months"));
        }
    }
    
    /**
     * Calculate maintenance priority based on asset condition and history
     */
    private function calculateMaintenancePriority($asset, $lastMaintenance) {
        $priority = 'medium';
        
        // High priority for critical assets
        if (stripos($asset['name'], 'critical') !== false || stripos($asset['name'], 'emergency') !== false) {
            $priority = 'high';
        }
        
        // High priority if no maintenance history
        if (!$lastMaintenance) {
            $priority = 'high';
        }
        
        // Check asset age
        if (!empty($asset['purchase_date'])) {
            $assetAge = (time() - strtotime($asset['purchase_date'])) / (365.25 * 24 * 3600);
            if ($assetAge > 8) {
                $priority = 'high';
            }
        }
        
        return $priority;
    }
    
    /**
     * Get maintenance reason explanation
     */
    private function getMaintenanceReason($category, $assetAge, $lastMaintenance) {
        $reasons = [];
        
        if (!$lastMaintenance) {
            $reasons[] = 'No maintenance history found';
        }
        
        if ($assetAge > 5) {
            $reasons[] = sprintf('Asset is %.1f years old', $assetAge);
        }
        
        $categoryLower = strtolower($category);
        if (strpos($categoryLower, 'machinery') !== false || strpos($categoryLower, 'equipment') !== false) {
            $reasons[] = 'Regular maintenance required for machinery/equipment';
        }
        
        if (empty($reasons)) {
            $reasons[] = 'Scheduled preventive maintenance';
        }
        
        return implode(', ', $reasons);
    }
    
    /**
     * Validate maintenance cost against asset value and thresholds
     */
    public function validateMaintenanceCost($asset, $estimatedCost) {
        try {
            $cost = (float)$estimatedCost;
            
            // Basic cost validation
            if ($cost < 0) {
                return ['valid' => false, 'message' => 'Maintenance cost cannot be negative'];
            }
            
            if ($cost > 1000000) {
                return ['valid' => false, 'message' => 'Maintenance cost exceeds maximum allowed limit (₱1,000,000)'];
            }
            
            // Cost vs asset value validation
            if (!empty($asset['value'])) {
                $assetValue = (float)$asset['value'];
                $costPercentage = ($cost / $assetValue) * 100;
                
                // If maintenance cost is more than 50% of asset value, require justification
                if ($costPercentage > 50) {
                    return [
                        'valid' => false, 
                        'message' => sprintf(
                            'Maintenance cost (₱%s) exceeds 50%% of asset value (₱%s). Consider asset replacement.',
                            number_format($cost, 2),
                            number_format($assetValue, 2)
                        )
                    ];
                }
                
                // Warning for costs over 25% of asset value
                if ($costPercentage > 25) {
                    return [
                        'valid' => true,
                        'warning' => sprintf(
                            'High maintenance cost detected: ₱%s is %.1f%% of asset value (₱%s)',
                            number_format($cost, 2),
                            $costPercentage,
                            number_format($assetValue, 2)
                        )
                    ];
                }
            }
            
            // Age-based cost validation
            if (!empty($asset['purchase_date'])) {
                $purchaseDate = strtotime($asset['purchase_date']);
                $assetAge = (time() - $purchaseDate) / (365.25 * 24 * 3600); // Years
                
                // For assets older than 10 years, high maintenance costs may not be economical
                if ($assetAge > 10 && $cost > 50000) {
                    return [
                        'valid' => false,
                        'message' => sprintf(
                            'Asset is %.1f years old. High maintenance cost (₱%s) may not be economical. Consider replacement.',
                            $assetAge,
                            number_format($cost, 2)
                        )
                    ];
                }
            }
            
            return ['valid' => true];
            
        } catch (Exception $e) {
            error_log("Maintenance cost validation error: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Unable to validate maintenance cost'];
        }
    }
    
    /**
     * Validate MVA workflow progression
     */
    public function validateMVAWorkflow($maintenanceId, $targetStatus, $currentUserRole) {
        try {
            $maintenance = $this->find($maintenanceId);
            if (!$maintenance) {
                return ['valid' => false, 'message' => 'Maintenance record not found'];
            }

            $currentStatus = $maintenance['status'];
            
            // Define valid status transitions based on MVA workflow
            $validTransitions = [
                'Pending Verification' => [
                    'Pending Approval' => ['Project Manager', 'System Admin'], // Verifier
                    'canceled' => ['System Admin', 'Asset Director', 'Project Manager']
                ],
                'Pending Approval' => [
                    'Approved' => ['Asset Director', 'System Admin'], // Authorizer
                    'canceled' => ['System Admin', 'Asset Director']
                ],
                'Approved' => [
                    'in_progress' => ['Asset Director', 'System Admin'],
                    'canceled' => ['System Admin', 'Asset Director']
                ],
                'in_progress' => [
                    'completed' => ['Asset Director', 'System Admin'],
                    'canceled' => ['System Admin', 'Asset Director']
                ]
            ];

            // Check if transition is valid
            if (!isset($validTransitions[$currentStatus][$targetStatus])) {
                return [
                    'valid' => false, 
                    'message' => "Invalid status transition from '{$currentStatus}' to '{$targetStatus}'"
                ];
            }

            // Check if user role can perform this transition
            $allowedRoles = $validTransitions[$currentStatus][$targetStatus];
            if (!in_array($currentUserRole, $allowedRoles)) {
                return [
                    'valid' => false, 
                    'message' => "Role '{$currentUserRole}' is not authorized to change status from '{$currentStatus}' to '{$targetStatus}'"
                ];
            }

            return ['valid' => true];

        } catch (Exception $e) {
            error_log("MVA workflow validation error: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Unable to validate workflow transition'];
        }
    }

    /**
     * Check for maintenance conflicts and overlapping schedules
     */
    public function checkMaintenanceConflicts($assetId, $scheduledDate) {
        try {
            // Check for existing maintenance on the same date
            $existingMaintenance = $this->findFirst([
                'asset_id' => $assetId,
                'scheduled_date' => $scheduledDate,
                'status' => ['Pending Verification', 'Pending Approval', 'Approved', 'in_progress']
            ]);
            
            if ($existingMaintenance) {
                return [
                    'can_schedule' => false,
                    'reason' => 'Maintenance already scheduled for this asset on ' . date('M j, Y', strtotime($scheduledDate))
                ];
            }
            
            // Check for maintenance in progress
            $maintenanceInProgress = $this->findFirst([
                'asset_id' => $assetId,
                'status' => 'in_progress'
            ]);
            
            if ($maintenanceInProgress) {
                return [
                    'can_schedule' => false,
                    'reason' => 'Asset currently has maintenance in progress (ID: ' . $maintenanceInProgress['id'] . ')'
                ];
            }
            
            // Check for recent maintenance (within 7 days) for preventive maintenance
            $scheduledDateTime = strtotime($scheduledDate);
            $recentMaintenance = $this->findFirst([
                'asset_id' => $assetId,
                'status' => 'completed'
            ], 'completed_date DESC');
            
            if ($recentMaintenance && $recentMaintenance['completed_date']) {
                $lastMaintenanceTime = strtotime($recentMaintenance['completed_date']);
                $daysDifference = ($scheduledDateTime - $lastMaintenanceTime) / (60 * 60 * 24);
                
                if ($daysDifference < 7) {
                    return [
                        'can_schedule' => false,
                        'reason' => 'Asset had maintenance completed recently on ' . date('M j, Y', $lastMaintenanceTime) . '. Wait at least 7 days between maintenance.'
                    ];
                }
            }
            
            return ['can_schedule' => true];
            
        } catch (Exception $e) {
            error_log("Maintenance conflict check error: " . $e->getMessage());
            return [
                'can_schedule' => false,
                'reason' => 'Unable to verify maintenance conflicts. Please try again.'
            ];
        }
    }
}
?>
