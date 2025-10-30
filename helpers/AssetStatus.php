<?php
/**
 * ConstructLink™ Asset Status Constants
 *
 * This helper class centralizes all asset status values to eliminate
 * hardcoded strings throughout the application.
 *
 * Usage:
 *   - Instead of: if ($status === 'available')
 *   - Use: if ($status === AssetStatus::AVAILABLE)
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

class AssetStatus {
    /**
     * Active Statuses
     */

    // Asset is available for use/borrowing
    const AVAILABLE = 'available';

    // Asset is currently borrowed/checked out
    const BORROWED = 'borrowed';

    // Asset is in use (not borrowable)
    const IN_USE = 'in_use';

    // Asset is in transit between locations
    const IN_TRANSIT = 'in_transit';

    /**
     * Maintenance & Repair Statuses
     */

    // Asset is under maintenance/repair
    const UNDER_MAINTENANCE = 'under_maintenance';

    // Asset is damaged and needs repair
    const DAMAGED = 'damaged';

    /**
     * Terminal Statuses
     */

    // Asset is permanently lost
    const LOST = 'lost';

    // Asset has been disposed of
    const DISPOSED = 'disposed';

    // Asset has been retired from service
    const RETIRED = 'retired';

    /**
     * Get all valid statuses
     *
     * @return array List of all valid status values
     */
    public static function getAllStatuses() {
        return [
            self::AVAILABLE,
            self::BORROWED,
            self::IN_USE,
            self::IN_TRANSIT,
            self::UNDER_MAINTENANCE,
            self::DAMAGED,
            self::LOST,
            self::DISPOSED,
            self::RETIRED,
        ];
    }

    /**
     * Get active statuses (can be used)
     *
     * @return array List of active status values
     */
    public static function getActiveStatuses() {
        return [
            self::AVAILABLE,
            self::BORROWED,
            self::IN_USE,
        ];
    }

    /**
     * Get maintenance statuses
     *
     * @return array List of maintenance status values
     */
    public static function getMaintenanceStatuses() {
        return [
            self::UNDER_MAINTENANCE,
            self::DAMAGED,
        ];
    }

    /**
     * Get terminal statuses (asset lifecycle ended)
     *
     * @return array List of terminal status values
     */
    public static function getTerminalStatuses() {
        return [
            self::LOST,
            self::DISPOSED,
            self::RETIRED,
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
     * Check if status represents an active asset
     *
     * @param string $status Status to check
     * @return bool True if asset is active
     */
    public static function isActive($status) {
        return in_array($status, self::getActiveStatuses());
    }

    /**
     * Check if status requires maintenance
     *
     * @param string $status Status to check
     * @return bool True if asset needs maintenance
     */
    public static function needsMaintenance($status) {
        return in_array($status, self::getMaintenanceStatuses());
    }

    /**
     * Check if status is terminal (asset lifecycle ended)
     *
     * @param string $status Status to check
     * @return bool True if status is terminal
     */
    public static function isTerminal($status) {
        return in_array($status, self::getTerminalStatuses());
    }

    /**
     * Get status badge color for UI display
     *
     * @param string $status Status value
     * @return string Bootstrap color class (primary, warning, success, etc.)
     */
    public static function getStatusBadgeColor($status) {
        switch ($status) {
            case self::AVAILABLE:
                return 'success';

            case self::BORROWED:
            case self::IN_USE:
                return 'primary';

            case self::IN_TRANSIT:
            case self::UNDER_MAINTENANCE:
            case self::DAMAGED:
                return 'warning';

            case self::LOST:
            case self::DISPOSED:
                return 'danger';

            case self::RETIRED:
                return 'secondary';

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
            case self::AVAILABLE:
                return 'Available for use';

            case self::BORROWED:
                return 'Currently borrowed';

            case self::IN_USE:
                return 'In use';

            case self::IN_TRANSIT:
                return 'In transit between locations';

            case self::UNDER_MAINTENANCE:
                return 'Under maintenance';

            case self::DAMAGED:
                return 'Damaged - needs repair';

            case self::LOST:
                return 'Permanently lost';

            case self::DISPOSED:
                return 'Disposed';

            case self::RETIRED:
                return 'Retired from service';

            default:
                return 'Unknown status';
        }
    }

    /**
     * Get display name for status (short version)
     *
     * @param string $status Status value
     * @return string Status display name
     */
    public static function getDisplayName($status) {
        switch ($status) {
            case self::AVAILABLE:
                return 'Available';

            case self::BORROWED:
                return 'Borrowed';

            case self::IN_USE:
                return 'In Use';

            case self::IN_TRANSIT:
                return 'In Transit';

            case self::UNDER_MAINTENANCE:
                return 'Under Maintenance';

            case self::DAMAGED:
                return 'Damaged';

            case self::LOST:
                return 'Lost';

            case self::DISPOSED:
                return 'Disposed';

            case self::RETIRED:
                return 'Retired';

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
            case self::AVAILABLE:
                return 'bi-check-circle-fill';

            case self::BORROWED:
                return 'bi-box-arrow-right';

            case self::IN_USE:
                return 'bi-gear-fill';

            case self::IN_TRANSIT:
                return 'bi-truck';

            case self::UNDER_MAINTENANCE:
                return 'bi-tools';

            case self::DAMAGED:
                return 'bi-exclamation-triangle-fill';

            case self::LOST:
                return 'bi-question-circle-fill';

            case self::DISPOSED:
                return 'bi-trash-fill';

            case self::RETIRED:
                return 'bi-archive-fill';

            default:
                return 'bi-circle';
        }
    }

    /**
     * Get Bootstrap badge class for status display
     * Alias for getStatusBadgeColor() with 'badge-' prefix
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
     * Check if asset can be borrowed in current status
     *
     * @param string $status Status to check
     * @return bool True if asset can be borrowed
     */
    public static function canBeBorrowed($status) {
        return $status === self::AVAILABLE;
    }

    /**
     * Check if asset can be transferred in current status
     *
     * @param string $status Status to check
     * @return bool True if asset can be transferred
     */
    public static function canBeTransferred($status) {
        return in_array($status, [
            self::AVAILABLE,
            self::IN_USE,
            self::BORROWED
        ]);
    }

    /**
     * Check if asset can be retired/disposed in current status
     *
     * @param string $status Status to check
     * @return bool True if asset can be retired/disposed
     */
    public static function canBeRetired($status) {
        return !in_array($status, [
            self::LOST,
            self::DISPOSED,
            self::RETIRED
        ]);
    }

    /**
     * Get all statuses for dropdown with display names
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

    /**
     * Get active statuses for dropdown with display names
     *
     * @return array Associative array of status value => display name
     */
    public static function getActiveStatusesForDropdown() {
        $statuses = self::getActiveStatuses();
        $dropdown = [];
        foreach ($statuses as $status) {
            $dropdown[$status] = self::getDisplayName($status);
        }
        return $dropdown;
    }
}
