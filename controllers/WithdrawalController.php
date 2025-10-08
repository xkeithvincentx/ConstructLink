<?php
/**
 * ConstructLink™ Withdrawal Controller - MVA RBAC REFACTORED
 * Handles withdrawal management operations with centralized RBAC and MVA workflow
 */

class WithdrawalController {
    private $auth;
    private $withdrawalModel;
    private $roleConfig;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->withdrawalModel = new WithdrawalModel();
        $this->roleConfig = require APP_ROOT . '/config/roles.php';
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }

    /**
     * Centralized RBAC permission check for withdrawals
     */
    private function hasWithdrawalPermission($action, $withdrawal = null) {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        if ($userRole === 'System Admin') return true;
        $allowedRoles = $this->roleConfig['withdrawals/' . $action] ?? [];
        if (in_array($userRole, $allowedRoles)) return true;
        // Ownership-based checks for cancel/return
        if ($withdrawal && $action === 'cancel') {
            return $withdrawal['withdrawn_by'] == $currentUser['id'];
        }
        if ($withdrawal && $action === 'return') {
            return $withdrawal['receiver_name'] === $currentUser['full_name'];
        }
        return false;
    }

    /**
     * Display withdrawal listing
     */
    public function index() {
        // Centralized RBAC: Only users with view permission can access
        if (!$this->hasWithdrawalPermission('view')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        
        // Get current user for project filtering
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        $userProjectId = $currentUser['current_project_id'] ?? null;
        
        // Build filters
        $filters = [];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        if (!empty($_GET['project_id'])) $filters['project_id'] = $_GET['project_id'];
        if (!empty($_GET['asset_id'])) $filters['asset_id'] = $_GET['asset_id'];
        if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
        if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        
        // Apply project filtering based on user role and current project
        if ($userProjectId && !in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
            // Project-based roles see only their project's withdrawals
            $filters['project_id'] = $userProjectId;
        }
        
        try {
            // Get withdrawals with pagination
            $result = $this->withdrawalModel->getWithdrawalsWithFilters($filters, $page, $perPage);
            $withdrawals = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];
            
            // Get withdrawal statistics
            $withdrawalStats = $this->withdrawalModel->getWithdrawalStatistics($userProjectId && !in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director']) ? $userProjectId : null);
            
            // Get overdue withdrawals
            $overdueWithdrawals = $this->withdrawalModel->getOverdueWithdrawals($userProjectId && !in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director']) ? $userProjectId : null);
            
            // Get filter options
            $projectModel = new ProjectModel();
            $assetModel = new AssetModel();
            
            // Filter projects based on user access
            if ($userProjectId && !in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
                $projects = $projectModel->findAll(['id' => $userProjectId, 'is_active' => 1]);
            } else {
                $projects = $projectModel->getActiveProjects();
            }
            
            // Filter assets based on user's accessible projects
            if ($userProjectId && !in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
                $assets = $assetModel->findAll(['project_id' => $userProjectId], "name ASC", 100);
            } else {
                $assets = $assetModel->findAll([], "name ASC", 100);
            }
            
            $pageTitle = 'Withdrawals - ConstructLink™';
            $pageHeader = 'Asset Withdrawals';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Withdrawals', 'url' => '?route=withdrawals']
            ];
            
            // Pass auth instance to view
            $auth = $this->auth;
            
            include APP_ROOT . '/views/withdrawals/index.php';
            
        } catch (Exception $e) {
            error_log("Withdrawal listing error: " . $e->getMessage());
            $error = 'Failed to load withdrawals';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display create withdrawal form
     */
    public function create() {
        // Centralized RBAC: Only users with create permission can access
        if (!$this->hasWithdrawalPermission('create')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        $errors = [];
        $messages = [];
        $formData = [];

        // Pre-fill asset_id if passed from asset index page
        if (isset($_GET['asset_id']) && is_numeric($_GET['asset_id'])) {
            $formData['asset_id'] = (int)$_GET['asset_id'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRFProtection::validateRequest();
            
            // Process form submission
            $formData = [
                'asset_id' => (int)($_POST['asset_id'] ?? 0),
                'project_id' => (int)($_POST['project_id'] ?? 0),
                'purpose' => Validator::sanitize($_POST['purpose'] ?? ''),
                'receiver_name' => Validator::sanitize($_POST['receiver_name'] ?? ''),
                'quantity' => (int)($_POST['quantity'] ?? 1),
                'unit' => Validator::sanitize($_POST['unit'] ?? 'pcs'),
                'expected_return' => $_POST['expected_return'] ?? null,
                'notes' => Validator::sanitize($_POST['notes'] ?? ''),
                'withdrawn_by' => $this->auth->getCurrentUser()['id'],
                'status' => 'Pending Verification'
            ];
            
            // Validate
            if (empty($formData['asset_id'])) {
                $errors[] = 'Asset is required';
            }
            if (empty($formData['project_id'])) {
                $errors[] = 'Project is required';
            }
            if (empty($formData['purpose'])) {
                $errors[] = 'Purpose is required';
            }
            if (empty($formData['receiver_name'])) {
                $errors[] = 'Receiver name is required';
            }
            if ($formData['quantity'] <= 0) {
                $errors[] = 'Quantity must be greater than 0';
            }
            
            // Validate expected return date if provided
            if (!empty($formData['expected_return'])) {
                if (strtotime($formData['expected_return']) <= time()) {
                    $errors[] = 'Expected return date must be in the future';
                }
            }
            
            // Additional validation: Check if asset belongs to selected project
            if (!empty($formData['asset_id']) && !empty($formData['project_id'])) {
                try {
                    $assetModel = new AssetModel();
                    $asset = $assetModel->find($formData['asset_id']);
                    if ($asset && $asset['project_id'] != $formData['project_id']) {
                        $errors[] = 'Selected asset does not belong to the selected project';
                    }
                } catch (Exception $e) {
                    $errors[] = 'Failed to validate asset-project relationship';
                }
            }
            
            if (empty($errors)) {
                try {
                    $result = $this->withdrawalModel->createWithdrawal($formData);
                    
                    if ($result['success']) {
                        header('Location: ?route=withdrawals/view&id=' . $result['withdrawal']['id'] . '&message=withdrawal_created');
                        exit;
                    } else {
                        if (isset($result['errors'])) {
                            $errors = array_merge($errors, $result['errors']);
                        } else {
                            $errors[] = $result['message'];
                        }
                    }
                    
                } catch (Exception $e) {
                    error_log("Withdrawal creation error: " . $e->getMessage());
                    $errors[] = 'Failed to create withdrawal request.';
                }
            }
        }
        
        // Get form options
        try {
            $projectModel = new ProjectModel();
            
            $projects = $projectModel->getActiveProjects();
            $assets = [];
            
            $pageTitle = 'Create Withdrawal - ConstructLink™';
            $pageHeader = 'Create Withdrawal Request';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
                ['title' => 'Create Request', 'url' => '?route=withdrawals/create']
            ];
            
            include APP_ROOT . '/views/withdrawals/create.php';
            
        } catch (Exception $e) {
            error_log("Withdrawal create form error: " . $e->getMessage());
            $error = 'Failed to load form data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Verify withdrawal (Project Manager/Site Inventory Clerk step)
     */
    public function verify() {
        $withdrawalId = $_GET['id'] ?? 0;
        if (!$withdrawalId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        $errors = [];
        try {
            $withdrawal = $this->withdrawalModel->getWithdrawalWithDetails($withdrawalId);
            if (!$withdrawal) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            if (!$this->hasWithdrawalPermission('verify', $withdrawal)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            if ($withdrawal['status'] !== 'Pending Verification') {
                $errors[] = 'Withdrawal is not in pending verification status.';
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                $verificationNotes = Validator::sanitize($_POST['verification_notes'] ?? '');
                $verifiedBy = $this->auth->getCurrentUser()['id'];
                if (empty($errors)) {
                    $result = $this->withdrawalModel->verifyWithdrawal($withdrawalId, $verifiedBy, $verificationNotes);
                    if ($result['success']) {
                        header('Location: ?route=withdrawals/view&id=' . $withdrawalId . '&message=withdrawal_verified');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            $pageTitle = 'Verify Withdrawal - ConstructLink™';
            $pageHeader = 'Verify Withdrawal: ' . htmlspecialchars($withdrawal['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
                ['title' => 'Verify Withdrawal', 'url' => '?route=withdrawals/verify&id=' . $withdrawalId]
            ];
            include APP_ROOT . '/views/withdrawals/verify.php';
        } catch (Exception $e) {
            error_log("Withdrawal verification error: " . $e->getMessage());
            $error = 'Failed to process withdrawal verification';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Approve withdrawal (Project Manager step)
     */
    public function approve() {
        $withdrawalId = $_GET['id'] ?? 0;
        if (!$withdrawalId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        $errors = [];
        try {
            $withdrawal = $this->withdrawalModel->getWithdrawalWithDetails($withdrawalId);
            if (!$withdrawal) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            if (!$this->hasWithdrawalPermission('approve', $withdrawal)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            if ($withdrawal['status'] !== 'Pending Approval') {
                $errors[] = 'Withdrawal is not in pending approval status.';
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                $approvalNotes = Validator::sanitize($_POST['approval_notes'] ?? '');
                $approvedBy = $this->auth->getCurrentUser()['id'];
                if (empty($errors)) {
                    $result = $this->withdrawalModel->approveWithdrawal($withdrawalId, $approvedBy, $approvalNotes);
                    if ($result['success']) {
                        header('Location: ?route=withdrawals/view&id=' . $withdrawalId . '&message=withdrawal_approved');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            $pageTitle = 'Approve Withdrawal - ConstructLink™';
            $pageHeader = 'Approve Withdrawal: ' . htmlspecialchars($withdrawal['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
                ['title' => 'Approve Withdrawal', 'url' => '?route=withdrawals/approve&id=' . $withdrawalId]
            ];
            include APP_ROOT . '/views/withdrawals/approve.php';
        } catch (Exception $e) {
            error_log("Withdrawal approval error: " . $e->getMessage());
            $error = 'Failed to process withdrawal approval';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Release withdrawal (mark as released)
     */
    public function release() {
        $withdrawalId = $_GET['id'] ?? $_POST['withdrawal_id'] ?? 0;
        if (!$withdrawalId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        try {
            $withdrawal = $this->withdrawalModel->getWithdrawalWithDetails($withdrawalId);
            if (!$withdrawal) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            // Centralized RBAC: Only users with release permission can access
            if (!$this->hasWithdrawalPermission('release', $withdrawal)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            // Enforce status transition: must be Approved
            if ($withdrawal['status'] !== 'Approved') {
                $error = 'Withdrawal must be approved before release.';
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            
            // Handle GET request: Render release form
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $pageTitle = 'Release Asset - ConstructLink™';
                $pageHeader = 'Release Asset: ' . htmlspecialchars($withdrawal['asset_name']);
                $breadcrumbs = [
                    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                    ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
                    ['title' => 'Release Asset', 'url' => '?route=withdrawals/release&id=' . $withdrawalId]
                ];
                
                $auth = $this->auth;
                include APP_ROOT . '/views/withdrawals/release.php';
                return;
            }
            
            // Handle POST request: Process form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $user = $this->auth->getCurrentUser();
                error_log("WithdrawalController::release - User ID: {$user['id']}, Role: {$user['role_name']}");
                
                $formData = [
                    'authorization_level' => $_POST['authorization_level'] ?? '',
                    'asset_condition' => $_POST['asset_condition'] ?? '',
                    'receiver_verification' => Validator::sanitize($_POST['receiver_verification'] ?? ''),
                    'release_notes' => Validator::sanitize($_POST['release_notes'] ?? ''),
                    'emergency_reason' => Validator::sanitize($_POST['emergency_reason'] ?? ''),
                    'released_by' => $user['id']
                ];
                
                // Validate form fields
                if (empty($formData['authorization_level']) || !in_array($formData['authorization_level'], ['standard', 'emergency'])) {
                    $errors[] = 'Invalid or missing authorization level.';
                }
                if (empty($formData['asset_condition']) || !in_array($formData['asset_condition'], ['excellent', 'good', 'fair'])) {
                    $errors[] = 'Invalid or missing asset condition.';
                }
                if ($formData['authorization_level'] === 'emergency' && empty(trim($formData['emergency_reason']))) {
                    $errors[] = 'Emergency release requires a reason.';
                }
                if (empty($_POST['confirmAssetCondition']) || empty($_POST['confirmReceiverIdentity']) || 
                    empty($_POST['confirmAuthorization']) || empty($_POST['confirmResponsibility'])) {
                    $errors[] = 'All confirmation checkboxes must be checked.';
                }
                
                if (!empty($errors)) {
                    error_log("WithdrawalController::release - Validation errors: " . json_encode($errors));
                    $pageTitle = 'Release Asset - ConstructLink™';
                    $pageHeader = 'Release Asset: ' . htmlspecialchars($withdrawal['asset_name']);
                    $breadcrumbs = [
                        ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                        ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
                        ['title' => 'Release Asset', 'url' => '?route=withdrawals/release&id=' . $withdrawalId]
                    ];
                    $auth = $this->auth;
                    include APP_ROOT . '/views/withdrawals/release.php';
                    return;
                }
                
                // Build comprehensive release notes from form data (since database doesn't have separate columns)
                $releaseNotes = [];
                if (!empty($formData['authorization_level'])) {
                    $releaseNotes[] = "Authorization Level: " . ucfirst($formData['authorization_level']);
                }
                if (!empty($formData['asset_condition'])) {
                    $releaseNotes[] = "Asset Condition: " . ucfirst($formData['asset_condition']);
                }
                if (!empty($formData['receiver_verification'])) {
                    $releaseNotes[] = "Receiver Verified: " . $formData['receiver_verification'];
                }
                if (!empty($formData['emergency_reason'])) {
                    $releaseNotes[] = "Emergency Reason: " . $formData['emergency_reason'];
                }
                if (!empty($formData['release_notes'])) {
                    $releaseNotes[] = "Additional Notes: " . $formData['release_notes'];
                }
                
                $completeNotes = implode("\n", $releaseNotes);
                
                // Process release using the correct method
                $result = $this->withdrawalModel->releaseAsset($withdrawalId, $formData['released_by'], $completeNotes);
                if ($result['success']) {
                    error_log("WithdrawalController::release - Successfully released withdrawal ID $withdrawalId by user {$user['id']}");
                    header('Location: ?route=withdrawals/view&id=' . $withdrawalId . '&message=withdrawal_released');
                    exit;
                } else {
                    $errors[] = $result['message'] ?? 'Failed to release withdrawal.';
                    error_log("WithdrawalController::release - Failed to release withdrawal ID $withdrawalId: " . ($result['message'] ?? 'Unknown error'));
                    $pageTitle = 'Release Asset - ConstructLink™';
                    $pageHeader = 'Release Asset: ' . htmlspecialchars($withdrawal['asset_name']);
                    $breadcrumbs = [
                        ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                        ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
                        ['title' => 'Release Asset', 'url' => '?route=withdrawals/release&id=' . $withdrawalId]
                    ];
                    $auth = $this->auth;
                    include APP_ROOT . '/views/withdrawals/release.php';
                }
            }
            
        } catch (Exception $e) {
            error_log("Withdrawal release error: " . $e->getMessage());
            $error = 'Failed to process withdrawal release';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Return withdrawal (mark asset as returned)
     */
    public function return() {
        $withdrawalId = $_GET['id'] ?? 0;
        if (!$withdrawalId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        $errors = [];
        try {
            $withdrawal = $this->withdrawalModel->getWithdrawalWithDetails($withdrawalId);
            if (!$withdrawal) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            if (!$this->hasWithdrawalPermission('return', $withdrawal)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            if ($withdrawal['status'] !== 'Released') {
                $errors[] = 'This asset is not currently released and cannot be returned.';
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                $returnNotes = Validator::sanitize($_POST['return_notes'] ?? '');
                $returnedBy = $this->auth->getCurrentUser()['id'];
                if (empty($errors)) {
                    $result = $this->withdrawalModel->returnAsset($withdrawalId, $returnedBy, $returnNotes);
                    if ($result['success']) {
                        header('Location: ?route=withdrawals/view&id=' . $withdrawalId . '&message=withdrawal_returned');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            $pageTitle = 'Return Asset - ConstructLink™';
            $pageHeader = 'Return Asset: ' . htmlspecialchars($withdrawal['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
                ['title' => 'Return Asset', 'url' => '?route=withdrawals/return&id=' . $withdrawalId]
            ];
            include APP_ROOT . '/views/withdrawals/return.php';
        } catch (Exception $e) {
            error_log("Withdrawal return error: " . $e->getMessage());
            $error = 'Failed to process return request';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Cancel withdrawal
     */
    public function cancel() {
        $withdrawalId = $_GET['id'] ?? 0;
        
        if (!$withdrawalId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        
        try {
            $withdrawal = $this->withdrawalModel->getWithdrawalWithDetails($withdrawalId);
            
            if (!$withdrawal) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check if withdrawal can be canceled
            if (!in_array($withdrawal['status'], ['pending', 'released'])) {
                $errors[] = 'This withdrawal cannot be canceled in its current status.';
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $reason = Validator::sanitize($_POST['reason'] ?? '');
                $cancellationReason = Validator::sanitize($_POST['cancellation_reason'] ?? '');
                $customReason = Validator::sanitize($_POST['custom_reason'] ?? '');
                
                // Build complete reason
                $completeReason = $cancellationReason;
                if ($cancellationReason === 'other' && !empty($customReason)) {
                    $completeReason = $customReason;
                }
                if (!empty($reason)) {
                    $completeReason .= ' - ' . $reason;
                }
                
                if (empty($errors)) {
                    $result = $this->withdrawalModel->cancelWithdrawal($withdrawalId, $completeReason);
                    
                    if ($result['success']) {
                        header('Location: ?route=withdrawals&message=withdrawal_canceled');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Cancel Withdrawal - ConstructLink™';
            $pageHeader = 'Cancel Withdrawal Request';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
                ['title' => 'Cancel Request', 'url' => '?route=withdrawals/cancel&id=' . $withdrawalId]
            ];
            
            include APP_ROOT . '/views/withdrawals/cancel.php';
            
        } catch (Exception $e) {
            error_log("Withdrawal cancellation error: " . $e->getMessage());
            $error = 'Failed to process cancellation';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * View withdrawal details
     */
    public function view() {
        $withdrawalId = $_GET['id'] ?? 0;
        
        if (!$withdrawalId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $withdrawal = $this->withdrawalModel->getWithdrawalWithDetails($withdrawalId);
            
            if (!$withdrawal) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            $pageTitle = 'Withdrawal Details - ConstructLink™';
            $pageHeader = 'Withdrawal #' . $withdrawal['id'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
                ['title' => 'View Details', 'url' => '?route=withdrawals/view&id=' . $withdrawalId]
            ];
            
            $auth = $this->auth;
            
            include APP_ROOT . '/views/withdrawals/view.php';
            
        } catch (Exception $e) {
            error_log("Withdrawal view error: " . $e->getMessage());
            $error = 'Failed to load withdrawal details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Export withdrawals to Excel
     */
    public function export() {
        try {
            $filters = [];
            if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
            if (!empty($_GET['project_id'])) $filters['project_id'] = $_GET['project_id'];
            if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
            if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
            
            $result = $this->withdrawalModel->getWithdrawalsWithFilters($filters, 1, 10000);
            $withdrawals = $result['data'] ?? [];
            
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="withdrawals_' . date('Y-m-d') . '.xls"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            echo '<table border="1">';
            echo '<tr>';
            echo '<th>ID</th>';
            echo '<th>Asset Reference</th>';
            echo '<th>Asset Name</th>';
            echo '<th>Category</th>';
            echo '<th>Project</th>';
            echo '<th>Receiver</th>';
            echo '<th>Purpose</th>';
            echo '<th>Withdrawn By</th>';
            echo '<th>Status</th>';
            echo '<th>Expected Return</th>';
            echo '<th>Actual Return</th>';
            echo '<th>Created Date</th>';
            echo '</tr>';
            
            foreach ($withdrawals as $withdrawal) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($withdrawal['id']) . '</td>';
                echo '<td>' . htmlspecialchars($withdrawal['asset_ref']) . '</td>';
                echo '<td>' . htmlspecialchars($withdrawal['asset_name']) . '</td>';
                echo '<td>' . htmlspecialchars($withdrawal['category_name']) . '</td>';
                echo '<td>' . htmlspecialchars($withdrawal['project_name']) . '</td>';
                echo '<td>' . htmlspecialchars($withdrawal['receiver_name']) . '</td>';
                echo '<td>' . htmlspecialchars($withdrawal['purpose']) . '</td>';
                echo '<td>' . htmlspecialchars($withdrawal['withdrawn_by_name']) . '</td>';
                echo '<td>' . htmlspecialchars(ucfirst($withdrawal['status'])) . '</td>';
                echo '<td>' . ($withdrawal['expected_return'] ? date('Y-m-d', strtotime($withdrawal['expected_return'])) : '') . '</td>';
                echo '<td>' . ($withdrawal['actual_return'] ? date('Y-m-d', strtotime($withdrawal['actual_return'])) : '') . '</td>';
                echo '<td>' . date('Y-m-d H:i', strtotime($withdrawal['created_at'])) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            exit;
            
        } catch (Exception $e) {
            error_log("Withdrawal export error: " . $e->getMessage());
            header('Location: ?route=withdrawals&error=export_failed');
            exit;
        }
    }
    
    /**
     * Get available assets for withdrawal (excluding already borrowed/withdrawn/transferred assets)
     */
    private function getAvailableAssetsForWithdrawal() {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "
                SELECT a.*, c.name as category_name, c.is_consumable, p.name as project_name
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                WHERE p.is_active = 1
                  AND (
                      (c.is_consumable = 1 AND a.available_quantity > 0)
                      OR 
                      (c.is_consumable = 0 AND a.status = 'available' AND a.id NOT IN (
                          SELECT DISTINCT asset_id FROM borrowed_tools WHERE status = 'borrowed'
                          UNION
                          SELECT DISTINCT asset_id FROM withdrawals WHERE status IN ('Pending Verification', 'Pending Approval', 'Approved', 'Released')
                          UNION
                          SELECT DISTINCT asset_id FROM transfers WHERE status IN ('Pending Verification', 'Pending Approval', 'Approved')
                      ))
                  )
                ORDER BY c.is_consumable DESC, p.name ASC, a.name ASC
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get available assets for withdrawal error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * AJAX endpoint to get assets by project
     */
    public function getAssetsByProject() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $projectId = $_GET['project_id'] ?? 0;
        
        if (!$projectId) {
            echo json_encode(['success' => false, 'message' => 'Project ID required']);
            return;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "
                SELECT a.*, c.name as category_name, c.is_consumable
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                WHERE a.project_id = ?
                  AND (
                      (c.is_consumable = 1 AND a.available_quantity > 0)
                      OR 
                      (c.is_consumable = 0 AND a.status = 'available' AND a.id NOT IN (
                          SELECT DISTINCT asset_id FROM borrowed_tools WHERE status = 'borrowed'
                          UNION
                          SELECT DISTINCT asset_id FROM withdrawals WHERE status IN ('Pending Verification', 'Pending Approval', 'Approved', 'Released')
                          UNION
                          SELECT DISTINCT asset_id FROM transfers WHERE status IN ('Pending Verification', 'Pending Approval', 'Approved')
                      ))
                  )
                ORDER BY c.is_consumable DESC, a.name ASC
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$projectId]);
            $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'assets' => $assets]);
            
        } catch (Exception $e) {
            error_log("Get assets by project error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load assets']);
        }
    }
    
    /**
     * AJAX endpoint to get asset details for withdrawal form
     */
    public function getAssetDetails() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $assetId = $_GET['asset_id'] ?? 0;
        
        if (!$assetId) {
            echo json_encode(['success' => false, 'message' => 'Asset ID required']);
            return;
        }
        
        try {
            $asset = $this->withdrawalModel->getAssetForWithdrawal($assetId);
            
            if (!$asset) {
                echo json_encode(['success' => false, 'message' => 'Asset not found']);
                return;
            }
            
            echo json_encode(['success' => true, 'asset' => $asset]);
            
        } catch (Exception $e) {
            error_log("Get asset details error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load asset details']);
        }
    }

    /**
     * API endpoint to get withdrawal statistics (called by dashboard and sidebar)
     */
    public function getStats() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        try {
            $stats = $this->withdrawalModel->getWithdrawalStats();
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            error_log("Get withdrawal stats error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load statistics']);
        }
    }
    
    /**
     * API endpoint to create withdrawal request (for mobile/AJAX)
     */
    public function createWithdrawalAPI() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $data = [
                'asset_id' => $input['asset_id'] ?? 0,
                'project_id' => $input['project_id'] ?? 0,
                'purpose' => Validator::sanitize($input['purpose'] ?? ''),
                'receiver_name' => Validator::sanitize($input['receiver_name'] ?? ''),
                'quantity' => (int)($input['quantity'] ?? 1),
                'unit' => Validator::sanitize($input['unit'] ?? 'pcs'),
                'expected_return' => $input['expected_return'] ?? null,
                'notes' => Validator::sanitize($input['notes'] ?? ''),
                'withdrawn_by' => $this->auth->getCurrentUser()['id']
            ];
            
            $result = $this->withdrawalModel->createWithdrawal($data);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Create withdrawal API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to create withdrawal request']);
        }
    }
    
    /**
     * API endpoint to cancel withdrawal (for AJAX)
     */
    public function cancelWithdrawalAPI() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $withdrawalId = $input['withdrawal_id'] ?? 0;
            $reason = Validator::sanitize($input['reason'] ?? 'Canceled via API');
            
            if (!$withdrawalId) {
                echo json_encode(['success' => false, 'message' => 'Withdrawal ID required']);
                return;
            }
            
            $withdrawal = $this->withdrawalModel->find($withdrawalId);
            if (!$withdrawal) {
                echo json_encode(['success' => false, 'message' => 'Withdrawal not found']);
                return;
            }
            
            $currentUser = $this->auth->getCurrentUser();
            if (!$currentUser) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }
            
            $userRole = $currentUser['role_name'] ?? '';
            if (!in_array($userRole, ['System Admin', 'Asset Director']) && $withdrawal['withdrawn_by'] != $currentUser['id']) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $result = $this->withdrawalModel->cancelWithdrawal($withdrawalId, $reason);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Cancel withdrawal API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to cancel withdrawal']);
        }
    }
    
    /**
     * API endpoint to get withdrawal details (for AJAX)
     */
    public function getWithdrawalDetails() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $withdrawalId = $_GET['id'] ?? 0;
        
        if (!$withdrawalId) {
            echo json_encode(['success' => false, 'message' => 'Withdrawal ID required']);
            return;
        }
        
        try {
            $withdrawal = $this->withdrawalModel->getWithdrawalWithDetails($withdrawalId);
            
            if (!$withdrawal) {
                echo json_encode(['success' => false, 'message' => 'Withdrawal not found']);
                return;
            }
            
            $currentUser = $this->auth->getCurrentUser();
            if (!$currentUser) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }
            
            $userRole = $currentUser['role_name'] ?? '';
            if (!in_array($userRole, ['System Admin', 'Asset Director', 'Warehouseman', 'Project Manager']) && $withdrawal['withdrawn_by'] != $currentUser['id']) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $withdrawal
            ]);
            
        } catch (Exception $e) {
            error_log("Get withdrawal details API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load withdrawal details']);
        }
    }
    
    /**
     * API endpoint to get overdue withdrawals
     */
    public function getOverdueWithdrawals() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        try {
            $overdueWithdrawals = $this->withdrawalModel->getOverdueWithdrawals();
            
            echo json_encode([
                'success' => true,
                'data' => $overdueWithdrawals,
                'count' => count($overdueWithdrawals)
            ]);
            
        } catch (Exception $e) {
            error_log("Get overdue withdrawals API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load overdue withdrawals']);
        }
    }
}
?>