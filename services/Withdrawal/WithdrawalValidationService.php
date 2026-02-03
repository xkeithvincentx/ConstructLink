<?php
/**
 * ConstructLink™ Withdrawal Validation Service
 *
 * Handles all validation logic for withdrawal operations
 * Follows PSR-4 namespacing and 2025 best practices
 */

class WithdrawalValidationService {

    /**
     * Validate withdrawal request data
     *
     * @param array $data Withdrawal request data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateWithdrawalRequest($data) {
        $errors = [];

        // Required fields
        if (empty($data['inventory_item_id'])) {
            $errors[] = 'Inventory item is required';
        } elseif (!is_numeric($data['inventory_item_id'])) {
            $errors[] = 'Invalid inventory item ID';
        }

        if (empty($data['project_id'])) {
            $errors[] = 'Project is required';
        } elseif (!is_numeric($data['project_id'])) {
            $errors[] = 'Invalid project ID';
        }

        if (empty($data['purpose'])) {
            $errors[] = 'Purpose is required';
        } elseif (strlen($data['purpose']) > 500) {
            $errors[] = 'Purpose cannot exceed 500 characters';
        }

        if (empty($data['receiver_name'])) {
            $errors[] = 'Receiver name is required';
        } elseif (strlen($data['receiver_name']) > 100) {
            $errors[] = 'Receiver name cannot exceed 100 characters';
        }

        if (empty($data['withdrawn_by'])) {
            $errors[] = 'Withdrawn by user is required';
        } elseif (!is_numeric($data['withdrawn_by'])) {
            $errors[] = 'Invalid user ID';
        }

        // Quantity validation
        if (empty($data['quantity']) || !is_numeric($data['quantity'])) {
            $errors[] = 'Valid quantity is required';
        } elseif ($data['quantity'] <= 0) {
            $errors[] = 'Quantity must be greater than 0';
        }

        // Expected return date validation (optional for consumables)
        if (!empty($data['expected_return'])) {
            if (!$this->isValidDate($data['expected_return'])) {
                $errors[] = 'Invalid expected return date format (use YYYY-MM-DD)';
            } elseif (strtotime($data['expected_return']) <= time()) {
                $errors[] = 'Expected return date must be in the future';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate consumable quantity availability
     *
     * @param int $availableQuantity Available quantity in inventory
     * @param int $requestedQuantity Requested quantity to withdraw
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateConsumableQuantity($availableQuantity, $requestedQuantity) {
        if ($requestedQuantity > $availableQuantity) {
            return [
                'valid' => false,
                'message' => "Insufficient quantity. Available: {$availableQuantity}, Requested: {$requestedQuantity}"
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate item-project relationship
     *
     * @param int $itemProjectId Project ID the item belongs to
     * @param int $requestedProjectId Project ID being requested
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateItemProjectRelationship($itemProjectId, $requestedProjectId) {
        if ($itemProjectId != $requestedProjectId) {
            return [
                'valid' => false,
                'message' => 'Selected item does not belong to the requested project'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate date format
     *
     * @param string $date Date string to validate
     * @param string $format Expected date format
     * @return bool True if valid, false otherwise
     */
    private function isValidDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate withdrawal status transition
     *
     * @param string $currentStatus Current withdrawal status
     * @param string $newStatus New desired status
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateStatusTransition($currentStatus, $newStatus) {
        $validTransitions = [
            'Pending Verification' => ['Pending Approval', 'Canceled'],
            'Pending Approval' => ['Approved', 'Canceled'],
            'Approved' => ['Released', 'Canceled'],
            'Released' => ['Returned', 'Canceled']
        ];

        if (!isset($validTransitions[$currentStatus])) {
            return [
                'valid' => false,
                'message' => "Cannot transition from status: {$currentStatus}"
            ];
        }

        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            return [
                'valid' => false,
                'message' => "Cannot transition from '{$currentStatus}' to '{$newStatus}'"
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate release form data
     *
     * @param array $data Release form data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateReleaseData($data) {
        $errors = [];

        if (empty($data['authorization_level']) || !in_array($data['authorization_level'], ['standard', 'emergency'])) {
            $errors[] = 'Valid authorization level is required (standard or emergency)';
        }

        if (empty($data['consumable_condition']) || !in_array($data['consumable_condition'], ['excellent', 'good', 'fair'])) {
            $errors[] = 'Valid consumable condition is required (excellent, good, or fair)';
        }

        if ($data['authorization_level'] === 'emergency' && empty(trim($data['emergency_reason'] ?? ''))) {
            $errors[] = 'Emergency release requires a reason';
        }

        if (empty($data['released_by'])) {
            $errors[] = 'Released by user is required';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>
