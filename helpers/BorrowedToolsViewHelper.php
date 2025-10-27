<?php
/**
 * Borrowed Tools View Helper
 * Extracts business logic from views to maintain MVC separation
 *
 * Provides utility functions for:
 * - Date calculations (overdue, due soon, days remaining)
 * - Status determination
 * - Visual styling (row classes, badges)
 * - Quantity calculations
 * - Request type determination
 *
 * @package ConstructLink
 * @subpackage Helpers
 */

class BorrowedToolsViewHelper
{
    /**
     * Days threshold for "due soon" indicator
     */
    const DUE_SOON_THRESHOLD_DAYS = 3;

    /**
     * Critical equipment acquisition cost threshold
     */
    const CRITICAL_EQUIPMENT_THRESHOLD = 50000;

    /**
     * Status constants - eliminates magic strings
     */
    const STATUS_PENDING_VERIFICATION = 'Pending Verification';
    const STATUS_PENDING_APPROVAL = 'Pending Approval';
    const STATUS_APPROVED = 'Approved';
    const STATUS_RELEASED = 'Released';
    const STATUS_BORROWED = 'Borrowed';
    const STATUS_RETURNED = 'Returned';
    const STATUS_PARTIALLY_RETURNED = 'Partially Returned';
    const STATUS_CANCELED = 'Canceled';

    /**
     * Check if a borrowed tool is overdue
     *
     * @param string $expectedReturn Expected return date (Y-m-d format)
     * @param string $status Current status
     * @return bool True if overdue, false otherwise
     */
    public static function isOverdue(string $expectedReturn, string $status): bool
    {
        // Only borrowed or partially returned items can be overdue
        if (!in_array($status, ['Borrowed', 'Partially Returned', 'Released'])) {
            return false;
        }

        return strtotime($expectedReturn) < time();
    }

    /**
     * Check if a borrowed tool is due soon
     *
     * @param string $expectedReturn Expected return date (Y-m-d format)
     * @param string $status Current status
     * @param int $threshold Days threshold (default: 3)
     * @return bool True if due within threshold, false otherwise
     */
    public static function isDueSoon(string $expectedReturn, string $status, int $threshold = self::DUE_SOON_THRESHOLD_DAYS): bool
    {
        // Only borrowed or partially returned items can be due soon
        if (!in_array($status, ['Borrowed', 'Partially Returned', 'Released'])) {
            return false;
        }

        $daysUntilDue = (strtotime($expectedReturn) - time()) / 86400;
        return $daysUntilDue > 0 && $daysUntilDue <= $threshold;
    }

    /**
     * Calculate days overdue
     *
     * @param string $expectedReturn Expected return date (Y-m-d format)
     * @return int Number of days overdue (0 if not overdue)
     */
    public static function getDaysOverdue(string $expectedReturn): int
    {
        $overdueDays = floor((time() - strtotime($expectedReturn)) / 86400);
        return max(0, $overdueDays);
    }

    /**
     * Calculate days remaining until due
     *
     * @param string $expectedReturn Expected return date (Y-m-d format)
     * @return int Number of days remaining (negative if overdue)
     */
    public static function getDaysRemaining(string $expectedReturn): int
    {
        return floor((strtotime($expectedReturn) - time()) / 86400);
    }

    /**
     * Determine Bootstrap table row class based on priority
     *
     * @param array $tool Tool data with expected_return and status
     * @return string CSS class name (table-danger, table-warning, or empty)
     */
    public static function getRowClass(array $tool): string
    {
        $expectedReturn = $tool['expected_return'] ?? '';
        $status = $tool['status'] ?? '';

        if (self::isOverdue($expectedReturn, $status)) {
            return 'table-danger';
        }

        if (self::isDueSoon($expectedReturn, $status)) {
            return 'table-warning';
        }

        return '';
    }

