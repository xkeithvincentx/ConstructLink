<?php
/**
 * ConstructLink™ Asset CRUD Service
 *
 * Handles core Create, Read, Update, Delete operations for assets.
 * Extracted from AssetModel as part of god object refactoring initiative.
 * Follows SOLID principles and 2025 industry standards.
 *
 * Responsibilities:
 * - Asset creation with validation and project scoping
 * - Asset updates with integrity checks
 * - Asset deletion with safety validations
 * - Asset retrieval with detailed information
 * - QR code operations and status management
 * - Bulk operations for efficiency
 *
 * @package ConstructLink
 * @subpackage Services\Asset
 * @version 2.0.0
 */

require_once APP_ROOT . '/core/Auth.php';
require_once APP_ROOT . '/core/Validator.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/traits/ActivityLoggingTrait.php';
require_once APP_ROOT . '/models/AssetModel.php';
require_once APP_ROOT . '/models/UserModel.php';
require_once APP_ROOT . '/models/CategoryModel.php';
require_once APP_ROOT . '/models/SystemSettingsModel.php';

class AssetCrudService {
    use ActivityLoggingTrait;

    private $db;
    private $assetModel;
    private $userModel;
    private $categoryModel;
    private $settingsModel;

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     * @param AssetModel|null $assetModel Asset model instance
     * @param UserModel|null $userModel User model instance
     * @param CategoryModel|null $categoryModel Category model instance
     */
    public function __construct(
        $db = null,
        $assetModel = null,
        $userModel = null,
        $categoryModel = null
    ) {
        if ($db === null) {
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }

        $this->assetModel = $assetModel ?? new AssetModel();
        $this->userModel = $userModel ?? new UserModel();
        $this->categoryModel = $categoryModel ?? new CategoryModel();
        $this->settingsModel = new SystemSettingsModel();
    }

    /**
     * Create asset with comprehensive validation and project scoping
     *
     * @param array $data Asset data
     * @return array Response with success status and asset data
     */
    public function createAsset($data) {
        try {
            $validation = $this->validateAssetData($data, true);
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }

            $this->db->beginTransaction();

            $projectAccessCheck = $this->validateProjectAccess($data['project_id']);
            if (!$projectAccessCheck['valid']) {
                $this->db->rollBack();
                return ['success' => false, 'message' => $projectAccessCheck['message']];
            }

            $categoryValidation = $this->validateCategoryBusinessRules($data);
            if (!$categoryValidation['success']) {
                $this->db->rollBack();
                return $categoryValidation;
            }

            // Check for duplicate consumable items (legacy workflow only)
            $isLegacyWorkflow = isset($data['inventory_source']) && $data['inventory_source'] === 'legacy';
            if ($isLegacyWorkflow && $categoryValidation['category']['is_consumable'] == 1) {
                $duplicateCheck = $this->checkConsumableDuplicate($data, $categoryValidation['category']);
                if ($duplicateCheck['is_duplicate']) {
                    // Add quantity to existing item instead of creating new one
                    $result = $this->addPendingQuantityToExistingItem(
                        $duplicateCheck['existing_item'],
                        $data
                    );
                    $this->db->commit();
                    return $result;
                }
            }

            $assetRef = $this->generateOrValidateReference($data);
            if (!$assetRef['success']) {
                $this->db->rollBack();
                return $assetRef;
            }
            $data['ref'] = $assetRef['ref'];

            $assetData = $this->prepareAssetData($data, $categoryValidation['category']);

            if ($this->isQRCodeEnabled()) {
                $assetData['qr_code'] = $this->generateQRCode($assetData['ref']);
            }

            $asset = $this->assetModel->create($assetData);
            if (!$asset) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to create asset'];
            }

            $this->processDisciplines($asset['id'], $data);
            $this->processTechnicalSpecifications($asset['id'], $data);
            $this->processNameStandardization($asset['id'], $data);

            $this->logActivity(
                'asset_created',
                "Asset created: {$assetData['name']} ({$assetData['ref']})",
                'assets',
                $asset['id']
            );

