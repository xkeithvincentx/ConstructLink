<?php
/**
 * ConstructLink™ Asset Activity Service
 *
 * Handles all activity logging and historical tracking for assets.
 * Extracted from AssetModel as part of god object refactoring initiative.
 * Follows SOLID principles and 2025 industry standards.
 *
 * Responsibilities:
 * - Asset activity logging with full context tracking
 * - Comprehensive activity log retrieval
 * - Multi-source historical aggregation (withdrawals, transfers, maintenance)
 * - User activity tracking across all assets
 * - System-wide and project-scoped recent activity
 * - Date-ranged activity queries
 * - IP address and user agent tracking for audit trails
 *
 * Performance Optimizations:
 * - Optimized JOINs for related data
 * - Prepared statements for SQL injection prevention
 * - Efficient pagination with limit/offset
 * - Proper indexing assumptions on activity_logs table
 *
 * @package ConstructLink
 * @subpackage Services\Asset
 * @version 2.0.0
 */

require_once APP_ROOT . '/core/Auth.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/traits/ActivityLoggingTrait.php';
require_once APP_ROOT . '/models/WithdrawalModel.php';

class AssetActivityService {
    use ActivityLoggingTrait;

    private $db;

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     */
    public function __construct($db = null) {
        if ($db === null) {
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }
    }

