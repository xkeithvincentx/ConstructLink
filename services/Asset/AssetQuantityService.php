<?php
/**
 * ConstructLink™ Asset Quantity Service
 * Handles consumable asset quantity management operations
 *
 * Responsibilities:
 * - Consume quantity from consumable assets
 * - Restore quantity to consumable assets
 * - Validate consumable status and quantity availability
 * - Log all quantity change activities
 * - Manage atomic quantity transactions
 */

class AssetQuantityService {
    private $assetModel;
    private $categoryModel;
    private $db;

    public function __construct() {
        require_once APP_ROOT . '/models/AssetModel.php';
        require_once APP_ROOT . '/models/CategoryModel.php';
        require_once APP_ROOT . '/config/database.php';

        $this->assetModel = new AssetModel();
        $this->categoryModel = new CategoryModel();
        $this->db = Database::getInstance();
    }

    /**
     * Consume quantity from consumable asset
     *
     * Validates asset is consumable and has sufficient quantity before consumption.
     * Logs activity and performs atomic transaction for data integrity.
     *
     * @param int $assetId Asset ID to consume from
     * @param int|float $quantityToConsume Amount to consume
     * @param string|null $reason Optional reason for consumption
     * @return array Success/error response with consumed amount and remaining quantity
     */
    public function consumeQuantity($assetId, $quantityToConsume, $reason = null) {
        try {
            // Validate quantity is positive
            if ($quantityToConsume <= 0) {
                return [
                    'success' => false,
                    'message' => 'Quantity to consume must be greater than zero'
                ];
            }

            // Get asset details
            $asset = $this->assetModel->find($assetId);
            if (!$asset) {
                return [
                    'success' => false,
                    'message' => 'Asset not found'
                ];
            }

            // Validate asset is consumable
            $validationResult = $this->validateConsumable($asset);
            if (!$validationResult['success']) {
                return $validationResult;
            }

            // Validate sufficient quantity available
            $availableQuantity = $asset['available_quantity'] ?? 0;
            if ($quantityToConsume > $availableQuantity) {
                return [
                    'success' => false,
                    'message' => "Insufficient quantity available. Available: {$availableQuantity}"
                ];
            }

            // Begin atomic transaction
            $this->assetModel->beginTransaction();

            // Calculate new quantity
            $newAvailableQuantity = $availableQuantity - $quantityToConsume;

            // Update asset quantity
            $updateResult = $this->assetModel->update($assetId, [
                'available_quantity' => $newAvailableQuantity
            ]);

            if (!$updateResult) {
                $this->assetModel->rollback();
                return [
                    'success' => false,
                    'message' => 'Failed to update quantity'
                ];
            }

            // Log activity
            $this->logQuantityActivity(
                $assetId,
                'quantity_consumed',
                "Consumed {$quantityToConsume} units" . ($reason ? ": {$reason}" : ''),
                ['available_quantity' => $availableQuantity],
                ['available_quantity' => $newAvailableQuantity]
            );

            $this->assetModel->commit();

            return [
                'success' => true,
                'message' => 'Quantity consumed successfully',
                'consumed' => $quantityToConsume,
                'remaining' => $newAvailableQuantity,
                'asset_ref' => $asset['ref'] ?? null
            ];

        } catch (Exception $e) {
            if ($this->assetModel) {
                $this->assetModel->rollback();
            }
            error_log("AssetQuantityService::consumeQuantity error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to consume quantity: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Restore quantity to consumable asset
     *
     * Validates asset is consumable and prevents restoration beyond total quantity.
     * Logs activity and performs atomic transaction for data integrity.
     *
     * @param int $assetId Asset ID to restore to
     * @param int|float $quantityToRestore Amount to restore
     * @param string|null $reason Optional reason for restoration
     * @return array Success/error response with restored amount and available quantity
     */
    public function restoreQuantity($assetId, $quantityToRestore, $reason = null) {
        try {
            // Validate quantity is positive
            if ($quantityToRestore <= 0) {
                return [
                    'success' => false,
                    'message' => 'Quantity to restore must be greater than zero'
                ];
            }

            // Get asset details
            $asset = $this->assetModel->find($assetId);
            if (!$asset) {
                return [
                    'success' => false,
                    'message' => 'Asset not found'
                ];
            }

            // Validate asset is consumable
            $validationResult = $this->validateConsumable($asset);
            if (!$validationResult['success']) {
                return $validationResult;
            }

            // Begin atomic transaction
            $this->assetModel->beginTransaction();

            // Calculate new quantity (cap at total quantity)
            $currentAvailable = $asset['available_quantity'] ?? 0;
            $totalQuantity = $asset['quantity'] ?? 1;
            $newAvailableQuantity = min($currentAvailable + $quantityToRestore, $totalQuantity);
            $actualRestored = $newAvailableQuantity - $currentAvailable;

            // Update asset quantity
            $updateResult = $this->assetModel->update($assetId, [
                'available_quantity' => $newAvailableQuantity
            ]);

            if (!$updateResult) {
                $this->assetModel->rollback();
                return [
                    'success' => false,
                    'message' => 'Failed to update quantity'
                ];
            }

            // Log activity
            $this->logQuantityActivity(
                $assetId,
                'quantity_restored',
                "Restored {$actualRestored} units" . ($reason ? ": {$reason}" : ''),
                ['available_quantity' => $currentAvailable],
                ['available_quantity' => $newAvailableQuantity]
            );

            $this->assetModel->commit();

            $warning = null;
            if ($actualRestored < $quantityToRestore) {
                $warning = "Only {$actualRestored} units restored (capped at total quantity of {$totalQuantity})";
            }

            return [
                'success' => true,
                'message' => 'Quantity restored successfully',
                'restored' => $actualRestored,
                'available' => $newAvailableQuantity,
                'total' => $totalQuantity,
                'asset_ref' => $asset['ref'] ?? null,
                'warning' => $warning
            ];

        } catch (Exception $e) {
            if ($this->assetModel) {
                $this->assetModel->rollback();
            }
            error_log("AssetQuantityService::restoreQuantity error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore quantity: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate if asset is consumable
     *
     * Checks asset's category to determine if it supports quantity operations.
     *
     * @param array $asset Asset data
     * @return array Success/error response
     */
    private function validateConsumable($asset) {
        if (empty($asset['category_id'])) {
            return [
                'success' => false,
                'message' => 'Asset has no category assigned'
            ];
        }

        $category = $this->categoryModel->find($asset['category_id']);

        if (!$category) {
            return [
                'success' => false,
                'message' => 'Asset category not found'
            ];
        }

        if ($category['is_consumable'] != 1) {
            return [
                'success' => false,
                'message' => 'Asset is not consumable. Only consumable assets support quantity operations.'
            ];
        }

        return ['success' => true];
    }

    /**
     * Get current quantity status for an asset
     *
     * Returns available, total, and consumed quantities for tracking.
     *
     * @param int $assetId Asset ID
     * @return array|false Quantity status or false on error
     */
    public function getQuantityStatus($assetId) {
        try {
            $asset = $this->assetModel->find($assetId);
            if (!$asset) {
                return false;
            }

            $validationResult = $this->validateConsumable($asset);
            if (!$validationResult['success']) {
                return false;
            }

            $totalQuantity = $asset['quantity'] ?? 0;
            $availableQuantity = $asset['available_quantity'] ?? 0;
            $consumedQuantity = $totalQuantity - $availableQuantity;

            return [
                'asset_id' => $assetId,
                'asset_ref' => $asset['ref'] ?? null,
                'asset_name' => $asset['name'] ?? null,
                'total_quantity' => $totalQuantity,
                'available_quantity' => $availableQuantity,
                'consumed_quantity' => $consumedQuantity,
                'percentage_available' => $totalQuantity > 0
                    ? round(($availableQuantity / $totalQuantity) * 100, 2)
                    : 0,
                'unit' => $asset['unit'] ?? 'units'
            ];

        } catch (Exception $e) {
            error_log("AssetQuantityService::getQuantityStatus error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if asset has sufficient quantity available
     *
     * Quick validation method for checking availability before operations.
     *
     * @param int $assetId Asset ID
     * @param int|float $requiredQuantity Required quantity
     * @return bool True if sufficient quantity available
     */
    public function hasSufficientQuantity($assetId, $requiredQuantity) {
        try {
            $asset = $this->assetModel->find($assetId);
            if (!$asset) {
                return false;
            }

            $validationResult = $this->validateConsumable($asset);
            if (!$validationResult['success']) {
                return false;
            }

            $availableQuantity = $asset['available_quantity'] ?? 0;
            return $availableQuantity >= $requiredQuantity;

        } catch (Exception $e) {
            error_log("AssetQuantityService::hasSufficientQuantity error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add quantity to consumable asset (for restock operations)
     *
     * Validates asset is consumable and adds quantity atomically.
     * Used when restocking existing consumable items via procurement.
     * Logs activity with optional procurement reference.
     *
     * @param int $assetId Asset ID to add quantity to
     * @param int|float $quantityToAdd Amount to add
     * @param string|null $reason Optional reason for quantity addition
     * @param int|null $procurementOrderId Optional procurement order reference
     * @return array Success/error response with added amount and new totals
     */
    public function addQuantity($assetId, $quantityToAdd, $reason = null, $procurementOrderId = null) {
        try {
            // Validate quantity is positive
            if ($quantityToAdd <= 0) {
                return [
                    'success' => false,
                    'message' => 'Quantity to add must be greater than zero'
                ];
            }

            // Get asset details
            $asset = $this->assetModel->find($assetId);
            if (!$asset) {
                return [
                    'success' => false,
                    'message' => 'Asset not found'
                ];
            }

            // Validate asset is consumable
            $validationResult = $this->validateConsumable($asset);
            if (!$validationResult['success']) {
                return $validationResult;
            }

            // Begin atomic transaction
            $this->assetModel->beginTransaction();

            // Calculate new quantities (atomic update to both total and available)
            $currentTotalQuantity = $asset['quantity'] ?? 0;
            $currentAvailableQuantity = $asset['available_quantity'] ?? 0;
            $newTotalQuantity = $currentTotalQuantity + $quantityToAdd;
            $newAvailableQuantity = $currentAvailableQuantity + $quantityToAdd;

            // Update asset quantities atomically
            $updateResult = $this->assetModel->update($assetId, [
                'quantity' => $newTotalQuantity,
                'available_quantity' => $newAvailableQuantity
            ]);

            if (!$updateResult) {
                $this->assetModel->rollback();
                return [
                    'success' => false,
                    'message' => 'Failed to update quantity'
                ];
            }

            // Build log description
            $logDescription = "Added {$quantityToAdd} units via restock";
            if ($reason) {
                $logDescription .= ": {$reason}";
            }
            if ($procurementOrderId) {
                $logDescription .= " (PO ID: {$procurementOrderId})";
            }

            // Log activity
            $this->logQuantityActivity(
                $assetId,
                'quantity_added',
                $logDescription,
                [
                    'quantity' => $currentTotalQuantity,
                    'available_quantity' => $currentAvailableQuantity
                ],
                [
                    'quantity' => $newTotalQuantity,
                    'available_quantity' => $newAvailableQuantity
                ]
            );

            $this->assetModel->commit();

            return [
                'success' => true,
                'message' => 'Quantity added successfully',
                'added' => $quantityToAdd,
                'new_total' => $newTotalQuantity,
                'new_available' => $newAvailableQuantity,
                'previous_total' => $currentTotalQuantity,
                'previous_available' => $currentAvailableQuantity,
                'asset_ref' => $asset['ref'] ?? null
            ];

        } catch (Exception $e) {
            if ($this->assetModel) {
                $this->assetModel->rollback();
            }
            error_log("AssetQuantityService::addQuantity error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to add quantity: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Log quantity change activity
     *
     * Records all quantity operations in activity log for audit trail.
     *
     * @param int $assetId Asset ID
     * @param string $action Action type
     * @param string $description Activity description
     * @param array|null $oldValues Previous values
     * @param array|null $newValues New values
     * @return void
     */
    private function logQuantityActivity($assetId, $action, $description, $oldValues = null, $newValues = null) {
        try {
            require_once APP_ROOT . '/models/Auth.php';
            $auth = Auth::getInstance();
            $user = $auth->getCurrentUser();

            $sql = "INSERT INTO activity_logs (
                user_id, action, description, table_name, record_id,
                ip_address, user_agent, created_at
            ) VALUES (?, ?, ?, 'assets', ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $user['id'] ?? null,
                $action,
                $description,
                $assetId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

        } catch (Exception $e) {
            error_log("AssetQuantityService::logQuantityActivity error: " . $e->getMessage());
        }
    }
}
