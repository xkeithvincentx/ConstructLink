<?php
/**
 * ConstructLink™ Borrowed Tool Service
 * Handles core business logic for borrowed tools operations
 * Created during Phase 2.2 refactoring
 */

class BorrowedToolService {
    private $batchModel;
    private $borrowedToolModel;
    private $assetModel;

    public function __construct() {
        require_once APP_ROOT . '/models/BorrowedToolBatchModel.php';
        require_once APP_ROOT . '/models/BorrowedToolModel.php';
        require_once APP_ROOT . '/models/AssetModel.php';

        $this->batchModel = new BorrowedToolBatchModel();
        $this->borrowedToolModel = new BorrowedToolModel();
        $this->assetModel = new AssetModel();
    }

    /**
     * Determine workflow type based on batch contents
     * Returns 'critical' or 'streamlined' based on business rules
     *
     * @param array $items Batch items to evaluate
     * @return string Workflow type ('critical' or 'streamlined')
     */
    public function determineWorkflow($items) {
        require_once APP_ROOT . '/helpers/BorrowedToolStatus.php';

        foreach ($items as $item) {
            $assetId = $item['asset_id'] ?? null;
            $acquisitionCost = $item['acquisition_cost'] ?? 0;

            if (!$assetId) {
                continue;
            }

            // Check if this is a critical tool (requires full MVA workflow)
            if ($this->borrowedToolModel->isCriticalTool($assetId, $acquisitionCost)) {
                return 'critical';
            }
        }

        return 'streamlined';
    }

    /**
     * Validate batch data before creation
     *
     * @param array $batchData Batch data to validate
     * @param array $items Items in the batch
     * @return array Validation result with 'valid' boolean and 'errors' array
     */
    public function validateBatchData($batchData, $items) {
        $errors = [];

        // Validate batch data
        if (empty($batchData['borrower_name'])) {
            $errors[] = 'Borrower name is required';
        }

        if (empty($batchData['site_location'])) {
            $errors[] = 'Site location is required';
        }

        if (empty($batchData['borrowed_date'])) {
            $errors[] = 'Borrowed date is required';
        }

        if (empty($batchData['expected_return_date'])) {
            $errors[] = 'Expected return date is required';
        }

        // Validate dates
        if (!empty($batchData['borrowed_date']) && !empty($batchData['expected_return_date'])) {
            $borrowedDate = strtotime($batchData['borrowed_date']);
            $returnDate = strtotime($batchData['expected_return_date']);

            if ($returnDate < $borrowedDate) {
                $errors[] = 'Expected return date must be after borrowed date';
            }
        }

        // Validate items
        if (empty($items)) {
            $errors[] = 'At least one item is required';
        }

        foreach ($items as $index => $item) {
            if (empty($item['asset_id'])) {
                $errors[] = "Item #" . ($index + 1) . ": Asset is required";
            }

            if (empty($item['quantity']) || $item['quantity'] < 1) {
                $errors[] = "Item #" . ($index + 1) . ": Quantity must be at least 1";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Create and process batch with workflow determination
     * This will be fully implemented when controller is split (Phase 2.3)
     *
     * @param array $user Current user data
     * @param array $batchData Batch information
     * @param array $items Items to be borrowed
     * @return array Result with success status and batch_id
     * @throws Exception on validation or creation failure
     */
    public function createAndProcessBatch($user, $batchData, $items) {
        // Validate batch data
        $validation = $this->validateBatchData($batchData, $items);
        if (!$validation['valid']) {
            throw new Exception(implode(', ', $validation['errors']));
        }

        // Determine workflow type
        $workflowType = $this->determineWorkflow($items);

        // Add workflow type and user info to batch data
        $batchData['workflow_type'] = $workflowType;
        $batchData['created_by'] = $user['id'];
        $batchData['project_id'] = $user['current_project_id'] ?? null;

        // Determine initial status based on workflow
        $batchData['status'] = ($workflowType === 'critical')
            ? BorrowedToolStatus::PENDING_VERIFICATION
            : BorrowedToolStatus::APPROVED;

        // Create batch through model
        // Full implementation will be added when controller logic is extracted
        $batchId = $this->batchModel->createBatch($batchData, $items);

        return [
            'success' => true,
            'batch_id' => $batchId,
            'workflow_type' => $workflowType
        ];
    }

    /**
     * Check if user can perform action on batch
     *
     * @param string $action Action to perform
     * @param array $batch Batch data
     * @param array $user Current user
     * @return bool True if action is permitted
     */
    public function canPerformAction($action, $batch, $user) {
        // This will integrate with the existing permission system
        // Full implementation when controller is refactored
        return true; // Placeholder
    }

    /**
     * Get batch workflow status and next steps
     *
     * @param array $batch Batch data
     * @return array Workflow information
     */
    public function getWorkflowStatus($batch) {
        $status = $batch['status'] ?? '';
        $workflowType = $batch['workflow_type'] ?? 'streamlined';

        $workflow = [
            'current_status' => $status,
            'workflow_type' => $workflowType,
            'next_action' => null,
            'can_proceed' => false
        ];

        // Determine next action based on current status
        switch ($status) {
            case BorrowedToolStatus::PENDING_VERIFICATION:
                $workflow['next_action'] = 'verify';
                $workflow['can_proceed'] = true;
                break;

            case BorrowedToolStatus::PENDING_APPROVAL:
                $workflow['next_action'] = 'approve';
                $workflow['can_proceed'] = true;
                break;

            case BorrowedToolStatus::APPROVED:
                $workflow['next_action'] = 'release';
                $workflow['can_proceed'] = true;
                break;

            case BorrowedToolStatus::BORROWED:
                $workflow['next_action'] = 'return';
                $workflow['can_proceed'] = true;
                break;

            case BorrowedToolStatus::OVERDUE:
                $workflow['next_action'] = 'return';
                $workflow['can_proceed'] = true;
                $workflow['is_overdue'] = true;
                break;
        }

        return $workflow;
    }
}
