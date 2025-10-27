<?php
/**
 * ConstructLink™ Borrowed Tool Batch Controller
 * Handles batch borrowed tool operations and MVA workflows
 * Phase 2.3 Refactoring - Extracted from monolithic BorrowedToolController
 * 
 * Responsibilities:
 * - Batch creation and storage
 * - Batch viewing
 * - Batch MVA workflows (verify, approve, release)
 * - Batch returns with condition checking
 * - Batch extensions
 * - Batch cancellations
 */

require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';
require_once APP_ROOT . '/helpers/BorrowedTools/PermissionGuard.php';
require_once APP_ROOT . '/helpers/BorrowedTools/ResponseHelper.php';

class BorrowedToolBatchController {
    private $permissionGuard;
    private $batchModel;
    private $borrowedToolModel;

    public function __construct() {
        $this->permissionGuard = new BorrowedToolsPermissionGuard();

        // Ensure user is authenticated
        if (!$this->permissionGuard->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }

        require_once APP_ROOT . '/models/BorrowedToolBatchModel.php';
        require_once APP_ROOT . '/models/BorrowedToolModel.php';

        $this->batchModel = new BorrowedToolBatchModel();
        $this->borrowedToolModel = new BorrowedToolModel();
    }

    /**
     * Template method for Batch MVA workflow actions
     * Consolidates duplicate code across batch MVA methods
     */
    private function handleBatchMVAAction($config) {
        $this->permissionGuard->requireProjectAssignment();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            BorrowedToolsResponseHelper::sendError('Invalid request method', 400, 'borrowed-tools');
            return;
        }

        $batchId = $_POST['batch_id'] ?? 0;
        if (!$batchId) {
            BorrowedToolsResponseHelper::sendError('Batch ID required', 400, 'borrowed-tools');
            return;
        }

