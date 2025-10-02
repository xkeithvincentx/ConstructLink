<?php
// Only require database.php if Database class not already loaded
if (!class_exists('Database')) {
    if (file_exists(APP_ROOT . '/config/Database.php')) {
        require_once APP_ROOT . '/config/Database.php';
    } else {
        require_once APP_ROOT . '/config/database.php';
    }
}

/**
 * Asset Data Quality Validation Engine
 * Validates asset data against predefined rules and provides quality scoring
 */
class AssetDataQualityValidator {
    private $db;
    private $validationRules;
    
    public function __construct() {
        // Get database connection properly
        if (isset($GLOBALS['pdo'])) {
            $this->db = $GLOBALS['pdo'];
        } else {
            // Fallback to creating new connection if global not available
            require_once APP_ROOT . '/config/Database.php';
            $this->db = Database::getInstance()->getConnection();
        }
        
        if (!$this->db) {
            throw new Exception("Database connection not available for validator");
        }
        
        $this->loadValidationRules();
    }
    
    /**
     * Load validation rules from database
     */
    private function loadValidationRules() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM asset_validation_rules WHERE is_active = 1 ORDER BY severity DESC");
            $stmt->execute();
            $this->validationRules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Loaded " . count($this->validationRules) . " validation rules");
        } catch (Exception $e) {
            error_log("Failed to load validation rules: " . $e->getMessage());
            $this->validationRules = [];
        }
    }
    
    /**
     * Validate asset data and return validation results
     */
    public function validateAsset($assetData, $userRole = null) {
        error_log("Starting validation for asset with role: " . ($userRole ?? 'none'));
        error_log("Asset data keys: " . implode(', ', array_keys($assetData)));
        
        $results = [
            'overall_score' => 0,
            'completeness_score' => 0,
            'accuracy_score' => 0,
            'validation_results' => [],
            'field_scores' => [],
            'errors' => [],
            'warnings' => [],
            'info' => []
        ];
        
        if (empty($this->validationRules)) {
            error_log("No validation rules loaded!");
            return $results;
        }
        
        $totalRules = 0;
        $passedRules = 0;
        $completenessRules = 0;
        $passedCompletenessRules = 0;
        $accuracyRules = 0;
        $passedAccuracyRules = 0;
        
        foreach ($this->validationRules as $rule) {
            // Check if rule applies to current user role
            if ($userRole && !$this->ruleAppliesTo($rule, $userRole)) {
                error_log("Rule {$rule['rule_name']} skipped - doesn't apply to role {$userRole}");
                continue;
            }
            
            $totalRules++;
            $validation = $this->executeValidationRule($rule, $assetData);
            error_log("Rule {$rule['rule_name']}: " . ($validation['passed'] ? 'PASSED' : 'FAILED'));
            
            $results['validation_results'][] = [
                'rule_name' => $rule['rule_name'],
                'field_name' => $rule['field_name'],
                'passed' => $validation['passed'],
                'message' => $validation['passed'] ? 'Valid' : $rule['error_message'],
                'severity' => $rule['severity'],
                'suggestions' => $validation['suggestions'] ?? []
            ];
            
            // Track field-specific scores
            if (!isset($results['field_scores'][$rule['field_name']])) {
                $results['field_scores'][$rule['field_name']] = ['total' => 0, 'passed' => 0];
            }
            $results['field_scores'][$rule['field_name']]['total']++;
            
            if ($validation['passed']) {
                $passedRules++;
                $results['field_scores'][$rule['field_name']]['passed']++;
                
                if ($rule['rule_type'] === 'completeness') {
                    $passedCompletenessRules++;
                }
                if (in_array($rule['rule_type'], ['format', 'logic', 'cost'])) {
                    $passedAccuracyRules++;
                }
            } else {
                // Categorize failures
                switch ($rule['severity']) {
                    case 'error':
                        $results['errors'][] = $rule['error_message'];
                        break;
                    case 'warning':
                        $results['warnings'][] = $rule['error_message'];
                        break;
                    case 'info':
                        $results['info'][] = $rule['error_message'];
                        break;
                }
            }
            
            // Count rule types for scoring
            if ($rule['rule_type'] === 'completeness') {
                $completenessRules++;
            }
            if (in_array($rule['rule_type'], ['format', 'logic', 'cost'])) {
                $accuracyRules++;
            }
        }
        
        // Calculate scores
        try {
            // Try context-aware scoring first
            $scores = $this->calculateContextAwareScores($assetData, $results['validation_results']);
            $results['overall_score'] = $scores['overall'];
            $results['completeness_score'] = $scores['completeness'];
            $results['accuracy_score'] = $scores['accuracy'];
            $results['score_context'] = $scores['context'];
        } catch (Exception $e) {
            error_log("Context-aware scoring failed, using simple scoring: " . $e->getMessage());
            // Fallback to simple percentage-based scoring
            if ($totalRules > 0) {
                $results['overall_score'] = round(($passedRules / $totalRules) * 100, 2);
            }
            if ($completenessRules > 0) {
                $results['completeness_score'] = round(($passedCompletenessRules / $completenessRules) * 100, 2);
            }
            if ($accuracyRules > 0) {
                $results['accuracy_score'] = round(($passedAccuracyRules / $accuracyRules) * 100, 2);
            }
        }
        
        // Calculate field-specific scores
        foreach ($results['field_scores'] as $field => &$score) {
            $score['percentage'] = $score['total'] > 0 ? round(($score['passed'] / $score['total']) * 100, 2) : 100;
        }
        
        error_log("Final scores - Overall: {$results['overall_score']}, Completeness: {$results['completeness_score']}, Accuracy: {$results['accuracy_score']}");
        error_log("Total rules: {$totalRules}, Passed: {$passedRules}");
        
        return $results;
    }
    
    /**
     * Check if validation rule applies to given user role
     */
    private function ruleAppliesTo($rule, $userRole) {
        if (empty($rule['applies_to_roles'])) {
            return true;
        }
        
        $applicableRoles = json_decode($rule['applies_to_roles'], true);
        return in_array($userRole, $applicableRoles);
    }
    
    /**
     * Execute individual validation rule
     */
    private function executeValidationRule($rule, $assetData) {
        $fieldValue = $assetData[$rule['field_name']] ?? null;
        $logic = json_decode($rule['validation_logic'], true);
        $suggestions = [];
        
        switch ($rule['rule_type']) {
            case 'completeness':
                return $this->validateCompleteness($fieldValue, $logic, $suggestions);
                
            case 'format':
                return $this->validateFormat($fieldValue, $logic, $suggestions);
                
            case 'logic':
                return $this->validateLogic($fieldValue, $logic, $assetData, $suggestions);
                
            case 'cost':
                return $this->validateCost($fieldValue, $logic, $assetData, $suggestions);
                
            case 'duplicate':
                return $this->validateDuplicate($fieldValue, $logic, $assetData, $suggestions);
                
            case 'context':
                return $this->validateContext($fieldValue, $logic, $assetData, $suggestions);
                
            default:
                return ['passed' => true, 'suggestions' => []];
        }
    }
    
    /**
     * Validate completeness rules
     */
    private function validateCompleteness($value, $logic, &$suggestions) {
        // Required field check
        if (isset($logic['required']) && $logic['required']) {
            if (empty($value)) {
                $suggestions[] = "This field is required and cannot be empty";
                return ['passed' => false, 'suggestions' => $suggestions];
            }
        }
        
        // Minimum length check
        if (isset($logic['min_length']) && strlen(trim($value)) < $logic['min_length']) {
            $suggestions[] = "Should be at least {$logic['min_length']} characters long";
            return ['passed' => false, 'suggestions' => $suggestions];
        }
        
        // Avoid generic terms check
        if (isset($logic['avoid_generic']) && is_array($logic['avoid_generic'])) {
            $lowerValue = strtolower($value);
            foreach ($logic['avoid_generic'] as $generic) {
                if (strpos($lowerValue, strtolower($generic)) !== false) {
                    $suggestions[] = "Try to be more specific than '{$generic}'";
                    return ['passed' => false, 'suggestions' => $suggestions];
                }
            }
        }
        
        // Context-based requirements (equipment type or cost-based)
        if (isset($logic['required_for_equipment_types']) || isset($logic['required_for_cost_above'])) {
            return $this->validateContextualRequirement($value, $logic, $assetData, $suggestions);
        }
        
        return ['passed' => true, 'suggestions' => $suggestions];
    }
    
    /**
     * Validate format rules
     */
    private function validateFormat($value, $logic, &$suggestions) {
        if (empty($value)) {
            return ['passed' => true, 'suggestions' => $suggestions]; // Skip validation if empty
        }
        
        // Type validation
        if (isset($logic['type'])) {
            switch ($logic['type']) {
                case 'integer':
                    if (!is_numeric($value) || !is_int((int)$value)) {
                        $suggestions[] = "Must be a whole number";
                        return ['passed' => false, 'suggestions' => $suggestions];
                    }
                    break;
                case 'decimal':
                    if (!is_numeric($value)) {
                        $suggestions[] = "Must be a valid number";
                        return ['passed' => false, 'suggestions' => $suggestions];
                    }
                    break;
            }
        }
        
        // Range validation
        if (isset($logic['min']) && $value < $logic['min']) {
            $suggestions[] = "Must be at least {$logic['min']}";
            return ['passed' => false, 'suggestions' => $suggestions];
        }
        if (isset($logic['max']) && $value > $logic['max']) {
            $suggestions[] = "Must not exceed {$logic['max']}";
            return ['passed' => false, 'suggestions' => $suggestions];
        }
        
        // Pattern validation
        if (isset($logic['pattern']) && !preg_match('/' . $logic['pattern'] . '/', $value)) {
            $suggestions[] = "Format doesn't match expected pattern";
            return ['passed' => false, 'suggestions' => $suggestions];
        }
        
        return ['passed' => true, 'suggestions' => $suggestions];
    }
    
    /**
     * Validate logic rules (relationships between fields)
     */
    private function validateLogic($value, $logic, $assetData, &$suggestions) {
        // Brand-equipment compatibility check
        if (isset($logic['check_brand_makes_equipment']) && $logic['check_brand_makes_equipment']) {
            if (!empty($assetData['brand_id']) && !empty($assetData['equipment_type_id'])) {
                // This would involve checking against a brand-equipment compatibility matrix
                // For now, we'll implement a basic check
                return $this->checkBrandEquipmentCompatibility($assetData['brand_id'], $assetData['equipment_type_id'], $suggestions);
            }
        }
        
        // Discipline-equipment match check
        if (isset($logic['check_discipline_equipment_match']) && $logic['check_discipline_equipment_match']) {
            if (!empty($assetData['discipline_tags']) && !empty($assetData['equipment_type_id'])) {
                return $this->checkDisciplineEquipmentMatch($assetData['discipline_tags'], $assetData['equipment_type_id'], $suggestions);
            }
        }
        
        // Critical equipment discipline check
        if (isset($logic['check_critical_equipment_discipline']) && $logic['check_critical_equipment_discipline']) {
            return $this->checkCriticalEquipmentDiscipline($assetData, $suggestions);
        }
        
        // Sub-discipline consistency check
        if (isset($logic['check_sub_discipline_parent']) && $logic['check_sub_discipline_parent']) {
            return $this->checkSubDisciplineConsistency($assetData['discipline_tags'] ?? '', $suggestions);
        }
        
        // Quantity vs equipment type check
        if (isset($logic['check_quantity_vs_equipment']) && $logic['check_quantity_vs_equipment']) {
            return $this->checkQuantityVsEquipment($assetData, $suggestions);
        }
        
        return ['passed' => true, 'suggestions' => $suggestions];
    }
    
    /**
     * Validate cost reasonableness
     */
    private function validateCost($value, $logic, $assetData, &$suggestions) {
        if (empty($value) || !is_numeric($value)) {
            return ['passed' => true, 'suggestions' => $suggestions]; // Skip if no cost provided
        }
        
        $cost = (float)$value;
        
        // Basic range check
        if (isset($logic['min']) && $cost < $logic['min']) {
            $suggestions[] = "Cost seems unusually low for this type of equipment";
            return ['passed' => false, 'suggestions' => $suggestions];
        }
        if (isset($logic['max']) && $cost > $logic['max']) {
            $suggestions[] = "Cost seems unusually high - please verify";
            return ['passed' => false, 'suggestions' => $suggestions];
        }
        
        // Equipment context-based cost check
        if (isset($logic['check_equipment_context']) && $logic['check_equipment_context']) {
            return $this->validateCostWithContext($cost, $logic, $assetData, $suggestions);
        }
        
        // Category-based cost check
        if (isset($logic['check_against_category']) && !empty($assetData['category_id'])) {
            return $this->checkCostAgainstCategory($cost, $assetData['category_id'], $suggestions);
        }
        
        return ['passed' => true, 'suggestions' => $suggestions];
    }
    
    /**
     * Validate for duplicates
     */
    private function validateDuplicate($value, $logic, $assetData, &$suggestions) {
        // Check for duplicate serial numbers
        if (!empty($value) && !empty($assetData['id'])) {
            $stmt = $this->db->prepare("SELECT id, ref, name FROM assets WHERE serial_number = ? AND id != ? LIMIT 1");
            $stmt->execute([$value, $assetData['id']]);
            $duplicate = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($duplicate) {
                $suggestions[] = "Similar serial number found in asset {$duplicate['ref']}: {$duplicate['name']}";
                return ['passed' => false, 'suggestions' => $suggestions];
            }
        }
        
        return ['passed' => true, 'suggestions' => $suggestions];
    }
    
    /**
     * Check brand-equipment compatibility
     */
    private function checkBrandEquipmentCompatibility($brandId, $equipmentTypeId, &$suggestions) {
        // Get brand and equipment type names for more intelligent checking
        $stmt = $this->db->prepare("
            SELECT b.official_name as brand_name, et.name as equipment_name
            FROM asset_brands b, equipment_types et 
            WHERE b.id = ? AND et.id = ?
        ");
        $stmt->execute([$brandId, $equipmentTypeId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            // Basic keyword matching for common incompatibilities
            $brandName = strtolower($data['brand_name']);
            $equipmentName = strtolower($data['equipment_name']);
            
            // Add specific brand-equipment logic here
            // This is a simplified example - in production, you'd have a comprehensive matrix
            $knownIncompatibilities = [
                'dewalt' => ['welding', 'concrete mixer'],
                'makita' => ['welding equipment', 'concrete pump'],
                'bosch' => ['excavator', 'bulldozer']
            ];
            
            foreach ($knownIncompatibilities as $brand => $incompatibleItems) {
                if (strpos($brandName, $brand) !== false) {
                    foreach ($incompatibleItems as $item) {
                        if (strpos($equipmentName, $item) !== false) {
                            $suggestions[] = "{$data['brand_name']} typically doesn't manufacture {$data['equipment_name']}";
                            return ['passed' => false, 'suggestions' => $suggestions];
                        }
                    }
                }
            }
        }
        
        return ['passed' => true, 'suggestions' => $suggestions];
    }
    
    /**
     * Check discipline-equipment match with context-aware logic
     */
    private function checkDisciplineEquipmentMatch($disciplineTags, $equipmentTypeId, &$suggestions) {
        // Get equipment type details
        $stmt = $this->db->prepare("SELECT name, category_id FROM equipment_types WHERE id = ?");
        $stmt->execute([$equipmentTypeId]);
        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$equipment) {
            return ['passed' => true, 'suggestions' => $suggestions];
        }
        
        $equipmentName = strtolower($equipment['name']);
        $disciplines = empty($disciplineTags) ? [] : array_map('trim', explode(',', $disciplineTags));
        
        // Get equipment discipline requirement level
        $requirementLevel = $this->getEquipmentDisciplineRequirement($equipmentName);
        
        switch ($requirementLevel) {
            case 'required':
                // Critical equipment must have disciplines
                if (empty($disciplines)) {
                    $suggestions[] = "This type of equipment requires discipline assignment for proper categorization";
                    return ['passed' => false, 'suggestions' => $suggestions];
                }
                break;
                
            case 'recommended':
                // Recommended but not required - give info level suggestion
                if (empty($disciplines)) {
                    $suggestions[] = "Consider assigning disciplines to improve categorization and searchability";
                    // Don't fail validation, just suggest
                }
                break;
                
            case 'optional':
                // General tools - disciplines are optional, no validation needed
                if (empty($disciplines)) {
                    return ['passed' => true, 'suggestions' => $suggestions];
                }
                break;
        }
        
        // If disciplines are assigned, validate they make sense
        if (!empty($disciplines)) {
            return $this->validateDisciplineEquipmentCompatibility($disciplines, $equipmentName, $suggestions);
        }
        
        return ['passed' => true, 'suggestions' => $suggestions];
    }
    
    /**
     * Determine discipline requirement level for equipment type
     */
    private function getEquipmentDisciplineRequirement($equipmentName) {
        // Critical equipment that MUST have disciplines
        $criticalEquipment = [
            'generator', 'transformer', 'panel', 'switchgear', 'motor', // Electrical
            'pump', 'compressor', 'engine', 'turbine', 'boiler',        // Mechanical  
            'crane', 'excavator', 'bulldozer', 'loader', 'concrete',    // Civil/Heavy
            'welding', 'cutting', 'steel', 'rebar', 'structural'        // Structural
        ];
        
        // Equipment where disciplines are recommended but not critical
        $recommendedEquipment = [
            'tool', 'drill', 'saw', 'grinder', 'mixer',
            'meter', 'tester', 'gauge', 'sensor', 'monitor'
        ];
        
        // Check critical equipment first
        foreach ($criticalEquipment as $critical) {
            if (strpos($equipmentName, $critical) !== false) {
                return 'required';
            }
        }
        
        // Check recommended equipment
        foreach ($recommendedEquipment as $recommended) {
            if (strpos($equipmentName, $recommended) !== false) {
                return 'recommended';
            }
        }
        
        // Default to optional for general tools/supplies
        return 'optional';
    }
    
    /**
     * Validate discipline-equipment compatibility with enhanced logic
     */
    private function validateDisciplineEquipmentCompatibility($disciplines, $equipmentName, &$suggestions) {
        // Enhanced discipline-equipment associations
        $disciplineEquipment = [
            'EL' => [
                'primary' => ['electrical', 'generator', 'transformer', 'motor', 'cable', 'panel', 'switch'],
                'secondary' => ['pump', 'crane', 'hoist'] // Can also be electrical
            ],
            'ME' => [
                'primary' => ['mechanical', 'pump', 'engine', 'compressor', 'turbine', 'boiler', 'hvac'],
                'secondary' => ['generator', 'crane', 'hoist'] // Can also be mechanical
            ],
            'CV' => [
                'primary' => ['concrete', 'rebar', 'formwork', 'excavator', 'bulldozer', 'crane', 'loader'],
                'secondary' => ['pump', 'generator'] // Used in civil work
            ],
            'ST' => [
                'primary' => ['steel', 'welding', 'cutting', 'structural', 'beam', 'column'],
                'secondary' => ['crane', 'hoist'] // Used for structural work
            ],
            'AR' => [
                'primary' => ['door', 'window', 'ceiling', 'flooring', 'paint', 'tiles'],
                'secondary' => ['tool'] // Architectural tools
            ],
            'PL' => [
                'primary' => ['pipe', 'valve', 'fitting', 'drain', 'water', 'sewage'],
                'secondary' => ['pump', 'tool'] // Plumbing related
            ]
        ];
        
        $validMatches = [];
        $possibleMatches = [];
        
        foreach ($disciplines as $discipline) {
            $found = false;
            
            if (isset($disciplineEquipment[$discipline])) {
                // Check primary matches (strong association)
                foreach ($disciplineEquipment[$discipline]['primary'] as $keyword) {
                    if (strpos($equipmentName, $keyword) !== false) {
                        $validMatches[] = $discipline;
                        $found = true;
                        break;
                    }
                }
                
                // Check secondary matches (possible association)
                if (!$found && isset($disciplineEquipment[$discipline]['secondary'])) {
                    foreach ($disciplineEquipment[$discipline]['secondary'] as $keyword) {
                        if (strpos($equipmentName, $keyword) !== false) {
                            $possibleMatches[] = $discipline;
                            $found = true;
                            break;
                        }
                    }
                }
            }
        }
        
        // Evaluation logic
        if (!empty($validMatches)) {
            // Perfect match found
            return ['passed' => true, 'suggestions' => $suggestions];
        } elseif (!empty($possibleMatches)) {
            // Possible match - give informational suggestion
            $suggestions[] = "Discipline assignment looks reasonable for this equipment type";
            return ['passed' => true, 'suggestions' => $suggestions];
        } else {
            // No clear match, but might be valid for multi-discipline equipment
            if (count($disciplines) > 1) {
                $suggestions[] = "Multiple disciplines assigned - please verify this equipment is used across these disciplines";
                return ['passed' => true, 'suggestions' => $suggestions]; // Don't fail, just warn
            } else {
                $suggestions[] = "Please verify the discipline assignment is appropriate for this equipment type";
                return ['passed' => false, 'suggestions' => $suggestions];
            }
        }
    }
    
    /**
     * Calculate context-aware scores with intelligent weighting
     */
    private function calculateContextAwareScores($assetData, $validationResults) {
        $equipmentName = '';
        if (!empty($assetData['equipment_type_id'])) {
            $stmt = $this->db->prepare("SELECT name FROM equipment_types WHERE id = ?");
            $stmt->execute([$assetData['equipment_type_id']]);
            $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
            $equipmentName = $equipment ? strtolower($equipment['name']) : '';
        }
        
        // Determine equipment criticality for weighting
        $equipmentCriticality = $this->getEquipmentCriticality($equipmentName);
        $disciplineRequirement = $this->getEquipmentDisciplineRequirement($equipmentName);
        
        // Rule weights based on severity and context
        $ruleWeights = [
            'error' => 3.0,      // Critical rules have highest weight
            'warning' => 2.0,    // Important rules
            'info' => 1.0        // Informational rules
        ];
        
        // Context adjustments
        $contextMultipliers = [
            'critical' => 1.2,   // Critical equipment needs higher quality
            'important' => 1.0,  // Standard scoring
            'basic' => 0.8       // Basic tools can have lower standards
        ];
        
        $totalWeightedScore = 0;
        $totalPossibleScore = 0;
        $completenessWeightedScore = 0;
        $completenessWeightedTotal = 0;
        $accuracyWeightedScore = 0;
        $accuracyWeightedTotal = 0;
        
        foreach ($validationResults as $result) {
            $baseWeight = $ruleWeights[$result['severity']] ?? 1.0;
            $contextMultiplier = $contextMultipliers[$equipmentCriticality] ?? 1.0;
            
            // Special handling for discipline rules
            if ($result['rule_name'] === 'discipline_equipment_match') {
                if ($disciplineRequirement === 'optional') {
                    $contextMultiplier *= 0.5; // Reduce weight for optional disciplines
                } elseif ($disciplineRequirement === 'required') {
                    $contextMultiplier *= 1.5; // Increase weight for required disciplines
                }
            }
            
            $finalWeight = $baseWeight * $contextMultiplier;
            $totalPossibleScore += $finalWeight;
            
            if ($result['passed']) {
                $totalWeightedScore += $finalWeight;
            }
            
            // Category-specific scoring
            $ruleCategory = $this->getRuleCategory($result['rule_name']);
            if ($ruleCategory === 'completeness') {
                $completenessWeightedTotal += $finalWeight;
                if ($result['passed']) {
                    $completenessWeightedScore += $finalWeight;
                }
            } elseif ($ruleCategory === 'accuracy') {
                $accuracyWeightedTotal += $finalWeight;
                if ($result['passed']) {
                    $accuracyWeightedScore += $finalWeight;
                }
            }
        }
        
        // Calculate final scores
        $overallScore = $totalPossibleScore > 0 ? round(($totalWeightedScore / $totalPossibleScore) * 100, 2) : 100;
        $completenessScore = $completenessWeightedTotal > 0 ? round(($completenessWeightedScore / $completenessWeightedTotal) * 100, 2) : 100;
        $accuracyScore = $accuracyWeightedTotal > 0 ? round(($accuracyWeightedScore / $accuracyWeightedTotal) * 100, 2) : 100;
        
        return [
            'overall' => $overallScore,
            'completeness' => $completenessScore,
            'accuracy' => $accuracyScore,
            'context' => [
                'equipment_criticality' => $equipmentCriticality,
                'discipline_requirement' => $disciplineRequirement,
                'scoring_approach' => $this->getScoreExplanation($equipmentCriticality, $disciplineRequirement)
            ]
        ];
    }
    
    /**
     * Determine equipment criticality for scoring context
     */
    private function getEquipmentCriticality($equipmentName) {
        // Critical equipment requiring highest standards
        $criticalEquipment = [
            'crane', 'excavator', 'bulldozer', 'generator', 'transformer', 
            'boiler', 'turbine', 'compressor', 'welding', 'cutting'
        ];
        
        // Important equipment with standard requirements  
        $importantEquipment = [
            'pump', 'motor', 'drill', 'saw', 'grinder', 'mixer',
            'meter', 'tester', 'gauge', 'hoist', 'loader'
        ];
        
        foreach ($criticalEquipment as $critical) {
            if (strpos($equipmentName, $critical) !== false) {
                return 'critical';
            }
        }
        
        foreach ($importantEquipment as $important) {
            if (strpos($equipmentName, $important) !== false) {
                return 'important';
            }
        }
        
        return 'basic'; // General tools and supplies
    }
    
    /**
     * Get rule category for scoring
     */
    private function getRuleCategory($ruleName) {
        $completenessRules = [
            'equipment_type_required', 'category_required', 'project_required', 
            'asset_name_required', 'location_specified', 'description_quality'
        ];
        
        $accuracyRules = [
            'quantity_valid', 'serial_format_check', 'cost_reasonableness',
            'brand_equipment_compatibility', 'discipline_equipment_match'
        ];
        
        if (in_array($ruleName, $completenessRules)) {
            return 'completeness';
        } elseif (in_array($ruleName, $accuracyRules)) {
            return 'accuracy';
        }
        
        return 'other';
    }
    
    /**
     * Get contextual explanation for scoring approach
     */
    private function getScoreExplanation($criticality, $disciplineRequirement) {
        $explanations = [
            'critical' => [
                'required' => 'Critical equipment with strict discipline requirements - highest quality standards applied',
                'recommended' => 'Critical equipment with flexible disciplines - high quality standards with some tolerance', 
                'optional' => 'Critical equipment with optional disciplines - focus on core specifications'
            ],
            'important' => [
                'required' => 'Standard equipment with discipline requirements - balanced scoring approach',
                'recommended' => 'Standard equipment with flexible disciplines - standard quality expectations',
                'optional' => 'Standard equipment with optional disciplines - core fields prioritized'
            ],
            'basic' => [
                'required' => 'Basic equipment with discipline needs - reasonable quality expectations',
                'recommended' => 'Basic equipment with some categorization - flexible standards',
                'optional' => 'General tools and supplies - essential fields only'
            ]
        ];
        
        return $explanations[$criticality][$disciplineRequirement] ?? 'Standard quality assessment applied';
    }
    
    /**
     * Validate contextual requirements (equipment type or cost-based)
     */
    private function validateContextualRequirement($value, $logic, $assetData, &$suggestions) {
        // Check if requirement applies to this equipment type
        if (isset($logic['required_for_equipment_types']) && !empty($assetData['equipment_type_id'])) {
            $stmt = $this->db->prepare("SELECT name FROM equipment_types WHERE id = ?");
            $stmt->execute([$assetData['equipment_type_id']]);
            $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($equipment) {
                $equipmentName = strtolower($equipment['name']);
                foreach ($logic['required_for_equipment_types'] as $requiredType) {
                    if (strpos($equipmentName, strtolower($requiredType)) !== false) {
                        if (empty($value)) {
                            $suggestions[] = "This field is recommended for {$requiredType} equipment";
                            return ['passed' => false, 'suggestions' => $suggestions];
                        }
                        break;
                    }
                }
            }
        }
        
        // Check if requirement applies based on cost
        if (isset($logic['required_for_cost_above']) && !empty($assetData['acquisition_cost'])) {
            $cost = (float)$assetData['acquisition_cost'];
            if ($cost >= $logic['required_for_cost_above'] && empty($value)) {
                $suggestions[] = "This field is recommended for high-value equipment";
                return ['passed' => false, 'suggestions' => $suggestions];
            }
        }
        
        return ['passed' => true, 'suggestions' => $suggestions];
    }
    
    /**
     * Check critical equipment discipline requirements
     */
    private function checkCriticalEquipmentDiscipline($assetData, &$suggestions) {
        if (empty($assetData['equipment_type_id'])) {
            return ['passed' => true, 'suggestions' => $suggestions];
        }
        
        $stmt = $this->db->prepare("SELECT name FROM equipment_types WHERE id = ?");
        $stmt->execute([$assetData['equipment_type_id']]);
        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($equipment) {
            $equipmentName = strtolower($equipment['name']);
            $requirementLevel = $this->getEquipmentDisciplineRequirement($equipmentName);
            
            if ($requirementLevel === 'required' && empty($assetData['discipline_tags'])) {
                $suggestions[] = "This type of critical equipment should have discipline assignment for proper categorization";
                return ['passed' => false, 'suggestions' => $suggestions];
            }
        }
        
        return ['passed' => true, 'suggestions' => $suggestions];
    }
    
    /**
     * Check sub-discipline consistency with main disciplines
     */
    private function checkSubDisciplineConsistency($disciplineTags, &$suggestions) {
        if (empty($disciplineTags)) {
            return ['passed' => true, 'suggestions' => $suggestions];
        }
        
        $disciplines = array_map('trim', explode(',', $disciplineTags));
        $mainDisciplines = [];
        $subDisciplines = [];
        
        foreach ($disciplines as $disciplineCode) {
            $stmt = $this->db->prepare("SELECT name, parent_id, code FROM asset_disciplines WHERE code = ? OR iso_code = ?");
            $stmt->execute([$disciplineCode, $disciplineCode]);
            $discipline = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($discipline) {
                if ($discipline['parent_id'] === null) {
                    $mainDisciplines[] = $discipline['code'];
                } else {
                    $subDisciplines[] = [
                        'code' => $discipline['code'],
                        'parent_id' => $discipline['parent_id'],
                        'name' => $discipline['name']
                    ];
                }
            }
        }
        
        // Check if sub-disciplines have corresponding main disciplines
        foreach ($subDisciplines as $subDiscipline) {
            $stmt = $this->db->prepare("SELECT code FROM asset_disciplines WHERE id = ?");
            $stmt->execute([$subDiscipline['parent_id']]);
            $parent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($parent && !in_array($parent['code'], $mainDisciplines)) {
                $suggestions[] = "Sub-discipline '{$subDiscipline['name']}' should have its main discipline included";
                return ['passed' => false, 'suggestions' => $suggestions];
            }
        }
        
        return ['passed' => true, 'suggestions' => $suggestions];
    }
    
    /**
     * Check quantity reasonableness vs equipment type
     */
    private function checkQuantityVsEquipment($assetData, &$suggestions) {
        if (empty($assetData['quantity']) || empty($assetData['equipment_type_id'])) {
            return ['passed' => true, 'suggestions' => $suggestions];
        }
        
        $quantity = (int)$assetData['quantity'];
        $stmt = $this->db->prepare("SELECT name FROM equipment_types WHERE id = ?");
        $stmt->execute([$assetData['equipment_type_id']]);
        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($equipment) {
            $equipmentName = strtolower($equipment['name']);
            
            // Typically single-unit equipment
            $singleUnitEquipment = ['crane', 'excavator', 'bulldozer', 'generator', 'transformer', 'compressor'];
            // Can be multiple units
            $multipleUnitEquipment = ['tool', 'bolt', 'screw', 'nail', 'pipe', 'cable', 'wire'];
            
            foreach ($singleUnitEquipment as $singleUnit) {
                if (strpos($equipmentName, $singleUnit) !== false && $quantity > 5) {
                    $suggestions[] = "Large quantity for this equipment type - please verify this is correct";
                    return ['passed' => false, 'suggestions' => $suggestions];
                }
            }
            
            foreach ($multipleUnitEquipment as $multiUnit) {
                if (strpos($equipmentName, $multiUnit) !== false && $quantity === 1) {
                    $suggestions[] = "Consider if quantity should be higher for this consumable/bulk item";
                    return ['passed' => true, 'suggestions' => $suggestions]; // Just suggest, don't fail
                }
            }
        }
        
        return ['passed' => true, 'suggestions' => $suggestions];
    }
    
    /**
     * Enhanced cost validation with equipment context
     */
    private function validateCostWithContext($value, $logic, $assetData, &$suggestions) {
        if (empty($value) || !is_numeric($value)) {
            return ['passed' => true, 'suggestions' => $suggestions]; // Skip if no cost provided
        }
        
        $cost = (float)$value;
        
        // Get equipment context for better cost validation
        if (!empty($assetData['equipment_type_id'])) {
            $stmt = $this->db->prepare("SELECT name FROM equipment_types WHERE id = ?");
            $stmt->execute([$assetData['equipment_type_id']]);
            $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($equipment) {
                $equipmentName = strtolower($equipment['name']);
                $criticality = $this->getEquipmentCriticality($equipmentName);
                
                // Adjust cost expectations based on equipment criticality
                $costRanges = [
                    'critical' => ['min' => 10000, 'max' => 5000000],
                    'important' => ['min' => 1000, 'max' => 1000000], 
                    'basic' => ['min' => 100, 'max' => 100000]
                ];
                
                $range = $costRanges[$criticality] ?? $costRanges['important'];
                
                if ($cost < $range['min']) {
                    $suggestions[] = "Cost seems low for this type of equipment - please verify";
                    return ['passed' => false, 'suggestions' => $suggestions];
                } elseif ($cost > $range['max']) {
                    $suggestions[] = "Cost seems high for this type of equipment - please verify";
                    return ['passed' => false, 'suggestions' => $suggestions];
                }
            }
        }
        
        // Fall back to original cost validation
        return $this->validateCost($value, $logic, $assetData, $suggestions);
    }
    
    /**
     * Validate context-specific rules
     */
    private function validateContext($value, $logic, $assetData, &$suggestions) {
        // This is a generic handler for context-based validation
        // that can be extended for specific use cases
        
        if (isset($logic['check_equipment_context'])) {
            return $this->validateCostWithContext($value, $logic, $assetData, $suggestions);
        }
        
        return ['passed' => true, 'suggestions' => $suggestions];
    }
    
    /**
     * Check cost against category averages
     */
    private function checkCostAgainstCategory($cost, $categoryId, &$suggestions) {
        // Get average cost for this category
        $stmt = $this->db->prepare("
            SELECT AVG(acquisition_cost) as avg_cost, COUNT(*) as count
            FROM assets 
            WHERE category_id = ? AND acquisition_cost > 0
        ");
        $stmt->execute([$categoryId]);
        $avgData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($avgData && $avgData['count'] >= 3) { // Only if we have enough data points
            $avgCost = (float)$avgData['avg_cost'];
            $deviation = abs($cost - $avgCost) / $avgCost;
            
            if ($deviation > 2.0) { // More than 200% deviation
                $suggestions[] = sprintf("Cost varies significantly from category average of ₱%s", number_format($avgCost, 2));
                return ['passed' => false, 'suggestions' => $suggestions];
            }
        }
        
        return ['passed' => true, 'suggestions' => $suggestions];
    }
    
    /**
     * Get suggestions for improving asset data quality
     */
    public function getImprovementSuggestions($assetData, $validationResults) {
        $suggestions = [];
        
        // General suggestions based on validation results
        if ($validationResults['completeness_score'] < 80) {
            $suggestions[] = "Consider filling in more optional fields to improve data quality";
        }
        
        if ($validationResults['accuracy_score'] < 70) {
            $suggestions[] = "Some field values may need review - check warnings above";
        }
        
        // Specific field suggestions
        if (empty($assetData['description']) || strlen($assetData['description']) < 20) {
            $suggestions[] = "Add a detailed description including specifications, condition, and intended use";
        }
        
        if (empty($assetData['location'])) {
            $suggestions[] = "Specify the current physical location (warehouse, site area, building, etc.)";
        }
        
        if (empty($assetData['serial_number']) && !empty($assetData['equipment_type_id'])) {
            // Check if this equipment type typically has serial numbers
            $suggestions[] = "Consider adding serial number if available - helps with tracking and identification";
        }
        
        return $suggestions;
    }
}
?>