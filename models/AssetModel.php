<?php
/**
 * AssetModel - Facade Pattern Implementation
 *
 * This model now acts as a facade, delegating all operations to specialized service classes.
 * Maintains 100% backward compatibility with existing controllers.
 *
 * Service Architecture:
 * - AssetCrudService: Create, read, update, delete operations
 * - AssetWorkflowService: Workflow status management and approvals
 * - AssetQuantityService: Quantity tracking and consumption
 * - AssetProcurementService: Procurement integration
 * - AssetStatisticsService: Analytics and reporting
 * - AssetQueryService: Advanced search and filtering
 * - AssetActivityService: Activity logging and history
 * - AssetValidationService: Business rule validation
 * - AssetExportService: Export and report generation
 *
 * @deprecated Individual methods will be deprecated over 3 months. Use services directly for new code.
 */

class AssetModel extends BaseModel {
    protected $table = 'inventory_items';
    protected $fillable = [
        'ref', 'category_id', 'name', 'description', 'project_id', 'maker_id',
        'vendor_id', 'client_id', 'acquired_date', 'status', 'is_client_supplied',
        'acquisition_cost', 'serial_number', 'model', 'qr_code', 'procurement_order_id',
        'procurement_item_id', 'unit_cost', 'quantity', 'available_quantity', 'unit',
        'inventory_source', 'sub_location', 'workflow_status', 'made_by', 'verified_by',
        'authorized_by', 'verification_date', 'authorization_date',
        'equipment_type_id', 'subtype_id', 'generated_name', 'name_components',
        'specifications', 'warranty_expiry', 'location', 'condition_notes', 'current_condition',
        'brand_id', 'standardized_name', 'original_name', 'asset_type_id',
        'discipline_tags',
        'qr_tag_printed', 'qr_tag_applied', 'qr_tag_verified',
        'qr_tag_applied_by', 'qr_tag_verified_by', 'tag_notes'
    ];

    // Service instances - lazy loaded
    private $crudService;
    private $workflowService;
    private $quantityService;
    private $procurementService;
    private $statisticsService;
    private $queryService;
    private $activityService;
    private $validationService;
    private $exportService;

    /**
     * Constructor - Initialize base model
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Lazy load CRUD service
     */
    private function getCrudService() {
        if ($this->crudService === null) {
            require_once __DIR__ . '/../services/Asset/AssetCrudService.php';
            $this->crudService = new AssetCrudService();
        }
        return $this->crudService;
    }

    /**
     * Lazy load Workflow service
     */
    private function getWorkflowService() {
        if ($this->workflowService === null) {
            require_once __DIR__ . '/../services/Asset/AssetWorkflowService.php';
            $this->workflowService = new AssetWorkflowService();
        }
        return $this->workflowService;
    }

    /**
     * Lazy load Quantity service
     */
    private function getQuantityService() {
        if ($this->quantityService === null) {
            require_once __DIR__ . '/../services/Asset/AssetQuantityService.php';
            $this->quantityService = new AssetQuantityService();
        }
        return $this->quantityService;
    }

    /**
     * Lazy load Procurement service
     */
    private function getProcurementService() {
        if ($this->procurementService === null) {
            require_once __DIR__ . '/../services/Asset/AssetProcurementService.php';
            $this->procurementService = new AssetProcurementService();
        }
        return $this->procurementService;
    }

    /**
     * Lazy load Statistics service
     */
    private function getStatisticsService() {
        if ($this->statisticsService === null) {
            require_once __DIR__ . '/../services/Asset/AssetStatisticsService.php';
            $this->statisticsService = new AssetStatisticsService();
        }
        return $this->statisticsService;
    }

    /**
     * Lazy load Query service
     */
    private function getQueryService() {
        if ($this->queryService === null) {
            require_once __DIR__ . '/../services/Asset/AssetQueryService.php';
            $this->queryService = new AssetQueryService();
        }
        return $this->queryService;
    }

    /**
     * Lazy load Activity service
     */
    private function getActivityService() {
        if ($this->activityService === null) {
            require_once __DIR__ . '/../services/Asset/AssetActivityService.php';
            $this->activityService = new AssetActivityService();
        }
        return $this->activityService;
    }

    /**
     * Lazy load Validation service
     */
    private function getValidationService() {
        if ($this->validationService === null) {
            require_once __DIR__ . '/../services/Asset/AssetValidationService.php';
            $this->validationService = new AssetValidationService();
        }
        return $this->validationService;
    }

