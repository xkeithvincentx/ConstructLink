<?php
/**
 * ConstructLink™ Withdrawal Model - FIXED VERSION
 * Handles asset withdrawal and release operations
 */

class WithdrawalModel extends BaseModel {
    protected $table = 'withdrawals';
    protected $fillable = [
        'asset_id', 'project_id', 'purpose', 'withdrawn_by', 'receiver_name', 
        'quantity', 'unit', 'expected_return', 'actual_return', 'status', 'notes'
    ];
    
    /**
     * Create withdrawal request with proper validation
     */
    public function createWithdrawal($data) {
        try {
            $validation = $this->validate($data, [
                'asset_id' => 'required|integer',
                'project_id' => 'required|integer',
                'purpose' => 'required|max:500',
                'receiver_name' => 'required|max:100',
                'withdrawn_by' => 'required|integer',
                'quantity' => 'required|integer|min:1',
                'unit' => 'max:50'
            ]);
            
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            $this->beginTransaction();
            
            // Check if asset exists and get category info
            $assetModel = new AssetModel();
            $sql = "SELECT a.*, c.is_consumable 
                    FROM assets a 
                    LEFT JOIN categories c ON a.category_id = c.id 
                    WHERE a.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$data['asset_id']]);
            $asset = $stmt->fetch();
            
