<?php
/**
 * ConstructLink™ Asset Workflow Service
 *
 * Handles MVA (Maker-Verifier-Authorizer) workflow operations for asset management.
 * Extracted from AssetModel to follow Single Responsibility Principle and 2025 standards.
 *
 * Workflow State Machine:
 * draft → pending_verification → pending_authorization → approved
 *                ↓                        ↓
 *             rejected                 rejected
 *
 * @package ConstructLink
 * @version 2.0.0
 */

require_once APP_ROOT . '/core/utils/ResponseFormatter.php';
require_once APP_ROOT . '/core/traits/ActivityLoggingTrait.php';

class AssetWorkflowService {
    use ActivityLoggingTrait;

    private $db;
    private $assetModel;
    private $auth;

    /**
     * Valid workflow status transitions
     */
    private const WORKFLOW_STATES = [
        'draft' => ['pending_verification'],
        'pending_verification' => ['pending_authorization', 'rejected'],
        'pending_authorization' => ['approved', 'rejected'],
        'approved' => [],
        'rejected' => ['draft']
    ];

    /**
     * Role permissions for workflow actions
     */
    private const ROLE_PERMISSIONS = [
        'submit' => ['System Admin', 'Project Manager', 'Site Inventory Clerk', 'Warehouseman'],
        'verify' => ['System Admin', 'Site Inventory Clerk', 'Project Manager'],
        'authorize' => ['System Admin', 'Project Manager'],
        'reject' => ['System Admin', 'Asset Director', 'Finance Director']
    ];

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     * @param AssetModel|null $assetModel Asset model instance
     * @param Auth|null $auth Auth instance
     */
    public function __construct($db = null, $assetModel = null, $auth = null) {
        if ($db === null) {
            require_once APP_ROOT . '/core/Database.php';
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }

        require_once APP_ROOT . '/models/AssetModel.php';
        require_once APP_ROOT . '/core/Auth.php';

        $this->assetModel = $assetModel ?? new AssetModel();
        $this->auth = $auth ?? Auth::getInstance();
    }

    /**
     * Get assets by workflow status with project scoping
     *
     * @param string $workflowStatus Workflow status to filter by
     * @param int|null $projectId Optional project ID for filtering
     * @return array List of assets
     */
    public function getAssetsByWorkflowStatus($workflowStatus, $projectId = null) {
        try {
            if (!$this->isValidWorkflowStatus($workflowStatus)) {
                error_log("Invalid workflow status: {$workflowStatus}");
                return [];
            }

            $conditions = ["a.workflow_status = ?"];
            $params = [$workflowStatus];

            // Project scoping based on user role
            $currentUser = $this->auth->getCurrentUser();
            if (!$currentUser) {
                error_log("No authenticated user found");
                return [];
            }

            if ($projectId) {
                $conditions[] = "a.project_id = ?";
                $params[] = $projectId;
            } elseif (!in_array($currentUser['role_name'], ['System Admin', 'Finance Director', 'Asset Director'])) {
                // Non-admin users see only their project's assets
                if ($currentUser['current_project_id']) {
                    $conditions[] = "a.project_id = ?";
                    $params[] = $currentUser['current_project_id'];
                }
            }

            $whereClause = "WHERE " . implode(" AND ", $conditions);

            $sql = "
                SELECT a.*,
                       p.name as project_name,
                       c.name as category_name,
                       m.name as maker_name,
                       v.name as vendor_name,
                       u1.full_name as made_by_name,
                       u2.full_name as verified_by_name,
                       u3.full_name as authorized_by_name,
                       po.po_number,
                       pi.item_name as procurement_item_name,
                       pi.brand as procurement_item_brand
                FROM inventory_items a
                LEFT JOIN projects p ON a.project_id = p.id
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN makers m ON a.maker_id = m.id
                LEFT JOIN vendors v ON a.vendor_id = v.id
                LEFT JOIN users u1 ON a.made_by = u1.id
                LEFT JOIN users u2 ON a.verified_by = u2.id
                LEFT JOIN users u3 ON a.authorized_by = u3.id
                LEFT JOIN procurement_orders po ON a.procurement_order_id = po.id
                LEFT JOIN procurement_items pi ON a.procurement_item_id = pi.id
                {$whereClause}
                ORDER BY a.created_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get assets by workflow status error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Submit asset for verification (Maker step)
     *
     * @param int $assetId Asset ID
     * @param int $submittedBy User ID submitting the asset
     * @return array Response with success status
     */
    public function submitForVerification($assetId, $submittedBy) {
        try {
            // Permission check
            if (!$this->checkWorkflowPermission('submit')) {
                return ResponseFormatter::error('You do not have permission to submit assets for verification');
            }

            $this->db->beginTransaction();

            $asset = $this->assetModel->find($assetId);
            if (!$asset) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Asset');
            }

            // Validate state transition
            if (!$this->canTransition($asset['workflow_status'], 'pending_verification')) {
                $this->db->rollBack();
                return ResponseFormatter::error(
                    "Cannot submit asset from {$asset['workflow_status']} status. Asset must be in draft status."
                );
            }

            // Validate asset has required fields
            $validation = $this->validateAssetForSubmission($asset);
            if (!$validation['valid']) {
                $this->db->rollBack();
                return ResponseFormatter::validationError($validation['errors']);
            }

            $updateResult = $this->assetModel->update($assetId, [
                'workflow_status' => 'pending_verification',
                'made_by' => $submittedBy
            ]);

            if (!$updateResult) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to update asset status');
            }

            $this->logActivity(
                'asset_submitted',
                "Asset '{$asset['name']}' submitted for verification",
                'assets',
                $assetId
            );

            $this->db->commit();
            return ResponseFormatter::success('Asset submitted for verification successfully');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Submit asset for verification error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to submit asset for verification');
        }
    }

