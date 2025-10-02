<?php
/**
 * ConstructLink™ Intelligent Asset Naming System
 * 
 * Eliminates redundancy by auto-generating asset names from:
 * Equipment Type + Subtype + Material + Power Source + Brand
 * 
 * Example: "Cordless Electric Drill (Metal/Wood)" instead of redundant "Drill" + "Drill Type" + "Electric"
 */

class IntelligentAssetNamer {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Generate intelligent asset name from equipment type and subtype
     * 
     * @param int $equipmentTypeId Equipment type ID
     * @param int $subtypeId Subtype ID  
     * @param string $brand Optional brand name
     * @param string $model Optional model name
     * @return array Generated name data
     */
    public function generateAssetName($equipmentTypeId, $subtypeId, $brand = null, $model = null) {
        try {
            // Get equipment type and subtype details
            $sql = "SELECT 
                        et.name as equipment_type,
                        es.subtype_name,
                        es.material_type,
                        es.power_source,
                        es.size_category,
                        es.application_area
                    FROM equipment_subtypes es
                    JOIN equipment_types et ON es.equipment_type_id = et.id
                    WHERE et.id = ? AND es.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$equipmentTypeId, $subtypeId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) {
                return $this->fallbackNaming($brand, $model);
            }
            
            // Build intelligent name components
            $nameComponents = $this->buildNameComponents($data, $brand, $model);
            
            // Generate the full asset name
            $generatedName = $this->assembleAssetName($nameComponents);
            
            return [
                'generated_name' => $generatedName,
                'name_components' => $nameComponents,
                'equipment_info' => $data
            ];
            
        } catch (Exception $e) {
            error_log("Asset name generation error: " . $e->getMessage());
            return $this->fallbackNaming($brand, $model);
        }
    }
    
    /**
     * Build name components intelligently
     */
    private function buildNameComponents($data, $brand, $model) {
        $components = [];
        
        // 1. Power Source (if relevant and not obvious)
        if ($data['power_source'] && $data['power_source'] !== 'Manual') {
            // Only add power source if it's distinctive
            $distinctivePower = ['Cordless', 'Battery', 'Pneumatic', 'Hydraulic'];
            if (in_array($data['power_source'], $distinctivePower)) {
                $components['power_source'] = $data['power_source'];
            }
        }
        
        // 2. Subtype (specific variation)
        if ($data['subtype_name'] && $data['subtype_name'] !== 'Standard') {
            $components['subtype'] = $data['subtype_name'];
        }
        
        // 3. Equipment Type (main category)
        $components['equipment_type'] = $data['equipment_type'];
        
        // 4. Material specification (in parentheses)
        if ($data['material_type']) {
            $components['material'] = $data['material_type'];
        }
        
        // 5. Size category (if specific)
        if ($data['size_category'] && 
            !in_array(strtolower($data['size_category']), ['standard', 'portable', 'compact'])) {
            $components['size'] = $data['size_category'];
        }
        
        // 6. Brand (if provided)
        if ($brand && trim($brand) !== '') {
            $components['brand'] = trim($brand);
        }
        
        // 7. Model (if provided)
        if ($model && trim($model) !== '') {
            $components['model'] = trim($model);
        }
        
        return $components;
    }
    
    /**
     * Assemble final asset name from components
     */
    private function assembleAssetName($components) {
        $nameParts = [];
        
        // Build main name: [Brand] [Power Source] [Subtype] [Equipment Type]
        if (isset($components['brand'])) {
            $nameParts[] = $components['brand'];
        }
        
        if (isset($components['power_source'])) {
            $nameParts[] = $components['power_source'];
        }
        
        if (isset($components['subtype'])) {
            $nameParts[] = $components['subtype'];
        }
        
        $nameParts[] = $components['equipment_type'];
        
        // Build specifications: (Material, Size)
        $specs = [];
        if (isset($components['material'])) {
            $specs[] = $components['material'];
        }
        if (isset($components['size'])) {
            $specs[] = $components['size'];
        }
        
        // Assemble final name
        $mainName = implode(' ', $nameParts);
        
        if (!empty($specs)) {
            $mainName .= ' (' . implode(', ', $specs) . ')';
        }
        
        // Add model if provided
        if (isset($components['model'])) {
            $mainName .= ' - ' . $components['model'];
        }
        
        return $mainName;
    }
    
    /**
     * Get appropriate unit based on equipment type and subtype
     * 
     * @param int $equipmentTypeId Equipment type ID
     * @param int $subtypeId Subtype ID
     * @return string Appropriate unit code
     */
    public function getIntelligentUnit($equipmentTypeId, $subtypeId = null) {
        try {
            // Get equipment type and subtype details
            $sql = "SELECT 
                        et.name as equipment_type,
                        c.name as category,
                        es.subtype_name,
                        es.material_type,
                        es.power_source,
                        es.size_category
                    FROM equipment_types et
                    JOIN categories c ON et.category_id = c.id
                    LEFT JOIN equipment_subtypes es ON es.equipment_type_id = et.id AND es.id = ?
                    WHERE et.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$subtypeId, $equipmentTypeId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) {
                return 'pcs'; // Default fallback
            }
            
            return $this->determineUnitFromEquipment($data);
            
        } catch (Exception $e) {
            error_log("Unit determination error: " . $e->getMessage());
            return 'pcs';
        }
    }
    
    /**
     * Determine unit based on equipment characteristics
     */
    private function determineUnitFromEquipment($equipmentData) {
        $category = strtolower($equipmentData['category']);
        $equipmentType = strtolower($equipmentData['equipment_type']);
        $subtype = strtolower($equipmentData['subtype_name'] ?? '');
        $material = strtolower($equipmentData['material_type'] ?? '');
        
        // Construction Materials - typically measured by volume, weight, or area
        if (strpos($category, 'construction materials') !== false) {
            if (strpos($equipmentType, 'concrete') !== false || 
                strpos($equipmentType, 'cement') !== false) return 'm3'; // Cubic meter
            if (strpos($equipmentType, 'steel') !== false || 
                strpos($equipmentType, 'timber') !== false) return 'kg'; // Kilogram
            if (strpos($equipmentType, 'roofing') !== false || 
                strpos($equipmentType, 'finishing') !== false) return 'sqm'; // Square meter
            if (strpos($equipmentType, 'piping') !== false) return 'm'; // Linear meter
            if (strpos($equipmentType, 'electrical materials') !== false) return 'pcs'; // Pieces
            return 'kg'; // Default for materials
        }
        
        // Wire and Cable - measured by length
        if (strpos($equipmentType, 'wire') !== false || 
            strpos($equipmentType, 'cable') !== false) return 'm';
        
        // Liquids and consumables
        if (strpos($material, 'liquid') !== false || 
            strpos($subtype, 'oil') !== false || 
            strpos($subtype, 'fuel') !== false) return 'l'; // Liters
        
        // Heavy Equipment and Vehicles - individual units
        if (strpos($category, 'heavy equipment') !== false || 
            strpos($category, 'construction vehicles') !== false ||
            strpos($equipmentType, 'excavator') !== false ||
            strpos($equipmentType, 'bulldozer') !== false ||
            strpos($equipmentType, 'crane') !== false ||
            strpos($equipmentType, 'compactor') !== false ||
            strpos($equipmentType, 'loader') !== false) return 'unit';
        
        // Tools (Hand Tools, Power Tools) - pieces
        if (strpos($category, 'hand tools') !== false || 
            strpos($category, 'power tools') !== false ||
            strpos($equipmentType, 'drill') !== false ||
            strpos($equipmentType, 'grinder') !== false ||
            strpos($equipmentType, 'saw') !== false ||
            strpos($equipmentType, 'hammer') !== false ||
            strpos($equipmentType, 'wrench') !== false) return 'pcs';
        
        // Welding Equipment - individual units
        if (strpos($category, 'welding equipment') !== false) return 'unit';
        
        // Safety Equipment - pieces for PPE, sets for systems
        if (strpos($category, 'safety equipment') !== false) {
            if (strpos($equipmentType, 'personal protective') !== false ||
                strpos($equipmentType, 'ppe') !== false) return 'pcs';
            if (strpos($equipmentType, 'fall protection') !== false ||
                strpos($equipmentType, 'barricade') !== false) return 'set';
            return 'pcs';
        }
        
        // IT Equipment - individual pieces
        if (strpos($category, 'it equipment') !== false ||
            strpos($equipmentType, 'computer') !== false ||
            strpos($equipmentType, 'laptop') !== false ||
            strpos($equipmentType, 'printer') !== false) return 'unit';
        
        // Office Furniture - individual pieces
        if (strpos($category, 'office furniture') !== false) return 'unit';
        
        // Measuring Instruments - individual pieces
        if (strpos($category, 'measuring instruments') !== false) return 'unit';
        
        // Test Equipment - individual pieces
        if (strpos($category, 'test equipment') !== false) return 'unit';
        
        // Electrical Supplies - pieces for most items
        if (strpos($category, 'electrical supplies') !== false) {
            if (strpos($equipmentType, 'panel') !== false) return 'unit';
            if (strpos($equipmentType, 'lighting') !== false) return 'pcs';
            return 'pcs';
        }
        
        // Small consumable items - boxes
        if (strpos($subtype, 'screw') !== false ||
            strpos($subtype, 'nail') !== false ||
            strpos($subtype, 'bolt') !== false ||
            strpos($subtype, 'fastener') !== false) return 'box';
        
        // Set items (things that come in matched groups)
        if (strpos($subtype, 'set') !== false ||
            strpos($equipmentType, 'kit') !== false) return 'set';
        
        // Default to pieces for countable items
        return 'pcs';
    }

    /**
     * Get intelligent suggestions based on partial input
     * 
     * @param string $partialName Partial asset name
     * @param int|null $categoryId Category filter
     * @return array Suggestions with confidence scores
     */
    public function getSuggestions($partialName, $categoryId = null) {
        try {
            $partialLower = strtolower(trim($partialName));
            
            if (strlen($partialLower) < 2) {
                return [];
            }
            
            // Build query to find matching equipment
            $whereConditions = ["et.is_active = 1", "es.is_active = 1"];
            $params = [];
            
            if ($categoryId) {
                $whereConditions[] = "et.category_id = ?";
                $params[] = $categoryId;
            }
            
            $sql = "SELECT 
                        et.id as equipment_type_id,
                        et.name as equipment_type,
                        es.id as subtype_id,
                        es.subtype_name,
                        es.material_type,
                        es.power_source,
                        es.size_category,
                        es.application_area,
                        c.name as category_name
                    FROM equipment_subtypes es
                    JOIN equipment_types et ON es.equipment_type_id = et.id
                    JOIN categories c ON et.category_id = c.id
                    WHERE " . implode(" AND ", $whereConditions) . "
                    ORDER BY et.name, es.subtype_name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $suggestions = [];
            
            foreach ($results as $result) {
                $confidence = $this->calculateMatchConfidence($partialLower, $result);
                
                if ($confidence > 0.1) { // Only suggest if confidence > 10%
                    $nameData = $this->generateAssetName(
                        $result['equipment_type_id'], 
                        $result['subtype_id']
                    );
                    
                    $suggestions[] = [
                        'equipment_type_id' => $result['equipment_type_id'],
                        'subtype_id' => $result['subtype_id'],
                        'generated_name' => $nameData['generated_name'],
                        'confidence' => $confidence,
                        'category_name' => $result['category_name'],
                        'application_area' => $result['application_area']
                    ];
                }
            }
            
            // Sort by confidence (descending)
            usort($suggestions, function($a, $b) {
                return $b['confidence'] <=> $a['confidence'];
            });
            
            return array_slice($suggestions, 0, 5); // Return top 5
            
        } catch (Exception $e) {
            error_log("Asset name suggestions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate confidence score for name matching
     */
    private function calculateMatchConfidence($partialName, $equipmentData) {
        $score = 0.0;
        
        // Check equipment type match
        $equipmentTypeLower = strtolower($equipmentData['equipment_type']);
        if (strpos($equipmentTypeLower, $partialName) !== false || 
            strpos($partialName, $equipmentTypeLower) !== false) {
            $score += 0.8;
        }
        
        // Check subtype match
        $subtypeLower = strtolower($equipmentData['subtype_name']);
        if (strpos($subtypeLower, $partialName) !== false || 
            strpos($partialName, $subtypeLower) !== false) {
            $score += 0.7;
        }
        
        // Check power source match
        if ($equipmentData['power_source']) {
            $powerLower = strtolower($equipmentData['power_source']);
            if (strpos($powerLower, $partialName) !== false || 
                strpos($partialName, $powerLower) !== false) {
                $score += 0.5;
            }
        }
        
        // Check material match
        if ($equipmentData['material_type']) {
            $materialLower = strtolower($equipmentData['material_type']);
            if (strpos($materialLower, $partialName) !== false || 
                strpos($partialName, $materialLower) !== false) {
                $score += 0.3;
            }
        }
        
        // Check application area
        if ($equipmentData['application_area']) {
            $appLower = strtolower($equipmentData['application_area']);
            if (strpos($appLower, $partialName) !== false) {
                $score += 0.2;
            }
        }
        
        // Word matching bonus
        $partialWords = explode(' ', $partialName);
        $allText = strtolower(implode(' ', [
            $equipmentData['equipment_type'],
            $equipmentData['subtype_name'],
            $equipmentData['power_source'] ?? '',
            $equipmentData['material_type'] ?? ''
        ]));
        
        foreach ($partialWords as $word) {
            if (strlen($word) > 2 && strpos($allText, $word) !== false) {
                $score += 0.1;
            }
        }
        
        return min($score, 1.0); // Cap at 1.0
    }
    
    /**
     * Fallback naming when equipment data is not available
     */
    private function fallbackNaming($brand, $model) {
        $nameParts = [];
        
        if ($brand) {
            $nameParts[] = $brand;
        }
        
        if ($model) {
            $nameParts[] = $model;
        }
        
        $fallbackName = !empty($nameParts) ? implode(' ', $nameParts) : 'Asset';
        
        return [
            'generated_name' => $fallbackName,
            'name_components' => [
                'brand' => $brand,
                'model' => $model
            ],
            'equipment_info' => null
        ];
    }
    
    /**
     * Update existing asset with generated name
     * 
     * @param int $assetId Asset ID
     * @param array $nameData Generated name data
     * @return bool Success status
     */
    public function updateAssetName($assetId, $nameData) {
        try {
            $sql = "UPDATE assets 
                    SET generated_name = ?, 
                        name_components = ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $nameData['generated_name'],
                json_encode($nameData['name_components']),
                $assetId
            ]);
            
        } catch (Exception $e) {
            error_log("Asset name update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get equipment types by category for form population
     * 
     * @param int $categoryId Category ID
     * @return array Equipment types
     */
    public function getEquipmentTypesByCategory($categoryId) {
        $sql = "SELECT et.id, et.name, et.description, et.category_id,
                       c.name as category_name
                FROM equipment_types et 
                JOIN categories c ON et.category_id = c.id 
                WHERE et.category_id = ? AND et.is_active = 1 
                ORDER BY et.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get subtypes by equipment type for form population
     * 
     * @param int $equipmentTypeId Equipment type ID
     * @return array Subtypes
     */
    public function getSubtypesByEquipmentType($equipmentTypeId) {
        $sql = "SELECT id, subtype_name, material_type, power_source, 
                       size_category, application_area 
                FROM equipment_subtypes 
                WHERE equipment_type_id = ? AND is_active = 1 
                ORDER BY subtype_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$equipmentTypeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get equipment type details including category for reverse lookup
     * 
     * @param int $equipmentTypeId Equipment type ID
     * @return array Equipment type details with category info
     */
    public function getEquipmentTypeDetails($equipmentTypeId) {
        $sql = "SELECT et.id, et.name, et.description, et.category_id,
                       c.name as category_name, c.description as category_description
                FROM equipment_types et
                LEFT JOIN categories c ON et.category_id = c.id
                WHERE et.id = ? AND et.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$equipmentTypeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return null;
        }
        
        // Also get subtypes for this equipment type
        $subtypes = $this->getSubtypesByEquipmentType($equipmentTypeId);
        $result['subtypes'] = $subtypes;
        
        return $result;
    }
}
?>