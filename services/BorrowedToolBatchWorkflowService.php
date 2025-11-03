<?php
/**
 * ConstructLink™ Borrowed Tool Batch Workflow Service
 *
 * Handles MVA (Maker-Verifier-Authorizer) workflow operations for borrowed tool batches.
 * Extracted from BorrowedToolBatchModel to follow Single Responsibility Principle.
 *
 * This service manages:
 * - Batch verification (Verifier step)
 * - Batch approval (Authorizer step)
 * - Batch release (Physical handover)
 * - Batch cancellation (Before release)
 *
 * @package ConstructLink
 * @version 2.0.0
 */

require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';
require_once APP_ROOT . '/helpers/AssetStatus.php';
require_once APP_ROOT . '/core/utils/ResponseFormatter.php';
require_once APP_ROOT . '/core/traits/ActivityLoggingTrait.php';

class BorrowedToolBatchWorkflowService {
    use ActivityLoggingTrait;

    private $db;
    private $batchModel;
    private $borrowedToolModel;

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     * @param BorrowedToolBatchModel|null $batchModel Batch model instance
     * @param BorrowedToolModel|null $borrowedToolModel Borrowed tool model instance
     */
    public function __construct($db = null, $batchModel = null, $borrowedToolModel = null) {
        if ($db === null) {
            require_once APP_ROOT . '/core/Database.php';
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }

        require_once APP_ROOT . '/models/BorrowedToolBatchModel.php';
        require_once APP_ROOT . '/models/BorrowedToolModel.php';

        $this->batchModel = $batchModel ?? new BorrowedToolBatchModel($this->db);
        $this->borrowedToolModel = $borrowedToolModel ?? new BorrowedToolModel($this->db);
    }

    /**
     * Verify batch (Verifier step in MVA workflow)
     *
     * @param int $batchId Batch ID
     * @param int $verifiedBy User ID of verifier
     * @param string|null $notes Optional verification notes
     * @return array Response with success status
     */
    public function verifyBatch($batchId, $verifiedBy, $notes = null) {
        try {
            $this->db->beginTransaction();

            $batch = $this->batchModel->find($batchId);
            if (!$batch) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Batch');
            }

            if ($batch['status'] !== BorrowedToolStatus::PENDING_VERIFICATION) {
                $this->db->rollBack();
                return ResponseFormatter::invalidStatus(
                    $batch['status'],
                    BorrowedToolStatus::PENDING_VERIFICATION
                );
            }

            // Update batch status
            $updated = $this->batchModel->update($batchId, [
                'status' => BorrowedToolStatus::PENDING_APPROVAL,
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
                UPDATE borrowed_tools
                SET status = ?,
                    verified_by = ?,
                    verification_date = NOW()
                WHERE batch_id = ?
            ";
            $stmt = $this->db->prepare($updateItemsSql);
            $stmt->execute([BorrowedToolStatus::PENDING_APPROVAL, $verifiedBy, $batchId]);

            $this->logActivity(
                'verify_batch',
                "Batch {$batch['batch_reference']} verified",
                'borrowed_tool_batches',
                $batchId
            );

            $this->db->commit();
            return ResponseFormatter::success('Batch verified successfully');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Batch verification error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to verify batch');
        }
    }

    /**
     * Approve batch (Authorizer step in MVA workflow)
     *
     * @param int $batchId Batch ID
     * @param int $approvedBy User ID of authorizer
     * @param string|null $notes Optional approval notes
     * @return array Response with success status
     */
    public function approveBatch($batchId, $approvedBy, $notes = null) {
        try {
            $this->db->beginTransaction();

            $batch = $this->batchModel->find($batchId);
            if (!$batch) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Batch');
            }

            if ($batch['status'] !== BorrowedToolStatus::PENDING_APPROVAL) {
                $this->db->rollBack();
                return ResponseFormatter::invalidStatus(
                    $batch['status'],
                    BorrowedToolStatus::PENDING_APPROVAL
                );
            }

            // Update batch status
            $updated = $this->batchModel->update($batchId, [
                'status' => BorrowedToolStatus::APPROVED,
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
                UPDATE borrowed_tools
                SET status = ?,
                    approved_by = ?,
                    approval_date = NOW()
                WHERE batch_id = ?
            ";
            $stmt = $this->db->prepare($updateItemsSql);
            $stmt->execute([BorrowedToolStatus::APPROVED, $approvedBy, $batchId]);

            $this->logActivity(
                'approve_batch',
                "Batch {$batch['batch_reference']} approved",
                'borrowed_tool_batches',
                $batchId
            );

            $this->db->commit();
            return ResponseFormatter::success('Batch approved successfully');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Batch approval error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to approve batch');
        }
    }

    /**
     * Release batch (mark as physically handed over to borrower)
     *
     * @param int $batchId Batch ID
     * @param int $releasedBy User ID who released the batch
     * @param string|null $notes Optional release notes
     * @return array Response with success status
     */
    public function releaseBatch($batchId, $releasedBy, $notes = null) {
        try {
            $this->db->beginTransaction();

            $batch = $this->batchModel->find($batchId);
            if (!$batch) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Batch');
            }

            if ($batch['status'] !== BorrowedToolStatus::APPROVED) {
                $this->db->rollBack();
                return ResponseFormatter::invalidStatus(
                    $batch['status'],
                    BorrowedToolStatus::APPROVED
                );
            }

            // Update batch status to Borrowed after release action
            $updated = $this->batchModel->update($batchId, [
                'status' => BorrowedToolStatus::BORROWED,
                'released_by' => $releasedBy,
                'release_date' => date('Y-m-d H:i:s'),
                'release_notes' => $notes
            ]);

            if (!$updated) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to release batch');
            }

            // Update all items in batch to Borrowed status
            $updateItemsSql = "
                UPDATE borrowed_tools
                SET status = ?,
                    borrowed_by = ?,
                    borrowed_date = NOW()
                WHERE batch_id = ?
            ";
            $stmt = $this->db->prepare($updateItemsSql);
            $stmt->execute([BorrowedToolStatus::BORROWED, $releasedBy, $batchId]);

            // Update asset statuses to borrowed
            $updateAssetsSql = "
                UPDATE assets a
                INNER JOIN borrowed_tools bt ON a.id = bt.asset_id
                SET a.status = ?
                WHERE bt.batch_id = ?
            ";
            $assetStmt = $this->db->prepare($updateAssetsSql);
            $assetStmt->execute([AssetStatus::BORROWED, $batchId]);

            $this->logActivity(
                'release_batch',
                "Batch {$batch['batch_reference']} released to {$batch['borrower_name']}",
                'borrowed_tool_batches',
                $batchId
            );

            $this->db->commit();
            return ResponseFormatter::success('Batch released successfully');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Batch release error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to release batch');
        }
    }

