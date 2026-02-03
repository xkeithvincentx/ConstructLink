<?php
/**
 * ConstructLink™ Withdrawal Service
 *
 * Main orchestration service for withdrawal operations
 * Coordinates between validation, query, workflow, and statistics services
 * Follows PSR-4 namespacing and 2025 best practices
 */

// Load withdrawal service dependencies
require_once APP_ROOT . '/services/Withdrawal/WithdrawalValidationService.php';
require_once APP_ROOT . '/services/Withdrawal/WithdrawalQueryService.php';
require_once APP_ROOT . '/services/Withdrawal/WithdrawalWorkflowService.php';
require_once APP_ROOT . '/services/Withdrawal/WithdrawalStatisticsService.php';

class WithdrawalService {
    private $withdrawalModel;
    private $validationService;
    private $queryService;
    private $workflowService;
    private $statisticsService;

    public function __construct() {
        $this->withdrawalModel = new WithdrawalModel();
        $this->validationService = new WithdrawalValidationService();
        $this->queryService = new WithdrawalQueryService();
        $this->workflowService = new WithdrawalWorkflowService();
        $this->statisticsService = new WithdrawalStatisticsService();
    }

    /**
     * Create a new withdrawal request
     * Orchestrates validation and creation process
     *
     * @param array $data Withdrawal request data
     * @return array Result with success status, withdrawal data, or errors
     */
    public function createWithdrawalRequest($data) {
        try {
            // Validate withdrawal request
            $validation = $this->validationService->validateWithdrawalRequest($data);
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }

            // Check if item exists and is consumable
            $inventoryItem = $this->queryService->getInventoryItemWithCategory($data['inventory_item_id']);
            if (!$inventoryItem) {
                return ['success' => false, 'message' => 'Inventory item not found'];
            }

            // Enforce consumable-only validation
            if (!$inventoryItem['is_consumable']) {
                return [
                    'success' => false,
                    'message' => 'Withdrawals are only for consumable items. Non-consumable assets must use the Borrowing system.',
                    'redirect' => '?route=borrowed-tools/create&inventory_item_id=' . $data['inventory_item_id']
                ];
            }

            // Check available quantity
            $availabilityCheck = $this->checkItemAvailability(
                $data['inventory_item_id'],
                $data['quantity']
            );

            if (!$availabilityCheck['available']) {
                return [
                    'success' => false,
                    'message' => $availabilityCheck['message'],
                    'available_quantity' => $availabilityCheck['available_quantity']
                ];
            }

            // Validate item-project relationship
            $projectValidation = $this->validationService->validateItemProjectRelationship(
                $inventoryItem['project_id'],
                $data['project_id']
            );

            if (!$projectValidation['valid']) {
                return ['success' => false, 'message' => $projectValidation['message']];
            }

            // Check for existing active withdrawals (shouldn't happen for consumables, but check anyway)
            $activeWithdrawal = $this->queryService->getActiveWithdrawalForItem($data['inventory_item_id']);
            if ($activeWithdrawal && !$inventoryItem['is_consumable']) {
                return [
                    'success' => false,
                    'message' => 'This item already has an active withdrawal request'
                ];
            }

            // Create withdrawal using model
            $result = $this->withdrawalModel->createWithdrawal($data);

            return $result;

        } catch (Exception $e) {
            error_log("WithdrawalService::createWithdrawalRequest error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create withdrawal request'];
        }
    }

