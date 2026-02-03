<?php
/**
 * RequestPermissionService - Permission and Authorization Logic
 *
 * Handles request-related permission checks and authorization logic.
 * Single Responsibility: Permission verification for request operations.
 *
 * @package ConstructLink\Services\Request
 */

class RequestPermissionService {

    /**
     * Check if user can view request
     *
     * @param array $request Request data
     * @param int $userId User ID
     * @param string $userRole User's role
     * @return bool Whether user can view this request
     */
    public function canViewRequest($request, $userId, $userRole) {
        // System Admin, Asset Director, Finance Director can view all requests
        if (in_array($userRole, ['System Admin', 'Asset Director', 'Finance Director'])) {
            return true;
        }

        // Procurement Officer can view requests that are approved or procured
        if ($userRole === 'Procurement Officer') {
            return in_array($request['status'], ['Approved', 'Procured']);
        }

        // Project Manager can view requests from their projects
        if ($userRole === 'Project Manager') {
            $projectModel = new ProjectModel();
            $project = $projectModel->find($request['project_id']);
            return $project && $project['project_manager_id'] == $userId;
        }

        // Users can view their own requests
        if ($request['requested_by'] == $userId) {
            return true;
        }

        // Warehouseman can view requests with procurement orders
        if ($userRole === 'Warehouseman') {
            return !empty($request['procurement_id']);
        }

        return false;
    }

    /**
     * Check if user can edit request
     *
     * @param array $request Request data
     * @param int $userId User ID
     * @param string $userRole User's role
     * @return bool Whether user can edit this request
     */
    public function canEditRequest($request, $userId, $userRole) {
        // Can only edit draft requests
        if ($request['status'] !== 'Draft') {
            return false;
        }

        // System Admin can edit any draft request
        if ($userRole === 'System Admin') {
            return true;
        }

        // Users can only edit their own draft requests
        return $request['requested_by'] == $userId;
    }

    /**
     * Check if user can delete request
     *
     * @param array $request Request data
     * @param int $userId User ID
     * @param string $userRole User's role
     * @return bool Whether user can delete this request
     */
    public function canDeleteRequest($request, $userId, $userRole) {
        // Can only delete draft requests
        if ($request['status'] !== 'Draft') {
            return false;
        }

        // System Admin can delete any draft request
        if ($userRole === 'System Admin') {
            return true;
        }

        // Users can only delete their own draft requests
        return $request['requested_by'] == $userId;
    }

    /**
     * Check if user can submit request
     *
     * @param array $request Request data
     * @param int $userId User ID
     * @param string $userRole User's role
     * @return bool Whether user can submit this request
     */
    public function canSubmitRequest($request, $userId, $userRole) {
        // Can only submit draft requests
        if ($request['status'] !== 'Draft') {
            return false;
        }

        // Only the creator can submit their request
        return $request['requested_by'] == $userId;
    }

    /**
     * Check if user can verify request (MVA workflow)
     *
     * @param array $request Request data
     * @param int $userId User ID
     * @param string $userRole User's role
     * @return bool Whether user can verify this request
     */
    public function canVerifyRequest($request, $userId, $userRole) {
        // Must be in Submitted status
        if ($request['status'] !== 'Submitted') {
            return false;
        }

        // Project Manager can verify if assigned to the project
        if ($userRole === 'Project Manager') {
            return isset($request['project_manager_id']) && $request['project_manager_id'] == $userId;
        }

        return false;
    }

    /**
     * Check if user can authorize request (MVA workflow)
     *
     * @param array $request Request data
     * @param int $userId User ID
     * @param string $userRole User's role
     * @return bool Whether user can authorize this request
     */
    public function canAuthorizeRequest($request, $userId, $userRole) {
        // Must be in Verified status
        if ($request['status'] !== 'Verified') {
            return false;
        }

        // Asset Director can authorize
        if ($userRole === 'Asset Director') {
            return true;
        }

        return false;
    }

    /**
     * Check if user can approve request (MVA workflow)
     *
     * @param array $request Request data
     * @param int $userId User ID
     * @param string $userRole User's role
     * @return bool Whether user can approve this request
     */
    public function canApproveRequest($request, $userId, $userRole) {
        // Must be in Authorized status
        if ($request['status'] !== 'Authorized') {
            return false;
        }

        // System Admin can approve
        if ($userRole === 'System Admin') {
            return true;
        }

        // Finance Director can approve high-value or specific types
        if ($userRole === 'Finance Director') {
            return ($request['estimated_cost'] ?? 0) > 50000
                || in_array($request['request_type'], ['Petty Cash', 'Service']);
        }

        return false;
    }

