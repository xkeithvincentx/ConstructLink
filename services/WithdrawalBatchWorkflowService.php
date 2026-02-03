<?php
/**
 * ConstructLink Withdrawal Batch Workflow Service
 *
 * Handles MVA (Maker-Verifier-Authorizer) workflow operations for withdrawal batches.
 * Extracted from WithdrawalBatchModel to follow Single Responsibility Principle.
 *
 * This service manages:
 * - Batch verification (Verifier step)
 * - Batch approval (Authorizer step)
 * - Batch release (Physical handover with QUANTITY DEDUCTION)
 * - Batch cancellation (With quantity restoration if released)
 *
 * CRITICAL DIFFERENCE FROM BORROWED TOOLS:
 * - Release: Deducts quantity from available_quantity (NOT status change)
 * - Cancel after release: Restores quantity back to available_quantity
 *
 * @package ConstructLink
 * @version 1.0.0
 */

require_once APP_ROOT . '/helpers/WithdrawalBatchStatus.php';
require_once APP_ROOT . '/core/utils/ResponseFormatter.php';
require_once APP_ROOT . '/core/traits/ActivityLoggingTrait.php';

class WithdrawalBatchWorkflowService {
    use ActivityLoggingTrait;

    private $db;
    private $batchModel;
    private $withdrawalModel;

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     * @param WithdrawalBatchModel|null $batchModel Batch model instance
     * @param WithdrawalModel|null $withdrawalModel Withdrawal model instance
     */
    public function __construct($db = null, $batchModel = null, $withdrawalModel = null) {
        if ($db === null) {
            require_once APP_ROOT . '/core/Database.php';
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }

        require_once APP_ROOT . '/models/WithdrawalBatchModel.php';
        require_once APP_ROOT . '/models/WithdrawalModel.php';

        $this->batchModel = $batchModel ?? new WithdrawalBatchModel($this->db);
        $this->withdrawalModel = $withdrawalModel ?? new WithdrawalModel($this->db);
    }

