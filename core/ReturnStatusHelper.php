<?php
/**
 * Return Status Helper Class
 * Centralized utilities for return status rendering and management
 *
 * @package ConstructLink
 * @since 1.0.0
 */

declare(strict_types=1);

class ReturnStatusHelper
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
            self::$statusConfig = require APP_ROOT . '/config/return_statuses.php';
        }

        return self::$statusConfig;
    }

    /**
     * Get status key from value
     *
     * @param string $statusValue Status value (e.g., "not_returned")
     * @return string Status key (e.g., "NOT_RETURNED")
     */
    private static function getStatusKey(string $statusValue): string
    {
        $config = self::loadConfig();

        foreach ($config as $key => $data) {
            if ($data['value'] === $statusValue) {
                return $key;
            }
        }

        // Default to NOT_RETURNED if not found
        return 'NOT_RETURNED';
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

        return $config[$statusKey] ?? $config['NOT_RETURNED'];
    }

    /**
     * Render return status badge HTML
     *
     * @param string $status Return status value
     * @param bool $showIcon Include icon in badge
     * @return string HTML badge
     */
    public static function renderStatusBadge(string $status, bool $showIcon = true): string
    {
        $config = self::getStatusConfig($status);

        $icon = $showIcon ? '<i class="' . htmlspecialchars($config['icon']) . ' me-1"></i>' : '';
        $textClass = !empty($config['text_class']) ? ' ' . htmlspecialchars($config['text_class']) : '';

        return sprintf(
            '<span class="badge %s%s">%s%s</span>',
            htmlspecialchars($config['badge_class']),
            $textClass,
            $icon,
            htmlspecialchars($config['label'])
        );
    }

    /**
     * Get status badge class
     *
     * @param string $status Return status value
     * @return string Bootstrap badge class (e.g., "bg-warning")
     */
    public static function getStatusBadgeClass(string $status): string
    {
        $config = self::getStatusConfig($status);
        return $config['badge_class'];
    }

    /**
     * Get status icon class
     *
     * @param string $status Return status value
     * @return string Bootstrap icon class (e.g., "bi-clock")
     */
    public static function getStatusIcon(string $status): string
    {
        $config = self::getStatusConfig($status);
        return $config['icon'];
    }

    /**
     * Get all available return statuses
     *
     * @return array<string, array> All statuses with configuration
     */
    public static function getAllStatuses(): array
    {
        return self::loadConfig();
    }

    /**
     * Check if status allows specific action
     *
     * @param string $status Return status
     * @param string $action Action name (e.g., 'can_initiate_return', 'can_receive_return')
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
     * Calculate days in transit for return
     *
     * @param string|null $returnInitiationDate Return initiation date
     * @return int Days in transit
     */
    public static function getDaysInTransit(?string $returnInitiationDate): int
    {
        if (empty($returnInitiationDate)) {
            return 0;
        }

        $diff = time() - strtotime($returnInitiationDate);
        return max(0, (int) floor($diff / (60 * 60 * 24)));
    }

    /**
     * Get transit badge class based on days in transit
     *
     * @param int $daysInTransit Days in transit
     * @return string Bootstrap badge class
     */
    public static function getTransitBadgeClass(int $daysInTransit): string
    {
        if ($daysInTransit > 3) {
            return 'bg-danger';
        } elseif ($daysInTransit > 1) {
            return 'bg-warning text-dark';
        }

        return 'bg-info';
    }

    /**
     * Render return transit badge with days
     *
     * @param string|null $returnInitiationDate Return initiation date
     * @return string HTML badge or empty string
     */
    public static function renderTransitBadge(?string $returnInitiationDate): string
    {
        if (empty($returnInitiationDate)) {
            return '';
        }

        $daysInTransit = self::getDaysInTransit($returnInitiationDate);
        $badgeClass = self::getTransitBadgeClass($daysInTransit);
        $plural = $daysInTransit != 1 ? 's' : '';

        return sprintf(
            '<span class="badge %s mt-1" style="font-size: 0.7em;">%d day%s in transit</span>',
            htmlspecialchars($badgeClass),
            $daysInTransit,
            $plural
        );
    }
}
