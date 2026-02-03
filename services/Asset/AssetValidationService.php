<?php
/**
 * AssetValidationService - Asset validation and business rules enforcement
 *
 * Extracts validation logic and business rules from AssetModel for better separation of concerns.
 * Handles comprehensive validation for asset creation, updates, and business rule enforcement.
 */

class AssetValidationService {
    private $db;
    private $categoryModel;
    private $userModel;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->categoryModel = new CategoryModel();
        $this->userModel = new UserModel();
    }

    /**
     * Validate asset creation with comprehensive business rules
     *
     * @param array $data Asset creation data
     * @param int $userId Current user ID for project access validation
     * @return array Validation result ['valid' => bool, 'errors' => array, 'sanitized_data' => array]
     */
    public function validateAssetCreation($data, $userId) {
        $errors = [];
        $sanitizedData = [];

        // Basic field validation
        $fieldValidation = $this->validateRequiredFields($data, [
            'name' => 'required|max:200',
            'category_id' => 'required|integer',
            'project_id' => 'required|integer',
            'acquired_date' => 'required|date'
        ]);

        if (!$fieldValidation['valid']) {
            return $fieldValidation;
        }

        // Validate project access
        $projectAccess = $this->validateProjectAccess($data['project_id'], $userId);
        if (!$projectAccess['valid']) {
            return $projectAccess;
        }

        // Validate reference uniqueness if provided
        if (!empty($data['ref'])) {
            $refValidation = $this->validateReferenceUniqueness($data['ref'], null);
            if (!$refValidation['valid']) {
                return $refValidation;
            }
        }

        // Validate category and business rules
        $categoryValidation = $this->validateCategoryRules(
            $data['category_id'],
            $this->extractUnitCost($data)
        );

        if (!$categoryValidation['valid']) {
            return $categoryValidation;
        }

        // Validate quantity based on category type
        $quantityValidation = $this->validateQuantityForCreation(
            $data,
            $categoryValidation['category']
        );

        if (!$quantityValidation['valid']) {
            return $quantityValidation;
        }

        // Sanitize data for database insertion
        $sanitizedData = $this->sanitizeAssetData($data, $categoryValidation['category']);

        return [
            'valid' => true,
            'errors' => [],
            'sanitized_data' => $sanitizedData,
            'category' => $categoryValidation['category']
        ];
    }

    /**
     * Validate asset update with existing asset context
     *
     * @param int $assetId Asset ID being updated
     * @param array $data Update data
     * @param int $userId Current user ID
     * @return array Validation result
     */
    public function validateAssetUpdate($assetId, $data, $userId) {
        $errors = [];

        // Get existing asset
        $assetModel = new AssetModel();
        $asset = $assetModel->find($assetId);

        if (!$asset) {
            return [
                'valid' => false,
                'errors' => ['Asset not found']
            ];
        }

        // Validate project access for current asset
        $projectAccess = $this->validateProjectAccess($asset['project_id'], $userId);
        if (!$projectAccess['valid']) {
            return $projectAccess;
        }

        // Basic field validation
        $fieldValidation = $this->validateRequiredFields($data, [
            'name' => 'max:200',
            'category_id' => 'integer',
            'project_id' => 'integer'
        ], false);

        if (!$fieldValidation['valid']) {
            return $fieldValidation;
        }

        // Validate reference change if applicable
        if (isset($data['ref']) && $data['ref'] !== $asset['ref']) {
            $refValidation = $this->validateReferenceUniqueness($data['ref'], $assetId);
            if (!$refValidation['valid']) {
                return $refValidation;
            }
        }

        // Validate new project access if project is being changed
        if (isset($data['project_id']) && $data['project_id'] != $asset['project_id']) {
            $newProjectAccess = $this->validateProjectAccess($data['project_id'], $userId);
            if (!$newProjectAccess['valid']) {
                return [
                    'valid' => false,
                    'errors' => ['Access denied: You do not have access to the target project']
                ];
            }
        }

        // Validate category change
        if (isset($data['category_id']) && $data['category_id'] != $asset['category_id']) {
            $categoryChangeValidation = $this->validateCategoryChange(
                $asset,
                $data['category_id'],
                $data
            );

            if (!$categoryChangeValidation['valid']) {
                return $categoryChangeValidation;
            }
        }

        // Validate quantity updates
        if (isset($data['quantity'])) {
            $quantityValidation = $this->validateQuantityRules($asset, (int)$data['quantity']);
            if (!$quantityValidation['valid']) {
                return $quantityValidation;
            }
        }

        // Sanitize update data
        $sanitizedData = $this->sanitizeUpdateData($data);

        return [
            'valid' => true,
            'errors' => [],
            'sanitized_data' => $sanitizedData,
            'asset' => $asset
        ];
    }

    /**
     * Validate category business rules (generates_assets, capitalization threshold)
     *
     * @param int $categoryId Category ID
     * @param float $unitCost Unit cost of asset
     * @return array Validation result with category data
     */
    public function validateCategoryRules($categoryId, $unitCost = 0) {
        $category = $this->categoryModel->find($categoryId);

        if (!$category) {
            return [
                'valid' => false,
                'errors' => ['Invalid category selected']
            ];
        }

        // Check if category can generate assets
        if (!$category['generates_assets']) {
            return [
                'valid' => false,
                'errors' => ['Cannot create assets for expense-only categories. This category is configured for direct expense allocation.']
            ];
        }

        // Check capitalization threshold
        $threshold = (float)$category['capitalization_threshold'];

        if ($threshold > 0 && $unitCost > 0 && $unitCost < $threshold && $category['auto_expense_below_threshold']) {
            return [
                'valid' => false,
                'errors' => ["Cannot create asset: Unit cost ({$unitCost}) is below the capitalization threshold ({$threshold}) for this category. Item should be expensed directly."]
            ];
        }

        return [
            'valid' => true,
            'errors' => [],
            'category' => $category
        ];
    }

    /**
     * Validate project access for user
     *
     * @param int $projectId Project ID
     * @param int $userId User ID
     * @return array Validation result
     */
    public function validateProjectAccess($projectId, $userId) {
        if (!$this->userModel->hasProjectAccess($userId, $projectId)) {
            return [
                'valid' => false,
                'errors' => ['Access denied: You do not have access to this project']
            ];
        }

        return [
            'valid' => true,
            'errors' => []
        ];
    }

    /**
     * Validate reference uniqueness
     *
     * @param string $ref Asset reference
     * @param int|null $excludeId Asset ID to exclude from check (for updates)
     * @return array Validation result
     */
    public function validateReferenceUniqueness($ref, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM inventory_items WHERE ref = ?";
        $params = [$ref];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->fetchColumn() > 0) {
            return [
                'valid' => false,
                'errors' => ['Asset reference already exists']
            ];
        }

        return [
            'valid' => true,
            'errors' => []
        ];
    }

    /**
     * Validate quantity rules (cannot reduce below used amount)
     *
     * @param array $asset Existing asset data
     * @param int $newQuantity New quantity
     * @return array Validation result
     */
    public function validateQuantityRules($asset, $newQuantity) {
        $category = $this->categoryModel->find($asset['category_id']);
        $isConsumable = $category && $category['is_consumable'] == 1;

        if (!$isConsumable) {
            // Non-consumable assets must have quantity of 1
            if ($newQuantity != 1) {
                return [
                    'valid' => false,
                    'errors' => ['Non-consumable assets must have a quantity of 1']
                ];
            }

            return [
                'valid' => true,
                'errors' => []
            ];
        }

        // For consumable assets, validate against used quantity
        $currentAvailable = $asset['available_quantity'] ?? 1;
        $currentTotal = $asset['quantity'] ?? 1;
        $usedQuantity = $currentTotal - $currentAvailable;

        if ($newQuantity < $usedQuantity) {
            return [
                'valid' => false,
                'errors' => ["Cannot reduce quantity below used amount ({$usedQuantity})"]
            ];
        }

        return [
            'valid' => true,
            'errors' => [],
            'used_quantity' => $usedQuantity,
            'available_quantity' => $newQuantity - $usedQuantity
        ];
    }

    /**
     * Validate required fields with rules
     *
     * @param array $data Input data
     * @param array $rules Validation rules
     * @param bool $strictRequired Whether required fields are strictly enforced
     * @return array Validation result
     */
    private function validateRequiredFields($data, $rules, $strictRequired = true) {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $ruleArray = explode('|', $rule);

            foreach ($ruleArray as $singleRule) {
                if ($singleRule === 'required') {
                    if ($strictRequired && empty($data[$field])) {
                        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                    }
                }

                if (strpos($singleRule, 'max:') === 0) {
                    $maxLength = (int)substr($singleRule, 4);
                    if (isset($data[$field]) && strlen($data[$field]) > $maxLength) {
                        $errors[] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$maxLength} characters";
                    }
                }

                if ($singleRule === 'integer') {
                    if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_INT)) {
                        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid integer';
                    }
                }

                if ($singleRule === 'date') {
                    if (isset($data[$field]) && !strtotime($data[$field])) {
                        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid date';
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate quantity for asset creation based on category type
     *
     * @param array $data Asset data
     * @param array $category Category data
     * @return array Validation result
     */
    private function validateQuantityForCreation($data, $category) {
        $isConsumable = $category && $category['is_consumable'] == 1;
        $isInventoryAsset = $category['asset_type'] === 'inventory';

        if (!$isConsumable && !$isInventoryAsset) {
            // Non-consumable capital assets don't need quantity validation
            return [
                'valid' => true,
                'errors' => []
            ];
        }

        // Consumable or inventory assets should have valid quantity
        if (isset($data['quantity'])) {
            $quantity = (int)$data['quantity'];

            if ($quantity < 1) {
                return [
                    'valid' => false,
                    'errors' => ['Quantity must be at least 1']
                ];
            }
        }

        return [
            'valid' => true,
            'errors' => []
        ];
    }

    /**
     * Validate category change during update
     *
     * @param array $asset Existing asset
     * @param int $newCategoryId New category ID
     * @param array $data Update data
     * @return array Validation result
     */
    private function validateCategoryChange($asset, $newCategoryId, $data) {
        $newCategory = $this->categoryModel->find($newCategoryId);

        if (!$newCategory) {
            return [
                'valid' => false,
                'errors' => ['Invalid new category selected']
            ];
        }

        // Check if new category generates assets
        if (!$newCategory['generates_assets']) {
            return [
                'valid' => false,
                'errors' => ['Cannot change to expense-only category']
            ];
        }

        return [
            'valid' => true,
            'errors' => [],
            'new_category' => $newCategory
        ];
    }

    /**
     * Sanitize asset data for database insertion
     *
     * @param array $data Raw input data
     * @param array $category Category data
     * @return array Sanitized data
     */
    private function sanitizeAssetData($data, $category) {
        $isConsumable = $category && $category['is_consumable'] == 1;
        $isInventoryAsset = $category['asset_type'] === 'inventory';

        // Determine quantity based on category type
        $quantity = 1;
        $availableQuantity = 1;

        if ($isConsumable || $isInventoryAsset) {
            $quantity = !empty($data['quantity']) ? (int)$data['quantity'] : 1;
            $availableQuantity = $quantity;
        }

        return [
            'ref' => $data['ref'] ?? null,
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
            'sub_location' => !empty($data['sub_location']) ? Validator::sanitize($data['sub_location']) : null
        ];
    }

    /**
     * Sanitize update data
     *
     * @param array $data Update data
     * @return array Sanitized data
     */
    private function sanitizeUpdateData($data) {
        $sanitized = [];
        $allowedFields = [
            'ref', 'category_id', 'name', 'description', 'project_id', 'maker_id',
            'vendor_id', 'client_id', 'acquired_date', 'status', 'acquisition_cost',
            'unit_cost', 'serial_number', 'model', 'specifications', 'warranty_expiry',
            'location', 'sub_location', 'condition_notes', 'quantity', 'available_quantity'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = is_string($data[$field]) ?
                    Validator::sanitize($data[$field]) :
                    $data[$field];
            }
        }

        if (isset($data['is_client_supplied'])) {
            $sanitized['is_client_supplied'] = $data['is_client_supplied'] ? 1 : 0;
        }

        return $sanitized;
    }

    /**
     * Extract unit cost from data (prioritizes unit_cost over acquisition_cost)
     *
     * @param array $data Asset data
     * @return float Unit cost
     */
    private function extractUnitCost($data) {
        if (!empty($data['unit_cost'])) {
            return (float)$data['unit_cost'];
        }

        if (!empty($data['acquisition_cost'])) {
            return (float)$data['acquisition_cost'];
        }

        return 0;
    }
}

