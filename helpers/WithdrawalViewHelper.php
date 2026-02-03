<?php
/**
 * Withdrawal View Helper
 * Provides utility functions for withdrawal batch views
 *
 * Functions for:
 * - Status badges and icons
 * - Batch reference formatting
 * - Quantity calculations
 * - Permission checks
 * - Workflow state determination
 *
 * @package ConstructLink
 * @subpackage Helpers
 */

class WithdrawalViewHelper
{
    /**
     * Status constants - eliminates magic strings
     */
    const STATUS_PENDING_VERIFICATION = 'Pending Verification';
    const STATUS_PENDING_APPROVAL = 'Pending Approval';
    const STATUS_APPROVED = 'Approved';
    const STATUS_RELEASED = 'Released';
    const STATUS_CANCELED = 'Canceled';

    /**
     * Workflow roles
     */
    const ROLE_MAKER = 'Warehouseman';
    const ROLE_VERIFIER = 'Project Manager';
    const ROLE_AUTHORIZER_ASSET = 'Asset Director';
    const ROLE_AUTHORIZER_FINANCE = 'Finance Director';

    /**
     * Get icon class for batch status
     *
     * @param string $status Batch status
     * @return string Bootstrap icon class
     */
    public static function getBatchIcon(string $status): string
    {
        $icons = [
            self::STATUS_PENDING_VERIFICATION => 'bi-hourglass-split',
            self::STATUS_PENDING_APPROVAL => 'bi-clock-history',
            self::STATUS_APPROVED => 'bi-check-circle',
            self::STATUS_RELEASED => 'bi-box-arrow-right',
            self::STATUS_CANCELED => 'bi-x-circle'
        ];

        return $icons[$status] ?? 'bi-question-circle';
    }

    /**
     * Get badge color class for batch status
     *
     * @param string $status Batch status
     * @return string Bootstrap background class
     */
    public static function getBatchStatusBadgeClass(string $status): string
    {
        $classes = [
            self::STATUS_PENDING_VERIFICATION => 'bg-warning text-dark',
            self::STATUS_PENDING_APPROVAL => 'bg-info text-white',
            self::STATUS_APPROVED => 'bg-success text-white',
            self::STATUS_RELEASED => 'bg-primary text-white',
            self::STATUS_CANCELED => 'bg-danger text-white'
        ];

        return $classes[$status] ?? 'bg-secondary text-white';
    }

    /**
     * Get CSS class for status badge (using custom classes)
     *
     * @param string $status Batch status
     * @return string CSS class name
     */
    public static function getStatusBadgeCssClass(string $status): string
    {
        $normalized = str_replace(' ', '-', strtolower($status));
        return 'status-' . $normalized;
    }

    /**
     * Calculate total quantity from batch items
     *
     * @param array $items Array of batch items
     * @return int Total quantity
     */
    public static function getTotalQuantity(array $items): int
    {
        return array_sum(array_column($items, 'quantity'));
    }

    /**
     * Calculate total items count from batch
     *
     * @param array $items Array of batch items
     * @return int Total unique items
     */
    public static function getTotalItems(array $items): int
    {
        return count($items);
    }

