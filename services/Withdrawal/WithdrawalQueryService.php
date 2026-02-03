<?php
/**
 * ConstructLink™ Withdrawal Query Service
 *
 * Handles complex database queries for withdrawal data
 * Separates query logic from model and business logic
 */

class WithdrawalQueryService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get withdrawal with full details including all joins
     *
     * @param int $id Withdrawal ID
     * @return array|false Withdrawal data with all related information
     */
    public function getWithdrawalDetails($id) {
        $sql = "
            SELECT w.*,
                   i.ref as item_ref,
                   i.name as item_name,
                   i.status as item_status,
                   i.quantity as item_total_quantity,
                   i.available_quantity as item_available_quantity,
                   c.name as category_name,
                   c.is_consumable,
                   p.name as project_name,
                   p.location as project_location,
                   u.full_name as withdrawn_by_name,
                   v.full_name as verified_by_name,
                   a.full_name as approved_by_name,
                   r.released_by,
                   r.notes as release_notes,
                   r.released_at,
                   rl.full_name as released_by_name,
                   ret.full_name as returned_by_name
            FROM withdrawals w
            LEFT JOIN inventory_items i ON w.inventory_item_id = i.id
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN projects p ON w.project_id = p.id
            LEFT JOIN users u ON w.withdrawn_by = u.id
            LEFT JOIN users v ON w.verified_by = v.id
            LEFT JOIN users a ON w.approved_by = a.id
            LEFT JOIN releases r ON w.id = r.withdrawal_id
            LEFT JOIN users rl ON r.released_by = rl.id
            LEFT JOIN users ret ON w.returned_by = ret.id
            WHERE w.id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get withdrawals with filters and pagination
     *
     * @param array $filters Filter criteria
     * @param int $page Page number
     * @param int $perPage Results per page
     * @return array Paginated withdrawal data
     */
    public function getWithdrawalsWithFilters($filters = [], $page = 1, $perPage = 20) {
        $conditions = [];
        $params = [];

        // Build WHERE conditions
        if (!empty($filters['status'])) {
            $conditions[] = "w.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['project_id'])) {
            $conditions[] = "w.project_id = ?";
            $params[] = $filters['project_id'];
        }

        if (!empty($filters['inventory_item_id'])) {
            $conditions[] = "w.inventory_item_id = ?";
            $params[] = $filters['inventory_item_id'];
        }

        if (!empty($filters['withdrawn_by'])) {
            $conditions[] = "w.withdrawn_by = ?";
            $params[] = $filters['withdrawn_by'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(w.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(w.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(i.ref LIKE ? OR i.name LIKE ? OR w.receiver_name LIKE ? OR w.purpose LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Count total records
        $countSql = "
            SELECT COUNT(*)
            FROM withdrawals w
            LEFT JOIN inventory_items i ON w.inventory_item_id = i.id
            {$whereClause}
        ";

        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Get paginated data
        $offset = ($page - 1) * $perPage;
        $orderBy = $filters['order_by'] ?? 'w.created_at DESC';

        $dataSql = "
            SELECT w.*,
                   i.ref as item_ref,
                   i.name as item_name,
                   c.name as category_name,
                   c.is_consumable,
                   p.name as project_name,
                   u.full_name as withdrawn_by_name,
                   w.receiver_name,
                   wb.batch_reference
            FROM withdrawals w
            LEFT JOIN inventory_items i ON w.inventory_item_id = i.id
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN projects p ON w.project_id = p.id
            LEFT JOIN users u ON w.withdrawn_by = u.id
            LEFT JOIN withdrawal_batches wb ON w.batch_id = wb.id
            {$whereClause}
            ORDER BY {$orderBy}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $this->db->prepare($dataSql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
     * Get inventory item with category information
     *
     * @param int $inventoryItemId Inventory item ID
     * @return array|false Item data with category
     */
    public function getInventoryItemWithCategory($inventoryItemId) {
        $sql = "
            SELECT i.*, c.is_consumable, c.name as category_name
            FROM inventory_items i
            LEFT JOIN categories c ON i.category_id = c.id
            WHERE i.id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$inventoryItemId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get active withdrawal for a specific item
     *
     * @param int $inventoryItemId Inventory item ID
     * @return array|false Active withdrawal data if exists
     */
    public function getActiveWithdrawalForItem($inventoryItemId) {
        $sql = "
            SELECT * FROM withdrawals
            WHERE inventory_item_id = ?
            AND status IN ('Pending Verification', 'Pending Approval', 'Approved', 'Released')
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$inventoryItemId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get available consumable items for withdrawal
     *
     * @param int|null $projectId Optional project ID filter
     * @return array List of available consumable items
     */
    public function getAvailableConsumablesForWithdrawal($projectId = null) {
        $conditions = [
            "p.is_active = 1",
            "c.is_consumable = 1",
            "i.available_quantity > 0"
        ];
        $params = [];

        if ($projectId) {
            $conditions[] = "i.project_id = ?";
            $params[] = $projectId;
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);

        $sql = "
            SELECT i.*,
                   c.name as category_name,
                   c.is_consumable,
                   p.name as project_name
            FROM inventory_items i
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN projects p ON i.project_id = p.id
            {$whereClause}
            ORDER BY p.name ASC, i.name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get overdue withdrawals (items not returned by expected date)
     *
     * @param int|null $projectId Optional project ID filter
     * @return array List of overdue withdrawals
     */
    public function getOverdueWithdrawals($projectId = null) {
        $conditions = [
            "w.status = 'Released'",
            "w.expected_return IS NOT NULL",
            "w.expected_return < CURDATE()"
        ];
        $params = [];

        if ($projectId) {
            $conditions[] = "w.project_id = ?";
            $params[] = $projectId;
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);

        $sql = "
            SELECT w.*,
                   i.ref as item_ref,
                   i.name as item_name,
                   p.name as project_name,
                   u.full_name as withdrawn_by_name,
                   DATEDIFF(CURDATE(), w.expected_return) as days_overdue
            FROM withdrawals w
            LEFT JOIN inventory_items i ON w.inventory_item_id = i.id
            LEFT JOIN projects p ON w.project_id = p.id
            LEFT JOIN users u ON w.withdrawn_by = u.id
            {$whereClause}
            ORDER BY w.expected_return ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get withdrawal report data for a date range
     *
     * @param string $dateFrom Start date
     * @param string $dateTo End date
     * @param int|null $projectId Optional project filter
     * @param string|null $status Optional status filter
     * @return array Report data
     */
    public function getWithdrawalReport($dateFrom, $dateTo, $projectId = null, $status = null) {
        $conditions = ["DATE(w.created_at) BETWEEN ? AND ?"];
        $params = [$dateFrom, $dateTo];

        if ($projectId) {
            $conditions[] = "w.project_id = ?";
            $params[] = $projectId;
        }

        if ($status) {
            $conditions[] = "w.status = ?";
            $params[] = $status;
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);

        $sql = "
            SELECT w.*,
                   i.ref as item_ref,
                   i.name as item_name,
                   c.name as category_name,
                   p.name as project_name,
                   u.full_name as withdrawn_by_name,
                   r.released_by,
                   r.released_at,
                   rl.full_name as released_by_name
            FROM withdrawals w
            LEFT JOIN inventory_items i ON w.inventory_item_id = i.id
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN projects p ON w.project_id = p.id
            LEFT JOIN users u ON w.withdrawn_by = u.id
            LEFT JOIN releases r ON w.id = r.withdrawal_id
            LEFT JOIN users rl ON r.released_by = rl.id
            {$whereClause}
            ORDER BY w.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get item withdrawal history
     *
     * @param int $inventoryItemId Inventory item ID
     * @return array Withdrawal history for the item
     */
    public function getItemWithdrawalHistory($inventoryItemId) {
        $sql = "
            SELECT w.*,
                   w.created_at as withdrawal_date,
                   u.full_name as withdrawn_by_name,
                   p.name as project_name,
                   r.released_by,
                   r.released_at,
                   r.notes as release_notes,
                   rl.full_name as released_by_name
            FROM withdrawals w
            LEFT JOIN users u ON w.withdrawn_by = u.id
            LEFT JOIN projects p ON w.project_id = p.id
            LEFT JOIN releases r ON w.id = r.withdrawal_id
            LEFT JOIN users rl ON r.released_by = rl.id
            WHERE w.inventory_item_id = ?
            ORDER BY w.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$inventoryItemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
