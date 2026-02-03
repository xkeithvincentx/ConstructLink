<?php
/**
 * RequestStatisticsModel - Statistics and Reporting
 *
 * Handles request statistics, analytics, and reporting queries.
 * Single Responsibility: Aggregation and reporting of request data.
 *
 * @package ConstructLink\Models\Request
 */

class RequestStatisticsModel extends BaseModel {
    protected $table = 'requests';

    /**
     * Get request statistics
     *
     * @param int|null $projectId Optional project filter
     * @param string|null $dateFrom Start date filter
     * @param string|null $dateTo End date filter
     * @return array Statistics array
     */
    public function getRequestStatistics($projectId = null, $dateFrom = null, $dateTo = null) {
        try {
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
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = 'Submitted' THEN 1 ELSE 0 END) as submitted,
                    SUM(CASE WHEN status = 'Reviewed' THEN 1 ELSE 0 END) as reviewed,
                    SUM(CASE WHEN status = 'Verified' THEN 1 ELSE 0 END) as verified,
                    SUM(CASE WHEN status = 'Authorized' THEN 1 ELSE 0 END) as authorized,
                    SUM(CASE WHEN status = 'Forwarded' THEN 1 ELSE 0 END) as forwarded,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Declined' THEN 1 ELSE 0 END) as declined,
                    SUM(CASE WHEN status = 'Procured' THEN 1 ELSE 0 END) as procured,
                    SUM(CASE WHEN urgency = 'Critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN urgency = 'Urgent' THEN 1 ELSE 0 END) as urgent
                FROM requests
                {$whereClause}
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            return $result ?: [
                'total_requests' => 0,
                'draft' => 0,
                'submitted' => 0,
                'reviewed' => 0,
                'verified' => 0,
                'authorized' => 0,
                'forwarded' => 0,
                'approved' => 0,
                'declined' => 0,
                'procured' => 0,
                'critical' => 0,
                'urgent' => 0
            ];

        } catch (Exception $e) {
            error_log("Get request statistics error: " . $e->getMessage());
            return [
                'total_requests' => 0,
                'draft' => 0,
                'submitted' => 0,
                'reviewed' => 0,
                'verified' => 0,
                'authorized' => 0,
                'forwarded' => 0,
                'approved' => 0,
                'declined' => 0,
                'procured' => 0,
                'critical' => 0,
                'urgent' => 0
            ];
        }
    }

    /**
     * Get requests by type for reporting
     *
     * @param string|null $dateFrom Start date filter
     * @param string|null $dateTo End date filter
     * @return array Array of request type statistics
     */
    public function getRequestsByType($dateFrom = null, $dateTo = null) {
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
                    request_type,
                    COUNT(*) as total_count,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = 'Declined' THEN 1 ELSE 0 END) as declined_count,
                    SUM(CASE WHEN status = 'Procured' THEN 1 ELSE 0 END) as procured_count,
                    AVG(CASE WHEN estimated_cost IS NOT NULL THEN estimated_cost ELSE 0 END) as avg_estimated_cost
                FROM requests
                {$whereClause}
                GROUP BY request_type
                ORDER BY total_count DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get requests by type error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get request statistics by urgency
     *
     * @param int|null $projectId Optional project filter
     * @return array Urgency statistics
     */
    public function getRequestsByUrgency($projectId = null) {
        try {
            $conditions = [];
            $params = [];

            if ($projectId) {
                $conditions[] = "project_id = ?";
                $params[] = $projectId;
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $sql = "
                SELECT
                    urgency,
                    COUNT(*) as count,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Declined' THEN 1 ELSE 0 END) as declined,
                    SUM(CASE WHEN status IN ('Draft', 'Submitted', 'Reviewed', 'Forwarded') THEN 1 ELSE 0 END) as pending
                FROM requests
                {$whereClause}
                GROUP BY urgency
                ORDER BY
                    CASE urgency
                        WHEN 'Critical' THEN 1
                        WHEN 'Urgent' THEN 2
                        WHEN 'Normal' THEN 3
                        ELSE 4
                    END
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get requests by urgency error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get request statistics by project
     *
     * @param string|null $dateFrom Start date filter
     * @param string|null $dateTo End date filter
     * @return array Project statistics
     */
    public function getRequestsByProject($dateFrom = null, $dateTo = null) {
        try {
            $conditions = [];
            $params = [];

            if ($dateFrom) {
                $conditions[] = "DATE(r.created_at) >= ?";
                $params[] = $dateFrom;
            }

            if ($dateTo) {
                $conditions[] = "DATE(r.created_at) <= ?";
                $params[] = $dateTo;
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $sql = "
                SELECT
                    p.id as project_id,
                    p.name as project_name,
                    p.code as project_code,
                    COUNT(r.id) as total_requests,
                    SUM(CASE WHEN r.status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN r.status = 'Declined' THEN 1 ELSE 0 END) as declined,
                    SUM(CASE WHEN r.status IN ('Draft', 'Submitted', 'Reviewed', 'Forwarded') THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN r.estimated_cost IS NOT NULL THEN r.estimated_cost ELSE 0 END) as total_estimated_cost
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                {$whereClause}
                GROUP BY p.id, p.name, p.code
                ORDER BY total_requests DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get requests by project error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get approval rate statistics
     *
     * @param int|null $projectId Optional project filter
     * @param string|null $dateFrom Start date filter
     * @param string|null $dateTo End date filter
     * @return array Approval rate statistics
     */
    public function getApprovalRate($projectId = null, $dateFrom = null, $dateTo = null) {
        try {
            $conditions = ["status IN ('Approved', 'Declined')"];
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
                    COUNT(*) as total_processed,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = 'Declined' THEN 1 ELSE 0 END) as declined_count,
                    ROUND((SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as approval_rate
                FROM requests
                {$whereClause}
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            return $result ?: [
                'total_processed' => 0,
                'approved_count' => 0,
                'declined_count' => 0,
                'approval_rate' => 0
            ];

        } catch (Exception $e) {
            error_log("Get approval rate error: " . $e->getMessage());
            return [
                'total_processed' => 0,
                'approved_count' => 0,
                'declined_count' => 0,
                'approval_rate' => 0
            ];
        }
    }
}
?>