    /**
     * Get priority indicator HTML (icon + text)
     *
     * @param array $tool Tool data with expected_return and status
     * @return string HTML for priority indicator or empty string
     */
    public static function getPriorityIndicator(array $tool): string
    {
        $expectedReturn = $tool['expected_return'] ?? '';
        $status = $tool['status'] ?? '';

        if (self::isOverdue($expectedReturn, $status)) {
            $daysOverdue = self::getDaysOverdue($expectedReturn);
            return sprintf(
                '<i class="bi bi-exclamation-triangle text-danger me-1" aria-hidden="true"></i><span class="text-danger">Overdue (%d day%s)</span>',
                $daysOverdue,
                $daysOverdue === 1 ? '' : 's'
            );
        }

        if (self::isDueSoon($expectedReturn, $status)) {
            $daysRemaining = self::getDaysRemaining($expectedReturn);
            return sprintf(
                '<i class="bi bi-clock text-warning me-1" aria-hidden="true"></i><span class="text-warning">Due in %d day%s</span>',
                $daysRemaining,
                $daysRemaining === 1 ? '' : 's'
            );
        }

        return '';
    }

    /**
     * Format expected return date with context
     *
     * @param string $expectedReturn Expected return date (Y-m-d format)
     * @param string $status Current status
     * @return string Formatted date with context (e.g., "Dec 25, 2025 (Overdue)")
     */
    public static function formatExpectedReturn(string $expectedReturn, string $status): string
    {
        $formattedDate = date('M d, Y', strtotime($expectedReturn));

        if (self::isOverdue($expectedReturn, $status)) {
            $daysOverdue = self::getDaysOverdue($expectedReturn);
            return sprintf('%s <span class="text-danger">(Overdue by %d day%s)</span>',
                $formattedDate, $daysOverdue, $daysOverdue === 1 ? '' : 's');
        }

        if (self::isDueSoon($expectedReturn, $status)) {
            $daysRemaining = self::getDaysRemaining($expectedReturn);
            return sprintf('%s <span class="text-warning">(Due in %d day%s)</span>',
                $formattedDate, $daysRemaining, $daysRemaining === 1 ? '' : 's');
        }

        return $formattedDate;
    }

    /**
     * Check if a tool item should show overdue reminder button
     *
     * @param array $tool Tool data with expected_return and status
     * @return bool True if reminder button should be shown
     */
    public static function shouldShowOverdueReminder(array $tool): bool
    {
        $expectedReturn = $tool['expected_return'] ?? '';
        $status = $tool['status'] ?? '';

        return self::isOverdue($expectedReturn, $status) &&
               in_array($status, ['Borrowed', 'Partially Returned', 'Released']);
    }

    /**
     * Get Bootstrap badge color for status
     * Helper for ViewHelper::renderStatusBadge if not already implemented
     *
     * @param string $status Status string
     * @return string Bootstrap badge class (bg-primary, bg-warning, etc.)
     */
    public static function getStatusBadgeClass(string $status): string
    {
        $badgeMap = [
            'Pending Verification' => 'bg-warning text-dark',
            'Pending Approval' => 'bg-info',
            'Approved' => 'bg-success',
            'Released' => 'bg-primary',
            'Borrowed' => 'bg-primary',
            'Partially Returned' => 'bg-warning text-dark',
            'Returned' => 'bg-secondary',
            'Canceled' => 'bg-danger',
            'Overdue' => 'bg-danger',
        ];

        return $badgeMap[$status] ?? 'bg-secondary';
    }

    /**
     * Calculate days in use for a batch
     * Uses release_date as start, or created_at if not released yet
     * Uses return_date as end if returned, otherwise uses today
     *
     * @param array $batch Batch data with release_date, created_at, status, return_date
     * @return int Number of days in use
     */
    public static function getDaysInUse(array $batch): int
    {
        // Use release_date as start, or created_at if not released yet
        $startDateStr = $batch['release_date'] ?? $batch['created_at'];
        $startDate = new DateTime($startDateStr);

        // If returned, use return_date, otherwise use today
        if (in_array($batch['status'], [self::STATUS_RETURNED, self::STATUS_PARTIALLY_RETURNED]) && !empty($batch['return_date'])) {
            $endDate = new DateTime($batch['return_date']);
        } elseif (in_array($batch['status'], [self::STATUS_RELEASED, self::STATUS_BORROWED, self::STATUS_PARTIALLY_RETURNED])) {
            $endDate = new DateTime();
        } else {
            // Not yet released (Pending, Approved, etc)
            $endDate = $startDate;
        }

        $duration = $startDate->diff($endDate);
        return $duration->days;
    }

