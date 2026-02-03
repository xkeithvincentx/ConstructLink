<?php
/**
 * ConstructLink™ Borrowed Tool Model
 * Handles borrowed tool data operations
 */

require_once APP_ROOT . '/core/traits/ActivityLoggingTrait.php';
require_once APP_ROOT . '/helpers/AssetStatus.php';
require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';

class BorrowedToolModel extends BaseModel {
    use ActivityLoggingTrait;

    protected $table = 'borrowed_tools';

    // Service dependencies for delegation
    private $workflowService;
    private $returnService;
    private $statisticsService;
    private $queryService;
    protected $fillable = [
        'inventory_item_id', 'borrower_name', 'borrower_contact', 'expected_return',
        'actual_return', 'issued_by', 'purpose', 'condition_out', 'condition_in', 'status',
        // Batch support fields
        'batch_id', 'quantity', 'quantity_returned', 'line_notes', 'condition_returned',
        // MVA workflow fields
        'verified_by', 'verification_date', 'approved_by', 'approval_date',
        'borrowed_by', 'borrowed_date', 'returned_by', 'return_date', 'canceled_by', 'cancellation_date', 'cancellation_reason'
    ];

    /**
     * Get workflow service instance (lazy loading)
     */
    private function getWorkflowService() {
        if ($this->workflowService === null) {
            require_once APP_ROOT . '/services/BorrowedToolWorkflowService.php';
            $this->workflowService = new BorrowedToolWorkflowService($this->db, $this);
        }
        return $this->workflowService;
    }

    /**
     * Get return service instance (lazy loading)
     */
    private function getReturnService() {
        if ($this->returnService === null) {
            require_once APP_ROOT . '/services/BorrowedToolReturnService.php';
            $this->returnService = new BorrowedToolReturnService($this->db);
        }
        return $this->returnService;
    }

    /**
     * Get statistics service instance (lazy loading)
     */
    private function getStatisticsService() {
        if ($this->statisticsService === null) {
            require_once APP_ROOT . '/services/BorrowedToolStatisticsService.php';
            $this->statisticsService = new BorrowedToolStatisticsService($this->db);
        }
        return $this->statisticsService;
    }

    /**
     * Get query service instance (lazy loading)
     */
    private function getQueryService() {
        if ($this->queryService === null) {
            require_once APP_ROOT . '/services/BorrowedToolQueryService.php';
            $this->queryService = new BorrowedToolQueryService($this->db);
        }
        return $this->queryService;
    }

    /**
     * Create borrowed tool request (Maker step)
     * DELEGATED TO: BorrowedToolWorkflowService
     */
    public function createBorrowedTool($data) {
        return $this->getWorkflowService()->createBorrowRequest($data);
    }

    /**
     * Verify borrowed tool request (Verifier step)
     * DELEGATED TO: BorrowedToolWorkflowService
     */
    public function verifyBorrowedTool($borrowId, $verifiedBy, $notes = null) {
        return $this->getWorkflowService()->verify($borrowId, $verifiedBy, $notes);
    }

    /**
     * Approve borrowed tool request (Authorizer step)
     * DELEGATED TO: BorrowedToolWorkflowService
     */
    public function approveBorrowedTool($borrowId, $approvedBy, $notes = null) {
        return $this->getWorkflowService()->approve($borrowId, $approvedBy, $notes);
    }

