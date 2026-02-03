<?php
/**
 * RequestFilterService - Request Filtering Logic
 *
 * Handles role-based filtering and filter application for requests.
 * Single Responsibility: Request filtering business logic.
 *
 * @package ConstructLink\Services\Request
 */

class RequestFilterService {

    /**
     * Apply role-based filters to request queries
     *
     * Modifies filters array based on user role and permissions.
     *
     * @param string $userRole User's role name
     * @param int $userId User's ID
     * @param array $baseFilters Existing filters to apply
     * @return array Modified filters array
     */
    public function applyRoleBasedFilters($userRole, $userId, $baseFilters = []) {
        $filters = $baseFilters;

        if ($userRole === 'System Admin') {
            // System admins can see all requests
            return $filters;
        }

        switch ($userRole) {
            case 'Project Manager':
                // Project managers can only see requests from their projects
                $projectModel = new ProjectModel();
                $userProjects = $projectModel->getProjectsByManager($userId);

                if (!empty($userProjects)) {
                    $projectIds = array_column($userProjects, 'id');
                    $filters['project_ids'] = $projectIds;
                } else {
                    // No projects assigned - show nothing
                    $filters['project_id'] = -1;
                }
                break;

            case 'Site Inventory Clerk':
                // Site clerks can only see their own requests
                $filters['requested_by'] = $userId;
                break;

            case 'Procurement Officer':
                // Procurement officers can see all requests but with procurement-specific filtering
                // This is handled by additional filters passed in baseFilters
                break;

            case 'Finance Director':
                // Finance directors can see all requests
                break;

            case 'Asset Director':
                // Asset directors can see all requests
                break;

            case 'Warehouseman':
                // Warehousemen can see requests with procurement orders
                // This requires a different filtering approach in the model
                break;

            default:
                // For any other role, only show their own requests
                $filters['requested_by'] = $userId;
                break;
        }

        return $filters;
    }

    /**
     * Build filters array from GET parameters
     *
     * @param array $getParams GET parameters ($_GET)
     * @return array Sanitized filters array
     */
    public function buildFiltersFromRequest($getParams) {
        $filters = [];

        if (!empty($getParams['status'])) {
            $filters['status'] = Validator::sanitize($getParams['status']);
        }

        if (!empty($getParams['request_type'])) {
            $filters['request_type'] = Validator::sanitize($getParams['request_type']);
        }

        if (!empty($getParams['project_id'])) {
            $filters['project_id'] = (int)$getParams['project_id'];
        }

        if (!empty($getParams['urgency'])) {
            $filters['urgency'] = Validator::sanitize($getParams['urgency']);
        }

        if (!empty($getParams['date_from'])) {
            $filters['date_from'] = Validator::sanitize($getParams['date_from']);
        }

        if (!empty($getParams['date_to'])) {
            $filters['date_to'] = Validator::sanitize($getParams['date_to']);
        }

        if (!empty($getParams['search'])) {
            $filters['search'] = Validator::sanitize($getParams['search']);
        }

        // Delivery tracking filters
        if (!empty($getParams['delivery_status'])) {
            $filters['delivery_status'] = Validator::sanitize($getParams['delivery_status']);
        }

        if (!empty($getParams['procurement_status'])) {
            $filters['procurement_status'] = Validator::sanitize($getParams['procurement_status']);
        }

        if (!empty($getParams['overdue_delivery'])) {
            $filters['overdue_delivery'] = true;
        }

        if (!empty($getParams['has_discrepancy'])) {
            $filters['has_discrepancy'] = true;
        }

        if (!empty($getParams['requested_by'])) {
            $filters['requested_by'] = (int)$getParams['requested_by'];
        }

        return $filters;
    }

