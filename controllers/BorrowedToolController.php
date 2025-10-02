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
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }

    /**
     * Centralized RBAC permission check for borrowed tools
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
        
        // Handle MVA workflow permissions
        switch ($action) {
            case 'create':
                // Maker: Warehouseman
                return in_array($userRole, ['Warehouseman']);
                
            case 'create_and_process':
                // For streamlined workflow when same user can do all steps (Basic tools only)
                // Only Warehouseman can do streamlined processing for Basic tools
                return in_array($userRole, ['Warehouseman']) && !$isCritical;
                
            case 'verify':
                // Verifier: Project Manager (for critical tools only)
                // Basic tools skip verification step in streamlined workflow
                if ($isCritical) {
                    return in_array($userRole, ['Project Manager']);
                } else {
                    // Basic tools don't need separate verification
                    return false;
                }
                
            case 'approve':
                // Authorizer: Asset Director/Finance Director (for critical tools only)
                // Basic tools skip approval step in streamlined workflow
                if ($isCritical) {
                    return in_array($userRole, ['Asset Director', 'Finance Director']);
                } else {
                    // Basic tools don't need separate approval
                    return false;
                }
                
            case 'borrow':
                // After approval, Warehouseman can mark as borrowed
                return in_array($userRole, ['Warehouseman']);
                
            case 'return':
                // Can return if user is Warehouseman or Site Inventory Clerk
                return in_array($userRole, ['Warehouseman', 'Site Inventory Clerk']);
                
            case 'extend':
                // Can extend if user has management permissions
                return in_array($userRole, ['Asset Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk']);
                
            case 'cancel':
                // Can cancel if user created the request or has management permissions
                if ($tool && $tool['issued_by'] == $currentUser['id']) {
                    return true;
                }
                return in_array($userRole, ['Project Manager', 'Asset Director', 'Warehouseman']);
                
            case 'view':
                // All relevant roles can view
                return in_array($userRole, ['Asset Director', 'Finance Director', 'Warehouseman', 'Project Manager', 'Site Inventory Clerk']);
                
            default:
                // Check standard role configuration
                $allowedRoles = $this->roleConfig['borrowed-tools/' . $action] ?? [];
                return in_array($userRole, $allowedRoles);
        }
    }

    /**
     * Display borrowed tools listing
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
        $perPage = 20;
        
        // Build filters
        $filters = [];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
        if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
        
        // Apply project filtering based on user role
        $projectFilter = $this->getProjectFilter();
        if ($projectFilter) {
            // Operational roles: filter by their assigned project
            $filters['project_id'] = $projectFilter;
        }
        // MVA oversight roles: no project filtering (see all projects)
        
        // Debug: Log the project filter being applied (remove this after testing)
        error_log("DEBUG - Borrowed Tools Filter: User=" . ($currentUser['full_name'] ?? 'Unknown') . " (ID:" . ($currentUser['id'] ?? 'N/A') . "), Role=" . ($currentUser['role_name'] ?? 'N/A') . ", ProjectID=" . $projectFilter);
        
        // Debug: Test what should be visible for this user
        if ($projectFilter) {
            $db = Database::getInstance()->getConnection();
            $testSql = "SELECT bt.id, bt.asset_id, a.name as asset_name, a.project_id, p.name as project_name 
                       FROM borrowed_tools bt 
                       INNER JOIN assets a ON bt.asset_id = a.id 
                       INNER JOIN projects p ON a.project_id = p.id 
                       WHERE bt.status IN ('Borrowed', 'Pending Verification', 'Pending Approval', 'Approved')";
            $testStmt = $db->prepare($testSql);
            $testStmt->execute();
            $allTools = $testStmt->fetchAll();
            error_log("DEBUG - All borrowed tools in DB: " . json_encode($allTools));
            
            $filteredSql = $testSql . " AND a.project_id = ?";
            $filteredStmt = $db->prepare($filteredSql);
            $filteredStmt->execute([$projectFilter]);
            $filteredTools = $filteredStmt->fetchAll();
            error_log("DEBUG - Filtered tools for project " . $projectFilter . ": " . json_encode($filteredTools));
        }
        
        try {
            // Get borrowed tools with pagination
            $result = $this->borrowedToolModel->getBorrowedToolsWithFilters($filters, $page, $perPage);
            $borrowedTools = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];
            
            // Debug: Log what we actually got back (remove this after testing)
            $projectsInResults = array_unique(array_column($borrowedTools, 'project_name'));
            error_log("DEBUG - Results: Count=" . count($borrowedTools) . ", Projects=" . json_encode($projectsInResults));
            
            // Get statistics (filtered by project for operational roles, all projects for MVA oversight roles)
            $borrowedToolStats = $this->borrowedToolModel->getBorrowedToolStats(null, null, $projectFilter);
            
            // Get overdue tools (filtered by project for operational roles, all projects for MVA oversight roles)
            $overdueTools = $this->borrowedToolModel->getOverdueBorrowedTools($projectFilter);
            
            $pageTitle = 'Borrowed Tools - ConstructLink™';
            $pageHeader = 'Borrowed Tools Management';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/borrowed-tools/index.php';
            
        } catch (Exception $e) {
            error_log("Borrowed tools listing error: " . $e->getMessage());
            $error = 'Failed to load borrowed tools';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display create borrowed tool form
     */
    public function create() {
        // Centralized RBAC: Only users with create permission can access
        if (!$this->hasBorrowedToolPermission('create')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        $formData = [];
        
        // Check if user has an assigned project
        $currentUser = $this->auth->getCurrentUser();
        if (!$currentUser['current_project_id']) {
            $errors[] = 'You must be assigned to a project to borrow tools. Please contact your administrator.';
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            $formData = [
                'asset_id' => (int)($_POST['asset_id'] ?? 0),
                'borrower_name' => Validator::sanitize($_POST['borrower_name'] ?? ''),
                'borrower_contact' => Validator::sanitize($_POST['borrower_contact'] ?? ''),
                'expected_return' => $_POST['expected_return'] ?? '',
                'purpose' => Validator::sanitize($_POST['purpose'] ?? ''),
                'condition_out' => Validator::sanitize($_POST['condition_out'] ?? ''),
                'issued_by' => $this->auth->getCurrentUser()['id']
            ];
            
            // Check if this is a basic tool that can use streamlined workflow
            $isCriticalTool = false;
            if ($formData['asset_id']) {
                $isCriticalTool = $this->borrowedToolModel->isCriticalTool($formData['asset_id']);
            }
            
            if (!$isCriticalTool && $this->hasBorrowedToolPermission('create_and_process')) {
                // Streamlined workflow for basic tools - create and immediately process to borrowed status
                $result = $this->borrowedToolModel->createAndProcessBasicTool($formData);
                
                if ($result['success']) {
                    header('Location: ?route=borrowed-tools/view&id=' . $result['borrowed_tool']['id'] . '&message=tool_processed_streamlined');
                    exit;
                } else {
                    if (isset($result['errors'])) {
                        $errors = $result['errors'];
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            } else {
                // Standard MVA workflow for critical tools
                $result = $this->borrowedToolModel->createBorrowedTool($formData);
                
                if ($result['success']) {
                    $messageType = $isCriticalTool ? 'tool_critical_created' : 'tool_borrowed';
                    header('Location: ?route=borrowed-tools/view&id=' . $result['borrowed_tool']['id'] . '&message=' . $messageType);
                    exit;
                } else {
                    if (isset($result['errors'])) {
                        $errors = $result['errors'];
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
        }
        
        // Get available assets - with comprehensive filtering
        try {
            $availableAssets = $this->getAvailableAssetsForBorrowing();
        } catch (Exception $e) {
            error_log("Assets loading error: " . $e->getMessage());
            $availableAssets = [];
        }
        
        include APP_ROOT . '/views/borrowed-tools/create.php';
    }
    
    /**
     * Display borrowed tool details
     */
    public function view() {
        $borrowId = $_GET['id'] ?? 0;
        
        if (!$borrowId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        // Ensure user has project assignment (MVA oversight roles are exempt)
        $this->requireProjectAssignment();
        
        try {
            $borrowedTool = $this->borrowedToolModel->getBorrowedToolWithMVADetails($borrowId, $this->getProjectFilter());
            
            if (!$borrowedTool) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            $pageTitle = 'Borrowed Tool Details - ConstructLink™';
            $pageHeader = 'Borrowed Tool: ' . htmlspecialchars($borrowedTool['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Borrowed Tools', 'url' => '?route=borrowed-tools'],
                ['title' => 'View Details', 'url' => '?route=borrowed-tools/view&id=' . $borrowId]
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/borrowed-tools/view.php';
            
        } catch (Exception $e) {
            error_log("Borrowed tool view error: " . $e->getMessage());
            $error = 'Failed to load borrowed tool details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Return borrowed tool
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
     * Mark tool as overdue (AJAX)
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
     * Verify borrowed tool (Verifier step)
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
     * Approve borrowed tool (Authorizer step)
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
     * MVA oversight roles (System Admin, Finance Director, Asset Director) are exempt
     */
    private function requireProjectAssignment() {
        $currentUser = $this->auth->getCurrentUser();
        $mvaOversightRoles = ['System Admin', 'Finance Director', 'Asset Director'];
        
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
        $mvaOversightRoles = ['System Admin', 'Finance Director', 'Asset Director'];
        
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
     */
    private function getAvailableAssetsForBorrowing() {
        try {
            $db = Database::getInstance()->getConnection();
            $currentUser = $this->auth->getCurrentUser();
            $currentProjectId = $currentUser['current_project_id'] ?? null;
            
            // Build query with enhanced filtering
            $sql = "
                SELECT a.id, a.ref, a.name, a.status, 
                       c.name as category_name, 
                       c.is_consumable,
                       p.name as project_name,
                       a.acquisition_cost,
                       a.model,
                       a.serial_number
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
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
            
            $sql .= " ORDER BY a.name ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $allAvailableAssets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Now filter out assets that are currently in use
            $filteredAssets = [];
            
            foreach ($allAvailableAssets as $asset) {
                $assetId = $asset['id'];
                $isInUse = false;
                
                // Check if currently borrowed (not returned)
                $borrowCheck = $db->prepare("
                    SELECT COUNT(*) 
                    FROM borrowed_tools 
                    WHERE asset_id = ? 
                    AND status IN ('Pending Verification', 'Pending Approval', 'Approved', 'Borrowed', 'Overdue')
                ");
                $borrowCheck->execute([$assetId]);
                if ($borrowCheck->fetchColumn() > 0) {
                    $isInUse = true;
                }
                
                // Check if in pending/released withdrawal
                if (!$isInUse) {
                    $withdrawalCheck = $db->prepare("SELECT COUNT(*) FROM withdrawals WHERE asset_id = ? AND status IN ('pending', 'released')");
                    $withdrawalCheck->execute([$assetId]);
                    if ($withdrawalCheck->fetchColumn() > 0) {
                        $isInUse = true;
                    }
                }
                
                // Check if in pending/approved transfer
                if (!$isInUse) {
                    $transferCheck = $db->prepare("SELECT COUNT(*) FROM transfers WHERE asset_id = ? AND status IN ('pending', 'approved')");
                    $transferCheck->execute([$assetId]);
                    if ($transferCheck->fetchColumn() > 0) {
                        $isInUse = true;
                    }
                }
                
                // If not in use, add to filtered list
                if (!$isInUse) {
                    $filteredAssets[] = $asset;
                }
            }
            
            return $filteredAssets;
            
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
}
?>
