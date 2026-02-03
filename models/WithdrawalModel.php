<?php
/**
 * ConstructLink™ Withdrawal Model - REFACTORED
 * Handles CRUD operations for withdrawals table only
 * Business logic moved to service layer
 */

class WithdrawalModel extends BaseModel {
    protected $table = 'withdrawals';
    protected $fillable = [
        'batch_id', 'inventory_item_id', 'project_id', 'purpose', 'withdrawn_by', 'receiver_name',
        'quantity', 'unit', 'expected_return', 'actual_return', 'status', 'notes',
        'returned_quantity', 'return_condition', 'return_item_notes',
        'verified_by', 'verification_date', 'approved_by', 'approval_date',
        'released_by', 'release_date', 'returned_by', 'return_date'
    ];

    /**
     * Create withdrawal request with consumable validation
     *
     * @param array $data Withdrawal data
     * @return array Result with success status
     */
    public function createWithdrawal($data) {
        try {
            // Validate required fields
            $validation = $this->validate($data, [
                'inventory_item_id' => 'required|integer',
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

            // Check if item exists and is consumable
            $sql = "SELECT a.*, c.is_consumable
                    FROM inventory_items a
                    LEFT JOIN categories c ON a.category_id = c.id
                    WHERE a.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$data['inventory_item_id']]);
            $asset = $stmt->fetch();

            if (!$asset) {
                $this->rollback();
                return ['success' => false, 'message' => 'Item not found'];
            }

            // CRITICAL: Withdrawals are only for consumable items
            if (!$asset['is_consumable']) {
                $this->rollback();
                return [
                    'success' => false,
                    'message' => 'Withdrawals are only for consumable items. Non-consumable assets must use the Borrowing system. Please use "Borrow Tool" instead.',
                    'redirect' => '?route=borrowed-tools/create&inventory_item_id=' . $data['inventory_item_id']
                ];
            }

            // Check available quantity for consumables
            if ($asset['available_quantity'] < $data['quantity']) {
                $this->rollback();
                return [
                    'success' => false,
                    'message' => 'Insufficient quantity available. Available: ' . $asset['available_quantity'] . ', Requested: ' . $data['quantity']
                ];
            }

            // Verify consumable belongs to the specified project
            if ($asset['project_id'] != $data['project_id']) {
                $this->rollback();
                return ['success' => false, 'message' => 'Consumable does not belong to the specified project'];
            }

            // Set default status and unit
            $data['status'] = 'Pending Verification';
            if (empty($data['unit'])) {
                $data['unit'] = 'pcs';
            }

            // Validate expected return date if provided
            if (!empty($data['expected_return']) && strtotime($data['expected_return']) <= time()) {
                $this->rollback();
                return ['success' => false, 'message' => 'Expected return date must be in the future'];
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
     * Get simple consumable details for withdrawal form
     * Single-purpose query method
     *
     * @param int $consumableId Consumable ID
     * @return array|false Consumable data or false
     */
    public function getConsumableForWithdrawal($consumableId) {
        try {
            $sql = "SELECT a.*, c.name as category_name, c.is_consumable
                    FROM inventory_items a
                    LEFT JOIN categories c ON a.category_id = c.id
                    WHERE a.id = ? AND c.is_consumable = 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$consumableId]);
            return $stmt->fetch();

        } catch (Exception $e) {
            error_log("Get consumable for withdrawal error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Backward compatibility method - alias for getConsumableForWithdrawal
     * @deprecated Use getConsumableForWithdrawal instead
     */
    public function getAssetForWithdrawal($assetId) {
        return $this->getConsumableForWithdrawal($assetId);
    }

    /**
     * Get withdrawal history for a specific inventory item (asset)
     * Used for asset activity tracking and history display
     *
     * @param int $inventoryItemId Inventory item ID (asset ID)
     * @return array Withdrawal history records
     */
    public function getAssetWithdrawalHistory($inventoryItemId) {
        try {
            $sql = "
                SELECT w.*,
                       u.full_name as withdrawn_by_name,
                       p.name as project_name,
                       ii.name as item_name,
                       ii.ref as item_ref
                FROM withdrawals w
                LEFT JOIN users u ON w.withdrawn_by = u.id
                LEFT JOIN projects p ON w.project_id = p.id
                LEFT JOIN inventory_items ii ON w.inventory_item_id = ii.id
                WHERE w.inventory_item_id = ?
                ORDER BY w.created_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$inventoryItemId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get asset withdrawal history error: " . $e->getMessage());
            return [];
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

    /**
     * Get withdrawal statistics for dashboard
     * Delegates to WithdrawalBatchStatisticsService
     *
     * @return array Statistics data
     */
    public function getWithdrawalStats() {
        try {
            require_once APP_ROOT . '/services/WithdrawalBatchStatisticsService.php';
            $statsService = new WithdrawalBatchStatisticsService($this->db);
            $stats = $statsService->getBatchStats();

            // Calculate pending as sum of pending_verification and pending_approval
            $pending = ($stats['pending_verification'] ?? 0) + ($stats['pending_approval'] ?? 0);

            // Format for dashboard compatibility
            return [
                'total' => $stats['total_batches'] ?? 0,
                'pending' => $pending,
                'released' => $stats['released'] ?? 0,
                'returned' => 0 // Withdrawals are consumables, not returned like tools
            ];
        } catch (Exception $e) {
            error_log("WithdrawalModel::getWithdrawalStats error: " . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'released' => 0,
                'returned' => 0
            ];
        }
    }
}

/**
 * Release Model for tracking asset releases
 * Minimal CRUD operations only
 */
class ReleaseModel extends BaseModel {
    protected $table = 'releases';
    protected $fillable = ['withdrawal_id', 'released_by', 'release_doc', 'notes', 'released_at'];
}
