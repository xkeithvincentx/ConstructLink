<?php
/**
 * Dashboard Thresholds Constants
 *
 * Centralized threshold values for dashboard metrics and progress indicators.
 * Eliminates 15+ magic number violations found in role-specific dashboards.
 *
 * @package ConstructLink
 * @subpackage Constants
 * @version 2.0
 * @since 2025-10-28
 */

class DashboardThresholds
{
    /* ==========================================================================
       Budget Utilization Thresholds
       ========================================================================== */

    /**
     * Budget utilization percentage that triggers danger status
     * @var int
     */
    const BUDGET_DANGER_THRESHOLD = 90;

    /**
     * Budget utilization percentage that triggers warning status
     * @var int
     */
    const BUDGET_WARNING_THRESHOLD = 75;

    /**
     * Budget utilization percentage considered safe (success status)
     * @var int
     */
    const BUDGET_SUCCESS_THRESHOLD = 0;

    /* ==========================================================================
       Asset Utilization Thresholds
       ========================================================================== */

    /**
     * Asset utilization rate considered excellent
     * @var int
     */
    const ASSET_UTILIZATION_EXCELLENT = 80;

    /**
     * Asset utilization rate considered good
     * @var int
     */
    const ASSET_UTILIZATION_GOOD = 60;

    /**
     * Asset utilization rate considered fair
     * @var int
     */
    const ASSET_UTILIZATION_FAIR = 40;

    /* ==========================================================================
       Delivery Performance Thresholds
       ========================================================================== */

    /**
     * On-time delivery percentage considered excellent
     * @var int
     */
    const DELIVERY_EXCELLENT_THRESHOLD = 90;

    /**
     * On-time delivery percentage considered good
     * @var int
     */
    const DELIVERY_GOOD_THRESHOLD = 80;

    /**
     * On-time delivery percentage considered acceptable
     * @var int
     */
    const DELIVERY_ACCEPTABLE_THRESHOLD = 70;

    /* ==========================================================================
       Maintenance Performance Thresholds
       ========================================================================== */

    /**
     * Percentage of assets under maintenance that triggers concern
     * @var int
     */
    const MAINTENANCE_WARNING_THRESHOLD = 20;

    /**
     * Percentage of assets under maintenance that triggers critical alert
     * @var int
     */
    const MAINTENANCE_CRITICAL_THRESHOLD = 30;

    /* ==========================================================================
       Incident Rate Thresholds
       ========================================================================== */

    /**
     * Incident rate percentage that triggers warning
     * @var int
     */
    const INCIDENT_RATE_WARNING = 5;

    /**
     * Incident rate percentage that triggers critical alert
     * @var int
     */
    const INCIDENT_RATE_CRITICAL = 10;

    /* ==========================================================================
       Borrowed Tools Thresholds
       ========================================================================== */

    /**
     * Number of days before return due date to show warning
     * @var int
     */
    const BORROWED_TOOLS_WARNING_DAYS = 3;

    /**
     * Percentage of overdue returns that triggers concern
     * @var int
     */
    const BORROWED_TOOLS_OVERDUE_WARNING = 10;

    /**
     * Percentage of overdue returns that triggers critical alert
     * @var int
     */
    const BORROWED_TOOLS_OVERDUE_CRITICAL = 20;

    /* ==========================================================================
       Procurement Performance Thresholds
       ========================================================================== */

    /**
     * Days before procurement order is considered delayed
     * @var int
     */
    const PROCUREMENT_DELAY_WARNING_DAYS = 7;

    /**
     * Days before procurement order is considered severely delayed
     * @var int
     */
    const PROCUREMENT_DELAY_CRITICAL_DAYS = 14;

    /**
     * Percentage of orders completed on time (excellent)
     * @var int
     */
    const PROCUREMENT_ON_TIME_EXCELLENT = 95;

    /**
     * Percentage of orders completed on time (good)
     * @var int
     */
    const PROCUREMENT_ON_TIME_GOOD = 85;

    /* ==========================================================================
       High Value Asset Thresholds
       ========================================================================== */

    /**
     * Monetary value that qualifies an asset as "high value"
     * @var int
     */
    const HIGH_VALUE_ASSET_AMOUNT = 100000;

