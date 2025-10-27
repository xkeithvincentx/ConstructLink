<?php
/**
 * ConstructLink™ Asset Model - Enhanced with Project Scoping and Procurement Integration
 * Handles asset management with project-level visibility and procurement linking
 */

class AssetModel extends BaseModel {
    protected $table = 'assets';
    protected $fillable = [
        'ref', 'category_id', 'name', 'description', 'project_id', 'maker_id',
        'vendor_id', 'client_id', 'acquired_date', 'status', 'is_client_supplied',
        'acquisition_cost', 'serial_number', 'model', 'qr_code', 'procurement_order_id',
        'procurement_item_id', 'unit_cost', 'quantity', 'available_quantity', 'unit',
        'asset_source', 'sub_location', 'workflow_status', 'made_by', 'verified_by',
        'authorized_by', 'verification_date', 'authorization_date',
        // Equipment classification fields
        'equipment_type_id', 'subtype_id', 'generated_name', 'name_components',
        // Asset details fields
        'specifications', 'warranty_expiry', 'location', 'condition_notes', 'current_condition',
        // Brand standardization fields
        'brand_id', 'standardized_name', 'original_name', 'asset_type_id',
        'discipline_tags',
        // QR tag tracking fields
        'qr_tag_printed', 'qr_tag_applied', 'qr_tag_verified',
        'qr_tag_applied_by', 'qr_tag_verified_by', 'tag_notes'
    ];
    
    /**
     * Override base create method to handle discipline relationships
     */
    public function create($data) {
        error_log("AssetModel::create called with data: " . print_r($data, true));
        
        // Handle discipline processing before creating asset
        $disciplineCodes = [];
        
        // Handle primary discipline
        if (!empty($data['primary_discipline'])) {
            error_log("Processing primary discipline: " . $data['primary_discipline']);
            $disciplineId = (int)$data['primary_discipline'];
            $stmt = $this->db->prepare("SELECT iso_code FROM asset_disciplines WHERE id = ? AND is_active = 1");
            $stmt->execute([$disciplineId]);
            $isoCode = $stmt->fetchColumn();
            error_log("Found ISO code: " . ($isoCode ?: 'NULL'));
            
            if ($isoCode) {
                $disciplineCodes[] = $isoCode;
                error_log("Added ISO code to disciplineCodes: " . $isoCode);
            }
        }
        
        // Handle additional disciplines
        if (!empty($data['disciplines']) && is_array($data['disciplines'])) {
            foreach ($data['disciplines'] as $disciplineId) {
                $disciplineId = (int)$disciplineId;
                if ($disciplineId > 0) {
                    $stmt = $this->db->prepare("SELECT iso_code FROM asset_disciplines WHERE id = ? AND is_active = 1");
                    $stmt->execute([$disciplineId]);
                    $isoCode = $stmt->fetchColumn();
                    
                    if ($isoCode && !in_array($isoCode, $disciplineCodes)) {
                        $disciplineCodes[] = $isoCode;
                    }
                }
            }
        }
        
        // Add discipline tags to data if we found any
        if (!empty($disciplineCodes)) {
            $data['discipline_tags'] = implode(',', $disciplineCodes);
            error_log("Setting discipline_tags to: " . $data['discipline_tags']);
        } else {
            error_log("No discipline codes found");
        }
        
        // Remove non-fillable discipline fields before calling parent create
        unset($data['primary_discipline']);
        unset($data['disciplines']);
        
        // Call parent create method
        return parent::create($data);
    }
    
    /**
     * Create asset with validation and project scoping
     */
    public function createAsset($data) {
        try {
            // Validate required fields
            $validation = $this->validate($data, [
                'name' => 'required|max:200',
                'category_id' => 'required|integer',
                'project_id' => 'required|integer',
                'acquired_date' => 'required|date'
            ]);
            
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            $this->beginTransaction();
            
            // Check project access for current user
            $currentUser = Auth::getInstance()->getCurrentUser();
            $userModel = new UserModel();
            
            if (!$userModel->hasProjectAccess($currentUser['id'], $data['project_id'])) {
                $this->rollback();
                return ['success' => false, 'message' => 'Access denied: You do not have access to this project'];
            }
            
            // Generate asset reference if not provided
            if (empty($data['ref'])) {
                $data['ref'] = generateAssetReference(
                    $data['category_id'] ?? null,
                    $data['primary_discipline'] ?? null,
                    false
                );
            } else {
                // Check if reference already exists
                if ($this->findFirst(['ref' => $data['ref']])) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Asset reference already exists'];
                }
            }
            
            // Check category business rules and validate asset generation eligibility
            $categoryModel = new CategoryModel();
            $category = $categoryModel->find($data['category_id']);
            
            if (!$category) {
                $this->rollback();
                return ['success' => false, 'message' => 'Invalid category selected'];
            }
            
            // Validate that this category can generate assets
            if (!$category['generates_assets']) {
                $this->rollback();
                return [
                    'success' => false, 
                    'message' => 'Cannot create assets for expense-only categories. This category is configured for direct expense allocation.'
                ];
            }
            
            // Check capitalization threshold if unit cost is provided
            $unitCost = !empty($data['unit_cost']) ? (float)$data['unit_cost'] : (!empty($data['acquisition_cost']) ? (float)$data['acquisition_cost'] : 0);
            $threshold = (float)$category['capitalization_threshold'];
            
            if ($threshold > 0 && $unitCost > 0 && $unitCost < $threshold && $category['auto_expense_below_threshold']) {
                $this->rollback();
                return [
                    'success' => false,
                    'message' => "Cannot create asset: Unit cost ($unitCost) is below the capitalization threshold ($threshold) for this category. Item should be expensed directly."
                ];
            }
            
            // Determine asset properties based on category type
            $isConsumable = $category && $category['is_consumable'] == 1;
            $isCapitalAsset = $category['asset_type'] === 'capital';
            $isInventoryAsset = $category['asset_type'] === 'inventory';
            
            // Set quantity based on category and asset type
            $quantity = 1; // Default for non-consumable
            $availableQuantity = 1; // Default for non-consumable
            
            if ($isConsumable || $isInventoryAsset) {
                $quantity = !empty($data['quantity']) ? (int)$data['quantity'] : 1;
                $availableQuantity = $quantity; // Initially all quantity is available
            }
            
            // Prepare asset data
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
                'brand_id' => !empty($data['brand_id']) ? (int)$data['brand_id'] : null
            ];
            
            // Generate QR code if enabled
            if ($this->isQRCodeEnabled()) {
                $assetData['qr_code'] = $this->generateQRCode($assetData['ref']);
            }
            
            // Create asset
            $asset = $this->create($assetData);
            
