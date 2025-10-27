<?php
/**
 * ConstructLink™ Borrowed Tool Return Service
 * Handles business logic for returning borrowed tools and incident creation
 * Created during Phase 2.2 refactoring
 */

class BorrowedToolReturnService {
    private $batchModel;
    private $borrowedToolModel;
    private $incidentModel;

    public function __construct() {
        require_once APP_ROOT . '/models/BorrowedToolBatchModel.php';
        require_once APP_ROOT . '/models/BorrowedToolModel.php';
        require_once APP_ROOT . '/models/IncidentModel.php';

        $this->batchModel = new BorrowedToolBatchModel();
        $this->borrowedToolModel = new BorrowedToolModel();
        $this->incidentModel = new IncidentModel();
    }

    /**
     * Process batch return with condition checking and incident creation
     * Full implementation will be extracted from controller (Phase 2.3)
     *
     * @param int $batchId Batch ID to return
     * @param int $userId User performing the return
     * @param array $returnData Return data including conditions and notes
     * @return array Result with success status and any incidents created
     * @throws Exception on processing failure
     */
    public function processBatchReturn($batchId, $userId, $returnData) {
        // Get batch with items
        $batch = $this->batchModel->getBatchWithItems($batchId);

        if (!$batch) {
            throw new Exception('Batch not found');
        }

        // Validate all items are accounted for
        $validation = $this->validateReturnData($batch, $returnData);
        if (!$validation['valid']) {
            throw new Exception(implode(', ', $validation['errors']));
        }

        // Process each item return
        $incidents = [];
        foreach ($returnData['items'] as $itemId => $itemReturn) {
            $result = $this->processSingleItemReturn(
                $itemId,
                $userId,
                $itemReturn
            );

            if (!empty($result['incident_id'])) {
                $incidents[] = $result['incident_id'];
            }
        }

        // Update batch status to returned
        $this->batchModel->update($batchId, [
            'status' => BorrowedToolStatus::RETURNED,
            'returned_at' => date('Y-m-d H:i:s'),
            'returned_by' => $userId,
            'return_notes' => $returnData['notes'] ?? ''
        ]);

        return [
            'success' => true,
            'batch_id' => $batchId,
            'incidents_created' => count($incidents),
            'incident_ids' => $incidents
        ];
    }

    /**
     * Process single item return with condition assessment
     *
     * @param int $itemId Item ID
     * @param int $userId User ID
     * @param array $itemReturn Return data for item
     * @return array Result with incident_id if created
     */
    public function processSingleItemReturn($itemId, $userId, $itemReturn) {
        $condition = $itemReturn['condition'] ?? 'good';
        $notes = $itemReturn['notes'] ?? '';
        $quantity = $itemReturn['quantity'] ?? 1;

        // Update item return information
        $this->borrowedToolModel->update($itemId, [
            'returned_at' => date('Y-m-d H:i:s'),
            'returned_by' => $userId,
            'return_condition' => $condition,
            'return_notes' => $notes,
            'returned_quantity' => $quantity
        ]);

        // Create incident if condition is not good
        $incidentId = null;
        if ($condition !== 'good') {
            $incidentId = $this->createIncidentIfNeeded($itemId, $userId, $condition, $notes);
        }

        return [
            'success' => true,
            'item_id' => $itemId,
            'incident_id' => $incidentId
        ];
    }

    /**
     * Create incident for damaged or lost item
     *
     * @param int $itemId Item ID
     * @param int $userId User ID reporting incident
     * @param string $condition Item condition (damaged, lost, etc.)
     * @param string $notes Additional notes
     * @return int|null Incident ID if created
     */
    public function createIncidentIfNeeded($itemId, $userId, $condition, $notes = '') {
        // Get item details
        $item = $this->borrowedToolModel->find($itemId);
        if (!$item) {
            return null;
        }

        // Determine incident type based on condition
        $incidentType = match($condition) {
            'damaged' => 'equipment_damage',
            'lost' => 'equipment_lost',
            'missing_parts' => 'equipment_incomplete',
            default => 'equipment_issue'
        };

        // Determine severity
        $severity = match($condition) {
            'lost' => 'critical',
            'damaged' => 'major',
            default => 'minor'
        };

        // Create incident
        $incidentData = [
            'asset_id' => $item['asset_id'],
            'incident_type' => $incidentType,
            'severity' => $severity,
            'description' => "Item returned in {$condition} condition. " . $notes,
            'date_reported' => date('Y-m-d H:i:s'),
            'reported_by' => $userId,
            'project_id' => $item['project_id'] ?? null,
            'status' => 'reported',
            'related_transaction_type' => 'borrowed_tool',
            'related_transaction_id' => $itemId
        ];

        try {
            $incidentId = $this->incidentModel->create($incidentData);
            error_log("Created incident #{$incidentId} for borrowed tool item #{$itemId} in {$condition} condition");
            return $incidentId;
        } catch (Exception $e) {
            error_log("Failed to create incident for borrowed tool item #{$itemId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate return data completeness
     *
     * @param array $batch Batch data
     * @param array $returnData Return data to validate
     * @return array Validation result
     */
    private function validateReturnData($batch, $returnData) {
        $errors = [];

        // Check all items are accounted for
        $batchItemIds = array_column($batch['items'] ?? [], 'id');
        $returnItemIds = array_keys($returnData['items'] ?? []);

        foreach ($batchItemIds as $itemId) {
            if (!in_array($itemId, $returnItemIds)) {
                $errors[] = "Item #{$itemId} is missing from return data";
            }
        }

        // Validate each item return
        foreach ($returnData['items'] ?? [] as $itemId => $itemReturn) {
            if (empty($itemReturn['condition'])) {
                $errors[] = "Item #{$itemId}: Condition is required";
            }

            if (isset($itemReturn['quantity']) && $itemReturn['quantity'] < 1) {
                $errors[] = "Item #{$itemId}: Invalid quantity";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Calculate return statistics (overdue days, etc.)
     *
     * @param array $batch Batch data
     * @return array Return statistics
     */
    public function calculateReturnStatistics($batch) {
        $expectedReturnDate = strtotime($batch['expected_return_date'] ?? 'now');
        $actualReturnDate = strtotime($batch['returned_at'] ?? 'now');
        $borrowedDate = strtotime($batch['borrowed_date'] ?? 'now');

        $stats = [
            'borrowed_duration_days' => ceil(($actualReturnDate - $borrowedDate) / 86400),
            'expected_duration_days' => ceil(($expectedReturnDate - $borrowedDate) / 86400),
            'overdue_days' => max(0, ceil(($actualReturnDate - $expectedReturnDate) / 86400)),
            'is_overdue' => $actualReturnDate > $expectedReturnDate,
            'returned_early' => $actualReturnDate < $expectedReturnDate
        ];

        return $stats;
    }
}
