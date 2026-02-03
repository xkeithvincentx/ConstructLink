<?php
/**
 * Withdrawal Batch Details API
 * Returns withdrawal items for populating return modal
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define APP_ROOT
define('APP_ROOT', dirname(dirname(__DIR__)));

// Include configuration
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/Auth.php';
require_once APP_ROOT . '/core/CSRFProtection.php';
require_once APP_ROOT . '/core/helpers.php';
require_once APP_ROOT . '/models/BaseModel.php';
require_once APP_ROOT . '/models/WithdrawalBatchModel.php';
require_once APP_ROOT . '/models/WithdrawalModel.php';

header('Content-Type: application/json');

try {
    $auth = Auth::getInstance();

    // Check authentication
    if (!$auth->isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate CSRF token
    if (!CSRFProtection::validateToken($input['_csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    // Validate required parameters
    if (!isset($input['batch_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing batch_id parameter']);
        exit;
    }

    $batchId = (int)$input['batch_id'];
    $isSingleItem = isset($input['is_single_item']) && $input['is_single_item'] === true;

    // Check permission - allow any withdrawal-related action
    $hasPermission = hasPermission('withdrawals/verify') ||
                     hasPermission('withdrawals/approve') ||
                     hasPermission('withdrawals/release') ||
                     hasPermission('withdrawals/return') ||
                     hasPermission('withdrawals/view');

    if (!$hasPermission) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }

    $db = Database::getInstance()->getConnection();
    $items = [];

    if ($isSingleItem) {
        // Fetch single withdrawal item
        $withdrawalModel = new WithdrawalModel($db);
        $withdrawal = $withdrawalModel->findById($batchId);

        if (!$withdrawal) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Withdrawal not found']);
            exit;
        }

        // Get asset details with current available quantity
        $sql = "SELECT w.*,
                       a.name as item_name,
                       a.ref as item_ref,
                       a.available_quantity as current_available_quantity,
                       w.unit
                FROM withdrawals w
                INNER JOIN inventory_items a ON w.inventory_item_id = a.id
                WHERE w.id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$batchId]);
        $itemData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($itemData) {
            $items[] = [
                'id' => $itemData['id'],
                'item_name' => $itemData['item_name'],
                'item_ref' => $itemData['item_ref'],
                'quantity' => $itemData['quantity'],
                'unit' => $itemData['unit'] ?? 'pcs',
                'current_available_quantity' => $itemData['current_available_quantity'],
                'inventory_item_id' => $itemData['inventory_item_id']
            ];
        }

    } else {
        // Fetch batch items
        $batchModel = new WithdrawalBatchModel($db);
        $batch = $batchModel->getBatchWithItems($batchId);

        if (!$batch) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Batch not found']);
            exit;
        }

        // For return operations, ensure batch is in Released status
        // For approve/verify operations, skip this check
        $checkingForReturn = isset($input['operation']) && $input['operation'] === 'return';
        if ($checkingForReturn && $batch['status'] !== 'Released') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Only released withdrawals can be returned']);
            exit;
        }

        // Get current available quantities for all items in batch
        $itemIds = array_column($batch['items'], 'id');
        if (!empty($itemIds)) {
            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            $availabilitySql = "
                SELECT w.id, i.available_quantity
                FROM withdrawals w
                INNER JOIN inventory_items i ON w.inventory_item_id = i.id
                WHERE w.id IN ($placeholders)
            ";
            $stmt = $db->prepare($availabilitySql);
            $stmt->execute($itemIds);
            $availabilityData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $availabilityMap = [];
            foreach ($availabilityData as $row) {
                $availabilityMap[$row['id']] = $row['available_quantity'];
            }
        } else {
            $availabilityMap = [];
        }

        // Transform batch items to required format
        foreach ($batch['items'] as $item) {
            $items[] = [
                'id' => $item['id'],
                'item_name' => $item['asset_name'],
                'item_ref' => $item['asset_ref'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'] ?? 'pcs',
                'current_available_quantity' => $availabilityMap[$item['id']] ?? 0,
                'inventory_item_id' => $item['inventory_item_id']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);

} catch (Exception $e) {
    error_log("Withdrawal batch details API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
