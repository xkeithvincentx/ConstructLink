<?php
/**
 * RequestRestockModel - Restock Request Management
 *
 * Handles restock-specific request operations including validation,
 * inventory item retrieval, and restock details.
 * Single Responsibility: Restock request data and validation.
 *
 * @package ConstructLink\Models\Request
 */

class RequestRestockModel extends BaseModel {
    protected $table = 'requests';

    /**
     * Get restock request details with inventory item information
     *
     * Retrieves complete restock request data including linked inventory item details,
     * current stock levels, and consumption statistics.
     *
     * @param int $requestId Request ID
     * @return array|false Request with inventory item details or false on error
     */
    public function getRestockDetails($requestId) {
        try {
            $sql = "
                SELECT r.*,
                       p.name as project_name,
                       p.code as project_code,
                       u1.full_name as requested_by_name,
                       u2.full_name as reviewed_by_name,
                       u3.full_name as approved_by_name,
                       -- Inventory item details
                       ii.id as item_id,
                       ii.ref as item_ref,
                       ii.name as item_name,
                       ii.description as item_description,
                       ii.quantity as current_total_quantity,
                       ii.available_quantity as current_available_quantity,
                       (ii.quantity - ii.available_quantity) as consumed_quantity,
                       ii.unit as item_unit,
                       ii.unit_cost,
                       ii.status as item_status,
                       -- Category details
                       c.name as category_name,
                       c.is_consumable,
                       -- Stock level calculation
                       CASE
                           WHEN ii.quantity > 0 THEN ROUND((ii.available_quantity / ii.quantity) * 100, 2)
                           ELSE 0
                       END as stock_level_percentage,
                       -- Procurement details if linked
                       po.po_number,
                       po.status as procurement_status
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                LEFT JOIN users u2 ON r.reviewed_by = u2.id
                LEFT JOIN users u3 ON r.approved_by = u3.id
                LEFT JOIN inventory_items ii ON r.inventory_item_id = ii.id
                LEFT JOIN categories c ON ii.category_id = c.id
                LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                WHERE r.id = ?
                  AND r.is_restock = 1
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$requestId]);
            return $stmt->fetch();

        } catch (Exception $e) {
            error_log("Get restock details error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate restock request data
     *
     * Ensures inventory item exists, is consumable, and request data is valid.
     *
     * @param array $data Request data to validate
     * @return array Validation result with errors if any
     */
    public function validateRestockRequest($data) {
        $errors = [];

        try {
            // Check if inventory_item_id is provided
            if (empty($data['inventory_item_id'])) {
                $errors[] = 'Inventory item is required for restock requests';
                return ['valid' => false, 'errors' => $errors];
            }

            // Fetch inventory item
            $sql = "
                SELECT ii.*, c.is_consumable, c.name as category_name
                FROM inventory_items ii
                LEFT JOIN categories c ON ii.category_id = c.id
                WHERE ii.id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$data['inventory_item_id']]);
            $item = $stmt->fetch();

            if (!$item) {
                $errors[] = 'Inventory item not found';
                return ['valid' => false, 'errors' => $errors];
            }

            // Validate item is consumable
            if ($item['is_consumable'] != 1) {
                $errors[] = 'Only consumable items can be restocked. Selected item is not consumable.';
            }

            // Validate item status
            $validStatuses = ['available', 'borrowed', 'in_maintenance'];
            if (!in_array($item['status'], $validStatuses)) {
                $errors[] = "Item status '{$item['status']}' is not eligible for restock";
            }

            // Validate quantity if provided
            if (isset($data['quantity']) && $data['quantity'] <= 0) {
                $errors[] = 'Restock quantity must be greater than zero';
            }

            // Validate project matches if specified
            if (!empty($data['project_id']) && $item['project_id'] != $data['project_id']) {
                $errors[] = 'Selected item belongs to a different project';
            }

            if (!empty($errors)) {
                return ['valid' => false, 'errors' => $errors];
            }

            return [
                'valid' => true,
                'errors' => [],
                'item' => $item
            ];

        } catch (Exception $e) {
            error_log("Validate restock request error: " . $e->getMessage());
            $errors[] = 'Failed to validate restock request';
            return ['valid' => false, 'errors' => $errors];
        }
    }

    /**
     * Get inventory items eligible for restock
     *
     * Returns consumable items that can be restocked, optionally filtered by project.
     * Includes current stock levels and consumption statistics.
     *
     * @param int|null $projectId Filter by project (null = all projects)
     * @param bool $lowStockOnly Only return low stock items (default: false)
     * @return array Array of eligible inventory items
     */
    public function getInventoryItemsForRestock($projectId = null, $lowStockOnly = false) {
        try {
            $conditions = ["c.is_consumable = 1", "ii.status = 'available'"];
            $params = [];

            if ($projectId !== null) {
                $conditions[] = "ii.project_id = ?";
                $params[] = $projectId;
            }

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
                    (ii.quantity - ii.available_quantity) as consumed_quantity,
                    ii.unit,
                    ii.unit_cost,
                    c.name as category_name,
                    p.name as project_name,
                    p.code as project_code,
                    -- Stock level percentage
                    CASE
                        WHEN ii.quantity > 0 THEN ROUND((ii.available_quantity / ii.quantity) * 100, 2)
                        ELSE 0
                    END as stock_level_percentage,
                    -- Active restock requests count
                    (SELECT COUNT(*)
                     FROM requests r
                     WHERE r.inventory_item_id = ii.id
                     AND r.is_restock = 1
                     AND r.status IN ('Draft', 'Submitted', 'Reviewed', 'Forwarded', 'Approved', 'Procured')
                    ) as active_restock_count,
                    -- Suggested urgency
                    CASE
                        WHEN ii.available_quantity = 0 THEN 'Critical'
                        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.1 THEN 'Critical'
                        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.2 THEN 'Urgent'
                        ELSE 'Normal'
                    END as suggested_urgency
                FROM inventory_items ii
                LEFT JOIN categories c ON ii.category_id = c.id
                LEFT JOIN projects p ON ii.project_id = p.id
                {$whereClause}
                ORDER BY
                    CASE
                        WHEN ii.available_quantity = 0 THEN 1
                        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.1 THEN 2
                        ELSE 3
                    END,
                    c.name ASC,
                    ii.name ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get inventory items for restock error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get low stock items that need restocking
     *
     * @param int|null $projectId Optional project filter
     * @param float $threshold Stock level threshold (default: 20%)
     * @return array Array of low stock items
     */
    public function getLowStockItems($projectId = null, $threshold = 0.2) {
        try {
            $conditions = ["c.is_consumable = 1", "ii.status = 'available'"];
            $params = [];

            if ($projectId !== null) {
                $conditions[] = "ii.project_id = ?";
                $params[] = $projectId;
            }

            $conditions[] = "(
                (ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= ?)
                OR ii.available_quantity = 0
            )";
            $params[] = $threshold;

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT
                    ii.id,
                    ii.ref,
                    ii.name,
                    ii.quantity,
                    ii.available_quantity,
                    ii.unit,
                    c.name as category_name,
                    p.name as project_name,
                    CASE
                        WHEN ii.quantity > 0 THEN ROUND((ii.available_quantity / ii.quantity) * 100, 2)
                        ELSE 0
                    END as stock_level_percentage,
                    CASE
                        WHEN ii.available_quantity = 0 THEN 'Critical'
                        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.1 THEN 'Critical'
                        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.2 THEN 'Urgent'
                        ELSE 'Normal'
                    END as urgency_level
                FROM inventory_items ii
                LEFT JOIN categories c ON ii.category_id = c.id
                LEFT JOIN projects p ON ii.project_id = p.id
                {$whereClause}
                ORDER BY
                    CASE
                        WHEN ii.available_quantity = 0 THEN 1
                        WHEN ii.quantity > 0 AND (ii.available_quantity / ii.quantity) <= 0.1 THEN 2
                        ELSE 3
                    END,
                    ii.name ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get low stock items error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get restock statistics
     *
     * @param int|null $projectId Optional project filter
     * @param string|null $dateFrom Start date filter
     * @param string|null $dateTo End date filter
     * @return array Restock statistics
     */
    public function getRestockStatistics($projectId = null, $dateFrom = null, $dateTo = null) {
        try {
            $conditions = ["is_restock = 1"];
            $params = [];

            if ($projectId) {
                $conditions[] = "project_id = ?";
                $params[] = $projectId;
            }

            if ($dateFrom) {
                $conditions[] = "DATE(created_at) >= ?";
                $params[] = $dateFrom;
            }

            if ($dateTo) {
                $conditions[] = "DATE(created_at) <= ?";
                $params[] = $dateTo;
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT
                    COUNT(*) as total_restock_requests,
                    SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = 'Submitted' THEN 1 ELSE 0 END) as submitted,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Procured' THEN 1 ELSE 0 END) as procured,
                    SUM(CASE WHEN status = 'Declined' THEN 1 ELSE 0 END) as declined,
                    SUM(CASE WHEN urgency = 'Critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN urgency = 'Urgent' THEN 1 ELSE 0 END) as urgent,
                    AVG(CASE WHEN estimated_cost IS NOT NULL THEN estimated_cost ELSE 0 END) as avg_cost
                FROM requests
                {$whereClause}
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            return $result ?: [
                'total_restock_requests' => 0,
                'draft' => 0,
                'submitted' => 0,
                'approved' => 0,
                'procured' => 0,
                'declined' => 0,
                'critical' => 0,
                'urgent' => 0,
                'avg_cost' => 0
            ];

        } catch (Exception $e) {
            error_log("Get restock statistics error: " . $e->getMessage());
            return [
                'total_restock_requests' => 0,
                'draft' => 0,
                'submitted' => 0,
                'approved' => 0,
                'procured' => 0,
                'declined' => 0,
                'critical' => 0,
                'urgent' => 0,
                'avg_cost' => 0
            ];
        }
    }
}
?>