    /**
     * Validate filter values
     *
     * @param array $filters Filters to validate
     * @return array Validation result with errors if any
     */
    public function validateFilters($filters) {
        $errors = [];

        // Validate status
        if (!empty($filters['status'])) {
            $validStatuses = ['Draft', 'Submitted', 'Reviewed', 'Verified', 'Authorized', 'Forwarded', 'Approved', 'Declined', 'Procured'];
            if (!in_array($filters['status'], $validStatuses)) {
                $errors[] = 'Invalid status filter value';
            }
        }

        // Validate request_type
        if (!empty($filters['request_type'])) {
            $validTypes = ['Material', 'Tool', 'Equipment', 'Service', 'Petty Cash', 'Other', 'Restock'];
            if (!in_array($filters['request_type'], $validTypes)) {
                $errors[] = 'Invalid request type filter value';
            }
        }

        // Validate urgency
        if (!empty($filters['urgency'])) {
            $validUrgencies = ['Critical', 'Urgent', 'Normal'];
            if (!in_array($filters['urgency'], $validUrgencies)) {
                $errors[] = 'Invalid urgency filter value';
            }
        }

        // Validate dates
        if (!empty($filters['date_from'])) {
            if (!strtotime($filters['date_from'])) {
                $errors[] = 'Invalid date_from format';
            }
        }

        if (!empty($filters['date_to'])) {
            if (!strtotime($filters['date_to'])) {
                $errors[] = 'Invalid date_to format';
            }
        }

        // Validate date range
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            if (strtotime($filters['date_from']) > strtotime($filters['date_to'])) {
                $errors[] = 'date_from must be before date_to';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get filter options for display
     *
     * @return array Filter options arrays
     */
    public function getFilterOptions() {
        return [
            'statuses' => [
                'Draft' => 'Draft',
                'Submitted' => 'Submitted',
                'Reviewed' => 'Reviewed',
                'Verified' => 'Verified',
                'Authorized' => 'Authorized',
                'Forwarded' => 'Forwarded',
                'Approved' => 'Approved',
                'Declined' => 'Declined',
                'Procured' => 'Procured'
            ],
            'request_types' => [
                'Material' => 'Material',
                'Tool' => 'Tool',
                'Equipment' => 'Equipment',
                'Service' => 'Service',
                'Petty Cash' => 'Petty Cash',
                'Other' => 'Other',
                'Restock' => 'Restock'
            ],
            'urgencies' => [
                'Critical' => 'Critical',
                'Urgent' => 'Urgent',
                'Normal' => 'Normal'
            ],
            'delivery_statuses' => [
                'Pending' => 'Pending',
                'Scheduled' => 'Scheduled',
                'In Transit' => 'In Transit',
                'Delivered' => 'Delivered',
                'Received' => 'Received',
                'Partial' => 'Partial'
            ],
            'procurement_statuses' => [
                'Pending' => 'Pending',
                'Reviewed' => 'Reviewed',
                'Approved' => 'Approved',
                'Declined' => 'Declined'
            ]
        ];
    }

    /**
     * Check if user can access specific filters
     *
     * @param string $userRole User's role
     * @param string $filterType Type of filter
     * @return bool Whether user can use this filter
     */
    public function canUseFilter($userRole, $filterType) {
        $roleFilterAccess = [
            'System Admin' => ['all'],
            'Asset Director' => ['all'],
            'Finance Director' => ['all'],
            'Procurement Officer' => ['status', 'request_type', 'urgency', 'delivery_status', 'procurement_status'],
            'Project Manager' => ['status', 'request_type', 'urgency', 'delivery_status'],
            'Warehouseman' => ['delivery_status', 'urgency'],
            'Site Inventory Clerk' => ['status', 'request_type', 'urgency']
        ];

        if (!isset($roleFilterAccess[$userRole])) {
            return false;
        }

        $allowedFilters = $roleFilterAccess[$userRole];

        if (in_array('all', $allowedFilters)) {
            return true;
        }

        return in_array($filterType, $allowedFilters);
    }
}
?>
