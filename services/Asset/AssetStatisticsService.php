<?php
/**
 * ConstructLink™ Asset Statistics Service
 *
 * Handles all statistical reporting, analytics, and data aggregation for assets.
 * Extracted from AssetModel as part of god object refactoring initiative.
 * Follows SOLID principles and 2025 industry standards.
 *
 * Responsibilities:
 * - Asset statistics and aggregations
 * - Asset utilization tracking and reporting
 * - Asset value reports by category
 * - Depreciation calculations and reports
 * - Maintenance schedule tracking
 * - Overdue asset tracking (maintenance and withdrawals)
 * - Dashboard statistics and metrics
 * - Project-scoped statistical analysis
 *
 * @package ConstructLink
 * @subpackage Services\Asset
 * @version 2.0.0
 */

require_once APP_ROOT . '/core/Auth.php';
require_once APP_ROOT . '/core/Database.php';

class AssetStatisticsService {
    private $db;
    private $auth;

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     * @param Auth|null $auth Authentication instance
     */
    public function __construct($db = null, $auth = null) {
        $this->db = $db ?? Database::getInstance()->getConnection();
        $this->auth = $auth ?? Auth::getInstance();
    }

    /**
     * Get comprehensive asset statistics with project scoping
     *
     * Returns counts by status, client supplied flag, and value totals.
     * Automatically applies project scoping based on user role and permissions.
     *
     * @param int|null $projectId Optional project filter
     * @return array Statistics with total_assets, available, in_use, borrowed, etc.
     */
    public function getAssetStatistics($projectId = null) {
        try {
            $conditions = [];
            $params = [];

            // Project scoping based on user role
            $currentUser = $this->auth->getCurrentUser();

            if ($projectId) {
                $conditions[] = "project_id = ?";
                $params[] = $projectId;
            } elseif (!in_array($currentUser['role_name'], ['System Admin', 'Finance Director', 'Asset Director'])) {
                if ($currentUser['current_project_id']) {
                    $conditions[] = "project_id = ?";
                    $params[] = $currentUser['current_project_id'];
                }
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $sql = "
                SELECT
                    COUNT(*) as total_assets,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use,
                    SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed,
                    SUM(CASE WHEN status = 'under_maintenance' THEN 1 ELSE 0 END) as under_maintenance,
                    SUM(CASE WHEN status = 'retired' THEN 1 ELSE 0 END) as retired,
                    SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) as in_transit,
                    SUM(CASE WHEN status = 'disposed' THEN 1 ELSE 0 END) as disposed,
                    SUM(CASE WHEN is_client_supplied = 1 THEN 1 ELSE 0 END) as client_supplied,
                    SUM(acquisition_cost) as total_value,
                    AVG(acquisition_cost) as average_value
                FROM inventory_items
                {$whereClause}
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: [
                'total_assets' => 0,
                'available' => 0,
                'in_use' => 0,
                'borrowed' => 0,
                'under_maintenance' => 0,
                'retired' => 0,
                'in_transit' => 0,
                'disposed' => 0,
                'client_supplied' => 0,
                'total_value' => 0,
                'average_value' => 0
            ];

        } catch (Exception $e) {
            error_log("AssetStatisticsService::getAssetStatistics error: " . $e->getMessage());
            return $this->getEmptyStatistics();
        }
    }