    /**
     * Lazy load Export service
     */
    private function getExportService() {
        if ($this->exportService === null) {
            require_once __DIR__ . '/../services/Asset/AssetExportService.php';
            $this->exportService = new AssetExportService();
        }
        return $this->exportService;
    }

    // =========================================================================
    // BASE MODEL OVERRIDE - Discipline Handling
    // =========================================================================

    /**
     * Override base create method to handle discipline relationships
     *
     * @param array $data Asset data
     * @return mixed Created asset ID or false on failure
     */
    public function create($data) {
        error_log("AssetModel::create called with data: " . print_r($data, true));

        // Handle discipline processing before creating asset
        $disciplineCodes = [];

        // Handle primary discipline
        if (!empty($data['primary_discipline'])) {
            error_log("Processing primary discipline: " . $data['primary_discipline']);
            $disciplineId = (int)$data['primary_discipline'];
            $stmt = $this->db->prepare("SELECT iso_code FROM inventory_disciplines WHERE id = ? AND is_active = 1");
            $stmt->execute([$disciplineId]);
            $isoCode = $stmt->fetchColumn();
            error_log("Found ISO code: " . ($isoCode ?: 'NULL'));

            if ($isoCode) {
                $disciplineCodes[] = $isoCode;
                error_log("Added ISO code to disciplineCodes: " . $isoCode);
            }
        }

        // Handle additional disciplines
        if (!empty($data['disciplines']) && is_array($data['disciplines'])) {
            foreach ($data['disciplines'] as $disciplineId) {
                $disciplineId = (int)$disciplineId;
                if ($disciplineId > 0) {
                    $stmt = $this->db->prepare("SELECT iso_code FROM inventory_disciplines WHERE id = ? AND is_active = 1");
                    $stmt->execute([$disciplineId]);
                    $isoCode = $stmt->fetchColumn();

                    if ($isoCode && !in_array($isoCode, $disciplineCodes)) {
                        $disciplineCodes[] = $isoCode;
                    }
                }
            }
        }

        // Add discipline tags to data if we found any
        if (!empty($disciplineCodes)) {
            $data['discipline_tags'] = implode(',', $disciplineCodes);
            error_log("Setting discipline_tags to: " . $data['discipline_tags']);
        } else {
            error_log("No discipline codes found");
        }

        // Remove non-fillable discipline fields before calling parent create
        unset($data['primary_discipline']);
        unset($data['disciplines']);

        // Call parent create method
        return parent::create($data);
    }

    // =========================================================================
    // CRUD OPERATIONS - AssetCrudService
    // =========================================================================

    /**
     * Create new asset with validation
     *
     * @param array $data Asset data
     * @return array Result with success status and asset ID or errors
     */
    public function createAsset($data) {
        return $this->getCrudService()->createAsset($data);
    }

    /**
     * Update existing asset
     *
     * @param int $id Asset ID
     * @param array $data Updated asset data
     * @return array Result with success status
     */
    public function updateAsset($id, $data) {
        return $this->getCrudService()->updateAsset($id, $data);
    }

    /**
     * Delete asset with validation
     *
     * @param int $id Asset ID
     * @return array Result with success status
     */
    public function deleteAsset($id) {
        return $this->getCrudService()->deleteAsset($id);
    }

    /**
     * Get asset with all related details
     *
     * @param int $id Asset ID
     * @return array|false Asset with details or false if not found
     */
    public function getAssetWithDetails($id) {
        return $this->getCrudService()->getAssetWithDetails($id);
    }

    /**
     * Find asset by QR code
     *
     * @param string $qrCode QR code to search
     * @return array|false Asset data or false if not found
     */
    public function findByQRCode($qrCode) {
        return $this->getCrudService()->findByQRCode($qrCode);
    }

    /**
     * Update asset status with logging
     *
     * @param int $id Asset ID
     * @param string $status New status
     * @param string|null $notes Optional notes
     * @return array Result with success status
     */
    public function updateAssetStatus($id, $status, $notes = null) {
        return $this->getCrudService()->updateAssetStatus($id, $status, $notes);
    }

    /**
     * Bulk update asset statuses
     *
     * @param array $assetIds Array of asset IDs
     * @param string $status New status
     * @param string|null $notes Optional notes
     * @return array Result with success count
     */
    public function bulkUpdateStatus($assetIds, $status, $notes = null) {
        return $this->getCrudService()->bulkUpdateStatus($assetIds, $status, $notes);
    }

