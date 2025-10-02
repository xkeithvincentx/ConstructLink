<?php
/**
 * ConstructLink™ Asset Subtype Management System
 * 
 * Provides intelligent subtype suggestions based on:
 * - Asset category
 * - Primary discipline
 * - Asset name patterns
 * - Brand specifications
 */

class AssetSubtypeManager {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get equipment types for a specific category
     * 
     * @param int $categoryId Category ID
     * @return array Equipment types
     */
    public function getEquipmentTypesByCategory($categoryId) {
        $sql = "SELECT id, name, code, description 
                FROM asset_equipment_types 
                WHERE category_id = ? AND is_active = 1 
                ORDER BY name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get subtypes for a specific equipment type
     * 
     * @param int $equipmentTypeId Equipment type ID
     * @return array Asset subtypes
     */
    public function getSubtypesByEquipmentType($equipmentTypeId) {
        $sql = "SELECT id, name, code, technical_name, description, discipline_tags 
                FROM asset_subtypes 
                WHERE equipment_type_id = ? AND is_active = 1 
                ORDER BY name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$equipmentTypeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get intelligent subtype suggestions based on asset name and discipline
     * 
     * @param string $assetName Asset name to analyze
     * @param int|null $categoryId Category ID
     * @param int|null $disciplineId Primary discipline ID
     * @return array Suggested subtypes with confidence scores
     */
    public function getSuggestedSubtypes($assetName, $categoryId = null, $disciplineId = null) {
        $suggestions = [];
        $assetNameLower = strtolower($assetName);
        
        // Get discipline name for matching
        $disciplineName = null;
        if ($disciplineId) {
            $stmt = $this->db->prepare("SELECT name FROM asset_disciplines WHERE id = ?");
            $stmt->execute([$disciplineId]);
            $discipline = $stmt->fetch(PDO::FETCH_ASSOC);
            $disciplineName = $discipline ? $discipline['name'] : null;
        }
        
        // Build query conditions
        $whereConditions = ["ast.is_active = 1"];
        $params = [];
        
        if ($categoryId) {
            $whereConditions[] = "aet.category_id = ?";
            $params[] = $categoryId;
        }
        
        $sql = "SELECT 
                    ast.id,
                    ast.name,
                    ast.code,
                    ast.technical_name,
                    ast.description,
                    ast.discipline_tags,
                    aet.name as equipment_type_name,
                    c.name as category_name
                FROM asset_subtypes ast
                JOIN asset_equipment_types aet ON ast.equipment_type_id = aet.id
                JOIN categories c ON aet.category_id = c.id
                WHERE " . implode(" AND ", $whereConditions) . "
                ORDER BY ast.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $subtypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($subtypes as $subtype) {
            $confidence = $this->calculateConfidenceScore(
                $assetNameLower, 
                $subtype, 
                $disciplineName
            );
            
            if ($confidence > 0.1) { // Only include if confidence > 10%
                $subtype['confidence'] = $confidence;
                $suggestions[] = $subtype;
            }
        }
        
        // Sort by confidence score (descending)
        usort($suggestions, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        return array_slice($suggestions, 0, 5); // Return top 5 suggestions
    }
    
    /**
     * Calculate confidence score for subtype suggestion
     * 
     * @param string $assetName Asset name (lowercase)
     * @param array $subtype Subtype data
     * @param string|null $disciplineName Primary discipline name
     * @return float Confidence score (0-1)
     */
    private function calculateConfidenceScore($assetName, $subtype, $disciplineName) {
        $score = 0.0;
        
        // Name matching patterns
        $namePatterns = [
            // Welding patterns
            'mig' => ['MIG'],
            'tig' => ['TIG'], 
            'stick' => ['STICK'],
            'arc' => ['MIG', 'TIG', 'STICK', 'FCAW'],
            'flux' => ['FCAW'],
            'plasma' => ['PLASMA'],
            'spot' => ['SPOT'],
            
            // Grinder patterns
            'angle grinder' => ['ANGLE'],
            'die grinder' => ['DIE'],
            'bench grinder' => ['BENCH'],
            'straight grinder' => ['STRAIGHT'],
            'grinder' => ['ANGLE', 'DIE', 'BENCH', 'STRAIGHT'],
            
            // Drill patterns
            'hammer drill' => ['HAMMER'],
            'impact drill' => ['IMPACT'],
            'rotary hammer' => ['ROTARY'],
            'core drill' => ['CORE'],
            
            // Sanding patterns
            'orbital sander' => ['ORBITAL', 'RO-SAND'],
            'belt sander' => ['BELT'],
            'disc sander' => ['DISC'],
            'random orbital' => ['RO-SAND']
        ];
        
        // Check name patterns
        foreach ($namePatterns as $pattern => $codes) {
            if (strpos($assetName, $pattern) !== false) {
                if (in_array($subtype['code'], $codes)) {
                    $score += 0.8; // High confidence for exact pattern match
                    break;
                }
            }
        }
        
        // Check if subtype name appears in asset name
        $subtypeNameLower = strtolower($subtype['name']);
        $subtypeWords = explode(' ', $subtypeNameLower);
        
        foreach ($subtypeWords as $word) {
            if (strlen($word) > 3 && strpos($assetName, $word) !== false) {
                $score += 0.4;
            }
        }
        
        // Check technical name match
        if ($subtype['technical_name']) {
            $technicalLower = strtolower($subtype['technical_name']);
            $technicalWords = explode(' ', $technicalLower);
            
            foreach ($technicalWords as $word) {
                if (strlen($word) > 3 && strpos($assetName, $word) !== false) {
                    $score += 0.3;
                }
            }
        }
        
        // Check discipline alignment
        if ($disciplineName && $subtype['discipline_tags']) {
            $disciplineTags = json_decode($subtype['discipline_tags'], true);
            if (is_array($disciplineTags)) {
                foreach ($disciplineTags as $tag) {
                    if (stripos($tag, $disciplineName) !== false || 
                        stripos($disciplineName, $tag) !== false) {
                        $score += 0.2;
                        break;
                    }
                }
            }
        }
        
        // Bonus for common equipment
        $commonEquipment = ['welder', 'welding', 'grinder', 'drill', 'sander'];
        foreach ($commonEquipment as $common) {
            if (strpos($assetName, $common) !== false) {
                $score += 0.1;
                break;
            }
        }
        
        return min($score, 1.0); // Cap at 1.0
    }
    
    /**
     * Get specification templates for a subtype
     * 
     * @param int $subtypeId Subtype ID
     * @return array Specification templates
     */
    public function getSpecificationTemplates($subtypeId) {
        $sql = "SELECT field_name, field_label, field_type, field_options, 
                       is_required, display_order, unit
                FROM asset_specification_templates 
                WHERE subtype_id = ? 
                ORDER BY display_order ASC, field_label ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$subtypeId]);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON field_options
        foreach ($templates as &$template) {
            if ($template['field_options']) {
                $template['field_options'] = json_decode($template['field_options'], true);
            }
        }
        
        return $templates;
    }
    
    /**
     * Save asset extended properties
     * 
     * @param int $assetId Asset ID
     * @param array $properties Array of property_name => value pairs
     * @return bool Success status
     */
    public function saveAssetProperties($assetId, $properties) {
        try {
            $this->db->beginTransaction();
            
            foreach ($properties as $name => $value) {
                if ($value !== null && $value !== '') {
                    $stmt = $this->db->prepare("
                        INSERT INTO asset_extended_properties (asset_id, property_name, property_value) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE property_value = VALUES(property_value)
                    ");
                    $stmt->execute([$assetId, $name, $value]);
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error saving asset properties: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get asset extended properties
     * 
     * @param int $assetId Asset ID
     * @return array Properties as name => value pairs
     */
    public function getAssetProperties($assetId) {
        $sql = "SELECT property_name, property_value, property_unit 
                FROM asset_extended_properties 
                WHERE asset_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $properties = [];
        foreach ($results as $result) {
            $properties[$result['property_name']] = [
                'value' => $result['property_value'],
                'unit' => $result['property_unit']
            ];
        }
        
        return $properties;
    }
    
    /**
     * Get full asset type hierarchy information
     * 
     * @param int $assetId Asset ID
     * @return array|null Asset type hierarchy
     */
    public function getAssetTypeHierarchy($assetId) {
        $sql = "SELECT 
                    a.name as asset_name,
                    c.name as category_name,
                    aet.name as equipment_type_name,
                    aet.code as equipment_type_code,
                    ast.name as subtype_name,
                    ast.code as subtype_code,
                    ast.technical_name,
                    ast.description as subtype_description
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN asset_equipment_types aet ON a.equipment_type_id = aet.id
                LEFT JOIN asset_subtypes ast ON a.subtype_id = ast.id
                WHERE a.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>