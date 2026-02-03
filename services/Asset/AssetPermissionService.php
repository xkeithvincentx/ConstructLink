<?php
/**
 * ConstructLink™ Asset Permission Service
 *
 * Handles all permission and authorization logic for asset operations.
 * Extracted from AssetController as part of Phase 2 refactoring.
 * Follows SOLID principles and 2025 industry standards.
 *
 * Responsibilities:
 * - Workflow-based edit permissions
 * - Role-based access control for assets
 * - Permission validation for asset operations
 * - Maker ownership validation for legacy assets
 *
 * @package ConstructLink
 * @subpackage Services\Asset
 * @version 1.0.0
 */

class AssetPermissionService {

    /**
     * Check if user can edit asset based on workflow status and role
     *
     * Business Rules:
     * - System Admin and Asset Director can always edit
     * - Warehouseman can only edit their own legacy assets in draft/pending stages
     * - Site Inventory Clerk can edit ANY asset during verification (to correct errors)
     * - Site Inventory Clerk can edit during authorization (before final approval)
     * - Project Managers can edit during authorization review
     * - Procurement Officers can edit during draft/pending/authorization stages
     * - Once approved, only System Admin and Asset Director can edit
     *
     * @param string $userRole User's role name
     * @param int $userId User's ID
     * @param array $asset Asset data including workflow_status, made_by, inventory_source
     * @return array ['allowed' => bool, 'message' => string, 'correction_role' => bool]
     */
    public function canEditAsset(string $userRole, int $userId, array $asset): array {
        $workflowStatus = $asset['workflow_status'] ?? '';
        $assetMakerId = $asset['made_by'] ?? 0;
        $assetSource = $asset['inventory_source'] ?? 'manual';

        // System Admin and Asset Director can always edit
        if (in_array($userRole, ['System Admin', 'Asset Director'])) {
            return ['allowed' => true, 'message' => ''];
        }

        // Check permissions based on workflow status
        switch ($workflowStatus) {
            case 'draft':
            case 'pending_verification':
                // Warehouseman can only edit their own legacy assets in draft/pending stages
                if ($userRole === 'Warehouseman' && $assetSource === 'legacy') {
                    if ($assetMakerId == $userId) {
                        return ['allowed' => true, 'message' => ''];
                    } else {
                        return [
                            'allowed' => false,
                            'message' => 'You can only edit legacy items that you created.'
                        ];
                    }
                }

                // Site Inventory Clerks can edit ANY asset during verification (to correct Warehouseman errors)
                if ($userRole === 'Site Inventory Clerk') {
                    return [
                        'allowed' => true,
                        'message' => '',
                        'correction_role' => true // Flag that this is a correction by verifier
                    ];
                }

                // Procurement Officers can edit during these stages
                if ($userRole === 'Procurement Officer') {
                    return ['allowed' => true, 'message' => ''];
                }

                break;

            case 'pending_authorization':
                // Site Inventory Clerk can still correct during authorization stage (before final approval)
                if ($userRole === 'Site Inventory Clerk') {
                    return [
                        'allowed' => true,
                        'message' => '',
                        'correction_role' => true
                    ];
                }

                // Project Managers can edit during authorization review
                if ($userRole === 'Project Manager') {
                    return [
                        'allowed' => true,
                        'message' => '',
                        'correction_role' => true
                    ];
                }

                // Procurement Officers can still edit
                if ($userRole === 'Procurement Officer') {
                    return ['allowed' => true, 'message' => ''];
                }

                break;

            case 'approved':
            case 'authorized':
                // Only Asset Director and System Admin can edit approved assets
                return [
                    'allowed' => false,
                    'message' => 'This item has been approved and cannot be edited. Please contact the Asset Director if changes are needed, or submit a change request through the system.'
                ];

            default:
                // Unknown status - be restrictive
                return [
                    'allowed' => false,
                    'message' => 'Item has unknown status. Please contact system administrator.'
                ];
        }

        // Default deny for roles not explicitly allowed
        return [
            'allowed' => false,
            'message' => 'You do not have permission to edit this asset at its current stage in the workflow.'
        ];
    }

    /**
     * Check if user owns the asset (is the maker)
     *
     * @param int $userId User ID
     * @param array $asset Asset data
     * @return bool True if user is the maker
     */
    public function isAssetOwner(int $userId, array $asset): bool {
        $assetMakerId = $asset['made_by'] ?? 0;
        return $assetMakerId == $userId;
    }

    /**
     * Check if asset is in editable workflow state
     *
     * @param array $asset Asset data
     * @return bool True if workflow allows editing
     */
    public function isEditableWorkflowState(array $asset): bool {
        $workflowStatus = $asset['workflow_status'] ?? '';
        return in_array($workflowStatus, ['draft', 'pending_verification', 'pending_authorization']);
    }

    /**
     * Get permission error message for display
     *
     * @param string $userRole User's role
     * @param array $asset Asset data
     * @return string User-friendly error message
     */
    public function getPermissionDeniedMessage(string $userRole, array $asset): string {
        $workflowStatus = $asset['workflow_status'] ?? '';

        if ($workflowStatus === 'approved' || $workflowStatus === 'authorized') {
            return 'This item has been approved and cannot be edited. Please contact the Asset Director if changes are needed.';
        }

        if ($asset['inventory_source'] === 'legacy' && $userRole === 'Warehouseman') {
            return 'You can only edit legacy items that you created.';
        }

        return 'You do not have permission to edit this asset at its current stage in the workflow.';
    }
}