    /**
     * Verify batch or single item (Verifier step in MVA workflow)
     *
     * @param int $batchId Batch ID or withdrawal ID (if single item)
     * @param int $verifiedBy User ID of verifier
     * @param string|null $notes Optional verification notes
     * @param bool $isSingleItem Whether this is a single item (true) or batch (false)
     * @return array Response with success status
     */
    public function verifyBatch($batchId, $verifiedBy, $notes = null, $isSingleItem = false) {
        try {
            $this->db->beginTransaction();

            if ($isSingleItem) {
                // For single items, $batchId is actually the withdrawal ID
                $withdrawalId = $batchId;

                // Get single withdrawal item
                $getItemSql = "SELECT w.* FROM withdrawals w WHERE w.id = ?";
                $stmt = $this->db->prepare($getItemSql);
                $stmt->execute([$withdrawalId]);
                $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$withdrawal) {
                    $this->db->rollBack();
                    return ResponseFormatter::notFound('Withdrawal');
                }

                if ($withdrawal['status'] !== WithdrawalBatchStatus::PENDING_VERIFICATION) {
                    $this->db->rollBack();
                    return ResponseFormatter::invalidStatus(
                        $withdrawal['status'],
                        WithdrawalBatchStatus::PENDING_VERIFICATION
                    );
                }

                // Update single withdrawal item
                $updateItemSql = "
                    UPDATE withdrawals
                    SET status = ?,
                        verified_by = ?,
                        verification_date = NOW()
                    WHERE id = ?
                ";
                $stmt = $this->db->prepare($updateItemSql);
                $updated = $stmt->execute([WithdrawalBatchStatus::PENDING_APPROVAL, $verifiedBy, $withdrawalId]);

                if (!$updated) {
                    $this->db->rollBack();
                    return ResponseFormatter::error('Failed to verify withdrawal');
                }

                $this->logActivity(
                    'verify_withdrawal',
                    "Withdrawal #{$withdrawalId} verified",
                    'withdrawals',
                    $withdrawalId
                );

                $this->db->commit();
                return ResponseFormatter::success('Withdrawal verified successfully');

            } else {
                // For batches
                $batch = $this->batchModel->find($batchId);
                if (!$batch) {
                    $this->db->rollBack();
                    return ResponseFormatter::notFound('Batch');
                }

                if ($batch['status'] !== WithdrawalBatchStatus::PENDING_VERIFICATION) {
                    $this->db->rollBack();
                    return ResponseFormatter::invalidStatus(
                        $batch['status'],
                        WithdrawalBatchStatus::PENDING_VERIFICATION
                    );
                }

                // Update batch status
                $updated = $this->batchModel->update($batchId, [
                    'status' => WithdrawalBatchStatus::PENDING_APPROVAL,
                    'verified_by' => $verifiedBy,
                    'verification_date' => date('Y-m-d H:i:s'),
                    'verification_notes' => $notes
                ]);

                if (!$updated) {
                    $this->db->rollBack();
                    return ResponseFormatter::error('Failed to verify batch');
                }

                // Update all items in batch
                $updateItemsSql = "
                    UPDATE withdrawals
                    SET status = ?,
                        verified_by = ?,
                        verification_date = NOW()
                    WHERE batch_id = ?
                ";
                $stmt = $this->db->prepare($updateItemsSql);
                $stmt->execute([WithdrawalBatchStatus::PENDING_APPROVAL, $verifiedBy, $batchId]);

                // Log to withdrawal_batch_logs
                $this->logBatchAction($batchId, 'verified', $verifiedBy, $notes);

                $this->logActivity(
                    'verify_withdrawal_batch',
                    "Batch {$batch['batch_reference']} verified",
                    'withdrawal_batches',
                    $batchId
                );

                $this->db->commit();
                return ResponseFormatter::success('Batch verified successfully');
            }

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Withdrawal batch verification error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to verify batch');
        }
    }

    /**
     * Approve batch or single item (Authorizer step in MVA workflow)
     *
     * @param int $batchId Batch ID or withdrawal ID (if single item)
     * @param int $approvedBy User ID of authorizer
     * @param string|null $notes Optional approval notes
     * @param bool $isSingleItem Whether this is a single item (true) or batch (false)
     * @return array Response with success status
     */
    public function approveBatch($batchId, $approvedBy, $notes = null, $isSingleItem = false) {
        try {
            $this->db->beginTransaction();

            if ($isSingleItem) {
                // For single items, $batchId is actually the withdrawal ID
                $withdrawalId = $batchId;

                // Get single withdrawal item with inventory details
                $getItemSql = "
                    SELECT w.*, i.name as item_name, i.available_quantity
                    FROM withdrawals w
                    INNER JOIN inventory_items i ON w.inventory_item_id = i.id
                    WHERE w.id = ?
                ";
                $stmt = $this->db->prepare($getItemSql);
                $stmt->execute([$withdrawalId]);
                $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$withdrawal) {
                    $this->db->rollBack();
                    return ResponseFormatter::notFound('Withdrawal');
                }

                if ($withdrawal['status'] !== WithdrawalBatchStatus::PENDING_APPROVAL) {
                    $this->db->rollBack();
                    return ResponseFormatter::invalidStatus(
                        $withdrawal['status'],
                        WithdrawalBatchStatus::PENDING_APPROVAL
                    );
                }

                // CRITICAL: Check available quantity before approval (reserve on approval)
                if ($withdrawal['available_quantity'] < $withdrawal['quantity']) {
                    $this->db->rollBack();
                    return ResponseFormatter::error(
                        "Cannot approve: Insufficient quantity for {$withdrawal['item_name']}. " .
                        "Requested: {$withdrawal['quantity']}, Available: {$withdrawal['available_quantity']}. " .
                        "This item may have been withdrawn by another user."
                    );
                }

                // ATOMIC QUANTITY RESERVATION - Deduct from available_quantity
                $reserveSql = "
                    UPDATE inventory_items
                    SET available_quantity = available_quantity - ?
                    WHERE id = ?
                      AND available_quantity >= ?
                ";
                $reserveStmt = $this->db->prepare($reserveSql);
                $reserveStmt->execute([$withdrawal['quantity'], $withdrawal['inventory_item_id'], $withdrawal['quantity']]);

                $affectedRows = $reserveStmt->rowCount();
                if ($affectedRows === 0) {
                    $this->db->rollBack();
                    return ResponseFormatter::error(
                        "Failed to reserve quantity for {$withdrawal['item_name']}. " .
                        "Possible concurrent withdrawal detected. Please try again."
                    );
                }

                // Update single withdrawal item
                $updateItemSql = "
                    UPDATE withdrawals
                    SET status = ?,
                        approved_by = ?,
                        approval_date = NOW()
                    WHERE id = ?
                ";
                $stmt = $this->db->prepare($updateItemSql);
                $updated = $stmt->execute([WithdrawalBatchStatus::APPROVED, $approvedBy, $withdrawalId]);

                if (!$updated) {
                    $this->db->rollBack();
                    return ResponseFormatter::error('Failed to approve withdrawal');
                }

                $this->logActivity(
                    'approve_withdrawal',
                    "Withdrawal #{$withdrawalId} approved and {$withdrawal['quantity']} units reserved",
                    'withdrawals',
                    $withdrawalId
                );

                $this->db->commit();
                return ResponseFormatter::success('Withdrawal approved successfully and quantity reserved');

            } else {
                // For batches
                $batch = $this->batchModel->find($batchId);
                if (!$batch) {
                    $this->db->rollBack();
                    return ResponseFormatter::notFound('Batch');
                }

                if ($batch['status'] !== WithdrawalBatchStatus::PENDING_APPROVAL) {
                    $this->db->rollBack();
                    return ResponseFormatter::invalidStatus(
                        $batch['status'],
                        WithdrawalBatchStatus::PENDING_APPROVAL
                    );
                }

                // Get all withdrawal items in this batch with inventory details
                $getItemsSql = "
                    SELECT w.id, w.inventory_item_id, w.quantity, i.name, i.available_quantity
                    FROM withdrawals w
                    INNER JOIN inventory_items i ON w.inventory_item_id = i.id
                    WHERE w.batch_id = ?
                ";
                $stmt = $this->db->prepare($getItemsSql);
                $stmt->execute([$batchId]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($items)) {
                    $this->db->rollBack();
                    return ResponseFormatter::error('No items found in batch');
                }

                // CRITICAL: Check all items have sufficient quantity before approval
                $insufficientItems = [];
                foreach ($items as $item) {
                    if ($item['available_quantity'] < $item['quantity']) {
                        $insufficientItems[] = "{$item['name']} (Requested: {$item['quantity']}, Available: {$item['available_quantity']})";
                    }
                }

                if (!empty($insufficientItems)) {
                    $this->db->rollBack();
                    return ResponseFormatter::error(
                        "Cannot approve batch: Insufficient quantity for the following items:\n" .
                        implode("\n", $insufficientItems) . "\n\n" .
                        "These items may have been withdrawn by another user. Please review and adjust quantities."
                    );
                }

                // ATOMIC QUANTITY RESERVATION - Deduct from available_quantity for each item
                $totalReserved = 0;
                foreach ($items as $item) {
                    $reserveSql = "
                        UPDATE inventory_items
                        SET available_quantity = available_quantity - ?
                        WHERE id = ?
                          AND available_quantity >= ?
                    ";
                    $reserveStmt = $this->db->prepare($reserveSql);
                    $reserveStmt->execute([$item['quantity'], $item['inventory_item_id'], $item['quantity']]);

                    $affectedRows = $reserveStmt->rowCount();
                    if ($affectedRows === 0) {
                        $this->db->rollBack();
                        return ResponseFormatter::error(
                            "Failed to reserve quantity for {$item['name']}. " .
                            "Possible concurrent withdrawal detected. Please try again."
                        );
                    }
                    $totalReserved += $item['quantity'];
                }

                // Update batch status
                $updated = $this->batchModel->update($batchId, [
                    'status' => WithdrawalBatchStatus::APPROVED,
                    'approved_by' => $approvedBy,
                    'approval_date' => date('Y-m-d H:i:s'),
                    'approval_notes' => $notes
                ]);

                if (!$updated) {
                    $this->db->rollBack();
                    return ResponseFormatter::error('Failed to approve batch');
                }

                // Update all items in batch
                $updateItemsSql = "
                    UPDATE withdrawals
                    SET status = ?,
                        approved_by = ?,
                        approval_date = NOW()
                    WHERE batch_id = ?
                ";
                $stmt = $this->db->prepare($updateItemsSql);
                $stmt->execute([WithdrawalBatchStatus::APPROVED, $approvedBy, $batchId]);

                // Log to withdrawal_batch_logs
                $this->logBatchAction($batchId, 'approved', $approvedBy, $notes);

                $this->logActivity(
                    'approve_withdrawal_batch',
                    "Batch {$batch['batch_reference']} approved and {$totalReserved} units reserved from inventory",
                    'withdrawal_batches',
                    $batchId
                );

                $this->db->commit();
                return ResponseFormatter::success("Batch approved successfully and {$totalReserved} units reserved from inventory");
            }

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Withdrawal batch approval error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to approve batch');
        }
    }

    /**
     * Release batch or single item (mark as physically handed over to receiver)
     *
     * CRITICAL LOGIC CHANGE: Quantities are now RESERVED at approval, not at release.
     * This method ONLY marks items as physically released - no quantity deduction.
     *
     * @param int $batchId Batch ID or withdrawal ID (if single item)
     * @param int $releasedBy User ID who released the batch
     * @param string|null $notes Optional release notes
     * @param bool $isSingleItem Whether this is a single item (true) or batch (false)
     * @return array Response with success status
     */
    public function releaseBatch($batchId, $releasedBy, $notes = null, $isSingleItem = false) {
        try {
            $this->db->beginTransaction();

            if ($isSingleItem) {
                // For single items, $batchId is actually the withdrawal ID
                $withdrawalId = $batchId;

                // Get single withdrawal item with inventory details
                $getItemSql = "
                    SELECT w.id, w.inventory_item_id, w.quantity, w.status, i.name, i.available_quantity
                    FROM withdrawals w
                    INNER JOIN inventory_items i ON w.inventory_item_id = i.id
                    WHERE w.id = ?
                ";
                $stmt = $this->db->prepare($getItemSql);
                $stmt->execute([$withdrawalId]);
                $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$withdrawal) {
                    $this->db->rollBack();
                    return ResponseFormatter::notFound('Withdrawal');
                }

                if ($withdrawal['status'] !== WithdrawalBatchStatus::APPROVED) {
                    $this->db->rollBack();
                    return ResponseFormatter::invalidStatus(
                        $withdrawal['status'],
                        WithdrawalBatchStatus::APPROVED
                    );
                }

                // NOTE: Quantity was already reserved at approval - no need to deduct again
                // This release step just marks the item as physically handed over

                // Update single withdrawal item to Released status
                $updateItemSql = "
                    UPDATE withdrawals
                    SET status = ?,
                        released_by = ?,
                        release_date = NOW()
                    WHERE id = ?
                ";
                $stmt = $this->db->prepare($updateItemSql);
                $updated = $stmt->execute([WithdrawalBatchStatus::RELEASED, $releasedBy, $withdrawalId]);

                if (!$updated) {
                    $this->db->rollBack();
                    return ResponseFormatter::error('Failed to release withdrawal');
                }

                $this->logActivity(
                    'release_withdrawal',
                    "Withdrawal #{$withdrawalId} physically released to receiver",
                    'withdrawals',
                    $withdrawalId
                );

                $this->db->commit();
                return ResponseFormatter::success('Withdrawal released successfully');

            } else {
                // For batches
                $batch = $this->batchModel->find($batchId);
                if (!$batch) {
                    $this->db->rollBack();
                    return ResponseFormatter::notFound('Batch');
                }

                if ($batch['status'] !== WithdrawalBatchStatus::APPROVED) {
                    $this->db->rollBack();
                    return ResponseFormatter::invalidStatus(
                        $batch['status'],
                        WithdrawalBatchStatus::APPROVED
                    );
                }

                // NOTE: Quantities were already reserved at approval - no need to deduct again
                // This release step just marks items as physically handed over

                // Update batch status to Released
                $updated = $this->batchModel->update($batchId, [
                    'status' => WithdrawalBatchStatus::RELEASED,
                    'released_by' => $releasedBy,
                    'release_date' => date('Y-m-d H:i:s'),
                    'release_notes' => $notes
                ]);

                if (!$updated) {
                    $this->db->rollBack();
                    return ResponseFormatter::error('Failed to release batch');
                }

                // Update all items in batch to Released status
                $updateItemsSql = "
                    UPDATE withdrawals
                    SET status = ?,
                        released_by = ?,
                        release_date = NOW()
                    WHERE batch_id = ?
                ";
                $itemStmt = $this->db->prepare($updateItemsSql);
                $itemStmt->execute([WithdrawalBatchStatus::RELEASED, $releasedBy, $batchId]);

                // Log to withdrawal_batch_logs
                $this->logBatchAction($batchId, 'released', $releasedBy, $notes);

                $this->logActivity(
                    'release_withdrawal_batch',
                    "Batch {$batch['batch_reference']} physically released to {$batch['receiver_name']}",
                    'withdrawal_batches',
                    $batchId
                );

                $this->db->commit();
                return ResponseFormatter::success('Batch released successfully');
            }

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Withdrawal batch release error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to release batch');
        }
    }

    /**
     * Cancel batch
     *
     * CRITICAL LOGIC CHANGE (Reserve on Approval):
     * - If batch status is Approved or Released: Must restore reserved quantities to inventory
     * - If batch is Pending Verification/Approval: Just cancel without quantity changes (nothing reserved yet)
     *
     * @param int $batchId Batch ID
     * @param int $canceledBy User ID who canceled
     * @param string|null $reason Optional cancellation reason
     * @return array Response with success status
     */
    public function cancelBatch($batchId, $canceledBy, $reason = null) {
        try {
            $this->db->beginTransaction();

            $batch = $this->batchModel->find($batchId);
            if (!$batch) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Batch');
            }

            $validCancelStatuses = [
                WithdrawalBatchStatus::PENDING_VERIFICATION,
                WithdrawalBatchStatus::PENDING_APPROVAL,
                WithdrawalBatchStatus::APPROVED,
                WithdrawalBatchStatus::RELEASED  // Can cancel even after release (with quantity restoration)
            ];

            if (!in_array($batch['status'], $validCancelStatuses)) {
                $this->db->rollBack();
                return ResponseFormatter::error(
                    'Cannot cancel batch at this stage. Batch is already in final state.'
                );
            }

            // CRITICAL: If batch was approved or released, restore reserved quantities
            // Quantities are reserved at approval, so both Approved and Released need restoration
            if ($batch['status'] === WithdrawalBatchStatus::APPROVED || $batch['status'] === WithdrawalBatchStatus::RELEASED) {
                // Get all withdrawal items in this batch
                $getItemsSql = "
                    SELECT w.id, w.inventory_item_id, w.quantity, i.name
                    FROM withdrawals w
                    INNER JOIN inventory_items i ON w.inventory_item_id = i.id
                    WHERE w.batch_id = ?
                ";
                $stmt = $this->db->prepare($getItemsSql);
                $stmt->execute([$batchId]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // ATOMIC QUANTITY RESTORATION
                foreach ($items as $item) {
                    $restoreSql = "
                        UPDATE inventory_items
                        SET available_quantity = available_quantity + ?
                        WHERE id = ?
                    ";
                    $restoreStmt = $this->db->prepare($restoreSql);
                    $restoreStmt->execute([$item['quantity'], $item['inventory_item_id']]);
                }

                $this->logActivity(
                    'restore_quantity',
                    "Restored {$batch['total_quantity']} units to inventory due to batch cancellation (was {$batch['status']})",
                    'withdrawal_batches',
                    $batchId
                );
            } else {
                // Batch was not approved yet - no quantities to restore
                $this->logActivity(
                    'cancel_withdrawal_batch',
                    "Batch {$batch['batch_reference']} canceled before approval - no quantities to restore",
                    'withdrawal_batches',
                    $batchId
                );
            }

            // Update batch status
            $updated = $this->batchModel->update($batchId, [
                'status' => WithdrawalBatchStatus::CANCELED,
                'canceled_by' => $canceledBy,
                'cancellation_date' => date('Y-m-d H:i:s'),
                'cancellation_reason' => $reason
            ]);

            if (!$updated) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to cancel batch');
            }

            // Update all items in batch
            $updateItemsSql = "
                UPDATE withdrawals
                SET status = ?,
                    canceled_by = ?,
                    cancellation_date = NOW(),
                    cancellation_reason = ?
                WHERE batch_id = ?
            ";
            $itemStmt = $this->db->prepare($updateItemsSql);
            $itemStmt->execute([WithdrawalBatchStatus::CANCELED, $canceledBy, $reason, $batchId]);

            // Log to withdrawal_batch_logs
            $this->logBatchAction($batchId, 'canceled', $canceledBy, $reason);

            $this->logActivity(
                'cancel_withdrawal_batch',
                "Batch {$batch['batch_reference']} canceled" . ($reason ? ": {$reason}" : ''),
                'withdrawal_batches',
                $batchId
            );

            $this->db->commit();

            $message = 'Batch canceled successfully';
            if ($batch['status'] === WithdrawalBatchStatus::RELEASED) {
                $message .= ' and quantities restored to inventory';
            }

            return ResponseFormatter::success($message);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Withdrawal batch cancellation error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to cancel batch');
        }
    }

    /**
     * Return batch or single item (restore consumables to inventory)
     *
     * CRITICAL LOGIC: Restores quantities back to inventory_items.available_quantity
     * Supports partial returns - only the quantities specified will be restored
     *
     * @param int $batchId Batch ID or withdrawal ID (if single item)
     * @param int $returnedBy User ID who processed return
     * @param array $returnData Return data with quantities and conditions per item
     * @param string|null $notes Optional overall return notes
     * @param bool $isSingleItem Whether this is a single item (true) or batch (false)
     * @return array Response with success status
     */
    public function returnBatch($batchId, $returnedBy, $returnData, $notes = null, $isSingleItem = false) {
        try {
            $this->db->beginTransaction();

            if ($isSingleItem) {
                // For single items, $batchId is actually the withdrawal ID
                $withdrawalId = $batchId;

                // Get single withdrawal item
                $getItemSql = "
                    SELECT w.id, w.inventory_item_id, w.quantity as withdrawn_quantity, w.status, i.name
                    FROM withdrawals w
                    INNER JOIN inventory_items i ON w.inventory_item_id = i.id
                    WHERE w.id = ?
                ";
                $stmt = $this->db->prepare($getItemSql);
                $stmt->execute([$withdrawalId]);
                $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$withdrawal) {
                    $this->db->rollBack();
                    return ResponseFormatter::notFound('Withdrawal');
                }

                // Can only return released withdrawals
                if ($withdrawal['status'] !== WithdrawalBatchStatus::RELEASED) {
                    $this->db->rollBack();
                    return ResponseFormatter::invalidStatus(
                        $withdrawal['status'],
                        WithdrawalBatchStatus::RELEASED
                    );
                }

                $items = [$withdrawal];

            } else {
                // For batches
                $batch = $this->batchModel->find($batchId);
                if (!$batch) {
                    $this->db->rollBack();
                    return ResponseFormatter::notFound('Batch');
                }

                // Can only return released batches
                if ($batch['status'] !== WithdrawalBatchStatus::RELEASED) {
                    $this->db->rollBack();
                    return ResponseFormatter::invalidStatus(
                        $batch['status'],
                        WithdrawalBatchStatus::RELEASED
                    );
                }

                // Get all withdrawal items in this batch
                $getItemsSql = "
                    SELECT w.id, w.inventory_item_id, w.quantity as withdrawn_quantity, i.name
                    FROM withdrawals w
                    INNER JOIN inventory_items i ON w.inventory_item_id = i.id
                    WHERE w.batch_id = ?
                ";
                $stmt = $this->db->prepare($getItemsSql);
                $stmt->execute([$batchId]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if (empty($items)) {
                $this->db->rollBack();
                return ResponseFormatter::error('No items found in batch');
            }

            $returnQuantities = $returnData['return_quantities'] ?? [];
            $returnConditions = $returnData['return_conditions'] ?? [];
            $returnItemNotes = $returnData['return_item_notes'] ?? [];

            $totalReturned = 0;

            // Process each returned item
            foreach ($items as $item) {
                $withdrawalId = $item['id'];
                $returnQty = isset($returnQuantities[$withdrawalId]) ? (int)$returnQuantities[$withdrawalId] : 0;

                if ($returnQty <= 0) {
                    continue; // Skip items with 0 return quantity
                }

                // Validate return quantity doesn't exceed withdrawn quantity
                if ($returnQty > $item['withdrawn_quantity']) {
                    $this->db->rollBack();
                    return ResponseFormatter::error(
                        "Return quantity for {$item['name']} ({$returnQty}) exceeds withdrawn quantity ({$item['withdrawn_quantity']})"
                    );
                }

                // ATOMIC QUANTITY RESTORATION
                $restoreSql = "
                    UPDATE inventory_items
                    SET available_quantity = available_quantity + ?
                    WHERE id = ?
                ";
                $restoreStmt = $this->db->prepare($restoreSql);
                $restoreStmt->execute([$returnQty, $item['inventory_item_id']]);

                // Update withdrawal record with return data
                $condition = $returnConditions[$withdrawalId] ?? 'Good';
                $itemNotes = $returnItemNotes[$withdrawalId] ?? null;

                $updateWithdrawalSql = "
                    UPDATE withdrawals
                    SET status = ?,
                        returned_quantity = ?,
                        return_condition = ?,
                        return_item_notes = ?,
                        returned_by = ?,
                        return_date = NOW(),
                        actual_return = NOW()
                    WHERE id = ?
                ";
                $updateStmt = $this->db->prepare($updateWithdrawalSql);
                $updateStmt->execute([
                    WithdrawalBatchStatus::RETURNED,
                    $returnQty,
                    $condition,
                    $itemNotes,
                    $returnedBy,
                    $withdrawalId
                ]);

                $totalReturned += $returnQty;
            }

            if ($totalReturned === 0) {
                $this->db->rollBack();
                return ResponseFormatter::error('No items to return. Please specify return quantities.');
            }

            // Update batch status to Returned (only for batches, not single items)
            if (!$isSingleItem) {
                $updated = $this->batchModel->update($batchId, [
                    'status' => WithdrawalBatchStatus::RETURNED,
                    'returned_by' => $returnedBy,
                    'return_date' => date('Y-m-d H:i:s'),
                    'return_notes' => $notes
                ]);

                if (!$updated) {
                    $this->db->rollBack();
                    return ResponseFormatter::error('Failed to update batch status');
                }

                // Log to withdrawal_batch_logs
                $this->logBatchAction($batchId, 'returned', $returnedBy, $notes);

                $this->logActivity(
                    'return_withdrawal_batch',
                    "Batch {$batch['batch_reference']} returned with {$totalReturned} units restored to inventory",
                    'withdrawal_batches',
                    $batchId
                );
            } else {
                // Log single item return
                $this->logActivity(
                    'return_withdrawal',
                    "Withdrawal #{$batchId} returned with {$totalReturned} units restored to inventory",
                    'withdrawals',
                    $batchId
                );
            }

            $this->db->commit();
            return ResponseFormatter::success("Return processed successfully. {$totalReturned} units restored to inventory.");

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Withdrawal batch return error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to return batch');
        }
    }

    /**
     * Log batch action to withdrawal_batch_logs table
     *
     * @param int $batchId Batch ID
     * @param string $action Action performed
     * @param int $userId User who performed action
     * @param string|null $notes Optional notes
     */
    private function logBatchAction($batchId, $action, $userId, $notes = null) {
        try {
            $logSql = "
                INSERT INTO withdrawal_batch_logs
                (batch_id, action, user_id, notes, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ";
            $stmt = $this->db->prepare($logSql);
            $stmt->execute([$batchId, $action, $userId, $notes]);
        } catch (Exception $e) {
            error_log("Failed to log batch action: " . $e->getMessage());
            // Don't fail the entire operation if logging fails
        }
    }
}
?>
