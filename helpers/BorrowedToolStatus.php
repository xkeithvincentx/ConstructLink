<?php
/**
 * ConstructLink Borrowed Tool Status Constants
 *
 * This helper class centralizes all borrowed tool status values to eliminate
 * hardcoded strings throughout the application.
 *
 * ISSUE #12: Hardcoded Status Values (47 occurrences)
 *
 * Usage:
 *   - Instead of: if ($status === 'Pending Verification')
 *   - Use: if ($status === BorrowedToolStatus::PENDING_VERIFICATION)
 *
 * Benefits:
 *   - Type safety and autocomplete support
 *   - Single source of truth for status values
 *   - Easy to refactor/rename statuses
 *   - Prevents typos in status strings
 */

class BorrowedToolStatus {
    /**
     * MVA Workflow Statuses (Critical Tools)
     */

    // Initial status when batch/item is created by Maker
    const PENDING_VERIFICATION = 'Pending Verification';

    // Status after Verifier reviews (awaiting Authorizer approval)
    const PENDING_APPROVAL = 'Pending Approval';

    // Status after Authorizer approves (ready for release)
    const APPROVED = 'Approved';

    /**
     * Active Borrowing Statuses
     */

    // Tool has been released to borrower (currently borrowed)
    const RELEASED = 'Released';

    // Alias for RELEASED (semantic clarity for borrowed state)
    const BORROWED = 'Borrowed';

    /**
     * Return Statuses
     */

    // Some items in batch have been returned, others still borrowed
    const PARTIALLY_RETURNED = 'Partially Returned';

    // All items have been returned
    const RETURNED = 'Returned';

    /**
     * Termination Statuses
     */

    // Request was canceled before completion
    const CANCELED = 'Canceled';

    // Tool is past expected return date (system-generated)
    const OVERDUE = 'Overdue';

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
            self::BORROWED,
            self::PARTIALLY_RETURNED,
            self::RETURNED,
            self::CANCELED,
            self::OVERDUE,
        ];
    }

    /**
     * Get MVA workflow statuses (for critical tools)
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
     * Get active borrowing statuses (tools currently with borrower)
     *
     * @return array List of active borrowing statuses
     */
    public static function getActiveBorrowingStatuses() {
        return [
            self::RELEASED,
            self::BORROWED,
            self::PARTIALLY_RETURNED,
            self::OVERDUE,
        ];
    }

    /**
     * Get completed statuses (no longer active)
     *
     * @return array List of completed statuses
     */
    public static function getCompletedStatuses() {
        return [
            self::RETURNED,
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
     * Check if status represents an active borrowing
     *
     * @param string $status Status to check
     * @return bool True if tool is currently borrowed
     */
    public static function isActiveBorrowing($status) {
        return in_array($status, self::getActiveBorrowingStatuses());
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
            case self::BORROWED:
                return 'primary';

            case self::PARTIALLY_RETURNED:
                return 'secondary';

            case self::RETURNED:
                return 'success';

            case self::CANCELED:
                return 'dark';

            case self::OVERDUE:
                return 'danger';

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
            case self::BORROWED:
                return 'Currently borrowed by user';

            case self::PARTIALLY_RETURNED:
                return 'Some items returned, others still borrowed';

            case self::RETURNED:
                return 'All items have been returned';

            case self::CANCELED:
                return 'Request was canceled';

            case self::OVERDUE:
                return 'Past expected return date';

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

            case self::BORROWED:
                return 'Borrowed';

            case self::PARTIALLY_RETURNED:
                return 'Partially Returned';

            case self::RETURNED:
                return 'Returned';

            case self::CANCELED:
                return 'Canceled';

            case self::OVERDUE:
                return 'Overdue';

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
            case self::BORROWED:
                return 'bi-box-arrow-right';

            case self::PARTIALLY_RETURNED:
                return 'bi-arrow-left-right';

            case self::RETURNED:
                return 'bi-check-circle-fill';

            case self::CANCELED:
                return 'bi-x-circle';

            case self::OVERDUE:
                return 'bi-exclamation-triangle-fill';

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
     * Get active borrowing statuses formatted for dropdown
     *
     * @return array Associative array of value => display name
     */
    public static function getActiveBorrowingStatusesForDropdown() {
        $statuses = self::getActiveBorrowingStatuses();
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
