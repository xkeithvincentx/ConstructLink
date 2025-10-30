<?php
/**
 * ConstructLink™ Borrowed Tool Workflow Service
 *
 * Handles MVA (Maker-Verifier-Authorizer) workflow operations for individual borrowed tools.
 * Extracted from BorrowedToolModel to follow Single Responsibility Principle.
 *
 * @package ConstructLink
 * @version 2.0.0
 */

require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';
require_once APP_ROOT . '/helpers/AssetStatus.php';
require_once APP_ROOT . '/core/utils/ResponseFormatter.php';
require_once APP_ROOT . '/core/utils/DateValidator.php';
require_once APP_ROOT . '/core/traits/ActivityLoggingTrait.php';

class BorrowedToolWorkflowService {
    use ActivityLoggingTrait;

    private $db;
    private $borrowedToolModel;
    private $assetModel;

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     * @param BorrowedToolModel|null $borrowedToolModel Borrowed tool model instance
     * @param AssetModel|null $assetModel Asset model instance
     */
    public function __construct($db = null, $borrowedToolModel = null, $assetModel = null) {
        if ($db === null) {
            require_once APP_ROOT . '/core/Database.php';
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }

        require_once APP_ROOT . '/models/BorrowedToolModel.php';
        require_once APP_ROOT . '/models/AssetModel.php';

        $this->borrowedToolModel = $borrowedToolModel ?? new BorrowedToolModel($this->db);
        $this->assetModel = $assetModel ?? new AssetModel($this->db);
    }