    /**
     * Format batch reference for display
     * Reference format: WDR-PROJ1-2025-0001
     *
     * @param string $reference Batch reference
     * @return string Formatted reference
     */
    public static function formatBatchReference(string $reference): string
    {
        return htmlspecialchars($reference, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get short batch reference (last 4 digits)
     *
     * @param string $reference Full batch reference
     * @return string Short reference
     */
    public static function getShortReference(string $reference): string
    {
        $parts = explode('-', $reference);
        return end($parts);
    }

    /**
     * Check if user can verify batch
     *
     * @param array $batch Batch data
     * @param object $auth Auth instance
     * @return bool True if user can verify
     */
    public static function canVerify(array $batch, $auth): bool
    {
        if ($batch['status'] !== self::STATUS_PENDING_VERIFICATION) {
            return false;
        }

        return $auth->hasPermission('withdrawal.verify');
    }

    /**
     * Check if user can approve batch
     *
     * @param array $batch Batch data
     * @param object $auth Auth instance
     * @return bool True if user can approve
     */
    public static function canApprove(array $batch, $auth): bool
    {
        if ($batch['status'] !== self::STATUS_PENDING_APPROVAL) {
            return false;
        }

        return $auth->hasPermission('withdrawal.approve');
    }

    /**
     * Check if user can release batch
     *
     * @param array $batch Batch data
     * @param object $auth Auth instance
     * @return bool True if user can release
     */
    public static function canRelease(array $batch, $auth): bool
    {
        if ($batch['status'] !== self::STATUS_APPROVED) {
            return false;
        }

        return $auth->hasPermission('withdrawal.release');
    }

    /**
     * Check if user can cancel batch
     *
     * @param array $batch Batch data
     * @param object $auth Auth instance
     * @return bool True if user can cancel
     */
    public static function canCancel(array $batch, $auth): bool
    {
        if ($batch['status'] === self::STATUS_RELEASED || $batch['status'] === self::STATUS_CANCELED) {
            return false;
        }

        return $auth->hasPermission('withdrawal.cancel');
    }

    /**
     * Check if user can perform any action on batch
     *
     * @param array $batch Batch data
     * @param object $auth Auth instance
     * @return bool True if any action is available
     */
    public static function hasAnyAction(array $batch, $auth): bool
    {
        return self::canVerify($batch, $auth)
            || self::canApprove($batch, $auth)
            || self::canRelease($batch, $auth)
            || self::canCancel($batch, $auth);
    }

    /**
     * Get workflow step for status
     *
     * @param string $status Batch status
     * @return int Workflow step (1-4)
     */
    public static function getWorkflowStep(string $status): int
    {
        $steps = [
            self::STATUS_PENDING_VERIFICATION => 1,
            self::STATUS_PENDING_APPROVAL => 2,
            self::STATUS_APPROVED => 3,
            self::STATUS_RELEASED => 4,
            self::STATUS_CANCELED => 0
        ];

        return $steps[$status] ?? 0;
    }

    /**
     * Get workflow steps data for timeline
     *
     * @param array $batch Batch data with audit trail
     * @return array Workflow steps with completion status
     */
    public static function getWorkflowSteps(array $batch): array
    {
        $currentStep = self::getWorkflowStep($batch['status']);

        $steps = [
            [
                'step' => 1,
                'label' => 'Created',
                'icon' => 'bi-plus-circle',
                'status' => $currentStep >= 1 ? 'completed' : 'pending',
                'date' => $batch['created_at'] ?? null,
                'user' => $batch['created_by_name'] ?? 'System'
            ],
            [
                'step' => 2,
                'label' => 'Verified',
                'icon' => 'bi-check-circle',
                'status' => $currentStep >= 2 ? 'completed' : ($currentStep === 1 ? 'current' : 'pending'),
                'date' => $batch['verified_at'] ?? null,
                'user' => $batch['verified_by_name'] ?? null
            ],
            [
                'step' => 3,
                'label' => 'Approved',
                'icon' => 'bi-check-circle-fill',
                'status' => $currentStep >= 3 ? 'completed' : ($currentStep === 2 ? 'current' : 'pending'),
                'date' => $batch['approved_at'] ?? null,
                'user' => $batch['approved_by_name'] ?? null
            ],
            [
                'step' => 4,
                'label' => 'Released',
                'icon' => 'bi-box-arrow-right',
                'status' => $currentStep >= 4 ? 'completed' : ($currentStep === 3 ? 'current' : 'pending'),
                'date' => $batch['released_at'] ?? null,
                'user' => $batch['released_by_name'] ?? null
            ]
        ];

        // Mark as canceled if status is canceled
        if ($batch['status'] === self::STATUS_CANCELED) {
            foreach ($steps as &$step) {
                if ($step['status'] === 'current' || $step['status'] === 'pending') {
                    $step['status'] = 'canceled';
                }
            }
        }

        return $steps;
    }

    /**
     * Format receiver name for display
     *
     * @param string $receiverName Receiver name (Last, First format)
     * @return string Formatted name
     */
    public static function formatReceiverName(string $receiverName): string
    {
        return htmlspecialchars($receiverName, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get receiver initials for avatar
     *
     * @param string $receiverName Receiver name
     * @return string Two-letter initials
     */
    public static function getReceiverInitials(string $receiverName): string
    {
        $parts = explode(',', $receiverName);
        if (count($parts) === 2) {
            // Last, First format
            $lastName = trim($parts[0]);
            $firstName = trim($parts[1]);
            return strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
        }

        // Single name or other format
        $words = explode(' ', trim($receiverName));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }

        return strtoupper(substr($receiverName, 0, 2));
    }

    /**
     * Format date for display
     *
     * @param string|null $date Date string
     * @param string $format Display format
     * @return string Formatted date or empty string
     */
    public static function formatDate(?string $date, string $format = 'M d, Y'): string
    {
        if (!$date) {
            return '';
        }

        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : '';
    }

    /**
     * Format datetime for display
     *
     * @param string|null $datetime Datetime string
     * @param string $format Display format
     * @return string Formatted datetime or empty string
     */
    public static function formatDateTime(?string $datetime, string $format = 'M d, Y g:i A'): string
    {
        if (!$datetime) {
            return '';
        }

        $timestamp = strtotime($datetime);
        return $timestamp ? date($format, $timestamp) : '';
    }

    /**
     * Get relative time string (e.g., "2 hours ago")
     *
     * @param string|null $datetime Datetime string
     * @return string Relative time string
     */
    public static function getRelativeTime(?string $datetime): string
    {
        if (!$datetime) {
            return 'Never';
        }

        $timestamp = strtotime($datetime);
        if (!$timestamp) {
            return 'Invalid date';
        }

        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M d, Y', $timestamp);
        }
    }

    /**
     * Sanitize output for HTML display
     *
     * @param mixed $value Value to sanitize
     * @return string Sanitized value
     */
    public static function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Truncate text with ellipsis
     *
     * @param string $text Text to truncate
     * @param int $length Maximum length
     * @param string $suffix Suffix to append
     * @return string Truncated text
     */
    public static function truncate(string $text, int $length = 50, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length - strlen($suffix)) . $suffix;
    }
}
