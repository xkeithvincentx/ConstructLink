<?php
/**
 * Site Inventory Clerk Dashboard
 *
 * Role-specific dashboard for Site Inventory Clerk with pending site actions,
 * inventory status monitoring, and project equipment tracking.
 *
 * Refactored to use reusable components and eliminate code duplication.
 * Implements WCAG 2.1 AA accessibility standards.
 *
 * @package ConstructLink
 * @subpackage Dashboard - Role Specific
 * @version 2.0 - Refactored
 * @since 2025-10-28
 */

// Ensure required constants are available
if (!class_exists('WorkflowStatus')) {
    require_once APP_ROOT . '/includes/constants/WorkflowStatus.php';
}
if (!class_exists('DashboardThresholds')) {
    require_once APP_ROOT . '/includes/constants/DashboardThresholds.php';
}
if (!class_exists('IconMapper')) {
    require_once APP_ROOT . '/includes/constants/IconMapper.php';
}

// Extract role-specific data
$siteData = $dashboardData['role_specific']['site_clerk'] ?? [];
?>

<!-- Site Inventory Clerk Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Pending Site Actions -->
        <div class="card mb-4 card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="pending-actions-title">
                    <i class="<?= IconMapper::PENDING_ACTIONS ?> me-2 text-muted" aria-hidden="true"></i>Pending Site Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row" role="group" aria-labelledby="pending-actions-title">
                    <?php
                    // Define pending action items using WorkflowStatus constants
                    $pendingItems = [
                        [
                            'label' => 'Draft Requests',
                            'count' => $siteData['draft_requests'] ?? 0,
                            'route' => WorkflowStatus::buildRoute('requests', WorkflowStatus::REQUEST_DRAFT),
                            'icon' => IconMapper::MODULE_REQUESTS,
                            'critical' => false
                        ],
                        [
                            'label' => 'Deliveries to Verify',
                            'count' => $siteData['deliveries_to_verify'] ?? 0,
                            'route' => WorkflowStatus::buildRoute('procurement-orders', WorkflowStatus::PROCUREMENT_DELIVERED),
                            'icon' => 'bi-clipboard-check',
                            'critical' => false
                        ],
                        [
                            'label' => 'Transfers to Receive',
                            'count' => $siteData['transfers_to_receive'] ?? 0,
                            'route' => WorkflowStatus::buildRoute('transfers', WorkflowStatus::TRANSFER_APPROVED),
                            'icon' => IconMapper::MODULE_TRANSFERS,
                            'critical' => false
                        ],
                        [
                            'label' => 'Withdrawals to Verify',
                            'count' => $siteData['withdrawals_to_verify'] ?? 0,
                            'route' => WorkflowStatus::buildRoute('withdrawals', WorkflowStatus::WITHDRAWAL_PENDING_VERIFICATION),
                            'icon' => 'bi-check-circle',
                            'critical' => false
                        ]
                    ];

                    // Set action button text
                    $actionText = 'Process Now';

                    // Render each pending action card using component
                    foreach ($pendingItems as $item) {
                        include APP_ROOT . '/views/dashboard/components/pending_action_card.php';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Site Inventory Status -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0" id="inventory-status-title">
                    <i class="bi bi-grid-3x3-gap me-2" aria-hidden="true"></i>Site Inventory Status
                </h5>
            </div>
            <div class="card-body">
                <div class="row" role="group" aria-labelledby="inventory-status-title">
                    <div class="col-md-6">
                        <h6 class="text-muted" id="site-assets-title">Current Site Assets</h6>
                        <?php
                        // Use list_group component for site asset counts
                        $items = [
                            [
                                'label' => 'Available on Site',
                                'value' => number_format($siteData['available_on_site'] ?? 0),
                                'critical' => false
                            ],
                            [
                                'label' => 'In Use on Site',
                                'value' => number_format($siteData['in_use_on_site'] ?? 0),
                                'critical' => false
                            ],
                            [
                                'label' => 'Low Stock Alerts',
                                'value' => number_format($siteData['low_stock_alerts'] ?? 0),
                                'critical' => ($siteData['low_stock_alerts'] ?? 0) > 0
                            ]
                        ];
                        include APP_ROOT . '/views/dashboard/components/list_group.php';
                        ?>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted" id="today-activity-title">Today's Activity</h6>
                        <?php
                        // Use list_group component for today's activity
                        $items = [
                            [
                                'label' => 'Tools Borrowed',
                                'value' => $siteData['tools_borrowed_today'] ?? 0,
                                'critical' => false
                            ],
                            [
                                'label' => 'Tools Returned',
                                'value' => $siteData['tools_returned_today'] ?? 0,
                                'critical' => false
                            ],
                            [
                                'label' => 'Requests Created',
                                'value' => $siteData['requests_created_today'] ?? 0,
                                'critical' => false
                            ]
                        ];
                        include APP_ROOT . '/views/dashboard/components/list_group.php';
                        ?>
                    </div>
                </div>

                <?php if (($siteData['low_stock_alerts'] ?? 0) > 0): ?>
                <div class="alert alert-warning mt-3" role="alert">
                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                    <strong>Low Stock Alert:</strong> <?= $siteData['low_stock_alerts'] ?> items are running low. Consider creating requests.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Quick Actions -->
        <?php
        $title = 'Site Operations';
        $actions = [
            [
                'label' => 'Create Request',
                'route' => 'requests/create',
                'icon' => IconMapper::ACTION_CREATE,
                'critical' => false
            ],
            [
                'label' => 'Report Incident',
                'route' => 'incidents/create',
                'icon' => 'bi-exclamation-circle',
                'critical' => true
            ],
            [
                'label' => 'Initiate Transfer',
                'route' => 'transfers/create',
                'icon' => 'bi-arrow-repeat',
                'critical' => false
            ],
            [
                'label' => 'Schedule Maintenance',
                'route' => 'maintenance/create',
                'icon' => IconMapper::MODULE_MAINTENANCE,
                'critical' => false
            ]
        ];
        include APP_ROOT . '/views/dashboard/components/quick_actions_card.php';
        ?>

        <!-- Project Equipment -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0" id="project-equipment-title">
                    <i class="bi bi-tools me-2" aria-hidden="true"></i>Project Equipment
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Use list_group component for project equipment
                $items = [
                    [
                        'label' => 'Currently Borrowed',
                        'value' => $dashboardData['borrowed_tools']['project_borrowed'] ?? 0,
                        'critical' => false,
                        'icon' => 'bi-box-arrow-right'
                    ],
                    [
                        'label' => 'Overdue Returns',
                        'value' => $dashboardData['borrowed_tools']['project_overdue'] ?? 0,
                        'critical' => true,
                        'icon' => 'bi-exclamation-triangle'
                    ],
                    [
                        'label' => 'Due This Week',
                        'value' => $dashboardData['borrowed_tools']['project_due_soon'] ?? 0,
                        'critical' => false,
                        'icon' => 'bi-calendar-event'
                    ],
                    [
                        'label' => 'Available',
                        'value' => $dashboardData['borrowed_tools']['project_available'] ?? 0,
                        'critical' => false,
                        'icon' => 'bi-box-seam'
                    ]
                ];
                include APP_ROOT . '/views/dashboard/components/list_group.php';
                ?>
                <div class="mt-3 d-grid gap-2">
                    <a href="?route=borrowed-tools"
                       class="btn btn-outline-secondary btn-sm"
                       aria-label="View all equipment">
                        <i class="bi bi-eye" aria-hidden="true"></i> View All Equipment
                    </a>
                    <a href="?route=<?= urlencode('borrowed-tools/create-batch') ?>"
                       class="btn btn-outline-secondary btn-sm"
                       aria-label="Borrow equipment">
                        <i class="<?= IconMapper::ACTION_CREATE ?>" aria-hidden="true"></i> Borrow Equipment
                    </a>
                </div>
            </div>
        </div>

        <!-- Incident Management -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0" id="incident-management-title">
                    <i class="<?= IconMapper::MODULE_INCIDENTS ?> me-2" aria-hidden="true"></i>Incident Management
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Use list_group component for incident management
                $items = [
                    [
                        'label' => 'Open Incidents',
                        'value' => $siteData['open_incidents'] ?? 0,
                        'critical' => ($siteData['open_incidents'] ?? 0) > 0,
                        'icon' => 'bi-exclamation-triangle'
                    ],
                    [
                        'label' => 'Recent (7 days)',
                        'value' => $siteData['recent_incidents'] ?? 0,
                        'critical' => false,
                        'icon' => 'bi-clock'
                    ],
                    [
                        'label' => 'Lost Items',
                        'value' => $siteData['lost_items'] ?? 0,
                        'critical' => true,
                        'icon' => 'bi-geo-alt'
                    ],
                    [
                        'label' => 'Damaged Items',
                        'value' => $siteData['damaged_items'] ?? 0,
                        'critical' => ($siteData['damaged_items'] ?? 0) > 0,
                        'icon' => 'bi-hammer'
                    ]
                ];
                include APP_ROOT . '/views/dashboard/components/list_group.php';
                ?>
                <div class="mt-3 d-grid">
                    <a href="?route=incidents"
                       class="btn btn-outline-secondary btn-sm"
                       aria-label="View all incidents">
                        View All Incidents
                    </a>
                </div>
            </div>
        </div>

        <!-- Daily Summary -->
        <?php
        $stats = [
            [
                'icon' => 'bi-tools',
                'count' => $siteData['tools_borrowed_today'] ?? 0,
                'label' => 'Tools Out',
                'critical' => false
            ],
            [
                'icon' => 'bi-arrow-counterclockwise',
                'count' => $siteData['tools_returned_today'] ?? 0,
                'label' => 'Tools In',
                'critical' => false
            ],
            [
                'icon' => 'bi-file-earmark-plus',
                'count' => $siteData['requests_created_today'] ?? 0,
                'label' => 'Requests Made',
                'critical' => false
            ],
            [
                'icon' => 'bi-clipboard-check',
                'count' => $siteData['deliveries_to_verify'] ?? 0,
                'label' => 'To Verify',
                'critical' => false
            ]
        ];
        $title = 'Daily Summary';
        include APP_ROOT . '/views/dashboard/components/stat_cards.php';
        ?>
    </div>
</div>