    // =========================================================================
    // WORKFLOW OPERATIONS - AssetWorkflowService
    // =========================================================================

    /**
     * Get assets filtered by workflow status
     *
     * @param string $workflowStatus Workflow status to filter
     * @param int|null $projectId Optional project filter
     * @return array Assets matching workflow status
     */
    public function getAssetsByWorkflowStatus($workflowStatus, $projectId = null) {
        return $this->getWorkflowService()->getAssetsByWorkflowStatus($workflowStatus, $projectId);
    }

    /**
     * Submit asset for verification
     *
     * @param int $assetId Asset ID
     * @param int $submittedBy User ID submitting
     * @return array Result with success status
     */
    public function submitForVerification($assetId, $submittedBy) {
        return $this->getWorkflowService()->submitForVerification($assetId, $submittedBy);
    }

    /**
     * Verify asset (approve verification step)
     *
     * @param int $assetId Asset ID
     * @param int $verifiedBy User ID verifying
     * @param string|null $notes Optional verification notes
     * @return array Result with success status
     */
    public function verifyAsset($assetId, $verifiedBy, $notes = null) {
        return $this->getWorkflowService()->verifyAsset($assetId, $verifiedBy, $notes);
    }

    /**
     * Authorize asset (final approval step)
     *
     * @param int $assetId Asset ID
     * @param int $authorizedBy User ID authorizing
     * @param string|null $notes Optional authorization notes
     * @return array Result with success status
     */
    public function authorizeAsset($assetId, $authorizedBy, $notes = null) {
        return $this->getWorkflowService()->authorizeAsset($assetId, $authorizedBy, $notes);
    }

    /**
     * Reject asset from verification stage
     *
     * @param int $assetId Asset ID
     * @param int $rejectedBy User ID rejecting
     * @param string $reason Rejection reason
     * @return array Result with success status
     */
    public function rejectVerification($assetId, $rejectedBy, $reason) {
        return $this->getWorkflowService()->rejectVerification($assetId, $rejectedBy, $reason);
    }

    /**
     * Reject asset from authorization stage
     *
     * @param int $assetId Asset ID
     * @param int $rejectedBy User ID rejecting
     * @param string $reason Rejection reason
     * @return array Result with success status
     */
    public function rejectAuthorization($assetId, $rejectedBy, $reason) {
        return $this->getWorkflowService()->rejectAuthorization($assetId, $rejectedBy, $reason);
    }

    /**
     * Generic reject asset method (backward compatibility)
     *
     * @param int $assetId Asset ID
     * @param int $rejectedBy User ID rejecting
     * @param string $rejectionReason Rejection reason
     * @return array Result with success status
     */
    public function rejectAsset($assetId, $rejectedBy, $rejectionReason) {
        return $this->getWorkflowService()->rejectAsset($assetId, $rejectedBy, $rejectionReason);
    }

    /**
     * Return asset to draft status
     *
     * @param int $assetId Asset ID
     * @param int $userId User ID performing action
     * @return array Result with success status
     */
    public function returnToDraft($assetId, $userId) {
        return $this->getWorkflowService()->returnToDraft($assetId, $userId);
    }

    /**
     * Get workflow statistics
     *
     * @param int|null $projectId Optional project filter
     * @return array Workflow statistics
     */
    public function getWorkflowStatistics($projectId = null) {
        return $this->getWorkflowService()->getWorkflowStatistics($projectId);
    }

    /**
     * Get pending workflow actions for current user
     *
     * @return array Pending actions requiring user attention
     */
    public function getPendingActionsForUser() {
        return $this->getWorkflowService()->getPendingActionsForUser();
    }

    // =========================================================================
    // QUANTITY MANAGEMENT - AssetQuantityService
    // =========================================================================

    /**
     * Consume asset quantity (decrease available quantity)
     *
     * @param int $assetId Asset ID
     * @param float $quantityToConsume Amount to consume
     * @param string|null $reason Optional consumption reason
     * @return array Result with success status
     */
    public function consumeQuantity($assetId, $quantityToConsume, $reason = null) {
        return $this->getQuantityService()->consumeQuantity($assetId, $quantityToConsume, $reason);
    }