    /**
     * Check if user can decline request
     *
     * @param array $request Request data
     * @param int $userId User ID
     * @param string $userRole User's role
     * @return bool Whether user can decline this request
     */
    public function canDeclineRequest($request, $userId, $userRole) {
        // Cannot decline draft or already declined requests
        if (in_array($request['status'], ['Draft', 'Declined', 'Procured'])) {
            return false;
        }

        // System Admin can decline any request
        if ($userRole === 'System Admin') {
            return true;
        }

        // Users with approval permissions can decline at their stage
        if ($this->canVerifyRequest($request, $userId, $userRole)) {
            return true;
        }

        if ($this->canAuthorizeRequest($request, $userId, $userRole)) {
            return true;
        }

        if ($this->canApproveRequest($request, $userId, $userRole)) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can resubmit declined request
     *
     * @param array $request Request data
     * @param int $userId User ID
     * @param string $userRole User's role
     * @return bool Whether user can resubmit this request
     */
    public function canResubmitRequest($request, $userId, $userRole) {
        // Must be declined
        if ($request['status'] !== 'Declined') {
            return false;
        }

        // Only the creator can resubmit
        return $request['requested_by'] == $userId;
    }

    /**
     * Check if user can link request to procurement order
     *
     * @param array $request Request data
     * @param int $userId User ID
     * @param string $userRole User's role
     * @return bool Whether user can link this request
     */
    public function canLinkToProcurement($request, $userId, $userRole) {
        // Must be approved
        if ($request['status'] !== 'Approved') {
            return false;
        }

        // Must not already be linked
        if (!empty($request['procurement_id'])) {
            return false;
        }

        // Procurement Officer, System Admin can link
        return in_array($userRole, ['System Admin', 'Procurement Officer', 'Asset Director']);
    }

    /**
     * Check if user can export requests
     *
     * @param string $userRole User's role
     * @return bool Whether user can export requests
     */
    public function canExportRequests($userRole) {
        $allowedRoles = ['System Admin', 'Asset Director', 'Finance Director', 'Procurement Officer'];
        return in_array($userRole, $allowedRoles);
    }

    /**
     * Check if user can view request statistics
     *
     * @param string $userRole User's role
     * @return bool Whether user can view statistics
     */
    public function canViewStatistics($userRole) {
        $allowedRoles = ['System Admin', 'Asset Director', 'Finance Director', 'Procurement Officer', 'Project Manager'];
        return in_array($userRole, $allowedRoles);
    }

    /**
     * Check if user can generate PO from request
     *
     * @param array $request Request data
     * @param string $userRole User's role
     * @return bool Whether user can generate PO
     */
    public function canGeneratePO($request, $userRole) {
        // Must be approved
        if ($request['status'] !== 'Approved') {
            return false;
        }

        // Must not already have a PO
        if (!empty($request['procurement_id'])) {
            return false;
        }

        // Procurement Officer, System Admin can generate PO
        return in_array($userRole, ['System Admin', 'Procurement Officer', 'Asset Director']);
    }

    /**
     * Get available actions for user on request
     *
     * @param array $request Request data
     * @param int $userId User ID
     * @param string $userRole User's role
     * @return array Available action names
     */
    public function getAvailableActions($request, $userId, $userRole) {
        $actions = [];

        if ($this->canViewRequest($request, $userId, $userRole)) {
            $actions[] = 'view';
        }

        if ($this->canEditRequest($request, $userId, $userRole)) {
            $actions[] = 'edit';
        }

        if ($this->canDeleteRequest($request, $userId, $userRole)) {
            $actions[] = 'delete';
        }

        if ($this->canSubmitRequest($request, $userId, $userRole)) {
            $actions[] = 'submit';
        }

        if ($this->canVerifyRequest($request, $userId, $userRole)) {
            $actions[] = 'verify';
        }

        if ($this->canAuthorizeRequest($request, $userId, $userRole)) {
            $actions[] = 'authorize';
        }

        if ($this->canApproveRequest($request, $userId, $userRole)) {
            $actions[] = 'approve';
        }

        if ($this->canDeclineRequest($request, $userId, $userRole)) {
            $actions[] = 'decline';
        }

        if ($this->canResubmitRequest($request, $userId, $userRole)) {
            $actions[] = 'resubmit';
        }

        if ($this->canLinkToProcurement($request, $userId, $userRole)) {
            $actions[] = 'link_procurement';
        }

        if ($this->canGeneratePO($request, $userRole)) {
            $actions[] = 'generate_po';
        }

        return $actions;
    }
}
?>
