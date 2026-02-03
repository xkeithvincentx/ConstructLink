<?php
/**
 * ConstructLink Request Status Constants
 *
 * This helper class centralizes all request status values to eliminate
 * hardcoded strings throughout the application.
 *
 * Usage:
 *   - Instead of: if ($status === 'Submitted')
 *   - Use: if ($status === RequestStatus::SUBMITTED)
 *
 * Benefits:
 *   - Type safety and autocomplete support
 *   - Single source of truth for status values
 *   - Easy to refactor/rename statuses
 *   - Prevents typos in status strings
 *
 * @package ConstructLink
 * @version 1.0.0
 */

class RequestStatus {
    /**
     * MVA Workflow Statuses
     */

    // Initial status when request is created (not yet submitted)
    const DRAFT = 'Draft';

    // Status after maker submits the request
    const SUBMITTED = 'Submitted';

    // Status after verifier reviews (Site Inventory Clerk or Project Manager)
    const VERIFIED = 'Verified';

    // Status after authorizer reviews (Project Manager)
    const AUTHORIZED = 'Authorized';

    // Status after final approval (Finance Director)
    const APPROVED = 'Approved';

    /**
     * Intermediate Statuses
     */

    // Status after request is reviewed but not yet approved
    const REVIEWED = 'Reviewed';

    // Status after request is forwarded to next approver
    const FORWARDED = 'Forwarded';

    /**
     * Terminal/Completion Statuses
     */

    // Status when request has been linked to a procurement order
    const PROCURED = 'Procured';

    // Status when procurement has been delivered and fulfilled
    const FULFILLED = 'Fulfilled';

    // Status when request is declined/rejected
    const DECLINED = 'Declined';

    /**
     * Get all valid statuses
     *
     * @return array List of all valid status values
     */
    public static function getAllStatuses() {
        return [
            self::DRAFT,
            self::SUBMITTED,
            self::VERIFIED,
            self::AUTHORIZED,
            self::REVIEWED,
            self::FORWARDED,
            self::APPROVED,
            self::PROCURED,
            self::FULFILLED,
            self::DECLINED,
        ];
    }

    /**
     * Get MVA workflow statuses (pending approval stages)
     *
     * @return array List of MVA workflow statuses
     */
    public static function getMVAStatuses() {
        return [
            self::SUBMITTED,
            self::VERIFIED,
            self::AUTHORIZED,
            self::REVIEWED,
            self::FORWARDED,
        ];
    }

    /**
     * Get pending statuses (not yet approved or declined)
     *
     * @return array List of pending statuses
     */
    public static function getPendingStatuses() {
        return [
            self::SUBMITTED,
            self::VERIFIED,
            self::AUTHORIZED,
            self::REVIEWED,
            self::FORWARDED,
        ];
    }

    /**
     * Get approved statuses (approved and beyond)
     *
     * @return array List of approved statuses
     */
    public static function getApprovedStatuses() {
        return [
            self::APPROVED,
            self::PROCURED,
            self::FULFILLED,
        ];
    }

