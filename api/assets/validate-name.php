<?php
/**
 * Asset Name Validation API
 * Validates and standardizes asset names with suggestions
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
    
    $currentUser = $auth->getCurrentUser();
    $userRole = $currentUser['role_name'] ?? '';
    
    // Check permissions
    $allowedRoles = [
        'System Admin', 'Asset Director', 'Procurement Officer', 
        'Warehouseman', 'Project Manager', 'Site Inventory Clerk'
    ];
    
    if (!in_array($userRole, $allowedRoles)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied'
        ]);
        exit;
    }
    
    // Get parameters
    $name = trim($_GET['name'] ?? '');
    $category = $_GET['category'] ?? null;
    $context = $_GET['context'] ?? 'tool_name';
    
    if (empty($name)) {
        echo json_encode([
            'success' => false,
            'message' => 'Asset name is required'
        ]);
        exit;
    }
    
    // Validate minimum length
    if (strlen($name) < 2) {
        echo json_encode([
            'success' => true,
            'data' => [
                'valid' => false,
                'original' => $name,
                'message' => 'Please enter at least 2 characters'
            ]
        ]);
        exit;
    }
    
    // Initialize standardizer
    $standardizer = AssetStandardizer::getInstance();
    
    // Process the asset name
    $result = $standardizer->processAssetName($name, $category, $context);
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'valid' => $result['confidence'] >= 0.5,
            'original' => $result['original'],
            'corrected' => $result['corrected'],
            'standardized' => $result['standardized'],
            'confidence' => $result['confidence'],
            'asset_type_id' => $result['asset_type_id'],
            'suggestions' => $result['suggestions'],
            'disciplines' => $result['disciplines'],
            'warnings' => $result['warnings'],
            'has_correction' => !empty($result['corrected']) && $result['corrected'] !== $name,
            'has_standardization' => !empty($result['standardized']) && $result['standardized'] !== $name,
            'confidence_level' => $result['confidence'] >= 0.9 ? 'high' : 
                                 ($result['confidence'] >= 0.7 ? 'medium' : 'low')
        ]
    ];
    
    // Add category-specific information if available
    if ($category && $result['asset_type_id']) {
        $specifications = $standardizer->getCategorySpecifications($category);
        if (!empty($specifications)) {
            $response['data']['specifications'] = $specifications;
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Asset name validation API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed',
        'error' => $e->getMessage()
    ]);
}
?>