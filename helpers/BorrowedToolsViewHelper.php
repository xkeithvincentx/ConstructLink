<?php
/**
 * Borrowed Tools View Helper
 * Extracts business logic from views to maintain MVC separation
 *
 * Provides utility functions for:
 * - Date calculations (overdue, due soon, days remaining)
 * - Status determination
 * - Visual styling (row classes, badges)
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
}