            $this->db->commit();
            return ['success' => true, 'asset' => $asset, 'message' => 'Asset created successfully'];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Asset creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create asset'];
        }
    }

    /**
     * Update asset with validation and project access checks
     *
     * @param int $id Asset ID
     * @param array $data Update data
     * @return array Response with success status
     */
    public function updateAsset($id, $data) {
        try {
            $asset = $this->assetModel->find($id);
            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found'];
            }

            $projectAccessCheck = $this->validateProjectAccess($asset['project_id']);
            if (!$projectAccessCheck['valid']) {
                return ['success' => false, 'message' => $projectAccessCheck['message']];
            }

            $validation = $this->validateAssetData($data, false);
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }

            $this->db->beginTransaction();

            if (isset($data['ref']) && $data['ref'] !== $asset['ref']) {
                $refCheck = $this->assetModel->findFirst(['ref' => $data['ref']]);
                if ($refCheck) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Asset reference already exists'];
                }
            }

            if (isset($data['project_id']) && $data['project_id'] != $asset['project_id']) {
                $newProjectCheck = $this->validateProjectAccess($data['project_id']);
                if (!$newProjectCheck['valid']) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Access denied: You do not have access to the target project'];
                }
            }

            $quantityValidation = $this->validateQuantityUpdate($asset, $data);
            if (!$quantityValidation['success']) {
                $this->db->rollBack();
                return $quantityValidation;
            }
            $data = array_merge($data, $quantityValidation['data']);

            $updateData = $this->prepareUpdateData($data);

            $oldData = $asset;
            $updatedAsset = $this->assetModel->update($id, $updateData);

            if (!$updatedAsset) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to update asset'];
            }

            $this->logActivity(
                'asset_updated',
                "Asset updated: {$asset['name']} ({$asset['ref']})",
                'assets',
                $id
            );

            $this->db->commit();
            return ['success' => true, 'asset' => $updatedAsset, 'message' => 'Asset updated successfully'];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Asset update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update asset'];
        }
    }

    /**
     * Delete asset with comprehensive safety checks
     *
     * @param int $id Asset ID
     * @return array Response with success status
     */
    public function deleteAsset($id) {
        try {
            $asset = $this->getAssetWithDetails($id);
            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found or access denied'];
            }

            $activeRecords = $this->checkActiveAssetRecords($id);
            if (!empty($activeRecords)) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete asset with active records: ' . implode(', ', $activeRecords)
                ];
            }

            $result = $this->assetModel->delete($id);

            if ($result) {
                $this->logActivity(
                    'asset_deleted',
                    "Asset deleted: {$asset['name']} ({$asset['ref']})",
                    'assets',
                    $id
                );

                return ['success' => true, 'message' => 'Asset deleted successfully'];
            }

            return ['success' => false, 'message' => 'Failed to delete asset'];

        } catch (Exception $e) {
            error_log("Asset deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete asset'];
        }
    }

    /**
     * Get asset with comprehensive details including relationships
     *
     * @param int $id Asset ID
     * @return array|false Asset data with details or false if not found
     */
    public function getAssetWithDetails($id) {
        try {
            $sql = "
                SELECT a.*,
                       c.name as category_name, c.is_consumable, c.asset_type,
                       p.name as project_name, p.code as project_code, p.location as project_location,
                       m.name as maker_name, m.country as maker_country,
                       v.name as vendor_name,
                       cl.name as client_name,
                       ab.official_name as brand_name, ab.quality_tier as brand_quality,
                       po.po_number, pi.item_name as procurement_item_name, pi.brand as procurement_item_brand
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN makers m ON a.maker_id = m.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN clients cl ON a.client_id = cl.id
                LEFT JOIN inventory_brands ab ON a.brand_id = ab.id
                LEFT JOIN procurement_orders po ON a.procurement_order_id = po.id
                LEFT JOIN procurement_items pi ON a.procurement_item_id = pi.id
                WHERE a.id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $asset = $stmt->fetch();

            if (!$asset) {
                return false;
            }

            $projectAccessCheck = $this->validateProjectAccess($asset['project_id']);
            if (!$projectAccessCheck['valid']) {
                return false;
            }

            return $asset;

        } catch (Exception $e) {
            error_log("Get asset with details error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find asset by QR code
     *
     * @param string $qrCode QR code to search for
     * @return array|false Asset data or false if not found
     */
    public function findByQRCode($qrCode) {
        try {
            return $this->assetModel->findFirst(['qr_code' => $qrCode]);
        } catch (Exception $e) {
            error_log("Find by QR code error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update asset status with logging
     *
     * @param int $id Asset ID
     * @param string $status New status
     * @param string|null $notes Optional notes
     * @return array Response with success status
     */
    public function updateAssetStatus($id, $status, $notes = null) {
        try {
            $asset = $this->assetModel->find($id);
            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found'];
            }

            $projectAccessCheck = $this->validateProjectAccess($asset['project_id']);
            if (!$projectAccessCheck['valid']) {
                return ['success' => false, 'message' => $projectAccessCheck['message']];
            }

            $oldStatus = $asset['status'];
            $result = $this->assetModel->update($id, ['status' => $status]);

            if ($result) {
                $description = "Status changed from {$oldStatus} to {$status}";
                if ($notes) {
                    $description .= ": {$notes}";
                }

                $this->logActivity('status_changed', $description, 'assets', $id);

                return ['success' => true, 'asset' => $result, 'message' => 'Asset status updated'];
            }

            return ['success' => false, 'message' => 'Failed to update asset status'];

        } catch (Exception $e) {
            error_log("Update asset status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update asset status'];
        }
    }

    /**
     * Bulk update asset status for multiple assets
     *
     * @param array $assetIds Array of asset IDs
     * @param string $status New status
     * @param string|null $notes Optional notes
     * @return array Response with success status
     */
    public function bulkUpdateStatus($assetIds, $status, $notes = null) {
        try {
            if (empty($assetIds) || !is_array($assetIds)) {
                return ['success' => false, 'message' => 'No assets selected'];
            }

            $this->db->beginTransaction();

            $placeholders = str_repeat('?,', count($assetIds) - 1) . '?';
            $sql = "UPDATE inventory_items SET status = ? WHERE id IN ({$placeholders})";

            $params = array_merge([$status], $assetIds);
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);

            if ($result) {
                $description = "Status updated to {$status}";
                if ($notes) {
                    $description .= ": {$notes}";
                }

                foreach ($assetIds as $assetId) {
                    $this->logActivity('bulk_status_update', $description, 'assets', $assetId);
                }

                $this->db->commit();
                return [
                    'success' => true,
                    'message' => 'Assets updated successfully',
                    'count' => count($assetIds)
                ];
            }

            $this->db->rollBack();
            return ['success' => false, 'message' => 'Failed to update assets'];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Bulk update status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update assets'];
        }
    }

    /**
     * Generate QR code for asset reference
     *
     * @param string $assetRef Asset reference
     * @return string|null QR code data or null on failure
     */
    private function generateQRCode($assetRef) {
        try {
            return base64_encode($assetRef . '|' . time());
        } catch (Exception $e) {
            error_log("Generate QR code error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if QR code generation is enabled in system settings
     *
     * @return bool True if QR codes are enabled
     */
    private function isQRCodeEnabled() {
        try {
            return $this->settingsModel->getSetting('qr_code_enabled', '1') === '1';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check for active asset records that prevent deletion
     *
     * @param int $assetId Asset ID
     * @return array List of active record types
     */
    private function checkActiveAssetRecords($assetId) {
        $activeRecords = [];

        try {
            $checks = [
                [
                    'sql' => "SELECT COUNT(*) FROM withdrawals WHERE asset_id = ? AND status IN ('pending', 'released')",
                    'message' => 'active withdrawals'
                ],
                [
                    'sql' => "SELECT COUNT(*) FROM transfers WHERE asset_id = ? AND status IN ('pending', 'approved')",
                    'message' => 'pending transfers'
                ],
                [
                    'sql' => "SELECT COUNT(*) FROM borrowed_tools WHERE asset_id = ? AND status = 'borrowed'",
                    'message' => 'active borrowings'
                ],
                [
                    'sql' => "SELECT COUNT(*) FROM maintenance WHERE asset_id = ? AND status IN ('scheduled', 'in_progress')",
                    'message' => 'active maintenance'
                ]
            ];

            foreach ($checks as $check) {
                $stmt = $this->db->prepare($check['sql']);
                $stmt->execute([$assetId]);
                if ($stmt->fetchColumn() > 0) {
                    $activeRecords[] = $check['message'];
                }
            }

        } catch (Exception $e) {
            error_log("Check active asset records error: " . $e->getMessage());
        }

        return $activeRecords;
    }

    /**
     * Generate or validate asset reference
     *
     * @param array $data Asset data
     * @return array Result with success and reference
     */
    private function generateOrValidateReference($data) {
        if (empty($data['ref'])) {
            $ref = generateAssetReference(
                $data['category_id'] ?? null,
                $data['primary_discipline'] ?? null,
                false
            );
            return ['success' => true, 'ref' => $ref];
        }

        if ($this->assetModel->findFirst(['ref' => $data['ref']])) {
            return ['success' => false, 'message' => 'Asset reference already exists'];
        }

        return ['success' => true, 'ref' => $data['ref']];
    }

    /**
     * Validate asset data
     *
     * @param array $data Data to validate
     * @param bool $isCreation True for creation, false for update
     * @return array Validation result
     */
    private function validateAssetData($data, $isCreation) {
        $rules = [];

        if ($isCreation) {
            $rules = [
                'name' => 'required|max:200',
                'category_id' => 'required|integer',
                'project_id' => 'required|integer',
                'acquired_date' => 'required|date'
            ];
        } else {
            $rules = [
                'name' => 'max:200',
                'category_id' => 'integer',
                'project_id' => 'integer'
            ];
        }

        return $this->assetModel->validate($data, $rules);
    }

    /**
     * Validate project access for current user
     *
     * @param int $projectId Project ID
     * @return array Validation result
     */
    private function validateProjectAccess($projectId) {
        $currentUser = Auth::getInstance()->getCurrentUser();

        if (!$this->userModel->hasProjectAccess($currentUser['id'], $projectId)) {
            return [
                'valid' => false,
                'message' => 'Access denied: You do not have access to this project'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate category business rules
     *
     * @param array $data Asset data
     * @return array Validation result with category
     */
    private function validateCategoryBusinessRules($data) {
        $category = $this->categoryModel->find($data['category_id']);

        if (!$category) {
            return ['success' => false, 'message' => 'Invalid category selected'];
        }

        if (!$category['generates_assets']) {
            return [
                'success' => false,
                'message' => 'Cannot create assets for expense-only categories. This category is configured for direct expense allocation.'
            ];
        }

        $unitCost = !empty($data['unit_cost']) ? (float)$data['unit_cost'] : (!empty($data['acquisition_cost']) ? (float)$data['acquisition_cost'] : 0);
        $threshold = (float)$category['capitalization_threshold'];

        if ($threshold > 0 && $unitCost > 0 && $unitCost < $threshold && $category['auto_expense_below_threshold']) {
            return [
                'success' => false,
                'message' => "Cannot create asset: Unit cost ({$unitCost}) is below the capitalization threshold ({$threshold}) for this category. Item should be expensed directly."
            ];
        }

        return ['success' => true, 'category' => $category];
    }

    /**
     * Prepare asset data for creation
     *
     * @param array $data Input data
     * @param array $category Category details
     * @return array Prepared asset data
     */
    private function prepareAssetData($data, $category) {
        $isConsumable = $category['is_consumable'] == 1;
        $isInventoryAsset = $category['asset_type'] === 'inventory';

        $quantity = 1;
        $availableQuantity = 1;

        if ($isConsumable || $isInventoryAsset) {
            $quantity = !empty($data['quantity']) ? (int)$data['quantity'] : 1;
            $availableQuantity = $quantity;
        }

        // Determine workflow status and made_by based on inventory_source
        $isLegacyWorkflow = isset($data['inventory_source']) && $data['inventory_source'] === 'legacy';
        // Legacy assets start at 'pending_verification' (auto-submitted for MVA workflow)
        // Non-legacy assets (e.g., from procurement) are auto-approved
        $workflowStatus = $isLegacyWorkflow ? 'pending_verification' : 'approved';
        $madeBy = null;

        if ($isLegacyWorkflow) {
            // Get current user (Warehouseman) for made_by field
            $auth = Auth::getInstance();
            $currentUser = $auth->getCurrentUser();
            $madeBy = $currentUser['id'] ?? null;
        }

        $assetData = [
            'ref' => $data['ref'],
            'category_id' => (int)$data['category_id'],
            'name' => Validator::sanitize($data['name']),
            'description' => Validator::sanitize($data['description'] ?? ''),
            'project_id' => (int)$data['project_id'],
            'maker_id' => !empty($data['maker_id']) ? (int)$data['maker_id'] : null,
            'vendor_id' => !empty($data['vendor_id']) ? (int)$data['vendor_id'] : null,
            'client_id' => !empty($data['client_id']) ? (int)$data['client_id'] : null,
            'acquired_date' => $data['acquired_date'],
            'status' => $data['status'] ?? 'available',
            'is_client_supplied' => isset($data['is_client_supplied']) ? 1 : 0,
            'quantity' => $quantity,
            'available_quantity' => $availableQuantity,
            'acquisition_cost' => !empty($data['acquisition_cost']) ? (float)$data['acquisition_cost'] : null,
            'serial_number' => Validator::sanitize($data['serial_number'] ?? ''),
            'model' => Validator::sanitize($data['model'] ?? ''),
            'specifications' => Validator::sanitize($data['specifications'] ?? ''),
            'warranty_expiry' => !empty($data['warranty_expiry']) ? $data['warranty_expiry'] : null,
            'location' => Validator::sanitize($data['location'] ?? ''),
            'condition_notes' => Validator::sanitize($data['condition_notes'] ?? ''),
            'unit' => Validator::sanitize($data['unit'] ?? 'pcs'),
            'procurement_order_id' => !empty($data['procurement_order_id']) ? (int)$data['procurement_order_id'] : null,
            'procurement_item_id' => !empty($data['procurement_item_id']) ? (int)$data['procurement_item_id'] : null,
            'unit_cost' => !empty($data['unit_cost']) ? (float)$data['unit_cost'] : $data['acquisition_cost'] ?? null,
            'equipment_type_id' => !empty($data['equipment_type_id']) ? (int)$data['equipment_type_id'] : null,
            'subtype_id' => !empty($data['subtype_id']) ? (int)$data['subtype_id'] : null,
            'generated_name' => !empty($data['generated_name']) ? Validator::sanitize($data['generated_name']) : null,
            'name_components' => !empty($data['name_components']) ? $data['name_components'] : null,
            'standardized_name' => !empty($data['standardized_name']) ? Validator::sanitize($data['standardized_name']) : null,
            'brand_id' => !empty($data['brand_id']) ? (int)$data['brand_id'] : null,
            // MVA Workflow fields
            'workflow_status' => $workflowStatus,
            'inventory_source' => $data['inventory_source'] ?? null,
            'made_by' => $madeBy
        ];

        return $assetData;
    }

    /**
     * Validate and prepare quantity update data
     *
     * @param array $asset Current asset
     * @param array $data Update data
     * @return array Validation result with prepared data
     */
    private function validateQuantityUpdate($asset, $data) {
        if (isset($data['category_id']) && $data['category_id'] != $asset['category_id']) {
            $newCategory = $this->categoryModel->find($data['category_id']);
            $isConsumable = $newCategory && $newCategory['is_consumable'] == 1;

            if ($isConsumable) {
                $data['quantity'] = !empty($data['quantity']) ? (int)$data['quantity'] : 1;
                $data['available_quantity'] = $data['quantity'];
            } else {
                $data['quantity'] = 1;
                $data['available_quantity'] = 1;
            }
        } elseif (isset($data['quantity'])) {
            $category = $this->categoryModel->find($asset['category_id']);
            $isConsumable = $category && $category['is_consumable'] == 1;

            if ($isConsumable) {
                $newQuantity = (int)$data['quantity'];
                $currentAvailable = $asset['available_quantity'] ?? 1;
                $currentTotal = $asset['quantity'] ?? 1;
                $usedQuantity = $currentTotal - $currentAvailable;

                if ($newQuantity < $usedQuantity) {
                    return [
                        'success' => false,
                        'message' => "Cannot reduce quantity below used amount ({$usedQuantity})"
                    ];
                }

                $data['available_quantity'] = $newQuantity - $usedQuantity;
            } else {
                $data['quantity'] = 1;
                $data['available_quantity'] = 1;
            }
        }

        return ['success' => true, 'data' => $data];
    }

    /**
     * Prepare update data from input
     *
     * @param array $data Input data
     * @return array Sanitized update data
     */
    private function prepareUpdateData($data) {
        $updateData = [];
        $allowedFields = [
            'ref', 'category_id', 'name', 'description', 'project_id', 'maker_id',
            'vendor_id', 'client_id', 'acquired_date', 'status', 'acquisition_cost',
            'unit_cost', 'serial_number', 'model', 'specifications', 'warranty_expiry',
            'location', 'sub_location', 'condition_notes', 'quantity', 'available_quantity'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = is_string($data[$field]) ? Validator::sanitize($data[$field]) : $data[$field];
            }
        }

        if (isset($data['is_client_supplied'])) {
            $updateData['is_client_supplied'] = $data['is_client_supplied'] ? 1 : 0;
        }

        return $updateData;
    }

    /**
     * Process and save discipline relationships
     *
     * @param int $assetId Asset ID
     * @param array $data Asset data with disciplines
     * @return void
     */
    private function processDisciplines($assetId, $data) {
        $disciplineCodes = [];

        if (!empty($data['primary_discipline'])) {
            $stmt = $this->db->prepare("SELECT iso_code FROM inventory_disciplines WHERE id = ? AND is_active = 1");
            $stmt->execute([(int)$data['primary_discipline']]);
            $isoCode = $stmt->fetchColumn();

            if ($isoCode) {
                $disciplineCodes[] = $isoCode;
            }
        }

        if (!empty($data['disciplines']) && is_array($data['disciplines'])) {
            foreach ($data['disciplines'] as $disciplineId) {
                $disciplineId = (int)$disciplineId;
                if ($disciplineId > 0) {
                    $stmt = $this->db->prepare("SELECT iso_code FROM inventory_disciplines WHERE id = ? AND is_active = 1");
                    $stmt->execute([$disciplineId]);
                    $isoCode = $stmt->fetchColumn();

                    if ($isoCode && !in_array($isoCode, $disciplineCodes)) {
                        $disciplineCodes[] = $isoCode;
                    }
                }
            }
        }

        if (!empty($disciplineCodes)) {
            $this->assetModel->update($assetId, ['discipline_tags' => implode(',', $disciplineCodes)]);
        }
    }

    /**
     * Process and save technical specifications
     *
     * @param int $assetId Asset ID
     * @param array $data Asset data with specifications
     * @return void
     */
    private function processTechnicalSpecifications($assetId, $data) {
        if (!empty($data['specifications_data']) && is_array($data['specifications_data'])) {
            require_once APP_ROOT . '/core/AssetSubtypeManager.php';
            $subtypeManager = new AssetSubtypeManager();

            if (!$subtypeManager->saveAssetProperties($assetId, $data['specifications_data'])) {
                error_log("Failed to save asset specifications for asset ID: {$assetId}");
            }
        }
    }

    /**
     * Process name standardization if available
     *
     * @param int $assetId Asset ID
     * @param array $data Asset data
     * @return void
     */
    private function processNameStandardization($assetId, $data) {
        if (!class_exists('AssetStandardizer')) {
            return;
        }

        try {
            $standardizer = AssetStandardizer::getInstance();
            $standardizationResult = $standardizer->processAssetName(
                $data['name'],
                $data['category_id'] ?? null
            );

            if ($standardizationResult['standardized'] && $standardizationResult['standardized'] !== $data['name']) {
                $this->assetModel->update($assetId, [
                    'original_name' => $data['name'],
                    'standardized_name' => $standardizationResult['standardized']
                ]);
            }
        } catch (Exception $e) {
            error_log("Asset standardization error: " . $e->getMessage());
        }
    }

    /**
     * Check if consumable item is a duplicate
     *
     * @param array $data New item data
     * @param array $category Category details
     * @return array Result with is_duplicate flag and existing item if found
     */
    private function checkConsumableDuplicate($data, $category) {
        try {
            require_once APP_ROOT . '/services/Asset/AssetMatchingService.php';
            $matchingService = new \Services\Asset\AssetMatchingService($this->db);

            $matchCriteria = [
                'name' => $data['name'],
                'category_id' => $data['category_id'],
                'project_id' => $data['project_id'],
                'consumable_only' => true
            ];

            // Add model to matching if provided
            if (!empty($data['model'])) {
                $matchCriteria['model'] = $data['model'];
            }

            $matchResult = $matchingService->findExistingConsumableItem($matchCriteria);

            if ($matchResult['success'] && !empty($matchResult['data']['items'])) {
                // Found exact match - return first matching item
                return [
                    'is_duplicate' => true,
                    'existing_item' => $matchResult['data']['items'][0]
                ];
            }

            return ['is_duplicate' => false, 'existing_item' => null];

        } catch (Exception $e) {
            error_log("Duplicate check error: " . $e->getMessage());
            // If duplicate check fails, proceed with creation (fail-safe)
            return ['is_duplicate' => false, 'existing_item' => null];
        }
    }

    /**
     * Add pending quantity to existing consumable item
     *
     * @param array $existingItem Existing inventory item
     * @param array $newData New item data with quantity
     * @return array Response with success status
     */
    private function addPendingQuantityToExistingItem($existingItem, $newData) {
        try {
            $currentUser = Auth::getInstance()->getCurrentUser();
            $quantityToAdd = isset($newData['quantity']) ? (int)$newData['quantity'] : 1;

            // Update the existing item with pending quantity
            $updateSql = "
                UPDATE inventory_items
                SET
                    pending_quantity_addition = pending_quantity_addition + ?,
                    pending_addition_made_by = ?,
                    pending_addition_date = NOW(),
                    workflow_status = 'pending_verification'
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($updateSql);
            $result = $stmt->execute([
                $quantityToAdd,
                $currentUser['id'],
                $existingItem['id']
            ]);

            if (!$result) {
                error_log("Failed to update pending quantity for item ID: {$existingItem['id']}");
                return [
                    'success' => false,
                    'message' => 'Failed to add quantity to existing item'
                ];
            }

            $this->logActivity(
                'pending_quantity_added',
                "Added pending quantity ({$quantityToAdd} {$existingItem['unit']}) to existing item: {$existingItem['name']} ({$existingItem['ref']}). Awaiting verification.",
                'inventory_items',
                $existingItem['id']
            );

            return [
                'success' => true,
                'is_duplicate' => true,
                'action' => 'quantity_added',
                'existing_item' => $existingItem,
                'quantity_added' => $quantityToAdd,
                'message' => "This item already exists in inventory. Added {$quantityToAdd} {$existingItem['unit']} as pending quantity awaiting verification."
            ];

        } catch (Exception $e) {
            error_log("Add pending quantity error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to add pending quantity'
            ];
        }
    }
}
