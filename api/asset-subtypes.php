<?php
/**
 * ConstructLink™ Asset Subtypes API
 * Handles AJAX requests for dynamic subtype loading
 */

// Set JSON response header
header('Content-Type: application/json');

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/AssetSubtypeManager.php';
require_once APP_ROOT . '/core/Auth.php';

try {
    // Check authentication
    $auth = Auth::getInstance();
    if (!$auth->isLoggedIn()) {
        throw new Exception('Authentication required');
    }

    $subtypeManager = new AssetSubtypeManager();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        
        case 'equipment-types':
            // Get equipment types for a category
            $categoryId = intval($_GET['category_id'] ?? 0);
            
            if (!$categoryId) {
                throw new Exception('Category ID is required');
            }
            
            $equipmentTypes = $subtypeManager->getEquipmentTypesByCategory($categoryId);
            
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
            
            $subtypes = $subtypeManager->getSubtypesByEquipmentType($equipmentTypeId);
            
            echo json_encode([
                'success' => true,
                'data' => $subtypes
            ]);
            break;
            
        case 'suggestions':
            // Get intelligent subtype suggestions
            $assetName = trim($_GET['asset_name'] ?? '');
            $categoryId = intval($_GET['category_id'] ?? 0) ?: null;
            $disciplineId = intval($_GET['discipline_id'] ?? 0) ?: null;
            
            if (!$assetName) {
                throw new Exception('Asset name is required for suggestions');
            }
            
            $suggestions = $subtypeManager->getSuggestedSubtypes($assetName, $categoryId, $disciplineId);
            
            echo json_encode([
                'success' => true,
                'data' => $suggestions
            ]);
            break;
            
        case 'specification-templates':
            // Get specification templates for a subtype
            $subtypeId = intval($_GET['subtype_id'] ?? 0);
            
            if (!$subtypeId) {
                throw new Exception('Subtype ID is required');
            }
            
            $templates = $subtypeManager->getSpecificationTemplates($subtypeId);
            
            echo json_encode([
                'success' => true,
                'data' => $templates
            ]);
            break;
            
        case 'hierarchy':
            // Get full asset type hierarchy
            $assetId = intval($_GET['asset_id'] ?? 0);
            
            if (!$assetId) {
                throw new Exception('Asset ID is required');
            }
            
            $hierarchy = $subtypeManager->getAssetTypeHierarchy($assetId);
            
            echo json_encode([
                'success' => true,
                'data' => $hierarchy
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