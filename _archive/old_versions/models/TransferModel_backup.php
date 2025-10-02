<?php
/**
 * ConstructLink™ Transfer Model
 * Handles inter-site asset transfers and movement tracking
 */

class TransferModel extends BaseModel {
    protected $table = 'transfers';
    protected $fillable = [
        'asset_id', 'from_project', 'to_project', 'reason', 'initiated_by', 
        'transfer_type', 'approved_by', 'transfer_date', 'expected_return', 
        'actual_return', 'approval_date', 'status', 'notes'
    ];
    
    /**
     * Create transfer request with proper validation
     */
    public function createTransfer($data) {
        try {
            $validation = $this->validate($data, [
                'asset_id' => 'required|integer',
                'from_project' => 'required|integer',
                'to_project' => 'required|integer',
                'reason' => 'required|max:500',
                'initiated_by' => 'required|integer',
                'transfer_type' => 'required|in:temporary,permanent',
                'transfer_date' => 'required|date'
            ]);
            
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            // Validate that from and to projects are different
            if ($data['from_project'] == $data['to_project']) {
                return ['success' => false, 'message' => 'Source and destination projects must be different'];
            }
            
            // For temporary transfers, require expected return date
            if ($data['transfer_type'] === 'temporary' && empty($data['expected_return'])) {
                return ['success' => false, 'message' => 'Expected return date is required for temporary transfers'];
            }
            
            // Validate expected return date is in the future for temporary transfers
            if ($data['transfer_type'] === 'temporary' && !empty($data['expected_return'])) {
                if (strtotime($data['expected_return']) <= strtotime($data['transfer_date'])) {
                    return ['success' => false, 'message' => 'Expected return date must be after transfer date'];
                }
            }
            
            $this->beginTransaction();
            
            // Check if asset is available for transfer
            $assetModel = new AssetModel();
            $asset = $assetModel->find($data['asset_id']);
            
            if (!$asset) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            if ($asset['project_id'] != $data['from_project']) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset is not currently at the specified source project'];
            }
            
            if (!in_array($asset['status'], ['available', 'in_use'])) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset is not available for transfer'];
            }
            
            // Set default status
            $data['status'] = 'pending';
            
            // Create transfer record
            $transfer = $this->create($data);
            
