<?php
/**
 * ConstructLink™ Restock Workflow Service
 *
 * Orchestrates restock workflow operations for consumable inventory items.
 * Handles MVA (Maker-Verifier-Authorizer) workflow through request system.
 *
 * Workflow Flow:
 * 1. Maker creates restock request (links to existing inventory item)
 * 2. Verifier reviews (Project Manager)
 * 3. Authorizer approves (Finance Director)
 * 4. Procurement Officer creates PO
 * 5. Upon delivery receipt, quantity is added to existing item (not new asset)
 *
 * @package ConstructLink
 * @version 1.0.0
 */

require_once APP_ROOT . '/models/RequestModel.php';
require_once APP_ROOT . '/services/Asset/AssetQuantityService.php';
require_once APP_ROOT . '/services/Asset/AssetMatchingService.php';
require_once APP_ROOT . '/core/utils/ResponseFormatter.php';
require_once APP_ROOT . '/core/traits/ActivityLoggingTrait.php';

use Services\Asset\AssetQuantityService;
use Services\Asset\AssetMatchingService;

class RestockWorkflowService {
    use ActivityLoggingTrait;

    private $db;
    private $requestModel;
    private $quantityService;
    private $matchingService;

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     */
    public function __construct($db = null) {
        if ($db === null) {
            require_once APP_ROOT . '/config/database.php';
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }

        $this->requestModel = new RequestModel($this->db);
        $this->quantityService = new AssetQuantityService();
        $this->matchingService = new AssetMatchingService($this->db);
    }

