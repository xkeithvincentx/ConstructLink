<?php
/**
 * RequestModelFacade - Backward Compatibility Layer
 *
 * Maintains backward compatibility with existing code by delegating
 * method calls to the appropriate refactored models.
 * This facade allows existing code to continue working without modification.
 *
 * @package ConstructLink\Models
 */

// Load refactored models
require_once APP_ROOT . '/models/Request/RequestModel.php';
require_once APP_ROOT . '/models/Request/RequestWorkflowModel.php';
require_once APP_ROOT . '/models/Request/RequestDeliveryModel.php';
require_once APP_ROOT . '/models/Request/RequestStatisticsModel.php';
require_once APP_ROOT . '/models/Request/RequestRestockModel.php';
require_once APP_ROOT . '/models/Request/RequestActivityModel.php';

class RequestModelFacade extends BaseModel {
    protected $table = 'requests';
    protected $fillable = [
        'project_id', 'request_type', 'category', 'description', 'quantity', 'unit',
        'urgency', 'date_needed', 'requested_by', 'reviewed_by', 'approved_by',
        'remarks', 'estimated_cost', 'actual_cost', 'procurement_id', 'status',
        'inventory_item_id', 'is_restock', 'verified_by', 'authorized_by', 'declined_by'
    ];

    private $crudModel;
    private $workflowModel;
    private $deliveryModel;
    private $statisticsModel;
    private $restockModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();

        // Initialize refactored models
        $this->crudModel = new RequestModel();
        $this->workflowModel = new RequestWorkflowModel();
        $this->deliveryModel = new RequestDeliveryModel();
        $this->statisticsModel = new RequestStatisticsModel();
        $this->restockModel = new RequestRestockModel();
        $this->activityModel = new RequestActivityModel();
    }

    // ===== CRUD Operations (delegate to RequestModel) =====

    public function createRequest($data) {
        return $this->crudModel->createRequest($data);
    }

    public function updateRequest($requestId, $data) {
        return $this->crudModel->updateRequest($requestId, $data);
    }

    public function deleteRequest($requestId) {
        return $this->crudModel->deleteRequest($requestId);
    }

    public function getRequestWithDetails($id) {
        return $this->crudModel->getRequestWithDetails($id);
    }

    public function getRequestsWithFilters($filters = [], $page = 1, $perPage = 20) {
        return $this->crudModel->getRequestsWithFilters($filters, $page, $perPage);
    }

    public function linkToProcurementOrder($requestId, $procurementOrderId) {
        return $this->crudModel->linkToProcurementOrder($requestId, $procurementOrderId);
    }

    public function canBeProcured($requestId) {
        return $this->crudModel->canBeProcured($requestId);
    }

    // ===== Workflow Operations (delegate to RequestWorkflowModel) =====

    public function submitRequest($requestId, $userId) {
        return $this->workflowModel->submitRequest($requestId, $userId);
    }

    public function updateRequestStatus($requestId, $newStatus, $userId, $remarks = null) {
        return $this->workflowModel->updateRequestStatus($requestId, $newStatus, $userId, $remarks);
    }

    public function getRequestWithWorkflow($id) {
        return $this->workflowModel->getRequestWithWorkflow($id);
    }

    public function getPendingRequests($userId = null, $userRole = null) {
        return $this->workflowModel->getPendingRequests($userId, $userRole);
    }

    public function getApprovedRequestsForProcurement($projectId = null) {
        return $this->workflowModel->getApprovedRequestsForProcurement($projectId);
    }

    public function getRequestsForProcurementOfficer($userId = null, $filters = []) {
        return $this->workflowModel->getRequestsForProcurementOfficer($userId, $filters);
    }

    // ===== Statistics Operations (delegate to RequestStatisticsModel) =====

    public function getRequestStatistics($projectId = null, $dateFrom = null, $dateTo = null) {
        return $this->statisticsModel->getRequestStatistics($projectId, $dateFrom, $dateTo);
    }

    public function getRequestsByType($dateFrom = null, $dateTo = null) {
        return $this->statisticsModel->getRequestsByType($dateFrom, $dateTo);
    }

    public function getRequestsByUrgency($projectId = null) {
        return $this->statisticsModel->getRequestsByUrgency($projectId);
    }

    public function getRequestsByProject($dateFrom = null, $dateTo = null) {
        return $this->statisticsModel->getRequestsByProject($dateFrom, $dateTo);
    }

    public function getApprovalRate($projectId = null, $dateFrom = null, $dateTo = null) {
        return $this->statisticsModel->getApprovalRate($projectId, $dateFrom, $dateTo);
    }

    // ===== Delivery Operations (delegate to RequestDeliveryModel) =====

    public function getRequestWithDeliveryStatus($id) {
        return $this->deliveryModel->getRequestWithDeliveryStatus($id);
    }

    public function getRequestsWithDeliveryTracking($filters = [], $userRole = null, $userId = null) {
        return $this->deliveryModel->getRequestsWithDeliveryTracking($filters, $userRole, $userId);
    }

    public function getDeliveryAlerts($userRole = null, $userId = null) {
        return $this->deliveryModel->getDeliveryAlerts($userRole, $userId);
    }

    public function getDeliveryStatistics($projectId = null, $dateFrom = null, $dateTo = null) {
        return $this->deliveryModel->getDeliveryStatistics($projectId, $dateFrom, $dateTo);
    }

    // ===== Restock Operations (delegate to RequestRestockModel) =====

    public function getRestockDetails($requestId) {
        return $this->restockModel->getRestockDetails($requestId);
    }

    public function validateRestockRequest($data) {
        return $this->restockModel->validateRestockRequest($data);
    }

    public function getInventoryItemsForRestock($projectId = null, $lowStockOnly = false) {
        return $this->restockModel->getInventoryItemsForRestock($projectId, $lowStockOnly);
    }

    public function getLowStockItems($projectId = null, $threshold = 0.2) {
        return $this->restockModel->getLowStockItems($projectId, $threshold);
    }

    public function getRestockStatistics($projectId = null, $dateFrom = null, $dateTo = null) {
        return $this->restockModel->getRestockStatistics($projectId, $dateFrom, $dateTo);
    }

    // ===== Activity Logging Operations (delegate to RequestActivityModel) =====

    public function logRequestActivity($requestId, $action, $oldStatus, $newStatus, $remarks = null, $userId = null) {
        return $this->activityModel->logRequestActivity($requestId, $action, $oldStatus, $newStatus, $remarks, $userId);
    }

    public function getRequestLogs($requestId) {
        return $this->activityModel->getRequestLogs($requestId);
    }

    public function getRecentActivities($limit = 50, $userId = null, $projectId = null) {
        return $this->activityModel->getRecentActivities($limit, $userId, $projectId);
    }

    public function getActivityStatistics($dateFrom = null, $dateTo = null) {
        return $this->activityModel->getActivityStatistics($dateFrom, $dateTo);
    }

    public function getActivitiesByAction($dateFrom = null, $dateTo = null) {
        return $this->activityModel->getActivitiesByAction($dateFrom, $dateTo);
    }

    public function getUserActivitySummary($userId, $dateFrom = null, $dateTo = null) {
        return $this->activityModel->getUserActivitySummary($userId, $dateFrom, $dateTo);
    }
}
?>
