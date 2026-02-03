<?php
/**
 * RequestWorkflowModel - Workflow and Status Management
 *
 * Handles request workflow operations including status transitions,
 * submission, verification, authorization, approval, and decline.
 * Single Responsibility: Request workflow state management.
 *
 * @package ConstructLink\Models\Request
 */

class RequestWorkflowModel extends BaseModel {
    protected $table = 'requests';

    /**
     * Submit request (change from Draft to Submitted)
     *
     * @param int $requestId Request ID
     * @param int $userId User performing the action
     * @return array Result with success status
     */
    public function submitRequest($requestId, $userId) {
        try {
            $request = $this->find($requestId);
            if (!$request) {
                return ['success' => false, 'message' => 'Request not found'];
            }

            if ($request['status'] !== 'Draft') {
                return ['success' => false, 'message' => 'Only draft requests can be submitted'];
            }

            $this->beginTransaction();

            $updateData = [
                'status' => 'Submitted',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $result = $this->update($requestId, $updateData);

            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to submit request'];
            }

            // Log the submission
            $activityModel = new RequestActivityModel();
            $activityModel->logRequestActivity($requestId, 'request_submitted', 'Draft', 'Submitted', 'Request submitted for review', $userId);

            $this->commit();

            return ['success' => true, 'request' => $result, 'message' => 'Request submitted successfully'];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Request submission error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit request: ' . $e->getMessage()];
        }
    }

