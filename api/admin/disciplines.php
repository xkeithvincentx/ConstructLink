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

// Helper function to check if column exists
function columnExists($tableName, $columnName) {
    static $cache = [];
    $key = "$tableName.$columnName";
    
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
        $stmt->execute([$columnName]);
        $exists = $stmt->fetch() !== false;
        $cache[$key] = $exists;
        return $exists;
    } catch (Exception $e) {
        $cache[$key] = false;
        return false;
    }
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
    error_log("Disciplines API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGet($db) {
    $id = intval($_GET['id'] ?? 0);
    
    // If ID is provided, return single discipline
    if ($id) {
        try {
            $sql = "
                SELECT 
                    d.id,
                    d.code,
                    " . (columnExists('asset_disciplines', 'iso_code') ? 'd.iso_code,' : 'NULL as iso_code,') . "
                    d.name,
                    d.description,
                    d.parent_id,
                    p.name as parent_name,
                    d.display_order as sort_order,
                    d.is_active,
                    d.created_at,
                    COALESCE(asset_count.count, 0) as assets_count
                FROM asset_disciplines d
                LEFT JOIN asset_disciplines p ON d.parent_id = p.id
                LEFT JOIN (
                    SELECT 
                        d_inner.id as discipline_id,
                        COUNT(DISTINCT a.id) as count
                    FROM asset_disciplines d_inner
                    LEFT JOIN assets a ON (
                        a.discipline_tags IS NOT NULL 
                        AND a.discipline_tags LIKE CONCAT('%', d_inner.iso_code, '%')
                        " . (columnExists('assets', 'deleted_at') ? 'AND a.deleted_at IS NULL' : '') . "
                    )
                    GROUP BY d_inner.id
                ) asset_count ON d.id = asset_count.discipline_id
                WHERE d.id = ?
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            $discipline = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($discipline) {
                $formattedDiscipline = [
                    'id' => (int)$discipline['id'],
                    'code' => $discipline['code'],
                    'iso_code' => $discipline['iso_code'],
                    'name' => $discipline['name'],
                    'description' => $discipline['description'],
                    'parent_id' => $discipline['parent_id'] ? (int)$discipline['parent_id'] : null,
                    'parent_name' => $discipline['parent_name'],
                    'sort_order' => (int)$discipline['sort_order'],
                    'is_active' => (bool)$discipline['is_active'],
                    'assets_count' => (int)$discipline['assets_count'],
                    'created_at' => $discipline['created_at']
                ];
                
                echo json_encode(['success' => true, 'data' => $formattedDiscipline]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Discipline not found']);
            }
            return;
            
        } catch (PDOException $e) {
            error_log("Database error in disciplines GET by ID: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error']);
            return;
        }
    }
    
    // List all disciplines with pagination and search
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(10, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    
    try {
        // Build query with search
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (d.code LIKE ? OR d.name LIKE ? OR d.description LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM asset_disciplines d $whereClause";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $totalItems = $countStmt->fetchColumn();
        
        // Get disciplines with parent information and asset counts
        $sql = "
            SELECT 
                d.id,
                d.code,
                d.iso_code,
                d.name,
                d.description,
                d.parent_id,
                p.name as parent_name,
                d.display_order as sort_order,
                d.is_active,
                d.created_at,
                COALESCE(asset_count.count, 0) as assets_count
            FROM asset_disciplines d
            LEFT JOIN asset_disciplines p ON d.parent_id = p.id
            LEFT JOIN (
                SELECT 
                    d_inner.id as discipline_id,
                    COUNT(DISTINCT a.id) as count
                FROM asset_disciplines d_inner
                LEFT JOIN assets a ON (
                    a.discipline_tags IS NOT NULL 
                    AND a.discipline_tags LIKE CONCAT('%', d_inner.iso_code, '%')
                    " . (columnExists('assets', 'deleted_at') ? 'AND a.deleted_at IS NULL' : '') . "
                )
                GROUP BY d_inner.id
            ) asset_count ON d.id = asset_count.discipline_id
            $whereClause
            ORDER BY d.parent_id ASC, d.display_order ASC, d.name ASC
            LIMIT $limit OFFSET $offset
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data
        $formattedDisciplines = array_map(function($discipline) {
            return [
                'id' => (int)$discipline['id'],
                'code' => $discipline['code'],
                'iso_code' => $discipline['iso_code'],
                'name' => $discipline['name'],
                'description' => $discipline['description'],
                'parent_id' => $discipline['parent_id'] ? (int)$discipline['parent_id'] : null,
                'parent_name' => $discipline['parent_name'],
                'sort_order' => (int)$discipline['sort_order'],
                'is_active' => (bool)$discipline['is_active'],
                'assets_count' => (int)$discipline['assets_count'],
                'created_at' => $discipline['created_at']
            ];
        }, $disciplines);
        
        $totalPages = ceil($totalItems / $limit);
        
        echo json_encode([
            'success' => true,
            'data' => $formattedDisciplines,
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
        error_log("Database error in disciplines GET: " . $e->getMessage());
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
    $required = ['code', 'name'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }
    
    try {
        // Check if code already exists
        $checkSql = "SELECT COUNT(*) FROM asset_disciplines WHERE code = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$input['code']]);
        
        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Discipline code already exists']);
            return;
        }
        
        // Validate parent_id if provided
        if (!empty($input['parent_id'])) {
            $parentCheckSql = "SELECT COUNT(*) FROM asset_disciplines WHERE id = ? AND is_active = 1";
            $parentCheckStmt = $db->prepare($parentCheckSql);
            $parentCheckStmt->execute([$input['parent_id']]);
            
            if ($parentCheckStmt->fetchColumn() == 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid parent discipline']);
                return;
            }
        }
        
        // Get next sort order
        $sortOrderSql = "SELECT COALESCE(MAX(sort_order), 0) + 1 FROM asset_disciplines WHERE parent_id " . 
                        (empty($input['parent_id']) ? "IS NULL" : "= ?");
        $sortOrderStmt = $db->prepare($sortOrderSql);
        if (!empty($input['parent_id'])) {
            $sortOrderStmt->execute([$input['parent_id']]);
        } else {
            $sortOrderStmt->execute();
        }
        $sortOrder = $sortOrderStmt->fetchColumn();
        
        // Insert new discipline
        $insertSql = "
            INSERT INTO asset_disciplines (code, iso_code, name, description, parent_id, sort_order, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ";
        
        $insertStmt = $db->prepare($insertSql);
        $insertStmt->execute([
            $input['code'],
            !empty($input['iso_code']) ? $input['iso_code'] : null,
            $input['name'],
            $input['description'] ?? '',
            !empty($input['parent_id']) ? $input['parent_id'] : null,
            $sortOrder
        ]);
        
        $newId = $db->lastInsertId();
        
        // Return the created discipline
        $selectSql = "
            SELECT 
                d.id, d.code, d.name, d.description, d.parent_id,
                p.name as parent_name, d.sort_order, d.is_active, d.created_at
            FROM asset_disciplines d
            LEFT JOIN asset_disciplines p ON d.parent_id = p.id
            WHERE d.id = ?
        ";
        $selectStmt = $db->prepare($selectSql);
        $selectStmt->execute([$newId]);
        $newDiscipline = $selectStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Discipline created successfully',
            'data' => [
                'id' => (int)$newDiscipline['id'],
                'code' => $newDiscipline['code'],
                'name' => $newDiscipline['name'],
                'description' => $newDiscipline['description'],
                'parent_id' => $newDiscipline['parent_id'] ? (int)$newDiscipline['parent_id'] : null,
                'parent_name' => $newDiscipline['parent_name'],
                'sort_order' => (int)$newDiscipline['sort_order'],
                'is_active' => (bool)$newDiscipline['is_active'],
                'assets_count' => 0,
                'created_at' => $newDiscipline['created_at']
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in disciplines POST: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function handlePut($db) {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Discipline ID is required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        return;
    }
    
    try {
        // Check if discipline exists
        $checkSql = "SELECT id, code FROM asset_disciplines WHERE id = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$id]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Discipline not found']);
            return;
        }
        
        // Check if code conflicts (if being changed)
        if (!empty($input['code']) && $input['code'] !== $existing['code']) {
            $codeCheckSql = "SELECT COUNT(*) FROM asset_disciplines WHERE code = ? AND id != ?";
            $codeCheckStmt = $db->prepare($codeCheckSql);
            $codeCheckStmt->execute([$input['code'], $id]);
            
            if ($codeCheckStmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Discipline code already exists']);
                return;
            }
        }
        
        // Validate parent_id if provided
        if (isset($input['parent_id']) && !empty($input['parent_id'])) {
            // Prevent self-reference
            if ($input['parent_id'] == $id) {
                echo json_encode(['success' => false, 'message' => 'Discipline cannot be its own parent']);
                return;
            }
            
            $parentCheckSql = "SELECT COUNT(*) FROM asset_disciplines WHERE id = ? AND is_active = 1";
            $parentCheckStmt = $db->prepare($parentCheckSql);
            $parentCheckStmt->execute([$input['parent_id']]);
            
            if ($parentCheckStmt->fetchColumn() == 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid parent discipline']);
                return;
            }
        }
        
        // Build update query
        $updateFields = [];
        $params = [];
        
        if (!empty($input['code'])) {
            $updateFields[] = "code = ?";
            $params[] = $input['code'];
        }
        if (!empty($input['name'])) {
            $updateFields[] = "name = ?";
            $params[] = $input['name'];
        }
        if (isset($input['description'])) {
            $updateFields[] = "description = ?";
            $params[] = $input['description'];
        }
        if (isset($input['parent_id'])) {
            $updateFields[] = "parent_id = ?";
            $params[] = !empty($input['parent_id']) ? $input['parent_id'] : null;
        }
        if (isset($input['is_active'])) {
            $updateFields[] = "is_active = ?";
            $params[] = $input['is_active'] ? 1 : 0;
        }
        
        if (empty($updateFields)) {
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }
        
        $updateSql = "UPDATE asset_disciplines SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $params[] = $id;
        
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Discipline updated successfully']);
        
    } catch (PDOException $e) {
        error_log("Database error in disciplines PUT: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function handleDelete($db) {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Discipline ID is required']);
        return;
    }
    
    try {
        // Check if discipline exists
        $checkSql = "SELECT id FROM asset_disciplines WHERE id = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$id]);
        
        if (!$checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Discipline not found']);
            return;
        }
        
        // Check if discipline has children
        $childrenCheckSql = "SELECT COUNT(*) FROM asset_disciplines WHERE parent_id = ?";
        $childrenCheckStmt = $db->prepare($childrenCheckSql);
        $childrenCheckStmt->execute([$id]);
        
        if ($childrenCheckStmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete discipline with child disciplines']);
            return;
        }
        
        // Check if discipline has assets
        $assetsCheckSql = "SELECT COUNT(*) FROM asset_discipline_mappings WHERE discipline_id = ?";
        $assetsCheckStmt = $db->prepare($assetsCheckSql);
        $assetsCheckStmt->execute([$id]);
        
        if ($assetsCheckStmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete discipline with associated assets']);
            return;
        }
        
        // Delete the discipline
        $deleteSql = "DELETE FROM asset_disciplines WHERE id = ?";
        $deleteStmt = $db->prepare($deleteSql);
        $deleteStmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Discipline deleted successfully']);
        
    } catch (PDOException $e) {
        error_log("Database error in disciplines DELETE: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>