    /**
     * Format days remaining with status text and CSS class
     *
     * @param int $daysRemaining Positive = days remaining, negative = days overdue
     * @return array ['value' => int, 'text' => string, 'class' => string]
     */
    public static function formatDaysRemaining(int $daysRemaining): array
    {
        if ($daysRemaining < 0) {
            return [
                'value' => abs($daysRemaining),
                'text' => 'Days Overdue',
                'class' => 'text-danger'
            ];
        } else {
            return [
                'value' => $daysRemaining,
                'text' => 'Days Remaining',
                'class' => 'text-success'
            ];
        }
    }

    /**
     * Get Bootstrap badge class for remaining quantity
     *
     * @param int $remaining Remaining quantity
     * @return string Bootstrap badge class
     */
    public static function getRemainingQuantityBadgeClass(int $remaining): string
    {
        return $remaining > 0 ? 'bg-warning' : 'bg-secondary';
    }

    /**
     * Check if equipment is critical based on acquisition cost
     *
     * @param float|null $acquisitionCost Equipment acquisition cost (null if unknown)
     * @return bool True if critical (cost > threshold), false if cost is null
     */
    public static function isCriticalEquipment(?float $acquisitionCost): bool
    {
        if ($acquisitionCost === null) {
            return false;
        }
        return $acquisitionCost > self::CRITICAL_EQUIPMENT_THRESHOLD;
    }

    /**
     * Calculate remaining quantity for an item
     *
     * @param array $item Item data with quantity and quantity_returned
     * @return int Remaining quantity
     */
    public static function getRemainingQuantity(array $item): int
    {
        return ($item['quantity'] ?? 0) - ($item['quantity_returned'] ?? 0);
    }

    /**
     * Calculate total returned quantity across all items
     *
     * @param array $items Array of item data
     * @return int Total returned quantity
     */
    public static function getTotalReturned(array $items): int
    {
        return array_sum(array_column($items, 'quantity_returned'));
    }

    /**
     * Calculate total remaining quantity for a batch
     *
     * @param array $batch Batch data with total_quantity and items
     * @return int Total remaining quantity
     */
    public static function getTotalRemaining(array $batch): int
    {
        return ($batch['total_quantity'] ?? 0) - self::getTotalReturned($batch['items'] ?? []);
    }

    /**
     * Check if a batch can be canceled based on status
     *
     * @param string $status Batch status
     * @return bool True if cancelable
     */
    public static function isCancelable(string $status): bool
    {
        return in_array($status, [
            self::STATUS_PENDING_VERIFICATION,
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_APPROVED
        ]);
    }

    /**
     * Check if a batch is returned
     *
     * @param string $status Batch status
     * @return bool True if returned or partially returned
     */
    public static function isReturned(string $status): bool
    {
        return in_array($status, [
            self::STATUS_RETURNED,
            self::STATUS_PARTIALLY_RETURNED
        ]);
    }

    /**
     * Check if a batch is currently borrowed
     *
     * @param string $status Batch status
     * @return bool True if released, borrowed, or partially returned
     */
    public static function isBorrowed(string $status): bool
    {
        return in_array($status, [
            self::STATUS_RELEASED,
            self::STATUS_BORROWED,
            self::STATUS_PARTIALLY_RETURNED
        ]);
    }

    /**
     * Get Bootstrap icon for request type
     *
     * @param bool $isMultiItem True if multi-item request
     * @return string Bootstrap icon name (without 'bi-' prefix)
     */
    public static function getRequestIcon(bool $isMultiItem): string
    {
        return $isMultiItem ? 'cart3' : 'box-seam';
    }
}
