<?php
/**
 * ConstructLink™ Intelligent Asset Naming API
 * Handles AJAX requests for intelligent asset name generation
 */

require_once '../core/Database.php';
require_once '../core/Auth.php';
require_once '../core/IntelligentAssetNamer.php';

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

    $namer = new IntelligentAssetNamer();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        
        case 'equipment-types':
            // Get equipment types for a category
            $categoryId = intval($_GET['category_id'] ?? 0);
            
            if (!$categoryId) {
                throw new Exception('Category ID is required');
            }
            
            $equipmentTypes = $namer->getEquipmentTypesByCategory($categoryId);
            
            echo json_encode([
                'success' => true,
                'data' => $equipmentTypes
            ]);
            break;
            
        case 'subtypes':
            // Get subtypes for an equipment type
            $equipmentTypeId = intval($_GET['equipment_type_id'] ?? 0);
            
            if (!$equipmentTypeId) {
                throw new Exception('Equipment Type ID is required');
            }
            
            $subtypes = $namer->getSubtypesByEquipmentType($equipmentTypeId);
            
            echo json_encode([
                'success' => true,
                'data' => $subtypes
            ]);
            break;
            
        case 'generate-name':
            // Generate intelligent asset name
            $equipmentTypeId = intval($_GET['equipment_type_id'] ?? 0);
            $subtypeId = intval($_GET['subtype_id'] ?? 0);
            $brand = trim($_GET['brand'] ?? '');
            $model = trim($_GET['model'] ?? '');
            
            if (!$equipmentTypeId || !$subtypeId) {
                throw new Exception('Equipment Type ID and Subtype ID are required');
            }
            
            $nameData = $namer->generateAssetName($equipmentTypeId, $subtypeId, $brand, $model);
            
            echo json_encode([
                'success' => true,
                'data' => $nameData
            ]);
            break;
            
        case 'suggestions':
            // Get intelligent suggestions based on partial input
            $partialName = trim($_GET['partial_name'] ?? '');
            $categoryId = intval($_GET['category_id'] ?? 0) ?: null;
            
            if (!$partialName) {
                throw new Exception('Partial name is required for suggestions');
            }
            
            $suggestions = $namer->getSuggestions($partialName, $categoryId);
            
            echo json_encode([
                'success' => true,
                'data' => $suggestions
            ]);
            break;
            
        case 'preview':
            // Preview name generation without saving
            $equipmentTypeId = intval($_POST['equipment_type_id'] ?? 0);
            $subtypeId = intval($_POST['subtype_id'] ?? 0);
            $brand = trim($_POST['brand'] ?? '');
            $model = trim($_POST['model'] ?? '');
            
            if (!$equipmentTypeId || !$subtypeId) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'generated_name' => '',
                        'preview_available' => false
                    ]
                ]);
                break;
            }
            
            $nameData = $namer->generateAssetName($equipmentTypeId, $subtypeId, $brand, $model);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'generated_name' => $nameData['generated_name'],
                    'name_components' => $nameData['name_components'],
                    'preview_available' => true
                ]
            ]);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>