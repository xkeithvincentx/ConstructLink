<?php
/**
 * ConstructLink™ API Controller
 * Handles API endpoints for AJAX requests and mobile app
 */

class ApiController {
    private $auth;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        
        // Set JSON header for all API responses
        header('Content-Type: application/json');
        
        // Enable CORS for mobile app (configure domains as needed)
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Handle API routing based on the action parameter
     */
    public function index() {
        // Get the full route to determine the API endpoint
        $route = $_GET['route'] ?? '';
        $routeParts = explode('/', trim($route, '/'));
        
        // Remove 'api' from the route parts
        array_shift($routeParts);
        
        if (empty($routeParts)) {
            $this->apiError('Invalid API endpoint', 400);
            return;
        }
        
        $endpoint = implode('/', $routeParts);
        
        switch ($endpoint) {
            case 'dashboard/stats':
                $this->dashboardStats();
                break;
            case 'validate-qr':
                $this->validateQR();
                break;
            case 'assets/search':
                $this->searchAssets();
                break;
            case 'assets/status':
                $this->updateAssetStatus();
                break;
            case 'transfers/cancel':
                $this->cancelTransfer();
                break;
            case 'projects/delete':
                $this->deleteProject();
                break;
            case 'projects/toggle-status':
                $this->toggleProjectStatus();
                break;
            case 'projects/assets':
                $this->getProjectAssets();
                break;
            case 'categories/delete':
                $this->deleteCategory();
                break;
            case 'categories/assets':
                $this->getCategoryAssets();
                break;
            case 'makers/delete':
                $this->deleteMaker();
                break;
            case 'makers/search':
                $this->searchMakers();
                break;
            case 'notifications':
                $this->getNotifications();
                break;
            case 'notifications/mark-read':
                $this->markNotificationAsRead();
                break;
            case 'assets/disciplines':
                $this->assetDisciplines();
                break;
            case 'assets/validate-brand':
                $this->validateBrand();
                break;
            case 'assets/suggest-brand':
                $this->suggestBrand();
                break;
            case 'intelligent-naming':
                $this->intelligentNaming();
                break;
            default:
                $this->apiError('API endpoint not found', 404);
                break;
        }
    }
    
    /**
     * Handle dashboard action (for backward compatibility)
     */
    public function dashboard() {
        $this->dashboardStats();
    }
    
    /**
     * Send API error response
     */
    private function apiError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Validate QR code
     */
    public function validateQR() {
        try {
            $qrData = $_GET['data'] ?? '';
            
            if (empty($qrData)) {
                echo json_encode([
                    'valid' => false,
                    'message' => 'QR data is required',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                return;
            }
            
            $secureLink = SecureLink::getInstance();
            $result = $secureLink->validateQR($qrData);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("QR validation API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'valid' => false,
                'message' => 'QR validation failed',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Search assets
     */
    public function searchAssets() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        try {
            $query = $_GET['q'] ?? '';
            $limit = min((int)($_GET['limit'] ?? 20), 100); // Max 100 results
            
            if (strlen($query) < 2) {
                echo json_encode([
                    'success' => true,
                    'results' => [],
                    'message' => 'Query too short'
                ]);
                return;
            }
            
            $assetModel = new AssetModel();
            $filters = [];
            
            // Apply role-based filtering
            $user = $this->auth->getCurrentUser();
            if (!$this->auth->hasPermission('view_all_assets')) {
                // Limit to user's projects (simplified implementation)
                // In real implementation, you'd have user-project relationships
            }
            
            $results = $assetModel->searchAssets($query, $filters, $limit);
            
            $formattedResults = [];
            foreach ($results as $asset) {
                $formattedResults[] = [
                    'id' => $asset['id'],
                    'ref' => $asset['ref'],
                    'name' => $asset['name'],
                    'category' => $asset['category_name'],
                    'project' => $asset['project_name'],
                    'maker' => $asset['maker_name'],
                    'status' => $asset['status'],
                    'status_label' => ucfirst(str_replace('_', ' ', $asset['status'])),
                    'url' => '?route=assets/view&id=' . $asset['id']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'results' => $formattedResults,
                'count' => count($formattedResults)
            ]);
            
        } catch (Exception $e) {
            error_log("Asset search API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Search failed'
            ]);
        }
    }
    
    /**
     * Update asset status
     */
    public function updateAssetStatus() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        // Check permissions
        if (!$this->auth->hasPermission('edit_assets')) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $assetId = $input['asset_id'] ?? 0;
            $newStatus = $input['status'] ?? '';
            $reason = $input['reason'] ?? '';
            
            if (!$assetId || !$newStatus) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Asset ID and status are required'
                ]);
                return;
            }
            
