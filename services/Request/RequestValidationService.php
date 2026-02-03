<?php
/**
 * RequestValidationService - Request Validation Logic
 *
 * Handles request data validation including role-based restrictions,
 * business rules, and data integrity checks.
 * Single Responsibility: Request validation business logic.
 *
 * @package ConstructLink\Services\Request
 */

class RequestValidationService {

    /**
     * Validate request creation data
     *
     * @param array $data Request data to validate
     * @param string $userRole User's role
     * @return array Validation errors (empty if valid)
     */
    public function validateCreateRequest($data, $userRole) {
        $errors = [];

        // Validate required fields
        if (empty($data['project_id'])) {
            $errors[] = 'Project is required';
        }

        if (empty($data['request_type'])) {
            $errors[] = 'Request type is required';
        }

        if (empty($data['description'])) {
            $errors[] = 'Description is required';
        }

        // Validate request type permissions
        if (!empty($data['request_type'])) {
            if (!$this->canCreateRequestType($userRole, $data['request_type'])) {
                $errors[] = "You do not have permission to create {$data['request_type']} requests";
            }
        }

        // Validate date_needed if provided
        if (!empty($data['date_needed'])) {
            if (strtotime($data['date_needed']) <= time()) {
                $errors[] = 'Date needed must be in the future';
            }
        }

        // Validate quantity if provided
        if (isset($data['quantity']) && $data['quantity'] !== null) {
            if (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
                $errors[] = 'Quantity must be a positive number';
            }
        }

        // Validate estimated_cost if provided
        if (isset($data['estimated_cost']) && $data['estimated_cost'] !== null) {
            if (!is_numeric($data['estimated_cost']) || $data['estimated_cost'] < 0) {
                $errors[] = 'Estimated cost must be a non-negative number';
            }
        }

        // Restock-specific validation
        if ($data['request_type'] === 'Restock' || ($data['is_restock'] ?? 0) == 1) {
            if (empty($data['inventory_item_id'])) {
                $errors[] = 'Inventory item is required for restock requests';
            } else {
                // Validate restock request using RestockModel
                $restockModel = new RequestRestockModel();
                $restockValidation = $restockModel->validateRestockRequest($data);
                if (!$restockValidation['valid']) {
                    $errors = array_merge($errors, $restockValidation['errors']);
                }
            }
        }

        return $errors;
    }

    /**
     * Validate request update data
     *
     * @param array $data Update data to validate
     * @param array $existingRequest Existing request data
     * @param string $userRole User's role
     * @return array Validation errors (empty if valid)
     */
    public function validateUpdateRequest($data, $existingRequest, $userRole) {
        $errors = [];

        // Can only update draft requests
        if ($existingRequest['status'] !== 'Draft') {
            $errors[] = 'Only draft requests can be updated';
        }

        // Validate date_needed if provided
        if (!empty($data['date_needed'])) {
            if (strtotime($data['date_needed']) <= time()) {
                $errors[] = 'Date needed must be in the future';
            }
        }

        // Validate quantity if provided
        if (isset($data['quantity']) && $data['quantity'] !== null) {
            if (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
                $errors[] = 'Quantity must be a positive number';
            }
        }

        // Validate estimated_cost if provided
        if (isset($data['estimated_cost']) && $data['estimated_cost'] !== null) {
            if (!is_numeric($data['estimated_cost']) || $data['estimated_cost'] < 0) {
                $errors[] = 'Estimated cost must be a non-negative number';
            }
        }

        return $errors;
    }

    /**
     * Check if user can create specific request type
     *
     * @param string $userRole User's role
     * @param string $requestType Request type to create
     * @return bool Whether user can create this request type
     */
    public function canCreateRequestType($userRole, $requestType) {
        $roleRequestTypes = [
            'System Admin' => ['Material', 'Tool', 'Equipment', 'Service', 'Petty Cash', 'Other', 'Restock'],
            'Asset Director' => ['Material', 'Tool', 'Equipment', 'Service', 'Other', 'Restock'],
            'Finance Director' => ['Petty Cash', 'Service', 'Material', 'Tool', 'Equipment', 'Other'],
            'Procurement Officer' => ['Material', 'Tool', 'Equipment', 'Other'],
            'Project Manager' => ['Material', 'Tool', 'Equipment', 'Service', 'Other', 'Restock'],
            'Site Inventory Clerk' => ['Material', 'Tool', 'Restock'],
            'Warehouseman' => ['Restock']
        ];

        if (!isset($roleRequestTypes[$userRole])) {
            return false;
        }

        return in_array($requestType, $roleRequestTypes[$userRole]);
    }

