<?php
/**
 * ConstructLink™ Asset Procurement Service
 *
 * Handles procurement-to-asset integration operations.
 * Extracted from AssetModel as part of god object refactoring initiative.
 * Follows SOLID principles and 2025 industry standards.
 *
 * Responsibilities:
 * - Creating assets from procurement items (single and batch)
 * - Linking assets to procurement orders and items
 * - Retrieving assets by procurement order
 * - Handling both consumable and non-consumable asset types
 * - Managing procurement inventory relationships
 * - Transaction management for data integrity
 *
 * @package ConstructLink
 * @subpackage Services\Asset
 * @version 2.0.0
 */

require_once APP_ROOT . '/core/Auth.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/traits/ActivityLoggingTrait.php';
require_once APP_ROOT . '/models/CategoryModel.php';
require_once APP_ROOT . '/models/ProcurementOrderModel.php';
require_once APP_ROOT . '/models/ProcurementItemModel.php';
require_once APP_ROOT . '/services/Asset/AssetCrudService.php';

class AssetProcurementService {
    use ActivityLoggingTrait;

    private $db;
    private $assetCrudService;
    private $categoryModel;
    private $procurementOrderModel;
    private $procurementItemModel;

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     * @param AssetCrudService|null $assetCrudService Asset CRUD service instance
     * @param CategoryModel|null $categoryModel Category model instance
     * @param ProcurementOrderModel|null $procurementOrderModel Procurement order model instance
     * @param ProcurementItemModel|null $procurementItemModel Procurement item model instance
     */
    public function __construct(
        $db = null,
        $assetCrudService = null,
        $categoryModel = null,
        $procurementOrderModel = null,
        $procurementItemModel = null
    ) {
        if ($db === null) {
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }

        $this->assetCrudService = $assetCrudService ?? new AssetCrudService($this->db);
        $this->categoryModel = $categoryModel ?? new CategoryModel();
        $this->procurementOrderModel = $procurementOrderModel ?? new ProcurementOrderModel();
        $this->procurementItemModel = $procurementItemModel ?? new ProcurementItemModel();
    }

