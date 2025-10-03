<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="btn-toolbar gap-2" role="toolbar">
        <a href="?route=admin/settings" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-sliders me-1"></i>
            <span class="d-none d-sm-inline">Settings</span>
        </a>
        <a href="?route=admin/upgrades" class="btn btn-outline-success btn-sm">
            <i class="bi bi-arrow-up-circle me-1"></i>
            <span class="d-none d-sm-inline">Upgrades</span>
        </a>
        <a href="?route=admin/backups" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-cloud-download me-1"></i>
            <span class="d-none d-sm-inline">Backups</span>
        </a>
        <a href="?route=admin/maintenance" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-tools me-1"></i>
            <span class="d-none d-sm-inline">Maintenance</span>
        </a>
        <a href="?route=admin/security" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-shield-lock me-1"></i>
            <span class="d-none d-sm-inline">Security</span>
        </a>
        <a href="?route=admin/modules" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-puzzle me-1"></i>
            <span class="d-none d-sm-inline">Modules</span>
        </a>
        <a href="?route=admin/logs" class="btn btn-outline-info btn-sm">
            <i class="bi bi-journal-text me-1"></i>
            <span class="d-none d-sm-inline">Logs</span>
        </a>
    </div>
</div>

    <!-- System Health Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-heart-pulse me-2"></i>System Health
                    </h6>
                    <?php
                    $healthClass = [
                        'good' => 'text-success',
                        'warning' => 'text-warning',
                        'critical' => 'text-danger',
                        'unknown' => 'text-muted'
                    ];
                    $healthIcon = [
                        'good' => 'bi-check-circle-fill',
                        'warning' => 'bi-exclamation-triangle-fill',
                        'critical' => 'bi-x-circle-fill',
                        'unknown' => 'bi-question-circle-fill'
                    ];
                    $overallHealth = $systemHealth['overall'] ?? 'unknown';
                    ?>
                    <span class="<?= $healthClass[$overallHealth] ?? 'text-muted' ?>">
                        <i class="bi <?= $healthIcon[$overallHealth] ?? 'bi-question-circle-fill' ?> me-1"></i>
                        <?= ucfirst($overallHealth) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (isset($systemHealth['checks'])): ?>
                            <?php foreach ($systemHealth['checks'] as $checkName => $check): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="d-flex align-items-center">
                                        <?php
                                        $statusClass = [
                                            'good' => 'text-success',
                                            'warning' => 'text-warning',
                                            'critical' => 'text-danger',
                                            'unknown' => 'text-muted'
                                        ];
                                        $statusIcon = [
                                            'good' => 'bi-check-circle',
                                            'warning' => 'bi-exclamation-triangle',
                                            'critical' => 'bi-x-circle',
                                            'unknown' => 'bi-question-circle'
                                        ];
                                        ?>
                                        <i class="bi <?= $statusIcon[$check['status']] ?? 'bi-question-circle' ?> <?= $statusClass[$check['status']] ?? 'text-muted' ?> me-2"></i>
                                        <div>
                                            <div class="fw-medium"><?= ucfirst($checkName) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($check['message']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Statistics -->
    <div class="row mb-4">
        <!-- Database Statistics -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-database me-2"></i>Database
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (isset($systemStats['database'])): ?>
                        <div class="mb-2">
                            <small class="text-muted">Size</small>
                            <div class="fw-medium"><?= number_format($systemStats['database']['size_mb'] ?? 0, 2) ?> MB</div>
                        </div>
                        <?php if (isset($systemStats['database']['tables'])): ?>
                            <?php foreach ($systemStats['database']['tables'] as $table => $count): ?>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted"><?= ucfirst($table) ?></small>
                                    <span class="badge bg-light text-dark"><?= number_format($count) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">Database statistics unavailable</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Statistics -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-people me-2"></i>Users
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (isset($systemStats['users'])): ?>
                        <div class="mb-2">
                            <small class="text-muted">Total Users</small>
                            <div class="fw-medium"><?= number_format($systemStats['users']['total'] ?? 0) ?></div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Active Users</small>
                            <div class="fw-medium"><?= number_format($systemStats['users']['active'] ?? 0) ?></div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Recent Logins (30 days)</small>
                            <div class="fw-medium"><?= number_format($systemStats['users']['recent_logins'] ?? 0) ?></div>
                        </div>
                        <?php if (isset($systemStats['users']['by_role'])): ?>
                            <hr class="my-2">
                            <small class="text-muted d-block mb-1">By Role:</small>
                            <?php foreach (array_slice($systemStats['users']['by_role'], 0, 3) as $role): ?>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted"><?= htmlspecialchars($role['name']) ?></small>
                                    <span class="badge bg-light text-dark"><?= $role['count'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">User statistics unavailable</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Asset Statistics -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-box me-2"></i>Assets
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (isset($systemStats['assets'])): ?>
                        <div class="mb-2">
                            <small class="text-muted">Total Assets</small>
                            <div class="fw-medium"><?= number_format($systemStats['assets']['total'] ?? 0) ?></div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Total Value</small>
                            <div class="fw-medium">₱<?= number_format($systemStats['assets']['total_value'] ?? 0, 2) ?></div>
                        </div>
                        <?php if (isset($systemStats['assets']['by_status'])): ?>
                            <hr class="my-2">
                            <small class="text-muted d-block mb-1">By Status:</small>
                            <?php foreach ($systemStats['assets']['by_status'] as $status): ?>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted"><?= ucfirst(str_replace('_', ' ', $status['status'])) ?></small>
                                    <span class="badge bg-light text-dark"><?= $status['count'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">Asset statistics unavailable</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>System Information
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (isset($systemStats['system'])): ?>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="mb-2">
                                    <small class="text-muted">PHP Version</small>
                                    <div class="fw-medium"><?= htmlspecialchars($systemStats['system']['php_version'] ?? 'Unknown') ?></div>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">MySQL Version</small>
                                    <div class="fw-medium"><?= htmlspecialchars($systemStats['system']['mysql_version'] ?? 'Unknown') ?></div>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Server Software</small>
                                    <div class="fw-medium"><?= htmlspecialchars($systemStats['system']['server_software'] ?? 'Unknown') ?></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <?php if (isset($systemStats['system']['memory_usage_mb'])): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">Memory Usage</small>
                                        <div class="fw-medium"><?= number_format($systemStats['system']['memory_usage_mb'], 2) ?> MB</div>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($systemStats['system']['disk_free_gb'])): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">Disk Space</small>
                                        <div class="fw-medium">
                                            <?= number_format($systemStats['system']['disk_free_gb'], 2) ?> GB free
                                            (<?= number_format($systemStats['system']['disk_used_percent'], 1) ?>% used)
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="mb-2">
                                    <small class="text-muted">Server Name</small>
                                    <div class="fw-medium"><?= htmlspecialchars($systemStats['system']['server_name'] ?? 'Unknown') ?></div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">System information unavailable</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-activity me-2"></i>Recent Activity
                    </h6>
                    <a href="?route=admin/logs" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye me-1"></i>View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentActivity)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($recentActivity, 0, 5) as $activity): ?>
                                <div class="list-group-item px-0 py-2 border-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="fw-medium small"><?= htmlspecialchars($activity['description']) ?></div>
                                            <small class="text-muted">
                                                by <?= htmlspecialchars($activity['user_name'] ?? 'System') ?>
                                                • <?= timeAgo($activity['created_at']) ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-light text-dark ms-2"><?= htmlspecialchars($activity['action']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No recent activity</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="?route=admin/settings" class="btn btn-outline-primary w-100">
                                <i class="bi bi-sliders me-2"></i>System Settings
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="?route=users" class="btn btn-outline-info w-100">
                                <i class="bi bi-people me-2"></i>Manage Users
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="?route=admin/maintenance" class="btn btn-outline-warning w-100">
                                <i class="bi bi-tools me-2"></i>Maintenance
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="?route=reports" class="btn btn-outline-success w-100">
                                <i class="bi bi-graph-up me-2"></i>View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: rgba(0, 0, 0, 0.03);
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.list-group-item:last-child {
    border-bottom: none !important;
}

.badge {
    font-size: 0.75em;
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'System Administration - ConstructLink™';
$pageHeader = 'System Administration';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'System Admin', 'url' => '?route=admin']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
