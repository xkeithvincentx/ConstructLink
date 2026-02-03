<?php
/**
 * ConstructLink Withdrawal Batch Statistics Service
 *
 * Handles statistical queries and reporting for withdrawal batches.
 * Extracted from WithdrawalBatchModel to follow Single Responsibility Principle.
 *
 * This service provides:
 * - Batch statistics by status and date range
 * - Released batch counts
 * - Time-based statistics (today, this week, this month)
 * - Project-filtered statistics
 *
 * @package ConstructLink
 * @version 1.0.0
 */

require_once APP_ROOT . '/helpers/WithdrawalBatchStatus.php';
require_once APP_ROOT . '/core/utils/ResponseFormatter.php';

class WithdrawalBatchStatisticsService {
    private $db;

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     */
    public function __construct($db = null) {
        if ($db === null) {
            require_once APP_ROOT . '/core/Database.php';
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }
    }

    /**
     * Get comprehensive batch statistics
     *
     * @param string|null $dateFrom Start date (YYYY-MM-DD)
     * @param string|null $dateTo End date (YYYY-MM-DD)
     * @param int|null $projectId Project ID filter
     * @return array Statistics data with batch counts by status
     */
    public function getBatchStats($dateFrom = null, $dateTo = null, $projectId = null) {
        $conditions = [];
        $params = [];

        if ($dateFrom) {
            $conditions[] = "DATE(wb.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $conditions[] = "DATE(wb.created_at) <= ?";
            $params[] = $dateTo;
        }

        if ($projectId) {
            $conditions[] = "EXISTS (
                SELECT 1 FROM withdrawals w
                INNER JOIN inventory_items a ON w.inventory_item_id = a.id
                WHERE w.batch_id = wb.id AND a.project_id = ?
            )";
            $params[] = $projectId;
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Use constants instead of hardcoded status strings
        $sql = "
            SELECT
                COUNT(*) as total_batches,
                COUNT(CASE WHEN wb.status = ? THEN 1 END) as pending_verification,
                COUNT(CASE WHEN wb.status = ? THEN 1 END) as pending_approval,
                COUNT(CASE WHEN wb.status = ? THEN 1 END) as approved,
                COUNT(CASE WHEN wb.status = ? THEN 1 END) as released,
                COUNT(CASE WHEN wb.status = ? THEN 1 END) as canceled,
                SUM(wb.total_items) as total_items_withdrawn,
                SUM(wb.total_quantity) as total_quantity_withdrawn
            FROM withdrawal_batches wb
            {$whereClause}
        ";

        // Prepend status constants to params
        $statusParams = [
            WithdrawalBatchStatus::PENDING_VERIFICATION,
            WithdrawalBatchStatus::PENDING_APPROVAL,
            WithdrawalBatchStatus::APPROVED,
            WithdrawalBatchStatus::RELEASED,
            WithdrawalBatchStatus::CANCELED
        ];

        $allParams = array_merge($statusParams, $params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($allParams);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get time-based statistics (today, this week, this month)
     *
     * Provides comprehensive time-based metrics for dashboard widgets and reports.
     *
     * @param int|null $projectId Optional project filter
     * @return array Array with keys: released_today, released_this_week, released_this_month
     */
    public function getTimeBasedStatistics($projectId = null) {
        $projectCondition = "";
        $params = [];

        if ($projectId) {
            $projectCondition = "AND EXISTS (
                SELECT 1 FROM withdrawals w
                INNER JOIN inventory_items a ON w.inventory_item_id = a.id
                WHERE w.batch_id = wb.id AND a.project_id = ?
            )";
            $params = array_fill(0, 6, $projectId);  // 6 queries need the project filter
        }

        // Today's releases
        $releasedTodaySql = "
            SELECT COUNT(*) as count
            FROM withdrawal_batches wb
            WHERE DATE(wb.release_date) = CURDATE()
              AND wb.status = ?
              {$projectCondition}
        ";
        $stmt = $this->db->prepare($releasedTodaySql);
        $stmt->execute(array_merge([WithdrawalBatchStatus::RELEASED], $projectId ? [$projectId] : []));
        $releasedToday = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // This week's releases
        $releasedThisWeekSql = "
            SELECT COUNT(*) as count
            FROM withdrawal_batches wb
            WHERE YEARWEEK(wb.release_date, 1) = YEARWEEK(CURDATE(), 1)
              AND wb.status = ?
              {$projectCondition}
        ";
        $stmt = $this->db->prepare($releasedThisWeekSql);
        $stmt->execute(array_merge([WithdrawalBatchStatus::RELEASED], $projectId ? [$projectId] : []));
        $releasedThisWeek = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // This month's releases
        $releasedThisMonthSql = "
            SELECT COUNT(*) as count
            FROM withdrawal_batches wb
            WHERE YEAR(wb.release_date) = YEAR(CURDATE())
              AND MONTH(wb.release_date) = MONTH(CURDATE())
              AND wb.status = ?
              {$projectCondition}
        ";
        $stmt = $this->db->prepare($releasedThisMonthSql);
        $stmt->execute(array_merge([WithdrawalBatchStatus::RELEASED], $projectId ? [$projectId] : []));
        $releasedThisMonth = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // Pending batches count
        $pendingBatchesSql = "
            SELECT COUNT(*) as count
            FROM withdrawal_batches wb
            WHERE wb.status IN (?, ?, ?)
              {$projectCondition}
        ";
        $stmt = $this->db->prepare($pendingBatchesSql);
        $stmt->execute(array_merge([
            WithdrawalBatchStatus::PENDING_VERIFICATION,
            WithdrawalBatchStatus::PENDING_APPROVAL,
            WithdrawalBatchStatus::APPROVED
        ], $projectId ? [$projectId] : []));
        $pendingBatches = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // Total quantity withdrawn this month
        $quantityThisMonthSql = "
            SELECT COALESCE(SUM(wb.total_quantity), 0) as total
            FROM withdrawal_batches wb
            WHERE YEAR(wb.release_date) = YEAR(CURDATE())
              AND MONTH(wb.release_date) = MONTH(CURDATE())
              AND wb.status = ?
              {$projectCondition}
        ";
        $stmt = $this->db->prepare($quantityThisMonthSql);
        $stmt->execute(array_merge([WithdrawalBatchStatus::RELEASED], $projectId ? [$projectId] : []));
        $quantityThisMonth = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        return [
            'released_today' => $releasedToday,
            'released_this_week' => $releasedThisWeek,
            'released_this_month' => $releasedThisMonth,
            'pending_batches' => $pendingBatches,
            'quantity_this_month' => $quantityThisMonth
        ];
    }

    /**
     * Get top withdrawn consumable items by quantity
     *
     * @param int $limit Number of top items to return
     * @param int|null $projectId Optional project filter
     * @param string|null $dateFrom Optional start date filter
     * @param string|null $dateTo Optional end date filter
     * @return array Top withdrawn items with quantities
     */
    public function getTopWithdrawnItems($limit = 10, $projectId = null, $dateFrom = null, $dateTo = null) {
        $conditions = ["wb.status = ?"];
        $params = [WithdrawalBatchStatus::RELEASED];

        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }

        if ($dateFrom) {
            $conditions[] = "DATE(wb.release_date) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $conditions[] = "DATE(wb.release_date) <= ?";
            $params[] = $dateTo;
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);

        $sql = "
            SELECT
                a.id,
                a.name,
                a.ref,
                c.name as category_name,
                SUM(w.quantity) as total_withdrawn,
                COUNT(DISTINCT wb.id) as batch_count
            FROM withdrawals w
            INNER JOIN withdrawal_batches wb ON w.batch_id = wb.id
            INNER JOIN inventory_items a ON w.inventory_item_id = a.id
            INNER JOIN categories c ON a.category_id = c.id
            {$whereClause}
            GROUP BY a.id, a.name, a.ref, c.name
            ORDER BY total_withdrawn DESC
            LIMIT ?
        ";

        $params[] = $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get monthly withdrawal trend
     *
     * @param int $months Number of months to look back
     * @param int|null $projectId Optional project filter
     * @return array Monthly withdrawal statistics
     */
    public function getMonthlyWithdrawalTrend($months = 6, $projectId = null) {
        $projectCondition = "";
        $params = [WithdrawalBatchStatus::RELEASED];

        if ($projectId) {
            $projectCondition = "AND EXISTS (
                SELECT 1 FROM withdrawals w
                INNER JOIN inventory_items a ON w.inventory_item_id = a.id
                WHERE w.batch_id = wb.id AND a.project_id = ?
            )";
            $params[] = $projectId;
        }

        $params[] = $months;

        $sql = "
            SELECT
                DATE_FORMAT(wb.release_date, '%Y-%m') as month,
                COUNT(*) as batch_count,
                SUM(wb.total_quantity) as total_quantity
            FROM withdrawal_batches wb
            WHERE wb.status = ?
              {$projectCondition}
              AND wb.release_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(wb.release_date, '%Y-%m')
            ORDER BY month ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
