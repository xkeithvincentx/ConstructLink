<!-- Generic Dashboard Content - Neutral Design V2.0 with Alpine.js -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Key Metrics with Alpine.js Collapsible -->
        <div class="card card-neutral mb-4" x-data="{ metricsOpen: true }">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-speedometer2 me-2 text-muted" aria-hidden="true"></i>Key Metrics
                </h5>
                <button @click="metricsOpen = !metricsOpen"
                        class="btn btn-sm btn-outline-secondary"
                        type="button"
                        :aria-expanded="metricsOpen"
                        aria-controls="metrics-content">
                    <i class="bi" :class="metricsOpen ? 'bi-chevron-up' : 'bi-chevron-down'" aria-hidden="true"></i>
                    <span x-text="metricsOpen ? 'Collapse' : 'Expand'"></span>
                </button>
            </div>
            <div x-show="metricsOpen" x-transition id="metrics-content" class="card-body">
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

                // Display stats directly in card body
                ?>
                <div class="row">
                    <?php foreach ($stats as $stat): ?>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="stat-card-item text-center" role="figure">
                            <i class="<?= htmlspecialchars($stat['icon']) ?> <?= $stat['critical'] ? 'text-danger' : 'text-muted' ?> fs-1 d-block mb-2" aria-hidden="true"></i>
                            <h4 class="mt-2 mb-1" aria-live="polite">
                                <?= number_format($stat['count']) ?>
                            </h4>
                            <p class="text-muted mb-0 small"><?= htmlspecialchars($stat['label']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- System Information with Alpine.js Filtering -->
        <div class="card card-neutral">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-activity me-2 text-muted" aria-hidden="true"></i>System Status
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
                        'icon' => 'bi-database',
                        'critical' => false
                    ],
                    [
                        'label' => 'Authentication',
                        'value' => 'Working',
                        'status' => 'online',
                        'icon' => 'bi-shield-check',
                        'critical' => false
                    ],
                    [
                        'label' => 'API Services',
                        'value' => 'Active',
                        'status' => 'online',
                        'icon' => 'bi-cloud-check',
                        'critical' => false
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
                        if (this.filter === 'issues') return this.services.filter(s => s.status !== 'online');
                        return this.services;
                    },
                    get onlineCount() {
                        return this.services.filter(s => s.status === 'online').length;
                    },
                    get issuesCount() {
                        return this.services.filter(s => s.status !== 'online').length;
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
                                :class="filter === 'issues' ? 'btn-warning' : 'btn-outline-secondary'"
                                @click="setFilter('issues')">
                            <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                            Issues (<span x-text="issuesCount"></span>)
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
                                <span class="badge badge-success-neutral"
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
                <div class="d-grid">
                    <a href="?route=users/profile" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-person-gear me-1" aria-hidden="true"></i>Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>