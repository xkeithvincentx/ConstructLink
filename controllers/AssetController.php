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

class AssetController {
    private $auth;
    private $assetModel;
    private $db;

    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->assetModel = new AssetModel();

        // Initialize database connection using Database class
        $this->db = Database::getInstance()->getConnection();

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
        
        if (!in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
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
            $assets = $this->enhanceAssetData($assets);
            
            // Get role-specific asset statistics for dashboard cards (from database)
            $currentProjectId = $currentUser['current_project_id'] ?? null;
            $roleStats = $this->assetModel->getRoleSpecificStatistics($userRole, $currentProjectId);

            // Get asset statistics for dashboard cards (fallback/legacy)
            $assetStats = $this->assetModel->getAssetStatistics();

            // Get MVA workflow statistics
            $workflowStats = $this->assetModel->getWorkflowStatistics();

            // Get overdue assets for alerts
            $overdueAssets = $this->assetModel->getOverdueAssets('withdrawal');

            // Get filter options
            $categoryModel = new CategoryModel();
            $projectModel = new ProjectModel();
            $vendorModel = new VendorModel();

            $categories = $categoryModel->getActiveCategories();
            $projects = $projectModel->getActiveProjects();
            $vendors = $vendorModel->findAll([], 'name ASC');

            // Load brands from asset_brands table (not makers table)
            $brandQuery = "SELECT id, official_name, quality_tier FROM asset_brands WHERE is_active = 1 ORDER BY official_name ASC";
            $stmt = $this->db->prepare($brandQuery);
            $stmt->execute();
            $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
            error_log("Asset listing error: " . $e->getMessage());
            $error = 'Failed to load assets';
            include APP_ROOT . '/views/errors/500.php';
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
            error_log("Asset view error: " . $e->getMessage());
            $error = 'Failed to load asset details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display create asset form
     */
    public function create() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        $canMaker = in_array($userRole, ['Procurement Officer', 'Warehouseman']);
        $canVerifier = ($userRole === 'Asset Director');
        $canAuthorizer = ($userRole === 'System Admin');
        if (!$canMaker && !$canVerifier && !$canAuthorizer) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
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
            $categoryModel = new CategoryModel();
            $projectModel = new ProjectModel();
            $makerModel = new MakerModel();
            $vendorModel = new VendorModel();
            $clientModel = new ClientModel();
            $procurementModel = new ProcurementModel();
            
            $categories = $categoryModel->getActiveCategories(); // Includes business fields
            $projects = $projectModel->getActiveProjects();
            $makers = $makerModel->findAll([], 'name ASC');
            $vendors = $vendorModel->findAll([], 'name ASC');
            $clients = $clientModel->findAll([], 'name ASC');
            
            // Get brands from database
            $db = Database::getInstance()->getConnection();
            $brandQuery = "SELECT id, official_name, quality_tier FROM asset_brands WHERE is_active = 1 ORDER BY official_name ASC";
            $brandStmt = $db->query($brandQuery);
            $brands = $brandStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get both legacy and multi-item procurement sources
            $procurements = $this->getCombinedProcurementSources();
            
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
            
            // Status-based permission checking
            $canEdit = $this->checkEditPermissions($userRole, $userId, $asset);
            
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
            
            // Get form options
            $categoryModel = new CategoryModel();
            $projectModel = new ProjectModel();
            $makerModel = new MakerModel();
            $vendorModel = new VendorModel();
            $clientModel = new ClientModel();
            
            $categories = $categoryModel->getActiveCategories(); // Includes business fields
            $projects = $projectModel->getActiveProjects();
            $makers = $makerModel->findAll([], 'name ASC');
            $vendors = $vendorModel->findAll([], 'name ASC');
            $clients = $clientModel->findAll([], 'name ASC');
            
            // Get brands from database (missing from edit form)
            $db = Database::getInstance()->getConnection();
            $brandQuery = "SELECT id, official_name, quality_tier FROM asset_brands WHERE is_active = 1 ORDER BY official_name ASC";
            $brandStmt = $db->query($brandQuery);
            $brands = $brandStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pageTitle = 'Edit Asset - ' . $asset['name'];
            $pageHeader = 'Edit Asset: ' . $asset['ref'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Assets', 'url' => '?route=assets'],
                ['title' => 'Edit Asset', 'url' => '?route=assets/edit&id=' . $assetId]
            ];
            
            include APP_ROOT . '/views/assets/edit.php';
            
        } catch (Exception $e) {
            error_log("Asset edit error: " . $e->getMessage());
            $error = 'Failed to load asset for editing';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Delete asset (AJAX)
     */
    public function delete() {
        // Check permissions - only System Admin and Asset Director can delete
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if (!in_array($userRole, ['System Admin', 'Asset Director'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        header('Content-Type: application/json');
        
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
            error_log("Asset deletion error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to delete asset']);
        }
    }
    
    /**
     * Update asset status (AJAX)
     */
    public function updateStatus() {
        // Check permissions
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if (!in_array($userRole, ['System Admin', 'Asset Director', 'Warehouseman', 'Project Manager'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        header('Content-Type: application/json');
        
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
            error_log("Asset status update error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update asset status']);
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
        
        if (!in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
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
        // Check permissions
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if (!in_array($userRole, ['System Admin', 'Asset Director'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        header('Content-Type: application/json');
        
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
            error_log("Bulk update error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to perform bulk update']);
        }
    }
    
    /**
     * Get asset utilization report
     */
    public function utilization() {
        // Check permissions
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if (!in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director', 'Project Manager'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
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
            error_log("Asset utilization error: " . $e->getMessage());
            $error = 'Failed to load utilization report';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Get depreciation report
     */
    public function depreciation() {
        // Check permissions - financial data access
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if (!in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
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
            error_log("Asset depreciation error: " . $e->getMessage());
            $error = 'Failed to load depreciation report';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Generate assets from procurement (called after procurement receipt)
     */
    public function generateFromProcurement() {
        // Check permissions - only Warehouseman and Procurement Officer
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if (!in_array($userRole, ['System Admin', 'Procurement Officer', 'Warehouseman'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        header('Content-Type: application/json');
        
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
            error_log("Generate assets from procurement error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to generate assets']);
        }
    }
    
    /**
     * Create legacy asset (simplified workflow for existing site assets)
     */
    public function legacyCreate() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions - only Warehouseman can create legacy assets
        if (!in_array($userRole, ['Warehouseman', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
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
                $result = $this->assetModel->createLegacyAsset($formData);
                
                if ($result['success']) {
                    // Handle different response types (single asset vs multiple assets)
                    if (isset($result['assets'])) {
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
            // Get dropdown data with business classification info
            $categoryModel = new CategoryModel();
            $projectModel = new ProjectModel();
            $makerModel = new MakerModel();
            
            $categories = $categoryModel->getActiveCategories(); // This includes business fields
            $projects = $projectModel->getActiveProjects();
            $makers = $makerModel->findAll([], 'name ASC');
            
            // Get brands from database
            $db = Database::getInstance()->getConnection();
            $brandQuery = "SELECT id, official_name, quality_tier FROM asset_brands WHERE is_active = 1 ORDER BY official_name ASC";
            $brandStmt = $db->query($brandQuery);
            $brands = $brandStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pageTitle = 'Add Legacy Item - ConstructLink™';
            $pageHeader = 'Add Legacy Item';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Inventory', 'url' => '?route=assets'],
                ['title' => 'Add Legacy Item', 'url' => '?route=assets/legacy-create']
            ];
            
            include APP_ROOT . '/views/assets/legacy_create.php';
            
        } catch (Exception $e) {
            error_log("Legacy create view error: " . $e->getMessage());
            $error = 'Failed to load legacy asset creation form';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Verification dashboard for Site Inventory Clerk
     */
    public function verificationDashboard() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions - only Site Inventory Clerk
        if (!in_array($userRole, ['Site Inventory Clerk', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
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
            error_log("Verification dashboard error: " . $e->getMessage());
            $error = 'Failed to load verification dashboard';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Authorization dashboard for Project Manager
     */
    public function authorizationDashboard() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions - only Project Manager
        if (!in_array($userRole, ['Project Manager', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
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
            error_log("Authorization dashboard error: " . $e->getMessage());
            $error = 'Failed to load authorization dashboard';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Verify legacy asset (AJAX endpoint)
     */
    public function verifyAsset() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        if (!in_array($userRole, ['Site Inventory Clerk', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
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
            error_log("Verify asset error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to verify asset']);
        }
    }

    /**
     * Authorize legacy asset (AJAX endpoint)
     */
    public function authorizeAsset() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        if (!in_array($userRole, ['Project Manager', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
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
            error_log("Authorize asset error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to authorize asset']);
        }
    }

    /**
     * Batch verify assets (AJAX endpoint)
     */
    public function batchVerify() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        if (!in_array($userRole, ['Site Inventory Clerk', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
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
            error_log("Batch verify error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to batch verify assets']);
        }
    }

    /**
     * Batch authorize assets (AJAX endpoint)
     */
    public function batchAuthorize() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        if (!in_array($userRole, ['Project Manager', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
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
            error_log("Batch authorize error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to batch authorize assets']);
        }
    }

    /**
     * Get combined procurement sources (legacy + multi-item)
     */
    private function getCombinedProcurementSources() {
        $procurements = [];
        
        try {
            // Get legacy procurement orders
            $procurementModel = new ProcurementModel();
            $legacyProcurements = $procurementModel->getReceivedProcurements();
            
            foreach ($legacyProcurements as $procurement) {
                $procurements[] = [
                    'id' => $procurement['id'],
                    'type' => 'legacy',
                    'po_number' => $procurement['po_number'],
                    'title' => $procurement['item_name'],
                    'vendor_name' => $procurement['vendor_name'] ?? '',
                    'total_value' => $procurement['total_cost'] ?? 0,
                    'item_count' => 1
                ];
            }
            
            // Get multi-item procurement orders
            if (class_exists('ProcurementOrderModel')) {
                $procurementOrderModel = new ProcurementOrderModel();
                $multiItemOrders = $procurementOrderModel->getReceivedOrders();
                
                foreach ($multiItemOrders as $order) {
                    $procurements[] = [
                        'id' => $order['id'],
                        'type' => 'multi_item',
                        'po_number' => $order['po_number'],
                        'title' => $order['title'],
                        'vendor_name' => $order['vendor_name'] ?? '',
                        'total_value' => $order['total_value'] ?? 0,
                        'item_count' => $order['item_count'] ?? 0
                    ];
                }
            }
            
        } catch (Exception $e) {
            error_log("Get combined procurement sources error: " . $e->getMessage());
        }
        
        return $procurements;
    }
    
    /**
     * Enhance asset data with consumable info and units
     */
    private function enhanceAssetData($assets) {
        if (empty($assets)) {
            return $assets;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get category and procurement item data for all assets
            $assetIds = array_column($assets, 'id');
            $placeholders = str_repeat('?,', count($assetIds) - 1) . '?';
            
            $sql = "
                SELECT a.id, c.is_consumable, pi.unit
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN procurement_items pi ON a.procurement_item_id = pi.id
                WHERE a.id IN ({$placeholders})
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($assetIds);
            $enhancementData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create lookup array
            $lookup = [];
            foreach ($enhancementData as $data) {
                $lookup[$data['id']] = [
                    'is_consumable' => $data['is_consumable'],
                    'unit' => $data['unit'] ?? 'pcs'
                ];
            }
            
            // Enhance each asset
            foreach ($assets as &$asset) {
                if (isset($lookup[$asset['id']])) {
                    $asset['is_consumable'] = $lookup[$asset['id']]['is_consumable'];
                    $asset['unit'] = $lookup[$asset['id']]['unit'];
                } else {
                    $asset['is_consumable'] = 0;
                    $asset['unit'] = 'pcs';
                }
            }
            
        } catch (Exception $e) {
            error_log("Enhance asset data error: " . $e->getMessage());
            // If enhancement fails, add default values
            foreach ($assets as &$asset) {
                $asset['is_consumable'] = 0;
                $asset['unit'] = 'pcs';
            }
        }
        
        return $assets;
    }
    
    /**
     * Verify asset (Asset Director workflow)
     */
    public function verify() {
        $assetId = $_GET['id'] ?? 0;
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        if (!in_array($userRole, ['Asset Director', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Get asset details
            $asset = $this->getAssetWithDetails($assetId);
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
        if (!in_array($userRole, ['Finance Director', 'System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Get asset details
            $asset = $this->getAssetWithDetails($assetId);
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
     * Get asset with detailed information including workflow data
     */
    public function getAssetWithDetails($assetId) {
        try {
            $sql = "
                SELECT a.*, 
                       c.name as category_name, c.is_consumable,
                       p.name as project_name, p.location as project_location,
                       v.name as vendor_name,
                       m.name as maker_name,
                       u1.full_name as made_by_name,
                       u2.full_name as verified_by_name,
                       u3.full_name as authorized_by_name,
                       po.po_number, pi.item_name as procurement_item_name, pi.brand as procurement_item_brand
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN makers m ON a.maker_id = m.id
                LEFT JOIN users u1 ON a.made_by = u1.id
                LEFT JOIN users u2 ON a.verified_by = u2.id
                LEFT JOIN users u3 ON a.authorized_by = u3.id
                LEFT JOIN procurement_orders po ON a.procurement_order_id = po.id
                LEFT JOIN procurement_items pi ON a.procurement_item_id = pi.id
                WHERE a.id = ?
                LIMIT 1
            ";
            
            $stmt = $this->assetModel->db->prepare($sql);
            $stmt->execute([$assetId]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Get asset with details error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Assign or reassign asset to a sub-location (AJAX)
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
        if (!in_array($userRole, ['Warehouseman', 'Site Inventory Clerk', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        $assetId = (int)($_POST['asset_id'] ?? 0);
        $subLocation = Validator::sanitize($_POST['sub_location'] ?? '');
        $notes = Validator::sanitize($_POST['notes'] ?? '');
        
        if (!$assetId) {
            echo json_encode(['success' => false, 'message' => 'Asset ID required']);
            return;
        }
        
        if (empty($subLocation)) {
            echo json_encode(['success' => false, 'message' => 'Sub-location required']);
            return;
        }
        
        try {
            // Verify asset exists and user has access
            $asset = $this->assetModel->getAssetWithDetails($assetId);
            
            if (!$asset) {
                echo json_encode(['success' => false, 'message' => 'Asset not found']);
                return;
            }
            
            // Get old location for logging
            $oldSubLocation = $asset['sub_location'] ?? '';
            
            // Update asset sub_location
            $result = $this->assetModel->update($assetId, [
                'sub_location' => $subLocation
            ]);
            
            if ($result) {
                // Log the location assignment activity
                $this->assetModel->logAssetActivity(
                    $assetId,
                    'location_assigned',
                    $oldSubLocation ? "Location changed from '{$oldSubLocation}' to '{$subLocation}'" : "Location assigned to '{$subLocation}'",
                    $asset,
                    array_merge($asset, ['sub_location' => $subLocation]),
                    $notes
                );
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Location assigned successfully',
                    'sub_location' => $subLocation
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to assign location']);
            }
            
        } catch (Exception $e) {
            error_log("Assign location error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }
    
    /**
     * Get asset verification data with enhanced details (API endpoint)
     */
    public function getVerificationData() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        if (!in_array($userRole, ['Site Inventory Clerk', 'Project Manager', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        header('Content-Type: application/json');
        
        // Get asset ID from request
        $assetId = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$assetId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Asset ID is required']);
            return;
        }
        
        try {
            // Get basic asset data first to debug
            $stmt = $this->db->prepare("SELECT * FROM assets WHERE id = ?");
            $stmt->execute([$assetId]);
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$asset) {
                throw new Exception("Asset not found with ID: " . $assetId);
            }
            
            // Check if it's a legacy asset
            if ($asset['asset_source'] !== 'legacy') {
                throw new Exception("Asset is not a legacy asset. Source: " . $asset['asset_source']);
            }
            
            // Get additional related data
            try {
                // Category
                if ($asset['category_id']) {
                    $stmt = $this->db->prepare("SELECT name FROM categories WHERE id = ?");
                    $stmt->execute([$asset['category_id']]);
                    $category = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['category_name'] = $category['name'] ?? null;
                }
                
                // Equipment Type
                if ($asset['equipment_type_id']) {
                    $stmt = $this->db->prepare("SELECT name FROM equipment_types WHERE id = ?");
                    $stmt->execute([$asset['equipment_type_id']]);
                    $equipmentType = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['equipment_type_name'] = $equipmentType['name'] ?? null;
                }
                
                // Equipment Subtype
                if ($asset['subtype_id']) {
                    $stmt = $this->db->prepare("SELECT subtype_name as name FROM equipment_subtypes WHERE id = ?");
                    $stmt->execute([$asset['subtype_id']]);
                    $subtype = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['subtype_name'] = $subtype['name'] ?? null;
                }
                
                // Project
                if ($asset['project_id']) {
                    $stmt = $this->db->prepare("SELECT name FROM projects WHERE id = ?");
                    $stmt->execute([$asset['project_id']]);
                    $project = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['project_name'] = $project['name'] ?? null;
                }
                
                // Brand
                if ($asset['brand_id']) {
                    $stmt = $this->db->prepare("SELECT official_name FROM asset_brands WHERE id = ?");
                    $stmt->execute([$asset['brand_id']]);
                    $brand = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['brand_name'] = $brand['official_name'] ?? null;
                }
                
                // Creator
                if ($asset['made_by']) {
                    $stmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
                    $stmt->execute([$asset['made_by']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['created_by_name'] = $user['full_name'] ?? null;
                }
                
                // Verifier
                if ($asset['verified_by']) {
                    $stmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
                    $stmt->execute([$asset['verified_by']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['verified_by_name'] = $user['full_name'] ?? null;
                }
                
                // Disciplines - parse discipline_tags field
                if (!empty($asset['discipline_tags'])) {
                    $disciplineCodes = explode(',', $asset['discipline_tags']);
                    $disciplineNames = [];
                    $subDisciplineNames = [];
                    
                    foreach ($disciplineCodes as $code) {
                        $code = trim($code);
                        $stmt = $this->db->prepare("SELECT name, parent_id FROM asset_disciplines WHERE code = ? OR iso_code = ?");
                        $stmt->execute([$code, $code]);
                        $discipline = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($discipline) {
                            if ($discipline['parent_id'] === null) {
                                // Main discipline
                                $disciplineNames[] = $discipline['name'];
                            } else {
                                // Sub-discipline
                                $subDisciplineNames[] = $discipline['name'];
                            }
                        }
                    }
                    
                    $asset['discipline_names'] = !empty($disciplineNames) ? implode(', ', $disciplineNames) : null;
                    $asset['sub_discipline_names'] = !empty($subDisciplineNames) ? implode(', ', $subDisciplineNames) : null;
                } else {
                    $asset['discipline_names'] = null;
                    $asset['sub_discipline_names'] = null;
                }
                
            } catch (Exception $e) {
                error_log("Warning: Could not load some related data: " . $e->getMessage());
                // Continue with basic asset data
            }
            
            echo json_encode($asset);
            
        } catch (Exception $e) {
            error_log("Get verification data error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to load asset data: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get asset data for authorization modal (API endpoint)
     */
    public function getAuthorizationData() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions - only Project Managers can authorize
        if (!in_array($userRole, ['Project Manager', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        header('Content-Type: application/json');
        
        // Get asset ID from request
        $assetId = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$assetId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Asset ID is required']);
            return;
        }
        
        try {
            // Get basic asset data
            $stmt = $this->db->prepare("SELECT * FROM assets WHERE id = ?");
            $stmt->execute([$assetId]);
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$asset) {
                throw new Exception("Asset not found with ID: " . $assetId);
            }
            
            // Only allow authorization for verified assets
            if ($asset['workflow_status'] !== 'pending_authorization') {
                throw new Exception("Asset is not ready for authorization. Current status: " . $asset['workflow_status']);
            }
            
            // Get additional related data (same as verification)
            try {
                // Category
                if ($asset['category_id']) {
                    $stmt = $this->db->prepare("SELECT name FROM categories WHERE id = ?");
                    $stmt->execute([$asset['category_id']]);
                    $category = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['category_name'] = $category['name'] ?? null;
                }
                
                // Equipment Type
                if ($asset['equipment_type_id']) {
                    $stmt = $this->db->prepare("SELECT name FROM equipment_types WHERE id = ?");
                    $stmt->execute([$asset['equipment_type_id']]);
                    $equipmentType = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['equipment_type_name'] = $equipmentType['name'] ?? null;
                }
                
                // Equipment Subtype
                if ($asset['subtype_id']) {
                    $stmt = $this->db->prepare("SELECT subtype_name as name FROM equipment_subtypes WHERE id = ?");
                    $stmt->execute([$asset['subtype_id']]);
                    $subtype = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['subtype_name'] = $subtype['name'] ?? null;
                }
                
                // Project
                if ($asset['project_id']) {
                    $stmt = $this->db->prepare("SELECT name FROM projects WHERE id = ?");
                    $stmt->execute([$asset['project_id']]);
                    $project = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['project_name'] = $project['name'] ?? null;
                }
                
                // Verifier
                if ($asset['verified_by']) {
                    $stmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
                    $stmt->execute([$asset['verified_by']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['verified_by_name'] = $user['full_name'] ?? null;
                }
                
                // Disciplines - parse discipline_tags field
                if (!empty($asset['discipline_tags'])) {
                    $disciplineCodes = explode(',', $asset['discipline_tags']);
                    $disciplineNames = [];
                    $subDisciplineNames = [];
                    
                    foreach ($disciplineCodes as $code) {
                        $code = trim($code);
                        $stmt = $this->db->prepare("SELECT name, parent_id FROM asset_disciplines WHERE code = ? OR iso_code = ?");
                        $stmt->execute([$code, $code]);
                        $discipline = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($discipline) {
                            if ($discipline['parent_id'] === null) {
                                // Main discipline
                                $disciplineNames[] = $discipline['name'];
                            } else {
                                // Sub-discipline
                                $subDisciplineNames[] = $discipline['name'];
                            }
                        }
                    }
                    
                    $asset['discipline_names'] = !empty($disciplineNames) ? implode(', ', $disciplineNames) : null;
                    $asset['sub_discipline_names'] = !empty($subDisciplineNames) ? implode(', ', $subDisciplineNames) : null;
                } else {
                    $asset['discipline_names'] = null;
                    $asset['sub_discipline_names'] = null;
                }
                
            } catch (Exception $e) {
                error_log("Warning: Could not load some related data for authorization: " . $e->getMessage());
                // Continue with basic asset data
            }
            
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
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        if (!in_array($userRole, ['Site Inventory Clerk', 'Project Manager', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        header('Content-Type: application/json');
        
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
     */
    public function rejectVerification() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        if (!in_array($userRole, ['Site Inventory Clerk', 'Project Manager', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        header('Content-Type: application/json');
        
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
            
            if (!$assetId) {
                throw new Exception("Asset ID is required");
            }
            
            if (empty($feedbackNotes)) {
                throw new Exception("Feedback notes are required");
            }
            
            // Update asset status back to draft for revision
            $stmt = $this->db->prepare("
                UPDATE assets 
                SET workflow_status = 'draft', 
                    updated_at = NOW() 
                WHERE id = ? AND asset_source = 'legacy'
            ");
            $stmt->execute([$assetId]);
            
            // Log the rejection review
            $stmt = $this->db->prepare("
                INSERT INTO asset_verification_reviews 
                (asset_id, reviewer_id, review_type, review_status, review_notes, validation_results, created_at)
                VALUES (?, ?, 'verification', 'needs_revision', ?, ?, NOW())
            ");
            $stmt->execute([
                $assetId,
                $currentUser['id'],
                $feedbackNotes,
                json_encode($validationResults)
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Asset sent back for revision']);
            
        } catch (Exception $e) {
            error_log("Reject verification error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Approve asset with conditions
     */
    public function approveWithConditions() {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // Check permissions
        if (!in_array($userRole, ['Site Inventory Clerk', 'Project Manager', 'System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            return;
        }
        
        header('Content-Type: application/json');
        
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
            
            if (!$assetId) {
                throw new Exception("Asset ID is required");
            }
            
            $this->db->beginTransaction();
            
            // Update asset with verification data
            $updateFields = [
                'workflow_status' => 'pending_authorization',
                'verified_by' => $currentUser['id'],
                'verification_date' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Update location if verified
            if (!empty($verifiedLocation)) {
                $updateFields['location'] = $verifiedLocation;
            }
            
            // Update quantity if verified
            if (!empty($verifiedQuantity)) {
                $updateFields['quantity'] = $verifiedQuantity;
                $updateFields['available_quantity'] = $verifiedQuantity;
            }
            
            $setClause = implode(', ', array_map(fn($key) => "$key = ?", array_keys($updateFields)));
            $stmt = $this->db->prepare("UPDATE assets SET $setClause WHERE id = ? AND asset_source = 'legacy'");
            $stmt->execute([...array_values($updateFields), $assetId]);
            
            // Log the verification review
            $overallScore = $validationResults['overall_score'] ?? null;
            $completenessScore = $validationResults['completeness_score'] ?? null;
            $accuracyScore = $validationResults['accuracy_score'] ?? null;
            
            $stmt = $this->db->prepare("
                INSERT INTO asset_verification_reviews 
                (asset_id, reviewer_id, review_type, review_status, overall_score, completeness_score, accuracy_score, review_notes, validation_results, physical_verification_completed, location_verified, created_at)
                VALUES (?, ?, 'verification', 'completed', ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $assetId,
                $currentUser['id'],
                $overallScore,
                $completenessScore,
                $accuracyScore,
                $verificationNotes,
                json_encode($validationResults),
                !empty($physicalCondition) ? 1 : 0,
                !empty($verifiedLocation) ? 1 : 0
            ]);
            
            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Asset approved with conditions and forwarded for authorization']);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Approve with conditions error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Check if user can edit asset based on workflow status and role
     */
    private function checkEditPermissions($userRole, $userId, $asset) {
        $workflowStatus = $asset['workflow_status'] ?? '';
        $assetMakerId = $asset['made_by'] ?? 0;
        $assetSource = $asset['asset_source'] ?? 'manual';

        // System Admin and Asset Director can always edit
        if (in_array($userRole, ['System Admin', 'Asset Director'])) {
            return ['allowed' => true, 'message' => ''];
        }

        // Check permissions based on workflow status
        switch ($workflowStatus) {
            case 'draft':
            case 'pending_verification':
                // Warehouseman can only edit their own legacy assets in draft/pending stages
                if ($userRole === 'Warehouseman' && $assetSource === 'legacy') {
                    if ($assetMakerId == $userId) {
                        return ['allowed' => true, 'message' => ''];
                    } else {
                        return [
                            'allowed' => false,
                            'message' => 'You can only edit legacy items that you created.'
                        ];
                    }
                }

                // Site Inventory Clerks can edit ANY asset during verification (to correct Warehouseman errors)
                if ($userRole === 'Site Inventory Clerk') {
                    return [
                        'allowed' => true,
                        'message' => '',
                        'correction_role' => true // Flag that this is a correction by verifier
                    ];
                }

                // Procurement Officers can edit during these stages
                if ($userRole === 'Procurement Officer') {
                    return ['allowed' => true, 'message' => ''];
                }

                break;

            case 'pending_authorization':
                // Site Inventory Clerk can still correct during authorization stage (before final approval)
                if ($userRole === 'Site Inventory Clerk') {
                    return [
                        'allowed' => true,
                        'message' => '',
                        'correction_role' => true
                    ];
                }

                // Project Managers can edit during authorization review
                if ($userRole === 'Project Manager') {
                    return [
                        'allowed' => true,
                        'message' => '',
                        'correction_role' => true
                    ];
                }

                // Procurement Officers can still edit
                if ($userRole === 'Procurement Officer') {
                    return ['allowed' => true, 'message' => ''];
                }

                break;

            case 'approved':
            case 'authorized':
                // Only Asset Director and System Admin can edit approved assets
                return [
                    'allowed' => false,
                    'message' => 'This item has been approved and cannot be edited. Please contact the Asset Director if changes are needed, or submit a change request through the system.'
                ];

            default:
                // Unknown status - be restrictive
                return [
                    'allowed' => false,
                    'message' => 'Item has unknown status. Please contact system administrator.'
                ];
        }

        // Default deny for roles not explicitly allowed
        return [
            'allowed' => false,
            'message' => 'Your role (' . $userRole . ') does not have permission to edit items in this workflow stage (' . $workflowStatus . ').'
        ];
    }
    
    /**
     * Generate role-specific statistics for dashboard cards
     */
    private function generateRoleBasedStats($userRole, $assets) {
        $stats = [];
        
        // Calculate basic statistics from assets
        $totalAssets = count($assets);
        $availableAssets = 0;
        $inUseAssets = 0;
        $maintenanceAssets = 0;
        $lowStockItems = 0;
        $totalValue = 0;
        $pendingVerification = 0;
        $pendingAuthorization = 0;
        $approved = 0;
        $projectsWithAssets = [];
        
        foreach ($assets as $asset) {
            // Basic status counts
            switch ($asset['status'] ?? '') {
                case 'available':
                    $availableAssets++;
                    break;
                case 'in_use':
                case 'borrowed':
                    $inUseAssets++;
                    break;
                case 'under_maintenance':
                    $maintenanceAssets++;
                    break;
            }
            
            // Workflow status counts
            switch ($asset['workflow_status'] ?? '') {
                case 'pending_verification':
                    $pendingVerification++;
                    break;
                case 'pending_authorization':
                    $pendingAuthorization++;
                    break;
                case 'approved':
                    $approved++;
                    break;
            }
            
            // Value calculations
            if (!empty($asset['acquisition_cost'])) {
                $totalValue += floatval($asset['acquisition_cost']);
            }
            
            // Low stock check (for consumables with quantity < 10)
            if (($asset['quantity'] ?? 0) < 10 && ($asset['available_quantity'] ?? 0) < 5) {
                $lowStockItems++;
            }
            
            // Track projects
            if (!empty($asset['project_id'])) {
                $projectsWithAssets[$asset['project_id']] = true;
            }
        }
        
        $utilizationRate = $totalAssets > 0 ? round(($inUseAssets / $totalAssets) * 100, 1) : 0;
        $avgAssetValue = $totalAssets > 0 ? $totalValue / $totalAssets : 0;
        $activeProjects = count($projectsWithAssets);
        
        // Role-specific statistics
        switch ($userRole) {
            case 'Project Manager':
                $stats = [
                    'total_project_assets' => $totalAssets,
                    'available_assets' => $availableAssets,
                    'utilization_rate' => $utilizationRate,
                    'assets_in_use' => $inUseAssets,
                    'low_stock_alerts' => $lowStockItems,
                    'maintenance_pending' => $maintenanceAssets,
                    'pending_authorization' => $pendingAuthorization,
                    'approved_assets' => $approved
                ];
                break;
                
            case 'Site Inventory Clerk':
                $stats = [
                    'total_inventory_items' => $totalAssets,
                    'total_consumable_units' => array_sum(array_column($assets, 'available_quantity')),
                    'available_for_use' => $availableAssets,
                    'pending_verification' => $pendingVerification,
                    'tools_on_loan' => $inUseAssets,
                    'items_in_transit' => $maintenanceAssets, // Could be refined
                    'today_receipts' => 0, // Would need today's date filter
                    'reorder_alerts' => $lowStockItems
                ];
                break;
                
            case 'Warehouseman':
                $stats = [
                    'warehouse_inventory' => $totalAssets,
                    'available_stock' => $availableAssets,
                    'tools_on_loan' => $inUseAssets,
                    'items_in_transit' => $maintenanceAssets,
                    'today_receipts' => 0, // Would need today's date filter
                    'reorder_alerts' => $lowStockItems,
                    'pending_verification' => $pendingVerification,
                    'ready_for_issue' => $availableAssets
                ];
                break;
                
            case 'System Admin':
            case 'Asset Director':
                $stats = [
                    'total_system_assets' => $totalAssets,
                    'active_projects' => $activeProjects,
                    'total_asset_value' => $totalValue,
                    'avg_asset_value' => $avgAssetValue,
                    'workflow_health' => round((($approved / max($totalAssets, 1)) * 100), 1),
                    'pending_verification' => $pendingVerification,
                    'pending_authorization' => $pendingAuthorization,
                    'system_alerts' => $lowStockItems + $maintenanceAssets,
                    'data_quality_score' => 85 // Could be calculated from completeness
                ];
                break;
                
            default:
                // Default minimal stats for other roles
                $stats = [
                    'total_assets' => $totalAssets,
                    'available_assets' => $availableAssets,
                    'in_use_assets' => $inUseAssets,
                    'maintenance_items' => $maintenanceAssets
                ];
                break;
        }
        
        return $stats;
    }
}
?>
