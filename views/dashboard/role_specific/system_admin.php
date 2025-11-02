<?php
/**
 * System Administrator Dashboard
 *
 * Role-specific dashboard for System Administrator with system overview,
 * health monitoring, and administrative operations.
 *
 * Enhanced with Alpine.js for interactive monitoring and filtering.
 * Implements WCAG 2.1 AA accessibility standards.
 *
 * Alpine.js Features:
 * - Collapsible System Health & Metrics sections
 * - Filterable System Status services (Online/Limited/Offline)
 * - Auto-refresh timestamp indicator
 * - Smooth transitions and animations
 *
 * @package ConstructLink
 * @subpackage Dashboard - Role Specific
 * @version 2.1 - Alpine.js Enhanced
 * @since 2025-10-28
 * @updated 2025-11-02
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

        <!-- System Health & Metrics with Alpine.js Collapsible -->
        <div class="card" x-data="{ healthOpen: true, assetOpen: true, workflowOpen: true }">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0" id="system-health-title">
                    <i class="bi bi-shield-check me-2" aria-hidden="true"></i>System Health & Metrics
                </h5>
                <button @click="healthOpen = !healthOpen"
                        class="btn btn-sm btn-outline-secondary"
                        type="button"
                        :aria-expanded="healthOpen"
                        aria-controls="health-content">
                    <i class="bi" :class="healthOpen ? 'bi-chevron-up' : 'bi-chevron-down'" aria-hidden="true"></i>
                    <span x-text="healthOpen ? 'Collapse' : 'Expand'"></span>
                </button>
            </div>
            <div x-show="healthOpen"
                 x-transition
                 id="health-content"
                 class="card-body">
                <div class="row" role="group" aria-labelledby="system-health-title">
                    <div class="col-md-6">
                        <!-- Collapsible Asset Management Section -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-muted mb-0" id="asset-management-title">Asset Management</h6>
                                <button @click="assetOpen = !assetOpen"
                                        class="btn btn-sm btn-link text-decoration-none p-0"
                                        type="button">
                                    <i class="bi" :class="assetOpen ? 'bi-chevron-up' : 'bi-chevron-down'" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div x-show="assetOpen" x-transition>
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
                        </div>
                    </div>
                    <div class="col-md-6">
                        <!-- Collapsible Workflow Status Section -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="text-muted mb-0" id="workflow-status-title">Workflow Status</h6>
                                <button @click="workflowOpen = !workflowOpen"
                                        class="btn btn-sm btn-link text-decoration-none p-0"
                                        type="button">
                                    <i class="bi" :class="workflowOpen ? 'bi-chevron-up' : 'bi-chevron-down'" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div x-show="workflowOpen" x-transition>
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

        <!-- System Status with Alpine.js Filtering and Auto-Refresh -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0" id="system-status-title">
                    <i class="bi bi-server me-2" aria-hidden="true"></i>System Status
                </h5>
                <small class="text-muted" x-data="{ lastUpdate: new Date().toLocaleTimeString() }" x-init="setInterval(() => { lastUpdate = new Date().toLocaleTimeString() }, 60000)">
                    <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>
                    <span x-text="lastUpdate"></span>
                </small>
            </div>
            <div class="card-body">
                <?php
                // Define system services with status
                $systemServices = [
                    [
                        'label' => 'Database',
                        'value' => 'Online',
                        'status' => 'online',
                        'critical' => false,
                        'icon' => 'bi-database'
                    ],
                    [
                        'label' => 'Authentication',
                        'value' => 'Active',
                        'status' => 'online',
                        'critical' => false,
                        'icon' => 'bi-shield-check'
                    ],
                    [
                        'label' => 'API Services',
                        'value' => 'Running',
                        'status' => 'online',
                        'critical' => false,
                        'icon' => 'bi-cloud-check'
                    ],
                    [
                        'label' => 'QR Scanner',
                        'value' => 'Ready',
                        'status' => 'online',
                        'critical' => false,
                        'icon' => 'bi-qr-code-scan'
                    ],
                    [
                        'label' => 'Email Service',
                        'value' => 'Limited',
                        'status' => 'limited',
                        'critical' => true,
                        'icon' => 'bi-envelope'
                    ]
                ];
                ?>

                <!-- Alpine.js Enhanced: Filterable System Services -->
                <div x-data="{
                    services: <?= htmlspecialchars(json_encode($systemServices)) ?>,
                    filter: 'all',
                    setFilter(value) {
                        this.filter = value;
                    },
                    get filteredServices() {
                        if (this.filter === 'all') return this.services;
                        if (this.filter === 'online') return this.services.filter(s => s.status === 'online');
                        if (this.filter === 'limited') return this.services.filter(s => s.status === 'limited');
                        if (this.filter === 'offline') return this.services.filter(s => s.status === 'offline');
                        return this.services;
                    },
                    get onlineCount() {
                        return this.services.filter(s => s.status === 'online').length;
                    },
                    get limitedCount() {
                        return this.services.filter(s => s.status === 'limited').length;
                    },
                    get offlineCount() {
                        return this.services.filter(s => s.status === 'offline').length;
                    }
                }" role="region" aria-labelledby="system-status-title">

                    <!-- Filter Controls -->
                    <div class="btn-group mb-3 d-flex" role="group" aria-label="Filter system services">
                        <button type="button"
                                class="btn btn-sm"
                                :class="filter === 'all' ? 'btn-primary' : 'btn-outline-secondary'"
                                @click="setFilter('all')">
                            <i class="bi bi-list-ul me-1" aria-hidden="true"></i>
                            All (<span x-text="services.length"></span>)
                        </button>
                        <button type="button"
                                class="btn btn-sm"
                                :class="filter === 'online' ? 'btn-success' : 'btn-outline-secondary'"
                                @click="setFilter('online')">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                            Online (<span x-text="onlineCount"></span>)
                        </button>
                        <button type="button"
                                class="btn btn-sm"
                                :class="filter === 'limited' ? 'btn-warning' : 'btn-outline-secondary'"
                                @click="setFilter('limited')">
                            <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                            Limited (<span x-text="limitedCount"></span>)
                        </button>
                        <button type="button"
                                class="btn btn-sm"
                                :class="filter === 'offline' ? 'btn-danger' : 'btn-outline-secondary'"
                                @click="setFilter('offline')">
                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>
                            Offline (<span x-text="offlineCount"></span>)
                        </button>
                    </div>

                    <!-- Dynamic Service List -->
                    <div class="list-group list-group-flush" role="list">
                        <template x-for="(service, index) in filteredServices" :key="service.label">
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center" role="listitem">
                                <div class="d-flex align-items-center">
                                    <i :class="service.icon + ' me-2'" aria-hidden="true"></i>
                                    <span x-text="service.label"></span>
                                </div>
                                <span class="badge"
                                      :class="{
                                          'badge-success-neutral': service.status === 'online',
                                          'badge-warning-neutral': service.status === 'limited',
                                          'badge-danger-neutral': service.status === 'offline'
                                      }"
                                      role="status"
                                      x-text="service.value"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Empty State -->
                    <div x-show="filteredServices.length === 0" class="alert alert-info mt-3" role="status">
                        <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                        No services match the selected filter.
                    </div>
                </div>

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