    /**
     * Get terminal statuses (workflow complete)
     *
     * @return array List of terminal statuses
     */
    public static function getTerminalStatuses() {
        return [
            self::FULFILLED,
            self::DECLINED,
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
     * Check if status is pending approval
     *
     * @param string $status Status to check
     * @return bool True if request is pending
     */
    public static function isPending($status) {
        return in_array($status, self::getPendingStatuses());
    }

    /**
     * Check if status is approved (or beyond)
     *
     * @param string $status Status to check
     * @return bool True if request is approved
     */
    public static function isApproved($status) {
        return in_array($status, self::getApprovedStatuses());
    }

    /**
     * Check if status is terminal (workflow complete)
     *
     * @param string $status Status to check
     * @return bool True if status is terminal
     */
    public static function isTerminal($status) {
        return in_array($status, self::getTerminalStatuses());
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
     * Check if request can be submitted (only Draft can be submitted)
     *
     * @param string $status Current status
     * @return bool True if request can be submitted
     */
    public static function canBeSubmitted($status) {
        return $status === self::DRAFT;
    }

    /**
     * Check if request can be edited (only Draft can be edited)
     *
     * @param string $status Current status
     * @return bool True if request can be edited
     */
    public static function canBeEdited($status) {
        return $status === self::DRAFT;
    }

    /**
     * Check if request can be declined
     *
     * @param string $status Current status
     * @return bool True if request can be declined
     */
    public static function canBeDeclined($status) {
        return in_array($status, [
            self::SUBMITTED,
            self::VERIFIED,
            self::AUTHORIZED,
            self::REVIEWED,
            self::FORWARDED,
        ]);
    }

    /**
     * Get status badge color for UI display
     *
     * @param string $status Status value
     * @return string Bootstrap color class (primary, warning, success, etc.)
     */
    public static function getStatusBadgeColor($status) {
        switch ($status) {
            case self::DRAFT:
                return 'secondary';

            case self::SUBMITTED:
            case self::REVIEWED:
            case self::FORWARDED:
                return 'info';

            case self::VERIFIED:
            case self::AUTHORIZED:
                return 'primary';

            case self::APPROVED:
                return 'success';

            case self::PROCURED:
                return 'success';

            case self::FULFILLED:
                return 'dark';

            case self::DECLINED:
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
            case self::DRAFT:
                return 'Request is being drafted';

            case self::SUBMITTED:
                return 'Awaiting verification';

            case self::VERIFIED:
                return 'Verified - awaiting authorization';

            case self::AUTHORIZED:
                return 'Authorized - awaiting approval';

            case self::REVIEWED:
                return 'Reviewed - awaiting approval';

            case self::FORWARDED:
                return 'Forwarded to next approver';

            case self::APPROVED:
                return 'Approved - ready for procurement';

            case self::PROCURED:
                return 'Procurement order created';

            case self::FULFILLED:
                return 'Request fulfilled and completed';

            case self::DECLINED:
                return 'Request declined';

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
        // Status constants already have proper capitalization
        return $status;
    }

    /**
     * Get status icon for UI display
     *
     * @param string $status Status value
     * @return string Bootstrap icon class
     */
    public static function getStatusIcon($status) {
        switch ($status) {
            case self::DRAFT:
                return 'bi-pencil-square';

            case self::SUBMITTED:
                return 'bi-send';

            case self::VERIFIED:
                return 'bi-check-circle';

            case self::AUTHORIZED:
                return 'bi-shield-check';

            case self::REVIEWED:
                return 'bi-eye-fill';

            case self::FORWARDED:
                return 'bi-arrow-right-circle';

            case self::APPROVED:
                return 'bi-check-circle-fill';

            case self::PROCURED:
                return 'bi-cart-check-fill';

            case self::FULFILLED:
                return 'bi-check-all';

            case self::DECLINED:
                return 'bi-x-circle-fill';

            default:
                return 'bi-question-circle';
        }
    }

    /**
     * Get Bootstrap badge class for status display
     *
     * @param string $status Status value
     * @return string Bootstrap badge class
     */
    public static function getBadgeClass($status) {
        return 'badge-' . self::getStatusBadgeColor($status);
    }

    /**
     * Get status text color class
     *
     * @param string $status Status value
     * @return string Bootstrap text color class
     */
    public static function getTextColor($status) {
        return 'text-' . self::getStatusBadgeColor($status);
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
     * Get pending statuses formatted for dropdown
     *
     * @return array Associative array of value => display name
     */
    public static function getPendingStatusesForDropdown() {
        $statuses = self::getPendingStatuses();
        $dropdown = [];
        foreach ($statuses as $status) {
            $dropdown[$status] = self::getDisplayName($status);
        }
        return $dropdown;
    }

    /**
     * Get approved statuses formatted for dropdown
     *
     * @return array Associative array of value => display name
     */
    public static function getApprovedStatusesForDropdown() {
        $statuses = self::getApprovedStatuses();
        $dropdown = [];
        foreach ($statuses as $status) {
            $dropdown[$status] = self::getDisplayName($status);
        }
        return $dropdown;
    }
}