    /**
     * Restore asset quantity (increase available quantity)
     *
     * @param int $assetId Asset ID
     * @param float $quantityToRestore Amount to restore
     * @param string|null $reason Optional restoration reason
     * @return array Result with success status
     */
    public function restoreQuantity($assetId, $quantityToRestore, $reason = null) {
        return $this->getQuantityService()->restoreQuantity($assetId, $quantityToRestore, $reason);
    }

    /**
     * Get quantity status for asset
     *
     * @param int $assetId Asset ID
     * @return array Quantity status information
     */
    public function getQuantityStatus($assetId) {
        return $this->getQuantityService()->getQuantityStatus($assetId);
    }

    /**
     * Check if asset has sufficient quantity
     *
     * @param int $assetId Asset ID
     * @param float $requiredQuantity Required quantity
     * @return bool True if sufficient quantity available
     */
    public function hasSufficientQuantity($assetId, $requiredQuantity) {
        return $this->getQuantityService()->hasSufficientQuantity($assetId, $requiredQuantity);
    }

    // =========================================================================
    // PROCUREMENT INTEGRATION - AssetProcurementService
    // =========================================================================

    /**
     * Create asset from procurement item
     *
     * @param int $procurementOrderId Procurement order ID
     * @param int $procurementItemId Procurement item ID
     * @param array $assetData Additional asset data
     * @return array Result with success status and asset ID
     */
    public function createAssetFromProcurementItem($procurementOrderId, $procurementItemId, $assetData = []) {
        return $this->getProcurementService()->createAssetFromProcurementItem($procurementOrderId, $procurementItemId, $assetData);
    }

    /**
     * Generate multiple assets from procurement item
     *
     * @param int $procurementOrderId Procurement order ID
     * @param int $procurementItemId Procurement item ID
     * @param array $assetData Common asset data for all generated assets
     * @return array Result with success status and created asset IDs
     */
    public function generateAssetsFromProcurementItem($procurementOrderId, $procurementItemId, $assetData = []) {
        return $this->getProcurementService()->generateAssetsFromProcurementItem($procurementOrderId, $procurementItemId, $assetData);
    }

    /**
     * Get all assets linked to procurement order
     *
     * @param int $procurementOrderId Procurement order ID
     * @return array Assets linked to procurement order
     */
    public function getAssetsByProcurementOrder($procurementOrderId) {
        return $this->getProcurementService()->getAssetsByProcurementOrder($procurementOrderId);
    }

    /**
     * Get procurement asset statistics
     *
     * @param int $procurementOrderId Procurement order ID
     * @return array Statistics about assets created from procurement
     */
    public function getProcurementAssetStats($procurementOrderId) {
        return $this->getProcurementService()->getProcurementAssetStats($procurementOrderId);
    }

    /**
     * Legacy method - create asset from procurement
     * Alias for createAssetFromProcurementItem
     *
     * @param array $procurementItem Procurement item data
     * @param array $assetData Additional asset data
     * @return array Result with success status
     */
    public function createAssetFromProcurement($procurementItem, $assetData = []) {
        if (isset($procurementItem['procurement_order_id']) && isset($procurementItem['id'])) {
            return $this->createAssetFromProcurementItem(
                $procurementItem['procurement_order_id'],
                $procurementItem['id'],
                $assetData
            );
        }
        return ['success' => false, 'error' => 'Invalid procurement item data'];
    }

    // =========================================================================
    // STATISTICS & REPORTING - AssetStatisticsService
    // =========================================================================

    /**
     * Get comprehensive asset statistics
     *
     * @param int|null $projectId Optional project filter
     * @return array Asset statistics
     */
    public function getAssetStatistics($projectId = null) {
        return $this->getStatisticsService()->getAssetStatistics($projectId);
    }

    /**
     * Get asset utilization metrics
     *
     * @param int|null $projectId Optional project filter
     * @return array Utilization metrics
     */
    public function getAssetUtilization($projectId = null) {
        return $this->getStatisticsService()->getAssetUtilization($projectId);
    }

    /**
     * Get asset value report
     *
     * @param int|null $projectId Optional project filter
     * @return array Value report data
     */
    public function getAssetValueReport($projectId = null) {
        return $this->getStatisticsService()->getAssetValueReport($projectId);
    }

    /**
     * Get depreciation report
     *
     * @param int|null $projectId Optional project filter
     * @return array Depreciation report data
     */
    public function getDepreciationReport($projectId = null) {
        return $this->getStatisticsService()->getDepreciationReport($projectId);
    }