    /**
     * Get asset utilization metrics with transaction history
     *
     * Tracks how frequently assets are used through withdrawals, borrowing, and transfers.
     * Higher counts indicate more active utilization.
     *
     * @param int|null $projectId Optional project filter
     * @return array Assets with utilization counts and details
     */
    public function getAssetUtilization($projectId = null) {
        try {
            $conditions = [];
            $params = [];

            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $sql = "
                SELECT a.*, c.name as category_name, p.name as project_name,
                       COUNT(w.id) as withdrawal_count,
                       COUNT(CASE WHEN w.status = 'released' THEN 1 END) as active_withdrawals,
                       COUNT(bt.id) as borrow_count,
                       COUNT(t.id) as transfer_count
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN withdrawals w ON a.id = w.inventory_item_id
                LEFT JOIN borrowed_tools bt ON a.id = bt.inventory_item_id
                LEFT JOIN transfers t ON a.id = t.inventory_item_id
                {$whereClause}
                GROUP BY a.id
                ORDER BY withdrawal_count DESC, borrow_count DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("AssetStatisticsService::getAssetUtilization error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get asset value report grouped by category
     *
     * Provides financial summary including total value, average value,
     * min/max values, and asset counts per category.
     *
     * @param int|null $projectId Optional project filter
     * @return array Value statistics by category
     */
    public function getAssetValueReport($projectId = null) {
        try {
            $conditions = [];
            $params = [];

            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $sql = "
                SELECT
                    c.name as category_name,
                    COUNT(a.id) as asset_count,
                    SUM(a.acquisition_cost) as total_value,
                    AVG(a.acquisition_cost) as average_value,
                    MIN(a.acquisition_cost) as min_value,
                    MAX(a.acquisition_cost) as max_value
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                {$whereClause}
                GROUP BY a.category_id, c.name
                ORDER BY total_value DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("AssetStatisticsService::getAssetValueReport error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get depreciation report with calculated current values
     *
     * Calculates asset depreciation using straight-line method over 5 years:
     * - Year 1-2: 0% depreciation (100% value)
     * - Year 2-3: 20% depreciation (80% value)
     * - Year 3-4: 40% depreciation (60% value)
     * - Year 4-5: 60% depreciation (40% value)
     * - Year 5+: 80% depreciation (20% value)
     *
     * @param int|null $projectId Optional project filter
     * @return array Assets with depreciation calculations
     */
    public function getDepreciationReport($projectId = null) {
        try {
            $conditions = ["a.acquisition_cost IS NOT NULL", "a.acquisition_cost > 0"];
            $params = [];

            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT a.*, c.name as category_name, p.name as project_name,
                       DATEDIFF(CURDATE(), a.acquired_date) as days_owned,
                       ROUND(DATEDIFF(CURDATE(), a.acquired_date) / 365.25, 2) as years_owned,
                       CASE
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1825 THEN a.acquisition_cost * 0.2
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1460 THEN a.acquisition_cost * 0.4
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1095 THEN a.acquisition_cost * 0.6
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 730 THEN a.acquisition_cost * 0.8
                           ELSE a.acquisition_cost
                       END as current_value,
                       CASE
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1825 THEN a.acquisition_cost * 0.8
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1460 THEN a.acquisition_cost * 0.6
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1095 THEN a.acquisition_cost * 0.4
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 730 THEN a.acquisition_cost * 0.2
                           ELSE 0
                       END as depreciation_amount,
                       CASE
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1825 THEN 80
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1460 THEN 60
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 1095 THEN 40
                           WHEN DATEDIFF(CURDATE(), a.acquired_date) >= 730 THEN 20
                           ELSE 0
                       END as depreciation_percentage
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                {$whereClause}
                ORDER BY depreciation_amount DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("AssetStatisticsService::getDepreciationReport error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get maintenance schedule with asset and assignment details
     *
     * Returns upcoming and past maintenance activities with assigned personnel.
     *
     * @param int|null $projectId Optional project filter
     * @return array Scheduled maintenance with asset details
     */
    public function getMaintenanceSchedule($projectId = null) {
        try {
            $conditions = [];
            $params = [];

            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $sql = "
                SELECT a.*, m.scheduled_date, m.type as maintenance_type, m.status as maintenance_status,
                       c.name as category_name, p.name as project_name,
                       u.full_name as assigned_to_name
                FROM inventory_items a
                INNER JOIN maintenance m ON a.id = m.inventory_item_id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN users u ON m.assigned_to = u.id
                {$whereClause}
                ORDER BY m.scheduled_date ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("AssetStatisticsService::getMaintenanceSchedule error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get overdue assets for maintenance or withdrawals
     *
     * Identifies assets with past-due dates to enable proactive management.
     *
     * @param string $type Type of overdue check: 'maintenance' or 'withdrawal'
     * @return array Overdue assets with relevant details
     */
    public function getOverdueAssets($type = 'maintenance') {
        try {
            $sql = "";

            switch ($type) {
                case 'maintenance':
                    $sql = "
                        SELECT a.*, m.scheduled_date, m.type as maintenance_type,
                               p.name as project_name, c.name as category_name,
                               DATEDIFF(CURDATE(), m.scheduled_date) as days_overdue
                        FROM inventory_items a
                        INNER JOIN maintenance m ON a.id = m.inventory_item_id
                        LEFT JOIN projects p ON a.project_id = p.id
                        LEFT JOIN categories c ON a.category_id = c.id
                        WHERE m.status = 'scheduled'
                          AND m.scheduled_date < CURDATE()
                        ORDER BY m.scheduled_date ASC
                    ";
                    break;

                case 'withdrawal':
                    $sql = "
                        SELECT a.*, w.expected_return, w.receiver_name,
                               p.name as project_name, c.name as category_name,
                               DATEDIFF(CURDATE(), w.expected_return) as days_overdue
                        FROM inventory_items a
                        INNER JOIN withdrawals w ON a.id = w.inventory_item_id
                        LEFT JOIN projects p ON a.project_id = p.id
                        LEFT JOIN categories c ON a.category_id = c.id
                        WHERE w.status = 'released'
                          AND w.expected_return IS NOT NULL
                          AND w.expected_return < CURDATE()
                        ORDER BY w.expected_return ASC
                    ";
                    break;

                default:
                    error_log("AssetStatisticsService::getOverdueAssets invalid type: {$type}");
                    return [];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("AssetStatisticsService::getOverdueAssets error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get asset statistics for dashboard API
     *
     * Returns recent asset metrics (last 30 days) for dashboard display.
     * Includes status counts, value totals, and basic analytics.
     *
     * @return array Dashboard statistics
     */
    public function getAssetStats() {
        try {
            $sql = "
                SELECT
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'available' THEN 1 END) as available,
                    COUNT(CASE WHEN status = 'in_use' THEN 1 END) as in_use,
                    COUNT(CASE WHEN status = 'borrowed' THEN 1 END) as borrowed,
                    COUNT(CASE WHEN status = 'under_maintenance' THEN 1 END) as under_maintenance,
                    COUNT(CASE WHEN status = 'retired' THEN 1 END) as retired,
                    COUNT(CASE WHEN status = 'disposed' THEN 1 END) as disposed,
                    COUNT(CASE WHEN status = 'in_transit' THEN 1 END) as in_transit,
                    SUM(CASE WHEN acquisition_cost IS NOT NULL THEN acquisition_cost ELSE 0 END) as total_value,
                    AVG(CASE WHEN acquisition_cost IS NOT NULL THEN acquisition_cost ELSE NULL END) as average_value
                FROM inventory_items
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total' => (int)$result['total'],
                'available' => (int)$result['available'],
                'in_use' => (int)$result['in_use'],
                'borrowed' => (int)$result['borrowed'],
                'under_maintenance' => (int)$result['under_maintenance'],
                'retired' => (int)$result['retired'],
                'disposed' => (int)$result['disposed'],
                'in_transit' => (int)$result['in_transit'],
                'total_value' => (float)$result['total_value'],
                'average_value' => (float)$result['average_value']
            ];

        } catch (Exception $e) {
            error_log("AssetStatisticsService::getAssetStats error: " . $e->getMessage());
            return $this->getEmptyDashboardStats();
        }
    }

    /**
     * Get asset age distribution statistics
     *
     * Groups assets by age ranges for lifecycle analysis.
     *
     * @param int|null $projectId Optional project filter
     * @return array Age distribution statistics
     */
    public function getAssetAgeDistribution($projectId = null) {
        try {
            $conditions = ["a.acquired_date IS NOT NULL"];
            $params = [];

            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT
                    CASE
                        WHEN DATEDIFF(CURDATE(), a.acquired_date) < 365 THEN '0-1 year'
                        WHEN DATEDIFF(CURDATE(), a.acquired_date) < 730 THEN '1-2 years'
                        WHEN DATEDIFF(CURDATE(), a.acquired_date) < 1095 THEN '2-3 years'
                        WHEN DATEDIFF(CURDATE(), a.acquired_date) < 1460 THEN '3-4 years'
                        WHEN DATEDIFF(CURDATE(), a.acquired_date) < 1825 THEN '4-5 years'
                        ELSE '5+ years'
                    END as age_range,
                    COUNT(*) as asset_count,
                    SUM(a.acquisition_cost) as total_value,
                    AVG(a.acquisition_cost) as average_value
                FROM inventory_items a
                {$whereClause}
                GROUP BY age_range
                ORDER BY MIN(DATEDIFF(CURDATE(), a.acquired_date))
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("AssetStatisticsService::getAssetAgeDistribution error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get asset statistics by project
     *
     * Provides project-level breakdown of asset counts and values.
     *
     * @return array Project statistics
     */
    public function getStatisticsByProject() {
        try {
            $sql = "
                SELECT
                    p.id as project_id,
                    p.name as project_name,
                    COUNT(a.id) as total_assets,
                    SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN a.status = 'in_use' THEN 1 ELSE 0 END) as in_use,
                    SUM(CASE WHEN a.status = 'borrowed' THEN 1 ELSE 0 END) as borrowed,
                    SUM(CASE WHEN a.is_client_supplied = 1 THEN 1 ELSE 0 END) as client_supplied,
                    SUM(a.acquisition_cost) as total_value,
                    AVG(a.acquisition_cost) as average_value
                FROM projects p
                LEFT JOIN inventory_items a ON p.id = a.project_id
                WHERE p.status = 'active'
                GROUP BY p.id, p.name
                ORDER BY total_assets DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("AssetStatisticsService::getStatisticsByProject error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get statistics summary for reports
     *
     * Provides comprehensive overview combining multiple metrics.
     *
     * @param int|null $projectId Optional project filter
     * @return array Comprehensive statistics summary
     */
    public function getStatisticsSummary($projectId = null) {
        try {
            return [
                'overview' => $this->getAssetStatistics($projectId),
                'value_report' => $this->getAssetValueReport($projectId),
                'age_distribution' => $this->getAssetAgeDistribution($projectId),
                'overdue_maintenance' => $this->getOverdueAssets('maintenance'),
                'overdue_withdrawals' => $this->getOverdueAssets('withdrawal'),
                'generated_at' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            error_log("AssetStatisticsService::getStatisticsSummary error: " . $e->getMessage());
            return [
                'overview' => $this->getEmptyStatistics(),
                'value_report' => [],
                'age_distribution' => [],
                'overdue_maintenance' => [],
                'overdue_withdrawals' => [],
                'generated_at' => date('Y-m-d H:i:s'),
                'error' => true
            ];
        }
    }

    /**
     * Get empty statistics array as fallback
     *
     * @return array Empty statistics structure
     */
    private function getEmptyStatistics() {
        return [
            'total_assets' => 0,
            'available' => 0,
            'in_use' => 0,
            'borrowed' => 0,
            'under_maintenance' => 0,
            'retired' => 0,
            'in_transit' => 0,
            'disposed' => 0,
            'client_supplied' => 0,
            'total_value' => 0,
            'average_value' => 0
        ];
    }

    /**
     * Get empty dashboard stats as fallback
     *
     * @return array Empty dashboard statistics
     */
    private function getEmptyDashboardStats() {
        return [
            'total' => 0,
            'available' => 0,
            'in_use' => 0,
            'borrowed' => 0,
            'under_maintenance' => 0,
            'retired' => 0,
            'disposed' => 0,
            'in_transit' => 0,
            'total_value' => 0,
            'average_value' => 0
        ];
    }

    /**
     * Generate role-based statistics for dashboard
     *
     * Calculates custom statistics based on user role, providing metrics
     * most relevant to each role's responsibilities.
     *
     * Moved from AssetController as part of Phase 2 refactoring.
     *
     * @param string $userRole User's role name
     * @param array $assets Array of asset data
     * @return array Role-specific statistics
     */
    public function getRoleBasedStats(string $userRole, array $assets): array {
        $stats = [];

        // Calculate basic statistics from assets
        $totalAssets = count($assets);
        $availableAssets = 0;
        $inUseAssets = 0;
        $maintenanceAssets = 0;
        $lowStockItems = 0;
        $totalValue = 0;
        $pendingVerification = 0;
        $pendingAuthorization = 0;
        $approved = 0;
        $projectsWithAssets = [];

        foreach ($assets as $asset) {
            // Basic status counts
            switch ($asset['status'] ?? '') {
                case 'available':
                    $availableAssets++;
                    break;
                case 'in_use':
                case 'borrowed':
                    $inUseAssets++;
                    break;
                case 'under_maintenance':
                    $maintenanceAssets++;
                    break;
            }

            // Workflow status counts
            switch ($asset['workflow_status'] ?? '') {
                case 'pending_verification':
                    $pendingVerification++;
                    break;
                case 'pending_authorization':
                    $pendingAuthorization++;
                    break;
                case 'approved':
                    $approved++;
                    break;
            }

            // Value calculations
            if (!empty($asset['acquisition_cost'])) {
                $totalValue += floatval($asset['acquisition_cost']);
            }

            // Low stock check (for consumables with quantity < 10)
            if (($asset['quantity'] ?? 0) < 10 && ($asset['available_quantity'] ?? 0) < 5) {
                $lowStockItems++;
            }

            // Track projects
            if (!empty($asset['project_id'])) {
                $projectsWithAssets[$asset['project_id']] = true;
            }
        }

        $utilizationRate = $totalAssets > 0 ? round(($inUseAssets / $totalAssets) * 100, 1) : 0;
        $avgAssetValue = $totalAssets > 0 ? $totalValue / $totalAssets : 0;
        $activeProjects = count($projectsWithAssets);

        // Role-specific statistics
        switch ($userRole) {
            case 'Project Manager':
                $stats = [
                    'total_project_assets' => $totalAssets,
                    'available_assets' => $availableAssets,
                    'utilization_rate' => $utilizationRate,
                    'assets_in_use' => $inUseAssets,
                    'low_stock_alerts' => $lowStockItems,
                    'maintenance_pending' => $maintenanceAssets,
                    'pending_authorization' => $pendingAuthorization,
                    'approved_assets' => $approved
                ];
                break;

            case 'Site Inventory Clerk':
                $stats = [
                    'total_inventory_items' => $totalAssets,
                    'total_consumable_units' => array_sum(array_column($assets, 'available_quantity')),
                    'available_for_use' => $availableAssets,
                    'pending_verification' => $pendingVerification,
                    'tools_on_loan' => $inUseAssets,
                    'items_in_transit' => $maintenanceAssets, // Could be refined
                    'today_receipts' => 0, // Would need today's date filter
                    'reorder_alerts' => $lowStockItems
                ];
                break;

            case 'Warehouseman':
                $stats = [
                    'warehouse_inventory' => $totalAssets,
                    'available_stock' => $availableAssets,
                    'tools_on_loan' => $inUseAssets,
                    'items_in_transit' => $maintenanceAssets,
                    'today_receipts' => 0, // Would need today's date filter
                    'reorder_alerts' => $lowStockItems,
                    'pending_verification' => $pendingVerification,
                    'ready_for_issue' => $availableAssets
                ];
                break;

            case 'System Admin':
            case 'Asset Director':
                $stats = [
                    'total_system_assets' => $totalAssets,
                    'active_projects' => $activeProjects,
                    'total_asset_value' => $totalValue,
                    'avg_asset_value' => $avgAssetValue,
                    'workflow_health' => round((($approved / max($totalAssets, 1)) * 100), 1),
                    'pending_verification' => $pendingVerification,
                    'pending_authorization' => $pendingAuthorization,
                    'system_alerts' => $lowStockItems + $maintenanceAssets,
                    'data_quality_score' => 85 // Could be calculated from completeness
                ];
                break;

            default:
                // Default minimal stats for other roles
                $stats = [
                    'total_assets' => $totalAssets,
                    'available_assets' => $availableAssets,
                    'in_use_assets' => $inUseAssets,
                    'maintenance_items' => $maintenanceAssets
                ];
                break;
        }

        return $stats;
    }
}
