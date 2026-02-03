<?php
/**
 * RequestActivityModel - Activity Logging and Audit Trail
 *
 * Handles request activity logging and audit trail operations.
 * Single Responsibility: Activity logging and retrieval.
 *
 * @package ConstructLink\Models\Request
 */

class RequestActivityModel extends BaseModel {
    protected $table = 'request_logs';

    /**
     * Log request activity
     *
     * Records an action performed on a request for audit trail purposes.
     *
     * @param int $requestId Request ID
     * @param string $action Action performed
     * @param string|null $oldStatus Previous status
     * @param string|null $newStatus New status
     * @param string|null $remarks Optional remarks
     * @param int|null $userId User who performed the action
     * @return bool Success status
     */
    public function logRequestActivity($requestId, $action, $oldStatus, $newStatus, $remarks = null, $userId = null) {
        try {
            if (!$userId) {
                $auth = Auth::getInstance();
                $user = $auth->getCurrentUser();
                $userId = $user['id'] ?? null;
            }

            $sql = "INSERT INTO request_logs (request_id, user_id, action, old_status, new_status, remarks)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$requestId, $userId, $action, $oldStatus, $newStatus, $remarks]);

            return true;

        } catch (Exception $e) {
            error_log("Request activity logging error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get request activity logs
     *
     * @param int $requestId Request ID
     * @return array Array of activity logs
     */
    public function getRequestLogs($requestId) {
        try {
            $sql = "
                SELECT rl.*, u.full_name as user_name
                FROM request_logs rl
                LEFT JOIN users u ON rl.user_id = u.id
                WHERE rl.request_id = ?
                ORDER BY rl.created_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$requestId]);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get request logs error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent activities across all requests
     *
     * @param int $limit Maximum number of records
     * @param int|null $userId Filter by user ID
     * @param int|null $projectId Filter by project ID
     * @return array Array of recent activities
     */
    public function getRecentActivities($limit = 50, $userId = null, $projectId = null) {
        try {
            $conditions = [];
            $params = [];

            if ($userId) {
                $conditions[] = "rl.user_id = ?";
                $params[] = $userId;
            }

            if ($projectId) {
                $conditions[] = "r.project_id = ?";
                $params[] = $projectId;
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $sql = "
                SELECT rl.*,
                       u.full_name as user_name,
                       r.description as request_description,
                       r.request_type,
                       p.name as project_name,
                       p.code as project_code
                FROM request_logs rl
                LEFT JOIN users u ON rl.user_id = u.id
                LEFT JOIN requests r ON rl.request_id = r.id
                LEFT JOIN projects p ON r.project_id = p.id
                {$whereClause}
                ORDER BY rl.created_at DESC
                LIMIT ?
            ";

            $params[] = $limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get recent activities error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get activity statistics
     *
     * @param string|null $dateFrom Start date filter
     * @param string|null $dateTo End date filter
     * @return array Activity statistics
     */
    public function getActivityStatistics($dateFrom = null, $dateTo = null) {
        try {
            $conditions = [];
            $params = [];

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
                    COUNT(*) as total_activities,
                    COUNT(DISTINCT request_id) as unique_requests,
                    COUNT(DISTINCT user_id) as unique_users,
                    SUM(CASE WHEN action = 'request_created' THEN 1 ELSE 0 END) as requests_created,
                    SUM(CASE WHEN action = 'request_submitted' THEN 1 ELSE 0 END) as requests_submitted,
                    SUM(CASE WHEN action = 'status_changed' THEN 1 ELSE 0 END) as status_changes,
                    SUM(CASE WHEN action = 'linked_to_procurement_order' THEN 1 ELSE 0 END) as procurement_links
                FROM request_logs
                {$whereClause}
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            return $result ?: [
                'total_activities' => 0,
                'unique_requests' => 0,
                'unique_users' => 0,
                'requests_created' => 0,
                'requests_submitted' => 0,
                'status_changes' => 0,
                'procurement_links' => 0
            ];

        } catch (Exception $e) {
            error_log("Get activity statistics error: " . $e->getMessage());
            return [
                'total_activities' => 0,
                'unique_requests' => 0,
                'unique_users' => 0,
                'requests_created' => 0,
                'requests_submitted' => 0,
                'status_changes' => 0,
                'procurement_links' => 0
            ];
        }
    }

    /**
     * Get activities by action type
     *
     * @param string|null $dateFrom Start date filter
     * @param string|null $dateTo End date filter
     * @return array Activities grouped by action type
     */
    public function getActivitiesByAction($dateFrom = null, $dateTo = null) {
        try {
            $conditions = [];
            $params = [];

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
                    action,
                    COUNT(*) as count,
                    COUNT(DISTINCT request_id) as unique_requests,
                    COUNT(DISTINCT user_id) as unique_users
                FROM request_logs
                {$whereClause}
                GROUP BY action
                ORDER BY count DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get activities by action error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user activity summary
     *
     * @param int $userId User ID
     * @param string|null $dateFrom Start date filter
     * @param string|null $dateTo End date filter
     * @return array User activity summary
     */
    public function getUserActivitySummary($userId, $dateFrom = null, $dateTo = null) {
        try {
            $conditions = ["user_id = ?"];
            $params = [$userId];

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
                    COUNT(*) as total_actions,
                    COUNT(DISTINCT request_id) as requests_affected,
                    SUM(CASE WHEN action = 'request_created' THEN 1 ELSE 0 END) as created,
                    SUM(CASE WHEN action = 'request_submitted' THEN 1 ELSE 0 END) as submitted,
                    SUM(CASE WHEN action = 'status_changed' THEN 1 ELSE 0 END) as status_changed,
                    MIN(created_at) as first_activity,
                    MAX(created_at) as last_activity
                FROM request_logs
                {$whereClause}
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            return $result ?: [
                'total_actions' => 0,
                'requests_affected' => 0,
                'created' => 0,
                'submitted' => 0,
                'status_changed' => 0,
                'first_activity' => null,
                'last_activity' => null
            ];

        } catch (Exception $e) {
            error_log("Get user activity summary error: " . $e->getMessage());
            return [
                'total_actions' => 0,
                'requests_affected' => 0,
                'created' => 0,
                'submitted' => 0,
                'status_changed' => 0,
                'first_activity' => null,
                'last_activity' => null
            ];
        }
    }
}
?>
