<?php
/**
 * ConstructLink™ Asset Standardizer
 * ISO 55000:2024 Compliant Asset Standardization System
 * Handles spelling correction, name standardization, and multi-disciplinary categorization
 */

class AssetStandardizer {
    private $db;
    private static $instance = null;
    private $correctionCache = [];
    private $brandCache = [];
    private $typeCache = [];
    private $cacheExpiry = 3600; // 1 hour cache
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->initializeCache();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize caches from session or database
     */
    private function initializeCache() {
        // Use session for caching in shared hosting environment
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Load corrections cache
        if (isset($_SESSION['asset_corrections_cache']) && 
            isset($_SESSION['asset_corrections_cache_time']) &&
            (time() - $_SESSION['asset_corrections_cache_time'] < $this->cacheExpiry)) {
            $this->correctionCache = $_SESSION['asset_corrections_cache'];
        } else {
            $this->loadCorrectionsCache();
        }
        
        // Load brands cache
        if (isset($_SESSION['asset_brands_cache']) && 
            isset($_SESSION['asset_brands_cache_time']) &&
            (time() - $_SESSION['asset_brands_cache_time'] < $this->cacheExpiry)) {
            $this->brandCache = $_SESSION['asset_brands_cache'];
        } else {
            $this->loadBrandsCache();
        }
    }
    
    /**
     * Load spelling corrections from database
     */
    private function loadCorrectionsCache() {
        try {
            $sql = "SELECT incorrect, correct, context, confidence_score
                    FROM inventory_spelling_corrections
                    WHERE approved = 1
                    ORDER BY confidence_score DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $corrections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->correctionCache = [];
            foreach ($corrections as $correction) {
                $key = strtolower($correction['incorrect']);
                if (!isset($this->correctionCache[$key])) {
                    $this->correctionCache[$key] = [];
                }
                $this->correctionCache[$key][$correction['context']] = [
                    'correct' => $correction['correct'],
                    'confidence' => $correction['confidence_score']
                ];
            }
            
            $_SESSION['asset_corrections_cache'] = $this->correctionCache;
            $_SESSION['asset_corrections_cache_time'] = time();
            
        } catch (Exception $e) {
            error_log("AssetStandardizer: Failed to load corrections - " . $e->getMessage());
        }
    }
    
    /**
     * Load brands from database
     */
    private function loadBrandsCache() {
        try {
            $sql = "SELECT id, official_name, variations, quality_tier
                    FROM inventory_brands
                    WHERE is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->brandCache = [];
            foreach ($brands as $brand) {
                // Index by official name
                $this->brandCache[strtolower($brand['official_name'])] = $brand;
                
                // Also index by variations
                if (!empty($brand['variations'])) {
                    $variations = json_decode($brand['variations'], true);
                    if (is_array($variations)) {
                        foreach ($variations as $variation) {
                            $this->brandCache[strtolower($variation)] = $brand;
                        }
                    }
                }
            }
            
            $_SESSION['asset_brands_cache'] = $this->brandCache;
            $_SESSION['asset_brands_cache_time'] = time();
            
        } catch (Exception $e) {
            error_log("AssetStandardizer: Failed to load brands - " . $e->getMessage());
        }
    }
    
    /**
     * Process and standardize asset name
     */
    public function processAssetName($input, $category = null, $context = 'tool_name') {
        $result = [
            'original' => $input,
            'corrected' => null,
            'standardized' => null,
            'confidence' => 0,
            'suggestions' => [],
            'disciplines' => [],
            'warnings' => [],
            'asset_type_id' => null
        ];
        
        if (empty($input)) {
            $result['warnings'][] = 'Asset name is required';
            return $result;
        }
        
        // Step 1: Clean and normalize input
        $cleaned = $this->cleanInput($input);
        $result['cleaned'] = $cleaned;
        
        // Step 2: Check for spelling corrections
        $corrected = $this->applySpellingCorrection($cleaned, $context);
        if ($corrected['corrected'] !== $cleaned) {
            $result['corrected'] = $corrected['corrected'];
            $result['confidence'] = $corrected['confidence'];
        }
        
        // Step 3: Find exact or fuzzy match in asset types
        $assetType = $this->findAssetType($corrected['corrected'] ?? $cleaned, $category);
        if ($assetType) {
            $result['standardized'] = $assetType['name'];
            $result['asset_type_id'] = $assetType['id'];
            $result['confidence'] = max($result['confidence'], $assetType['confidence']);
            
            // Get applicable disciplines
            $result['disciplines'] = $this->getAssetDisciplines($assetType['id']);
        }
        
        // Step 4: Generate suggestions if confidence is low
        if ($result['confidence'] < 0.8) {
            $result['suggestions'] = $this->generateSuggestions(
                $corrected['corrected'] ?? $cleaned, 
                $category
            );
        }
        
        // Step 5: Add warnings for low confidence
        if ($result['confidence'] < 0.5) {
            $result['warnings'][] = 'Low confidence match. Please verify or select from suggestions.';
        }
        
        // Set final standardized name
        if (empty($result['standardized'])) {
            $result['standardized'] = $result['corrected'] ?? $cleaned;
        }
        
        return $result;
    }
    
