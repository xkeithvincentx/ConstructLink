<?php
/**
 * ConstructLink™ Dashboard Service
 * Handles dashboard business logic and data processing
 *
 * Responsibilities:
 * - Permission-based action filtering for quick actions
 * - Dashboard data aggregation and formatting
 * - Business rules for dashboard widgets
 *
 * Follows MVC separation of concerns:
 * - Controllers call this service to get processed data
 * - Views receive ready-to-display data (no business logic in views)
 *
 * @package ConstructLink
 * @subpackage Services
 * @version 1.0
 * @since 2025-11-02
 */

class DashboardService {

    /**
     * Filter quick actions based on user permissions
     *
     * Removes actions that require permissions the user doesn't have.
     * This ensures users only see actions they're authorized to perform.
     *
     * @param array $actions Array of action configurations
     *   Each action should have:
     *   - 'label' (string): Action label
     *   - 'route' (string): URL route
     *   - 'icon' (string): Bootstrap icon class
     *   - 'permission' (string|null): Required permission (null = no permission required)
     *
     * @return array Filtered actions (with 'permission' key removed)
     *
     * @example
     * ```php
     * $service = new DashboardService();
     * $allActions = [
     *     ['label' => 'New Request', 'route' => 'borrowed-tools/create', 'permission' => 'borrowed_tools.create'],
     *     ['label' => 'View Inventory', 'route' => 'assets', 'permission' => 'assets.view']
     * ];
     * $authorizedActions = $service->filterActionsByPermission($allActions);
     * ```
     */
    public function filterActionsByPermission(array $actions): array {
        // Filter based on permissions
        $filtered = array_filter($actions, function($action) {
            // If no permission required, always include
            if (!isset($action['permission']) || $action['permission'] === null) {
                return true;
            }

            // Check if user has the required permission
            return hasPermission($action['permission']);
        });

        // Remove permission key (views don't need it)
        $filtered = array_map(function($action) {
            unset($action['permission']);
            return $action;
        }, $filtered);

        // Re-index array (remove gaps from filtering)
        return array_values($filtered);
    }

    /**
     * Get authorized quick actions for Warehouseman role
     *
     * Returns a list of quick action buttons filtered by user permissions.
     * Centralizes the warehouseman action definitions and permission checking.
     *
     * @return array Authorized actions ready for display
     */
    public function getWarehousemanActions(): array {
        $allActions = [
            [
                'label' => 'Process Deliveries',
                'route' => 'procurement-orders/for-receipt',
                'icon' => 'bi-box-arrow-in-down',
                'permission' => null // No permission required
            ],
            [
                'label' => 'Release Items',
                'route' => 'withdrawals/pending',
                'icon' => 'bi-box-arrow-right',
                'permission' => null // No permission required
            ],
            [
                'label' => 'New Request',
                'route' => 'borrowed-tools/create-batch',
                'icon' => 'bi-tools',
                'permission' => 'borrowed_tools.create'
            ],
            [
                'label' => 'View Inventory',
                'route' => 'assets?status=available',
                'icon' => 'bi-list-ul',
                'permission' => 'assets.view'
            ]
        ];

        return $this->filterActionsByPermission($allActions);
    }

    /**
     * Calculate pending action item counts and determine critical status
     *
     * Processes pending items to add computed properties like 'critical' flag
     * and formatted count badges.
     *
     * @param array $pendingItems Pending action items with counts
     * @return array Enhanced pending items with critical flags
     *
     * @example
     * ```php
     * $service = new DashboardService();
     * $items = [
     *     ['label' => 'Overdue Returns', 'count' => 5],
     *     ['label' => 'Pending Approvals', 'count' => 2]
     * ];
     * $enhanced = $service->enhancePendingItems($items, ['Overdue Returns']);
     * // Returns: [['label' => 'Overdue Returns', 'count' => 5, 'critical' => true], ...]
     * ```
     */
    public function enhancePendingItems(array $pendingItems, array $criticalLabels = []): array {
        return array_map(function($item) use ($criticalLabels) {
            // Mark as critical if label is in critical list or count exceeds threshold
            $item['critical'] = in_array($item['label'] ?? '', $criticalLabels) ||
                               ($item['count'] ?? 0) > 10; // More than 10 items = critical

            return $item;
        }, $pendingItems);
    }

    /**
     * Format dashboard statistics for display
     *
     * Standardizes number formatting and adds visual indicators
     * (e.g., trends, percentages, color coding).
     *
     * @param array $stats Raw statistics data
     * @return array Formatted statistics ready for display
     */
    public function formatDashboardStats(array $stats): array {
        return array_map(function($stat) {
            // Add thousand separators to counts
            if (isset($stat['count'])) {
                $stat['formatted_count'] = number_format($stat['count']);
            }

            // Calculate percentage if total is provided
            if (isset($stat['count']) && isset($stat['total']) && $stat['total'] > 0) {
                $stat['percentage'] = round(($stat['count'] / $stat['total']) * 100, 1);
            }

            return $stat;
        }, $stats);
    }

    /**
     * Determine if a metric exceeds warning threshold
     *
     * Used for dashboard widgets that need visual alerts.
     *
     * @param int|float $value Current metric value
     * @param int|float $threshold Warning threshold
     * @return bool True if value exceeds threshold
     */
    public function exceedsThreshold($value, $threshold): bool {
        return $value > $threshold;
    }

    /**
     * Get role-specific dashboard configuration
     *
     * Returns dashboard layout and widget configuration for a given role.
     * Centralizes role-specific dashboard rules.
     *
     * @param string $role User role name
     * @return array Dashboard configuration
     */
    public function getDashboardConfig(string $role): array {
        $configs = [
            'Warehouseman' => [
                'show_inventory_levels' => true,
                'show_pending_deliveries' => true,
                'show_financial_metrics' => false,
                'default_view' => 'inventory'
            ],
            'Finance Director' => [
                'show_inventory_levels' => true,
                'show_pending_deliveries' => false,
                'show_financial_metrics' => true,
                'default_view' => 'financial'
            ],
            'Asset Director' => [
                'show_inventory_levels' => true,
                'show_pending_deliveries' => true,
                'show_financial_metrics' => false,
                'default_view' => 'assets'
            ],
            // Add more role configs as needed
        ];

        return $configs[$role] ?? [
            'show_inventory_levels' => true,
            'show_pending_deliveries' => true,
            'show_financial_metrics' => false,
            'default_view' => 'overview'
        ];
    }
}
