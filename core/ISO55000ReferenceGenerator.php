<?php
/**
 * ConstructLink™ ISO 55000:2024 Compliant Asset Reference Generator
 * 
 * Generates standardized asset references following ISO 55000:2024
 * Asset Management Systems - Requirements and Guidelines
 * 
 * Format: [ORG]-[YEAR]-[CAT]-[DIS]-[SEQ]
 * Example: CON-2025-EQ-ME-0001
 */

class ISO55000ReferenceGenerator {

    private $db;
    private $orgCode;
    private $tableName;

    // Note: Category and discipline codes are now stored in database tables:
    // - categories.iso_code (2-character ISO 55000:2024 category codes)
    // - inventory_disciplines.iso_code (2-character ISO 55000:2024 discipline codes)
    // This provides full scalability and configurability without code changes.

    /**
     * Constructor
     *
     * @param string $tableName Database table name for reference generation (default: 'inventory_items')
     */
    public function __construct($tableName = 'inventory_items') {
        $this->db = Database::getInstance()->getConnection();
        $this->orgCode = defined('ASSET_ORG_CODE') ? ASSET_ORG_CODE : 'CON';
        $this->tableName = $tableName;
    }
    
    /**
     * Generate ISO 55000:2024 compliant asset reference
     * 
     * @param int $categoryId Asset category ID
     * @param int|null $disciplineId Primary discipline ID 
     * @param bool $isLegacy Whether this is a legacy asset
     * @return string ISO compliant reference
     */
    public function generateReference($categoryId, $disciplineId = null, $isLegacy = false) {
        try {
            // Get category information
            $categoryCode = $this->getCategoryCode($categoryId);
            
            // Get discipline code
            $disciplineCode = $this->getDisciplineCode($disciplineId);
            
            // Determine year component
            $yearComponent = $isLegacy ? 'LEG' : date('Y');
            
            // Generate sequential number
            $sequentialNumber = $this->getNextSequentialNumber($categoryCode, $disciplineCode, $yearComponent);
            
            // Format according to ISO 55000:2024
            $reference = sprintf(
                '%s-%s-%s-%s-%04d',
                $this->orgCode,
                $yearComponent,
                $categoryCode,
                $disciplineCode,
                $sequentialNumber
            );
            
            // Validate uniqueness
            if ($this->isReferenceUnique($reference)) {
                return $reference;
            } else {
                // Retry with incremented sequence
                return $this->generateReference($categoryId, $disciplineId, $isLegacy);
            }
            
        } catch (Exception $e) {
            // Log the error and re-throw for strict database-only behavior
            error_log("ISO reference generation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get ISO 55000 category code from database category
     */
    private function getCategoryCode($categoryId) {
        if (!$categoryId) {
            throw new Exception("Category ID is required for ISO reference generation");
        }
        
        $sql = "SELECT iso_code, name FROM categories WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) {
            throw new Exception("Category with ID {$categoryId} not found");
        }
        
        if (empty($category['iso_code'])) {
            throw new Exception("Category '{$category['name']}' (ID: {$categoryId}) is missing required iso_code. Please update the category with a valid 2-character ISO code.");
        }
        
        return $category['iso_code'];
    }
    
    /**
     * Get ISO 55000 discipline code from database discipline
     */
    private function getDisciplineCode($disciplineId) {
        if (!$disciplineId) {
            return 'GN'; // General - allow null discipline for flexibility
        }

        $sql = "SELECT iso_code, name FROM inventory_disciplines WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$disciplineId]);
        $discipline = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$discipline) {
            throw new Exception("Discipline with ID {$disciplineId} not found");
        }
        
        if (empty($discipline['iso_code'])) {
            throw new Exception("Discipline '{$discipline['name']}' (ID: {$disciplineId}) is missing required iso_code. Please update the discipline with a valid 2-character ISO code.");
        }
        
        return $discipline['iso_code'];
    }
    
    /**
     * Get next sequential number for the combination
     */
    private function getNextSequentialNumber($categoryCode, $disciplineCode, $yearComponent) {
        $prefix = "{$this->orgCode}-{$yearComponent}-{$categoryCode}-{$disciplineCode}-";

        // Get the highest sequence number for this prefix (supports multiple tables)
        $sql = "SELECT MAX(CAST(SUBSTRING(ref, LENGTH(?) + 1) AS UNSIGNED)) as max_seq
                FROM {$this->tableName} WHERE ref LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$prefix, "{$prefix}%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $maxSeq = (int)($result['max_seq'] ?? 0);
        return $maxSeq + 1;
    }
    
    /**
     * Validate reference uniqueness
     */
    private function isReferenceUnique($reference) {
        $sql = "SELECT COUNT(*) as count FROM {$this->tableName} WHERE ref = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reference]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($result['count'] ?? 0) === 0;
    }
    
    /**
     * Parse existing reference to extract components
     * 
     * @param string $reference Existing asset reference
     * @return array|null Components or null if not ISO format
     */
    public function parseReference($reference) {
        $pattern = '/^([A-Z]{3})-(\d{4}|LEG)-([A-Z]{2})-([A-Z]{2})-(\d{4})$/';
        
        if (preg_match($pattern, $reference, $matches)) {
            return [
                'full' => $matches[0],
                'org' => $matches[1],
                'year' => $matches[2],
                'category' => $matches[3],
                'discipline' => $matches[4],
                'sequence' => (int)$matches[5],
                'is_legacy' => $matches[2] === 'LEG'
            ];
        }
        
        return null;
    }
    
    /**
     * Get human-readable description of reference components
     */
    public function describeReference($reference) {
        $components = $this->parseReference($reference);
        if (!$components) {
            return "Non-ISO format reference";
        }
        
        $categoryName = array_search($components['category'], self::CATEGORY_CODES);
        $disciplineName = array_search($components['discipline'], self::DISCIPLINE_CODES);
        
        return sprintf(
            "%s %s %s %s #%d",
            $components['org'],
            $components['is_legacy'] ? 'Legacy' : $components['year'],
            $categoryName ?: $components['category'],
            $disciplineName ?: $components['discipline'],
            $components['sequence']
        );
    }
    
    /**
     * Batch generate references for multiple assets
     */
    public function batchGenerateReferences($assets) {
        $references = [];
        
        foreach ($assets as $asset) {
            $references[] = $this->generateReference(
                $asset['category_id'] ?? null,
                $asset['primary_discipline'] ?? null,
                $asset['is_legacy'] ?? false
            );
        }
        
        return $references;
    }
}
?>