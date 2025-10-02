<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-journal-text me-2"></i>
        System Logs
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="?route=admin" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Admin
            </a>
            <button type="button" class="btn btn-outline-primary" onclick="refreshLogs()">
                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
            </button>
        </div>
    </div>
</div>

<!-- Log Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Logs</h6>
                        <h3 class="mb-0"><?= number_format($logStats['total_logs'] ?? 0) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-journal-text display-6"></i>
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
                        <h6 class="card-title">Today's Logs</h6>
                        <h3 class="mb-0"><?= number_format($logStats['today_logs'] ?? 0) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-calendar-day display-6"></i>
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
                        <h6 class="card-title">Error Logs</h6>
                        <h3 class="mb-0"><?= number_format($logStats['error_logs'] ?? 0) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-exclamation-triangle display-6"></i>
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
                        <h6 class="card-title">Active Users</h6>
                        <h3 class="mb-0"><?= number_format($logStats['active_users'] ?? 0) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-people display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-funnel me-2"></i>Log Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="?route=admin/logs" class="row g-3">
            <input type="hidden" name="route" value="admin/logs">
            
            <div class="col-md-2">
                <label for="log_type" class="form-label">Log Type</label>
                <select class="form-select" id="log_type" name="log_type">
                    <option value="">All Types</option>
                    <option value="login" <?= ($_GET['log_type'] ?? '') === 'login' ? 'selected' : '' ?>>Login</option>
                    <option value="logout" <?= ($_GET['log_type'] ?? '') === 'logout' ? 'selected' : '' ?>>Logout</option>
                    <option value="create" <?= ($_GET['log_type'] ?? '') === 'create' ? 'selected' : '' ?>>Create</option>
                    <option value="update" <?= ($_GET['log_type'] ?? '') === 'update' ? 'selected' : '' ?>>Update</option>
                    <option value="delete" <?= ($_GET['log_type'] ?? '') === 'delete' ? 'selected' : '' ?>>Delete</option>
                    <option value="error" <?= ($_GET['log_type'] ?? '') === 'error' ? 'selected' : '' ?>>Error</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="user_id" class="form-label">User</label>
                <select class="form-select" id="user_id" name="user_id">
                    <option value="">All Users</option>
                    <?php if (isset($users) && is_array($users)): ?>
                        <?php foreach ($users as $logUser): ?>
                            <option value="<?= $logUser['id'] ?>" 
                                    <?= ($_GET['user_id'] ?? '') == $logUser['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($logUser['full_name'] ?? 'Unknown') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="date_from" class="form-label">Date From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
            </div>
            
            <div class="col-md-2">
                <label for="date_to" class="form-label">Date To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
            </div>
            
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search in descriptions..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i>
                </button>
                <a href="?route=admin/logs" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Activity Logs Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Activity Logs</h6>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" onclick="exportLogs()">
                <i class="bi bi-file-earmark-excel me-1"></i>Export
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="clearOldLogs()">
                <i class="bi bi-trash me-1"></i>Clear Old
            </button>
            <small class="text-muted align-self-center ms-2">
                Showing <?= count($logs ?? []) ?> of <?= number_format($pagination['total'] ?? 0) ?> logs
            </small>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <div class="text-center py-5">
                <i class="bi bi-journal-text display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No logs found</h5>
                <p class="text-muted">Try adjusting your filters or check back later.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="logsTable">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($log['created_at'])): ?>
                                        <div class="fw-medium"><?= date('M j, Y', strtotime($log['created_at'])) ?></div>
                                        <small class="text-muted"><?= date('g:i:s A', strtotime($log['created_at'])) ?></small>
                                    <?php else: ?>
                                        <div class="fw-medium text-muted">N/A</div>
                                        <small class="text-muted">N/A</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($log['user_name'])): ?>
                                        <div class="fw-medium"><?= htmlspecialchars($log['user_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($log['user_role'] ?? 'Unknown Role') ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $action = $log['action'] ?? 'unknown';
                                    $actionClasses = [
                                        'login' => 'bg-success',
                                        'logout' => 'bg-info',
                                        'create' => 'bg-primary',
                                        'update' => 'bg-warning',
                                        'delete' => 'bg-danger',
                                        'error' => 'bg-danger'
                                    ];
                                    $actionClass = $actionClasses[$action] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $actionClass ?>">
                                        <?= ucfirst($action) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php $description = $log['description'] ?? 'No description'; ?>
                                    <div class="text-truncate" style="max-width: 300px;" 
                                         title="<?= htmlspecialchars($description) ?>">
                                        <?= htmlspecialchars($description) ?>
                                    </div>
                                    <?php if (!empty($log['table_name'])): ?>
                                        <small class="text-muted">
                                            Table: <?= htmlspecialchars($log['table_name']) ?>
                                            <?php if (!empty($log['record_id'])): ?>
                                                | ID: <?= $log['record_id'] ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code class="small"><?= htmlspecialchars($log['ip_address'] ?? 'Unknown') ?></code>
                                </td>
                                <td>
                                    <button class="btn btn-outline-primary btn-sm" 
                                            onclick="showLogDetails(<?= $log['id'] ?? 0 ?>)" 
                                            title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Logs pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=admin/logs&page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?route=admin/logs&page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=admin/logs&page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function refreshLogs() {
    window.location.reload();
}

function exportLogs() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '?route=admin/exportLogs&' + params.toString();
}

function clearOldLogs() {
    if (confirm('This will permanently delete logs older than 90 days. Continue?')) {
        fetch('?route=admin/clearOldLogs', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Old logs cleared successfully.');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while clearing logs');
        });
    }
}

