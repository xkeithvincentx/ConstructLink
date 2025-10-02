<?php
/**
 * ConstructLink™ Category Model
 * Handles asset categories and subcategories
 */

class CategoryModel extends BaseModel {
    protected $table = 'categories';
    protected $fillable = [
        'name', 'description', 'is_consumable', 'parent_id',
        'generates_assets', 'asset_type', 'expense_category', 'depreciation_applicable',
        'capitalization_threshold', 'business_description', 'auto_expense_below_threshold'
    ];
    
    /**
     * Get all active categories
     */
    public function getActiveCategories() {
        try {
            $result = $this->findAll([], 'name ASC');
            if (!is_array($result)) {
                return [];
            }
            
            // Add migration-safe fallbacks for business fields
            foreach ($result as &$category) {
                $this->addBusinessFieldFallbacks($category);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("CategoryModel::getActiveCategories error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all categories with hierarchy
     */
    public function getCategoriesWithHierarchy() {
        $sql = "
            SELECT 
                c.*,
                p.name as parent_name,
                COUNT(a.id) as asset_count
            FROM {$this->table} c
            LEFT JOIN {$this->table} p ON c.parent_id = p.id
            LEFT JOIN assets a ON c.id = a.category_id
            GROUP BY c.id
            ORDER BY COALESCE(p.name, c.name), c.name
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get categories for dropdown
     */
    public function getCategoriesForDropdown() {
        try {
            $sql = "
                SELECT 
                    c.id,
                    c.name,
                    CASE 
                        WHEN c.parent_id IS NOT NULL THEN CONCAT(p.name, ' > ', c.name)
                        ELSE c.name
                    END as display_name,
                    c.is_consumable
                FROM {$this->table} c
                LEFT JOIN {$this->table} p ON c.parent_id = p.id
                ORDER BY display_name
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("CategoryModel::getCategoriesForDropdown error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get categories with filters and pagination
     */
    public function getCategoriesWithFilters($filters = [], $page = 1, $perPage = 20) {
        $conditions = [];
        $params = [];
        
        // Apply filters
        if (!empty($filters['type'])) {
            if ($filters['type'] === 'equipment') {
                $conditions[] = "c.is_consumable = 0";
            } elseif ($filters['type'] === 'consumable') {
                $conditions[] = "c.is_consumable = 1";
            }
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(c.name LIKE ? OR c.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        // Count total records
        $countSql = "
            SELECT COUNT(*) 
            FROM {$this->table} c
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get paginated data
        $offset = ($page - 1) * $perPage;
        $orderBy = $filters['order_by'] ?? 'c.name ASC';
        
        $dataSql = "
            SELECT c.*,
                   COUNT(a.id) as assets_count,
                   p.name as parent_name
            FROM {$this->table} c
            LEFT JOIN assets a ON c.id = a.category_id
            LEFT JOIN {$this->table} p ON c.parent_id = p.id
            {$whereClause}
            GROUP BY c.id, c.name, c.is_consumable, c.description, c.parent_id, c.created_at, p.name
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
    }
    
    /**
     * Get category statistics
     */
    public function getCategoryStatistics($categoryId = null) {
        $whereClause = $categoryId ? "WHERE c.id = ?" : "";
        $params = $categoryId ? [$categoryId] : [];
        
        $sql = "
            SELECT 
                COUNT(DISTINCT c.id) as total_categories,
                COUNT(DISTINCT CASE WHEN c.is_consumable = 0 THEN c.id END) as equipment_categories,
                COUNT(DISTINCT CASE WHEN c.is_consumable = 1 THEN c.id END) as consumable_categories,
                COUNT(a.id) as total_assets,
                SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available_assets,
                SUM(CASE WHEN a.status = 'in_use' THEN 1 ELSE 0 END) as in_use_assets,
                SUM(CASE WHEN a.status = 'under_maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
                COALESCE(SUM(a.acquisition_cost), 0) as total_value
            FROM {$this->table} c
            LEFT JOIN assets a ON c.id = a.category_id
            {$whereClause}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get category with detailed information
     */
    public function getCategoryWithDetails($id) {
        $sql = "
            SELECT c.*,
                   COUNT(a.id) as assets_count,
                   SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available_assets,
                   SUM(CASE WHEN a.status = 'in_use' THEN 1 ELSE 0 END) as in_use_assets,
                   SUM(CASE WHEN a.status = 'under_maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
                   COALESCE(SUM(a.acquisition_cost), 0) as total_value,
                   p.name as parent_name
            FROM {$this->table} c
            LEFT JOIN assets a ON c.id = a.category_id
            LEFT JOIN {$this->table} p ON c.parent_id = p.id
            WHERE c.id = ?
            GROUP BY c.id
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Add migration-safe fallbacks for business fields
            $this->addBusinessFieldFallbacks($result);
        }
        
        return $result;
    }
    
    /**
     * Add migration-safe fallbacks for business fields
     */
    private function addBusinessFieldFallbacks(&$category) {
        if (is_array($category)) {
            $category['generates_assets'] = $category['generates_assets'] ?? 1;
            $category['asset_type'] = $category['asset_type'] ?? 'capital';
            $category['expense_category'] = $category['expense_category'] ?? null;
            $category['depreciation_applicable'] = $category['depreciation_applicable'] ?? 0;
            $category['capitalization_threshold'] = $category['capitalization_threshold'] ?? 0.00;
            $category['business_description'] = $category['business_description'] ?? '';
            $category['auto_expense_below_threshold'] = $category['auto_expense_below_threshold'] ?? 0;
        }
    }
    
    /**
     * Override find method to include business field fallbacks
     */
    public function find($id) {
        $result = parent::find($id);
        if ($result) {
            $this->addBusinessFieldFallbacks($result);
        }
        return $result;
    }
    
    /**
     * Create category with validation
     */
    public function createCategory($data) {
        try {
            // Validate required fields
            $errors = [];
            
            if (empty($data['name'])) {
                $errors[] = 'Category name is required';
            }
            
            // Check for duplicate name
            if ($this->findFirst(['name' => $data['name']])) {
                $errors[] = 'Category name already exists';
            }
            
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Prepare data
            $categoryData = [
                'name' => Validator::sanitize($data['name']),
                'is_consumable' => (isset($data['is_consumable']) && $data['is_consumable'] == '1') ? 1 : 0,
                'description' => Validator::sanitize($data['description'] ?? ''),
                'parent_id' => !empty($data['parent_id']) ? (int)$data['parent_id'] : null
            ];
            
            $createdCategory = $this->create($categoryData);
            
            if ($createdCategory) {
                if (function_exists('logActivity')) {
                    logActivity('category_created', "Category '{$categoryData['name']}' created");
                }
                
                return [
                    'success' => true,
                    'category' => $createdCategory,
                    'message' => 'Category created successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create category'];
            }
            
        } catch (Exception $e) {
            error_log("Category creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Update category
     */
    public function updateCategory($id, $data) {
        try {
            $category = $this->find($id);
            if (!$category) {
                return ['success' => false, 'message' => 'Category not found'];
            }
            
            // Validate required fields
            $errors = [];
            
            if (empty($data['name'])) {
                $errors[] = 'Category name is required';
            }
            
            // Check for duplicate name (excluding current category)
            $existing = $this->findFirst(['name' => $data['name']]);
            if ($existing && $existing['id'] != $id) {
                $errors[] = 'Category name already exists';
            }
            
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Prepare data
            $categoryData = [
                'name' => Validator::sanitize($data['name']),
                'is_consumable' => (isset($data['is_consumable']) && $data['is_consumable'] == '1') ? 1 : 0,
                'description' => Validator::sanitize($data['description'] ?? ''),
                'parent_id' => !empty($data['parent_id']) ? (int)$data['parent_id'] : null
            ];
            
            $updatedCategory = $this->update($id, $categoryData);
            
            if ($updatedCategory) {
                if (function_exists('logActivity')) {
                    logActivity('category_updated', "Category '{$categoryData['name']}' updated");
                }
                
                return [
                    'success' => true,
                    'category' => $updatedCategory,
                    'message' => 'Category updated successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update category'];
            }
            
        } catch (Exception $e) {
            error_log("Category update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Delete category
     */
    public function deleteCategory($id) {
        try {
            $category = $this->find($id);
            if (!$category) {
                return ['success' => false, 'message' => 'Category not found'];
            }
            
            // Check if category has assets
            $assetCount = $this->db->prepare("SELECT COUNT(*) FROM assets WHERE category_id = ?");
            $assetCount->execute([$id]);
            
            if ($assetCount->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Cannot delete category with existing assets'];
            }
            
            // Check if category has subcategories
            $subcategoryCount = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE parent_id = ?");
            $subcategoryCount->execute([$id]);
            
            if ($subcategoryCount->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Cannot delete category with subcategories'];
            }
            
            $success = $this->delete($id);
            
            if ($success) {
                if (function_exists('logActivity')) {
                    logActivity('category_deleted', "Category '{$category['name']}' deleted");
                }
                return ['success' => true, 'message' => 'Category deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete category'];
            }
            
        } catch (Exception $e) {
            error_log("Category deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    // ================================================================================
    // BUSINESS CLASSIFICATION METHODS
    // ================================================================================

    /**
     * Get categories by business type
     */
    public function getCategoriesByType($assetType = null, $generatesAssets = null) {
        try {
            $conditions = [];
            $params = [];

            if ($assetType !== null) {
                $conditions[] = "asset_type = ?";
                $params[] = $assetType;
            }

            if ($generatesAssets !== null) {
                $conditions[] = "generates_assets = ?";
                $params[] = $generatesAssets ? 1 : 0;
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $sql = "
                SELECT c.*,
                       CASE 
                           WHEN c.parent_id IS NOT NULL THEN CONCAT(p.name, ' > ', c.name)
                           ELSE c.name
                       END as display_name,
                       p.name as parent_name
                FROM {$this->table} c
                LEFT JOIN {$this->table} p ON c.parent_id = p.id
                {$whereClause}
                ORDER BY display_name
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("CategoryModel::getCategoriesByType error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get asset-generating categories for procurement dropdown
     */
    public function getAssetGeneratingCategories() {
        return $this->getCategoriesByType(null, true);
    }

    /**
     * Get expense categories (non-asset-generating)
     */
    public function getExpenseCategories() {
        return $this->getCategoriesByType(null, false);
    }

    /**
     * Get categories by asset type (capital, inventory, expense)
     */
    public function getCategoriesByAssetType($assetType) {
        return $this->getCategoriesByType($assetType, null);
    }

    /**
     * Validate category business rules
     */
    public function validateCategoryBusinessRules($categoryData) {
        $errors = [];
        
        // Normalize data types for validation
        $generatesAssets = isset($categoryData['generates_assets']) ? 
            ($categoryData['generates_assets'] == 1 || $categoryData['generates_assets'] === true) : true;
        $assetType = $categoryData['asset_type'] ?? 'capital';
        $expenseCategory = $categoryData['expense_category'] ?? null;
        
        // Validate asset_type and generates_assets relationship
        if ($assetType === 'expense' && $generatesAssets) {
            $errors[] = 'Expense categories cannot generate assets';
        }
        
        if (in_array($assetType, ['capital', 'inventory']) && !$generatesAssets) {
            $errors[] = 'Capital and inventory categories must generate assets';
        }
        
        // Validate expense_category logic
        if ($generatesAssets && !empty($expenseCategory)) {
            $errors[] = 'Asset-generating categories should not have expense categories';
        }
        
        if (!$generatesAssets && empty($expenseCategory)) {
            $errors[] = 'Non-asset-generating categories must specify an expense category';
        }
        
        // Validate depreciation logic
        $depreciationApplicable = isset($categoryData['depreciation_applicable']) ? 
            ($categoryData['depreciation_applicable'] == 1 || $categoryData['depreciation_applicable'] === true) : false;
        
        if ($depreciationApplicable && $assetType !== 'capital') {
            $errors[] = 'Only capital asset categories can be subject to depreciation';
        }
        
        // Validate capitalization threshold
        if (isset($categoryData['capitalization_threshold'])) {
            if ($categoryData['capitalization_threshold'] < 0) {
                $errors[] = 'Capitalization threshold must be non-negative';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get category with business classification details
     */
    public function getCategoryWithBusinessDetails($id) {
        try {
            $sql = "
                SELECT c.*,
                       COUNT(a.id) as assets_count,
                       SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available_assets,
                       SUM(CASE WHEN a.status = 'in_use' THEN 1 ELSE 0 END) as in_use_assets,
                       SUM(CASE WHEN a.status = 'under_maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
                       COALESCE(SUM(a.acquisition_cost), 0) as total_value,
                       p.name as parent_name,
                       CASE 
                           WHEN c.generates_assets = 1 THEN 'Asset-Generating'
                           ELSE 'Direct Expense'
                       END as procurement_behavior,
                       CASE c.asset_type
                           WHEN 'capital' THEN 'Capital Assets (Depreciable)'
                           WHEN 'inventory' THEN 'Inventory/Materials (Consumable)'
                           WHEN 'expense' THEN 'Operating Expenses'
                           ELSE 'Undefined'
                       END as business_classification
                FROM {$this->table} c
                LEFT JOIN assets a ON c.id = a.category_id
                LEFT JOIN {$this->table} p ON c.parent_id = p.id
                WHERE c.id = ?
                GROUP BY c.id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("CategoryModel::getCategoryWithBusinessDetails error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a category should generate assets for a given procurement item
     */
    public function shouldGenerateAsset($categoryId, $unitPrice = 0) {
        try {
            $category = $this->find($categoryId);
            
            if (!$category) {
                return ['should_generate' => false, 'reason' => 'Category not found'];
            }
            
            // If category doesn't generate assets, return false
            if (!$category['generates_assets']) {
                return [
                    'should_generate' => false, 
                    'reason' => 'Category is configured for direct expense',
                    'expense_category' => $category['expense_category']
                ];
            }
            
            // Check capitalization threshold
            $threshold = (float)$category['capitalization_threshold'];
            if ($threshold > 0 && $unitPrice < $threshold && $category['auto_expense_below_threshold']) {
                return [
                    'should_generate' => false,
                    'reason' => "Unit price ($unitPrice) below capitalization threshold ($threshold)",
                    'expense_category' => 'below_threshold'
                ];
            }
            
            return [
                'should_generate' => true,
                'asset_type' => $category['asset_type'],
                'is_consumable' => $category['is_consumable'],
                'depreciation_applicable' => $category['depreciation_applicable']
            ];
            
        } catch (Exception $e) {
            error_log("CategoryModel::shouldGenerateAsset error: " . $e->getMessage());
            return ['should_generate' => false, 'reason' => 'Database error'];
        }
    }

    /**
     * Get business statistics for categories
     */
    public function getBusinessStatistics() {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_categories,
                    COUNT(CASE WHEN generates_assets = 1 THEN 1 END) as asset_generating,
                    COUNT(CASE WHEN generates_assets = 0 THEN 1 END) as expense_only,
                    COUNT(CASE WHEN asset_type = 'capital' THEN 1 END) as capital_categories,
                    COUNT(CASE WHEN asset_type = 'inventory' THEN 1 END) as inventory_categories,
                    COUNT(CASE WHEN asset_type = 'expense' THEN 1 END) as expense_categories,
                    COUNT(CASE WHEN depreciation_applicable = 1 THEN 1 END) as depreciable_categories,
                    AVG(capitalization_threshold) as avg_capitalization_threshold
                FROM {$this->table}
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("CategoryModel::getBusinessStatistics error: " . $e->getMessage());
            return [
                'total_categories' => 0,
                'asset_generating' => 0,
                'expense_only' => 0,
                'capital_categories' => 0,
                'inventory_categories' => 0,
                'expense_categories' => 0,
                'depreciable_categories' => 0,
                'avg_capitalization_threshold' => 0
            ];
        }
    }

    /**
     * Enhanced create category with business validation
     */
    public function createCategoryWithBusinessRules($data) {
        try {
            // Validate basic requirements
            $errors = [];
            
            if (empty($data['name'])) {
                $errors[] = 'Category name is required';
            }
            
            // Check for duplicate name
            if ($this->findFirst(['name' => $data['name']])) {
                $errors[] = 'Category name already exists';
            }
            
            // Validate business rules
            $businessValidation = $this->validateCategoryBusinessRules($data);
            if (!$businessValidation['valid']) {
                $errors = array_merge($errors, $businessValidation['errors']);
            }
            
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Set defaults for business fields
            $categoryData = [
                'name' => Validator::sanitize($data['name']),
                'description' => Validator::sanitize($data['description'] ?? ''),
                'is_consumable' => (isset($data['is_consumable']) && $data['is_consumable'] == '1') ? 1 : 0,
                'parent_id' => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                'generates_assets' => (isset($data['generates_assets']) && $data['generates_assets'] == '1') ? 1 : 0,
                'asset_type' => $data['asset_type'] ?? 'capital',
                'expense_category' => $data['expense_category'] ?? null,
                'depreciation_applicable' => (isset($data['depreciation_applicable']) && $data['depreciation_applicable'] == '1') ? 1 : 0,
                'capitalization_threshold' => isset($data['capitalization_threshold']) ? (float)$data['capitalization_threshold'] : 0.00,
                'business_description' => Validator::sanitize($data['business_description'] ?? ''),
                'auto_expense_below_threshold' => (isset($data['auto_expense_below_threshold']) && $data['auto_expense_below_threshold'] == '1') ? 1 : 0
            ];
            
            $createdCategory = $this->create($categoryData);
            
            if ($createdCategory) {
                if (function_exists('logActivity')) {
                    logActivity('category_created', "Business category '{$categoryData['name']}' created with type: {$categoryData['asset_type']}");
                }
                
                return [
                    'success' => true,
                    'category' => $createdCategory,
                    'message' => 'Category created successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create category'];
            }
            
        } catch (Exception $e) {
            error_log("Category creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Update category with business rules validation
     */
    public function updateCategoryWithBusinessRules($id, $data) {
        try {
            $category = $this->find($id);
            if (!$category) {
                return ['success' => false, 'message' => 'Category not found'];
            }
            
            // Validate basic requirements
            $errors = [];
            
            if (empty($data['name'])) {
                $errors[] = 'Category name is required';
            }
            
            // Check for duplicate name (excluding current category)
            $existing = $this->findFirst(['name' => $data['name']]);
            if ($existing && $existing['id'] != $id) {
                $errors[] = 'Category name already exists';
            }
            
            // Validate business rules
            $businessValidation = $this->validateCategoryBusinessRules($data);
            if (!$businessValidation['valid']) {
                $errors = array_merge($errors, $businessValidation['errors']);
            }
            
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Prepare data with business fields
            $categoryData = [
                'name' => Validator::sanitize($data['name']),
                'description' => Validator::sanitize($data['description'] ?? ''),
                'is_consumable' => (isset($data['is_consumable']) && $data['is_consumable'] == '1') ? 1 : 0,
                'parent_id' => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                'generates_assets' => (isset($data['generates_assets']) && $data['generates_assets'] == '1') ? 1 : 0,
                'asset_type' => in_array($data['asset_type'] ?? 'capital', ['capital', 'inventory', 'expense']) ? 
                    ($data['asset_type'] ?? 'capital') : 'capital',
                'expense_category' => !empty($data['expense_category']) ? Validator::sanitize($data['expense_category']) : null,
                'depreciation_applicable' => (isset($data['depreciation_applicable']) && $data['depreciation_applicable'] == '1') ? 1 : 0,
                'capitalization_threshold' => !empty($data['capitalization_threshold']) ? (float)$data['capitalization_threshold'] : 0.00,
                'business_description' => Validator::sanitize($data['business_description'] ?? ''),
                'auto_expense_below_threshold' => (isset($data['auto_expense_below_threshold']) && $data['auto_expense_below_threshold'] == '1') ? 1 : 0
            ];
            
            $updatedCategory = $this->update($id, $categoryData);
            
            if ($updatedCategory) {
                if (function_exists('logActivity')) {
                    logActivity('category_updated', "Business category '{$categoryData['name']}' updated with type: {$categoryData['asset_type']}");
                }
                
                return [
                    'success' => true,
                    'category' => $updatedCategory,
                    'message' => 'Category updated successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update category'];
            }
            
        } catch (Exception $e) {
            error_log("Category update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
}
?>