            $assetModel = new AssetModel();
            $result = $assetModel->updateStatus($assetId, $newStatus, $reason);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Asset status update API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Status update failed'
            ]);
        }
    }
    
    /**
     * Get dashboard statistics
     */
    public function dashboardStats() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            $userRole = $user['role_name'];
            
            $stats = [];
            
            // Get basic asset statistics
            $assetModel = new AssetModel();
            $stats['assets'] = $assetModel->getAssetStats();
            
            // Role-specific statistics
            if (in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
                try {
                    $withdrawalModel = new WithdrawalModel();
                    $stats['withdrawals'] = $withdrawalModel->getWithdrawalStats();
                    
                    // Get overdue withdrawals
                    $stats['overdue']['withdrawals'] = count($withdrawalModel->getOverdueWithdrawals());
                } catch (Exception $e) {
                    error_log("Withdrawal stats error: " . $e->getMessage());
                    $stats['withdrawals'] = ['total' => 0, 'pending' => 0, 'released' => 0, 'returned' => 0];
                    $stats['overdue']['withdrawals'] = 0;
                }
                
                // Get delivery tracking statistics
                try {
                    if (class_exists('ProcurementOrderModel')) {
                        $procurementOrderModel = new ProcurementOrderModel();
                        
                        // Get orders ready for delivery scheduling
                        $readyForDelivery = $procurementOrderModel->getOrdersReadyForDelivery();
                        $stats['delivery']['ready_for_delivery'] = count($readyForDelivery);
                        
                        // Get orders awaiting receipt
                        $awaitingReceipt = $procurementOrderModel->getOrdersForReceipt();
                        $stats['delivery']['awaiting_receipt'] = count($awaitingReceipt);
                        
                        // Get delivery alerts (overdue, discrepancies, etc.)
                        if (method_exists($procurementOrderModel, 'getOrdersWithDeliveryAlerts')) {
                            $deliveryAlerts = $procurementOrderModel->getOrdersWithDeliveryAlerts($userRole, $user['id']);
                            $stats['delivery']['alerts'] = count($deliveryAlerts);
                        } else {
                            $stats['delivery']['alerts'] = 0;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Delivery stats error: " . $e->getMessage());
                    $stats['delivery'] = ['ready_for_delivery' => 0, 'awaiting_receipt' => 0, 'alerts' => 0];
                }
                
                try {
                    if (class_exists('TransferModel')) {
                        $transferModel = new TransferModel();
                        if (method_exists($transferModel, 'getTransferStats')) {
                            $stats['transfers'] = $transferModel->getTransferStats();
                        }
                    }
                } catch (Exception $e) {
                    error_log("Transfer stats error: " . $e->getMessage());
                }
                
                try {
                    if (class_exists('MaintenanceModel')) {
                        $maintenanceModel = new MaintenanceModel();
                        if (method_exists($maintenanceModel, 'getMaintenanceStats')) {
                            $stats['maintenance'] = $maintenanceModel->getMaintenanceStats();
                        }
                        if (method_exists($maintenanceModel, 'getOverdueMaintenance')) {
                            $stats['overdue']['maintenance'] = count($maintenanceModel->getOverdueMaintenance());
                        }
                    }
                } catch (Exception $e) {
                    error_log("Maintenance stats error: " . $e->getMessage());
                }
                
                try {
                    if (class_exists('IncidentModel')) {
                        $incidentModel = new IncidentModel();
                        if (method_exists($incidentModel, 'getIncidentStats')) {
                            $stats['incidents'] = $incidentModel->getIncidentStats();
                        }
                        if (method_exists($incidentModel, 'getOpenIncidents')) {
                            $stats['overdue']['incidents'] = count($incidentModel->getOpenIncidents());
                        }
                    }
                } catch (Exception $e) {
                    error_log("Incident stats error: " . $e->getMessage());
                }
            }
            
            if ($userRole === 'Warehouseman') {
                try {
                    if (class_exists('BorrowedToolModel')) {
                        $borrowedToolModel = new BorrowedToolModel();
                        if (method_exists($borrowedToolModel, 'getBorrowedToolStats')) {
                            $stats['borrowed_tools'] = $borrowedToolModel->getBorrowedToolStats();
                        }
                        if (method_exists($borrowedToolModel, 'getOverdueBorrowedTools')) {
                            $stats['overdue_borrowed'] = count($borrowedToolModel->getOverdueBorrowedTools());
                        }
                    }
                } catch (Exception $e) {
                    error_log("Borrowed tools stats error: " . $e->getMessage());
                }
                
                // Get delivery tracking statistics for warehouseman
                try {
                    if (class_exists('ProcurementOrderModel')) {
                        $procurementOrderModel = new ProcurementOrderModel();
                        
                        // Get orders awaiting receipt
                        $awaitingReceipt = $procurementOrderModel->getOrdersForReceipt();
                        $stats['delivery']['awaiting_receipt'] = count($awaitingReceipt);
                        
                        // Get delivery alerts
                        if (method_exists($procurementOrderModel, 'getOrdersWithDeliveryAlerts')) {
                            $deliveryAlerts = $procurementOrderModel->getOrdersWithDeliveryAlerts($userRole, $user['id']);
                            $stats['delivery']['alerts'] = count($deliveryAlerts);
                        } else {
                            $stats['delivery']['alerts'] = 0;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Delivery stats error for warehouseman: " . $e->getMessage());
                    $stats['delivery'] = ['awaiting_receipt' => 0, 'alerts' => 0];
                }
            }
            
            // Get delivery tracking statistics for Procurement Officers
            if ($userRole === 'Procurement Officer') {
                try {
                    if (class_exists('ProcurementOrderModel')) {
                        $procurementOrderModel = new ProcurementOrderModel();
                        
                        // Get orders ready for delivery scheduling
                        $readyForDelivery = $procurementOrderModel->getOrdersReadyForDelivery();
                        $stats['delivery']['ready_for_delivery'] = count($readyForDelivery);
                        
                        // Get orders awaiting receipt
                        $awaitingReceipt = $procurementOrderModel->getOrdersForReceipt();
                        $stats['delivery']['awaiting_receipt'] = count($awaitingReceipt);
                        
                        // Get delivery alerts
                        if (method_exists($procurementOrderModel, 'getOrdersWithDeliveryAlerts')) {
                            $deliveryAlerts = $procurementOrderModel->getOrdersWithDeliveryAlerts($userRole, $user['id']);
                            $stats['delivery']['alerts'] = count($deliveryAlerts);
                        } else {
                            $stats['delivery']['alerts'] = 0;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Delivery stats error for procurement officer: " . $e->getMessage());
                    $stats['delivery'] = ['ready_for_delivery' => 0, 'awaiting_receipt' => 0, 'alerts' => 0];
                }
            }
            
            // Get delivery tracking statistics for Project Managers and Site Inventory Clerks
            if (in_array($userRole, ['Project Manager', 'Site Inventory Clerk'])) {
                try {
                    if (class_exists('ProcurementOrderModel')) {
                        $procurementOrderModel = new ProcurementOrderModel();
                        
                        // Get orders awaiting receipt
                        $awaitingReceipt = $procurementOrderModel->getOrdersForReceipt();
                        $stats['delivery']['awaiting_receipt'] = count($awaitingReceipt);
                        
                        // Get delivery alerts
                        if (method_exists($procurementOrderModel, 'getOrdersWithDeliveryAlerts')) {
                            $deliveryAlerts = $procurementOrderModel->getOrdersWithDeliveryAlerts($userRole, $user['id']);
                            $stats['delivery']['alerts'] = count($deliveryAlerts);
                        } else {
                            $stats['delivery']['alerts'] = 0;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Delivery stats error for {$userRole}: " . $e->getMessage());
                    $stats['delivery'] = ['awaiting_receipt' => 0, 'alerts' => 0];
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $stats,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("Dashboard stats API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load statistics'
            ]);
        }
    }
    
    /**
     * Get asset details by ID or QR scan
     */
    public function getAsset() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        try {
            $assetId = $_GET['id'] ?? 0;
            $assetRef = $_GET['ref'] ?? '';
            
            $assetModel = new AssetModel();
            
            if ($assetId) {
                $asset = $assetModel->getAssetWithDetails($assetId);
            } elseif ($assetRef) {
                $asset = $assetModel->findFirst(['ref' => $assetRef]);
                if ($asset) {
                    $asset = $assetModel->getAssetWithDetails($asset['id']);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Asset ID or reference required'
                ]);
                return;
            }
            
            if (!$asset) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Asset not found'
                ]);
                return;
            }
            
            // Format asset data for API response
            $formattedAsset = [
                'id' => $asset['id'],
                'ref' => $asset['ref'],
                'name' => $asset['name'],
                'description' => $asset['description'],
                'category' => [
                    'id' => $asset['category_id'],
                    'name' => $asset['category_name'],
                    'is_consumable' => (bool)$asset['is_consumable']
                ],
                'project' => [
                    'id' => $asset['project_id'],
                    'name' => $asset['project_name'],
                    'location' => $asset['project_location']
                ],
                'maker' => [
                    'id' => $asset['maker_id'],
                    'name' => $asset['maker_name']
                ],
                'vendor' => [
                    'id' => $asset['vendor_id'],
                    'name' => $asset['vendor_name']
                ],
                'model' => $asset['model'],
                'serial_number' => $asset['serial_number'],
                'status' => $asset['status'],
                'status_label' => ucfirst(str_replace('_', ' ', $asset['status'])),
                'acquired_date' => $asset['acquired_date'],
                'acquisition_cost' => $asset['acquisition_cost'],
                'is_client_supplied' => (bool)$asset['is_client_supplied'],
                'qr_code_path' => $asset['qr_code_path'],
                'created_at' => $asset['created_at']
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $formattedAsset
            ]);
            
        } catch (Exception $e) {
            error_log("Get asset API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load asset'
            ]);
        }
    }
    
    /**
     * Create withdrawal request
     */
    public function createWithdrawal() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $data = [
                'asset_id' => $input['asset_id'] ?? 0,
                'project_id' => $input['project_id'] ?? 0,
                'purpose' => $input['purpose'] ?? '',
                'receiver_name' => $input['receiver_name'] ?? '',
                'receiver_position' => $input['receiver_position'] ?? '',
                'expected_return' => $input['expected_return'] ?? null,
                'withdrawn_by' => $_SESSION['user_id']
            ];
            
            $withdrawalModel = new WithdrawalModel();
            $result = $withdrawalModel->createWithdrawal($data);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Create withdrawal API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to create withdrawal request'
            ]);
        }
    }
    
    /**
     * Get user's recent activities
     */
    public function getRecentActivities() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        try {
            $limit = min((int)($_GET['limit'] ?? 10), 50);
            $userId = $_SESSION['user_id'];
            
            $userModel = new UserModel();
            $activities = $userModel->getUserActivity($userId, $limit);
            
            $formattedActivities = [];
            foreach ($activities as $activity) {
                $formattedActivities[] = [
                    'action' => $activity['action'],
                    'table' => $activity['table_name'],
                    'record_id' => $activity['record_id'],
                    'description' => $this->formatActivityDescription($activity),
                    'timestamp' => $activity['created_at'],
                    'ip_address' => $activity['ip_address']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $formattedActivities
            ]);
            
        } catch (Exception $e) {
            error_log("Recent activities API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load activities'
            ]);
        }
    }
    
    /**
     * Get filter options for forms
     */
    public function getFilterOptions() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        try {
            $type = $_GET['type'] ?? '';
            
            $options = [];
            
            switch ($type) {
                case 'categories':
                    $categoryModel = new CategoryModel();
                    $categories = $categoryModel->getActiveCategories();
                    foreach ($categories as $category) {
                        $options[] = [
                            'id' => $category['id'],
                            'name' => $category['name'],
                            'code' => $category['code'],
                            'is_consumable' => (bool)$category['is_consumable']
                        ];
                    }
                    break;
                    
                case 'projects':
                    $projectModel = new ProjectModel();
                    $projects = $projectModel->getActiveProjects();
                    foreach ($projects as $project) {
                        $options[] = [
                            'id' => $project['id'],
                            'name' => $project['name'],
                            'code' => $project['code'],
                            'location' => $project['location']
                        ];
                    }
                    break;
                    
                case 'makers':
                    $makerModel = new MakerModel();
                    $makers = $makerModel->getActiveMakers();
                    foreach ($makers as $maker) {
                        $options[] = [
                            'id' => $maker['id'],
                            'name' => $maker['name'],
                            'code' => $maker['code']
                        ];
                    }
                    break;
                    
                case 'vendors':
                    $vendorModel = new VendorModel();
                    $vendors = $vendorModel->getActiveVendors();
                    foreach ($vendors as $vendor) {
                        $options[] = [
                            'id' => $vendor['id'],
                            'name' => $vendor['name'],
                            'code' => $vendor['code']
                        ];
                    }
                    break;
                    
                case 'users':
                    if ($this->auth->hasPermission('manage_users')) {
                        $userModel = new UserModel();
                        $users = $userModel->getUsersForSelect();
                        foreach ($users as $user) {
                            $options[] = [
                                'id' => $user['id'],
                                'name' => $user['full_name'],
                                'username' => $user['username']
                            ];
                        }
                    }
                    break;
                    
                default:
                    echo json_encode([
                        'success' => false,
                        'error' => 'Invalid filter type'
                    ]);
                    return;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $options
            ]);
            
        } catch (Exception $e) {
            error_log("Filter options API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load filter options'
            ]);
        }
    }
    
    /**
     * Upload file (for incident reports, maintenance docs, etc.)
     */
    public function uploadFile() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        try {
            if (!isset($_FILES['file'])) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No file uploaded'
                ]);
                return;
            }
            
            $file = $_FILES['file'];
            $uploadType = $_POST['type'] ?? 'general'; // incident, maintenance, etc.
            
            // Validate file
            $validator = new Validator([]);
            $validator->file('file', ALLOWED_FILE_TYPES, UPLOAD_MAX_SIZE);
            
            if ($validator->fails()) {
                echo json_encode([
                    'success' => false,
                    'error' => implode(', ', $validator->getAllErrors())
                ]);
                return;
            }
            
            // Generate unique filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $uploadPath = UPLOAD_PATH . $uploadType . '/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $fullPath = $uploadPath . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                echo json_encode([
                    'success' => true,
                    'filename' => $filename,
                    'path' => '/uploads/' . $uploadType . '/' . $filename,
                    'size' => $file['size'],
                    'type' => $file['type']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to upload file'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("File upload API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Upload failed'
            ]);
        }
    }
    
    /**
     * Format activity description for display
     */
    private function formatActivityDescription($activity) {
        $action = ucfirst(str_replace('_', ' ', $activity['action']));
        $table = ucfirst(str_replace('_', ' ', $activity['table_name']));
        
        return "{$action} {$table} #{$activity['record_id']}";
    }
    
    /**
     * Cancel transfer request
     */
    public function cancelTransfer() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $transferId = $input['transfer_id'] ?? 0;
            $reason = $input['reason'] ?? 'Canceled via API';
            
            if (!$transferId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Transfer ID is required'
                ]);
                return;
            }
            
            $transferModel = new TransferModel();
            
            // Get transfer details to check permissions
            $transfer = $transferModel->getTransferWithDetails($transferId);
            if (!$transfer) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Transfer not found'
                ]);
                return;
            }
            
            // Check if user can cancel this transfer
            $user = $this->auth->getCurrentUser();
            if (!$this->auth->hasRole(['System Admin']) && $_SESSION['user_id'] != $transfer['initiated_by']) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Permission denied'
                ]);
                return;
            }
            
            // Check if transfer can be canceled
            if ($transfer['status'] !== 'pending') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Only pending transfers can be canceled'
                ]);
                return;
            }
            
            $result = $transferModel->cancelTransfer($transferId, $reason);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Cancel transfer API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to cancel transfer'
            ]);
        }
    }
    
    /**
     * Delete project via API
     */
    public function deleteProject() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        // Check permissions
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $projectId = $input['project_id'] ?? 0;
            
            if (!$projectId) {
                echo json_encode(['success' => false, 'message' => 'Project ID is required']);
                return;
            }
            
            $projectModel = new ProjectModel();
            $result = $projectModel->deleteProject($projectId);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Delete project API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to delete project']);
        }
    }
    
    /**
     * Toggle project status via API
     */
    public function toggleProjectStatus() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        // Check permissions
        if (!$this->auth->hasRole(['System Admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $projectId = $input['project_id'] ?? 0;
            
            if (!$projectId) {
                echo json_encode(['success' => false, 'message' => 'Project ID is required']);
                return;
            }
            
            $projectModel = new ProjectModel();
            $result = $projectModel->toggleProjectStatus($projectId);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Toggle project status API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to toggle project status']);
        }
    }
    
    /**
     * Get project assets via API
     */
    public function getProjectAssets() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        try {
            $projectId = $_GET['project_id'] ?? 0;
            
            if (!$projectId) {
                echo json_encode(['success' => false, 'message' => 'Project ID is required']);
                return;
            }
            
            $assetModel = new AssetModel();
            $assets = $assetModel->getAssetsByProject($projectId);
            
            echo json_encode([
                'success' => true,
                'assets' => $assets
            ]);
            
        } catch (Exception $e) {
            error_log("Get project assets API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load project assets']);
        }
    }
    
    /**
     * Delete category via API
     */
    public function deleteCategory() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Asset Director'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $categoryId = $input['category_id'] ?? 0;
            
            if (!$categoryId) {
                echo json_encode(['success' => false, 'message' => 'Category ID is required']);
                return;
            }
            
            $categoryModel = new CategoryModel();
            $result = $categoryModel->deleteCategory($categoryId);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Delete category API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
        }
    }
    
    /**
     * Get category assets via API
     */
    public function getCategoryAssets() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        try {
            $categoryId = $_GET['category_id'] ?? 0;
            
            if (!$categoryId) {
                echo json_encode(['success' => false, 'message' => 'Category ID is required']);
                return;
            }
            
            $assetModel = new AssetModel();
            $assets = $assetModel->getAssetsByCategory($categoryId);
            
            echo json_encode([
                'success' => true,
                'assets' => $assets
            ]);
            
        } catch (Exception $e) {
            error_log("Get category assets API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load category assets']);
        }
    }
    
    /**
     * Delete maker via API
     */
    public function deleteMaker() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        // Check permissions
        if (!$this->auth->hasRole(['System Admin', 'Procurement Officer'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $makerId = $input['maker_id'] ?? 0;
            
            if (!$makerId) {
                echo json_encode(['success' => false, 'message' => 'Maker ID is required']);
                return;
            }
            
            $makerModel = new MakerModel();
            $result = $makerModel->deleteMaker($makerId);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Delete maker API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to delete manufacturer']);
        }
    }
    
    /**
     * Search makers via API
     */
    public function searchMakers() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        try {
            $query = $_GET['q'] ?? '';
            $limit = min((int)($_GET['limit'] ?? 20), 100);
            
            if (strlen($query) < 2) {
                echo json_encode([
                    'success' => true,
                    'makers' => [],
                    'message' => 'Query too short'
                ]);
                return;
            }
            
            $makerModel = new MakerModel();
            $makers = $makerModel->searchMakers($query, $limit);
            
            $formattedMakers = [];
            foreach ($makers as $maker) {
                $formattedMakers[] = [
                    'id' => $maker['id'],
                    'name' => $maker['name'],
                    'country' => $maker['country'],
                    'website' => $maker['website'],
                    'description' => $maker['description'],
                    'url' => '?route=makers/view&id=' . $maker['id']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'makers' => $formattedMakers,
                'count' => count($formattedMakers)
            ]);
            
        } catch (Exception $e) {
            error_log("Search makers API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to search manufacturers']);
        }
    }
    
    /**
     * Get user notifications
     */
    public function getNotifications() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }

        try {
            $user = $this->auth->getCurrentUser();
            $userRole = $user['role_name'];
            $userId = $user['id'];
            $limit = min((int)($_GET['limit'] ?? 10), 50);
            $offset = max((int)($_GET['offset'] ?? 0), 0);

            $notifications = [];
            $notificationModel = null;

            // Get database notifications first (if table exists)
            try {
                require_once APP_ROOT . '/models/NotificationModel.php';
                $notificationModel = new NotificationModel();
                $dbNotifications = $notificationModel->getUserNotifications($userId, $limit, $offset);

                foreach ($dbNotifications as $dbNotif) {
                    $notifications[] = [
                        'id' => $dbNotif['id'],
                        'type' => $dbNotif['type'],
                        'title' => $dbNotif['title'],
                        'message' => $dbNotif['message'],
                        'icon' => $this->getNotificationIcon($dbNotif['type']),
                        'url' => $dbNotif['url'] ?? '#',
                        'time' => $this->timeAgo($dbNotif['created_at']),
                        'unread' => !$dbNotif['is_read']
                    ];
                }
            } catch (Exception $e) {
                // Table might not exist yet - log and continue with system notifications only
                error_log("NotificationModel error (table may not exist): " . $e->getMessage());
            }
            
            // Get overdue withdrawals
            if (hasRole(['System Admin', 'Asset Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])) {
                try {
                    $withdrawalModel = new WithdrawalModel();
                    $overdueWithdrawals = $withdrawalModel->getOverdueWithdrawals();
                    
                    foreach ($overdueWithdrawals as $withdrawal) {
                        $notifications[] = [
                            'id' => 'withdrawal_' . $withdrawal['id'],
                            'type' => 'warning',
                            'title' => 'Overdue Withdrawal',
                            'message' => "Asset {$withdrawal['asset_ref']} is overdue for return",
                            'icon' => 'bi bi-exclamation-triangle',
                            'url' => '?route=withdrawals/view&id=' . $withdrawal['id'],
                            'time' => $this->timeAgo($withdrawal['expected_return']),
                            'unread' => true
                        ];
                    }
                } catch (Exception $e) {
                    error_log("Withdrawal notifications error: " . $e->getMessage());
                }
            }
            
            // Get overdue maintenance
            if (hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])) {
                try {
                    if (class_exists('MaintenanceModel')) {
                        $maintenanceModel = new MaintenanceModel();
                        if (method_exists($maintenanceModel, 'getOverdueMaintenance')) {
                            $overdueMaintenance = $maintenanceModel->getOverdueMaintenance();
                            
                            foreach ($overdueMaintenance as $maintenance) {
                                $notifications[] = [
                                    'id' => 'maintenance_' . $maintenance['id'],
                                    'type' => 'danger',
                                    'title' => 'Maintenance Due',
                                    'message' => "Asset {$maintenance['asset_ref']} requires maintenance",
                                    'icon' => 'bi bi-wrench',
                                    'url' => '?route=maintenance/view&id=' . $maintenance['id'],
                                    'time' => $this->timeAgo($maintenance['scheduled_date']),
                                    'unread' => true
                                ];
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("Maintenance notifications error: " . $e->getMessage());
                }
            }
            
            // Get open incidents
            if (hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])) {
                try {
                    if (class_exists('IncidentModel')) {
                        $incidentModel = new IncidentModel();
                        if (method_exists($incidentModel, 'getOpenIncidents')) {
                            $openIncidents = $incidentModel->getOpenIncidents();
                            
                            foreach ($openIncidents as $incident) {
                                $notifications[] = [
                                    'id' => 'incident_' . $incident['id'],
                                    'type' => 'info',
                                    'title' => 'Open Incident',
                                    'message' => "Incident #{$incident['id']}: {$incident['type']}",
                                    'icon' => 'bi bi-exclamation-circle',
                                    'url' => '?route=incidents/view&id=' . $incident['id'],
                                    'time' => $this->timeAgo($incident['reported_at']),
                                    'unread' => true
                                ];
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("Incident notifications error: " . $e->getMessage());
                }
            }
            
            // Get delivery alerts for relevant roles
            if (hasRole(['System Admin', 'Procurement Officer', 'Asset Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk'])) {
                try {
                    if (class_exists('ProcurementOrderModel')) {
                        $procurementModel = new ProcurementOrderModel();
                        
                        // Orders ready for delivery
                        if (hasRole(['System Admin', 'Procurement Officer', 'Asset Director'])) {
                            $readyForDelivery = $procurementModel->getOrdersReadyForDelivery();
                            foreach ($readyForDelivery as $order) {
                                $notifications[] = [
                                    'id' => 'delivery_ready_' . $order['id'],
                                    'type' => 'info',
                                    'title' => 'Ready for Delivery',
                                    'message' => "PO #{$order['po_number']} is ready for delivery scheduling",
                                    'icon' => 'bi bi-truck',
                                    'url' => '?route=procurement-orders/view&id=' . $order['id'],
                                    'time' => $this->timeAgo($order['approved_at']),
                                    'unread' => true
                                ];
                            }
                        }
                        
                        // Orders awaiting receipt
                        $awaitingReceipt = $procurementModel->getOrdersForReceipt();
                        foreach ($awaitingReceipt as $order) {
                            $notifications[] = [
                                'id' => 'receipt_pending_' . $order['id'],
                                'type' => 'warning',
                                'title' => 'Awaiting Receipt',
                                'message' => "PO #{$order['po_number']} delivery needs confirmation",
                                'icon' => 'bi bi-clipboard-check',
                                'url' => '?route=procurement-orders/view&id=' . $order['id'],
                                'time' => $this->timeAgo($order['delivery_date']),
                                'unread' => true
                            ];
                        }
                    }
                } catch (Exception $e) {
                    error_log("Delivery notifications error: " . $e->getMessage());
                }
            }
            
            // Sort by time (most recent first)
            usort($notifications, function($a, $b) {
                return strcmp($b['time'], $a['time']);
            });
            
            // Calculate total before pagination
            $totalCount = count($notifications);
            $unreadCount = count(array_filter($notifications, function($n) { return $n['unread']; }));

            // Add database unread count (if model exists)
            if ($notificationModel) {
                $unreadCount += $notificationModel->getUnreadCount($userId);
            }
            
            // Apply pagination
            $notifications = array_slice($notifications, $offset, $limit);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
                'total_count' => $totalCount,
                'returned_count' => count($notifications),
                'offset' => $offset,
                'limit' => $limit
            ]);
            
        } catch (Exception $e) {
            error_log("Get notifications API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load notifications'
            ]);
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationAsRead() {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $notificationId = $input['notification_id'] ?? '';
            
            if (!$notificationId) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Notification ID is required'
                ]);
                return;
            }
            
            // Mark notification as read in database
            require_once APP_ROOT . '/models/NotificationModel.php';
            $notificationModel = new NotificationModel();

            $result = $notificationModel->markAsRead($notificationId);

            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Notification marked as read' : 'Failed to mark notification as read'
            ]);
            
        } catch (Exception $e) {
            error_log("Mark notification read API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to mark notification as read'
            ]);
        }
    }
    
    /**
     * Helper function to format time ago
     */
    private function timeAgo($datetime) {
        if (empty($datetime)) return 'N/A';

        $time = time() - strtotime($datetime);

        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time / 60) . ' minutes ago';
        if ($time < 86400) return floor($time / 3600) . ' hours ago';
        if ($time < 2592000) return floor($time / 86400) . ' days ago';

        return date('M j, Y', strtotime($datetime));
    }

    /**
     * Get notification icon based on type
     */
    private function getNotificationIcon($type) {
        $icons = [
            'info' => 'bi bi-info-circle',
            'success' => 'bi bi-check-circle',
            'warning' => 'bi bi-exclamation-triangle',
            'danger' => 'bi bi-x-circle',
            'transfer' => 'bi bi-arrow-left-right',
            'approval' => 'bi bi-check-circle-fill',
            'workflow' => 'bi bi-diagram-3'
        ];

        return $icons[$type] ?? 'bi bi-bell';
    }
    
    /**
     * Admin Brands API - handles brand CRUD operations
     */
    public function adminBrands() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            return;
        }
        
        $user = $this->auth->getCurrentUser();
        if (!in_array($user['role_name'], ['System Admin', 'Asset Director'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    $this->handleBrandsGet($db);
                    break;
                case 'POST':
                    $this->handleBrandsPost($db);
                    break;
                case 'PUT':
                    $this->handleBrandsPut($db);
                    break;
                case 'DELETE':
                    $this->handleBrandsDelete($db);
                    break;
                default:
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
        } catch (Exception $e) {
            error_log("Brands API Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }
    
    private function handleBrandsGet($db) {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(100, max(10, intval($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        $tier = $_GET['tier'] ?? '';
        
        try {
            // Build query with filters
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if (!empty($search)) {
                $whereClause .= " AND (official_name LIKE ? OR country LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($tier)) {
                $whereClause .= " AND quality_tier = ?";
                $params[] = $tier;
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) FROM asset_brands $whereClause";
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            
            // Get brands with usage count
            $sql = "
                SELECT 
                    b.id,
                    b.official_name,
                    b.variations,
                    b.country,
                    b.website,
                    b.quality_tier,
                    b.is_active,
                    b.created_at,
                    COALESCE(asset_count.count, 0) as assets_count
                FROM asset_brands b
                LEFT JOIN (
                    SELECT brand_id, COUNT(*) as count
                    FROM assets
                    GROUP BY brand_id
                ) asset_count ON b.id = asset_count.brand_id
                $whereClause
                ORDER BY b.official_name ASC
                LIMIT $limit OFFSET $offset
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the data
            $formattedBrands = array_map(function($brand) {
                return [
                    'id' => (int)$brand['id'],
                    'official_name' => $brand['official_name'],
                    'variations' => json_decode($brand['variations'] ?: '[]'),
                    'country' => $brand['country'],
                    'website' => $brand['website'],
                    'quality_tier' => $brand['quality_tier'],
                    'is_verified' => true, // All brands are verified in this simplified schema
                    'is_active' => (bool)$brand['is_active'],
                    'assets_count' => (int)$brand['assets_count'],
                    'created_at' => $brand['created_at']
                ];
            }, $brands);
            
            $totalPages = ceil($totalItems / $limit);
            
            echo json_encode([
                'success' => true,
                'data' => $formattedBrands,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => (int)$totalItems,
                    'items_per_page' => $limit,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ]);
            
        } catch (PDOException $e) {
            error_log("Database error in brands GET: " . $e->getMessage());
            error_log("Brands SQL: " . $sql);
            error_log("Brands Params: " . json_encode($params));
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    private function handleBrandsPost($db) {
        // For now, return not implemented
        echo json_encode(['success' => false, 'message' => 'Brand creation not implemented in this interface']);
    }
    
    private function handleBrandsPut($db) {
        // For now, return not implemented
        echo json_encode(['success' => false, 'message' => 'Brand editing not implemented in this interface']);
    }
    
    private function handleBrandsDelete($db) {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Brand ID is required']);
            return;
        }
        
        try {
            // Check if brand has assets
            $assetsCheckSql = "SELECT COUNT(*) FROM assets WHERE brand_id = ?";
            $assetsCheckStmt = $db->prepare($assetsCheckSql);
            $assetsCheckStmt->execute([$id]);
            
            if ($assetsCheckStmt->fetchColumn() > 0) {
                // Soft delete - just deactivate
                $deactivateSql = "UPDATE asset_brands SET is_active = 0 WHERE id = ?";
                $deactivateStmt = $db->prepare($deactivateSql);
                $deactivateStmt->execute([$id]);
                
                echo json_encode(['success' => true, 'message' => 'Brand deactivated (has associated assets)']);
            } else {
                // Hard delete - no assets
                $deleteSql = "DELETE FROM asset_brands WHERE id = ?";
                $deleteStmt = $db->prepare($deleteSql);
                $deleteStmt->execute([$id]);
                
                echo json_encode(['success' => true, 'message' => 'Brand deleted successfully']);
            }
            
        } catch (PDOException $e) {
            error_log("Database error in brands DELETE: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
    
    /**
     * Admin Disciplines API - handles discipline CRUD operations
     */
    public function adminDisciplines() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            return;
        }
        
        $user = $this->auth->getCurrentUser();
        if (!in_array($user['role_name'], ['System Admin', 'Asset Director'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    $this->handleDisciplinesGet($db);
                    break;
                case 'DELETE':
                    $this->handleDisciplinesDelete($db);
                    break;
                default:
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Method not allowed for disciplines']);
            }
        } catch (Exception $e) {
            error_log("Disciplines API Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }
    
    private function handleDisciplinesGet($db) {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(100, max(10, intval($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        
        try {
            // Build query with search
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if (!empty($search)) {
                $whereClause .= " AND (d.code LIKE ? OR d.name LIKE ? OR d.description LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) FROM asset_disciplines d $whereClause";
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            
            // Get disciplines with parent information and asset counts
            $sql = "
                SELECT 
                    d.id,
                    d.code,
                    d.iso_code,
                    d.name,
                    d.description,
                    d.parent_id,
                    p.name as parent_name,
                    d.display_order,
                    d.is_active,
                    d.created_at,
                    COALESCE(asset_count.count, 0) as assets_count
                FROM asset_disciplines d
                LEFT JOIN asset_disciplines p ON d.parent_id = p.id
                LEFT JOIN (
                    SELECT 
                        d_inner.id as discipline_id,
                        COUNT(DISTINCT a.id) as count
                    FROM asset_disciplines d_inner
                    LEFT JOIN assets a ON (
                        a.discipline_tags IS NOT NULL 
                        AND a.discipline_tags LIKE CONCAT('%', d_inner.iso_code, '%')
                    )
                    GROUP BY d_inner.id
                ) asset_count ON d.id = asset_count.discipline_id
                $whereClause
                ORDER BY d.parent_id ASC, d.display_order ASC, d.name ASC
                LIMIT $limit OFFSET $offset
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the data
            $formattedDisciplines = array_map(function($discipline) {
                return [
                    'id' => (int)$discipline['id'],
                    'code' => $discipline['code'],
                    'iso_code' => $discipline['iso_code'],
                    'name' => $discipline['name'],
                    'description' => $discipline['description'],
                    'parent_id' => $discipline['parent_id'] ? (int)$discipline['parent_id'] : null,
                    'parent_name' => $discipline['parent_name'],
                    'sort_order' => (int)$discipline['display_order'],
                    'is_active' => (bool)$discipline['is_active'],
                    'assets_count' => (int)$discipline['assets_count'],
                    'created_at' => $discipline['created_at']
                ];
            }, $disciplines);
            
            $totalPages = ceil($totalItems / $limit);
            
            echo json_encode([
                'success' => true,
                'data' => $formattedDisciplines,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => (int)$totalItems,
                    'items_per_page' => $limit,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ]);
            
        } catch (PDOException $e) {
            error_log("Database error in disciplines GET: " . $e->getMessage());
            error_log("Disciplines SQL: " . $sql);
            error_log("Disciplines Params: " . json_encode($params));
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    private function handleDisciplinesDelete($db) {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Discipline ID is required']);
            return;
        }
        
        try {
            // Check if discipline exists
            $checkSql = "SELECT id FROM asset_disciplines WHERE id = ?";
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->execute([$id]);
            
            if (!$checkStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Discipline not found']);
                return;
            }
            
            // Check if discipline has children
            $childrenCheckSql = "SELECT COUNT(*) FROM asset_disciplines WHERE parent_id = ?";
            $childrenCheckStmt = $db->prepare($childrenCheckSql);
            $childrenCheckStmt->execute([$id]);
            
            if ($childrenCheckStmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete discipline with child disciplines']);
                return;
            }
            
            // Check if discipline has assets
            $assetsCheckSql = "SELECT COUNT(*) FROM asset_discipline_mappings WHERE discipline_id = ?";
            $assetsCheckStmt = $db->prepare($assetsCheckSql);
            $assetsCheckStmt->execute([$id]);
            
            if ($assetsCheckStmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete discipline with associated assets']);
                return;
            }
            
            // Delete the discipline
            $deleteSql = "DELETE FROM asset_disciplines WHERE id = ?";
            $deleteStmt = $db->prepare($deleteSql);
            $deleteStmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Discipline deleted successfully']);
            
        } catch (PDOException $e) {
            error_log("Database error in disciplines DELETE: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
    
    /**
     * Asset Disciplines API - For asset creation forms
     */
    public function assetDisciplines() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            return;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            $action = $_GET['action'] ?? 'list';
            
            switch ($action) {
                case 'list':
                    // Get all disciplines with hierarchy
                    $sql = "SELECT d.id, d.code, d.name, d.description, 
                            d.parent_id, p.name as parent_name, d.display_order
                            FROM asset_disciplines d
                            LEFT JOIN asset_disciplines p ON d.parent_id = p.id
                            WHERE d.is_active = 1
                            ORDER BY COALESCE(p.display_order, d.display_order), d.display_order";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute();
                    $disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Organize by parent-child relationship
                    $organized = [];
                    $children = [];
                    
                    foreach ($disciplines as $discipline) {
                        if (empty($discipline['parent_id'])) {
                            $organized[$discipline['id']] = $discipline;
                            $organized[$discipline['id']]['children'] = [];
                        } else {
                            $children[] = $discipline;
                        }
                    }
                    
                    // Add children to parents
                    foreach ($children as $child) {
                        if (isset($organized[$child['parent_id']])) {
                            $organized[$child['parent_id']]['children'][] = $child;
                        }
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'data' => array_values($organized)
                    ]);
                    break;
                    
                case 'by_category':
                    $categoryId = (int)($_GET['category_id'] ?? 0);
                    
                    if (!$categoryId) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Category ID is required'
                        ]);
                        return;
                    }
                    
                    // Get disciplines based on category's asset types
                    $sql = "SELECT DISTINCT d.id, d.code, d.name, d.description,
                            COUNT(adm.id) as usage_count,
                            MAX(adm.primary_use) as has_primary_use
                            FROM categories c
                            JOIN asset_types at ON at.category = c.name
                            JOIN asset_discipline_mappings adm ON adm.asset_type_id = at.id
                            JOIN asset_disciplines d ON adm.discipline_id = d.id
                            WHERE c.id = ? AND d.is_active = 1
                            GROUP BY d.id, d.code, d.name, d.description
                            ORDER BY has_primary_use DESC, usage_count DESC, d.display_order";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$categoryId]);
                    $disciplines = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $disciplines
                    ]);
                    break;
                    
                default:
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid action'
                    ]);
                    break;
            }
            
        } catch (PDOException $e) {
            error_log("Asset disciplines API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Brand Validation API - For asset creation forms
     */
    public function validateBrand() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            return;
        }
        
        try {
            // Get parameters
            $brand = trim($_GET['brand'] ?? '');
            
            if (empty($brand)) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'original' => '',
                        'standardized' => null,
                        'brand_id' => null,
                        'valid' => false,
                        'message' => 'Brand name is required'
                    ]
                ]);
                return;
            }
            
            $db = Database::getInstance()->getConnection();
            
            // Try exact match first
            $sql = "SELECT id, official_name, quality_tier FROM asset_brands 
                    WHERE LOWER(official_name) = LOWER(?) LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$brand]);
            $exactMatch = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($exactMatch) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'original' => $brand,
                        'standardized' => $exactMatch['official_name'],
                        'brand_id' => (int)$exactMatch['id'],
                        'quality_tier' => $exactMatch['quality_tier'],
                        'confidence' => 1.0,
                        'valid' => true,
                        'has_correction' => $brand !== $exactMatch['official_name']
                    ]
                ]);
                return;
            }
            
            // Try fuzzy match for suggestions
            $sql = "SELECT id, official_name, quality_tier,
                    CASE
                        WHEN LOWER(official_name) LIKE LOWER(CONCAT('%', ?, '%')) THEN 80
                        WHEN LOWER(variations) LIKE LOWER(CONCAT('%', ?, '%')) THEN 70
                        ELSE 50
                    END as score
                    FROM asset_brands
                    WHERE (
                        LOWER(official_name) LIKE LOWER(CONCAT('%', ?, '%'))
                        OR LOWER(variations) LIKE LOWER(CONCAT('%', ?, '%'))
                    )
                    ORDER BY score DESC, official_name
                    LIMIT 5";
            
            $stmt = $db->prepare($sql);
            $searchTerm = $brand;
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true,
                'data' => [
                    'original' => $brand,
                    'standardized' => $brand,
                    'brand_id' => null,
                    'confidence' => 0.0,
                    'valid' => false,
                    'has_correction' => false
                ]
            ];
            
            if (!empty($suggestions)) {
                $response['data']['suggestions'] = array_map(function($suggestion) {
                    return [
                        'id' => (int)$suggestion['id'],
                        'name' => $suggestion['official_name'],
                        'quality_tier' => $suggestion['quality_tier'],
                        'score' => (int)$suggestion['score']
                    ];
                }, $suggestions);
            }
            
            // If no match found and this is from asset creation, auto-create notification
            if (empty($response['data']['brand_id']) && !empty($_GET['asset_context'])) {
                try {
                    $contextData = json_decode($_GET['asset_context'], true);
                    if ($contextData && !empty($contextData['asset_id'])) {
                        $this->createUnknownBrandNotification([
                            'brand_name' => $brand,
                            'asset_id' => $contextData['asset_id'],
                            'created_by' => $this->auth->getCurrentUser()['id'],
                            'category_context' => $contextData['category'] ?? '',
                            'project_context' => $contextData['project'] ?? '',
                            'notification_type' => 'unknown_brand'
                        ]);
                        
                        $response['data']['notification_created'] = true;
                    }
                } catch (Exception $e) {
                    // Fail silently for notifications
                    error_log("Failed to create auto-notification: " . $e->getMessage());
                }
            }
            
            echo json_encode($response);
            
        } catch (PDOException $e) {
            error_log("Brand validation API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Brand Suggestion API - For Warehouseman to suggest new brands
     */
    public function suggestBrand() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            return;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            $method = $_SERVER['REQUEST_METHOD'];
            $userId = $this->auth->getCurrentUser()['id'];
            
            switch ($method) {
                case 'POST':
                    // Create new brand suggestion
                    $data = json_decode(file_get_contents('php://input'), true);
                    
                    $requiredFields = ['suggested_name'];
                    foreach ($requiredFields as $field) {
                        if (empty($data[$field])) {
                            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                            return;
                        }
                    }
                    
                    // Check if suggestion already exists for this brand
                    $checkSql = "SELECT id FROM brand_suggestions WHERE suggested_name = ? AND status = 'pending'";
                    $checkStmt = $db->prepare($checkSql);
                    $checkStmt->execute([$data['suggested_name']]);
                    
                    if ($checkStmt->fetch()) {
                        echo json_encode(['success' => false, 'message' => 'Brand suggestion already exists and is pending review']);
                        return;
                    }
                    
                    // Insert suggestion
                    $sql = "INSERT INTO brand_suggestions 
                            (suggested_name, original_context, suggested_by, asset_id, category_context) 
                            VALUES (?, ?, ?, ?, ?)";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $data['suggested_name'],
                        $data['original_context'] ?? '',
                        $userId,
                        !empty($data['asset_id']) ? (int)$data['asset_id'] : null,
                        $data['category_context'] ?? ''
                    ]);
                    
                    $suggestionId = $db->lastInsertId();
                    
                    // Log the action
                    $this->logBrandWorkflowAction('brand_suggestion', $suggestionId, 'suggested', $userId);
                    
                    // Auto-create notification for Asset Director
                    if (!empty($data['asset_id'])) {
                        $this->createUnknownBrandNotification([
                            'brand_name' => $data['suggested_name'],
                            'asset_id' => $data['asset_id'],
                            'created_by' => $userId,
                            'category_context' => $data['category_context'] ?? '',
                            'notification_type' => 'brand_suggestion',
                            'related_suggestion_id' => $suggestionId
                        ]);
                    }
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Brand suggestion submitted successfully',
                        'suggestion_id' => $suggestionId
                    ]);
                    break;
                    
                case 'GET':
                    // Get user's brand suggestions
                    $sql = "SELECT bs.*, u.full_name as suggested_by_name, a.name as asset_name, a.ref as asset_ref
                            FROM brand_suggestions bs 
                            JOIN users u ON bs.suggested_by = u.id
                            LEFT JOIN assets a ON bs.asset_id = a.id
                            WHERE bs.suggested_by = ?
                            ORDER BY bs.created_at DESC";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$userId]);
                    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['success' => true, 'data' => $suggestions]);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                    break;
            }
            
        } catch (PDOException $e) {
            error_log("Brand suggestion API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Unknown Brand Notifications API - For Asset Directors
     */
    public function unknownBrandNotifications() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            return;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            $method = $_SERVER['REQUEST_METHOD'];
            $userId = $this->auth->getCurrentUser()['id'];
            
            switch ($method) {
                case 'GET':
                    // Get notifications for Asset Director
                    $sql = "SELECT ubn.*, u.full_name as created_by_name, 
                            ad.full_name as assigned_to_name,
                            bs.suggested_name as related_suggestion_name
                            FROM unknown_brand_notifications ubn
                            JOIN users u ON ubn.created_by = u.id
                            LEFT JOIN users ad ON ubn.assigned_to = ad.id
                            LEFT JOIN brand_suggestions bs ON ubn.related_suggestion_id = bs.id
                            WHERE ubn.status IN ('pending', 'in_review')
                            ORDER BY ubn.created_at DESC";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute();
                    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['success' => true, 'data' => $notifications]);
                    break;
                    
                case 'PUT':
                    // Update notification status
                    $data = json_decode(file_get_contents('php://input'), true);
                    $notificationId = $data['id'] ?? 0;
                    
                    if (!$notificationId) {
                        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
                        return;
                    }
                    
                    $allowedStatuses = ['pending', 'in_review', 'resolved', 'dismissed'];
                    if (!in_array($data['status'], $allowedStatuses)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid status']);
                        return;
                    }
                    
                    $sql = "UPDATE unknown_brand_notifications 
                            SET status = ?, assigned_to = ?, resolution_notes = ?, 
                                resolved_at = ?, updated_at = NOW()
                            WHERE id = ?";
                    
                    $resolvedAt = in_array($data['status'], ['resolved', 'dismissed']) ? date('Y-m-d H:i:s') : null;
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $data['status'],
                        $data['status'] === 'in_review' ? $userId : null,
                        $data['resolution_notes'] ?? '',
                        $resolvedAt,
                        $notificationId
                    ]);
                    
                    // Log the action
                    $this->logBrandWorkflowAction('unknown_brand_notification', $notificationId, $data['status'], $userId);
                    
                    echo json_encode(['success' => true, 'message' => 'Notification updated successfully']);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                    break;
            }
            
        } catch (PDOException $e) {
            error_log("Unknown brand notifications API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Brand Suggestions Admin API - For Asset Director to manage suggestions
     */
    public function brandSuggestions() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            return;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            $method = $_SERVER['REQUEST_METHOD'];
            $userId = $this->auth->getCurrentUser()['id'];
            
            switch ($method) {
                case 'GET':
                    // Get all pending suggestions for Asset Director review
                    $status = $_GET['status'] ?? 'pending';
                    $limit = min((int)($_GET['limit'] ?? 20), 100);
                    $offset = max((int)($_GET['offset'] ?? 0), 0);
                    
                    $sql = "SELECT bs.*, u.full_name as suggested_by_name, 
                            a.name as asset_name, a.ref as asset_ref,
                            r.full_name as reviewed_by_name,
                            ab.official_name as approved_brand_name
                            FROM brand_suggestions bs
                            JOIN users u ON bs.suggested_by = u.id
                            LEFT JOIN assets a ON bs.asset_id = a.id
                            LEFT JOIN users r ON bs.reviewed_by = r.id
                            LEFT JOIN asset_brands ab ON bs.approved_brand_id = ab.id
                            WHERE bs.status = ?
                            ORDER BY bs.created_at DESC
                            LIMIT ? OFFSET ?";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$status, $limit, $offset]);
                    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['success' => true, 'data' => $suggestions]);
                    break;
                    
                case 'PUT':
                    // Review and approve/reject suggestion
                    $data = json_decode(file_get_contents('php://input'), true);
                    $suggestionId = $data['id'] ?? 0;
                    
                    if (!$suggestionId) {
                        echo json_encode(['success' => false, 'message' => 'Suggestion ID required']);
                        return;
                    }
                    
                    $allowedStatuses = ['approved', 'rejected', 'merged'];
                    if (!in_array($data['status'], $allowedStatuses)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid status']);
                        return;
                    }
                    
                    $db->beginTransaction();
                    
                    try {
                        // Update suggestion
                        $sql = "UPDATE brand_suggestions 
                                SET status = ?, reviewed_by = ?, review_notes = ?, 
                                    approved_brand_id = ?, reviewed_at = NOW(), updated_at = NOW()
                                WHERE id = ?";
                        
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            $data['status'],
                            $userId,
                            $data['review_notes'] ?? '',
                            $data['approved_brand_id'] ?? null,
                            $suggestionId
                        ]);
                        
                        // If approved and creating new brand
                        if ($data['status'] === 'approved' && !empty($data['create_brand'])) {
                            $brandSql = "INSERT INTO asset_brands (official_name, quality_tier, is_active)
                                         VALUES (?, ?, 1)";
                            $brandStmt = $db->prepare($brandSql);
                            $brandStmt->execute([
                                $data['brand_name'],
                                $data['quality_tier'] ?? 'standard'
                            ]);
                            
                            $newBrandId = $db->lastInsertId();
                            
                            // Update suggestion with new brand ID
                            $updateSql = "UPDATE brand_suggestions SET approved_brand_id = ? WHERE id = ?";
                            $updateStmt = $db->prepare($updateSql);
                            $updateStmt->execute([$newBrandId, $suggestionId]);
                            
                            // Update related asset if exists
                            if (!empty($data['update_asset']) && !empty($data['asset_id'])) {
                                $assetSql = "UPDATE assets SET brand_id = ? WHERE id = ?";
                                $assetStmt = $db->prepare($assetSql);
                                $assetStmt->execute([$newBrandId, $data['asset_id']]);
                                
                                // Log brand history
                                $this->logAssetBrandChange($data['asset_id'], null, $newBrandId, $userId, 'Brand suggestion approved');
                            }
                        }
                        
                        // Log the action
                        $this->logBrandWorkflowAction('brand_suggestion', $suggestionId, $data['status'], $userId);
                        
                        $db->commit();
                        echo json_encode(['success' => true, 'message' => 'Suggestion processed successfully']);
                        
                    } catch (Exception $e) {
                        $db->rollBack();
                        throw $e;
                    }
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                    break;
            }
            
        } catch (PDOException $e) {
            error_log("Brand suggestions admin API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Brand Workflow API - General workflow management
     */
    public function brandWorkflow() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            return;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            $action = $_GET['action'] ?? 'stats';
            
            switch ($action) {
                case 'stats':
                    // Get workflow statistics
                    $stats = [];
                    
                    // Pending suggestions
                    $stmt = $db->query("SELECT COUNT(*) FROM brand_suggestions WHERE status = 'pending'");
                    $stats['pending_suggestions'] = $stmt->fetchColumn();
                    
                    // Pending notifications
                    $stmt = $db->query("SELECT COUNT(*) FROM unknown_brand_notifications WHERE status = 'pending'");
                    $stats['pending_notifications'] = $stmt->fetchColumn();
                    
                    // Recent activity
                    $stmt = $db->query("SELECT COUNT(*) FROM brand_workflow_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                    $stats['recent_activity'] = $stmt->fetchColumn();
                    
                    // Assets with unknown brands
                    $stmt = $db->query("SELECT COUNT(*) FROM assets WHERE brand_id IS NULL AND standardized_name IS NOT NULL");
                    $stats['assets_unknown_brands'] = $stmt->fetchColumn();
                    
                    echo json_encode(['success' => true, 'data' => $stats]);
                    break;
                    
                case 'recent':
                    // Get recent workflow activity
                    $limit = min((int)($_GET['limit'] ?? 10), 50);
                    
                    $sql = "SELECT bwl.*, u.full_name as performed_by_name
                            FROM brand_workflow_log bwl
                            JOIN users u ON bwl.performed_by = u.id
                            ORDER BY bwl.created_at DESC
                            LIMIT ?";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$limit]);
                    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['success' => true, 'data' => $activities]);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    break;
            }
            
        } catch (PDOException $e) {
            error_log("Brand workflow API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Helper method to create unknown brand notification
     */
    private function createUnknownBrandNotification($data) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get asset details
            $assetSql = "SELECT name FROM assets WHERE id = ?";
            $assetStmt = $db->prepare($assetSql);
            $assetStmt->execute([$data['asset_id']]);
            $assetName = $assetStmt->fetchColumn();
            
            $sql = "INSERT INTO unknown_brand_notifications 
                    (brand_name, asset_id, asset_name, created_by, category_context, 
                     notification_type, related_suggestion_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['brand_name'],
                $data['asset_id'],
                $assetName,
                $data['created_by'],
                $data['category_context'] ?? '',
                $data['notification_type'] ?? 'unknown_brand',
                $data['related_suggestion_id'] ?? null
            ]);
            
            return $db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Failed to create unknown brand notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Helper method to log brand workflow actions
     */
    private function logBrandWorkflowAction($entityType, $entityId, $action, $userId, $notes = null) {
        try {
            $db = Database::getInstance()->getConnection();
            
            $sql = "INSERT INTO brand_workflow_log 
                    (entity_type, entity_id, action, performed_by, notes) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$entityType, $entityId, $action, $userId, $notes]);
            
        } catch (Exception $e) {
            error_log("Failed to log brand workflow action: " . $e->getMessage());
        }
    }
    
    /**
     * Helper method to log asset brand changes
     */
    private function logAssetBrandChange($assetId, $oldBrandId, $newBrandId, $userId, $reason) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get brand names
            $oldBrandName = null;
            $newBrandName = null;
            
            if ($oldBrandId) {
                $stmt = $db->prepare("SELECT official_name FROM asset_brands WHERE id = ?");
                $stmt->execute([$oldBrandId]);
                $oldBrandName = $stmt->fetchColumn();
            }
            
            if ($newBrandId) {
                $stmt = $db->prepare("SELECT official_name FROM asset_brands WHERE id = ?");
                $stmt->execute([$newBrandId]);
                $newBrandName = $stmt->fetchColumn();
            }
            
            $sql = "INSERT INTO asset_brand_history 
                    (asset_id, old_brand_id, old_brand_name, new_brand_id, new_brand_name, 
                     changed_by, change_reason) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $assetId, $oldBrandId, $oldBrandName, 
                $newBrandId, $newBrandName, $userId, $reason
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to log asset brand change: " . $e->getMessage());
        }
    }
    
    /**
     * Intelligent asset naming API endpoint
     */
    public function intelligentNaming() {
        try {
            // Check authentication
            if (!$this->auth->isAuthenticated()) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Authentication required'
                ]);
                return;
            }
            
            require_once APP_ROOT . '/core/IntelligentAssetNamer.php';
            $namer = new IntelligentAssetNamer();
            $action = $_GET['action'] ?? '';
            
            // If no action is provided, infer it from the route
            if (empty($action) && isset($_GET['route'])) {
                $route = $_GET['route'];
                if (strpos($route, 'api/equipment-types') !== false) {
                    $action = 'equipment-types';
                } elseif (strpos($route, 'api/subtypes') !== false) {
                    $action = 'subtypes';
                } elseif (strpos($route, 'api/equipment-type-details') !== false) {
                    $action = 'equipment-type-details';
                } elseif (strpos($route, 'api/intelligent-naming') !== false) {
                    $action = 'generate-name'; // Default for intelligent-naming route
                }
            }
            
            switch ($action) {
                
                case 'equipment-types':
                    // Get equipment types for a category
                    $categoryId = intval($_GET['category_id'] ?? 0);
                    
                    if (!$categoryId) {
                        throw new Exception('Category ID is required');
                    }
                    
                    $equipmentTypes = $namer->getEquipmentTypesByCategory($categoryId);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $equipmentTypes
                    ]);
                    break;
                    
                case 'subtypes':
                    // Get subtypes for an equipment type
                    $equipmentTypeId = intval($_GET['equipment_type_id'] ?? 0);
                    
                    if (!$equipmentTypeId) {
                        throw new Exception('Equipment Type ID is required');
                    }
                    
                    $subtypes = $namer->getSubtypesByEquipmentType($equipmentTypeId);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $subtypes
                    ]);
                    break;
                    
                case 'equipment-type-details':
                    // Get equipment type details with category info for reverse lookup
                    $equipmentTypeId = intval($_GET['equipment_type_id'] ?? 0);
                    
                    if (!$equipmentTypeId) {
                        throw new Exception('Equipment Type ID is required');
                    }
                    
                    $details = $namer->getEquipmentTypeDetails($equipmentTypeId);
                    
                    if (!$details) {
                        throw new Exception('Equipment type not found');
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $details
                    ]);
                    break;
                    
                case 'generate-name':
                    // Generate intelligent asset name
                    $equipmentTypeId = intval($_GET['equipment_type_id'] ?? 0);
                    $subtypeId = intval($_GET['subtype_id'] ?? 0);
                    $brand = $_GET['brand'] ?? null;
                    $model = $_GET['model'] ?? null;
                    
                    if (!$equipmentTypeId || !$subtypeId) {
                        throw new Exception('Equipment Type ID and Subtype ID are required');
                    }
                    
                    $nameData = $namer->generateAssetName($equipmentTypeId, $subtypeId, $brand, $model);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $nameData
                    ]);
                    break;
                    
                case 'suggestions':
                    // Get intelligent suggestions based on partial input
                    $partialName = $_GET['partial_name'] ?? '';
                    $categoryId = intval($_GET['category_id'] ?? 0);
                    
                    if (strlen(trim($partialName)) < 2) {
                        echo json_encode([
                            'success' => true,
                            'data' => []
                        ]);
                        return;
                    }
                    
                    $suggestions = $namer->getSuggestions($partialName, $categoryId ?: null);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $suggestions
                    ]);
                    break;
                    
                case 'preview':
                    // Preview name generation (same as generate-name but for preview)
                    $equipmentTypeId = intval($_GET['equipment_type_id'] ?? 0);
                    $subtypeId = intval($_GET['subtype_id'] ?? 0);
                    $brand = $_GET['brand'] ?? null;
                    $model = $_GET['model'] ?? null;
                    
                    if (!$equipmentTypeId || !$subtypeId) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Equipment Type ID and Subtype ID are required'
                        ]);
                        return;
                    }
                    
                    $nameData = $namer->generateAssetName($equipmentTypeId, $subtypeId, $brand, $model);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'generated_name' => $nameData['generated_name'],
                            'name_components' => $nameData['name_components']
                        ]
                    ]);
                    break;
                    
                case 'all-equipment-types':
                    // Get all equipment types with categories for intelligent search
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->query('
                        SELECT et.id, et.name, et.description, et.category_id,
                               c.name as category_name
                        FROM equipment_types et 
                        JOIN categories c ON et.category_id = c.id 
                        WHERE et.is_active = 1 
                        ORDER BY et.name ASC
                    ');
                    
                    $equipmentTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $equipmentTypes
                    ]);
                    break;
                    
                case 'intelligent-unit':
                    // Get intelligent unit suggestion based on equipment type and subtype
                    $equipmentTypeId = intval($_GET['equipment_type_id'] ?? 0);
                    $subtypeId = intval($_GET['subtype_id'] ?? 0);
                    
                    if (!$equipmentTypeId) {
                        throw new Exception('Equipment Type ID is required');
                    }
                    
                    $unit = $namer->getIntelligentUnit($equipmentTypeId, $subtypeId ?: null);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'unit' => $unit,
                            'equipment_type_id' => $equipmentTypeId,
                            'subtype_id' => $subtypeId
                        ]
                    ]);
                    break;
                    
                default:
                    if (empty($action)) {
                        throw new Exception('No action specified and could not infer from route: ' . ($_GET['route'] ?? 'unknown'));
                    } else {
                        throw new Exception('Invalid action specified: ' . $action);
                    }
            }
            
        } catch (Exception $e) {
            error_log("Intelligent naming API error: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }


    /**
     * Rate limiting check
     */
    private function checkRateLimit($key, $maxRequests = 60, $timeWindow = 60) {
        if (!RateLimit::check($key, $maxRequests, $timeWindow)) {
            http_response_code(429);
            echo json_encode([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests. Please try again later.'
            ]);
            exit;
        }
    }
}
?>
