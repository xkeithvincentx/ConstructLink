<?php
/**
 * ConstructLink™ Withdrawal Workflow Service
 *
 * Handles MVA (Multi-level Verification & Approval) workflow state management
 * Manages consumable quantity tracking and workflow transitions
 * Follows PSR-4 namespacing and 2025 best practices
 */

class WithdrawalWorkflowService {
    private $db;
    private $withdrawalModel;
    private $inventoryModel;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->withdrawalModel = new WithdrawalModel();
        $this->inventoryModel = new AssetModel();
    }

    /**
     * Verify withdrawal (Step 1 of MVA workflow)
     * Performed by Site Inventory Clerk or Project Manager
     *
     * @param int $withdrawalId Withdrawal ID
     * @param int $verifiedBy User ID who is verifying
     * @param string|null $notes Verification notes
     * @return array Result with success status and message
     */
    public function verifyWithdrawal($withdrawalId, $verifiedBy, $notes = null) {
        try {
            $this->db->beginTransaction();

            // Get withdrawal details
            $withdrawal = $this->withdrawalModel->find($withdrawalId);
            if (!$withdrawal) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Withdrawal not found'];
            }

            // Validate workflow transition
            if (!$this->validateWorkflowTransition($withdrawal['status'], 'Pending Approval')) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Withdrawal cannot be verified from current status: ' . $withdrawal['status']
                ];
            }

            // Update withdrawal status
            $updateResult = $this->withdrawalModel->update($withdrawalId, [
                'status' => 'Pending Approval',
                'verified_by' => $verifiedBy,
                'verification_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ]);

            if (!$updateResult) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to update withdrawal status'];
            }

            // Log activity
            $this->logActivity('withdrawal_verified', "Withdrawal #{$withdrawalId} verified", 'withdrawals', $withdrawalId);

            $this->db->commit();

            return [
                'success' => true,
                'withdrawal' => $updateResult,
                'message' => 'Withdrawal verified successfully and moved to Pending Approval'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("WithdrawalWorkflowService::verifyWithdrawal error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to verify withdrawal'];
        }
    }

    /**
     * Approve withdrawal (Step 2 of MVA workflow)
     * Performed by Project Manager
     *
     * @param int $withdrawalId Withdrawal ID
     * @param int $approvedBy User ID who is approving
     * @param string|null $notes Approval notes
     * @return array Result with success status and message
     */
    public function approveWithdrawal($withdrawalId, $approvedBy, $notes = null) {
        try {
            $this->db->beginTransaction();

            // Get withdrawal details
            $withdrawal = $this->withdrawalModel->find($withdrawalId);
            if (!$withdrawal) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Withdrawal not found'];
            }

            // Validate workflow transition
            if (!$this->validateWorkflowTransition($withdrawal['status'], 'Approved')) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Withdrawal cannot be approved from current status: ' . $withdrawal['status']
                ];
            }

            // Update withdrawal status
            $updateResult = $this->withdrawalModel->update($withdrawalId, [
                'status' => 'Approved',
                'approved_by' => $approvedBy,
                'approval_date' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ]);

            if (!$updateResult) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to update withdrawal status'];
            }

            // Log activity
            $this->logActivity('withdrawal_approved', "Withdrawal #{$withdrawalId} approved", 'withdrawals', $withdrawalId);

            $this->db->commit();

            return [
                'success' => true,
                'withdrawal' => $updateResult,
                'message' => 'Withdrawal approved successfully and ready for release'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("WithdrawalWorkflowService::approveWithdrawal error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve withdrawal'];
        }
    }

    /**
     * Release consumable item (Step 3 of MVA workflow)
     * Performed by Warehouseman or Authorized Personnel
     * Deducts quantity from inventory for consumables
     *
     * @param int $withdrawalId Withdrawal ID
     * @param array $releaseData Release form data including released_by, notes, etc.
     * @return array Result with success status and message
     */
    public function releaseConsumable($withdrawalId, $releaseData) {
        try {
            $this->db->beginTransaction();

            // Get withdrawal with full details
            $sql = "SELECT w.*, a.available_quantity, a.quantity as total_quantity,
                           c.is_consumable, a.status as consumable_status
                    FROM withdrawals w
                    LEFT JOIN inventory_items a ON w.inventory_item_id = a.id
                    LEFT JOIN categories c ON a.category_id = c.id
                    WHERE w.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$withdrawalId]);
            $withdrawal = $stmt->fetch();

            if (!$withdrawal) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Withdrawal not found'];
            }

            // Validate workflow transition
            if (!$this->validateWorkflowTransition($withdrawal['status'], 'Released')) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Withdrawal must be approved before release. Current status: ' . $withdrawal['status']
                ];
            }

            // Enforce consumable-only for withdrawals
            if (!$withdrawal['is_consumable']) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Withdrawals are only for consumable items. Non-consumable assets must use the Borrowing system.'
                ];
            }

            // Check available quantity for consumables
            if ($withdrawal['available_quantity'] < $withdrawal['quantity']) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Insufficient quantity available. Available: ' . $withdrawal['available_quantity'] . ', Requested: ' . $withdrawal['quantity']
                ];
            }

            // Update withdrawal status
            $updateResult = $this->withdrawalModel->update($withdrawalId, [
                'status' => 'Released',
                'notes' => $releaseData['notes'] ?? null
            ]);

            if (!$updateResult) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to update withdrawal status'];
            }

            // Create release record
            $releaseRecordResult = $this->createReleaseRecord($withdrawalId, $releaseData);
            if (!$releaseRecordResult['success']) {
                $this->db->rollBack();
                return $releaseRecordResult;
            }

            // Deduct quantity from inventory (consumables only)
            $newAvailableQuantity = $withdrawal['available_quantity'] - $withdrawal['quantity'];
            $inventoryUpdateResult = $this->inventoryModel->update($withdrawal['inventory_item_id'], [
                'available_quantity' => $newAvailableQuantity
            ]);

            if (!$inventoryUpdateResult) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to update inventory quantity'];
            }

            // Log activity
            $this->logActivity(
                'consumable_released',
                "Consumable item released - Withdrawal #{$withdrawalId}, Quantity: {$withdrawal['quantity']}",
                'withdrawals',
                $withdrawalId
            );

            $this->db->commit();

            return [
                'success' => true,
                'withdrawal' => $updateResult,
                'new_quantity' => $newAvailableQuantity,
                'message' => 'Consumable item released successfully. Quantity deducted from inventory.'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("WithdrawalWorkflowService::releaseConsumable error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to release consumable item'];
        }
    }

    /**
     * Return item (rare for consumables, but possible)
     * Restores quantity if consumable is returned unused
     *
     * @param int $withdrawalId Withdrawal ID
     * @param int $returnedBy User ID who processed the return
     * @param string|null $notes Return notes
     * @return array Result with success status and message
     */
    public function returnItem($withdrawalId, $returnedBy, $notes = null) {
        try {
            $this->db->beginTransaction();

            // Get withdrawal with full details
            $sql = "SELECT w.*, a.available_quantity, c.is_consumable
                    FROM withdrawals w
                    LEFT JOIN inventory_items a ON w.inventory_item_id = a.id
                    LEFT JOIN categories c ON a.category_id = c.id
                    WHERE w.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$withdrawalId]);
            $withdrawal = $stmt->fetch();

            if (!$withdrawal) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Withdrawal not found'];
            }

            // Validate workflow transition
            if (!$this->validateWorkflowTransition($withdrawal['status'], 'Returned')) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Withdrawal must be released before it can be returned. Current status: ' . $withdrawal['status']
                ];
            }

            // Update withdrawal status
            $updateData = [
                'status' => 'Returned',
                'actual_return' => date('Y-m-d'),
                'notes' => $notes ? ($withdrawal['notes'] ? $withdrawal['notes'] . "\n\nReturn Notes: " . $notes : "Return Notes: " . $notes) : $withdrawal['notes']
            ];

            $updateResult = $this->withdrawalModel->update($withdrawalId, $updateData);
            if (!$updateResult) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to update withdrawal status'];
            }

            // Restore inventory quantity (rare for consumables, but possible if unused)
            $restoreResult = $this->restoreInventory($withdrawalId);
            if (!$restoreResult['success']) {
                $this->db->rollBack();
                return $restoreResult;
            }

            // Log activity
            $this->logActivity(
                'item_returned',
                "Item returned for Withdrawal #{$withdrawalId}",
                'withdrawals',
                $withdrawalId
            );

            $this->db->commit();

            return [
                'success' => true,
                'withdrawal' => $updateResult,
                'message' => 'Item returned successfully. Quantity restored to inventory.'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("WithdrawalWorkflowService::returnItem error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process item return'];
        }
    }

    /**
     * Cancel withdrawal request
     * Restores inventory if withdrawal was already released
     *
     * @param int $withdrawalId Withdrawal ID
     * @param string $reason Cancellation reason
     * @return array Result with success status and message
     */
    public function cancelWithdrawal($withdrawalId, $reason) {
        try {
            $this->db->beginTransaction();

            // Get withdrawal with full details
            $sql = "SELECT w.*, a.available_quantity, c.is_consumable
                    FROM withdrawals w
                    LEFT JOIN inventory_items a ON w.inventory_item_id = a.id
                    LEFT JOIN categories c ON a.category_id = c.id
                    WHERE w.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$withdrawalId]);
            $withdrawal = $stmt->fetch();

            if (!$withdrawal) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Withdrawal not found'];
            }

            // Check if withdrawal can be canceled
            if (!in_array($withdrawal['status'], ['Pending Verification', 'Pending Approval', 'Approved', 'Released'])) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Withdrawal cannot be canceled from current status: ' . $withdrawal['status']
                ];
            }

            $oldStatus = $withdrawal['status'];

            // Update withdrawal status
            $updateResult = $this->withdrawalModel->update($withdrawalId, [
                'status' => 'Canceled',
                'notes' => $reason
            ]);

            if (!$updateResult) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to cancel withdrawal'];
            }

            // If withdrawal was released, restore inventory
            if ($oldStatus === 'Released') {
                $restoreResult = $this->restoreInventory($withdrawalId);
                if (!$restoreResult['success']) {
                    $this->db->rollBack();
                    return $restoreResult;
                }
            }

            // Log activity
            $this->logActivity(
                'withdrawal_canceled',
                "Withdrawal #{$withdrawalId} canceled. Reason: {$reason}",
                'withdrawals',
                $withdrawalId
            );

            $this->db->commit();

            return [
                'success' => true,
                'withdrawal' => $updateResult,
                'message' => 'Withdrawal canceled successfully' . ($oldStatus === 'Released' ? '. Quantity restored to inventory.' : '.')
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("WithdrawalWorkflowService::cancelWithdrawal error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to cancel withdrawal'];
        }
    }

    /**
     * Validate workflow state transition
     * Ensures proper MVA workflow progression
     *
     * @param string $currentStatus Current withdrawal status
     * @param string $newStatus Intended new status
     * @return bool True if transition is valid
     */
    public function validateWorkflowTransition($currentStatus, $newStatus) {
        $validTransitions = [
            'Pending Verification' => ['Pending Approval', 'Canceled'],
            'Pending Approval' => ['Approved', 'Canceled'],
            'Approved' => ['Released', 'Canceled'],
            'Released' => ['Returned', 'Canceled']
        ];

        if (!isset($validTransitions[$currentStatus])) {
            return false;
        }

        return in_array($newStatus, $validTransitions[$currentStatus]);
    }

    /**
     * Create release record in releases table
     *
     * @param int $withdrawalId Withdrawal ID
     * @param array $releaseData Release form data
     * @return array Result with success status
     */
    public function createReleaseRecord($withdrawalId, $releaseData) {
        try {
            $sql = "INSERT INTO releases (withdrawal_id, released_by, notes, released_at)
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $withdrawalId,
                $releaseData['released_by'],
                $releaseData['notes'] ?? null,
                date('Y-m-d H:i:s')
            ]);

            if (!$result) {
                error_log("Create release record error: " . implode(", ", $stmt->errorInfo()));
                return ['success' => false, 'message' => 'Failed to create release record'];
            }

            return ['success' => true, 'release_id' => $this->db->lastInsertId()];

        } catch (Exception $e) {
            error_log("WithdrawalWorkflowService::createReleaseRecord error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create release record'];
        }
    }

    /**
     * Restore inventory quantity when withdrawal is canceled or returned
     *
     * @param int $withdrawalId Withdrawal ID
     * @return array Result with success status
     */
    public function restoreInventory($withdrawalId) {
        try {
            // Get withdrawal and asset details
            $sql = "SELECT w.inventory_item_id, w.quantity, a.available_quantity, c.is_consumable
                    FROM withdrawals w
                    LEFT JOIN inventory_items a ON w.inventory_item_id = a.id
                    LEFT JOIN categories c ON a.category_id = c.id
                    WHERE w.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$withdrawalId]);
            $withdrawal = $stmt->fetch();

            if (!$withdrawal) {
                return ['success' => false, 'message' => 'Withdrawal not found'];
            }

            // For consumables, restore quantity
            if ($withdrawal['is_consumable']) {
                $newAvailableQuantity = $withdrawal['available_quantity'] + $withdrawal['quantity'];

                $inventoryUpdateResult = $this->inventoryModel->update($withdrawal['inventory_item_id'], [
                    'available_quantity' => $newAvailableQuantity
                ]);

                if (!$inventoryUpdateResult) {
                    return ['success' => false, 'message' => 'Failed to restore inventory quantity'];
                }

                return [
                    'success' => true,
                    'new_quantity' => $newAvailableQuantity,
                    'message' => 'Inventory quantity restored'
                ];
            }

            // For non-consumables (shouldn't happen, but handle gracefully)
            return ['success' => true, 'message' => 'No inventory restoration needed for non-consumable'];

        } catch (Exception $e) {
            error_log("WithdrawalWorkflowService::restoreInventory error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to restore inventory'];
        }
    }

    /**
     * Log activity for audit trail
     *
     * @param string $action Action type
     * @param string $description Action description
     * @param string $table Table name
     * @param int $recordId Record ID
     * @return void
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
