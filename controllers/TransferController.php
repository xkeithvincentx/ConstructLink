<?php
/**
 * ConstructLink™ Transfer Controller - ENHANCED VERSION
 * Handles inter-site asset transfer operations with centralized MVA RBAC
 */

class TransferController {
    private $auth;
    private $transferModel;
    private $roleConfig;
    
    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->transferModel = new TransferModel();
        $this->roleConfig = require APP_ROOT . '/config/roles.php';
        
        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }
    }
    
    /**
     * Check if user has permission for specific transfer action
     */
    private function hasTransferPermission($action, $transfer = null) {
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        // System Admin bypasses all checks
        if ($userRole === 'System Admin') {
            return true;
        }
        
        // Get allowed roles for this action
        $allowedRoles = $this->roleConfig['transfers/' . $action] ?? [];
        
        // Check if user has any of the allowed roles
        if (in_array($userRole, $allowedRoles)) {
            return true;
        }
        
        // For specific actions, check additional conditions
        if ($transfer && $action === 'cancel') {
            // Only the initiator can cancel their own transfers
            return $transfer['initiated_by'] == $currentUser['id'];
        }
        
        return false;
    }
    
    /**
     * Display transfer listing
     */
    public function index() {
        // Check permissions using centralized RBAC
        if (!$this->hasTransferPermission('view')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        
        // Build filters
        $filters = [];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        if (!empty($_GET['from_project'])) $filters['from_project'] = $_GET['from_project'];
        if (!empty($_GET['to_project'])) $filters['to_project'] = $_GET['to_project'];
        if (!empty($_GET['transfer_type'])) $filters['transfer_type'] = $_GET['transfer_type'];
        if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
        if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
        if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
        
        try {
            // Get transfers with pagination
            $result = $this->transferModel->getTransfersWithFilters($filters, $page, $perPage);
            $transfers = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];
            
            // Get transfer statistics (with user-specific counts)
            $currentUser = $this->auth->getCurrentUser();
            $transferStats = $this->transferModel->getTransferStatistics(null, null, $currentUser['id'] ?? null);
            
            // Get overdue returns
            $overdueReturns = $this->transferModel->getOverdueReturns();
            
            // Get filter options
            $projectModel = new ProjectModel();
            $assetModel = new AssetModel();

            // For transfers, get ALL active projects (not filtered by role)
            $projects = $projectModel->getAllActiveProjects();
            $assets = $assetModel->findAll([], "name ASC", 100);
            
            $pageTitle = 'Asset Transfers - ConstructLink™';
            $pageHeader = 'Asset Transfers';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Transfers', 'url' => '?route=transfers']
            ];
            
            // Pass auth instance and role config to view
            $auth = $this->auth;
            $roleConfig = $this->roleConfig;
            
            include APP_ROOT . '/views/transfers/index.php';
            
        } catch (Exception $e) {
            error_log("Transfer listing error: " . $e->getMessage());
            $error = 'Failed to load transfers';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Display create transfer form
     */
    public function create() {
        // Check permissions using centralized RBAC
        if (!$this->hasTransferPermission('create')) {
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
                'asset_id' => (int)($_POST['asset_id'] ?? 0),
                'from_project' => (int)($_POST['from_project'] ?? 0),
                'to_project' => (int)($_POST['to_project'] ?? 0),
                'reason' => Validator::sanitize($_POST['reason'] ?? ''),
                'transfer_type' => $_POST['transfer_type'] ?? 'permanent',
                'transfer_date' => $_POST['transfer_date'] ?? date('Y-m-d'),
                'expected_return' => !empty($_POST['expected_return']) ? $_POST['expected_return'] : null,
                'notes' => Validator::sanitize($_POST['notes'] ?? ''),
                'initiated_by' => $this->auth->getCurrentUser()['id']
            ];
            
            // Validate
            if (empty($formData['asset_id'])) {
                $errors[] = 'Asset is required';
            }
            if (empty($formData['from_project'])) {
                $errors[] = 'Source project is required';
            }
            if (empty($formData['to_project'])) {
                $errors[] = 'Destination project is required';
            }
            if (empty($formData['reason'])) {
                $errors[] = 'Transfer reason is required';
            }
            if ($formData['from_project'] == $formData['to_project']) {
                $errors[] = 'Source and destination projects must be different';
            }
            
            // Validate transfer date
            if (strtotime($formData['transfer_date']) < strtotime(date('Y-m-d'))) {
                $errors[] = 'Transfer date cannot be in the past';
            }
            
            // For temporary transfers, validate expected return date
            if ($formData['transfer_type'] === 'temporary') {
                if (empty($formData['expected_return'])) {
                    $errors[] = 'Expected return date is required for temporary transfers';
                } elseif (strtotime($formData['expected_return']) <= strtotime($formData['transfer_date'])) {
                    $errors[] = 'Expected return date must be after transfer date';
                }
            }
            
            if (empty($errors)) {
                try {
                    $result = $this->transferModel->createTransfer($formData);
                    
                    if ($result['success']) {
                        $currentUser = $this->auth->getCurrentUser();
                        $userRole = $currentUser['role_name'] ?? '';
                        
                        // Different success messages based on workflow
                        if (in_array($userRole, ['Finance Director', 'Asset Director'])) {
                            $message = 'transfer_streamlined';
                        } elseif ($userRole === 'Project Manager') {
                            $message = 'transfer_simplified';
                        } else {
                            $message = 'transfer_created';
                        }
                        
                        header('Location: ?route=transfers/view&id=' . $result['transfer']['id'] . '&message=' . $message);
                        exit;
                    } else {
                        if (isset($result['errors'])) {
                            $errors = array_merge($errors, $result['errors']);
                        } else {
                            $errors[] = $result['message'];
                        }
                    }
                    
                } catch (Exception $e) {
                    error_log("Transfer creation error: " . $e->getMessage());
                    $errors[] = 'Failed to create transfer request.';
                }
            }
        }
        
        // Get form options
        try {
            $projectModel = new ProjectModel();

            // For transfers, get ALL active projects (not filtered by role)
            // Project Managers need to see all projects to transfer assets between sites
            $projects = $projectModel->getAllActiveProjects();
            $availableAssets = $this->getAvailableAssetsForTransfer();
            
            $pageTitle = 'Create Transfer Request - ConstructLink™';
            $pageHeader = 'Create Transfer Request';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Transfers', 'url' => '?route=transfers'],
                ['title' => 'Create Request', 'url' => '?route=transfers/create']
            ];
            
            include APP_ROOT . '/views/transfers/create.php';
            
        } catch (Exception $e) {
            error_log("Transfer create form error: " . $e->getMessage());
            $error = 'Failed to load form data';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Verify transfer (Project Manager step)
     */
    public function verify() {
        // Check permissions using centralized RBAC
        if (!$this->hasTransferPermission('verify')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $transferId = $_GET['id'] ?? 0;
        if (!$transferId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $transfer = $this->transferModel->getTransferWithDetails($transferId);
            if (!$transfer) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check if transfer is in correct status for verification
            if ($transfer['status'] !== 'Pending Verification') {
                $errors[] = 'This transfer is not in pending verification status.';
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $verificationNotes = Validator::sanitize($_POST['verification_notes'] ?? '');
                $verifiedBy = $this->auth->getCurrentUser()['id'];
                
                if (empty($errors)) {
                    $result = $this->transferModel->verifyTransfer($transferId, $verifiedBy, $verificationNotes);
                    
                    if ($result['success']) {
                        header('Location: ?route=transfers/view&id=' . $transferId . '&message=transfer_verified');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Verify Transfer - ConstructLink™';
            $pageHeader = 'Verify Transfer: ' . htmlspecialchars($transfer['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Transfers', 'url' => '?route=transfers'],
                ['title' => 'Verify Transfer', 'url' => '?route=transfers/verify&id=' . $transferId]
            ];
            
            include APP_ROOT . '/views/transfers/verify.php';
            
        } catch (Exception $e) {
            error_log("Transfer verification error: " . $e->getMessage());
            $error = 'Failed to process transfer verification';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Approve transfer (Finance Director/Asset Director step)
     */
    public function approve() {
        $transferId = $_GET['id'] ?? 0;
        if (!$transferId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        try {
            $transfer = $this->transferModel->getTransferWithDetails($transferId);
            if (!$transfer) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check permissions using centralized RBAC
            if (!$this->hasTransferPermission('approve', $transfer)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            
            // Check if transfer is in correct status
            if ($transfer['status'] !== 'Pending Approval') {
                $errors[] = 'This transfer is not in pending approval status and cannot be approved.';
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $approvalNotes = Validator::sanitize($_POST['approval_notes'] ?? '');
                $approvedBy = $this->auth->getCurrentUser()['id'];
                
                if (empty($errors)) {
                    $result = $this->transferModel->approveTransfer($transferId, $approvedBy, $approvalNotes);
                    
                    if ($result['success']) {
                        header('Location: ?route=transfers/view&id=' . $transferId . '&message=transfer_approved');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Approve Transfer - ConstructLink™';
            $pageHeader = 'Approve Transfer: ' . htmlspecialchars($transfer['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Transfers', 'url' => '?route=transfers'],
                ['title' => 'Approve Transfer', 'url' => '?route=transfers/approve&id=' . $transferId]
            ];
            
            include APP_ROOT . '/views/transfers/approve.php';
            
        } catch (Exception $e) {
            error_log("Transfer approval error: " . $e->getMessage());
            $error = 'Failed to process transfer approval';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Dispatch transfer (FROM Project Manager confirms asset sent)
     */
    public function dispatch() {
        $transferId = $_GET['id'] ?? 0;
        if (!$transferId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        $errors = [];

        try {
            $transfer = $this->transferModel->getTransferWithDetails($transferId);
            if (!$transfer) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }

            // Check permissions using centralized RBAC
            if (!$this->hasTransferPermission('dispatch', $transfer)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }

            // Check if transfer is in correct status
            if ($transfer['status'] !== 'Approved') {
                $errors[] = 'This transfer must be approved before dispatch.';
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();

                $dispatchNotes = Validator::sanitize($_POST['dispatch_notes'] ?? '');
                $dispatchedBy = $this->auth->getCurrentUser()['id'];

                if (empty($errors)) {
                    $result = $this->transferModel->dispatchTransfer($transferId, $dispatchedBy, $dispatchNotes);

                    if ($result['success']) {
                        header('Location: ?route=transfers/view&id=' . $transferId . '&message=transfer_dispatched');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }

            $pageTitle = 'Dispatch Transfer - ConstructLink™';
            $pageHeader = 'Dispatch Transfer: ' . htmlspecialchars($transfer['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Transfers', 'url' => '?route=transfers'],
                ['title' => 'Dispatch Transfer', 'url' => '?route=transfers/dispatch&id=' . $transferId]
            ];

            include APP_ROOT . '/views/transfers/dispatch.php';

        } catch (Exception $e) {
            error_log("Transfer dispatch error: " . $e->getMessage());
            $error = 'Failed to process transfer dispatch';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Receive transfer (TO Project Manager acknowledges receipt)
     */
    public function receive() {
        // Check permissions using centralized RBAC
        if (!$this->hasTransferPermission('receive')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $transferId = $_GET['id'] ?? 0;
        if (!$transferId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $transfer = $this->transferModel->getTransferWithDetails($transferId);
            if (!$transfer) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check if transfer is in correct status
            if ($transfer['status'] !== 'In Transit') {
                $errors[] = 'Transfer must be dispatched (In Transit) before receiving.';
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $receiptNotes = Validator::sanitize($_POST['receipt_notes'] ?? '');
                $receivedBy = $this->auth->getCurrentUser()['id'];
                
                if (empty($errors)) {
                    $result = $this->transferModel->receiveTransfer($transferId, $receivedBy, $receiptNotes);
                    
                    if ($result['success']) {
                        header('Location: ?route=transfers/view&id=' . $transferId . '&message=transfer_received');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Receive Transfer - ConstructLink™';
            $pageHeader = 'Receive Transfer: ' . htmlspecialchars($transfer['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Transfers', 'url' => '?route=transfers'],
                ['title' => 'Receive Transfer', 'url' => '?route=transfers/receive&id=' . $transferId]
            ];
            
            include APP_ROOT . '/views/transfers/receive.php';
            
        } catch (Exception $e) {
            error_log("Transfer receipt error: " . $e->getMessage());
            $error = 'Failed to process transfer receipt';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Complete transfer (move asset to destination)
     */
    public function complete() {
        // Check permissions using centralized RBAC
        if (!$this->hasTransferPermission('complete')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $transferId = $_GET['id'] ?? 0;
        
        if (!$transferId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $transfer = $this->transferModel->getTransferWithDetails($transferId);
            
            if (!$transfer) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check if transfer is in correct status
            if ($transfer['status'] !== 'Received') {
                $errors[] = 'Transfer must be received before completion.';
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $completionNotes = Validator::sanitize($_POST['completion_notes'] ?? '');
                
                if (empty($errors)) {
                    $result = $this->transferModel->completeTransfer($transferId, $this->auth->getCurrentUser()['id'], $completionNotes);
                    
                    if ($result['success']) {
                        header('Location: ?route=transfers/view&id=' . $transferId . '&message=transfer_completed');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Complete Transfer - ConstructLink™';
            $pageHeader = 'Complete Transfer: ' . htmlspecialchars($transfer['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Transfers', 'url' => '?route=transfers'],
                ['title' => 'Complete Transfer', 'url' => '?route=transfers/complete&id=' . $transferId]
            ];
            
            include APP_ROOT . '/views/transfers/complete.php';
            
        } catch (Exception $e) {
            error_log("Transfer completion error: " . $e->getMessage());
            $error = 'Failed to process transfer completion';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Cancel transfer
     */
    public function cancel() {
        $transferId = $_GET['id'] ?? 0;
        
        if (!$transferId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        
        try {
            // Get transfer with all details (including joined data)
            $transfer = $this->transferModel->getTransferWithDetails($transferId);
            
            if (!$transfer) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check permissions using centralized RBAC
            if (!$this->hasTransferPermission('cancel', $transfer)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            
            // Check if transfer can be canceled
            if (!in_array($transfer['status'], ['Pending Verification', 'Pending Approval'])) {
                $errors[] = 'This transfer cannot be canceled in its current status.';
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
                    $result = $this->transferModel->cancelTransfer($transferId, $completeReason);
                    
                    if ($result['success']) {
                        header('Location: ?route=transfers&message=transfer_canceled');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Cancel Transfer - ConstructLink™';
            $pageHeader = 'Cancel Transfer Request';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Transfers', 'url' => '?route=transfers'],
                ['title' => 'Cancel Request', 'url' => '?route=transfers/cancel&id=' . $transferId]
            ];
            
            include APP_ROOT . '/views/transfers/cancel.php';
            
        } catch (Exception $e) {
            error_log("Transfer cancellation error: " . $e->getMessage());
            $error = 'Failed to process cancellation';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * View transfer details
     */
    public function view() {
        $transferId = $_GET['id'] ?? 0;
        
        if (!$transferId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        try {
            $transfer = $this->transferModel->getTransferWithDetails($transferId);
            
            if (!$transfer) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check permissions using centralized RBAC
            if (!$this->hasTransferPermission('view', $transfer)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }
            
            $pageTitle = 'Transfer Details - ConstructLink™';
            $pageHeader = 'Transfer #' . $transfer['id'];
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Transfers', 'url' => '?route=transfers'],
                ['title' => 'View Details', 'url' => '?route=transfers/view&id=' . $transferId]
            ];
            
            // Pass auth instance and role config to view
            $auth = $this->auth;
            $roleConfig = $this->roleConfig;
            $user = $this->auth->getCurrentUser();
            
            include APP_ROOT . '/views/transfers/view.php';
            
        } catch (Exception $e) {
            error_log("Transfer view error: " . $e->getMessage());
            $error = 'Failed to load transfer details';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Initiate return process for temporary transfer
     */
    public function returnAsset() {
        // Check permissions using centralized RBAC
        if (!$this->hasTransferPermission('returnAsset')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $transferId = $_GET['id'] ?? 0;
        
        if (!$transferId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $transfer = $this->transferModel->getTransferWithDetails($transferId);
            
            if (!$transfer) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check if transfer can be returned
            if ($transfer['status'] !== 'Completed' || $transfer['transfer_type'] !== 'temporary') {
                $errors[] = 'Only completed temporary transfers can be returned.';
            }
            
            if ($transfer['return_status'] !== 'not_returned') {
                $errors[] = 'Return process already initiated or completed for this transfer.';
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $returnNotes = Validator::sanitize($_POST['return_notes'] ?? '');
                $currentUser = $this->auth->getCurrentUser();
                
                if (empty($errors)) {
                    $result = $this->transferModel->initiateReturn($transferId, $currentUser['id'], $returnNotes);
                    
                    if ($result['success']) {
                        header('Location: ?route=transfers/view&id=' . $transferId . '&message=return_initiated');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Initiate Asset Return - ConstructLink™';
            $pageHeader = 'Initiate Return: ' . htmlspecialchars($transfer['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Transfers', 'url' => '?route=transfers'],
                ['title' => 'Initiate Return', 'url' => '?route=transfers/return&id=' . $transferId]
            ];
            
            include APP_ROOT . '/views/transfers/return.php';
            
        } catch (Exception $e) {
            error_log("Asset return initiation error: " . $e->getMessage());
            $error = 'Failed to process asset return initiation';
            include APP_ROOT . '/views/errors/500.php';
        }
    }

    /**
     * Receive returned asset at origin project
     */
    public function receiveReturn() {
        // Check permissions using centralized RBAC
        if (!$this->hasTransferPermission('receiveReturn')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        $transferId = $_GET['id'] ?? 0;
        
        if (!$transferId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }
        
        $errors = [];
        $messages = [];
        
        try {
            $transfer = $this->transferModel->getTransferWithDetails($transferId);
            
            if (!$transfer) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }
            
            // Check if return can be received
            if ($transfer['return_status'] !== 'in_return_transit') {
                $errors[] = 'Return must be in transit to be received.';
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                
                $receiptNotes = Validator::sanitize($_POST['receipt_notes'] ?? '');
                $currentUser = $this->auth->getCurrentUser();
                
                if (empty($errors)) {
                    $result = $this->transferModel->receiveReturn($transferId, $currentUser['id'], $receiptNotes);
                    
                    if ($result['success']) {
                        header('Location: ?route=transfers/view&id=' . $transferId . '&message=return_completed');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }
            
            $pageTitle = 'Receive Returned Asset - ConstructLink™';
            $pageHeader = 'Receive Return: ' . htmlspecialchars($transfer['asset_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Transfers', 'url' => '?route=transfers'],
                ['title' => 'Receive Return', 'url' => '?route=transfers/receive-return&id=' . $transferId]
            ];
            
            include APP_ROOT . '/views/transfers/receive_return.php';
            
        } catch (Exception $e) {
            error_log("Asset return receipt error: " . $e->getMessage());
            $error = 'Failed to process asset return receipt';
            include APP_ROOT . '/views/errors/500.php';
        }
    }
    
    /**
     * Export transfers to Excel
     */
    public function export() {
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Asset Director', 'Finance Director'])) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }
        
        try {
            // Build filters from GET parameters
            $filters = [];
            if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
            if (!empty($_GET['from_project'])) $filters['from_project'] = $_GET['from_project'];
            if (!empty($_GET['to_project'])) $filters['to_project'] = $_GET['to_project'];
            if (!empty($_GET['transfer_type'])) $filters['transfer_type'] = $_GET['transfer_type'];
            if (!empty($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
            if (!empty($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
            
            // Get all transfers (no pagination for export)
            $result = $this->transferModel->getTransfersWithFilters($filters, 1, 10000);
            $transfers = $result['data'] ?? [];
            
            // Set headers for Excel download
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="transfers_' . date('Y-m-d') . '.xls"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output Excel content
            echo '<table border="1">';
            echo '<tr>';
            echo '<th>ID</th>';
            echo '<th>Asset Reference</th>';
            echo '<th>Asset Name</th>';
            echo '<th>Category</th>';
            echo '<th>From Project</th>';
            echo '<th>To Project</th>';
            echo '<th>Transfer Type</th>';
            echo '<th>Reason</th>';
            echo '<th>Initiated By</th>';
            echo '<th>Status</th>';
            echo '<th>Transfer Date</th>';
            echo '<th>Expected Return</th>';
            echo '<th>Actual Return</th>';
            echo '<th>Created Date</th>';
            echo '</tr>';
            
            foreach ($transfers as $transfer) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($transfer['id']) . '</td>';
                echo '<td>' . htmlspecialchars($transfer['asset_ref']) . '</td>';
                echo '<td>' . htmlspecialchars($transfer['asset_name']) . '</td>';
                echo '<td>' . htmlspecialchars($transfer['category_name']) . '</td>';
                echo '<td>' . htmlspecialchars($transfer['from_project_name']) . '</td>';
                echo '<td>' . htmlspecialchars($transfer['to_project_name']) . '</td>';
                echo '<td>' . htmlspecialchars(ucfirst($transfer['transfer_type'])) . '</td>';
                echo '<td>' . htmlspecialchars($transfer['reason']) . '</td>';
                echo '<td>' . htmlspecialchars($transfer['initiated_by_name']) . '</td>';
                echo '<td>' . htmlspecialchars(ucfirst($transfer['status'])) . '</td>';
                echo '<td>' . ($transfer['transfer_date'] ? date('Y-m-d', strtotime($transfer['transfer_date'])) : '') . '</td>';
                echo '<td>' . ($transfer['expected_return'] ? date('Y-m-d', strtotime($transfer['expected_return'])) : '') . '</td>';
                echo '<td>' . ($transfer['actual_return'] ? date('Y-m-d', strtotime($transfer['actual_return'])) : '') . '</td>';
                echo '<td>' . date('Y-m-d H:i', strtotime($transfer['created_at'])) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            exit;
            
        } catch (Exception $e) {
            error_log("Transfer export error: " . $e->getMessage());
            header('Location: ?route=transfers&error=export_failed');
            exit;
        }
    }
    
    /**
     * Get available assets for transfer (excluding already borrowed/withdrawn/transferred assets)
     * Project Managers can see assets from all sites for transfer purposes
     */
    private function getAvailableAssetsForTransfer() {
        try {
            $db = Database::getInstance()->getConnection();
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            
            // Base query
            $sql = "
                SELECT a.*, c.name as category_name, p.name as project_name, p.location as project_location
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                WHERE a.status = 'available'
                  AND p.is_active = 1
                  AND a.id NOT IN (
                      SELECT DISTINCT asset_id FROM borrowed_tools WHERE status = 'borrowed'
                      UNION
                      SELECT DISTINCT asset_id FROM withdrawals WHERE status IN ('pending', 'released')
                      UNION
                      SELECT DISTINCT asset_id FROM transfers WHERE status IN ('Pending Verification', 'Pending Approval', 'Approved', 'Received')
                  )
            ";
            
            $params = [];
            
            // Apply role-based filtering
            if (in_array($userRole, ['Finance Director', 'Asset Director', 'Project Manager', 'System Admin'])) {
                // These roles can see all assets for transfer purposes
                // No additional filtering needed
            } else {
                // Other roles get limited access or no access
                // For security, default to no assets unless explicitly allowed
                return [];
            }
            
            $sql .= " ORDER BY p.name ASC, a.name ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get available assets for transfer error: " . $e->getMessage());
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
                SELECT a.*, c.name as category_name
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                WHERE a.project_id = ?
                  AND a.status = 'available'
                  AND a.id NOT IN (
                      SELECT DISTINCT asset_id FROM borrowed_tools WHERE status = 'borrowed'
                      UNION
                      SELECT DISTINCT asset_id FROM withdrawals WHERE status IN ('pending', 'released')
                      UNION
                      SELECT DISTINCT asset_id FROM transfers WHERE status IN ('pending', 'approved')
                  )
                ORDER BY a.name ASC
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
     * API endpoint to get transfer statistics (called by dashboard)
     */
    public function getStats() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        try {
            $stats = $this->transferModel->getTransferStats();
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            error_log("Get transfer stats error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load statistics']);
        }
    }
    
    /**
     * API endpoint to create transfer request (for mobile/AJAX)
     */
    public function createTransferAPI() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Project Manager', 'Asset Director'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $data = [
                'asset_id' => $input['asset_id'] ?? 0,
                'from_project' => $input['from_project'] ?? 0,
                'to_project' => $input['to_project'] ?? 0,
                'reason' => Validator::sanitize($input['reason'] ?? ''),
                'transfer_type' => $input['transfer_type'] ?? 'permanent',
                'transfer_date' => $input['transfer_date'] ?? date('Y-m-d'),
                'expected_return' => $input['expected_return'] ?? null,
                'notes' => Validator::sanitize($input['notes'] ?? ''),
                'initiated_by' => $currentUser['id']
            ];
            
            $result = $this->transferModel->createTransfer($data);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Create transfer API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to create transfer request']);
        }
    }
    
    /**
     * API endpoint to cancel transfer (for AJAX)
     */
    public function cancelTransferAPI() {
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
            $transferId = $input['transfer_id'] ?? 0;
            $reason = Validator::sanitize($input['reason'] ?? 'Canceled via API');
            
            if (!$transferId) {
                echo json_encode(['success' => false, 'message' => 'Transfer ID is required']);
                return;
            }
            
            // Get transfer to check permissions
            $transfer = $this->transferModel->find($transferId);
            if (!$transfer) {
                echo json_encode(['success' => false, 'message' => 'Transfer not found']);
                return;
            }
            
            // Check permissions - System Admin bypasses all checks
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            
            if ($userRole !== 'System Admin' && $transfer['initiated_by'] != $currentUser['id']) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $result = $this->transferModel->cancelTransfer($transferId, $reason);
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Cancel transfer API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to cancel transfer']);
        }
    }
    
    /**
     * API endpoint to get transfer details (for AJAX)
     */
    public function getTransferDetails() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $transferId = $_GET['id'] ?? 0;
        
        if (!$transferId) {
            echo json_encode(['success' => false, 'message' => 'Transfer ID required']);
            return;
        }
        
        try {
            $transfer = $this->transferModel->getTransferWithDetails($transferId);
            
            if (!$transfer) {
                echo json_encode(['success' => false, 'message' => 'Transfer not found']);
                return;
            }
            
            // Check permissions - System Admin bypasses all checks
            $currentUser = $this->auth->getCurrentUser();
            $userRole = $currentUser['role_name'] ?? '';
            
            if ($userRole !== 'System Admin' && 
                !$this->auth->hasRole(['Asset Director', 'Finance Director', 'Project Manager']) && 
                $transfer['initiated_by'] != $currentUser['id']) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $transfer
            ]);
            
        } catch (Exception $e) {
            error_log("Get transfer details API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load transfer details']);
        }
    }
    
    /**
     * API endpoint to get overdue returns
     */
    public function getOverdueReturns() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Asset Director', 'Project Manager'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $overdueReturns = $this->transferModel->getOverdueReturns();
            
            echo json_encode([
                'success' => true,
                'data' => $overdueReturns,
                'count' => count($overdueReturns)
            ]);
            
        } catch (Exception $e) {
            error_log("Get overdue returns API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load overdue returns']);
        }
    }

    /**
     * API endpoint to get returns in transit
     */
    public function getReturnsInTransit() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Asset Director', 'Project Manager'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $returnsInTransit = $this->transferModel->getReturnsInTransit();
            
            echo json_encode([
                'success' => true,
                'data' => $returnsInTransit,
                'count' => count($returnsInTransit)
            ]);
            
        } catch (Exception $e) {
            error_log("Get returns in transit API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load returns in transit']);
        }
    }

    /**
     * API endpoint to get overdue return transits
     */
    public function getOverdueReturnTransits() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        // Check permissions - System Admin bypasses all checks
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        
        if ($userRole !== 'System Admin' && !$this->auth->hasRole(['Asset Director', 'Project Manager'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        try {
            $maxDays = (int)($_GET['max_days'] ?? 3);
            $overdueReturnTransits = $this->transferModel->getOverdueReturnTransits($maxDays);
            
            echo json_encode([
                'success' => true,
                'data' => $overdueReturnTransits,
                'count' => count($overdueReturnTransits)
            ]);
            
        } catch (Exception $e) {
            error_log("Get overdue return transits API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load overdue return transits']);
        }
    }

    /**
     * API endpoint to initiate return (for AJAX)
     */
    public function initiateReturnAPI() {
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
            $transferId = $input['transfer_id'] ?? 0;
            $returnNotes = Validator::sanitize($input['return_notes'] ?? '');
            
            if (!$transferId) {
                echo json_encode(['success' => false, 'message' => 'Transfer ID is required']);
                return;
            }
            
            // Get transfer to check permissions
            $transfer = $this->transferModel->find($transferId);
            if (!$transfer) {
                echo json_encode(['success' => false, 'message' => 'Transfer not found']);
                return;
            }
            
            // Check permissions
            if (!$this->hasTransferPermission('returnAsset', $transfer)) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $currentUser = $this->auth->getCurrentUser();
            $result = $this->transferModel->initiateReturn($transferId, $currentUser['id'], $returnNotes);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Initiate return API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to initiate return']);
        }
    }

    /**
     * API endpoint to receive return (for AJAX)
     */
    public function receiveReturnAPI() {
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
            $transferId = $input['transfer_id'] ?? 0;
            $receiptNotes = Validator::sanitize($input['receipt_notes'] ?? '');
            
            if (!$transferId) {
                echo json_encode(['success' => false, 'message' => 'Transfer ID is required']);
                return;
            }
            
            // Get transfer to check permissions
            $transfer = $this->transferModel->find($transferId);
            if (!$transfer) {
                echo json_encode(['success' => false, 'message' => 'Transfer not found']);
                return;
            }
            
            // Check permissions
            if (!$this->hasTransferPermission('receiveReturn', $transfer)) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $currentUser = $this->auth->getCurrentUser();
            $result = $this->transferModel->receiveReturn($transferId, $currentUser['id'], $receiptNotes);
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            error_log("Receive return API error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to receive return']);
        }
    }
}
?>
