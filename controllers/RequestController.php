<?php
/**
 * ConstructLink™ Request Controller - Unified Request Management
 * Handles unified request operations for materials, tools, equipment, services, petty cash, and others
 */

class RequestController {
    private $auth;
    private $requestModel;
    private $roleConfig;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->roleConfig = require APP_ROOT . '/config/roles.php';
        $this->requestModel = new RequestModel();
        
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
        $perPage = 20;
        
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
                'requested_by' => $currentUser['id']
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
            
            // Role-based request type restrictions
            if ($userRole === 'Site Inventory Clerk') {
                $allowedTypes = ['Material', 'Tool'];
                if (!in_array($formData['request_type'], $allowedTypes)) {
                    $errors[] = 'Site Inventory Clerks can only request Materials and Tools';
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
            
            // Request types based on user role
            $requestTypes = ['Material', 'Tool', 'Equipment', 'Service', 'Petty Cash', 'Other'];
            if ($userRole === 'Site Inventory Clerk') {
                $requestTypes = ['Material', 'Tool']; // Restricted types
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
     * Review request (Asset Director, System Admin)
     */
    public function review() {
        // Check permissions
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        $requestId = $_GET['id'] ?? 0;
        if (!$requestId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        $errors = [];
        try {
            $request = $this->requestModel->getRequestWithDetails($requestId);
            if (!$request) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            // Project Manager can review if assigned to the project and status is Submitted
            $canPM = (
                $userRole === 'Project Manager' &&
                ($request['project_manager_id'] ?? null) == $currentUser['id'] &&
                $request['status'] === 'Submitted'
            );
            if (!$this->auth->hasRole($this->roleConfig['requests/review'] ?? ['System Admin', 'Project Manager'])) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            // Check if request can be reviewed
            if ($request['status'] !== 'Submitted') {
                $errors[] = 'This request is not ready for review or has already been reviewed.';
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                $action = $_POST['action'] ?? '';
                $remarks = Validator::sanitize($_POST['remarks'] ?? '');
                if (empty($errors)) {
                    $newStatus = ($action === 'forward') ? 'Forwarded' : 'Reviewed';
                    $result = $this->requestModel->updateRequestStatus($requestId, $newStatus, $currentUser['id'], $remarks);
                    if ($result['success']) {
                        $message = ($action === 'forward') ? 'request_forwarded' : 'request_reviewed';
                        header('Location: ?route=requests/view&id=' . $requestId . '&message=' . $message);
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            $pageTitle = 'Review Request - ConstructLink™';
            $pageHeader = 'Review Request #' . $request['id'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Requests', 'url' => '?route=requests'],
                ['title' => 'Review Request', 'url' => '?route=requests/review&id=' . $requestId]
            ];
            include APP_ROOT . '/views/requests/review.php';
        } catch (Exception $e) {
            error_log("Request review error: " . $e->getMessage());
            $error = 'Failed to process review';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Approve/Decline request
     */
    public function approve() {
        $currentUser = $this->auth->getCurrentUser();
        $requestId = $_GET['id'] ?? 0;
        if (!$requestId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        $errors = [];
        try {
            $request = $this->requestModel->getRequestWithDetails($requestId);
            if (!$request) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            // Workflow-specific: Project Manager must be assigned to the project
            if ($currentUser['role_name'] === 'Project Manager' && ($request['project_manager_id'] ?? null) != $currentUser['id']) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            // Check if request can be approved
            if (!in_array($request['status'], ['Reviewed', 'Forwarded'])) {
                $errors[] = 'This request is not ready for approval.';
            }
            // (Optional) Keep type/value restrictions if you want, or remove for pure MVA
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                $action = $_POST['action'] ?? '';
                $remarks = Validator::sanitize($_POST['remarks'] ?? '');
                if (empty($errors)) {
                    $newStatus = ($action === 'approve') ? 'Approved' : 'Declined';
                    $result = $this->requestModel->updateRequestStatus($requestId, $newStatus, $currentUser['id'], $remarks);
                    if ($result['success']) {
                        $message = ($action === 'approve') ? 'request_approved' : 'request_declined';
                        header('Location: ?route=requests/view&id=' . $requestId . '&message=' . $message);
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            $pageTitle = 'Approve Request - ConstructLink™';
            $pageHeader = 'Approve/Decline Request #' . $request['id'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Requests', 'url' => '?route=requests'],
                ['title' => 'Approve Request', 'url' => '?route=requests/approve&id=' . $requestId]
            ];
            include APP_ROOT . '/views/requests/approve.php';
        } catch (Exception $e) {
            error_log("Request approval error: " . $e->getMessage());
            $error = 'Failed to process approval';
            include APP_ROOT . '/views/errors/500.php';
        }
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
            // Get enhanced request data with delivery status
            $request = $this->requestModel->getRequestWithDeliveryStatus($requestId);
            
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
            
            // Get request activity logs
            $requestLogs = $this->requestModel->getRequestLogs($requestId);
            
            // Get related procurement orders with delivery tracking
            $procurementOrders = [];
            if ($request['status'] === 'Approved' || $request['status'] === 'Procured' || $request['status'] === 'Fulfilled') {
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
}
?>
