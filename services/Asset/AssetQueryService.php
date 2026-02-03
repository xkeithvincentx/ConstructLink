<?php
/**
 * ConstructLink™ Asset Query Service
 *
 * Handles all search, filtering, and query operations for assets.
 * Extracted from AssetModel as part of god object refactoring initiative.
 * Follows SOLID principles and 2025 industry standards.
 *
 * Responsibilities:
 * - Advanced filtering with multi-criteria support
 * - Pagination and result ordering
 * - Project scoping and permission-based filtering
 * - Category and vendor-based queries
 * - Asset availability checks
 * - Historical data retrieval
 * - Activity log queries
 *
 * Performance Optimizations:
 * - Optimized JOINs for related data
 * - Prepared statements for SQL injection prevention
 * - Efficient pagination with offset/limit
 * - Proper indexing assumptions on foreign keys
 *
 * @package ConstructLink
 * @subpackage Services\Asset
 * @version 2.0.0
 */

require_once APP_ROOT . '/core/Auth.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/models/UserModel.php';
require_once APP_ROOT . '/models/WithdrawalModel.php';

class AssetQueryService {
    private $db;
    private $userModel;

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     * @param UserModel|null $userModel User model instance
     */
    public function __construct($db = null, $userModel = null) {
        if ($db === null) {
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }

        $this->userModel = $userModel ?? new UserModel();
    }

