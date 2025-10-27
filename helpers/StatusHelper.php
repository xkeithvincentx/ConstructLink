<?php
/**
 * ConstructLink™ Status Helper
 * Centralized status calculation logic for borrowed tools
 * Developed by: Ranoa Digital Solutions
 */

class StatusHelper {
    /**
     * Calculate current status for a borrowed tool or batch
     *
     * This method provides centralized status calculation logic to prevent
     * code duplication and ensure consistent status determination across
     * the application.
     *
     * Status Logic:
     * - If fully returned (quantity_returned >= quantity): "Returned" with label type "default"
     * - If partially returned (0 < quantity_returned < quantity): "Partially Returned" with label type "info"
     * - If not returned at all (quantity_returned == 0): Use the base status
     *
     * @param array $batch The batch data with status field
     * @param array|null $borrowedTool Optional individual borrowed tool data with quantity fields
     * @return array ['status' => string, 'label_type' => string]
     */
    public static function calculateCurrentStatus($batch, $borrowedTool = null) {
        // If borrowed tool data provided, calculate item-level status
        if ($borrowedTool !== null && isset($borrowedTool['quantity'], $borrowedTool['quantity_returned'])) {
            $quantity = (int)$borrowedTool['quantity'];
            $quantityReturned = (int)$borrowedTool['quantity_returned'];

            if ($quantityReturned >= $quantity && $quantityReturned > 0) {
                // Fully returned
                return [
                    'status' => 'Returned',
                    'label_type' => 'default'
                ];
            } elseif ($quantityReturned > 0 && $quantityReturned < $quantity) {
                // Partially returned
                return [
                    'status' => 'Partially Returned',
                    'label_type' => 'info'
                ];
            }
        }

        // Use batch status or borrowed tool status as fallback
        $baseStatus = $borrowedTool['status'] ?? $batch['status'] ?? 'Unknown';

        // Determine label type based on status
        $labelType = self::getLabelTypeForStatus($baseStatus);

        return [
            'status' => $baseStatus,
            'label_type' => $labelType
        ];
    }

    /**
     * Get Bootstrap label type for a given status
     *
     * @param string $status The status string
     * @return string Bootstrap label type (success, warning, danger, info, default)
     */
    public static function getLabelTypeForStatus($status) {
        $statusMap = [
            // MVA Workflow Statuses
            'Pending Verification' => 'warning',
            'Pending Approval' => 'warning',
            'Approved' => 'info',

            // Active States
            'Released' => 'primary',
            'Borrowed' => 'primary',

            // Return States
            'Partially Returned' => 'info',
            'Returned' => 'default',

            // Terminal States
            'Canceled' => 'default',
            'Rejected' => 'danger',

            // Overdue
            'Overdue' => 'danger',
        ];

        return $statusMap[$status] ?? 'default';
    }

    /**
     * Check if a batch or borrowed tool is overdue
     *
     * @param array $data Batch or borrowed tool data with expected_return and status
     * @return bool True if overdue, false otherwise
     */
    public static function isOverdue($data) {
        // Only items that are still out can be overdue
        $activeStatuses = ['Released', 'Borrowed', 'Partially Returned', 'Approved'];

        if (!in_array($data['status'] ?? '', $activeStatuses)) {
            return false;
        }

        $expectedReturn = $data['expected_return'] ?? null;
        if (!$expectedReturn) {
            return false;
        }

        return strtotime($expectedReturn) < strtotime(date('Y-m-d'));
    }

    /**
     * Get human-readable status description
     *
     * @param string $status The status code
     * @return string Human-readable description
     */
    public static function getStatusDescription($status) {
        $descriptions = [
            'Pending Verification' => 'Awaiting verification by warehouse staff',
            'Pending Approval' => 'Awaiting approval by authorized personnel',
            'Approved' => 'Approved and ready for release',
            'Released' => 'Released to borrower',
            'Borrowed' => 'Currently borrowed',
            'Partially Returned' => 'Some items have been returned',
            'Returned' => 'All items have been returned',
            'Canceled' => 'Request has been canceled',
            'Rejected' => 'Request has been rejected',
            'Overdue' => 'Past expected return date',
        ];

        return $descriptions[$status] ?? 'Unknown status';
    }

    /**
     * Check if a status allows for specific actions
     *
     * @param string $status Current status
     * @param string $action Action to check (verify, approve, release, return, cancel, extend)
     * @return bool True if action is allowed, false otherwise
     */
    public static function canPerformAction($status, $action) {
        $allowedActions = [
            'Pending Verification' => ['verify', 'cancel'],
            'Pending Approval' => ['approve', 'cancel'],
            'Approved' => ['release', 'cancel'],
            'Released' => ['return', 'extend'],
            'Borrowed' => ['return', 'extend'],
            'Partially Returned' => ['return', 'extend'],
        ];

        $allowed = $allowedActions[$status] ?? [];
        return in_array($action, $allowed);
    }
}
?>
