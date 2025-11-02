<?php
/**
 * Input Validator Class
 * Validates and sanitizes user input to prevent XSS and other attacks
 *
 * @package ConstructLink
 * @since 1.0.0
 */

declare(strict_types=1);

class InputValidator
{
    /**
     * Allowed transfer statuses (whitelist)
     */
    private const ALLOWED_STATUSES = [
        'Pending Verification',
        'Pending Approval',
        'Approved',
        'In Transit',
        'Received',
        'Completed',
        'Canceled'
    ];

    /**
     * Allowed transfer types (whitelist)
     */
    private const ALLOWED_TRANSFER_TYPES = [
        'temporary',
        'permanent'
    ];

    /**
     * Allowed return statuses (whitelist)
     */
    private const ALLOWED_RETURN_STATUSES = [
        'not_returned',
        'in_return_transit',
        'returned'
    ];

    /**
     * Allowed message types (whitelist)
     */
    private const ALLOWED_MESSAGES = [
        'transfer_created',
        'transfer_streamlined',
        'transfer_simplified',
        'transfer_verified',
        'transfer_approved',
        'transfer_received',
        'transfer_completed',
        'transfer_canceled',
        'asset_returned',
        'export_failed'
    ];

    /**
     * Sanitize string input
     *
     * @param string $input Input string
     * @return string Sanitized string
     */
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate and sanitize transfer status
     *
     * @param string|null $status Status to validate
     * @return string|null Validated status or null
     */
    public static function validateStatus(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return in_array($status, self::ALLOWED_STATUSES, true) ? $status : null;
    }

    /**
     * Validate and sanitize transfer type
     *
     * @param string|null $type Transfer type to validate
     * @return string|null Validated type or null
     */
    public static function validateTransferType(?string $type): ?string
    {
        if ($type === null || $type === '') {
            return null;
        }

        return in_array($type, self::ALLOWED_TRANSFER_TYPES, true) ? $type : null;
    }

    /**
     * Validate and sanitize return status
     *
     * @param string|null $returnStatus Return status to validate
     * @return string|null Validated status or null
     */
    public static function validateReturnStatus(?string $returnStatus): ?string
    {
        if ($returnStatus === null || $returnStatus === '') {
            return null;
        }

        return in_array($returnStatus, self::ALLOWED_RETURN_STATUSES, true) ? $returnStatus : null;
    }

    /**
     * Validate and sanitize message parameter
     *
     * @param string|null $message Message to validate
     * @return string|null Validated message or null
     */
    public static function validateMessage(?string $message): ?string
    {
        if ($message === null || $message === '') {
            return null;
        }

        return in_array($message, self::ALLOWED_MESSAGES, true) ? $message : null;
    }

    /**
     * Validate integer ID
     *
     * @param mixed $id ID to validate
     * @return int|null Valid positive integer or null
     */
    public static function validateId($id): ?int
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        return ($id !== false && $id > 0) ? $id : null;
    }

    /**
     * Validate date string
     *
     * @param string|null $date Date string to validate
     * @return string|null Valid date in Y-m-d format or null
     */
    public static function validateDate(?string $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        $parsed = date_parse($date);
        if ($parsed['error_count'] > 0 || $parsed['warning_count'] > 0) {
            return null;
        }

        // Ensure date is in Y-m-d format
        $timestamp = strtotime($date);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }

    /**
     * Sanitize search query
     *
     * @param string|null $query Search query
     * @return string|null Sanitized query or null
     */
    public static function sanitizeSearch(?string $query): ?string
    {
        if ($query === null || $query === '') {
            return null;
        }

        // Remove potential SQL injection characters
        $query = trim($query);
        $query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');

        return strlen($query) > 0 ? $query : null;
    }

    /**
     * Get sanitized GET parameter
     *
     * @param string $key Parameter key
     * @param string|null $default Default value
     * @return string|null Sanitized value or default
     */
    public static function getClean(string $key, ?string $default = null): ?string
    {
        $value = $_GET[$key] ?? $default;
        return $value !== null ? self::sanitizeString($value) : null;
    }

    /**
     * Get sanitized POST parameter
     *
     * @param string $key Parameter key
     * @param string|null $default Default value
     * @return string|null Sanitized value or default
     */
    public static function postClean(string $key, ?string $default = null): ?string
    {
        $value = $_POST[$key] ?? $default;
        return $value !== null ? self::sanitizeString($value) : null;
    }
}
