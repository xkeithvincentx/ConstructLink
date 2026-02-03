<?php
/**
 * RequestCRUD - Core CRUD Operations
 *
 * Handles basic Create, Read, Update, Delete operations for requests.
 * Single Responsibility: Data persistence and retrieval only.
 *
 * @package ConstructLink\Models\Request
 */

class RequestCRUD extends BaseModel {
    protected $table = 'requests';
    protected $fillable = [
        'project_id', 'request_type', 'category', 'description', 'quantity', 'unit',
        'urgency', 'date_needed', 'requested_by', 'reviewed_by', 'approved_by',
        'remarks', 'estimated_cost', 'actual_cost', 'procurement_id', 'status',
        'inventory_item_id', 'is_restock', 'verified_by', 'authorized_by', 'declined_by'
    ];

    /**
     * Create a new request
     *
     * @param array $data Request data
     * @return array Result with success status and created request
     */
    public function createRequest($data) {
        try {
            $validation = $this->validate($data, [
                'project_id' => 'required|integer',
                'request_type' => 'required|in:Material,Tool,Equipment,Service,Petty Cash,Other,Restock',
                'description' => 'required|max:1000',
                'requested_by' => 'required|integer'
            ]);

            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }

            $this->beginTransaction();

            // Set default status
            $data['status'] = 'Draft';

            // Validate date_needed if provided
            if (!empty($data['date_needed'])) {
                if (strtotime($data['date_needed']) <= time()) {
                    $this->rollback();
                    return ['success' => false, 'message' => 'Date needed must be in the future'];
                }
            }

            // Create request record
            $request = $this->create($data);

            if (!$request) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to create request'];
            }

            // Log the request creation
            $activityModel = new RequestActivityModel();
            $activityModel->logRequestActivity($request['id'], 'request_created', null, 'Draft', 'Request created');

            $this->commit();

