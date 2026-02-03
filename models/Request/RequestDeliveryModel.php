<?php
/**
 * RequestDeliveryModel - Delivery Tracking and Procurement Status
 *
 * Handles delivery tracking, procurement status, and delivery alerts.
 * Single Responsibility: Request delivery and procurement tracking.
 *
 * @package ConstructLink\Models\Request
 */

class RequestDeliveryModel extends BaseModel {
    protected $table = 'requests';

    /**
     * Get request with procurement and delivery status
     *
     * @param int $id Request ID
     * @return array|false Request with delivery status or false
     */
    public function getRequestWithDeliveryStatus($id) {
        try {
            $sql = "
                SELECT r.*,
                       p.name as project_name, p.code as project_code,
                       u1.full_name as requested_by_name,
                       u2.full_name as reviewed_by_name,
                       u3.full_name as approved_by_name,
                       po.id as procurement_order_id,
                       po.po_number,
                       po.status as procurement_status,
                       po.delivery_status,
                       po.delivery_method,
                       po.tracking_number,
                       po.scheduled_delivery_date,
                       po.actual_delivery_date,
                       po.delivery_location,
                       po.delivery_notes,
                       po.delivery_discrepancy_notes,
                       po.net_total as procurement_total,
                       v.name as vendor_name,
                       v.contact_person as vendor_contact,
                       CASE
                           WHEN po.delivery_status = 'Received' THEN 'Completed'
                           WHEN po.delivery_status IN ('Delivered', 'In Transit') THEN 'In Progress'
                           WHEN po.delivery_status = 'Scheduled' THEN 'Scheduled'
                           WHEN po.status = 'Approved' THEN 'Ready for Delivery'
                           WHEN po.status IN ('Pending', 'Reviewed') THEN 'Processing'
                           WHEN r.status = 'Approved' AND po.id IS NULL THEN 'Awaiting Procurement'
                           ELSE 'Not Started'
                       END as overall_delivery_status
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                LEFT JOIN users u2 ON r.reviewed_by = u2.id
                LEFT JOIN users u3 ON r.approved_by = u3.id
                LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                LEFT JOIN vendors v ON po.vendor_id = v.id
                WHERE r.id = ?
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();

        } catch (Exception $e) {
            error_log("Get request with delivery status error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get requests with delivery tracking for stakeholder dashboard
     *
     * @param array $filters Filter criteria
     * @param string|null $userRole User role for filtering
     * @param int|null $userId User ID for filtering
     * @return array Array of requests with delivery info
     */
    public function getRequestsWithDeliveryTracking($filters = [], $userRole = null, $userId = null) {
        try {
            $conditions = [];
            $params = [];

            // Role-based filtering
            if ($userRole && $userRole !== 'System Admin') {
                switch ($userRole) {
                    case 'Finance Director':
                        // Can see all requests
                        break;
                    case 'Asset Director':
                        // Can see all requests
                        break;
                    case 'Procurement Officer':
                        $conditions[] = "r.status IN ('Approved', 'Procured') OR po.id IS NOT NULL";
                        break;
                    case 'Warehouseman':
                        $conditions[] = "po.delivery_status IN ('Scheduled', 'In Transit', 'Delivered')";
                        break;
                    case 'Project Manager':
                        if ($userId) {
                            $conditions[] = "(p.project_manager_id = ? OR r.requested_by = ?)";
                            $params = array_merge($params, [$userId, $userId]);
                        }
                        break;
                    case 'Site Inventory Clerk':
                        if ($userId) {
                            $conditions[] = "r.requested_by = ?";
                            $params[] = $userId;
                        }
                        break;
                }
            }

            // Apply additional filters
            if (!empty($filters['status'])) {
                $conditions[] = "r.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['delivery_status'])) {
                $conditions[] = "po.delivery_status = ?";
                $params[] = $filters['delivery_status'];
            }

            if (!empty($filters['project_id'])) {
                $conditions[] = "r.project_id = ?";
                $params[] = $filters['project_id'];
            }

            // Handle project_ids array for Project Managers
            if (!empty($filters['project_ids']) && is_array($filters['project_ids'])) {
                $placeholders = str_repeat('?,', count($filters['project_ids']) - 1) . '?';
                $conditions[] = "r.project_id IN ($placeholders)";
                $params = array_merge($params, $filters['project_ids']);
            }

            if (!empty($filters['requested_by'])) {
                $conditions[] = "r.requested_by = ?";
                $params[] = $filters['requested_by'];
            }

            if (!empty($filters['procurement_status'])) {
                $conditions[] = "po.status = ?";
                $params[] = $filters['procurement_status'];
            }

            if (!empty($filters['urgency'])) {
                $conditions[] = "r.urgency = ?";
                $params[] = $filters['urgency'];
            }

            if (!empty($filters['overdue_delivery'])) {
                $conditions[] = "po.scheduled_delivery_date < CURDATE() AND po.delivery_status NOT IN ('Delivered', 'Received')";
            }

            if (!empty($filters['has_discrepancy'])) {
                $conditions[] = "po.delivery_discrepancy_notes IS NOT NULL AND po.delivery_discrepancy_notes != ''";
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $sql = "
                SELECT r.*,
                       p.name as project_name, p.code as project_code,
                       p.project_manager_id,
                       u1.full_name as requested_by_name,
                       po.id as procurement_order_id,
                       po.po_number,
                       po.status as procurement_status,
                       po.delivery_status,
                       po.delivery_method,
                       po.tracking_number,
                       po.scheduled_delivery_date,
                       po.actual_delivery_date,
                       po.delivery_location,
                       po.delivery_discrepancy_notes,
                       po.net_total as procurement_total,
                       v.name as vendor_name,
                       CASE
                           WHEN po.delivery_status = 'Received' THEN 'Completed'
                           WHEN po.delivery_status IN ('Delivered', 'In Transit') THEN 'In Progress'
                           WHEN po.delivery_status = 'Scheduled' THEN 'Scheduled'
                           WHEN po.status = 'Approved' THEN 'Ready for Delivery'
                           WHEN po.status IN ('Pending', 'Reviewed') THEN 'Processing'
                           WHEN r.status = 'Approved' AND po.id IS NULL THEN 'Awaiting Procurement'
                           ELSE 'Not Started'
                       END as overall_delivery_status,
                       CASE
                           WHEN po.scheduled_delivery_date < CURDATE() AND po.delivery_status NOT IN ('Delivered', 'Received') THEN 1
                           ELSE 0
                       END as is_overdue,
                       CASE
                           WHEN po.delivery_discrepancy_notes IS NOT NULL AND po.delivery_discrepancy_notes != '' THEN 1
                           ELSE 0
                       END as has_discrepancy
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                LEFT JOIN vendors v ON po.vendor_id = v.id
                {$whereClause}
                ORDER BY
                    CASE r.urgency
                        WHEN 'Critical' THEN 1
                        WHEN 'Urgent' THEN 2
                        ELSE 3
                    END,
                    CASE
                        WHEN po.scheduled_delivery_date < CURDATE() AND po.delivery_status NOT IN ('Delivered', 'Received') THEN 1
                        ELSE 2
                    END,
                    r.created_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get requests with delivery tracking error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get delivery alerts for stakeholders
     *
     * @param string|null $userRole User role for filtering
     * @param int|null $userId User ID for filtering
     * @return array Array of alert data
     */
    public function getDeliveryAlerts($userRole = null, $userId = null) {
        try {
            $conditions = [];
            $params = [];
            $alerts = [];

            // Role-based alert filtering
            if ($userRole && $userRole !== 'System Admin') {
                switch ($userRole) {
                    case 'Procurement Officer':
                        $conditions[] = "po.status = 'Approved' AND (po.delivery_status IS NULL OR po.delivery_status = 'Pending')";
                        break;
                    case 'Warehouseman':
                        $conditions[] = "po.delivery_status IN ('Scheduled', 'In Transit', 'Delivered')";
                        break;
                    case 'Project Manager':
                    case 'Site Inventory Clerk':
                        if ($userId) {
                            $conditions[] = "(p.project_manager_id = ? OR r.requested_by = ?)";
                            $params = array_merge($params, [$userId, $userId]);
                        }
                        break;
                    case 'Finance Director':
                    case 'Asset Director':
                        // Can see all alerts
                        break;
                    default:
                        return []; // No alerts for other roles
                }
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            // Overdue deliveries
            $sql = "
                SELECT 'overdue_delivery' as alert_type,
                       COUNT(*) as count,
                       'Overdue Deliveries' as title,
                       'danger' as severity
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                WHERE po.scheduled_delivery_date < CURDATE()
                  AND po.delivery_status NOT IN ('Delivered', 'Received')
                  " . ($whereClause ? "AND " . str_replace("WHERE ", "", $whereClause) : "") . "
                HAVING count > 0
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $overdueAlert = $stmt->fetch();
            if ($overdueAlert && $overdueAlert['count'] > 0) {
                $alerts[] = $overdueAlert;
            }

            // Delivery discrepancies
            $sql = "
                SELECT 'delivery_discrepancy' as alert_type,
                       COUNT(*) as count,
                       'Delivery Discrepancies' as title,
                       'warning' as severity
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                WHERE po.delivery_discrepancy_notes IS NOT NULL
                  AND po.delivery_discrepancy_notes != ''
                  AND po.delivery_status != 'Received'
                  " . ($whereClause ? "AND " . str_replace("WHERE ", "", $whereClause) : "") . "
                HAVING count > 0
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $discrepancyAlert = $stmt->fetch();
            if ($discrepancyAlert && $discrepancyAlert['count'] > 0) {
                $alerts[] = $discrepancyAlert;
            }

            // Ready for delivery scheduling
            if (in_array($userRole, ['System Admin', 'Procurement Officer', 'Asset Director'])) {
                $sql = "
                    SELECT 'ready_for_delivery' as alert_type,
                           COUNT(*) as count,
                           'Ready for Delivery Scheduling' as title,
                           'info' as severity
                    FROM requests r
                    LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                    WHERE po.status = 'Approved'
                      AND (po.delivery_status IS NULL OR po.delivery_status = 'Pending')
                    HAVING count > 0
                ";

                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $readyAlert = $stmt->fetch();
                if ($readyAlert && $readyAlert['count'] > 0) {
                    $alerts[] = $readyAlert;
                }
            }

            // Awaiting receipt confirmation
            if (in_array($userRole, ['System Admin', 'Warehouseman', 'Asset Director'])) {
                $sql = "
                    SELECT 'awaiting_receipt' as alert_type,
                           COUNT(*) as count,
                           'Awaiting Receipt Confirmation' as title,
                           'success' as severity
                    FROM requests r
                    LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                    WHERE po.delivery_status = 'Delivered'
                      AND po.status != 'Received'
                    HAVING count > 0
                ";

                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $receiptAlert = $stmt->fetch();
                if ($receiptAlert && $receiptAlert['count'] > 0) {
                    $alerts[] = $receiptAlert;
                }
            }

            return $alerts;

        } catch (Exception $e) {
            error_log("Get delivery alerts error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get delivery statistics for dashboard
     *
     * @param int|null $projectId Optional project filter
     * @param string|null $dateFrom Start date filter
     * @param string|null $dateTo End date filter
     * @return array Delivery statistics
     */
    public function getDeliveryStatistics($projectId = null, $dateFrom = null, $dateTo = null) {
        try {
            $conditions = [];
            $params = [];

            if ($projectId) {
                $conditions[] = "r.project_id = ?";
                $params[] = $projectId;
            }

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
                    COUNT(DISTINCT r.id) as total_requests_with_procurement,
                    COUNT(DISTINCT CASE WHEN po.delivery_status = 'Pending' THEN po.id END) as pending_delivery,
                    COUNT(DISTINCT CASE WHEN po.delivery_status = 'Scheduled' THEN po.id END) as scheduled_delivery,
                    COUNT(DISTINCT CASE WHEN po.delivery_status = 'In Transit' THEN po.id END) as in_transit,
                    COUNT(DISTINCT CASE WHEN po.delivery_status = 'Delivered' THEN po.id END) as delivered,
                    COUNT(DISTINCT CASE WHEN po.delivery_status = 'Received' THEN po.id END) as received,
                    COUNT(DISTINCT CASE WHEN po.delivery_status = 'Partial' THEN po.id END) as partial_delivery,
                    COUNT(DISTINCT CASE WHEN po.delivery_discrepancy_notes IS NOT NULL AND po.delivery_discrepancy_notes != '' THEN po.id END) as with_discrepancies,
                    COUNT(DISTINCT CASE WHEN po.scheduled_delivery_date < CURDATE() AND po.delivery_status NOT IN ('Delivered', 'Received') THEN po.id END) as overdue_deliveries,
                    AVG(CASE WHEN po.actual_delivery_date IS NOT NULL AND po.scheduled_delivery_date IS NOT NULL
                        THEN DATEDIFF(po.actual_delivery_date, po.scheduled_delivery_date) ELSE NULL END) as avg_delivery_delay_days
                FROM requests r
                LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                {$whereClause}
                  AND po.id IS NOT NULL
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            return $result ?: [
                'total_requests_with_procurement' => 0,
                'pending_delivery' => 0,
                'scheduled_delivery' => 0,
                'in_transit' => 0,
                'delivered' => 0,
                'received' => 0,
                'partial_delivery' => 0,
                'with_discrepancies' => 0,
                'overdue_deliveries' => 0,
                'avg_delivery_delay_days' => 0
            ];

        } catch (Exception $e) {
            error_log("Get delivery statistics error: " . $e->getMessage());
            return [
                'total_requests_with_procurement' => 0,
                'pending_delivery' => 0,
                'scheduled_delivery' => 0,
                'in_transit' => 0,
                'delivered' => 0,
                'received' => 0,
                'partial_delivery' => 0,
                'with_discrepancies' => 0,
                'overdue_deliveries' => 0,
                'avg_delivery_delay_days' => 0
            ];
        }
    }
}
?>