            if (!$asset) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            // Check availability based on asset type
            if ($asset['is_consumable']) {
                // For consumables, check available quantity
                if ($asset['available_quantity'] < $data['quantity']) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Insufficient quantity available. Available: ' . $asset['available_quantity'] . ', Requested: ' . $data['quantity']];
                }
            } else {
                // For non-consumables, check status
                if ($asset['status'] !== 'available') {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Asset is not available for withdrawal'];
                }
                
                // For non-consumables, quantity must be 1
                if ($data['quantity'] != 1) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Non-consumable assets can only be withdrawn in quantity of 1'];
                }
            }
            
            // Verify asset belongs to the specified project
            if ($asset['project_id'] != $data['project_id']) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset does not belong to the specified project'];
            }
            
            // Check for existing pending/released withdrawals for non-consumable assets only
            if (!$asset['is_consumable']) {
                $existingWithdrawal = $this->findFirst([
                    'asset_id' => $data['asset_id'],
                    'status' => ['Pending Verification', 'Pending Approval', 'Approved', 'Released']
                ]);
                
                if ($existingWithdrawal) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Asset already has a pending or active withdrawal'];
                }
            }
            
            // Set default status and validate dates
            $data['status'] = 'Pending Verification';
            
            // Set default unit if not provided
            if (empty($data['unit'])) {
                $data['unit'] = 'pcs';
            }
            
            if (!empty($data['expected_return'])) {
                if (strtotime($data['expected_return']) <= time()) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Expected return date must be in the future'];
                }
            }
            
            // Create withdrawal record
            $withdrawal = $this->create($data);
            
            if (!$withdrawal) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to create withdrawal request'];
            }
            
            // Log activity
            $this->logActivity('withdrawal_created', 'Withdrawal request created', 'withdrawals', $withdrawal['id']);
            
            $this->commit();
            
            return ['success' => true, 'withdrawal' => $withdrawal];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Withdrawal creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create withdrawal request'];
        }
    }
    
    /**
     * Verify withdrawal (MVA workflow)
     */
    public function verifyWithdrawal($withdrawalId, $verifiedBy, $notes = null) {
        try {
            $this->beginTransaction();
            $withdrawal = $this->getWithdrawalWithDetails($withdrawalId);
            if (!$withdrawal) {
                $this->rollback();
                return ['success' => false, 'message' => 'Withdrawal not found'];
            }
            if ($withdrawal['status'] !== 'Pending Verification') {
                $this->rollback();
                return ['success' => false, 'message' => 'Withdrawal is not in pending verification status'];
            }
            $updateResult = $this->update($withdrawalId, [
                'status' => 'Pending Approval',
                'verified_by' => $verifiedBy,
                'verification_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ]);
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update withdrawal status'];
            }
            $this->commit();
            return ['success' => true, 'withdrawal' => $updateResult, 'message' => 'Withdrawal verified successfully'];
        } catch (Exception $e) {
            $this->rollback();
            error_log("Withdrawal verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to verify withdrawal'];
        }
    }

    /**
     * Approve withdrawal (MVA workflow)
     */
    public function approveWithdrawal($withdrawalId, $approvedBy, $notes = null) {
        try {
            $this->beginTransaction();
            $withdrawal = $this->getWithdrawalWithDetails($withdrawalId);
            if (!$withdrawal) {
                $this->rollback();
                return ['success' => false, 'message' => 'Withdrawal not found'];
            }
            if ($withdrawal['status'] !== 'Pending Approval') {
                $this->rollback();
                return ['success' => false, 'message' => 'Withdrawal is not in pending approval status'];
            }
            $updateResult = $this->update($withdrawalId, [
                'status' => 'Approved',
                'approved_by' => $approvedBy,
                'approval_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ]);
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update withdrawal status'];
            }
            $this->commit();
            return ['success' => true, 'withdrawal' => $updateResult, 'message' => 'Withdrawal approved successfully'];
        } catch (Exception $e) {
            $this->rollback();
            error_log("Withdrawal approval error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve withdrawal'];
        }
    }

    /**
     * Release withdrawal with form data
     */
    public function releaseWithdrawal($withdrawalId, $data) {
        try {
            $this->beginTransaction();
            
            $withdrawal = $this->getWithdrawalWithDetails($withdrawalId);
            if (!$withdrawal) {
                $this->rollback();
                return ['success' => false, 'message' => 'Withdrawal not found'];
            }
            
            if ($withdrawal['status'] !== 'Approved') {
                $this->rollback();
                return ['success' => false, 'message' => 'Withdrawal is not in approved status'];
            }
            
            // Update withdrawal with form data
            $updateData = [
                'status' => 'Released',
                'authorization_level' => $data['authorization_level'],
                'asset_condition' => $data['asset_condition'],
                'receiver_verification' => $data['receiver_verification'],
                'release_notes' => $data['release_notes'],
                'emergency_reason' => $data['emergency_reason'],
                'released_by' => $data['released_by'],
                'released_at' => date('Y-m-d H:i:s')
            ];
            
            $updateResult = $this->update($withdrawalId, $updateData);
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update withdrawal'];
            }
            
            // Call existing releaseAsset method for status updates
            $releaseResult = $this->releaseAsset($withdrawalId, $data['released_by'], $data['release_notes']);
            if (!$releaseResult['success']) {
                $this->rollback();
                return $releaseResult;
            }
            
            $this->commit();
            return ['success' => true, 'message' => 'Withdrawal released successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("WithdrawalModel::releaseWithdrawal error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to release withdrawal'];
        }
    }
    
    /**
     * Release asset (MVA workflow)
     */
    public function releaseAsset($withdrawalId, $releasedBy, $releaseNotes = null) {
        try {
            $this->beginTransaction();
            
            // Get withdrawal details
            $withdrawal = $this->getWithdrawalWithDetails($withdrawalId);
            if (!$withdrawal) {
                $this->rollback();
                return ['success' => false, 'message' => 'Withdrawal not found'];
            }
            
            if ($withdrawal['status'] !== 'Approved') {
                $this->rollback();
                return ['success' => false, 'message' => 'Withdrawal must be approved before release'];
            }
            
            // Update withdrawal status
            $updateResult = $this->update($withdrawalId, [
                'status' => 'Released',
                'notes' => $releaseNotes
            ]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update withdrawal status'];
            }
            
            // Create release record directly with SQL to avoid BaseModel auto-fields
            $sql = "INSERT INTO releases (withdrawal_id, released_by, notes, released_at) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $releaseResult = $stmt->execute([
                $withdrawalId,
                $releasedBy,
                $releaseNotes,
                date('Y-m-d H:i:s')
            ]);
            
            if (!$releaseResult) {
                $this->rollback();
                error_log("Create error: " . implode(", ", $stmt->errorInfo()));
                return ['success' => false, 'message' => 'Failed to create release record'];
            }
            
            // Update asset based on consumable type
            $assetModel = new AssetModel();
            $sql = "SELECT a.*, c.is_consumable 
                    FROM assets a 
                    LEFT JOIN categories c ON a.category_id = c.id 
                    WHERE a.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$withdrawal['asset_id']]);
            $asset = $stmt->fetch();
            
            if ($asset['is_consumable']) {
                // For consumables, deduct the quantity
                $newAvailableQuantity = $asset['available_quantity'] - $withdrawal['quantity'];
                
                // Check if we have enough quantity
                if ($newAvailableQuantity < 0) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Insufficient quantity available for release'];
                }
                
                $assetUpdateResult = $assetModel->update($withdrawal['asset_id'], [
                    'available_quantity' => $newAvailableQuantity
                ]);
                
                if (!$assetUpdateResult) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Failed to update asset quantity'];
                }
            } else {
                // For non-consumables, update status to in_use
                $assetUpdateResult = $assetModel->update($withdrawal['asset_id'], [
                    'status' => 'in_use'
                ]);
                
                if (!$assetUpdateResult) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Failed to update asset status'];
                }
            }
            
            // Log activity
            $this->logActivity('asset_released', 'Asset released for withdrawal', 'withdrawals', $withdrawalId);
            
            $this->commit();
            
            return [
                'success' => true, 
                'withdrawal' => $updateResult,
                'message' => 'Asset released successfully'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Asset release error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to release asset'];
        }
    }
    
    /**
     * Return asset (MVA workflow)
     */
    public function returnAsset($withdrawalId, $returnedBy, $returnNotes = null) {
        try {
            $this->beginTransaction();
            
            // Get withdrawal details
            $withdrawal = $this->getWithdrawalWithDetails($withdrawalId);
            if (!$withdrawal) {
                $this->rollback();
                return ['success' => false, 'message' => 'Withdrawal not found'];
            }
            
            if ($withdrawal['status'] !== 'Released') {
                $this->rollback();
                return ['success' => false, 'message' => 'Withdrawal is not in released status'];
            }
            
            // Update withdrawal with return date
            $updateData = [
                'status' => 'Returned',
                'actual_return' => date('Y-m-d'),
                'notes' => $returnNotes ? ($withdrawal['notes'] ? $withdrawal['notes'] . "\n\nReturn Notes: " . $returnNotes : "Return Notes: " . $returnNotes) : $withdrawal['notes']
            ];
            
            $updateResult = $this->update($withdrawalId, $updateData);
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update withdrawal status'];
            }
            
            // Update asset based on consumable type
            $assetModel = new AssetModel();
            $sql = "SELECT a.*, c.is_consumable 
                    FROM assets a 
                    LEFT JOIN categories c ON a.category_id = c.id 
                    WHERE a.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$withdrawal['asset_id']]);
            $asset = $stmt->fetch();
            
            if ($asset['is_consumable']) {
                // For consumables, restore the quantity (rare case, but possible)
                $newAvailableQuantity = $asset['available_quantity'] + $withdrawal['quantity'];
                
                $assetUpdateResult = $assetModel->update($withdrawal['asset_id'], [
                    'available_quantity' => $newAvailableQuantity
                ]);
                
                if (!$assetUpdateResult) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Failed to update asset quantity'];
                }
            } else {
                // For non-consumables, update status back to available
                $assetUpdateResult = $assetModel->update($withdrawal['asset_id'], [
                    'status' => 'available'
                ]);
                
                if (!$assetUpdateResult) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Failed to update asset status'];
                }
            }
            
            // Log activity
            $this->logActivity('asset_returned', 'Asset returned from withdrawal', 'withdrawals', $withdrawalId);
            
            $this->commit();
            
            return [
                'success' => true, 
                'withdrawal' => $updateResult,
                'message' => 'Asset returned successfully'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Asset return error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to return asset'];
        }
    }
    
    /**
     * Cancel withdrawal
     */
    public function cancelWithdrawal($withdrawalId, $reason = null) {
        try {
            $withdrawal = $this->find($withdrawalId);
            if (!$withdrawal) {
                return ['success' => false, 'message' => 'Withdrawal not found'];
            }
            
            if (!in_array($withdrawal['status'], ['Pending Verification', 'Pending Approval', 'Approved', 'Released'])) {
                return ['success' => false, 'message' => 'Cannot cancel withdrawal in current status'];
            }
            
            $this->beginTransaction();
            
            $oldStatus = $withdrawal['status'];
            
            // Update withdrawal status
            $updateResult = $this->update($withdrawalId, [
                'status' => 'Canceled',
                'notes' => $reason
            ]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to cancel withdrawal'];
            }
            
            // If asset was released, restore availability
            if ($oldStatus === 'Released') {
                $assetModel = new AssetModel();
                $sql = "SELECT a.*, c.is_consumable 
                        FROM assets a 
                        LEFT JOIN categories c ON a.category_id = c.id 
                        WHERE a.id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$withdrawal['asset_id']]);
                $asset = $stmt->fetch();
                
                if ($asset['is_consumable']) {
                    // For consumables, restore the quantity
                    $newAvailableQuantity = $asset['available_quantity'] + $withdrawal['quantity'];
                    
                    $assetUpdateResult = $assetModel->update($withdrawal['asset_id'], [
                        'available_quantity' => $newAvailableQuantity
                    ]);
                    
                    if (!$assetUpdateResult) {
                        $this->rollback();
                        return ['success' => false, 'message' => 'Failed to update asset quantity'];
                    }
                } else {
                    // For non-consumables, update status back to available
                    $assetUpdateResult = $assetModel->update($withdrawal['asset_id'], [
                        'status' => 'available'
                    ]);
                    
                    if (!$assetUpdateResult) {
                        $this->rollback();
                        return ['success' => false, 'message' => 'Failed to update asset status'];
                    }
                }
            }
            
            // Log activity
            $this->logActivity('withdrawal_canceled', 'Withdrawal request canceled', 'withdrawals', $withdrawalId);
            
            $this->commit();
            
            return [
                'success' => true, 
                'withdrawal' => $updateResult,
                'message' => 'Withdrawal canceled successfully'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Withdrawal cancellation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to cancel withdrawal'];
        }
    }
    
    /**
     * Get withdrawal with detailed information
     */
    public function getWithdrawalWithDetails($id) {
        try {
            $sql = "
                SELECT w.*, 
                       a.ref as asset_ref, a.name as asset_name, a.status as asset_status,
                       a.quantity as asset_total_quantity, a.available_quantity as asset_available_quantity,
                       c.name as category_name, c.is_consumable,
                       p.name as project_name, p.location as project_location,
                       u.full_name as withdrawn_by_name,
                       r.released_by, r.notes as release_notes, r.released_at,
                       ur.full_name as released_by_name
                FROM withdrawals w
                LEFT JOIN assets a ON w.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON w.project_id = p.id
                LEFT JOIN users u ON w.withdrawn_by = u.id
                LEFT JOIN releases r ON w.id = r.withdrawal_id
                LEFT JOIN users ur ON r.released_by = ur.id
                WHERE w.id = ?
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Get withdrawal with details error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get withdrawals with filters and pagination
     */
    public function getWithdrawalsWithFilters($filters = [], $page = 1, $perPage = 20) {
        try {
            $conditions = [];
            $params = [];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $conditions[] = "w.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['project_id'])) {
                $conditions[] = "w.project_id = ?";
                $params[] = $filters['project_id'];
            }
            
            if (!empty($filters['asset_id'])) {
                $conditions[] = "w.asset_id = ?";
                $params[] = $filters['asset_id'];
            }
            
            if (!empty($filters['withdrawn_by'])) {
                $conditions[] = "w.withdrawn_by = ?";
                $params[] = $filters['withdrawn_by'];
            }
            
            if (!empty($filters['date_from'])) {
                $conditions[] = "DATE(w.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $conditions[] = "DATE(w.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['search'])) {
                $conditions[] = "(a.ref LIKE ? OR a.name LIKE ? OR w.receiver_name LIKE ? OR w.purpose LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            // Count total records
            $countSql = "
                SELECT COUNT(*) 
                FROM withdrawals w
                LEFT JOIN assets a ON w.asset_id = a.id
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();
            
            // Get paginated data
            $offset = ($page - 1) * $perPage;
            $orderBy = $filters['order_by'] ?? 'w.created_at DESC';
            
            $dataSql = "
                SELECT w.*, 
                       a.ref as asset_ref, a.name as asset_name,
                       c.name as category_name,
                       p.name as project_name,
                       u.full_name as withdrawn_by_name
                FROM withdrawals w
                LEFT JOIN assets a ON w.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON w.project_id = p.id
                LEFT JOIN users u ON w.withdrawn_by = u.id
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
            error_log("Get withdrawals with filters error: " . $e->getMessage());
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
     * Get withdrawal statistics
     */
    public function getWithdrawalStatistics($projectId = null, $dateFrom = null, $dateTo = null) {
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
                    COUNT(*) as total_withdrawals,
                    SUM(CASE WHEN status = 'Pending Verification' THEN 1 ELSE 0 END) as pending_verification,
                    SUM(CASE WHEN status = 'Pending Approval' THEN 1 ELSE 0 END) as pending_approval,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Released' THEN 1 ELSE 0 END) as released,
                    SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned,
                    SUM(CASE WHEN status = 'Canceled' THEN 1 ELSE 0 END) as canceled,
                    SUM(CASE WHEN status = 'Released' AND expected_return IS NOT NULL AND expected_return < CURDATE() THEN 1 ELSE 0 END) as overdue
                FROM withdrawals 
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result ?: [
                'total_withdrawals' => 0,
                'pending_verification' => 0,
                'pending_approval' => 0,
                'approved' => 0,
                'released' => 0,
                'returned' => 0,
                'canceled' => 0,
                'overdue' => 0
            ];
            
        } catch (Exception $e) {
            error_log("Get withdrawal statistics error: " . $e->getMessage());
            return [
                'total_withdrawals' => 0,
                'pending_verification' => 0,
                'pending_approval' => 0,
                'approved' => 0,
                'released' => 0,
                'returned' => 0,
                'canceled' => 0,
                'overdue' => 0
            ];
        }
    }
    
    /**
     * Get overdue withdrawals
     */
    public function getOverdueWithdrawals($projectId = null) {
        try {
            $conditions = [
                "w.status = 'Released'",
                "w.expected_return IS NOT NULL",
                "w.expected_return < CURDATE()"
            ];
            $params = [];
            
            if ($projectId) {
                $conditions[] = "w.project_id = ?";
                $params[] = $projectId;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT w.*, 
                       a.ref as asset_ref, a.name as asset_name,
                       p.name as project_name,
                       u.full_name as withdrawn_by_name,
                       DATEDIFF(CURDATE(), w.expected_return) as days_overdue
                FROM withdrawals w
                LEFT JOIN assets a ON w.asset_id = a.id
                LEFT JOIN projects p ON w.project_id = p.id
                LEFT JOIN users u ON w.withdrawn_by = u.id
                {$whereClause}
                ORDER BY w.expected_return ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get overdue withdrawals error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get withdrawal report data
     */
    public function getWithdrawalReport($dateFrom, $dateTo, $projectId = null, $status = null) {
        try {
            $conditions = ["DATE(w.created_at) BETWEEN ? AND ?"];
            $params = [$dateFrom, $dateTo];
            
            if ($projectId) {
                $conditions[] = "w.project_id = ?";
                $params[] = $projectId;
            }
            
            if ($status) {
                $conditions[] = "w.status = ?";
                $params[] = $status;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT w.*, 
                       a.ref as asset_ref, a.name as asset_name,
                       c.name as category_name,
                       p.name as project_name,
                       u.full_name as withdrawn_by_name,
                       r.released_by, r.released_at,
                       ur.full_name as released_by_name
                FROM withdrawals w
                LEFT JOIN assets a ON w.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON w.project_id = p.id
                LEFT JOIN users u ON w.withdrawn_by = u.id
                LEFT JOIN releases r ON w.id = r.withdrawal_id
                LEFT JOIN users ur ON r.released_by = ur.id
                {$whereClause}
                ORDER BY w.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get withdrawal report error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get withdrawal statistics for dashboard API
     */
    public function getWithdrawalStats() {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'Pending Verification' THEN 1 END) as pending_verification,
                    COUNT(CASE WHEN status = 'Pending Approval' THEN 1 END) as pending_approval,
                    COUNT(CASE WHEN status = 'Approved' THEN 1 END) as approved,
                    COUNT(CASE WHEN status = 'Released' THEN 1 END) as released,
                    COUNT(CASE WHEN status = 'Returned' THEN 1 END) as returned,
                    COUNT(CASE WHEN status = 'Canceled' THEN 1 END) as canceled,
                    COUNT(CASE WHEN status = 'Released' AND expected_return IS NOT NULL AND expected_return < CURDATE() THEN 1 END) as overdue
                FROM withdrawals
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total' => (int)$result['total'],
                'pending_verification' => (int)$result['pending_verification'],
                'pending_approval' => (int)$result['pending_approval'],
                'approved' => (int)$result['approved'],
                'released' => (int)$result['released'],
                'returned' => (int)$result['returned'],
                'canceled' => (int)$result['canceled'],
                'overdue' => (int)$result['overdue']
            ];
            
        } catch (Exception $e) {
            error_log("WithdrawalModel::getWithdrawalStats error: " . $e->getMessage());
            return [
                'total' => 0,
                'pending_verification' => 0,
                'pending_approval' => 0,
                'approved' => 0,
                'released' => 0,
                'returned' => 0,
                'canceled' => 0,
                'overdue' => 0
            ];
        }
    }
    
    /**
     * Get asset withdrawal history (called by AssetController)
     */
    public function getAssetWithdrawalHistory($assetId) {
        try {
            $sql = "
                SELECT w.*, 
                       u.full_name as withdrawn_by_name,
                       p.name as project_name,
                       r.released_by, r.released_at, r.notes as release_notes,
                       ur.full_name as released_by_name
                FROM withdrawals w
                LEFT JOIN users u ON w.withdrawn_by = u.id
                LEFT JOIN projects p ON w.project_id = p.id
                LEFT JOIN releases r ON w.id = r.withdrawal_id
                LEFT JOIN users ur ON r.released_by = ur.id
                WHERE w.asset_id = ?
                ORDER BY w.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get asset withdrawal history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get available assets for withdrawal by project
     */
    public function getAvailableAssetsForWithdrawal($projectId) {
        try {
            $sql = "
                SELECT a.*, c.name as category_name, c.is_consumable
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                WHERE a.project_id = ? 
                AND (
                    (c.is_consumable = 1 AND a.available_quantity > 0) 
                    OR 
                    (c.is_consumable = 0 AND a.status = 'available')
                )
                ORDER BY c.is_consumable DESC, a.name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$projectId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get available assets for withdrawal error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get asset details for withdrawal form
     */
    public function getAssetForWithdrawal($assetId) {
        try {
            $sql = "
                SELECT a.*, c.name as category_name, c.is_consumable
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                WHERE a.id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Get asset for withdrawal error: " . $e->getMessage());
            return false;
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

/**
 * Release Model for tracking asset releases
 */
class ReleaseModel extends BaseModel {
    protected $table = 'releases';
    protected $fillable = ['withdrawal_id', 'released_by', 'release_doc', 'notes', 'released_at'];
}
?>