            if (!$transfer) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to create transfer request'];
            }
            
            $this->commit();
            
            return ['success' => true, 'transfer' => $transfer];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Transfer creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create transfer request'];
        }
    }
    
    /**
     * Approve transfer
     */
    public function approveTransfer($transferId, $approvedBy, $notes = null) {
        try {
            $this->beginTransaction();
            
            // Get transfer details
            $transfer = $this->find($transferId);
            if (!$transfer) {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer not found'];
            }
            
            if ($transfer['status'] !== 'pending') {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer is not in pending status'];
            }
            
            // Update transfer status
            $updateResult = $this->update($transferId, [
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approval_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update transfer status'];
            }
            
            $this->commit();
            
            return [
                'success' => true, 
                'transfer' => $updateResult,
                'message' => 'Transfer approved successfully'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Transfer approval error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve transfer'];
        }
    }
    
    /**
     * Complete transfer (move asset to destination)
     */
    public function completeTransfer($transferId, $completedBy, $notes = null) {
        try {
            $this->beginTransaction();
            
            // Get transfer details
            $transfer = $this->getTransferWithDetails($transferId);
            if (!$transfer) {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer not found'];
            }
            
            if ($transfer['status'] !== 'approved') {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer must be approved before completion'];
            }
            
            // Update asset project location
            $assetModel = new AssetModel();
            $assetUpdateResult = $assetModel->update($transfer['asset_id'], [
                'project_id' => $transfer['to_project']
            ]);
            
            if (!$assetUpdateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update asset location'];
            }
            
            // Update transfer status
            $updateResult = $this->update($transferId, [
                'status' => 'completed',
                'notes' => $notes
            ]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update transfer status'];
            }
            
            $this->commit();
            
            return [
                'success' => true, 
                'transfer' => $updateResult,
                'message' => 'Transfer completed successfully'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Transfer completion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to complete transfer'];
        }
    }
    
    /**
     * Cancel transfer
     */
    public function cancelTransfer($transferId, $reason = null) {
        try {
            $transfer = $this->find($transferId);
            if (!$transfer) {
                return ['success' => false, 'message' => 'Transfer not found'];
            }
            
            if (!in_array($transfer['status'], ['pending', 'approved'])) {
                return ['success' => false, 'message' => 'Cannot cancel transfer in current status'];
            }
            
            // Update transfer status
            $updateResult = $this->update($transferId, [
                'status' => 'canceled',
                'notes' => $reason
            ]);
            
            if (!$updateResult) {
                return ['success' => false, 'message' => 'Failed to cancel transfer'];
            }
            
            return [
                'success' => true, 
                'transfer' => $updateResult,
                'message' => 'Transfer canceled successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Transfer cancellation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to cancel transfer'];
        }
    }
    
    /**
     * Get transfer with detailed information
     */
    public function getTransferWithDetails($id) {
        try {
            $sql = "
                SELECT t.*, 
                       COALESCE(a.ref, 'N/A') as asset_ref, 
                       COALESCE(a.name, 'Unknown') as asset_name, 
                       COALESCE(a.status, 'unknown') as asset_status,
                       COALESCE(c.name, 'Unknown') as category_name,
                       COALESCE(pf.name, 'Unknown') as from_project_name, 
                       COALESCE(pf.location, '') as from_project_location,
                       COALESCE(pt.name, 'Unknown') as to_project_name, 
                       COALESCE(pt.location, '') as to_project_location,
                       COALESCE(ui.full_name, 'Unknown') as initiated_by_name,
                       COALESCE(ua.full_name, 'Unknown') as approved_by_name
                FROM transfers t
                LEFT JOIN assets a ON t.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects pf ON t.from_project = pf.id
                LEFT JOIN projects pt ON t.to_project = pt.id
                LEFT JOIN users ui ON t.initiated_by = ui.id
                LEFT JOIN users ua ON t.approved_by = ua.id
                WHERE t.id = ?
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Get transfer with details error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get transfers with filters and pagination
     */
    public function getTransfersWithFilters($filters = [], $page = 1, $perPage = 20) {
        try {
            $conditions = [];
            $params = [];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $conditions[] = "t.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['from_project'])) {
                $conditions[] = "t.from_project = ?";
                $params[] = $filters['from_project'];
            }
            
            if (!empty($filters['to_project'])) {
                $conditions[] = "t.to_project = ?";
                $params[] = $filters['to_project'];
            }
            
            if (!empty($filters['transfer_type'])) {
                $conditions[] = "t.transfer_type = ?";
                $params[] = $filters['transfer_type'];
            }
            
            if (!empty($filters['initiated_by'])) {
                $conditions[] = "t.initiated_by = ?";
                $params[] = $filters['initiated_by'];
            }
            
            if (!empty($filters['date_from'])) {
                $conditions[] = "t.transfer_date >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions[] = "t.transfer_date <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['search'])) {
                $conditions[] = "(a.ref LIKE ? OR a.name LIKE ? OR t.reason LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            // Count total records
            $countSql = "
                SELECT COUNT(*) 
                FROM transfers t
                LEFT JOIN assets a ON t.asset_id = a.id
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();
            
            // Get paginated data
            $offset = ($page - 1) * $perPage;
            $orderBy = $filters['order_by'] ?? 't.created_at DESC';
            
            $dataSql = "
                SELECT t.*, 
                       COALESCE(a.ref, 'N/A') as asset_ref, 
                       COALESCE(a.name, 'Unknown') as asset_name,
                       COALESCE(c.name, 'Unknown') as category_name,
                       COALESCE(pf.name, 'Unknown') as from_project_name,
                       COALESCE(pt.name, 'Unknown') as to_project_name,
                       COALESCE(ui.full_name, 'Unknown') as initiated_by_name
                FROM transfers t
                LEFT JOIN assets a ON t.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects pf ON t.from_project = pf.id
                LEFT JOIN projects pt ON t.to_project = pt.id
                LEFT JOIN users ui ON t.initiated_by = ui.id
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
            error_log("Get transfers with filters error: " . $e->getMessage());
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
     * Get transfer statistics (alias for backward compatibility)
     */
    public function getTransferStats($dateFrom = null, $dateTo = null) {
        return $this->getTransferStatistics($dateFrom, $dateTo);
    }
    
    /**
     * Get asset transfer history
     */
    public function getAssetTransferHistory($assetId) {
        try {
            $sql = "
                SELECT t.*, 
                       pf.name as from_project_name,
                       pt.name as to_project_name,
                       ui.full_name as initiated_by_name,
                       ua.full_name as approved_by_name
                FROM transfers t
                LEFT JOIN projects pf ON t.from_project = pf.id
                LEFT JOIN projects pt ON t.to_project = pt.id
                LEFT JOIN users ui ON t.initiated_by = ui.id
                LEFT JOIN users ua ON t.approved_by = ua.id
                WHERE t.asset_id = ?
                ORDER BY t.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get asset transfer history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent transfers
     */
    public function getRecentTransfers($limit = 10, $projectId = null) {
        try {
            $whereClause = $projectId ? "WHERE (t.from_project = ? OR t.to_project = ?)" : "";
            $params = $projectId ? [$projectId, $projectId] : [];
            
            $sql = "
                SELECT t.*, 
                       a.ref as asset_ref, a.name as asset_name,
                       pf.name as from_project_name,
                       pt.name as to_project_name,
                       ui.full_name as initiated_by_name
                FROM transfers t
                LEFT JOIN assets a ON t.asset_id = a.id
                LEFT JOIN projects pf ON t.from_project = pf.id
                LEFT JOIN projects pt ON t.to_project = pt.id
                LEFT JOIN users ui ON t.initiated_by = ui.id
                {$whereClause}
                ORDER BY t.created_at DESC
                LIMIT ?
            ";
            
            $params[] = $limit;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get recent transfers error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get transfer report data
     */
    public function getTransferReport($dateFrom, $dateTo, $fromProject = null, $toProject = null) {
        try {
            $conditions = ["DATE(t.transfer_date) BETWEEN ? AND ?"];
            $params = [$dateFrom, $dateTo];
            
            if ($fromProject) {
                $conditions[] = "t.from_project = ?";
                $params[] = $fromProject;
            }
            
            if ($toProject) {
                $conditions[] = "t.to_project = ?";
                $params[] = $toProject;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT t.*, 
                       a.ref as asset_ref, a.name as asset_name,
                       c.name as category_name,
                       pf.name as from_project_name,
                       pt.name as to_project_name,
                       ui.full_name as initiated_by_name,
                       ua.full_name as approved_by_name
                FROM transfers t
                LEFT JOIN assets a ON t.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects pf ON t.from_project = pf.id
                LEFT JOIN projects pt ON t.to_project = pt.id
                LEFT JOIN users ui ON t.initiated_by = ui.id
                LEFT JOIN users ua ON t.approved_by = ua.id
                {$whereClause}
                ORDER BY t.transfer_date DESC, t.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get transfer report error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Return asset from temporary transfer
     */
    public function returnFromTransfer($transferId, $returnNotes = null) {
        try {
            $this->beginTransaction();
            
            // Get transfer details
            $transfer = $this->getTransferWithDetails($transferId);
            if (!$transfer) {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer not found'];
            }
            
            if ($transfer['status'] !== 'completed' || $transfer['transfer_type'] !== 'temporary') {
                $this->rollback();
                return ['success' => false, 'message' => 'Only completed temporary transfers can be returned'];
            }
            
            if (!empty($transfer['actual_return'])) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset has already been returned'];
            }
            
            // Update asset back to original project
            $assetModel = new AssetModel();
            $assetUpdateResult = $assetModel->update($transfer['asset_id'], [
                'project_id' => $transfer['from_project']
            ]);
            
            if (!$assetUpdateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to return asset'];
            }
            
            // Update transfer with return date
            $updateResult = $this->update($transferId, [
                'actual_return' => date('Y-m-d'),
                'notes' => $returnNotes ? $transfer['notes'] . "\n\nReturn Notes: " . $returnNotes : $transfer['notes']
            ]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update transfer record'];
            }
            
            $this->commit();
            
            return [
                'success' => true, 
                'transfer' => $updateResult,
                'message' => 'Asset returned successfully'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Transfer return error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to return asset'];
        }
    }
    
    /**
     * Get overdue temporary transfers
     */
    public function getOverdueReturns() {
        try {
            $sql = "
                SELECT t.*, 
                       a.ref as asset_ref, a.name as asset_name,
                       c.name as category_name,
                       pf.name as from_project_name,
                       pt.name as to_project_name,
                       ui.full_name as initiated_by_name,
                       DATEDIFF(CURDATE(), t.expected_return) as days_overdue
                FROM transfers t
                LEFT JOIN assets a ON t.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects pf ON t.from_project = pf.id
                LEFT JOIN projects pt ON t.to_project = pt.id
                LEFT JOIN users ui ON t.initiated_by = ui.id
                WHERE t.transfer_type = 'temporary' 
                  AND t.status = 'completed'
                  AND t.expected_return < CURDATE()
                  AND t.actual_return IS NULL
                ORDER BY t.expected_return ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get overdue returns error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get temporary transfers due for return soon
     */
    public function getTransfersDueSoon($days = 7) {
        try {
            $sql = "
                SELECT t.*, 
                       a.ref as asset_ref, a.name as asset_name,
                       c.name as category_name,
                       pf.name as from_project_name,
                       pt.name as to_project_name,
                       ui.full_name as initiated_by_name,
                       DATEDIFF(t.expected_return, CURDATE()) as days_until_due
                FROM transfers t
                LEFT JOIN assets a ON t.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects pf ON t.from_project = pf.id
                LEFT JOIN projects pt ON t.to_project = pt.id
                LEFT JOIN users ui ON t.initiated_by = ui.id
                WHERE t.transfer_type = 'temporary' 
                  AND t.status = 'completed'
                  AND t.expected_return BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                  AND t.actual_return IS NULL
                ORDER BY t.expected_return ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get transfers due soon error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update transfer statistics to include overdue returns
     */
    public function getTransferStatistics($dateFrom = null, $dateTo = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($dateFrom) {
                $conditions[] = "transfer_date >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $conditions[] = "transfer_date <= ?";
                $params[] = $dateTo;
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT 
                    COUNT(*) as total_transfers,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled,
                    SUM(CASE WHEN transfer_type = 'temporary' THEN 1 ELSE 0 END) as temporary,
                    SUM(CASE WHEN transfer_type = 'permanent' THEN 1 ELSE 0 END) as permanent,
                    SUM(CASE WHEN transfer_type = 'temporary' AND status = 'completed' AND expected_return < CURDATE() AND actual_return IS NULL THEN 1 ELSE 0 END) as overdue_returns,
                    SUM(CASE WHEN transfer_type = 'temporary' AND status = 'completed' AND actual_return IS NOT NULL THEN 1 ELSE 0 END) as returned
                FROM transfers 
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result ?: [
                'total_transfers' => 0,
                'pending' => 0,
                'approved' => 0,
                'completed' => 0,
                'canceled' => 0,
                'temporary' => 0,
                'permanent' => 0,
                'overdue_returns' => 0,
                'returned' => 0
            ];
            
        } catch (Exception $e) {
            error_log("Get transfer statistics error: " . $e->getMessage());
            return [
                'total_transfers' => 0,
                'pending' => 0,
                'approved' => 0,
                'completed' => 0,
                'canceled' => 0,
                'temporary' => 0,
                'permanent' => 0,
                'overdue_returns' => 0,
                'returned' => 0
            ];
        }
    }
}
?>
