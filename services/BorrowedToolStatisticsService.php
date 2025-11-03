<?php
/**
 * ConstructLink™ Borrowed Tool Statistics Service
 * Handles statistical queries and reporting for borrowed tools
 *
 * @package ConstructLink
 * @version 2.0.0
 */

require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';

class BorrowedToolStatisticsService {
    private $db;
    private $borrowedToolModel;

    public function __construct($db = null) {
        if ($db === null) {
            require_once APP_ROOT . '/core/Database.php';
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }

        require_once APP_ROOT . '/models/BorrowedToolModel.php';
        $this->borrowedToolModel = new BorrowedToolModel($this->db);
    }

    /**
     * Get borrowed tool statistics with optional date and project filtering
     *
     * @param string|null $dateFrom Start date (YYYY-MM-DD)
     * @param string|null $dateTo End date (YYYY-MM-DD)
     * @param int|null $projectId Project ID filter
     * @return array Statistics data
     */
    public function getBorrowedToolStats($dateFrom = null, $dateTo = null, $projectId = null) {
        $conditions = [];
        $params = [];

        if ($dateFrom) {
            $conditions[] = "DATE(bt.created_at) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $conditions[] = "DATE(bt.created_at) <= ?";
            $params[] = $dateTo;
        }

        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "SELECT
                    COUNT(*) as total_borrows,
                    SUM(CASE WHEN bt.status = ? THEN 1 ELSE 0 END) as returned_count,
                    SUM(CASE WHEN bt.status = ? THEN 1 ELSE 0 END) as active_count,
                    SUM(CASE WHEN bt.status = ? THEN 1 ELSE 0 END) as overdue_count
                FROM borrowed_tools bt
                LEFT JOIN assets a ON bt.asset_id = a.id
                {$whereClause}";

        // Prepend status constants to params
        $statusParams = [
            BorrowedToolStatus::RETURNED,
            BorrowedToolStatus::BORROWED,
            BorrowedToolStatus::OVERDUE
        ];
        $allParams = array_merge($statusParams, $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($allParams);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get overdue borrowed tools
     *
     * @param int|null $projectId Project ID filter
     * @return array Array of overdue tool records
     */
    public function getOverdueTools($projectId = null) {
        $conditions = [
            "bt.status IN (?, ?)",
            "bt.expected_return < CURDATE()"
        ];
        $params = [
            BorrowedToolStatus::BORROWED,
            BorrowedToolStatus::RELEASED
        ];

        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }

        $whereClause = "WHERE " . implode(" AND ", $conditions);

        $sql = "SELECT bt.*,
                       a.name as asset_name,
                       a.ref as asset_ref,
                       DATEDIFF(CURDATE(), bt.expected_return) as days_overdue
                FROM borrowed_tools bt
                INNER JOIN assets a ON bt.asset_id = a.id
                {$whereClause}
                ORDER BY bt.expected_return ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get borrowing trends over time
     *
     * @param string $groupBy Grouping period ('day', 'week', 'month')
     * @param int $limit Number of periods to return
     * @param int|null $projectId Project ID filter
     * @return array Trend data
     */
    public function getBorrowingTrends($groupBy = 'month', $limit = 12, $projectId = null) {
        $conditions = [];
        $params = [];

        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $dateFormat = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-W%u',
            'month' => '%Y-%m',
            default => '%Y-%m'
        };

        $sql = "SELECT
                    DATE_FORMAT(bt.created_at, '{$dateFormat}') as period,
                    COUNT(*) as borrow_count,
                    SUM(CASE WHEN bt.status = ? THEN 1 ELSE 0 END) as returned_count
                FROM borrowed_tools bt
                LEFT JOIN assets a ON bt.asset_id = a.id
                {$whereClause}
                GROUP BY period
                ORDER BY period DESC
                LIMIT ?";

        // Prepend status constant, append limit
        array_unshift($params, BorrowedToolStatus::RETURNED);
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get frequent borrowers
     *
     * @param int $limit Number of borrowers to return
     * @param int|null $projectId Project ID filter
     * @return array Array of borrower statistics
     */
    public function getFrequentBorrowers($limit = 10, $projectId = null) {
        $conditions = [];
        $params = [];

        if ($projectId) {
            $conditions[] = "a.project_id = ?";
            $params[] = $projectId;
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "SELECT
                    bt.borrower_name,
                    bt.borrower_contact,
                    COUNT(*) as borrow_count,
                    SUM(CASE WHEN bt.status = ? THEN 1 ELSE 0 END) as overdue_count
                FROM borrowed_tools bt
                LEFT JOIN assets a ON bt.asset_id = a.id
                {$whereClause}
                GROUP BY bt.borrower_name, bt.borrower_contact
                ORDER BY borrow_count DESC
                LIMIT ?";

        // Prepend status constant, append limit
        array_unshift($params, BorrowedToolStatus::OVERDUE);
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