            return ['success' => true, 'request' => $request];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Request creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create request'];
        }
    }

    /**
     * Update request
     *
     * @param int $requestId Request ID
     * @param array $data Update data
     * @return array Result with success status
     */
    public function updateRequest($requestId, $data) {
        try {
            $this->beginTransaction();

            $result = $this->update($requestId, $data);

            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to update request'];
            }

            $this->commit();

            return ['success' => true, 'request' => $result];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Request update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update request'];
        }
    }

    /**
     * Delete request
     *
     * @param int $requestId Request ID
     * @return array Result with success status
     */
    public function deleteRequest($requestId) {
        try {
            $this->beginTransaction();

            $result = $this->delete($requestId);

            if (!$result) {
                $this->rollback();
                return ['success' => false, 'message' => 'Failed to delete request'];
            }

            $this->commit();

            return ['success' => true, 'message' => 'Request deleted successfully'];

        } catch (Exception $e) {
            $this->rollback();
            error_log("Request deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete request'];
        }
    }

    /**
     * Get request with detailed information
     *
     * @param int $id Request ID
     * @return array|false Request with joined details or false
     */
    public function getRequestWithDetails($id) {
        try {
            $sql = "
                SELECT r.*,
                       p.name as project_name, p.code as project_code,
                       p.project_manager_id,
                       u1.full_name as requested_by_name,
                       u2.full_name as reviewed_by_name,
                       u3.full_name as approved_by_name,
                       u4.full_name as verified_by_name,
                       u5.full_name as authorized_by_name,
                       u6.full_name as declined_by_name,
                       proc.po_number, proc.status as procurement_status
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                LEFT JOIN users u2 ON r.reviewed_by = u2.id
                LEFT JOIN users u3 ON r.approved_by = u3.id
                LEFT JOIN users u4 ON r.verified_by = u4.id
                LEFT JOIN users u5 ON r.authorized_by = u5.id
                LEFT JOIN users u6 ON r.declined_by = u6.id
                LEFT JOIN procurement_orders proc ON r.procurement_id = proc.id
                WHERE r.id = ?
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();

        } catch (Exception $e) {
            error_log("Get request with details error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get requests with filters and pagination
     *
     * @param array $filters Filter criteria
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Paginated results with data and pagination info
     */
    public function getRequestsWithFilters($filters = [], $page = 1, $perPage = 20) {
        try {
            $conditions = [];
            $params = [];

            // Apply filters
            if (!empty($filters['status'])) {
                $conditions[] = "r.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['request_type'])) {
                $conditions[] = "r.request_type = ?";
                $params[] = $filters['request_type'];
            }

            if (!empty($filters['project_id'])) {
                $conditions[] = "r.project_id = ?";
                $params[] = $filters['project_id'];
            }

            if (!empty($filters['urgency'])) {
                $conditions[] = "r.urgency = ?";
                $params[] = $filters['urgency'];
            }

            if (!empty($filters['requested_by'])) {
                $conditions[] = "r.requested_by = ?";
                $params[] = $filters['requested_by'];
            }

            if (!empty($filters['date_from'])) {
                $conditions[] = "DATE(r.created_at) >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $conditions[] = "DATE(r.created_at) <= ?";
                $params[] = $filters['date_to'];
            }

            if (!empty($filters['search'])) {
                $conditions[] = "(r.description LIKE ? OR p.name LIKE ? OR u1.full_name LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            // Count total records
            $countSql = "
                SELECT COUNT(*)
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                {$whereClause}
            ";

            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();

            // Get paginated data
            $offset = ($page - 1) * $perPage;
            $orderBy = $filters['order_by'] ?? 'r.created_at DESC';

            $dataSql = "
                SELECT r.*,
                       p.name as project_name, p.code as project_code,
                       u1.full_name as requested_by_name,
                       u2.full_name as reviewed_by_name,
                       u3.full_name as approved_by_name
                FROM requests r
                LEFT JOIN projects p ON r.project_id = p.id
                LEFT JOIN users u1 ON r.requested_by = u1.id
                LEFT JOIN users u2 ON r.reviewed_by = u2.id
                LEFT JOIN users u3 ON r.approved_by = u3.id
                {$whereClause}
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}
            ";

            $stmt = $this->db->prepare($dataSql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();

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

        } catch (Exception $e) {
            error_log("Get requests with filters error: " . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_next' => false,
                    'has_prev' => false
                ]
            ];
        }
    }

    /**
     * Link request to procurement order
     *
     * @param int $requestId Request ID
     * @param int $procurementOrderId Procurement order ID
     * @return array Result with success status
     */
    public function linkToProcurementOrder($requestId, $procurementOrderId) {
        try {
            $request = $this->find($requestId);
            if (!$request) {
                return ['success' => false, 'message' => 'Request not found'];
            }

            if ($request['status'] !== 'Approved') {
                return ['success' => false, 'message' => 'Only approved requests can be linked to procurement orders'];
            }

            if ($request['procurement_id']) {
                return ['success' => false, 'message' => 'Request is already linked to a procurement order'];
            }

            $result = $this->update($requestId, [
                'procurement_id' => $procurementOrderId,
                'status' => 'Procured'
            ]);

            if (!$result) {
                return ['success' => false, 'message' => 'Failed to link request to procurement order'];
            }

            // Log the linking activity
            try {
                $activityModel = new RequestActivityModel();
                $activityModel->logRequestActivity($requestId, 'linked_to_procurement_order', 'Approved', 'Procured',
                    "Linked to procurement order ID: {$procurementOrderId}");
            } catch (Exception $logError) {
                error_log("Failed to log request activity: " . $logError->getMessage());
            }

            return ['success' => true, 'message' => 'Request successfully linked to procurement order'];

        } catch (Exception $e) {
            error_log("Link to procurement order error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to link request to procurement order'];
        }
    }

    /**
     * Check if request can be procured
     *
     * @param int $requestId Request ID
     * @return array Can procure status with reason
     */
    public function canBeProcured($requestId) {
        try {
            $request = $this->find($requestId);

            if (!$request) {
                return ['can_procure' => false, 'reason' => 'Request not found'];
            }

            if ($request['status'] !== 'Approved') {
                return ['can_procure' => false, 'reason' => 'Request is not approved'];
            }

            if ($request['procurement_id']) {
                return ['can_procure' => false, 'reason' => 'Request is already linked to a procurement order'];
            }

            return ['can_procure' => true, 'request' => $request];

        } catch (Exception $e) {
            error_log("Check if request can be procured error: " . $e->getMessage());
            return ['can_procure' => false, 'reason' => 'System error'];
        }
    }
}
?>
