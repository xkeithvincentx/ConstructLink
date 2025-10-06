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
        'actual_return', 'approval_date', 'status', 'notes', 'verified_by',
        'verification_date', 'dispatched_by', 'dispatch_date', 'dispatch_notes',
        'received_by', 'receipt_date', 'completed_by', 'completion_date',
        'return_initiated_by', 'return_initiation_date', 'return_received_by',
        'return_receipt_date', 'return_status', 'return_notes'
    ];
    
    /**
     * Create transfer request with proper validation and smart workflow
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
            
            // Get initiator role for smart workflow
            $userModel = new UserModel();
            $initiator = $userModel->getUserWithRole($data['initiated_by']);
            $initiatorRole = $initiator ? ($initiator['role_name'] ?? '') : '';
            
            // Smart workflow logic based on initiator role
            if (in_array($initiatorRole, ['Finance Director', 'Asset Director'])) {
                // Finance Director or Asset Director - streamlined process (skip to Received)
                $data['status'] = 'Received';
                $data['verified_by'] = $data['initiated_by'];
                $data['verification_date'] = date('Y-m-d H:i:s');
                $data['approved_by'] = $data['initiated_by'];
                $data['approval_date'] = date('Y-m-d H:i:s');
                $data['received_by'] = $data['initiated_by'];
                $data['receipt_date'] = date('Y-m-d H:i:s');
            } else {
                // All other roles (including Project Manager) - standard MVA workflow
                // FROM Project Manager must verify, then Finance/Asset Director approves
                $data['status'] = 'Pending Verification';
            }
            
            // Create transfer record
            $transfer = $this->create($data);
            
            if (!$transfer) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to create transfer request'];
            }
            
            // Update asset status based on transfer status
            if ($data['status'] === 'Received') {
                // For streamlined process (Finance Director/Asset Director), asset is ready for completion
                $assetStatus = 'in_transit';
            } elseif ($data['status'] === 'Approved') {
                $assetStatus = 'in_transit';
            } else {
                $assetStatus = 'in_use';
            }
            
            $assetUpdateResult = $assetModel->update($data['asset_id'], [
                'status' => $assetStatus
            ]);
            
            if (!$assetUpdateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update asset status'];
            }
            
            $this->commit();

            // Send notifications based on workflow status
            $this->sendTransferNotification($transfer['id'], 'created', $data['initiated_by']);

            return ['success' => true, 'transfer' => $transfer, 'smart_workflow' => true];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Transfer creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create transfer request'];
        }
    }
    
    /**
     * Verify transfer (Project Manager step)
     */
    public function verifyTransfer($transferId, $verifiedBy, $notes = null) {
        try {
            $this->beginTransaction();
            
            // Get transfer details
            $transfer = $this->find($transferId);
            if (!$transfer) {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer not found'];
            }
            
            if ($transfer['status'] !== 'Pending Verification') {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer is not in pending verification status'];
            }
            
            // Update transfer status
            $updateResult = $this->update($transferId, [
                'status' => 'Pending Approval',
                'verified_by' => $verifiedBy,
                'verification_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update transfer status'];
            }
            
            $this->commit();

            // Send notification
            $this->sendTransferNotification($transferId, 'verified', $verifiedBy);

            return [
                'success' => true,
                'transfer' => $updateResult,
                'message' => 'Transfer verified successfully'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Transfer verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to verify transfer'];
        }
    }
    
    /**
     * Approve transfer (Finance Director/Asset Director step)
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
            
            if ($transfer['status'] !== 'Pending Approval') {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer is not in pending approval status'];
            }
            
            // Update asset status to 'in_transit' after approval
            $assetModel = new AssetModel();
            $assetUpdateResult = $assetModel->update($transfer['asset_id'], [
                'status' => 'in_transit'  // Asset is now in transit
            ]);
            
            if (!$assetUpdateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update asset status to in transit'];
            }
            
            // Update transfer status
            $updateResult = $this->update($transferId, [
                'status' => 'Approved',
                'approved_by' => $approvedBy,
                'approval_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update transfer status'];
            }
            
            $this->commit();

            // Send notification
            $this->sendTransferNotification($transferId, 'approved', $approvedBy);

            return [
                'success' => true,
                'transfer' => $updateResult,
                'message' => 'Transfer approved successfully - Asset is now in transit'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Transfer approval error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve transfer'];
        }
    }
    
    /**
     * Dispatch transfer (FROM Project Manager confirms asset sent)
     */
    public function dispatchTransfer($transferId, $dispatchedBy, $notes = null) {
        try {
            $this->beginTransaction();

            // Get transfer details
            $transfer = $this->find($transferId);
            if (!$transfer) {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer not found'];
            }

            if ($transfer['status'] !== 'Approved') {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer must be approved before dispatch'];
            }

            // Update transfer status to In Transit
            $updateResult = $this->update($transferId, [
                'status' => 'In Transit',
                'dispatched_by' => $dispatchedBy,
                'dispatch_date' => date('Y-m-d H:i:s'),
                'dispatch_notes' => $notes
            ]);

            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update transfer status'];
            }

            $this->commit();

            // Send notification
            $this->sendTransferNotification($transferId, 'dispatched', $dispatchedBy);

            return [
                'success' => true,
                'transfer' => $updateResult,
                'message' => 'Transfer dispatched successfully - Asset is now in transit'
            ];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Transfer dispatch error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to dispatch transfer'];
        }
    }

    /**
     * Receive and Complete transfer in one step (simplified workflow)
     * TO Project Manager receives the asset and it's immediately completed
     */
    public function receiveTransfer($transferId, $receivedBy, $notes = null) {
        try {
            $this->beginTransaction();

            // Get transfer details
            $transfer = $this->getTransferWithDetails($transferId);
            if (!$transfer) {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer not found'];
            }

            // Transfer must be In Transit before it can be received
            if ($transfer['status'] !== 'In Transit') {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer must be dispatched (In Transit) before receiving'];
            }

            // Update asset project location and status
            $assetModel = new AssetModel();
            $assetUpdateResult = $assetModel->update($transfer['asset_id'], [
                'project_id' => $transfer['to_project'],
                'status' => 'available'  // Asset is now available at the new location
            ]);

            if (!$assetUpdateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update asset location'];
            }

            // Update transfer to Completed status (skip Received status)
            $updateResult = $this->update($transferId, [
                'status' => 'Completed',
                'received_by' => $receivedBy,
                'receipt_date' => date('Y-m-d H:i:s'),
                'completed_by' => $receivedBy,  // Same person receives and completes
                'completion_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ]);

            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update transfer status'];
            }

            $this->commit();

            // Send notification
            $this->sendTransferNotification($transferId, 'completed', $receivedBy);

            return [
                'success' => true,
                'transfer' => $updateResult,
                'message' => 'Transfer received and completed successfully'
            ];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Transfer receipt error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to receive transfer'];
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
            
            if ($transfer['status'] !== 'Received') {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer must be received before completion'];
            }
            
            // Update asset project location and status
            $assetModel = new AssetModel();
            $assetUpdateResult = $assetModel->update($transfer['asset_id'], [
                'project_id' => $transfer['to_project'],
                'status' => 'available'  // Asset is now available at the new location
            ]);
            
            if (!$assetUpdateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update asset location'];
            }
            
            // Update transfer status
            $updateResult = $this->update($transferId, [
                'status' => 'Completed',
                'completed_by' => $completedBy,
                'completion_date' => date('Y-m-d H:i:s'),
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
            $this->beginTransaction();
            
            $transfer = $this->find($transferId);
            if (!$transfer) {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer not found'];
            }
            
            if (!in_array($transfer['status'], ['Pending Verification', 'Pending Approval', 'Approved', 'In Transit'])) {
                $this->rollback();
                return ['success' => false, 'message' => 'Cannot cancel transfer in current status'];
            }
            
            // Update transfer status
            $updateResult = $this->update($transferId, [
                'status' => 'Canceled',
                'notes' => $reason
            ]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to cancel transfer'];
            }
            
            // Restore asset status to available (whether it was in_use or in_transit)
            $assetModel = new AssetModel();
            $assetUpdateResult = $assetModel->update($transfer['asset_id'], [
                'status' => 'available'
            ]);
            
            if (!$assetUpdateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to restore asset status'];
            }
            
            $this->commit();

            // Send notification
            $currentUser = Auth::getInstance()->getCurrentUser();
            $this->sendTransferNotification($transferId, 'canceled', $currentUser['id'] ?? null);

            return [
                'success' => true,
                'transfer' => $updateResult,
                'message' => 'Transfer canceled successfully'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
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
                       pf.project_manager_id as from_project_manager_id,
                       COALESCE(pt.name, 'Unknown') as to_project_name, 
                       COALESCE(pt.location, '') as to_project_location,
                       pt.project_manager_id as to_project_manager_id,
                       COALESCE(ui.full_name, 'Unknown') as initiated_by_name,
                       COALESCE(ua.full_name, 'Unknown') as approved_by_name,
                       COALESCE(uv.full_name, 'Unknown') as verified_by_name,
                       COALESCE(ud.full_name, 'Unknown') as dispatched_by_name,
                       COALESCE(ur.full_name, 'Unknown') as received_by_name,
                       COALESCE(uc.full_name, 'Unknown') as completed_by_name,
                       COALESCE(uri.full_name, 'Unknown') as return_initiated_by_name,
                       COALESCE(urr.full_name, 'Unknown') as return_received_by_name
                FROM transfers t
                LEFT JOIN assets a ON t.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects pf ON t.from_project = pf.id
                LEFT JOIN projects pt ON t.to_project = pt.id
                LEFT JOIN users ui ON t.initiated_by = ui.id
                LEFT JOIN users ua ON t.approved_by = ua.id
                LEFT JOIN users uv ON t.verified_by = uv.id
                LEFT JOIN users ud ON t.dispatched_by = ud.id
                LEFT JOIN users ur ON t.received_by = ur.id
                LEFT JOIN users uc ON t.completed_by = uc.id
                LEFT JOIN users uri ON t.return_initiated_by = uri.id
                LEFT JOIN users urr ON t.return_received_by = urr.id
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
                       pf.project_manager_id as from_project_manager_id,
                       COALESCE(pt.name, 'Unknown') as to_project_name,
                       pt.project_manager_id as to_project_manager_id,
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
     * Get transfer statistics with overdue returns support and user-specific counts
     */
    public function getTransferStatistics($dateFrom = null, $dateTo = null, $userId = null) {
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
                    SUM(CASE WHEN status = 'Pending Verification' THEN 1 ELSE 0 END) as pending_verification,
                    SUM(CASE WHEN status = 'Pending Approval' THEN 1 ELSE 0 END) as pending_approval,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'In Transit' THEN 1 ELSE 0 END) as in_transit,
                    SUM(CASE WHEN status = 'Received' THEN 1 ELSE 0 END) as received,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'Canceled' THEN 1 ELSE 0 END) as canceled,
                    SUM(CASE WHEN transfer_type = 'temporary' THEN 1 ELSE 0 END) as temporary_transfers,
                    SUM(CASE WHEN transfer_type = 'permanent' THEN 1 ELSE 0 END) as permanent_transfers,
                    SUM(CASE WHEN transfer_type = 'temporary' AND status = 'Completed' AND expected_return < CURDATE() AND return_status = 'not_returned' THEN 1 ELSE 0 END) as overdue_returns,
                    SUM(CASE WHEN transfer_type = 'temporary' AND status = 'Completed' AND return_status = 'not_returned' THEN 1 ELSE 0 END) as pending_returns,
                    SUM(CASE WHEN transfer_type = 'temporary' AND return_status = 'returned' THEN 1 ELSE 0 END) as returned,
                    SUM(CASE WHEN return_status = 'in_return_transit' THEN 1 ELSE 0 END) as returns_in_transit,
                    SUM(CASE WHEN return_status = 'in_return_transit' AND DATEDIFF(NOW(), return_initiation_date) > 3 THEN 1 ELSE 0 END) as overdue_return_transits,
                    ROUND((SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0)), 0) as completion_rate
                FROM transfers
                {$whereClause}
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            // Get user-specific statistics if userId is provided
            if ($userId) {
                // Get count of transfers pending verification where user is the target project manager
                $userVerificationSql = "
                    SELECT COUNT(*) as my_pending_verifications
                    FROM transfers t
                    LEFT JOIN projects pf ON t.from_project = pf.id
                    WHERE t.status = 'Pending Verification'
                      AND pf.project_manager_id = ?
                ";
                $stmt = $this->db->prepare($userVerificationSql);
                $stmt->execute([$userId]);
                $userStats = $stmt->fetch();
                $result['my_pending_verifications'] = $userStats['my_pending_verifications'] ?? 0;

                // For approvals - this would be for Asset Directors / Finance Directors
                // Just count all pending approvals for now (they can approve all)
                $result['my_pending_approvals'] = $result['pending_approval'] ?? 0;
            } else {
                $result['my_pending_verifications'] = 0;
                $result['my_pending_approvals'] = 0;
            }

            // Calculate permanent transfer value if not included
            if (!isset($result['permanent_transfer_value'])) {
                $valueSql = "
                    SELECT COALESCE(SUM(a.acquisition_cost), 0) as permanent_transfer_value
                    FROM transfers t
                    LEFT JOIN assets a ON t.asset_id = a.id
                    WHERE t.transfer_type = 'permanent'
                      AND t.status = 'Completed'
                ";
                $stmt = $this->db->prepare($valueSql);
                $stmt->execute();
                $valueResult = $stmt->fetch();
                $result['permanent_transfer_value'] = $valueResult['permanent_transfer_value'] ?? 0;
            }

            return $result ?: $this->getDefaultStats();

        } catch (Exception $e) {
            error_log("Get transfer statistics error: " . $e->getMessage());
            return $this->getDefaultStats();
        }
    }

    /**
     * Get default statistics structure
     */
    private function getDefaultStats() {
        return [
            'total_transfers' => 0,
            'pending_verification' => 0,
            'pending_approval' => 0,
            'approved' => 0,
            'in_transit' => 0,
            'received' => 0,
            'completed' => 0,
            'canceled' => 0,
            'temporary_transfers' => 0,
            'permanent_transfers' => 0,
            'overdue_returns' => 0,
            'pending_returns' => 0,
            'returned' => 0,
            'returns_in_transit' => 0,
            'overdue_return_transits' => 0,
            'completion_rate' => 0,
            'my_pending_verifications' => 0,
            'my_pending_approvals' => 0,
            'permanent_transfer_value' => 0
        ];
    }
    
    /**
     * Get transfer statistics (alias for backward compatibility)
     */
    public function getTransferStats($dateFrom = null, $dateTo = null, $userId = null) {
        return $this->getTransferStatistics($dateFrom, $dateTo, $userId);
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
     * Initiate return process for temporary transfer
     */
    public function initiateReturn($transferId, $initiatedBy, $returnNotes = null) {
        try {
            $this->beginTransaction();
            
            // Get transfer details
            $transfer = $this->getTransferWithDetails($transferId);
            if (!$transfer) {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer not found'];
            }
            
            // Validate transfer eligibility for return
            if ($transfer['status'] !== 'Completed' || $transfer['transfer_type'] !== 'temporary') {
                $this->rollback();
                return ['success' => false, 'message' => 'Only completed temporary transfers can be returned'];
            }
            
            if ($transfer['return_status'] !== 'not_returned') {
                $this->rollback();
                return ['success' => false, 'message' => 'Return process already initiated for this transfer'];
            }
            
            // Validate asset is available at destination
            $assetModel = new AssetModel();
            $asset = $assetModel->find($transfer['asset_id']);
            
            if (!$asset || $asset['status'] !== 'available') {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset must be available at destination to initiate return'];
            }
            
            if ($asset['project_id'] != $transfer['to_project']) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset is not currently at the destination project'];
            }
            
            // Set asset status to in_transit
            $assetUpdateResult = $assetModel->update($transfer['asset_id'], [
                'status' => 'in_transit'
            ]);
            
            if (!$assetUpdateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update asset status for return transit'];
            }
            
            // Update transfer with return initiation details
            $updateResult = $this->update($transferId, [
                'return_initiated_by' => $initiatedBy,
                'return_initiation_date' => date('Y-m-d H:i:s'),
                'return_status' => 'in_return_transit',
                'return_notes' => $returnNotes
            ]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update transfer record'];
            }
            
            $this->commit();

            // Send email notification to FROM Project Manager to receive return
            require_once APP_ROOT . '/models/ProjectModel.php';
            require_once APP_ROOT . '/models/UserModel.php';

            $projectModel = new ProjectModel();
            $userModel = new UserModel();

            $fromProjectPM = $projectModel->find($transfer['from_project'])['project_manager_id'] ?? null;
            if ($fromProjectPM) {
                $pmUser = $userModel->getUserWithRole($fromProjectPM);
                if ($pmUser && !empty($pmUser['email'])) {
                    require_once APP_ROOT . '/core/TransferEmailTemplates.php';
                    $emailTemplates = new TransferEmailTemplates();

                    // Get updated transfer with all details
                    $transferWithDetails = $this->getTransferWithDetails($transferId);
                    if ($transferWithDetails) {
                        $emailTemplates->sendReturnReceiptRequest($transferWithDetails, $pmUser);
                    }
                }
            }

            return [
                'success' => true,
                'transfer' => $updateResult,
                'message' => 'Return process initiated successfully - Asset is now in transit'
            ];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Transfer return initiation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to initiate return process'];
        }
    }

    /**
     * Receive returned asset at origin project
     */
    public function receiveReturn($transferId, $receivedBy, $receiptNotes = null) {
        try {
            $this->beginTransaction();
            
            // Get transfer details
            $transfer = $this->getTransferWithDetails($transferId);
            if (!$transfer) {
                $this->rollback();
                return ['success' => false, 'message' => 'Transfer not found'];
            }
            
            // Validate return is in correct status
            if ($transfer['return_status'] !== 'in_return_transit') {
                $this->rollback();
                return ['success' => false, 'message' => 'Return must be in transit to be received'];
            }
            
            // Validate asset exists and is in appropriate status for return receipt
            $assetModel = new AssetModel();
            $asset = $assetModel->find($transfer['asset_id']);
            
            if (!$asset) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            // Asset should be in_transit for return, but if it's available at destination, we can still process
            // This handles cases where return was initiated but asset status wasn't properly updated
            if (!in_array($asset['status'], ['in_transit', 'available'])) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset must be available or in transit to complete return process'];
            }
            
            // Validate asset location - it should be at destination project or already moved to origin
            // If asset is already at origin, the return receipt might be a duplicate/redundant action
            if ($asset['project_id'] != $transfer['to_project'] && $asset['project_id'] != $transfer['from_project']) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset is at an unexpected location (Project ID: ' . $asset['project_id'] . '). Expected at destination (ID: ' . $transfer['to_project'] . ') or origin (ID: ' . $transfer['from_project'] . ')'];
            }
            
            // If asset is already at origin project, this might be a duplicate receipt
            if ($asset['project_id'] == $transfer['from_project']) {
                // Check if this is a legitimate re-receipt or if return was already completed
                // We'll allow it but log a warning and ensure status is correct
                error_log("Warning: Receiving return for asset {$asset['ref']} that is already at origin project. Transfer ID: {$transferId}");
            }
            
            // Return asset to original project and mark as available
            $assetUpdateResult = $assetModel->update($transfer['asset_id'], [
                'project_id' => $transfer['from_project'],
                'status' => 'available'
            ]);
            
            if (!$assetUpdateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to return asset to origin project'];
            }
            
            // Update transfer with receipt details
            $currentNotes = $transfer['return_notes'] ? $transfer['return_notes'] : '';
            $updatedNotes = $currentNotes;
            if ($receiptNotes) {
                $updatedNotes .= ($currentNotes ? "\n\n" : '') . "Receipt Notes: " . $receiptNotes;
            }
            
            $updateResult = $this->update($transferId, [
                'return_received_by' => $receivedBy,
                'return_receipt_date' => date('Y-m-d H:i:s'),
                'return_status' => 'returned',
                'actual_return' => date('Y-m-d'),
                'return_notes' => $updatedNotes
            ]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update transfer record'];
            }
            
            $this->commit();

            // Send completion notification to all involved parties
            require_once APP_ROOT . '/models/UserModel.php';

            $userModel = new UserModel();
            $usersToNotify = [];

            // Notify initiator (TO PM who requested the transfer)
            if ($transfer['initiated_by']) {
                $initiator = $userModel->getUserWithRole($transfer['initiated_by']);
                if ($initiator && !empty($initiator['email'])) $usersToNotify[] = $initiator;
            }

            // Notify return initiator (TO PM who initiated return)
            if ($transfer['return_initiated_by'] && $transfer['return_initiated_by'] != $transfer['initiated_by']) {
                $returnInitiator = $userModel->getUserWithRole($transfer['return_initiated_by']);
                if ($returnInitiator && !empty($returnInitiator['email'])) $usersToNotify[] = $returnInitiator;
            }

            // Notify FROM PM (who received the return)
            if ($receivedBy && $receivedBy != $transfer['initiated_by']) {
                $receiver = $userModel->getUserWithRole($receivedBy);
                if ($receiver && !empty($receiver['email'])) $usersToNotify[] = $receiver;
            }

            if (!empty($usersToNotify)) {
                require_once APP_ROOT . '/core/TransferEmailTemplates.php';
                $emailTemplates = new TransferEmailTemplates();

                // Get updated transfer with all details
                $transferWithDetails = $this->getTransferWithDetails($transferId);
                if ($transferWithDetails) {
                    $emailTemplates->sendReturnCompletedNotification($transferWithDetails, $usersToNotify);
                }
            }

            return [
                'success' => true,
                'transfer' => $updateResult,
                'message' => 'Asset return completed successfully - Asset is now available at origin project'
            ];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Transfer return receipt error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to receive returned asset'];
        }
    }

    /**
     * Get returns currently in transit
     */
    public function getReturnsInTransit() {
        try {
            $sql = "
                SELECT t.*, 
                       a.ref as asset_ref, a.name as asset_name,
                       c.name as category_name,
                       pf.name as from_project_name,
                       pt.name as to_project_name,
                       ui.full_name as return_initiated_by_name,
                       DATEDIFF(NOW(), t.return_initiation_date) as days_in_transit
                FROM transfers t
                LEFT JOIN assets a ON t.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects pf ON t.from_project = pf.id
                LEFT JOIN projects pt ON t.to_project = pt.id
                LEFT JOIN users ui ON t.return_initiated_by = ui.id
                WHERE t.return_status = 'in_return_transit'
                ORDER BY t.return_initiation_date ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get returns in transit error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get overdue return transits (returns stuck in transit too long)
     */
    public function getOverdueReturnTransits($maxDaysInTransit = 3) {
        try {
            $sql = "
                SELECT t.*, 
                       a.ref as asset_ref, a.name as asset_name,
                       c.name as category_name,
                       pf.name as from_project_name,
                       pt.name as to_project_name,
                       ui.full_name as return_initiated_by_name,
                       DATEDIFF(NOW(), t.return_initiation_date) as days_in_transit
                FROM transfers t
                LEFT JOIN assets a ON t.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects pf ON t.from_project = pf.id
                LEFT JOIN projects pt ON t.to_project = pt.id
                LEFT JOIN users ui ON t.return_initiated_by = ui.id
                WHERE t.return_status = 'in_return_transit'
                  AND DATEDIFF(NOW(), t.return_initiation_date) > ?
                ORDER BY t.return_initiation_date ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$maxDaysInTransit]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get overdue return transits error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Legacy method - kept for backward compatibility but now uses proper workflow
     * @deprecated Use initiateReturn() instead
     */
    public function returnFromTransfer($transferId, $returnNotes = null) {
        // For backward compatibility, this will initiate the return process
        // The caller should then handle the receipt process separately
        return $this->initiateReturn($transferId, null, $returnNotes);
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
                  AND t.status = 'Completed'
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
                  AND t.status = 'Completed'
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
     * Send transfer workflow notifications
     */
    private function sendTransferNotification($transferId, $action, $actorId) {
        try {
            error_log("sendTransferNotification START: transferId={$transferId}, action={$action}, actorId={$actorId}");

            require_once APP_ROOT . '/models/NotificationModel.php';
            require_once APP_ROOT . '/models/ProjectModel.php';
            require_once APP_ROOT . '/models/UserModel.php';
            require_once APP_ROOT . '/core/TransferEmailTemplates.php';

            $notificationModel = new NotificationModel();
            $projectModel = new ProjectModel();
            $userModel = new UserModel();
            $emailTemplates = new TransferEmailTemplates();

            // Get transfer details
            $transfer = $this->getTransferWithDetails($transferId);
            if (!$transfer) {
                error_log("sendTransferNotification ERROR: Transfer not found");
                return false;
            }

            error_log("sendTransferNotification: Transfer status = {$transfer['status']}");

            $assetName = $transfer['asset_name'] ?? 'Asset';
            $assetRef = $transfer['asset_ref'] ?? '';
            $fromProject = $transfer['from_project_name'] ?? 'Unknown';
            $toProject = $transfer['to_project_name'] ?? 'Unknown';
            $transferUrl = '?route=transfers/view&id=' . $transferId;

            // Determine recipients and message based on action
            $recipients = [];
            $title = '';
            $message = '';
            $type = 'transfer';

            switch ($action) {
                case 'created':
                    $title = 'New Transfer Request Created';
                    $message = "Transfer request for {$assetName} ({$assetRef}) from {$fromProject} to {$toProject} has been created and requires verification.";

                    // Notify FROM Project Manager for verification
                    if ($transfer['status'] === 'Pending Verification') {
                        error_log("sendTransferNotification: Status is Pending Verification - sending email");
                        $fromProjectPM = $projectModel->find($transfer['from_project'])['project_manager_id'] ?? null;
                        error_log("sendTransferNotification: FROM PM ID = " . ($fromProjectPM ?? 'NULL'));

                        if ($fromProjectPM) {
                            $recipients[] = $fromProjectPM;

                            // Send email with one-click verification link
                            $pmUser = $userModel->getUserWithRole($fromProjectPM);
                            error_log("sendTransferNotification: PM User email = " . ($pmUser['email'] ?? 'NULL'));

                            if ($pmUser && !empty($pmUser['email'])) {
                                error_log("sendTransferNotification: Calling sendVerificationRequest");
                                $emailResult = $emailTemplates->sendVerificationRequest($transfer, $pmUser);
                                error_log("sendTransferNotification: Email result = " . json_encode($emailResult));
                            } else {
                                error_log("sendTransferNotification: PM has no email - skipping");
                            }
                        } else {
                            error_log("sendTransferNotification: FROM Project has no PM assigned");
                        }
                    } else {
                        error_log("sendTransferNotification: Status is '{$transfer['status']}' - not Pending Verification, skipping email");
                    }
                    // Note: For Finance/Asset Director initiated transfers, status is 'Received' (completed)
                    // No email needed on creation as they completed it themselves
                    break;

                case 'verified':
                    $title = 'Transfer Request Verified';
                    $message = "Transfer request for {$assetName} ({$assetRef}) has been verified and is awaiting approval.";

                    // Notify approvers (Finance Director, Asset Director)
                    $approvers = $userModel->getUsersByRole(['Finance Director', 'Asset Director']);
                    foreach ($approvers as $approver) {
                        $recipients[] = $approver['id'];

                        // Send email with one-click approval link
                        $emailTemplates->sendApprovalRequest($transfer, $approver);
                    }

                    // Notify initiator
                    if ($transfer['initiated_by']) {
                        $recipients[] = $transfer['initiated_by'];
                    }
                    break;

                case 'approved':
                    $title = 'Transfer Request Approved';
                    $message = "Transfer request for {$assetName} ({$assetRef}) has been approved and is ready for dispatch.";

                    // Notify FROM Project Manager for dispatch
                    $fromProjectPM = $projectModel->find($transfer['from_project'])['project_manager_id'] ?? null;
                    if ($fromProjectPM) {
                        $recipients[] = $fromProjectPM;

                        // Send email with one-click dispatch link
                        $pmUser = $userModel->getUserWithRole($fromProjectPM);
                        if ($pmUser && !empty($pmUser['email'])) {
                            $emailTemplates->sendDispatchRequest($transfer, $pmUser);
                        }
                    }

                    // Notify initiator
                    if ($transfer['initiated_by']) {
                        $recipients[] = $transfer['initiated_by'];
                    }
                    break;

                case 'dispatched':
                    $title = 'Transfer Dispatched - Asset In Transit';
                    $message = "Transfer for {$assetName} ({$assetRef}) has been dispatched from {$fromProject} and is now in transit to {$toProject}.";

                    // Notify TO Project Manager to receive
                    $toProjectPM = $projectModel->find($transfer['to_project'])['project_manager_id'] ?? null;
                    if ($toProjectPM) {
                        $recipients[] = $toProjectPM;

                        // Send email with one-click receive link
                        $pmUser = $userModel->getUserWithRole($toProjectPM);
                        if ($pmUser && !empty($pmUser['email'])) {
                            $emailTemplates->sendReceiveRequest($transfer, $pmUser);
                        }
                    }

                    // Notify initiator
                    if ($transfer['initiated_by']) {
                        $recipients[] = $transfer['initiated_by'];
                    }
                    break;

                case 'completed':
                    $title = 'Transfer Completed Successfully';
                    $message = "Transfer for {$assetName} ({$assetRef}) has been completed. Asset is now at {$toProject}.";
                    $type = 'success';

                    // Notify initiator
                    if ($transfer['initiated_by']) {
                        $recipients[] = $transfer['initiated_by'];
                    }

                    // Notify FROM Project Manager
                    $fromProjectPM = $projectModel->find($transfer['from_project'])['project_manager_id'] ?? null;
                    if ($fromProjectPM) {
                        $recipients[] = $fromProjectPM;
                    }

                    // Notify approvers
                    if ($transfer['approved_by']) {
                        $recipients[] = $transfer['approved_by'];
                    }
                    break;

                case 'canceled':
                    $title = 'Transfer Request Canceled';
                    $message = "Transfer request for {$assetName} ({$assetRef}) from {$fromProject} to {$toProject} has been canceled.";
                    $type = 'warning';

                    // Notify all involved parties
                    if ($transfer['initiated_by']) {
                        $recipients[] = $transfer['initiated_by'];
                    }
                    if ($transfer['verified_by']) {
                        $recipients[] = $transfer['verified_by'];
                    }
                    if ($transfer['approved_by']) {
                        $recipients[] = $transfer['approved_by'];
                    }
                    break;
            }

            // Remove duplicates and send notifications
            $recipients = array_unique(array_filter($recipients));

            if (!empty($recipients)) {
                $notificationModel->notifyMultipleUsers(
                    $recipients,
                    $title,
                    $message,
                    $type,
                    $transferUrl,
                    'transfer',
                    $transferId
                );
            }

            return true;
        } catch (Exception $e) {
            error_log("Transfer notification error: " . $e->getMessage());
            return false;
        }
    }
}
?>
