<?php
/**
 * ConstructLink Withdrawal Batch Controller
 * Handles batch withdrawal operations for consumable items with MVA workflows
 *
 * Responsibilities:
 * - Batch creation and storage (consumables only)
 * - Batch viewing
 * - Batch MVA workflows (verify, approve, release with quantity deduction)
 * - Batch cancellations (with quantity restoration if released)
 */

require_once APP_ROOT . '/helpers/WithdrawalBatchStatus.php';

class WithdrawalBatchController {
    private $batchModel;
    private $withdrawalModel;
    private $assetModel;
    private $auth;

    public function __construct() {
        $this->auth = Auth::getInstance();

        // Ensure user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ?route=login');
            exit;
        }

        require_once APP_ROOT . '/models/WithdrawalBatchModel.php';
        require_once APP_ROOT . '/models/WithdrawalModel.php';
        require_once APP_ROOT . '/models/AssetModel.php';

        $this->batchModel = new WithdrawalBatchModel();
        $this->withdrawalModel = new WithdrawalModel();
        $this->assetModel = new AssetModel();
    }

    /**
     * Display batch creation form
     */
    public function createBatch() {
        // Permission check handled by Router (routes.php)
        // Role validation: Warehouseman, Project Manager, Site Inventory Clerk, System Admin

        $pageTitle = 'Withdraw Multiple Consumable Items - ConstructLink';
        include APP_ROOT . '/views/withdrawals/create-batch.php';
    }

    /**
     * Store new batch (AJAX/POST endpoint)
     *
     * Creates batch with MVA workflow for consumable items
     */
    public function storeBatch() {
        @ini_set('display_errors', '0');
        error_reporting(E_ALL);
        header('Content-Type: application/json');

        // Permission check handled by Router (routes.php)
        // Role validation: Warehouseman, Project Manager, Site Inventory Clerk, System Admin

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            CSRFProtection::validateRequest();

            // Validate receiver name
            $receiverName = Validator::sanitize($_POST['receiver_name'] ?? '');
            if (empty($receiverName) || strlen($receiverName) < 3) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Receiver name must be at least 3 characters']);
                return;
            }

            // Validate receiver name format (letters, spaces, commas, periods, hyphens only)
            if (!preg_match('/^[a-zA-Z\s,.\-]+$/', $receiverName)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Receiver name contains invalid characters']);
                return;
            }

            // Validate contact if provided
            $receiverContact = Validator::sanitize($_POST['receiver_contact'] ?? '');
            if (!empty($receiverContact) && strlen($receiverContact) < 7) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Contact information must be at least 7 characters']);
                return;
            }

            // Parse items JSON
            $itemsJson = $_POST['items'] ?? '[]';
            $items = json_decode($itemsJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid items data format']);
                return;
            }

            if (empty($items) || !is_array($items)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No items selected']);
                return;
            }

            // Validate item limit (prevent abuse - max 50 items per batch)
            if (count($items) > 50) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot withdraw more than 50 items in one batch']);
                return;
            }

            // Validate each item structure and consumable status
            foreach ($items as $item) {
                if (!isset($item['inventory_item_id']) || !is_numeric($item['inventory_item_id']) || $item['inventory_item_id'] <= 0) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid inventory item ID in items']);
                    return;
                }

                if (!isset($item['quantity']) || !is_numeric($item['quantity']) || $item['quantity'] < 1) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid quantity for item (must be at least 1)']);
                    return;
                }

                // Validate against available_quantity and consumable status
                // Need to join with categories to get is_consumable flag
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("
                    SELECT ii.*, c.is_consumable
                    FROM inventory_items ii
                    INNER JOIN categories c ON ii.category_id = c.id
                    WHERE ii.id = ?
                ");
                $stmt->execute([$item['inventory_item_id']]);
                $asset = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$asset) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Inventory item not found: ' . $item['inventory_item_id']]);
                    return;
                }

                // CRITICAL: Enforce consumable-only
                if (!$asset['is_consumable'] || $asset['is_consumable'] != 1) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "'{$asset['name']}' is not a consumable item. Only consumables can be withdrawn in batches."
                    ]);
                    return;
                }

                // Check if requested quantity exceeds available quantity
                if ($item['quantity'] > $asset['available_quantity']) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "Cannot withdraw {$item['quantity']} units of '{$asset['name']}' - only {$asset['available_quantity']} available"
                    ]);
                    return;
                }
            }

            // Prepare batch data
            $currentUser = $this->auth->getCurrentUser();
            $batchData = [
                'receiver_name' => $receiverName,
                'receiver_contact' => $receiverContact,
                'purpose' => Validator::sanitize($_POST['purpose'] ?? ''),
                'issued_by' => $currentUser['id']
            ];

            // Create batch
            $result = $this->batchModel->createBatch($batchData, $items);

            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Batch created successfully',
                    'batch_reference' => $result['batch']['batch_reference'],
                    'batch_id' => $result['batch']['id'],
                    'workflow_type' => $result['workflow_type']
                ]);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }

        } catch (Exception $e) {
            error_log("Withdrawal batch creation error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create batch']);
        }
    }

    /**
     * View batch details
     */
    public function viewBatch() {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            include APP_ROOT . '/views/errors/400.php';
            exit;
        }

        $batchId = (int)$_GET['id'];
        $projectFilter = $this->auth->getCurrentUser()['current_project_id'] ?? null;

        $batch = $this->batchModel->getBatchWithItems($batchId, $projectFilter);

        if (!$batch) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            exit;
        }

        $pageTitle = "Withdrawal Batch {$batch['batch_reference']} - ConstructLink";
        include APP_ROOT . '/views/withdrawals/batch-view.php';
    }

    /**
     * List all batches with filters
     */
    public function listBatches() {
        $filters = [
            'status' => $_GET['status'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        $currentUser = $this->auth->getCurrentUser();
        if ($currentUser['current_project_id']) {
            $filters['project_id'] = $currentUser['current_project_id'];
        }

        $page = (int)($_GET['page'] ?? 1);
        $result = $this->batchModel->getBatchesWithFilters($filters, $page, 20);

        $pageTitle = 'Withdrawal Batches - ConstructLink';
        include APP_ROOT . '/views/withdrawals/batch-list.php';
    }

    /**
     * Verify batch (Verifier step)
     */
    public function verifyBatch() {
        $this->handleBatchMVAAction([
            'permission' => 'withdrawal.verify',
            'notesField' => 'verification_notes',
            'modelMethod' => 'verifyBatch',
            'successMessage' => 'Batch verified successfully',
            'errorPrefix' => 'Batch verification',
            'action' => 'verification'
        ]);
    }

    /**
     * Approve batch (Authorizer step)
     */
    public function approveBatch() {
        $this->handleBatchMVAAction([
            'permission' => 'withdrawal.approve',
            'notesField' => 'approval_notes',
            'modelMethod' => 'approveBatch',
            'successMessage' => 'Batch approved successfully',
            'errorPrefix' => 'Batch approval',
            'action' => 'approval'
        ]);
    }

    /**
     * Release batch (Physical handover with quantity deduction)
     */
    public function releaseBatch() {
        $this->handleBatchMVAAction([
            'permission' => 'withdrawal.release',
            'notesField' => 'release_notes',
            'modelMethod' => 'releaseBatch',
            'successMessage' => 'Batch released successfully and quantities deducted from inventory',
            'errorPrefix' => 'Batch release',
            'action' => 'release'
        ]);
    }

    /**
     * Cancel batch (with quantity restoration if released)
     */
    public function cancelBatch() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        header('Content-Type: application/json');

        try {
            CSRFProtection::validateRequest();

            $batchId = $_POST['batch_id'] ?? 0;
            if (!$batchId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Batch ID required']);
                return;
            }

            if (!$this->auth->hasPermission('withdrawal.cancel')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                return;
            }

            $reason = Validator::sanitize($_POST['cancellation_reason'] ?? '');
            $currentUser = $this->auth->getCurrentUser();

            $result = $this->batchModel->cancelBatch($batchId, $currentUser['id'], $reason);

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Batch cancellation error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to cancel batch']);
        }
    }

    /**
     * Print batch slip
     */
    public function printBatch() {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            include APP_ROOT . '/views/errors/400.php';
            exit;
        }

        $batchId = (int)$_GET['id'];
        $batch = $this->batchModel->getBatchWithItems($batchId);

        if (!$batch) {
            http_response_code(404);
            include APP_ROOT . '/views/errors/404.php';
            exit;
        }

        include APP_ROOT . '/views/withdrawals/batch-print.php';
    }

    /**
     * Template method for Batch MVA workflow actions
     * Consolidates duplicate code across batch MVA methods
     */
    private function handleBatchMVAAction($config) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        header('Content-Type: application/json');

        try {
            CSRFProtection::validateRequest();

            $batchId = $_POST['batch_id'] ?? 0;
            if (!$batchId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Batch ID required']);
                return;
            }

            $batch = $this->batchModel->find($batchId);
            if (!$batch) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Batch not found']);
                return;
            }

            // Check permission
            if (!$this->auth->hasPermission($config['permission'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                return;
            }

            $notes = Validator::sanitize($_POST[$config['notesField']] ?? '');
            $currentUser = $this->auth->getCurrentUser();

            $result = $this->batchModel->{$config['modelMethod']}($batchId, $currentUser['id'], $notes);

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Batch {$config['action']} error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $config['errorPrefix'] . ' failed']);
        }
    }
}
?>
