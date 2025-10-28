<?php
/**
 * Workflow Status Constants
 *
 * Centralized constants for all workflow status values used throughout the application.
 * Eliminates 27+ hardcoded status values found in role-specific dashboards.
 *
 * @package ConstructLink
 * @subpackage Constants
 * @version 2.0
 * @since 2025-10-28
 */

class WorkflowStatus
{
    /* ==========================================================================
       Request Status Values
       ========================================================================== */

    const REQUEST_DRAFT = 'Draft';
    const REQUEST_SUBMITTED = 'Submitted';
    const REQUEST_REVIEWED = 'Reviewed';
    const REQUEST_APPROVED = 'Approved';
    const REQUEST_REJECTED = 'Rejected';
    const REQUEST_CANCELLED = 'Cancelled';
    const REQUEST_COMPLETED = 'Completed';

    /* ==========================================================================
       Procurement Order Status Values
       ========================================================================== */

    const PROCUREMENT_DRAFT = 'Draft';
    const PROCUREMENT_PENDING = 'Pending';
    const PROCUREMENT_REVIEWED = 'Reviewed';
    const PROCUREMENT_APPROVED = 'Approved';
    const PROCUREMENT_REJECTED = 'Rejected';
    const PROCUREMENT_ORDERED = 'Ordered';
    const PROCUREMENT_DELIVERED = 'Delivered';
    const PROCUREMENT_CANCELLED = 'Cancelled';

    /* ==========================================================================
       Delivery Status Values
       ========================================================================== */

    const DELIVERY_PENDING = 'Pending';
    const DELIVERY_SCHEDULED = 'Scheduled';
    const DELIVERY_IN_TRANSIT = 'In Transit';
    const DELIVERY_DELIVERED = 'Delivered';
    const DELIVERY_FAILED = 'Failed';
    const DELIVERY_CANCELLED = 'Cancelled';

    /* ==========================================================================
       Transfer Status Values
       ========================================================================== */

    const TRANSFER_DRAFT = 'Draft';
    const TRANSFER_PENDING_APPROVAL = 'Pending Approval';
    const TRANSFER_APPROVED = 'Approved';
    const TRANSFER_PENDING_VERIFICATION = 'Pending Verification';
    const TRANSFER_VERIFIED = 'Verified';
    const TRANSFER_IN_TRANSIT = 'In Transit';
    const TRANSFER_COMPLETED = 'Completed';
    const TRANSFER_REJECTED = 'Rejected';
    const TRANSFER_CANCELLED = 'Cancelled';

    /* ==========================================================================
       Withdrawal Status Values
       ========================================================================== */

    const WITHDRAWAL_DRAFT = 'Draft';
    const WITHDRAWAL_PENDING_VERIFICATION = 'Pending Verification';
    const WITHDRAWAL_VERIFIED = 'Verified';
    const WITHDRAWAL_PENDING_APPROVAL = 'Pending Approval';
    const WITHDRAWAL_APPROVED = 'Approved';
    const WITHDRAWAL_COMPLETED = 'Completed';
    const WITHDRAWAL_REJECTED = 'Rejected';
    const WITHDRAWAL_CANCELLED = 'Cancelled';

    /* ==========================================================================
       Borrowed Tools Status Values
       ========================================================================== */

    const BORROWED_TOOLS_DRAFT = 'Draft';
    const BORROWED_TOOLS_PENDING_VERIFICATION = 'Pending Verification';
    const BORROWED_TOOLS_VERIFIED = 'Verified';
    const BORROWED_TOOLS_PENDING_APPROVAL = 'Pending Approval';
    const BORROWED_TOOLS_APPROVED = 'Approved';
    const BORROWED_TOOLS_ACTIVE = 'Active';
    const BORROWED_TOOLS_PENDING_RETURN = 'Pending Return';
    const BORROWED_TOOLS_RETURNED = 'Returned';
    const BORROWED_TOOLS_OVERDUE = 'Overdue';
    const BORROWED_TOOLS_LOST = 'Lost';
    const BORROWED_TOOLS_DAMAGED = 'Damaged';
    const BORROWED_TOOLS_REJECTED = 'Rejected';
    const BORROWED_TOOLS_CANCELLED = 'Cancelled';

    /* ==========================================================================
       Incident Status Values
       ========================================================================== */

    const INCIDENT_DRAFT = 'Draft';
    const INCIDENT_PENDING_VERIFICATION = 'Pending Verification';
    const INCIDENT_VERIFIED = 'Verified';
    const INCIDENT_PENDING_AUTHORIZATION = 'Pending Authorization';
    const INCIDENT_AUTHORIZED = 'Authorized';
    const INCIDENT_IN_PROGRESS = 'In Progress';
    const INCIDENT_RESOLVED = 'Resolved';
    const INCIDENT_CLOSED = 'Closed';
    const INCIDENT_REJECTED = 'Rejected';

