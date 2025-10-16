<?php
/**
 * ConstructLink™ Borrowed Tool Batch Model
 * Handles multi-item borrowed tool batch operations
 * Developed by: Ranoa Digital Solutions
 */

class BorrowedToolBatchModel extends BaseModel {
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
            error_log("Batch reference generation error: " . $e->getMessage());
            // Fallback to timestamp-based unique reference
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
     * @param array $batchData Batch information
     * @param array $items Array of items with [asset_id, quantity, line_notes]
     * @return array Result with success status and batch data
     */
    public function createBatch($batchData, $items) {
        // Validation
        $validation = $this->validate($batchData, [
            'borrower_name' => 'required|max:100',
            'expected_return' => 'required|date',
            'issued_by' => 'required|integer'
        ]);

        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }

        if (empty($items)) {
            return ['success' => false, 'message' => 'At least one item must be selected'];
        }

        // Validate expected return date
        if (strtotime($batchData['expected_return']) < strtotime(date('Y-m-d'))) {
            return ['success' => false, 'message' => 'Expected return date cannot be in the past'];
        }

        try {
            $this->db->beginTransaction();

            // Check if any item is critical (>50K) to determine workflow
            $isCriticalBatch = false;
            $assetModel = new AssetModel();
            $validatedItems = [];
            $totalQuantity = 0;
            $projectId = null;

            foreach ($items as $item) {
                // CONCURRENCY FIX: Lock asset row for reading to prevent double-booking
                // SELECT ... FOR UPDATE ensures no other transaction can modify this asset
                // until our transaction commits
                $lockSql = "SELECT * FROM assets WHERE id = ? FOR UPDATE";
                $lockStmt = $this->db->prepare($lockSql);
                $lockStmt->execute([$item['asset_id']]);
                $asset = $lockStmt->fetch(PDO::FETCH_ASSOC);

                if (!$asset) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Asset ID ' . $item['asset_id'] . ' not found'];
                }

                if ($asset['status'] !== 'available') {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => $asset['name'] . ' is not available for borrowing (status: ' . $asset['status'] . ')'];
                }