    /**
     * Create borrowed tool request (Maker step)
     *
     * @param array $data Borrow request data
     * @return array Response with success status
     */
    public function createBorrowRequest($data) {
        // Validate expected return date
        $error = null;
        if (!DateValidator::validateExpectedReturn($data['expected_return'] ?? '', $error)) {
            return ResponseFormatter::error($error);
        }

        try {
            $this->db->beginTransaction();

            // Validate asset availability
            $asset = $this->assetModel->find($data['asset_id']);
            if (!$asset) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Asset');
            }

            if ($asset['status'] !== AssetStatus::AVAILABLE) {
                $this->db->rollBack();
                return ResponseFormatter::error('Asset is not available for borrowing');
            }

            // Prepare borrow data with initial MVA status
            $borrowData = [
                'asset_id' => $data['asset_id'],
                'borrower_name' => $data['borrower_name'],
                'borrower_contact' => $data['borrower_contact'] ?? null,
                'expected_return' => $data['expected_return'],
                'issued_by' => $data['issued_by'],
                'purpose' => $data['purpose'] ?? null,
                'condition_out' => $data['condition_out'] ?? null,
                'status' => BorrowedToolStatus::PENDING_VERIFICATION,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $borrowedTool = $this->borrowedToolModel->create($borrowData);
            if (!$borrowedTool) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to create borrowed tool request');
            }

            $this->logActivity(
                'borrow_tool_request',
                "Tool borrow request created: {$asset['name']} by {$data['borrower_name']}",
                'borrowed_tools',
                $borrowedTool['id']
            );

            $this->db->commit();
            return ResponseFormatter::success('Borrowed tool request created', ['borrowed_tool' => $borrowedTool]);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Borrowed tool creation error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to create borrowed tool request');
        }
    }

    /**
     * Verify borrowed tool request (Verifier step)
     *
     * @param int $borrowId Borrowed tool ID
     * @param int $verifiedBy User ID of verifier
     * @param string|null $notes Optional verification notes
     * @return array Response with success status
     */
    public function verify($borrowId, $verifiedBy, $notes = null) {
        return $this->executeWorkflowTransition(
            $borrowId,
            BorrowedToolStatus::PENDING_VERIFICATION,
            BorrowedToolStatus::PENDING_APPROVAL,
            [
                'verified_by' => $verifiedBy,
                'verification_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ],
            'verify_borrow_tool',
            'Tool borrow request verified',
            'verify'
        );
    }

    /**
     * Approve borrowed tool request (Authorizer step)
     *
     * @param int $borrowId Borrowed tool ID
     * @param int $approvedBy User ID of authorizer
     * @param string|null $notes Optional approval notes
     * @return array Response with success status
     */
    public function approve($borrowId, $approvedBy, $notes = null) {
        return $this->executeWorkflowTransition(
            $borrowId,
            BorrowedToolStatus::PENDING_APPROVAL,
            BorrowedToolStatus::APPROVED,
            [
                'approved_by' => $approvedBy,
                'approval_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ],
            'approve_borrow_tool',
            'Tool borrow request approved',
            'approve'
        );
    }

    /**
     * Release borrowed tool (mark as borrowed after approval)
     *
     * @param int $borrowId Borrowed tool ID
     * @param int $borrowedBy User ID who released the tool
     * @param string|null $notes Optional release notes
     * @return array Response with success status
     */
    public function release($borrowId, $borrowedBy, $notes = null) {
        try {
            $this->db->beginTransaction();

            $borrowedTool = $this->borrowedToolModel->find($borrowId);
            if (!$borrowedTool) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Borrowed tool');
            }

            if ($borrowedTool['status'] !== BorrowedToolStatus::APPROVED) {
                $this->db->rollBack();
                return ResponseFormatter::invalidStatus($borrowedTool['status'], BorrowedToolStatus::APPROVED);
            }

            // Verify asset is still available
            $asset = $this->assetModel->find($borrowedTool['asset_id']);
            if (!$asset) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Asset');
            }

            if ($asset['status'] !== AssetStatus::AVAILABLE) {
                $this->db->rollBack();
                return ResponseFormatter::error('Asset is no longer available for borrowing');
            }

            // Update borrowed tool status
            $updateData = [
                'status' => BorrowedToolStatus::BORROWED,
                'borrowed_by' => $borrowedBy,
                'borrowed_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ];
            $updated = $this->borrowedToolModel->update($borrowId, $updateData);

            if (!$updated) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to release tool');
            }

            // Update asset status to borrowed
            $assetUpdated = $this->assetModel->update($borrowedTool['asset_id'], ['status' => AssetStatus::BORROWED]);
            if (!$assetUpdated) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to update asset status');
            }

            $this->logActivity(
                'borrow_tool',
                "Tool released: {$asset['name']} by {$borrowedTool['borrower_name']}",
                'borrowed_tools',
                $borrowId
            );

            $this->db->commit();
            return ResponseFormatter::success('Tool released successfully');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Tool release error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to release tool');
        }
    }

    /**
     * Cancel borrowed tool request (any stage before Released)
     *
     * @param int $borrowId Borrowed tool ID
     * @param int $canceledBy User ID who canceled
     * @param string|null $reason Optional cancellation reason
     * @return array Response with success status
     */
    public function cancel($borrowId, $canceledBy, $reason = null) {
        try {
            $this->db->beginTransaction();

            $borrowedTool = $this->borrowedToolModel->find($borrowId);
            if (!$borrowedTool) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Borrowed tool');
            }

            $validCancelStatuses = [
                BorrowedToolStatus::PENDING_VERIFICATION,
                BorrowedToolStatus::PENDING_APPROVAL,
                BorrowedToolStatus::APPROVED
            ];

            if (!in_array($borrowedTool['status'], $validCancelStatuses)) {
                $this->db->rollBack();
                return ResponseFormatter::error('Cannot cancel at this stage. Tool must be in pending or approved status.');
            }

            $updateData = [
                'status' => BorrowedToolStatus::CANCELED,
                'canceled_by' => $canceledBy,
                'cancellation_date' => date('Y-m-d H:i:s'),
                'cancellation_reason' => $reason
            ];

            $updated = $this->borrowedToolModel->update($borrowId, $updateData);
            if (!$updated) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to cancel borrowed tool request');
            }

            $this->logActivity(
                'cancel_borrow_tool',
                "Tool borrow request canceled" . ($reason ? ": {$reason}" : ''),
                'borrowed_tools',
                $borrowId
            );

            $this->db->commit();
            return ResponseFormatter::success('Borrowed tool request canceled');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Cancel borrowed tool error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to cancel borrowed tool request');
        }
    }

    /**
     * Execute a workflow transition (generic method for verify/approve)
     *
     * @param int $borrowId Borrowed tool ID
     * @param string $fromStatus Expected current status
     * @param string $toStatus Target status
     * @param array $updateData Data to update
     * @param string $logAction Action name for logging
     * @param string $logDescription Description for activity log
     * @param string $actionName Human-readable action name for error messages
     * @return array Response with success status
     */
    private function executeWorkflowTransition(
        $borrowId,
        $fromStatus,
        $toStatus,
        $updateData,
        $logAction,
        $logDescription,
        $actionName
    ) {
        try {
            $this->db->beginTransaction();

            $borrowedTool = $this->borrowedToolModel->find($borrowId);
            if (!$borrowedTool) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Borrowed tool');
            }

            if ($borrowedTool['status'] !== $fromStatus) {
                $this->db->rollBack();
                return ResponseFormatter::invalidStatus($borrowedTool['status'], $fromStatus);
            }

            $updateData['status'] = $toStatus;
            $updated = $this->borrowedToolModel->update($borrowId, $updateData);

            if (!$updated) {
                $this->db->rollBack();
                return ResponseFormatter::error("Failed to {$actionName} borrowed tool request");
            }

            $this->logActivity($logAction, $logDescription, 'borrowed_tools', $borrowId);
            $this->db->commit();

            return ResponseFormatter::success("Tool borrow request {$actionName}d successfully");

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Borrowed tool {$actionName} error: " . $e->getMessage());
            return ResponseFormatter::error("Failed to {$actionName} borrowed tool request");
        }
    }

    /**
     * Create and process basic tool request in one streamlined operation
     *
     * This combines create, verify, approve, and mark as borrowed for basic tools (<₱50,000).
     * Streamlined workflow bypasses MVA approval for authorized roles (Warehouseman, System Admin).
     *
     * @param array $data Tool borrowing data
     * @return array Result with success status and borrowed tool data
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
            $asset = $this->assetModel->find($data['asset_id']);
            if (!$asset) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Asset not found'];
            }

            if ($asset['status'] !== AssetStatus::AVAILABLE) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Asset is not available for borrowing'];
            }

            // Verify this is a basic tool (not critical)
            if ($this->borrowedToolModel->isCriticalTool($data['asset_id'], $asset['acquisition_cost'])) {
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
                'status' => BorrowedToolStatus::BORROWED, // Skip MVA steps for basic tools
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

            $borrowedTool = $this->borrowedToolModel->create($borrowData);
            if (!$borrowedTool) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to create borrowed tool request'];
            }

            // Update asset status to borrowed
            $assetUpdateSql = "UPDATE assets SET status = ? WHERE id = ?";
            $assetStmt = $this->db->prepare($assetUpdateSql);
            if (!$assetStmt->execute([AssetStatus::BORROWED, $data['asset_id']])) {
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
}
