<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-arrow-left-right me-2"></i>
        Transfer Report
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-outline-success" onclick="exportReport()">
                <i class="bi bi-download me-1"></i>Export CSV
            </button>
            <button type="button" class="btn btn-outline-primary" onclick="printReport()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
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
        <form method="GET" action="?route=reports/transfers" class="row g-3">
            <div class="col-md-2">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?= htmlspecialchars($_GET['date_from'] ?? date('Y-m-01')) ?>">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?= htmlspecialchars($_GET['date_to'] ?? date('Y-m-t')) ?>">
            </div>
            <div class="col-md-3">
                <label for="from_project" class="form-label">From Project</label>
                <select class="form-select" id="from_project" name="from_project">
                    <option value="">All Projects</option>
                    <?php if (isset($projects) && is_array($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" 
                                    <?= ($_GET['from_project'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="to_project" class="form-label">To Project</label>
                <select class="form-select" id="to_project" name="to_project">
                    <option value="">All Projects</option>
                    <?php if (isset($projects) && is_array($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" 
                                    <?= ($_GET['to_project'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
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
                <h3><?= $reportData['summary']['total_transfers'] ?? 0 ?></h3>
                <p class="mb-0 small">Total</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <i class="bi bi-clock display-4 mb-2"></i>
                <h3><?= $reportData['summary']['pending_transfers'] ?? 0 ?></h3>
                <p class="mb-0 small">Pending</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <i class="bi bi-check-circle display-4 mb-2"></i>
                <h3><?= $reportData['summary']['approved_transfers'] ?? 0 ?></h3>
                <p class="mb-0 small">Approved</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <i class="bi bi-check-all display-4 mb-2"></i>
                <h3><?= $reportData['summary']['completed_transfers'] ?? 0 ?></h3>
                <p class="mb-0 small">Completed</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <i class="bi bi-x-circle display-4 mb-2"></i>
                <h3><?= $reportData['summary']['canceled_transfers'] ?? 0 ?></h3>
                <p class="mb-0 small">Canceled</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-dark text-white">
            <div class="card-body text-center">
                <i class="bi bi-speedometer display-4 mb-2"></i>
                <h3><?= number_format($reportData['summary']['avg_approval_time'] ?? 0, 1) ?></h3>
                <p class="mb-0 small">Avg Days</p>
            </div>
        </div>
    </div>
</div>

<!-- Transfer Type Breakdown -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-pie-chart me-2"></i>Transfer Types
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 text-center">
                        <div class="border-end">
                            <h4 class="text-primary"><?= $reportData['summary']['permanent_transfers'] ?? 0 ?></h4>
                            <p class="text-muted mb-0">Permanent</p>
                        </div>
                    </div>
                    <div class="col-6 text-center">
                        <h4 class="text-warning"><?= $reportData['summary']['temporary_transfers'] ?? 0 ?></h4>
                        <p class="text-muted mb-0">Temporary</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-graph-up me-2"></i>Performance Metrics
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">Completion Rate</small>
                        <div class="progress mb-2">
                            <?php 
                            $completionRate = ($reportData['summary']['total_transfers'] ?? 0) > 0 ? 
                                (($reportData['summary']['completed_transfers'] ?? 0) / $reportData['summary']['total_transfers']) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-success" style="width: <?= $completionRate ?>%"></div>
                        </div>
                        <small><?= number_format($completionRate, 1) ?>%</small>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Approval Rate</small>
                        <div class="progress mb-2">
                            <?php 
                            $approvalRate = ($reportData['summary']['total_transfers'] ?? 0) > 0 ? 
                                ((($reportData['summary']['approved_transfers'] ?? 0) + ($reportData['summary']['completed_transfers'] ?? 0)) / $reportData['summary']['total_transfers']) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-info" style="width: <?= $approvalRate ?>%"></div>
                        </div>
                        <small><?= number_format($approvalRate, 1) ?>%</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transfer Details -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Transfer Details</h6>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary" onclick="filterPending()">
                <i class="bi bi-clock me-1"></i>Show Pending
            </button>
            <button class="btn btn-outline-secondary" onclick="showAll()">
                <i class="bi bi-list me-1"></i>Show All
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($reportData['transfers'])): ?>
            <div class="table-responsive">
                <table class="table table-hover" id="transfersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Asset</th>
                            <th>From Project</th>
                            <th>To Project</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Initiated By</th>
                            <th>Transfer Date</th>
                            <th class="text-center">Days to Approval</th>
                            <th class="text-center">Days in Process</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData['transfers'] as $transfer): ?>
                            <tr>
                                <td>
                                    <a href="?route=transfers/view&id=<?= $transfer['id'] ?>" class="text-decoration-none">
                                        #<?= $transfer['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($transfer['asset_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($transfer['asset_ref']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($transfer['from_project_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($transfer['to_project_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $transfer['transfer_type'] === 'permanent' ? 'bg-primary' : 'bg-warning' ?>">
                                        <?= ucfirst($transfer['transfer_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'pending' => 'bg-warning',
                                        'approved' => 'bg-info',
                                        'completed' => 'bg-success',
                                        'canceled' => 'bg-secondary'
                                    ];
                                    $statusClass = $statusClasses[$transfer['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= ucfirst($transfer['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <div class="small"><?= htmlspecialchars($transfer['initiated_by_name']) ?></div>
                                        <?php if ($transfer['approved_by_name']): ?>
                                            <small class="text-muted">Approved by: <?= htmlspecialchars($transfer['approved_by_name']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="small"><?= date('M j, Y', strtotime($transfer['transfer_date'])) ?></div>
                                        <small class="text-muted">Created: <?= date('M j', strtotime($transfer['created_at'])) ?></small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($transfer['days_to_approval']): ?>
                                        <span class="badge <?= $transfer['days_to_approval'] > 7 ? 'bg-warning' : 'bg-light text-dark' ?>">
                                            <?= $transfer['days_to_approval'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $transfer['days_in_process'] > 14 ? 'bg-danger' : ($transfer['days_in_process'] > 7 ? 'bg-warning' : 'bg-light text-dark') ?>">
                                        <?= $transfer['days_in_process'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=transfers/view&id=<?= $transfer['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($transfer['status'] === 'pending' && $auth->hasRole(['System Admin', 'Asset Director'])): ?>
                                            <a href="?route=transfers/approve&id=<?= $transfer['id'] ?>" 
                                               class="btn btn-outline-success" title="Approve">
                                                <i class="bi bi-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($transfer['status'] === 'approved' && $auth->hasRole(['System Admin', 'Asset Director', 'Project Manager'])): ?>
                                            <a href="?route=transfers/complete&id=<?= $transfer['id'] ?>" 
                                               class="btn btn-outline-info" title="Complete">
                                                <i class="bi bi-check-all"></i>
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
                <i class="bi bi-arrow-left-right display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No transfer data found</h5>
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
    window.location.href = '?route=reports/export&type=transfers&' + params.toString();
}

// Print report
function printReport() {
    window.print();
}

// Reset filters
function resetFilters() {
    window.location.href = '?route=reports/transfers';
}

// Filter pending transfers
function filterPending() {
    const table = document.getElementById('transfersTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const statusCell = row.cells[5];
        const isPending = statusCell.textContent.trim().toLowerCase() === 'pending';
        
        if (isPending) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Show all transfers
function showAll() {
    const table = document.getElementById('transfersTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        row.style.display = '';
    });
}

// Initialize table interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for quick actions
    const table = document.getElementById('transfersTable');
    if (table) {
        table.addEventListener('click', function(e) {
            if (e.target.closest('.btn-group')) {
                e.stopPropagation();
            }
        });
    }
    
    // Highlight long-running transfers
    const rows = document.querySelectorAll('#transfersTable tbody tr');
    rows.forEach(row => {
        const daysInProcessCell = row.cells[9];
        const badge = daysInProcessCell.querySelector('.badge.bg-danger');
        if (badge) {
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
