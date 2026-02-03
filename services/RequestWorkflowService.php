<?php
/**
 * ConstructLink Request Workflow Service
 *
 * Handles MVA (Maker-Verifier-Authorizer) workflow operations for requests with
 * dynamic routing based on initiator role. Follows DRY principles by reusing
 * patterns from WithdrawalBatchWorkflowService.
 *
 * WORKFLOW SCENARIOS:
 * Scenario A (Warehouseman initiates):
 *   Draft → Submitted → Verified (Site Inv Clerk) → Authorized (PM) → Approved (FD) → Procured → Fulfilled
 *
 * Scenario B (Site Inventory Clerk initiates):
 *   Draft → Submitted → Verified (PM) → Approved (FD) → Procured → Fulfilled
 *
 * Scenario C (Project Manager initiates):
 *   Draft → Submitted → Approved (FD) → Procured → Fulfilled
 *
 * @package ConstructLink
 * @version 1.0.0
 */

require_once APP_ROOT . '/core/utils/ResponseFormatter.php';
require_once APP_ROOT . '/core/traits/ActivityLoggingTrait.php';

class RequestWorkflowService {
    use ActivityLoggingTrait;

    private $db;
    private $requestModel;
    private $roleConfig;

    // Role hierarchy levels for dynamic routing
    private const ROLE_HIERARCHY = [
        'Warehouseman' => 1,
        'Site Inventory Clerk' => 2,
        'Site Admin' => 2,
        'Project Manager' => 3,
        'Finance Director' => 4,
        'Asset Director' => 4,
        'System Admin' => 5
    ];

    /**
     * Constructor with dependency injection
     *
     * @param PDO|null $db Database connection
     * @param RequestModel|null $requestModel Request model instance
     */
    public function __construct($db = null, $requestModel = null) {
        if ($db === null) {
            require_once APP_ROOT . '/core/Database.php';
            $database = Database::getInstance();
            $this->db = $database->getConnection();
        } else {
            $this->db = $db;
        }

        require_once APP_ROOT . '/models/RequestModel.php';
        $this->requestModel = $requestModel ?? new RequestModel($this->db);

        $this->roleConfig = require APP_ROOT . '/config/roles.php';
    }