    /**
     * Get maintenance schedule
     *
     * @param int|null $projectId Optional project filter
     * @return array Maintenance schedule data
     */
    public function getMaintenanceSchedule($projectId = null) {
        return $this->getStatisticsService()->getMaintenanceSchedule($projectId);
    }

    /**
     * Get basic asset statistics
     *
     * @return array Basic statistics
     */
    public function getAssetStats() {
        return $this->getStatisticsService()->getAssetStats();
    }

    /**
     * Get overdue assets (maintenance or other)
     *
     * @param string $type Type of overdue check (default: 'maintenance')
     * @return array Overdue assets
     */
    public function getOverdueAssets($type = 'maintenance') {
        return $this->getStatisticsService()->getOverdueAssets($type);
    }

    // =========================================================================
    // QUERY & SEARCH - AssetQueryService
    // =========================================================================

    /**
     * Get assets with advanced filters and pagination
     *
     * @param array $filters Filter criteria
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Paginated assets with metadata
     */
    public function getAssetsWithFilters($filters = [], $page = 1, $perPage = 20) {
        return $this->getQueryService()->getAssetsWithFilters($filters, $page, $perPage);
    }

    /**
     * Get assets by project
     *
     * @param int $projectId Project ID
     * @param string|null $status Optional status filter
     * @return array Assets in project
     */
    public function getAssetsByProject($projectId, $status = null) {
        return $this->getQueryService()->getAssetsByProject($projectId, $status);
    }

    /**
     * Get available assets (not fully consumed)
     *
     * @param int|null $projectId Optional project filter
     * @return array Available assets
     */
    public function getAvailableAssets($projectId = null) {
        return $this->getQueryService()->getAvailableAssets($projectId);
    }

    /**
     * Get assets by category
     *
     * @param int $categoryId Category ID
     * @param int|null $projectId Optional project filter
     * @return array Assets in category
     */
    public function getAssetsByCategory($categoryId, $projectId = null) {
        return $this->getQueryService()->getAssetsByCategory($categoryId, $projectId);
    }

    /**
     * Get assets by vendor
     *
     * @param int $vendorId Vendor ID
     * @param int|null $projectId Optional project filter
     * @return array Assets from vendor
     */
    public function getAssetsByVendor($vendorId, $projectId = null) {
        return $this->getQueryService()->getAssetsByVendor($vendorId, $projectId);
    }

    /**
     * Get asset history (activity logs)
     *
     * @param int $assetId Asset ID
     * @return array Asset activity history
     */
    public function getAssetHistory($assetId) {
        return $this->getActivityService()->getAssetHistory($assetId);
    }

    /**
     * Get complete activity logs with details
     *
     * @param int $assetId Asset ID
     * @param int|null $limit Optional limit
     * @return array Complete activity logs
     */
    public function getCompleteActivityLogs($assetId, $limit = null) {
        return $this->getActivityService()->getCompleteActivityLogs($assetId, $limit);
    }

    // =========================================================================
    // ACTIVITY LOGGING - AssetActivityService
    // =========================================================================

    /**
     * Get activity by user
     *
     * @param int $userId User ID
     * @param int $limit Maximum results
     * @return array User activity logs
     */
    public function getActivityByUser($userId, $limit = 50) {
        return $this->getActivityService()->getActivityByUser($userId, $limit);
    }

    /**
     * Get recent activity across all assets
     *
     * @param int $limit Maximum results
     * @param int|null $projectId Optional project filter
     * @return array Recent activity logs
     */
    public function getRecentActivity($limit = 50, $projectId = null) {
        return $this->getActivityService()->getRecentActivity($limit, $projectId);
    }

    // =========================================================================
    // EXPORT & REPORTS - AssetExportService
    // =========================================================================

    /**
     * Export assets to CSV
     *
     * @param array $filters Filter criteria
     * @return array CSV export data
     */
    public function exportAssets($filters = []) {
        return $this->getExportService()->exportAssets($filters);
    }

    /**
     * Export assets to PDF
     *
     * @param array $filters Filter criteria
     * @param string $orientation Page orientation (L or P)
     * @return mixed PDF file path or false on failure
     */
    public function exportAssetsPDF($filters = [], $orientation = 'L') {
        return $this->getExportService()->exportAssetsPDF($filters, $orientation);
    }

