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
        <form method="GET" action="?route=reports/maintenance" class="row g-3">
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
                    <option value="preventive" <?= ($_GET['type'] ?? '') === 'preventive' ? 'selected' : '' ?>>Preventive</option>
                    <option value="corrective" <?= ($_GET['type'] ?? '') === 'corrective' ? 'selected' : '' ?>>Corrective</option>
                    <option value="emergency" <?= ($_GET['type'] ?? '') === 'emergency' ? 'selected' : '' ?>>Emergency</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="scheduled" <?= ($_GET['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="in_progress" <?= ($_GET['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed" <?= ($_GET['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="canceled" <?= ($_GET['status'] ?? '') === 'canceled' ? 'selected' : '' ?>>Canceled</option>
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
                <h3><?= $reportData['summary']['total_maintenance'] ?? 0 ?></h3>
                <p class="mb-0 small">Total</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <i class="bi bi-calendar display-4 mb-2"></i>
                <h3><?= $reportData['summary']['scheduled_maintenance'] ?? 0 ?></h3>
                <p class="mb-0 small">Scheduled</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <i class="bi bi-gear display-4 mb-2"></i>
                <h3><?= $reportData['summary']['in_progress_maintenance'] ?? 0 ?></h3>
                <p class="mb-0 small">In Progress</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <i class="bi bi-check-circle display-4 mb-2"></i>
                <h3><?= $reportData['summary']['completed_maintenance'] ?? 0 ?></h3>
                <p class="mb-0 small">Completed</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <i class="bi bi-exclamation-triangle display-4 mb-2"></i>
                <h3><?= $reportData['summary']['overdue_maintenance'] ?? 0 ?></h3>
                <p class="mb-0 small">Overdue</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <i class="bi bi-lightning display-4 mb-2"></i>
                <h3><?= $reportData['summary']['emergency_maintenance'] ?? 0 ?></h3>
                <p class="mb-0 small">Emergency</p>
            </div>
        </div>
    </div>
</div>

<!-- Cost Analysis -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-currency-dollar me-2"></i>Cost Analysis
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-12 mb-3">
                        <h5 class="text-primary"><?= formatCurrency($reportData['summary']['total_estimated_cost'] ?? 0) ?></h5>
                        <small class="text-muted">Estimated Cost</small>
                    </div>
                    <div class="col-12 mb-3">
                        <h5 class="text-success"><?= formatCurrency($reportData['summary']['total_actual_cost'] ?? 0) ?></h5>
                        <small class="text-muted">Actual Cost</small>
                    </div>
                    <div class="col-12">
                        <?php $variance = $reportData['summary']['cost_variance'] ?? 0; ?>
                        <h5 class="<?= $variance > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= $variance > 0 ? '+' : '' ?><?= formatCurrency($variance) ?>
                        </h5>
                        <small class="text-muted">Variance</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-pie-chart me-2"></i>Maintenance Types
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-4 text-center">
                        <div class="border-end">
                            <h4 class="text-success"><?= $reportData['summary']['preventive_maintenance'] ?? 0 ?></h4>
                            <p class="text-muted mb-0">Preventive</p>
                            <small class="text-muted">
                                <?= ($reportData['summary']['total_maintenance'] ?? 0) > 0 ? 
                                    number_format((($reportData['summary']['preventive_maintenance'] ?? 0) / $reportData['summary']['total_maintenance']) * 100, 1) : 0 ?>%
                            </small>
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="border-end">
                            <h4 class="text-warning"><?= $reportData['summary']['corrective_maintenance'] ?? 0 ?></h4>
                            <p class="text-muted mb-0">Corrective</p>
                            <small class="text-muted">
                                <?= ($reportData['summary']['total_maintenance'] ?? 0) > 0 ? 
                                    number_format((($reportData['summary']['corrective_maintenance'] ?? 0) / $reportData['summary']['total_maintenance']) * 100, 1) : 0 ?>%
                            </small>
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <h4 class="text-danger"><?= $reportData['summary']['emergency_maintenance'] ?? 0 ?></h4>
                        <p class="text-muted mb-0">Emergency</p>
                        <small class="text-muted">
                            <?= ($reportData['summary']['total_maintenance'] ?? 0) > 0 ? 
                                number_format((($reportData['summary']['emergency_maintenance'] ?? 0) / $reportData['summary']['total_maintenance']) * 100, 1) : 0 ?>%
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Details -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Maintenance Details</h6>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-danger" onclick="filterOverdue()">
                <i class="bi bi-exclamation-triangle me-1"></i>Show Overdue
            </button>
            <button class="btn btn-outline-warning" onclick="filterEmergency()">
                <i class="bi bi-lightning me-1"></i>Show Emergency
            </button>
            <button class="btn btn-outline-secondary" onclick="showAll()">
                <i class="bi bi-list me-1"></i>Show All
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($reportData['maintenance'])): ?>
            <div class="table-responsive">
                <table class="table table-hover" id="maintenanceTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Asset</th>
                            <th>Type</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Scheduled Date</th>
                            <th>Completed Date</th>
                            <th>Assigned To</th>
                            <th class="text-end">Est. Cost</th>
                            <th class="text-end">Actual Cost</th>
                            <th class="text-center">Days Variance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData['maintenance'] as $maintenance): ?>
                            <tr class="<?= $maintenance['days_overdue'] > 0 ? 'table-warning' : '' ?>">
                                <td>
                                    <a href="?route=maintenance/view&id=<?= $maintenance['id'] ?>" class="text-decoration-none">
                                        #<?= $maintenance['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($maintenance['asset_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($maintenance['asset_ref']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $typeClasses = [
                                        'preventive' => 'bg-success',
                                        'corrective' => 'bg-warning',
                                        'emergency' => 'bg-danger'
                                    ];
                                    $typeClass = $typeClasses[$maintenance['type']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $typeClass ?>">
                                        <?= ucfirst($maintenance['type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $priorityClasses = [
                                        'low' => 'bg-light text-dark',
                                        'medium' => 'bg-info',
                                        'high' => 'bg-warning',
                                        'urgent' => 'bg-danger'
                                    ];
                                    $priorityClass = $priorityClasses[$maintenance['priority']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $priorityClass ?>">
                                        <?= ucfirst($maintenance['priority']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'scheduled' => 'bg-warning',
                                        'in_progress' => 'bg-info',
                                        'completed' => 'bg-success',
                                        'canceled' => 'bg-secondary'
                                    ];
                                    $statusClass = $statusClasses[$maintenance['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $maintenance['status'])) ?>
                                    </span>
                                    <?php if ($maintenance['days_overdue'] > 0): ?>
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <div class="small <?= $maintenance['days_overdue'] > 0 ? 'text-danger fw-bold' : '' ?>">
                                            <?= date('M j, Y', strtotime($maintenance['scheduled_date'])) ?>
                                        </div>
                                        <?php if ($maintenance['days_overdue'] > 0): ?>
                                            <small class="text-danger"><?= $maintenance['days_overdue'] ?> days overdue</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($maintenance['completed_date']): ?>
                                        <div class="small"><?= date('M j, Y', strtotime($maintenance['completed_date'])) ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">Not completed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($maintenance['assigned_to'] ?? 'Not assigned') ?>
                                </td>
                                <td class="text-end">
                                    <?= formatCurrency($maintenance['estimated_cost'] ?? 0) ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($maintenance['actual_cost']): ?>
                                        <?= formatCurrency($maintenance['actual_cost']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($maintenance['days_variance'] !== null): ?>
                                        <span class="<?= $maintenance['days_variance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                            <?= $maintenance['days_variance'] > 0 ? '+' : '' ?><?= $maintenance['days_variance'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=maintenance/view&id=<?= $maintenance['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($maintenance['status'] === 'scheduled' && $auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman'])): ?>
                                            <a href="?route=maintenance/start&id=<?= $maintenance['id'] ?>" 
                                               class="btn btn-outline-info" title="Start">
                                                <i class="bi bi-play"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($maintenance['status'] === 'in_progress' && $auth->hasRole(['System Admin', 'Asset Director', 'Warehouseman'])): ?>
                                            <a href="?route=maintenance/complete&id=<?= $maintenance['id'] ?>" 
                                               class="btn btn-outline-success" title="Complete">
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
                <i class="bi bi-tools display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No maintenance data found</h5>
                <p class="text-muted">Try adjusting your filters or date range.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Export report to CSV
function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '?route=reports/export&type=maintenance&' + params.toString();
}

// Print report
function printReport() {
    window.print();
}

// Reset filters
function resetFilters() {
    window.location.href = '?route=reports/maintenance';
}

// Filter overdue maintenance
function filterOverdue() {
    const table = document.getElementById('maintenanceTable');
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

// Filter emergency maintenance
function filterEmergency() {
    const table = document.getElementById('maintenanceTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const typeCell = row.cells[2];
        const isEmergency = typeCell.textContent.trim().toLowerCase() === 'emergency';
        
        if (isEmergency) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Show all maintenance
function showAll() {
    const table = document.getElementById('maintenanceTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        row.style.display = '';
    });
}

// Initialize table interactions
document.addEventListener('DOMContentLoaded', function() {
    // Highlight overdue maintenance
    const overdueRows = document.querySelectorAll('.table-warning');
    overdueRows.forEach(row => {
        row.style.borderLeft = '4px solid #ffc107';
    });
    
    // Highlight emergency maintenance
    const emergencyRows = document.querySelectorAll('#maintenanceTable tbody tr');
    emergencyRows.forEach(row => {
        const typeCell = row.cells[2];
        const isEmergency = typeCell.querySelector('.badge.bg-danger');
        if (isEmergency && isEmergency.textContent.trim().toLowerCase() === 'emergency') {
            row.style.borderLeft = '4px solid #dc3545';
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
