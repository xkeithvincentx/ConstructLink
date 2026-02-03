<?php
/**
 * ConstructLink™ Borrowed Tool Query Service
 *
 * Handles complex database queries for borrowed tools.
 * Extracted from BorrowedToolModel to improve separation of concerns
 * and reduce model line count.
 *
 * Responsibilities:
 * - Complex filtered queries with pagination
 * - Detailed record retrieval with joins
 * - MVA workflow detail queries
 *
 * @package ConstructLink
 * @version 1.0.0
 */

require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';

class BorrowedToolQueryService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get borrowed tools with filters and pagination
     *
     * @param array $filters Filter criteria (status, priority, date_from, date_to, search, project_id, sort_by, sort_order)
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @return array Result with data and pagination metadata
     */
    public function getBorrowedToolsWithFilters($filters = [], $page = 1, $perPage = 20) {
        $conditions = [];
        $params = [];

        // Apply filters
        if (!empty($filters['status'])) {
            if ($filters['status'] === BorrowedToolStatus::OVERDUE || $filters['status'] === 'overdue') {
                // Check both single items and batches for overdue status
                $conditions[] = "((bt.status = ? AND bt.expected_return < CURDATE())
                                 OR (btb.status = ? AND btb.expected_return < CURDATE()))";
                $params[] = BorrowedToolStatus::BORROWED;
                $params[] = BorrowedToolStatus::BORROWED;
            } else {
                // Match status against both tables using COALESCE logic (matches SELECT clause)
                // This ensures WHERE filtering matches the displayed status values
                $conditions[] = "((btb.status IS NOT NULL AND btb.status = ?)
                                 OR (btb.status IS NULL AND bt.status = ?))";
                $params[] = $filters['status'];
                $params[] = $filters['status'];
            }
        }
        // No else clause - show all statuses by default (including Canceled and Returned)

        // Priority filter
        if (!empty($filters['priority'])) {
            if ($filters['priority'] === 'overdue') {
                // Check both single items and batches for overdue items
                $conditions[] = "((bt.status = ? AND bt.expected_return < CURDATE())
                                 OR (btb.status = ? AND btb.expected_return < CURDATE()))";
                $params[] = BorrowedToolStatus::BORROWED;
                $params[] = BorrowedToolStatus::BORROWED;
            } elseif ($filters['priority'] === 'due_soon') {
                // Check both single items and batches for items due within 3 days
                $conditions[] = "((bt.status = ? AND bt.expected_return BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY))
                                 OR (btb.status = ? AND btb.expected_return BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)))";
                $params[] = BorrowedToolStatus::BORROWED;
                $params[] = BorrowedToolStatus::BORROWED;
            } elseif ($filters['priority'] === 'pending_action') {
                // Check both tables for pending statuses using COALESCE logic
                $conditions[] = "(COALESCE(btb.status, bt.status) IN (?, ?))";
                $params[] = BorrowedToolStatus::PENDING_VERIFICATION;
                $params[] = BorrowedToolStatus::PENDING_APPROVAL;
            }
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(bt.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(bt.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(a.name LIKE ? OR a.ref LIKE ? OR bt.borrower_name LIKE ? OR bt.purpose LIKE ? OR btb.batch_reference LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        // Filter by project if specified
        if (!empty($filters['project_id'])) {
            $conditions[] = "a.project_id = ?";
            $params[] = $filters['project_id'];
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Build ORDER BY clause
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = strtoupper($filters['sort_order'] ?? 'DESC');

        // Map sort column to SQL expression
        $orderByMap = [
            'id' => 'bt.id',
            'reference' => 'COALESCE(btb.batch_reference, CONCAT("BT-", LPAD(bt.id, 6, "0")))',
            'borrower' => 'bt.borrower_name',
            'status' => sprintf('CASE bt.status
                WHEN "%s" THEN 1
                WHEN "%s" THEN 2
                WHEN "%s" THEN 3
                WHEN "%s" THEN 4
                WHEN "%s" THEN 5
                WHEN "%s" THEN 6
                WHEN "%s" THEN 7
                ELSE 8
            END',
                BorrowedToolStatus::PENDING_VERIFICATION,
                BorrowedToolStatus::PENDING_APPROVAL,
                BorrowedToolStatus::APPROVED,
                BorrowedToolStatus::BORROWED,
                BorrowedToolStatus::PARTIALLY_RETURNED,
                BorrowedToolStatus::RETURNED,
                BorrowedToolStatus::CANCELED
            ),
            'date' => 'bt.created_at',
            'items' => 'bt.quantity',
            'created_at' => 'bt.created_at'
        ];

        $orderByColumn = $orderByMap[$sortBy] ?? 'bt.created_at';
        $orderByClause = "ORDER BY {$orderByColumn} {$sortOrder}";

        // BATCH-AWARE PAGINATION FIX
        // Problem: The view groups records by batch_id, so 5 DB records might become 4 display items
        // Solution: Get distinct batch/single groups, paginate those, then fetch all their records

        // Step 1: Get distinct batch identifiers (batch_id or 'single_X' for non-batched items)
        $groupsSql = "
            SELECT DISTINCT
                   COALESCE(bt.batch_id, CONCAT('single_', bt.id)) as display_group,
                   bt.batch_id,
                   CASE WHEN bt.batch_id IS NULL THEN bt.id ELSE NULL END as single_id,
                   MIN(bt.id) as min_id
            FROM borrowed_tools bt
            INNER JOIN inventory_items a ON bt.inventory_item_id = a.id
            INNER JOIN categories c ON a.category_id = c.id
            INNER JOIN projects p ON a.project_id = p.id
            LEFT JOIN users u ON bt.issued_by = u.id
            LEFT JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
            {$whereClause}
            GROUP BY display_group, bt.batch_id, single_id
            {$orderByClause}
        ";

        $stmt = $this->db->prepare($groupsSql);
        $stmt->execute($params);
        $allGroups = $stmt->fetchAll();

        // Step 2: Count total display items (matches what user sees in UI)
        $total = count($allGroups);

        // Step 3: Paginate the groups array in PHP
        $offset = ($page - 1) * $perPage;
        $paginatedGroups = array_slice($allGroups, $offset, $perPage);

        // Step 4: Extract batch IDs and single IDs from paginated groups
        $batchIds = [];
        $singleIds = [];
        foreach ($paginatedGroups as $group) {
            if ($group['batch_id'] !== null) {
                $batchIds[] = $group['batch_id'];
            } else {
                $singleIds[] = $group['single_id'];
            }
        }

        // Step 5: Fetch all records belonging to these paginated groups
        if (empty($batchIds) && empty($singleIds)) {
            $data = [];
        } else {
            $fetchConditions = [];
            $fetchParams = [];

            if (!empty($batchIds)) {
                $batchPlaceholders = implode(',', array_fill(0, count($batchIds), '?'));
                $fetchConditions[] = "bt.batch_id IN ({$batchPlaceholders})";
                $fetchParams = array_merge($fetchParams, $batchIds);
            }

            if (!empty($singleIds)) {
                $singlePlaceholders = implode(',', array_fill(0, count($singleIds), '?'));
                $fetchConditions[] = "(bt.batch_id IS NULL AND bt.id IN ({$singlePlaceholders}))";
                $fetchParams = array_merge($fetchParams, $singleIds);
            }

            $fetchWhereClause = "WHERE (" . implode(" OR ", $fetchConditions) . ")";

            $dataSql = "
                SELECT bt.*,
                       a.name as asset_name,
                       a.ref as asset_ref,
                       c.name as category_name,
                       p.name as project_name,
                       u.full_name as issued_by_name,
                       u.full_name as created_by_name,
                       u_verified.full_name as verified_by_name,
                       u_approved.full_name as approved_by_name,
                       btb.batch_reference,
                       CASE
                           WHEN btb.status IN (?, ?) THEN btb.status
                           WHEN bt.status IN (?, ?) THEN bt.status
                           ELSE COALESCE(btb.status, bt.status)
                       END as status,
                       CASE
                           WHEN btb.status IN (?, ?) THEN btb.status
                           WHEN bt.status = ? AND bt.expected_return < CURDATE() THEN ?
                           WHEN bt.status IN (?, ?) THEN bt.status
                           ELSE COALESCE(btb.status, bt.status)
                       END as current_status
                FROM borrowed_tools bt
                INNER JOIN inventory_items a ON bt.inventory_item_id = a.id
                INNER JOIN categories c ON a.category_id = c.id
                INNER JOIN projects p ON a.project_id = p.id
                LEFT JOIN users u ON bt.issued_by = u.id
                LEFT JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
                LEFT JOIN users u_verified ON COALESCE(btb.verified_by, bt.verified_by) = u_verified.id
                LEFT JOIN users u_approved ON COALESCE(btb.approved_by, bt.approved_by) = u_approved.id
                {$fetchWhereClause}
                {$orderByClause}
            ";

            // Add status constants for CASE statement parameters
            $statusParams = [
                BorrowedToolStatus::PARTIALLY_RETURNED, BorrowedToolStatus::RETURNED,
                BorrowedToolStatus::BORROWED, BorrowedToolStatus::PARTIALLY_RETURNED,
                BorrowedToolStatus::PARTIALLY_RETURNED, BorrowedToolStatus::RETURNED,
                BorrowedToolStatus::BORROWED, BorrowedToolStatus::OVERDUE,
                BorrowedToolStatus::BORROWED, BorrowedToolStatus::PARTIALLY_RETURNED
            ];
            $allParams = array_merge($statusParams, $fetchParams);

            $stmt = $this->db->prepare($dataSql);
            $stmt->execute($allParams);
            $data = $stmt->fetchAll();
        }

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

    /**
     * Get borrowed tool with detailed information
     *
     * @param int $id Borrowed tool ID
     * @param int|null $projectId Optional project ID for filtering
     * @return array|false Borrowed tool record with joined data or false if not found
     */
    public function getBorrowedToolWithDetails($id, $projectId = null) {
        $conditions = ["bt.id = ?"];
        $params = [$id];

        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);

        $sql = "
            SELECT bt.*,
                   a.name as asset_name,
                   a.ref as asset_ref,
                   c.name as category_name,
                   p.name as project_name,
                   u.full_name as issued_by_name
            FROM borrowed_tools bt
            INNER JOIN inventory_items a ON bt.inventory_item_id = a.id
            INNER JOIN categories c ON a.category_id = c.id
            INNER JOIN projects p ON a.project_id = p.id
            LEFT JOIN users u ON bt.issued_by = u.id
            {$whereClause}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Get borrowed tool with MVA workflow details
     *
     * Includes all user information for Maker, Verifier, Approver, Borrower, Returner, Canceler
     *
     * @param int $id Borrowed tool ID
     * @param int|null $projectId Optional project ID for filtering
     * @return array|false Borrowed tool record with full MVA workflow data or false if not found
     */
    public function getBorrowedToolWithMVADetails($id, $projectId = null) {
        $conditions = ["bt.id = ?"];
        $params = [$id];

        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);
        $sql = "
            SELECT bt.*,
                   a.name as asset_name,
                   a.ref as asset_ref,
                   a.acquisition_cost,
                   c.name as category_name,
                   p.name as project_name,
                   btb.batch_reference,
                   u.full_name as issued_by_name,
                   uv.full_name as verified_by_name,
                   ua.full_name as approved_by_name,
                   ub.full_name as borrowed_by_name,
                   ur.full_name as returned_by_name,
                   uc.full_name as canceled_by_name
            FROM borrowed_tools bt
            INNER JOIN inventory_items a ON bt.inventory_item_id = a.id
            INNER JOIN categories c ON a.category_id = c.id
            INNER JOIN projects p ON a.project_id = p.id
            LEFT JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
            LEFT JOIN users u ON bt.issued_by = u.id
            LEFT JOIN users uv ON bt.verified_by = uv.id
            LEFT JOIN users ua ON bt.approved_by = ua.id
            LEFT JOIN users ub ON bt.borrowed_by = ub.id
            LEFT JOIN users ur ON bt.returned_by = ur.id
            LEFT JOIN users uc ON bt.canceled_by = uc.id
            {$whereClause}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}
?>
