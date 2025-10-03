<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

        <a href="?route=reports" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Reports
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-funnel me-2"></i>Report Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="?route=reports/incidents" class="row g-3">
            <div class="col-md-3">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?= htmlspecialchars($_GET['date_from'] ?? date('Y-m-01')) ?>">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?= htmlspecialchars($_GET['date_to'] ?? date('Y-m-t')) ?>">
            </div>
            <div class="col-md-3">
                <label for="type" class="form-label">Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Types</option>
                    <option value="lost" <?= ($_GET['type'] ?? '') === 'lost' ? 'selected' : '' ?>>Lost</option>
                    <option value="damaged" <?= ($_GET['type'] ?? '') === 'damaged' ? 'selected' : '' ?>>Damaged</option>
                    <option value="stolen" <?= ($_GET['type'] ?? '') === 'stolen' ? 'selected' : '' ?>>Stolen</option>
                    <option value="other" <?= ($_GET['type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="project_id" class="form-label">Project</label>
                <select class="form-select" id="project_id" name="project_id">
                    <option value="">All Projects</option>
                    <?php if (isset($projects) && is_array($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" 
                                    <?= ($_GET['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Generate Report
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                    <i class="bi bi-x-circle me-1"></i>Clear Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <i class="bi bi-list-ul display-4 mb-2"></i>
                <h3><?= $reportData['summary']['total_incidents'] ?? 0 ?></h3>
                <p class="mb-0 small">Total</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <i class="bi bi-search display-4 mb-2"></i>
                <h3><?= $reportData['summary']['under_investigation'] ?? 0 ?></h3>
                <p class="mb-0 small">Investigating</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <i class="bi bi-check-circle display-4 mb-2"></i>
                <h3><?= $reportData['summary']['verified_incidents'] ?? 0 ?></h3>
                <p class="mb-0 small">Verified</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <i class="bi bi-check-all display-4 mb-2"></i>
                <h3><?= $reportData['summary']['resolved_incidents'] ?? 0 ?></h3>
                <p class="mb-0 small">Resolved</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <i class="bi bi-clock display-4 mb-2"></i>
                <h3><?= $reportData['summary']['overdue_incidents'] ?? 0 ?></h3>
                <p class="mb-0 small">Overdue</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <i class="bi bi-speedometer display-4 mb-2"></i>
                <h3><?= number_format($reportData['summary']['avg_resolution_time'] ?? 0, 1) ?></h3>
                <p class="mb-0 small">Avg Days</p>
            </div>
        </div>
    </div>
</div>

<!-- Incident Types Breakdown -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-pie-chart me-2"></i>Incident Types
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-3 text-center">
                        <div class="border-end">
                            <h4 class="text-warning"><?= $reportData['summary']['lost_incidents'] ?? 0 ?></h4>
                            <p class="text-muted mb-0 small">Lost</p>
                        </div>
                    </div>
                    <div class="col-3 text-center">
                        <div class="border-end">
                            <h4 class="text-info"><?= $reportData['summary']['damaged_incidents'] ?? 0 ?></h4>
                            <p class="text-muted mb-0 small">Damaged</p>
                        </div>
                    </div>
                    <div class="col-3 text-center">
                        <div class="border-end">
                            <h4 class="text-danger"><?= $reportData['summary']['stolen_incidents'] ?? 0 ?></h4>
                            <p class="text-muted mb-0 small">Stolen</p>
                        </div>
                    </div>
                    <div class="col-3 text-center">
                        <h4 class="text-secondary"><?= $reportData['summary']['critical_incidents'] ?? 0 ?></h4>
                        <p class="text-muted mb-0 small">Critical</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-graph-up me-2"></i>Resolution Performance
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">Resolution Rate</small>
                        <div class="progress mb-2">
                            <?php 
                            $resolutionRate = ($reportData['summary']['total_incidents'] ?? 0) > 0 ? 
                                ((($reportData['summary']['resolved_incidents'] ?? 0) + ($reportData['summary']['closed_incidents'] ?? 0)) / $reportData['summary']['total_incidents']) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-success" style="width: <?= $resolutionRate ?>%"></div>
                        </div>
                        <small><?= number_format($resolutionRate, 1) ?>%</small>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">On-Time Resolution</small>
                        <div class="progress mb-2">
                            <?php 
                            $onTimeRate = ($reportData['summary']['total_incidents'] ?? 0) > 0 ? 
                                ((($reportData['summary']['total_incidents'] ?? 0) - ($reportData['summary']['overdue_incidents'] ?? 0)) / $reportData['summary']['total_incidents']) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-info" style="width: <?= $onTimeRate ?>%"></div>
                        </div>
                        <small><?= number_format($onTimeRate, 1) ?>%</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Incident Trends Chart -->
<?php if (!empty($incidentTrends)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-graph-up me-2"></i>Incident Trends
        </h6>
    </div>
    <div class="card-body">
        <canvas id="incidentTrendsChart" height="100"></canvas>
    </div>
</div>
<?php endif; ?>

<!-- Incident Details -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Incident Details</h6>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-danger" onclick="filterCritical()">
                <i class="bi bi-exclamation-triangle me-1"></i>Show Critical
            </button>
            <button class="btn btn-outline-warning" onclick="filterOverdue()">
                <i class="bi bi-clock me-1"></i>Show Overdue
            </button>
            <button class="btn btn-outline-secondary" onclick="showAll()">
                <i class="bi bi-list me-1"></i>Show All
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($reportData['incidents'])): ?>
            <div class="table-responsive">
                <table class="table table-hover" id="incidentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Asset</th>
                            <th>Type</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Reported By</th>
                            <th>Date Reported</th>
                            <th class="text-center">Days Open</th>
                            <th class="text-center">Overdue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData['incidents'] as $incident): ?>
                            <tr class="<?= $incident['is_overdue'] ? 'table-warning' : '' ?> <?= $incident['severity'] === 'critical' ? 'table-danger' : '' ?>">
                                <td>
                                    <a href="?route=incidents/view&id=<?= $incident['id'] ?>" class="text-decoration-none">
                                        #<?= $incident['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($incident['asset_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($incident['asset_ref']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $typeClasses = [
                                        'lost' => 'bg-warning',
                                        'damaged' => 'bg-info',
                                        'stolen' => 'bg-danger',
                                        'other' => 'bg-secondary'
                                    ];
                                    $typeClass = $typeClasses[$incident['type']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $typeClass ?>">
                                        <?= ucfirst($incident['type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $severityClasses = [
                                        'low' => 'bg-light text-dark',
                                        'medium' => 'bg-info',
                                        'high' => 'bg-warning',
                                        'critical' => 'bg-danger'
                                    ];
                                    $severityClass = $severityClasses[$incident['severity']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $severityClass ?>">
                                        <?= ucfirst($incident['severity']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'under_investigation' => 'bg-warning',
                                        'verified' => 'bg-info',
                                        'resolved' => 'bg-success',
                                        'closed' => 'bg-secondary'
                                    ];
                                    $statusClass = $statusClasses[$incident['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $incident['status'])) ?>
                                    </span>
                                    <?php if ($incident['is_overdue']): ?>
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($incident['description']) ?>">
                                        <?= htmlspecialchars($incident['description']) ?>
                                    </div>
                                    <?php if ($incident['location']): ?>
                                        <small class="text-muted">Location: <?= htmlspecialchars($incident['location']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <div class="small"><?= htmlspecialchars($incident['reported_by_name']) ?></div>
                                        <?php if ($incident['resolved_by_name']): ?>
                                            <small class="text-muted">Resolved by: <?= htmlspecialchars($incident['resolved_by_name']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="small"><?= date('M j, Y', strtotime($incident['date_reported'])) ?></div>
                                        <small class="text-muted"><?= date('g:i A', strtotime($incident['date_reported'])) ?></small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($incident['days_to_resolution']): ?>
                                        <span class="badge <?= $incident['days_to_resolution'] > 7 ? 'bg-warning' : 'bg-light text-dark' ?>">
                                            <?= $incident['days_to_resolution'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($incident['is_overdue']): ?>
                                        <span class="badge bg-danger">Yes</span>
                                    <?php else: ?>
                                        <span class="text-muted">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=incidents/view&id=<?= $incident['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($incident['status'] === 'under_investigation' && $auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])): ?>
                                            <a href="?route=incidents/investigate&id=<?= $incident['id'] ?>" 
                                               class="btn btn-outline-info" title="Investigate">
                                                <i class="bi bi-search"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (in_array($incident['status'], ['under_investigation', 'verified']) && $auth->hasRole(['System Admin', 'Asset Director'])): ?>
                                            <a href="?route=incidents/resolve&id=<?= $incident['id'] ?>" 
                                               class="btn btn-outline-success" title="Resolve">
                                                <i class="bi bi-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-exclamation-triangle display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No incident data found</h5>
                <p class="text-muted">Try adjusting your filters or date range.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Export report to CSV
function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '?route=reports/export&type=incidents&' + params.toString();
}

// Print report
function printReport() {
    window.print();
}

// Reset filters
function resetFilters() {
    window.location.href = '?route=reports/incidents';
}

// Filter critical incidents
function filterCritical() {
    const table = document.getElementById('incidentsTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const severityCell = row.cells[3];
        const isCritical = severityCell.textContent.trim().toLowerCase() === 'critical';
        
        if (isCritical) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Filter overdue incidents
function filterOverdue() {
    const table = document.getElementById('incidentsTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const hasOverdue = row.classList.contains('table-warning');
        
        if (hasOverdue) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Show all incidents
function showAll() {
    const table = document.getElementById('incidentsTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        row.style.display = '';
    });
}

// Initialize incident trends chart
<?php if (!empty($incidentTrends)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('incidentTrendsChart').getContext('2d');
    const trendsData = <?= json_encode($incidentTrends) ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendsData.map(item => item.month),
            datasets: [
                {
                    label: 'Total Incidents',
                    data: trendsData.map(item => item.incident_count),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                },
                {
                    label: 'Critical Incidents',
                    data: trendsData.map(item => item.critical_count),
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Incident Trends Over Time'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
<?php endif; ?>

// Initialize table interactions
document.addEventListener('DOMContentLoaded', function() {
    // Highlight critical incidents
    const criticalRows = document.querySelectorAll('.table-danger');
    criticalRows.forEach(row => {
        row.style.borderLeft = '4px solid #dc3545';
    });
    
    // Highlight overdue incidents
    const overdueRows = document.querySelectorAll('.table-warning');
    overdueRows.forEach(row => {
        if (!row.classList.contains('table-danger')) {
            row.style.borderLeft = '4px solid #ffc107';
        }
    });
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
