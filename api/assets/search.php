<?php
/**
 * ConstructLink™ Asset Search API - Enhanced
 * Provides comprehensive asset search functionality with project scoping
 */

require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../models/BaseModel.php';
require_once '../../models/AssetModel.php';

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
    if (!in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied'
        ]);
        exit;
    }
    
    // Get search parameters
    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? 'all'; // all, qr, ref, name, serial
    $project_id = $_GET['project_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $category_id = $_GET['category_id'] ?? null;
    $limit = min((int)($_GET['limit'] ?? 20), 100); // Max 100 results
    
    if (empty($query) && $type !== 'all') {
        echo json_encode([
            'success' => false,
            'message' => 'Search query is required'
        ]);
        exit;
    }
    
    $assetModel = new AssetModel();
    $results = [];
    
    switch ($type) {
        case 'qr':
            // QR Code search
            $asset = $assetModel->findByQRCode($query);
            if ($asset) {
                $results = [$asset];
            }
            break;
            
        case 'ref':
            // Asset reference search
            $asset = $assetModel->findFirst(['ref' => $query]);
            if ($asset) {
                $results = [$asset];
            }
            break;
            
        case 'serial':
            // Serial number search
            $asset = $assetModel->findFirst(['serial_number' => $query]);
            if ($asset) {
                $results = [$asset];
            }
            break;
            
        case 'name':
        case 'all':
        default:
            // General search with filters
            $filters = [];
            
            if (!empty($query)) {
                $filters['search'] = $query;
            }
            
            if ($project_id) {
                $filters['project_id'] = $project_id;
            }
            
            if ($status) {
                $filters['status'] = $status;
            }
            
            if ($category_id) {
                $filters['category_id'] = $category_id;
            }
            
            $result = $assetModel->getAssetsWithFilters($filters, 1, $limit);
            $results = $result['data'] ?? [];
            break;
    }
    
    // Format results for API response
    $formattedResults = [];
    foreach ($results as $asset) {
        $formattedResults[] = [
            'id' => (int)$asset['id'],
            'ref' => $asset['ref'],
            'name' => $asset['name'],
            'description' => $asset['description'] ?? '',
            'status' => $asset['status'],
            'status_label' => ucfirst(str_replace('_', ' ', $asset['status'])),
            'category' => [
                'id' => (int)($asset['category_id'] ?? 0),
                'name' => $asset['category_name'] ?? 'N/A'
            ],
            'project' => [
                'id' => (int)($asset['project_id'] ?? 0),
                'name' => $asset['project_name'] ?? 'N/A',
                'location' => $asset['project_location'] ?? null
            ],
            'vendor' => [
                'id' => (int)($asset['vendor_id'] ?? 0),
                'name' => $asset['vendor_name'] ?? null
            ],
            'maker' => [
                'id' => (int)($asset['maker_id'] ?? 0),
                'name' => $asset['maker_name'] ?? null
            ],
            'specifications' => [
                'model' => $asset['model'] ?? null,
                'serial_number' => $asset['serial_number'] ?? null,
                'specifications' => $asset['specifications'] ?? null
            ],
            'financial' => [
                'acquisition_cost' => $auth->hasRole(['System Admin', 'Finance Director', 'Asset Director']) ? 
                    (float)($asset['acquisition_cost'] ?? 0) : null,
                'unit_cost' => $auth->hasRole(['System Admin', 'Finance Director', 'Asset Director']) ? 
                    (float)($asset['unit_cost'] ?? 0) : null,
                'is_client_supplied' => (bool)($asset['is_client_supplied'] ?? false)
            ],
            'dates' => [
                'acquired_date' => $asset['acquired_date'],
                'warranty_expiry' => $asset['warranty_expiry'] ?? null,
                'created_at' => $asset['created_at'],
                'updated_at' => $asset['updated_at'] ?? null
            ],
            'location' => $asset['location'] ?? null,
            'condition_notes' => $asset['condition_notes'] ?? null,
            'qr_code' => !empty($asset['qr_code']),
            'procurement' => [
                'id' => (int)($asset['procurement_id'] ?? 0),
                'po_number' => $asset['po_number'] ?? null
            ],
            'urls' => [
                'view' => "?route=assets/view&id={$asset['id']}",
                'edit' => "?route=assets/edit&id={$asset['id']}",
                'withdraw' => "?route=withdrawals/create&asset_id={$asset['id']}"
            ]
        ];
    }
    
    // Response
    echo json_encode([
        'success' => true,
        'data' => $formattedResults,
        'meta' => [
            'query' => $query,
            'type' => $type,
            'count' => count($formattedResults),
            'limit' => $limit,
            'user_role' => $userRole,
            'project_scoped' => !in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Asset search API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
?>
