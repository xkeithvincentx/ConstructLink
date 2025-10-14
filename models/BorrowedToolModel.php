<?php
/**
 * ConstructLink™ Borrowed Tool Model
 * Handles borrowed tool data operations
 */

class BorrowedToolModel extends BaseModel {
    protected $table = 'borrowed_tools';
    protected $fillable = [
        'asset_id', 'borrower_name', 'borrower_contact', 'expected_return',
        'actual_return', 'issued_by', 'purpose', 'condition_out', 'condition_in', 'status',
        // Batch support fields
        'batch_id', 'quantity', 'quantity_returned', 'line_notes', 'condition_returned',
        // MVA workflow fields
        'verified_by', 'verification_date', 'approved_by', 'approval_date',
        'borrowed_by', 'borrowed_date', 'returned_by', 'return_date', 'canceled_by', 'cancellation_date', 'cancellation_reason'
    ];
    
    /**
     * Create borrowed tool request (Maker step)
     */
    public function createBorrowedTool($data) {
        $validation = $this->validate($data, [
            'asset_id' => 'required|integer',
            'borrower_name' => 'required|max:100',
            'expected_return' => 'required|date',
            'issued_by' => 'required|integer'
        ]);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        if (strtotime($data['expected_return']) < strtotime(date('Y-m-d'))) {
            return ['success' => false, 'message' => 'Expected return date cannot be in the past'];
        }
        try {
            $this->db->beginTransaction();
            $assetModel = new AssetModel();
            $asset = $assetModel->find($data['asset_id']);
            if (!$asset) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Asset not found'];
            }
            if ($asset['status'] !== 'available') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Asset is not available for borrowing'];
            }
            // Set initial status for MVA workflow
            $borrowData = [
                'asset_id' => $data['asset_id'],
                'borrower_name' => $data['borrower_name'],
                'borrower_contact' => $data['borrower_contact'] ?? null,
                'expected_return' => $data['expected_return'],
                'issued_by' => $data['issued_by'],
                'purpose' => $data['purpose'] ?? null,
                'condition_out' => $data['condition_out'] ?? null,
                'status' => 'Pending Verification',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $borrowedTool = $this->create($borrowData);
            if (!$borrowedTool) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to create borrowed tool request'];
            }
            $this->logActivity('borrow_tool_request', "Tool borrow request created: {$asset['name']} by {$data['borrower_name']}", 'borrowed_tools', $borrowedTool['id']);
            $this->db->commit();
            return ['success' => true, 'borrowed_tool' => $borrowedTool];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Borrowed tool creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create borrowed tool request'];
        }
    }

    /**
     * Verify borrowed tool request (Verifier step)
     */
    public function verifyBorrowedTool($borrowId, $verifiedBy, $notes = null) {
        try {
            $this->db->beginTransaction();
            $borrowedTool = $this->find($borrowId);
            if (!$borrowedTool) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Borrowed tool not found'];
            }
            if ($borrowedTool['status'] !== 'Pending Verification') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tool is not in pending verification status'];
            }
            $updateData = [
                'status' => 'Pending Approval',
                'verified_by' => $verifiedBy,
                'verification_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ];
            $updated = $this->update($borrowId, $updateData);
            if (!$updated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to verify borrowed tool request'];
            }
            $this->logActivity('verify_borrow_tool', "Tool borrow request verified", 'borrowed_tools', $borrowId);
            $this->db->commit();
            return ['success' => true, 'message' => 'Tool borrow request verified'];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Borrowed tool verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to verify borrowed tool request'];
        }
    }

    /**
     * Approve borrowed tool request (Authorizer step)
     */
    public function approveBorrowedTool($borrowId, $approvedBy, $notes = null) {
        try {
            $this->db->beginTransaction();
            $borrowedTool = $this->find($borrowId);
            if (!$borrowedTool) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Borrowed tool not found'];
            }
            if ($borrowedTool['status'] !== 'Pending Approval') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tool is not in pending approval status'];
            }
            $updateData = [
                'status' => 'Approved',
                'approved_by' => $approvedBy,
                'approval_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ];
            $updated = $this->update($borrowId, $updateData);
            if (!$updated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to approve borrowed tool request'];
            }
            $this->logActivity('approve_borrow_tool', "Tool borrow request approved", 'borrowed_tools', $borrowId);
            $this->db->commit();
            return ['success' => true, 'message' => 'Tool borrow request approved'];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Borrowed tool approval error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve borrowed tool request'];
        }
    }

    /**
     * Mark as borrowed (after approval)
     */
    public function markAsBorrowed($borrowId, $borrowedBy) {
        try {
            $this->db->beginTransaction();
            $borrowedTool = $this->find($borrowId);
            if (!$borrowedTool) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Borrowed tool not found'];
            }
            if ($borrowedTool['status'] !== 'Approved') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tool is not in approved status'];
            }
            $updateData = [
                'status' => 'Borrowed',
                'borrowed_by' => $borrowedBy,
                'borrowed_date' => date('Y-m-d H:i:s')
            ];
            $updated = $this->update($borrowId, $updateData);
            if (!$updated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to mark as borrowed'];
            }
            // Update asset status to borrowed
            $assetModel = new AssetModel();
            $assetUpdated = $assetModel->update($borrowedTool['asset_id'], ['status' => 'borrowed']);
            if (!$assetUpdated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to update asset status'];
            }
            $this->logActivity('mark_borrowed', "Tool marked as borrowed", 'borrowed_tools', $borrowId);
            $this->db->commit();
            return ['success' => true, 'message' => 'Tool marked as borrowed'];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Mark as borrowed error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to mark as borrowed'];
        }
    }

    /**
     * Mark as borrowed (after approval)
     */
    public function borrowTool($borrowId, $borrowedBy, $notes = null) {
        try {
            $this->db->beginTransaction();
            $borrowedTool = $this->find($borrowId);
            if (!$borrowedTool) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Borrowed tool not found'];
            }
            if ($borrowedTool['status'] !== 'Approved') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tool is not in approved status'];
            }
            
            // Update asset status to borrowed
            $assetModel = new AssetModel();
            $asset = $assetModel->find($borrowedTool['asset_id']);
            if (!$asset) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Asset not found'];
            }
            if ($asset['status'] !== 'available') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Asset is not available for borrowing'];
            }
            
            $updateData = [
                'status' => 'Borrowed',
                'borrowed_by' => $borrowedBy,
                'borrowed_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ];
            $updated = $this->update($borrowId, $updateData);
            if (!$updated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to mark as borrowed'];
            }
            
            // Update asset status to borrowed
            $assetUpdated = $assetModel->update($borrowedTool['asset_id'], ['status' => 'borrowed']);
            if (!$assetUpdated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to update asset status'];
            }
            
            $this->logActivity('borrow_tool', "Tool borrowed: {$asset['name']} by {$borrowedTool['borrower_name']}", 'borrowed_tools', $borrowId);
            $this->db->commit();
            return ['success' => true, 'message' => 'Tool marked as borrowed successfully'];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Borrow tool error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to mark tool as borrowed'];
        }
    }

    /**
     * Return borrowed tool (Receiver step)
     */
    public function returnBorrowedTool($borrowId, $returnedBy, $conditionIn, $returnNotes = null) {
        try {
            $this->db->beginTransaction();
            $borrowedTool = $this->getBorrowedToolWithDetails($borrowId);
            if (!$borrowedTool) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Borrowed tool not found'];
            }
            if (!in_array($borrowedTool['status'], ['Borrowed', 'Overdue'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tool is not currently borrowed or overdue'];
            }
            $updateData = [
                'actual_return' => date('Y-m-d'),
                'condition_in' => $conditionIn,
                'status' => 'Returned',
                'returned_by' => $returnedBy,
                'return_date' => date('Y-m-d H:i:s'),
                'notes' => $returnNotes
            ];
            $updated = $this->update($borrowId, $updateData);
            if (!$updated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to update borrowed tool record'];
            }
            // Update asset status back to available
            $assetModel = new AssetModel();
            $assetUpdated = $assetModel->update($borrowedTool['asset_id'], ['status' => 'available']);
            if (!$assetUpdated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to update asset status'];
            }
            $this->logActivity('return_tool', "Tool returned: {$borrowedTool['asset_name']} by {$borrowedTool['borrower_name']}", 'borrowed_tools', $borrowId);
            $this->db->commit();
            return ['success' => true, 'message' => 'Tool returned successfully'];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Tool return error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to return tool'];
        }
    }

    /**
     * Cancel borrowed tool request (any stage before Borrowed)
     */
    public function cancelBorrowedTool($borrowId, $canceledBy, $reason = null) {
        try {
            $this->db->beginTransaction();
            $borrowedTool = $this->find($borrowId);
            if (!$borrowedTool) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Borrowed tool not found'];
            }
            if (!in_array($borrowedTool['status'], ['Pending Verification', 'Pending Approval', 'Approved'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Cannot cancel at this stage'];
            }
            $updateData = [
                'status' => 'Canceled',
                'canceled_by' => $canceledBy,
                'cancellation_date' => date('Y-m-d H:i:s'),
                'cancellation_reason' => $reason
            ];
            $updated = $this->update($borrowId, $updateData);
            if (!$updated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to cancel borrowed tool request'];
            }
            $this->logActivity('cancel_borrow_tool', "Tool borrow request canceled", 'borrowed_tools', $borrowId);
            $this->db->commit();
            return ['success' => true, 'message' => 'Borrowed tool request canceled'];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Cancel borrowed tool error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to cancel borrowed tool request'];
        }
    }
    
    /**
     * Extend borrowing period
     */
    public function extendBorrowingPeriod($borrowId, $newExpectedReturn, $reason) {
        try {
            $this->db->beginTransaction();
            
            // Get borrowed tool details
            $borrowedTool = $this->find($borrowId);
            
            if (!$borrowedTool) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Borrowed tool not found'];
            }
            
            if (!in_array($borrowedTool['status'], ['borrowed', 'overdue'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tool is not currently borrowed or overdue'];
            }
            
            // Validate new date is after current expected return
            if (strtotime($newExpectedReturn) <= strtotime($borrowedTool['expected_return'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'New return date must be after current expected return date'];
            }
            
            // Update borrowed tool record
            $updated = $this->update($borrowId, ['expected_return' => $newExpectedReturn]);
            
            if (!$updated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to extend borrowing period'];
            }
            
            // Log activity
            $this->logActivity('extend_borrowing', "Borrowing extended to {$newExpectedReturn}. Reason: {$reason}", 'borrowed_tools', $borrowId);
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Borrowing period extended successfully'];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Borrowing extension error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to extend borrowing period'];
        }
    }
    
    /**
     * Get borrowed tools with filters and pagination
     */
    public function getBorrowedToolsWithFilters($filters = [], $page = 1, $perPage = 20) {
        $conditions = [];
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'overdue' || $filters['status'] === 'Overdue') {
                $conditions[] = "bt.status = 'Borrowed' AND bt.expected_return < CURDATE()";
            } else {
                $conditions[] = "bt.status = ?";
                $params[] = $filters['status'];
            }
        }
        // No else clause - show all statuses by default (including Canceled and Returned)
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(bt.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(bt.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(a.name LIKE ? OR a.ref LIKE ? OR bt.borrower_name LIKE ? OR bt.purpose LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        // Filter by project if specified
        if (!empty($filters['project_id'])) {
            $conditions[] = "a.project_id = ?";
            $params[] = $filters['project_id'];
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        // Debug: Log the SQL and parameters (remove this after testing)
        error_log("DEBUG - SQL WHERE: " . $whereClause . ", Params: " . json_encode($params));
        
        // Count total records
        $countSql = "
            SELECT COUNT(*) 
            FROM borrowed_tools bt
            INNER JOIN assets a ON bt.asset_id = a.id
            INNER JOIN categories c ON a.category_id = c.id
            INNER JOIN projects p ON a.project_id = p.id
            LEFT JOIN users u ON bt.issued_by = u.id
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get paginated data
        $offset = ($page - 1) * $perPage;
        
        $dataSql = "
            SELECT bt.*,
                   a.name as asset_name,
                   a.ref as asset_ref,
                   c.name as category_name,
                   p.name as project_name,
                   u.full_name as issued_by_name,
                   btb.batch_reference,
                   CASE
                       WHEN bt.status = 'Borrowed' AND bt.expected_return < CURDATE() THEN 'Overdue'
                       ELSE bt.status
                   END as current_status
            FROM borrowed_tools bt
            INNER JOIN assets a ON bt.asset_id = a.id
            INNER JOIN categories c ON a.category_id = c.id
            INNER JOIN projects p ON a.project_id = p.id
            LEFT JOIN users u ON bt.issued_by = u.id
            LEFT JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
            {$whereClause}
            ORDER BY bt.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        
        $stmt = $this->db->prepare($dataSql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        // Debug: Log what data was actually returned
        $recordDetails = array_map(function($record) {
            return "ID:" . $record['id'] . ",Asset:" . $record['asset_id'] . ",Status:" . $record['status'];
        }, $data);
        error_log("DEBUG - Data returned: " . count($data) . " records - " . json_encode($recordDetails) . ", Projects: " . json_encode(array_unique(array_column($data, 'project_name'))));
        
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
     * Get borrowed tool with detailed information
     */
    public function getBorrowedToolWithDetails($id, $projectId = null) {
        $conditions = ["bt.id = ?"];
        $params = [$id];
        
        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }
        
        $whereClause = "WHERE " . implode(" AND ", $conditions);
        
        $sql = "
            SELECT bt.*,
                   a.name as asset_name,
                   a.ref as asset_ref,
                   c.name as category_name,
                   p.name as project_name,
                   u.full_name as issued_by_name
            FROM borrowed_tools bt
            INNER JOIN assets a ON bt.asset_id = a.id
            INNER JOIN categories c ON a.category_id = c.id
            INNER JOIN projects p ON a.project_id = p.id
            LEFT JOIN users u ON bt.issued_by = u.id
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Get borrowed tool with MVA workflow details
     */
    public function getBorrowedToolWithMVADetails($id, $projectId = null) {
        $conditions = ["bt.id = ?"];
        $params = [$id];
        
        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }
        
        $whereClause = "WHERE " . implode(" AND ", $conditions);
        $sql = "
            SELECT bt.*,
                   a.name as asset_name,
                   a.ref as asset_ref,
                   a.acquisition_cost,
                   c.name as category_name,
                   p.name as project_name,
                   u.full_name as issued_by_name,
                   uv.full_name as verified_by_name,
                   ua.full_name as approved_by_name,
                   ub.full_name as borrowed_by_name,
                   ur.full_name as returned_by_name,
                   uc.full_name as canceled_by_name
            FROM borrowed_tools bt
            INNER JOIN assets a ON bt.asset_id = a.id
            INNER JOIN categories c ON a.category_id = c.id
            INNER JOIN projects p ON a.project_id = p.id
            LEFT JOIN users u ON bt.issued_by = u.id
            LEFT JOIN users uv ON bt.verified_by = uv.id
            LEFT JOIN users ua ON bt.approved_by = ua.id
            LEFT JOIN users ub ON bt.borrowed_by = ub.id
            LEFT JOIN users ur ON bt.returned_by = ur.id
            LEFT JOIN users uc ON bt.canceled_by = uc.id
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Determine if tool is critical (requiring full MVA workflow)
     */
    public function isCriticalTool($assetId, $assetCost = null) {
        try {
            if (!$assetCost) {
                $assetModel = new AssetModel();
                $asset = $assetModel->find($assetId);
                $assetCost = $asset['acquisition_cost'] ?? 0;
            }
            
            // Critical tools criteria:
            // 1. High-value assets (>50,000)
            // 2. Specific categories (Equipment, Machinery)
            // 3. Safety-critical items
            
            if ($assetCost > 50000) {
                return true;
            }
            
            // Check if asset is in critical categories
            $db = Database::getInstance()->getConnection();
            $sql = "
                SELECT c.name, c.description
                FROM assets a
                JOIN categories c ON a.category_id = c.id
                WHERE a.id = ?
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute([$assetId]);
            $category = $stmt->fetch();
            
            if ($category) {
                $criticalCategories = ['Equipment', 'Machinery', 'Safety', 'Heavy Equipment'];
                return in_array($category['name'], $criticalCategories);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Critical tool check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get borrowed tool statistics
     */
    public function getBorrowedToolStats($dateFrom = null, $dateTo = null, $projectId = null) {
        $conditions = [];
        $params = [];
        
        if ($dateFrom) {
            $conditions[] = "DATE(bt.created_at) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $conditions[] = "DATE(bt.created_at) <= ?";
            $params[] = $dateTo;
        }
        
        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        $sql = "
            SELECT 
                COUNT(*) as total_borrowed,
                COUNT(CASE WHEN bt.status = 'Borrowed' THEN 1 END) as borrowed,
                COUNT(CASE WHEN bt.status = 'Pending Verification' THEN 1 END) as pending_verification,
                COUNT(CASE WHEN bt.status = 'Pending Approval' THEN 1 END) as pending_approval,
                COUNT(CASE WHEN bt.status = 'Approved' THEN 1 END) as approved,
                COUNT(CASE WHEN bt.status = 'Returned' THEN 1 END) as returned,
                COUNT(CASE WHEN bt.status = 'Overdue' THEN 1 END) as overdue,
                COUNT(CASE WHEN bt.status = 'Canceled' THEN 1 END) as canceled,
                AVG(DATEDIFF(COALESCE(bt.actual_return, CURDATE()), bt.created_at)) as avg_borrowing_days
            FROM borrowed_tools bt
            INNER JOIN assets a ON bt.asset_id = a.id
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get overdue borrowed tools
     */
    public function getOverdueBorrowedTools($projectId = null) {
        $conditions = ["bt.status = 'Borrowed'", "bt.expected_return < CURDATE()"];
        $params = [];
        
        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }
        
        $whereClause = "WHERE " . implode(" AND ", $conditions);
        
        $sql = "
            SELECT bt.*,
                   a.name as asset_name,
                   a.ref as asset_ref,
                   p.name as project_name,
                   DATEDIFF(CURDATE(), bt.expected_return) as days_overdue
            FROM borrowed_tools bt
            INNER JOIN assets a ON bt.asset_id = a.id
            INNER JOIN projects p ON a.project_id = p.id
            {$whereClause}
            ORDER BY bt.expected_return ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Mark tool as overdue
     */
    public function markOverdue($borrowId) {
        try {
            $borrowedTool = $this->find($borrowId);
            
            if (!$borrowedTool) {
                return ['success' => false, 'message' => 'Borrowed tool not found'];
            }
            
            if ($borrowedTool['status'] !== 'borrowed') {
                return ['success' => false, 'message' => 'Tool is not currently borrowed'];
            }
            
            // Update status to overdue
            $updated = $this->update($borrowId, ['status' => 'overdue']);
            
            if ($updated) {
                // Log activity
                $this->logActivity('mark_overdue', "Tool marked as overdue", 'borrowed_tools', $borrowId);
                
                return ['success' => true, 'message' => 'Tool marked as overdue'];
            } else {
                return ['success' => false, 'message' => 'Failed to mark tool as overdue'];
            }
            
        } catch (Exception $e) {
            error_log("Mark overdue error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to mark tool as overdue'];
        }
    }
    
    /**
     * Update overdue status for all borrowed tools
     */
    public function updateOverdueStatus() {
        try {
            $sql = "
                UPDATE borrowed_tools 
                SET status = 'overdue' 
                WHERE status = 'borrowed' 
                AND expected_return < CURDATE()
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->rowCount();
            
        } catch (Exception $e) {
            error_log("Update overdue status error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get overdue borrower contacts
     */
    public function getOverdueBorrowerContacts() {
        $sql = "
            SELECT DISTINCT bt.borrower_name,
                   bt.borrower_contact,
                   COUNT(*) as overdue_count,
                   GROUP_CONCAT(a.name SEPARATOR ', ') as overdue_assets
            FROM borrowed_tools bt
            INNER JOIN assets a ON bt.asset_id = a.id
            WHERE bt.status = 'borrowed' 
            AND bt.expected_return < CURDATE()
            AND bt.borrower_contact IS NOT NULL
            AND bt.borrower_contact != ''
            GROUP BY bt.borrower_name, bt.borrower_contact
            ORDER BY overdue_count DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get borrowed tool report data
     */
    public function getBorrowedToolReport($dateFrom, $dateTo, $status = null, $projectId = null) {
        $conditions = ["DATE(bt.created_at) BETWEEN ? AND ?"];
        $params = [$dateFrom, $dateTo];
        
        if ($status) {
            if ($status === 'overdue') {
                $conditions[] = "bt.status = 'Borrowed' AND bt.expected_return < CURDATE()";
            } else {
                $conditions[] = "bt.status = ?";
                $params[] = $status;
            }
        }
        
        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }
        
        $whereClause = "WHERE " . implode(" AND ", $conditions);
        
        $sql = "
            SELECT bt.*,
                   a.name as asset_name,
                   a.ref as asset_ref,
                   c.name as category_name,
                   p.name as project_name,
                   u.full_name as issued_by_name,
                   DATEDIFF(COALESCE(bt.actual_return, CURDATE()), bt.created_at) as borrowing_days
            FROM borrowed_tools bt
            INNER JOIN assets a ON bt.asset_id = a.id
            INNER JOIN categories c ON a.category_id = c.id
            INNER JOIN projects p ON a.project_id = p.id
            LEFT JOIN users u ON bt.issued_by = u.id
            {$whereClause}
            ORDER BY bt.created_at DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get borrowing trends
     */
    public function getBorrowingTrends($dateFrom, $dateTo) {
        $sql = "
            SELECT DATE(bt.created_at) as borrow_date,
                   COUNT(*) as daily_borrows,
                   COUNT(CASE WHEN bt.status = 'returned' THEN 1 END) as daily_returns
            FROM borrowed_tools bt
            WHERE DATE(bt.created_at) BETWEEN ? AND ?
            GROUP BY DATE(bt.created_at)
            ORDER BY borrow_date ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get most borrowed assets
     */
    public function getMostBorrowedAssets($limit = 10) {
        $sql = "
            SELECT a.name as asset_name,
                   a.ref as asset_ref,
                   c.name as category_name,
                   COUNT(bt.id) as borrow_count,
                   AVG(DATEDIFF(COALESCE(bt.actual_return, CURDATE()), bt.created_at)) as avg_days
            FROM borrowed_tools bt
            INNER JOIN assets a ON bt.asset_id = a.id
            INNER JOIN categories c ON a.category_id = c.id
            GROUP BY bt.asset_id, a.name, a.ref, c.name
            ORDER BY borrow_count DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get frequent borrowers
     */
    public function getFrequentBorrowers($limit = 10) {
        $sql = "
            SELECT bt.borrower_name,
                   bt.borrower_contact,
                   COUNT(*) as total_borrows,
                   COUNT(CASE WHEN bt.status = 'returned' THEN 1 END) as returned_count,
                   COUNT(CASE WHEN bt.status = 'borrowed' AND bt.expected_return < CURDATE() THEN 1 END) as overdue_count
            FROM borrowed_tools bt
            GROUP BY bt.borrower_name, bt.borrower_contact
            ORDER BY total_borrows DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get asset borrowing history
     */
    public function getAssetBorrowingHistory($assetId) {
        try {
            $sql = "
                SELECT bt.*,
                       u.full_name as issued_by_name,
                       DATEDIFF(COALESCE(bt.actual_return, CURDATE()), bt.created_at) as borrowing_days,
                       CASE 
                           WHEN bt.status = 'borrowed' AND bt.expected_return < CURDATE() THEN 'overdue'
                           ELSE bt.status
                       END as current_status
                FROM borrowed_tools bt
                LEFT JOIN users u ON bt.issued_by = u.id
                WHERE bt.asset_id = ?
                ORDER BY bt.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get asset borrowing history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create and process basic tool request in one streamlined operation
     * This combines create, verify, approve, and mark as borrowed for basic tools (<₱50,000)
     */
    public function createAndProcessBasicTool($data) {
        try {
            $this->db->beginTransaction();
            
            // Validate required fields
            $errors = [];
            if (empty($data['asset_id'])) $errors[] = 'Asset is required';
            if (empty($data['borrower_name'])) $errors[] = 'Borrower name is required';
            if (empty($data['expected_return'])) $errors[] = 'Expected return date is required';
            if (empty($data['issued_by'])) $errors[] = 'Issued by is required';
            
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Check if asset is available and is basic tool
            $assetModel = new AssetModel();
            $asset = $assetModel->find($data['asset_id']);
            if (!$asset) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            if ($asset['status'] !== 'available') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Asset is not available for borrowing'];
            }
            
            // Verify this is a basic tool (not critical)
            if ($this->isCriticalTool($data['asset_id'], $asset['acquisition_cost'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Critical tools must follow standard MVA workflow'];
            }
            
            $currentDateTime = date('Y-m-d H:i:s');
            $currentUserId = $data['issued_by'];
            
            // Create borrowed tool record with streamlined status (directly to Borrowed)
            $borrowData = [
                'asset_id' => $data['asset_id'],
                'borrower_name' => $data['borrower_name'],
                'borrower_contact' => $data['borrower_contact'] ?? null,
                'expected_return' => $data['expected_return'],
                'purpose' => $data['purpose'] ?? null,
                'condition_out' => $data['condition_out'] ?? null,
                'status' => 'Borrowed', // Skip MVA steps for basic tools
                'issued_by' => $currentUserId,
                // Set all MVA fields to the same user and current time for audit trail
                'verified_by' => $currentUserId,
                'verification_date' => $currentDateTime,
                'approved_by' => $currentUserId,
                'approval_date' => $currentDateTime,
                'borrowed_by' => $currentUserId,
                'borrowed_date' => $currentDateTime,
                'created_at' => $currentDateTime
            ];
            
            $borrowedTool = $this->create($borrowData);
            if (!$borrowedTool) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to create borrowed tool request'];
            }
            
            // Update asset status to borrowed
            $assetUpdateSql = "UPDATE assets SET status = 'borrowed' WHERE id = ?";
            $assetStmt = $this->db->prepare($assetUpdateSql);
            if (!$assetStmt->execute([$data['asset_id']])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to update asset status'];
            }
            
            // Log streamlined activity
            $this->logActivity(
                'streamlined_borrow_basic_tool', 
                "Basic tool streamlined processing: {$asset['name']} borrowed by {$data['borrower_name']} (Maker/Verifier/Authorizer: same user)", 
                'borrowed_tools', 
                $borrowedTool['id']
            );
            
            $this->db->commit();
            return ['success' => true, 'borrowed_tool' => $borrowedTool];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Streamlined borrowed tool creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process tool borrowing request'];
        }
    }
    
    /**
     * Get items by batch ID
     */
    public function getItemsByBatchId($batchId) {
        try {
            $sql = "
                SELECT bt.*,
                       a.name as asset_name,
                       a.ref as asset_ref,
                       a.acquisition_cost,
                       c.name as category_name,
                       et.name as equipment_type_name
                FROM borrowed_tools bt
                INNER JOIN assets a ON bt.asset_id = a.id
                INNER JOIN categories c ON a.category_id = c.id
                LEFT JOIN equipment_types et ON a.equipment_type_id = et.id
                WHERE bt.batch_id = ?
                ORDER BY a.name ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$batchId]);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get items by batch ID error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if item is fully returned
     */
    public function isFullyReturned($borrowedToolId) {
        try {
            $record = $this->find($borrowedToolId);
            if (!$record) {
                return false;
            }

            return $record['quantity_returned'] >= $record['quantity'];

        } catch (Exception $e) {
            error_log("Check fully returned error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unreturned quantity for an item
     */
    public function getUnreturnedQuantity($borrowedToolId) {
        try {
            $record = $this->find($borrowedToolId);
            if (!$record) {
                return 0;
            }

            return max(0, $record['quantity'] - $record['quantity_returned']);

        } catch (Exception $e) {
            error_log("Get unreturned quantity error: " . $e->getMessage());
            return 0;
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
