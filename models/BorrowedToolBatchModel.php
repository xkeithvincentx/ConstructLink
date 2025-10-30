<?php
/**
 * ConstructLink™ Borrowed Tool Batch Model
 * Handles multi-item borrowed tool batch operations
 */

require_once APP_ROOT . '/core/traits/ActivityLoggingTrait.php';
require_once APP_ROOT . '/helpers/AssetStatus.php';
require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';

class BorrowedToolBatchModel extends BaseModel {
    use ActivityLoggingTrait;

    protected $table = 'borrowed_tool_batches';
    protected $fillable = [
        'batch_reference', 'borrower_name', 'borrower_contact', 'borrower_signature_image',
        'borrower_photo', 'expected_return', 'actual_return', 'purpose', 'status',
        'issued_by', 'verified_by', 'verification_date', 'verification_notes',
        'approved_by', 'approval_date', 'approval_notes',
        'released_by', 'release_date', 'release_notes',
        'returned_by', 'return_date', 'return_notes',
        'canceled_by', 'cancellation_date', 'cancellation_reason',
        'is_critical_batch', 'total_items', 'total_quantity', 'printed_at'
    ];

    // Service dependencies for delegation
    private $workflowService;
    private $statisticsService;
    private $queryService;

    /**
     * Get workflow service instance (lazy loading)
     */
    private function getWorkflowService() {
        if ($this->workflowService === null) {
            require_once APP_ROOT . '/services/BorrowedToolBatchWorkflowService.php';
            $this->workflowService = new BorrowedToolBatchWorkflowService($this->db, $this);
        }
        return $this->workflowService;
    }

    /**
     * Get statistics service instance (lazy loading)
     */
    private function getStatisticsService() {
        if ($this->statisticsService === null) {
            require_once APP_ROOT . '/services/BorrowedToolBatchStatisticsService.php';
            $this->statisticsService = new BorrowedToolBatchStatisticsService($this->db);
        }
        return $this->statisticsService;
    }

    /**
     * Get query service instance (lazy loading)
     */
    private function getQueryService() {
        if ($this->queryService === null) {
            require_once APP_ROOT . '/services/BorrowedToolBatchQueryService.php';
            $this->queryService = new BorrowedToolBatchQueryService($this->db);
        }
        return $this->queryService;
    }

    /**
     * Generate unique batch reference number following ISO 55000 principles
     * Format: BRW-[PROJECT]-[YEAR]-[SEQ] (e.g., BRW-PROJ1-2025-0001)
     *
     * This format allows Finance/Asset Directors to immediately identify:
     * - BRW: Borrowing transaction type
     * - PROJECT: Which project the borrowing belongs to
     * - YEAR: Year of transaction
     * - SEQ: Sequential number within project/year
     *
     * @param int $projectId The project ID for the borrowing
     * @return string ISO-compliant batch reference
     */
    public function generateBatchReference($projectId) {
        try {
            // Get project code
            $projectCode = $this->getProjectCode($projectId);
            $year = date('Y');

            // CONCURRENCY FIX: Use INSERT ... ON DUPLICATE KEY UPDATE which is atomic in MySQL
            // This ensures thread-safe sequence increment even with multiple simultaneous requests
            $seqSql = "INSERT INTO borrowed_tool_batch_sequences (project_id, year, last_sequence)
                       VALUES (?, ?, 1)
                       ON DUPLICATE KEY UPDATE last_sequence = last_sequence + 1";
            $stmt = $this->db->prepare($seqSql);
            $stmt->execute([$projectId, $year]);

            // Get the current sequence with row lock to prevent race conditions
            // FOR UPDATE locks the row until transaction commits
            $getSql = "SELECT last_sequence FROM borrowed_tool_batch_sequences
                      WHERE project_id = ? AND year = ?
                      FOR UPDATE";
            $getStmt = $this->db->prepare($getSql);
            $getStmt->execute([$projectId, $year]);
            $sequence = $getStmt->fetchColumn();

            return sprintf('BRW-%s-%s-%04d', $projectCode, $year, $sequence);

        } catch (Exception $e) {
            // Fallback to timestamp-based unique reference if generation fails
            return 'BRW-UNK-' . date('Ymd') . '-' . substr(uniqid(), -4);
        }
    }

    /**
     * Get project code from project ID
     *
     * @param int $projectId
     * @return string Project code
     * @throws Exception if project not found
     */
    private function getProjectCode($projectId) {
        if (!$projectId) {
            throw new Exception("Project ID is required for batch reference generation");
        }

        $sql = "SELECT code, name FROM projects WHERE id = ? AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            throw new Exception("Active project with ID {$projectId} not found");
        }