    /**
     * Export assets to Excel
     *
     * @param array $filters Filter criteria
     * @return mixed Excel file path or false on failure
     */
    public function exportAssetsExcel($filters = []) {
        return $this->getExportService()->exportAssetsExcel($filters);
    }

    /**
     * Generate comprehensive asset report
     *
     * @param int $assetId Asset ID
     * @param string $format Report format (pdf or excel)
     * @return array Report data or file path
     */
    public function generateAssetReport($assetId, $format = 'pdf') {
        return $this->getExportService()->generateAssetReport($assetId, $format);
    }

    /**
     * Generate barcode labels for assets
     *
     * @param array $assetIds Array of asset IDs
     * @param string $templateType Label template type
     * @param int $tagsPerPage Tags per page
     * @return mixed PDF file path or false on failure
     */
    public function generateBarcodeLabels($assetIds, $templateType = 'medium', $tagsPerPage = 12) {
        return $this->getExportService()->generateBarcodeLabels($assetIds, $templateType, $tagsPerPage);
    }

    // =========================================================================
    // LEGACY WORKFLOW METHODS (Backward Compatibility)
    // =========================================================================

    /**
     * Create legacy asset (old workflow system)
     * Maps to new createAsset method
     *
     * @deprecated Use createAsset() instead
     * @param array $data Asset data
     * @return array Result with success status
     */
    public function createLegacyAsset($data) {
        return $this->createAsset($data);
    }

    /**
     * Verify legacy asset
     * Maps to verifyAsset method
     *
     * @deprecated Use verifyAsset() instead
     * @param int $assetId Asset ID
     * @param string $notes Verification notes
     * @return array Result with success status
     */
    public function verifyLegacyAsset($assetId, $notes = '') {
        $auth = Auth::getInstance();
        $userId = $auth->getCurrentUser()['id'] ?? 0;
        return $this->verifyAsset($assetId, $userId, $notes);
    }

    /**
     * Authorize legacy asset
     * Maps to authorizeAsset method
     *
     * @deprecated Use authorizeAsset() instead
     * @param int $assetId Asset ID
     * @param string $notes Authorization notes
     * @return array Result with success status
     */
    public function authorizeLegacyAsset($assetId, $notes = '') {
        $auth = Auth::getInstance();
        $userId = $auth->getCurrentUser()['id'] ?? 0;
        return $this->authorizeAsset($assetId, $userId, $notes);
    }

    /**
     * Get assets pending verification
     * Maps to getAssetsByWorkflowStatus
     *
     * @deprecated Use getAssetsByWorkflowStatus('pending_verification') instead
     * @param int|null $projectId Optional project filter
     * @return array Assets pending verification
     */
    public function getAssetsPendingVerification($projectId = null) {
        return $this->getAssetsByWorkflowStatus('pending_verification', $projectId);
    }

    /**
     * Get assets pending authorization
     * Maps to getAssetsByWorkflowStatus
     *
     * @deprecated Use getAssetsByWorkflowStatus('pending_authorization') instead
     * @param int|null $projectId Optional project filter
     * @return array Assets pending authorization
     */
    public function getAssetsPendingAuthorization($projectId = null) {
        return $this->getAssetsByWorkflowStatus('pending_authorization', $projectId);
    }

    /**
     * Get legacy workflow statistics
     * Maps to getWorkflowStatistics
     *
     * @deprecated Use getWorkflowStatistics() instead
     * @param int|null $projectId Optional project filter
     * @return array Workflow statistics
     */
    public function getLegacyWorkflowStats($projectId = null) {
        return $this->getWorkflowStatistics($projectId);
    }

    /**
     * Batch verify assets
     *
     * @deprecated Use bulkUpdateStatus() or loop through verifyAsset()
     * @param array $assetIds Asset IDs to verify
     * @param string $notes Verification notes
     * @return array Result with success count
     */
    public function batchVerifyAssets($assetIds, $notes = '') {
        global $auth;
        $userId = $auth->getCurrentUser()['id'] ?? 0;
        $successCount = 0;
        $errors = [];

        foreach ($assetIds as $assetId) {
            $result = $this->verifyAsset($assetId, $userId, $notes);
            if ($result['success']) {
                $successCount++;
            } else {
                $errors[] = $result['error'] ?? 'Unknown error for asset ' . $assetId;
            }
        }

        return [
            'success' => $successCount > 0,
            'verified_count' => $successCount,
            'errors' => $errors
        ];
    }

