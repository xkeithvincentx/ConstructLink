<?php
/**
 * ConstructLink Withdrawal Batch Status Constants
 *
 * This helper class centralizes all withdrawal batch status values to eliminate
 * hardcoded strings throughout the application.
 *
 * Usage:
 *   - Instead of: if ($status === 'Pending Verification')
 *   - Use: if ($status === WithdrawalBatchStatus::PENDING_VERIFICATION)
 *
 * Benefits:
 *   - Type safety and autocomplete support
 *   - Single source of truth for status values
 *   - Easy to refactor/rename statuses
 *   - Prevents typos in status strings
 */

class WithdrawalBatchStatus {
    /**
     * MVA Workflow Statuses (All Withdrawals)
     */

    // Initial status when batch is created by Maker
    const PENDING_VERIFICATION = 'Pending Verification';

    // Status after Verifier reviews (awaiting Authorizer approval)
    const PENDING_APPROVAL = 'Pending Approval';

    // Status after Authorizer approves (ready for release)
    const APPROVED = 'Approved';

    /**
     * Active Withdrawal Statuses
     */

    // Items have been physically released to receiver
    const RELEASED = 'Released';

    // Items have been returned to inventory
    const RETURNED = 'Returned';

    /**
     * Termination Statuses
     */

    // Batch was canceled before completion
    const CANCELED = 'Canceled';

    /**
     * Get all valid statuses
     *
     * @return array List of all valid status values
     */
    public static function getAllStatuses() {
        return [
            self::PENDING_VERIFICATION,
            self::PENDING_APPROVAL,
            self::APPROVED,
            self::RELEASED,
            self::RETURNED,
            self::CANCELED,
        ];
    }

    /**
     * Get MVA workflow statuses
     *
     * @return array List of MVA workflow statuses
     */
    public static function getMVAStatuses() {
        return [
            self::PENDING_VERIFICATION,
            self::PENDING_APPROVAL,
            self::APPROVED,
        ];
    }

    /**
     * Get active statuses (pending or released)
     *
     * @return array List of active statuses
     */
    public static function getActiveStatuses() {
        return [
            self::PENDING_VERIFICATION,
            self::PENDING_APPROVAL,
            self::APPROVED,
            self::RELEASED,
        ];
    }

    /**
     * Get completed statuses (terminal states)
     *
     * @return array List of completed statuses
     */
    public static function getCompletedStatuses() {
        return [
            self::CANCELED,
        ];
    }

    /**
     * Check if status is valid
     *
     * @param string $status Status to validate
     * @return bool True if status is valid
     */
    public static function isValidStatus($status) {
        return in_array($status, self::getAllStatuses());
    }

    /**
     * Check if status represents an active withdrawal
     *
     * @param string $status Status to check
     * @return bool True if withdrawal is currently active
     */
    public static function isActive($status) {
        return in_array($status, self::getActiveStatuses());
    }

    /**
     * Check if status is in MVA workflow
     *
     * @param string $status Status to check
     * @return bool True if status is part of MVA workflow
     */
    public static function isMVAWorkflow($status) {
        return in_array($status, self::getMVAStatuses());
    }

    /**
     * Check if status is completed (terminal state)
     *
     * @param string $status Status to check
     * @return bool True if status is completed
     */
    public static function isCompleted($status) {
        return in_array($status, self::getCompletedStatuses());
    }

    /**
     * Get status badge color for UI display
     *
     * @param string $status Status value
     * @return string Bootstrap color class (primary, warning, success, etc.)
     */
    public static function getStatusBadgeColor($status) {
        switch ($status) {
            case self::PENDING_VERIFICATION:
            case self::PENDING_APPROVAL:
                return 'warning';

            case self::APPROVED:
                return 'info';

            case self::RELEASED:
                return 'success';

            case self::RETURNED:
                return 'primary';

            case self::CANCELED:
                return 'dark';

            default:
                return 'secondary';
        }
    }

    /**
     * Get human-readable status description
     *
     * @param string $status Status value
     * @return string Status description
     */
    public static function getStatusDescription($status) {
        switch ($status) {
            case self::PENDING_VERIFICATION:
                return 'Awaiting verification by Project Manager';

            case self::PENDING_APPROVAL:
                return 'Awaiting approval by Asset/Finance Director';

            case self::APPROVED:
                return 'Approved and ready for release';

            case self::RELEASED:
                return 'Items have been released to receiver';

            case self::RETURNED:
                return 'Items have been returned to inventory';

            case self::CANCELED:
                return 'Batch was canceled';

            default:
                return 'Unknown status';
        }
    }

    /**
     * Get short display name for status (for labels and dropdowns)
     *
     * @param string $status Status value
     * @return string Short display name
     */
    public static function getDisplayName($status) {
        switch ($status) {
            case self::PENDING_VERIFICATION:
                return 'Pending Verification';

            case self::PENDING_APPROVAL:
                return 'Pending Approval';

            case self::APPROVED:
                return 'Approved';

            case self::RELEASED:
                return 'Released';

            case self::RETURNED:
                return 'Returned';

            case self::CANCELED:
                return 'Canceled';

            default:
                return 'Unknown';
        }
    }

    /**
     * Get status icon for UI display
     *
     * @param string $status Status value
     * @return string Bootstrap icon class
     */
    public static function getStatusIcon($status) {
        switch ($status) {
            case self::PENDING_VERIFICATION:
                return 'bi-hourglass-split';

            case self::PENDING_APPROVAL:
                return 'bi-clock-history';

            case self::APPROVED:
                return 'bi-check-circle';

            case self::RELEASED:
                return 'bi-check-circle-fill';

            case self::RETURNED:
                return 'bi-box-arrow-down';

            case self::CANCELED:
                return 'bi-x-circle';

            default:
                return 'bi-question-circle';
        }
    }

    /**
     * Get all statuses formatted for dropdown
     *
     * @return array Associative array of value => display name
     */
    public static function getStatusesForDropdown() {
        $statuses = self::getAllStatuses();
        $dropdown = [];
        foreach ($statuses as $status) {
            $dropdown[$status] = self::getDisplayName($status);
        }
        return $dropdown;
    }

    /**
     * Get active statuses formatted for dropdown
     *
     * @return array Associative array of value => display name
     */
    public static function getActiveStatusesForDropdown() {
        $statuses = self::getActiveStatuses();
        $dropdown = [];
        foreach ($statuses as $status) {
            $dropdown[$status] = self::getDisplayName($status);
        }
        return $dropdown;
    }

    /**
     * Get MVA workflow statuses formatted for dropdown
     *
     * @return array Associative array of value => display name
     */
    public static function getMVAStatusesForDropdown() {
        $statuses = self::getMVAStatuses();
        $dropdown = [];
        foreach ($statuses as $status) {
            $dropdown[$status] = self::getDisplayName($status);
        }
        return $dropdown;
    }
}
