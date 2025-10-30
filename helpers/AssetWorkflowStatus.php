<?php
/**
 * ConstructLink™ Asset Workflow Status Constants
 *
 * This helper class centralizes all MVA (Multi-level Verification and Authorization)
 * workflow status values to eliminate hardcoded strings throughout the application.
 *
 * MVA WORKFLOW STATES:
 * 1. DRAFT - Asset created but not submitted for verification
 * 2. PENDING_VERIFICATION - Awaiting verification by Finance Director
 * 3. PENDING_AUTHORIZATION - Verified, awaiting authorization by Asset Director
 * 4. APPROVED - Verified and authorized, ready for use
 * 5. REJECTED - Rejected during verification or authorization
 *
 * Usage:
 *   - Instead of: if ($workflow_status === 'pending_verification')
 *   - Use: if ($workflow_status === AssetWorkflowStatus::PENDING_VERIFICATION)
 *
 * Benefits:
 *   - Type safety and autocomplete support
 *   - Single source of truth for workflow status values
 *   - Easy to refactor/rename statuses
 *   - Prevents typos in status strings
 *   - Clear workflow state documentation
 *
 * @package ConstructLink
 * @version 1.0.0
 */

class AssetWorkflowStatus {
    /**
     * Workflow Status Constants
     */

    // Asset is in draft mode, not yet submitted
    const DRAFT = 'draft';

    // Asset is pending verification by Finance Director
    const PENDING_VERIFICATION = 'pending_verification';

    // Asset is verified, pending authorization by Asset Director
    const PENDING_AUTHORIZATION = 'pending_authorization';

    // Asset is verified and authorized, ready for operations
    const APPROVED = 'approved';

    // Asset was rejected during verification or authorization
    const REJECTED = 'rejected';

    /**
     * Get all valid workflow statuses
     *
     * @return array List of all valid workflow status values
     */
    public static function getAllStatuses() {
        return [
            self::DRAFT,
            self::PENDING_VERIFICATION,
            self::PENDING_AUTHORIZATION,
            self::APPROVED,
            self::REJECTED,
        ];
    }

    /**
     * Get pending workflow statuses (requiring action)
     *
     * @return array List of pending workflow status values
     */
    public static function getPendingStatuses() {
        return [
            self::PENDING_VERIFICATION,
            self::PENDING_AUTHORIZATION,
        ];
    }

    /**
     * Get completed workflow statuses (no action needed)
     *
     * @return array List of completed workflow status values
     */
    public static function getCompletedStatuses() {
        return [
            self::APPROVED,
            self::REJECTED,
        ];
    }

    /**
     * Check if workflow status is valid
     *
     * @param string $status Status to validate
     * @return bool True if status is valid
     */
    public static function isValidStatus($status) {
        return in_array($status, self::getAllStatuses());
    }

    /**
     * Check if workflow status is pending (requires action)
     *
     * @param string $status Status to check
     * @return bool True if status is pending
     */
    public static function isPending($status) {
        return in_array($status, self::getPendingStatuses());
    }

    /**
     * Check if workflow status is completed
     *
     * @param string $status Status to check
     * @return bool True if status is completed
     */
    public static function isCompleted($status) {
        return in_array($status, self::getCompletedStatuses());
    }

    /**
     * Check if status allows editing
     *
     * @param string $status Status to check
     * @return bool True if asset can be edited in this workflow status
     */
    public static function allowsEditing($status) {
        return in_array($status, [self::DRAFT, self::REJECTED]);
    }

    /**
     * Get workflow status badge color for UI display
     *
     * @param string $status Workflow status value
     * @return string Bootstrap color class (primary, warning, success, etc.)
     */
    public static function getStatusBadgeColor($status) {
        switch ($status) {
            case self::DRAFT:
                return 'secondary';

            case self::PENDING_VERIFICATION:
            case self::PENDING_AUTHORIZATION:
                return 'warning';

            case self::APPROVED:
                return 'success';

            case self::REJECTED:
                return 'danger';

            default:
                return 'secondary';
        }
    }

