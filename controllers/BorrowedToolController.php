<?php
/**
 * ConstructLink™ Borrowed Tool Controller
 * Handles single-item borrowed tool operations and main listing
 * Phase 2.3 Refactoring - Split from monolithic controller
 * 
 * Responsibilities:
 * - List view with statistics (index)
 * - Single-item viewing (view)
 * - Single-item MVA workflow (verify, approve, borrow, cancel)
 * - Single-item returns and extensions
 * - AJAX endpoints and utility functions
 * 
 * Related Controllers:
 * - BorrowedToolBatchController: Batch operations
 * - BorrowedToolPrintController: Print functionality
 */

require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';
require_once APP_ROOT . '/helpers/BorrowedTools/PermissionGuard.php';
require_once APP_ROOT . '/helpers/BorrowedTools/ResponseHelper.php';

class BorrowedToolController {
    private $permissionGuard;
    private $borrowedToolModel;
    private $assetModel;
    
    public function __construct() {
        $this->permissionGuard = new BorrowedToolsPermissionGuard();

        // Ensure user is authenticated
        if (!$this->permissionGuard->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }

        require_once APP_ROOT . '/models/BorrowedToolModel.php';
        require_once APP_ROOT . '/models/AssetModel.php';

        $this->borrowedToolModel = new BorrowedToolModel();
        $this->assetModel = new AssetModel();
    }

    /**
     * Template method for single-item MVA workflow actions
     * Consolidates duplicate code across single-item MVA methods
     */
    private function handleMVAWorkflowAction($config) {
        $this->permissionGuard->requireProjectAssignment();

        $borrowId = $_GET['id'] ?? 0;
        if (!$borrowId) {
            BorrowedToolsResponseHelper::renderError(404);
            return;
        }

        $errors = [];

        try {
            $projectFilter = $this->permissionGuard->getProjectFilter();
            $borrowedTool = $this->borrowedToolModel->getBorrowedToolWithMVADetails($borrowId, $projectFilter);
            
            if (!$borrowedTool) {
                BorrowedToolsResponseHelper::renderError(404);
                return;
            }

            // Check permission
            if (!$this->permissionGuard->hasPermission($config['permission'], $borrowedTool)) {
                BorrowedToolsResponseHelper::renderError(403);
                return;
            }

            // Process POST request
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();

                $notes = Validator::sanitize($_POST[$config['notesField']] ?? '');
                $userId = $this->permissionGuard->getCurrentUser()['id'];

                $result = $this->borrowedToolModel->{$config['modelMethod']}($borrowId, $userId, $notes);

                if ($result['success']) {
                    BorrowedToolsResponseHelper::redirectWithSuccess(
                        $config['successMessage'],
                        $config['successRoute'] . $borrowId
                    );
                } else {
                    $errors[] = $result['message'];
                }
            }

            // Render view
            $pageTitle = $config['pageTitle'];
            $pageHeader = $config['pageHeader'] . htmlspecialchars($borrowedTool['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
                ['title' => $config['breadcrumbTitle'], 'url' => '?route=borrowed-tools/' . $config['action'] . '&id=' . $borrowId]
            ];

            include APP_ROOT . $config['viewFile'];

        } catch (Exception $e) {
            error_log("Borrowed tool {$config['action']} error: " . $e->getMessage());
            BorrowedToolsResponseHelper::renderError(500, "Failed to process borrowed tool {$config['action']}");
        }
    }

    /**
     * Display borrowed tools listing with filters, pagination, and statistics
     */
    public function index() {
        // Check view permission
        if (!$this->permissionGuard->hasPermission('view')) {
            BorrowedToolsResponseHelper::renderError(403);
            return;
        }
        
        $this->permissionGuard->requireProjectAssignment();
        $currentUser = $this->permissionGuard->getCurrentUser();

        $page = (int)($_GET['page'] ?? 1);

        // Allow users to select records per page (5, 10, 25, 50, 100)
        $perPage = (int)($_GET['per_page'] ?? PAGINATION_PER_PAGE_BORROWED_TOOLS);
        $allowedPerPage = [5, 10, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = PAGINATION_PER_PAGE_BORROWED_TOOLS;
        }
        
        // Build filters
        $filters = $this->buildFilters();
        
        // Apply project filtering
        $projectFilter = $this->permissionGuard->getProjectFilter();
        if ($projectFilter) {
            $filters['project_id'] = $projectFilter;
        }
        
        try {
            // Get borrowed tools with pagination
            $result = $this->borrowedToolModel->getBorrowedToolsWithFilters($filters, $page, $perPage);
            $borrowedTools = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];

            // Get statistics
            $borrowedToolStats = $this->getStatistics($projectFilter);

            // Get overdue tools
            $overdueTools = $this->borrowedToolModel->getOverdueBorrowedTools($projectFilter);

            $pageTitle = 'Borrowed Tools - ConstructLink™';
            $pageHeader = 'Borrowed Tools Management';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools']
            ];

