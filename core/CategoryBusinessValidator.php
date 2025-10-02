<?php
/**
 * ConstructLink™ Category Business Validator
 * Centralized validation for category business rules and procurement logic
 * 
 * This validator ensures proper business rule enforcement across:
 * - Category creation and modification
 * - Procurement item categorization
 * - Asset generation decisions
 * - Accounting classification compliance
 */

class CategoryBusinessValidator {
    
    private $categoryModel;
    
    public function __construct() {
        $this->categoryModel = new CategoryModel();
    }
    
    /**
     * Validate category business rules during creation/update
     */
    public function validateCategoryData($data, $isUpdate = false, $categoryId = null) {
        $errors = [];
        $warnings = [];
        
        try {
            // Basic field validation
            if (empty($data['name'])) {
                $errors[] = 'Category name is required';
            }
            
            // Check for duplicate names (excluding current category if updating)
            if (!empty($data['name'])) {
                $existing = $this->categoryModel->findFirst(['name' => $data['name']]);
                if ($existing && (!$isUpdate || $existing['id'] != $categoryId)) {
                    $errors[] = 'Category name already exists';
                }
            }
            
            // Validate generates_assets and asset_type relationship
            if (isset($data['generates_assets']) && isset($data['asset_type'])) {
                if (!$data['generates_assets'] && $data['asset_type'] !== 'expense') {
                    $errors[] = 'Non-asset-generating categories must have asset_type = "expense"';
                }
                
                if ($data['generates_assets'] && $data['asset_type'] === 'expense') {
                    $errors[] = 'Asset-generating categories cannot have asset_type = "expense"';
                }
            }
            
            // Validate expense_category logic
            if (isset($data['generates_assets']) && isset($data['expense_category'])) {
                if ($data['generates_assets'] && !empty($data['expense_category'])) {
                    $warnings[] = 'Asset-generating categories typically do not need expense categories';
                }
                
                if (!$data['generates_assets'] && empty($data['expense_category'])) {
                    $errors[] = 'Non-asset-generating categories must specify an expense category';
                }
            }
            
            // Validate depreciation logic
            if (isset($data['depreciation_applicable']) && isset($data['asset_type'])) {
                if ($data['depreciation_applicable'] && $data['asset_type'] !== 'capital') {
                    $errors[] = 'Only capital asset categories can be subject to depreciation';
                }
                
                if ($data['asset_type'] === 'capital' && !$data['depreciation_applicable']) {
                    $warnings[] = 'Capital asset categories are typically subject to depreciation';
                }
            }
            
            // Validate capitalization threshold
            if (isset($data['capitalization_threshold'])) {
                $threshold = (float)$data['capitalization_threshold'];
                
                if ($threshold < 0) {
                    $errors[] = 'Capitalization threshold must be non-negative';
                }
                
                if ($threshold > 0 && isset($data['generates_assets']) && !$data['generates_assets']) {
                    $warnings[] = 'Capitalization threshold is not applicable for non-asset-generating categories';
                }
                
                if ($threshold > 100000) {
                    $warnings[] = 'Capitalization threshold seems unusually high (' . number_format($threshold) . ')';
                }
            }
            
            // Validate auto_expense_below_threshold logic
            if (isset($data['auto_expense_below_threshold']) && $data['auto_expense_below_threshold']) {
                if (!isset($data['capitalization_threshold']) || $data['capitalization_threshold'] <= 0) {
                    $errors[] = 'Auto-expense below threshold requires a positive capitalization threshold';
                }
                
                if (isset($data['generates_assets']) && !$data['generates_assets']) {
                    $errors[] = 'Auto-expense below threshold only applies to asset-generating categories';
                }
            }
            
            // Business logic consistency checks
            if (isset($data['is_consumable']) && isset($data['asset_type'])) {
                if ($data['is_consumable'] && $data['asset_type'] === 'capital') {
                    $warnings[] = 'Consumable items are typically classified as inventory, not capital assets';
                }
                
                if (!$data['is_consumable'] && $data['asset_type'] === 'inventory') {
                    $warnings[] = 'Non-consumable items are typically classified as capital assets, not inventory';
                }
            }
            
        } catch (Exception $e) {
            $errors[] = 'Validation error: ' . $e->getMessage();
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Validate procurement item against category rules
     */
    public function validateProcurementItemCategory($procurementItem, $category = null) {
        $errors = [];
        $warnings = [];
        
        try {
            // Get category if not provided
            if (!$category && !empty($procurementItem['category_id'])) {
                $category = $this->categoryModel->find($procurementItem['category_id']);
            }
            
            if (!$category) {
                $errors[] = 'Category not found or not provided';
                return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
            }
            
            $unitPrice = (float)($procurementItem['unit_price'] ?? 0);
            $quantity = (int)($procurementItem['quantity'] ?? 1);
            
            // Validate unit price for asset-generating categories
            if ($category['generates_assets']) {
                $threshold = (float)$category['capitalization_threshold'];
                
                if ($threshold > 0 && $unitPrice < $threshold) {
                    if ($category['auto_expense_below_threshold']) {
                        $warnings[] = "Unit price ($unitPrice) is below capitalization threshold ($threshold). Item will be auto-expensed.";
                    } else {
                        $warnings[] = "Unit price ($unitPrice) is below capitalization threshold ($threshold). Consider reviewing categorization.";
                    }
                }
                
                // Validate quantity for different asset types
                if ($category['asset_type'] === 'capital' && $quantity > 1 && !$category['is_consumable']) {
                    $warnings[] = "Multiple quantities for non-consumable capital assets may require individual asset tracking.";
                }
            }
            
            // Validate expense categorization
            if (!$category['generates_assets']) {
                if (empty($category['expense_category'])) {
                    $errors[] = 'Non-asset-generating category must have expense category defined';
                }
                
                // Validate expense category appropriateness
                $expenseCategory = $category['expense_category'];
                $itemName = strtolower($procurementItem['item_name'] ?? '');
                
                if ($expenseCategory === 'maintenance' && !preg_match('/repair|maintain|service|fix/i', $itemName)) {
                    $warnings[] = 'Item doesn\'t appear to be maintenance-related but is categorized as maintenance expense';
                }
                
                if ($expenseCategory === 'professional_services' && !preg_match('/consult|service|test|certif|inspect/i', $itemName)) {
                    $warnings[] = 'Item doesn\'t appear to be a professional service but is categorized as such';
                }
            }
            
            // Business value validation
            $totalValue = $unitPrice * $quantity;
            if ($totalValue > 50000 && !$category['generates_assets']) {
                $warnings[] = "High-value procurement ($totalValue) categorized as expense. Consider if asset tracking is appropriate.";
            }
            
        } catch (Exception $e) {
            $errors[] = 'Procurement validation error: ' . $e->getMessage();
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'asset_generation_recommended' => $category['generates_assets'] ?? false,
            'expense_category' => $category['expense_category'] ?? null
        ];
    }
    
    /**
     * Determine asset generation eligibility with detailed reasoning
     */
    public function evaluateAssetGeneration($procurementItem, $category = null) {
        try {
            // Get category if not provided
            if (!$category && !empty($procurementItem['category_id'])) {
                $category = $this->categoryModel->find($procurementItem['category_id']);
            }
            
            if (!$category) {
                return [
                    'should_generate' => false,
                    'reason' => 'Category not found',
                    'decision_factors' => ['category_missing' => true]
                ];
            }
            
            $unitPrice = (float)($procurementItem['unit_price'] ?? 0);
            $threshold = (float)$category['capitalization_threshold'];
            $decisionFactors = [
                'category_generates_assets' => (bool)$category['generates_assets'],
                'asset_type' => $category['asset_type'],
                'unit_price' => $unitPrice,
                'capitalization_threshold' => $threshold,
                'auto_expense_below_threshold' => (bool)$category['auto_expense_below_threshold']
            ];
            
            // Primary check: Does category generate assets?
            if (!$category['generates_assets']) {
                return [
                    'should_generate' => false,
                    'reason' => 'Category configured for direct expense allocation',
                    'expense_category' => $category['expense_category'],
                    'decision_factors' => $decisionFactors
                ];
            }
            
            // Secondary check: Capitalization threshold
            if ($threshold > 0 && $unitPrice > 0 && $unitPrice < $threshold) {
                if ($category['auto_expense_below_threshold']) {
                    return [
                        'should_generate' => false,
                        'reason' => "Unit price ($unitPrice) below capitalization threshold ($threshold) with auto-expense enabled",
                        'expense_category' => 'below_threshold',
                        'decision_factors' => $decisionFactors
                    ];
                } else {
                    // Threshold exists but auto-expense is disabled - generate asset but flag for review
                    $decisionFactors['threshold_warning'] = true;
                }
            }
            
            // Asset generation approved
            return [
                'should_generate' => true,
                'asset_type' => $category['asset_type'],
                'is_consumable' => (bool)$category['is_consumable'],
                'depreciation_applicable' => (bool)$category['depreciation_applicable'],
                'decision_factors' => $decisionFactors,
                'warnings' => isset($decisionFactors['threshold_warning']) ? 
                    ["Unit price ($unitPrice) below threshold ($threshold) but auto-expense disabled"] : []
            ];
            
        } catch (Exception $e) {
            return [
                'should_generate' => false,
                'reason' => 'Evaluation error: ' . $e->getMessage(),
                'decision_factors' => ['error' => $e->getMessage()]
            ];
        }
    }
    
    /**
     * Validate category hierarchy and parent-child relationships
     */
    public function validateCategoryHierarchy($categoryData, $isUpdate = false, $categoryId = null) {
        $errors = [];
        $warnings = [];
        
        try {
            if (!empty($categoryData['parent_id'])) {
                $parent = $this->categoryModel->find($categoryData['parent_id']);
                
                if (!$parent) {
                    $errors[] = 'Parent category not found';
                } else {
                    // Validate circular references
                    if ($isUpdate && $categoryId == $categoryData['parent_id']) {
                        $errors[] = 'Category cannot be its own parent';
                    }
                    
                    // Check business rule consistency between parent and child
                    if (isset($categoryData['asset_type']) && $parent['asset_type'] !== $categoryData['asset_type']) {
                        $warnings[] = 'Child category asset type differs from parent. This may cause confusion.';
                    }
                    
                    if (isset($categoryData['generates_assets']) && $parent['generates_assets'] != $categoryData['generates_assets']) {
                        $warnings[] = 'Child category asset generation setting differs from parent.';
                    }
                }
            }
            
        } catch (Exception $e) {
            $errors[] = 'Hierarchy validation error: ' . $e->getMessage();
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Generate business rule compliance report for a category
     */
    public function generateComplianceReport($categoryId) {
        try {
            $category = $this->categoryModel->getCategoryWithBusinessDetails($categoryId);
            
            if (!$category) {
                return ['error' => 'Category not found'];
            }
            
            $compliance = [
                'category' => $category,
                'business_rules' => [],
                'compliance_score' => 0,
                'recommendations' => []
            ];
            
            // Check asset generation configuration
            if ($category['generates_assets']) {
                $compliance['business_rules']['asset_generation'] = [
                    'configured' => true,
                    'asset_type' => $category['asset_type'],
                    'compliance' => 'good'
                ];
                $compliance['compliance_score'] += 20;
            } else {
                if (!empty($category['expense_category'])) {
                    $compliance['business_rules']['expense_categorization'] = [
                        'configured' => true,
                        'expense_category' => $category['expense_category'],
                        'compliance' => 'good'
                    ];
                    $compliance['compliance_score'] += 20;
                } else {
                    $compliance['business_rules']['expense_categorization'] = [
                        'configured' => false,
                        'compliance' => 'poor'
                    ];
                    $compliance['recommendations'][] = 'Define expense category for non-asset-generating category';
                }
            }
            
            // Check capitalization threshold
            if ($category['generates_assets'] && $category['capitalization_threshold'] > 0) {
                $compliance['business_rules']['capitalization_threshold'] = [
                    'configured' => true,
                    'threshold' => $category['capitalization_threshold'],
                    'auto_expense' => (bool)$category['auto_expense_below_threshold'],
                    'compliance' => 'good'
                ];
                $compliance['compliance_score'] += 15;
            }
            
            // Check depreciation settings for capital assets
            if ($category['asset_type'] === 'capital') {
                if ($category['depreciation_applicable']) {
                    $compliance['business_rules']['depreciation'] = [
                        'configured' => true,
                        'compliance' => 'good'
                    ];
                    $compliance['compliance_score'] += 15;
                } else {
                    $compliance['business_rules']['depreciation'] = [
                        'configured' => false,
                        'compliance' => 'fair'
                    ];
                    $compliance['recommendations'][] = 'Consider enabling depreciation for capital asset category';
                }
            }
            
            // Usage statistics
            if ($category['assets_count'] > 0) {
                $compliance['business_rules']['usage'] = [
                    'active_usage' => true,
                    'asset_count' => $category['assets_count'],
                    'total_value' => $category['total_value'],
                    'compliance' => 'good'
                ];
                $compliance['compliance_score'] += 25;
            }
            
            // Business description
            if (!empty($category['business_description'])) {
                $compliance['business_rules']['documentation'] = [
                    'documented' => true,
                    'compliance' => 'good'
                ];
                $compliance['compliance_score'] += 5;
            } else {
                $compliance['recommendations'][] = 'Add business description to improve category clarity';
            }
            
            // Final compliance assessment
            if ($compliance['compliance_score'] >= 90) {
                $compliance['overall_compliance'] = 'excellent';
            } elseif ($compliance['compliance_score'] >= 70) {
                $compliance['overall_compliance'] = 'good';
            } elseif ($compliance['compliance_score'] >= 50) {
                $compliance['overall_compliance'] = 'fair';
            } else {
                $compliance['overall_compliance'] = 'poor';
            }
            
            return $compliance;
            
        } catch (Exception $e) {
            return ['error' => 'Compliance report generation error: ' . $e->getMessage()];
        }
    }
}
?>