    /**
     * Verify asset (Verifier step)
     *
     * @param int $assetId Asset ID
     * @param int $verifiedBy User ID verifying the asset
     * @param string|null $notes Verification notes
     * @return array Response with success status
     */
    public function verifyAsset($assetId, $verifiedBy, $notes = null) {
        try {
            // Permission check
            if (!$this->checkWorkflowPermission('verify')) {
                return ResponseFormatter::error('You do not have permission to verify assets');
            }

            $this->db->beginTransaction();

            $asset = $this->assetModel->find($assetId);
            if (!$asset) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Asset');
            }

            // Validate state transition
            if (!$this->canTransition($asset['workflow_status'], 'pending_authorization')) {
                $this->db->rollBack();
                return ResponseFormatter::error(
                    "Cannot verify asset from {$asset['workflow_status']} status. Asset must be pending verification."
                );
            }

            // Prevent self-verification
            if ($asset['made_by'] == $verifiedBy) {
                $this->db->rollBack();
                return ResponseFormatter::error('You cannot verify an asset you submitted');
            }

            $updateResult = $this->assetModel->update($assetId, [
                'workflow_status' => 'pending_authorization',
                'verified_by' => $verifiedBy,
                'verification_date' => date('Y-m-d H:i:s'),
                'verification_notes' => $notes
            ]);

            if (!$updateResult) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to update asset status');
            }

            $this->logActivity(
                'asset_verified',
                "Asset '{$asset['name']}' verified by Asset Director" . ($notes ? ": {$notes}" : ''),
                'assets',
                $assetId
            );

