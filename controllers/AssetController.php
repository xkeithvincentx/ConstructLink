<?php
/**
 * ConstructLink™ Inventory Controller (AssetController)
 *
 * IMPORTANT DATABASE MAPPING:
 * - Frontend: "Inventory Management" / "Inventory Items"
 * - Backend: AssetController.php / AssetModel.php
 * - Database: `assets` table (contains both Capital Assets and Consumable Inventory)
 * - Routes: ?route=assets (kept for backward compatibility)
 *
 * TERMINOLOGY GUIDE:
 * - User sees: "Inventory", "Inventory Items", "Inventory Management"
 * - Code uses: Asset* classes (AssetController, AssetModel, assets table)
 * - Why: Database uses "assets" table, but frontend displays "Inventory" to avoid confusion
 *
 * INVENTORY TYPES:
 * 1. Capital Assets (Depreciable Equipment)
 *    - Tracked by status: available, in_use, borrowed, under_maintenance
 *    - Categories where asset_type = 'capital' OR is_consumable = 0
 *    - Examples: Equipment, Tools, Vehicles
 *
 * 2. Consumable Inventory (Materials/Supplies)
 *    - Tracked by quantity: available_quantity, quantity
 *    - Categories where asset_type = 'inventory' OR is_consumable = 1
 *    - Examples: Electrical Supplies, Construction Materials
 *
 * RELATED FILES:
 * - Model: models/AssetModel.php (database operations on `assets` table)
 * - Views: views/assets/*.php (displays "Inventory" to users)
 * - Database: `assets` table, `categories` table (has is_consumable and asset_type fields)
 */

// Middleware Dependencies
require_once APP_ROOT . '/middleware/PermissionMiddleware.php';

// Utility Dependencies
require_once APP_ROOT . '/utils/FormDataProvider.php';

// Phase 2 Refactoring - Service Layer Dependencies
require_once APP_ROOT . '/services/Asset/AssetPermissionService.php';
require_once APP_ROOT . '/services/Asset/AssetStatisticsService.php';

// Phase 3 Refactoring - Additional Service Layer Dependencies
require_once APP_ROOT . '/services/Asset/AssetLocationService.php';
require_once APP_ROOT . '/services/Asset/AssetQueryService.php';
require_once APP_ROOT . '/services/Asset/AssetProcurementService.php';
require_once APP_ROOT . '/services/Asset/AssetWorkflowService.php';

class AssetController {
    private $auth;
    private $assetModel;
    private $db;

    // Phase 3: Service instances
    private $locationService;
    private $queryService;
    private $procurementService;
    private $workflowService;

    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->assetModel = new AssetModel();

        // Initialize database connection using Database class
        $this->db = Database::getInstance()->getConnection();