    /* ==========================================================================
       Maintenance Status Values
       ========================================================================== */

    const MAINTENANCE_SCHEDULED = 'scheduled';
    const MAINTENANCE_IN_PROGRESS = 'in_progress';
    const MAINTENANCE_COMPLETED = 'completed';
    const MAINTENANCE_CANCELLED = 'cancelled';
    const MAINTENANCE_FAILED = 'failed';

    /* ==========================================================================
       Asset Status Values
       ========================================================================== */

    const ASSET_AVAILABLE = 'Available';
    const ASSET_IN_USE = 'In Use';
    const ASSET_MAINTENANCE = 'Maintenance';
    const ASSET_RETIRED = 'Retired';
    const ASSET_DISPOSED = 'Disposed';
    const ASSET_LOST = 'Lost';
    const ASSET_DAMAGED = 'Damaged';

    /**
     * Build URL route with status parameter
     *
     * @param string $module Module/route name (e.g., 'requests', 'procurement-orders')
     * @param string $status Status constant value
     * @param array $additionalParams Additional URL parameters
     * @return string Properly encoded route string
     */
    public static function buildRoute($module, $status, $additionalParams = [])
    {
        $params = array_merge(['status' => $status], $additionalParams);
        $queryString = http_build_query($params);
        return $module . '?' . $queryString;
    }

    /**
     * Get all status values for a specific module
     *
     * @param string $module Module name (request, procurement, transfer, etc.)
     * @return array Array of status constants
     */
    public static function getAllStatuses($module)
    {
        $moduleStatuses = [
            'request' => [
                self::REQUEST_DRAFT,
                self::REQUEST_SUBMITTED,
                self::REQUEST_REVIEWED,
                self::REQUEST_APPROVED,
                self::REQUEST_REJECTED,
                self::REQUEST_CANCELLED,
                self::REQUEST_COMPLETED
            ],
            'procurement' => [
                self::PROCUREMENT_DRAFT,
                self::PROCUREMENT_PENDING,
                self::PROCUREMENT_REVIEWED,
                self::PROCUREMENT_APPROVED,
                self::PROCUREMENT_REJECTED,
                self::PROCUREMENT_ORDERED,
                self::PROCUREMENT_DELIVERED,
                self::PROCUREMENT_CANCELLED
            ],
            'transfer' => [
                self::TRANSFER_DRAFT,
                self::TRANSFER_PENDING_APPROVAL,
                self::TRANSFER_APPROVED,
                self::TRANSFER_PENDING_VERIFICATION,
                self::TRANSFER_VERIFIED,
                self::TRANSFER_IN_TRANSIT,
                self::TRANSFER_COMPLETED,
                self::TRANSFER_REJECTED,
                self::TRANSFER_CANCELLED
            ],
            'withdrawal' => [
                self::WITHDRAWAL_DRAFT,
                self::WITHDRAWAL_PENDING_VERIFICATION,
                self::WITHDRAWAL_VERIFIED,
                self::WITHDRAWAL_PENDING_APPROVAL,
                self::WITHDRAWAL_APPROVED,
                self::WITHDRAWAL_COMPLETED,
                self::WITHDRAWAL_REJECTED,
                self::WITHDRAWAL_CANCELLED
            ],
            'borrowed_tools' => [
                self::BORROWED_TOOLS_DRAFT,
                self::BORROWED_TOOLS_PENDING_VERIFICATION,
                self::BORROWED_TOOLS_VERIFIED,
                self::BORROWED_TOOLS_PENDING_APPROVAL,
                self::BORROWED_TOOLS_APPROVED,
                self::BORROWED_TOOLS_ACTIVE,
                self::BORROWED_TOOLS_PENDING_RETURN,
                self::BORROWED_TOOLS_RETURNED,
                self::BORROWED_TOOLS_OVERDUE,
                self::BORROWED_TOOLS_LOST,
                self::BORROWED_TOOLS_DAMAGED,
                self::BORROWED_TOOLS_REJECTED,
                self::BORROWED_TOOLS_CANCELLED
            ],
            'incident' => [
                self::INCIDENT_DRAFT,
                self::INCIDENT_PENDING_VERIFICATION,
                self::INCIDENT_VERIFIED,
                self::INCIDENT_PENDING_AUTHORIZATION,
                self::INCIDENT_AUTHORIZED,
                self::INCIDENT_IN_PROGRESS,
                self::INCIDENT_RESOLVED,
                self::INCIDENT_CLOSED,
                self::INCIDENT_REJECTED
            ],
            'maintenance' => [
                self::MAINTENANCE_SCHEDULED,
                self::MAINTENANCE_IN_PROGRESS,
                self::MAINTENANCE_COMPLETED,
                self::MAINTENANCE_CANCELLED,
                self::MAINTENANCE_FAILED
            ],
            'asset' => [
                self::ASSET_AVAILABLE,
                self::ASSET_IN_USE,
                self::ASSET_MAINTENANCE,
                self::ASSET_RETIRED,
                self::ASSET_DISPOSED,
                self::ASSET_LOST,
                self::ASSET_DAMAGED
            ]
        ];

        return $moduleStatuses[$module] ?? [];
    }

