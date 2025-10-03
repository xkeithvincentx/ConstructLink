<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Current System Information -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Current System
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>Version:</strong> <?= htmlspecialchars($currentVersion) ?>
                </div>
                <div class="mb-2">
                    <strong>Status:</strong> 
                    <span class="badge bg-<?= $systemIntegrity['valid'] ? 'success' : 'warning' ?>">
                        <?= $systemIntegrity['valid'] ? 'Healthy' : 'Issues Detected' ?>
                    </span>
                </div>
                <div class="mb-2">
                    <strong>Pending Migrations:</strong> 
                    <span class="badge bg-<?= empty($pendingMigrations) ? 'success' : 'warning' ?>">
                        <?= count($pendingMigrations) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-list-check me-2"></i>System Integrity Check
                </h6>
            </div>
            <div class="card-body">
                <?php if ($systemIntegrity['valid']): ?>
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle me-2"></i>
                        System integrity check passed. All components are functioning properly.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        System integrity issues detected:
                        <ul class="mb-0 mt-2">
                            <?php foreach ($systemIntegrity['issues'] as $category => $issues): ?>
                                <?php foreach ($issues as $issue): ?>
                                    <li><strong><?= ucfirst($category) ?>:</strong> <?= htmlspecialchars($issue) ?></li>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <button class="btn btn-outline-primary" onclick="checkIntegrity()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Re-check Integrity
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Available Upgrades -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-download me-2"></i>Available Upgrades
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($availableVersions)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-check-circle me-2"></i>
                        Your system is up to date. No upgrades available.
                    </div>
                <?php else: ?>
                    <?php foreach ($availableVersions as $version): ?>
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h5 class="mb-2">
                                        Version <?= htmlspecialchars($version['version']) ?>
                                        <small class="text-muted">Released: <?= htmlspecialchars($version['release_date']) ?></small>
                                    </h5>
                                    <p class="mb-2"><?= htmlspecialchars($version['description']) ?></p>
                                    
                                    <?php if (!empty($version['features'])): ?>
                                        <div class="mb-2">
                                            <strong>New Features:</strong>
                                            <ul class="mb-0">
                                                <?php foreach ($version['features'] as $feature): ?>
                                                    <li><?= htmlspecialchars($feature) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($version['breaking_changes'])): ?>
                                        <div class="mb-2">
                                            <strong class="text-warning">Breaking Changes:</strong>
                                            <ul class="mb-0">
                                                <?php foreach ($version['breaking_changes'] as $change): ?>
                                                    <li class="text-warning"><?= htmlspecialchars($change) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($version['migrations'])): ?>
                                        <div class="mb-2">
                                            <strong>Database Migrations:</strong>
                                            <small class="text-muted"><?= count($version['migrations']) ?> migration(s) required</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ms-3">
                                    <button class="btn btn-success" onclick="upgradeToVersion('<?= htmlspecialchars($version['version']) ?>')">
                                        <i class="bi bi-download me-1"></i>Upgrade Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Pending Migrations -->
<?php if (!empty($pendingMigrations)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="bi bi-database me-2"></i>Pending Database Migrations
                </h6>
                <button class="btn btn-primary btn-sm" onclick="executePendingMigrations()">
                    <i class="bi bi-play me-1"></i>Execute All Migrations
                </button>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    There are <?= count($pendingMigrations) ?> pending database migration(s). 
                    These should be executed before using the system.
                </div>
                <div class="list-group">
                    <?php foreach ($pendingMigrations as $migration): ?>
                        <div class="list-group-item">
                            <i class="bi bi-file-earmark-code me-2"></i>
                            <?= htmlspecialchars($migration) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Upgrade History -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Upgrade History
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($upgradeHistory)): ?>
                    <p class="text-muted mb-0">No upgrade history available.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>From Version</th>
                                    <th>To Version</th>
                                    <th>Upgraded By</th>
                                    <th>Date</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upgradeHistory as $upgrade): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($upgrade['from_version']) ?></td>
                                        <td><strong><?= htmlspecialchars($upgrade['to_version']) ?></strong></td>
                                        <td><?= htmlspecialchars($upgrade['upgraded_by_name'] ?? 'System') ?></td>
                                        <td><?= date('M j, Y g:i A', strtotime($upgrade['upgraded_at'])) ?></td>
                                        <td><?= htmlspecialchars($upgrade['notes'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function upgradeToVersion(version) {
    if (!confirm(`Are you sure you want to upgrade to version ${version}?\n\nThis will:\n- Create a backup of the current system\n- Execute database migrations\n- Update system files\n\nThis process cannot be undone without restoring from backup.`)) {
        return;
    }
    
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Upgrading...';
    
    fetch('?route=admin/executeUpgrade', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            version: version
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('System upgrade completed successfully!\n\n' + data.message);
            location.reload();
        } else {
            alert('Upgrade failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during the upgrade process');
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function executePendingMigrations() {
    if (!confirm('Execute all pending migrations?\n\nThis will modify your database structure. A backup is recommended before proceeding.')) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Executing...';
    
    fetch('?route=admin/executeMigrations', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Migrations executed successfully!\n\n' + data.message);
            location.reload();
        } else {
            alert('Migration execution failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during migration execution');
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function checkIntegrity() {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Checking...';
    
    fetch('?route=admin/checkIntegrity', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('System integrity check completed.\n\nResult: ' + (data.integrity.valid ? 'No issues found' : 'Issues detected'));
            location.reload();
        } else {
            alert('Integrity check failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during integrity check');
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalText;
    });
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
$pageTitle = 'System Upgrades - ConstructLink™';
$pageHeader = 'System Upgrades';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'System Admin', 'url' => '?route=admin'],
    ['title' => 'Upgrades', 'url' => '?route=admin/upgrades']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>