        // Initialize Phase 3 services
        $this->locationService = new AssetLocationService($this->db, $this->assetModel);
        $this->queryService = new AssetQueryService($this->db);
        $this->procurementService = new AssetProcurementService($this->db, $this->assetModel);
        $this->workflowService = new AssetWorkflowService($this->db, $this->assetModel, $this->auth);

        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }
    
    /**
     * Display asset listing with project-level filtering
     */
    public function index() {
        // Check permissions - role-based access control
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        PermissionMiddleware::requirePermission('assets.index');
        
        $page = (int)($_GET['page'] ?? 1);

        // Allow users to select records per page (5, 10, 25, 50, 100)
        $perPage = (int)($_GET['per_page'] ?? 5); // Default to 5 records
        $allowedPerPage = [5, 10, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 5; // Fallback to default if invalid value
        }
        
        // Build filters from query parameters
        $filters = [];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        if (!empty($_GET['category_id'])) $filters['category_id'] = $_GET['category_id'];
        if (!empty($_GET['project_id'])) $filters['project_id'] = $_GET['project_id'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        if (!empty($_GET['brand_id'])) $filters['brand_id'] = $_GET['brand_id'];
        if (!empty($_GET['vendor_id'])) $filters['vendor_id'] = $_GET['vendor_id'];
        if (!empty($_GET['asset_type'])) $filters['asset_type'] = $_GET['asset_type'];
        if (isset($_GET['is_client_supplied'])) $filters['is_client_supplied'] = $_GET['is_client_supplied'];
        if (!empty($_GET['workflow_status'])) $filters['workflow_status'] = $_GET['workflow_status'];
        
        try {
            // Get assets with pagination and project scoping
            $result = $this->assetModel->getAssetsWithFilters($filters, $page, $perPage);
            $assets = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];

            // Enhance asset data with consumable info and units
            $assets = $this->queryService->enhanceAssetData($assets);
            
            // Get role-specific asset statistics for dashboard cards (from database)
            $currentProjectId = $currentUser['current_project_id'] ?? null;
            $roleStats = $this->assetModel->getRoleSpecificStatistics($userRole, $currentProjectId);

            // Get asset statistics for dashboard cards (fallback/legacy)
            $assetStats = $this->assetModel->getAssetStatistics();

            // Get MVA workflow statistics
            $workflowStats = $this->assetModel->getWorkflowStatistics();

            // Get overdue assets for alerts
            $overdueAssets = $this->assetModel->getOverdueAssets('withdrawal');

            // Get filter options using FormDataProvider
            $formProvider = new FormDataProvider();
            $filterOptions = $formProvider->getAssetFilterOptions();
            extract($filterOptions); // Extracts: categories, projects, vendors, brands
            
            $pageTitle = 'Inventory - ConstructLink™';
            $pageHeader = 'Inventory Management';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Inventory', 'url' => '?route=assets']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/assets/index.php';
            
        } catch (Exception $e) {
            ControllerErrorHandler::handleException($e, 'load assets');
        }
    }
    
    /**
     * Display asset details with comprehensive information
     */
    public function view() {
        $assetId = $_GET['id'] ?? 0;
        
        if (!$assetId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $asset = $this->assetModel->getAssetWithDetails($assetId);
            
            if (!$asset) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Get comprehensive asset history
            $history = $this->assetModel->getAssetHistory($assetId);
            
            // Get related records with error handling
            $withdrawals = [];
            $transfers = [];
            $maintenance = [];
            $incidents = [];
            $borrowHistory = [];
            
            try {
                $withdrawalModel = new WithdrawalModel();
                $withdrawals = $withdrawalModel->getAssetWithdrawalHistory($assetId);
            } catch (Exception $e) {
                error_log("Withdrawal history error for asset $assetId: " . $e->getMessage());
                $withdrawals = [];
            }
            
            try {
                $transferModel = new TransferModel();
                $transfers = $transferModel->getAssetTransferHistory($assetId);
            } catch (Exception $e) {
                error_log("Transfer history error for asset $assetId: " . $e->getMessage());
                $transfers = [];
            }
            
            try {
                $maintenanceModel = new MaintenanceModel();
                $maintenance = $maintenanceModel->getAssetMaintenanceHistory($assetId);
            } catch (Exception $e) {
                error_log("Maintenance history error for asset $assetId: " . $e->getMessage());
                $maintenance = [];
            }
            
            try {
                $incidentModel = new IncidentModel();
                $incidents = $incidentModel->getAssetIncidentHistory($assetId);
            } catch (Exception $e) {
                error_log("Incident history error for asset $assetId: " . $e->getMessage());
                $incidents = [];
            }
            
            try {
                $borrowedToolModel = new BorrowedToolModel();
                $borrowHistory = $borrowedToolModel->getAssetBorrowingHistory($assetId);
            } catch (Exception $e) {
                error_log("Borrow history error for asset $assetId: " . $e->getMessage());
                $borrowHistory = [];
            }

            // Get complete activity logs
            $completeLogs = [];
            try {
                $completeLogs = $this->assetModel->getCompleteActivityLogs($assetId);
            } catch (Exception $e) {
                error_log("Complete logs error for asset $assetId: " . $e->getMessage());
                $completeLogs = [];
            }

            // Detect asset category type (consumable vs non-consumable)
            $isConsumable = false;
            $isNonConsumable = false;
            try {
                $categoryModel = new CategoryModel();
                $category = $categoryModel->find($asset['category_id']);
                if ($category) {
                    $isConsumable = (isset($category['is_consumable']) && $category['is_consumable'] == 1);
                    $isNonConsumable = !$isConsumable;
                }
            } catch (Exception $e) {
                error_log("Category detection error for asset $assetId: " . $e->getMessage());
                // Default to non-consumable if error occurs
                $isNonConsumable = true;
            }

            $pageTitle = 'Inventory Details - ' . $asset['name'];
            $pageHeader = 'Item: ' . $asset['ref'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Inventory', 'url' => '?route=assets'],
                ['title' => 'View Details', 'url' => '?route=assets/view&id=' . $assetId]
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            $user = $this->auth->getCurrentUser();
            
            include APP_ROOT . '/views/assets/view.php';
            
        } catch (Exception $e) {
            ControllerErrorHandler::handleException($e, 'load asset details');
        }
    }
    
    /**
     * Display create asset form
     */
    public function create() {
        // Check permissions using config
        PermissionMiddleware::requirePermission('assets/create');
        
        $errors = [];
        $messages = [];
        $formData = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            // Sanitize and prepare form data
            $formData = [
                'ref' => Validator::sanitize($_POST['ref'] ?? ''),
                'name' => Validator::sanitize($_POST['name'] ?? ''),
                'description' => Validator::sanitize($_POST['description'] ?? ''),
                'category_id' => (int)($_POST['category_id'] ?? 0),
                'project_id' => (int)($_POST['project_id'] ?? 0),
                'maker_id' => !empty($_POST['maker_id']) ? (int)$_POST['maker_id'] : null,
                'vendor_id' => !empty($_POST['vendor_id']) ? (int)$_POST['vendor_id'] : null,
                'client_id' => !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null,
                'model' => Validator::sanitize($_POST['model'] ?? ''),
                'serial_number' => Validator::sanitize($_POST['serial_number'] ?? ''),
                'specifications' => Validator::sanitize($_POST['specifications'] ?? ''),
                'acquired_date' => $_POST['acquired_date'] ?? '',
                'acquisition_cost' => !empty($_POST['acquisition_cost']) ? (float)$_POST['acquisition_cost'] : null,
                'unit_cost' => !empty($_POST['unit_cost']) ? (float)$_POST['unit_cost'] : null,
                'quantity' => !empty($_POST['quantity']) ? (int)$_POST['quantity'] : 1,
                'is_client_supplied' => isset($_POST['is_client_supplied']),
                'warranty_expiry' => !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,
                'location' => Validator::sanitize($_POST['location'] ?? ''),
                'condition_notes' => Validator::sanitize($_POST['condition_notes'] ?? ''),
                // Note: standardized_name should be for asset name standardization, not brand names
                'brand_id' => !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null,
                'primary_discipline' => !empty($_POST['primary_discipline']) ? (int)$_POST['primary_discipline'] : null,
                'disciplines' => $_POST['disciplines'] ?? [],
                'equipment_type_id' => !empty($_POST['equipment_type_id']) ? (int)$_POST['equipment_type_id'] : null,
                'subtype_id' => !empty($_POST['subtype_id']) ? (int)$_POST['subtype_id'] : null,
                'specifications_data' => $_POST['specifications'] ?? []
            ];
            
            // Handle procurement source (supports both legacy and multi-item)
            if (!empty($_POST['procurement_source'])) {
                $procurementSource = $_POST['procurement_source'];
                $parts = explode(':', $procurementSource);
                
                if (count($parts) === 2) {
                    $type = $parts[0];
                    $id = (int)$parts[1];
                    
                    if ($type === 'legacy') {
                        // For legacy procurement, we'll store it as procurement_order_id for consistency
                        $formData['procurement_order_id'] = $id;
                    } elseif ($type === 'multi_item') {
                        $formData['procurement_order_id'] = $id;
                        
                        // Handle specific procurement item
                        if (!empty($_POST['procurement_item_id'])) {
                            $formData['procurement_item_id'] = (int)$_POST['procurement_item_id'];
                        }
                    }
                }
            }
            
            // Create asset using model method
            $result = $this->assetModel->createAsset($formData);
            
            if ($result['success']) {
                $assetId = $result['asset']['id'];
                header('Location: ?route=assets/view&id=' . $assetId . '&message=asset_created');
                exit;
            } else {
                if (isset($result['errors'])) {
                    $errors = $result['errors'];
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
        
        // Get form options
        try {
            // Load all form options using FormDataProvider
            $formProvider = new FormDataProvider();
            $formOptions = $formProvider->getAssetFormOptions();
            extract($formOptions); // Extracts: categories, projects, makers, vendors, clients, brands

            // Get procurement sources separately (not in FormDataProvider)
            $procurements = $this->procurementService->getCombinedProcurementSources();
            
        } catch (Exception $e) {
            error_log("Form options loading error: " . $e->getMessage());
            $categories = [];
            $projects = [];
            $makers = [];
            $vendors = [];
            $clients = [];
            $procurements = [];
        }
        
        $pageTitle = 'Create Inventory Item - ConstructLink™';
        $pageHeader = 'Create New Item';
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '?route=dashboard'],
            ['title' => 'Inventory', 'url' => '?route=assets'],
            ['title' => 'Create Item', 'url' => '?route=assets/create']
        ];
        
        include APP_ROOT . '/views/assets/create.php';
    }
    
    /**
     * Display edit asset form
     */
    public function edit() {
        // Check permissions
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        $userId = $currentUser['id'] ?? 0;
        
        $assetId = $_GET['id'] ?? 0;
        
        if (!$assetId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        // Get asset first to check workflow status and permissions
        try {
            $asset = $this->assetModel->getAssetWithDetails($assetId);
            
            if (!$asset) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Status-based permission checking using AssetPermissionService
            $permissionService = new AssetPermissionService();
            $canEdit = $permissionService->canEditAsset($userRole, $userId, $asset);

            if (!$canEdit['allowed']) {
                // Show friendly message instead of 403 error
                $errorMessage = $canEdit['message'];
                include APP_ROOT . '/views/errors/edit_restricted.php';
                return;
            }
            
        } catch (Exception $e) {
            error_log("Edit permission check error: " . $e->getMessage());
            http_response_code(500);
            include APP_ROOT . '/views/errors/500.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            // Process form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                // Sanitize input data
                $formData = [
                    'ref' => Validator::sanitize($_POST['ref'] ?? $asset['ref']),
                    'name' => Validator::sanitize($_POST['name'] ?? ''),
                    'description' => Validator::sanitize($_POST['description'] ?? ''),
                    'category_id' => (int)($_POST['category_id'] ?? 0),
                    'project_id' => (int)($_POST['project_id'] ?? 0),
                    'maker_id' => !empty($_POST['maker_id']) ? (int)$_POST['maker_id'] : null,
                    'vendor_id' => !empty($_POST['vendor_id']) ? (int)$_POST['vendor_id'] : null,
                    'client_id' => !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null,
                    'model' => Validator::sanitize($_POST['model'] ?? ''),
                    'serial_number' => Validator::sanitize($_POST['serial_number'] ?? ''),
                    'specifications' => Validator::sanitize($_POST['specifications'] ?? ''),
                    'acquired_date' => $_POST['acquired_date'] ?? '',
                    'acquisition_cost' => !empty($_POST['acquisition_cost']) ? (float)$_POST['acquisition_cost'] : null,
                    'unit_cost' => !empty($_POST['unit_cost']) ? (float)$_POST['unit_cost'] : null,
                    'is_client_supplied' => isset($_POST['is_client_supplied']),
                    'warranty_expiry' => !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,
                    'location' => Validator::sanitize($_POST['location'] ?? ''),
                    'condition_notes' => Validator::sanitize($_POST['condition_notes'] ?? ''),
                    'equipment_type_id' => !empty($_POST['equipment_type_id']) ? (int)$_POST['equipment_type_id'] : null,
                    'subtype_id' => !empty($_POST['subtype_id']) ? (int)$_POST['subtype_id'] : null,
                    // Note: standardized_name should be for asset name standardization, not brand names
                    'brand_id' => !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null,
                    'quantity' => !empty($_POST['quantity']) ? (int)$_POST['quantity'] : 1,
                    'unit' => Validator::sanitize($_POST['unit'] ?? 'pcs'),
                    'specifications' => Validator::sanitize($_POST['specifications'] ?? ''),
                    'warranty_expiry' => !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,
                    'location' => Validator::sanitize($_POST['location'] ?? '')
                ];
                
                // Update asset using model method
                $result = $this->assetModel->updateAsset($assetId, $formData);
                
                if ($result['success']) {
                    $messages[] = 'Asset updated successfully.';
                    $asset = $result['asset']; // Update local asset data
                } else {
                    if (isset($result['errors'])) {
                        $errors = $result['errors'];
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            // Get form options using FormDataProvider
            $formProvider = new FormDataProvider();
            $formOptions = $formProvider->getAssetFormOptions();
            extract($formOptions); // Extracts: categories, projects, makers, vendors, clients, brands

            $pageTitle = 'Edit Asset - ' . $asset['name'];
            $pageHeader = 'Edit Asset: ' . $asset['ref'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Assets', 'url' => '?route=assets'],
                ['title' => 'Edit Asset', 'url' => '?route=assets/edit&id=' . $assetId]
            ];
            
            include APP_ROOT . '/views/assets/edit.php';
            
        } catch (Exception $e) {
            ControllerErrorHandler::handleException($e, 'load asset for editing');
        }
    }
    
    /**
     * Delete asset (AJAX)
     */
    public function delete() {
        header('Content-Type: application/json');

        // Check permissions using PermissionMiddleware
        if (!PermissionMiddleware::hasPermission('assets.delete')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        $assetId = $_POST['asset_id'] ?? 0;
        
        if (!$assetId) {
            echo json_encode(['success' => false, 'message' => 'Asset ID required']);
            return;
        }
        
        try {
            $result = $this->assetModel->deleteAsset($assetId);
            echo json_encode($result);
            
        } catch (Exception $e) {
            $errorData = ControllerErrorHandler::getErrorData($e, 'delete asset');
            echo json_encode($errorData);
        }
    }
    
    /**
     * Update asset status (AJAX)
     */
    public function updateStatus() {
        header('Content-Type: application/json');

        // Check permissions using PermissionMiddleware
        if (!PermissionMiddleware::hasPermission('assets.update_status')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        $assetId = $_POST['asset_id'] ?? 0;
        $newStatus = $_POST['status'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (!$assetId || !$newStatus) {
            echo json_encode(['success' => false, 'message' => 'Asset ID and status required']);
            return;
        }
        
        try {
            $result = $this->assetModel->updateAssetStatus($assetId, $newStatus, $notes);
            echo json_encode($result);
            
        } catch (Exception $e) {
            $errorData = ControllerErrorHandler::getErrorData($e, 'update asset status');
            echo json_encode($errorData);
        }
    }
    
    /**
     * Asset QR code scanner
     */
    public function scanner() {
        // Check permissions - all authenticated users can scan
        $pageTitle = 'Asset Scanner - ConstructLink™';
        $pageHeader = 'Asset QR Code Scanner';
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => '?route=dashboard'],
            ['title' => 'Assets', 'url' => '?route=assets'],
            ['title' => 'QR Scanner', 'url' => '?route=assets/scanner']
        ];
        
        include APP_ROOT . '/views/assets/scanner.php';
    }
    
    /**
     * Export assets to CSV
     */
    public function export() {
        // Check permissions - only certain roles can export
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        PermissionMiddleware::requirePermission('assets.export');
        
        try {
            // Build filters from GET parameters
            $filters = [];
            if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
            if (!empty($_GET['category_id'])) $filters['category_id'] = $_GET['category_id'];
            if (!empty($_GET['project_id'])) $filters['project_id'] = $_GET['project_id'];
            if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
            if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
            
            // Get export data using model method
            $csvData = $this->assetModel->exportAssets($filters);
            
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="assets_export_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            $output = fopen('php://output', 'w');
            
            // Output CSV data
            foreach ($csvData as $row) {
                fputcsv($output, $row);
            }
            
            fclose($output);
            
        } catch (Exception $e) {
            error_log("Asset export error: " . $e->getMessage());
            http_response_code(500);
            echo 'Export failed';
        }
    }
    
    /**
     * Bulk update asset status
     */
    public function bulkUpdate() {
        header('Content-Type: application/json');

        // Check permissions using PermissionMiddleware
        if (!PermissionMiddleware::hasPermission('assets.bulk_update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        $assetIds = $_POST['asset_ids'] ?? [];
        $action = $_POST['action'] ?? '';
        $status = $_POST['status'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (empty($assetIds) || !$action) {
            echo json_encode(['success' => false, 'message' => 'Asset IDs and action required']);
            return;
        }
        
        try {
            switch ($action) {
                case 'update_status':
                    if (!$status) {
                        echo json_encode(['success' => false, 'message' => 'Status required for update']);
                        return;
                    }
                    $result = $this->assetModel->bulkUpdateStatus($assetIds, $status, $notes);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    return;
            }
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            $errorData = ControllerErrorHandler::getErrorData($e, 'bulk update');
            echo json_encode($errorData);
        }
    }
    
    /**
     * Get asset utilization report
     */
    public function utilization() {
        // Check permissions
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        PermissionMiddleware::requirePermission('assets.utilization');
        
        try {
            $projectId = $_GET['project_id'] ?? null;
            $utilizationData = $this->assetModel->getAssetUtilization($projectId);
            
            $pageTitle = 'Asset Utilization Report - ConstructLink™';
            $pageHeader = 'Asset Utilization Report';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Assets', 'url' => '?route=assets'],
                ['title' => 'Utilization Report', 'url' => '?route=assets/utilization']
            ];
            
            // Get projects for filter
            $projectModel = new ProjectModel();
            $projects = $projectModel->getActiveProjects();
            
            include APP_ROOT . '/views/assets/utilization.php';
            
        } catch (Exception $e) {
            ControllerErrorHandler::handleException($e, 'load utilization report');
        }
    }
    
    /**
     * Get depreciation report
     */
    public function depreciation() {
        // Check permissions - financial data access
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        PermissionMiddleware::requirePermission('assets.depreciation');
        
        try {
            $projectId = $_GET['project_id'] ?? null;
            $depreciationData = $this->assetModel->getDepreciationReport($projectId);
            
            $pageTitle = 'Asset Depreciation Report - ConstructLink™';
            $pageHeader = 'Asset Depreciation Report';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Assets', 'url' => '?route=assets'],
                ['title' => 'Depreciation Report', 'url' => '?route=assets/depreciation']
            ];
            
            // Get projects for filter
            $projectModel = new ProjectModel();
            $projects = $projectModel->getActiveProjects();
            
            include APP_ROOT . '/views/assets/depreciation.php';
            
        } catch (Exception $e) {
            ControllerErrorHandler::handleException($e, 'load depreciation report');
        }
    }
    
    /**
     * Generate assets from procurement (called after procurement receipt)
     */
    public function generateFromProcurement() {
        header('Content-Type: application/json');

        // Check permissions using PermissionMiddleware
        if (!PermissionMiddleware::hasPermission('assets.generate_from_procurement')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        CSRFProtection::validateRequest();
        
        $procurementId = $_POST['procurement_id'] ?? 0;
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        if (!$procurementId) {
            echo json_encode(['success' => false, 'message' => 'Procurement ID required']);
            return;
        }
        
        try {
            $result = $this->assetModel->generateAssetsFromProcurement($procurementId, $quantity);
            echo json_encode($result);
            
        } catch (Exception $e) {
            $errorData = ControllerErrorHandler::getErrorData($e, 'generate assets from procurement');
            echo json_encode($errorData);
        }
    }
    
    /**
     * Create legacy asset (simplified workflow for existing site assets)
     */
    public function legacyCreate() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions - only Warehouseman can create legacy assets
        PermissionMiddleware::requirePermission('assets.legacy_create');
        
        $errors = [];
        $messages = [];
        $formData = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            // Sanitize and prepare form data
            $formData = [
                'name' => Validator::sanitize($_POST['name'] ?? ''),
                'description' => Validator::sanitize($_POST['description'] ?? ''),
                'category_id' => (int)($_POST['category_id'] ?? 0),
                'project_id' => (int)($_POST['project_id'] ?? 0),
                'quantity' => (int)($_POST['quantity'] ?? 1),
                'unit' => Validator::sanitize($_POST['unit'] ?? 'pcs'),
                'acquired_date' => $_POST['acquired_date'] ?? '',
                'sub_location' => Validator::sanitize($_POST['sub_location'] ?? ''),
                'condition_notes' => Validator::sanitize($_POST['condition_notes'] ?? ''),
                'serial_number' => Validator::sanitize($_POST['serial_number'] ?? ''),
                'model' => Validator::sanitize($_POST['model'] ?? ''),
                'specifications' => Validator::sanitize($_POST['specifications'] ?? ''),
                'location' => Validator::sanitize($_POST['location'] ?? ''),
                'warranty_expiry' => !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,
                'acquisition_cost' => !empty($_POST['acquisition_cost']) ? (float)$_POST['acquisition_cost'] : null,
                'unit_cost' => !empty($_POST['unit_cost']) ? (float)$_POST['unit_cost'] : null,
                'equipment_type_id' => !empty($_POST['equipment_type_id']) ? (int)$_POST['equipment_type_id'] : null,
                'subtype_id' => !empty($_POST['subtype_id']) ? (int)$_POST['subtype_id'] : null,
                // Note: standardized_name should be for asset name standardization, not brand names
                'brand_id' => !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null,
                'is_client_supplied' => isset($_POST['is_client_supplied']),
                'primary_discipline' => !empty($_POST['primary_discipline']) ? (int)$_POST['primary_discipline'] : null,
                'disciplines' => $_POST['disciplines'] ?? [],
                'maker_id' => !empty($_POST['maker_id']) ? (int)$_POST['maker_id'] : null,
                'vendor_id' => !empty($_POST['vendor_id']) ? (int)$_POST['vendor_id'] : null,
                'client_id' => !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null
            ];
            
            try {
                // Add inventory_source to mark as legacy workflow
                $formData['inventory_source'] = 'legacy';

                $result = $this->assetModel->createLegacyAsset($formData);

                if ($result['success']) {
                    // Check if this was a duplicate item with quantity addition
                    if (isset($result['is_duplicate']) && $result['is_duplicate'] === true) {
                        // Duplicate detected - quantity was added to existing item
                        $existingItem = $result['existing_item'];
                        $quantityAdded = $result['quantity_added'];
                        $messages[] = "Duplicate item detected! Added {$quantityAdded} {$existingItem['unit']} to existing item: <strong>{$existingItem['name']}</strong> (Ref: {$existingItem['ref']}). The quantity addition is pending verification through the MVA workflow.";
                    } elseif (isset($result['assets'])) {
                        // Multiple assets created (non-consumable)
                        $count = $result['count'];
                        $type = $result['type'];
                        $messages[] = "Successfully created {$count} individual legacy assets (non-consumable). Each asset is pending verification.";
                    } else {
                        // Single asset created (consumable)
                        $messages[] = 'Legacy asset created successfully and is pending verification.';
                    }
                    $formData = []; // Clear form on success
                } else {
                    if (isset($result['errors'])) {
                        $errors = array_merge($errors, $result['errors']);
                    } else {
                        $errors[] = $result['message'] ?? 'Failed to create legacy asset';
                    }
                }

            } catch (Exception $e) {
                error_log("Legacy asset creation error: " . $e->getMessage());
                $errors[] = 'Failed to create legacy asset';
            }
        }
        
        try {
            // Get form options using FormDataProvider
            $formProvider = new FormDataProvider();
            $formOptions = $formProvider->getAssetFormOptions();
            extract($formOptions); // Extracts: categories, projects, makers, vendors, clients, brands

            $pageTitle = 'Add Legacy Item - ConstructLink™';
            $pageHeader = 'Add Legacy Item';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Inventory', 'url' => '?route=assets'],
                ['title' => 'Add Legacy Item', 'url' => '?route=assets/legacy-create']
            ];
            
            include APP_ROOT . '/views/assets/legacy_create.php';
            
        } catch (Exception $e) {
            ControllerErrorHandler::handleException($e, 'load legacy asset creation form');
        }
    }

    /**
     * Verification dashboard for Site Inventory Clerk
     */
    public function verificationDashboard() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions - only Site Inventory Clerk
        PermissionMiddleware::requirePermission('assets.verification_dashboard');
        
        try {
            // Get assets pending verification
            $pendingAssets = $this->assetModel->getAssetsPendingVerification();
            
            // Get legacy workflow statistics
            $workflowStats = $this->assetModel->getLegacyWorkflowStats();
            
            $pageTitle = 'Asset Verification Dashboard - ConstructLink™';
            $pageHeader = 'Asset Verification Dashboard';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Assets', 'url' => '?route=assets'],
                ['title' => 'Verification Dashboard', 'url' => '?route=assets/verification-dashboard']
            ];
            
            include APP_ROOT . '/views/assets/verification_dashboard.php';
            
        } catch (Exception $e) {
            ControllerErrorHandler::handleException($e, 'load verification dashboard');
        }
    }

    /**
     * Authorization dashboard for Project Manager
     */
    public function authorizationDashboard() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions - only Project Manager
        PermissionMiddleware::requirePermission('assets.authorization_dashboard');
        
        try {
            // Get assets pending authorization
            $pendingAssets = $this->assetModel->getAssetsPendingAuthorization();
            
            // Get legacy workflow statistics
            $workflowStats = $this->assetModel->getLegacyWorkflowStats();
            
            $pageTitle = 'Asset Authorization Dashboard - ConstructLink™';
            $pageHeader = 'Asset Authorization Dashboard';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Assets', 'url' => '?route=assets'],
                ['title' => 'Authorization Dashboard', 'url' => '?route=assets/authorization-dashboard']
            ];
            
            include APP_ROOT . '/views/assets/authorization_dashboard.php';
            
        } catch (Exception $e) {
            ControllerErrorHandler::handleException($e, 'load authorization dashboard');
        }
    }

    /**
     * Verify legacy asset (AJAX endpoint)
     */
    public function verifyAsset() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();

        $assetId = (int)($_POST['asset_id'] ?? 0);
        $notes = Validator::sanitize($_POST['notes'] ?? '');
        
        if (!$assetId) {
            echo json_encode(['success' => false, 'message' => 'Asset ID required']);
            return;
        }
        
        try {
            $result = $this->assetModel->verifyLegacyAsset($assetId, $notes);
            echo json_encode($result);
            
        } catch (Exception $e) {
            $errorData = ControllerErrorHandler::getErrorData($e, 'verify asset');
            echo json_encode($errorData);
        }
    }

    /**
     * Authorize legacy asset (AJAX endpoint)
     */
    public function authorizeAsset() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();
        
        $assetId = (int)($_POST['asset_id'] ?? 0);
        $notes = Validator::sanitize($_POST['notes'] ?? '');
        
        if (!$assetId) {
            echo json_encode(['success' => false, 'message' => 'Asset ID required']);
            return;
        }
        
        try {
            $result = $this->assetModel->authorizeLegacyAsset($assetId, $notes);
            echo json_encode($result);
            
        } catch (Exception $e) {
            $errorData = ControllerErrorHandler::getErrorData($e, 'authorize asset');
            echo json_encode($errorData);
        }
    }

    /**
     * Batch verify assets (AJAX endpoint)
     */
    public function batchVerify() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();
        
        $assetIds = $_POST['asset_ids'] ?? [];
        $notes = Validator::sanitize($_POST['notes'] ?? '');
        
        if (empty($assetIds) || !is_array($assetIds)) {
            echo json_encode(['success' => false, 'message' => 'Asset IDs required']);
            return;
        }
        
        try {
            $result = $this->assetModel->batchVerifyAssets($assetIds, $notes);
            echo json_encode($result);
            
        } catch (Exception $e) {
            $errorData = ControllerErrorHandler::getErrorData($e, 'batch verify assets');
            echo json_encode($errorData);
        }
    }

    /**
     * Batch authorize assets (AJAX endpoint)
     */
    public function batchAuthorize() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();
        
        $assetIds = $_POST['asset_ids'] ?? [];
        $notes = Validator::sanitize($_POST['notes'] ?? '');
        
        if (empty($assetIds) || !is_array($assetIds)) {
            echo json_encode(['success' => false, 'message' => 'Asset IDs required']);
            return;
        }
        
        try {
            $result = $this->assetModel->batchAuthorizeAssets($assetIds, $notes);
            echo json_encode($result);
            
        } catch (Exception $e) {
            $errorData = ControllerErrorHandler::getErrorData($e, 'batch authorize assets');
            echo json_encode($errorData);
        }
    }

    
    /**
     * Verify asset (Asset Director workflow)
     */
    public function verify() {
        $assetId = $_GET['id'] ?? 0;
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        PermissionMiddleware::requirePermission('assets.verify');
        
        try {
            // Get asset details
            $asset = $this->queryService->getAssetWithDetails($assetId);
            if (!$asset) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }

            // Check if asset is in pending verification status
            if ($asset['workflow_status'] !== 'pending_verification') {
                $_SESSION['error'] = 'Asset is not in pending verification status';
                header('Location: ?route=assets/view&id=' . $assetId);
                exit;
            }
            
            $errors = [];
            
            // Handle form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $verificationNotes = $_POST['verification_notes'] ?? '';
                
                if (isset($_POST['action']) && $_POST['action'] === 'verify') {
                    // Verify asset
                    $result = $this->assetModel->verifyAsset($assetId, $currentUser['id'], $verificationNotes);
                    
                    if ($result['success']) {
                        $_SESSION['success'] = $result['message'];
                        header('Location: ?route=assets/view&id=' . $assetId);
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                } elseif (isset($_POST['action']) && $_POST['action'] === 'reject') {
                    // Reject asset
                    $rejectionReason = $_POST['rejection_reason'] ?? '';
                    if (empty($rejectionReason)) {
                        $errors[] = 'Rejection reason is required';
                    } else {
                        $result = $this->assetModel->rejectAsset($assetId, $currentUser['id'], $rejectionReason);
                        
                        if ($result['success']) {
                            $_SESSION['success'] = $result['message'];
                            header('Location: ?route=assets/view&id=' . $assetId);
                            exit;
                        } else {
                            $errors[] = $result['message'];
                        }
                    }
                }
            }
            
            include APP_ROOT . '/views/assets/verify.php';
            
        } catch (Exception $e) {
            error_log("Asset verification error: " . $e->getMessage());
            $error = 'Failed to load asset verification page';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Authorize asset (Finance Director workflow)
     */
    public function authorize() {
        $assetId = $_GET['id'] ?? 0;
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        PermissionMiddleware::requirePermission('assets.authorize');
        
        try {
            // Get asset details
            $asset = $this->queryService->getAssetWithDetails($assetId);
            if (!$asset) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }

            // Check if asset is in pending authorization status
            if ($asset['workflow_status'] !== 'pending_authorization') {
                $_SESSION['error'] = 'Asset is not in pending authorization status';
                header('Location: ?route=assets/view&id=' . $assetId);
                exit;
            }
            
            $errors = [];
            
            // Handle form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $authorizationNotes = $_POST['authorization_notes'] ?? '';
                
                if (isset($_POST['action']) && $_POST['action'] === 'authorize') {
                    // Authorize asset
                    $result = $this->assetModel->authorizeAsset($assetId, $currentUser['id'], $authorizationNotes);
                    
                    if ($result['success']) {
                        $_SESSION['success'] = $result['message'];
                        header('Location: ?route=assets/view&id=' . $assetId);
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                } elseif (isset($_POST['action']) && $_POST['action'] === 'reject') {
                    // Reject asset
                    $rejectionReason = $_POST['rejection_reason'] ?? '';
                    if (empty($rejectionReason)) {
                        $errors[] = 'Rejection reason is required';
                    } else {
                        $result = $this->assetModel->rejectAsset($assetId, $currentUser['id'], $rejectionReason);
                        
                        if ($result['success']) {
                            $_SESSION['success'] = $result['message'];
                            header('Location: ?route=assets/view&id=' . $assetId);
                            exit;
                        } else {
                            $errors[] = $result['message'];
                        }
                    }
                }
            }
            
            include APP_ROOT . '/views/assets/authorize.php';
            
        } catch (Exception $e) {
            error_log("Asset authorization error: " . $e->getMessage());
            $error = 'Failed to load asset authorization page';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Assign or reassign asset to a sub-location (AJAX)
     * Phase 3: Delegated to AssetLocationService
     */
    public function assignLocation() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();

        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        // Check permissions
        if (!$this->locationService->canAssignLocation($userRole)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }

        $assetId = (int)($_POST['asset_id'] ?? 0);
        $subLocation = $_POST['sub_location'] ?? '';
        $notes = $_POST['notes'] ?? '';

        try {
            $result = $this->locationService->assignLocation(
                $assetId,
                $subLocation,
                $notes,
                $currentUser['id']
            );

            echo json_encode($result);

        } catch (Exception $e) {
            $errorData = ControllerErrorHandler::getErrorData($e, 'assign location');
            echo json_encode($errorData);
        }
    }
    
    /**
     * Get asset verification data with enhanced details (API endpoint)
     * Phase 3: Delegated to AssetWorkflowService
     */
    public function getVerificationData() {
        header('Content-Type: application/json');

        // Get asset ID from request
        $assetId = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$assetId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Asset ID is required']);
            return;
        }

        try {
            $asset = $this->workflowService->getVerificationData($assetId);
            echo json_encode($asset);

        } catch (Exception $e) {
            error_log("Get verification data error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to load asset data: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get asset data for authorization modal (API endpoint)
     * Phase 3: Delegated to AssetWorkflowService
     */
    public function getAuthorizationData() {
        header('Content-Type: application/json');

        // Get asset ID from request
        $assetId = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$assetId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Asset ID is required']);
            return;
        }

        try {
            $asset = $this->workflowService->getAuthorizationData($assetId);
            echo json_encode($asset);

        } catch (Exception $e) {
            error_log("Get authorization data error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to load asset data: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Validate asset quality using validation engine (API endpoint)
     */
    public function validateAssetQuality() {
        header('Content-Type: application/json');

        // Check permissions using PermissionMiddleware
        if (!PermissionMiddleware::hasPermission('assets.validate_quality')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $assetData = $input['asset_data'] ?? [];
            $role = $input['user_role'] ?? $userRole;
            
            // Use simple calculator instead of complex validator
            require_once APP_ROOT . '/core/SimpleDataQualityCalculator.php';
            
            // Calculate quality scores
            $results = SimpleDataQualityCalculator::calculateQuality($assetData, $role);
            
            echo json_encode($results);
            
        } catch (Exception $e) {
            error_log("Asset quality calculation error: " . $e->getMessage());
            echo json_encode([
                'overall_score' => 0,
                'completeness_score' => 0,
                'accuracy_score' => 0,
                'validation_results' => [],
                'errors' => ['Quality calculation error'],
                'warnings' => [],
                'info' => []
            ]);
        }
    }
    
    /**
     * Reject asset verification with feedback
     * Phase 3: Delegated to AssetWorkflowService
     */
    public function rejectVerification() {
        header('Content-Type: application/json');

        // Check permissions using PermissionMiddleware
        if (!PermissionMiddleware::hasPermission('assets.reject_verification')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $assetId = $input['asset_id'] ?? null;
            $feedbackNotes = $input['feedback_notes'] ?? '';
            $validationResults = $input['validation_results'] ?? null;

            $result = $this->workflowService->rejectVerificationWithFeedback(
                $assetId,
                $currentUser['id'],
                $feedbackNotes,
                $validationResults
            );

            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Reject verification error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Approve asset with conditions
     * Phase 3: Delegated to AssetWorkflowService
     */
    public function approveWithConditions() {
        header('Content-Type: application/json');

        // Check permissions using PermissionMiddleware
        if (!PermissionMiddleware::hasPermission('assets.approve_with_conditions')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        CSRFProtection::validateRequest();

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $assetId = $input['asset_id'] ?? null;
            $verificationNotes = $input['verification_notes'] ?? '';
            $verifiedLocation = $input['verified_location'] ?? '';
            $verifiedQuantity = $input['verified_quantity'] ?? null;
            $physicalCondition = $input['physical_condition'] ?? '';
            $validationResults = $input['validation_results'] ?? null;

            $result = $this->workflowService->approveWithConditions(
                $assetId,
                $currentUser['id'],
                $verificationNotes,
                $verifiedLocation,
                $verifiedQuantity,
                $physicalCondition,
                $validationResults
            );

            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Approve with conditions error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * ========================================================================
     * PHASE 2 REFACTORING COMPLETE - BUSINESS LOGIC MOVED TO SERVICES
     * ========================================================================
     *
     * The following methods have been extracted to service classes:
     *
     * 1. checkEditPermissions()
     *    └─> Moved to: AssetPermissionService::canEditAsset()
     *    └─> Purpose: Workflow-based edit permission logic
     *    └─> Lines saved: ~90 lines
     *
     * 2. generateRoleBasedStats()
     *    └─> Moved to: AssetStatisticsService::getRoleBasedStats()
     *    └─> Purpose: Role-specific dashboard statistics
     *    └─> Lines saved: ~132 lines
     *
     * Total lines removed from controller: ~222 lines
     * Result: Cleaner controller, testable business logic
     *
     * ========================================================================
     */
}
?>