    /**
     * Clean and normalize input
     */
    private function cleanInput($input) {
        // Remove extra spaces
        $cleaned = preg_replace('/\s+/', ' ', trim($input));
        
        // Remove special characters except hyphens and apostrophes
        $cleaned = preg_replace('/[^a-zA-Z0-9\s\-\']/', '', $cleaned);
        
        // Normalize case for comparison (but preserve original case)
        return $cleaned;
    }
    
    /**
     * Apply spelling correction
     */
    private function applySpellingCorrection($input, $context = 'tool_name') {
        $lower = strtolower($input);
        $result = [
            'corrected' => $input,
            'confidence' => 0
        ];
        
        // Check exact match in corrections cache
        if (isset($this->correctionCache[$lower])) {
            if (isset($this->correctionCache[$lower][$context])) {
                $correction = $this->correctionCache[$lower][$context];
                $result['corrected'] = $correction['correct'];
                $result['confidence'] = $correction['confidence'];
            } elseif (isset($this->correctionCache[$lower]['tool_name'])) {
                // Fallback to tool_name context
                $correction = $this->correctionCache[$lower]['tool_name'];
                $result['corrected'] = $correction['correct'];
                $result['confidence'] = $correction['confidence'] * 0.8; // Reduce confidence
            }
        }
        
        // If no exact match, try fuzzy matching
        if ($result['confidence'] < 0.5) {
            $fuzzyResult = $this->fuzzyMatchCorrection($input, $context);
            if ($fuzzyResult && $fuzzyResult['confidence'] > $result['confidence']) {
                $result = $fuzzyResult;
            }
        }
        
        return $result;
    }
    
    /**
     * Fuzzy match for spelling corrections
     */
    private function fuzzyMatchCorrection($input, $context) {
        $bestMatch = null;
        $bestScore = 0;
        $threshold = 0.7; // Minimum similarity threshold
        
        foreach ($this->correctionCache as $incorrect => $contexts) {
            if (isset($contexts[$context])) {
                $similarity = 0;
                similar_text(strtolower($input), $incorrect, $similarity);
                $similarity = $similarity / 100;
                
                // Also check Levenshtein distance
                $levDistance = levenshtein(strtolower($input), $incorrect);
                $levScore = 1 - ($levDistance / max(strlen($input), strlen($incorrect)));
                
                // Combined score
                $score = ($similarity + $levScore) / 2;
                
                if ($score > $threshold && $score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = [
                        'corrected' => $contexts[$context]['correct'],
                        'confidence' => $score * $contexts[$context]['confidence']
                    ];
                }
            }
        }
        
        return $bestMatch;
    }
    
    /**
     * Find asset type by name
     */
    private function findAssetType($name, $category = null) {
        try {
            // First try exact match
            $sql = "SELECT id, name, category, subcategory, search_keywords
                    FROM inventory_types
                    WHERE LOWER(name) = LOWER(?)
                    AND is_active = 1";
            
            $params = [$name];
            if ($category) {
                $sql .= " AND category = ?";
                $params[] = $category;
            }
            $sql .= " LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $result['confidence'] = 1.0;
                return $result;
            }
            
            // Try matching against common misspellings
            $sql = "SELECT id, name, category, subcategory, search_keywords, common_misspellings
                    FROM inventory_types
                    WHERE JSON_CONTAINS(LOWER(common_misspellings), ?)
                    AND is_active = 1";
            
            $params = [json_encode(strtolower($name))];
            if ($category) {
                $sql .= " AND category = ?";
                $params[] = $category;
            }
            $sql .= " LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $result['confidence'] = 0.85;
                return $result;
            }
            