    /**
     * Get assets with advanced filtering and pagination
     *
     * Supports comprehensive filtering with project scoping for non-admin users.
     * BUSINESS RULE: "Available" status requires workflow_status='approved'
     *
     * @param array $filters Filter criteria (status, category_id, project_id, vendor_id, brand_id,
     *                       is_client_supplied, workflow_status, asset_type, search, order_by)
     * @param int $page Current page number (1-indexed)
     * @param int $perPage Number of results per page
     * @return array Paginated results with data and pagination metadata
     */
    public function getAssetsWithFilters($filters = [], $page = 1, $perPage = 20) {
        try {
            $conditions = [];
            $params = [];

            // Project scoping for non-admin users
            $currentUser = Auth::getInstance()->getCurrentUser();

            if (!in_array($currentUser['role_name'], ['System Admin', 'Finance Director', 'Asset Director'])) {
                if ($currentUser['current_project_id']) {
                    $conditions[] = "a.project_id = ?";
                    $params[] = $currentUser['current_project_id'];
                }
            }

            // Apply status filter
            if (!empty($filters['status'])) {
                $conditions[] = "a.status = ?";
                $params[] = $filters['status'];

                // BUSINESS RULE: "Available" means status='available' AND workflow_status='approved'
                // Only show assets that are truly available (not pending verification or authorization)
                // If workflow_status is not explicitly set, auto-apply 'approved' for 'available' status
                if ($filters['status'] === 'available' && empty($filters['workflow_status'])) {
                    $conditions[] = "a.workflow_status = ?";
                    $params[] = 'approved';
                }
            }

            // Apply category filter
            if (!empty($filters['category_id'])) {
                $conditions[] = "a.category_id = ?";
                $params[] = $filters['category_id'];
            }

            // Apply project filter with security check
            if (!empty($filters['project_id'])) {
                // Security check: Ensure non-admin users can only filter their assigned projects
                if (!in_array($currentUser['role_name'], ['System Admin', 'Finance Director', 'Asset Director'])) {
                    if ($filters['project_id'] != $currentUser['current_project_id']) {
                        // Reject unauthorized project access - return empty result
                        return [
                            'data' => [],
                            'pagination' => [
                                'current_page' => 1,
                                'per_page' => $perPage,
                                'total' => 0,
                                'total_pages' => 0,
                                'has_next' => false,
                                'has_prev' => false
                            ]
                        ];
                    }
                }
                $conditions[] = "a.project_id = ?";
                $params[] = $filters['project_id'];
            }

            // Apply vendor filter
            if (!empty($filters['vendor_id'])) {
                $conditions[] = "a.vendor_id = ?";
                $params[] = $filters['vendor_id'];
            }

            // Apply brand filter
            if (!empty($filters['brand_id'])) {
                $conditions[] = "a.brand_id = ?";
                $params[] = $filters['brand_id'];
            }

            // Apply client supplied filter
            if (isset($filters['is_client_supplied'])) {
                $conditions[] = "a.is_client_supplied = ?";
                $params[] = $filters['is_client_supplied'];
            }

            // Apply workflow status filter
            if (!empty($filters['workflow_status'])) {
                $conditions[] = "a.workflow_status = ?";
                $params[] = $filters['workflow_status'];
            }

            // Apply asset type filter
            if (!empty($filters['asset_type'])) {
                // Only consumable and non_consumable are valid
                // Low stock and out of stock removed - replenishment is based on project needs, not stock levels
                switch ($filters['asset_type']) {
                    case 'consumable':
                        $conditions[] = "c.is_consumable = 1";
                        break;
                    case 'non_consumable':
                        $conditions[] = "(c.is_consumable = 0 OR c.is_consumable IS NULL)";
                        break;
                }
            }

            // Apply search filter (ref, name, serial_number)
            if (!empty($filters['search'])) {
                $conditions[] = "(a.ref LIKE ? OR a.name LIKE ? OR a.serial_number LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            // Count total records
            $countSql = "
                SELECT COUNT(*) FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN inventory_brands b ON a.brand_id = b.id
                LEFT JOIN procurement_orders po ON a.procurement_order_id = po.id
                LEFT JOIN procurement_items pi ON a.procurement_item_id = pi.id
                {$whereClause}
            ";
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();

            // Get paginated data with all related information
            $offset = ($page - 1) * $perPage;
            $orderBy = $filters['order_by'] ?? 'a.created_at DESC';

            $dataSql = "
                SELECT a.*,
                       c.name as category_name,
                       p.name as project_name,
                       v.name as vendor_name,
                       b.official_name as brand_name,
                       b.quality_tier as brand_quality,
                       po.po_number, pi.item_name as procurement_item_name, pi.brand as procurement_item_brand
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN inventory_brands b ON a.brand_id = b.id
                LEFT JOIN procurement_orders po ON a.procurement_order_id = po.id
                LEFT JOIN procurement_items pi ON a.procurement_item_id = pi.id
                {$whereClause}
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}
            ";

            $stmt = $this->db->prepare($dataSql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();

            return [
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                    'has_next' => $page < ceil($total / $perPage),
                    'has_prev' => $page > 1
                ]
            ];

        } catch (Exception $e) {
            error_log("Get assets with filters error: " . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_next' => false,
                    'has_prev' => false
                ]
            ];
        }
    }

    /**
     * Get assets by project (for project-scoped operations)
     *
     * @param int $projectId Project ID
     * @param string|null $status Optional status filter
     * @return array Array of assets with category information
     */
    public function getAssetsByProject($projectId, $status = null) {
        try {
            $conditions = ["a.project_id = ?"];
            $params = [$projectId];

            if ($status) {
                $conditions[] = "a.status = ?";
                $params[] = $status;
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT a.*, c.name as category_name
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                {$whereClause}
                ORDER BY a.name ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get assets by project error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available assets for withdrawal/transfer
     *
     * Excludes assets that are currently borrowed, withdrawn, or in transit.
     * Only returns assets with status='available' AND workflow_status='approved'.
     * BUSINESS RULE: Assets must be approved before they can be withdrawn/transferred.
     *
     * @param int|null $projectId Optional project ID filter
     * @return array Array of available assets with project and category info
     */
    public function getAvailableAssets($projectId = null) {
        try {
            // Only include assets that are truly available (not in_transit, borrowed, etc.)
            // AND approved (not pending verification or authorization)
            $conditions = ["a.status = 'available'", "a.workflow_status = 'approved'"];
            $params = [];

            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }

            // Exclude assets that are currently borrowed, withdrawn, or transferred
            $conditions[] = "a.id NOT IN (
                SELECT DISTINCT asset_id FROM borrowed_tools WHERE status = 'borrowed'
                UNION
                SELECT DISTINCT asset_id FROM withdrawals WHERE status IN ('pending', 'released')
                UNION
                SELECT DISTINCT asset_id FROM transfers WHERE status IN ('pending', 'approved')
            )";

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT a.*, c.name as category_name, p.name as project_name
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                {$whereClause}
                ORDER BY p.name ASC, a.name ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get available assets error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get assets by category
     *
     * @param int $categoryId Category ID
     * @param int|null $projectId Optional project ID filter
     * @return array Array of assets with project information
     */
    public function getAssetsByCategory($categoryId, $projectId = null) {
        try {
            $conditions = ["a.category_id = ?"];
            $params = [$categoryId];

            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT a.*, p.name as project_name
                FROM inventory_items a
                LEFT JOIN projects p ON a.project_id = p.id
                {$whereClause}
                ORDER BY a.name ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get assets by category error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get assets by vendor
     *
     * @param int $vendorId Vendor ID
     * @param int|null $projectId Optional project ID filter
     * @return array Array of assets with project and category information
     */
    public function getAssetsByVendor($vendorId, $projectId = null) {
        try {
            $conditions = ["a.vendor_id = ?"];
            $params = [$vendorId];

            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT a.*, p.name as project_name, c.name as category_name
                FROM inventory_items a
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN categories c ON a.category_id = c.id
                {$whereClause}
                ORDER BY a.name ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (Exception $e) {
            error_log("Get assets by vendor error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get complete asset history including withdrawals, transfers, and maintenance
     *
     * Aggregates historical data from multiple related tables and returns
     * a unified timeline sorted by date descending.
     *
     * @param int $assetId Asset ID
     * @return array Array of history entries with type, date, description, status, and full data
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
                SELECT t.*, pf.name as from_project, pt.name as to_project, u.full_name as initiated_by_name
                FROM transfers t
                LEFT JOIN projects pf ON t.from_project = pf.id
                LEFT JOIN projects pt ON t.to_project = pt.id
                LEFT JOIN users u ON t.initiated_by = u.id
                WHERE t.inventory_item_id = ?
                ORDER BY t.created_at DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            $transfers = $stmt->fetchAll();

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
            $maintenance = $stmt->fetchAll();

            foreach ($maintenance as $maint) {
                $history[] = [
                    'type' => 'maintenance',
                    'date' => $maint['created_at'],
                    'description' => "{$maint['type']} maintenance: {$maint['description']}",
                    'status' => $maint['status'],
                    'data' => $maint
                ];
            }

            // Sort by date descending
            usort($history, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            return $history;

        } catch (Exception $e) {
            error_log("Get asset history error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get complete activity logs for an asset
     *
     * Returns all activity log entries for the specified asset from the activity_logs table.
     * Includes user information and can be limited to recent entries.
     *
     * @param int $assetId Asset ID
     * @param int|null $limit Optional limit for number of records (null = all records)
     * @return array Array of activity log entries with user details
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
     * Get asset with detailed information including workflow data
     *
     * Retrieves comprehensive asset data with all related information including:
     * - Category details
     * - Project information
     * - Vendor/maker/client data
     * - Workflow actors (made_by, verified_by, authorized_by)
     * - Procurement linkage
     *
     * @param int $assetId Asset ID
     * @return array|false Asset data with details or false if not found
     */
    public function getAssetWithDetails($assetId) {
        try {
            $sql = "
                SELECT a.*,
                       c.name as category_name, c.is_consumable,
                       p.name as project_name, p.location as project_location,
                       v.name as vendor_name,
                       m.name as maker_name,
                       u1.full_name as made_by_name,
                       u2.full_name as verified_by_name,
                       u3.full_name as authorized_by_name,
                       po.po_number, pi.item_name as procurement_item_name, pi.brand as procurement_item_brand
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN makers m ON a.maker_id = m.id
                LEFT JOIN users u1 ON a.made_by = u1.id
                LEFT JOIN users u2 ON a.verified_by = u2.id
                LEFT JOIN users u3 ON a.authorized_by = u3.id
                LEFT JOIN procurement_orders po ON a.procurement_order_id = po.id
                LEFT JOIN procurement_items pi ON a.procurement_item_id = pi.id
                WHERE a.id = ?
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get asset with details error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enhance asset data with consumable info and units
     *
     * Adds is_consumable flag and unit information to asset records by joining
     * with categories and procurement_items tables. This is used for list views
     * to display quantity units correctly.
     *
     * @param array $assets Array of asset records
     * @return array Enhanced asset records with is_consumable and unit fields
     */
    public function enhanceAssetData($assets) {
        if (empty($assets)) {
            return $assets;
        }

        try {
            // Get category and procurement item data for all assets
            $assetIds = array_column($assets, 'id');
            $placeholders = str_repeat('?,', count($assetIds) - 1) . '?';

            $sql = "
                SELECT a.id, c.is_consumable, pi.unit
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN procurement_items pi ON a.procurement_item_id = pi.id
                WHERE a.id IN ({$placeholders})
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($assetIds);
            $enhancementData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Create lookup array
            $lookup = [];
            foreach ($enhancementData as $data) {
                $lookup[$data['id']] = [
                    'is_consumable' => $data['is_consumable'],
                    'unit' => $data['unit'] ?? 'pcs'
                ];
            }

            // Enhance each asset
            foreach ($assets as &$asset) {
                if (isset($lookup[$asset['id']])) {
                    $asset['is_consumable'] = $lookup[$asset['id']]['is_consumable'];
                    $asset['unit'] = $lookup[$asset['id']]['unit'];
                } else {
                    $asset['is_consumable'] = 0;
                    $asset['unit'] = 'pcs';
                }
            }

        } catch (Exception $e) {
            error_log("Enhance asset data error: " . $e->getMessage());
            // If enhancement fails, add default values
            foreach ($assets as &$asset) {
                $asset['is_consumable'] = 0;
                $asset['unit'] = 'pcs';
            }
        }

        return $assets;
    }
}