            // Pass data to view
            $auth = $this->permissionGuard; // For permission checks in view
            $currentSort = $filters['sort_by'];
            $currentOrder = $filters['sort_order'];

            include APP_ROOT . '/views/borrowed-tools/index.php';
            
        } catch (Exception $e) {
            error_log("Borrowed tools listing error: " . $e->getMessage());
            BorrowedToolsResponseHelper::renderError(500, 'Failed to load borrowed tools');
        }
    }

    /**
     * Build filters from request parameters
     * Helper method to keep index() under 50 lines
     */
    private function buildFilters() {
        $filters = [];

        // Apply default "Borrowed" status when no filters are active
        // This matches the UX expectation: show active borrowings by default
        // IMPORTANT: Use isset() for status to detect explicit "All Statuses" selection (empty string)
        $hasAnyFilter = isset($_GET['status']) || !empty($_GET['priority']) ||
                       !empty($_GET['search']) || !empty($_GET['date_from']) ||
                       !empty($_GET['date_to']) || !empty($_GET['project']);

        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        } elseif (!$hasAnyFilter) {
            // No filters provided - apply default "Borrowed" status
            $filters['status'] = 'Borrowed';
        }
        // If status is explicitly set to empty (All Statuses), don't set filters['status']

        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
        if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
        if (!empty($_GET['priority'])) $filters['priority'] = $_GET['priority'];

        // Sorting parameters
        $sortBy = $_GET['sort'] ?? 'date';
        $sortOrder = $_GET['order'] ?? 'desc';

        // Validate sort column
        $allowedSortColumns = ['id', 'reference', 'borrower', 'status', 'date', 'items'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'date';
        }

        // Validate sort order
        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $filters['sort_by'] = $sortBy;
        $filters['sort_order'] = $sortOrder;

        return $filters;
    }

    /**
     * Get statistics for dashboard
     * Helper method extracted from index()
     */
    private function getStatistics($projectFilter = null) {
        require_once APP_ROOT . '/models/BorrowedToolBatchModel.php';
        
        $batchModel = new BorrowedToolBatchModel();
        $batchStats = $batchModel->getBatchStats(null, null, $projectFilter);

        // Get available equipment count
        $availableEquipmentCount = $this->assetModel->getAvailableEquipmentCount($projectFilter);

        // Get time-based statistics
        $timeStats = $batchModel->getTimeBasedStatistics($projectFilter);

        // Get overdue count
        $overdueCount = $batchModel->getOverdueBatchCount($projectFilter);

        return [
            'pending_verification' => $batchStats['pending_verification'] ?? 0,
            'pending_approval' => $batchStats['pending_approval'] ?? 0,
            'available_equipment' => $availableEquipmentCount ?? 0,
            'borrowed' => $batchStats['released'] ?? 0,
            'partially_returned' => $batchStats['partially_returned'] ?? 0,
            'returned' => $batchStats['returned'] ?? 0,
            'canceled' => $batchStats['canceled'] ?? 0,
            'total_borrowings' => $batchStats['total_batches'] ?? 0,
            'overdue' => $overdueCount,
            'borrowed_today' => $timeStats['borrowed_today'] ?? 0,
            'returned_today' => $timeStats['returned_today'] ?? 0,
            'due_today' => $timeStats['due_today'] ?? 0,
            'due_this_week' => $timeStats['due_this_week'] ?? 0,
            'activity_this_week' => $timeStats['activity_this_week'] ?? 0,
            'borrowed_this_month' => $timeStats['borrowed_this_month'] ?? 0,
            'returned_this_month' => $timeStats['returned_this_month'] ?? 0,
        ];
    }

    /**
     * View single borrowed tool or batch details
     */
    public function view() {
        $id = $_GET['id'] ?? 0;

        if (!$id) {
            BorrowedToolsResponseHelper::renderError(404);
            return;
        }

        $this->permissionGuard->requireProjectAssignment();

        try {
            require_once APP_ROOT . '/models/BorrowedToolBatchModel.php';
            $batchModel = new BorrowedToolBatchModel();
            
            $projectFilter = $this->permissionGuard->getProjectFilter();
            $batch = $batchModel->getBatchWithItems($id, $projectFilter);

            if ($batch) {
                $auth = $this->permissionGuard;
                include APP_ROOT . '/views/borrowed-tools/view.php';
                return;
            }

            // Try as single borrowed tool
            $borrowedTool = $this->borrowedToolModel->getBorrowedToolWithMVADetails($id, $projectFilter);

            if (!$borrowedTool) {
                BorrowedToolsResponseHelper::renderError(404);
                return;
            }

            // If item has batch_id, load full batch
            if (!empty($borrowedTool['batch_id'])) {
                $batch = $batchModel->getBatchWithItems($borrowedTool['batch_id'], $projectFilter);
                if ($batch) {
                    $auth = $this->permissionGuard;
                    include APP_ROOT . '/views/borrowed-tools/view.php';
                    return;
                }
            }

            // Convert single item to batch format for unified view
            $batch = $this->convertSingleItemToBatchFormat($borrowedTool);
            $auth = $this->permissionGuard;
            
            include APP_ROOT . '/views/borrowed-tools/view.php';

        } catch (Exception $e) {
            error_log("View request error: " . $e->getMessage());
            BorrowedToolsResponseHelper::renderError(500, 'Failed to load request details');
        }
    }

    /**
     * Convert single borrowed tool to batch format
     * Helper method to keep view() under 50 lines
     */
    private function convertSingleItemToBatchFormat($borrowedTool) {
        return [
            'id' => $borrowedTool['batch_id'] ?? $borrowedTool['id'],
            'batch_reference' => $borrowedTool['batch_reference'] ?? 
                ($borrowedTool['id'] ? "BT-" . str_pad($borrowedTool['id'], 6, '0', STR_PAD_LEFT) : 'N/A'),
            'borrower_name' => $borrowedTool['borrower_name'] ?? 'N/A',
            'borrower_contact' => $borrowedTool['borrower_contact'] ?? '',
            'expected_return' => $borrowedTool['expected_return'] ?? date('Y-m-d'),
            'actual_return' => $borrowedTool['actual_return'] ?? null,
            'status' => $borrowedTool['status'] ?? 'Unknown',
            'is_critical_batch' => ($borrowedTool['acquisition_cost'] ?? 0) > config('business_rules.critical_tool_threshold'),
            'purpose' => $borrowedTool['purpose'] ?? '',
            'created_at' => $borrowedTool['created_at'] ?? date('Y-m-d H:i:s'),
            'issued_by_name' => $borrowedTool['issued_by_name'] ?? 'Unknown',
            'verified_by' => $borrowedTool['verified_by'] ?? null,
            'verified_by_name' => $borrowedTool['verified_by_name'] ?? null,
            'verification_date' => $borrowedTool['verification_date'] ?? null,
            'verification_notes' => $borrowedTool['verification_notes'] ?? null,
            'approved_by' => $borrowedTool['approved_by'] ?? null,
            'approved_by_name' => $borrowedTool['approved_by_name'] ?? null,
            'approval_date' => $borrowedTool['approval_date'] ?? null,
            'approval_notes' => $borrowedTool['approval_notes'] ?? null,
            'released_by' => $borrowedTool['borrowed_by'] ?? null,
            'released_by_name' => $borrowedTool['borrowed_by_name'] ?? null,
            'release_date' => $borrowedTool['borrowed_date'] ?? null,
            'release_notes' => null,
            'returned_by' => $borrowedTool['returned_by'] ?? null,
            'returned_by_name' => $borrowedTool['returned_by_name'] ?? null,
            'return_date' => $borrowedTool['return_date'] ?? null,
            'return_notes' => null,
            'canceled_by' => $borrowedTool['canceled_by'] ?? null,
            'canceled_by_name' => $borrowedTool['canceled_by_name'] ?? null,
            'cancellation_date' => $borrowedTool['cancellation_date'] ?? null,
            'cancellation_reason' => $borrowedTool['cancellation_reason'] ?? null,
            'total_items' => 1,
            'total_quantity' => $borrowedTool['quantity'] ?? 1,
            'items' => [[
                'id' => $borrowedTool['id'],
                'asset_id' => $borrowedTool['asset_id'],
                'asset_name' => $borrowedTool['asset_name'] ?? 'Unknown',
                'asset_ref' => $borrowedTool['asset_ref'] ?? 'N/A',
                'category_name' => $borrowedTool['category_name'] ?? 'N/A',
                'acquisition_cost' => $borrowedTool['acquisition_cost'] ?? 0,
                'quantity' => $borrowedTool['quantity'] ?? 1,
                'quantity_returned' => 0,
                'status' => $borrowedTool['status'] ?? 'Unknown'
            ]]
        ];
    }

    /**
     * Display create form (references batch creation)
     */
    public function create() {
        // Redirect to batch creation
        header('Location: ?route=borrowed-tools/batch/create');
        exit;
    }

    /**
     * Verify single borrowed tool (MVA Verifier step)
     */
    public function verify() {
        $this->handleMVAWorkflowAction([
            'action' => 'verify',
            'permission' => 'verify',
            'modelMethod' => 'verifyBorrowedTool',
            'notesField' => 'verification_notes',
            'pageTitle' => 'Verify Borrowed Tool - ConstructLink™',
            'pageHeader' => 'Verify Borrowed Tool: ',
            'breadcrumbTitle' => 'Verify',
            'viewFile' => '/views/borrowed-tools/verify.php',
            'successRoute' => 'borrowed-tools/view&id=',
            'successMessage' => 'Tool verified successfully'
        ]);
    }

    /**
     * Approve single borrowed tool (MVA Authorizer step)
     */
    public function approve() {
        $this->handleMVAWorkflowAction([
            'action' => 'approve',
            'permission' => 'approve',
            'modelMethod' => 'approveBorrowedTool',
            'notesField' => 'approval_notes',
            'pageTitle' => 'Approve Borrowed Tool - ConstructLink™',
            'pageHeader' => 'Approve Borrowed Tool: ',
            'breadcrumbTitle' => 'Approve',
            'viewFile' => '/views/borrowed-tools/approve.php',
            'successRoute' => 'borrowed-tools/view&id=',
            'successMessage' => 'Tool approved successfully'
        ]);
    }

    /**
     * Cancel single borrowed tool request
     */
    public function cancel() {
        $this->handleMVAWorkflowAction([
            'action' => 'cancel',
            'permission' => 'cancel',
            'modelMethod' => 'cancelBorrowedTool',
            'notesField' => 'cancellation_reason',
            'pageTitle' => 'Cancel Borrowed Tool - ConstructLink™',
            'pageHeader' => 'Cancel Borrowed Tool: ',
            'breadcrumbTitle' => 'Cancel',
            'viewFile' => '/views/borrowed-tools/cancel.php',
            'successRoute' => 'borrowed-tools/view&id=',
            'successMessage' => 'Tool canceled successfully'
        ]);
    }

    /**
     * Return single borrowed tool
     */
    public function returnTool() {
        if (!$this->permissionGuard->hasPermission('return')) {
            BorrowedToolsResponseHelper::renderError(403);
            return;
        }
        
        $this->permissionGuard->requireProjectAssignment();
        
        $borrowId = $_GET['id'] ?? $_POST['borrow_id'] ?? 0;
        
        if (!$borrowId) {
            BorrowedToolsResponseHelper::renderError(404);
            return;
        }
        
        $errors = [];
        
        try {
            $projectFilter = $this->permissionGuard->getProjectFilter();
            $borrowedTool = $this->borrowedToolModel->getBorrowedToolWithDetails($borrowId, $projectFilter);
            
            if (!$borrowedTool) {
                BorrowedToolsResponseHelper::renderError(404);
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $conditionIn = Validator::sanitize($_POST['condition_in'] ?? '');
                $returnNotes = Validator::sanitize($_POST['return_notes'] ?? '');
                $returnedBy = $this->permissionGuard->getCurrentUser()['id'];
                
                $result = $this->borrowedToolModel->returnBorrowedTool($borrowId, $returnedBy, $conditionIn, $returnNotes);
                
                if ($result['success']) {
                    BorrowedToolsResponseHelper::redirectWithSuccess(
                        'Tool returned successfully',
                        'borrowed-tools/view&id=' . $borrowId
                    );
                } else {
                    $errors[] = $result['message'];
                }
            }
            
            $pageTitle = 'Return Tool - ConstructLink™';
            $pageHeader = 'Return Tool: ' . htmlspecialchars($borrowedTool['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
                ['title' => 'Return Tool', 'url' => '?route=borrowed-tools/return&id=' . $borrowId]
            ];
            
            include APP_ROOT . '/views/borrowed-tools/return.php';
            
        } catch (Exception $e) {
            error_log("Tool return error: " . $e->getMessage());
            BorrowedToolsResponseHelper::renderError(500, 'Failed to process tool return');
        }
    }

    /**
     * Extend borrowing period for single item
     */
    public function extend() {
        if (!$this->permissionGuard->hasPermission('extend')) {
            BorrowedToolsResponseHelper::renderError(403);
            return;
        }
        
        $this->permissionGuard->requireProjectAssignment();
        
        $borrowId = $_GET['id'] ?? 0;
        
        if (!$borrowId) {
            BorrowedToolsResponseHelper::renderError(404);
            return;
        }
        
        $errors = [];
        
        try {
            $projectFilter = $this->permissionGuard->getProjectFilter();
            $borrowedTool = $this->borrowedToolModel->getBorrowedToolWithDetails($borrowId, $projectFilter);
            
            if (!$borrowedTool) {
                BorrowedToolsResponseHelper::renderError(404);
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $newReturnDate = $_POST['new_return_date'] ?? '';
                $extensionReason = Validator::sanitize($_POST['extension_reason'] ?? '');
                $requestedBy = $this->permissionGuard->getCurrentUser()['id'];
                
                if (empty($newReturnDate)) {
                    $errors[] = 'New return date is required';
                } elseif (strtotime($newReturnDate) <= strtotime($borrowedTool['expected_return'])) {
                    $errors[] = 'New return date must be after current expected return date';
                } else {
                    $result = $this->borrowedToolModel->extendBorrowingPeriod(
                        $borrowId,
                        $newReturnDate,
                        $extensionReason,
                        $requestedBy
                    );
                    
                    if ($result['success']) {
                        BorrowedToolsResponseHelper::redirectWithSuccess(
                            'Extension request submitted successfully',
                            'borrowed-tools/view&id=' . $borrowId
                        );
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Extend Borrowing Period - ConstructLink™';
            $pageHeader = 'Extend Borrowing: ' . htmlspecialchars($borrowedTool['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
                ['title' => 'Extend', 'url' => '?route=borrowed-tools/extend&id=' . $borrowId]
            ];
            
            include APP_ROOT . '/views/borrowed-tools/extend.php';
            
        } catch (Exception $e) {
            error_log("Extension request error: " . $e->getMessage());
            BorrowedToolsResponseHelper::renderError(500, 'Failed to process extension request');
        }
    }

    /**
     * Validate QR code for borrowing (AJAX endpoint)
     */
    public function validateQRForBorrowing() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['valid' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $qrData = $_GET['data'] ?? '';
        
        if (empty($qrData)) {
            http_response_code(400);
            echo json_encode(['valid' => false, 'message' => 'QR data is required']);
            return;
        }
        
        try {
            // Validate QR code using SecureLink
            $secureLink = SecureLink::getInstance();
            $qrResult = $secureLink->validateQR($qrData);
            
            if (!$qrResult['valid']) {
                echo json_encode([
                    'valid' => false,
                    'message' => 'Invalid QR code',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                return;
            }
            
            // Get asset information
            $asset = $this->assetModel->getAssetWithDetails($qrResult['asset_id']);
            
            if (!$asset) {
                echo json_encode([
                    'valid' => false,
                    'message' => 'Asset not found',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                return;
            }
            
            // Check if asset is available
            $projectFilter = $this->permissionGuard->getProjectFilter();
            $currentUser = $this->permissionGuard->getCurrentUser();
            $availableAssets = $this->assetModel->getAvailableForBorrowing($currentUser['current_project_id'] ?? null);
            
            $isAvailable = false;
            foreach ($availableAssets as $availableAsset) {
                if ($availableAsset['id'] == $asset['id']) {
                    $isAvailable = true;
                    break;
                }
            }
            
            if (!$isAvailable) {
                echo json_encode([
                    'valid' => false,
                    'message' => 'Asset is not available for borrowing',
                    'asset' => [
                        'id' => $asset['id'],
                        'name' => $asset['name'],
                        'ref' => $asset['ref'],
                        'status' => $asset['status']
                    ],
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                return;
            }
            
            // Asset is valid and available
            echo json_encode([
                'valid' => true,
                'asset' => [
                    'id' => $asset['id'],
                    'name' => $asset['name'],
                    'ref' => $asset['ref'],
                    'category' => $asset['category_name'] ?? 'N/A',
                    'acquisition_cost' => $asset['acquisition_cost'] ?? 0,
                    'model' => $asset['model'] ?? '',
                    'serial_number' => $asset['serial_number'] ?? ''
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("QR validation error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'valid' => false,
                'message' => 'Failed to validate QR code',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Get statistics (AJAX endpoint)
     */
    public function getStats() {
        header('Content-Type: application/json');
        
        try {
            $projectFilter = $this->permissionGuard->getProjectFilter();
            $stats = $this->getStatistics($projectFilter);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            error_log("Get stats error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to retrieve statistics'
            ]);
        }
    }

    /**
     * Export borrowed tools data
     */
    public function export() {
        // Check permission
        if (!$this->permissionGuard->hasPermission('view')) {
            BorrowedToolsResponseHelper::renderError(403);
            return;
        }
        
        try {
            $projectFilter = $this->permissionGuard->getProjectFilter();
            $format = $_GET['format'] ?? 'csv';
            
            // Get all borrowed tools (no pagination)
            $filters = ['project_id' => $projectFilter];
            $result = $this->borrowedToolModel->getBorrowedToolsWithFilters($filters, 1, 10000);
            $borrowedTools = $result['data'] ?? [];
            
            if ($format === 'csv') {
                $this->exportCSV($borrowedTools);
            } else {
                BorrowedToolsResponseHelper::renderError(400, 'Unsupported export format');
            }
            
        } catch (Exception $e) {
            error_log("Export error: " . $e->getMessage());
            BorrowedToolsResponseHelper::renderError(500, 'Failed to export data');
        }
    }

    /**
     * Export data as CSV
     * Helper method for export()
     */
    private function exportCSV($data) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="borrowed_tools_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'Reference',
            'Borrower',
            'Status',
            'Borrowed Date',
            'Expected Return',
            'Items Count',
            'Total Quantity'
        ]);
        
        // CSV data
        foreach ($data as $row) {
            fputcsv($output, [
                $row['batch_reference'] ?? 'N/A',
                $row['borrower_name'] ?? 'N/A',
                $row['status'] ?? 'Unknown',
                $row['created_at'] ?? '',
                $row['expected_return'] ?? '',
                $row['total_items'] ?? 0,
                $row['total_quantity'] ?? 0
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Mark overdue items (background task)
     */
    public function markOverdue() {
        // This should only be called by cron/scheduled tasks
        // Add IP whitelist or authentication check here
        
        try {
            $result = $this->borrowedToolModel->markOverdueItems();
            
            echo json_encode([
                'success' => true,
                'marked_overdue' => $result['count'] ?? 0
            ]);
            
        } catch (Exception $e) {
            error_log("Mark overdue error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to mark overdue items'
            ]);
        }
    }

    /**
     * Update overdue status (background task)
     */
    public function updateOverdueStatus() {
        // This should only be called by cron/scheduled tasks
        
        try {
            require_once APP_ROOT . '/models/BorrowedToolBatchModel.php';
            $batchModel = new BorrowedToolBatchModel();
            
            $result = $batchModel->updateOverdueStatuses();
            
            echo json_encode([
                'success' => true,
                'updated' => $result['count'] ?? 0
            ]);
            
        } catch (Exception $e) {
            error_log("Update overdue status error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update overdue statuses'
            ]);
        }
    }

    /**
     * Get list of overdue contacts for notifications
     */
    public function getOverdueContacts() {
        // Check permission
        if (!$this->permissionGuard->hasPermission('view')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        header('Content-Type: application/json');
        
        try {
            $projectFilter = $this->permissionGuard->getProjectFilter();
            $overdueTools = $this->borrowedToolModel->getOverdueBorrowedTools($projectFilter);
            
            $contacts = [];
            foreach ($overdueTools as $tool) {
                if (!empty($tool['borrower_contact'])) {
                    $contacts[] = [
                        'name' => $tool['borrower_name'],
                        'contact' => $tool['borrower_contact'],
                        'reference' => $tool['batch_reference'],
                        'days_overdue' => $tool['days_overdue'] ?? 0
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'contacts' => $contacts,
                'count' => count($contacts)
            ]);
            
        } catch (Exception $e) {
            error_log("Get overdue contacts error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to retrieve overdue contacts'
            ]);
        }
    }
}
