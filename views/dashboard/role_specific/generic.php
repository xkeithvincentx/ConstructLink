<!-- Generic Dashboard Content - Neutral Design V2.0 -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Key Metrics -->
        <?php
        // Use stat_cards component for consistency (DRY)
        $stats = [
            [
                'icon' => 'bi-building',
                'count' => $dashboardData['active_projects'] ?? 0,
                'label' => 'Active Projects',
                'critical' => false
            ],
            [
                'icon' => 'bi-tools',
                'count' => $dashboardData['maintenance_assets'] ?? 0,
                'label' => 'Under Maintenance',
                'critical' => false
            ],
            [
                'icon' => 'bi-exclamation-triangle',
                'count' => $dashboardData['total_incidents'] ?? 0,
                'label' => 'Total Incidents',
                'critical' => ($dashboardData['total_incidents'] ?? 0) > 0 // Critical if incidents exist
            ]
        ];
        $title = 'Key Metrics';
        $titleIcon = null;
        $columns = 3;
        include APP_ROOT . '/views/dashboard/components/stat_cards.php';
        ?>
    </div>

    <div class="col-lg-4">
        <!-- System Information -->
        <div class="card card-neutral">
            <div class="card-header">
                <h5 class="mb-0">System Status</h5>
            </div>
            <div class="card-body">
                <?php
                // Use list_group component for consistency (DRY)
                $items = [
                    [
                        'label' => 'Database',
                        'value' => 'Online',
                        'icon' => 'bi-database',
                        'success' => true
                    ],
                    [
                        'label' => 'Authentication',
                        'value' => 'Working',
                        'icon' => 'bi-shield-check',
                        'success' => true
                    ],
                    [
                        'label' => 'API Services',
                        'value' => 'Active',
                        'icon' => 'bi-cloud-check',
                        'success' => true
                    ]
                ];
                include APP_ROOT . '/views/dashboard/components/list_group.php';
                ?>
                <hr>
                <div class="d-grid">
                    <a href="?route=users/profile" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-person-gear me-1"></i>Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>