            if (!$asset) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to create asset'];
            }
            
            // Handle disciplines if provided
            $disciplineCodes = [];
            
            // Handle primary discipline
            if (!empty($data['primary_discipline'])) {
                error_log("Processing primary discipline: " . $data['primary_discipline']);
                $disciplineId = (int)$data['primary_discipline'];
                $stmt = $this->db->prepare("SELECT iso_code FROM asset_disciplines WHERE id = ? AND is_active = 1");
                $stmt->execute([$disciplineId]);
                $isoCode = $stmt->fetchColumn();
                error_log("Found ISO code: " . ($isoCode ?: 'NULL'));
                
                if ($isoCode) {
                    $disciplineCodes[] = $isoCode;
                    error_log("Added ISO code to disciplineCodes: " . $isoCode);
                }
            }
            
            // Handle additional disciplines
            if (!empty($data['disciplines']) && is_array($data['disciplines'])) {
                foreach ($data['disciplines'] as $disciplineId) {
                    $disciplineId = (int)$disciplineId;
                    if ($disciplineId > 0) {
                        // Get discipline ISO code for storing in discipline_tags
                        $stmt = $this->db->prepare("SELECT iso_code FROM asset_disciplines WHERE id = ? AND is_active = 1");
                        $stmt->execute([$disciplineId]);
                        $isoCode = $stmt->fetchColumn();
                        
                        if ($isoCode && !in_array($isoCode, $disciplineCodes)) {
                            $disciplineCodes[] = $isoCode;
                        }
                    }
                }
            }
            
            // Update asset with discipline tags (ISO codes)
            error_log("Discipline codes found: " . print_r($disciplineCodes, true));
            if (!empty($disciplineCodes)) {
                $disciplineTags = implode(',', $disciplineCodes);
                error_log("Updating asset " . $asset['id'] . " with discipline_tags: " . $disciplineTags);
                $this->update($asset['id'], ['discipline_tags' => $disciplineTags]);
                // Update the returned asset data as well
                $asset['discipline_tags'] = $disciplineTags;
                error_log("Asset data updated with discipline_tags: " . $asset['discipline_tags']);
            } else {
                error_log("No discipline codes found, skipping update");
            }
            
            // Handle technical specifications if provided
            if (!empty($data['specifications_data']) && is_array($data['specifications_data'])) {
                require_once APP_ROOT . '/core/AssetSubtypeManager.php';
                $subtypeManager = new AssetSubtypeManager();
                
                if (!$subtypeManager->saveAssetProperties($asset['id'], $data['specifications_data'])) {
                    error_log("Failed to save asset specifications for asset ID: " . $asset['id']);
                }
            }
            
            // Handle asset standardization if AssetStandardizer is available
            if (class_exists('AssetStandardizer')) {
                try {
                    $standardizer = AssetStandardizer::getInstance();
                    $standardizationResult = $standardizer->processAssetName($data['name'], $data['category_id'] ?? null);
                    
                    if ($standardizationResult['standardized'] && $standardizationResult['standardized'] !== $data['name']) {
                        $this->update($asset['id'], [
                            'original_name' => $data['name'],
                            'standardized_name' => $standardizationResult['standardized']
                        ]);
                    }
                } catch (Exception $e) {
                    // Don't fail asset creation if standardization fails
                    error_log("Asset standardization error: " . $e->getMessage());
                }
            }
            
            // Log activity
            $this->logAssetActivity($asset['id'], 'asset_created', 'Asset created', null, $assetData);
            
            $this->commit();
            return ['success' => true, 'asset' => $asset];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Asset creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create asset'];
        }
    }
    
    /**
     * Update asset with project access validation
     */
    public function updateAsset($id, $data) {
        try {
            $asset = $this->find($id);
            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            // Check project access
            $currentUser = Auth::getInstance()->getCurrentUser();
            $userModel = new UserModel();
            
            if (!$userModel->hasProjectAccess($currentUser['id'], $asset['project_id'])) {
                return ['success' => false, 'message' => 'Access denied: You do not have access to this asset'];
            }
            
            // Validate data
            $validation = $this->validate($data, [
                'name' => 'max:200',
                'category_id' => 'integer',
                'project_id' => 'integer'
            ]);
            
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            $this->beginTransaction();
            
            // Check if reference is being changed and if it already exists
            if (isset($data['ref']) && $data['ref'] !== $asset['ref']) {
                if ($this->findFirst(['ref' => $data['ref']])) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Asset reference already exists'];
                }
            }
            
            // Check project access for new project if being changed
            if (isset($data['project_id']) && $data['project_id'] != $asset['project_id']) {
                if (!$userModel->hasProjectAccess($currentUser['id'], $data['project_id'])) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Access denied: You do not have access to the target project'];
                }
            }
            
            // Handle quantity updates based on category type
            if (isset($data['category_id']) && $data['category_id'] != $asset['category_id']) {
                // Category changed, need to check if new category is consumable
                $categoryModel = new CategoryModel();
                $newCategory = $categoryModel->find($data['category_id']);
                $isConsumable = $newCategory && $newCategory['is_consumable'] == 1;
                
                if ($isConsumable) {
                    // New category is consumable, allow quantity input
                    $data['quantity'] = !empty($data['quantity']) ? (int)$data['quantity'] : 1;
                    $data['available_quantity'] = $data['quantity']; // Reset available quantity
                } else {
                    // New category is not consumable, force quantity to 1
                    $data['quantity'] = 1;
                    $data['available_quantity'] = 1;
                }
            } elseif (isset($data['quantity'])) {
                // Quantity update for existing category
                $categoryModel = new CategoryModel();
                $category = $categoryModel->find($asset['category_id']);
                $isConsumable = $category && $category['is_consumable'] == 1;
                
                if ($isConsumable) {
                    $newQuantity = (int)$data['quantity'];
                    $currentAvailable = $asset['available_quantity'] ?? 1;
                    $currentTotal = $asset['quantity'] ?? 1;
                    $usedQuantity = $currentTotal - $currentAvailable;
                    
                    // Ensure new quantity is not less than used quantity
                    if ($newQuantity < $usedQuantity) {
                        $this->rollback();
                        return ['success' => false, 'message' => 'Cannot reduce quantity below used amount (' . $usedQuantity . ')'];
                    }
                    
                    $data['available_quantity'] = $newQuantity - $usedQuantity;
                } else {
                    // Non-consumable, force quantity to 1
                    $data['quantity'] = 1;
                    $data['available_quantity'] = 1;
                }
            }
            
            // Prepare update data
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
            
            // Update asset
            $oldData = $asset;
            $updatedAsset = $this->update($id, $updateData);
            
            if (!$updatedAsset) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update asset'];
            }
            
            // Log activity
            $this->logAssetActivity($id, 'asset_updated', 'Asset updated', $oldData, $updateData);
            
            $this->commit();
            return ['success' => true, 'asset' => $updatedAsset];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Asset update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update asset'];
        }
    }
    
    /**
     * Get asset with detailed information
     */
    public function getAssetWithDetails($id) {
        try {
            $sql = "
                SELECT a.*, 
                       c.name as category_name, c.is_consumable,
                       p.name as project_name, p.code as project_code, p.location as project_location,
                       m.name as maker_name, m.country as maker_country,
                       v.name as vendor_name,
                       cl.name as client_name,
                       ab.official_name as brand_name, ab.quality_tier as brand_quality,
                       po.po_number, pi.item_name as procurement_item_name, pi.brand as procurement_item_brand
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN makers m ON a.maker_id = m.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN clients cl ON a.client_id = cl.id
                LEFT JOIN asset_brands ab ON a.brand_id = ab.id
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
            
            // Check project access
            $currentUser = Auth::getInstance()->getCurrentUser();
            $userModel = new UserModel();
            
            if (!$userModel->hasProjectAccess($currentUser['id'], $asset['project_id'])) {
                return false; // Access denied
            }
            
            return $asset;
            
        } catch (Exception $e) {
            error_log("Get asset with details error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Consume quantity from consumable asset
     */
    public function consumeQuantity($assetId, $quantityToConsume, $reason = null) {
        try {
            $asset = $this->find($assetId);
            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            // Check if asset category is consumable
            $categoryModel = new CategoryModel();
            $category = $categoryModel->find($asset['category_id']);
            if (!$category || $category['is_consumable'] != 1) {
                return ['success' => false, 'message' => 'Asset is not consumable'];
            }
            
            // Check if enough quantity is available
            $availableQuantity = $asset['available_quantity'] ?? 0;
            if ($quantityToConsume > $availableQuantity) {
                return ['success' => false, 'message' => 'Insufficient quantity available. Available: ' . $availableQuantity];
            }
            
            $this->beginTransaction();
            
            // Update available quantity
            $newAvailableQuantity = $availableQuantity - $quantityToConsume;
            $updateResult = $this->update($assetId, ['available_quantity' => $newAvailableQuantity]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update quantity'];
            }
            
            // Log activity
            $this->logAssetActivity($assetId, 'quantity_consumed', 
                "Consumed {$quantityToConsume} units" . ($reason ? ": {$reason}" : ''), 
                ['available_quantity' => $availableQuantity], 
                ['available_quantity' => $newAvailableQuantity]);
            
            $this->commit();
            
            return [
                'success' => true, 
                'message' => 'Quantity consumed successfully',
                'consumed' => $quantityToConsume,
                'remaining' => $newAvailableQuantity
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Consume quantity error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to consume quantity'];
        }
    }
    
    /**
     * Restore quantity to consumable asset
     */
    public function restoreQuantity($assetId, $quantityToRestore, $reason = null) {
        try {
            $asset = $this->find($assetId);
            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            // Check if asset category is consumable
            $categoryModel = new CategoryModel();
            $category = $categoryModel->find($asset['category_id']);
            if (!$category || $category['is_consumable'] != 1) {
                return ['success' => false, 'message' => 'Asset is not consumable'];
            }
            
            $this->beginTransaction();
            
            // Update available quantity
            $currentAvailable = $asset['available_quantity'] ?? 0;
            $totalQuantity = $asset['quantity'] ?? 1;
            $newAvailableQuantity = min($currentAvailable + $quantityToRestore, $totalQuantity);
            
            $updateResult = $this->update($assetId, ['available_quantity' => $newAvailableQuantity]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update quantity'];
            }
            
            // Log activity
            $this->logAssetActivity($assetId, 'quantity_restored', 
                "Restored {$quantityToRestore} units" . ($reason ? ": {$reason}" : ''), 
                ['available_quantity' => $currentAvailable], 
                ['available_quantity' => $newAvailableQuantity]);
            
            $this->commit();
            
            return [
                'success' => true, 
                'message' => 'Quantity restored successfully',
                'restored' => $quantityToRestore,
                'available' => $newAvailableQuantity
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Restore quantity error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to restore quantity'];
        }
    }
    
    /**
     * Get assets with filters and project scoping
     */
    public function getAssetsWithFilters($filters = [], $page = 1, $perPage = 20) {
        try {
            $conditions = [];
            $params = [];
            
            // Project scoping for non-admin users
            $currentUser = Auth::getInstance()->getCurrentUser();
            $userModel = new UserModel();
            
            if (!in_array($currentUser['role_name'], ['System Admin', 'Finance Director', 'Asset Director'])) {
                if ($currentUser['current_project_id']) {
                    $conditions[] = "a.project_id = ?";
                    $params[] = $currentUser['current_project_id'];
                }
            }
            
            // Apply filters
            if (!empty($filters['status'])) {
                $conditions[] = "a.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['category_id'])) {
                $conditions[] = "a.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            if (!empty($filters['project_id'])) {
                // Security check: Ensure non-admin users can only filter their assigned projects
                if (!in_array($currentUser['role_name'], ['System Admin', 'Finance Director', 'Asset Director'])) {
                    if ($filters['project_id'] != $currentUser['current_project_id']) {
                        // Reject unauthorized project access - return empty result
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
                $conditions[] = "a.project_id = ?";
                $params[] = $filters['project_id'];
            }
            
            if (!empty($filters['vendor_id'])) {
                $conditions[] = "a.vendor_id = ?";
                $params[] = $filters['vendor_id'];
            }
            
            if (!empty($filters['maker_id'])) {
                $conditions[] = "a.maker_id = ?";
                $params[] = $filters['maker_id'];
            }
            
            if (isset($filters['is_client_supplied'])) {
                $conditions[] = "a.is_client_supplied = ?";
                $params[] = $filters['is_client_supplied'];
            }
            
            // Workflow status filter commented out as column doesn't exist in database
            // if (!empty($filters['workflow_status'])) {
            //     $conditions[] = "a.workflow_status = ?";
            //     $params[] = $filters['workflow_status'];
            // }
            
            if (!empty($filters['asset_type'])) {
                switch ($filters['asset_type']) {
                    case 'consumable':
                        $conditions[] = "c.is_consumable = 1";
                        break;
                    case 'non_consumable':
                        $conditions[] = "(c.is_consumable = 0 OR c.is_consumable IS NULL)";
                        break;
                    case 'low_stock':
                        $conditions[] = "c.is_consumable = 1 AND a.available_quantity <= (a.quantity * 0.2) AND a.available_quantity > 0";
                        break;
                    case 'out_of_stock':
                        $conditions[] = "c.is_consumable = 1 AND a.available_quantity = 0";
                        break;
                }
            }
            
            if (!empty($filters['search'])) {
                $conditions[] = "(a.ref LIKE ? OR a.name LIKE ? OR a.serial_number LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            // Count total records
            $countSql = "
                SELECT COUNT(*) FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN makers m ON a.maker_id = m.id
                LEFT JOIN procurement_orders po ON a.procurement_order_id = po.id
                LEFT JOIN procurement_items pi ON a.procurement_item_id = pi.id
                {$whereClause}
            ";
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();
            
            // Get paginated data
            $offset = ($page - 1) * $perPage;
            $orderBy = $filters['order_by'] ?? 'a.created_at DESC';
            
            $dataSql = "
                SELECT a.*, 
                       c.name as category_name,
                       p.name as project_name,
                       v.name as vendor_name,
                       m.name as maker_name,
                       po.po_number, pi.item_name as procurement_item_name, pi.brand as procurement_item_brand
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN makers m ON a.maker_id = m.id
                LEFT JOIN procurement_orders po ON a.procurement_order_id = po.id
                LEFT JOIN procurement_items pi ON a.procurement_item_id = pi.id
                {$whereClause}
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}
            ";
            
            $stmt = $this->db->prepare($dataSql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            return [
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                    'has_next' => $page < ceil($total / $perPage),
                    'has_prev' => $page > 1
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Get assets with filters error: " . $e->getMessage());
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
     * Get assets by project (for project-scoped operations)
     */
    public function getAssetsByProject($projectId, $status = null) {
        try {
            $conditions = ["a.project_id = ?"];
            $params = [$projectId];
            
            if ($status) {
                $conditions[] = "a.status = ?";
                $params[] = $status;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT a.*, c.name as category_name
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                {$whereClause}
                ORDER BY a.name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get assets by project error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get available assets for withdrawal/transfer
     */
    public function getAvailableAssets($projectId = null) {
        try {
            // Only include assets that are truly available (not in_transit, borrowed, etc.)
            $conditions = ["a.status = 'available'"];
            $params = [];
            
            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }
            
            // Exclude assets that are currently borrowed, withdrawn, or transferred
            $conditions[] = "a.id NOT IN (
                SELECT DISTINCT asset_id FROM borrowed_tools WHERE status = 'borrowed'
                UNION
                SELECT DISTINCT asset_id FROM withdrawals WHERE status IN ('pending', 'released')
                UNION
                SELECT DISTINCT asset_id FROM transfers WHERE status IN ('pending', 'approved')
            )";
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT a.*, c.name as category_name, p.name as project_name
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                {$whereClause}
                ORDER BY p.name ASC, a.name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get available assets error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get asset statistics with project scoping
     */
    public function getAssetStatistics($projectId = null) {
        try {
            $conditions = [];
            $params = [];
            
            // Project scoping
            $currentUser = Auth::getInstance()->getCurrentUser();
            
            if ($projectId) {
                $conditions[] = "project_id = ?";
                $params[] = $projectId;
            } elseif (!in_array($currentUser['role_name'], ['System Admin', 'Finance Director', 'Asset Director'])) {
                if ($currentUser['current_project_id']) {
                    $conditions[] = "project_id = ?";
                    $params[] = $currentUser['current_project_id'];
                }
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT 
                    COUNT(*) as total_assets,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use,
                    SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed,
                    SUM(CASE WHEN status = 'under_maintenance' THEN 1 ELSE 0 END) as under_maintenance,
                    SUM(CASE WHEN status = 'retired' THEN 1 ELSE 0 END) as retired,
                    SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) as in_transit,
                    SUM(CASE WHEN status = 'disposed' THEN 1 ELSE 0 END) as disposed,
                    SUM(CASE WHEN is_client_supplied = 1 THEN 1 ELSE 0 END) as client_supplied,
                    SUM(acquisition_cost) as total_value,
                    AVG(acquisition_cost) as average_value
                FROM assets 
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result ?: [
                'total_assets' => 0,
                'available' => 0,
                'in_use' => 0,
                'borrowed' => 0,
                'under_maintenance' => 0,
                'retired' => 0,
                'client_supplied' => 0,
                'total_value' => 0,
                'average_value' => 0
            ];
            
        } catch (Exception $e) {
            error_log("Get asset statistics error: " . $e->getMessage());
            return [
                'total_assets' => 0,
                'available' => 0,
                'in_use' => 0,
                'borrowed' => 0,
                'under_maintenance' => 0,
                'retired' => 0,
                'client_supplied' => 0,
                'total_value' => 0,
                'average_value' => 0
            ];
        }
    }
    
    /**
     * Create asset from procurement item (multi-item support)
     */
    public function createAssetFromProcurementItem($procurementOrderId, $procurementItemId, $assetData = []) {
        try {
            // Check if multi-item procurement models exist
            if (!class_exists('ProcurementOrderModel') || !class_exists('ProcurementItemModel')) {
                return ['success' => false, 'message' => 'Multi-item procurement system not available'];
            }
            
            // Get procurement order and item details
            $procurementOrderModel = new ProcurementOrderModel();
            $procurementItemModel = new ProcurementItemModel();
            
            $procurementOrder = $procurementOrderModel->find($procurementOrderId);
            $procurementItem = $procurementItemModel->find($procurementItemId);
            
            if (!$procurementOrder || !$procurementItem) {
                return ['success' => false, 'message' => 'Procurement order or item not found'];
            }
            
            // Prepare asset data with procurement information
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
            
            // Merge with provided asset data
            $finalAssetData = array_merge($defaultAssetData, $assetData);

            // Ensure correct quantity for consumables
            $categoryModel = new CategoryModel();
            $category = $categoryModel->find($finalAssetData['category_id']);
            $isConsumable = $category && $category['is_consumable'] == 1;
            if ($isConsumable) {
                $finalAssetData['quantity'] = isset($procurementItem['quantity']) && $procurementItem['quantity'] > 0 ? (int)$procurementItem['quantity'] : 1;
                $finalAssetData['available_quantity'] = $finalAssetData['quantity'];
            }
            
            // Create the asset
            $result = $this->createAsset($finalAssetData);
            
            if ($result['success']) {
                // Link asset to procurement
                $this->linkAssetToProcurement($result['asset']['id'], $procurementOrderId, $procurementItemId);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Create asset from procurement item error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create asset from procurement item'];
        }
    }
    
    /**
     * Generate assets from a procurement item (handles both consumable and non-consumable)
     * If consumable: one asset with total quantity
     * If non-consumable: one asset per quantity (each with quantity=1)
     */
    public function generateAssetsFromProcurementItem($procurementOrderId, $procurementItemId, $assetData = []) {
        // Get procurement order and item details
        $procurementOrderModel = new ProcurementOrderModel();
        $procurementItemModel = new ProcurementItemModel();
        $procurementOrder = $procurementOrderModel->find($procurementOrderId);
        $procurementItem = $procurementItemModel->find($procurementItemId);
        if (!$procurementOrder || !$procurementItem) {
            return ['success' => false, 'message' => 'Procurement order or item not found'];
        }
        $categoryModel = new CategoryModel();
        $category = $categoryModel->find($procurementItem['category_id']);
        $isConsumable = $category && $category['is_consumable'] == 1;
        $createdAssets = [];
        if ($isConsumable) {
            // One asset with total quantity
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
            $result = $this->createAsset($finalAssetData);
            if ($result['success']) {
                $this->linkAssetToProcurement($result['asset']['id'], $procurementOrderId, $procurementItemId);
                $createdAssets[] = $result['asset'];
            }
        } else {
            // One asset per quantity, each with quantity=1
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
                $result = $this->createAsset($finalAssetData);
                if ($result['success']) {
                    $this->linkAssetToProcurement($result['asset']['id'], $procurementOrderId, $procurementItemId);
                    $createdAssets[] = $result['asset'];
                }
            }
        }
        return ['success' => true, 'assets' => $createdAssets];
    }
    
    /**
     * Link asset to procurement (supports both legacy and multi-item)
     */
    private function linkAssetToProcurement($assetId, $procurementOrderId = null, $procurementItemId = null, $legacyProcurementId = null) {
        try {
            $sql = "INSERT INTO procurement_assets (asset_id, procurement_order_id, procurement_item_id, procurement_id, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            
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
     * Get assets by procurement order
     */
    public function getAssetsByProcurementOrder($procurementOrderId) {
        try {
            $sql = "
                SELECT a.*, c.name as category_name, pi.item_name as procurement_item_name, pi.brand as procurement_item_brand
                FROM assets a
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
     * Get asset utilization report
     */
    public function getAssetUtilization($projectId = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT a.*, c.name as category_name, p.name as project_name,
                       COUNT(w.id) as withdrawal_count,
                       COUNT(CASE WHEN w.status = 'released' THEN 1 END) as active_withdrawals,
                       COUNT(bt.id) as borrow_count,
                       COUNT(t.id) as transfer_count
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN withdrawals w ON a.id = w.asset_id
                LEFT JOIN borrowed_tools bt ON a.id = bt.asset_id
                LEFT JOIN transfers t ON a.id = t.asset_id
                {$whereClause}
                GROUP BY a.id
                ORDER BY withdrawal_count DESC, borrow_count DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get asset utilization error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate asset reference
     */
    private function generateAssetReference() {
        try {
            $settingsModel = new SystemSettingsModel();
            $prefix = $settingsModel->getSetting('asset_ref_prefix', 'CL');
            
            // Get next sequence number
            $sql = "SELECT MAX(CAST(SUBSTRING(ref, LENGTH(?) + 1) AS UNSIGNED)) as max_seq FROM assets WHERE ref LIKE ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$prefix, $prefix . '%']);
            $result = $stmt->fetch();
            
            $nextSeq = ($result['max_seq'] ?? 0) + 1;
            
            return $prefix . date('Y') . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
            
        } catch (Exception $e) {
            error_log("Generate asset reference error: " . $e->getMessage());
            return 'CL' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
    
    /**
     * Generate QR code for asset
     */
    private function generateQRCode($assetRef) {
        try {
            // Simple QR code data - in real implementation, use QR code library
            return base64_encode($assetRef . '|' . time());
        } catch (Exception $e) {
            error_log("Generate QR code error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if QR code generation is enabled
     */
    private function isQRCodeEnabled() {
        try {
            $settingsModel = new SystemSettingsModel();
            return $settingsModel->getSetting('qr_code_enabled', '1') === '1';
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Update asset status
     */
    public function updateAssetStatus($id, $status, $notes = null) {
        try {
            $asset = $this->find($id);
            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            // Check project access
            $currentUser = Auth::getInstance()->getCurrentUser();
            $userModel = new UserModel();
            
            if (!$userModel->hasProjectAccess($currentUser['id'], $asset['project_id'])) {
                return ['success' => false, 'message' => 'Access denied: You do not have access to this asset'];
            }
            
            $oldStatus = $asset['status'];
            $result = $this->update($id, ['status' => $status]);
            
            if ($result) {
                // Log activity
                $this->logAssetActivity($id, 'status_changed', "Status changed from {$oldStatus} to {$status}" . ($notes ? ": {$notes}" : ''), 
                    ['status' => $oldStatus], ['status' => $status]);
                
                return ['success' => true, 'asset' => $result];
            } else {
                return ['success' => false, 'message' => 'Failed to update asset status'];
            }
            
        } catch (Exception $e) {
            error_log("Update asset status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update asset status'];
        }
    }
    
    /**
     * Get asset history (withdrawals, transfers, maintenance, etc.)
     */
    public function getAssetHistory($assetId) {
        try {
            $history = [];
            
            // Get withdrawals
            $withdrawalModel = new WithdrawalModel();
            $withdrawals = $withdrawalModel->getAssetWithdrawalHistory($assetId);
            foreach ($withdrawals as $withdrawal) {
                $history[] = [
                    'type' => 'withdrawal',
                    'date' => $withdrawal['created_at'],
                    'description' => "Withdrawn by {$withdrawal['withdrawn_by_name']} for {$withdrawal['purpose']}",
                    'status' => $withdrawal['status'],
                    'data' => $withdrawal
                ];
            }
            
            // Get transfers
            $sql = "
                SELECT t.*, pf.name as from_project, pt.name as to_project, u.full_name as initiated_by_name
                FROM transfers t
                LEFT JOIN projects pf ON t.from_project = pf.id
                LEFT JOIN projects pt ON t.to_project = pt.id
                LEFT JOIN users u ON t.initiated_by = u.id
                WHERE t.asset_id = ?
                ORDER BY t.created_at DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            $transfers = $stmt->fetchAll();
            
            foreach ($transfers as $transfer) {
                $history[] = [
                    'type' => 'transfer',
                    'date' => $transfer['created_at'],
                    'description' => "Transfer from {$transfer['from_project']} to {$transfer['to_project']} by {$transfer['initiated_by_name']}",
                    'status' => $transfer['status'],
                    'data' => $transfer
                ];
            }
            
            // Get maintenance records
            $sql = "
                SELECT m.*, u.full_name as assigned_to_name
                FROM maintenance m
                LEFT JOIN users u ON m.assigned_to = u.id
                WHERE m.asset_id = ?
                ORDER BY m.created_at DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            $maintenance = $stmt->fetchAll();
            
            foreach ($maintenance as $maint) {
                $history[] = [
                    'type' => 'maintenance',
                    'date' => $maint['created_at'],
                    'description' => "{$maint['type']} maintenance: {$maint['description']}",
                    'status' => $maint['status'],
                    'data' => $maint
                ];
            }
            
            // Sort by date descending
            usort($history, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            return $history;
            
        } catch (Exception $e) {
            error_log("Get asset history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Log asset activity
     */
    private function logAssetActivity($assetId, $action, $description, $oldValues = null, $newValues = null) {
        try {
            $auth = Auth::getInstance();
            $user = $auth->getCurrentUser();
            
            $sql = "INSERT INTO activity_logs (user_id, action, description, table_name, record_id, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, 'assets', ?, ?, ?, NOW())";
            
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
            error_log("Asset activity logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Delete asset (with validation)
     */
    public function deleteAsset($id) {
        try {
            $asset = $this->getAssetWithDetails($id);
            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found or access denied'];
            }
            
            // Check if asset has active records
            $activeRecords = $this->checkActiveAssetRecords($id);
            if (!empty($activeRecords)) {
                return ['success' => false, 'message' => 'Cannot delete asset with active records: ' . implode(', ', $activeRecords)];
            }
            
            $result = $this->delete($id);
            
            if ($result) {
                // Log activity
                $this->logAssetActivity($id, 'asset_deleted', 'Asset deleted', $asset, null);
                
                return ['success' => true, 'message' => 'Asset deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete asset'];
            }
            
        } catch (Exception $e) {
            error_log("Asset deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete asset'];
        }
    }
    
    /**
     * Check for active asset records
     */
    private function checkActiveAssetRecords($assetId) {
        $activeRecords = [];
        
        try {
            // Check withdrawals
            $sql = "SELECT COUNT(*) FROM withdrawals WHERE asset_id = ? AND status IN ('pending', 'released')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            if ($stmt->fetchColumn() > 0) {
                $activeRecords[] = 'active withdrawals';
            }
            
            // Check transfers
            $sql = "SELECT COUNT(*) FROM transfers WHERE asset_id = ? AND status IN ('pending', 'approved')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            if ($stmt->fetchColumn() > 0) {
                $activeRecords[] = 'pending transfers';
            }
            
            // Check borrowed tools
            $sql = "SELECT COUNT(*) FROM borrowed_tools WHERE asset_id = ? AND status = 'borrowed'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            if ($stmt->fetchColumn() > 0) {
                $activeRecords[] = 'active borrowings';
            }
            
            // Check maintenance
            $sql = "SELECT COUNT(*) FROM maintenance WHERE asset_id = ? AND status IN ('scheduled', 'in_progress')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            if ($stmt->fetchColumn() > 0) {
                $activeRecords[] = 'active maintenance';
            }
            
        } catch (Exception $e) {
            error_log("Check active asset records error: " . $e->getMessage());
        }
        
        return $activeRecords;
    }
    
    /**
     * Search assets by QR code
     */
    public function findByQRCode($qrCode) {
        try {
            return $this->findFirst(['qr_code' => $qrCode]);
        } catch (Exception $e) {
            error_log("Find by QR code error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get assets by category
     */
    public function getAssetsByCategory($categoryId, $projectId = null) {
        try {
            $conditions = ["a.category_id = ?"];
            $params = [$categoryId];
            
            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT a.*, p.name as project_name
                FROM assets a
                LEFT JOIN projects p ON a.project_id = p.id
                {$whereClause}
                ORDER BY a.name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get assets by category error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get assets by vendor
     */
    public function getAssetsByVendor($vendorId, $projectId = null) {
        try {
            $conditions = ["a.vendor_id = ?"];
            $params = [$vendorId];
            
            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT a.*, p.name as project_name, c.name as category_name
                FROM assets a
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN categories c ON a.category_id = c.id
                {$whereClause}
                ORDER BY a.name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get assets by vendor error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get overdue assets (for maintenance, returns, etc.)
     */
    public function getOverdueAssets($type = 'maintenance') {
        try {
            $sql = "";
            
            switch ($type) {
                case 'maintenance':
                    $sql = "
                        SELECT a.*, m.scheduled_date, m.type as maintenance_type,
                               p.name as project_name, c.name as category_name
                        FROM assets a
                        INNER JOIN maintenance m ON a.id = m.asset_id
                        LEFT JOIN projects p ON a.project_id = p.id
                        LEFT JOIN categories c ON a.category_id = c.id
                        WHERE m.status = 'scheduled' 
                          AND m.scheduled_date < CURDATE()
                        ORDER BY m.scheduled_date ASC
                    ";
                    break;
                    
                case 'withdrawal':
                    $sql = "
                        SELECT a.*, w.expected_return, w.receiver_name,
                               p.name as project_name, c.name as category_name,
                               DATEDIFF(CURDATE(), w.expected_return) as days_overdue
                        FROM assets a
                        INNER JOIN withdrawals w ON a.id = w.asset_id
                        LEFT JOIN projects p ON a.project_id = p.id
                        LEFT JOIN categories c ON a.category_id = c.id
                        WHERE w.status = 'released' 
                          AND w.expected_return IS NOT NULL
                          AND w.expected_return < CURDATE()
                        ORDER BY w.expected_return ASC
                    ";
                    break;
                    
                default:
                    return [];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get overdue assets error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get asset value report
     */
    public function getAssetValueReport($projectId = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT 
                    c.name as category_name,
                    COUNT(a.id) as asset_count,
                    SUM(a.acquisition_cost) as total_value,
                    AVG(a.acquisition_cost) as average_value,
                    MIN(a.acquisition_cost) as min_value,
                    MAX(a.acquisition_cost) as max_value
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                {$whereClause}
                GROUP BY a.category_id, c.name
                ORDER BY total_value DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get asset value report error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Bulk update asset status
     */
    public function bulkUpdateStatus($assetIds, $status, $notes = null) {
        try {
            if (empty($assetIds) || !is_array($assetIds)) {
                return ['success' => false, 'message' => 'No assets selected'];
            }
            
            $this->beginTransaction();
            
            $placeholders = str_repeat('?,', count($assetIds) - 1) . '?';
            $sql = "UPDATE assets SET status = ? WHERE id IN ({$placeholders})";
            
            $params = array_merge([$status], $assetIds);
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                // Log activity for each asset
                foreach ($assetIds as $assetId) {
                    $this->logAssetActivity($assetId, 'bulk_status_update', 
                        "Status updated to {$status}" . ($notes ? ": {$notes}" : ''));
                }
                
                $this->commit();
                return ['success' => true, 'message' => 'Assets updated successfully', 'count' => count($assetIds)];
            } else {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update assets'];
            }
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Bulk update status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update assets'];
        }
    }
    
    /**
     * Get asset depreciation report
     */
    public function getDepreciationReport($projectId = null) {
        try {
            $conditions = ["a.acquisition_cost IS NOT NULL", "a.acquisition_cost > 0"];
            $params = [];
            
            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT a.*, c.name as category_name, p.name as project_name,
                       DATEDIFF(CURDATE(), a.acquired_date) as days_owned,
                       ROUND(DATEDIFF(CURDATE(), a.acquired_date) / 365.25, 2) as years_owned,
                       CASE 
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1825 THEN a.acquisition_cost * 0.2  -- 5+ years: 80% depreciation
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1460 THEN a.acquisition_cost * 0.4  -- 4+ years: 60% depreciation
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1095 THEN a.acquisition_cost * 0.6  -- 3+ years: 40% depreciation
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 730 THEN a.acquisition_cost * 0.8   -- 2+ years: 20% depreciation
                           ELSE a.acquisition_cost
                       END as current_value,
                       CASE 
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1825 THEN a.acquisition_cost * 0.8
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1460 THEN a.acquisition_cost * 0.6
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1095 THEN a.acquisition_cost * 0.4
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 730 THEN a.acquisition_cost * 0.2
                           ELSE 0
                       END as depreciation_amount
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                {$whereClause}
                ORDER BY depreciation_amount DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get depreciation report error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Export assets to CSV format
     */
    public function exportAssets($filters = []) {
        try {
            $assets = $this->getAssetsWithFilters($filters, 1, 10000); // Get all assets
            
            $csvData = [];
            $csvData[] = [
                'Reference', 'Name', 'Category', 'Project', 'Status', 'Vendor', 'Maker',
                'Serial Number', 'Model', 'Acquired Date', 'Acquisition Cost', 'Current Value'
            ];
            
            foreach ($assets['data'] as $asset) {
                $csvData[] = [
                    $asset['ref'],
                    $asset['name'],
                    $asset['category_name'] ?? '',
                    $asset['project_name'] ?? '',
                    ucfirst($asset['status']),
                    $asset['vendor_name'] ?? '',
                    $asset['maker_name'] ?? '',
                    $asset['serial_number'] ?? '',
                    $asset['model'] ?? '',
                    $asset['acquired_date'],
                    $asset['acquisition_cost'] ?? 0,
                    $asset['acquisition_cost'] ?? 0 // Simplified - in real app, calculate depreciation
                ];
            }
            
            return $csvData;
            
        } catch (Exception $e) {
            error_log("Export assets error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get asset maintenance schedule
     */
    public function getMaintenanceSchedule($projectId = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT a.*, m.scheduled_date, m.type as maintenance_type, m.status as maintenance_status,
                       c.name as category_name, p.name as project_name,
                       u.full_name as assigned_to_name
                FROM assets a
                INNER JOIN maintenance m ON a.id = m.asset_id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN users u ON m.assigned_to = u.id
                {$whereClause}
                ORDER BY m.scheduled_date ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get maintenance schedule error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get asset statistics for dashboard API
     */
    public function getAssetStats() {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'available' THEN 1 END) as available,
                    COUNT(CASE WHEN status = 'in_use' THEN 1 END) as in_use,
                    COUNT(CASE WHEN status = 'borrowed' THEN 1 END) as borrowed,
                    COUNT(CASE WHEN status = 'under_maintenance' THEN 1 END) as under_maintenance,
                    COUNT(CASE WHEN status = 'retired' THEN 1 END) as retired,
                    COUNT(CASE WHEN status = 'disposed' THEN 1 END) as disposed,
                    COUNT(CASE WHEN status = 'in_transit' THEN 1 END) as in_transit,
                    SUM(CASE WHEN acquisition_cost IS NOT NULL THEN acquisition_cost ELSE 0 END) as total_value,
                    AVG(CASE WHEN acquisition_cost IS NOT NULL THEN acquisition_cost ELSE NULL END) as average_value
                FROM assets
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total' => (int)$result['total'],
                'available' => (int)$result['available'],
                'in_use' => (int)$result['in_use'],
                'borrowed' => (int)$result['borrowed'],
                'under_maintenance' => (int)$result['under_maintenance'],
                'retired' => (int)$result['retired'],
                'disposed' => (int)$result['disposed'],
                'total_value' => (float)$result['total_value'],
                'average_value' => (float)$result['average_value']
            ];
            
        } catch (Exception $e) {
            error_log("AssetModel::getAssetStats error: " . $e->getMessage());
            return [
                'total' => 0,
                'available' => 0,
                'in_use' => 0,
                'borrowed' => 0,
                'under_maintenance' => 0,
                'retired' => 0,
                'disposed' => 0,
                'total_value' => 0,
                'average_value' => 0
            ];
        }
    }
    
    /**
     * MVA Workflow Methods for Asset Management
     */
    
    /**
     * Get assets by workflow status
     */
    public function getAssetsByWorkflowStatus($workflowStatus, $projectId = null) {
        try {
            $conditions = ["a.workflow_status = ?"];
            $params = [$workflowStatus];
            
            // Project scoping
            $currentUser = Auth::getInstance()->getCurrentUser();
            
            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            } elseif (!in_array($currentUser['role_name'], ['System Admin', 'Finance Director', 'Asset Director'])) {
                if ($currentUser['current_project_id']) {
                    $conditions[] = "a.project_id = ?";
                    $params[] = $currentUser['current_project_id'];
                }
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT a.*, 
                       p.name as project_name, 
                       c.name as category_name,
                       m.name as maker_name,
                       v.name as vendor_name,
                       u1.full_name as made_by_name,
                       u2.full_name as verified_by_name,
                       u3.full_name as authorized_by_name,
                       po.po_number, pi.item_name as procurement_item_name, pi.brand as procurement_item_brand
                FROM assets a
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN makers m ON a.maker_id = m.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN users u1 ON a.made_by = u1.id
                LEFT JOIN users u2 ON a.verified_by = u2.id
                LEFT JOIN users u3 ON a.authorized_by = u3.id
                LEFT JOIN procurement_orders po ON a.procurement_order_id = po.id
                LEFT JOIN procurement_items pi ON a.procurement_item_id = pi.id
                {$whereClause}
                ORDER BY a.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get assets by workflow status error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Submit asset for verification
     */
    public function submitForVerification($assetId, $submittedBy) {
        try {
            $this->beginTransaction();
            
            $asset = $this->find($assetId);
            if (!$asset) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            if ($asset['workflow_status'] !== 'draft') {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset is not in draft status'];
            }
            
            $updateResult = $this->update($assetId, [
                'workflow_status' => 'pending_verification',
                'made_by' => $submittedBy,
                'submitted_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update asset status'];
            }
            
            // Log activity
            $this->logActivity('asset_submitted', 'Asset submitted for verification', 'assets', $assetId);
            
            $this->commit();
            return ['success' => true, 'message' => 'Asset submitted for verification successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Submit asset for verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit asset for verification'];
        }
    }
    
    /**
     * Verify asset (MVA workflow)
     */
    public function verifyAsset($assetId, $verifiedBy, $notes = null) {
        try {
            $this->beginTransaction();
            
            $asset = $this->find($assetId);
            if (!$asset) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            if ($asset['workflow_status'] !== 'pending_verification') {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset is not in pending verification status'];
            }
            
            $updateResult = $this->update($assetId, [
                'workflow_status' => 'pending_authorization',
                'verified_by' => $verifiedBy,
                'verification_date' => date('Y-m-d H:i:s'),
                'verification_notes' => $notes
            ]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update asset status'];
            }
            
            // Log activity
            $this->logActivity('asset_verified', 'Asset verified by Asset Director', 'assets', $assetId);
            
            $this->commit();
            return ['success' => true, 'message' => 'Asset verified successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Verify asset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to verify asset'];
        }
    }
    
    /**
     * Authorize asset (MVA workflow)
     */
    public function authorizeAsset($assetId, $authorizedBy, $notes = null) {
        try {
            $this->beginTransaction();
            
            $asset = $this->find($assetId);
            if (!$asset) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            if ($asset['workflow_status'] !== 'pending_authorization') {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset is not in pending authorization status'];
            }
            
            $updateResult = $this->update($assetId, [
                'workflow_status' => 'approved',
                'status' => 'available',
                'authorized_by' => $authorizedBy,
                'authorization_date' => date('Y-m-d H:i:s'),
                'authorization_notes' => $notes
            ]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update asset status'];
            }
            
            // Log activity
            $this->logActivity('asset_authorized', 'Asset authorized by Finance Director', 'assets', $assetId);
            
            $this->commit();
            return ['success' => true, 'message' => 'Asset authorized successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Authorize asset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to authorize asset'];
        }
    }
    
    /**
     * Reject asset (MVA workflow)
     */
    public function rejectAsset($assetId, $rejectedBy, $rejectionReason) {
        try {
            $this->beginTransaction();
            
            $asset = $this->find($assetId);
            if (!$asset) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            if (!in_array($asset['workflow_status'], ['pending_verification', 'pending_authorization'])) {
                $this->rollback();
                return ['success' => false, 'message' => 'Asset cannot be rejected in current status'];
            }
            
            $updateResult = $this->update($assetId, [
                'workflow_status' => 'rejected',
                'rejected_by' => $rejectedBy,
                'rejection_date' => date('Y-m-d H:i:s'),
                'rejection_reason' => $rejectionReason
            ]);
            
            if (!$updateResult) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update asset status'];
            }
            
            // Log activity
            $this->logActivity('asset_rejected', 'Asset rejected: ' . $rejectionReason, 'assets', $assetId);
            
            $this->commit();
            return ['success' => true, 'message' => 'Asset rejected successfully'];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Reject asset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reject asset'];
        }
    }
    
    /**
     * Get MVA workflow statistics
     */
    public function getWorkflowStatistics($projectId = null) {
        try {
            // Return empty statistics since workflow_status column doesn't exist in database
            // This prevents SQL errors while maintaining the expected return structure
            return [
                'total_assets' => 0,
                'draft' => 0,
                'pending_verification' => 0,
                'pending_authorization' => 0,
                'approved' => 0,
                'rejected' => 0,
                'avg_approval_time_hours' => 0
            ];
            
        } catch (Exception $e) {
            error_log("Get workflow statistics error: " . $e->getMessage());
            return [
                'total_assets' => 0,
                'draft' => 0,
                'pending_verification' => 0,
                'pending_authorization' => 0,
                'approved' => 0,
                'rejected' => 0,
                'avg_approval_time_hours' => 0
            ];
        }
    }
    
    /**
     * Get role-specific asset statistics
     */
    public function getRoleSpecificStatistics($userRole, $projectId = null) {
        try {
            switch ($userRole) {
                case 'Project Manager':
                    return $this->getProjectManagerStats($projectId);
                case 'Site Inventory Clerk':
                    return $this->getSiteInventoryClerkStats($projectId);
                case 'Warehouseman':
                    return $this->getWarehousemanStats($projectId);
                case 'System Admin':
                    return $this->getSystemAdminStats();
                case 'Finance Director':
                    return $this->getFinanceDirectorStats();
                case 'Asset Director':
                    return $this->getAssetDirectorStats();
                default:
                    return $this->getAssetStatistics($projectId);
            }
        } catch (Exception $e) {
            error_log("Get role-specific statistics error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get Project Manager specific statistics
     */
    private function getProjectManagerStats($projectId) {
        $conditions = [];
        $params = [];
        
        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        $sql = "
            SELECT 
                COUNT(*) as total_project_assets,
                SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available_assets,
                SUM(CASE WHEN a.status = 'in_use' THEN 1 ELSE 0 END) as assets_in_use,
                ROUND(
                    (SUM(CASE WHEN a.status = 'in_use' THEN 1 ELSE 0 END) * 100.0 / 
                     NULLIF(COUNT(*), 0)), 1
                ) as utilization_rate,
                SUM(CASE WHEN c.is_consumable = 1 AND a.available_quantity <= (a.quantity * 0.2) 
                    AND a.available_quantity > 0 THEN 1 ELSE 0 END) as low_stock_alerts,
                COUNT(CASE WHEN a.status = 'under_maintenance' THEN 1 END) as maintenance_pending
            FROM assets a
            LEFT JOIN categories c ON a.category_id = c.id
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get Site Inventory Clerk specific statistics
     */
    private function getSiteInventoryClerkStats($projectId) {
        $conditions = [];
        $params = [];
        
        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        $sql = "
            SELECT 
                COUNT(*) as total_inventory_items,
                SUM(CASE WHEN c.is_consumable = 1 THEN a.available_quantity ELSE 0 END) as total_consumable_units,
                SUM(CASE WHEN c.is_consumable = 1 AND a.available_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_items,
                SUM(CASE WHEN c.is_consumable = 1 AND a.available_quantity <= (a.quantity * 0.2) 
                    AND a.available_quantity > 0 THEN 1 ELSE 0 END) as low_stock_items,
                SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available_for_use,
                COUNT(CASE WHEN DATE(a.updated_at) = CURDATE() THEN 1 END) as today_activities
            FROM assets a
            LEFT JOIN categories c ON a.category_id = c.id
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get Warehouseman specific statistics
     * Warehouseman sees assets for their assigned project
     * Handles both Capital Assets (Depreciable Equipment) and Consumable Inventory
     */
    private function getWarehousemanStats($projectId) {
        $conditions = [];
        $params = [];

        // Filter by Warehouseman's assigned project
        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "
            SELECT
                -- Total counts
                COUNT(*) as total_items,
                COUNT(CASE WHEN c.asset_type = 'capital' THEN 1 END) as total_capital_assets,
                COUNT(CASE WHEN c.asset_type = 'inventory' OR c.is_consumable = 1 THEN 1 END) as total_inventory_items,

                -- Capital Assets (Depreciable Equipment) - tracked by status
                SUM(CASE WHEN c.asset_type = 'capital' AND a.status = 'available' THEN 1 ELSE 0 END) as capital_available,
                SUM(CASE WHEN c.asset_type = 'capital' AND a.status IN ('borrowed', 'in_use') THEN 1 ELSE 0 END) as capital_in_use,
                SUM(CASE WHEN c.asset_type = 'capital' AND a.status = 'under_maintenance' THEN 1 ELSE 0 END) as capital_maintenance,

                -- Consumable Inventory - tracked by quantity
                SUM(CASE WHEN (c.asset_type = 'inventory' OR c.is_consumable = 1) AND a.available_quantity > 0 THEN a.available_quantity ELSE 0 END) as consumable_units_available,
                SUM(CASE WHEN (c.asset_type = 'inventory' OR c.is_consumable = 1) THEN a.quantity ELSE 0 END) as consumable_units_total,
                COUNT(CASE WHEN (c.asset_type = 'inventory' OR c.is_consumable = 1) AND a.available_quantity = 0 THEN 1 END) as consumable_out_of_stock,
                COUNT(CASE WHEN (c.asset_type = 'inventory' OR c.is_consumable = 1) AND a.available_quantity > 0 THEN 1 END) as consumable_in_stock,

                -- Combined metrics
                COUNT(CASE WHEN DATE(a.created_at) = CURDATE() THEN 1 END) as today_receipts,
                SUM(CASE WHEN a.status = 'in_transit' THEN 1 ELSE 0 END) as in_transit
            FROM assets a
            LEFT JOIN categories c ON a.category_id = c.id
            {$whereClause}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get System Admin specific statistics
     */
    private function getSystemAdminStats() {
        $sql = "
            SELECT 
                COUNT(DISTINCT a.project_id) as active_projects,
                COUNT(*) as total_system_assets,
                SUM(a.acquisition_cost) as total_asset_value,
                COUNT(CASE WHEN DATE(a.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS) THEN 1 END) as assets_added_week,
                ROUND(AVG(a.acquisition_cost), 2) as avg_asset_value,
                COUNT(CASE WHEN a.status = 'disposed' THEN 1 END) as disposed_assets
            FROM assets a
            WHERE a.acquisition_cost IS NOT NULL
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get Finance Director specific statistics
     */
    private function getFinanceDirectorStats() {
        $sql = "
            SELECT 
                SUM(a.acquisition_cost) as total_asset_investment,
                COUNT(CASE WHEN a.acquisition_cost > 10000 THEN 1 END) as high_value_assets,
                ROUND(AVG(a.acquisition_cost), 2) as avg_acquisition_cost,
                SUM(CASE WHEN a.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAYS) 
                    THEN a.acquisition_cost ELSE 0 END) as monthly_acquisitions,
                COUNT(CASE WHEN a.is_client_supplied = 1 THEN 1 END) as client_supplied_assets,
                COUNT(DISTINCT a.project_id) as projects_with_assets
            FROM assets a
            WHERE a.acquisition_cost IS NOT NULL
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get Asset Director specific statistics
     */
    private function getAssetDirectorStats() {
        $sql = "
            SELECT 
                COUNT(*) as total_managed_assets,
                ROUND(
                    (SUM(CASE WHEN a.status = 'in_use' THEN 1 ELSE 0 END) * 100.0 / 
                     NULLIF(COUNT(*), 0)), 1
                ) as overall_utilization,
                COUNT(CASE WHEN a.status = 'under_maintenance' THEN 1 END) as maintenance_required,
                COUNT(CASE WHEN a.status = 'retired' THEN 1 END) as retired_assets,
                SUM(CASE WHEN c.is_consumable = 1 AND a.available_quantity <= (a.quantity * 0.2) 
                    AND a.available_quantity > 0 THEN 1 ELSE 0 END) as inventory_alerts,
                COUNT(DISTINCT a.project_id) as projects_managed
            FROM assets a
            LEFT JOIN categories c ON a.category_id = c.id
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Create legacy asset with simplified workflow
     */
    public function createLegacyAsset($data) {
        try {
            // Validate required fields for legacy assets
            $validation = $this->validate($data, [
                'name' => 'required|max:200',
                'category_id' => 'required|integer',
                'acquired_date' => 'required|date'
            ]);
            
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            $this->beginTransaction();
            
            // Get current user info
            $currentUser = Auth::getInstance()->getCurrentUser();
            $userModel = new UserModel();
            
            // Auto-set project from user's current assignment
            $projectId = $currentUser['current_project_id'];
            
            // For System Admin or users without project assignment, use default project or allow selection
            if (!$projectId) {
                if ($currentUser['role_name'] === 'System Admin') {
                    // System Admin can create assets - use default project (Head Office)
                    $projectId = 1; // Default to Head Office project
                } else {
                    $this->rollback();
                    return ['success' => false, 'message' => 'No project assigned to user. Please contact your administrator.'];
                }
            }
            
            // Check project access
            if (!$userModel->hasProjectAccess($currentUser['id'], $projectId)) {
                $this->rollback();
                return ['success' => false, 'message' => 'Access denied: You do not have access to this project'];
            }
            
            // Generate asset reference if not provided  
            if (empty($data['ref'])) {
                $data['ref'] = generateAssetReference(
                    $data['category_id'] ?? null,
                    $data['primary_discipline'] ?? null,
                    true  // This is a legacy asset
                );
            }
            
            // Get category and validate business rules
            $categoryModel = new CategoryModel();
            $category = $categoryModel->find($data['category_id']);
            
            if (!$category) {
                $this->rollback();
                return ['success' => false, 'message' => 'Invalid category selected'];
            }
            
            // Validate that this category can generate assets for legacy creation
            if (!$category['generates_assets']) {
                $this->rollback();
                return [
                    'success' => false, 
                    'message' => 'Cannot create legacy assets for expense-only categories. This category "' . $category['name'] . '" is configured for direct expense allocation only.'
                ];
            }
            
            // Check capitalization threshold if unit cost is provided
            $unitCost = !empty($data['unit_cost']) ? (float)$data['unit_cost'] : 0;
            $threshold = (float)$category['capitalization_threshold'];
            
            // For legacy assets, we provide a warning but don't block creation since they already exist
            $thresholdWarning = null;
            if ($threshold > 0 && $unitCost > 0 && $unitCost < $threshold && $category['auto_expense_below_threshold']) {
                $thresholdWarning = "Note: Unit cost ($unitCost) is below the capitalization threshold ($threshold) for this category.";
            }
            
            $isConsumable = $category && $category['is_consumable'] == 1;
            $isCapitalAsset = $category['asset_type'] === 'capital';
            $isInventoryAsset = $category['asset_type'] === 'inventory';
            
            // Get input quantity
            $inputQuantity = !empty($data['quantity']) ? (int)$data['quantity'] : 1;
            
            // Determine asset source
            $assetSource = 'legacy';
            if (!empty($data['is_client_supplied'])) {
                $assetSource = 'client_supplied';
            }
            
            $createdAssets = [];
            
            if ($isConsumable) {
                // For consumable items: Create 1 asset with total quantity
                $assetData = [
                    'ref' => empty($data['ref']) ? generateAssetReference($data['category_id'] ?? null, $data['primary_discipline'] ?? null, true) : $data['ref'],
                    'category_id' => (int)$data['category_id'],
                    'name' => Validator::sanitize($data['name']),
                    'description' => Validator::sanitize($data['description'] ?? ''),
                    'project_id' => $projectId,
                    'acquired_date' => $data['acquired_date'],
                    'status' => 'available',
                    'is_client_supplied' => isset($data['is_client_supplied']) ? 1 : 0,
                    'quantity' => $inputQuantity,
                    'available_quantity' => $inputQuantity,
                    'unit' => Validator::sanitize($data['unit'] ?? 'pcs'),
                    'sub_location' => Validator::sanitize($data['sub_location'] ?? ''),
                    'asset_source' => $assetSource,
                    'workflow_status' => 'pending_verification',
                    'made_by' => $currentUser['id'],
                    'serial_number' => Validator::sanitize($data['serial_number'] ?? ''),
                    'model' => Validator::sanitize($data['model'] ?? ''),
                    'specifications' => Validator::sanitize($data['specifications'] ?? ''),
                    'warranty_expiry' => !empty($data['warranty_expiry']) ? $data['warranty_expiry'] : null,
                    'location' => Validator::sanitize($data['location'] ?? ''),
                    'condition_notes' => Validator::sanitize($data['condition_notes'] ?? ''),
                    'acquisition_cost' => !empty($data['acquisition_cost']) ? (float)$data['acquisition_cost'] : null,
                    'unit_cost' => !empty($data['unit_cost']) ? (float)$data['unit_cost'] : null,
                    'equipment_type_id' => !empty($data['equipment_type_id']) ? (int)$data['equipment_type_id'] : null,
                    'subtype_id' => !empty($data['subtype_id']) ? (int)$data['subtype_id'] : null,
                    'generated_name' => !empty($data['generated_name']) ? Validator::sanitize($data['generated_name']) : null,
                    'name_components' => !empty($data['name_components']) ? $data['name_components'] : null,
                    'standardized_name' => !empty($data['standardized_name']) ? Validator::sanitize($data['standardized_name']) : null,
                    'brand_id' => !empty($data['brand_id']) ? (int)$data['brand_id'] : null,
                    'maker_id' => !empty($data['maker_id']) ? (int)$data['maker_id'] : null,
                    'vendor_id' => !empty($data['vendor_id']) ? (int)$data['vendor_id'] : null,
                    'client_id' => !empty($data['client_id']) ? (int)$data['client_id'] : null
                ];
                
                $asset = $this->create($assetData);
                
                if (!$asset) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Failed to create legacy asset'];
                }
                
                // Log activity
                $this->logAssetActivity($asset['id'], 'legacy_asset_created', 'Legacy asset created by ' . $currentUser['username'], null, $assetData);
                $createdAssets[] = $asset;
                
            } else {
                // For non-consumable items: Create individual assets (quantity=1 each)
                for ($i = 0; $i < $inputQuantity; $i++) {
                    $assetData = [
                        'ref' => generateAssetReference($data['category_id'] ?? null, $data['primary_discipline'] ?? null, true),
                        'category_id' => (int)$data['category_id'],
                        'name' => Validator::sanitize($data['name']),
                        'description' => Validator::sanitize($data['description'] ?? ''),
                        'project_id' => $projectId,
                        'acquired_date' => $data['acquired_date'],
                        'status' => 'available',
                        'is_client_supplied' => isset($data['is_client_supplied']) ? 1 : 0,
                        'quantity' => 1,
                        'available_quantity' => 1,
                        'unit' => Validator::sanitize($data['unit'] ?? 'pcs'),
                        'sub_location' => Validator::sanitize($data['sub_location'] ?? ''),
                        'asset_source' => $assetSource,
                        'workflow_status' => 'pending_verification',
                        'made_by' => $currentUser['id'],
                        'serial_number' => Validator::sanitize($data['serial_number'] ?? ''),
                        'model' => Validator::sanitize($data['model'] ?? ''),
                        'specifications' => Validator::sanitize($data['specifications'] ?? ''),
                        'warranty_expiry' => !empty($data['warranty_expiry']) ? $data['warranty_expiry'] : null,
                        'location' => Validator::sanitize($data['location'] ?? ''),
                        'condition_notes' => Validator::sanitize($data['condition_notes'] ?? ''),
                        'acquisition_cost' => !empty($data['acquisition_cost']) ? (float)$data['acquisition_cost'] : null,
                        'unit_cost' => !empty($data['unit_cost']) ? (float)$data['unit_cost'] : null,
                        'equipment_type_id' => !empty($data['equipment_type_id']) ? (int)$data['equipment_type_id'] : null,
                        'subtype_id' => !empty($data['subtype_id']) ? (int)$data['subtype_id'] : null,
                        'generated_name' => !empty($data['generated_name']) ? Validator::sanitize($data['generated_name']) : null,
                        'name_components' => !empty($data['name_components']) ? $data['name_components'] : null,
                        'standardized_name' => !empty($data['standardized_name']) ? Validator::sanitize($data['standardized_name']) : null,
                        'brand_id' => !empty($data['brand_id']) ? (int)$data['brand_id'] : null,
                        'maker_id' => !empty($data['maker_id']) ? (int)$data['maker_id'] : null,
                        'vendor_id' => !empty($data['vendor_id']) ? (int)$data['vendor_id'] : null,
                        'client_id' => !empty($data['client_id']) ? (int)$data['client_id'] : null
                    ];
                    
                    $asset = $this->create($assetData);
                    
                    if (!$asset) {
                        $this->rollback();
                        return ['success' => false, 'message' => 'Failed to create legacy asset ' . ($i + 1) . ' of ' . $inputQuantity];
                    }
                    
                    // Log activity
                    $this->logAssetActivity($asset['id'], 'legacy_asset_created', 'Legacy asset created by ' . $currentUser['username'] . ' (' . ($i + 1) . ' of ' . $inputQuantity . ')', null, $assetData);
                    $createdAssets[] = $asset;
                }
            }
            
            // Handle disciplines for all created assets
            $disciplineCodes = [];
            
            // Handle primary discipline
            if (!empty($data['primary_discipline'])) {
                $disciplineId = (int)$data['primary_discipline'];
                $stmt = $this->db->prepare("SELECT iso_code FROM asset_disciplines WHERE id = ? AND is_active = 1");
                $stmt->execute([$disciplineId]);
                $isoCode = $stmt->fetchColumn();
                
                if ($isoCode) {
                    $disciplineCodes[] = $isoCode;
                }
            }
            
            // Handle additional disciplines
            if (!empty($data['disciplines']) && is_array($data['disciplines'])) {
                foreach ($data['disciplines'] as $disciplineId) {
                    $disciplineId = (int)$disciplineId;
                    if ($disciplineId > 0) {
                        // Get discipline ISO code for storing in discipline_tags
                        $stmt = $this->db->prepare("SELECT iso_code FROM asset_disciplines WHERE id = ? AND is_active = 1");
                        $stmt->execute([$disciplineId]);
                        $isoCode = $stmt->fetchColumn();
                        
                        if ($isoCode && !in_array($isoCode, $disciplineCodes)) {
                            $disciplineCodes[] = $isoCode;
                        }
                    }
                }
            }
            
            // Update all created assets with discipline tags (ISO codes)
            if (!empty($disciplineCodes)) {
                $disciplineTags = implode(',', $disciplineCodes);
                foreach ($createdAssets as $asset) {
                    $this->update($asset['id'], ['discipline_tags' => $disciplineTags]);
                }
            }
            
            $this->commit();
            
            // Return appropriate response based on number of assets created
            $response = ['success' => true];
            
            if (count($createdAssets) === 1) {
                $response['asset'] = $createdAssets[0];
            } else {
                $response['assets'] = $createdAssets;
                $response['count'] = count($createdAssets);
                $response['type'] = $isConsumable ? 'consumable' : 'non_consumable';
            }
            
            // Add threshold warning if applicable
            if ($thresholdWarning) {
                $response['warning'] = $thresholdWarning;
            }
            
            // Add category business information
            $response['category_info'] = [
                'asset_type' => $category['asset_type'],
                'generates_assets' => $category['generates_assets'],
                'is_consumable' => $isConsumable,
                'workflow_status' => 'pending_verification'
            ];
            
            return $response;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Legacy asset creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create legacy asset'];
        }
    }

    /**
     * Verify legacy asset (Site Inventory Clerk action)
     */
    public function verifyLegacyAsset($assetId, $notes = '') {
        try {
            $asset = $this->find($assetId);
            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            if ($asset['workflow_status'] !== 'pending_verification') {
                return ['success' => false, 'message' => 'Asset is not pending verification'];
            }
            
            $currentUser = Auth::getInstance()->getCurrentUser();
            
            $updateData = [
                'workflow_status' => 'pending_authorization',
                'verified_by' => $currentUser['id'],
                'verification_date' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->update($assetId, $updateData);
            
            if ($result) {
                // Log activity
                $this->logAssetActivity($assetId, 'asset_verified', 'Asset verified by ' . $currentUser['username'] . '. Notes: ' . $notes);
                return ['success' => true, 'asset' => $this->find($assetId)];
            }
            
            return ['success' => false, 'message' => 'Failed to verify asset'];
            
        } catch (Exception $e) {
            error_log("Asset verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to verify asset'];
        }
    }

    /**
     * Authorize legacy asset (Project Manager action)
     */
    public function authorizeLegacyAsset($assetId, $notes = '') {
        try {
            $asset = $this->find($assetId);
            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found'];
            }
            
            if ($asset['workflow_status'] !== 'pending_authorization') {
                return ['success' => false, 'message' => 'Asset is not pending authorization'];
            }
            
            $currentUser = Auth::getInstance()->getCurrentUser();
            
            $updateData = [
                'workflow_status' => 'approved',
                'authorized_by' => $currentUser['id'],
                'authorization_date' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->update($assetId, $updateData);
            
            if ($result) {
                // Log activity
                $this->logAssetActivity($assetId, 'asset_authorized', 'Asset authorized by ' . $currentUser['username'] . '. Notes: ' . $notes);
                return ['success' => true, 'asset' => $this->find($assetId)];
            }
            
            return ['success' => false, 'message' => 'Failed to authorize asset'];
            
        } catch (Exception $e) {
            error_log("Asset authorization error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to authorize asset'];
        }
    }

    /**
     * Get assets pending verification (for Site Inventory Clerk)
     */
    public function getAssetsPendingVerification($projectId = null) {
        try {
            $conditions = ["workflow_status = 'pending_verification'"];
            $params = [];
            
            // Project scoping
            $currentUser = Auth::getInstance()->getCurrentUser();
            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            } elseif (!in_array($currentUser['role_name'], ['System Admin', 'Finance Director', 'Asset Director'])) {
                if ($currentUser['current_project_id']) {
                    $conditions[] = "a.project_id = ?";
                    $params[] = $currentUser['current_project_id'];
                }
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT a.*, c.name as category_name, p.name as project_name,
                       u.username as made_by_username,
                       m.name as maker_name, v.name as vendor_name,
                       po.po_number, pi.item_name as procurement_item_name, pi.brand as procurement_item_brand
                FROM {$this->table} a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN users u ON a.made_by = u.id
                LEFT JOIN makers m ON a.maker_id = m.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN procurement_orders po ON a.procurement_order_id = po.id
                LEFT JOIN procurement_items pi ON a.procurement_item_id = pi.id
                {$whereClause}
                ORDER BY a.created_at ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get pending verification assets error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get assets pending authorization (for Project Manager)
     */
    public function getAssetsPendingAuthorization($projectId = null) {
        try {
            $conditions = ["workflow_status = 'pending_authorization'"];
            $params = [];
            
            // Project scoping
            $currentUser = Auth::getInstance()->getCurrentUser();
            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            } elseif (!in_array($currentUser['role_name'], ['System Admin', 'Finance Director', 'Asset Director'])) {
                if ($currentUser['current_project_id']) {
                    $conditions[] = "a.project_id = ?";
                    $params[] = $currentUser['current_project_id'];
                }
            }
            
            $whereClause = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT a.*, c.name as category_name, p.name as project_name,
                       u1.username as made_by_username, u2.username as verified_by_username,
                       m.name as maker_name, v.name as vendor_name,
                       po.po_number, pi.item_name as procurement_item_name, pi.brand as procurement_item_brand
                FROM {$this->table} a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN users u1 ON a.made_by = u1.id
                LEFT JOIN users u2 ON a.verified_by = u2.id
                LEFT JOIN makers m ON a.maker_id = m.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN procurement_orders po ON a.procurement_order_id = po.id
                LEFT JOIN procurement_items pi ON a.procurement_item_id = pi.id
                {$whereClause}
                ORDER BY a.verification_date ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get pending authorization assets error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get legacy asset workflow statistics
     */
    public function getLegacyWorkflowStats($projectId = null) {
        try {
            $conditions = [];
            $params = [];
            
            // Project scoping
            $currentUser = Auth::getInstance()->getCurrentUser();
            if ($projectId) {
                $conditions[] = "project_id = ?";
                $params[] = $projectId;
            } elseif (!in_array($currentUser['role_name'], ['System Admin', 'Finance Director', 'Asset Director'])) {
                if ($currentUser['current_project_id']) {
                    $conditions[] = "project_id = ?";
                    $params[] = $currentUser['current_project_id'];
                }
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT 
                    COUNT(CASE WHEN workflow_status = 'pending_verification' THEN 1 END) as pending_verification,
                    COUNT(CASE WHEN workflow_status = 'pending_authorization' THEN 1 END) as pending_authorization,
                    COUNT(CASE WHEN workflow_status = 'approved' AND asset_source IN ('legacy', 'client_supplied') THEN 1 END) as approved_legacy,
                    COUNT(CASE WHEN asset_source = 'legacy' THEN 1 END) as total_legacy,
                    COUNT(CASE WHEN asset_source = 'client_supplied' THEN 1 END) as client_supplied
                FROM {$this->table} 
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            
        } catch (Exception $e) {
            error_log("Get legacy workflow stats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Batch verify assets (for Site Inventory Clerk efficiency)
     */
    public function batchVerifyAssets($assetIds, $notes = '') {
        try {
            $currentUser = Auth::getInstance()->getCurrentUser();
            $successCount = 0;
            $errors = [];
            
            foreach ($assetIds as $assetId) {
                $result = $this->verifyLegacyAsset($assetId, $notes);
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errors[] = "Asset #{$assetId}: " . $result['message'];
                }
            }
            
            return [
                'success' => $successCount > 0,
                'verified_count' => $successCount,
                'total_count' => count($assetIds),
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            error_log("Batch verify assets error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to batch verify assets'];
        }
    }

    /**
     * Batch authorize assets (for Project Manager efficiency)
     */
    public function batchAuthorizeAssets($assetIds, $notes = '') {
        try {
            $currentUser = Auth::getInstance()->getCurrentUser();
            $successCount = 0;
            $errors = [];
            
            foreach ($assetIds as $assetId) {
                $result = $this->authorizeLegacyAsset($assetId, $notes);
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errors[] = "Asset #{$assetId}: " . $result['message'];
                }
            }
            
            return [
                'success' => $successCount > 0,
                'authorized_count' => $successCount,
                'total_count' => count($assetIds),
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            error_log("Batch authorize assets error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to batch authorize assets'];
        }
    }

    /**
     * Get assets that can be maintained (non-consumables, appropriate status)
     */
    public function getMaintenableAssets() {
        try {
            $sql = "
                SELECT a.*, c.name as category_name, c.is_consumable,
                       p.name as project_name
                FROM {$this->table} a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                WHERE a.status IN ('available', 'in_use')
                  AND c.is_consumable = 0
                  AND a.quantity = 1
                ORDER BY a.name ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get maintainable assets error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if an asset can be maintained
     */
    public function canBeMaintained($assetId) {
        try {
            $sql = "
                SELECT a.*, c.is_consumable, c.name as category_name
                FROM {$this->table} a
                LEFT JOIN categories c ON a.category_id = c.id
                WHERE a.id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$asset) {
                return ['canMaintain' => false, 'reason' => 'Asset not found'];
            }
            
            // Check if asset is consumable
            if ($asset['is_consumable']) {
                return [
                    'canMaintain' => false, 
                    'reason' => 'Consumable items cannot be maintained',
                    'asset' => $asset
                ];
            }
            
            // Check if asset has quantity > 1 (bulk items)
            if ($asset['quantity'] > 1) {
                return [
                    'canMaintain' => false, 
                    'reason' => 'Bulk/quantity items cannot have individual maintenance',
                    'asset' => $asset
                ];
            }
            
            // Check asset status
            if (!in_array($asset['status'], ['available', 'in_use', 'under_maintenance'])) {
                return [
                    'canMaintain' => false, 
                    'reason' => 'Asset status "' . $asset['status'] . '" is not suitable for maintenance',
                    'asset' => $asset
                ];
            }
            
            // Check if asset is already under maintenance
            if ($asset['status'] === 'under_maintenance') {
                // Check if there's active maintenance
                $maintenanceModel = new MaintenanceModel();
                $activeMaintenance = $maintenanceModel->findFirst([
                    'asset_id' => $assetId,
                    'status' => 'in_progress'
                ]);
                
                if ($activeMaintenance) {
                    return [
                        'canMaintain' => false, 
                        'reason' => 'Asset is currently under maintenance (ID: ' . $activeMaintenance['id'] . ')',
                        'asset' => $asset
                    ];
                }
            }
            
            return ['canMaintain' => true, 'asset' => $asset];
            
        } catch (Exception $e) {
            error_log("Can be maintained check error: " . $e->getMessage());
            return ['canMaintain' => false, 'reason' => 'Error checking asset eligibility'];
        }
    }
    
    /**
     * Log activity for audit trail
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

    // ================================================================================
    // BUSINESS CATEGORY INTEGRATION METHODS
    // ================================================================================

    /**
     * Create asset from procurement item with business rule validation
     */
    public function createAssetFromProcurement($procurementItem, $assetData = []) {
        try {
            // Get category and validate business rules
            $categoryModel = new CategoryModel();
            $assetEligibility = $categoryModel->shouldGenerateAsset(
                $procurementItem['category_id'], 
                $procurementItem['unit_price']
            );
            
            if (!$assetEligibility['should_generate']) {
                return [
                    'success' => false,
                    'message' => 'Asset generation not appropriate: ' . $assetEligibility['reason'],
                    'eligibility' => $assetEligibility
                ];
            }
            
            // Merge procurement item data with asset data
            $assetCreationData = array_merge([
                'name' => $procurementItem['item_name'],
                'description' => $procurementItem['description'] ?? $procurementItem['specifications'] ?? '',
                'category_id' => $procurementItem['category_id'],
                'project_id' => $procurementItem['project_id'],
                'vendor_id' => $procurementItem['vendor_id'] ?? null,
                'acquired_date' => date('Y-m-d'),
                'procurement_order_id' => $procurementItem['procurement_order_id'],
                'procurement_item_id' => $procurementItem['id'],
                'unit_cost' => $procurementItem['unit_price'],
                'acquisition_cost' => $procurementItem['unit_price'],
                'model' => $procurementItem['model'] ?? '',
                'quantity' => $assetEligibility['is_consumable'] ? $procurementItem['quantity_received'] : 1,
                'serial_number' => $assetData['serial_number'] ?? ''
            ], $assetData);
            
            return $this->createAsset($assetCreationData);
            
        } catch (Exception $e) {
            error_log("Create asset from procurement error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create asset from procurement item'];
        }
    }

    /**
     * Get assets by business classification
     */
    public function getAssetsByBusinessType($assetType = null, $projectId = null, $filters = []) {
        try {
            $conditions = [];
            $params = [];
            
            // Project access validation
            $currentUser = Auth::getInstance()->getCurrentUser();
            $userModel = new UserModel();
            
            if ($projectId) {
                if (!$userModel->hasProjectAccess($currentUser['id'], $projectId)) {
                    return ['success' => false, 'message' => 'Access denied to project'];
                }
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            } else {
                // Get user's accessible project IDs
                $accessibleProjects = $userModel->getUserAccessibleProjects($currentUser['id']);
                if (!empty($accessibleProjects)) {
                    $placeholders = str_repeat('?,', count($accessibleProjects) - 1) . '?';
                    $conditions[] = "a.project_id IN ({$placeholders})";
                    $params = array_merge($params, $accessibleProjects);
                }
            }
            
            // Asset type filtering
            if ($assetType) {
                $conditions[] = "c.asset_type = ?";
                $params[] = $assetType;
            }
            
            // Additional filters
            if (!empty($filters['status'])) {
                $conditions[] = "a.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['category_id'])) {
                $conditions[] = "a.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT a.*, 
                       c.name as category_name,
                       c.asset_type,
                       c.generates_assets,
                       c.depreciation_applicable,
                       c.business_description,
                       p.name as project_name,
                       p.code as project_code,
                       v.name as vendor_name,
                       CASE c.asset_type
                           WHEN 'capital' THEN 'Capital Asset'
                           WHEN 'inventory' THEN 'Inventory/Materials'
                           WHEN 'expense' THEN 'Direct Expense'
                           ELSE 'Undefined'
                       END as business_classification
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                {$whereClause}
                ORDER BY c.asset_type, c.name, a.name
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get assets by business type error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate asset creation against business rules
     */
    public function validateAssetBusinessRules($data) {
        $errors = [];
        
        try {
            if (empty($data['category_id'])) {
                $errors[] = 'Category is required';
                return ['valid' => false, 'errors' => $errors];
            }
            
            $categoryModel = new CategoryModel();
            $category = $categoryModel->find($data['category_id']);
            
            if (!$category) {
                $errors[] = 'Invalid category selected';
                return ['valid' => false, 'errors' => $errors];
            }
            
            // Check if category allows asset generation
            if (!$category['generates_assets']) {
                $errors[] = 'Selected category is configured for direct expense, not asset generation';
            }
            
            // Check capitalization threshold
            $unitCost = !empty($data['unit_cost']) ? (float)$data['unit_cost'] : 
                       (!empty($data['acquisition_cost']) ? (float)$data['acquisition_cost'] : 0);
            $threshold = (float)$category['capitalization_threshold'];
            
            if ($threshold > 0 && $unitCost > 0 && $unitCost < $threshold && $category['auto_expense_below_threshold']) {
                $errors[] = "Unit cost ($unitCost) is below capitalization threshold ($threshold). Consider expensing directly.";
            }
            
            // Validate quantity for inventory vs capital assets
            if ($category['asset_type'] === 'inventory' || $category['is_consumable']) {
                if (empty($data['quantity']) || $data['quantity'] < 1) {
                    $errors[] = 'Quantity is required for inventory/consumable assets';
                }
            }
            
        } catch (Exception $e) {
            $errors[] = 'Error validating business rules: ' . $e->getMessage();
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'category_info' => $category ?? null
        ];
    }

    /**
     * Get count of available non-consumable equipment
     * Moved from BorrowedToolController (Phase 2.1)
     *
     * @param int|null $projectFilter Optional project ID filter
     * @return int Count of available equipment
     */
    public function getAvailableEquipmentCount($projectFilter = null) {
        try {
            $sql = "SELECT COUNT(*) FROM assets a
                JOIN categories c ON a.category_id = c.id
                WHERE c.is_consumable = 0
                  AND a.status = 'available'
                  AND a.workflow_status = 'approved'";

            $params = [];
            if ($projectFilter) {
                $sql .= " AND a.project_id = ?";
                $params[] = $projectFilter;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();

        } catch (Exception $e) {
            error_log("Get available equipment count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get available assets for borrowing
     * Excludes assets that are already borrowed, withdrawn, or transferred
     * Uses optimized LEFT JOIN aggregation to eliminate N+1 queries
     * Moved from BorrowedToolController (Phase 2.1)
     *
     * @param int|null $projectId Optional project ID filter
     * @return array Available assets with details
     */
    public function getAvailableForBorrowing($projectId = null) {
        try {
            require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';

            // Build query with enhanced filtering using LEFT JOIN to avoid N+1 queries
            // This single query replaces the previous loop with 3 queries per asset
            $sql = "
                SELECT
                    a.id,
                    a.ref,
                    a.name,
                    a.status,
                    c.name as category_name,
                    c.is_consumable,
                    p.name as project_name,
                    a.acquisition_cost,
                    a.model,
                    a.serial_number,
                    -- Aggregate checks for asset usage (eliminates N+1 problem)
                    COUNT(DISTINCT bt.id) as active_borrowings,
                    COUNT(DISTINCT w.id) as active_withdrawals,
                    COUNT(DISTINCT t.id) as active_transfers
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                -- Check for active borrowed tools (not returned)
                LEFT JOIN borrowed_tools bt ON a.id = bt.asset_id
                    AND bt.status IN (?, ?, ?, ?, ?)
                -- Check for active withdrawals
                LEFT JOIN withdrawals w ON a.id = w.asset_id
                    AND w.status IN ('pending', 'released')
                -- Check for active transfers
                LEFT JOIN transfers t ON a.id = t.asset_id
                    AND t.status IN ('pending', 'approved')
                WHERE a.status = 'available'
                  AND p.is_active = 1
                  AND c.is_consumable = 0  -- Exclude consumable items
            ";

            // Initialize params with borrowed tool status constants
            $params = [
                BorrowedToolStatus::PENDING_VERIFICATION,
                BorrowedToolStatus::PENDING_APPROVAL,
                BorrowedToolStatus::APPROVED,
                BorrowedToolStatus::BORROWED,
                BorrowedToolStatus::OVERDUE
            ];

            // Filter by project if specified
            if ($projectId) {
                $sql .= " AND a.project_id = ?";
                $params[] = $projectId;
            }

            $sql .= "
                GROUP BY a.id, a.ref, a.name, a.status, c.name, c.is_consumable,
                         p.name, a.acquisition_cost, a.model, a.serial_number
                -- Only include assets that are NOT in use (all counts are 0)
                HAVING active_borrowings = 0
                   AND active_withdrawals = 0
                   AND active_transfers = 0
                ORDER BY a.name ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $availableAssets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Remove the aggregation columns before returning
            foreach ($availableAssets as &$asset) {
                unset($asset['active_borrowings']);
                unset($asset['active_withdrawals']);
                unset($asset['active_transfers']);
            }

            return $availableAssets;

        } catch (Exception $e) {
            error_log("Get available assets for borrowing error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get project ID for a specific asset
     * Moved from BorrowedToolController (Phase 2.1)
     *
     * @param int $assetId Asset ID
     * @return int|null Project ID or null if not found
     */
    public function getAssetProjectId($assetId) {
        try {
            $stmt = $this->db->prepare("SELECT project_id FROM assets WHERE id = ?");
            $stmt->execute([$assetId]);
            $projectId = $stmt->fetchColumn();
            return $projectId ? (int)$projectId : null;
        } catch (Exception $e) {
            error_log("Error getting asset project ID: " . $e->getMessage());
            return null;
        }
    }
}
