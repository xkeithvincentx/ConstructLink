<?php
/**
 * Asset Disciplines API
 * Provides discipline mappings and category information
 */

require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../models/BaseModel.php';

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
    
    $db = Database::getInstance()->getConnection();
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Get all disciplines with hierarchy
            $sql = "SELECT d.id, d.code, d.name, d.description, 
                    d.parent_id, p.name as parent_name, d.display_order
                    FROM asset_disciplines d
                    LEFT JOIN asset_disciplines p ON d.parent_id = p.id
                    WHERE d.is_active = 1
                    ORDER BY COALESCE(p.display_order, d.display_order), d.display_order";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organize by parent-child relationship
            $organized = [];
            $children = [];
            
            foreach ($disciplines as $discipline) {
                if (empty($discipline['parent_id'])) {
                    $organized[$discipline['id']] = $discipline;
                    $organized[$discipline['id']]['children'] = [];
                } else {
                    $children[] = $discipline;
                }
            }
            
            // Add children to parents
            foreach ($children as $child) {
                if (isset($organized[$child['parent_id']])) {
                    $organized[$child['parent_id']]['children'][] = $child;
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => array_values($organized)
            ]);
            break;
            
        case 'by_asset_type':
            $assetTypeId = (int)($_GET['asset_type_id'] ?? 0);
            
            if (!$assetTypeId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Asset type ID is required'
                ]);
                exit;
            }
            
            $sql = "SELECT d.id, d.code, d.name, d.description,
                    adm.primary_use, adm.use_description, adm.frequency_of_use
                    FROM asset_discipline_mappings adm
                    JOIN asset_disciplines d ON adm.discipline_id = d.id
                    WHERE adm.asset_type_id = ? AND d.is_active = 1
                    ORDER BY adm.primary_use DESC, d.display_order";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$assetTypeId]);
            $disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $disciplines
            ]);
            break;
            
        case 'by_category':
            $categoryId = (int)($_GET['category_id'] ?? 0);
            
            if (!$categoryId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Category ID is required'
                ]);
                exit;
            }
            
            // Get disciplines based on category's asset types
            $sql = "SELECT DISTINCT d.id, d.code, d.name, d.description,
                    COUNT(adm.id) as usage_count,
                    MAX(adm.primary_use) as has_primary_use
                    FROM categories c
                    JOIN asset_types at ON at.category = c.name
                    JOIN asset_discipline_mappings adm ON adm.asset_type_id = at.id
                    JOIN asset_disciplines d ON adm.discipline_id = d.id
                    WHERE c.id = ? AND d.is_active = 1
                    GROUP BY d.id, d.code, d.name, d.description
                    ORDER BY has_primary_use DESC, usage_count DESC, d.display_order";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$categoryId]);
            $disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $disciplines
            ]);
            break;
            
        case 'search_assets':
            $disciplineIds = $_GET['discipline_ids'] ?? [];
            $limit = min((int)($_GET['limit'] ?? 20), 50);
            
            if (empty($disciplineIds)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'At least one discipline ID is required'
                ]);
                exit;
            }
            
            // Ensure array format
            if (!is_array($disciplineIds)) {
                $disciplineIds = explode(',', $disciplineIds);
            }
            
            $placeholders = str_repeat('?,', count($disciplineIds) - 1) . '?';
            
            $sql = "SELECT DISTINCT at.id, at.name, at.category, at.subcategory,
                    GROUP_CONCAT(d.name SEPARATOR ', ') as disciplines,
                    COUNT(adm.id) as discipline_count
                    FROM asset_types at
                    JOIN asset_discipline_mappings adm ON adm.asset_type_id = at.id
                    JOIN asset_disciplines d ON adm.discipline_id = d.id
                    WHERE d.id IN ($placeholders) AND at.is_active = 1
                    GROUP BY at.id, at.name, at.category, at.subcategory
                    ORDER BY discipline_count DESC, at.name
                    LIMIT ?";
            
            $params = array_merge($disciplineIds, [$limit]);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $assets
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Asset disciplines API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Request failed',
        'error' => $e->getMessage()
    ]);
}
?>