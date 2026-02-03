<?php
/**
 * ConstructLink™ Asset Location Service
 *
 * Handles all location management operations for assets.
 * Extracted from AssetController as part of Phase 2 refactoring.
 * Follows SOLID principles and 2025 industry standards.
 *
 * Responsibilities:
 * - Asset location assignment and updates
 * - Sub-location management
 * - Location history tracking
 * - Location validation
 *
 * @package ConstructLink
 * @subpackage Services\Asset
 * @version 1.0.0
 */

require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/Validator.php';
require_once APP_ROOT . '/models/AssetModel.php';

class AssetLocationService {
    private $db;
    private $assetModel;

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     * @param AssetModel|null $assetModel Asset model instance
     */
    public function __construct($db = null, $assetModel = null) {
        if ($db === null) {
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }

        $this->assetModel = $assetModel ?? new AssetModel();
    }

    /**
     * Assign or reassign asset to a sub-location
     *
     * Business Rules:
     * - Only Warehouseman, Site Inventory Clerk, and System Admin can assign locations
     * - Sub-location is required (cannot be empty)
     * - Location changes are logged in activity logs
     * - Returns old location for audit trail
     *
     * @param int $assetId Asset ID to assign location to
     * @param string $subLocation New sub-location value
     * @param string $notes Optional notes about the location assignment
     * @param int $userId User ID performing the action
     * @return array Response with success status and details
     */
    public function assignLocation(int $assetId, string $subLocation, string $notes = '', int $userId = 0): array {
        try {
            if (!$assetId) {
                return [
                    'success' => false,
                    'message' => 'Asset ID required'
                ];
            }

            // Sanitize input
            $subLocation = Validator::sanitize($subLocation);
            $notes = Validator::sanitize($notes);

            if (empty($subLocation)) {
                return [
                    'success' => false,
                    'message' => 'Sub-location required'
                ];
            }

            // Verify asset exists
            $asset = $this->assetModel->getAssetWithDetails($assetId);

            if (!$asset) {
                return [
                    'success' => false,
                    'message' => 'Asset not found'
                ];
            }

            // Get old location for logging
            $oldSubLocation = $asset['sub_location'] ?? '';

            // Update asset sub_location
            $result = $this->assetModel->update($assetId, [
                'sub_location' => $subLocation
            ]);

            if ($result) {
                // Log the location assignment activity
                $this->assetModel->logAssetActivity(
                    $assetId,
                    'location_assigned',
                    $oldSubLocation ? "Location changed from '{$oldSubLocation}' to '{$subLocation}'" : "Location assigned to '{$subLocation}'",
                    $asset,
                    array_merge($asset, ['sub_location' => $subLocation]),
                    $notes
                );

                return [
                    'success' => true,
                    'message' => 'Location assigned successfully',
                    'sub_location' => $subLocation,
                    'old_location' => $oldSubLocation
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to assign location'
                ];
            }

        } catch (Exception $e) {
            error_log("Assign location error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to assign location: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get location history for an asset
     *
     * Retrieves all location assignment activities from activity logs.
     *
     * @param int $assetId Asset ID
     * @return array Array of location history entries
     */
    public function getLocationHistory(int $assetId): array {
        try {
            $sql = "
                SELECT
                    al.id,
                    al.action,
                    al.description,
                    al.created_at,
                    al.notes,
                    u.full_name as user_name
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.table_name = 'assets'
                  AND al.record_id = ?
                  AND al.action = 'location_assigned'
                ORDER BY al.created_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$assetId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get location history error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate location assignment permissions
     *
     * @param string $userRole User's role name
     * @return bool True if user can assign locations
     */
    public function canAssignLocation(string $userRole): bool {
        return in_array($userRole, ['Warehouseman', 'Site Inventory Clerk', 'System Admin']);
    }

    /**
     * Get assets by location
     *
     * @param string $subLocation Sub-location to search
     * @param int|null $projectId Optional project ID filter
     * @return array Array of assets at the location
     */
    public function getAssetsByLocation(string $subLocation, ?int $projectId = null): array {
        try {
            $conditions = ["a.sub_location = ?"];
            $params = [$subLocation];

            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT a.*, c.name as category_name, p.name as project_name
                FROM inventory_items a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN projects p ON a.project_id = p.id
                {$whereClause}
                ORDER BY a.name ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get assets by location error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all sub-locations for a project
     *
     * Returns unique list of sub-locations in use.
     *
     * @param int|null $projectId Optional project ID filter
     * @return array Array of sub-location strings
     */
    public function getSubLocations(?int $projectId = null): array {
        try {
            $sql = "
                SELECT DISTINCT sub_location
                FROM inventory_items
                WHERE sub_location IS NOT NULL
                  AND sub_location != ''
            ";

            $params = [];

            if ($projectId) {
                $sql .= " AND project_id = ?";
                $params[] = $projectId;
            }

            $sql .= " ORDER BY sub_location ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_column($results, 'sub_location');

        } catch (Exception $e) {
            error_log("Get sub-locations error: " . $e->getMessage());
            return [];
        }
    }
}
