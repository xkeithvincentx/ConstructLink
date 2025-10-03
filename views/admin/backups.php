<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

        <a href="?route=admin" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Admin
        </a>
    </div>
</div>

<!-- Backup Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Backups</h6>
                        <h3 class="mb-0"><?= isset($backups) ? count($backups) : 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-archive display-6"></i>
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
                        <h6 class="card-title">Latest Backup</h6>
                        <h6 class="mb-0">
                            <?php 
                            if (!empty($backups)) {
                                echo date('M j, Y', strtotime($backups[0]['created_at']));
                            } else {
                                echo 'None';
                            }
                            ?>
                        </h6>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-calendar-check display-6"></i>
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
                        <h6 class="card-title">Active Schedules</h6>
                        <h3 class="mb-0"><?= isset($schedules) ? count($schedules) : 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock display-6"></i>
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
                        <h6 class="card-title">Storage Used</h6>
                        <h6 class="mb-0">
                            <?php
                            $totalSize = 0;
                            if (!empty($backups)) {
                                foreach ($backups as $backup) {
                                    $totalSize += $backup['file_size'] ?? 0;
                                }
                            }
                            echo $totalSize > 0 ? formatBytes($totalSize) : '0 B';
                            ?>
                        </h6>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-hdd display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Backup Schedules -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="bi bi-calendar3 me-2"></i>Backup Schedules
                </h6>
                <button class="btn btn-outline-primary btn-sm" onclick="showScheduleModal()">
                    <i class="bi bi-plus me-1"></i>Add Schedule
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($schedules)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        No backup schedules configured. Create a schedule for automatic backups.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Frequency</th>
                                    <th>Time</th>
                                    <th>Description</th>
                                    <th>Last Run</th>
                                    <th>Created By</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?= ucfirst($schedule['frequency']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('g:i A', strtotime($schedule['scheduled_time'])) ?></td>
                                        <td><?= htmlspecialchars($schedule['description'] ?? '') ?></td>
                                        <td>
                                            <?= $schedule['last_run'] ? date('M j, Y g:i A', strtotime($schedule['last_run'])) : 'Never' ?>
                                        </td>
                                        <td><?= htmlspecialchars($schedule['created_by_name'] ?? 'System') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $schedule['is_active'] ? 'success' : 'secondary' ?>">
                                                <?= $schedule['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline-danger btn-sm" onclick="deleteSchedule(<?= $schedule['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
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

<!-- Backup History -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Backup History
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($backups)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        No backups found. Create your first backup to get started.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Created</th>
                                    <th>Created By</th>
                                    <th>Verified</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-earmark-zip me-2"></i>
                                            <?= htmlspecialchars($backup['filename']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $backup['type'] === 'manual' ? 'primary' : 'secondary' ?>">
                                                <?= ucfirst($backup['type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($backup['file_exists']): ?>
                                                <?= $backup['file_size_formatted'] ?>
                                            <?php else: ?>
                                                <span class="text-danger">File Missing</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('M j, Y g:i A', strtotime($backup['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($backup['created_by_name'] ?? 'System') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $backup['verified'] ? 'success' : 'warning' ?>">
                                                <?= $backup['verified'] ? 'Yes' : 'No' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($backup['file_exists']): ?>
                                                    <button class="btn btn-outline-success" onclick="restoreBackup(<?= $backup['id'] ?>)" title="Restore">
                                                        <i class="bi bi-arrow-clockwise"></i>
                                                    </button>
                                                    <button class="btn btn-outline-info" onclick="downloadBackup('<?= htmlspecialchars($backup['filename']) ?>')" title="Download">
                                                        <i class="bi bi-download"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-outline-danger" onclick="deleteBackup(<?= $backup['id'] ?>)" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
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

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Backup Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleForm">
                    <div class="mb-3">
                        <label for="frequency" class="form-label">Frequency</label>
                        <select class="form-select" id="frequency" required>
                            <option value="">Select frequency...</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="scheduledTime" class="form-label">Time</label>
                        <input type="time" class="form-control" id="scheduledTime" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" rows="2" placeholder="Optional description for this backup schedule"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="createSchedule()">Create Schedule</button>
            </div>
        </div>
    </div>
</div>

<script>
function createManualBackup() {
    const description = prompt('Enter backup description (optional):');
    if (description === null) return; // User cancelled
    
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Creating...';
    
    fetch('?route=admin/createBackup', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            description: description || 'Manual backup'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Backup created successfully!\n\nFile: ' + data.backup_file + '\nSize: ' + formatBytes(data.size));
            location.reload();
        } else {
            alert('Backup creation failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during backup creation');
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function showScheduleModal() {
    const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
    modal.show();
}

function createSchedule() {
    const form = document.getElementById('scheduleForm');
    const formData = new FormData(form);
    
    const frequency = document.getElementById('frequency').value;
    const time = document.getElementById('scheduledTime').value;
    const description = document.getElementById('description').value;
    
    if (!frequency || !time) {
        alert('Please fill in all required fields');
        return;
    }
    
    // Implementation would go here
    alert('Schedule creation functionality will be implemented');
}

function restoreBackup(backupId) {
    if (!confirm('Are you sure you want to restore from this backup?\n\nThis will:\n- Create a backup of the current state\n- Replace the current database with the backup\n- This action cannot be easily undone\n\nProceed with restore?')) {
        return;
    }
    
    // Implementation would go here
    alert('Backup restore functionality will be implemented');
}

function deleteBackup(backupId) {
    if (!confirm('Are you sure you want to delete this backup?\n\nThis action cannot be undone.')) {
        return;
    }
    
    // Implementation would go here
    alert('Backup deletion functionality will be implemented');
}

function downloadBackup(filename) {
    // Create a temporary link to download the backup file
    const link = document.createElement('a');
    link.href = '?route=admin/downloadBackup&file=' + encodeURIComponent(filename);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 B';

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];

    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
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

.btn:disabled {
    opacity: 0.6;
}

.table th {
    border-top: none;
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Backup Management - ConstructLink™';
$pageHeader = 'Backup Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'System Admin', 'url' => '?route=admin'],
    ['title' => 'Backups', 'url' => '?route=admin/backups']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>