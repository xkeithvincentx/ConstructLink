<?php
/**
 * ConstructLink™ Request Statistics Cards Configuration
 *
 * Defines statistics card data for the requests index page.
 * Eliminates 150+ lines of duplicated HTML by using data-driven approach.
 *
 * @return array Array of statistics card configurations
 */

return [
    [
        'title' => 'Draft/Submitted',
        'value' => ($requestStats['draft'] ?? 0) + ($requestStats['submitted'] ?? 0),
        'icon' => 'pencil-square',
        'color' => 'info',
        'description' => 'Awaiting initial review',
        'actionUrl' => '?route=requests&status=Submitted',
        'actionLabel' => 'Review',
        'actionBadge' => $requestStats['my_pending_reviews'] ?? 0
    ],
    [
        'title' => 'Under Review',
        'value' => ($requestStats['reviewed'] ?? 0) + ($requestStats['forwarded'] ?? 0),
        'icon' => 'search',
        'color' => 'warning',
        'description' => 'Project Manager verified',
        'actionUrl' => '',
        'actionLabel' => ''
    ],
    [
        'title' => 'Procurement Ready',
        'value' => $requestStats['approved'] ?? 0,
        'icon' => 'check-circle',
        'color' => 'success',
        'description' => 'Approved, awaiting PO creation',
        'actionUrl' => '',
        'actionLabel' => ''
    ],
    [
        'title' => 'In Procurement',
        'value' => $requestStats['in_procurement'] ?? 0,
        'icon' => 'cart-check',
        'color' => 'primary',
        'description' => 'PO created and in progress',
        'actionUrl' => '',
        'actionLabel' => ''
    ],
    [
        'title' => 'Urgent/Critical',
        'value' => ($requestStats['critical'] ?? 0) + ($requestStats['urgent'] ?? 0),
        'icon' => 'exclamation-triangle',
        'color' => 'danger',
        'description' => ($requestStats['overdue_requests'] ?? 0) . ' overdue',
        'actionUrl' => (($requestStats['critical'] ?? 0) + ($requestStats['urgent'] ?? 0)) > 0 ? '?route=requests&urgency=Critical' : '',
        'actionLabel' => (($requestStats['critical'] ?? 0) + ($requestStats['urgent'] ?? 0)) > 0 ? 'Review Priority Items' : ''
    ],
    [
        'title' => 'Completed',
        'value' => $requestStats['completed'] ?? 0,
        'icon' => 'check-all',
        'color' => 'neutral',
        'description' => 'Fulfilled requests',
        'actionUrl' => '',
        'actionLabel' => ''
    ],
    [
        'title' => 'Declined',
        'value' => $requestStats['declined'] ?? 0,
        'icon' => 'x-circle',
        'color' => 'neutral',
        'description' => 'Rejected requests',
        'actionUrl' => '',
        'actionLabel' => ''
    ],
    [
        'title' => 'Total Requests',
        'value' => $requestStats['total_requests'] ?? 0,
        'icon' => 'list-ul',
        'color' => 'neutral',
        'description' => 'All time',
        'actionUrl' => '',
        'actionLabel' => ''
    ]
];
