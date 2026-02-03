<?php
/**
 * Enhanced Asset Search API
 * Advanced search with standardization, fuzzy matching, and multi-disciplinary filtering
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
        'System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer',
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
    
    // Get search parameters
    $query = trim($_GET['q'] ?? '');
    $searchType = $_GET['type'] ?? 'smart'; // smart, exact, fuzzy, phonetic
    $filters = [
        'category_id' => $_GET['category_id'] ?? null,
        'project_id' => $_GET['project_id'] ?? null,
        'status' => $_GET['status'] ?? null,
        'disciplines' => $_GET['disciplines'] ?? null,
        'brand_id' => $_GET['brand_id'] ?? null,
        'maker_id' => $_GET['maker_id'] ?? null,
        'vendor_id' => $_GET['vendor_id'] ?? null,
        'client_id' => $_GET['client_id'] ?? null,
        'date_from' => $_GET['date_from'] ?? null,
        'date_to' => $_GET['date_to'] ?? null,
        'min_cost' => $_GET['min_cost'] ?? null,
        'max_cost' => $_GET['max_cost'] ?? null
    ];
    
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(50, max(10, (int)($_GET['per_page'] ?? 20)));
    $sortBy = $_GET['sort_by'] ?? 'relevance';
    $sortOrder = strtoupper($_GET['sort_order'] ?? 'DESC');
    
    if (!in_array($sortOrder, ['ASC', 'DESC'])) {
        $sortOrder = 'DESC';
    }
    
    // Initialize standardizer for enhanced search
    $standardizer = AssetStandardizer::getInstance();
    
    // Perform enhanced search
    $searchResults = $standardizer->searchAssets($query, $filters, $page, $perPage);
    
    if (isset($searchResults['error'])) {
        throw new Exception($searchResults['error']);
    }
    
    $assets = $searchResults['data'] ?? [];
    $total = $searchResults['total'] ?? 0;
    
    // Enhance results with additional data
    $enhancedAssets = array_map(function($asset) use ($auth, $userRole) {
        // Add computed fields
        $asset['age_days'] = $asset['acquired_date'] ? 
            (new DateTime())->diff(new DateTime($asset['acquired_date']))->days : null;
        
        $asset['status_badge'] = [
            'available' => ['class' => 'success', 'text' => 'Available'],
            'in_use' => ['class' => 'primary', 'text' => 'In Use'],
            'borrowed' => ['class' => 'warning', 'text' => 'Borrowed'],
            'under_maintenance' => ['class' => 'info', 'text' => 'Maintenance'],
            'retired' => ['class' => 'secondary', 'text' => 'Retired'],
            'disposed' => ['class' => 'dark', 'text' => 'Disposed']
        ][$asset['status']] ?? ['class' => 'secondary', 'text' => 'Unknown'];
        
        // Role-based data filtering
        if (!in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
            unset($asset['acquisition_cost']);
            unset($asset['unit_cost']);
        }
        
        // Add action permissions
        $asset['permissions'] = [
            'can_edit' => in_array($userRole, ['System Admin', 'Asset Director']),
            'can_withdraw' => in_array($userRole, ['System Admin', 'Asset Director', 'Project Manager', 'Warehouseman']),
            'can_maintain' => in_array($userRole, ['System Admin', 'Asset Director', 'Warehouseman']),
            'can_transfer' => in_array($userRole, ['System Admin', 'Asset Director', 'Project Manager']),
            'can_view_financial' => in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])
        ];
        
        // Add quick action URLs
        $asset['actions'] = [
            'view' => "?route=assets/view&id={$asset['id']}",
            'edit' => "?route=assets/edit&id={$asset['id']}",
            'withdraw' => "?route=withdrawals/create-batch&asset_id={$asset['id']}",
            'transfer' => "?route=transfers/create&asset_id={$asset['id']}",
            'maintenance' => "?route=maintenance/create&asset_id={$asset['id']}",
            'qr_print' => "?route=assets/print-tag&id={$asset['id']}"
        ];
        
        return $asset;
    }, $assets);
    
    // Build search metadata
    $searchMeta = [
        'query' => $query,
        'original_query' => $query,
        'standardized_query' => $searchResults['standardized'] ?? $query,
        'corrections_applied' => $searchResults['corrections'] ?? null,
        'search_type' => $searchType,
        'filters_applied' => array_filter($filters),
        'total_results' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($total / $perPage),
        'has_more' => ($page * $perPage) < $total,
        'user_role' => $userRole,
        'search_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
    ];
    
    // Get faceted search data for filters
    $facets = [];
    if ($total > 0 && $total <= 1000) { // Only compute facets for reasonable result sets
        $facets = computeSearchFacets($enhancedAssets, $filters);
    }
    
    // Response
    echo json_encode([
        'success' => true,
        'data' => $enhancedAssets,
        'meta' => $searchMeta,
        'facets' => $facets,
        'suggestions' => generateSearchSuggestions($query, $total),
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("Enhanced asset search API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Search failed',
        'error' => $e->getMessage(),
        'debug' => [
            'query' => $_GET['q'] ?? '',
            'type' => $_GET['type'] ?? 'smart',
            'timestamp' => date('c')
        ]
    ]);
}

/**
 * Compute faceted search data for dynamic filtering
 */
