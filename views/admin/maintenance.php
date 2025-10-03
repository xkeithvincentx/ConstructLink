<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- System Health Overview -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">System Status</h6>
                        <h3 class="mb-0"><?= $systemHealth['status'] ?? 'Unknown' ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-heart-pulse display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Database Size</h6>
                        <h3 class="mb-0"><?= formatBytes($systemHealth['database_size'] ?? 0) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-database display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Memory Usage</h6>
                        <h3 class="mb-0"><?= formatBytes($systemHealth['memory_usage'] ?? 0) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-memory display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Uptime</h6>
                        <h3 class="mb-0"><?= $systemHealth['uptime'] ?? 'N/A' ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Actions -->
<div class="row">
    <!-- Database Maintenance -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-database me-2"></i>Database Maintenance
                </h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Optimize Database</h6>
                            <small class="text-muted">Optimize database tables for better performance</small>
                        </div>
                        <button class="btn btn-outline-primary btn-sm" onclick="optimizeDatabase()">
                            <i class="bi bi-speedometer2 me-1"></i>Optimize
                        </button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Clean Activity Logs</h6>
                            <small class="text-muted">Remove old activity logs (older than 90 days)</small>
                        </div>
                        <button class="btn btn-outline-warning btn-sm" onclick="cleanLogs()">
                            <i class="bi bi-trash me-1"></i>Clean
                        </button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Database Backup</h6>
                            <small class="text-muted">Create a backup of the database</small>
                        </div>
                        <button class="btn btn-outline-success btn-sm" onclick="backupDatabase()">
                            <i class="bi bi-download me-1"></i>Backup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cache Management -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Cache Management
                </h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Clear Application Cache</h6>
                            <small class="text-muted">Clear all cached data and temporary files</small>
                        </div>
                        <button class="btn btn-outline-danger btn-sm" onclick="clearCache()">
                            <i class="bi bi-trash me-1"></i>Clear
                        </button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Clear Session Data</h6>
                            <small class="text-muted">Clear all user sessions (users will need to re-login)</small>
                        </div>
                        <button class="btn btn-outline-warning btn-sm" onclick="clearSessions()">
                            <i class="bi bi-people me-1"></i>Clear
                        </button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Refresh System Cache</h6>
                            <small class="text-muted">Rebuild system cache for optimal performance</small>
                        </div>
                        <button class="btn btn-outline-info btn-sm" onclick="refreshCache()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- File System -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-folder me-2"></i>File System
                </h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Clean Temporary Files</h6>
                            <small class="text-muted">Remove temporary files and uploads</small>
                        </div>
                        <button class="btn btn-outline-warning btn-sm" onclick="cleanTempFiles()">
                            <i class="bi bi-trash me-1"></i>Clean
                        </button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Check File Permissions</h6>
                            <small class="text-muted">Verify file and directory permissions</small>
                        </div>
                        <button class="btn btn-outline-info btn-sm" onclick="checkPermissions()">
                            <i class="bi bi-shield-check me-1"></i>Check
                        </button>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Disk Space Usage</h6>
                            <small class="text-muted">Check available disk space</small>
                        </div>
                        <span class="badge bg-light text-dark">
                            <?= $systemHealth['disk_usage'] ?? 'Unknown' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>System Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6">
                        <strong>PHP Version:</strong>
                    </div>
                    <div class="col-sm-6">
                        <?= PHP_VERSION ?>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-6">
                        <strong>Server Software:</strong>
                    </div>
                    <div class="col-sm-6">
                        <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-6">
                        <strong>Database Version:</strong>
                    </div>
                    <div class="col-sm-6">
                        <?= $systemHealth['database_version'] ?? 'Unknown' ?>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-6">
                        <strong>System Load:</strong>
                    </div>
                    <div class="col-sm-6">
                        <?= $systemHealth['system_load'] ?? 'Unknown' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Mode Toggle -->
<div class="row">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Maintenance Mode
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">System Maintenance Mode</h6>
                        <p class="text-muted mb-0">
                            When enabled, only System Administrators can access the system. 
                            All other users will see a maintenance message.
                        </p>
                    </div>
                    <div>
                        <?php $maintenanceMode = $systemHealth['maintenance_mode'] ?? false; ?>
                        <button class="btn <?= $maintenanceMode ? 'btn-danger' : 'btn-success' ?>" 
                                onclick="toggleMaintenanceMode(<?= $maintenanceMode ? 'false' : 'true' ?>)">
                            <i class="bi bi-<?= $maintenanceMode ? 'play' : 'pause' ?> me-1"></i>
                            <?= $maintenanceMode ? 'Disable' : 'Enable' ?> Maintenance Mode
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function optimizeDatabase() {
    if (confirm('This will optimize all database tables. Continue?')) {
        performMaintenance('optimizeDatabase', 'Database optimization completed successfully.');
    }
}

function cleanLogs() {
    if (confirm('This will permanently delete old activity logs. Continue?')) {
        performMaintenance('cleanLogs', 'Activity logs cleaned successfully.');
    }
}

function backupDatabase() {
    if (confirm('Create a database backup? This may take a few minutes.')) {
        performMaintenance('backupDatabase', 'Database backup created successfully.');
    }
}

function clearCache() {
    if (confirm('Clear all application cache? This may temporarily slow down the system.')) {
        performMaintenance('clearCache', 'Application cache cleared successfully.');
    }
}

function clearSessions() {
    if (confirm('This will log out all users. Continue?')) {
        performMaintenance('clearSessions', 'All user sessions cleared successfully.');
    }
}

function refreshCache() {
    performMaintenance('refreshCache', 'System cache refreshed successfully.');
}

function cleanTempFiles() {
    if (confirm('Remove all temporary files? This action cannot be undone.')) {
        performMaintenance('cleanTempFiles', 'Temporary files cleaned successfully.');
    }
}

function checkPermissions() {
    performMaintenance('checkPermissions', 'File permissions checked successfully.');
}

function toggleMaintenanceMode(enable) {
    const action = enable ? 'enable' : 'disable';
    const message = enable ? 
        'Enable maintenance mode? Only System Administrators will be able to access the system.' :
        'Disable maintenance mode? All users will regain access to the system.';
    
    if (confirm(message)) {
        performMaintenance('toggleMaintenanceMode', 
            `Maintenance mode ${enable ? 'enabled' : 'disabled'} successfully.`, 
            { enable: enable });
    }
}

function performMaintenance(action, successMessage, data = {}) {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Processing...';
    
    fetch('?route=admin/maintenance', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            action: action,
            ...data
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(successMessage);
            if (action === 'toggleMaintenanceMode') {
                location.reload();
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during maintenance operation');
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

// Auto-refresh system health every 30 seconds
setInterval(() => {
    fetch('?route=admin/getSystemHealth')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update system health display
                updateSystemHealth(data.health);
            }
        })
        .catch(error => console.error('Error refreshing system health:', error));
}, 30000);

function updateSystemHealth(health) {
    // Update health indicators (implementation depends on specific needs)
    console.log('System health updated:', health);
}
</script>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: rgba(0, 0, 0, 0.03);
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.list-group-item {
    border-left: none;
    border-right: none;
}

.list-group-item:first-child {
    border-top: none;
}

.list-group-item:last-child {
    border-bottom: none;
}

.btn:disabled {
    opacity: 0.6;
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'System Maintenance - ConstructLink™';
$pageHeader = 'System Maintenance';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'System Admin', 'url' => '?route=admin'],
    ['title' => 'Maintenance', 'url' => '?route=admin/maintenance']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