    /**
     * Get allowed request types for user role
     *
     * @param string $userRole User's role
     * @return array Array of allowed request types
     */
    public function getAllowedRequestTypes($userRole) {
        $roleRequestTypes = [
            'System Admin' => ['Material', 'Tool', 'Equipment', 'Service', 'Petty Cash', 'Other', 'Restock'],
            'Asset Director' => ['Material', 'Tool', 'Equipment', 'Service', 'Other', 'Restock'],
            'Finance Director' => ['Petty Cash', 'Service', 'Material', 'Tool', 'Equipment', 'Other'],
            'Procurement Officer' => ['Material', 'Tool', 'Equipment', 'Other'],
            'Project Manager' => ['Material', 'Tool', 'Equipment', 'Service', 'Other', 'Restock'],
            'Site Inventory Clerk' => ['Material', 'Tool', 'Restock'],
            'Warehouseman' => ['Restock']
        ];

        return $roleRequestTypes[$userRole] ?? [];
    }

    /**
     * Validate request status transition
     *
     * @param string $currentStatus Current request status
     * @param string $newStatus New status to transition to
     * @return array Validation result with errors if any
     */
    public function validateStatusTransition($currentStatus, $newStatus) {
        $errors = [];

        // Define allowed status transitions
        $allowedTransitions = [
            'Draft' => ['Submitted'],
            'Submitted' => ['Verified', 'Declined', 'Draft'],
            'Verified' => ['Authorized', 'Declined'],
            'Authorized' => ['Approved', 'Declined'],
            'Reviewed' => ['Approved', 'Declined'],
            'Forwarded' => ['Approved', 'Declined'],
            'Approved' => ['Procured'],
            'Declined' => ['Draft'],
            'Procured' => []
        ];

        if (!isset($allowedTransitions[$currentStatus])) {
            $errors[] = "Invalid current status: {$currentStatus}";
            return ['valid' => false, 'errors' => $errors];
        }

        if (!in_array($newStatus, $allowedTransitions[$currentStatus])) {
            $errors[] = "Cannot transition from {$currentStatus} to {$newStatus}";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate request submission
     *
     * @param array $request Request data
     * @return array Validation errors (empty if valid)
     */
    public function validateSubmission($request) {
        $errors = [];

        // Check if request can be submitted
        if ($request['status'] !== 'Draft') {
            $errors[] = 'Only draft requests can be submitted';
        }

        // Validate required fields are filled
        if (empty($request['project_id'])) {
            $errors[] = 'Project is required';
        }

        if (empty($request['request_type'])) {
            $errors[] = 'Request type is required';
        }

        if (empty($request['description'])) {
            $errors[] = 'Description is required';
        }

        return $errors;
    }

    /**
     * Validate urgency level
     *
     * @param string $urgency Urgency level
     * @return bool Whether urgency is valid
     */
    public function isValidUrgency($urgency) {
        $validUrgencies = ['Critical', 'Urgent', 'Normal'];
        return in_array($urgency, $validUrgencies);
    }

    /**
     * Validate request type
     *
     * @param string $requestType Request type
     * @return bool Whether request type is valid
     */
    public function isValidRequestType($requestType) {
        $validTypes = ['Material', 'Tool', 'Equipment', 'Service', 'Petty Cash', 'Other', 'Restock'];
        return in_array($requestType, $validTypes);
    }

    /**
     * Validate request status
     *
     * @param string $status Request status
     * @return bool Whether status is valid
     */
    public function isValidStatus($status) {
        $validStatuses = ['Draft', 'Submitted', 'Reviewed', 'Verified', 'Authorized', 'Forwarded', 'Approved', 'Declined', 'Procured'];
        return in_array($status, $validStatuses);
    }

    /**
     * Validate project assignment for user
     *
     * @param int $projectId Project ID
     * @param int $userId User ID
     * @param string $userRole User's role
     * @return array Validation result with errors if any
     */
    public function validateProjectAccess($projectId, $userId, $userRole) {
        $errors = [];

        if ($userRole === 'System Admin' || $userRole === 'Asset Director' || $userRole === 'Finance Director') {
            // These roles can access all projects
            return ['valid' => true, 'errors' => []];
        }

        if ($userRole === 'Project Manager') {
            // Verify user is the project manager
            $projectModel = new ProjectModel();
            $project = $projectModel->find($projectId);

            if (!$project) {
                $errors[] = 'Project not found';
            } elseif ($project['project_manager_id'] != $userId) {
                $errors[] = 'You are not assigned to this project';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>
