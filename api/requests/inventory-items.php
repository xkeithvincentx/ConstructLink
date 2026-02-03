<?php
/**
 * ConstructLink™ API - Get Inventory Items for Restock
 *
 * Returns consumable inventory items eligible for restock.
 * Used by restock request form to populate item selector dropdown.
 *
 * Query Parameters:
 * - project_id: Filter items by project (required for project-specific restock)
 * - low_stock_only: Only return items with low stock (optional, default: false)
 * - search: Search term for item name/ref (optional)
 *
 * @package ConstructLink
 * @version 1.0.0
 */

// Define APP_ROOT for this API context
define('APP_ROOT', dirname(__DIR__, 2));

require_once APP_ROOT . '/config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../core/utils/ResponseFormatter.php';
require_once '../../models/BaseModel.php';
require_once '../../models/AssetModel.php';
require_once '../../models/CategoryModel.php';
require_once '../../models/RequestModel.php';
require_once '../../services/Asset/AssetMatchingService.php';

use Services\Asset\AssetMatchingService;

// Start session for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Authenticate user
$auth = Auth::getInstance();
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Check permissions
$roleConfig = require APP_ROOT . '/config/roles.php';
$allowedRoles = $roleConfig['api/requests/inventory-items'] ?? ['System Admin'];

if (!$auth->hasRole($allowedRoles)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Permission denied'
    ]);
    exit;
}

try {
    $requestModel = new RequestModel();
    $matchingService = new AssetMatchingService();

    // Get query parameters
    $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
    $lowStockOnly = isset($_GET['low_stock_only']) && $_GET['low_stock_only'] === 'true';
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;

    // Get inventory items for restock
    $items = $requestModel->getInventoryItemsForRestock($projectId, $lowStockOnly);

    // Apply search filter if provided
    if ($search && !empty($items)) {
        $search = strtolower($search);
        $items = array_filter($items, function($item) use ($search) {
            $itemName = strtolower($item['name'] ?? '');
            $itemRef = strtolower($item['ref'] ?? '');
            $categoryName = strtolower($item['category_name'] ?? '');

            return strpos($itemName, $search) !== false ||
                   strpos($itemRef, $search) !== false ||
                   strpos($categoryName, $search) !== false;
        });

        // Re-index array after filtering
        $items = array_values($items);
    }

    // Format items for Select2 dropdown
    $formattedItems = array_map(function($item) {
        $stockStatus = '';
        $stockClass = 'success';

        if ($item['stock_level_percentage'] <= 10) {
            $stockStatus = '(Critical Stock)';
            $stockClass = 'danger';
        } elseif ($item['stock_level_percentage'] <= 20) {
            $stockStatus = '(Low Stock)';
            $stockClass = 'warning';
        }

        $hasActiveRestock = $item['active_restock_count'] > 0;

        return [
            'id' => $item['id'],
            'text' => sprintf(
                '%s - %s [%s/%s %s] %s',
                $item['ref'],
                $item['name'],
                $item['available_quantity'],
                $item['quantity'],
                $item['unit'],
                $stockStatus
            ),
            'ref' => $item['ref'],
            'name' => $item['name'],
            'category' => $item['category_name'],
            'total_quantity' => $item['quantity'],
            'available_quantity' => $item['available_quantity'],
            'consumed_quantity' => $item['consumed_quantity'],
            'unit' => $item['unit'],
            'unit_cost' => $item['unit_cost'],
            'stock_level_percentage' => $item['stock_level_percentage'],
            'stock_status' => $stockStatus,
            'stock_class' => $stockClass,
            'suggested_urgency' => $item['suggested_urgency'],
            'suggested_restock_quantity' => $item['consumed_quantity'], // Suggest restocking consumed amount
            'active_restock_count' => $item['active_restock_count'],
            'has_active_restock' => $hasActiveRestock,
            'disabled' => false // Can have multiple restock requests if needed
        ];
    }, $items);

    // Get statistics
    $criticalCount = count(array_filter($items, function($item) {
        return $item['stock_level_percentage'] <= 10;
    }));

    $lowStockCount = count(array_filter($items, function($item) {
        return $item['stock_level_percentage'] > 10 && $item['stock_level_percentage'] <= 20;
    }));

    echo json_encode([
        'success' => true,
        'items' => $formattedItems,
        'count' => count($formattedItems),
        'statistics' => [
            'total' => count($formattedItems),
            'critical_stock' => $criticalCount,
            'low_stock' => $lowStockCount,
            'normal_stock' => count($formattedItems) - $criticalCount - $lowStockCount
        ]
    ]);

} catch (Exception $e) {
    error_log("API inventory-items error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve inventory items'
    ]);
}
