<?php
/**
 * ConstructLink™ Request Model - Unified Request Management
 * Handles unified request operations for materials, tools, equipment, services, petty cash, and others
 */

class RequestModel extends BaseModel {
    protected $table = 'requests';
    protected $fillable = [
        'project_id', 'request_type', 'category', 'description', 'quantity', 'unit',
        'urgency', 'date_needed', 'requested_by', 'reviewed_by', 'approved_by',
        'remarks', 'estimated_cost', 'actual_cost', 'procurement_id', 'status'
    ];
    
    /**
     * Create a new request
     */
    public function createRequest($data) {
        try {
            $validation = $this->validate($data, [
                'project_id' => 'required|integer',
                'request_type' => 'required|in:Material,Tool,Equipment,Service,Petty Cash,Other',
                'description' => 'required|max:1000',
                'requested_by' => 'required|integer'
            ]);
            
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            $this->beginTransaction();
            
            // Set default status
            $data['status'] = 'Draft';
            
            // Validate date_needed if provided
            if (!empty($data['date_needed'])) {
                if (strtotime($data['date_needed']) <= time()) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Date needed must be in the future'];
                }
            }
            
            // Create request record
            $request = $this->create($data);
            
            if (!$request) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to create request'];
            }
            
            // Log the request creation
            $this->logRequestActivity($request['id'], 'request_created', null, 'Draft', 'Request created');
            
            $this->commit();
            