function computeSearchFacets($assets, $currentFilters) {
    $facets = [
        'categories' => [],
        'projects' => [],
        'statuses' => [],
        'brands' => [],
        'disciplines' => []
    ];
    
    foreach ($assets as $asset) {
        // Category facets
        if (!empty($asset['category_name'])) {
            $key = $asset['category_id'];
            if (!isset($facets['categories'][$key])) {
                $facets['categories'][$key] = [
                    'name' => $asset['category_name'],
                    'count' => 0,
                    'selected' => $currentFilters['category_id'] == $key
                ];
            }
            $facets['categories'][$key]['count']++;
        }
        
        // Project facets
        if (!empty($asset['project_name'])) {
            $key = $asset['project_id'];
            if (!isset($facets['projects'][$key])) {
                $facets['projects'][$key] = [
                    'name' => $asset['project_name'],
                    'count' => 0,
                    'selected' => $currentFilters['project_id'] == $key
                ];
            }
            $facets['projects'][$key]['count']++;
        }
        
        // Status facets
        $status = $asset['status'];
        if (!isset($facets['statuses'][$status])) {
            $facets['statuses'][$status] = [
                'name' => ucfirst(str_replace('_', ' ', $status)),
                'count' => 0,
                'selected' => $currentFilters['status'] == $status
            ];
        }
        $facets['statuses'][$status]['count']++;
        
        // Brand facets
        if (!empty($asset['brand_name'])) {
            $key = $asset['brand_id'] ?? $asset['brand_name'];
            if (!isset($facets['brands'][$key])) {
                $facets['brands'][$key] = [
                    'name' => $asset['brand_name'],
                    'count' => 0,
                    'selected' => $currentFilters['brand_id'] == $key
                ];
            }
            $facets['brands'][$key]['count']++;
        }
    }
    
    // Sort facets by count (descending)
    foreach ($facets as &$facetGroup) {
        uasort($facetGroup, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        // Convert to indexed array for JSON
        $facetGroup = array_values($facetGroup);
    }
    
    return $facets;
}

/**
 * Generate search suggestions based on query and results
 */
function generateSearchSuggestions($query, $totalResults) {
    $suggestions = [];
    
    if ($totalResults == 0 && strlen($query) > 2) {
        try {
            $standardizer = AssetStandardizer::getInstance();
            
            // Get spelling suggestions
            $nameResult = $standardizer->processAssetName($query);
            if (!empty($nameResult['suggestions'])) {
                foreach ($nameResult['suggestions'] as $suggestion) {
                    $suggestions[] = [
                        'type' => 'spelling',
                        'text' => $suggestion['name'],
                        'reason' => 'Did you mean this?',
                        'confidence' => $suggestion['relevance_score'] ?? 50
                    ];
                }
            }
            
            // Get related terms
            $relatedTerms = getRelatedSearchTerms($query);
            foreach ($relatedTerms as $term) {
                $suggestions[] = [
                    'type' => 'related',
                    'text' => $term,
                    'reason' => 'Related search',
                    'confidence' => 70
                ];
            }
            
        } catch (Exception $e) {
            error_log("Search suggestions error: " . $e->getMessage());
        }
    }
    
    return $suggestions;
}

/**
 * Get related search terms
 */
function getRelatedSearchTerms($query) {
    $db = Database::getInstance();
    $terms = [];
    
    try {
        // Find terms that appear in similar searches
        $sql = "SELECT DISTINCT search_query, COUNT(*) as frequency
                FROM asset_search_history 
                WHERE search_query LIKE ? 
                AND search_query != ?
                AND result_count > 0
                GROUP BY search_query
                ORDER BY frequency DESC
                LIMIT 5";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(["%{$query}%", $query]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as $result) {
            $terms[] = $result['search_query'];
        }
        
    } catch (Exception $e) {
        error_log("Related terms error: " . $e->getMessage());
    }
    
    return $terms;
}
?>