    /**
     * Get withdrawal details by ID
     *
     * @param int $id Withdrawal ID
     * @return array|false Withdrawal data or false if not found
     */
    public function getWithdrawal($id) {
        try {
            return $this->queryService->getWithdrawalDetails($id);
        } catch (Exception $e) {
            error_log("WithdrawalService::getWithdrawal error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get withdrawals with filters and pagination
     *
     * @param array $filters Filter criteria
     * @param int $page Current page number
     * @param int $perPage Records per page
     * @return array Paginated withdrawal data
     */
    public function getWithdrawals($filters = [], $page = 1, $perPage = 20) {
        try {
            return $this->queryService->getWithdrawalsWithFilters($filters, $page, $perPage);
        } catch (Exception $e) {
            error_log("WithdrawalService::getWithdrawals error: " . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_next' => false,
                    'has_prev' => false
                ]
            ];
        }
    }

    /**
     * Check if inventory item has sufficient quantity available
     *
     * @param int $inventoryItemId Inventory item ID
     * @param int $quantity Requested quantity
     * @return array Availability status with details
     */
    public function checkItemAvailability($inventoryItemId, $quantity) {
        try {
            $item = $this->queryService->getInventoryItemWithCategory($inventoryItemId);

            if (!$item) {
                return [
                    'available' => false,
                    'message' => 'Inventory item not found',
                    'available_quantity' => 0
                ];
            }

            // For consumables, check quantity
            if ($item['is_consumable']) {
                $validation = $this->validationService->validateConsumableQuantity(
                    $item['available_quantity'],
                    $quantity
                );

                return [
                    'available' => $validation['valid'],
                    'message' => $validation['valid'] ? 'Item available' : $validation['message'],
                    'available_quantity' => $item['available_quantity'],
                    'requested_quantity' => $quantity
                ];
            }

            // Non-consumables shouldn't be withdrawn
            return [
                'available' => false,
                'message' => 'Non-consumable items cannot be withdrawn. Use borrowing system instead.',
                'available_quantity' => 0
            ];

        } catch (Exception $e) {
            error_log("WithdrawalService::checkItemAvailability error: " . $e->getMessage());
            return [
                'available' => false,
                'message' => 'Failed to check availability',
                'available_quantity' => 0
            ];
        }
    }

    /**
     * Get available consumable items for withdrawal by project
     *
     * @param int $projectId Project ID
     * @return array List of available consumable items
     */
    public function getAvailableItems($projectId) {
        try {
            return $this->queryService->getAvailableConsumablesForWithdrawal($projectId);
        } catch (Exception $e) {
            error_log("WithdrawalService::getAvailableItems error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get withdrawal statistics
     *
     * @param int|null $projectId Optional project ID for filtering
     * @param string|null $dateFrom Optional start date
     * @param string|null $dateTo Optional end date
     * @return array Statistics data
     */
    public function getStatistics($projectId = null, $dateFrom = null, $dateTo = null) {
        try {
            return $this->statisticsService->getWithdrawalStatistics($projectId, $dateFrom, $dateTo);
        } catch (Exception $e) {
            error_log("WithdrawalService::getStatistics error: " . $e->getMessage());
            return [
                'total_withdrawals' => 0,
                'pending_verification' => 0,
                'pending_approval' => 0,
                'approved' => 0,
                'released' => 0,
                'returned' => 0,
                'canceled' => 0,
                'overdue' => 0
            ];
        }
    }

    /**
     * Get dashboard statistics (30-day window)
     *
     * @return array Dashboard statistics
     */
    public function getDashboardStats() {
        try {
            return $this->statisticsService->getDashboardStats();
        } catch (Exception $e) {
            error_log("WithdrawalService::getDashboardStats error: " . $e->getMessage());
            return [
                'total' => 0,
                'pending_verification' => 0,
                'pending_approval' => 0,
                'approved' => 0,
                'released' => 0,
                'returned' => 0,
                'canceled' => 0,
                'overdue' => 0
            ];
        }
    }

    /**
     * Get overdue withdrawals
     *
     * @param int|null $projectId Optional project ID for filtering
     * @return array List of overdue withdrawals
     */
    public function getOverdueWithdrawals($projectId = null) {
        try {
            return $this->queryService->getOverdueWithdrawals($projectId);
        } catch (Exception $e) {
            error_log("WithdrawalService::getOverdueWithdrawals error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get withdrawal report data
     *
     * @param string $dateFrom Start date
     * @param string $dateTo End date
     * @param int|null $projectId Optional project ID
     * @param string|null $status Optional status filter
     * @return array Report data
     */
    public function getReport($dateFrom, $dateTo, $projectId = null, $status = null) {
        try {
            return $this->queryService->getWithdrawalReport($dateFrom, $dateTo, $projectId, $status);
        } catch (Exception $e) {
            error_log("WithdrawalService::getReport error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get item withdrawal history
     *
     * @param int $inventoryItemId Inventory item ID
     * @return array Withdrawal history
     */
    public function getItemHistory($inventoryItemId) {
        try {
            return $this->queryService->getItemWithdrawalHistory($inventoryItemId);
        } catch (Exception $e) {
            error_log("WithdrawalService::getItemHistory error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verify withdrawal (delegate to workflow service)
     *
     * @param int $withdrawalId Withdrawal ID
     * @param int $verifiedBy User ID
     * @param string|null $notes Verification notes
     * @return array Result
     */
    public function verify($withdrawalId, $verifiedBy, $notes = null) {
        return $this->workflowService->verifyWithdrawal($withdrawalId, $verifiedBy, $notes);
    }

    /**
     * Approve withdrawal (delegate to workflow service)
     *
     * @param int $withdrawalId Withdrawal ID
     * @param int $approvedBy User ID
     * @param string|null $notes Approval notes
     * @return array Result
     */
    public function approve($withdrawalId, $approvedBy, $notes = null) {
        return $this->workflowService->approveWithdrawal($withdrawalId, $approvedBy, $notes);
    }

    /**
     * Release consumable (delegate to workflow service)
     *
     * @param int $withdrawalId Withdrawal ID
     * @param array $releaseData Release form data
     * @return array Result
     */
    public function release($withdrawalId, $releaseData) {
        return $this->workflowService->releaseConsumable($withdrawalId, $releaseData);
    }

    /**
     * Return item (delegate to workflow service)
     *
     * @param int $withdrawalId Withdrawal ID
     * @param int $returnedBy User ID
     * @param string|null $notes Return notes
     * @return array Result
     */
    public function returnItem($withdrawalId, $returnedBy, $notes = null) {
        return $this->workflowService->returnItem($withdrawalId, $returnedBy, $notes);
    }

    /**
     * Cancel withdrawal (delegate to workflow service)
     *
     * @param int $withdrawalId Withdrawal ID
     * @param string $reason Cancellation reason
     * @return array Result
     */
    public function cancel($withdrawalId, $reason) {
        return $this->workflowService->cancelWithdrawal($withdrawalId, $reason);
    }
}