    /**
     * Get the role hierarchy level for a user
     *
     * @param int $userId User ID
     * @return array User role information
     */
    private function getUserRole($userId) {
        try {
            $sql = "SELECT u.id, u.full_name, r.name as role_name
                    FROM users u
                    LEFT JOIN roles r ON u.role_id = r.id
                    WHERE u.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return null;
            }

            $user['hierarchy_level'] = self::ROLE_HIERARCHY[$user['role_name']] ?? 0;
            return $user;
        } catch (Exception $e) {
            error_log("Get user role error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Determine next approver based on maker's role and current status
     *
     * Dynamic routing logic:
     * - Lower hierarchy roles need more approval steps
     * - Higher hierarchy roles skip intermediate approval steps
     *
     * @param int $requestId Request ID
     * @return array|null Next approver role and required status, or null if workflow complete
     */
    public function getNextApprover($requestId) {
        try {
            $request = $this->requestModel->find($requestId);
            if (!$request) {
                return null;
            }

            $maker = $this->getUserRole($request['requested_by']);
            if (!$maker) {
                return null;
            }

            $makerLevel = $maker['hierarchy_level'];
            $currentStatus = $request['status'];

            // Submitted → Determine verifier based on maker level
            if ($currentStatus === 'Submitted') {
                if ($makerLevel === 1) {
                    // Warehouseman → Site Inventory Clerk verifies
                    return [
                        'role' => 'Site Inventory Clerk',
                        'next_status' => 'Verified',
                        'action' => 'verify'
                    ];
                } elseif ($makerLevel === 2) {
                    // Site Inventory Clerk → Project Manager verifies
                    return [
                        'role' => 'Project Manager',
                        'next_status' => 'Verified',
                        'action' => 'verify'
                    ];
                } elseif ($makerLevel >= 3) {
                    // Project Manager or higher → Finance Director approves directly
                    return [
                        'role' => 'Finance Director',
                        'next_status' => 'Approved',
                        'action' => 'approve'
                    ];
                }
            }

            // Verified → Determine authorizer based on maker level
            if ($currentStatus === 'Verified') {
                if ($makerLevel === 1) {
                    // Warehouseman → Project Manager authorizes
                    return [
                        'role' => 'Project Manager',
                        'next_status' => 'Authorized',
                        'action' => 'authorize'
                    ];
                } else {
                    // Site Inventory Clerk or higher → Finance Director approves directly
                    return [
                        'role' => 'Finance Director',
                        'next_status' => 'Approved',
                        'action' => 'approve'
                    ];
                }
            }

            // Authorized → Finance Director final approval
            if ($currentStatus === 'Authorized') {
                return [
                    'role' => 'Finance Director',
                    'next_status' => 'Approved',
                    'action' => 'approve'
                ];
            }

            // No further approvers needed
            return null;

        } catch (Exception $e) {
            error_log("Get next approver error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get complete workflow chain for display
     *
     * @param int $requestId Request ID
     * @return array Workflow chain with steps and statuses
     */
    public function getWorkflowChain($requestId) {
        try {
            $request = $this->requestModel->find($requestId);
            if (!$request) {
                return [];
            }

            $maker = $this->getUserRole($request['requested_by']);
            if (!$maker) {
                return [];
            }

            $makerLevel = $maker['hierarchy_level'];
            $chain = [];

            // Step 1: Submitted (always present)
            $chain[] = [
                'step' => 'Submitted',
                'role' => $maker['role_name'],
                'user' => $maker['full_name'],
                'completed' => in_array($request['status'], ['Submitted', 'Verified', 'Authorized', 'Approved', 'Procured', 'Fulfilled']),
                'timestamp' => $request['created_at']
            ];

            // Step 2: Verified (if maker level <= 2)
            if ($makerLevel <= 2) {
                $verifierRole = ($makerLevel === 1) ? 'Site Inventory Clerk' : 'Project Manager';
                $chain[] = [
                    'step' => 'Verified',
                    'role' => $verifierRole,
                    'user' => $request['verified_by'] ? $this->getUserRole($request['verified_by'])['full_name'] : null,
                    'completed' => in_array($request['status'], ['Verified', 'Authorized', 'Approved', 'Procured', 'Fulfilled']),
                    'timestamp' => $request['verified_at']
                ];
            }

            // Step 3: Authorized (only if maker level === 1)
            if ($makerLevel === 1) {
                $chain[] = [
                    'step' => 'Authorized',
                    'role' => 'Project Manager',
                    'user' => $request['authorized_by'] ? $this->getUserRole($request['authorized_by'])['full_name'] : null,
                    'completed' => in_array($request['status'], ['Authorized', 'Approved', 'Procured', 'Fulfilled']),
                    'timestamp' => $request['authorized_at']
                ];
            }

            // Step 4: Approved (always present)
            $chain[] = [
                'step' => 'Approved',
                'role' => 'Finance Director',
                'user' => $request['approved_by'] ? $this->getUserRole($request['approved_by'])['full_name'] : null,
                'completed' => in_array($request['status'], ['Approved', 'Procured', 'Fulfilled']),
                'timestamp' => $request['approved_at']
            ];

            // Step 5: Procured (if linked to PO)
            if ($request['procurement_id']) {
                $chain[] = [
                    'step' => 'Procured',
                    'role' => 'Procurement Officer',
                    'user' => null,
                    'completed' => in_array($request['status'], ['Procured', 'Fulfilled']),
                    'timestamp' => null
                ];
            }

            // Step 6: Fulfilled (final step)
            $chain[] = [
                'step' => 'Fulfilled',
                'role' => 'Warehouseman',
                'user' => null,
                'completed' => $request['status'] === 'Fulfilled',
                'timestamp' => null
            ];

            return $chain;

        } catch (Exception $e) {
            error_log("Get workflow chain error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if user can verify this request
     *
     * @param int $requestId Request ID
     * @param int $userId User ID
     * @return bool True if user can verify
     */
    public function canUserVerify($requestId, $userId) {
        try {
            $request = $this->requestModel->find($requestId);
            if (!$request || $request['status'] !== 'Submitted') {
                return false;
            }

            $nextApprover = $this->getNextApprover($requestId);
            if (!$nextApprover || $nextApprover['action'] !== 'verify') {
                return false;
            }

            $user = $this->getUserRole($userId);
            if (!$user) {
                return false;
            }

            return $user['role_name'] === $nextApprover['role'];

        } catch (Exception $e) {
            error_log("Can user verify error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user can authorize this request
     *
     * @param int $requestId Request ID
     * @param int $userId User ID
     * @return bool True if user can authorize
     */
    public function canUserAuthorize($requestId, $userId) {
        try {
            $request = $this->requestModel->find($requestId);
            if (!$request || $request['status'] !== 'Verified') {
                return false;
            }

            $nextApprover = $this->getNextApprover($requestId);
            if (!$nextApprover || $nextApprover['action'] !== 'authorize') {
                return false;
            }

            $user = $this->getUserRole($userId);
            if (!$user) {
                return false;
            }

            return $user['role_name'] === $nextApprover['role'];

        } catch (Exception $e) {
            error_log("Can user authorize error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user can approve this request
     *
     * @param int $requestId Request ID
     * @param int $userId User ID
     * @return bool True if user can approve
     */
    public function canUserApprove($requestId, $userId) {
        try {
            $request = $this->requestModel->find($requestId);
            if (!$request || !in_array($request['status'], ['Submitted', 'Verified', 'Authorized'])) {
                return false;
            }

            $nextApprover = $this->getNextApprover($requestId);
            if (!$nextApprover || $nextApprover['action'] !== 'approve') {
                return false;
            }

            $user = $this->getUserRole($userId);
            if (!$user) {
                return false;
            }

            return $user['role_name'] === $nextApprover['role'] || $user['role_name'] === 'Asset Director';

        } catch (Exception $e) {
            error_log("Can user approve error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify request (Verifier step in MVA workflow)
     *
     * @param int $requestId Request ID
     * @param int $verifiedBy User ID of verifier
     * @param string|null $notes Optional verification notes
     * @return array Response with success status
     */
    public function verifyRequest($requestId, $verifiedBy, $notes = null) {
        try {
            $this->db->beginTransaction();

            $request = $this->requestModel->find($requestId);
            if (!$request) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Request');
            }

            if ($request['status'] !== 'Submitted') {
                $this->db->rollBack();
                return ResponseFormatter::invalidStatus($request['status'], 'Submitted');
            }

            // Verify user has permission
            if (!$this->canUserVerify($requestId, $verifiedBy)) {
                $this->db->rollBack();
                return ResponseFormatter::permissionDenied('You are not authorized to verify this request');
            }

            // Update request status
            $updated = $this->requestModel->update($requestId, [
                'status' => 'Verified',
                'verified_by' => $verifiedBy,
                'verified_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!$updated) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to verify request');
            }

            // Log activity
            $this->logActivity(
                'verify_request',
                "Request #{$requestId} verified",
                'requests',
                $requestId
            );

            // Log to request_logs
            $this->requestModel->logRequestActivity(
                $requestId,
                'request_verified',
                'Submitted',
                'Verified',
                $notes,
                $verifiedBy
            );

            $this->db->commit();
            return ResponseFormatter::success('Request verified successfully');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Request verification error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to verify request');
        }
    }

    /**
     * Authorize request (Authorizer step in MVA workflow)
     *
     * @param int $requestId Request ID
     * @param int $authorizedBy User ID of authorizer
     * @param string|null $notes Optional authorization notes
     * @return array Response with success status
     */
    public function authorizeRequest($requestId, $authorizedBy, $notes = null) {
        try {
            $this->db->beginTransaction();

            $request = $this->requestModel->find($requestId);
            if (!$request) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Request');
            }

            if ($request['status'] !== 'Verified') {
                $this->db->rollBack();
                return ResponseFormatter::invalidStatus($request['status'], 'Verified');
            }

            // Verify user has permission
            if (!$this->canUserAuthorize($requestId, $authorizedBy)) {
                $this->db->rollBack();
                return ResponseFormatter::permissionDenied('You are not authorized to authorize this request');
            }

            // Update request status
            $updated = $this->requestModel->update($requestId, [
                'status' => 'Authorized',
                'authorized_by' => $authorizedBy,
                'authorized_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!$updated) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to authorize request');
            }

            // Log activity
            $this->logActivity(
                'authorize_request',
                "Request #{$requestId} authorized",
                'requests',
                $requestId
            );

            // Log to request_logs
            $this->requestModel->logRequestActivity(
                $requestId,
                'request_authorized',
                'Verified',
                'Authorized',
                $notes,
                $authorizedBy
            );

            $this->db->commit();
            return ResponseFormatter::success('Request authorized successfully');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Request authorization error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to authorize request');
        }
    }

    /**
     * Approve request (Final approval step in MVA workflow)
     *
     * @param int $requestId Request ID
     * @param int $approvedBy User ID of approver
     * @param string|null $notes Optional approval notes
     * @return array Response with success status
     */
    public function approveRequest($requestId, $approvedBy, $notes = null) {
        try {
            $this->db->beginTransaction();

            $request = $this->requestModel->find($requestId);
            if (!$request) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Request');
            }

            $validStatuses = ['Submitted', 'Verified', 'Authorized'];
            if (!in_array($request['status'], $validStatuses)) {
                $this->db->rollBack();
                return ResponseFormatter::invalidStatus(
                    $request['status'],
                    implode(', ', $validStatuses)
                );
            }

            // Verify user has permission
            if (!$this->canUserApprove($requestId, $approvedBy)) {
                $this->db->rollBack();
                return ResponseFormatter::permissionDenied('You are not authorized to approve this request');
            }

            $oldStatus = $request['status'];

            // Update request status
            $updated = $this->requestModel->update($requestId, [
                'status' => 'Approved',
                'approved_by' => $approvedBy,
                'approved_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!$updated) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to approve request');
            }

            // Log activity
            $this->logActivity(
                'approve_request',
                "Request #{$requestId} approved",
                'requests',
                $requestId
            );

            // Log to request_logs
            $this->requestModel->logRequestActivity(
                $requestId,
                'request_approved',
                $oldStatus,
                'Approved',
                $notes,
                $approvedBy
            );

            $this->db->commit();
            return ResponseFormatter::success('Request approved successfully');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Request approval error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to approve request');
        }
    }

    /**
     * Decline request at any approval stage
     *
     * @param int $requestId Request ID
     * @param int $declinedBy User ID of decliner
     * @param string $reason Reason for declining
     * @return array Response with success status
     */
    public function declineRequest($requestId, $declinedBy, $reason) {
        try {
            $this->db->beginTransaction();

            $request = $this->requestModel->find($requestId);
            if (!$request) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Request');
            }

            $validStatuses = ['Submitted', 'Verified', 'Authorized'];
            if (!in_array($request['status'], $validStatuses)) {
                $this->db->rollBack();
                return ResponseFormatter::error('Request cannot be declined at this stage');
            }

            if (empty($reason)) {
                $this->db->rollBack();
                return ResponseFormatter::error('Decline reason is required');
            }

            $oldStatus = $request['status'];

            // Update request status
            $updated = $this->requestModel->update($requestId, [
                'status' => 'Declined',
                'declined_by' => $declinedBy,
                'declined_at' => date('Y-m-d H:i:s'),
                'decline_reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!$updated) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to decline request');
            }

            // Log activity
            $this->logActivity(
                'decline_request',
                "Request #{$requestId} declined: {$reason}",
                'requests',
                $requestId
            );

            // Log to request_logs
            $this->requestModel->logRequestActivity(
                $requestId,
                'request_declined',
                $oldStatus,
                'Declined',
                $reason,
                $declinedBy
            );

            $this->db->commit();
            return ResponseFormatter::success('Request declined successfully');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Request decline error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to decline request');
        }
    }

    /**
     * Resubmit declined request (reset to Draft for editing)
     *
     * @param int $requestId Request ID
     * @param int $userId User ID (must be original maker)
     * @return array Response with success status
     */
    public function resubmitRequest($requestId, $userId) {
        try {
            $this->db->beginTransaction();

            $request = $this->requestModel->find($requestId);
            if (!$request) {
                $this->db->rollBack();
                return ResponseFormatter::notFound('Request');
            }

            if ($request['status'] !== 'Declined') {
                $this->db->rollBack();
                return ResponseFormatter::error('Only declined requests can be resubmitted');
            }

            // Only original maker can resubmit
            if ($request['requested_by'] != $userId) {
                $this->db->rollBack();
                return ResponseFormatter::permissionDenied('Only the original requester can resubmit');
            }

            // Reset to Draft for editing
            $updated = $this->requestModel->update($requestId, [
                'status' => 'Draft',
                'declined_by' => null,
                'declined_at' => null,
                'decline_reason' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!$updated) {
                $this->db->rollBack();
                return ResponseFormatter::error('Failed to resubmit request');
            }

            // Log activity
            $this->logActivity(
                'resubmit_request',
                "Request #{$requestId} reset to draft for resubmission",
                'requests',
                $requestId
            );

            // Log to request_logs
            $this->requestModel->logRequestActivity(
                $requestId,
                'request_resubmitted',
                'Declined',
                'Draft',
                'Request reset to draft for editing and resubmission',
                $userId
            );

            $this->db->commit();
            return ResponseFormatter::success('Request reset to draft. You can now edit and resubmit.');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Request resubmit error: " . $e->getMessage());
            return ResponseFormatter::error('Failed to resubmit request');
        }
    }
}
?>
