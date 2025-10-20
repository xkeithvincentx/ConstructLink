<?php
/**
 * ConstructLink™ Borrowed Tool Controller - MVA RBAC REFACTORED
 * Handles borrowed tool management operations with centralized RBAC and MVA workflow
 */

class BorrowedToolController {
    private $auth;
    private $borrowedToolModel;
    private $roleConfig;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->borrowedToolModel = new BorrowedToolModel();
        $this->roleConfig = require APP_ROOT . '/config/roles.php';

        // Require batch model for batch operations
        require_once APP_ROOT . '/models/BorrowedToolBatchModel.php';
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }

    /**
     * Centralized RBAC permission check for borrowed tools
     * Uses permissions configuration from config/permissions.php
     */
    private function hasBorrowedToolPermission($action, $tool = null) {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';

        // System Admin has all permissions
        if ($userRole === 'System Admin') return true;

        // Check if tool is critical (requires full MVA workflow)
        $isCritical = false;
        if ($tool && isset($tool['asset_id'])) {
            $isCritical = $this->borrowedToolModel->isCriticalTool($tool['asset_id'], $tool['acquisition_cost'] ?? null);
        }

        // Handle MVA workflow permissions using configuration
        switch ($action) {
            case 'create':
                // Maker roles from config
                $allowedRoles = config('permissions.borrowed_tools.create', []);
                return in_array($userRole, $allowedRoles);

            case 'create_and_process':
                // For streamlined workflow when same user can do all steps (Basic tools only)
                // Only roles that can release can do streamlined processing for Basic tools
                $allowedRoles = config('permissions.borrowed_tools.release', []);
                return in_array($userRole, $allowedRoles) && !$isCritical;

            case 'verify':
                // Verifier roles from config (for critical tools only)
                // Basic tools skip verification step in streamlined workflow
                if ($isCritical) {
                    $allowedRoles = config('permissions.borrowed_tools.verify', []);
                    return in_array($userRole, $allowedRoles);
                } else {
                    // Basic tools don't need separate verification
                    return false;
                }

            case 'approve':
                // Authorizer roles from config (for critical tools only)
                // Basic tools skip approval step in streamlined workflow
                if ($isCritical) {
                    $allowedRoles = config('permissions.borrowed_tools.approve', []);
                    return in_array($userRole, $allowedRoles);
                } else {
                    // Basic tools don't need separate approval
                    return false;
                }

            case 'borrow':
            case 'release':
                // Roles that can mark as borrowed/released
                $allowedRoles = config('permissions.borrowed_tools.release', []);
                return in_array($userRole, $allowedRoles);

            case 'return':
                // Roles that can process returns
                $allowedRoles = config('permissions.borrowed_tools.return', []);
                return in_array($userRole, $allowedRoles);

            case 'extend':
            case 'edit':
                // Roles that can edit batches
                $allowedRoles = config('permissions.borrowed_tools.edit', []);
                return in_array($userRole, $allowedRoles);

            case 'cancel':
                // Can cancel if user created the request or has cancel permissions
                if ($tool && $tool['issued_by'] == $currentUser['id']) {
                    return true;
                }
                $allowedRoles = config('permissions.borrowed_tools.cancel', []);
                return in_array($userRole, $allowedRoles);

            case 'delete':
                // Delete permissions
                $allowedRoles = config('permissions.borrowed_tools.delete', []);
                return in_array($userRole, $allowedRoles);

            case 'view':
            case 'view_statistics':
                // View permissions
                $allowedRoles = config('permissions.borrowed_tools.view', []);
                return in_array($userRole, $allowedRoles);

            case 'mva_oversight':
                // MVA oversight permissions
                $allowedRoles = config('permissions.borrowed_tools.mva_oversight', []);
                return in_array($userRole, $allowedRoles);

            default:
                // Check standard role configuration
                $allowedRoles = $this->roleConfig['borrowed-tools/' . $action] ?? [];
                return in_array($userRole, $allowedRoles);
        }
    }

    /**
     * Check if current request is AJAX
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Send JSON error response and exit
     *
     * @param string $message Error message
     * @param int $code HTTP status code (default: 400)
     * @return void
     */
    private function jsonError($message, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    /**
     * Send JSON success response and exit
     *
     * @param string $message Success message
     * @param array $data Additional data to include in response
     * @return void
     */
    private function jsonSuccess($message, $data = []) {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => true, 'message' => $message], $data));
        exit;
    }

    /**
     * Redirect with error message and exit
     *
     * @param string $message Error message
     * @param string $route Target route (default: borrowed-tools)
     * @return void
     */
    private function redirectWithError($message, $route = 'borrowed-tools') {
        $_SESSION['error'] = $message;
        header('Location: ?route=' . $route);
        exit;
    }

    /**
     * Redirect with success message and exit
     *
     * @param string $message Success message
     * @param string $route Target route (default: borrowed-tools)
     * @return void
     */
    private function redirectWithSuccess($message, $route = 'borrowed-tools') {
        $_SESSION['success'] = $message;
        header('Location: ?route=' . $route);
        exit;
    }

    /**
     * Render error page and exit
     *
     * @param int $code HTTP status code (403, 404, 500)
     * @param string|null $message Optional custom error message
     * @return void
     */
    private function renderError($code = 403, $message = null) {
        http_response_code($code);
        if ($message) {
            $error = $message;
        }
        include APP_ROOT . "/views/errors/{$code}.php";
        exit;
    }

    /**
     * Display borrowed tools listing with filters, pagination, and statistics
     *
     * Handles the main borrowed tools index page with:
     * - Filtering by status, search, date range, priority
     * - Sorting by various columns
     * - Pagination (20 items per page)
     * - Project-based filtering for operational roles
     * - Statistics cards showing MVA workflow states and time-based metrics
     *
     * @return void Renders the borrowed tools index view
     */
    public function index() {
        // Centralized RBAC: Only users with view permission can access
        if (!$this->hasBorrowedToolPermission('view')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        // Ensure user has project assignment and get current user
        $this->requireProjectAssignment();
        $currentUser = $this->auth->getCurrentUser();

        $page = (int)($_GET['page'] ?? 1);
        $perPage = PAGINATION_PER_PAGE_BORROWED_TOOLS;
        
        // Build filters
        $filters = [];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
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
        
        // Apply project filtering based on user role
        $projectFilter = $this->getProjectFilter();
        if ($projectFilter) {
            // Operational roles: filter by their assigned project
            $filters['project_id'] = $projectFilter;
        }
        // MVA oversight roles: no project filtering (see all projects)
        
        try {
            // Get borrowed tools with pagination
            $result = $this->borrowedToolModel->getBorrowedToolsWithFilters($filters, $page, $perPage);
            $borrowedTools = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];

            // Get statistics (filtered by project for operational roles, all projects for MVA oversight roles)
            $batchModel = new BorrowedToolBatchModel();
            $batchStats = $batchModel->getBatchStats(null, null, $projectFilter);

            // Get available non-consumable equipment count
            try {
                $db = Database::getInstance()->getConnection();
                $availableEquipmentSql = "SELECT COUNT(*) FROM assets a
                    JOIN categories c ON a.category_id = c.id
                    WHERE c.is_consumable = 0
                      AND a.status = 'available'
                      AND a.workflow_status = 'approved'";
                $availableParams = [];
                if ($projectFilter) {
                    $availableEquipmentSql .= " AND a.project_id = ?";
                    $availableParams[] = $projectFilter;
                }
                $availableStmt = $db->prepare($availableEquipmentSql);
                $availableStmt->execute($availableParams);
                $availableEquipmentCount = $availableStmt->fetchColumn();
            } catch (Exception $e) {
                $availableEquipmentCount = 0;
            }

            // Get time-based statistics for operational roles
            $timeStats = $batchModel->getTimeBasedStatistics($projectFilter);

            // Transform batch stats to match expected format in views
            $borrowedToolStats = [
                // MVA workflow stats
                'pending_verification' => $batchStats['pending_verification'] ?? 0,
                'pending_approval' => $batchStats['pending_approval'] ?? 0,
                'available_equipment' => $availableEquipmentCount ?? 0,
                'borrowed' => $batchStats['released'] ?? 0,  // "Released" batches are currently borrowed
                'partially_returned' => $batchStats['partially_returned'] ?? 0,
                'returned' => $batchStats['returned'] ?? 0,
                'canceled' => $batchStats['canceled'] ?? 0,
                'total_borrowings' => $batchStats['total_batches'] ?? 0,
                'overdue' => 0,  // Will calculate separately

                // Time-based stats for operational roles
                'borrowed_today' => $timeStats['borrowed_today'] ?? 0,
                'returned_today' => $timeStats['returned_today'] ?? 0,
                'due_today' => $timeStats['due_today'] ?? 0,
                'due_this_week' => $timeStats['due_this_week'] ?? 0,
                'activity_this_week' => $timeStats['activity_this_week'] ?? 0,

                // Monthly stats for management roles
                'borrowed_this_month' => $timeStats['borrowed_this_month'] ?? 0,
                'returned_this_month' => $timeStats['returned_this_month'] ?? 0,
            ];

            // Get overdue batches (Released batches past expected_return date)
            $overdueCount = $batchModel->getOverdueBatchCount($projectFilter);
            $borrowedToolStats['overdue'] = $overdueCount;

            // Get overdue tools (filtered by project for operational roles, all projects for MVA oversight roles)
            $overdueTools = $this->borrowedToolModel->getOverdueBorrowedTools($projectFilter);
            
            $pageTitle = 'Borrowed Tools - ConstructLink™';
            $pageHeader = 'Borrowed Tools Management';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools']
            ];
            
            // Pass auth instance and sort parameters to view
            $auth = $this->auth;
            $currentSort = $sortBy;
            $currentOrder = $sortOrder;

            include APP_ROOT . '/views/borrowed-tools/index.php';
            
        } catch (Exception $e) {
            error_log("Borrowed tools listing error: " . $e->getMessage());
            $error = 'Failed to load borrowed tools';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Display statistics dashboard for borrowed tools
     * Dedicated analytics view separate from operational index
     */
    public function statistics() {
        // Centralized RBAC: Only users with view_statistics permission can access
        if (!$this->hasBorrowedToolPermission('view_statistics')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }

        // Ensure user has project assignment and get current user
        $this->requireProjectAssignment();
        $currentUser = $this->auth->getCurrentUser();

        // Apply project filtering based on user role
        $projectFilter = $this->getProjectFilter();
        // MVA oversight roles: no project filtering (see all projects)
        // Operational roles: filter by their assigned project

        try {
            // Get comprehensive statistics
            $batchModel = new BorrowedToolBatchModel();
            $batchStats = $batchModel->getBatchStats(null, null, $projectFilter);

            // Get available non-consumable equipment count
            try {
                $db = Database::getInstance()->getConnection();
                $availableEquipmentSql = "SELECT COUNT(*) FROM assets a
                    JOIN categories c ON a.category_id = c.id
                    WHERE c.is_consumable = 0
                      AND a.status = 'available'
                      AND a.workflow_status = 'approved'";
                $availableParams = [];
                if ($projectFilter) {
                    $availableEquipmentSql .= " AND a.project_id = ?";
                    $availableParams[] = $projectFilter;
                }
                $availableStmt = $db->prepare($availableEquipmentSql);
                $availableStmt->execute($availableParams);
                $availableEquipmentCount = $availableStmt->fetchColumn();
            } catch (Exception $e) {
                $availableEquipmentCount = 0;
            }

            // Get time-based statistics
            $timeStats = $batchModel->getTimeBasedStatistics($projectFilter);

            // Transform batch stats to match expected format in views
            $borrowedToolStats = [
                // MVA workflow stats
                'pending_verification' => $batchStats['pending_verification'] ?? 0,
                'pending_approval' => $batchStats['pending_approval'] ?? 0,
                'available_equipment' => $availableEquipmentCount ?? 0,
                'borrowed' => $batchStats['released'] ?? 0,  // "Released" batches are currently borrowed
                'partially_returned' => $batchStats['partially_returned'] ?? 0,
                'returned' => $batchStats['returned'] ?? 0,
                'canceled' => $batchStats['canceled'] ?? 0,
                'total_borrowings' => $batchStats['total_batches'] ?? 0,
                'overdue' => 0,  // Will calculate separately

                // Time-based stats
                'borrowed_today' => $timeStats['borrowed_today'] ?? 0,
                'returned_today' => $timeStats['returned_today'] ?? 0,
                'due_today' => $timeStats['due_today'] ?? 0,
                'due_this_week' => $timeStats['due_this_week'] ?? 0,
                'activity_this_week' => $timeStats['activity_this_week'] ?? 0,

                // Monthly stats
                'borrowed_this_month' => $timeStats['borrowed_this_month'] ?? 0,
                'returned_this_month' => $timeStats['returned_this_month'] ?? 0,
            ];

            // Get overdue batches
            $overdueCount = $batchModel->getOverdueBatchCount($projectFilter);
            $borrowedToolStats['overdue'] = $overdueCount;

            // Get detailed overdue tools for the report
            $overdueTools = $this->borrowedToolModel->getOverdueBorrowedTools($projectFilter);

            // Pass auth instance to view
            $auth = $this->auth;
            $user = $currentUser;

            include APP_ROOT . '/views/borrowed-tools/statistics.php';

        } catch (Exception $e) {
            error_log("Borrowed tools statistics error: " . $e->getMessage());
            $error = 'Failed to load statistics';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Display create borrowed tool form
     * Redirects to create-batch which handles both single and multiple items
     */
    public function create() {
        // Redirect to create-batch (unified interface for single/multiple items)
        header('Location: ?route=borrowed-tools/create-batch');
        exit;
    }
    
    /**
     * Display borrowed tool details (single item or batch)
     *
     * Handles viewing both:
     * - Modern batch requests (multiple items)
     * - Legacy single item requests (backwards compatibility)
     *
     * Automatically loads batch if item belongs to one, otherwise
     * converts single item to batch format for unified display.
     *
     * @return void Renders the borrowed tools view page
     */
    public function view() {
        $id = $_GET['id'] ?? 0;

        if (!$id) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        // Ensure user has project assignment (MVA oversight roles are exempt)
        $this->requireProjectAssignment();

        try {
            // First, check if this is a batch_id or a borrowed_tools id
            $batchModel = new BorrowedToolBatchModel();
            $batch = $batchModel->getBatchWithItems($id, $this->getProjectFilter());

            if ($batch) {
                // It's a batch request
                $auth = $this->auth;
                include APP_ROOT . '/views/borrowed-tools/view.php';
                return;
            }

            // Not a batch, try loading as single borrowed tool
            $borrowedTool = $this->borrowedToolModel->getBorrowedToolWithMVADetails($id, $this->getProjectFilter());

            if (!$borrowedTool) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }

            // If this item has a batch_id, load the full batch instead
            if (!empty($borrowedTool['batch_id'])) {
                $batch = $batchModel->getBatchWithItems($borrowedTool['batch_id'], $this->getProjectFilter());
                if ($batch) {
                    $auth = $this->auth;
                    include APP_ROOT . '/views/borrowed-tools/view.php';
                    return;
                }
            }

            // Convert single borrowed tool to batch format for unified view (legacy items without batch)
            $batch = [
                'id' => $borrowedTool['batch_id'] ?? $borrowedTool['id'],
                'batch_reference' => $borrowedTool['batch_reference'] ?? ($borrowedTool['id'] ? "BT-" . str_pad($borrowedTool['id'], 6, '0', STR_PAD_LEFT) : 'N/A'),
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
                'items' => [
                    [
                        'id' => $borrowedTool['id'],
                        'asset_id' => $borrowedTool['asset_id'],
                        'asset_name' => $borrowedTool['asset_name'] ?? 'Unknown',
                        'asset_ref' => $borrowedTool['asset_ref'] ?? 'N/A',
                        'category_name' => $borrowedTool['category_name'] ?? 'N/A',
                        'acquisition_cost' => $borrowedTool['acquisition_cost'] ?? 0,
                        'quantity' => $borrowedTool['quantity'] ?? 1,
                        'quantity_returned' => 0, // Old single items don't track partial returns
                        'status' => $borrowedTool['status'] ?? 'Unknown'
                    ]
                ]
            ];

            $auth = $this->auth;
            include APP_ROOT . '/views/borrowed-tools/view.php';

        } catch (Exception $e) {
            error_log("View request error: " . $e->getMessage());
            $error = 'Failed to load request details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Return borrowed tool (legacy single item return)
     *
     * Processes the return of a single borrowed tool with:
     * - Condition assessment (Good, Fair, Poor, Damaged, Lost)
     * - Return notes
     * - Asset status update
     * - Automatic incident creation for Damaged/Lost items
     *
     * @return void Renders return form (GET) or processes return (POST)
     */
    public function returnTool() {
        // Centralized RBAC: Only users with return permission can access
        if (!$this->hasBorrowedToolPermission('return')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        // Ensure user has project assignment
        $this->requireProjectAssignment();
        
        $borrowId = $_GET['id'] ?? $_POST['borrow_id'] ?? 0;
        
        if (!$borrowId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $formData = [];
        
        try {
            $borrowedTool = $this->borrowedToolModel->getBorrowedToolWithDetails($borrowId, $this->getProjectFilter());
            
            if (!$borrowedTool) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $conditionIn = Validator::sanitize($_POST['condition_in'] ?? '');
                $returnNotes = Validator::sanitize($_POST['return_notes'] ?? '');
                $returnedBy = $this->auth->getCurrentUser()['id'];
                
                $result = $this->borrowedToolModel->returnBorrowedTool($borrowId, $returnedBy, $conditionIn, $returnNotes);
                
                if ($result['success']) {
                    header('Location: ?route=borrowed-tools/view&id=' . $borrowId . '&message=tool_returned');
                    exit;
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
            $error = 'Failed to process tool return';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Extend borrowing period
     */
    public function extend() {
        // Centralized RBAC: Only users with extend permission can access
        if (!$this->hasBorrowedToolPermission('extend')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        // Ensure user has project assignment
        $this->requireProjectAssignment();
        
        $borrowId = $_GET['id'] ?? $_POST['borrow_id'] ?? 0;
        
        if (!$borrowId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $formData = [];
        
        try {
            $borrowedTool = $this->borrowedToolModel->getBorrowedToolWithDetails($borrowId, $this->getProjectFilter());
            
            if (!$borrowedTool) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $newExpectedReturn = $_POST['new_expected_return'] ?? '';
                $reason = Validator::sanitize($_POST['reason'] ?? '');
                
                if (empty($newExpectedReturn)) {
                    $errors[] = 'New expected return date is required';
                } else {
                    $result = $this->borrowedToolModel->extendBorrowingPeriod($borrowId, $newExpectedReturn, $reason);
                    
                    if ($result['success']) {
                        header('Location: ?route=borrowed-tools/view&id=' . $borrowId . '&message=borrowing_extended');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Extend Borrowing - ConstructLink™';
            $pageHeader = 'Extend Borrowing: ' . htmlspecialchars($borrowedTool['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
                ['title' => 'Extend Borrowing', 'url' => '?route=borrowed-tools/extend&id=' . $borrowId]
            ];
            
            include APP_ROOT . '/views/borrowed-tools/extend.php';
            
        } catch (Exception $e) {
            error_log("Borrowing extension error: " . $e->getMessage());
            $error = 'Failed to process borrowing extension';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Mark tool as overdue (AJAX endpoint)
     *
     * Updates a single borrowed tool's status to 'Overdue'.
     * Used for manual overdue marking by authorized users.
     *
     * @return void Outputs JSON response
     */
    public function markOverdue() {
        // Centralized RBAC: Only users with mark_overdue permission can access
        if (!$this->hasBorrowedToolPermission('mark_overdue')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        $borrowId = $_POST['borrow_id'] ?? 0;
        
        if (!$borrowId) {
            echo json_encode(['success' => false, 'message' => 'Invalid borrow ID']);
            return;
        }
        
        try {
            $result = $this->borrowedToolModel->markOverdue($borrowId);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Mark overdue error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to mark tool as overdue']);
        }
    }
    
    /**
     * Get borrowed tool statistics (AJAX)
     */
    public function getStats() {
        // Ensure user has project assignment (MVA oversight roles are exempt)
        $this->requireProjectAssignment();
        
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        
        // Apply project filtering (operational roles only, MVA oversight roles see all)
        $projectFilter = $this->getProjectFilter();
        
        try {
            $stats = $this->borrowedToolModel->getBorrowedToolStats($dateFrom, $dateTo, $projectFilter);
            echo json_encode(['success' => true, 'stats' => $stats]);
            
        } catch (Exception $e) {
            error_log("Get borrowed tool stats error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load statistics']);
        }
    }
    
    /**
     * Export borrowed tools data
     */
    public function export() {
        // Centralized RBAC: Only users with export permission can access
        if (!$this->hasBorrowedToolPermission('export')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        // Ensure user has project assignment (MVA oversight roles are exempt)
        $this->requireProjectAssignment();
        
        $format = $_GET['format'] ?? 'csv';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-t');
        $status = $_GET['status'] ?? null;
        
        // Apply project filtering (operational roles only, MVA oversight roles see all)
        $projectFilter = $this->getProjectFilter();
        
        try {
            $data = $this->borrowedToolModel->getBorrowedToolReport($dateFrom, $dateTo, $status, $projectFilter);
            
            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="borrowed_tools_' . date('Y-m-d') . '.csv"');
                
                $output = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($output, [
                    'ID', 'Asset Reference', 'Asset Name', 'Category', 'Project',
                    'Borrower Name', 'Borrower Contact', 'Purpose', 'Expected Return',
                    'Actual Return', 'Status', 'Condition Out', 'Condition In',
                    'Issued By', 'Created At'
                ]);
                
                // CSV data
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row['id'],
                        $row['asset_ref'],
                        $row['asset_name'],
                        $row['category_name'],
                        $row['project_name'],
                        $row['borrower_name'],
                        $row['borrower_contact'],
                        $row['purpose'],
                        $row['expected_return'],
                        $row['actual_return'],
                        $row['status'],
                        $row['condition_out'],
                        $row['condition_in'],
                        $row['issued_by_name'],
                        $row['created_at']
                    ]);
                }
                
                fclose($output);
            }
            
        } catch (Exception $e) {
            error_log("Export borrowed tools error: " . $e->getMessage());
            $error = 'Failed to export borrowed tools data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Update overdue status for all borrowed tools
     */
    public function updateOverdueStatus() {
        // Centralized RBAC: Only users with update_overdue_status permission can access
        if (!$this->hasBorrowedToolPermission('update_overdue_status')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $updatedCount = $this->borrowedToolModel->updateOverdueStatus();
            echo json_encode([
                'success' => true, 
                'message' => "Updated {$updatedCount} tools to overdue status"
            ]);
            
        } catch (Exception $e) {
            error_log("Update overdue status error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update overdue status']);
        }
    }
    
    /**
     * Get overdue borrower contacts
     */
    public function getOverdueContacts() {
        // Centralized RBAC: Only users with get_overdue_contacts permission can access
        if (!$this->hasBorrowedToolPermission('get_overdue_contacts')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $contacts = $this->borrowedToolModel->getOverdueBorrowerContacts();
            echo json_encode(['success' => true, 'contacts' => $contacts]);
            
        } catch (Exception $e) {
            error_log("Get overdue contacts error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load overdue contacts']);
        }
    }
    
    
    /**
     * Verify borrowed tool request (MVA Verifier step)
     *
     * Second step in MVA workflow for critical tools:
     * 1. Maker creates request (Pending Verification)
     * 2. **Verifier reviews and verifies** (Pending Approval)
     * 3. Authorizer approves (Approved)
     * 4. Release officer releases tool (Released/Borrowed)
     *
     * Only required for critical tools (acquisition cost > threshold).
     * Basic tools skip this step in streamlined workflow.
     *
     * @return void Renders verification form (GET) or processes verification (POST)
     */
    public function verify() {
        // Ensure user has project assignment
        $this->requireProjectAssignment();
        
        $borrowId = $_GET['id'] ?? 0;
        if (!$borrowId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        $errors = [];
        
        try {
            $borrowedTool = $this->borrowedToolModel->getBorrowedToolWithMVADetails($borrowId, $this->getProjectFilter());
            if (!$borrowedTool) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }

            // Check if user has permission to verify
            if (!$this->hasBorrowedToolPermission('verify', $borrowedTool)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $notes = Validator::sanitize($_POST['verification_notes'] ?? '');
                $verifiedBy = $this->auth->getCurrentUser()['id'];
                
                $result = $this->borrowedToolModel->verifyBorrowedTool($borrowId, $verifiedBy, $notes);
                
                if ($result['success']) {
                    header('Location: ?route=borrowed-tools/view&id=' . $borrowId . '&message=tool_verified');
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            
            $pageTitle = 'Verify Borrowed Tool - ConstructLink™';
            $pageHeader = 'Verify Borrowed Tool: ' . htmlspecialchars($borrowedTool['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
                ['title' => 'Verify', 'url' => '?route=borrowed-tools/verify&id=' . $borrowId]
            ];
            
            include APP_ROOT . '/views/borrowed-tools/verify.php';
            
        } catch (Exception $e) {
            error_log("Borrowed tool verification error: " . $e->getMessage());
            $error = 'Failed to process borrowed tool verification';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Approve borrowed tool request (MVA Authorizer step)
     *
     * Third step in MVA workflow for critical tools:
     * 1. Maker creates request (Pending Verification)
     * 2. Verifier reviews (Pending Approval)
     * 3. **Authorizer approves** (Approved)
     * 4. Release officer releases tool (Released/Borrowed)
     *
     * Only required for critical tools (acquisition cost > threshold).
     * Basic tools skip this step in streamlined workflow.
     *
     * @return void Renders approval form (GET) or processes approval (POST)
     */
    public function approve() {
        // Ensure user has project assignment
        $this->requireProjectAssignment();
        
        $borrowId = $_GET['id'] ?? 0;
        if (!$borrowId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        $errors = [];
        
        try {
            $borrowedTool = $this->borrowedToolModel->getBorrowedToolWithMVADetails($borrowId, $this->getProjectFilter());
            if (!$borrowedTool) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }

            // Check if user has permission to approve
            if (!$this->hasBorrowedToolPermission('approve', $borrowedTool)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $notes = Validator::sanitize($_POST['approval_notes'] ?? '');
                $approvedBy = $this->auth->getCurrentUser()['id'];
                
                $result = $this->borrowedToolModel->approveBorrowedTool($borrowId, $approvedBy, $notes);
                
                if ($result['success']) {
                    header('Location: ?route=borrowed-tools/view&id=' . $borrowId . '&message=tool_approved');
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            
            $pageTitle = 'Approve Borrowed Tool - ConstructLink™';
            $pageHeader = 'Approve Borrowed Tool: ' . htmlspecialchars($borrowedTool['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
                ['title' => 'Approve', 'url' => '?route=borrowed-tools/approve&id=' . $borrowId]
            ];
            
            include APP_ROOT . '/views/borrowed-tools/approve.php';
            
        } catch (Exception $e) {
            error_log("Borrowed tool approval error: " . $e->getMessage());
            $error = 'Failed to process borrowed tool approval';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Mark tool as borrowed (after approval)
     */
    public function borrow() {
        // Ensure user has project assignment
        $this->requireProjectAssignment();
        
        $borrowId = $_GET['id'] ?? 0;
        if (!$borrowId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        $errors = [];
        
        try {
            $borrowedTool = $this->borrowedToolModel->getBorrowedToolWithMVADetails($borrowId, $this->getProjectFilter());
            if (!$borrowedTool) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }

            // Check if user has permission to mark as borrowed
            if (!$this->hasBorrowedToolPermission('borrow', $borrowedTool)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $notes = Validator::sanitize($_POST['borrow_notes'] ?? '');
                $borrowedBy = $this->auth->getCurrentUser()['id'];
                
                $result = $this->borrowedToolModel->borrowTool($borrowId, $borrowedBy, $notes);
                
                if ($result['success']) {
                    header('Location: ?route=borrowed-tools/view&id=' . $borrowId . '&message=tool_borrowed');
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            
            $pageTitle = 'Borrow Tool - ConstructLink™';
            $pageHeader = 'Borrow Tool: ' . htmlspecialchars($borrowedTool['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
                ['title' => 'Borrow', 'url' => '?route=borrowed-tools/borrow&id=' . $borrowId]
            ];
            
            include APP_ROOT . '/views/borrowed-tools/borrow.php';
            
        } catch (Exception $e) {
            error_log("Borrowed tool borrow error: " . $e->getMessage());
            $error = 'Failed to process tool borrowing';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Cancel borrowed tool request
     */
    public function cancel() {
        // Ensure user has project assignment
        $this->requireProjectAssignment();
        
        $borrowId = $_GET['id'] ?? 0;
        if (!$borrowId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        $errors = [];
        
        try {
            $borrowedTool = $this->borrowedToolModel->getBorrowedToolWithMVADetails($borrowId, $this->getProjectFilter());
            if (!$borrowedTool) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }

            // Check if user has permission to cancel
            if (!$this->hasBorrowedToolPermission('cancel', $borrowedTool)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $reason = Validator::sanitize($_POST['cancellation_reason'] ?? '');
                $canceledBy = $this->auth->getCurrentUser()['id'];
                
                $result = $this->borrowedToolModel->cancelBorrowedTool($borrowId, $canceledBy, $reason);
                
                if ($result['success']) {
                    header('Location: ?route=borrowed-tools/view&id=' . $borrowId . '&message=tool_canceled');
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            
            $pageTitle = 'Cancel Borrowed Tool - ConstructLink™';
            $pageHeader = 'Cancel Borrowed Tool: ' . htmlspecialchars($borrowedTool['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
                ['title' => 'Cancel', 'url' => '?route=borrowed-tools/cancel&id=' . $borrowId]
            ];
            
            include APP_ROOT . '/views/borrowed-tools/cancel.php';
            
        } catch (Exception $e) {
            error_log("Borrowed tool cancellation error: " . $e->getMessage());
            $error = 'Failed to process tool cancellation';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Check if current user has project assignment, redirect with error if not
     * MVA oversight roles are exempt from project assignment requirement
     */
    private function requireProjectAssignment() {
        $currentUser = $this->auth->getCurrentUser();
        $mvaOversightRoles = config('permissions.borrowed_tools.mva_oversight', []);

        // MVA oversight roles don't need project assignment
        if (in_array($currentUser['role_name'], $mvaOversightRoles)) {
            return null; // No project restriction for oversight roles
        }

        // Operational roles must have project assignment
        if (!$currentUser['current_project_id']) {
            $error = 'You must be assigned to a project to access borrowed tools. Please contact your administrator.';
            include APP_ROOT . '/views/errors/500.php';
            exit;
        }
        return $currentUser['current_project_id'];
    }

    /**
     * Get project filter for current user
     * MVA oversight roles see all projects, operational roles see only their assigned project
     */
     private function getProjectFilter() {
        $currentUser = $this->auth->getCurrentUser();
        $mvaOversightRoles = config('permissions.borrowed_tools.mva_oversight', []);

        // MVA oversight roles see all projects (no filtering)
        if (in_array($currentUser['role_name'], $mvaOversightRoles)) {
            return null;
        }

        // Operational roles see only their assigned project
        return !empty($currentUser['current_project_id'])
            ? $currentUser['current_project_id'] : null;
    }
    
    /**
     * Check if asset belongs to user's project
     */
    private function isAssetInUserProject($assetId, $userProjectId) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT project_id FROM assets WHERE id = ?");
            $stmt->execute([$assetId]);
            $assetProjectId = $stmt->fetchColumn();
            return $assetProjectId == $userProjectId;
        } catch (Exception $e) {
            error_log("Error checking asset project: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get available assets for borrowing (excluding already borrowed/withdrawn/transferred assets)
     * REFACTORED: Eliminated N+1 queries by using LEFT JOIN aggregation
     */
    private function getAvailableAssetsForBorrowing() {
        try {
            $db = Database::getInstance()->getConnection();
            $currentUser = $this->auth->getCurrentUser();
            $currentProjectId = $currentUser['current_project_id'] ?? null;

            // Build query with enhanced filtering using LEFT JOIN to avoid N+1 queries
            // This single query replaces the previous loop with 3 queries per asset
            $sql = "
                SELECT
                    a.id,
                    a.ref,
                    a.name,
                    a.status,
                    c.name as category_name,
                    c.is_consumable,
                    p.name as project_name,
                    a.acquisition_cost,
                    a.model,
                    a.serial_number,
                    -- Aggregate checks for asset usage (eliminates N+1 problem)
                    COUNT(DISTINCT bt.id) as active_borrowings,
                    COUNT(DISTINCT w.id) as active_withdrawals,
                    COUNT(DISTINCT t.id) as active_transfers
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                -- Check for active borrowed tools (not returned)
                LEFT JOIN borrowed_tools bt ON a.id = bt.asset_id
                    AND bt.status IN ('Pending Verification', 'Pending Approval', 'Approved', 'Borrowed', 'Overdue')
                -- Check for active withdrawals
                LEFT JOIN withdrawals w ON a.id = w.asset_id
                    AND w.status IN ('pending', 'released')
                -- Check for active transfers
                LEFT JOIN transfers t ON a.id = t.asset_id
                    AND t.status IN ('pending', 'approved')
                WHERE a.status = 'available'
                  AND p.is_active = 1
                  AND c.is_consumable = 0  -- Exclude consumable items
            ";

            $params = [];

            // Filter by user's current project if assigned
            if ($currentProjectId) {
                $sql .= " AND a.project_id = ?";
                $params[] = $currentProjectId;
            }

            $sql .= "
                GROUP BY a.id, a.ref, a.name, a.status, c.name, c.is_consumable,
                         p.name, a.acquisition_cost, a.model, a.serial_number
                -- Only include assets that are NOT in use (all counts are 0)
                HAVING active_borrowings = 0
                   AND active_withdrawals = 0
                   AND active_transfers = 0
                ORDER BY a.name ASC
            ";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $availableAssets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Remove the aggregation columns before returning
            foreach ($availableAssets as &$asset) {
                unset($asset['active_borrowings']);
                unset($asset['active_withdrawals']);
                unset($asset['active_transfers']);
            }

            return $availableAssets;

        } catch (Exception $e) {
            error_log("Get available assets for borrowing error: " . $e->getMessage());
            return [];
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
            $assetModel = new AssetModel();
            $asset = $assetModel->getAssetWithDetails($qrResult['asset_id']);
            
            if (!$asset) {
                echo json_encode([
                    'valid' => false,
                    'message' => 'Asset not found',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                return;
            }
            
            // Check if asset is available for borrowing
            $availableAssets = $this->getAvailableAssetsForBorrowing();
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
                        'ref' => $asset['ref'],
                        'name' => $asset['name'],
                        'status' => $asset['status']
                    ],
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                return;
            }
            
            // Return success with asset details
            echo json_encode([
                'valid' => true,
                'message' => 'Asset is available for borrowing',
                'asset' => [
                    'id' => $asset['id'],
                    'ref' => $asset['ref'],
                    'name' => $asset['name'],
                    'category_name' => $asset['category_name'],
                    'project_name' => $asset['project_name'],
                    'model' => $asset['model'],
                    'serial_number' => $asset['serial_number'],
                    'acquisition_cost' => $asset['acquisition_cost'],
                    'status' => $asset['status']
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("QR validation for borrowing error: " . $e->getMessage());
            
            http_response_code(500);
            echo json_encode([
                'valid' => false,
                'message' => 'QR validation failed due to server error',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }

    // =========================================================================
    // BATCH BORROWING METHODS
    // Multi-item borrowing with shopping cart interface
    // =========================================================================

    /**
     * Display multi-item batch creation form
     *
     * Unified interface for borrowing single or multiple tools with:
     * - Shopping cart-style item selection
     * - QR code scanning support
     * - Real-time available equipment filtering
     * - Automatic MVA workflow determination (critical vs basic tools)
     *
     * @return void Renders the batch creation form
     */
    public function createBatch() {
        if (!$this->hasBorrowedToolPermission('create')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }

        // Check project assignment (MVA oversight roles exempt)
        $currentUser = $this->auth->getCurrentUser();
        $mvaOversightRoles = config('permissions.borrowed_tools.mva_oversight', []);

        if (!in_array($currentUser['role_name'], $mvaOversightRoles) && !$currentUser['current_project_id']) {
            $error = 'You must be assigned to a project to borrow tools. Please contact your administrator.';
            include APP_ROOT . '/views/errors/403.php';
            exit;
        }

        $errors = [];
        $messages = [];

        $pageTitle = 'Borrow Multiple Tools - ConstructLink™';
        include APP_ROOT . '/views/borrowed-tools/create-batch.php';
    }

    /**
     * Store new batch (AJAX/POST endpoint)
     *
     * Creates a new borrowed tools batch with automatic workflow:
     * - **Critical Tools** (cost > threshold): Full MVA workflow
     *   - Creates batch → Pending Verification
     * - **Basic Tools**: Streamlined workflow
     *   - Creates batch → Auto-release → Released (Borrowed)
     *
     * @return void Outputs JSON response with batch_id and workflow_type
     */
    public function storeBatch() {
        // Suppress error output to prevent breaking JSON response
        @ini_set('display_errors', '0');
        error_reporting(E_ALL);

        header('Content-Type: application/json');

        if (!$this->hasBorrowedToolPermission('create')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied: Only Warehouseman or System Admin can create batches']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Validate user has project assignment (except MVA oversight roles)
        $currentUser = $this->auth->getCurrentUser();
        $mvaOversightRoles = config('permissions.borrowed_tools.mva_oversight', []);

        if (!in_array($currentUser['role_name'], $mvaOversightRoles) && !$currentUser['current_project_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You must be assigned to a project to borrow equipment']);
            return;
        }

        CSRFProtection::validateRequest();

        try {
            $currentUser = $this->auth->getCurrentUser();

            // Parse batch data
            $batchData = [
                'borrower_name' => Validator::sanitize($_POST['borrower_name'] ?? ''),
                'borrower_contact' => Validator::sanitize($_POST['borrower_contact'] ?? ''),
                'expected_return' => $_POST['expected_return'] ?? '',
                'purpose' => Validator::sanitize($_POST['purpose'] ?? ''),
                'issued_by' => $currentUser['id']
            ];

            // Parse items
            $itemsJson = $_POST['items'] ?? '[]';
            $items = json_decode($itemsJson, true);

            if (empty($items)) {
                echo json_encode(['success' => false, 'message' => 'No items selected']);
                return;
            }

            // Create batch using model
            $batchModel = new BorrowedToolBatchModel();
            $result = $batchModel->createBatch($batchData, $items);

            if ($result['success']) {
                // If streamlined workflow (basic tools), also release immediately
                if ($result['workflow_type'] === 'streamlined') {
                    $releaseResult = $batchModel->releaseBatch(
                        $result['batch']['id'],
                        $currentUser['id'],
                        'Streamlined auto-release for basic tools'
                    );

                    if ($releaseResult['success']) {
                        $result['message'] = 'Batch created and released successfully';
                    }
                }

                echo json_encode([
                    'success' => true,
                    'batch_id' => $result['batch']['id'],
                    'batch_reference' => $result['batch']['batch_reference'],
                    'workflow_type' => $result['workflow_type'],
                    'message' => $result['message']
                ]);
            } else {
                echo json_encode($result);
            }

        } catch (Exception $e) {
            error_log("Batch creation error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create batch: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * View request details (single or multi-item)
     */
    public function viewBatch() {
        $this->requireProjectAssignment();

        $batchId = $_GET['batch_id'] ?? $_GET['id'] ?? 0;

        if (!$batchId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        try {
            $batchModel = new BorrowedToolBatchModel();
            $batch = $batchModel->getBatchWithItems($batchId, $this->getProjectFilter());

            if (!$batch) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }

            include APP_ROOT . '/views/borrowed-tools/view.php';

        } catch (Exception $e) {
            error_log("View request error: " . $e->getMessage());
            $error = 'Failed to load request details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Verify batch request (MVA Verifier step for batches)
     *
     * Batch-level verification for critical tool batches.
     * Verifies all items in the batch as a single unit.
     *
     * @return void Redirects to borrowed-tools index with status message
     */
    public function verifyBatch() {
        $this->requireProjectAssignment();

        // Only accept POST requests (form handled by modal in index.php)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=borrowed-tools&error=invalid_request');
            exit;
        }

        $batchId = $_POST['batch_id'] ?? 0;

        if (!$batchId) {
            header('Location: ?route=borrowed-tools&error=batch_id_required');
            exit;
        }

        try {
            CSRFProtection::validateRequest();

            $batchModel = new BorrowedToolBatchModel();
            $batch = $batchModel->getBatchWithItems($batchId, $this->getProjectFilter());

            if (!$batch) {
                header('Location: ?route=borrowed-tools&error=batch_not_found');
                exit;
            }

            // Check permission
            if (!$this->hasBorrowedToolPermission('verify', $batch)) {
                header('Location: ?route=borrowed-tools&error=permission_denied');
                exit;
            }

            $notes = Validator::sanitize($_POST['verification_notes'] ?? '');
            $verifiedBy = $this->auth->getCurrentUser()['id'];

            $result = $batchModel->verifyBatch($batchId, $verifiedBy, $notes);

            if ($result['success']) {
                header('Location: ?route=borrowed-tools&message=batch_verified');
                exit;
            } else {
                header('Location: ?route=borrowed-tools&error=' . urlencode($result['message']));
                exit;
            }

        } catch (Exception $e) {
            error_log("Batch verification error: " . $e->getMessage());
            header('Location: ?route=borrowed-tools&error=verification_failed');
            exit;
        }
    }

    /**
     * Approve batch (Authorizer step)
     */
    public function approveBatch() {
        $this->requireProjectAssignment();

        // Only accept POST requests (form handled by modal in index.php)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=borrowed-tools&error=invalid_request');
            exit;
        }

        $batchId = $_POST['batch_id'] ?? 0;

        if (!$batchId) {
            header('Location: ?route=borrowed-tools&error=batch_id_required');
            exit;
        }

        try {
            CSRFProtection::validateRequest();

            $batchModel = new BorrowedToolBatchModel();
            $batch = $batchModel->getBatchWithItems($batchId, $this->getProjectFilter());

            if (!$batch) {
                header('Location: ?route=borrowed-tools&error=batch_not_found');
                exit;
            }

            // Check permission
            if (!$this->hasBorrowedToolPermission('approve', $batch)) {
                header('Location: ?route=borrowed-tools&error=permission_denied');
                exit;
            }

            $notes = Validator::sanitize($_POST['approval_notes'] ?? '');
            $approvedBy = $this->auth->getCurrentUser()['id'];

            $result = $batchModel->approveBatch($batchId, $approvedBy, $notes);

            if ($result['success']) {
                header('Location: ?route=borrowed-tools&message=batch_approved');
                exit;
            } else {
                header('Location: ?route=borrowed-tools&error=' . urlencode($result['message']));
                exit;
            }

        } catch (Exception $e) {
            error_log("Batch approval error: " . $e->getMessage());
            header('Location: ?route=borrowed-tools&error=approval_failed');
            exit;
        }
    }

    /**
     * Release batch to borrower
     */
    public function releaseBatch() {
        $this->requireProjectAssignment();

        // Only accept POST requests (form handled by modal in index.php)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=borrowed-tools&error=invalid_request');
            exit;
        }

        $batchId = $_POST['batch_id'] ?? 0;

        if (!$batchId) {
            header('Location: ?route=borrowed-tools&error=batch_id_required');
            exit;
        }

        try {
            CSRFProtection::validateRequest();

            $batchModel = new BorrowedToolBatchModel();
            $batch = $batchModel->getBatchWithItems($batchId, $this->getProjectFilter());

            if (!$batch) {
                header('Location: ?route=borrowed-tools&error=batch_not_found');
                exit;
            }

            // Check permission
            if (!$this->hasBorrowedToolPermission('borrow', $batch)) {
                header('Location: ?route=borrowed-tools&error=permission_denied');
                exit;
            }

            $notes = Validator::sanitize($_POST['release_notes'] ?? '');
            $releasedBy = $this->auth->getCurrentUser()['id'];

            $result = $batchModel->releaseBatch($batchId, $releasedBy, $notes);

            if ($result['success']) {
                header('Location: ?route=borrowed-tools&message=batch_released');
                exit;
            } else {
                header('Location: ?route=borrowed-tools&error=' . urlencode($result['message']));
                exit;
            }

        } catch (Exception $e) {
            error_log("Batch release error: " . $e->getMessage());
            header('Location: ?route=borrowed-tools&error=release_failed');
            exit;
        }
    }

    /**
     * Return batch (full or partial returns supported)
     *
     * Processes batch returns with:
     * - **Partial Returns**: Return some items, keep others borrowed
     * - **Full Returns**: Return all items in batch
     * - Per-item condition assessment
     * - Automatic incident creation for Damaged/Lost items
     * - Batch status updates (Partially Returned → Returned)
     *
     * @return void Outputs JSON (AJAX) or redirects with status message
     */
    public function returnBatch() {
        // Suppress error output to prevent breaking JSON response
        @ini_set('display_errors', '0');
        error_reporting(E_ALL);

        // Check project assignment (MVA oversight roles exempt)
        $currentUser = $this->auth->getCurrentUser();
        $mvaOversightRoles = config('permissions.borrowed_tools.mva_oversight', []);

        if (!in_array($currentUser['role_name'], $mvaOversightRoles) && !$currentUser['current_project_id']) {
            $error = 'You must be assigned to a project to return tools. Please contact your administrator.';
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            exit;
        }

        $batchId = $_GET['id'] ?? $_POST['batch_id'] ?? 0;

        if (!$batchId) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Batch ID required']);
                return;
            }
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        $errors = [];

        try {
            $batchModel = new BorrowedToolBatchModel();

            // First, try to get as batch
            $batch = $batchModel->getBatchWithItems($batchId, $this->getProjectFilter());

            // If not found as batch, try as single item
            if (!$batch) {
                $borrowedTool = $this->borrowedToolModel->getBorrowedToolWithDetails($batchId, $this->getProjectFilter());

                if ($borrowedTool) {
                    // Convert single item to batch format for consistent processing
                    $batch = [
                        'id' => null, // No batch ID for single items
                        'batch_reference' => $borrowedTool['asset_ref'] ?? "BT-{$batchId}",
                        'status' => $borrowedTool['status'],
                        'expected_return' => $borrowedTool['expected_return'],
                        'items' => [
                            [
                                'id' => $borrowedTool['id'],
                                'asset_id' => $borrowedTool['asset_id'],
                                'asset_name' => $borrowedTool['asset_name'],
                                'asset_ref' => $borrowedTool['asset_ref'],
                                'quantity' => $borrowedTool['quantity'] ?? 1,
                                'quantity_returned' => $borrowedTool['quantity_returned'] ?? 0,
                                'status' => $borrowedTool['status']
                            ]
                        ]
                    ];
                }
            }

            if (!$batch) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Item or batch not found']);
                    return;
                }
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }

            // Check permission
            if (!$this->hasBorrowedToolPermission('return', $batch)) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Permission denied']);
                    return;
                }
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();

                // Parse returned items - support both array format (qty_in[], item_ref[]) and individual format
                $returnedItems = [];

                // Array format from modal (qty_in[], condition[], item_id[])
                if (isset($_POST['qty_in']) && is_array($_POST['qty_in'])) {
                    $qtyIn = $_POST['qty_in'];
                    $conditions = $_POST['condition'] ?? [];
                    $itemIds = $_POST['item_id'] ?? [];
                    $itemNotes = $_POST['item_notes'] ?? [];

                    foreach ($qtyIn as $index => $qty) {
                        // Skip items with qty 0 or empty
                        if (empty($qty) || $qty <= 0) {
                            continue;
                        }

                        $borrowedToolId = $itemIds[$index] ?? '';

                        if ($borrowedToolId) {
                            $returnedItems[] = [
                                'borrowed_tool_id' => (int)$borrowedToolId,
                                'quantity_returned' => (int)$qty,
                                'condition_in' => Validator::sanitize($conditions[$index] ?? 'Good'),
                                'notes' => Validator::sanitize($itemNotes[$index] ?? '')
                            ];
                        }
                    }
                } else {
                    // Individual format from batch-return.php view (quantity_returned_ID, condition_in_ID)
                    foreach ($batch['items'] as $item) {
                        $quantityKey = 'quantity_returned_' . $item['id'];
                        $conditionKey = 'condition_in_' . $item['id'];

                        if (isset($_POST[$quantityKey]) && $_POST[$quantityKey] > 0) {
                            $returnedItems[] = [
                                'borrowed_tool_id' => $item['id'],
                                'quantity_returned' => (int)$_POST[$quantityKey],
                                'condition_in' => Validator::sanitize($_POST[$conditionKey] ?? 'Good')
                            ];
                        }
                    }
                }

                if (empty($returnedItems)) {
                    $errors[] = 'Please specify at least one item to return';

                    if ($this->isAjaxRequest()) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Please specify at least one item to return']);
                        return;
                    }
                } else {
                    $notes = Validator::sanitize($_POST['return_notes'] ?? '');
                    $returnedBy = $this->auth->getCurrentUser()['id'];

                    // Determine if this is a batch or single item
                    $isSingleItem = ($batch['id'] === null);

                    if ($isSingleItem) {
                        // For single items, process the return directly using BorrowedToolModel
                        $itemId = $batch['items'][0]['id'];
                        $quantityReturned = $returnedItems[0]['quantity_returned'];
                        $conditionIn = $returnedItems[0]['condition_in'] ?? 'Good';
                        $itemNotes = $returnedItems[0]['notes'] ?? '';

                        try {
                            $db = Database::getInstance()->getConnection();
                            $db->beginTransaction();

                            // Get borrowed tool and asset details for incident creation
                            $borrowedToolSql = "SELECT bt.*, a.ref as asset_ref, a.name as asset_name FROM borrowed_tools bt LEFT JOIN assets a ON bt.asset_id = a.id WHERE bt.id = ?";
                            $borrowedToolStmt = $db->prepare($borrowedToolSql);
                            $borrowedToolStmt->execute([$itemId]);
                            $borrowedToolData = $borrowedToolStmt->fetch(PDO::FETCH_ASSOC);

                            // Auto-create incident for Damaged or Lost items
                            $incidentCreated = null;
                            if (in_array($conditionIn, ['Damaged', 'Lost'])) {
                                $incidentModel = new IncidentModel();

                                $incidentType = ($conditionIn === 'Damaged') ? 'damaged' : 'lost';
                                $incidentSeverity = ($conditionIn === 'Damaged') ? 'medium' : 'high';

                                $incidentData = [
                                    'asset_id' => $borrowedToolData['asset_id'],
                                    'borrowed_tool_id' => $itemId,
                                    'reported_by' => $returnedBy,
                                    'type' => $incidentType,
                                    'severity' => $incidentSeverity,
                                    'description' => "Equipment returned from borrowed tools with condition: {$conditionIn}. " . $itemNotes,
                                    'date_reported' => date('Y-m-d')
                                ];

                                $incidentResult = $incidentModel->createIncident($incidentData);

                                if ($incidentResult['success']) {
                                    $incidentCreated = $incidentResult['incident']['id'];
                                }
                            }

                            // Update the borrowed tool record
                            $updateSql = "
                                UPDATE borrowed_tools
                                SET quantity_returned = quantity_returned + ?,
                                    condition_returned = ?,
                                    line_notes = ?,
                                    returned_by = ?,
                                    return_date = NOW(),
                                    status = CASE
                                        WHEN (quantity_returned + ?) >= quantity THEN 'Returned'
                                        ELSE 'Borrowed'
                                    END,
                                    updated_at = NOW()
                                WHERE id = ?
                            ";

                            $stmt = $db->prepare($updateSql);
                            $stmt->execute([
                                $quantityReturned,
                                $conditionIn,
                                $itemNotes,
                                $returnedBy,
                                $quantityReturned,
                                $itemId
                            ]);

                            // Update asset status and current condition
                            if (in_array($conditionIn, ['Damaged', 'Lost'])) {
                                // Damaged/Lost: Mark as under maintenance
                                $assetUpdateSql = "UPDATE assets SET status = 'under_maintenance', current_condition = ? WHERE id = ?";
                                $assetStmt = $db->prepare($assetUpdateSql);
                                $assetStmt->execute([$conditionIn, $borrowedToolData['asset_id']]);
                            } else {
                                // Good/Fair/Poor: Update condition and keep available
                                $assetUpdateSql = "UPDATE assets SET status = 'available', current_condition = ? WHERE id = ?";
                                $assetStmt = $db->prepare($assetUpdateSql);
                                $assetStmt->execute([$conditionIn, $borrowedToolData['asset_id']]);
                            }

                            // Log the return
                            $logSql = "
                                INSERT INTO borrowed_tool_logs (borrowed_tool_id, action, user_id, notes, created_at)
                                VALUES (?, 'returned', ?, ?, NOW())
                            ";
                            $logStmt = $db->prepare($logSql);
                            $logStmt->execute([$itemId, $returnedBy, "Returned {$quantityReturned} unit(s) in {$conditionIn} condition. " . $notes]);

                            $db->commit();

                            $successMessage = 'Item returned successfully';
                            if ($incidentCreated) {
                                $successMessage .= '. Incident #' . $incidentCreated . ' created';
                            }

                            if ($this->isAjaxRequest()) {
                                header('Content-Type: application/json');
                                echo json_encode([
                                    'success' => true,
                                    'message' => $successMessage,
                                    'incident_created' => $incidentCreated
                                ]);
                                return;
                            }
                            header('Location: ?route=borrowed-tools&message=item_returned');
                            exit;
                        } catch (Exception $e) {
                            $db->rollBack();
                            error_log("Single item return error: " . $e->getMessage());

                            if ($this->isAjaxRequest()) {
                                header('Content-Type: application/json');
                                echo json_encode(['success' => false, 'message' => 'Failed to return item: ' . $e->getMessage()]);
                                return;
                            }
                            $errors[] = 'Failed to return item';
                        }
                    } else {
                        // For batches, use the existing batch return logic
                        $result = $batchModel->returnBatch($batchId, $returnedBy, $returnedItems, $notes);

                        if ($result['success']) {
                            if ($this->isAjaxRequest()) {
                                header('Content-Type: application/json');
                                echo json_encode([
                                    'success' => true,
                                    'message' => 'Batch returned successfully',
                                    'batch_id' => $batchId
                                ]);
                                return;
                            }
                            header('Location: ?route=borrowed-tools/batch/view&id=' . $batchId . '&message=batch_returned');
                            exit;
                        } else {
                            $errors[] = $result['message'];

                            if ($this->isAjaxRequest()) {
                                header('Content-Type: application/json');
                                echo json_encode(['success' => false, 'message' => $result['message']]);
                                return;
                            }
                        }
                    }
                }
            }

            // GET request - redirect to borrowed-tools list
            // The return modal is available in the index page
            header('Location: ?route=borrowed-tools');
            exit;

        } catch (Exception $e) {
            error_log("Batch return error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to process return: ' . $e->getMessage()
                ]);
                return;
            }

            $error = 'Failed to process return';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Extend batch return date
     */
    public function extendBatch() {
        // Set JSON header
        header('Content-Type: application/json');

        $this->requireProjectAssignment();

        // Only accept POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        try {
            CSRFProtection::validateRequest();

            $batchId = $_POST['batch_id'] ?? 0;
            $itemIds = $_POST['item_ids'] ?? [];
            $newExpectedReturn = $_POST['new_expected_return'] ?? '';
            $reason = Validator::sanitize($_POST['reason'] ?? '');

            // Validate inputs
            if (!$batchId) {
                echo json_encode(['success' => false, 'message' => 'Invalid batch ID']);
                return;
            }

            if (empty($itemIds) || !is_array($itemIds)) {
                echo json_encode(['success' => false, 'message' => 'No items selected']);
                return;
            }

            if (empty($newExpectedReturn)) {
                echo json_encode(['success' => false, 'message' => 'New expected return date is required']);
                return;
            }

            if (empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'Reason for extension is required']);
                return;
            }

            // Validate date format and ensure it's in the future
            $newDate = new DateTime($newExpectedReturn);
            $today = new DateTime();
            $today->setTime(0, 0, 0);

            if ($newDate < $today) {
                echo json_encode(['success' => false, 'message' => 'New return date must be today or in the future']);
                return;
            }

            // Extend the batch items
            $batchModel = new BorrowedToolBatchModel($this->db);
            $result = $batchModel->extendBatchItems($batchId, $itemIds, $newExpectedReturn, $reason, $this->auth->getCurrentUser()['id']);

            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Batch extend error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to extend batch: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancel batch
     */
    public function cancelBatch() {
        $this->requireProjectAssignment();

        // Only accept POST requests (can be handled by modal in index.php if needed)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=borrowed-tools&error=invalid_request');
            exit;
        }

        $batchId = $_POST['batch_id'] ?? 0;

        if (!$batchId) {
            header('Location: ?route=borrowed-tools&error=batch_id_required');
            exit;
        }

        try {
            CSRFProtection::validateRequest();

            $batchModel = new BorrowedToolBatchModel();
            $batch = $batchModel->getBatchWithItems($batchId, $this->getProjectFilter());

            if (!$batch) {
                header('Location: ?route=borrowed-tools&error=batch_not_found');
                exit;
            }

            // Check permission
            if (!$this->hasBorrowedToolPermission('cancel', $batch)) {
                header('Location: ?route=borrowed-tools&error=permission_denied');
                exit;
            }

            $reason = Validator::sanitize($_POST['cancellation_reason'] ?? '');
            $canceledBy = $this->auth->getCurrentUser()['id'];

            $result = $batchModel->cancelBatch($batchId, $canceledBy, $reason);

            if ($result['success']) {
                header('Location: ?route=borrowed-tools&message=batch_canceled');
                exit;
            } else {
                header('Location: ?route=borrowed-tools&error=' . urlencode($result['message']));
                exit;
            }

        } catch (Exception $e) {
            error_log("Batch cancellation error: " . $e->getMessage());
            header('Location: ?route=borrowed-tools&error=cancellation_failed');
            exit;
        }
    }

    /**
     * Print batch form (4 per page layout)
     *
     * Generates printable batch form with:
     * - Batch details and borrower information
     * - All items in batch with specifications
     * - MVA workflow signatures section
     * - Equipment condition assessment fields
     * - Optimized for 4 forms per page printing
     *
     * @return void Renders printable batch form
     */
    public function printBatchForm() {
        $batchId = $_GET['id'] ?? 0;

        if (!$batchId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        try {
            // Load AssetHelper for standalone print view
            require_once APP_ROOT . '/helpers/AssetHelper.php';

            $batchModel = new BorrowedToolBatchModel();
            $batch = $batchModel->getBatchWithItems($batchId, $this->getProjectFilter());

            if (!$batch) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }

            // Update printed_at timestamp
            $batchModel->update($batchId, ['printed_at' => date('Y-m-d H:i:s')]);

            include APP_ROOT . '/views/borrowed-tools/batch-print.php';

        } catch (Exception $e) {
            error_log("Print batch form error: " . $e->getMessage());
            $error = 'Failed to generate print form';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Print blank borrowing form with equipment types and subtypes from database
     *
     * Generates a blank form for manual borrowing requests with:
     * - Power tools section with subtypes (e.g., "Drill [Cordless, Electric, Hammer]")
     * - Hand tools section with subtypes
     * - Borrower information fields
     * - MVA workflow signature sections
     * - Space-optimized format for printing
     *
     * @return void Renders printable blank form
     */
    public function printBlankForm() {
        try {
            // Load AssetHelper for standalone print view
            require_once APP_ROOT . '/helpers/AssetHelper.php';

            $db = Database::getInstance()->getConnection();

            // Fetch Power Tools with all their subtypes grouped
            $powerToolsQuery = "
                SELECT
                    et.name as type_name,
                    GROUP_CONCAT(es.subtype_name ORDER BY es.subtype_name SEPARATOR ', ') as subtypes
                FROM equipment_types et
                INNER JOIN categories c ON et.category_id = c.id
                LEFT JOIN equipment_subtypes es ON et.id = es.equipment_type_id AND es.is_active = 1
                WHERE c.name IN ('Power Tools', 'Drilling Tools', 'Cutting Tools', 'Grinding Tools')
                  AND et.is_active = 1
                GROUP BY et.id, et.name
                ORDER BY et.name ASC
            ";
            $powerToolsRaw = $db->query($powerToolsQuery)->fetchAll(PDO::FETCH_ASSOC);

            // Format: Type [Subtype1, Subtype2, ...]
            $powerTools = [];
            foreach ($powerToolsRaw as $tool) {
                if ($tool['subtypes']) {
                    $powerTools[] = [
                        'display_name' => $tool['type_name'] . ' [' . $tool['subtypes'] . ']'
                    ];
                } else {
                    $powerTools[] = [
                        'display_name' => $tool['type_name']
                    ];
                }
            }

            // Fetch Hand Tools with all their subtypes grouped
            $handToolsQuery = "
                SELECT
                    et.name as type_name,
                    GROUP_CONCAT(es.subtype_name ORDER BY es.subtype_name SEPARATOR ', ') as subtypes
                FROM equipment_types et
                INNER JOIN categories c ON et.category_id = c.id
                LEFT JOIN equipment_subtypes es ON et.id = es.equipment_type_id AND es.is_active = 1
                WHERE c.name IN ('Hand Tools', 'Fastening Tools', 'Measuring Tools')
                  AND et.is_active = 1
                GROUP BY et.id, et.name
                ORDER BY et.name ASC
            ";
            $handToolsRaw = $db->query($handToolsQuery)->fetchAll(PDO::FETCH_ASSOC);

            // Format: Type [Subtype1, Subtype2, ...]
            $handTools = [];
            foreach ($handToolsRaw as $tool) {
                if ($tool['subtypes']) {
                    $handTools[] = [
                        'display_name' => $tool['type_name'] . ' [' . $tool['subtypes'] . ']'
                    ];
                } else {
                    $handTools[] = [
                        'display_name' => $tool['type_name']
                    ];
                }
            }

            include APP_ROOT . '/views/borrowed-tools/print-blank-form.php';

        } catch (Exception $e) {
            error_log("Print blank form error: " . $e->getMessage());
            // Fallback to empty arrays if database fails
            $powerTools = [];
            $handTools = [];
            include APP_ROOT . '/views/borrowed-tools/print-blank-form.php';
        }
    }
}
?>