    /**
     * Cancel batch (before release)
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
                BorrowedToolStatus::PENDING_VERIFICATION,
                BorrowedToolStatus::PENDING_APPROVAL,
                BorrowedToolStatus::APPROVED
            ];

            if (!in_array($batch['status'], $validCancelStatuses)) {
                $this->db->rollBack();
                return ResponseFormatter::error(
                    'Cannot cancel batch at this stage. Batch must be in pending or approved status.'
                );
            }

            // Update batch status
            $updated = $this->batchModel->update($batchId, [
                'status' => BorrowedToolStatus::CANCELED,
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
                UPDATE borrowed_tools
                SET status = ?,
                    canceled_by = ?,
                    cancellation_date = NOW(),
                    cancellation_reason = ?
                WHERE batch_id = ?
            ";
            $stmt = $this->db->prepare($updateItemsSql);
            $stmt->execute([BorrowedToolStatus::CANCELED, $canceledBy, $reason, $batchId]);

            $this->logActivity(
                'cancel_batch',
                "Batch {$batch['batch_reference']} canceled" . ($reason ? ": {$reason}" : ''),
                'borrowed_tool_batches',
                $batchId
            );

            $this->db->commit();
            return ResponseFormatter::success('Batch canceled successfully');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Batch cancellation error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to cancel batch');
        }
    }

    /**
     * Return batch (delegates to BorrowedToolReturnService for complex return logic)
     *
     * This method serves as a workflow coordination point, delegating the actual
     * return processing to the specialized BorrowedToolReturnService.
     *
     * @param int $batchId Batch ID
     * @param int $returnedBy User ID who received the return
     * @param array $returnedItems Array of returned items with conditions
     * @param string|null $notes Optional return notes
     * @return array Response with success status and incident information
     */
    public function returnBatch($batchId, $returnedBy, $returnedItems, $notes = null) {
        // Delegate to specialized return service
        require_once APP_ROOT . '/services/BorrowedToolReturnService.php';
        $returnService = new BorrowedToolReturnService($this->db);

        $returnData = [
            'items' => $returnedItems,
            'notes' => $notes
        ];

        return $returnService->processBatchReturn($batchId, $returnedBy, $returnData);
    }
}
