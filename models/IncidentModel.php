<?php
/**
 * ConstructLink™ Incident Model
 * Handles incident reporting and tracking for lost, damaged, or stolen assets
 */

class IncidentModel extends BaseModel {
    protected $table = 'incidents';
    protected $fillable = [
        'asset_id', 'borrowed_tool_id', 'reported_by', 'type', 'severity', 'description', 'location',
        'witnesses', 'date_reported', 'status', 'resolution_notes', 'resolved_by', 'resolution_date',
        // MVA workflow fields
        'verified_by', 'verification_date', 'authorized_by', 'authorization_date',
        'closed_by', 'closure_date', 'closure_notes', 'canceled_by', 'cancellation_date', 'cancellation_reason'
    ];
    
    /**
     * Create incident report (Maker step)
     */
    public function createIncident($data) {
        // Manual validation for better control
        $errors = [];
        
        // Required fields
        if (empty($data['asset_id'])) {
            $errors[] = 'Asset is required';
        }
        
        if (empty($data['reported_by'])) {
            $errors[] = 'Reporter is required';
        }
        
        if (empty($data['description'])) {
            $errors[] = 'Description is required';
        }
        
        if (empty($data['date_reported'])) {
            $errors[] = 'Date reported is required';
        }
        
        // Validate type
        $validTypes = ['lost', 'damaged', 'stolen', 'other'];
        if (!empty($data['type']) && !in_array($data['type'], $validTypes)) {
            $errors[] = 'Invalid incident type';
        }
        
        // Validate severity
        $validSeverities = ['low', 'medium', 'high', 'critical'];
        if (!empty($data['severity']) && !in_array($data['severity'], $validSeverities)) {
            $errors[] = 'Invalid severity level';
        }
        
        // Validate location length
        if (!empty($data['location']) && strlen($data['location']) > 200) {
            $errors[] = 'Location must not exceed 200 characters';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $this->beginTransaction();
            
            // Check if asset exists
            $assetModel = new AssetModel();
            $asset = $assetModel->find($data['asset_id']);
            
            if (!$asset) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            // Set initial status for MVA workflow
            $incidentData = [
                'asset_id' => (int)$data['asset_id'],
                'borrowed_tool_id' => !empty($data['borrowed_tool_id']) ? (int)$data['borrowed_tool_id'] : null,
                'reported_by' => (int)$data['reported_by'],
                'type' => $data['type'],
                'severity' => $data['severity'] ?? 'medium',
                'description' => Validator::sanitize($data['description']),
                'location' => Validator::sanitize($data['location'] ?? ''),
                'witnesses' => Validator::sanitize($data['witnesses'] ?? ''),
                'date_reported' => $data['date_reported'],
                'status' => 'Pending Verification'
            ];
            
            // Create incident record
            $incident = $this->create($incidentData);
            
            if (!$incident) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to create incident report'];
            }
            
            // For critical incidents (lost/stolen), update asset status immediately
            if (in_array($data['type'], ['lost', 'stolen']) || $data['severity'] === 'critical') {
                $assetModel->update($data['asset_id'], ['status' => 'under_maintenance']);
                logActivity('asset_status_changed', "Asset status changed to under_maintenance due to {$data['type']} incident", 'assets', $data['asset_id']);
            }
            
            // Log activity
            logActivity('incident_created', "Incident reported for asset {$asset['ref']}", 'incidents', $incident['id']);
            
            $this->commit();
            
            return ['success' => true, 'incident' => $incident];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Incident creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create incident report'];
        }
    }

    /**
     * Verify incident (Verifier step)
     */
    public function verifyIncident($incidentId, $verifiedBy, $notes = null) {
        try {
            $this->beginTransaction();
            
            $incident = $this->find($incidentId);
            if (!$incident) {
                $this->rollback();
                return ['success' => false, 'message' => 'Incident not found'];
            }
            
            if ($incident['status'] !== 'Pending Verification') {
                $this->rollback();
                return ['success' => false, 'message' => 'Incident is not in pending verification status'];
            }
            
            $updateData = [
                'status' => 'Pending Authorization',
                'verified_by' => $verifiedBy,
                'verification_date' => date('Y-m-d H:i:s'),
                'resolution_notes' => $notes
            ];
            
            $result = $this->update($incidentId, $updateData);
            
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to verify incident'];
            }
            
            // Log activity
            logActivity('incident_verified', "Incident verified", 'incidents', $incidentId);
            
            $this->commit();
            
            return ['success' => true, 'incident' => $result, 'message' => 'Incident verified successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Incident verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to verify incident'];
        }
    }

    /**
     * Authorize incident (Authorizer step)
     */
    public function authorizeIncident($incidentId, $authorizedBy, $notes = null) {
        try {
            $this->beginTransaction();
            
            $incident = $this->find($incidentId);
            if (!$incident) {
                $this->rollback();
                return ['success' => false, 'message' => 'Incident not found'];
            }
            
            if ($incident['status'] !== 'Pending Authorization') {
                $this->rollback();
                return ['success' => false, 'message' => 'Incident is not in pending authorization status'];
            }
            
            $updateData = [
                'status' => 'Authorized',
                'authorized_by' => $authorizedBy,
                'authorization_date' => date('Y-m-d H:i:s'),
                'resolution_notes' => $notes
            ];
            
            $result = $this->update($incidentId, $updateData);
            
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to authorize incident'];
            }
            
            // Log activity
            logActivity('incident_authorized', "Incident authorized", 'incidents', $incidentId);
            
            $this->commit();
            
            return ['success' => true, 'incident' => $result, 'message' => 'Incident authorized successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Incident authorization error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to authorize incident'];
        }
    }

    /**
     * Resolve incident (after authorization)
     */
    public function resolveIncident($incidentId, $resolvedBy, $resolution, $notes = null) {
        try {
            $this->beginTransaction();
            
            $incident = $this->getIncidentWithDetails($incidentId);
            if (!$incident) {
                $this->rollback();
                return ['success' => false, 'message' => 'Incident not found'];
            }
            
            if (!in_array($incident['status'], ['Authorized', 'Pending Authorization'])) {
                $this->rollback();
                return ['success' => false, 'message' => 'Incident must be authorized before resolution'];
            }
            
            $updateData = [
                'status' => 'Resolved',
                'resolution_notes' => Validator::sanitize($resolution),
                'resolved_by' => $resolvedBy,
                'resolution_date' => date('Y-m-d H:i:s')
            ];
            
            if ($notes) {
                $updateData['resolution_notes'] .= "\n\nAdditional Notes: " . Validator::sanitize($notes);
            }
            
            $result = $this->update($incidentId, $updateData);
            
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to resolve incident'];
            }
            
            // Update asset status based on resolution
            $assetModel = new AssetModel();
            
            // If asset was lost/stolen and not recovered, mark as retired
            if (in_array($incident['type'], ['lost', 'stolen']) && strpos(strtolower($resolution), 'recovered') === false) {
                $assetModel->update($incident['asset_id'], ['status' => 'retired']);
                logActivity('asset_status_changed', "Asset marked as retired due to {$incident['type']} incident", 'assets', $incident['asset_id']);
            } else {
                // Return to available status if recovered or repaired
                $assetModel->update($incident['asset_id'], ['status' => 'available']);
                logActivity('asset_status_changed', "Asset returned to available status after incident resolution", 'assets', $incident['asset_id']);
            }
            
            // Log activity
            logActivity('incident_resolved', "Incident resolved", 'incidents', $incidentId);
            
            $this->commit();
            
            return ['success' => true, 'incident' => $result, 'message' => 'Incident resolved successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Incident resolution error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to resolve incident'];
        }
    }

    /**
     * Close incident (final step)
     */
    public function closeIncident($incidentId, $closedBy, $closureNotes = null) {
        try {
            $this->beginTransaction();
            
            $incident = $this->find($incidentId);
            if (!$incident) {
                $this->rollback();
                return ['success' => false, 'message' => 'Incident not found'];
            }
            
            if ($incident['status'] !== 'Resolved') {
                $this->rollback();
                return ['success' => false, 'message' => 'Incident must be resolved before closing'];
            }
            
            $updateData = [
                'status' => 'Closed',
                'closed_by' => $closedBy,
                'closure_date' => date('Y-m-d H:i:s')
            ];
            
            if ($closureNotes) {
                $currentNotes = $incident['resolution_notes'] ?? '';
                $updateData['closure_notes'] = Validator::sanitize($closureNotes);
                $updateData['resolution_notes'] = $currentNotes . "\n\nClosure Notes: " . Validator::sanitize($closureNotes);
            }
            
            $result = $this->update($incidentId, $updateData);
            
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to close incident'];
            }
            
            // Log activity
            logActivity('incident_closed', "Incident closed", 'incidents', $incidentId);
            
            $this->commit();
            
            return ['success' => true, 'incident' => $result, 'message' => 'Incident closed successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Incident closure error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to close incident'];
        }
    }

    /**
     * Cancel incident (any stage before Resolved)
     */
    public function cancelIncident($incidentId, $canceledBy, $reason = null) {
        try {
            $this->beginTransaction();
            
            $incident = $this->find($incidentId);
            if (!$incident) {
                $this->rollback();
                return ['success' => false, 'message' => 'Incident not found'];
            }
            
            if (!in_array($incident['status'], ['Pending Verification', 'Pending Authorization', 'Authorized'])) {
                $this->rollback();
                return ['success' => false, 'message' => 'Cannot cancel at this stage'];
            }
            
            $updateData = [
                'status' => 'Canceled',
                'resolution_notes' => $reason
            ];
            
            $result = $this->update($incidentId, $updateData);
            
            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to cancel incident'];
            }
            
            // Log activity
            logActivity('incident_canceled', "Incident canceled", 'incidents', $incidentId);
            
            $this->commit();
            
            return ['success' => true, 'incident' => $result, 'message' => 'Incident canceled successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Incident cancellation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to cancel incident'];
        }
    }
    

    

    
    /**
     * Get incident with detailed information
     */
    public function getIncidentWithDetails($id) {
        $sql = "
            SELECT i.*, 
                   a.ref as asset_ref, a.name as asset_name, a.status as asset_status,
                   c.name as category_name,
                   p.name as project_name, p.location as project_location,
                   ur.full_name as reported_by_name,
                   ures.full_name as resolved_by_name,
                   uv.full_name as verified_by_name,
                   ua.full_name as authorized_by_name,
                   uc.full_name as closed_by_name,
                   ucan.full_name as canceled_by_name
            FROM {$this->table} i
            LEFT JOIN assets a ON i.asset_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN projects p ON a.project_id = p.id
            LEFT JOIN users ur ON i.reported_by = ur.id
            LEFT JOIN users ures ON i.resolved_by = ures.id
            LEFT JOIN users uv ON i.verified_by = uv.id
            LEFT JOIN users ua ON i.authorized_by = ua.id
            LEFT JOIN users uc ON i.closed_by = uc.id
            LEFT JOIN users ucan ON i.canceled_by = ucan.id
            WHERE i.id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get incidents with filters and pagination
     */
    public function getIncidentsWithFilters($filters = [], $page = 1, $perPage = 20) {
        $conditions = [];
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $conditions[] = "i.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['type'])) {
            $conditions[] = "i.type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['severity'])) {
            $conditions[] = "i.severity = ?";
            $params[] = $filters['severity'];
        }
        
        if (!empty($filters['asset_id'])) {
            $conditions[] = "i.asset_id = ?";
            $params[] = $filters['asset_id'];
        }
        
        if (!empty($filters['reported_by'])) {
            $conditions[] = "i.reported_by = ?";
            $params[] = $filters['reported_by'];
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(i.date_reported) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(i.date_reported) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(a.ref LIKE ? OR a.name LIKE ? OR i.description LIKE ? OR i.location LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        // Count total records
        $countSql = "
            SELECT COUNT(*) 
            FROM {$this->table} i
            LEFT JOIN assets a ON i.asset_id = a.id
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get paginated data
        $offset = ($page - 1) * $perPage;
        $orderBy = $filters['order_by'] ?? 'i.date_reported DESC';
        
        $dataSql = "
            SELECT i.*, 
                   a.ref as asset_ref, a.name as asset_name,
                   c.name as category_name,
                   p.name as project_name,
                   ur.full_name as reported_by_name,
                   uv.full_name as verified_by_name,
                   ua.full_name as authorized_by_name,
                   uc.full_name as closed_by_name
            FROM {$this->table} i
            LEFT JOIN assets a ON i.asset_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN projects p ON a.project_id = p.id
            LEFT JOIN users ur ON i.reported_by = ur.id
            LEFT JOIN users uv ON i.verified_by = uv.id
            LEFT JOIN users ua ON i.authorized_by = ua.id
            LEFT JOIN users uc ON i.closed_by = uc.id
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
     * Get incident statistics
     */
    public function getIncidentStats($dateFrom = null, $dateTo = null) {
        $conditions = [];
        $params = [];
        
        if ($dateFrom) {
            $conditions[] = "DATE(date_reported) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $conditions[] = "DATE(date_reported) <= ?";
            $params[] = $dateTo;
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        $sql = "
            SELECT 
                COUNT(*) as total_incidents,
                SUM(CASE WHEN status = 'Pending Verification' THEN 1 ELSE 0 END) as pending_verification,
                SUM(CASE WHEN status = 'Pending Authorization' THEN 1 ELSE 0 END) as pending_authorization,
                SUM(CASE WHEN status = 'Authorized' THEN 1 ELSE 0 END) as authorized,
                SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed,
                SUM(CASE WHEN status = 'Canceled' THEN 1 ELSE 0 END) as canceled,
                SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost,
                SUM(CASE WHEN type = 'damaged' THEN 1 ELSE 0 END) as damaged,
                SUM(CASE WHEN type = 'stolen' THEN 1 ELSE 0 END) as stolen,
                SUM(CASE WHEN type = 'other' THEN 1 ELSE 0 END) as other,
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high,
                SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium,
                SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low
            FROM {$this->table}
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get critical incidents
     */
    public function getCriticalIncidents() {
        $sql = "
            SELECT i.*, 
                   a.ref as asset_ref, a.name as asset_name,
                   c.name as category_name,
                   p.name as project_name,
                   ur.full_name as reported_by_name,
                   DATEDIFF(CURDATE(), i.date_reported) as days_open
            FROM {$this->table} i
            LEFT JOIN assets a ON i.asset_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN projects p ON a.project_id = p.id
            LEFT JOIN users ur ON i.reported_by = ur.id
            WHERE i.severity = 'critical' 
              AND i.status NOT IN ('Resolved', 'Closed', 'Canceled')
            ORDER BY i.date_reported ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get open incidents requiring attention
     */
    public function getOpenIncidents() {
        $sql = "
            SELECT i.*, 
                   a.ref as asset_ref, a.name as asset_name,
                   c.name as category_name,
                   p.name as project_name,
                   ur.full_name as reported_by_name,
                   DATEDIFF(CURDATE(), i.date_reported) as days_open
            FROM {$this->table} i
            LEFT JOIN assets a ON i.asset_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN projects p ON a.project_id = p.id
            LEFT JOIN users ur ON i.reported_by = ur.id
            WHERE i.status IN ('Pending Verification', 'Pending Authorization', 'Authorized')
            ORDER BY i.severity DESC, i.date_reported ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get asset incident history
     */
    public function getAssetIncidentHistory($assetId) {
        $sql = "
            SELECT i.*,
                   i.date_reported as incident_date,
                   i.type as incident_type,
                   i.resolution_notes as resolution,
                   ur.full_name as reported_by_name,
                   ures.full_name as resolved_by_name
            FROM {$this->table} i
            LEFT JOIN users ur ON i.reported_by = ur.id
            LEFT JOIN users ures ON i.resolved_by = ures.id
            WHERE i.asset_id = ?
            ORDER BY i.date_reported DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get recent incidents
     */
    public function getRecentIncidents($limit = 10, $projectId = null) {
        $whereClause = $projectId ? "WHERE a.project_id = ?" : "";
        $params = $projectId ? [$projectId, $limit] : [$limit];
        
        $sql = "
            SELECT i.*, 
                   a.ref as asset_ref, a.name as asset_name,
                   c.name as category_name,
                   p.name as project_name,
                   ur.full_name as reported_by_name
            FROM {$this->table} i
            LEFT JOIN assets a ON i.asset_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN projects p ON a.project_id = p.id
            LEFT JOIN users ur ON i.reported_by = ur.id
            {$whereClause}
            ORDER BY i.created_at DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get incident trends
     */
    public function getIncidentTrends($dateFrom, $dateTo) {
        $sql = "
            SELECT 
                DATE_FORMAT(date_reported, '%Y-%m') as month,
                COUNT(*) as incident_count,
                SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_count,
                SUM(CASE WHEN type = 'damaged' THEN 1 ELSE 0 END) as damaged_count,
                SUM(CASE WHEN type = 'stolen' THEN 1 ELSE 0 END) as stolen_count,
                SUM(CASE WHEN type = 'other' THEN 1 ELSE 0 END) as other_count
            FROM {$this->table}
            WHERE date_reported BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(date_reported, '%Y-%m')
            ORDER BY month ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get incident report data
     */
    public function getIncidentReport($dateFrom, $dateTo, $type = null, $projectId = null) {
        $conditions = ["i.date_reported BETWEEN ? AND ?"];
        $params = [$dateFrom, $dateTo];
        
        if ($type) {
            $conditions[] = "i.type = ?";
            $params[] = $type;
        }
        
        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }
        
        $whereClause = "WHERE " . implode(" AND ", $conditions);
        
        $sql = "
            SELECT i.*, 
                   a.ref as asset_ref, a.name as asset_name,
                   c.name as category_name,
                   p.name as project_name,
                   ur.full_name as reported_by_name,
                   ures.full_name as resolved_by_name
            FROM {$this->table} i
            LEFT JOIN assets a ON i.asset_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN projects p ON a.project_id = p.id
            LEFT JOIN users ur ON i.reported_by = ur.id
            LEFT JOIN users ures ON i.resolved_by = ures.id
            {$whereClause}
            ORDER BY i.date_reported DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    

}
?>