    /**
     * Get badge color for a given status
     *
     * @param string $status Status value
     * @return string Bootstrap color class (primary, success, warning, danger, etc.)
     */
    public static function getStatusColor($status)
    {
        $colorMap = [
            // Positive/Completed statuses
            self::REQUEST_COMPLETED => 'success',
            self::REQUEST_APPROVED => 'success',
            self::PROCUREMENT_DELIVERED => 'success',
            self::TRANSFER_COMPLETED => 'success',
            self::WITHDRAWAL_COMPLETED => 'success',
            self::BORROWED_TOOLS_RETURNED => 'success',
            self::INCIDENT_RESOLVED => 'success',
            self::INCIDENT_CLOSED => 'success',
            self::MAINTENANCE_COMPLETED => 'success',
            self::ASSET_AVAILABLE => 'success',

            // Warning/Pending statuses
            self::REQUEST_SUBMITTED => 'warning',
            self::REQUEST_REVIEWED => 'warning',
            self::PROCUREMENT_PENDING => 'warning',
            self::PROCUREMENT_REVIEWED => 'warning',
            self::TRANSFER_PENDING_APPROVAL => 'warning',
            self::TRANSFER_PENDING_VERIFICATION => 'warning',
            self::WITHDRAWAL_PENDING_VERIFICATION => 'warning',
            self::WITHDRAWAL_PENDING_APPROVAL => 'warning',
            self::BORROWED_TOOLS_PENDING_VERIFICATION => 'warning',
            self::BORROWED_TOOLS_PENDING_APPROVAL => 'warning',
            self::BORROWED_TOOLS_PENDING_RETURN => 'warning',
            self::INCIDENT_PENDING_VERIFICATION => 'warning',
            self::INCIDENT_PENDING_AUTHORIZATION => 'warning',
            self::MAINTENANCE_SCHEDULED => 'warning',
            self::ASSET_IN_USE => 'warning',

            // Info/In Progress statuses
            self::PROCUREMENT_ORDERED => 'info',
            self::TRANSFER_IN_TRANSIT => 'info',
            self::TRANSFER_APPROVED => 'info',
            self::WITHDRAWAL_APPROVED => 'info',
            self::BORROWED_TOOLS_ACTIVE => 'info',
            self::INCIDENT_IN_PROGRESS => 'info',
            self::MAINTENANCE_IN_PROGRESS => 'info',
            self::ASSET_MAINTENANCE => 'info',

            // Danger/Error statuses
            self::REQUEST_REJECTED => 'danger',
            self::REQUEST_CANCELLED => 'danger',
            self::PROCUREMENT_REJECTED => 'danger',
            self::PROCUREMENT_CANCELLED => 'danger',
            self::TRANSFER_REJECTED => 'danger',
            self::TRANSFER_CANCELLED => 'danger',
            self::WITHDRAWAL_REJECTED => 'danger',
            self::WITHDRAWAL_CANCELLED => 'danger',
            self::BORROWED_TOOLS_OVERDUE => 'danger',
            self::BORROWED_TOOLS_LOST => 'danger',
            self::BORROWED_TOOLS_DAMAGED => 'danger',
            self::BORROWED_TOOLS_REJECTED => 'danger',
            self::INCIDENT_REJECTED => 'danger',
            self::MAINTENANCE_FAILED => 'danger',
            self::ASSET_LOST => 'danger',
            self::ASSET_DAMAGED => 'danger',

            // Secondary/Draft statuses
            self::REQUEST_DRAFT => 'secondary',
            self::PROCUREMENT_DRAFT => 'secondary',
            self::TRANSFER_DRAFT => 'secondary',
            self::WITHDRAWAL_DRAFT => 'secondary',
            self::BORROWED_TOOLS_DRAFT => 'secondary',
            self::INCIDENT_DRAFT => 'secondary',
            self::ASSET_RETIRED => 'secondary',
            self::ASSET_DISPOSED => 'secondary'
        ];

        return $colorMap[$status] ?? 'primary';
    }
}