    /**
     * Get human-readable workflow status description
     *
     * @param string $status Workflow status value
     * @return string Status description
     */
    public static function getStatusDescription($status) {
        switch ($status) {
            case self::DRAFT:
                return 'Draft';

            case self::PENDING_VERIFICATION:
                return 'Pending Verification';

            case self::PENDING_AUTHORIZATION:
                return 'Pending Authorization';

            case self::APPROVED:
                return 'Approved';

            case self::REJECTED:
                return 'Rejected';

            default:
                return 'Unknown';
        }
    }

    /**
     * Get workflow status display name (alias for getStatusDescription)
     *
     * @param string $status Workflow status value
     * @return string Status display name
     */
    public static function getDisplayName($status) {
        return self::getStatusDescription($status);
    }

    /**
     * Get workflow status icon for UI display
     *
     * @param string $status Workflow status value
     * @return string Bootstrap icon class
     */
    public static function getStatusIcon($status) {
        switch ($status) {
            case self::DRAFT:
                return 'bi-file-earmark-text';

            case self::PENDING_VERIFICATION:
                return 'bi-hourglass-split';

            case self::PENDING_AUTHORIZATION:
                return 'bi-shield-check';

            case self::APPROVED:
                return 'bi-check-circle-fill';

            case self::REJECTED:
                return 'bi-x-circle-fill';

            default:
                return 'bi-question-circle';
        }
    }

    /**
     * Get next workflow status
     *
     * @param string $currentStatus Current workflow status
     * @return string|null Next status in workflow, or null if terminal
     */
    public static function getNextStatus($currentStatus) {
        switch ($currentStatus) {
            case self::DRAFT:
                return self::PENDING_VERIFICATION;

            case self::PENDING_VERIFICATION:
                return self::PENDING_AUTHORIZATION;

            case self::PENDING_AUTHORIZATION:
                return self::APPROVED;

            default:
                return null; // Terminal states (approved/rejected)
        }
    }

    /**
     * Get action label for workflow status
     *
     * @param string $status Workflow status value
     * @return string Action label (e.g., "Submit for Verification")
     */
    public static function getActionLabel($status) {
        switch ($status) {
            case self::DRAFT:
                return 'Submit for Verification';

            case self::PENDING_VERIFICATION:
                return 'Verify Asset';

            case self::PENDING_AUTHORIZATION:
                return 'Authorize Asset';

            case self::REJECTED:
                return 'Resubmit for Verification';

            default:
                return 'No Action Available';
        }
    }

    /**
     * Get workflow transition rules
     *
     * @return array Workflow transition rules
     */
    public static function getTransitionRules() {
        return [
            self::DRAFT => [
                'next' => [self::PENDING_VERIFICATION],
                'required_permission' => 'asset.submit'
            ],
            self::PENDING_VERIFICATION => [
                'next' => [self::PENDING_AUTHORIZATION, self::REJECTED],
                'required_permission' => 'asset.verify'
            ],
            self::PENDING_AUTHORIZATION => [
                'next' => [self::APPROVED, self::REJECTED],
                'required_permission' => 'asset.authorize'
            ],
            self::REJECTED => [
                'next' => [self::PENDING_VERIFICATION],
                'required_permission' => 'asset.submit'
            ],
            self::APPROVED => [
                'next' => [],
                'required_permission' => null
            ]
        ];
    }

    /**
     * Check if workflow transition is valid
     *
     * @param string $fromStatus Current status
     * @param string $toStatus Target status
     * @return bool True if transition is valid
     */
    public static function isValidTransition($fromStatus, $toStatus) {
        $rules = self::getTransitionRules();
        if (!isset($rules[$fromStatus])) {
            return false;
        }
        return in_array($toStatus, $rules[$fromStatus]['next']);
    }

    /**
     * Get required permission for workflow status action
     *
     * @param string $status Workflow status
     * @return string|null Required permission, or null if no permission needed
     */
    public static function getRequiredPermission($status) {
        $rules = self::getTransitionRules();
        return $rules[$status]['required_permission'] ?? null;
    }

    /**
     * Get all workflow statuses for dropdown with display names
     *
     * @return array Associative array of status value => display name
     */
    public static function getStatusesForDropdown() {
        $statuses = self::getAllStatuses();
        $dropdown = [];
        foreach ($statuses as $status) {
            $dropdown[$status] = self::getDisplayName($status);
        }
        return $dropdown;
    }
}
