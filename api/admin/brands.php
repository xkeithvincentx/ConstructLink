<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Check authentication and admin permissions
$auth = Auth::getInstance();
$user = $auth->getCurrentUser();

if (!$user || !in_array($user['role_name'], ['System Admin', 'Asset Director'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGet($db);
            break;
        case 'POST':
            handlePost($db);
            break;
        case 'PUT':
            handlePut($db);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Brands API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGet($db) {
    // Check if requesting a specific brand by ID
    $id = intval($_GET['id'] ?? 0);
    
    if ($id) {
        // Single brand request
        try {
            $sql = "
                SELECT 
                    b.id,
                    b.official_name,
                    b.variations,
                    b.country,
                    b.website,
                    b.quality_tier,
                    b.is_verified,
                    b.is_active,
                    b.created_at,
                    b.updated_at,
                    COALESCE(asset_count.count, 0) as assets_count
                FROM asset_brands b
                LEFT JOIN (
                    SELECT brand_id, COUNT(*) as count
                    FROM inventory_items
                    WHERE deleted_at IS NULL
                    GROUP BY brand_id
                ) asset_count ON b.id = asset_count.brand_id
                WHERE b.id = ?
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            $brand = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$brand) {
                echo json_encode(['success' => false, 'message' => 'Brand not found']);
                return;
            }
            
            // Format the data
            $formattedBrand = [
                'id' => (int)$brand['id'],
                'official_name' => $brand['official_name'],
                'variations' => json_decode($brand['variations'] ?: '[]'),
                'country' => $brand['country'],
                'website' => $brand['website'],
                'quality_tier' => $brand['quality_tier'],
                'is_verified' => (bool)$brand['is_verified'],
                'is_active' => (bool)$brand['is_active'],
                'assets_count' => (int)$brand['assets_count'],
                'created_at' => $brand['created_at'],
                'updated_at' => $brand['updated_at']
            ];
            
            echo json_encode([
                'success' => true,
                'data' => [$formattedBrand] // Return as array for consistency
            ]);
            return;
            
        } catch (PDOException $e) {
            error_log("Database error in brand GET by ID: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error']);
            return;
        }
    }
    
    // List brands with pagination and filters
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(10, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $tier = $_GET['tier'] ?? '';
    
    try {
        // Build query with filters
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (official_name LIKE ? OR country LIKE ? OR JSON_CONTAINS(LOWER(variations), LOWER(?)))";
            $searchTerm = "%$search%";
            $jsonSearch = json_encode(strtolower($search));
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $jsonSearch;
        }
        
        if (!empty($tier)) {
            $whereClause .= " AND quality_tier = ?";
            $params[] = $tier;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM asset_brands $whereClause";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $totalItems = $countStmt->fetchColumn();
        
        // Get brands with usage count
        $sql = "
            SELECT 
                b.id,
                b.official_name,
                b.variations,
                b.country,
                b.quality_tier,
                b.is_verified,
                b.is_active,
                b.created_at,
                COALESCE(asset_count.count, 0) as assets_count
            FROM asset_brands b
            LEFT JOIN (
                SELECT brand_id, COUNT(*) as count
                FROM inventory_items
                WHERE deleted_at IS NULL
                GROUP BY brand_id
            ) asset_count ON b.id = asset_count.brand_id
            $whereClause
            ORDER BY b.official_name ASC
            LIMIT $limit OFFSET $offset
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data
        $formattedBrands = array_map(function($brand) {
            return [
                'id' => (int)$brand['id'],
                'official_name' => $brand['official_name'],
                'variations' => json_decode($brand['variations'] ?: '[]'),
                'country' => $brand['country'],
                'quality_tier' => $brand['quality_tier'],
                'is_verified' => (bool)$brand['is_verified'],
                'is_active' => (bool)$brand['is_active'],
                'assets_count' => (int)$brand['assets_count'],
                'created_at' => $brand['created_at']
            ];
        }, $brands);
        
        $totalPages = ceil($totalItems / $limit);
        
        echo json_encode([
            'success' => true,
            'data' => $formattedBrands,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => (int)$totalItems,
                'items_per_page' => $limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in brands GET: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function handlePost($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        return;
    }
    
    // Validate required fields
    if (empty($input['official_name'])) {
        echo json_encode(['success' => false, 'message' => 'Official name is required']);
        return;
    }
    
    try {
        // Check if brand already exists
        $checkSql = "SELECT COUNT(*) FROM asset_brands WHERE LOWER(official_name) = LOWER(?)";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$input['official_name']]);
        
        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Brand already exists']);
            return;
        }
        
        // Prepare variations as JSON
        $variations = !empty($input['variations']) ? 
            json_encode(array_map('trim', $input['variations'])) : 
            json_encode([]);
        
        // Insert new brand
        $insertSql = "
            INSERT INTO asset_brands (
                official_name, variations, country, quality_tier, 
                is_verified, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, 1, NOW())
        ";
        
        $insertStmt = $db->prepare($insertSql);
        $insertStmt->execute([
            $input['official_name'],
            $variations,
            $input['country'] ?? '',
            $input['quality_tier'] ?? 'standard',
            !empty($input['is_verified']) ? 1 : 0
        ]);
        
        $newId = $db->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Brand created successfully',
            'data' => ['id' => $newId]
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in brands POST: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function handlePut($db) {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Brand ID is required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        return;
    }
    
    try {
        // Check if brand exists
        $checkSql = "SELECT id FROM asset_brands WHERE id = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$id]);
        
        if (!$checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Brand not found']);
            return;
        }
        
        // Build update query
        $updateFields = [];
        $params = [];
        
        if (isset($input['official_name'])) {
            $updateFields[] = "official_name = ?";
            $params[] = $input['official_name'];
        }
        if (isset($input['variations'])) {
            $updateFields[] = "variations = ?";
            $params[] = json_encode(array_map('trim', $input['variations']));
        }
        if (isset($input['country'])) {
            $updateFields[] = "country = ?";
            $params[] = $input['country'];
        }
        if (isset($input['quality_tier'])) {
            $updateFields[] = "quality_tier = ?";
            $params[] = $input['quality_tier'];
        }
        if (isset($input['is_verified'])) {
            $updateFields[] = "is_verified = ?";
            $params[] = $input['is_verified'] ? 1 : 0;
        }
        if (isset($input['is_active'])) {
            $updateFields[] = "is_active = ?";
            $params[] = $input['is_active'] ? 1 : 0;
        }
        
        if (empty($updateFields)) {
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }
        
        $updateSql = "UPDATE asset_brands SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $params[] = $id;
        
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Brand updated successfully']);
        
    } catch (PDOException $e) {
        error_log("Database error in brands PUT: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function handleDelete($db) {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Brand ID is required']);
        return;
    }
    
    try {
        // Check if brand has assets
        $assetsCheckSql = "SELECT COUNT(*) FROM inventory_items WHERE brand_id = ?";
        $assetsCheckStmt = $db->prepare($assetsCheckSql);
        $assetsCheckStmt->execute([$id]);
        
        if ($assetsCheckStmt->fetchColumn() > 0) {
            // Soft delete - just deactivate
            $deactivateSql = "UPDATE asset_brands SET is_active = 0 WHERE id = ?";
            $deactivateStmt = $db->prepare($deactivateSql);
            $deactivateStmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Brand deactivated (has associated assets)']);
        } else {
            // Hard delete - no assets
            $deleteSql = "DELETE FROM asset_brands WHERE id = ?";
            $deleteStmt = $db->prepare($deleteSql);
            $deleteStmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Brand deleted successfully']);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in brands DELETE: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>