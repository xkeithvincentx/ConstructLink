<?php
/**
 * ConstructLink™ Borrowed Tool Batch Statistics Service
 *
 * Handles statistical queries and reporting for borrowed tool batches.
 * Extracted from BorrowedToolBatchModel to follow Single Responsibility Principle.
 *
 * This service provides:
 * - Batch statistics by status and date range
 * - Overdue batch counts
 * - Time-based statistics (today, this week, this month)
 * - Project-filtered statistics
 *
 * @package ConstructLink
 * @version 2.0.0
 */

require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';
require_once APP_ROOT . '/core/utils/ResponseFormatter.php';

class BorrowedToolBatchStatisticsService {
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
            $conditions[] = "DATE(btb.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $conditions[] = "DATE(btb.created_at) <= ?";
            $params[] = $dateTo;
        }

        if ($projectId) {
            $conditions[] = "EXISTS (
                SELECT 1 FROM borrowed_tools bt
                INNER JOIN assets a ON bt.asset_id = a.id
                WHERE bt.batch_id = btb.id AND a.project_id = ?
            )";
            $params[] = $projectId;
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Use constants instead of hardcoded status strings
        $sql = "
            SELECT
                COUNT(*) as total_batches,
                COUNT(CASE WHEN btb.status = ? THEN 1 END) as pending_verification,
                COUNT(CASE WHEN btb.status = ? THEN 1 END) as pending_approval,
                COUNT(CASE WHEN btb.status = ? THEN 1 END) as approved,
                COUNT(CASE WHEN btb.status = ? THEN 1 END) as released,
                COUNT(CASE WHEN btb.status = ? THEN 1 END) as partially_returned,
                COUNT(CASE WHEN btb.status = ? THEN 1 END) as returned,
                COUNT(CASE WHEN btb.status = ? THEN 1 END) as canceled,
                SUM(btb.total_items) as total_items_borrowed,
                SUM(btb.total_quantity) as total_quantity_borrowed
            FROM borrowed_tool_batches btb
            {$whereClause}
        ";

        // Prepend status constants to params
        $statusParams = [
            BorrowedToolStatus::PENDING_VERIFICATION,
            BorrowedToolStatus::PENDING_APPROVAL,
            BorrowedToolStatus::APPROVED,
            BorrowedToolStatus::RELEASED,
            BorrowedToolStatus::PARTIALLY_RETURNED,
            BorrowedToolStatus::RETURNED,
            BorrowedToolStatus::CANCELED
        ];

        $allParams = array_merge($statusParams, $params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($allParams);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get count of overdue batches
     *
     * @param int|null $projectId Project ID filter
     * @return int Number of overdue batches
     */
    public function getOverdueBatchCount($projectId = null) {
        $conditions = [
            "btb.status = ?",
            "btb.expected_return < CURDATE()"
        ];
        $params = [BorrowedToolStatus::RELEASED];

        if ($projectId) {
            $conditions[] = "EXISTS (
                SELECT 1 FROM borrowed_tools bt
                INNER JOIN assets a ON bt.asset_id = a.id
                WHERE bt.batch_id = btb.id AND a.project_id = ?
            )";
            $params[] = $projectId;
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);

        $sql = "
            SELECT COUNT(*) as overdue_count
            FROM borrowed_tool_batches btb
            {$whereClause}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['overdue_count'] ?? 0;
    }

    /**
     * Get time-based statistics (today, this week, this month)
     *
     * Provides comprehensive time-based metrics for dashboard widgets and reports.
     *
     * @param int|null $projectId Optional project filter
     * @return array Array with keys: borrowed_today, returned_today, due_today,
     *               due_this_week, activity_this_week, borrowed_this_month, returned_this_month
     */
    public function getTimeBasedStatistics($projectId = null) {
        try {
            $stats = [];

            // 1. Borrowed Today (items released/checked out today)
            $sql = "
                SELECT COUNT(DISTINCT bt.id) as count
                FROM borrowed_tools bt
                INNER JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
                " . ($projectId ? "INNER JOIN assets a ON bt.asset_id = a.id" : "") . "
                WHERE DATE(bt.borrowed_date) = CURDATE()
                  AND bt.borrowed_date IS NOT NULL
                  " . ($projectId ? "AND a.project_id = ?" : "") . "
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($projectId ? [$projectId] : []);
            $stats['borrowed_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // 2. Returned Today (items returned today)
            $sql = "
                SELECT COUNT(DISTINCT bt.id) as count
                FROM borrowed_tools bt
                INNER JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
                " . ($projectId ? "INNER JOIN assets a ON bt.asset_id = a.id" : "") . "
                WHERE DATE(bt.return_date) = CURDATE()
                  AND bt.status = ?
                  " . ($projectId ? "AND a.project_id = ?" : "") . "
            ";
            $params = $projectId ? [BorrowedToolStatus::RETURNED, $projectId] : [BorrowedToolStatus::RETURNED];
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stats['returned_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // 3. Due Today (items expected back today that are still out)
            $sql = "
                SELECT COUNT(DISTINCT bt.id) as count
                FROM borrowed_tools bt
                INNER JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
                " . ($projectId ? "INNER JOIN assets a ON bt.asset_id = a.id" : "") . "
                WHERE DATE(bt.expected_return) = CURDATE()
                  AND bt.status IN (?, ?)
                  AND (bt.quantity > bt.quantity_returned OR bt.quantity_returned IS NULL)
                  " . ($projectId ? "AND a.project_id = ?" : "") . "
            ";
            $params = $projectId ?
                [BorrowedToolStatus::APPROVED, BorrowedToolStatus::BORROWED, $projectId] :
                [BorrowedToolStatus::APPROVED, BorrowedToolStatus::BORROWED];
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stats['due_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // 4. Due This Week (items expected back within next 7 days, excluding today)
            $sql = "
                SELECT COUNT(DISTINCT bt.id) as count
                FROM borrowed_tools bt
                INNER JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
                " . ($projectId ? "INNER JOIN assets a ON bt.asset_id = a.id" : "") . "
                WHERE bt.expected_return > CURDATE()
                  AND bt.expected_return <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                  AND bt.status IN (?, ?)
                  AND (bt.quantity > bt.quantity_returned OR bt.quantity_returned IS NULL)
                  " . ($projectId ? "AND a.project_id = ?" : "") . "
            ";
            $params = $projectId ?
                [BorrowedToolStatus::APPROVED, BorrowedToolStatus::BORROWED, $projectId] :
                [BorrowedToolStatus::APPROVED, BorrowedToolStatus::BORROWED];
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stats['due_this_week'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // 5. This Week's Activity (items borrowed + items returned in the past 7 days)
            $sql = "
                SELECT
                    (SELECT COUNT(DISTINCT bt.id)
                     FROM borrowed_tools bt
                     INNER JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
                     " . ($projectId ? "INNER JOIN assets a ON bt.asset_id = a.id" : "") . "
                     WHERE bt.borrowed_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                       AND bt.borrowed_date IS NOT NULL
                       " . ($projectId ? "AND a.project_id = ?" : "") . "
                    ) +
                    (SELECT COUNT(DISTINCT bt.id)
                     FROM borrowed_tools bt
                     INNER JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
                     " . ($projectId ? "INNER JOIN assets a ON bt.asset_id = a.id" : "") . "
                     WHERE bt.return_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                       AND bt.status = ?
                       " . ($projectId ? "AND a.project_id = ?" : "") . "
                    ) as count
            ";
            $params = $projectId ? [BorrowedToolStatus::RETURNED, $projectId, $projectId] :
                                  [BorrowedToolStatus::RETURNED];
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stats['activity_this_week'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // 6. Borrowed This Month (items released this month)
            $sql = "
                SELECT COUNT(DISTINCT bt.id) as count
                FROM borrowed_tools bt
                INNER JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
                " . ($projectId ? "INNER JOIN assets a ON bt.asset_id = a.id" : "") . "
                WHERE YEAR(bt.borrowed_date) = YEAR(CURDATE())
                  AND MONTH(bt.borrowed_date) = MONTH(CURDATE())
                  AND bt.borrowed_date IS NOT NULL
                  " . ($projectId ? "AND a.project_id = ?" : "") . "
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($projectId ? [$projectId] : []);
            $stats['borrowed_this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            // 7. Returned This Month (items returned this month)
            $sql = "
                SELECT COUNT(DISTINCT bt.id) as count
                FROM borrowed_tools bt
                INNER JOIN borrowed_tool_batches btb ON bt.batch_id = btb.id
                " . ($projectId ? "INNER JOIN assets a ON bt.asset_id = a.id" : "") . "
                WHERE YEAR(bt.return_date) = YEAR(CURDATE())
                  AND MONTH(bt.return_date) = MONTH(CURDATE())
                  AND bt.status = ?
                  " . ($projectId ? "AND a.project_id = ?" : "") . "
            ";
            $params = $projectId ? [BorrowedToolStatus::RETURNED, $projectId] : [BorrowedToolStatus::RETURNED];
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stats['returned_this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

            return $stats;

        } catch (Exception $e) {
            error_log("Get time-based statistics error: " . $e->getMessage());
            // Return zero values on error
            return [
                'borrowed_today' => 0,
                'returned_today' => 0,
                'due_today' => 0,
                'due_this_week' => 0,
                'activity_this_week' => 0,
                'borrowed_this_month' => 0,
                'returned_this_month' => 0,
            ];
        }
    }
}