    /**
     * Mark as borrowed / Release tool (after approval)
     * DELEGATED TO: BorrowedToolWorkflowService
     */
    public function borrowTool($borrowId, $borrowedBy, $notes = null) {
        return $this->getWorkflowService()->release($borrowId, $borrowedBy, $notes);
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
            if (!in_array($borrowedTool['status'], [BorrowedToolStatus::BORROWED, BorrowedToolStatus::OVERDUE])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tool is not currently borrowed or overdue'];
            }
            $updateData = [
                'actual_return' => date('Y-m-d'),
                'condition_in' => $conditionIn,
                'status' => BorrowedToolStatus::RETURNED,
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
            $assetUpdated = $assetModel->update($borrowedTool['inventory_item_id'], ['status' => AssetStatus::AVAILABLE]);
            if (!$assetUpdated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to update asset status'];
            }

            // Check if this item belongs to a batch and update batch status if all items are returned
            if (!empty($borrowedTool['batch_id'])) {
                $batchModel = new BorrowedToolBatchModel();

                // Check if all items in the batch are now returned
                $checkAllReturnedSql = "
                    SELECT COUNT(*) as total_items,
                           SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as returned_items
                    FROM borrowed_tools
                    WHERE batch_id = ?
                ";
                $checkStmt = $this->db->prepare($checkAllReturnedSql);
                $checkStmt->execute([BorrowedToolStatus::RETURNED, $borrowedTool['batch_id']]);
                $batchStatus = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($batchStatus && $batchStatus['total_items'] == $batchStatus['returned_items']) {
                    // All items returned - update batch status
                    $batchModel->update($borrowedTool['batch_id'], [
                        'status' => BorrowedToolStatus::RETURNED,
                        'actual_return' => date('Y-m-d'),
                        'returned_by' => $returnedBy,
                        'return_date' => date('Y-m-d H:i:s')
                    ]);
                } elseif ($batchStatus && $batchStatus['returned_items'] > 0) {
                    // Some items returned - update to partially returned
                    $batchModel->update($borrowedTool['batch_id'], [
                        'status' => BorrowedToolStatus::PARTIALLY_RETURNED
                    ]);
                }
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
     * DELEGATED TO: BorrowedToolWorkflowService
     */
    public function cancelBorrowedTool($borrowId, $canceledBy, $reason = null) {
        return $this->getWorkflowService()->cancel($borrowId, $canceledBy, $reason);
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
            
            if (!in_array($borrowedTool['status'], [BorrowedToolStatus::BORROWED, BorrowedToolStatus::OVERDUE])) {
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
     * DELEGATED TO: BorrowedToolQueryService
     */
    public function getBorrowedToolsWithFilters($filters = [], $page = 1, $perPage = 20) {
        return $this->getQueryService()->getBorrowedToolsWithFilters($filters, $page, $perPage);
    }

    /**
     * Get borrowed tool with detailed information
     * DELEGATED TO: BorrowedToolQueryService
     */
    public function getBorrowedToolWithDetails($id, $projectId = null) {
        return $this->getQueryService()->getBorrowedToolWithDetails($id, $projectId);
    }

    /**
     * Get borrowed tool with MVA workflow details
     * DELEGATED TO: BorrowedToolQueryService
     */
    public function getBorrowedToolWithMVADetails($id, $projectId = null) {
        return $this->getQueryService()->getBorrowedToolWithMVADetails($id, $projectId);
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
            // 1. High-value assets (>threshold from config)
            // 2. Specific categories (Equipment, Machinery)
            // 3. Safety-critical items

            if ($assetCost > config('business_rules.critical_tool_threshold')) {
                return true;
            }
            
            // Check if asset is in critical categories
            $db = Database::getInstance()->getConnection();
            $sql = "
                SELECT c.name, c.description
                FROM inventory_items a
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
     * DELEGATED TO: BorrowedToolStatisticsService
     */
    public function getBorrowedToolStats($dateFrom = null, $dateTo = null, $projectId = null) {
        return $this->getStatisticsService()->getBorrowedToolStats($dateFrom, $dateTo, $projectId);
    }
    
    /**
     * Get overdue borrowed tools
     * DELEGATED TO: BorrowedToolStatisticsService
     */
    public function getOverdueBorrowedTools($projectId = null) {
        return $this->getStatisticsService()->getOverdueTools($projectId);
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
            
            if ($borrowedTool['status'] !== BorrowedToolStatus::BORROWED) {
                return ['success' => false, 'message' => 'Tool is not currently borrowed'];
            }

            // Update status to overdue
            $updated = $this->update($borrowId, ['status' => BorrowedToolStatus::OVERDUE]);
            
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
                SET status = ?
                WHERE status = ?
                AND expected_return < CURDATE()
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([BorrowedToolStatus::OVERDUE, BorrowedToolStatus::BORROWED]);

            return $stmt->rowCount();
            
        } catch (Exception $e) {
            error_log("Update overdue status error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get overdue borrower contacts
     * DELEGATED TO: BorrowedToolStatisticsService
     */
    public function getOverdueBorrowerContacts() {
        return $this->getStatisticsService()->getOverdueBorrowerContacts();
    }
    
    /**
     * Get borrowed tool report data
     * DELEGATED TO: BorrowedToolStatisticsService
     */
    public function getBorrowedToolReport($dateFrom, $dateTo, $status = null, $projectId = null) {
        return $this->getStatisticsService()->getBorrowedToolReport($dateFrom, $dateTo, $status, $projectId);
    }
    
    /**
     * Get borrowing trends
     * DELEGATED TO: BorrowedToolStatisticsService
     */
    public function getBorrowingTrends($dateFrom, $dateTo) {
        return $this->getStatisticsService()->getBorrowingTrends('day', null, null, $dateFrom, $dateTo);
    }
    
    /**
     * Get most borrowed assets
     * DELEGATED TO: BorrowedToolStatisticsService
     */
    public function getMostBorrowedAssets($limit = 10) {
        return $this->getStatisticsService()->getMostBorrowedAssets($limit);
    }
    
    /**
     * Get frequent borrowers
     * DELEGATED TO: BorrowedToolStatisticsService
     */
    public function getFrequentBorrowers($limit = 10) {
        return $this->getStatisticsService()->getFrequentBorrowers($limit);
    }
    
    /**
     * Get asset borrowing history
     * DELEGATED TO: BorrowedToolStatisticsService
     */
    public function getAssetBorrowingHistory($assetId) {
        return $this->getStatisticsService()->getAssetBorrowingHistory($assetId);
    }
    
    /**
     * Create and process basic tool request in one streamlined operation
     * DELEGATED TO: BorrowedToolWorkflowService
     */
    public function createAndProcessBasicTool($data) {
        return $this->getWorkflowService()->createAndProcessBasicTool($data);
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
                INNER JOIN inventory_items a ON bt.inventory_item_id = a.id
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
}
?>