    /**
     * Update request status
     *
     * @param int $requestId Request ID
     * @param string $newStatus New status value
     * @param int $userId User performing the action
     * @param string|null $remarks Optional remarks
     * @return array Result with success status
     */
    public function updateRequestStatus($requestId, $newStatus, $userId, $remarks = null) {
        try {
            $request = $this->find($requestId);
            if (!$request) {
                return ['success' => false, 'message' => 'Request not found'];
            }

            $oldStatus = $request['status'];

            $this->beginTransaction();

            $updateData = ['status' => $newStatus];

            // Set appropriate user fields based on status
            switch ($newStatus) {
                case 'Reviewed':
                    $updateData['reviewed_by'] = $userId;
                    break;
                case 'Approved':
                case 'Declined':
                    $updateData['approved_by'] = $userId;
                    break;
                case 'Verified':
                    $updateData['verified_by'] = $userId;
                    break;
                case 'Authorized':
                    $updateData['authorized_by'] = $userId;
                    break;
            }

            if ($remarks) {
                $updateData['remarks'] = $remarks;
            }

            $result = $this->update($requestId, $updateData);

            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update request status'];
            }

            // Log the status change
            $activityModel = new RequestActivityModel();
            $activityModel->logRequestActivity($requestId, 'status_changed', $oldStatus, $newStatus, $remarks, $userId);

            $this->commit();

            return ['success' => true, 'request' => $result];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Request status update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update request status'];
        }
    }

    /**
     * Get request with workflow details for MVA display
     *
     * @param int $id Request ID
     * @return array|false Request with complete workflow information
     */
    public function getRequestWithWorkflow($id) {
        try {
            $sql = "
                SELECT r.*,
                       p.name as project_name, p.code as project_code,
                       p.project_manager_id,
                       creator.id as creator_id,
                       creator.full_name as creator_name,
                       creator.full_name as requested_by_name,
                       creator_role.name as creator_role,
                       verifier.full_name as verifier_name,
                       verifier.full_name as verified_by_name,
                       authorizer.full_name as authorizer_name,
                       authorizer.full_name as authorized_by_name,
                       approver.full_name as approver_name,
                       approver.full_name as approved_by_name,
                       decliner.full_name as decliner_name,
                       decliner.full_name as declined_by_name,
                       reviewer.full_name as reviewed_by_name,
                       proc.po_number, proc.status as procurement_status
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users creator ON r.requested_by = creator.id
                LEFT JOIN roles creator_role ON creator.role_id = creator_role.id
                LEFT JOIN users reviewer ON r.reviewed_by = reviewer.id
                LEFT JOIN users verifier ON r.verified_by = verifier.id
                LEFT JOIN users authorizer ON r.authorized_by = authorizer.id
                LEFT JOIN users approver ON r.approved_by = approver.id
                LEFT JOIN users decliner ON r.declined_by = decliner.id
                LEFT JOIN procurement_orders proc ON r.procurement_id = proc.id
                WHERE r.id = ?
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();

        } catch (Exception $e) {
            error_log("Get request with workflow error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get pending requests for approval
     *
     * @param int|null $userId User ID for role-based filtering
     * @param string|null $userRole User role for filtering
     * @return array Array of pending requests
     */
    public function getPendingRequests($userId = null, $userRole = null) {
        try {
            $conditions = ["r.status IN ('Submitted', 'Reviewed', 'Forwarded', 'Verified', 'Authorized')"];
            $params = [];

            // Role-based filtering
            if ($userRole && $userRole !== 'System Admin') {
                switch ($userRole) {
                    case 'Asset Director':
                        // Can review all requests
                        break;
                    case 'Finance Director':
                        // Can approve high-value or specific types
                        $conditions[] = "(r.estimated_cost > 50000 OR r.request_type IN ('Petty Cash', 'Service'))";
                        break;
                    case 'Procurement Officer':
                        // Can approve procurement-related requests
                        $conditions[] = "r.request_type IN ('Material', 'Tool', 'Equipment')";
                        break;
                    case 'Project Manager':
                        // Can only see own project requests
                        if ($userId) {
                            $conditions[] = "p.project_manager_id = ?";
                            $params[] = $userId;
                        }
                        break;
                }
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT r.*,
                       p.name as project_name, p.code as project_code,
                       u1.full_name as requested_by_name
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                {$whereClause}
                ORDER BY
                    CASE r.urgency
                        WHEN 'Critical' THEN 1
                        WHEN 'Urgent' THEN 2
                        ELSE 3
                    END,
                    r.created_at ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get pending requests error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get approved requests not yet procured (for procurement officers)
     *
     * @param int|null $projectId Optional project filter
     * @return array Array of approved requests
     */
    public function getApprovedRequestsForProcurement($projectId = null) {
        try {
            $conditions = ["r.status = 'Approved'", "r.procurement_id IS NULL"];
            $params = [];

            if ($projectId) {
                $conditions[] = "r.project_id = ?";
                $params[] = $projectId;
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT r.*,
                       p.name as project_name, p.code as project_code,
                       u1.full_name as requested_by_name,
                       u2.full_name as approved_by_name,
                       DATEDIFF(CURDATE(), r.created_at) as days_since_approval
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                LEFT JOIN users u2 ON r.approved_by = u2.id
                {$whereClause}
                ORDER BY
                    CASE r.urgency
                        WHEN 'Critical' THEN 1
                        WHEN 'Urgent' THEN 2
                        ELSE 3
                    END,
                    r.date_needed ASC,
                    r.created_at ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get approved requests for procurement error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get requests for procurement officer with filters
     *
     * @param int|null $userId User ID (optional)
     * @param array $filters Additional filters
     * @return array Array of requests
     */
    public function getRequestsForProcurementOfficer($userId = null, $filters = []) {
        try {
            $conditions = ["r.status IN ('Approved')"];
            $params = [];

            // Apply additional filters
            if (!empty($filters['project_id'])) {
                $conditions[] = "r.project_id = ?";
                $params[] = $filters['project_id'];
            }

            if (!empty($filters['request_type'])) {
                $conditions[] = "r.request_type = ?";
                $params[] = $filters['request_type'];
            }

            if (!empty($filters['urgency'])) {
                $conditions[] = "r.urgency = ?";
                $params[] = $filters['urgency'];
            }

            if (isset($filters['not_procured']) && $filters['not_procured']) {
                $conditions[] = "r.procurement_id IS NULL";
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT r.*,
                       p.name as project_name, p.code as project_code,
                       u1.full_name as requested_by_name,
                       u2.full_name as approved_by_name,
                       po.po_number, po.status as procurement_status
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                LEFT JOIN users u2 ON r.approved_by = u2.id
                LEFT JOIN procurement_orders po ON r.procurement_id = po.id
                {$whereClause}
                ORDER BY
                    CASE r.urgency
                        WHEN 'Critical' THEN 1
                        WHEN 'Urgent' THEN 2
                        ELSE 3
                    END,
                    r.date_needed ASC,
                    r.created_at ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get requests for procurement officer error: " . $e->getMessage());
            return [];
        }
    }
}
?>
