<?php
/**
 * ConstructLink™ Request Controller - Unified Request Management
 * Handles unified request operations for materials, tools, equipment, services, petty cash, and others
 */

class RequestController {
    private $auth;
    private $requestModel;
    private $roleConfig;
    private $workflowService;

    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->roleConfig = require APP_ROOT . '/config/roles.php';
        $this->requestModel = new RequestModel();

        // Load RequestWorkflowService for MVA workflow
        require_once APP_ROOT . '/services/RequestWorkflowService.php';
        $this->workflowService = new RequestWorkflowService();
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }
    
    /**
     * Display request listing
     */
    public function index() {
        // Check permissions
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if (!$this->auth->hasRole($this->roleConfig['requests/view'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 5); // Default to 5 entries per page
        
        // Build filters
        $filters = [];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        if (!empty($_GET['request_type'])) $filters['request_type'] = $_GET['request_type'];
        if (!empty($_GET['project_id'])) $filters['project_id'] = $_GET['project_id'];
        if (!empty($_GET['urgency'])) $filters['urgency'] = $_GET['urgency'];
        if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
        if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        
        // Delivery tracking filters
        if (!empty($_GET['delivery_status'])) $filters['delivery_status'] = $_GET['delivery_status'];
        if (!empty($_GET['overdue_delivery'])) $filters['overdue_delivery'] = true;
        if (!empty($_GET['has_discrepancy'])) $filters['has_discrepancy'] = true;
        
        // Role-based filtering
        if ($userRole === 'Project Manager') {
            // Project managers can only see requests from their projects
            $projectModel = new ProjectModel();
            $userProjects = $projectModel->getProjectsByManager($currentUser['id']);
            if (!empty($userProjects)) {
                $projectIds = array_column($userProjects, 'id');
                $filters['project_ids'] = $projectIds;
            } else {
                $filters['project_id'] = -1; // No projects assigned
            }
        } elseif ($userRole === 'Site Inventory Clerk') {
            // Site clerks can only see their own requests
            $filters['requested_by'] = $currentUser['id'];
        } elseif ($userRole === 'Warehouseman') {
            // Warehouseman can only see their own requests
            $filters['requested_by'] = $currentUser['id'];
        } elseif ($userRole === 'Procurement Officer') {
            // Procurement officers can see all requests but with procurement-specific filtering
            if (!empty($_GET['procurement_status'])) {
                $filters['procurement_status'] = $_GET['procurement_status'];
            }
        }
        
        try {
            // Get requests with delivery tracking information
            $requests = $this->requestModel->getRequestsWithDeliveryTracking($filters, $userRole, $currentUser['id']);
            
            // Paginate results manually
            $total = count($requests);
            $offset = ($page - 1) * $perPage;
            $paginatedRequests = array_slice($requests, $offset, $perPage);
            
            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1
            ];
            
            // Get request statistics
            $requestStats = $this->requestModel->getRequestStatistics();
            
            // Get delivery statistics
            $deliveryStats = $this->requestModel->getDeliveryStatistics();
            
            // Get delivery alerts
            $deliveryAlerts = $this->requestModel->getDeliveryAlerts($userRole, $currentUser['id']);
            
            // Get filter options
            $projectModel = new ProjectModel();
            $projects = $projectModel->getActiveProjects();
            
            $pageTitle = 'Requests - ConstructLink™';
            $pageHeader = 'Unified Request Management';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Requests', 'url' => '?route=requests']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            $requests = $paginatedRequests; // Use paginated results
            
            include APP_ROOT . '/views/requests/index.php';
            
        } catch (Exception $e) {
            error_log("Request listing error: " . $e->getMessage());
            $error = 'Failed to load requests';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display create request form
     */
    public function create() {
        // Check permissions
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if (!$this->auth->hasRole($this->roleConfig['requests/create'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        $formData = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            // Process form submission
            $formData = [
                'project_id' => (int)($_POST['project_id'] ?? 0),
                'request_type' => Validator::sanitize($_POST['request_type'] ?? ''),
                'category' => Validator::sanitize($_POST['category'] ?? ''),
                'description' => Validator::sanitize($_POST['description'] ?? ''),
                'quantity' => !empty($_POST['quantity']) ? (int)$_POST['quantity'] : null,
                'unit' => Validator::sanitize($_POST['unit'] ?? ''),
                'urgency' => Validator::sanitize($_POST['urgency'] ?? 'Normal'),
                'date_needed' => !empty($_POST['date_needed']) ? $_POST['date_needed'] : null,
                'estimated_cost' => !empty($_POST['estimated_cost']) ? (float)$_POST['estimated_cost'] : null,
                'remarks' => Validator::sanitize($_POST['remarks'] ?? ''),
                'requested_by' => $currentUser['id'],
                'inventory_item_id' => !empty($_POST['inventory_item_id']) ? (int)$_POST['inventory_item_id'] : null,
                'is_restock' => isset($_POST['is_restock']) && $_POST['is_restock'] == '1' ? 1 : 0
            ];
            
            // Validate required fields
            if (empty($formData['project_id'])) {
                $errors[] = 'Project is required';
            }
            if (empty($formData['request_type'])) {
                $errors[] = 'Request type is required';
            }
            if (empty($formData['description'])) {
                $errors[] = 'Description is required';
            }

            // Restock-specific validation
            if ($formData['request_type'] === 'Restock' || $formData['is_restock'] == 1) {
                if (empty($formData['inventory_item_id'])) {
                    $errors[] = 'Inventory item is required for restock requests';
                } else {
                    // Validate restock request
                    $restockValidation = $this->requestModel->validateRestockRequest($formData);
                    if (!$restockValidation['valid']) {
                        $errors = array_merge($errors, $restockValidation['errors']);
                    }
                }
            }

            // Role-based request type restrictions
            if ($userRole === 'Site Inventory Clerk') {
                $allowedTypes = ['Material', 'Tool', 'Restock'];
                if (!in_array($formData['request_type'], $allowedTypes)) {
                    $errors[] = 'Site Inventory Clerks can only request Materials, Tools, and Restock';
                }
            }
            
            // Use helper function for validation
            $validationErrors = validateRequestCreation($formData, $userRole);
            if (!empty($validationErrors)) {
                $errors = array_merge($errors, $validationErrors);
            }
            
            // Validate date_needed if provided
            if (!empty($formData['date_needed'])) {
                if (strtotime($formData['date_needed']) <= time()) {
                    $errors[] = 'Date needed must be in the future';
                }
            }
            
            if (empty($errors)) {
                try {
                    $result = $this->requestModel->createRequest($formData);
                    
                    if ($result['success']) {
                        header('Location: ?route=requests/view&id=' . $result['request']['id'] . '&message=request_created');
                        exit;
                    } else {
                        if (isset($result['errors'])) {
                            $errors = array_merge($errors, $result['errors']);
                        } else {
                            $errors[] = $result['message'];
                        }
                    }
                    
                } catch (Exception $e) {
                    error_log("Request creation error: " . $e->getMessage());
                    $errors[] = 'Failed to create request.';
                }
            }
        }
        
        // Get form options
        try {
            $projectModel = new ProjectModel();
            $categoryModel = new CategoryModel();
            
            // Get projects based on user role
            if ($userRole === 'Project Manager') {
                // Project managers can only see their assigned projects
                $projects = $projectModel->getProjectsByManager($currentUser['id']);
                
                // Auto-populate project if user has only one project assigned
                if (count($projects) === 1 && empty($formData['project_id'])) {
                    $formData['project_id'] = $projects[0]['id'];
                }
            } else {
                // System Admin and other roles can see all active projects
                $projects = $projectModel->getActiveProjects();
            }
            
            $categories = $categoryModel->findAll([], "name ASC");

            // Get request types from database ENUM (no hardcoding)
            $db = Database::getInstance()->getConnection();
            $sql = "SHOW COLUMNS FROM requests LIKE 'request_type'";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $typeInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            preg_match("/^enum\(\'(.*)\'\)$/", $typeInfo['Type'], $matches);
            $allRequestTypes = explode("','", $matches[1]);

            // Role-based filtering
            $requestTypes = $allRequestTypes;
            if ($userRole === 'Site Inventory Clerk') {
                $requestTypes = array_intersect($allRequestTypes, ['Material', 'Tool', 'Restock']);
            } elseif ($userRole === 'Project Manager') {
                $requestTypes = array_diff($allRequestTypes, ['Petty Cash']);
            }
            
            $pageTitle = 'Create Request - ConstructLink™';
            $pageHeader = 'Create New Request';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Requests', 'url' => '?route=requests'],
                ['title' => 'Create Request', 'url' => '?route=requests/create']
            ];
            
            include APP_ROOT . '/views/requests/create.php';
            
        } catch (Exception $e) {
            error_log("Request create form error: " . $e->getMessage());
            $error = 'Failed to load form data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Submit request (change from Draft to Submitted)
     */
    public function submit() {
        $requestId = $_GET['id'] ?? 0;
        
        if (!$requestId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $currentUser = $this->auth->getCurrentUser();
        
        try {
            $request = $this->requestModel->getRequestWithDetails($requestId);
            
            if (!$request) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check if user can submit this request (only the creator can submit)
            if ($request['requested_by'] != $currentUser['id']) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            
            // Check if request can be submitted
            if ($request['status'] !== 'Draft') {
                header('Location: ?route=requests/view&id=' . $requestId . '&error=cannot_submit');
                exit;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $result = $this->requestModel->submitRequest($requestId, $currentUser['id']);
                
                if ($result['success']) {
                    header('Location: ?route=requests/view&id=' . $requestId . '&message=request_submitted');
                    exit;
                } else {
                    header('Location: ?route=requests/view&id=' . $requestId . '&error=submit_failed');
                    exit;
                }
            }
            
            // If GET request, redirect to view page
            header('Location: ?route=requests/view&id=' . $requestId);
            exit;
            
        } catch (Exception $e) {
            error_log("Request submission error: " . $e->getMessage());
            header('Location: ?route=requests/view&id=' . $requestId . '&error=submit_failed');
            exit;
        }
    }
    
    /**
     * Review request (DEPRECATED - redirects to view page)
     * Kept for backward compatibility
     */
    public function review() {
        $requestId = $_GET['id'] ?? 0;
        if ($requestId) {
            header('Location: ?route=requests/view&id=' . $requestId);
            exit;
        }
        header('Location: ?route=requests');
        exit;
    }

    /**
     * Approve request (DEPRECATED - redirects to view page)
     * Kept for backward compatibility
     */
    public function approve() {
        $requestId = $_GET['id'] ?? 0;
        if ($requestId) {
            header('Location: ?route=requests/view&id=' . $requestId);
            exit;
        }
        header('Location: ?route=requests');
        exit;
    }
    
    /**
     * View request details
     */
    public function view() {
        $requestId = $_GET['id'] ?? 0;

        if (!$requestId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        try {
            // Get enhanced request data with workflow details
            $request = $this->requestModel->getRequestWithWorkflow($requestId);

            if (!$request) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }

            // Check permissions
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';

            if (!$this->auth->hasRole($this->roleConfig['requests/view'] ?? ['System Admin'])) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }

            // Get workflow service for action button logic
            require_once APP_ROOT . '/services/RequestWorkflowService.php';
            $workflowService = new RequestWorkflowService();

            // Get next approver info
            $nextApprover = $workflowService->getNextApprover($requestId);

            // Get workflow chain for timeline display
            $workflowChain = $workflowService->getWorkflowChain($requestId);

            // Check user permissions for workflow actions
            $canVerify = $workflowService->canUserVerify($requestId, $currentUser['id']);
            $canAuthorize = $workflowService->canUserAuthorize($requestId, $currentUser['id']);
            $canApprove = $workflowService->canUserApprove($requestId, $currentUser['id']);

            // Get request activity logs
            $requestLogs = $this->requestModel->getRequestLogs($requestId);

            // Get related procurement orders with delivery tracking
            $procurementOrders = [];
            if (in_array($request['status'], ['Approved', 'Procured', 'Fulfilled'])) {
                $procurementOrderModel = new ProcurementOrderModel();
                $procurementOrders = $procurementOrderModel->getProcurementOrdersByRequest($requestId);
            }

            $pageTitle = 'Request Details - ConstructLink™';
            $pageHeader = 'Request #' . $request['id'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Requests', 'url' => '?route=requests'],
                ['title' => 'View Details', 'url' => '?route=requests/view&id=' . $requestId]
            ];

            // Pass auth instance to view
            $auth = $this->auth;

            include APP_ROOT . '/views/requests/view.php';

        } catch (Exception $e) {
            error_log("Request view error: " . $e->getMessage());
            $error = 'Failed to load request details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Get pending requests for dashboard
     */
    public function getPendingRequests() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        try {
            $pendingRequests = $this->requestModel->getPendingRequests($currentUser['id'], $userRole);
            
            echo json_encode([
                'success' => true,
                'data' => $pendingRequests,
                'count' => count($pendingRequests)
            ]);
            
        } catch (Exception $e) {
            error_log("Get pending requests error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load pending requests']);
        }
    }
    
    /**
     * Export requests to Excel
     */
    public function export() {
        // Check permissions
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if (!$this->auth->hasRole($this->roleConfig['requests/export'] ?? ['System Admin'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Build filters from GET parameters
            $filters = [];
            if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
            if (!empty($_GET['request_type'])) $filters['request_type'] = $_GET['request_type'];
            if (!empty($_GET['project_id'])) $filters['project_id'] = $_GET['project_id'];
            if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
            if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
            
            // Get all requests (no pagination for export)
            $result = $this->requestModel->getRequestsWithFilters($filters, 1, 10000);
            $requests = $result['data'] ?? [];
            
            // Set headers for Excel download
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="requests_' . date('Y-m-d') . '.xls"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output Excel content
            echo '<table border="1">';
            echo '<tr>';
            echo '<th>ID</th>';
            echo '<th>Project</th>';
            echo '<th>Request Type</th>';
            echo '<th>Category</th>';
            echo '<th>Description</th>';
            echo '<th>Quantity</th>';
            echo '<th>Unit</th>';
            echo '<th>Urgency</th>';
            echo '<th>Status</th>';
            echo '<th>Requested By</th>';
            echo '<th>Date Needed</th>';
            echo '<th>Estimated Cost</th>';
            echo '<th>Created Date</th>';
            echo '</tr>';
            
            foreach ($requests as $request) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($request['id']) . '</td>';
                echo '<td>' . htmlspecialchars($request['project_name']) . '</td>';
                echo '<td>' . htmlspecialchars($request['request_type']) . '</td>';
                echo '<td>' . htmlspecialchars($request['category'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($request['description']) . '</td>';
                echo '<td>' . htmlspecialchars($request['quantity'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($request['unit'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($request['urgency']) . '</td>';
                echo '<td>' . htmlspecialchars(ucfirst($request['status'])) . '</td>';
                echo '<td>' . htmlspecialchars($request['requested_by_name']) . '</td>';
                echo '<td>' . ($request['date_needed'] ? date('Y-m-d', strtotime($request['date_needed'])) : '') . '</td>';
                echo '<td>' . ($request['estimated_cost'] ? number_format($request['estimated_cost'], 2) : '') . '</td>';
                echo '<td>' . date('Y-m-d H:i', strtotime($request['created_at'])) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            exit;
            
        } catch (Exception $e) {
            error_log("Request export error: " . $e->getMessage());
            header('Location: ?route=requests&error=export_failed');
            exit;
        }
    }
    
    /**
     * Get request statistics for dashboard
     */
    public function getStats() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        try {
            $stats = $this->requestModel->getRequestStatistics();
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            error_log("Get request stats error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load statistics']);
        }
    }

    /**
     * Redirect Generate PO action to ProcurementOrderController
     */
    public function generatePO() {
        $requestId = $_GET['request_id'] ?? 0;
        if (!$requestId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        // Optionally, check permissions here if needed
        header('Location: ?route=procurement-orders/createFromRequest&request_id=' . $requestId);
        exit;
    }

    /**
     * Get inventory items for restock (API endpoint for AJAX calls)
     */
    public function getInventoryItemsForRestock() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $currentUser = $this->auth->getCurrentUser();
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $lowStockOnly = isset($_GET['low_stock_only']) && $_GET['low_stock_only'] === 'true';

        try {
            $items = $this->requestModel->getInventoryItemsForRestock($projectId, $lowStockOnly);

            // Format for Select2
            $formattedItems = array_map(function($item) {
                return [
                    'id' => $item['id'],
                    'text' => sprintf(
                        '%s - %s [%s/%s %s] %s%%',
                        $item['ref'],
                        $item['name'],
                        $item['available_quantity'],
                        $item['quantity'],
                        $item['unit'],
                        $item['stock_level_percentage']
                    ),
                    'data' => $item
                ];
            }, $items);

            echo json_encode([
                'success' => true,
                'items' => $formattedItems,
                'count' => count($formattedItems)
            ]);

        } catch (Exception $e) {
            error_log("Get inventory items for restock error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load inventory items']);
        }
    }

    /**
     * Verify request (MVA workflow - Verifier step)
     */
    public function verify() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            include APP_ROOT . '/views/errors/405.php';
            return;
        }

        CSRFProtection::validateRequest();

        $requestId = $_POST['request_id'] ?? null;
        $notes = Validator::sanitize($_POST['notes'] ?? '');

        if (!$requestId) {
            $_SESSION['error'] = 'Request ID is required';
            header('Location: ?route=requests');
            exit;
        }

        $currentUser = $this->auth->getCurrentUser();

        try {
            require_once APP_ROOT . '/services/RequestWorkflowService.php';
            $workflowService = new RequestWorkflowService();

            if (!$workflowService->canUserVerify($requestId, $currentUser['id'])) {
                $_SESSION['error'] = 'You are not authorized to verify this request';
                header('Location: ?route=requests/view&id=' . $requestId);
                exit;
            }

            $result = $workflowService->verifyRequest($requestId, $currentUser['id'], $notes);

            if ($result['success']) {
                $_SESSION['success'] = 'Request verified successfully';
            } else {
                $_SESSION['error'] = $result['message'];
            }

            header('Location: ?route=requests/view&id=' . $requestId);
            exit;

        } catch (Exception $e) {
            error_log("Request verification error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to verify request';
            header('Location: ?route=requests/view&id=' . $requestId);
            exit;
        }
    }

    /**
     * Authorize request (MVA workflow - Authorizer step)
     */
    public function authorize() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            include APP_ROOT . '/views/errors/405.php';
            return;
        }

        CSRFProtection::validateRequest();

        $requestId = $_POST['request_id'] ?? null;
        $notes = Validator::sanitize($_POST['notes'] ?? '');

        if (!$requestId) {
            $_SESSION['error'] = 'Request ID is required';
            header('Location: ?route=requests');
            exit;
        }

        $currentUser = $this->auth->getCurrentUser();

        try {
            require_once APP_ROOT . '/services/RequestWorkflowService.php';
            $workflowService = new RequestWorkflowService();

            if (!$workflowService->canUserAuthorize($requestId, $currentUser['id'])) {
                $_SESSION['error'] = 'You are not authorized to authorize this request';
                header('Location: ?route=requests/view&id=' . $requestId);
                exit;
            }

            $result = $workflowService->authorizeRequest($requestId, $currentUser['id'], $notes);

            if ($result['success']) {
                $_SESSION['success'] = 'Request authorized successfully';
            } else {
                $_SESSION['error'] = $result['message'];
            }

            header('Location: ?route=requests/view&id=' . $requestId);
            exit;

        } catch (Exception $e) {
            error_log("Request authorization error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to authorize request';
            header('Location: ?route=requests/view&id=' . $requestId);
            exit;
        }
    }

    /**
     * Approve request - Enhanced with MVA workflow
     */
    public function approveWorkflow() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            include APP_ROOT . '/views/errors/405.php';
            return;
        }

        CSRFProtection::validateRequest();

        $requestId = $_POST['request_id'] ?? null;
        $notes = Validator::sanitize($_POST['notes'] ?? '');

        if (!$requestId) {
            $_SESSION['error'] = 'Request ID is required';
            header('Location: ?route=requests');
            exit;
        }

        $currentUser = $this->auth->getCurrentUser();

        try {
            require_once APP_ROOT . '/services/RequestWorkflowService.php';
            $workflowService = new RequestWorkflowService();

            if (!$workflowService->canUserApprove($requestId, $currentUser['id'])) {
                $_SESSION['error'] = 'You are not authorized to approve this request';
                header('Location: ?route=requests/view&id=' . $requestId);
                exit;
            }

            $result = $workflowService->approveRequest($requestId, $currentUser['id'], $notes);

            if ($result['success']) {
                $_SESSION['success'] = 'Request approved successfully';
            } else {
                $_SESSION['error'] = $result['message'];
            }

            header('Location: ?route=requests/view&id=' . $requestId);
            exit;

        } catch (Exception $e) {
            error_log("Request approval error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to approve request';
            header('Location: ?route=requests/view&id=' . $requestId);
            exit;
        }
    }

    /**
     * Decline request (MVA workflow - Can decline at any approval stage)
     */
    public function decline() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            include APP_ROOT . '/views/errors/405.php';
            return;
        }

        CSRFProtection::validateRequest();

        $requestId = $_POST['request_id'] ?? null;
        $declineReason = Validator::sanitize($_POST['decline_reason'] ?? '');

        if (!$requestId) {
            $_SESSION['error'] = 'Request ID is required';
            header('Location: ?route=requests');
            exit;
        }

        if (empty($declineReason)) {
            $_SESSION['error'] = 'Decline reason is required';
            header('Location: ?route=requests/view&id=' . $requestId);
            exit;
        }

        $currentUser = $this->auth->getCurrentUser();

        try {
            require_once APP_ROOT . '/services/RequestWorkflowService.php';
            $workflowService = new RequestWorkflowService();

            $result = $workflowService->declineRequest($requestId, $currentUser['id'], $declineReason);

            if ($result['success']) {
                $_SESSION['success'] = 'Request declined successfully';
            } else {
                $_SESSION['error'] = $result['message'];
            }

            header('Location: ?route=requests/view&id=' . $requestId);
            exit;

        } catch (Exception $e) {
            error_log("Request decline error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to decline request';
            header('Location: ?route=requests/view&id=' . $requestId);
            exit;
        }
    }

    /**
     * Resubmit declined request (reset to Draft for editing)
     */
    public function resubmit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            include APP_ROOT . '/views/errors/405.php';
            return;
        }

        CSRFProtection::validateRequest();

        $requestId = $_POST['request_id'] ?? null;

        if (!$requestId) {
            $_SESSION['error'] = 'Request ID is required';
            header('Location: ?route=requests');
            exit;
        }

        $currentUser = $this->auth->getCurrentUser();

        try {
            require_once APP_ROOT . '/services/RequestWorkflowService.php';
            $workflowService = new RequestWorkflowService();

            $result = $workflowService->resubmitRequest($requestId, $currentUser['id']);

            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }

            header('Location: ?route=requests/view&id=' . $requestId);
            exit;

        } catch (Exception $e) {
            error_log("Request resubmit error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to resubmit request';
            header('Location: ?route=requests/view&id=' . $requestId);
            exit;
        }
    }
}
?>