function showLogDetails(logId) {
    fetch('?route=admin/getLogDetails&id=' + logId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const log = data.log;
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Basic Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>ID:</strong></td><td>${log.id}</td></tr>
                                <tr><td><strong>Action:</strong></td><td><span class="badge bg-primary">${log.action}</span></td></tr>
                                <tr><td><strong>User:</strong></td><td>${log.user_name || 'System'}</td></tr>
                                <tr><td><strong>Timestamp:</strong></td><td>${new Date(log.created_at).toLocaleString()}</td></tr>
                                <tr><td><strong>IP Address:</strong></td><td><code>${log.ip_address || 'Unknown'}</code></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Technical Details</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Table:</strong></td><td>${log.table_name || 'N/A'}</td></tr>
                                <tr><td><strong>Record ID:</strong></td><td>${log.record_id || 'N/A'}</td></tr>
                                <tr><td><strong>User Agent:</strong></td><td class="text-break small">${log.user_agent || 'Unknown'}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <h6>Description</h6>
                            <div class="alert alert-light">
                                ${log.description}
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('logDetailsContent').innerHTML = content;
                new bootstrap.Modal(document.getElementById('logDetailsModal')).show();
            } else {
                alert('Error loading log details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading log details');
        });
}

// Auto-submit form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('form[action="?route=admin/logs"]');
    const filterInputs = filterForm.querySelectorAll('select, input[name="date_from"], input[name="date_to"]');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Search with debounce
    let searchTimeout;
    const searchInput = filterForm.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterForm.submit();
            }, 500);
        });
    }
});

// Auto-refresh logs every 30 seconds
setInterval(() => {
    if (!document.querySelector('.modal.show')) { // Don't refresh if modal is open
        const currentUrl = new URL(window.location);
        fetch(currentUrl.toString())
            .then(response => response.text())
            .then(html => {
                // Update only the logs table content
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTable = doc.querySelector('#logsTable tbody');
                const currentTable = document.querySelector('#logsTable tbody');
                
                if (newTable && currentTable) {
                    currentTable.innerHTML = newTable.innerHTML;
                }
            })
            .catch(error => console.error('Error refreshing logs:', error));
    }
}, 30000);
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

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

code {
    background-color: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}

.badge {
    font-size: 0.75em;
}

.modal-body table {
    margin-bottom: 0;
}

.modal-body .alert {
    margin-bottom: 0;
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'System Logs - ConstructLink™';
$pageHeader = 'System Logs';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'System Admin', 'url' => '?route=admin'],
    ['title' => 'Logs', 'url' => '?route=admin/logs']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
