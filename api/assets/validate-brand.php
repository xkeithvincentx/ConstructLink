<?php
/**
 * Brand Validation API
 * Standardizes brand names and provides suggestions
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
    $brand = trim($_GET['brand'] ?? '');
    
    if (empty($brand)) {
        echo json_encode([
            'success' => true,
            'data' => [
                'original' => '',
                'standardized' => null,
                'brand_id' => null,
                'valid' => false,
                'message' => 'Brand name is required'
            ]
        ]);
        exit;
    }
    
    // Initialize standardizer
    $standardizer = AssetStandardizer::getInstance();
    
    // Standardize the brand
    $result = $standardizer->standardizeBrand($brand);
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'original' => $result['original'],
            'standardized' => $result['standardized'],
            'brand_id' => $result['brand_id'],
            'quality_tier' => $result['quality_tier'] ?? null,
            'confidence' => $result['confidence'] ?? ($result['brand_id'] ? 1.0 : 0.0),
            'valid' => !empty($result['brand_id']),
            'has_correction' => $result['standardized'] !== $result['original']
        ]
    ];
    
    // If no exact match found, try to get similar brands for suggestions
    if (empty($result['brand_id']) && strlen($brand) >= 2) {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT id, official_name, quality_tier,
                    CASE
                        WHEN LOWER(official_name) LIKE LOWER(CONCAT('%', ?, '%')) THEN 80
                        WHEN LOWER(variations) LIKE LOWER(CONCAT('%', ?, '%')) THEN 70
                        ELSE 50
                    END as score
                    FROM asset_brands
                    WHERE 1=1
                    AND (
                        LOWER(official_name) LIKE LOWER(CONCAT('%', ?, '%'))
                        OR LOWER(variations) LIKE LOWER(CONCAT('%', ?, '%'))
                    )
                    ORDER BY score DESC, official_name
                    LIMIT 5";
            
            $stmt = $db->prepare($sql);
            $searchTerm = strtolower($brand);
            $jsonTerm = json_encode($searchTerm);
            $stmt->execute([$searchTerm, $jsonTerm, $searchTerm, $jsonTerm]);
            
            $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($suggestions)) {
                $response['data']['suggestions'] = array_map(function($suggestion) {
                    return [
                        'id' => $suggestion['id'],
                        'name' => $suggestion['official_name'],
                        'quality_tier' => $suggestion['quality_tier'],
                        'score' => $suggestion['score']
                    ];
                }, $suggestions);
            }
            
        } catch (Exception $e) {
            error_log("Brand suggestions error: " . $e->getMessage());
            // Continue without suggestions
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Brand validation API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Brand validation failed',
        'error' => $e->getMessage()
    ]);
}
?>