            return ['success' => true, 'request' => $request];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Request creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create request'];
        }
    }
    
    /**
     * Submit request (change from Draft to Submitted)
     */
    public function submitRequest($requestId, $userId) {
        try {
            $request = $this->find($requestId);
            if (!$request) {
                return ['success' => false, 'message' => 'Request not found'];
            }
            
            if ($request['status'] !== 'Draft') {
                return ['success' => false, 'message' => 'Only draft requests can be submitted'];
            }
            
            $this->beginTransaction();
            
            // Update status to Submitted
            $updateData = [
                'status' => 'Submitted',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->update($requestId, $updateData);
            
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to submit request'];
            }
            
            // Log the submission
            $this->logRequestActivity($requestId, 'request_submitted', 'Draft', 'Submitted', 'Request submitted for review', $userId);
            
            $this->commit();
            
            return ['success' => true, 'request' => $result, 'message' => 'Request submitted successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Request submission error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit request: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update request status
     */
    public function updateRequestStatus($requestId, $newStatus, $userId, $remarks = null) {
        try {
            $request = $this->find($requestId);
            if (!$request) {
                return ['success' => false, 'message' => 'Request not found'];
            }
            
            $oldStatus = $request['status'];
            
            $this->beginTransaction();
            
            $updateData = ['status' => $newStatus];
            
            // Set appropriate user fields based on status
            switch ($newStatus) {
                case 'Reviewed':
                    $updateData['reviewed_by'] = $userId;
                    break;
                case 'Approved':
                case 'Declined':
                    $updateData['approved_by'] = $userId;
                    break;
            }
            
            if ($remarks) {
                $updateData['remarks'] = $remarks;
            }
            
            $result = $this->update($requestId, $updateData);
            
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update request status'];
            }
            
            // Log the status change
            $this->logRequestActivity($requestId, 'status_changed', $oldStatus, $newStatus, $remarks, $userId);
            
            $this->commit();
            
            return ['success' => true, 'request' => $result];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Request status update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update request status'];
        }
    }
    
    /**
     * Get request with detailed information
     */
    public function getRequestWithDetails($id) {
        try {
            $sql = "
                SELECT r.*, 
                       p.name as project_name, p.code as project_code,
                       p.project_manager_id,
                       u1.full_name as requested_by_name,
                       u2.full_name as reviewed_by_name,
                       u3.full_name as approved_by_name,
                       proc.po_number, proc.status as procurement_status
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                LEFT JOIN users u2 ON r.reviewed_by = u2.id
                LEFT JOIN users u3 ON r.approved_by = u3.id
                LEFT JOIN procurement_orders proc ON r.procurement_id = proc.id
                WHERE r.id = ?
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Get request with details error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get requests with filters and pagination
     */
    public function getRequestsWithFilters($filters = [], $page = 1, $perPage = 20) {
        try {
            $conditions = [];
            $params = [];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $conditions[] = "r.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['request_type'])) {
                $conditions[] = "r.request_type = ?";
                $params[] = $filters['request_type'];
            }
            
            if (!empty($filters['project_id'])) {
                $conditions[] = "r.project_id = ?";
                $params[] = $filters['project_id'];
            }
            
            if (!empty($filters['urgency'])) {
                $conditions[] = "r.urgency = ?";
                $params[] = $filters['urgency'];
            }
            
            if (!empty($filters['requested_by'])) {
                $conditions[] = "r.requested_by = ?";
                $params[] = $filters['requested_by'];
            }
            
            if (!empty($filters['date_from'])) {
                $conditions[] = "DATE(r.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions[] = "DATE(r.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['search'])) {
                $conditions[] = "(r.description LIKE ? OR p.name LIKE ? OR u1.full_name LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            // Count total records
            $countSql = "
                SELECT COUNT(*) 
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();
            
            // Get paginated data
            $offset = ($page - 1) * $perPage;
            $orderBy = $filters['order_by'] ?? 'r.created_at DESC';
            
            $dataSql = "
                SELECT r.*, 
                       p.name as project_name, p.code as project_code,
                       u1.full_name as requested_by_name,
                       u2.full_name as reviewed_by_name,
                       u3.full_name as approved_by_name
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                LEFT JOIN users u2 ON r.reviewed_by = u2.id
                LEFT JOIN users u3 ON r.approved_by = u3.id
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
            
        } catch (Exception $e) {
            error_log("Get requests with filters error: " . $e->getMessage());
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
     * Get request statistics
     */
    public function getRequestStatistics($projectId = null, $dateFrom = null, $dateTo = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($projectId) {
                $conditions[] = "project_id = ?";
                $params[] = $projectId;
            }
            
            if ($dateFrom) {
                $conditions[] = "DATE(created_at) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $conditions[] = "DATE(created_at) <= ?";
                $params[] = $dateTo;
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = 'Submitted' THEN 1 ELSE 0 END) as submitted,
                    SUM(CASE WHEN status = 'Reviewed' THEN 1 ELSE 0 END) as reviewed,
                    SUM(CASE WHEN status = 'Forwarded' THEN 1 ELSE 0 END) as forwarded,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Declined' THEN 1 ELSE 0 END) as declined,
                    SUM(CASE WHEN status = 'Procured' THEN 1 ELSE 0 END) as procured,
                    SUM(CASE WHEN urgency = 'Critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN urgency = 'Urgent' THEN 1 ELSE 0 END) as urgent
                FROM requests 
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result ?: [
                'total_requests' => 0,
                'draft' => 0,
                'submitted' => 0,
                'reviewed' => 0,
                'forwarded' => 0,
                'approved' => 0,
                'declined' => 0,
                'procured' => 0,
                'critical' => 0,
                'urgent' => 0
            ];
            
        } catch (Exception $e) {
            error_log("Get request statistics error: " . $e->getMessage());
            return [
                'total_requests' => 0,
                'draft' => 0,
                'submitted' => 0,
                'reviewed' => 0,
                'forwarded' => 0,
                'approved' => 0,
                'declined' => 0,
                'procured' => 0,
                'critical' => 0,
                'urgent' => 0
            ];
        }
    }
    
    /**
     * Get pending requests for approval
     */
    public function getPendingRequests($userId = null, $userRole = null) {
        try {
            $conditions = ["r.status IN ('Submitted', 'Reviewed', 'Forwarded')"];
            $params = [];
            
            // Role-based filtering
            if ($userRole && $userRole !== 'System Admin') {
                switch ($userRole) {
                    case 'Asset Director':
                        // Can review all requests
                        break;
                    case 'Finance Director':
                        // Can approve high-value or specific types
                        $conditions[] = "(r.estimated_cost > 50000 OR r.request_type IN ('Petty Cash', 'Service'))";
                        break;
                    case 'Procurement Officer':
                        // Can approve procurement-related requests
                        $conditions[] = "r.request_type IN ('Material', 'Tool', 'Equipment')";
                        break;
                    case 'Project Manager':
                        // Can only see own project requests
                        if ($userId) {
                            $conditions[] = "p.manager_id = ?";
                            $params[] = $userId;
                        }
                        break;
                }
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT r.*, 
                       p.name as project_name, p.code as project_code,
                       u1.full_name as requested_by_name
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                {$whereClause}
                ORDER BY 
                    CASE r.urgency 
                        WHEN 'Critical' THEN 1 
                        WHEN 'Urgent' THEN 2 
                        ELSE 3 
                    END,
                    r.created_at ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get pending requests error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Link request to procurement order
     */
    public function linkToProcurementOrder($requestId, $procurementOrderId) {
        try {
            // Check if request exists and is approved
            $request = $this->find($requestId);
            if (!$request) {
                return ['success' => false, 'message' => 'Request not found'];
            }
            
            if ($request['status'] !== 'Approved') {
                return ['success' => false, 'message' => 'Only approved requests can be linked to procurement orders'];
            }
            
            if ($request['procurement_id']) {
                return ['success' => false, 'message' => 'Request is already linked to a procurement order'];
            }
            
            // Update request with procurement order link
            $result = $this->update($requestId, [
                'procurement_id' => $procurementOrderId,
                'status' => 'Procured'
            ]);
            
            if (!$result) {
                return ['success' => false, 'message' => 'Failed to link request to procurement order'];
            }
            
            // Log the linking activity (outside of transaction since parent method handles transaction)
            try {
                $this->logRequestActivity($requestId, 'linked_to_procurement_order', 'Approved', 'Procured', 
                    "Linked to procurement order ID: {$procurementOrderId}");
            } catch (Exception $logError) {
                // Log error but don't fail the whole process
                error_log("Failed to log request activity: " . $logError->getMessage());
            }
            
            return ['success' => true, 'message' => 'Request successfully linked to procurement order'];
            
        } catch (Exception $e) {
            error_log("Link to procurement order error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to link request to procurement order'];
        }
    }
    
    /**
     * Get approved requests not yet procured (for procurement officers)
     */
    public function getApprovedRequestsForProcurement($projectId = null) {
        try {
            $conditions = ["r.status = 'Approved'", "r.procurement_id IS NULL"];
            $params = [];
            
            if ($projectId) {
                $conditions[] = "r.project_id = ?";
                $params[] = $projectId;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT r.*, 
                       p.name as project_name, p.code as project_code,
                       u1.full_name as requested_by_name,
                       u2.full_name as approved_by_name,
                       DATEDIFF(CURDATE(), r.created_at) as days_since_approval
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                LEFT JOIN users u2 ON r.approved_by = u2.id
                {$whereClause}
                ORDER BY 
                    CASE r.urgency 
                        WHEN 'Critical' THEN 1 
                        WHEN 'Urgent' THEN 2 
                        ELSE 3 
                    END,
                    r.date_needed ASC,
                    r.created_at ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get approved requests for procurement error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if request can be procured (prevent duplicate procurement)
     */
    public function canBeProcured($requestId) {
        try {
            $request = $this->find($requestId);
            
            if (!$request) {
                return ['can_procure' => false, 'reason' => 'Request not found'];
            }
            
            if ($request['status'] !== 'Approved') {
                return ['can_procure' => false, 'reason' => 'Request is not approved'];
            }
            
            if ($request['procurement_id']) {
                return ['can_procure' => false, 'reason' => 'Request is already linked to a procurement order'];
            }
            
            return ['can_procure' => true, 'request' => $request];
            
        } catch (Exception $e) {
            error_log("Check if request can be procured error: " . $e->getMessage());
            return ['can_procure' => false, 'reason' => 'System error'];
        }
    }
    
    /**
     * Get requests by procurement officer (role-based filtering)
     */
    public function getRequestsForProcurementOfficer($userId = null, $filters = []) {
        try {
            $conditions = ["r.status IN ('Approved')"];
            $params = [];
            
            // Apply additional filters
            if (!empty($filters['project_id'])) {
                $conditions[] = "r.project_id = ?";
                $params[] = $filters['project_id'];
            }
            
            if (!empty($filters['request_type'])) {
                $conditions[] = "r.request_type = ?";
                $params[] = $filters['request_type'];
            }
            
            if (!empty($filters['urgency'])) {
                $conditions[] = "r.urgency = ?";
                $params[] = $filters['urgency'];
            }
            
            if (isset($filters['not_procured']) && $filters['not_procured']) {
                $conditions[] = "r.procurement_id IS NULL";
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT r.*, 
                       p.name as project_name, p.code as project_code,
                       u1.full_name as requested_by_name,
                       u2.full_name as approved_by_name,
                       po.po_number, po.status as procurement_status
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                LEFT JOIN users u2 ON r.approved_by = u2.id
                LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                {$whereClause}
                ORDER BY 
                    CASE r.urgency 
                        WHEN 'Critical' THEN 1 
                        WHEN 'Urgent' THEN 2 
                        ELSE 3 
                    END,
                    r.date_needed ASC,
                    r.created_at ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get requests for procurement officer error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get requests by type for reporting
     */
    public function getRequestsByType($dateFrom = null, $dateTo = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($dateFrom) {
                $conditions[] = "DATE(created_at) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $conditions[] = "DATE(created_at) <= ?";
                $params[] = $dateTo;
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT 
                    request_type,
                    COUNT(*) as total_count,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = 'Declined' THEN 1 ELSE 0 END) as declined_count,
                    SUM(CASE WHEN status = 'Procured' THEN 1 ELSE 0 END) as procured_count,
                    AVG(CASE WHEN estimated_cost IS NOT NULL THEN estimated_cost ELSE 0 END) as avg_estimated_cost
                FROM requests 
                {$whereClause}
                GROUP BY request_type
                ORDER BY total_count DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get requests by type error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get request with procurement and delivery status
     */
    public function getRequestWithDeliveryStatus($id) {
        try {
            $sql = "
                SELECT r.*, 
                       p.name as project_name, p.code as project_code,
                       u1.full_name as requested_by_name,
                       u2.full_name as reviewed_by_name,
                       u3.full_name as approved_by_name,
                       po.id as procurement_order_id,
                       po.po_number, 
                       po.status as procurement_status,
                       po.delivery_status,
                       po.delivery_method,
                       po.tracking_number,
                       po.scheduled_delivery_date,
                       po.actual_delivery_date,
                       po.delivery_location,
                       po.delivery_notes,
                       po.delivery_discrepancy_notes,
                       po.net_total as procurement_total,
                       v.name as vendor_name,
                       v.contact_person as vendor_contact,
                       CASE 
                           WHEN po.delivery_status = 'Received' THEN 'Completed'
                           WHEN po.delivery_status IN ('Delivered', 'In Transit') THEN 'In Progress'
                           WHEN po.delivery_status = 'Scheduled' THEN 'Scheduled'
                           WHEN po.status = 'Approved' THEN 'Ready for Delivery'
                           WHEN po.status IN ('Pending', 'Reviewed') THEN 'Processing'
                           WHEN r.status = 'Approved' AND po.id IS NULL THEN 'Awaiting Procurement'
                           ELSE 'Not Started'
                       END as overall_delivery_status
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                LEFT JOIN users u2 ON r.reviewed_by = u2.id
                LEFT JOIN users u3 ON r.approved_by = u3.id
                LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                LEFT JOIN vendors v ON po.vendor_id = v.id
                WHERE r.id = ?
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Get request with delivery status error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get requests with delivery tracking for stakeholder dashboard
     */
    public function getRequestsWithDeliveryTracking($filters = [], $userRole = null, $userId = null) {
        try {
            $conditions = [];
            $params = [];
            
            // Role-based filtering
            if ($userRole && $userRole !== 'System Admin') {
                switch ($userRole) {
                    case 'Finance Director':
                        // $conditions[] = "(r.estimated_cost > 50000 OR po.net_total > 50000)";
                        break;
                    case 'Asset Director':
                        // Can see all requests
                        break;
                    case 'Procurement Officer':
                        $conditions[] = "r.status IN ('Approved', 'Procured') OR po.id IS NOT NULL";
                        break;
                    case 'Warehouseman':
                        $conditions[] = "po.delivery_status IN ('Scheduled', 'In Transit', 'Delivered')";
                        break;
                    case 'Project Manager':
                        if ($userId) {
                            $conditions[] = "(p.project_manager_id = ? OR r.requested_by = ?)";
                            $params = array_merge($params, [$userId, $userId]);
                        }
                        break;
                    case 'Site Inventory Clerk':
                        if ($userId) {
                            $conditions[] = "r.requested_by = ?";
                            $params[] = $userId;
                        }
                        break;
                }
            }
            
            // Apply additional filters
            if (!empty($filters['status'])) {
                $conditions[] = "r.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['delivery_status'])) {
                $conditions[] = "po.delivery_status = ?";
                $params[] = $filters['delivery_status'];
            }
            
            if (!empty($filters['project_id'])) {
                $conditions[] = "r.project_id = ?";
                $params[] = $filters['project_id'];
            }
            
            // Handle project_ids array for Project Managers
            if (!empty($filters['project_ids']) && is_array($filters['project_ids'])) {
                $placeholders = str_repeat('?,', count($filters['project_ids']) - 1) . '?';
                $conditions[] = "r.project_id IN ($placeholders)";
                $params = array_merge($params, $filters['project_ids']);
            }
            
            if (!empty($filters['requested_by'])) {
                $conditions[] = "r.requested_by = ?";
                $params[] = $filters['requested_by'];
            }
            
            if (!empty($filters['procurement_status'])) {
                $conditions[] = "po.status = ?";
                $params[] = $filters['procurement_status'];
            }
            
            if (!empty($filters['urgency'])) {
                $conditions[] = "r.urgency = ?";
                $params[] = $filters['urgency'];
            }
            
            if (!empty($filters['overdue_delivery'])) {
                $conditions[] = "po.scheduled_delivery_date < CURDATE() AND po.delivery_status NOT IN ('Delivered', 'Received')";
            }
            
            if (!empty($filters['has_discrepancy'])) {
                $conditions[] = "po.delivery_discrepancy_notes IS NOT NULL AND po.delivery_discrepancy_notes != ''";
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT r.*, 
                       p.name as project_name, p.code as project_code,
                       p.project_manager_id,
                       u1.full_name as requested_by_name,
                       po.id as procurement_order_id,
                       po.po_number, 
                       po.status as procurement_status,
                       po.delivery_status,
                       po.delivery_method,
                       po.tracking_number,
                       po.scheduled_delivery_date,
                       po.actual_delivery_date,
                       po.delivery_location,
                       po.delivery_discrepancy_notes,
                       po.net_total as procurement_total,
                       v.name as vendor_name,
                       CASE 
                           WHEN po.delivery_status = 'Received' THEN 'Completed'
                           WHEN po.delivery_status IN ('Delivered', 'In Transit') THEN 'In Progress'
                           WHEN po.delivery_status = 'Scheduled' THEN 'Scheduled'
                           WHEN po.status = 'Approved' THEN 'Ready for Delivery'
                           WHEN po.status IN ('Pending', 'Reviewed') THEN 'Processing'
                           WHEN r.status = 'Approved' AND po.id IS NULL THEN 'Awaiting Procurement'
                           ELSE 'Not Started'
                       END as overall_delivery_status,
                       CASE 
                           WHEN po.scheduled_delivery_date < CURDATE() AND po.delivery_status NOT IN ('Delivered', 'Received') THEN 1
                           ELSE 0
                       END as is_overdue,
                       CASE 
                           WHEN po.delivery_discrepancy_notes IS NOT NULL AND po.delivery_discrepancy_notes != '' THEN 1
                           ELSE 0
                       END as has_discrepancy
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                LEFT JOIN vendors v ON po.vendor_id = v.id
                {$whereClause}
                ORDER BY 
                    CASE r.urgency 
                        WHEN 'Critical' THEN 1 
                        WHEN 'Urgent' THEN 2 
                        ELSE 3 
                    END,
                    CASE 
                        WHEN po.scheduled_delivery_date < CURDATE() AND po.delivery_status NOT IN ('Delivered', 'Received') THEN 1
                        ELSE 2
                    END,
                    r.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get requests with delivery tracking error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get delivery alerts for stakeholders
     */
    public function getDeliveryAlerts($userRole = null, $userId = null) {
        try {
            $conditions = [];
            $params = [];
            $alerts = [];
            
            // Role-based alert filtering
            if ($userRole && $userRole !== 'System Admin') {
                switch ($userRole) {
                    case 'Procurement Officer':
                        $conditions[] = "po.status = 'Approved' AND (po.delivery_status IS NULL OR po.delivery_status = 'Pending')";
                        break;
                    case 'Warehouseman':
                        $conditions[] = "po.delivery_status IN ('Scheduled', 'In Transit', 'Delivered')";
                        break;
                    case 'Project Manager':
                    case 'Site Inventory Clerk':
                        if ($userId) {
                            $conditions[] = "(p.project_manager_id = ? OR r.requested_by = ?)";
                            $params = array_merge($params, [$userId, $userId]);
                        }
                        break;
                    case 'Finance Director':
                    case 'Asset Director':
                        // Can see all alerts
                        break;
                    default:
                        return []; // No alerts for other roles
                }
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            // Overdue deliveries
            $sql = "
                SELECT 'overdue_delivery' as alert_type,
                       COUNT(*) as count,
                       'Overdue Deliveries' as title,
                       'danger' as severity
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                WHERE po.scheduled_delivery_date < CURDATE() 
                  AND po.delivery_status NOT IN ('Delivered', 'Received')
                  " . ($whereClause ? "AND " . str_replace("WHERE ", "", $whereClause) : "") . "
                HAVING count > 0
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $overdueAlert = $stmt->fetch();
            if ($overdueAlert && $overdueAlert['count'] > 0) {
                $alerts[] = $overdueAlert;
            }
            
            // Delivery discrepancies
            $sql = "
                SELECT 'delivery_discrepancy' as alert_type,
                       COUNT(*) as count,
                       'Delivery Discrepancies' as title,
                       'warning' as severity
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                WHERE po.delivery_discrepancy_notes IS NOT NULL 
                  AND po.delivery_discrepancy_notes != ''
                  AND po.delivery_status != 'Received'
                  " . ($whereClause ? "AND " . str_replace("WHERE ", "", $whereClause) : "") . "
                HAVING count > 0
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $discrepancyAlert = $stmt->fetch();
            if ($discrepancyAlert && $discrepancyAlert['count'] > 0) {
                $alerts[] = $discrepancyAlert;
            }
            
            // Ready for delivery scheduling
            if (in_array($userRole, ['System Admin', 'Procurement Officer', 'Asset Director'])) {
                $sql = "
                    SELECT 'ready_for_delivery' as alert_type,
                           COUNT(*) as count,
                           'Ready for Delivery Scheduling' as title,
                           'info' as severity
                    FROM requests r
                    LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                    WHERE po.status = 'Approved' 
                      AND (po.delivery_status IS NULL OR po.delivery_status = 'Pending')
                    HAVING count > 0
                ";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $readyAlert = $stmt->fetch();
                if ($readyAlert && $readyAlert['count'] > 0) {
                    $alerts[] = $readyAlert;
                }
            }
            
            // Awaiting receipt confirmation
            if (in_array($userRole, ['System Admin', 'Warehouseman', 'Asset Director'])) {
                $sql = "
                    SELECT 'awaiting_receipt' as alert_type,
                           COUNT(*) as count,
                           'Awaiting Receipt Confirmation' as title,
                           'success' as severity
                    FROM requests r
                    LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                    WHERE po.delivery_status = 'Delivered'
                      AND po.status != 'Received'
                    HAVING count > 0
                ";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $receiptAlert = $stmt->fetch();
                if ($receiptAlert && $receiptAlert['count'] > 0) {
                    $alerts[] = $receiptAlert;
                }
            }
            
            return $alerts;
            
        } catch (Exception $e) {
            error_log("Get delivery alerts error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get delivery statistics for dashboard
     */
    public function getDeliveryStatistics($projectId = null, $dateFrom = null, $dateTo = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($projectId) {
                $conditions[] = "r.project_id = ?";
                $params[] = $projectId;
            }
            
            if ($dateFrom) {
                $conditions[] = "DATE(r.created_at) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $conditions[] = "DATE(r.created_at) <= ?";
                $params[] = $dateTo;
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT 
                    COUNT(DISTINCT r.id) as total_requests_with_procurement,
                    COUNT(DISTINCT CASE WHEN po.delivery_status = 'Pending' THEN po.id END) as pending_delivery,
                    COUNT(DISTINCT CASE WHEN po.delivery_status = 'Scheduled' THEN po.id END) as scheduled_delivery,
                    COUNT(DISTINCT CASE WHEN po.delivery_status = 'In Transit' THEN po.id END) as in_transit,
                    COUNT(DISTINCT CASE WHEN po.delivery_status = 'Delivered' THEN po.id END) as delivered,
                    COUNT(DISTINCT CASE WHEN po.delivery_status = 'Received' THEN po.id END) as received,
                    COUNT(DISTINCT CASE WHEN po.delivery_status = 'Partial' THEN po.id END) as partial_delivery,
                    COUNT(DISTINCT CASE WHEN po.delivery_discrepancy_notes IS NOT NULL AND po.delivery_discrepancy_notes != '' THEN po.id END) as with_discrepancies,
                    COUNT(DISTINCT CASE WHEN po.scheduled_delivery_date < CURDATE() AND po.delivery_status NOT IN ('Delivered', 'Received') THEN po.id END) as overdue_deliveries,
                    AVG(CASE WHEN po.actual_delivery_date IS NOT NULL AND po.scheduled_delivery_date IS NOT NULL 
                        THEN DATEDIFF(po.actual_delivery_date, po.scheduled_delivery_date) ELSE NULL END) as avg_delivery_delay_days
                FROM requests r
                LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                {$whereClause}
                  AND po.id IS NOT NULL
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result ?: [
                'total_requests_with_procurement' => 0,
                'pending_delivery' => 0,
                'scheduled_delivery' => 0,
                'in_transit' => 0,
                'delivered' => 0,
                'received' => 0,
                'partial_delivery' => 0,
                'with_discrepancies' => 0,
                'overdue_deliveries' => 0,
                'avg_delivery_delay_days' => 0
            ];
            
        } catch (Exception $e) {
            error_log("Get delivery statistics error: " . $e->getMessage());
            return [
                'total_requests_with_procurement' => 0,
                'pending_delivery' => 0,
                'scheduled_delivery' => 0,
                'in_transit' => 0,
                'delivered' => 0,
                'received' => 0,
                'partial_delivery' => 0,
                'with_discrepancies' => 0,
                'overdue_deliveries' => 0,
                'avg_delivery_delay_days' => 0
            ];
        }
    }
    
    /**
     * Log request activity
     */
    private function logRequestActivity($requestId, $action, $oldStatus, $newStatus, $remarks = null, $userId = null) {
        try {
            if (!$userId) {
                $auth = Auth::getInstance();
                $user = $auth->getCurrentUser();
                $userId = $user['id'] ?? null;
            }
            
            $sql = "INSERT INTO request_logs (request_id, user_id, action, old_status, new_status, remarks) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$requestId, $userId, $action, $oldStatus, $newStatus, $remarks]);
            
        } catch (Exception $e) {
            error_log("Request activity logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Get request activity logs
     */
    public function getRequestLogs($requestId) {
        try {
            $sql = "
                SELECT rl.*, u.full_name as user_name
                FROM request_logs rl
                LEFT JOIN users u ON rl.user_id = u.id
                WHERE rl.request_id = ?
                ORDER BY rl.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$requestId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get request logs error: " . $e->getMessage());
            return [];
        }
    }
}
?>
