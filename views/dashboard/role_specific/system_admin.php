<?php
/**
 * System Administrator Dashboard
 *
 * Role-specific dashboard for System Administrator with system overview,
 * health monitoring, and administrative operations.
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
$adminData = $dashboardData['role_specific']['admin'] ?? [];
?>

<!-- System Administrator Dashboard -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- System Overview -->
        <div class="card mb-4 card-neutral">
            <div class="card-header">
                <h5 class="mb-0" id="system-overview-title">
                    <i class="bi bi-speedometer2 me-2 text-muted" aria-hidden="true"></i>System Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="row" role="group" aria-labelledby="system-overview-title">
                    <?php
                    // Define system overview stats
                    $stats = [
                        [
                            'icon' => 'bi-people',
                            'count' => $adminData['total_users'] ?? 0,
                            'label' => 'Total Users',
                            'critical' => false,
                            'subtext' => '<i class="bi bi-check-circle" aria-hidden="true"></i> ' . number_format($adminData['active_users'] ?? 0) . ' active',
                            'subtextColor' => 'muted'
                        ],
                        [
                            'icon' => 'bi-activity',
                            'count' => $adminData['active_sessions'] ?? 0,
                            'label' => 'Active Sessions',
                            'critical' => false,
                            'subtext' => 'Current online users',
                            'subtextColor' => 'muted'
                        ],
                        [
                            'icon' => IconMapper::MODULE_ASSETS,
                            'count' => $dashboardData['total_assets'] ?? 0,
                            'label' => 'Total Assets',
                            'critical' => false,
                            'subtext' => 'System-wide',
                            'subtextColor' => 'muted'
                        ],
                        [
                            'icon' => IconMapper::MODULE_PROJECTS,
                            'count' => $dashboardData['active_projects'] ?? 0,
                            'label' => 'Active Projects',
                            'critical' => false,
                            'subtext' => 'Currently running',
                            'subtextColor' => 'muted'
                        ]
                    ];

                    foreach ($stats as $stat):
                    ?>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card-item text-center" role="figure">
                            <i class="<?= htmlspecialchars($stat['icon']) ?> text-muted fs-1 d-block mb-2" aria-hidden="true"></i>
                            <h4 class="mt-2 mb-1" aria-live="polite">
                                <?= number_format($stat['count']) ?>
                            </h4>
                            <p class="text-muted mb-1 small"><?= htmlspecialchars($stat['label']) ?></p>
                            <small class="text-<?= htmlspecialchars($stat['subtextColor']) ?>">
                                <?= $stat['subtext'] ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- System Health & Metrics -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0" id="system-health-title">
                    <i class="bi bi-shield-check me-2" aria-hidden="true"></i>System Health & Metrics
                </h5>
            </div>
            <div class="card-body">
                <div class="row" role="group" aria-labelledby="system-health-title">
                    <div class="col-md-6">
                        <h6 class="text-muted" id="asset-management-title">Asset Management</h6>
                        <div class="mb-2" role="region" aria-labelledby="asset-management-title">
                            <?php
                            // Available Assets Progress Bar
                            $label = 'Available Assets';
                            $current = $dashboardData['available_assets'] ?? 0;
                            $total = max(($dashboardData['total_assets'] ?? 1), 1);
                            $config = [
                                'showPercentage' => false,
                                'showCount' => true,
                                'height' => 'progress-sm',
                                'color' => 'success'
                            ];
                            include APP_ROOT . '/views/dashboard/components/progress_bar.php';
                            ?>
                        </div>
                        <div class="mb-2">
                            <?php
                            // In Use Assets Progress Bar
                            $label = 'In Use Assets';
                            $current = $dashboardData['in_use_assets'] ?? 0;
                            $total = max(($dashboardData['total_assets'] ?? 1), 1);
                            $config = [
                                'showPercentage' => false,
                                'showCount' => true,
                                'height' => 'progress-sm',
                                'color' => 'warning'
                            ];
                            include APP_ROOT . '/views/dashboard/components/progress_bar.php';
                            ?>
                        </div>
                        <div class="mb-2">
                            <?php
                            // Under Maintenance Progress Bar
                            $label = 'Under Maintenance';
                            $current = $dashboardData['maintenance_assets'] ?? 0;
                            $total = max(($dashboardData['total_assets'] ?? 1), 1);
                            $config = [
                                'showPercentage' => false,
                                'showCount' => true,
                                'height' => 'progress-sm',
                                'color' => 'info'
                            ];
                            include APP_ROOT . '/views/dashboard/components/progress_bar.php';
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted" id="workflow-status-title">Workflow Status</h6>
                        <?php
                        // Use list_group component for workflow status
                        $items = [
                            [
                                'label' => 'Pending Withdrawals',
                                'value' => $dashboardData['pending_withdrawals'] ?? 0,
                                'critical' => false
                            ],
                            [
                                'label' => 'Overdue Items',
                                'value' => $dashboardData['overdue_withdrawals'] ?? 0,
                                'critical' => ($dashboardData['overdue_withdrawals'] ?? 0) > 0
                            ],
                            [
                                'label' => 'Total Incidents',
                                'value' => $dashboardData['total_incidents'] ?? 0,
                                'critical' => ($dashboardData['total_incidents'] ?? 0) > 0
                            ],
                            [
                                'label' => 'Scheduled Maintenance',
                                'value' => $dashboardData['scheduled_maintenance'] ?? 0,
                                'critical' => false
                            ]
                        ];
                        include APP_ROOT . '/views/dashboard/components/list_group.php';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Quick Actions -->
        <?php
        $title = 'System Administration';
        $actions = [
            [
                'label' => 'Manage Users',
                'route' => 'users',
                'icon' => 'bi-people',
                'critical' => false
            ],
            [
                'label' => 'System Settings',
                'route' => 'admin/settings',
                'icon' => 'bi-gear',
                'critical' => false
            ],
            [
                'label' => 'View Logs',
                'route' => 'admin/logs',
                'icon' => 'bi-activity',
                'critical' => false
            ],
            [
                'label' => 'Generate Reports',
                'route' => 'reports',
                'icon' => 'bi-graph-up',
                'critical' => false
            ]
        ];
        include APP_ROOT . '/views/dashboard/components/quick_actions_card.php';
        ?>

        <!-- System Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0" id="system-status-title">
                    <i class="bi bi-server me-2" aria-hidden="true"></i>System Status
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Use list_group component for system status
                $items = [
                    [
                        'label' => 'Database',
                        'value' => 'Online',
                        'critical' => false,
                        'icon' => 'bi-database'
                    ],
                    [
                        'label' => 'Authentication',
                        'value' => 'Active',
                        'critical' => false,
                        'icon' => 'bi-shield-check'
                    ],
                    [
                        'label' => 'API Services',
                        'value' => 'Running',
                        'critical' => false,
                        'icon' => 'bi-cloud-check'
                    ],
                    [
                        'label' => 'QR Scanner',
                        'value' => 'Ready',
                        'critical' => false,
                        'icon' => 'bi-qr-code-scan'
                    ],
                    [
                        'label' => 'Email Service',
                        'value' => 'Limited',
                        'critical' => false,
                        'icon' => 'bi-envelope'
                    ]
                ];
                include APP_ROOT . '/views/dashboard/components/list_group.php';
                ?>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span><strong>System Version</strong></span>
                    <small class="text-muted"><?= APP_VERSION ?? '2.0.0' ?></small>
                </div>
            </div>
        </div>

        <!-- Recent Admin Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0" id="recent-actions-title">
                    <i class="bi bi-clock-history me-2" aria-hidden="true"></i>Recent Admin Actions
                </h5>
            </div>
            <div class="card-body">
                <?php
                // Use list_group component for recent admin actions
                $items = [
                    [
                        'label' => 'Users Created This Week',
                        'value' => 0,
                        'critical' => false
                    ],
                    [
                        'label' => 'Settings Modified',
                        'value' => 0,
                        'critical' => false
                    ],
                    [
                        'label' => 'Reports Generated',
                        'value' => 0,
                        'critical' => false
                    ],
                    [
                        'label' => 'System Maintenance',
                        'value' => 0,
                        'critical' => false
                    ]
                ];
                include APP_ROOT . '/views/dashboard/components/list_group.php';
                ?>
                <div class="mt-3 d-grid">
                    <a href="?route=<?= urlencode('admin/logs') ?>"
                       class="btn btn-outline-secondary btn-sm"
                       aria-label="View full admin activity log">
                        View Full Log
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