        try {
            CSRFProtection::validateRequest();

            $projectFilter = $this->permissionGuard->getProjectFilter();
            $batch = $this->batchModel->getBatchWithItems($batchId, $projectFilter);

            if (!$batch) {
                BorrowedToolsResponseHelper::sendError('Batch not found', 404, 'borrowed-tools');
                return;
            }

            // Check permission
            if (!$this->permissionGuard->hasPermission($config['permission'], $batch)) {
                BorrowedToolsResponseHelper::sendError('Permission denied', 403, 'borrowed-tools');
                return;
            }

            $notes = Validator::sanitize($_POST[$config['notesField']] ?? '');
            $userId = $this->permissionGuard->getCurrentUser()['id'];

            $result = $this->batchModel->{$config['modelMethod']}($batchId, $userId, $notes);

            if ($result['success']) {
                BorrowedToolsResponseHelper::sendSuccess($config['successMessage'], [], 'borrowed-tools');
            } else {
                BorrowedToolsResponseHelper::sendError($result['message'], 400, 'borrowed-tools');
            }

        } catch (Exception $e) {
            error_log("Batch {$config['action']} error: " . $e->getMessage());
            BorrowedToolsResponseHelper::sendError($config['errorPrefix'] . ' failed', 500, 'borrowed-tools');
        }
    }

    /**
     * Display batch creation form
     */
    public function createBatch() {
        // Check permission
        $this->permissionGuard->requirePermission('create');

        // Check project assignment (MVA oversight roles exempt)
        $currentUser = $this->permissionGuard->getCurrentUser();
        $mvaOversightRoles = config('permissions.borrowed_tools.mva_oversight', []);

        if (!in_array($currentUser['role_name'], $mvaOversightRoles) && !$currentUser['current_project_id']) {
            BorrowedToolsResponseHelper::renderError(
                403,
                'You must be assigned to a project to borrow tools. Please contact your administrator.'
            );
        }

        $errors = [];
        $messages = [];
        $pageTitle = 'Borrow Multiple Tools - ConstructLink™';
        
        include APP_ROOT . '/views/borrowed-tools/create-batch.php';
    }

    /**
     * Store new batch (AJAX/POST endpoint)
     * 
     * Creates batch with automatic workflow determination:
     * - Critical Tools: Full MVA workflow (Pending Verification)
     * - Basic Tools: Streamlined workflow (Auto-released)
     */
    public function storeBatch() {
        @ini_set('display_errors', '0');
        error_reporting(E_ALL);
        header('Content-Type: application/json');

        // Check permission
        if (!$this->permissionGuard->hasPermission('create')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Validate project assignment
        $currentUser = $this->permissionGuard->getCurrentUser();
        $mvaOversightRoles = config('permissions.borrowed_tools.mva_oversight', []);

        if (!in_array($currentUser['role_name'], $mvaOversightRoles) && !$currentUser['current_project_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You must be assigned to a project']);
            return;
        }

        try {
            CSRFProtection::validateRequest();

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

            // Create batch
            $result = $this->batchModel->createBatch($batchData, $items);

            if ($result['success']) {
                // Auto-release for streamlined workflow
                if ($result['workflow_type'] === 'streamlined') {
                    $releaseResult = $this->batchModel->releaseBatch(
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
            http_response_code(500);

            $response = ['success' => false, 'message' => 'Failed to create batch'];
            if (defined('ENV_DEBUG') && ENV_DEBUG === true) {
                $response['error'] = $e->getMessage();
            }

            echo json_encode($response);
        }
    }

    /**
     * View batch details
     */
    public function viewBatch() {
        $this->permissionGuard->requireProjectAssignment();

        $batchId = $_GET['batch_id'] ?? $_GET['id'] ?? 0;

        if (!$batchId) {
            BorrowedToolsResponseHelper::renderError(404);
            return;
        }

        try {
            $projectFilter = $this->permissionGuard->getProjectFilter();
            $batch = $this->batchModel->getBatchWithItems($batchId, $projectFilter);

            if (!$batch) {
                BorrowedToolsResponseHelper::renderError(404);
                return;
            }

            include APP_ROOT . '/views/borrowed-tools/view.php';

        } catch (Exception $e) {
            error_log("View batch error: " . $e->getMessage());
            BorrowedToolsResponseHelper::renderError(500, 'Failed to load batch details');
        }
    }

    /**
     * Verify batch (MVA Verifier step)
     */
    public function verifyBatch() {
        $this->handleBatchMVAAction([
            'action' => 'verify',
            'permission' => 'verify',
            'modelMethod' => 'verifyBatch',
            'notesField' => 'verification_notes',
            'successMessage' => 'Batch verified successfully',
            'errorPrefix' => 'Batch verification'
        ]);
    }

    /**
     * Approve batch (MVA Authorizer step)
     */
    public function approveBatch() {
        $this->handleBatchMVAAction([
            'action' => 'approve',
            'permission' => 'approve',
            'modelMethod' => 'approveBatch',
            'notesField' => 'approval_notes',
            'successMessage' => 'Batch approved successfully',
            'errorPrefix' => 'Batch approval'
        ]);
    }

    /**
     * Release batch to borrower
     */
    public function releaseBatch() {
        $this->handleBatchMVAAction([
            'action' => 'release',
            'permission' => 'borrow',
            'modelMethod' => 'releaseBatch',
            'notesField' => 'release_notes',
            'successMessage' => 'Batch released successfully',
            'errorPrefix' => 'Batch release'
        ]);
    }

    /**
     * Cancel batch
     */
    public function cancelBatch() {
        $this->handleBatchMVAAction([
            'action' => 'cancel',
            'permission' => 'cancel',
            'modelMethod' => 'cancelBatch',
            'notesField' => 'cancellation_reason',
            'successMessage' => 'Batch canceled successfully',
            'errorPrefix' => 'Batch cancellation'
        ]);
    }

    /**
     * Return batch - handles both GET (show form) and POST (process return)
     */
    public function returnBatch() {
        $this->permissionGuard->requireProjectAssignment();

        // GET request - redirect to batch view (return handled via modal)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $batchId = $_GET['id'] ?? $_GET['batch_id'] ?? 0;
            if ($batchId) {
                header('Location: ?route=borrowed-tools/batch/view&batch_id=' . $batchId);
                exit;
            }
            BorrowedToolsResponseHelper::renderError(404);
            return;
        }

        // POST request - process return
        if (!$this->permissionGuard->hasPermission('return')) {
            BorrowedToolsResponseHelper::sendError('Access denied', 403, 'borrowed-tools');
            return;
        }

        $batchId = $_POST['batch_id'] ?? 0;
        if (!$batchId) {
            BorrowedToolsResponseHelper::sendError('Batch ID required', 400, 'borrowed-tools');
            return;
        }

        try {
            CSRFProtection::validateRequest();

            $projectFilter = $this->permissionGuard->getProjectFilter();
            $batch = $this->batchModel->getBatchWithItems($batchId, $projectFilter);

            if (!$batch) {
                BorrowedToolsResponseHelper::sendError('Batch not found', 404, 'borrowed-tools');
                return;
            }

            // Check permission
            if (!$this->permissionGuard->hasPermission('return', $batch)) {
                BorrowedToolsResponseHelper::sendError('Permission denied', 403, 'borrowed-tools');
                return;
            }

            // Parse returned items data - service expects ALL items with itemId as key
            $returnData = [
                'notes' => Validator::sanitize($_POST['return_notes'] ?? ''),
                'items' => []
            ];

            // Initialize all items from batch with default values
            foreach ($batch['items'] as $item) {
                $returnData['items'][$item['id']] = [
                    'condition' => 'Good',
                    'quantity' => 0,
                    'notes' => ''
                ];
            }

            // Parse items - support array format (qty_in[], condition[], item_id[])
            if (isset($_POST['qty_in']) && is_array($_POST['qty_in'])) {
                $qtyIn = $_POST['qty_in'];
                $conditions = $_POST['condition'] ?? [];
                $itemIds = $_POST['item_id'] ?? [];
                $itemNotes = $_POST['item_notes'] ?? [];

                foreach ($qtyIn as $index => $qty) {
                    $borrowedToolId = isset($itemIds[$index]) ? (int)$itemIds[$index] : 0;

                    if ($borrowedToolId && isset($returnData['items'][$borrowedToolId])) {
                        $returnData['items'][$borrowedToolId] = [
                            'quantity' => (int)$qty,
                            'condition' => Validator::sanitize($conditions[$index] ?? 'Good'),
                            'notes' => Validator::sanitize($itemNotes[$index] ?? '')
                        ];
                    }
                }
            }

            // Remove items with quantity = 0 (not being returned)
            $returnData['items'] = array_filter($returnData['items'], function($itemData) {
                return $itemData['quantity'] > 0;
            });

            if (empty($returnData['items'])) {
                BorrowedToolsResponseHelper::sendError('Please specify at least one item to return', 400, 'borrowed-tools');
                return;
            }

            // Use service to process return
            require_once APP_ROOT . '/services/BorrowedToolReturnService.php';
            $returnService = new BorrowedToolReturnService();

            $userId = $this->permissionGuard->getCurrentUser()['id'];
            $result = $returnService->processBatchReturn($batchId, $userId, $returnData);

            if ($result['success']) {
                $message = 'Batch returned successfully';
                if ($result['incidents_created'] > 0) {
                    $message .= '. ' . $result['incidents_created'] . ' incident(s) created for damaged/lost items';
                }
                BorrowedToolsResponseHelper::sendSuccess($message, $result, 'borrowed-tools');
            } else {
                BorrowedToolsResponseHelper::sendError('Failed to return batch', 400, 'borrowed-tools');
            }

        } catch (Exception $e) {
            error_log("Batch return error: " . $e->getMessage());
            BorrowedToolsResponseHelper::sendError($e->getMessage(), 500, 'borrowed-tools');
        }
    }

    /**
     * Extend batch return date
     */
    public function extendBatch() {
        // Implementation will be added from refactored service layer
        // Placeholder to prevent routing errors
        BorrowedToolsResponseHelper::renderError(501, 'Extend batch implementation in progress');
    }
}