                // CRITICAL SECURITY FIX: Check if asset is already reserved in another active batch
                // This prevents double-booking during the approval workflow
                $checkReservationSql = "
                    SELECT bt.id, btb.batch_reference, btb.status
                    FROM borrowed_tools bt
                    INNER JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
                    WHERE bt.asset_id = ?
                      AND btb.status IN ('Pending Verification', 'Pending Approval', 'Approved', 'Released', 'Partially Returned')
                    LIMIT 1
                ";
                $checkStmt = $this->db->prepare($checkReservationSql);
                $checkStmt->execute([$asset['id']]);
                $existingReservation = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($existingReservation) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => $asset['name'] . ' is already reserved in batch ' . $existingReservation['batch_reference'] . ' (status: ' . $existingReservation['status'] . ')'];
                }

                // Validate all items belong to same project
                if ($projectId === null) {
                    $projectId = $asset['project_id'];
                } elseif ($projectId !== $asset['project_id']) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'All items in a batch must belong to the same project'];
                }

                // Check if critical tool
                if ($asset['acquisition_cost'] > 50000) {
                    $isCriticalBatch = true;
                }

                $quantity = (int)($item['quantity'] ?? 1);
                if ($quantity < 1) {
                    $this->db->rollBack();
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
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Unable to determine project for batch items'];
            }

            // Generate batch reference with project code
            $batchReference = $this->generateBatchReference($projectId);

            // Determine initial status based on critical flag and streamlined workflow
            $initialStatus = 'Pending Verification'; // Default: MVA workflow

            // For non-critical batches, check if user can do streamlined processing
            if (!$isCriticalBatch) {
                $currentUser = Auth::getInstance()->getCurrentUser();
                if (in_array($currentUser['role_name'], ['Warehouseman', 'System Admin'])) {
                    // Streamlined: skip to Released status
                    $initialStatus = 'Approved'; // Will be released immediately after
                }
            }

            // Create batch record
            $batch = $this->create([
                'batch_reference' => $batchReference,
                'borrower_name' => $batchData['borrower_name'],
                'borrower_contact' => $batchData['borrower_contact'] ?? null,
                'expected_return' => $batchData['expected_return'],
                'purpose' => $batchData['purpose'] ?? null,
                'status' => $initialStatus,
                'issued_by' => $batchData['issued_by'],
                'is_critical_batch' => $isCriticalBatch ? 1 : 0,
                'total_items' => count($validatedItems),
                'total_quantity' => $totalQuantity,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if (!$batch) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to create batch'];
            }

            // Create individual borrowed_tools records
            $borrowedToolModel = new BorrowedToolModel();
            $currentDateTime = date('Y-m-d H:i:s');

            foreach ($validatedItems as $item) {
                $borrowData = [
                    'batch_id' => $batch['id'],
                    'asset_id' => $item['asset']['id'],
                    'quantity' => $item['quantity'],
                    'quantity_returned' => 0,
                    'borrower_name' => $batchData['borrower_name'],
                    'borrower_contact' => $batchData['borrower_contact'] ?? null,
                    'expected_return' => $batchData['expected_return'],
                    'issued_by' => $batchData['issued_by'],
                    'purpose' => $batchData['purpose'] ?? null,
                    'line_notes' => $item['line_notes'],
                    'status' => $initialStatus,
                    'created_at' => $currentDateTime
                ];

                // For streamlined workflow (basic tools only)
                if ($initialStatus === 'Approved') {
                    $borrowData['verified_by'] = $batchData['issued_by'];
                    $borrowData['verification_date'] = $currentDateTime;
                    $borrowData['approved_by'] = $batchData['issued_by'];
                    $borrowData['approval_date'] = $currentDateTime;
                }

                $created = $borrowedToolModel->create($borrowData);

                if (!$created) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Failed to create line item for ' . $item['asset']['name']];
                }
            }

            // Log activity
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
     * Get batch with all items
     */
    public function getBatchWithItems($batchId, $projectId = null) {
        try {
            $conditions = ["btb.id = ?"];
            $params = [$batchId];

            // Optional project filtering
            if ($projectId) {
                // Check if all items in batch belong to project
                $checkSql = "SELECT COUNT(DISTINCT a.project_id) as project_count
                           FROM borrowed_tools bt
                           INNER JOIN assets a ON bt.asset_id = a.id
                           WHERE bt.batch_id = ?";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([$batchId]);
                $result = $checkStmt->fetch();

                // If batch has items from multiple projects or wrong project, deny access
                if ($result['project_count'] > 1) {
                    return null; // Cross-project batch
                }
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            // Get batch info
            $batchSql = "
                SELECT btb.*,
                       u_issued.full_name as issued_by_name,
                       u_verified.full_name as verified_by_name,
                       u_approved.full_name as approved_by_name,
                       u_released.full_name as released_by_name,
                       u_returned.full_name as returned_by_name,
                       u_canceled.full_name as canceled_by_name
                FROM borrowed_tool_batches btb
                LEFT JOIN users u_issued ON btb.issued_by = u_issued.id
                LEFT JOIN users u_verified ON btb.verified_by = u_verified.id
                LEFT JOIN users u_approved ON btb.approved_by = u_approved.id
                LEFT JOIN users u_released ON btb.released_by = u_released.id
                LEFT JOIN users u_returned ON btb.returned_by = u_returned.id
                LEFT JOIN users u_canceled ON btb.canceled_by = u_canceled.id
                {$whereClause}
            ";

            $batchStmt = $this->db->prepare($batchSql);
            $batchStmt->execute($params);
            $batch = $batchStmt->fetch();

            if (!$batch) {
                return null;
            }

            // Get all items in batch
            $itemsSql = "
                SELECT bt.*,
                       a.name as asset_name,
                       a.ref as asset_ref,
                       a.acquisition_cost,
                       c.name as category_name,
                       et.name as equipment_type_name,
                       p.name as project_name
                FROM borrowed_tools bt
                INNER JOIN assets a ON bt.asset_id = a.id
                INNER JOIN categories c ON a.category_id = c.id
                LEFT JOIN equipment_types et ON a.equipment_type_id = et.id
                INNER JOIN projects p ON a.project_id = p.id
                WHERE bt.batch_id = ?
                ORDER BY a.name ASC
            ";

            $itemsStmt = $this->db->prepare($itemsSql);
            $itemsStmt->execute([$batchId]);
            $batch['items'] = $itemsStmt->fetchAll();

            return $batch;

        } catch (Exception $e) {
            error_log("Get batch with items error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify batch (Verifier step in MVA workflow)
     */
    public function verifyBatch($batchId, $verifiedBy, $notes = null) {
        try {
            $this->db->beginTransaction();

            $batch = $this->find($batchId);
            if (!$batch) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Batch not found'];
            }

            if ($batch['status'] !== 'Pending Verification') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Batch is not in pending verification status'];
            }

            // Update batch
            $updated = $this->update($batchId, [
                'status' => 'Pending Approval',
                'verified_by' => $verifiedBy,
                'verification_date' => date('Y-m-d H:i:s'),
                'verification_notes' => $notes
            ]);

            if (!$updated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to verify batch'];
            }

            // Update all items in batch
            $updateItemsSql = "
                UPDATE borrowed_tools
                SET status = 'Pending Approval',
                    verified_by = ?,
                    verification_date = NOW()
                WHERE batch_id = ?
            ";
            $stmt = $this->db->prepare($updateItemsSql);
            $stmt->execute([$verifiedBy, $batchId]);

            $this->logActivity('verify_batch', "Batch {$batch['batch_reference']} verified", 'borrowed_tool_batches', $batchId);

            $this->db->commit();
            return ['success' => true, 'message' => 'Batch verified successfully'];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Batch verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to verify batch'];
        }
    }

    /**
     * Approve batch (Authorizer step in MVA workflow)
     */
    public function approveBatch($batchId, $approvedBy, $notes = null) {
        try {
            $this->db->beginTransaction();

            $batch = $this->find($batchId);
            if (!$batch) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Batch not found'];
            }

            if ($batch['status'] !== 'Pending Approval') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Batch is not in pending approval status'];
            }

            // Update batch
            $updated = $this->update($batchId, [
                'status' => 'Approved',
                'approved_by' => $approvedBy,
                'approval_date' => date('Y-m-d H:i:s'),
                'approval_notes' => $notes
            ]);

            if (!$updated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to approve batch'];
            }

            // Update all items in batch
            $updateItemsSql = "
                UPDATE borrowed_tools
                SET status = 'Approved',
                    approved_by = ?,
                    approval_date = NOW()
                WHERE batch_id = ?
            ";
            $stmt = $this->db->prepare($updateItemsSql);
            $stmt->execute([$approvedBy, $batchId]);

            $this->logActivity('approve_batch', "Batch {$batch['batch_reference']} approved", 'borrowed_tool_batches', $batchId);

            $this->db->commit();
            return ['success' => true, 'message' => 'Batch approved successfully'];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Batch approval error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve batch'];
        }
    }

    /**
     * Release batch (mark as physically handed over to borrower)
     */
    public function releaseBatch($batchId, $releasedBy, $notes = null) {
        try {
            $this->db->beginTransaction();

            $batch = $this->find($batchId);
            if (!$batch) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Batch not found'];
            }

            if ($batch['status'] !== 'Approved') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Batch must be approved before release'];
            }

            // Update batch
            $updated = $this->update($batchId, [
                'status' => 'Released',
                'released_by' => $releasedBy,
                'release_date' => date('Y-m-d H:i:s'),
                'release_notes' => $notes
            ]);

            if (!$updated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to release batch'];
            }

            // Update all items in batch and their asset statuses
            $updateItemsSql = "
                UPDATE borrowed_tools
                SET status = 'Borrowed',
                    borrowed_by = ?,
                    borrowed_date = NOW()
                WHERE batch_id = ?
            ";
            $stmt = $this->db->prepare($updateItemsSql);
            $stmt->execute([$releasedBy, $batchId]);

            // Update asset statuses to borrowed
            $updateAssetsSql = "
                UPDATE assets a
                INNER JOIN borrowed_tools bt ON a.id = bt.asset_id
                SET a.status = 'borrowed'
                WHERE bt.batch_id = ?
            ";
            $assetStmt = $this->db->prepare($updateAssetsSql);
            $assetStmt->execute([$batchId]);

            $this->logActivity('release_batch', "Batch {$batch['batch_reference']} released to {$batch['borrower_name']}", 'borrowed_tool_batches', $batchId);

            $this->db->commit();
            return ['success' => true, 'message' => 'Batch released successfully'];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Batch release error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to release batch'];
        }
    }

    /**
     * Return batch (full or partial)
     */
    public function returnBatch($batchId, $returnedBy, $returnedItems, $notes = null) {
        try {
            $this->db->beginTransaction();

            $batch = $this->find($batchId);
            if (!$batch) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Batch not found'];
            }

            if (!in_array($batch['status'], ['Released', 'Partially Returned'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Batch is not currently released or partially returned'];
            }

            // Process each returned item
            $borrowedToolModel = new BorrowedToolModel();

            foreach ($returnedItems as $item) {
                $borrowToolId = $item['borrowed_tool_id'];
                $quantityReturned = (int)$item['quantity_returned'];
                $conditionIn = $item['condition_in'] ?? 'Good';

                // CONCURRENCY FIX: Lock borrowed_tools row to prevent double-return
                // This prevents race condition where two users simultaneously return the same item
                $lockSql = "SELECT * FROM borrowed_tools WHERE id = ? FOR UPDATE";
                $lockStmt = $this->db->prepare($lockSql);
                $lockStmt->execute([$borrowToolId]);
                $borrowedTool = $lockStmt->fetch(PDO::FETCH_ASSOC);

                if (!$borrowedTool || $borrowedTool['batch_id'] != $batchId) {
                    continue; // Skip invalid items
                }

                $newTotalReturned = $borrowedTool['quantity_returned'] + $quantityReturned;
                if ($newTotalReturned > $borrowedTool['quantity']) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Cannot return more than borrowed quantity for ' . $borrowedTool['asset_id']];
                }

                // Update borrowed_tools record
                $updateData = [
                    'quantity_returned' => $newTotalReturned,
                    'condition_returned' => $conditionIn
                ];

                // If fully returned, mark as complete
                if ($newTotalReturned >= $borrowedTool['quantity']) {
                    $updateData['status'] = 'Returned';
                    $updateData['actual_return'] = date('Y-m-d');
                    $updateData['returned_by'] = $returnedBy;
                    $updateData['return_date'] = date('Y-m-d H:i:s');
                    $updateData['condition_in'] = $conditionIn;

                    // Update asset status back to available
                    $assetModel = new AssetModel();
                    $assetModel->update($borrowedTool['asset_id'], ['status' => 'available']);
                }

                $borrowedToolModel->update($borrowToolId, $updateData);
            }

            // Check if ALL items in the batch are fully returned
            $checkAllReturnedSql = "
                SELECT
                    COUNT(*) as total_items,
                    SUM(CASE WHEN quantity_returned >= quantity THEN 1 ELSE 0 END) as fully_returned_items
                FROM borrowed_tools
                WHERE batch_id = ?
            ";
            $checkStmt = $this->db->prepare($checkAllReturnedSql);
            $checkStmt->execute([$batchId]);
            $batchReturnStatus = $checkStmt->fetch(PDO::FETCH_ASSOC);

            $allItemsReturned = ($batchReturnStatus['total_items'] == $batchReturnStatus['fully_returned_items']);

            // Update batch status
            $batchStatus = $allItemsReturned ? 'Returned' : 'Partially Returned';
            $batchUpdate = [
                'status' => $batchStatus,
                'return_notes' => $notes
            ];

            if ($allItemsReturned) {
                $batchUpdate['actual_return'] = date('Y-m-d');
                $batchUpdate['returned_by'] = $returnedBy;
                $batchUpdate['return_date'] = date('Y-m-d H:i:s');
            }

            $this->update($batchId, $batchUpdate);

            $this->logActivity('return_batch', "Batch {$batch['batch_reference']} " . ($allItemsReturned ? 'fully' : 'partially') . " returned", 'borrowed_tool_batches', $batchId);

            $this->db->commit();
            return ['success' => true, 'message' => 'Batch return processed successfully', 'fully_returned' => $allItemsReturned];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Batch return error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process batch return'];
        }
    }

    /**
     * Cancel batch
     */
    public function cancelBatch($batchId, $canceledBy, $reason = null) {
        try {
            $this->db->beginTransaction();

            $batch = $this->find($batchId);
            if (!$batch) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Batch not found'];
            }

            if (!in_array($batch['status'], ['Pending Verification', 'Pending Approval', 'Approved'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Cannot cancel batch at this stage'];
            }

            // Update batch
            $updated = $this->update($batchId, [
                'status' => 'Canceled',
                'canceled_by' => $canceledBy,
                'cancellation_date' => date('Y-m-d H:i:s'),
                'cancellation_reason' => $reason
            ]);

            if (!$updated) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to cancel batch'];
            }

            // Update all items in batch
            $updateItemsSql = "
                UPDATE borrowed_tools
                SET status = 'Canceled',
                    canceled_by = ?,
                    cancellation_date = NOW(),
                    cancellation_reason = ?
                WHERE batch_id = ?
            ";
            $stmt = $this->db->prepare($updateItemsSql);
            $stmt->execute([$canceledBy, $reason, $batchId]);

            $this->logActivity('cancel_batch', "Batch {$batch['batch_reference']} canceled", 'borrowed_tool_batches', $batchId);

            $this->db->commit();
            return ['success' => true, 'message' => 'Batch canceled successfully'];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Batch cancellation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to cancel batch'];
        }
    }

    /**
     * Get batches with filters and pagination
     */
    public function getBatchesWithFilters($filters = [], $page = 1, $perPage = 20) {
        $conditions = [];
        $params = [];

        // Status filter
        if (!empty($filters['status'])) {
            $conditions[] = "btb.status = ?";
            $params[] = $filters['status'];
        }

        // Date range filters
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(btb.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(btb.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $conditions[] = "(btb.borrower_name LIKE ? OR btb.batch_reference LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }

        // Project filter
        if (!empty($filters['project_id'])) {
            $conditions[] = "EXISTS (
                SELECT 1 FROM borrowed_tools bt
                INNER JOIN assets a ON bt.asset_id = a.id
                WHERE bt.batch_id = btb.id AND a.project_id = ?
            )";
            $params[] = $filters['project_id'];
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Count total
        $countSql = "SELECT COUNT(*) FROM borrowed_tool_batches btb {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Get paginated data
        $offset = ($page - 1) * $perPage;

        $dataSql = "
            SELECT btb.*,
                   u_issued.full_name as issued_by_name
            FROM borrowed_tool_batches btb
            LEFT JOIN users u_issued ON btb.issued_by = u_issued.id
            {$whereClause}
            ORDER BY btb.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $dataStmt = $this->db->prepare($dataSql);
        $dataStmt->execute($params);
        $data = $dataStmt->fetchAll();

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
     * Get batch statistics
     */
    public function getBatchStats($dateFrom = null, $dateTo = null, $projectId = null) {
        $conditions = [];
        $params = [];

        if ($dateFrom) {
            $conditions[] = "DATE(btb.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $conditions[] = "DATE(btb.created_at) <= ?";
            $params[] = $dateTo;
        }

        if ($projectId) {
            $conditions[] = "EXISTS (
                SELECT 1 FROM borrowed_tools bt
                INNER JOIN assets a ON bt.asset_id = a.id
                WHERE bt.batch_id = btb.id AND a.project_id = ?
            )";
            $params[] = $projectId;
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "
            SELECT
                COUNT(*) as total_batches,
                COUNT(CASE WHEN btb.status = 'Pending Verification' THEN 1 END) as pending_verification,
                COUNT(CASE WHEN btb.status = 'Pending Approval' THEN 1 END) as pending_approval,
                COUNT(CASE WHEN btb.status = 'Approved' THEN 1 END) as approved,
                COUNT(CASE WHEN btb.status = 'Released' THEN 1 END) as released,
                COUNT(CASE WHEN btb.status = 'Partially Returned' THEN 1 END) as partially_returned,
                COUNT(CASE WHEN btb.status = 'Returned' THEN 1 END) as returned,
                COUNT(CASE WHEN btb.status = 'Canceled' THEN 1 END) as canceled,
                SUM(btb.total_items) as total_items_borrowed,
                SUM(btb.total_quantity) as total_quantity_borrowed
            FROM borrowed_tool_batches btb
            {$whereClause}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Get count of overdue batches
     */
    public function getOverdueBatchCount($projectId = null) {
        $conditions = [
            "btb.status = 'Released'",
            "btb.expected_return < CURDATE()"
        ];
        $params = [];

        if ($projectId) {
            $conditions[] = "EXISTS (
                SELECT 1 FROM borrowed_tools bt
                INNER JOIN assets a ON bt.asset_id = a.id
                WHERE bt.batch_id = btb.id AND a.project_id = ?
            )";
            $params[] = $projectId;
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);

        $sql = "
            SELECT COUNT(*) as overdue_count
            FROM borrowed_tool_batches btb
            {$whereClause}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['overdue_count'] ?? 0;
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

                    if (!in_array($item['status'], ['Borrowed', 'Released'])) {
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
}
?>