    /**
     * Create a single asset from a procurement item
     *
     * This method creates one asset record from a procurement item, automatically
     * populating fields from the procurement order and item data. Handles both
     * consumable and non-consumable items appropriately.
     *
     * @param int $procurementOrderId Procurement order ID
     * @param int $procurementItemId Procurement item ID
     * @param array $assetData Additional asset data to override defaults
     * @return array Response with success status and asset data
     */
    public function createAssetFromProcurementItem($procurementOrderId, $procurementItemId, $assetData = []) {
        try {
            // Validate procurement system availability
            if (!class_exists('ProcurementOrderModel') || !class_exists('ProcurementItemModel')) {
                return [
                    'success' => false,
                    'message' => 'Multi-item procurement system not available'
                ];
            }

            // Retrieve procurement order and item details
            $procurementOrder = $this->procurementOrderModel->find($procurementOrderId);
            $procurementItem = $this->procurementItemModel->find($procurementItemId);

            if (!$procurementOrder || !$procurementItem) {
                return [
                    'success' => false,
                    'message' => 'Procurement order or item not found'
                ];
            }

            // Prepare default asset data from procurement information
            $defaultAssetData = [
                'category_id' => $procurementItem['category_id'] ?? 1,
                'name' => $procurementItem['item_name'],
                'description' => $procurementItem['description'],
                'project_id' => $procurementOrder['project_id'],
                'vendor_id' => $procurementOrder['vendor_id'],
                'procurement_order_id' => $procurementOrderId,
                'procurement_item_id' => $procurementItemId,
                'acquired_date' => date('Y-m-d'),
                'acquisition_cost' => $procurementItem['unit_price'],
                'unit_cost' => $procurementItem['unit_price'],
                'model' => $procurementItem['model'],
                'specifications' => $procurementItem['specifications']
            ];

            // Merge with provided asset data (overrides defaults)
            $finalAssetData = array_merge($defaultAssetData, $assetData);

            // Handle quantity for consumable items
            $category = $this->categoryModel->find($finalAssetData['category_id']);
            $isConsumable = $category && $category['is_consumable'] == 1;

            if ($isConsumable) {
                $quantity = isset($procurementItem['quantity']) && $procurementItem['quantity'] > 0
                    ? (int)$procurementItem['quantity']
                    : 1;
                $finalAssetData['quantity'] = $quantity;
                $finalAssetData['available_quantity'] = $quantity;
            }

            // Create the asset using AssetCrudService
            $result = $this->assetCrudService->createAsset($finalAssetData);

            if ($result['success']) {
                // Link asset to procurement order and item
                $this->linkAssetToProcurement(
                    $result['asset']['id'],
                    $procurementOrderId,
                    $procurementItemId
                );

                // Log activity
                $this->logActivity(
                    'create_from_procurement',
                    "Created asset '{$finalAssetData['name']}' from procurement order #{$procurementOrderId}",
                    'assets',
                    $result['asset']['id']
                );
            }

            return $result;

        } catch (Exception $e) {
            error_log("Create asset from procurement item error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create asset from procurement item',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate multiple assets from a procurement item
     *
     * Handles both consumable and non-consumable items:
     * - Consumable: Creates one asset with total quantity
     * - Non-consumable: Creates one asset per quantity (each with quantity=1)
     *
     * @param int $procurementOrderId Procurement order ID
     * @param int $procurementItemId Procurement item ID
     * @param array $assetData Additional asset data to override defaults
     * @return array Response with success status and array of created assets
     */
    public function generateAssetsFromProcurementItem($procurementOrderId, $procurementItemId, $assetData = []) {
        try {
            // Retrieve procurement order and item details
            $procurementOrder = $this->procurementOrderModel->find($procurementOrderId);
            $procurementItem = $this->procurementItemModel->find($procurementItemId);

            if (!$procurementOrder || !$procurementItem) {
                return [
                    'success' => false,
                    'message' => 'Procurement order or item not found'
                ];
            }

            // Determine if item is consumable
            $category = $this->categoryModel->find($procurementItem['category_id']);
            $isConsumable = $category && $category['is_consumable'] == 1;

            $createdAssets = [];

            if ($isConsumable) {
                // For consumable items: Create one asset with total quantity
                $finalAssetData = array_merge([
                    'category_id' => $procurementItem['category_id'],
                    'name' => $procurementItem['item_name'],
                    'description' => $procurementItem['description'],
                    'project_id' => $procurementOrder['project_id'],
                    'vendor_id' => $procurementOrder['vendor_id'],
                    'procurement_order_id' => $procurementOrderId,
                    'procurement_item_id' => $procurementItemId,
                    'acquired_date' => date('Y-m-d'),
                    'acquisition_cost' => $procurementItem['unit_price'],
                    'unit_cost' => $procurementItem['unit_price'],
                    'model' => $procurementItem['model'],
                    'specifications' => $procurementItem['specifications'],
                    'quantity' => (int)$procurementItem['quantity'],
                    'available_quantity' => (int)$procurementItem['quantity']
                ], $assetData);

                $result = $this->assetCrudService->createAsset($finalAssetData);

                if ($result['success']) {
                    $this->linkAssetToProcurement(
                        $result['asset']['id'],
                        $procurementOrderId,
                        $procurementItemId
                    );
                    $createdAssets[] = $result['asset'];

                    // Log activity
                    $this->logActivity(
                        'batch_create_from_procurement',
                        "Created consumable asset '{$finalAssetData['name']}' (qty: {$finalAssetData['quantity']}) from procurement order #{$procurementOrderId}",
                        'assets',
                        $result['asset']['id']
                    );
                }
            } else {
                // For non-consumable items: Create one asset per quantity
                $qty = (int)$procurementItem['quantity'];

                for ($i = 0; $i < $qty; $i++) {
                    $finalAssetData = array_merge([
                        'category_id' => $procurementItem['category_id'],
                        'name' => $procurementItem['item_name'],
                        'description' => $procurementItem['description'],
                        'project_id' => $procurementOrder['project_id'],
                        'vendor_id' => $procurementOrder['vendor_id'],
                        'procurement_order_id' => $procurementOrderId,
                        'procurement_item_id' => $procurementItemId,
                        'acquired_date' => date('Y-m-d'),
                        'acquisition_cost' => $procurementItem['unit_price'],
                        'unit_cost' => $procurementItem['unit_price'],
                        'model' => $procurementItem['model'],
                        'specifications' => $procurementItem['specifications'],
                        'quantity' => 1,
                        'available_quantity' => 1
                    ], $assetData);

                    $result = $this->assetCrudService->createAsset($finalAssetData);

                    if ($result['success']) {
                        $this->linkAssetToProcurement(
                            $result['asset']['id'],
                            $procurementOrderId,
                            $procurementItemId
                        );
                        $createdAssets[] = $result['asset'];
                    }
                }

                // Log batch creation activity
                if (!empty($createdAssets)) {
                    $this->logActivity(
                        'batch_create_from_procurement',
                        "Created {$qty} non-consumable assets '{$procurementItem['item_name']}' from procurement order #{$procurementOrderId}",
                        'assets',
                        $createdAssets[0]['id']
                    );
                }
            }

            return [
                'success' => true,
                'assets' => $createdAssets,
                'count' => count($createdAssets)
            ];

        } catch (Exception $e) {
            error_log("Generate assets from procurement item error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate assets from procurement item',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Link asset to procurement order and item
     *
     * Creates a record in procurement_inventory table to maintain the relationship
     * between assets and their procurement sources. Supports both legacy and
     * multi-item procurement systems.
     *
     * @param int $assetId Asset ID to link
     * @param int|null $procurementOrderId Procurement order ID (multi-item system)
     * @param int|null $procurementItemId Procurement item ID (multi-item system)
     * @param int|null $legacyProcurementId Legacy procurement ID (old system)
     * @return bool Success status
     */
    private function linkAssetToProcurement(
        $assetId,
        $procurementOrderId = null,
        $procurementItemId = null,
        $legacyProcurementId = null
    ) {
        try {
            $sql = "INSERT INTO procurement_inventory (
                        asset_id,
                        procurement_order_id,
                        procurement_item_id,
                        procurement_id,
                        created_at
                    ) VALUES (?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $assetId,
                $procurementOrderId,
                $procurementItemId,
                $legacyProcurementId
            ]);

            return true;

        } catch (Exception $e) {
            error_log("Link asset to procurement error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all assets associated with a procurement order
     *
     * Retrieves assets with expanded category and procurement item information.
     * Useful for reviewing what assets were created from a procurement order.
     *
     * @param int $procurementOrderId Procurement order ID
     * @return array Array of assets with related data
     */
    public function getAssetsByProcurementOrder($procurementOrderId) {
        try {
            $sql = "
                SELECT
                    a.*,
                    c.name as category_name,
                    pi.item_name as procurement_item_name,
                    pi.brand as procurement_item_brand
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN procurement_items pi ON a.procurement_item_id = pi.id
                WHERE a.procurement_order_id = ?
                ORDER BY a.created_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementOrderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get assets by procurement order error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get procurement statistics for an order
     *
     * Returns count and total value of assets created from a procurement order.
     *
     * @param int $procurementOrderId Procurement order ID
     * @return array Statistics including count and total value
     */
    public function getProcurementAssetStats($procurementOrderId) {
        try {
            $sql = "
                SELECT
                    COUNT(*) as asset_count,
                    SUM(acquisition_cost) as total_value,
                    SUM(quantity) as total_quantity
                FROM inventory_items
                WHERE procurement_order_id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$procurementOrderId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'asset_count' => (int)($stats['asset_count'] ?? 0),
                'total_value' => (float)($stats['total_value'] ?? 0),
                'total_quantity' => (int)($stats['total_quantity'] ?? 0)
            ];

        } catch (Exception $e) {
            error_log("Get procurement asset stats error: " . $e->getMessage());
            return [
                'asset_count' => 0,
                'total_value' => 0,
                'total_quantity' => 0
            ];
        }
    }

    /**
     * Bulk create assets from multiple procurement items
     *
     * Efficiently creates assets from multiple items in a procurement order.
     * Uses transaction to ensure all-or-nothing behavior.
     *
     * @param int $procurementOrderId Procurement order ID
     * @param array $itemIds Array of procurement item IDs to process
     * @param array $commonAssetData Common asset data for all items
     * @return array Response with success status and summary
     */
    public function bulkCreateAssetsFromProcurement($procurementOrderId, $itemIds, $commonAssetData = []) {
        try {
            $this->db->beginTransaction();

            $allCreatedAssets = [];
            $successCount = 0;
            $failureCount = 0;
            $errors = [];

            foreach ($itemIds as $itemId) {
                $result = $this->generateAssetsFromProcurementItem(
                    $procurementOrderId,
                    $itemId,
                    $commonAssetData
                );

                if ($result['success']) {
                    $allCreatedAssets = array_merge($allCreatedAssets, $result['assets']);
                    $successCount += count($result['assets']);
                } else {
                    $failureCount++;
                    $errors[] = "Item {$itemId}: " . ($result['message'] ?? 'Unknown error');
                }
            }

            if ($failureCount > 0 && $successCount === 0) {
                // Complete failure - rollback
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Failed to create any assets',
                    'errors' => $errors
                ];
            }

            $this->db->commit();

            // Log bulk creation activity
            $this->logActivity(
                'bulk_create_from_procurement',
                "Bulk created {$successCount} assets from procurement order #{$procurementOrderId}",
                'assets',
                $procurementOrderId
            );

            return [
                'success' => true,
                'assets' => $allCreatedAssets,
                'summary' => [
                    'total_assets_created' => $successCount,
                    'items_processed' => count($itemIds),
                    'items_failed' => $failureCount,
                    'errors' => $errors
                ]
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Bulk create assets from procurement error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to bulk create assets from procurement',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate procurement item readiness for asset creation
     *
     * Checks if a procurement item has all required data to create assets.
     *
     * @param int $procurementItemId Procurement item ID
     * @return array Validation result with details
     */
    public function validateProcurementItemForAssetCreation($procurementItemId) {
        try {
            $procurementItem = $this->procurementItemModel->find($procurementItemId);

            if (!$procurementItem) {
                return [
                    'valid' => false,
                    'errors' => ['Procurement item not found']
                ];
            }

            $errors = [];

            // Required fields validation
            if (empty($procurementItem['item_name'])) {
                $errors[] = 'Item name is required';
            }

            if (empty($procurementItem['category_id'])) {
                $errors[] = 'Category is required';
            }

            if (empty($procurementItem['quantity']) || $procurementItem['quantity'] <= 0) {
                $errors[] = 'Valid quantity is required';
            }

            if (empty($procurementItem['unit_price']) || $procurementItem['unit_price'] < 0) {
                $errors[] = 'Valid unit price is required';
            }

            // Check if category exists
            if (!empty($procurementItem['category_id'])) {
                $category = $this->categoryModel->find($procurementItem['category_id']);
                if (!$category) {
                    $errors[] = 'Category does not exist';
                }
            }

            return [
                'valid' => empty($errors),
                'errors' => $errors,
                'item' => $procurementItem
            ];

        } catch (Exception $e) {
            error_log("Validate procurement item error: " . $e->getMessage());
            return [
                'valid' => false,
                'errors' => ['Validation failed: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Get combined procurement sources (legacy + multi-item)
     *
     * Retrieves all procurement orders that can be used as asset sources.
     * Combines legacy single-item procurement and multi-item procurement orders.
     * Used for asset creation form dropdown.
     *
     * @return array Array of procurement sources with type, po_number, title, vendor, etc.
     */
    public function getCombinedProcurementSources() {
        $procurements = [];

        try {
            // Get legacy procurement orders
            require_once APP_ROOT . '/models/ProcurementModel.php';
            $procurementModel = new ProcurementModel();
            $legacyProcurements = $procurementModel->getReceivedProcurements();

            foreach ($legacyProcurements as $procurement) {
                $procurements[] = [
                    'id' => $procurement['id'],
                    'type' => 'legacy',
                    'po_number' => $procurement['po_number'],
                    'title' => $procurement['item_name'],
                    'vendor_name' => $procurement['vendor_name'] ?? '',
                    'total_value' => $procurement['total_cost'] ?? 0,
                    'item_count' => 1
                ];
            }

            // Get multi-item procurement orders
            if (class_exists('ProcurementOrderModel')) {
                $multiItemOrders = $this->procurementOrderModel->getReceivedOrders();

                foreach ($multiItemOrders as $order) {
                    $procurements[] = [
                        'id' => $order['id'],
                        'type' => 'multi_item',
                        'po_number' => $order['po_number'],
                        'title' => $order['title'],
                        'vendor_name' => $order['vendor_name'] ?? '',
                        'total_value' => $order['total_value'] ?? 0,
                        'item_count' => $order['item_count'] ?? 0
                    ];
                }
            }

        } catch (Exception $e) {
            error_log("Get combined procurement sources error: " . $e->getMessage());
        }

        return $procurements;
    }
}