    /**
     * Batch authorize assets
     *
     * @deprecated Use bulkUpdateStatus() or loop through authorizeAsset()
     * @param array $assetIds Asset IDs to authorize
     * @param string $notes Authorization notes
     * @return array Result with success count
     */
    public function batchAuthorizeAssets($assetIds, $notes = '') {
        global $auth;
        $userId = $auth->getCurrentUser()['id'] ?? 0;
        $successCount = 0;
        $errors = [];

        foreach ($assetIds as $assetId) {
            $result = $this->authorizeAsset($assetId, $userId, $notes);
            if ($result['success']) {
                $successCount++;
            } else {
                $errors[] = $result['error'] ?? 'Unknown error for asset ' . $assetId;
            }
        }

        return [
            'success' => $successCount > 0,
            'authorized_count' => $successCount,
            'errors' => $errors
        ];
    }

    // =========================================================================
    // ROLE-SPECIFIC METHODS (Backward Compatibility)
    // =========================================================================

    /**
     * Get role-specific statistics
     * Maps to workflow statistics
     *
     * @deprecated Use getWorkflowStatistics() instead
     * @param string $userRole User role
     * @param int|null $projectId Optional project filter
     * @return array Role-specific statistics
     */
    public function getRoleSpecificStatistics($userRole, $projectId = null) {
        // Return workflow statistics - the service handles role-based filtering
        return $this->getWorkflowStatistics($projectId);
    }

    // =========================================================================
    // BUSINESS TYPE & VALIDATION
    // =========================================================================

    /**
     * Get assets by business type
     *
     * @param string|null $assetType Asset type filter
     * @param int|null $projectId Project filter
     * @param array $filters Additional filters
     * @return array Assets matching business type
     */
    public function getAssetsByBusinessType($assetType = null, $projectId = null, $filters = []) {
        if ($assetType) {
            $filters['asset_type'] = $assetType;
        }
        if ($projectId) {
            $filters['project_id'] = $projectId;
        }
        return $this->getAssetsWithFilters($filters);
    }

    /**
     * Validate asset business rules
     *
     * @param array $data Asset data to validate
     * @return array Validation result
     */
    public function validateAssetBusinessRules($data) {
        global $auth;
        $userId = $auth->getCurrentUser()['id'] ?? 0;

        if (isset($data['id'])) {
            return $this->getValidationService()->validateAssetUpdate($data['id'], $data, $userId);
        } else {
            return $this->getValidationService()->validateAssetCreation($data, $userId);
        }
    }

    // =========================================================================
    // EQUIPMENT & BORROWING
    // =========================================================================

    /**
     * Get available equipment count
     *
     * @param array|null $projectFilter Project filter criteria
     * @return int Available equipment count
     */
    public function getAvailableEquipmentCount($projectFilter = null) {
        $filters = ['status' => 'active'];
        if ($projectFilter) {
            $filters['project_id'] = $projectFilter;
        }

        $result = $this->getAssetsWithFilters($filters, 1, 1);
        return $result['total'] ?? 0;
    }

    /**
     * Get assets available for borrowing
     *
     * @param int|null $projectId Optional project filter
     * @return array Assets available for borrowing
     */
    public function getAvailableForBorrowing($projectId = null) {
        return $this->getAvailableAssets($projectId);
    }

    /**
     * Get maintainable assets
     *
     * @return array Assets that can be maintained
     */
    public function getMaintenableAssets() {
        return $this->getAssetsWithFilters(['status' => 'active']);
    }

    /**
     * Check if asset can be maintained
     *
     * @param int $assetId Asset ID
     * @return bool True if asset can be maintained
     */
    public function canBeMaintained($assetId) {
        $asset = $this->getAssetWithDetails($assetId);
        return $asset && in_array($asset['status'], ['active', 'in_use']);
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

    /**
     * Get asset's project ID
     *
     * @param int $assetId Asset ID
     * @return int|null Project ID or null if not found
     */
    public function getAssetProjectId($assetId) {
        try {
            $stmt = $this->db->prepare("SELECT project_id FROM inventory_items WHERE id = ?");
            $stmt->execute([$assetId]);
            $projectId = $stmt->fetchColumn();
            return $projectId ? (int)$projectId : null;
        } catch (Exception $e) {
            error_log("Error getting asset project ID: " . $e->getMessage());
            return null;
        }
    }
}