    /**
     * Initiate restock request for consumable inventory item
     *
     * Creates a new request linked to existing inventory item for quantity replenishment.
     * Validates item is consumable and eligible for restock.
     *
     * @param int $inventoryItemId Inventory item ID to restock
     * @param int $quantity Quantity to restock
     * @param string|null $reason Reason for restock
     * @param int $userId User initiating restock
     * @param array $additionalData Additional request data (urgency, date_needed, etc.)
     * @return array Response with request details
     */
    public function initiateRestock($inventoryItemId, $quantity, $reason, $userId, $additionalData = []) {
        try {
            $this->db->beginTransaction();

            // Validate restock eligibility
            $validation = $this->validateRestockEligibility($inventoryItemId);
            if (!$validation['success']) {
                $this->db->rollBack();
                return $validation;
            }

            $item = $validation['item'];

            // Prepare request data
            $requestData = [
                'project_id' => $item['project_id'],
                'request_type' => 'Restock',
                'category' => $item['category_name'] ?? 'Consumable',
                'description' => $reason ?? "Restock for {$item['name']}",
                'quantity' => $quantity,
                'unit' => $item['unit'] ?? 'units',
                'urgency' => $additionalData['urgency'] ?? 'Normal',
                'date_needed' => $additionalData['date_needed'] ?? null,
                'estimated_cost' => $quantity * ($item['unit_cost'] ?? 0),
                'requested_by' => $userId,
                'inventory_item_id' => $inventoryItemId,
                'is_restock' => 1,
                'status' => 'Draft'
            ];

            // Merge additional data
            $requestData = array_merge($requestData, $additionalData);

            // Create request
            $result = $this->requestModel->createRequest($requestData);

            if (!$result['success']) {
                $this->db->rollBack();
                return $result;
            }

            // Log activity
            $this->logActivity(
                'restock_initiated',
                "Restock request created for {$item['name']} ({$item['ref']}): {$quantity} {$item['unit']}",
                'requests',
                $result['request']['id']
            );

            $this->db->commit();

            return ResponseFormatter::success('Restock request created successfully', [
                'request' => $result['request'],
                'item' => $item
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("RestockWorkflowService::initiateRestock error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to initiate restock request');
        }
    }

    /**
     * Process restock delivery after procurement order receipt
     *
     * Called when procurement order for restock is received. Adds quantity to existing
     * inventory item rather than creating new asset record.
     *
     * @param int $procurementOrderId Procurement order ID
     * @param int $requestId Linked restock request ID
     * @return array Response with quantity addition details
     */
    public function processRestockDelivery($procurementOrderId, $requestId) {
        try {
            $this->db->beginTransaction();

            // Get restock request details
            $request = $this->requestModel->getRestockDetails($requestId);
            if (!$request) {
                $this->db->rollBack();
                return ResponseFormatter::error('Restock request not found');
            }

            // Validate request is approved and linked to PO
            if ($request['status'] !== 'Approved' && $request['status'] !== 'Procured') {
                $this->db->rollBack();
                return ResponseFormatter::error('Request must be approved before processing delivery');
            }

            if ($request['procurement_id'] != $procurementOrderId) {
                $this->db->rollBack();
                return ResponseFormatter::error('Request is not linked to specified procurement order');
            }

            // Validate inventory item exists
            if (empty($request['inventory_item_id'])) {
                $this->db->rollBack();
                return ResponseFormatter::error('No inventory item linked to restock request');
            }

            // Add quantity to existing item
            $quantityResult = $this->quantityService->addQuantity(
                $request['inventory_item_id'],
                $request['quantity'],
                "Restock delivery from PO #{$request['po_number']}",
                $procurementOrderId
            );

            if (!$quantityResult['success']) {
                $this->db->rollBack();
                return $quantityResult;
            }

            // Update request status to fulfilled
            $this->requestModel->update($requestId, [
                'status' => 'Procured'
            ]);

            // Log activity
            $this->logActivity(
                'restock_delivered',
                "Restocked {$request['item_name']} ({$request['item_ref']}): Added {$request['quantity']} {$request['item_unit']} from PO #{$request['po_number']}",
                'requests',
                $requestId
            );

            $this->db->commit();

            return ResponseFormatter::success('Restock delivery processed successfully', [
                'request' => $request,
                'quantity_result' => $quantityResult
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("RestockWorkflowService::processRestockDelivery error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to process restock delivery');
        }
    }

    /**
     * Validate if inventory item is eligible for restock
     *
     * Checks if item exists, is consumable, and in valid status for restock.
     *
     * @param int $inventoryItemId Inventory item ID
     * @return array Validation response
     */
    public function validateRestockEligibility($inventoryItemId) {
        try {
            // Use RequestModel's validation method
            $validation = $this->requestModel->validateRestockRequest([
                'inventory_item_id' => $inventoryItemId
            ]);

            if (!$validation['valid']) {
                return ResponseFormatter::validationError($validation['errors']);
            }

            return ResponseFormatter::success('Item is eligible for restock', [
                'item' => $validation['item']
            ]);

        } catch (Exception $e) {
            error_log("RestockWorkflowService::validateRestockEligibility error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to validate restock eligibility');
        }
    }

    /**
     * Get low stock items requiring restock
     *
     * Wrapper around AssetMatchingService to suggest restock candidates.
     *
     * @param int|null $projectId Filter by project
     * @param float $threshold Stock level threshold (default 0.2 = 20%)
     * @return array Response with restock suggestions
     */
    public function getLowStockItems($projectId = null, $threshold = 0.2) {
        return $this->matchingService->suggestRestockCandidates($projectId, $threshold);
    }

    /**
     * Get restock requests for a project
     *
     * Retrieves all restock requests (active and completed) for reporting.
     *
     * @param int $projectId Project ID
     * @param string|null $status Filter by status
     * @return array Array of restock requests
     */
    public function getRestockRequests($projectId, $status = null) {
        try {
            $conditions = ["r.project_id = ?", "r.is_restock = 1"];
            $params = [$projectId];

            if ($status !== null) {
                $conditions[] = "r.status = ?";
                $params[] = $status;
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT
                    r.*,
                    ii.ref as item_ref,
                    ii.name as item_name,
                    ii.available_quantity as current_stock,
                    ii.quantity as total_quantity,
                    u.full_name as requested_by_name,
                    po.po_number
                FROM requests r
                LEFT JOIN inventory_items ii ON r.inventory_item_id = ii.id
                LEFT JOIN users u ON r.requested_by = u.id
                LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                {$whereClause}
                ORDER BY r.created_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("RestockWorkflowService::getRestockRequests error: " . $e->getMessage());
            return [];
        }
    }
}
