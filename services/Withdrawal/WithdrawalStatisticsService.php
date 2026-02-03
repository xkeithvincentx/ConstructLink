<?php
/**
 * ConstructLink™ Withdrawal Statistics Service
 *
 * Handles statistical calculations and reporting for withdrawals
 * Separates analytics logic from models and controllers
 */

class WithdrawalStatisticsService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get withdrawal statistics with optional filters
     *
     * @param int|null $projectId Optional project filter
     * @param string|null $dateFrom Optional start date filter
     * @param string|null $dateTo Optional end date filter
     * @return array Statistics data
     */
    public function getWithdrawalStatistics($projectId = null, $dateFrom = null, $dateTo = null) {
        $conditions = [];
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

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "
            SELECT
                COUNT(*) as total_withdrawals,
                SUM(CASE WHEN status = 'Pending Verification' THEN 1 ELSE 0 END) as pending_verification,
                SUM(CASE WHEN status = 'Pending Approval' THEN 1 ELSE 0 END) as pending_approval,
                SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'Released' THEN 1 ELSE 0 END) as released,
                SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned,
                SUM(CASE WHEN status = 'Canceled' THEN 1 ELSE 0 END) as canceled,
                SUM(CASE WHEN status = 'Released' AND expected_return IS NOT NULL AND expected_return < CURDATE() THEN 1 ELSE 0 END) as overdue
            FROM withdrawals
            {$whereClause}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: [
            'total_withdrawals' => 0,
            'pending_verification' => 0,
            'pending_approval' => 0,
            'approved' => 0,
            'released' => 0,
            'returned' => 0,
            'canceled' => 0,
            'overdue' => 0
        ];
    }

    /**
     * Get withdrawal statistics for dashboard (last 30 days)
     *
     * @return array Statistics for dashboard display
     */
    public function getDashboardStats() {
        $sql = "
            SELECT
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'Pending Verification' THEN 1 END) as pending_verification,
                COUNT(CASE WHEN status = 'Pending Approval' THEN 1 END) as pending_approval,
                COUNT(CASE WHEN status = 'Approved' THEN 1 END) as approved,
                COUNT(CASE WHEN status = 'Released' THEN 1 END) as released,
                COUNT(CASE WHEN status = 'Returned' THEN 1 END) as returned,
                COUNT(CASE WHEN status = 'Canceled' THEN 1 END) as canceled,
                COUNT(CASE WHEN status = 'Released' AND expected_return IS NOT NULL AND expected_return < CURDATE() THEN 1 END) as overdue
            FROM withdrawals
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (int)($result['total'] ?? 0),
            'pending_verification' => (int)($result['pending_verification'] ?? 0),
            'pending_approval' => (int)($result['pending_approval'] ?? 0),
            'approved' => (int)($result['approved'] ?? 0),
            'released' => (int)($result['released'] ?? 0),
            'returned' => (int)($result['returned'] ?? 0),
            'canceled' => (int)($result['canceled'] ?? 0),
            'overdue' => (int)($result['overdue'] ?? 0)
        ];
    }

    /**
     * Get withdrawal trends by month
     *
     * @param int $months Number of months to include
     * @param int|null $projectId Optional project filter
     * @return array Monthly withdrawal trends
     */
    public function getWithdrawalTrends($months = 6, $projectId = null) {
        $conditions = [];
        $params = [$months];

        if ($projectId) {
            $conditions[] = "project_id = ?";
            $params[] = $projectId;
        }

        $whereClause = !empty($conditions) ? "AND " . implode(" AND ", $conditions) : "";

        $sql = "
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Released' THEN 1 ELSE 0 END) as released,
                SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned,
                SUM(CASE WHEN status = 'Canceled' THEN 1 ELSE 0 END) as canceled
            FROM withdrawals
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            {$whereClause}
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get most withdrawn items
     *
     * @param int $limit Number of items to return
     * @param int|null $projectId Optional project filter
     * @return array List of most withdrawn items
     */
    public function getMostWithdrawnItems($limit = 10, $projectId = null) {
        $conditions = [];
        $params = [];

        if ($projectId) {
            $conditions[] = "w.project_id = ?";
            $params[] = $projectId;
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "
            SELECT
                i.id,
                i.ref as item_ref,
                i.name as item_name,
                c.name as category_name,
                COUNT(w.id) as withdrawal_count,
                SUM(w.quantity) as total_quantity_withdrawn
            FROM withdrawals w
            LEFT JOIN inventory_items i ON w.inventory_item_id = i.id
            LEFT JOIN categories c ON i.category_id = c.id
            {$whereClause}
            GROUP BY i.id
            ORDER BY withdrawal_count DESC
            LIMIT {$limit}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get withdrawal statistics by project
     *
     * @return array Withdrawal statistics grouped by project
     */
    public function getWithdrawalsByProject() {
        $sql = "
            SELECT
                p.id,
                p.name as project_name,
                COUNT(w.id) as total_withdrawals,
                SUM(CASE WHEN w.status = 'Released' THEN 1 ELSE 0 END) as released,
                SUM(CASE WHEN w.status = 'Pending Verification' THEN 1 ELSE 0 END) as pending_verification,
                SUM(CASE WHEN w.status = 'Pending Approval' THEN 1 ELSE 0 END) as pending_approval
            FROM projects p
            LEFT JOIN withdrawals w ON p.id = w.project_id
            WHERE p.is_active = 1
            GROUP BY p.id
            ORDER BY total_withdrawals DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get withdrawal completion rate
     *
     * @param int|null $projectId Optional project filter
     * @param string|null $dateFrom Optional start date
     * @param string|null $dateTo Optional end date
     * @return array Completion rate statistics
     */
    public function getCompletionRate($projectId = null, $dateFrom = null, $dateTo = null) {
        $conditions = [];
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

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('Released', 'Returned') THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'Canceled' THEN 1 ELSE 0 END) as canceled,
                SUM(CASE WHEN status IN ('Pending Verification', 'Pending Approval', 'Approved') THEN 1 ELSE 0 END) as pending
            FROM withdrawals
            {$whereClause}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $total = (int)$result['total'];
        $completed = (int)$result['completed'];
        $canceled = (int)$result['canceled'];
        $pending = (int)$result['pending'];

        return [
            'total' => $total,
            'completed' => $completed,
            'canceled' => $canceled,
            'pending' => $pending,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'cancellation_rate' => $total > 0 ? round(($canceled / $total) * 100, 2) : 0
        ];
    }

    /**
     * Get average processing time by status
     *
     * @param int|null $projectId Optional project filter
     * @return array Average processing times
     */
    public function getAverageProcessingTime($projectId = null) {
        $conditions = [];
        $params = [];

        if ($projectId) {
            $conditions[] = "project_id = ?";
            $params[] = $projectId;
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $sql = "
            SELECT
                AVG(TIMESTAMPDIFF(HOUR, created_at, verification_date)) as avg_verification_hours,
                AVG(TIMESTAMPDIFF(HOUR, verification_date, approval_date)) as avg_approval_hours,
                AVG(TIMESTAMPDIFF(HOUR, approval_date, release_date)) as avg_release_hours,
                AVG(TIMESTAMPDIFF(DAY, created_at, release_date)) as avg_total_days
            FROM withdrawals
            {$whereClause}
            AND verification_date IS NOT NULL
            AND approval_date IS NOT NULL
            AND release_date IS NOT NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'avg_verification_hours' => round((float)($result['avg_verification_hours'] ?? 0), 2),
            'avg_approval_hours' => round((float)($result['avg_approval_hours'] ?? 0), 2),
            'avg_release_hours' => round((float)($result['avg_release_hours'] ?? 0), 2),
            'avg_total_days' => round((float)($result['avg_total_days'] ?? 0), 2)
        ];
    }
}
?>
