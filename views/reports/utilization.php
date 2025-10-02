<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-graph-up-arrow me-2"></i>
        Asset Utilization Report
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
        <form method="GET" action="?route=reports/utilization" class="row g-3">
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
            <div class="col-md-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">All Categories</option>
                    <?php if (isset($categories) && is_array($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                    <?= ($_GET['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
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
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <i class="bi bi-box display-4 mb-2"></i>
                <h3><?= $reportData['summary']['total_assets'] ?? 0 ?></h3>
                <p class="mb-0">Total Assets</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <i class="bi bi-check-circle display-4 mb-2"></i>
                <h3><?= $reportData['summary']['active_assets'] ?? 0 ?></h3>
                <p class="mb-0">Active Assets</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <i class="bi bi-pause-circle display-4 mb-2"></i>
                <h3><?= $reportData['summary']['idle_assets'] ?? 0 ?></h3>
                <p class="mb-0">Idle Assets</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <i class="bi bi-graph-up display-4 mb-2"></i>
                <h3><?= number_format($reportData['summary']['avg_utilization'] ?? 0, 1) ?></h3>
                <p class="mb-0">Avg Utilization</p>
            </div>
        </div>
    </div>
</div>

<!-- Asset Utilization Data -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Asset Utilization Details</h6>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary" onclick="sortTable('withdrawals')">
                <i class="bi bi-sort-numeric-down me-1"></i>Sort by Usage
            </button>
            <button class="btn btn-outline-secondary" onclick="sortTable('idle')">
                <i class="bi bi-sort-alpha-down me-1"></i>Sort by Idle Time
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($reportData['utilization_data'])): ?>
            <div class="table-responsive">
                <table class="table table-hover" id="utilizationTable">
                    <thead>
                        <tr>
                            <th>Asset</th>
                            <th>Category</th>
                            <th>Project</th>
                            <th>Status</th>
                            <th class="text-center">Withdrawals</th>
                            <th class="text-center">Active</th>
                            <th class="text-center">Avg Days Out</th>
                            <th class="text-center">Last Used</th>
                            <th class="text-center">Days Idle</th>
                            <th class="text-center">Utilization</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData['utilization_data'] as $asset): ?>
                            <tr>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?= htmlspecialchars($asset['name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($asset['ref']) ?></small>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($asset['project_name'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'available' => 'bg-success',
                                        'in_use' => 'bg-warning',
                                        'under_maintenance' => 'bg-danger',
                                        'retired' => 'bg-secondary'
                                    ];
                                    $statusClass = $statusClasses[$asset['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $asset['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary"><?= $asset['withdrawal_count'] ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($asset['active_withdrawals'] > 0): ?>
                                        <span class="badge bg-warning"><?= $asset['active_withdrawals'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?= $asset['avg_usage_days'] ? number_format($asset['avg_usage_days'], 1) : '-' ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($asset['last_used']): ?>
                                        <span title="<?= date('M j, Y', strtotime($asset['last_used'])) ?>">
                                            <?= date('M j', strtotime($asset['last_used'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($asset['days_since_last_use'] > 30): ?>
                                        <span class="text-danger fw-bold"><?= $asset['days_since_last_use'] ?></span>
                                    <?php elseif ($asset['days_since_last_use'] > 14): ?>
                                        <span class="text-warning"><?= $asset['days_since_last_use'] ?></span>
                                    <?php else: ?>
                                        <span><?= $asset['days_since_last_use'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $utilization = $asset['withdrawal_count'] > 0 ? 
                                        min(100, ($asset['withdrawal_count'] / 10) * 100) : 0;
                                    $utilizationClass = $utilization > 70 ? 'text-success' : 
                                                       ($utilization > 30 ? 'text-warning' : 'text-danger');
                                    ?>
                                    <span class="<?= $utilizationClass ?> fw-bold">
                                        <?= number_format($utilization, 0) ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-graph-up display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No utilization data found</h5>
                <p class="text-muted">Try adjusting your filters or date range.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Idle Assets -->
<?php if (!empty($reportData['idle_assets'])): ?>
<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-pause-circle me-2"></i>Idle Assets (Not Used in Period)
        </h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Asset</th>
                        <th>Category</th>
                        <th>Project</th>
                        <th>Status</th>
                        <th class="text-center">Days Since Acquisition</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['idle_assets'] as $asset): ?>
                        <tr>
                            <td>
                                <div>
                                    <div class="fw-medium"><?= htmlspecialchars($asset['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($asset['ref']) ?></small>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    <?= htmlspecialchars($asset['project_name'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-success">
                                    <?= ucfirst(str_replace('_', ' ', $asset['status'])) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($asset['days_since_acquisition'] > 90): ?>
                                    <span class="text-danger fw-bold"><?= $asset['days_since_acquisition'] ?></span>
                                <?php elseif ($asset['days_since_acquisition'] > 30): ?>
                                    <span class="text-warning"><?= $asset['days_since_acquisition'] ?></span>
                                <?php else: ?>
                                    <span><?= $asset['days_since_acquisition'] ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Export report to CSV
function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '?route=reports/export&type=utilization&' + params.toString();
}

// Print report
function printReport() {
    window.print();
}

// Reset filters
function resetFilters() {
    window.location.href = '?route=reports/utilization';
}

// Sort table
function sortTable(type) {
    const table = document.getElementById('utilizationTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        if (type === 'withdrawals') {
            const aVal = parseInt(a.cells[4].textContent);
            const bVal = parseInt(b.cells[4].textContent);
            return bVal - aVal;
        } else if (type === 'idle') {
            const aVal = parseInt(a.cells[8].textContent);
            const bVal = parseInt(b.cells[8].textContent);
            return bVal - aVal;
        }
        return 0;
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects for better UX
    const tableRows = document.querySelectorAll('#utilizationTable tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
        });
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