        if (empty($project['code'])) {
            throw new Exception("Project '{$project['name']}' (ID: {$projectId}) is missing required code");
        }

        return strtoupper($project['code']);
    }

    /**
     * Create batch with multiple items
     *
     * This method orchestrates the batch creation process by coordinating
     * validation, asset locking, workflow determination, and record creation.
     * Refactored from a 197-line god method into focused, single-responsibility methods.
     *
     * @param array $batchData Batch information
     * @param array $items Array of items with [asset_id, quantity, line_notes]
     * @return array Result with success status and batch data
     */
    public function createBatch($batchData, $items) {
        // Step 1: Validate batch data
        $validationResult = $this->validateBatchData($batchData, $items);
        if (!$validationResult['valid']) {
            return $validationResult;
        }

        try {
            $this->db->beginTransaction();

            // Step 2: Validate and lock items (prevents double-booking)
            $itemsResult = $this->validateAndLockItems($items);
            if (!$itemsResult['success']) {
                $this->db->rollBack();
                return $itemsResult;
            }

            $validatedItems = $itemsResult['validated_items'];
            $projectId = $itemsResult['project_id'];
            $isCriticalBatch = $itemsResult['is_critical'];
            $totalQuantity = $itemsResult['total_quantity'];

            // Step 3: Generate batch reference with project code
            $batchReference = $this->generateBatchReference($projectId);

            // Step 4: Determine workflow status based on criticality and user role
            $workflowStatus = $this->determineBatchWorkflow($isCriticalBatch);

            // Step 5: Create batch record
            $batchResult = $this->createBatchRecord(
                $batchData,
                $batchReference,
                $workflowStatus,
                $isCriticalBatch,
                count($validatedItems),
                $totalQuantity
            );

            if (!$batchResult['success']) {
                $this->db->rollBack();
                return $batchResult;
            }

            $batch = $batchResult['batch'];

            // Step 6: Create batch items
            $itemsCreationResult = $this->createBatchItems(
                $batch['id'],
                $validatedItems,
                $batchData,
                $workflowStatus
            );

            if (!$itemsCreationResult['success']) {
                $this->db->rollBack();
                return $itemsCreationResult;
            }

            // Step 7: Log activity
            $this->logActivity(
                'create_borrowed_batch',
                "Created borrowed tool batch {$batchReference} with " . count($validatedItems) . " items for {$batchData['borrower_name']}",
                'borrowed_tool_batches',
                $batch['id']
            );

            $this->db->commit();

            return [
                'success' => true,
                'batch' => $batch,
                'is_critical' => $isCriticalBatch,
                'workflow_type' => $isCriticalBatch ? 'mva' : 'streamlined',
                'message' => 'Batch created successfully'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Batch creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create batch'];
        }
    }

    /**
     * Validate batch data and items array
     *
     * Performs input validation on batch data and ensures items array is not empty.
     * Validates expected return date is not in the past.
     *
     * @param array $batchData Batch information
     * @param array $items Array of items
     * @return array Validation result with ['valid' => bool, 'errors'|'message' => string|array]
     */
    private function validateBatchData($batchData, $items) {
        // Validate required batch fields
        $validation = $this->validate($batchData, [
            'borrower_name' => 'required|max:100',
            'expected_return' => 'required|date',
            'issued_by' => 'required|integer'
        ]);

        if (!$validation['valid']) {
            return ['success' => false, 'valid' => false, 'errors' => $validation['errors']];
        }

        // Validate items array is not empty
        if (empty($items)) {
            return ['success' => false, 'valid' => false, 'message' => 'At least one item must be selected'];
        }

        // Validate expected return date is not in the past
        if (strtotime($batchData['expected_return']) < strtotime(date('Y-m-d'))) {
            return ['success' => false, 'valid' => false, 'message' => 'Expected return date cannot be in the past'];
        }

        return ['valid' => true];
    }

    /**
     * Validate and lock items for borrowing
     *
     * For each item:
     * - Locks asset row using SELECT ... FOR UPDATE (prevents double-booking)
     * - Validates asset exists and is available
     * - Checks for existing reservations in active batches
     * - Validates all items belong to same project
     * - Determines if batch contains critical tools (>50K)
     * - Validates quantity is at least 1
     *
     * @param array $items Array of items with [asset_id, quantity, line_notes]
     * @return array Result with validated_items, project_id, is_critical, total_quantity
     */
    private function validateAndLockItems($items) {
        $validatedItems = [];
        $totalQuantity = 0;
        $projectId = null;
        $isCriticalBatch = false;

        foreach ($items as $item) {
            // CONCURRENCY FIX: Lock asset row for reading to prevent double-booking
            // SELECT ... FOR UPDATE ensures no other transaction can modify this asset
            // until our transaction commits
            $lockSql = "SELECT * FROM assets WHERE id = ? FOR UPDATE";
            $lockStmt = $this->db->prepare($lockSql);
            $lockStmt->execute([$item['asset_id']]);
            $asset = $lockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$asset) {
                return ['success' => false, 'message' => 'Asset ID ' . $item['asset_id'] . ' not found'];
            }

            if ($asset['status'] !== AssetStatus::AVAILABLE) {
                return ['success' => false, 'message' => $asset['name'] . ' is not available for borrowing (status: ' . $asset['status'] . ')'];
            }

            // CRITICAL SECURITY FIX: Check if asset is already reserved in another active batch
            // This prevents double-booking during the approval workflow
            $checkReservationSql = "
                SELECT bt.id, btb.batch_reference, btb.status
                FROM borrowed_tools bt
                INNER JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
                WHERE bt.asset_id = ?
                  AND btb.status IN (?, ?, ?, ?, ?)
                LIMIT 1
            ";
            $checkStmt = $this->db->prepare($checkReservationSql);
            $checkStmt->execute([
                $asset['id'],
                BorrowedToolStatus::PENDING_VERIFICATION,
                BorrowedToolStatus::PENDING_APPROVAL,
                BorrowedToolStatus::APPROVED,
                BorrowedToolStatus::RELEASED,
                BorrowedToolStatus::PARTIALLY_RETURNED
            ]);
            $existingReservation = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingReservation) {
                return ['success' => false, 'message' => $asset['name'] . ' is already reserved in batch ' . $existingReservation['batch_reference'] . ' (status: ' . $existingReservation['status'] . ')'];
            }

            // Validate all items belong to same project
            if ($projectId === null) {
                $projectId = $asset['project_id'];
            } elseif ($projectId !== $asset['project_id']) {
                return ['success' => false, 'message' => 'All items in a batch must belong to the same project'];
            }

            // Check if critical tool
            if ($asset['acquisition_cost'] > config('business_rules.critical_tool_threshold')) {
                $isCriticalBatch = true;
            }

            $quantity = (int)($item['quantity'] ?? 1);
            if ($quantity < 1) {
                return ['success' => false, 'message' => 'Quantity must be at least 1 for ' . $asset['name']];
            }

            $validatedItems[] = [
                'asset' => $asset,
                'quantity' => $quantity,
                'line_notes' => $item['line_notes'] ?? null
            ];

            $totalQuantity += $quantity;
        }

        // Validate we have a project ID
        if (!$projectId) {
            return ['success' => false, 'message' => 'Unable to determine project for batch items'];
        }

        return [
            'success' => true,
            'validated_items' => $validatedItems,
            'project_id' => $projectId,
            'is_critical' => $isCriticalBatch,
            'total_quantity' => $totalQuantity
        ];
    }

    /**
     * Determine batch workflow status based on criticality and user role
     *
     * Workflow Logic:
     * - Critical tools (>50K): Always use full MVA workflow (Pending Verification)
     * - Basic tools: Use streamlined workflow for authorized roles (Warehouseman, System Admin)
     *   - Streamlined workflow skips to 'Approved' status for immediate release
     *
     * @param bool $isCritical Whether batch contains critical tools
     * @return string Initial workflow status
     */
    private function determineBatchWorkflow($isCritical) {
        // Critical tools always use full MVA workflow
        if ($isCritical) {
            return BorrowedToolStatus::PENDING_VERIFICATION;
        }

        // For non-critical batches, check if user can do streamlined processing
        $currentUser = Auth::getInstance()->getCurrentUser();
        if (in_array($currentUser['role_name'], ['Warehouseman', 'System Admin'])) {
            // Streamlined: skip to Approved status (will be released immediately after)
            return BorrowedToolStatus::APPROVED;
        }

        // Default to MVA workflow
        return BorrowedToolStatus::PENDING_VERIFICATION;
    }

    /**
     * Create batch record in database
     *
     * @param array $batchData Original batch data from user input
     * @param string $batchReference Generated batch reference number
     * @param string $workflowStatus Initial workflow status
     * @param bool $isCritical Whether batch contains critical tools
     * @param int $totalItems Total number of distinct items
     * @param int $totalQuantity Total quantity across all items
     * @return array Result with success status and batch data
     */
    private function createBatchRecord($batchData, $batchReference, $workflowStatus, $isCritical, $totalItems, $totalQuantity) {
        $batch = $this->create([
            'batch_reference' => $batchReference,
            'borrower_name' => $batchData['borrower_name'],
            'borrower_contact' => $batchData['borrower_contact'] ?? null,
            'expected_return' => $batchData['expected_return'],
            'purpose' => $batchData['purpose'] ?? null,
            'status' => $workflowStatus,
            'issued_by' => $batchData['issued_by'],
            'is_critical_batch' => $isCritical ? 1 : 0,
            'total_items' => $totalItems,
            'total_quantity' => $totalQuantity,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if (!$batch) {
            return ['success' => false, 'message' => 'Failed to create batch'];
        }

        return ['success' => true, 'batch' => $batch];
    }

    /**
     * Create individual borrowed_tools records for each item in batch
     *
     * For streamlined workflow (Approved status), auto-populates verification
     * and approval fields with issuer information.
     *
     * @param int $batchId The created batch ID
     * @param array $validatedItems Array of validated items with asset, quantity, line_notes
     * @param array $batchData Original batch data
     * @param string $workflowStatus Current workflow status
     * @return array Result with success status
     */
    private function createBatchItems($batchId, $validatedItems, $batchData, $workflowStatus) {
        $borrowedToolModel = new BorrowedToolModel();
        $currentDateTime = date('Y-m-d H:i:s');

        foreach ($validatedItems as $item) {
            $borrowData = [
                'batch_id' => $batchId,
                'asset_id' => $item['asset']['id'],
                'quantity' => $item['quantity'],
                'quantity_returned' => 0,
                'borrower_name' => $batchData['borrower_name'],
                'borrower_contact' => $batchData['borrower_contact'] ?? null,
                'expected_return' => $batchData['expected_return'],
                'issued_by' => $batchData['issued_by'],
                'purpose' => $batchData['purpose'] ?? null,
                'line_notes' => $item['line_notes'],
                'status' => $workflowStatus,
                'created_at' => $currentDateTime
            ];

            // For streamlined workflow (basic tools only)
            if ($workflowStatus === BorrowedToolStatus::APPROVED) {
                $borrowData['verified_by'] = $batchData['issued_by'];
                $borrowData['verification_date'] = $currentDateTime;
                $borrowData['approved_by'] = $batchData['issued_by'];
                $borrowData['approval_date'] = $currentDateTime;
            }

            $created = $borrowedToolModel->create($borrowData);

            if (!$created) {
                return ['success' => false, 'message' => 'Failed to create line item for ' . $item['asset']['name']];
            }
        }

        return ['success' => true];
    }

    /**
     * Get batch with all items
     * DELEGATED TO: BorrowedToolBatchQueryService
     */
    public function getBatchWithItems($batchId, $projectId = null) {
        return $this->getQueryService()->getBatchWithItems($batchId, $projectId);
    }

    /**
     * Verify batch (Verifier step in MVA workflow)
     * DELEGATED TO: BorrowedToolBatchWorkflowService
     */
    public function verifyBatch($batchId, $verifiedBy, $notes = null) {
        return $this->getWorkflowService()->verifyBatch($batchId, $verifiedBy, $notes);
    }

    /**
     * Approve batch (Authorizer step in MVA workflow)
     * DELEGATED TO: BorrowedToolBatchWorkflowService
     */
    public function approveBatch($batchId, $approvedBy, $notes = null) {
        return $this->getWorkflowService()->approveBatch($batchId, $approvedBy, $notes);
    }

    /**
     * Release batch (mark as physically handed over to borrower)
     * DELEGATED TO: BorrowedToolBatchWorkflowService
     */
    public function releaseBatch($batchId, $releasedBy, $notes = null) {
        return $this->getWorkflowService()->releaseBatch($batchId, $releasedBy, $notes);
    }

    /**
     * Return batch (full or partial)
     * DELEGATED TO: BorrowedToolBatchWorkflowService
     */
    public function returnBatch($batchId, $returnedBy, $returnedItems, $notes = null) {
        return $this->getWorkflowService()->returnBatch($batchId, $returnedBy, $returnedItems, $notes);
    }

    /**
     * Cancel batch
     * DELEGATED TO: BorrowedToolBatchWorkflowService
     */
    public function cancelBatch($batchId, $canceledBy, $reason = null) {
        return $this->getWorkflowService()->cancelBatch($batchId, $canceledBy, $reason);
    }

    /**
     * Get batches with filters and pagination
     * DELEGATED TO: BorrowedToolBatchQueryService
     */
    public function getBatchesWithFilters($filters = [], $page = 1, $perPage = 20) {
        return $this->getQueryService()->getBatchesWithFilters($filters, $page, $perPage);
    }

    /**
     * Get batch statistics
     * DELEGATED TO: BorrowedToolBatchStatisticsService
     */
    public function getBatchStats($dateFrom = null, $dateTo = null, $projectId = null) {
        return $this->getStatisticsService()->getBatchStats($dateFrom, $dateTo, $projectId);
    }

    /**
     * Get count of overdue batches
     * DELEGATED TO: BorrowedToolBatchStatisticsService
     */
    public function getOverdueBatchCount($projectId = null) {
        return $this->getStatisticsService()->getOverdueBatchCount($projectId);
    }

    /**
     * Extend batch items return date
     * @param int $batchId - The batch ID
     * @param array $itemIds - Array of borrowed_tools IDs to extend
     * @param string $newExpectedReturn - New expected return date
     * @param string $reason - Reason for extension
     * @param int $extendedBy - User ID who extended
     * @return array - Success/error message
     */
    public function extendBatchItems($batchId, $itemIds, $newExpectedReturn, $reason, $extendedBy) {
        try {
            $this->db->beginTransaction();

            // Validate batch exists and belongs to the project
            $batch = $this->getBatchWithItems($batchId);
            if (!$batch) {
                throw new Exception("Batch not found");
            }

            // Validate that all item IDs belong to this batch
            $validItemIds = array_column($batch['items'], 'id');
            $invalidItems = array_diff($itemIds, $validItemIds);

            if (!empty($invalidItems)) {
                throw new Exception("Some items do not belong to this batch");
            }

            // Validate items are in a state that can be extended (Borrowed or Partially Returned)
            foreach ($batch['items'] as $item) {
                if (in_array($item['id'], $itemIds)) {
                    $remaining = $item['quantity'] - $item['quantity_returned'];

                    if ($remaining <= 0) {
                        throw new Exception("Cannot extend item {$item['asset_name']} - already fully returned");
                    }

                    if (!in_array($item['status'], [BorrowedToolStatus::BORROWED, BorrowedToolStatus::RELEASED])) {
                        throw new Exception("Cannot extend item {$item['asset_name']} - invalid status: {$item['status']}");
                    }
                }
            }

            // Validate new date is not earlier than current expected return
            $currentExpectedReturn = new DateTime($batch['expected_return']);
            $newDate = new DateTime($newExpectedReturn);

            if ($newDate < $currentExpectedReturn) {
                throw new Exception("New return date cannot be earlier than current expected return date");
            }

            // Update expected_return for selected items in borrowed_tools
            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            $updateSql = "
                UPDATE borrowed_tools
                SET expected_return = ?,
                    updated_at = NOW()
                WHERE id IN ($placeholders)
                AND batch_id = ?
            ";

            $params = array_merge([$newExpectedReturn], $itemIds, [$batchId]);
            $stmt = $this->db->prepare($updateSql);
            $stmt->execute($params);

            $affectedRows = $stmt->rowCount();

            // Update batch expected_return date to the maximum of all items
            $updateBatchSql = "
                UPDATE borrowed_tool_batches
                SET expected_return = (
                    SELECT MAX(expected_return)
                    FROM borrowed_tools
                    WHERE batch_id = ?
                ),
                updated_at = NOW()
                WHERE id = ?
            ";
            $batchStmt = $this->db->prepare($updateBatchSql);
            $batchStmt->execute([$batchId, $batchId]);

            // Log extension for each item
            foreach ($itemIds as $itemId) {
                $logSql = "
                    INSERT INTO borrowed_tool_logs
                    (borrowed_tool_id, action, user_id, notes, created_at)
                    VALUES (?, 'extended', ?, ?, NOW())
                ";
                $logStmt = $this->db->prepare($logSql);
                $logStmt->execute([
                    $itemId,
                    $extendedBy,
                    "Extended return date to " . date('Y-m-d', strtotime($newExpectedReturn)) . ". Reason: " . $reason
                ]);
            }

            // Log activity for batch
            try {
                $activityModel = new ActivityLogModel($this->db);
                $activityModel->logActivity(
                    'borrowed_tool_batch',
                    $batchId,
                    'extended',
                    "Batch return date extended for " . count($itemIds) . " item(s). Reason: " . $reason,
                    $extendedBy
                );
            } catch (Exception $e) {
                error_log("Activity logging error: " . $e->getMessage());
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Successfully extended " . $affectedRows . " item(s) to " . date('M d, Y', strtotime($newExpectedReturn))
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Batch extend error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get time-based statistics for operational dashboards
     * DELEGATED TO: BorrowedToolBatchStatisticsService
     */
    public function getTimeBasedStatistics($projectId = null) {
        return $this->getStatisticsService()->getTimeBasedStatistics($projectId);
    }
}
?>
