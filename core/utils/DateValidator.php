<?php
/**
 * ConstructLink™ Date Validator Utility
 *
 * Standardizes date validation across the application.
 * Eliminates duplicate date validation logic in models.
 *
 * Usage:
 *   if (!DateValidator::isNotInPast($date)) {
 *       return ResponseFormatter::error('Date cannot be in the past');
 *   }
 *
 * @package ConstructLink
 * @version 1.0.0
 */

class DateValidator {
    /**
     * Check if a date is not in the past
     *
     * @param string $dateString Date string to validate
     * @return bool True if date is today or future, false if in the past
     */
    public static function isNotInPast($dateString) {
        if (empty($dateString)) {
            return false;
        }
        return strtotime($dateString) >= strtotime(date('Y-m-d'));
    }

    /**
     * Check if a date is in the past
     *
     * @param string $dateString Date string to validate
     * @return bool True if date is in the past
     */
    public static function isInPast($dateString) {
        if (empty($dateString)) {
            return false;
        }
        return strtotime($dateString) < strtotime(date('Y-m-d'));
    }

    /**
     * Check if a date is in the future
     *
     * @param string $dateString Date string to validate
     * @return bool True if date is in the future
     */
    public static function isInFuture($dateString) {
        if (empty($dateString)) {
            return false;
        }
        return strtotime($dateString) > strtotime(date('Y-m-d'));
    }

    /**
     * Check if a date is today
     *
     * @param string $dateString Date string to validate
     * @return bool True if date is today
     */
    public static function isToday($dateString) {
        if (empty($dateString)) {
            return false;
        }
        return date('Y-m-d', strtotime($dateString)) === date('Y-m-d');
    }

    /**
     * Validate expected return date for borrowed tools
     *
     * @param string $dateString Expected return date
     * @param string|null &$error Reference variable to store error message
     * @return bool True if valid, false otherwise
     */
    public static function validateExpectedReturn($dateString, &$error = null) {
        if (empty($dateString)) {
            $error = 'Expected return date is required';
            return false;
        }

        if (self::isInPast($dateString)) {
            $error = 'Expected return date cannot be in the past';
            return false;
        }

        return true;
    }

    /**
     * Validate date range (return date must be after borrow date)
     *
     * @param string $borrowDate Borrow date
     * @param string $returnDate Expected return date
     * @param string|null &$error Reference variable to store error message
     * @return bool True if valid, false otherwise
     */
    public static function validateDateRange($borrowDate, $returnDate, &$error = null) {
        if (empty($borrowDate) || empty($returnDate)) {
            $error = 'Both borrow date and return date are required';
            return false;
        }

        if (strtotime($returnDate) < strtotime($borrowDate)) {
            $error = 'Expected return date must be on or after borrow date';
            return false;
        }

        return true;
    }

    /**
     * Calculate days difference between two dates
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return int Number of days between dates
     */
    public static function daysBetween($startDate, $endDate) {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        return (int) ceil(($end - $start) / 86400);
    }

    /**
     * Check if an item is overdue
     *
     * @param string $expectedReturnDate Expected return date
     * @param string|null $actualReturnDate Actual return date (null if not yet returned)
     * @return bool True if overdue
     */
    public static function isOverdue($expectedReturnDate, $actualReturnDate = null) {
        if (empty($expectedReturnDate)) {
            return false;
        }

        $compareDate = $actualReturnDate ?? date('Y-m-d');
        return strtotime($compareDate) > strtotime($expectedReturnDate);
    }

    /**
     * Calculate overdue days
     *
     * @param string $expectedReturnDate Expected return date
     * @param string|null $actualReturnDate Actual return date (null if not yet returned)
     * @return int Number of days overdue (0 if not overdue)
     */
    public static function overdueDays($expectedReturnDate, $actualReturnDate = null) {
        if (!self::isOverdue($expectedReturnDate, $actualReturnDate)) {
            return 0;
        }

        $compareDate = $actualReturnDate ?? date('Y-m-d');
        return self::daysBetween($expectedReturnDate, $compareDate);
    }

    /**
     * Format date for display
     *
     * @param string $dateString Date string
     * @param string $format Format string (default: 'Y-m-d')
     * @return string Formatted date
     */
    public static function format($dateString, $format = 'Y-m-d') {
        if (empty($dateString)) {
            return '';
        }
        return date($format, strtotime($dateString));
    }

    /**
     * Parse various date formats to Y-m-d format
     *
     * @param string $dateString Date string in various formats
     * @return string|false Date in Y-m-d format or false on failure
     */
    public static function parse($dateString) {
        if (empty($dateString)) {
            return false;
        }

        $timestamp = strtotime($dateString);
        if ($timestamp === false) {
            return false;
        }

        return date('Y-m-d', $timestamp);
    }
}