            // Try fuzzy matching
            return $this->fuzzyMatchAssetType($name, $category);
            
        } catch (Exception $e) {
            error_log("AssetStandardizer: Failed to find asset type - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Fuzzy match asset type
     */
    private function fuzzyMatchAssetType($name, $category = null) {
        try {
            $sql = "SELECT id, name, category, subcategory, search_keywords
                    FROM inventory_types
                    WHERE is_active = 1";
            
            if ($category) {
                $sql .= " AND category = ?";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($category ? [$category] : []);
            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $bestMatch = null;
            $bestScore = 0;
            $threshold = 0.6;
            
            foreach ($types as $type) {
                // Check name similarity
                similar_text(strtolower($name), strtolower($type['name']), $similarity);
                $score = $similarity / 100;
                
                // Check if name appears in search keywords
                if (stripos($type['search_keywords'], $name) !== false) {
                    $score = max($score, 0.7);
                }
                
                if ($score > $threshold && $score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $type;
                    $bestMatch['confidence'] = $score;
                }
            }
            
            return $bestMatch;
            
        } catch (Exception $e) {
            error_log("AssetStandardizer: Fuzzy match failed - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get applicable disciplines for an asset type
     */
    private function getAssetDisciplines($assetTypeId) {
        try {
            $sql = "SELECT d.id, d.code, d.name, adm.primary_use, adm.use_description
                    FROM inventory_discipline_mappings adm
                    JOIN inventory_disciplines d ON adm.discipline_id = d.id
                    WHERE adm.asset_type_id = ?
                    ORDER BY adm.primary_use DESC, d.display_order";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetTypeId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("AssetStandardizer: Failed to get disciplines - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate suggestions for asset name
     */
    public function generateSuggestions($input, $category = null, $limit = 5) {
        try {
            // Use stored procedure if available
            $sql = "CALL GetAssetSuggestions(?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$input, $category, $limit]);
            $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $suggestions;
            
        } catch (Exception $e) {
            // Fallback to regular query
            return $this->generateSuggestionsFallback($input, $category, $limit);
        }
    }
    
    /**
     * Fallback method for generating suggestions
     */
    private function generateSuggestionsFallback($input, $category, $limit) {
        try {
            $sql = "SELECT id, name, category, subcategory,
                    CASE
                        WHEN LOWER(name) = LOWER(?) THEN 100
                        WHEN LOWER(name) LIKE LOWER(CONCAT(?, '%')) THEN 90
                        WHEN LOWER(name) LIKE LOWER(CONCAT('%', ?, '%')) THEN 80
                        WHEN LOWER(search_keywords) LIKE LOWER(CONCAT('%', ?, '%')) THEN 60
                        ELSE 50
                    END AS relevance_score
                    FROM inventory_types
                    WHERE is_active = 1
                    AND (
                        LOWER(name) LIKE LOWER(CONCAT('%', ?, '%'))
                        OR LOWER(search_keywords) LIKE LOWER(CONCAT('%', ?, '%'))
                    )";
            
            $params = [$input, $input, $input, $input, $input, $input];
            
            if ($category) {
                $sql .= " AND category = ?";
                $params[] = $category;
            }
            
            $sql .= " ORDER BY relevance_score DESC, name LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("AssetStandardizer: Suggestion generation failed - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Standardize brand name
     */
    public function standardizeBrand($input) {
        if (empty($input)) {
            return ['original' => $input, 'standardized' => null, 'brand_id' => null];
        }
        
        $lower = strtolower(trim($input));
        
        // Check cache
        if (isset($this->brandCache[$lower])) {
            return [
                'original' => $input,
                'standardized' => $this->brandCache[$lower]['official_name'],
                'brand_id' => $this->brandCache[$lower]['id'],
                'quality_tier' => $this->brandCache[$lower]['quality_tier']
            ];
        }
        
        // Fuzzy match
        $bestMatch = $this->fuzzyMatchBrand($input);
        if ($bestMatch) {
            return [
                'original' => $input,
                'standardized' => $bestMatch['official_name'],
                'brand_id' => $bestMatch['id'],
                'quality_tier' => $bestMatch['quality_tier'],
                'confidence' => $bestMatch['confidence']
            ];
        }
        
        return [
            'original' => $input,
            'standardized' => $input,
            'brand_id' => null,
            'confidence' => 0
        ];
    }
    
    /**
     * Fuzzy match brand
     */
    private function fuzzyMatchBrand($input) {
        $bestMatch = null;
        $bestScore = 0;
        $threshold = 0.7;
        
        foreach ($this->brandCache as $key => $brand) {
            similar_text(strtolower($input), $key, $similarity);
            $score = $similarity / 100;
            
            if ($score > $threshold && $score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $brand;
                $bestMatch['confidence'] = $score;
            }
        }
        
        return $bestMatch;
    }
    
    /**
     * Learn from user correction
     */
    public function learnCorrection($original, $corrected, $userId, $context = 'tool_name') {
        try {
            // Check if correction already exists
            $sql = "SELECT id, usage_count, confidence_score
                    FROM inventory_spelling_corrections
                    WHERE LOWER(incorrect) = LOWER(?)
                    AND LOWER(correct) = LOWER(?)
                    AND context = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$original, $corrected, $context]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update existing correction
                $sql = "UPDATE inventory_spelling_corrections
                        SET usage_count = usage_count + 1,
                            confidence_score = LEAST(confidence_score + 0.05, 1.00),
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$existing['id']]);
            } else {
                // Add new correction
                $sql = "INSERT INTO inventory_spelling_corrections
                        (incorrect, correct, context, confidence_score, usage_count, created_by)
                        VALUES (?, ?, ?, 0.5, 1, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$original, $corrected, $context, $userId]);
            }
            
            // Clear cache
            unset($_SESSION['asset_corrections_cache']);
            $this->loadCorrectionsCache();
            
            return true;
            
        } catch (Exception $e) {
            error_log("AssetStandardizer: Failed to learn correction - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Search assets with multiple strategies
     */
    public function searchAssets($query, $filters = [], $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $results = [];
        
        try {
            // Strategy 1: Exact match with standardization
            $standardized = $this->processAssetName($query);
            $searchTerms = [
                $query,
                $standardized['corrected'] ?? null,
                $standardized['standardized'] ?? null
            ];
            $searchTerms = array_unique(array_filter($searchTerms));
            
            // Build search query
            $sql = "SELECT DISTINCT a.*, 
                    c.name as category_name,
                    p.name as project_name,
                    v.name as vendor_name,
                    m.name as maker_name,
                    ab.official_name as brand_name,
                    CASE ";
            
            // Add relevance scoring
            $paramIndex = 0;
            foreach ($searchTerms as $term) {
                $sql .= "WHEN a.name = ? THEN " . (100 - $paramIndex * 10) . " ";
                $paramIndex++;
            }
            foreach ($searchTerms as $term) {
                $sql .= "WHEN a.standardized_name = ? THEN " . (90 - $paramIndex * 10) . " ";
                $paramIndex++;
            }
            foreach ($searchTerms as $term) {
                $sql .= "WHEN a.name LIKE CONCAT('%', ?, '%') THEN " . (70 - $paramIndex * 5) . " ";
                $paramIndex++;
            }
            
            $sql .= "ELSE 50 END AS relevance_score
                    FROM inventory_items a
                    LEFT JOIN categories c ON a.category_id = c.id
                    LEFT JOIN projects p ON a.project_id = p.id
                    LEFT JOIN vendors v ON a.vendor_id = v.id
                    LEFT JOIN makers m ON a.maker_id = m.id
                    LEFT JOIN inventory_brands ab ON a.brand_id = ab.id
                    WHERE 1=1 ";
            
            $params = [];
            foreach ($searchTerms as $term) {
                $params[] = $term;
            }
            foreach ($searchTerms as $term) {
                $params[] = $term;
            }
            foreach ($searchTerms as $term) {
                $params[] = $term;
            }
            
            // Add search condition
            $searchConditions = [];
            foreach ($searchTerms as $term) {
                $searchConditions[] = "a.name LIKE CONCAT('%', ?, '%')";
                $params[] = $term;
                $searchConditions[] = "a.standardized_name LIKE CONCAT('%', ?, '%')";
                $params[] = $term;
                $searchConditions[] = "a.description LIKE CONCAT('%', ?, '%')";
                $params[] = $term;
            }
            
            if (!empty($searchConditions)) {
                $sql .= " AND (" . implode(" OR ", $searchConditions) . ")";
            }
            
            // Add filters
            if (!empty($filters['category_id'])) {
                $sql .= " AND a.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            if (!empty($filters['project_id'])) {
                $sql .= " AND a.project_id = ?";
                $params[] = $filters['project_id'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND a.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['disciplines'])) {
                $disciplineTags = implode(',', (array)$filters['disciplines']);
                $sql .= " AND a.discipline_tags LIKE ?";
                $params[] = '%' . $disciplineTags . '%';
            }
            
            $sql .= " ORDER BY relevance_score DESC, a.name ASC LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Track search for learning
            if (!empty($results) && isset($_SESSION['user_id'])) {
                $this->trackSearch($query, $standardized['standardized'] ?? $query, count($results));
            }
            
            return [
                'data' => $results,
                'query' => $query,
                'standardized' => $standardized['standardized'] ?? $query,
                'corrections' => $standardized['corrected'] ?? null,
                'total' => count($results)
            ];
            
        } catch (Exception $e) {
            error_log("AssetStandardizer: Search failed - " . $e->getMessage());
            return ['data' => [], 'error' => 'Search failed'];
        }
    }
    
    /**
     * Track search for analytics and learning
     */
    private function trackSearch($query, $correctedQuery, $resultCount) {
        try {
            $sql = "INSERT INTO inventory_search_history
                    (user_id, search_query, corrected_query, result_count, search_type)
                    VALUES (?, ?, ?, ?, 'manual')";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $query,
                $correctedQuery !== $query ? $correctedQuery : null,
                $resultCount
            ]);
            
        } catch (Exception $e) {
            error_log("AssetStandardizer: Failed to track search - " . $e->getMessage());
        }
    }
    
    /**
     * Get category specifications
     */
    public function getCategorySpecifications($categoryId) {
        try {
            $sql = "SELECT standard_specifications 
                    FROM categories 
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$categoryId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['standard_specifications'])) {
                return json_decode($result['standard_specifications'], true);
            }
            
            // Fallback to asset type specifications
            $sql = "SELECT at.typical_specifications
                    FROM inventory_types at
                    JOIN categories c ON at.category = c.name
                    WHERE c.id = ?
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$categoryId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['typical_specifications'])) {
                return json_decode($result['typical_specifications'], true);
            }
            
            return [];
            
        } catch (Exception $e) {
            error_log("AssetStandardizer: Failed to get specifications - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clear all caches
     */
    public function clearCache() {
        unset($_SESSION['asset_corrections_cache']);
        unset($_SESSION['asset_corrections_cache_time']);
        unset($_SESSION['asset_brands_cache']);
        unset($_SESSION['asset_brands_cache_time']);
        
        $this->correctionCache = [];
        $this->brandCache = [];
        $this->typeCache = [];
        
        $this->initializeCache();
    }
    
    /**
     * Get statistics for admin dashboard
     */
    public function getStatistics() {
        try {
            $stats = [];
            
            // Total corrections
            $sql = "SELECT COUNT(*) as total,
                    SUM(approved = 1) as approved,
                    AVG(confidence_score) as avg_confidence
                    FROM inventory_spelling_corrections";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['corrections'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Total asset types
            $sql = "SELECT COUNT(*) as total,
                    COUNT(DISTINCT category) as categories
                    FROM inventory_types
                    WHERE is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['types'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Total brands
            $sql = "SELECT COUNT(*) as total,
                    SUM(is_verified = 1) as verified
                    FROM inventory_brands
                    WHERE is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['brands'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Search statistics
            $sql = "SELECT COUNT(*) as total_searches,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(corrected_query) as corrections_applied,
                    AVG(result_count) as avg_results
                    FROM inventory_search_history
                    WHERE search_timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['searches'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("AssetStandardizer: Failed to get statistics - " . $e->getMessage());
            return [];
        }
    }
}
?>