    /**
     * Log asset activity with comprehensive context tracking
     *
     * @param int $assetId Asset ID
     * @param string $action Action performed (e.g., 'create', 'update', 'delete')
     * @param string $description Human-readable description
     * @param array|null $oldValues Previous values (for updates)
     * @param array|null $newValues New values (for updates)
     * @return bool Success status
     */
    public function logAssetActivity($assetId, $action, $description, $oldValues = null, $newValues = null) {
        try {
            // Use ActivityLoggingTrait for standardized logging
            $this->logActivity($action, $description, 'assets', $assetId);
            return true;
        } catch (Exception $e) {
            error_log("Asset activity logging error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get complete activity logs for an asset
     * Returns all activity log entries for the specified asset
     *
     * @param int $assetId Asset ID
     * @param int|null $limit Optional limit for number of records (null = all records)
     * @return array Array of activity log entries
     */
    public function getCompleteActivityLogs($assetId, $limit = null) {
        try {
            $sql = "
                SELECT
                    al.id,
                    al.user_id,
                    al.action,
                    al.description,
                    al.ip_address,
                    al.user_agent,
                    al.created_at,
                    u.full_name as user_name,
                    u.username,
                    u.email as user_email
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.table_name = 'assets' AND al.record_id = ?
                ORDER BY al.created_at DESC
            ";

            if ($limit !== null && is_numeric($limit)) {
                $sql .= " LIMIT " . (int)$limit;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get complete activity logs error for asset $assetId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get comprehensive asset history aggregating multiple sources
     * Combines withdrawals, transfers, and maintenance records
     *
     * @param int $assetId Asset ID
     * @return array Chronologically sorted history array
     */
    public function getAssetHistory($assetId) {
        try {
            $history = [];

            // Get withdrawals
            $withdrawalModel = new WithdrawalModel();
            $withdrawals = $withdrawalModel->getAssetWithdrawalHistory($assetId);
            foreach ($withdrawals as $withdrawal) {
                $history[] = [
                    'type' => 'withdrawal',
                    'date' => $withdrawal['created_at'],
                    'description' => "Withdrawn by {$withdrawal['withdrawn_by_name']} for {$withdrawal['purpose']}",
                    'status' => $withdrawal['status'],
                    'data' => $withdrawal
                ];
            }

            // Get transfers
            $sql = "
                SELECT t.*,
                       pf.name as from_project,
                       pt.name as to_project,
                       u.full_name as initiated_by_name
                FROM transfers t
                LEFT JOIN projects pf ON t.from_project = pf.id
                LEFT JOIN projects pt ON t.to_project = pt.id
                LEFT JOIN users u ON t.initiated_by = u.id
                WHERE t.inventory_item_id = ?
                ORDER BY t.created_at DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($transfers as $transfer) {
                $history[] = [
                    'type' => 'transfer',
                    'date' => $transfer['created_at'],
                    'description' => "Transfer from {$transfer['from_project']} to {$transfer['to_project']} by {$transfer['initiated_by_name']}",
                    'status' => $transfer['status'],
                    'data' => $transfer
                ];
            }

            // Get maintenance records
            $sql = "
                SELECT m.*, u.full_name as assigned_to_name
                FROM maintenance m
                LEFT JOIN users u ON m.assigned_to = u.id
                WHERE m.inventory_item_id = ?
                ORDER BY m.created_at DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($maintenance as $maint) {
                $history[] = [
                    'type' => 'maintenance',
                    'date' => $maint['created_at'],
                    'description' => "{$maint['type']} maintenance: {$maint['description']}",
                    'status' => $maint['status'],
                    'data' => $maint
                ];
            }

            // Sort by date descending (most recent first)
            usort($history, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            return $history;

        } catch (Exception $e) {
            error_log("Get asset history error for asset $assetId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get activity by specific user across all assets
     * Useful for user audit trails and activity reports
     *
     * @param int $userId User ID
     * @param int $limit Maximum number of records to return
     * @return array Array of activity log entries
     */
    public function getActivityByUser($userId, $limit = 50) {
        try {
            $sql = "
                SELECT
                    al.id,
                    al.user_id,
                    al.action,
                    al.description,
                    al.table_name,
                    al.record_id,
                    al.ip_address,
                    al.user_agent,
                    al.created_at,
                    u.full_name as user_name,
                    u.username,
                    a.name as asset_name,
                    a.asset_code as asset_code
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN inventory_items a ON al.record_id = a.id AND al.table_name = 'assets'
                WHERE al.user_id = ? AND al.table_name = 'assets'
                ORDER BY al.created_at DESC
                LIMIT ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, (int)$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get activity by user error for user $userId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent activity system-wide or filtered by project
     * Provides dashboard-style recent activity feed
     *
     * @param int $limit Maximum number of records to return
     * @param int|null $projectId Optional project filter
     * @return array Array of recent activity entries
     */
    public function getRecentActivity($limit = 50, $projectId = null) {
        try {
            $sql = "
                SELECT
                    al.id,
                    al.user_id,
                    al.action,
                    al.description,
                    al.table_name,
                    al.record_id,
                    al.created_at,
                    u.full_name as user_name,
                    u.username,
                    a.name as asset_name,
                    a.asset_code as asset_code,
                    a.project_id
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN inventory_items a ON al.record_id = a.id AND al.table_name = 'assets'
                WHERE al.table_name = 'assets'
            ";

            $params = [];

            if ($projectId !== null) {
                $sql .= " AND a.project_id = ?";
                $params[] = $projectId;
            }

            $sql .= " ORDER BY al.created_at DESC LIMIT ?";
            $params[] = (int)$limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get recent activity error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get activity logs filtered by date range
     * Useful for compliance reporting and historical audits
     *
     * @param int $assetId Asset ID
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @return array Array of activity log entries within date range
     */
    public function getActivityByDateRange($assetId, $startDate, $endDate) {
        try {
            $sql = "
                SELECT
                    al.id,
                    al.user_id,
                    al.action,
                    al.description,
                    al.ip_address,
                    al.user_agent,
                    al.created_at,
                    u.full_name as user_name,
                    u.username,
                    u.email as user_email
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.table_name = 'assets'
                  AND al.record_id = ?
                  AND DATE(al.created_at) BETWEEN ? AND ?
                ORDER BY al.created_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId, $startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get activity by date range error for asset $assetId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get activity statistics for an asset
     * Returns aggregated counts by action type
     *
     * @param int $assetId Asset ID
     * @return array Associative array with action counts
     */
    public function getActivityStatistics($assetId) {
        try {
            $sql = "
                SELECT
                    action,
                    COUNT(*) as count
                FROM activity_logs
                WHERE table_name = 'assets' AND record_id = ?
                GROUP BY action
                ORDER BY count DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Transform to associative array
            $statistics = [];
            foreach ($results as $row) {
                $statistics[$row['action']] = (int)$row['count'];
            }

            return $statistics;

        } catch (Exception $e) {
            error_log("Get activity statistics error for asset $assetId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get most active users for an asset
     * Returns users with the most activity on a specific asset
     *
     * @param int $assetId Asset ID
     * @param int $limit Maximum number of users to return
     * @return array Array of users with activity counts
     */
    public function getMostActiveUsers($assetId, $limit = 10) {
        try {
            $sql = "
                SELECT
                    u.id,
                    u.full_name,
                    u.username,
                    COUNT(*) as activity_count,
                    MAX(al.created_at) as last_activity
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.table_name = 'assets' AND al.record_id = ?
                GROUP BY u.id, u.full_name, u.username
                ORDER BY activity_count DESC
                LIMIT ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId, (int)$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get most active users error for asset $assetId: " . $e->getMessage());
            return [];
        }
    }
}
