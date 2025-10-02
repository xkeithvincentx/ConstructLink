<?php
/**
 * Asset Suggestions API
 * Provides intelligent suggestions for asset names and categories
 */

require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../models/BaseModel.php';
require_once '../../core/AssetStandardizer.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Initialize authentication
    $auth = Auth::getInstance();
    
    // Check if user is authenticated
    if (!$auth->isAuthenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit;
    }
    
    // Get parameters
    $query = trim($_GET['q'] ?? '');
    $type = $_GET['type'] ?? 'asset'; // asset, brand, category
    $category = $_GET['category'] ?? null;
    $limit = min((int)($_GET['limit'] ?? 10), 20); // Max 20 suggestions
    
    if (strlen($query) < 1) {
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
        exit;
    }
    
    $suggestions = [];
    $db = Database::getInstance();
    
    switch ($type) {
        case 'brand':
            $sql = "SELECT id, official_name as name, quality_tier,
                    CASE
                        WHEN LOWER(official_name) = LOWER(?) THEN 100
                        WHEN LOWER(official_name) LIKE LOWER(CONCAT(?, '%')) THEN 90
                        WHEN LOWER(official_name) LIKE LOWER(CONCAT('%', ?, '%')) THEN 80
                        WHEN JSON_CONTAINS(LOWER(variations), ?) THEN 70
                        ELSE 50
                    END as relevance
                    FROM asset_brands
                    WHERE is_active = 1
                    AND (
                        LOWER(official_name) LIKE LOWER(CONCAT('%', ?, '%'))
                        OR JSON_CONTAINS(LOWER(variations), ?)
                    )
                    ORDER BY relevance DESC, official_name
                    LIMIT ?";
            
            $searchTerm = strtolower($query);
            $jsonTerm = json_encode($searchTerm);
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $query, $query, $query, $jsonTerm, 
                $query, $jsonTerm, $limit
            ]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $suggestions = array_map(function($row) {
                return [
                    'id' => $row['id'],
                    'value' => $row['name'],
                    'label' => $row['name'],
                    'quality_tier' => $row['quality_tier'],
                    'relevance' => $row['relevance']
                ];
            }, $results);
            break;
            
        case 'category':
            $sql = "SELECT id, name, 
                    CASE
                        WHEN LOWER(name) = LOWER(?) THEN 100
                        WHEN LOWER(name) LIKE LOWER(CONCAT(?, '%')) THEN 90
                        WHEN LOWER(name) LIKE LOWER(CONCAT('%', ?, '%')) THEN 80
                        WHEN LOWER(search_keywords) LIKE LOWER(CONCAT('%', ?, '%')) THEN 60
                        ELSE 50
                    END as relevance
                    FROM categories
                    WHERE (
                        LOWER(name) LIKE LOWER(CONCAT('%', ?, '%'))
                        OR LOWER(description) LIKE LOWER(CONCAT('%', ?, '%'))
                        OR LOWER(search_keywords) LIKE LOWER(CONCAT('%', ?, '%'))
                    )
                    ORDER BY relevance DESC, name
                    LIMIT ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $query, $query, $query, $query, 
                $query, $query, $query, $limit
            ]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $suggestions = array_map(function($row) {
                return [
                    'id' => $row['id'],
                    'value' => $row['name'],
                    'label' => $row['name'],
                    'relevance' => $row['relevance']
                ];
            }, $results);
            break;
            
        case 'asset':
        default:
            // Use AssetStandardizer for intelligent suggestions
            $standardizer = AssetStandardizer::getInstance();
            $assetSuggestions = $standardizer->generateSuggestions($query, $category, $limit);
            
            $suggestions = array_map(function($row) {
                return [
                    'id' => $row['id'],
                    'value' => $row['name'],
                    'label' => $row['name'],
                    'category' => $row['category'],
                    'subcategory' => $row['subcategory'] ?? null,
                    'relevance' => $row['relevance_score'] ?? 50
                ];
            }, $assetSuggestions);
            
            // Also check for spelling corrections
            $sql = "SELECT DISTINCT correct as name, confidence_score,
                    'correction' as type
                    FROM asset_spelling_corrections
                    WHERE LOWER(incorrect) LIKE LOWER(CONCAT('%', ?, '%'))
                    AND approved = 1
                    AND context = 'tool_name'
                    ORDER BY confidence_score DESC
                    LIMIT 3";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$query]);
            $corrections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($corrections as $correction) {
                $suggestions[] = [
                    'id' => 'correction',
                    'value' => $correction['name'],
                    'label' => $correction['name'] . ' (suggested)',
                    'type' => 'correction',
                    'relevance' => $correction['confidence_score'] * 100
                ];
            }
            
            // Sort by relevance
            usort($suggestions, function($a, $b) {
                return $b['relevance'] - $a['relevance'];
            });
            
            // Limit results
            $suggestions = array_slice($suggestions, 0, $limit);
            break;
    }
    
    // Prepare response
    echo json_encode([
        'success' => true,
        'data' => $suggestions,
        'meta' => [
            'query' => $query,
            'type' => $type,
            'count' => count($suggestions),
            'limit' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Asset suggestions API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get suggestions',
        'error' => $e->getMessage()
    ]);
}
?>