            $this->db->commit();
            return ResponseFormatter::success('Asset verified successfully');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Verify asset error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to verify asset');
        }
    }

    /**
     * Authorize asset (Authorizer step)
     *
     * @param int $assetId Asset ID
     * @param int $authorizedBy User ID authorizing the asset
     * @param string|null $notes Authorization notes
     * @return array Response with success status
     */
    public function authorizeAsset($assetId, $authorizedBy, $notes = null) {
        try {
            // Permission check
            if (!$this->checkWorkflowPermission('authorize')) {
                return ResponseFormatter::error('You do not have permission to authorize assets');
            }

            $this->db->beginTransaction();

            $asset = $this->assetModel->find($assetId);
            if (!$asset) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Asset');
            }

            // Validate state transition
            if (!$this->canTransition($asset['workflow_status'], 'approved')) {
                $this->db->rollBack();
                return ResponseFormatter::error(
                    "Cannot authorize asset from {$asset['workflow_status']} status. Asset must be pending authorization."
                );
            }

            // Prevent self-authorization
            if ($asset['made_by'] == $authorizedBy || $asset['verified_by'] == $authorizedBy) {
                $this->db->rollBack();
                return ResponseFormatter::error('You cannot authorize an asset you submitted or verified');
            }

            // Check if this is a quantity addition approval
            $hasPendingQuantity = !empty($asset['pending_quantity_addition']) && $asset['pending_quantity_addition'] > 0;
            $updateData = [
                'workflow_status' => 'approved',
                'status' => 'available',
                'authorized_by' => $authorizedBy,
                'authorization_date' => date('Y-m-d H:i:s'),
                'authorization_notes' => $notes
            ];

            // If there's a pending quantity addition, approve it
            if ($hasPendingQuantity) {
                $updateData['quantity'] = $asset['quantity'] + $asset['pending_quantity_addition'];
                $updateData['available_quantity'] = $asset['available_quantity'] + $asset['pending_quantity_addition'];
                $updateData['pending_quantity_addition'] = 0;
                $updateData['pending_addition_made_by'] = null;
                $updateData['pending_addition_date'] = null;
            }

            $updateResult = $this->assetModel->update($assetId, $updateData);

            if (!$updateResult) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to update asset status');
            }

            $logMessage = "Asset '{$asset['name']}' authorized by Finance Director";
            if ($hasPendingQuantity) {
                $logMessage .= " (approved quantity addition: +{$asset['pending_quantity_addition']} {$asset['unit']})";
            }
            if ($notes) {
                $logMessage .= ": {$notes}";
            }

            $this->logActivity(
                'asset_authorized',
                $logMessage,
                'assets',
                $assetId
            );

            $this->db->commit();

            $successMessage = $hasPendingQuantity
                ? "Asset authorized successfully! Quantity increased by {$asset['pending_quantity_addition']} {$asset['unit']}."
                : 'Asset authorized successfully and is now available';

            return ResponseFormatter::success($successMessage);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Authorize asset error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to authorize asset');
        }
    }

    /**
     * Reject asset during verification
     *
     * @param int $assetId Asset ID
     * @param int $rejectedBy User ID rejecting the asset
     * @param string $reason Rejection reason
     * @return array Response with success status
     */
    public function rejectVerification($assetId, $rejectedBy, $reason) {
        return $this->rejectAsset($assetId, $rejectedBy, $reason, 'pending_verification');
    }

    /**
     * Reject asset during authorization
     *
     * @param int $assetId Asset ID
     * @param int $rejectedBy User ID rejecting the asset
     * @param string $reason Rejection reason
     * @return array Response with success status
     */
    public function rejectAuthorization($assetId, $rejectedBy, $reason) {
        return $this->rejectAsset($assetId, $rejectedBy, $reason, 'pending_authorization');
    }

    /**
     * Reject asset (common logic for verification and authorization rejection)
     *
     * @param int $assetId Asset ID
     * @param int $rejectedBy User ID rejecting the asset
     * @param string $rejectionReason Rejection reason
     * @param string|null $fromStatus Expected current status
     * @return array Response with success status
     */
    public function rejectAsset($assetId, $rejectedBy, $rejectionReason, $fromStatus = null) {
        try {
            // Permission check
            if (!$this->checkWorkflowPermission('reject')) {
                return ResponseFormatter::error('You do not have permission to reject assets');
            }

            if (empty($rejectionReason)) {
                return ResponseFormatter::validationError(['reason' => 'Rejection reason is required']);
            }

            $this->db->beginTransaction();

            $asset = $this->assetModel->find($assetId);
            if (!$asset) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Asset');
            }

            // Validate current status
            if ($fromStatus && $asset['workflow_status'] !== $fromStatus) {
                $this->db->rollBack();
                return ResponseFormatter::error(
                    "Cannot reject asset from {$asset['workflow_status']} status. Expected {$fromStatus}."
                );
            }

            // Validate state transition
            if (!$this->canTransition($asset['workflow_status'], 'rejected')) {
                $this->db->rollBack();
                return ResponseFormatter::error(
                    "Cannot reject asset in {$asset['workflow_status']} status"
                );
            }

            $updateResult = $this->assetModel->update($assetId, [
                'workflow_status' => 'rejected',
                'rejected_by' => $rejectedBy,
                'rejection_date' => date('Y-m-d H:i:s'),
                'rejection_reason' => $rejectionReason
            ]);

            if (!$updateResult) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to update asset status');
            }

            $this->logActivity(
                'asset_rejected',
                "Asset '{$asset['name']}' rejected: {$rejectionReason}",
                'assets',
                $assetId
            );

            $this->db->commit();
            return ResponseFormatter::success('Asset rejected successfully');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Reject asset error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to reject asset');
        }
    }

    /**
     * Return rejected asset to draft for resubmission
     *
     * @param int $assetId Asset ID
     * @param int $userId User ID requesting the action
     * @return array Response with success status
     */
    public function returnToDraft($assetId, $userId) {
        try {
            $this->db->beginTransaction();

            $asset = $this->assetModel->find($assetId);
            if (!$asset) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Asset');
            }

            if ($asset['workflow_status'] !== 'rejected') {
                $this->db->rollBack();
                return ResponseFormatter::error('Only rejected assets can be returned to draft');
            }

            // Only the original submitter can return to draft
            if ($asset['made_by'] != $userId) {
                $currentUser = $this->auth->getCurrentUser();
                if (!in_array($currentUser['role_name'], ['System Admin', 'Asset Director', 'Finance Director'])) {
                    $this->db->rollBack();
                    return ResponseFormatter::error('Only the original submitter or admins can return asset to draft');
                }
            }

            $updateResult = $this->assetModel->update($assetId, [
                'workflow_status' => 'draft',
                'rejected_by' => null,
                'rejection_date' => null,
                'rejection_reason' => null
            ]);

            if (!$updateResult) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to update asset status');
            }

            $this->logActivity(
                'asset_returned_to_draft',
                "Asset '{$asset['name']}' returned to draft for resubmission",
                'assets',
                $assetId
            );

            $this->db->commit();
            return ResponseFormatter::success('Asset returned to draft successfully');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Return to draft error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to return asset to draft');
        }
    }

    /**
     * Get workflow statistics for dashboard
     *
     * @param int|null $projectId Optional project ID for filtering
     * @return array Workflow statistics
     */
    public function getWorkflowStatistics($projectId = null) {
        try {
            $currentUser = $this->auth->getCurrentUser();
            if (!$currentUser) {
                return $this->getEmptyStatistics();
            }

            $conditions = [];
            $params = [];

            // Project scoping
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
                    SUM(CASE WHEN workflow_status = 'draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN workflow_status = 'pending_verification' THEN 1 ELSE 0 END) as pending_verification,
                    SUM(CASE WHEN workflow_status = 'pending_authorization' THEN 1 ELSE 0 END) as pending_authorization,
                    SUM(CASE WHEN workflow_status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN workflow_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    AVG(
                        CASE
                            WHEN workflow_status = 'approved' AND created_at IS NOT NULL AND authorization_date IS NOT NULL
                            THEN TIMESTAMPDIFF(HOUR, created_at, authorization_date)
                            ELSE NULL
                        END
                    ) as avg_approval_time_hours
                FROM inventory_items
                {$whereClause}
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_assets' => (int)$result['total_assets'],
                'draft' => (int)$result['draft'],
                'pending_verification' => (int)$result['pending_verification'],
                'pending_authorization' => (int)$result['pending_authorization'],
                'approved' => (int)$result['approved'],
                'rejected' => (int)$result['rejected'],
                'avg_approval_time_hours' => round((float)$result['avg_approval_time_hours'], 1)
            ];

        } catch (Exception $e) {
            error_log("Get workflow statistics error: " . $e->getMessage());
            return $this->getEmptyStatistics();
        }
    }

    /**
     * Get pending actions for current user
     *
     * @return array List of pending workflow actions
     */
    public function getPendingActionsForUser() {
        try {
            $currentUser = $this->auth->getCurrentUser();
            if (!$currentUser) {
                return [];
            }

            $pendingActions = [];

            // Asset Directors see pending verification
            if (in_array($currentUser['role_name'], ['System Admin', 'Asset Director'])) {
                $pendingVerification = $this->getAssetsByWorkflowStatus('pending_verification');
                if (!empty($pendingVerification)) {
                    $pendingActions[] = [
                        'action' => 'verify',
                        'count' => count($pendingVerification),
                        'assets' => $pendingVerification
                    ];
                }
            }

            // Finance Directors see pending authorization
            if (in_array($currentUser['role_name'], ['System Admin', 'Finance Director'])) {
                $pendingAuthorization = $this->getAssetsByWorkflowStatus('pending_authorization');
                if (!empty($pendingAuthorization)) {
                    $pendingActions[] = [
                        'action' => 'authorize',
                        'count' => count($pendingAuthorization),
                        'assets' => $pendingAuthorization
                    ];
                }
            }

            return $pendingActions;

        } catch (Exception $e) {
            error_log("Get pending actions error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate if state transition is allowed
     *
     * @param string $fromStatus Current workflow status
     * @param string $toStatus Target workflow status
     * @return bool True if transition is allowed
     */
    private function canTransition($fromStatus, $toStatus) {
        if (!isset(self::WORKFLOW_STATES[$fromStatus])) {
            return false;
        }

        return in_array($toStatus, self::WORKFLOW_STATES[$fromStatus]);
    }

    /**
     * Check if current user has permission for workflow action
     *
     * @param string $action Workflow action (submit, verify, authorize, reject)
     * @return bool True if user has permission
     */
    private function checkWorkflowPermission($action) {
        $currentUser = $this->auth->getCurrentUser();
        if (!$currentUser) {
            return false;
        }

        if (!isset(self::ROLE_PERMISSIONS[$action])) {
            return false;
        }

        return in_array($currentUser['role_name'], self::ROLE_PERMISSIONS[$action]);
    }

    /**
     * Validate asset has required fields for submission
     *
     * @param array $asset Asset data
     * @return array Validation result
     */
    private function validateAssetForSubmission($asset) {
        $errors = [];

        if (empty($asset['name'])) {
            $errors['name'] = 'Asset name is required';
        }

        if (empty($asset['category_id'])) {
            $errors['category_id'] = 'Category is required';
        }

        if (empty($asset['project_id'])) {
            $errors['project_id'] = 'Project is required';
        }

        if (empty($asset['acquired_date'])) {
            $errors['acquired_date'] = 'Acquisition date is required';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check if workflow status is valid
     *
     * @param string $status Workflow status to validate
     * @return bool True if valid
     */
    private function isValidWorkflowStatus($status) {
        return in_array($status, ['draft', 'pending_verification', 'pending_authorization', 'approved', 'rejected']);
    }

    /**
     * Get empty statistics structure
     *
     * @return array Empty statistics
     */
    private function getEmptyStatistics() {
        return [
            'total_assets' => 0,
            'draft' => 0,
            'pending_verification' => 0,
            'pending_authorization' => 0,
            'approved' => 0,
            'rejected' => 0,
            'avg_approval_time_hours' => 0
        ];
    }

    /**
     * Get asset verification data with enhanced details (for verification modal/form)
     *
     * Retrieves comprehensive asset data for verification review including:
     * - All asset fields
     * - Category, equipment type, subtype names
     * - Project, brand information
     * - Creator and verifier details
     * - Discipline tags (parsed and formatted)
     *
     * @param int $assetId Asset ID
     * @return array|false Asset data or false with error message
     */
    public function getVerificationData($assetId) {
        try {
            // Get basic asset data first
            $stmt = $this->db->prepare("SELECT * FROM inventory_items WHERE id = ?");
            $stmt->execute([$assetId]);
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$asset) {
                throw new Exception("Asset not found with ID: " . $assetId);
            }

            // Check if it's a legacy asset
            if ($asset['inventory_source'] !== 'legacy') {
                throw new Exception("Asset is not a legacy asset. Source: " . $asset['inventory_source']);
            }

            // Get additional related data
            try {
                // Category
                if ($asset['category_id']) {
                    $stmt = $this->db->prepare("SELECT name FROM categories WHERE id = ?");
                    $stmt->execute([$asset['category_id']]);
                    $category = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['category_name'] = $category['name'] ?? null;
                }

                // Equipment Type
                if ($asset['equipment_type_id']) {
                    $stmt = $this->db->prepare("SELECT name FROM equipment_types WHERE id = ?");
                    $stmt->execute([$asset['equipment_type_id']]);
                    $equipmentType = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['equipment_type_name'] = $equipmentType['name'] ?? null;
                }

                // Equipment Subtype
                if ($asset['subtype_id']) {
                    $stmt = $this->db->prepare("SELECT subtype_name as name FROM equipment_subtypes WHERE id = ?");
                    $stmt->execute([$asset['subtype_id']]);
                    $subtype = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['subtype_name'] = $subtype['name'] ?? null;
                }

                // Project
                if ($asset['project_id']) {
                    $stmt = $this->db->prepare("SELECT name FROM projects WHERE id = ?");
                    $stmt->execute([$asset['project_id']]);
                    $project = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['project_name'] = $project['name'] ?? null;
                }

                // Brand
                if ($asset['brand_id']) {
                    $stmt = $this->db->prepare("SELECT official_name FROM inventory_brands WHERE id = ?");
                    $stmt->execute([$asset['brand_id']]);
                    $brand = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['brand_name'] = $brand['official_name'] ?? null;
                }

                // Creator
                if ($asset['made_by']) {
                    $stmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
                    $stmt->execute([$asset['made_by']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['created_by_name'] = $user['full_name'] ?? null;
                }

                // Verifier
                if ($asset['verified_by']) {
                    $stmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
                    $stmt->execute([$asset['verified_by']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['verified_by_name'] = $user['full_name'] ?? null;
                }

                // Disciplines - parse discipline_tags field
                if (!empty($asset['discipline_tags'])) {
                    $disciplineCodes = explode(',', $asset['discipline_tags']);
                    $disciplineNames = [];
                    $subDisciplineNames = [];

                    foreach ($disciplineCodes as $code) {
                        $code = trim($code);
                        $stmt = $this->db->prepare("SELECT name, parent_id FROM inventory_disciplines WHERE code = ? OR iso_code = ?");
                        $stmt->execute([$code, $code]);
                        $discipline = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($discipline) {
                            if ($discipline['parent_id'] === null) {
                                // Main discipline
                                $disciplineNames[] = $discipline['name'];
                            } else {
                                // Sub-discipline
                                $subDisciplineNames[] = $discipline['name'];
                            }
                        }
                    }

                    $asset['discipline_names'] = !empty($disciplineNames) ? implode(', ', $disciplineNames) : null;
                    $asset['sub_discipline_names'] = !empty($subDisciplineNames) ? implode(', ', $subDisciplineNames) : null;
                } else {
                    $asset['discipline_names'] = null;
                    $asset['sub_discipline_names'] = null;
                }

            } catch (Exception $e) {
                error_log("Warning: Could not load some related data: " . $e->getMessage());
                // Continue with basic asset data
            }

            return $asset;

        } catch (Exception $e) {
            error_log("Get verification data error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load asset data: ' . $e->getMessage()];
        }
    }

    /**
     * Get asset data for authorization modal (for authorization review)
     *
     * Similar to getVerificationData but checks for pending_authorization status.
     * Only assets that have been verified can be authorized.
     *
     * @param int $assetId Asset ID
     * @return array|false Asset data or false with error message
     */
    public function getAuthorizationData($assetId) {
        try {
            // Get basic asset data
            $stmt = $this->db->prepare("SELECT * FROM inventory_items WHERE id = ?");
            $stmt->execute([$assetId]);
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$asset) {
                throw new Exception("Asset not found with ID: " . $assetId);
            }

            // Only allow authorization for verified assets
            if ($asset['workflow_status'] !== 'pending_authorization') {
                throw new Exception("Asset is not ready for authorization. Current status: " . $asset['workflow_status']);
            }

            // Get additional related data (same as verification)
            try {
                // Category
                if ($asset['category_id']) {
                    $stmt = $this->db->prepare("SELECT name FROM categories WHERE id = ?");
                    $stmt->execute([$asset['category_id']]);
                    $category = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['category_name'] = $category['name'] ?? null;
                }

                // Equipment Type
                if ($asset['equipment_type_id']) {
                    $stmt = $this->db->prepare("SELECT name FROM equipment_types WHERE id = ?");
                    $stmt->execute([$asset['equipment_type_id']]);
                    $equipmentType = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['equipment_type_name'] = $equipmentType['name'] ?? null;
                }

                // Equipment Subtype
                if ($asset['subtype_id']) {
                    $stmt = $this->db->prepare("SELECT subtype_name as name FROM equipment_subtypes WHERE id = ?");
                    $stmt->execute([$asset['subtype_id']]);
                    $subtype = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['subtype_name'] = $subtype['name'] ?? null;
                }

                // Project
                if ($asset['project_id']) {
                    $stmt = $this->db->prepare("SELECT name FROM projects WHERE id = ?");
                    $stmt->execute([$asset['project_id']]);
                    $project = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['project_name'] = $project['name'] ?? null;
                }

                // Verifier
                if ($asset['verified_by']) {
                    $stmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
                    $stmt->execute([$asset['verified_by']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $asset['verified_by_name'] = $user['full_name'] ?? null;
                }

                // Disciplines - parse discipline_tags field
                if (!empty($asset['discipline_tags'])) {
                    $disciplineCodes = explode(',', $asset['discipline_tags']);
                    $disciplineNames = [];
                    $subDisciplineNames = [];

                    foreach ($disciplineCodes as $code) {
                        $code = trim($code);
                        $stmt = $this->db->prepare("SELECT name, parent_id FROM inventory_disciplines WHERE code = ? OR iso_code = ?");
                        $stmt->execute([$code, $code]);
                        $discipline = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($discipline) {
                            if ($discipline['parent_id'] === null) {
                                // Main discipline
                                $disciplineNames[] = $discipline['name'];
                            } else {
                                // Sub-discipline
                                $subDisciplineNames[] = $discipline['name'];
                            }
                        }
                    }

                    $asset['discipline_names'] = !empty($disciplineNames) ? implode(', ', $disciplineNames) : null;
                    $asset['sub_discipline_names'] = !empty($subDisciplineNames) ? implode(', ', $subDisciplineNames) : null;
                } else {
                    $asset['discipline_names'] = null;
                    $asset['sub_discipline_names'] = null;
                }

            } catch (Exception $e) {
                error_log("Warning: Could not load some related data for authorization: " . $e->getMessage());
                // Continue with basic asset data
            }

            return $asset;

        } catch (Exception $e) {
            error_log("Get authorization data error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load asset data: ' . $e->getMessage()];
        }
    }

    /**
     * Reject asset verification with feedback
     *
     * Sends asset back to draft status with feedback notes for the maker to address.
     * Creates a review record in inventory_verification_reviews table.
     *
     * @param int $assetId Asset ID
     * @param int $reviewerId User ID performing rejection
     * @param string $feedbackNotes Feedback for maker
     * @param array|null $validationResults Optional validation results
     * @return array Response with success status
     */
    public function rejectVerificationWithFeedback($assetId, $reviewerId, $feedbackNotes, $validationResults = null) {
        try {
            if (empty($feedbackNotes)) {
                throw new Exception("Feedback notes are required");
            }

            $this->db->beginTransaction();

            // Update asset status back to draft for revision
            $stmt = $this->db->prepare("
                UPDATE inventory_items
                SET workflow_status = 'draft',
                    updated_at = NOW()
                WHERE id = ? AND inventory_source = 'legacy'
            ");
            $stmt->execute([$assetId]);

            // Log the rejection review
            $stmt = $this->db->prepare("
                INSERT INTO inventory_verification_reviews
                (asset_id, reviewer_id, review_type, review_status, review_notes, validation_results, created_at)
                VALUES (?, ?, 'verification', 'needs_revision', ?, ?, NOW())
            ");
            $stmt->execute([
                $assetId,
                $reviewerId,
                $feedbackNotes,
                json_encode($validationResults)
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Asset sent back for revision'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Reject verification error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Approve asset with conditions during verification
     *
     * Approves asset and moves to pending_authorization status.
     * Allows verifier to correct location and quantity during verification.
     * Creates detailed verification review record with quality scores.
     *
     * @param int $assetId Asset ID
     * @param int $verifierId User ID performing approval
     * @param string $verificationNotes Verification notes
     * @param string $verifiedLocation Verified/corrected location
     * @param int|null $verifiedQuantity Verified/corrected quantity
     * @param string $physicalCondition Physical condition notes
     * @param array|null $validationResults Validation scores and results
     * @return array Response with success status
     */
    public function approveWithConditions($assetId, $verifierId, $verificationNotes, $verifiedLocation = '', $verifiedQuantity = null, $physicalCondition = '', $validationResults = null) {
        try {
            $this->db->beginTransaction();

            // Update asset with verification data
            $updateFields = [
                'workflow_status' => 'pending_authorization',
                'verified_by' => $verifierId,
                'verification_date' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Update location if verified
            if (!empty($verifiedLocation)) {
                $updateFields['location'] = $verifiedLocation;
            }

            // Update quantity if verified
            if (!empty($verifiedQuantity)) {
                $updateFields['quantity'] = $verifiedQuantity;
                $updateFields['available_quantity'] = $verifiedQuantity;
            }

            $setClause = implode(', ', array_map(fn($key) => "$key = ?", array_keys($updateFields)));
            $stmt = $this->db->prepare("UPDATE inventory_items SET $setClause WHERE id = ? AND inventory_source = 'legacy'");
            $stmt->execute([...array_values($updateFields), $assetId]);

            // Log the verification review
            $overallScore = $validationResults['overall_score'] ?? null;
            $completenessScore = $validationResults['completeness_score'] ?? null;
            $accuracyScore = $validationResults['accuracy_score'] ?? null;

            $stmt = $this->db->prepare("
                INSERT INTO inventory_verification_reviews
                (asset_id, reviewer_id, review_type, review_status, overall_score, completeness_score, accuracy_score, review_notes, validation_results, physical_verification_completed, location_verified, created_at)
                VALUES (?, ?, 'verification', 'completed', ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $assetId,
                $verifierId,
                $overallScore,
                $completenessScore,
                $accuracyScore,
                $verificationNotes,
                json_encode($validationResults),
                !empty($physicalCondition) ? 1 : 0,
                !empty($verifiedLocation) ? 1 : 0
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Asset approved with conditions and forwarded for authorization'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Approve with conditions error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
