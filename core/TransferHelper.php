<?php
/**
 * Transfer Helper Class
 * Centralized utilities for transfer status rendering and management
 *
 * @package ConstructLink
 * @since 1.0.0
 */

declare(strict_types=1);

class TransferHelper
{
    /**
     * Status configuration cache
     *
     * @var array|null
     */
    private static ?array $statusConfig = null;

    /**
     * Load status configuration
     *
     * @return array Status configuration
     */
    private static function loadConfig(): array
    {
        if (self::$statusConfig === null) {
            self::$statusConfig = require APP_ROOT . '/config/transfer_statuses.php';
        }

        return self::$statusConfig;
    }

    /**
     * Get status key from value
     *
     * @param string $statusValue Status value (e.g., "Pending Verification")
     * @return string Status key (e.g., "PENDING_VERIFICATION")
     */
    private static function getStatusKey(string $statusValue): string
    {
        $config = self::loadConfig();

        foreach ($config as $key => $data) {
            if ($data['value'] === $statusValue) {
                return $key;
            }
        }

        // Default to first status if not found
        return array_key_first($config);
    }

    /**
     * Get status configuration by value
     *
     * @param string $statusValue Status value
     * @return array Status configuration
     */
    public static function getStatusConfig(string $statusValue): array
    {
        $config = self::loadConfig();
        $statusKey = self::getStatusKey($statusValue);

        return $config[$statusKey] ?? $config['PENDING_VERIFICATION'];
    }

    /**
     * Render status badge HTML
     *
     * @param string $status Status value
     * @param bool $showIcon Include icon in badge
     * @return string HTML badge
     */
    public static function renderStatusBadge(string $status, bool $showIcon = true): string
    {
        $config = self::getStatusConfig($status);

        $icon = $showIcon ? '<i class="' . htmlspecialchars($config['icon']) . ' me-1"></i>' : '';
        $textClass = !empty($config['text_class']) ? ' ' . htmlspecialchars($config['text_class']) : '';

        return sprintf(
            '<span class="badge bg-%s%s">%s%s</span>',
            htmlspecialchars($config['badge_class']),
            $textClass,
            $icon,
            htmlspecialchars($config['label'])
        );
    }

    /**
     * Get status badge class
     *
     * @param string $status Status value
     * @return string Bootstrap badge class (e.g., "bg-warning")
     */
    public static function getStatusBadgeClass(string $status): string
    {
        $config = self::getStatusConfig($status);
        return 'bg-' . $config['badge_class'];
    }

    /**
     * Get status icon class
     *
     * @param string $status Status value
     * @return string Bootstrap icon class (e.g., "bi-clock-history")
     */
    public static function getStatusIcon(string $status): string
    {
        $config = self::getStatusConfig($status);
        return $config['icon'];
    }

    /**
     * Get all available transfer statuses
     *
     * @return array<string, array> All statuses with configuration
     */
    public static function getAllStatuses(): array
    {
        return self::loadConfig();
    }

    /**
     * Get status select options for forms
     *
     * @param string|null $selected Currently selected status
     * @return string HTML option elements
     */
    public static function getStatusSelectOptions(?string $selected = null): string
    {
        $config = self::loadConfig();
        $options = '<option value="">All Statuses</option>';

        foreach ($config as $data) {
            $isSelected = ($selected === $data['value']) ? ' selected' : '';
            $options .= sprintf(
                '<option value="%s"%s>%s</option>',
                htmlspecialchars($data['value']),
                $isSelected,
                htmlspecialchars($data['label'])
            );
        }

        return $options;
    }

    /**
     * Check if status allows specific action
     *
     * @param string $status Transfer status
     * @param string $action Action name (e.g., 'can_verify', 'can_approve')
     * @return bool True if action is allowed
     */
    public static function canPerformAction(string $status, string $action): bool
    {
        $config = self::getStatusConfig($status);
        return $config[$action] ?? false;
    }

    /**
     * Get human-readable description of status
     *
     * @param string $status Status value
     * @return string Description
     */
    public static function getStatusDescription(string $status): string
    {
        $config = self::getStatusConfig($status);
        return $config['description'];
    }

    /**
     * Render transfer type badge
     *
     * @param string $transferType Transfer type ('temporary' or 'permanent')
     * @return string HTML badge
     */
    public static function renderTransferTypeBadge(string $transferType): string
    {
        $badgeClass = ($transferType === 'permanent') ? 'bg-warning' : 'bg-info';
        $label = ucfirst($transferType);

        return sprintf(
            '<span class="badge %s">%s</span>',
            htmlspecialchars($badgeClass),
            htmlspecialchars($label)
        );
    }

    /**
     * Format transfer date for display
     *
     * @param string|null $date Date string
     * @param string $format Date format
     * @return string Formatted date or "Not set"
     */
    public static function formatDate(?string $date, string $format = 'M j, Y'): string
    {
        if (empty($date)) {
            return '<span class="text-muted">Not set</span>';
        }

        $timestamp = strtotime($date);
        return $timestamp !== false ? date($format, $timestamp) : '<span class="text-muted">Invalid date</span>';
    }

    /**
     * Check if return date is overdue
     *
     * @param string|null $expectedReturn Expected return date
     * @param string $transferStatus Transfer status
     * @param string $returnStatus Return status
     * @return bool True if overdue
     */
    public static function isReturnOverdue(?string $expectedReturn, string $transferStatus, string $returnStatus): bool
    {
        if (empty($expectedReturn) || $transferStatus !== 'Completed' || $returnStatus !== 'not_returned') {
            return false;
        }

        return strtotime($expectedReturn) < time();
    }

    /**
     * Get days overdue for return
     *
     * @param string $expectedReturn Expected return date
     * @return int Days overdue (positive number)
     */
    public static function getDaysOverdue(string $expectedReturn): int
    {
        $diff = time() - strtotime($expectedReturn);
        return max(0, (int) floor($diff / (60 * 60 * 24)));
    }
}