    /**
     * Monetary value for requests requiring additional approval
     * @var int
     */
    const HIGH_VALUE_REQUEST_AMOUNT = 50000;

    /* ==========================================================================
       Helper Methods
       ========================================================================== */

    /**
     * Get progress bar color based on percentage and thresholds
     *
     * @param float $percentage Current percentage value
     * @param array|null $customThresholds Optional custom thresholds [danger => X, warning => Y]
     * @return string Bootstrap color class (success, warning, danger)
     */
    public static function getProgressColor($percentage, $customThresholds = null)
    {
        if ($customThresholds) {
            $dangerThreshold = $customThresholds['danger'] ?? self::BUDGET_DANGER_THRESHOLD;
            $warningThreshold = $customThresholds['warning'] ?? self::BUDGET_WARNING_THRESHOLD;
        } else {
            $dangerThreshold = self::BUDGET_DANGER_THRESHOLD;
            $warningThreshold = self::BUDGET_WARNING_THRESHOLD;
        }

        if ($percentage >= $dangerThreshold) {
            return 'danger';
        } elseif ($percentage >= $warningThreshold) {
            return 'warning';
        } else {
            return 'success';
        }
    }

    /**
     * Get delivery performance color based on on-time percentage
     *
     * @param float $onTimePercentage Percentage of on-time deliveries
     * @return string Bootstrap color class
     */
    public static function getDeliveryPerformanceColor($onTimePercentage)
    {
        if ($onTimePercentage >= self::DELIVERY_EXCELLENT_THRESHOLD) {
            return 'success';
        } elseif ($onTimePercentage >= self::DELIVERY_GOOD_THRESHOLD) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    /**
     * Get asset utilization color based on utilization rate
     *
     * @param float $utilizationRate Asset utilization percentage
     * @return string Bootstrap color class
     */
    public static function getUtilizationColor($utilizationRate)
    {
        if ($utilizationRate >= self::ASSET_UTILIZATION_EXCELLENT) {
            return 'success';
        } elseif ($utilizationRate >= self::ASSET_UTILIZATION_GOOD) {
            return 'info';
        } elseif ($utilizationRate >= self::ASSET_UTILIZATION_FAIR) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    /**
     * Check if value exceeds high value threshold
     *
     * @param float $amount Monetary amount
     * @return bool True if amount is considered high value
     */
    public static function isHighValue($amount)
    {
        return $amount >= self::HIGH_VALUE_ASSET_AMOUNT;
    }

    /**
     * Check if request requires additional approval due to value
     *
     * @param float $amount Request amount
     * @return bool True if additional approval required
     */
    public static function requiresHighValueApproval($amount)
    {
        return $amount >= self::HIGH_VALUE_REQUEST_AMOUNT;
    }

    /**
     * Get thresholds for progress bar auto-coloring
     *
     * @param string $type Threshold type (budget, delivery, utilization, etc.)
     * @return array Array of thresholds [danger => X, warning => Y, success => Z]
     */
    public static function getThresholds($type = 'budget')
    {
        $thresholds = [
            'budget' => [
                'danger' => self::BUDGET_DANGER_THRESHOLD,
                'warning' => self::BUDGET_WARNING_THRESHOLD,
                'success' => self::BUDGET_SUCCESS_THRESHOLD
            ],
            'delivery' => [
                'success' => self::DELIVERY_EXCELLENT_THRESHOLD,
                'warning' => self::DELIVERY_GOOD_THRESHOLD,
                'danger' => self::DELIVERY_ACCEPTABLE_THRESHOLD
            ],
            'utilization' => [
                'success' => self::ASSET_UTILIZATION_EXCELLENT,
                'info' => self::ASSET_UTILIZATION_GOOD,
                'warning' => self::ASSET_UTILIZATION_FAIR,
                'danger' => 0
            ],
            'maintenance' => [
                'danger' => self::MAINTENANCE_CRITICAL_THRESHOLD,
                'warning' => self::MAINTENANCE_WARNING_THRESHOLD,
                'success' => 0
            ],
            'incident' => [
                'danger' => self::INCIDENT_RATE_CRITICAL,
                'warning' => self::INCIDENT_RATE_WARNING,
                'success' => 0
            ]
        ];

        return $thresholds[$type] ?? $thresholds['budget'];
    }
}
