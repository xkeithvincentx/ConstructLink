<?php
/**
 * ConstructLink Withdrawal Batch Model
 * Handles multi-item withdrawal batch operations for consumable items
 */

require_once APP_ROOT . '/core/traits/ActivityLoggingTrait.php';
require_once APP_ROOT . '/helpers/WithdrawalBatchStatus.php';

class WithdrawalBatchModel extends BaseModel {
    use ActivityLoggingTrait;

    protected $table = 'withdrawal_batches';
    protected $fillable = [
        'batch_reference', 'receiver_name', 'receiver_contact', 'purpose', 'status',
        'issued_by', 'verified_by', 'verification_date', 'verification_notes',
        'approved_by', 'approval_date', 'approval_notes',
        'released_by', 'release_date', 'release_notes',
        'canceled_by', 'cancellation_date', 'cancellation_reason',
        'total_items', 'total_quantity'
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
            require_once APP_ROOT . '/services/WithdrawalBatchWorkflowService.php';
            $this->workflowService = new WithdrawalBatchWorkflowService($this->db, $this);
        }
        return $this->workflowService;
    }

    /**
     * Get statistics service instance (lazy loading)
     */
    private function getStatisticsService() {
        if ($this->statisticsService === null) {
            require_once APP_ROOT . '/services/WithdrawalBatchStatisticsService.php';
            $this->statisticsService = new WithdrawalBatchStatisticsService($this->db);
        }
        return $this->statisticsService;
    }

    /**
     * Get query service instance (lazy loading)
     */
    private function getQueryService() {
        if ($this->queryService === null) {
            require_once APP_ROOT . '/services/WithdrawalBatchQueryService.php';
            $this->queryService = new WithdrawalBatchQueryService($this->db);
        }
        return $this->queryService;
    }

    /**
     * Generate unique batch reference number following ISO 55000 principles
     * Format: WDR-[PROJECT]-[YEAR]-[SEQ] (e.g., WDR-PROJ1-2025-0001)
     *
     * This format allows Finance/Asset Directors to immediately identify:
     * - WDR: Withdrawal transaction type
     * - PROJECT: Which project the withdrawal belongs to
     * - YEAR: Year of transaction
     * - SEQ: Sequential number within project/year
     *
     * @param int $projectId The project ID for the withdrawal
     * @return string ISO-compliant batch reference
     */
    public function generateBatchReference($projectId) {
        try {
            // Get project code
            $projectCode = $this->getProjectCode($projectId);
            $year = date('Y');

            // CONCURRENCY FIX: Use INSERT ... ON DUPLICATE KEY UPDATE which is atomic in MySQL
            // This ensures thread-safe sequence increment even with multiple simultaneous requests
            $seqSql = "INSERT INTO withdrawal_batch_sequences (project_id, year, last_sequence)
                       VALUES (?, ?, 1)
                       ON DUPLICATE KEY UPDATE last_sequence = last_sequence + 1";
            $stmt = $this->db->prepare($seqSql);
            $stmt->execute([$projectId, $year]);

            // Get the current sequence with row lock to prevent race conditions
            // FOR UPDATE locks the row until transaction commits
            $getSql = "SELECT last_sequence FROM withdrawal_batch_sequences
                      WHERE project_id = ? AND year = ?
                      FOR UPDATE";
            $getStmt = $this->db->prepare($getSql);
            $getStmt->execute([$projectId, $year]);
            $sequence = $getStmt->fetchColumn();

            return sprintf('WDR-%s-%s-%04d', $projectCode, $year, $sequence);

        } catch (Exception $e) {
            // Fallback to timestamp-based unique reference if generation fails
            return 'WDR-UNK-' . date('Ymd') . '-' . substr(uniqid(), -4);
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
     * Create batch with multiple consumable items
     *
     * This method orchestrates the batch creation process by coordinating
     * validation, consumable quantity checks, and record creation.
     *
     * @param array $batchData Batch information
     * @param array $items Array of items with [inventory_item_id, quantity, line_notes]
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

            // Step 2: Validate and lock consumable items (prevents double-booking)
            $itemsResult = $this->validateAndLockConsumableItems($items);
            if (!$itemsResult['success']) {
                $this->db->rollBack();
                return $itemsResult;
            }

            $validatedItems = $itemsResult['validated_items'];
            $projectId = $itemsResult['project_id'];
            $totalQuantity = $itemsResult['total_quantity'];

            // Step 3: Generate batch reference with project code
            $batchReference = $this->generateBatchReference($projectId);

            // Step 4: All batches follow MVA workflow (no critical determination needed)
            $workflowStatus = WithdrawalBatchStatus::PENDING_VERIFICATION;

            // Step 5: Create batch record
            $batchResult = $this->createBatchRecord(
                $batchData,
                $batchReference,
                $workflowStatus,
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
                'create_withdrawal_batch',
                "Created withdrawal batch {$batchReference} with " . count($validatedItems) . " items for {$batchData['receiver_name']}",
                'withdrawal_batches',
                $batch['id']
            );

            $this->db->commit();

            return [
                'success' => true,
                'batch' => $batch,
                'workflow_type' => 'mva',
                'message' => 'Batch created successfully'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Withdrawal batch creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create batch'];
        }
    }

    /**
     * Validate batch data and items array
     *
     * Performs input validation on batch data and ensures items array is not empty.
     *
     * @param array $batchData Batch information
     * @param array $items Array of items
     * @return array Validation result with ['valid' => bool, 'errors'|'message' => string|array]
     */
    private function validateBatchData($batchData, $items) {
        // Validate required batch fields
        $validation = $this->validate($batchData, [
            'receiver_name' => 'required|max:100',
            'issued_by' => 'required|integer'
        ]);

        if (!$validation['valid']) {
            return ['success' => false, 'valid' => false, 'errors' => $validation['errors']];
        }

        // Validate items array is not empty
        if (empty($items)) {
            return ['success' => false, 'valid' => false, 'message' => 'At least one item must be selected'];
        }

        return ['valid' => true];
    }

    /**
     * Validate and lock consumable items for withdrawal
     *
     * For each item:
     * - Locks asset row using SELECT ... FOR UPDATE (prevents double-booking)
     * - Validates asset exists and is consumable
     * - Validates sufficient quantity available
     * - Validates all items belong to same project
     * - Validates quantity is at least 1
     *
     * @param array $items Array of items with [inventory_item_id, quantity, line_notes]
     * @return array Result with validated_items, project_id, total_quantity
     */
    private function validateAndLockConsumableItems($items) {
        $validatedItems = [];
        $totalQuantity = 0;
        $projectId = null;

        foreach ($items as $item) {
            // CONCURRENCY FIX: Lock asset row for reading to prevent double-booking
            // SELECT ... FOR UPDATE ensures no other transaction can modify this asset
            // until our transaction commits
            // IMPORTANT: Join with categories to get is_consumable flag
            $lockSql = "
                SELECT ii.*, c.is_consumable
                FROM inventory_items ii
                INNER JOIN categories c ON ii.category_id = c.id
                WHERE ii.id = ?
                FOR UPDATE
            ";
            $lockStmt = $this->db->prepare($lockSql);
            $lockStmt->execute([$item['inventory_item_id']]);
            $asset = $lockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$asset) {
                return ['success' => false, 'message' => 'Inventory item ID ' . $item['inventory_item_id'] . ' not found'];
            }

            // CRITICAL: Enforce consumable-only items
            if (!$asset['is_consumable'] || $asset['is_consumable'] != 1) {
                return ['success' => false, 'message' => $asset['name'] . ' is not a consumable item. Only consumables can be withdrawn in batches.'];
            }

            $quantity = (int)($item['quantity'] ?? 1);
            if ($quantity < 1) {
                return ['success' => false, 'message' => 'Quantity must be at least 1 for ' . $asset['name']];
            }

            // CRITICAL: Validate sufficient quantity available
            if ($asset['available_quantity'] < $quantity) {
                return ['success' => false, 'message' => 'Insufficient quantity for ' . $asset['name'] . '. Available: ' . $asset['available_quantity'] . ', Requested: ' . $quantity];
            }

            // Validate all items belong to same project
            if ($projectId === null) {
                $projectId = $asset['project_id'];
            } elseif ($projectId !== $asset['project_id']) {
                return ['success' => false, 'message' => 'All items in a batch must belong to the same project'];
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
            'total_quantity' => $totalQuantity
        ];
    }

    /**
     * Create batch record in database
     *
     * @param array $batchData Original batch data from user input
     * @param string $batchReference Generated batch reference number
     * @param string $workflowStatus Initial workflow status
     * @param int $totalItems Total number of distinct items
     * @param int $totalQuantity Total quantity across all items
     * @return array Result with success status and batch data
     */
    private function createBatchRecord($batchData, $batchReference, $workflowStatus, $totalItems, $totalQuantity) {
        $batch = $this->create([
            'batch_reference' => $batchReference,
            'receiver_name' => $batchData['receiver_name'],
            'receiver_contact' => $batchData['receiver_contact'] ?? null,
            'purpose' => $batchData['purpose'] ?? null,
            'status' => $workflowStatus,
            'issued_by' => $batchData['issued_by'],
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
     * Create individual withdrawals records for each item in batch
     *
     * @param int $batchId The created batch ID
     * @param array $validatedItems Array of validated items with asset, quantity, line_notes
     * @param array $batchData Original batch data
     * @param string $workflowStatus Current workflow status
     * @return array Result with success status
     */
    private function createBatchItems($batchId, $validatedItems, $batchData, $workflowStatus) {
        $withdrawalModel = new WithdrawalModel();
        $currentDateTime = date('Y-m-d H:i:s');

        foreach ($validatedItems as $item) {
            $withdrawalData = [
                'batch_id' => $batchId,
                'inventory_item_id' => $item['asset']['id'],
                'project_id' => $item['asset']['project_id'],
                'quantity' => $item['quantity'],
                'receiver_name' => $batchData['receiver_name'],
                'withdrawn_by' => $batchData['issued_by'],
                'purpose' => $batchData['purpose'] ?? null,
                'status' => $workflowStatus,
                'created_at' => $currentDateTime
            ];

            $created = $withdrawalModel->create($withdrawalData);

            if (!$created) {
                return ['success' => false, 'message' => 'Failed to create line item for ' . $item['asset']['name']];
            }
        }

        return ['success' => true];
    }

    /**
     * Get batch with all items
     * DELEGATED TO: WithdrawalBatchQueryService
     */
    public function getBatchWithItems($batchId, $projectId = null) {
        return $this->getQueryService()->getBatchWithItems($batchId, $projectId);
    }

    /**
     * Verify batch (Verifier step in MVA workflow)
     * DELEGATED TO: WithdrawalBatchWorkflowService
     */
    public function verifyBatch($batchId, $verifiedBy, $notes = null) {
        return $this->getWorkflowService()->verifyBatch($batchId, $verifiedBy, $notes);
    }

    /**
     * Approve batch (Authorizer step in MVA workflow)
     * DELEGATED TO: WithdrawalBatchWorkflowService
     */
    public function approveBatch($batchId, $approvedBy, $notes = null) {
        return $this->getWorkflowService()->approveBatch($batchId, $approvedBy, $notes);
    }

    /**
     * Release batch (mark as physically handed over to receiver)
     * DELEGATED TO: WithdrawalBatchWorkflowService
     */
    public function releaseBatch($batchId, $releasedBy, $notes = null) {
        return $this->getWorkflowService()->releaseBatch($batchId, $releasedBy, $notes);
    }

    /**
     * Cancel batch
     * DELEGATED TO: WithdrawalBatchWorkflowService
     */
    public function cancelBatch($batchId, $canceledBy, $reason = null) {
        return $this->getWorkflowService()->cancelBatch($batchId, $canceledBy, $reason);
    }

    /**
     * Get batches with filters and pagination
     * DELEGATED TO: WithdrawalBatchQueryService
     */
    public function getBatchesWithFilters($filters = [], $page = 1, $perPage = 20) {
        return $this->getQueryService()->getBatchesWithFilters($filters, $page, $perPage);
    }

    /**
     * Get batch statistics
     * DELEGATED TO: WithdrawalBatchStatisticsService
     */
    public function getBatchStats($dateFrom = null, $dateTo = null, $projectId = null) {
        return $this->getStatisticsService()->getBatchStats($dateFrom, $dateTo, $projectId);
    }
}
?>
