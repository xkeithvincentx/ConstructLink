<?php
/**
 * ConstructLink™ Withdrawal Controller - REFACTORED
 * Handles withdrawal management operations with service-oriented architecture
 * Delegates business logic to specialized services
 */

// Load withdrawal services (order matters: dependencies first)
require_once APP_ROOT . '/services/Withdrawal/WithdrawalValidationService.php';
require_once APP_ROOT . '/services/Withdrawal/WithdrawalQueryService.php';
require_once APP_ROOT . '/services/Withdrawal/WithdrawalStatisticsService.php';
require_once APP_ROOT . '/services/Withdrawal/WithdrawalWorkflowService.php';
require_once APP_ROOT . '/services/Withdrawal/WithdrawalExportService.php';
require_once APP_ROOT . '/services/Withdrawal/WithdrawalService.php';

class WithdrawalController {
    private $auth;
    private $roleConfig;
    private $withdrawalService;
    private $withdrawalWorkflowService;
    private $withdrawalStatisticsService;
    private $withdrawalExportService;

    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->roleConfig = require APP_ROOT . '/config/roles.php';

        // Initialize services
        $this->withdrawalService = new WithdrawalService();
        $this->withdrawalWorkflowService = new WithdrawalWorkflowService();
        $this->withdrawalStatisticsService = new WithdrawalStatisticsService();
        $this->withdrawalExportService = new WithdrawalExportService();

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
        if ($withdrawal && $action === 'cancel') {
            return $withdrawal['withdrawn_by'] == $currentUser['id'];
        }
        if ($withdrawal && $action === 'return') {
            return $withdrawal['receiver_name'] === $currentUser['full_name'];
        }
        return false;
    }

    /**
     * Validate and prepare batch AJAX request (DRY helper)
     *
     * @param string $permissionKey Permission action key (verify, approve, release, return)
     * @return array ['error' => bool, 'data' => array|null, 'message' => string|null]
     */
    private function validateBatchAjaxRequest($permissionKey) {
        // Set JSON response header
        header('Content-Type: application/json');

        // Check HTTP method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return ['error' => true, 'message' => 'Method not allowed'];
        }

        // Parse JSON request
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate CSRF token presence
        if (!isset($data['_csrf_token'])) {
            http_response_code(400);
            return ['error' => true, 'message' => 'CSRF token missing'];
        }

        // Validate CSRF token
        try {
            CSRFProtection::validateToken($data['_csrf_token']);
        } catch (Exception $e) {
            http_response_code(403);
            return ['error' => true, 'message' => 'Invalid CSRF token'];
        }

        // Validate batch ID
        if (!isset($data['batch_id']) || empty($data['batch_id'])) {
            http_response_code(400);
            return ['error' => true, 'message' => 'Batch ID required'];
        }

        // Check permissions
        if (!$this->hasWithdrawalPermission($permissionKey)) {
            http_response_code(403);
            return ['error' => true, 'message' => 'Permission denied'];
        }

        // Return validated data
        return ['error' => false, 'data' => $data];
    }

    /**
     * Display withdrawal listing
     */
    public function index() {
        if (!$this->hasWithdrawalPermission('view')) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            return;
        }

        // Fetch all withdrawals for client-side pagination
        // This is necessary because we group by batch_id in the view
        // TODO: Optimize with batch-aware database pagination later
        $page = 1; // Always fetch from page 1
        $perPage = 1000; // Fetch large dataset for grouping/pagination in view
        $currentUser = $this->auth->getCurrentUser();
        $userRole = $currentUser['role_name'] ?? '';
        $userProjectId = $currentUser['current_project_id'] ?? null;

        // Build filters
        $filters = array_filter([
            'status' => $_GET['status'] ?? null,
            'project_id' => $_GET['project_id'] ?? null,
            'inventory_item_id' => $_GET['inventory_item_id'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? $_GET['receiver'] ?? null // Support both search and receiver filters
        ]);

        // Apply project filtering based on user role
        if ($userProjectId && !in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
            $filters['project_id'] = $userProjectId;
        }

        try {
            $result = $this->withdrawalService->getWithdrawals($filters, $page, $perPage);
            $withdrawals = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];

            $withdrawalStats = $this->withdrawalService->getStatistics($userProjectId && !in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director']) ? $userProjectId : null);

            $projectModel = new ProjectModel();
            $assetModel = new AssetModel();

            if ($userProjectId && !in_array($userRole, ['System Admin', 'Finance Director', 'Asset Director'])) {
                $projects = $projectModel->findAll(['id' => $userProjectId, 'is_active' => 1]);
                $assets = $assetModel->findAll(['project_id' => $userProjectId], "name ASC", 100);
            } else {
                $projects = $projectModel->getActiveProjects();
                $assets = $assetModel->findAll([], "name ASC", 100);
            }

            $pageTitle = 'Withdrawals - ConstructLink™';
            $pageHeader = 'Consumable Withdrawals';
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Withdrawals', 'url' => '?route=withdrawals']
            ];
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
     * Redirects to batch creation (batch system is primary)
     */
    public function create() {
        // Redirect to batch creation (matches borrowed-tools pattern)
        header('Location: ?route=withdrawals/create-batch');
        exit;
    }

    /**
     * Verify withdrawal
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
            $withdrawal = $this->withdrawalService->getWithdrawal($withdrawalId);
            if (!$withdrawal || !$this->hasWithdrawalPermission('verify', $withdrawal)) {
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
                    $result = $this->withdrawalWorkflowService->verifyWithdrawal($withdrawalId, $verifiedBy, $verificationNotes);
                    if ($result['success']) {
                        header('Location: ?route=withdrawals/view&id=' . $withdrawalId . '&message=withdrawal_verified');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }

            $pageTitle = 'Verify Withdrawal - ConstructLink™';
            $pageHeader = 'Verify Withdrawal: ' . htmlspecialchars($withdrawal['item_name']);
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
     * Approve withdrawal
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
            $withdrawal = $this->withdrawalService->getWithdrawal($withdrawalId);
            if (!$withdrawal || !$this->hasWithdrawalPermission('approve', $withdrawal)) {
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
                    $result = $this->withdrawalWorkflowService->approveWithdrawal($withdrawalId, $approvedBy, $approvalNotes);
                    if ($result['success']) {
                        header('Location: ?route=withdrawals/view&id=' . $withdrawalId . '&message=withdrawal_approved');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }

            $pageTitle = 'Approve Withdrawal - ConstructLink™';
            $pageHeader = 'Approve Withdrawal: ' . htmlspecialchars($withdrawal['item_name']);
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
     * Release withdrawal
     */
    public function release() {
        $withdrawalId = $_GET['id'] ?? $_POST['withdrawal_id'] ?? 0;
        if (!$withdrawalId) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            return;
        }

        try {
            $withdrawal = $this->withdrawalService->getWithdrawal($withdrawalId);
            if (!$withdrawal || !$this->hasWithdrawalPermission('release', $withdrawal)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }

            if ($withdrawal['status'] !== 'Approved') {
                $error = 'Withdrawal must be approved before release.';
                include APP_ROOT . '/views/errors/403.php';
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $pageTitle = 'Release Consumable - ConstructLink™';
                $pageHeader = 'Release Consumable: ' . htmlspecialchars($withdrawal['item_name']);
                $breadcrumbs = [
                    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                    ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
                    ['title' => 'Release Consumable', 'url' => '?route=withdrawals/release&id=' . $withdrawalId]
                ];
                $auth = $this->auth;
                include APP_ROOT . '/views/withdrawals/release.php';
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                $user = $this->auth->getCurrentUser();

                $releaseData = [
                    'released_by' => $user['id'],
                    'notes' => implode("\n", array_filter([
                        !empty($_POST['authorization_level']) ? "Authorization Level: " . ucfirst($_POST['authorization_level']) : '',
                        !empty($_POST['asset_condition']) ? "Consumable Condition: " . ucfirst($_POST['asset_condition']) : '',
                        !empty($_POST['receiver_verification']) ? "Receiver Verified: " . Validator::sanitize($_POST['receiver_verification']) : '',
                        !empty($_POST['emergency_reason']) ? "Emergency Reason: " . Validator::sanitize($_POST['emergency_reason']) : '',
                        !empty($_POST['release_notes']) ? "Additional Notes: " . Validator::sanitize($_POST['release_notes']) : ''
                    ]))
                ];

                $result = $this->withdrawalWorkflowService->releaseConsumable($withdrawalId, $releaseData);
                if ($result['success']) {
                    header('Location: ?route=withdrawals/view&id=' . $withdrawalId . '&message=withdrawal_released');
                    exit;
                } else {
                    $errors[] = $result['message'];
                    $pageTitle = 'Release Consumable - ConstructLink™';
                    $pageHeader = 'Release Consumable: ' . htmlspecialchars($withdrawal['item_name']);
                    $breadcrumbs = [
                        ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                        ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
                        ['title' => 'Release Consumable', 'url' => '?route=withdrawals/release&id=' . $withdrawalId]
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
     * Return withdrawal
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
            $withdrawal = $this->withdrawalService->getWithdrawal($withdrawalId);
            if (!$withdrawal || !$this->hasWithdrawalPermission('return', $withdrawal)) {
                http_response_code(403);
                include APP_ROOT . '/views/errors/403.php';
                return;
            }

            if ($withdrawal['status'] !== 'Released') {
                $errors[] = 'This consumable is not currently released and cannot be returned.';
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                $returnNotes = Validator::sanitize($_POST['return_notes'] ?? '');
                $returnedBy = $this->auth->getCurrentUser()['id'];

                if (empty($errors)) {
                    $result = $this->withdrawalWorkflowService->returnItem($withdrawalId, $returnedBy, $returnNotes);
                    if ($result['success']) {
                        header('Location: ?route=withdrawals/view&id=' . $withdrawalId . '&message=withdrawal_returned');
                        exit;
                    } else {
                        $errors[] = $result['message'];
                    }
                }
            }

            $pageTitle = 'Return Consumable - ConstructLink™';
            $pageHeader = 'Return Consumable: ' . htmlspecialchars($withdrawal['item_name']);
            $breadcrumbs = [
                ['title' => 'Dashboard', 'url' => '?route=dashboard'],
                ['title' => 'Withdrawals', 'url' => '?route=withdrawals'],
                ['title' => 'Return Consumable', 'url' => '?route=withdrawals/return&id=' . $withdrawalId]
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
            $withdrawal = $this->withdrawalService->getWithdrawal($withdrawalId);
            if (!$withdrawal) {
                http_response_code(404);
                include APP_ROOT . '/views/errors/404.php';
                return;
            }

            // Allow cancellation for all statuses except final states (Returned, Canceled)
            $nonCancelableStatuses = ['Returned', 'Canceled'];
            if (in_array($withdrawal['status'], $nonCancelableStatuses)) {
                $errors[] = 'This withdrawal cannot be canceled in its current status.';
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                CSRFProtection::validateRequest();
                $reason = Validator::sanitize($_POST['reason'] ?? '');
                $cancellationReason = Validator::sanitize($_POST['cancellation_reason'] ?? '');
                $customReason = Validator::sanitize($_POST['custom_reason'] ?? '');

                $completeReason = $cancellationReason;
                if ($cancellationReason === 'other' && !empty($customReason)) {
                    $completeReason = $customReason;
                }
                if (!empty($reason)) {
                    $completeReason .= ' - ' . $reason;
                }

                if (empty($errors)) {
                    $result = $this->withdrawalWorkflowService->cancelWithdrawal($withdrawalId, $completeReason);
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
            $withdrawal = $this->withdrawalService->getWithdrawal($withdrawalId);
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
            $filters = array_filter([
                'status' => $_GET['status'] ?? null,
                'project_id' => $_GET['project_id'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ]);

            $result = $this->withdrawalService->getWithdrawals($filters, 1, 10000);
            $withdrawals = $result['data'] ?? [];

            $this->withdrawalExportService->exportToExcel($withdrawals, $filters);
        } catch (Exception $e) {
            error_log("Withdrawal export error: " . $e->getMessage());
            header('Location: ?route=withdrawals&error=export_failed');
            exit;
        }
    }

    /**
     * API: Get withdrawal statistics
     */
    public function getStats() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            $stats = $this->withdrawalService->getDashboardStats();
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            error_log("Get withdrawal stats error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load statistics']);
        }
    }

    /**
     * API: Get consumables by project
     */
    public function getConsumablesByProject() {
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
            $consumables = $this->withdrawalService->getAvailableItems($projectId);
            echo json_encode(['success' => true, 'consumables' => $consumables, 'assets' => $consumables]); // Keep 'assets' for backward compatibility
        } catch (Exception $e) {
            error_log("Get consumables by project error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load consumables']);
        }
    }

    /**
     * API: Get consumable details
     */
    public function getConsumableDetails() {
        if (!$this->auth->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $itemId = $_GET['inventory_item_id'] ?? $_GET['consumable_id'] ?? $_GET['asset_id'] ?? 0; // Keep asset_id for backward compatibility
        if (!$itemId) {
            echo json_encode(['success' => false, 'message' => 'Item ID required']);
            return;
        }

        try {
            $withdrawalModel = new WithdrawalModel();
            $item = $withdrawalModel->getConsumableForWithdrawal($itemId);
            if (!$item) {
                echo json_encode(['success' => false, 'message' => 'Consumable not found']);
                return;
            }
            echo json_encode(['success' => true, 'consumable' => $item, 'item' => $item, 'asset' => $item]); // Keep 'asset' for backward compatibility
        } catch (Exception $e) {
            error_log("Get consumable details error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load consumable details']);
        }
    }

    /**
     * AJAX: Verify withdrawal batch or single item
     */
    public function batchVerify() {
        // Validate request using DRY helper
        $validation = $this->validateBatchAjaxRequest('verify');
        if ($validation['error']) {
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            return;
        }

        try {
            $data = $validation['data'];
            $batchId = $data['batch_id'];
            $notes = $data['verification_notes'] ?? '';
            $isSingleItem = isset($data['is_single_item']) && $data['is_single_item'] === true;
            $verifiedBy = $this->auth->getCurrentUser()['id'];

            // Verify batch using workflow service
            require_once APP_ROOT . '/services/WithdrawalBatchWorkflowService.php';
            $batchWorkflowService = new WithdrawalBatchWorkflowService();
            $result = $batchWorkflowService->verifyBatch($batchId, $verifiedBy, $notes, $isSingleItem);

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Batch verify error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to verify batch: ' . $e->getMessage()]);
        }
    }

    /**
     * AJAX: Approve withdrawal batch
     */
    public function batchApprove() {
        // Validate request using DRY helper
        $validation = $this->validateBatchAjaxRequest('approve');
        if ($validation['error']) {
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            return;
        }

        try {
            $data = $validation['data'];
            $batchId = $data['batch_id'];
            $notes = $data['approval_notes'] ?? '';
            $isSingleItem = isset($data['is_single_item']) && $data['is_single_item'] === true;
            $approvedBy = $this->auth->getCurrentUser()['id'];

            // Approve batch using workflow service
            require_once APP_ROOT . '/services/WithdrawalBatchWorkflowService.php';
            $batchWorkflowService = new WithdrawalBatchWorkflowService();
            $result = $batchWorkflowService->approveBatch($batchId, $approvedBy, $notes, $isSingleItem);

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Batch approve error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to approve batch: ' . $e->getMessage()]);
        }
    }

    /**
     * AJAX: Release withdrawal batch
     */
    public function batchRelease() {
        // Validate request using DRY helper
        $validation = $this->validateBatchAjaxRequest('release');
        if ($validation['error']) {
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            return;
        }

        try {
            $data = $validation['data'];
            $batchId = $data['batch_id'];
            $notes = $data['release_notes'] ?? '';
            $isSingleItem = isset($data['is_single_item']) && $data['is_single_item'] === true;
            $releasedBy = $this->auth->getCurrentUser()['id'];

            // Release batch using workflow service
            require_once APP_ROOT . '/services/WithdrawalBatchWorkflowService.php';
            $batchWorkflowService = new WithdrawalBatchWorkflowService();
            $result = $batchWorkflowService->releaseBatch($batchId, $releasedBy, $notes, $isSingleItem);

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Batch release error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to release batch: ' . $e->getMessage()]);
        }
    }

    /**
     * AJAX: Return withdrawal batch
     */
    public function batchReturn() {
        // Validate request using DRY helper
        $validation = $this->validateBatchAjaxRequest('return');
        if ($validation['error']) {
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            return;
        }

        try {
            $data = $validation['data'];
            $batchId = $data['batch_id'];
            $notes = $data['return_notes'] ?? '';
            $isSingleItem = isset($data['is_single_item']) && $data['is_single_item'] === true;
            $returnedBy = $this->auth->getCurrentUser()['id'];

            // Prepare return data with quantities, conditions, and item notes
            $returnData = [
                'return_quantities' => $data['return_quantities'] ?? [],
                'return_conditions' => $data['return_conditions'] ?? [],
                'return_item_notes' => $data['return_item_notes'] ?? []
            ];

            // Return batch using workflow service
            require_once APP_ROOT . '/services/WithdrawalBatchWorkflowService.php';
            $batchWorkflowService = new WithdrawalBatchWorkflowService();
            $result = $batchWorkflowService->returnBatch($batchId, $returnedBy, $returnData, $notes, $isSingleItem);

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Batch return error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to return batch: ' . $e->getMessage()]);
        }
    }
}
