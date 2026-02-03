<?php
/**
 * ConstructLink™ Borrowed Tool Batch Query Service
 *
 * Handles complex database queries for borrowed tool batches.
 * Extracted from BorrowedToolBatchModel to improve separation of concerns
 * and reduce model line count.
 *
 * Responsibilities:
 * - Complex filtered batch queries with pagination
 * - Batch retrieval with all items
 *
 * @package ConstructLink
 * @version 1.0.0
 */

class BorrowedToolBatchQueryService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get batch with all items
     *
     * Retrieves complete batch information including all borrowed items,
     * MVA workflow users, and asset details.
     *
     * @param int $batchId Batch ID
     * @param int|null $projectId Optional project ID for filtering
     * @return array|null Batch record with items array or null if not found
     */
    public function getBatchWithItems($batchId, $projectId = null) {
        try {
            $conditions = ["btb.id = ?"];
            $params = [$batchId];

            // Optional project filtering
            if ($projectId) {
                // Check if all items in batch belong to project
                $checkSql = "SELECT COUNT(DISTINCT a.project_id) as project_count
                           FROM borrowed_tools bt
                           INNER JOIN inventory_items a ON bt.inventory_item_id = a.id
                           WHERE bt.batch_id = ?";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([$batchId]);
                $result = $checkStmt->fetch();

                // If batch has items from multiple projects or wrong project, deny access
                if ($result['project_count'] > 1) {
                    return null; // Cross-project batch
                }
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            // Get batch info
            $batchSql = "
                SELECT btb.*,
                       u_issued.full_name as issued_by_name,
                       u_verified.full_name as verified_by_name,
                       u_approved.full_name as approved_by_name,
                       u_released.full_name as released_by_name,
                       u_returned.full_name as returned_by_name,
                       u_canceled.full_name as canceled_by_name
                FROM borrowed_tool_batches btb
                LEFT JOIN users u_issued ON btb.issued_by = u_issued.id
                LEFT JOIN users u_verified ON btb.verified_by = u_verified.id
                LEFT JOIN users u_approved ON btb.approved_by = u_approved.id
                LEFT JOIN users u_released ON btb.released_by = u_released.id
                LEFT JOIN users u_returned ON btb.returned_by = u_returned.id
                LEFT JOIN users u_canceled ON btb.canceled_by = u_canceled.id
                {$whereClause}
            ";

            $batchStmt = $this->db->prepare($batchSql);
            $batchStmt->execute($params);
            $batch = $batchStmt->fetch();

            if (!$batch) {
                return null;
            }

            // Get all items in batch
            $itemsSql = "
                SELECT bt.*,
                       a.name as asset_name,
                       a.ref as asset_ref,
                       a.acquisition_cost,
                       c.name as category_name,
                       et.name as equipment_type_name,
                       p.name as project_name
                FROM borrowed_tools bt
                INNER JOIN inventory_items a ON bt.inventory_item_id = a.id
                INNER JOIN categories c ON a.category_id = c.id
                LEFT JOIN equipment_types et ON a.equipment_type_id = et.id
                INNER JOIN projects p ON a.project_id = p.id
                WHERE bt.batch_id = ?
                ORDER BY a.name ASC
            ";

            $itemsStmt = $this->db->prepare($itemsSql);
            $itemsStmt->execute([$batchId]);
            $batch['items'] = $itemsStmt->fetchAll();

            return $batch;

        } catch (Exception $e) {
            error_log("Get batch with items error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get batches with filters and pagination
     *
     * @param array $filters Filter criteria (status, date_from, date_to, search, project_id)
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @return array Result with data and pagination metadata
     */
    public function getBatchesWithFilters($filters = [], $page = 1, $perPage = 20) {
        $conditions = [];
        $params = [];

        // Status filter
        if (!empty($filters['status'])) {
            $conditions[] = "btb.status = ?";
            $params[] = $filters['status'];
        }

        // Date range filters
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(btb.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(btb.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $conditions[] = "(btb.borrower_name LIKE ? OR btb.batch_reference LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }

        // Project filter
        if (!empty($filters['project_id'])) {
            $conditions[] = "EXISTS (
                SELECT 1 FROM borrowed_tools bt
                INNER JOIN inventory_items a ON bt.inventory_item_id = a.id
                WHERE bt.batch_id = btb.id AND a.project_id = ?
            )";
            $params[] = $filters['project_id'];
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Count total
        $countSql = "SELECT COUNT(*) FROM borrowed_tool_batches btb {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Get paginated data
        $offset = ($page - 1) * $perPage;

        $dataSql = "
            SELECT btb.*,
                   u_issued.full_name as issued_by_name
            FROM borrowed_tool_batches btb
            LEFT JOIN users u_issued ON btb.issued_by = u_issued.id
            {$whereClause}
            ORDER BY btb.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $dataStmt = $this->db->prepare($dataSql);
        $dataStmt->execute($params);
        $data = $dataStmt->fetchAll();

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1
            ]
        ];
    }
}
?>
