<?php
/**
 * ConstructLink™ Asset Matching Service
 *
 * Provides intelligent matching and discovery services for inventory items.
 * Used primarily in restock workflow to find existing consumable items.
 *
 * Responsibilities:
 * - Find existing consumable items by criteria
 * - Suggest items requiring restock based on stock levels
 * - Prevent duplicate item creation
 * - Match items across projects with similar specifications
 *
 * @package ConstructLink
 * @version 1.0.0
 */

namespace Services\Asset;

// Load required core utilities
require_once __DIR__ . '/../../core/utils/ResponseFormatter.php';

use ResponseFormatter;
use AssetModel;
use CategoryModel;
use Database;
use Exception;

class AssetMatchingService {
    private $db;
    private $assetModel;
    private $categoryModel;

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     */
    public function __construct($db = null) {
        if ($db === null) {
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }

        $this->assetModel = new AssetModel($this->db);
        $this->categoryModel = new CategoryModel($this->db);
    }

    /**
     * Find existing consumable items matching specified criteria
     *
     * Searches for consumable inventory items that match provided criteria such as
     * category, name, project, specifications. Useful for identifying items that
     * can be restocked rather than procured as new items.
     *
     * @param array $criteria Search criteria
     *   - category_id: Filter by category
     *   - name: Item name (partial match supported)
     *   - project_id: Filter by project
     *   - specifications: Match specifications
     *   - model: Match model number
     *   - consumable_only: Limit to consumable items (default: true)
     * @return array Response with matched items
     */
    public function findExistingConsumableItem($criteria) {
        try {
            $conditions = [];
            $params = [];

            // Always filter for consumable items unless explicitly disabled
            if (!isset($criteria['consumable_only']) || $criteria['consumable_only'] !== false) {
                $conditions[] = "c.is_consumable = 1";
            }

            // Category filter
            if (!empty($criteria['category_id'])) {
                $conditions[] = "ii.category_id = ?";
                $params[] = $criteria['category_id'];
            }

            // Name filter (partial match, case-insensitive)
            if (!empty($criteria['name'])) {
                $conditions[] = "ii.name LIKE ?";
                $params[] = '%' . $criteria['name'] . '%';
            }

            // Project filter
            if (!empty($criteria['project_id'])) {
                $conditions[] = "ii.project_id = ?";
                $params[] = $criteria['project_id'];
            }

            // Specifications filter (partial match)
            if (!empty($criteria['specifications'])) {
                $conditions[] = "ii.specifications LIKE ?";
                $params[] = '%' . $criteria['specifications'] . '%';
            }

            // Model filter
            if (!empty($criteria['model'])) {
                $conditions[] = "ii.model = ?";
                $params[] = $criteria['model'];
            }

            // Status filter - default to available items
            if (!empty($criteria['status'])) {
                $conditions[] = "ii.status = ?";
                $params[] = $criteria['status'];
            } else {
                $conditions[] = "ii.status = 'available'";
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $sql = "
                SELECT
                    ii.id,
                    ii.ref,
                    ii.name,
                    ii.description,
                    ii.category_id,
                    ii.project_id,
                    ii.quantity as total_quantity,
                    ii.available_quantity,
                    (ii.quantity - ii.available_quantity) as consumed_quantity,
                    ii.unit,
                    ii.status,
                    ii.model,
                    ii.specifications,
                    ii.unit_cost,
                    c.name as category_name,
                    c.is_consumable,
                    p.name as project_name,
                    p.code as project_code,
                    -- Stock level percentage
                    CASE
                        WHEN ii.quantity > 0 THEN ROUND((ii.available_quantity / ii.quantity) * 100, 2)
                        ELSE 0
                    END as stock_level_percentage,
                    -- Check if active restock request exists
                    (SELECT COUNT(*)
                     FROM requests r
                     WHERE r.inventory_item_id = ii.id
                     AND r.is_restock = 1
                     AND r.status IN ('Draft', 'Submitted', 'Reviewed', 'Forwarded', 'Approved', 'Procured')
                    ) as active_restock_count
                FROM inventory_items ii
                LEFT JOIN categories c ON ii.category_id = c.id
                LEFT JOIN projects p ON ii.project_id = p.id
                {$whereClause}
                ORDER BY ii.name ASC, ii.created_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return ResponseFormatter::success('Items found', [
                'items' => $items,
                'count' => count($items)
            ]);

        } catch (Exception $e) {
            error_log("AssetMatchingService::findExistingConsumableItem error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to search for consumable items');
        }
    }

    /**
     * Suggest items requiring restock based on stock threshold
     *
     * Identifies consumable items that are running low on stock and suggests them
     * for restock. Uses configurable threshold (default 20%) or category-specific
     * low_stock_threshold if available.
     *
     * @param int|null $projectId Filter by project (null = all projects)
     * @param float $threshold Stock level threshold (0.0 to 1.0, default 0.2 = 20%)
     * @param int|null $limit Maximum number of suggestions (default: 50)
     * @return array Response with restock candidates
     */
    public function suggestRestockCandidates($projectId = null, $threshold = 0.2, $limit = 50) {
        try {
            $conditions = [];
            $params = [];

            // Only consumable items
            $conditions[] = "c.is_consumable = 1";

            // Only available items
            $conditions[] = "ii.status = 'available'";

            // Project filter if specified
            if ($projectId !== null) {
                $conditions[] = "ii.project_id = ?";
                $params[] = $projectId;
            }

            // Stock level threshold condition
            // Items with stock below threshold OR category-specific threshold
            $conditions[] = "(
                (ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= ?)
                OR (c.low_stock_threshold IS NOT NULL AND ii.available_quantity <= c.low_stock_threshold)
                OR ii.available_quantity = 0
            )";
            $params[] = $threshold;

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT
                    ii.id,
                    ii.ref,
                    ii.name,
                    ii.description,
                    ii.category_id,
                    ii.project_id,
                    ii.quantity as total_quantity,
                    ii.available_quantity,
                    (ii.quantity - ii.available_quantity) as consumed_quantity,
                    ii.unit,
                    ii.unit_cost,
                    c.name as category_name,
                    c.low_stock_threshold as category_threshold,
                    p.name as project_name,
                    p.code as project_code,
                    -- Stock level calculation
                    CASE
                        WHEN ii.quantity > 0 THEN ROUND((ii.available_quantity / ii.quantity) * 100, 2)
                        ELSE 0
                    END as stock_level_percentage,
                    -- Suggested restock quantity (to bring back to original total)
                    (ii.quantity - ii.available_quantity) as suggested_restock_quantity,
                    -- Urgency based on stock level
                    CASE
                        WHEN ii.available_quantity = 0 THEN 'Critical'
                        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.1 THEN 'Critical'
                        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.2 THEN 'Urgent'
                        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.3 THEN 'Normal'
                        ELSE 'Low'
                    END as suggested_urgency,
                    -- Active restock requests
                    (SELECT COUNT(*)
                     FROM requests r
                     WHERE r.inventory_item_id = ii.id
                     AND r.is_restock = 1
                     AND r.status IN ('Draft', 'Submitted', 'Reviewed', 'Forwarded', 'Approved', 'Procured')
                    ) as active_restock_count,
                    -- Last procurement date
                    (SELECT MAX(po.created_at)
                     FROM procurement_orders po
                     WHERE po.id = ii.procurement_order_id
                    ) as last_procurement_date
                FROM inventory_items ii
                LEFT JOIN categories c ON ii.category_id = c.id
                LEFT JOIN projects p ON ii.project_id = p.id
                {$whereClause}
                ORDER BY
                    -- Prioritize by urgency
                    CASE
                        WHEN ii.available_quantity = 0 THEN 1
                        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.1 THEN 2
                        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.2 THEN 3
                        ELSE 4
                    END,
                    ii.available_quantity ASC,
                    ii.name ASC
                LIMIT ?
            ";

            $params[] = $limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Categorize by urgency
            $categorized = [
                'critical' => [],
                'urgent' => [],
                'normal' => [],
                'low' => []
            ];

            foreach ($candidates as $candidate) {
                $urgency = strtolower($candidate['suggested_urgency']);
                if (isset($categorized[$urgency])) {
                    $categorized[$urgency][] = $candidate;
                }
            }

            return ResponseFormatter::success('Restock candidates identified', [
                'candidates' => $candidates,
                'categorized' => $categorized,
                'total_count' => count($candidates),
                'summary' => [
                    'critical' => count($categorized['critical']),
                    'urgent' => count($categorized['urgent']),
                    'normal' => count($categorized['normal']),
                    'low' => count($categorized['low'])
                ]
            ]);

        } catch (Exception $e) {
            error_log("AssetMatchingService::suggestRestockCandidates error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to identify restock candidates');
        }
    }

    /**
     * Check if item data represents a duplicate of existing item
     *
     * Prevents accidental creation of duplicate consumable items by checking
     * for existing items with same key characteristics.
     *
     * @param array $itemData Item data to check
     *   - name: Item name (required)
     *   - category_id: Category ID (required)
     *   - project_id: Project ID (optional)
     *   - model: Model number (optional)
     *   - specifications: Specifications (optional)
     * @return array Response indicating if duplicate exists
     */
    public function checkDuplicate($itemData) {
        try {
            // Validate required fields
            if (empty($itemData['name']) || empty($itemData['category_id'])) {
                return ResponseFormatter::validationError([
                    'name' => 'Item name is required',
                    'category_id' => 'Category ID is required'
                ]);
            }

            $conditions = [];
            $params = [];

            // Exact name match (case-insensitive)
            $conditions[] = "LOWER(ii.name) = LOWER(?)";
            $params[] = $itemData['name'];

            // Same category
            $conditions[] = "ii.category_id = ?";
            $params[] = $itemData['category_id'];

            // Same project if specified
            if (!empty($itemData['project_id'])) {
                $conditions[] = "ii.project_id = ?";
                $params[] = $itemData['project_id'];
            }

            // Same model if specified
            if (!empty($itemData['model'])) {
                $conditions[] = "(ii.model = ? OR ii.model IS NULL)";
                $params[] = $itemData['model'];
            }

            // Status must be available (ignore disposed/deleted items)
            $conditions[] = "ii.status IN ('available', 'borrowed', 'in_maintenance')";

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT
                    ii.id,
                    ii.ref,
                    ii.name,
                    ii.category_id,
                    ii.project_id,
                    ii.model,
                    ii.specifications,
                    ii.quantity,
                    ii.available_quantity,
                    ii.status,
                    c.name as category_name,
                    c.is_consumable,
                    p.name as project_name
                FROM inventory_items ii
                LEFT JOIN categories c ON ii.category_id = c.id
                LEFT JOIN projects p ON ii.project_id = p.id
                {$whereClause}
                LIMIT 5
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $duplicates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $isDuplicate = count($duplicates) > 0;

            if ($isDuplicate) {
                return ResponseFormatter::success('Potential duplicates found', [
                    'is_duplicate' => true,
                    'duplicates' => $duplicates,
                    'count' => count($duplicates),
                    'suggestion' => 'Consider creating a restock request instead of a new item'
                ]);
            }

            return ResponseFormatter::success('No duplicates found', [
                'is_duplicate' => false,
                'duplicates' => [],
                'count' => 0
            ]);

        } catch (Exception $e) {
            error_log("AssetMatchingService::checkDuplicate error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to check for duplicates');
        }
    }

    /**
     * Get consumable items for a specific project
     *
     * Retrieves all consumable items assigned to a project, useful for
     * populating dropdown selectors in restock request forms.
     *
     * @param int $projectId Project ID
     * @param bool $lowStockOnly Only return items with low stock (default: false)
     * @return array Response with project's consumable items
     */
    public function getProjectConsumables($projectId, $lowStockOnly = false) {
        try {
            $conditions = ["ii.project_id = ?", "c.is_consumable = 1", "ii.status = 'available'"];
            $params = [$projectId];

            if ($lowStockOnly) {
                $conditions[] = "(
                    (ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.2)
                    OR ii.available_quantity = 0
                )";
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT
                    ii.id,
                    ii.ref,
                    ii.name,
                    ii.description,
                    ii.quantity,
                    ii.available_quantity,
                    ii.unit,
                    ii.unit_cost,
                    c.name as category_name,
                    CASE
                        WHEN ii.quantity > 0 THEN ROUND((ii.available_quantity / ii.quantity) * 100, 2)
                        ELSE 0
                    END as stock_level_percentage
                FROM inventory_items ii
                LEFT JOIN categories c ON ii.category_id = c.id
                {$whereClause}
                ORDER BY c.name ASC, ii.name ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return ResponseFormatter::success('Project consumables retrieved', [
                'items' => $items,
                'count' => count($items)
            ]);

        } catch (Exception $e) {
            error_log("AssetMatchingService::getProjectConsumables error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to retrieve project consumables');
        }
    }
}
